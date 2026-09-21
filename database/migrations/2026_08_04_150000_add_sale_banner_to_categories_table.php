<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (! Schema::hasColumn('categories', 'sale_banner')) {
                $table->string('sale_banner')->nullable()->after('image');
            }
            if (! Schema::hasColumn('categories', 'sale_title')) {
                $table->string('sale_title')->nullable()->after('sale_banner');
            }
            if (! Schema::hasColumn('categories', 'sale_subtitle')) {
                $table->string('sale_subtitle')->nullable()->after('sale_title');
            }
            if (! Schema::hasColumn('categories', 'sale_active')) {
                $table->boolean('sale_active')->default(false)->after('sale_subtitle');
            }
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $cols = array_values(array_filter(
                ['sale_banner', 'sale_title', 'sale_subtitle', 'sale_active'],
                fn (string $col) => Schema::hasColumn('categories', $col)
            ));
            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });
    }
};
