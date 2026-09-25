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
        'policy_titles_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'variant_details_snapshot' => 'array',
            'device_units' => 'array',
            'policy_titles_snapshot' => 'array',
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

    /**
     * Policy titles linked to this line's product (selected brand policies).
     *
     * @return list<string>
     */
    public function policyTitles(): array
    {
        if (is_array($this->policy_titles_snapshot) && $this->policy_titles_snapshot !== []) {
            return array_values(array_filter(array_map(
                static fn ($t) => trim((string) $t),
                $this->policy_titles_snapshot
            )));
        }

        $product = $this->variant?->product;

        if (! $product) {
            return [];
        }

        $product->loadMissing('brandPolicies');

        return $product->brandPolicies
            ->pluck('title')
            ->map(static fn ($t) => trim((string) $t))
            ->filter()
            ->values()
            ->all();
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
