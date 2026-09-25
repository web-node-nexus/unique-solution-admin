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

        if (! Schema::hasColumn('order_items', 'policy_titles_snapshot')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->json('policy_titles_snapshot')->nullable()->after('device_units');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('order_items') && Schema::hasColumn('order_items', 'policy_titles_snapshot')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropColumn('policy_titles_snapshot');
            });
        }
    }
};
