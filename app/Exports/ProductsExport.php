<?php

namespace App\Exports;

use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    public function collection(): Collection
    {
        return Product::query()
            ->with(['category:id,name', 'brand:id,name', 'variants'])
            ->orderBy('name')
            ->get();
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Slug',
            'Category',
            'Brand',
            'Base Price',
            'Status',
            'Featured',
            'Variants',
            'Total Stock',
            'Created At',
        ];
    }

    /**
     * @param  Product  $product
     * @return list<mixed>
     */
    public function map($product): array
    {
        return [
            $product->id,
            $product->name,
            $product->slug,
            $product->category?->name,
            $product->brand?->name,
            (float) $product->base_price,
            $product->status,
            $product->is_featured ? 'Yes' : 'No',
            $product->variants->count(),
            (int) $product->variants->sum('stock_quantity'),
            optional($product->created_at)->format('Y-m-d H:i'),
        ];
    }
}
