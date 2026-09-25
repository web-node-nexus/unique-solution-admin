<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['brand_policies', 'product_policies'] as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'description')) {
                continue;
            }

            $driver = Schema::getConnection()->getDriverName();

            if ($driver === 'mysql' || $driver === 'mariadb') {
                DB::statement("ALTER TABLE `{$table}` MODIFY `description` LONGTEXT NULL");
            } elseif ($driver === 'pgsql') {
                DB::statement("ALTER TABLE {$table} ALTER COLUMN description TYPE TEXT");
            } else {
                // SQLite TEXT already stores large content; keep a no-op friendly change attempt.
                try {
                    Schema::table($table, function (Blueprint $blueprint) {
                        $blueprint->longText('description')->nullable()->change();
                    });
                } catch (\Throwable) {
                    // Already unlimited / change unsupported.
                }
            }
        }
    }

    public function down(): void
    {
        foreach (['brand_policies', 'product_policies'] as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'description')) {
                continue;
            }

            $driver = Schema::getConnection()->getDriverName();

            if ($driver === 'mysql' || $driver === 'mariadb') {
                DB::statement("ALTER TABLE `{$table}` MODIFY `description` TEXT NULL");
            }
        }
    }
};
