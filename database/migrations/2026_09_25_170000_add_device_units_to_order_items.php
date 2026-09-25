<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('order_items')) {
            return;
        }

        if (! Schema::hasColumn('order_items', 'device_units')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->json('device_units')->nullable()->after('subtotal');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('order_items') && Schema::hasColumn('order_items', 'device_units')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropColumn('device_units');
            });
        }
    }
};
