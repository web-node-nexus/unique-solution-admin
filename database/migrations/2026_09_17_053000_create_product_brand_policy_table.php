<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_brand_policy', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('brand_policy_id')->constrained('brand_policies')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'brand_policy_id']);
        });

        // Migrate products that previously used “all brand policies”
        if (Schema::hasColumn('products', 'use_brand_policies')) {
            $products = DB::table('products')
                ->where('use_brand_policies', true)
                ->whereNotNull('brand_id')
                ->get(['id', 'brand_id']);

            foreach ($products as $product) {
                $policyIds = DB::table('brand_policies')
                    ->where('brand_id', $product->brand_id)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->pluck('id');

                foreach ($policyIds as $index => $policyId) {
                    DB::table('product_brand_policy')->insert([
                        'product_id' => $product->id,
                        'brand_policy_id' => $policyId,
                        'sort_order' => $index,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_brand_policy');
    }
};
