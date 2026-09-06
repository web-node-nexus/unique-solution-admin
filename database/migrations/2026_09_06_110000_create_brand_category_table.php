<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brand_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('brands')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['brand_id', 'category_id']);
        });

        $now = now();
        $rows = DB::table('brands')
            ->whereNotNull('category_id')
            ->get(['id', 'category_id']);

        foreach ($rows as $row) {
            DB::table('brand_category')->insertOrIgnore([
                'brand_id' => $row->id,
                'category_id' => $row->category_id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->attachKnownMultiCategoryBrands();
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_category');
    }

    /**
     * Brands that sell across verticals (Samsung, LG, …).
     */
    private function attachKnownMultiCategoryBrands(): void
    {
        $map = [
            'Samsung' => [
                'Mobile Phones', 'Smartphones', 'Android Phones',
                'Televisions', 'Refrigerators', 'Washing Machines',
                'Air Conditioners', 'Split ACs', 'Window ACs', 'Microwaves',
            ],
            'LG' => [
                'Mobile Phones', 'Smartphones', 'Android Phones',
                'Televisions', 'Refrigerators', 'Washing Machines',
                'Air Conditioners', 'Split ACs', 'Window ACs', 'Microwaves',
            ],
            'Sony' => ['Mobile Phones', 'Smartphones', 'Televisions'],
            'Apple' => ['Mobile Phones', 'Smartphones'],
            'Mi' => ['Mobile Phones', 'Smartphones', 'Android Phones', 'Televisions'],
            'OnePlus' => ['Mobile Phones', 'Smartphones', 'Android Phones'],
            'Vivo' => ['Mobile Phones', 'Smartphones', 'Android Phones'],
            'Oppo' => ['Mobile Phones', 'Smartphones', 'Android Phones'],
            'Realme' => ['Mobile Phones', 'Smartphones', 'Android Phones'],
            'Dell' => ['Laptops'],
            'Whirlpool' => ['Refrigerators', 'Washing Machines', 'Microwaves'],
            'Voltas' => ['Air Conditioners', 'Split ACs', 'Window ACs'],
            'Daikin' => ['Air Conditioners', 'Split ACs', 'Window ACs'],
            'Lloyd' => [
                'Televisions', 'Refrigerators', 'Washing Machines',
                'Air Conditioners', 'Split ACs', 'Window ACs',
            ],
            'Panasonic' => [
                'Televisions', 'Refrigerators', 'Washing Machines',
                'Air Conditioners', 'Split ACs', 'Window ACs', 'Microwaves',
            ],
        ];

        $categories = DB::table('categories')->pluck('id', 'name');
        $now = now();

        foreach ($map as $brandName => $categoryNames) {
            $brandId = DB::table('brands')->where('name', $brandName)->value('id');
            if (! $brandId) {
                continue;
            }

            foreach ($categoryNames as $categoryName) {
                $categoryId = $categories[$categoryName] ?? null;
                if (! $categoryId) {
                    continue;
                }

                DB::table('brand_category')->insertOrIgnore([
                    'brand_id' => $brandId,
                    'category_id' => $categoryId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
};
