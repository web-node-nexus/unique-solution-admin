<?php

namespace App\Services;

use App\Models\InventoryLog;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryService
{
    /**
     * Adjust variant stock and write an inventory log.
     *
     * Types:
     * - stock_in: add quantity
     * - stock_out: subtract quantity
     * - adjustment: set absolute stock to quantity
     */
    public function adjust(
        ProductVariant $variant,
        string $type,
        int $quantity,
        string $reason,
        User $user
    ): InventoryLog {
        $type = strtolower($type);
        $allowed = ['stock_in', 'stock_out', 'adjustment'];

        if (! in_array($type, $allowed, true)) {
            throw new InvalidArgumentException("Invalid inventory adjustment type [{$type}].");
        }

        if ($quantity < 0) {
            throw new InvalidArgumentException('Quantity must be zero or greater.');
        }

        return DB::transaction(function () use ($variant, $type, $quantity, $reason, $user) {
            $variant = ProductVariant::query()->lockForUpdate()->findOrFail($variant->id);
            $before = (int) $variant->stock_quantity;

            $after = match ($type) {
                'stock_in' => $before + $quantity,
                'stock_out' => $before - $quantity,
                'adjustment' => $quantity,
            };

            if ($after < 0) {
                throw new InvalidArgumentException(
                    "Insufficient stock for SKU [{$variant->sku}]. Available: {$before}."
                );
            }

            $variant->update(['stock_quantity' => $after]);

            $logQuantity = match ($type) {
                'stock_in' => $quantity,
                'stock_out' => $quantity,
                'adjustment' => $after - $before,
            };

            $log = InventoryLog::query()->create([
                'variant_id' => $variant->id,
                'type' => $type,
                'quantity' => $logQuantity,
                'reason' => $reason,
                'created_by' => $user->id,
            ]);

            activity_log(
                $type,
                'inventory',
                "SKU {$variant->sku}: {$before} → {$after} ({$reason})"
            );

            return $log;
        });
    }
}
