<?php

namespace App\Exports;

use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StockValuationExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    public function collection(): Collection
    {
        return ProductVariant::query()
            ->with(['product:id,name,status', 'attributeValues'])
            ->orderBy('sku')
            ->get();
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'SKU',
            'Product',
            'Variant',
            'Stock Quantity',
            'Unit Price',
            'Discount Price',
            'Stock Value',
            'Low Stock Threshold',
            'Status',
        ];
    }

    /**
     * @param  ProductVariant  $variant
     * @return list<mixed>
     */
    public function map($variant): array
    {
        $unitPrice = (float) ($variant->discount_price ?? $variant->price);
        $stock = (int) $variant->stock_quantity;

        return [
            $variant->sku,
            $variant->product?->name,
            $variant->attributeValues->pluck('value')->implode(' / ') ?: 'Default',
            $stock,
            (float) $variant->price,
            $variant->discount_price !== null ? (float) $variant->discount_price : null,
            round($stock * $unitPrice, 2),
            (int) $variant->low_stock_threshold,
            $variant->status ? 'Active' : 'Inactive',
        ];
    }
}
