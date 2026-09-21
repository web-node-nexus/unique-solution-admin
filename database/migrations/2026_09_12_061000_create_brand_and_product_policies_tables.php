<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('brand_policies')) {
            Schema::create('brand_policies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('brand_id')->constrained('brands')->cascadeOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('icon')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('product_policies')) {
            Schema::create('product_policies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('icon')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('products', 'use_brand_policies')) {
            Schema::table('products', function (Blueprint $table) {
                $table->boolean('use_brand_policies')->default(false)->after('warranty_info');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'use_brand_policies')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('use_brand_policies');
            });
        }

        Schema::dropIfExists('product_policies');
        Schema::dropIfExists('brand_policies');
    }
};
