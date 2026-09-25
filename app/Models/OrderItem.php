<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_variant_id',
        'product_name_snapshot',
        'variant_details_snapshot',
        'quantity',
        'price',
        'subtotal',
        'device_units',
    ];

    protected function casts(): array
    {
        return [
            'variant_details_snapshot' => 'array',
            'device_units' => 'array',
            'quantity' => 'integer',
            'price' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    /**
     * Flatten device slots for this line (one per quantity unit).
     *
     * @return list<array{imei: string, serial_number: string}>
     */
    public function deviceSlots(): array
    {
        $qty = max(1, (int) $this->quantity);
        $saved = is_array($this->device_units) ? array_values($this->device_units) : [];
        $slots = [];

        for ($i = 0; $i < $qty; $i++) {
            $row = $saved[$i] ?? [];
            $slots[] = [
                'imei' => trim((string) ($row['imei'] ?? '')),
                'serial_number' => trim((string) ($row['serial_number'] ?? $row['serial'] ?? '')),
            ];
        }

        return $slots;
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
