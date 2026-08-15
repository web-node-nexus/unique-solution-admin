<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('sale_banner')->nullable()->after('image');
            $table->string('sale_title')->nullable()->after('sale_banner');
            $table->string('sale_subtitle')->nullable()->after('sale_title');
            $table->boolean('sale_active')->default(false)->after('sale_subtitle');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['sale_banner', 'sale_title', 'sale_subtitle', 'sale_active']);
        });
    }
};
