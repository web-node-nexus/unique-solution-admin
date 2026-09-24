<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop legacy global unique on brands.name (if present).
        $this->dropLegacyNameUniques();
        $this->dropIndexIfExists('brands', 'brands_name_unique');
        $this->dropIndexIfExists('brands', 'unique_category_brand');

        // Ensure every brand has a category_id for the composite unique (use first pivot mapping).
        if (Schema::hasTable('brand_category') && Schema::hasColumn('brands', 'category_id')) {
            $rows = DB::table('brands')->whereNull('category_id')->get(['id']);
            foreach ($rows as $row) {
                $categoryId = DB::table('brand_category')
                    ->where('brand_id', $row->id)
                    ->orderBy('category_id')
                    ->value('category_id');
                if ($categoryId) {
                    DB::table('brands')->where('id', $row->id)->update(['category_id' => $categoryId]);
                }
            }
        }

        // Resolve duplicate (category_id, name) pairs before adding the constraint.
        if (Schema::hasColumn('brands', 'category_id')) {
            $dupes = DB::table('brands')
                ->select('category_id', 'name', DB::raw('COUNT(*) as c'), DB::raw('MIN(id) as keep_id'))
                ->whereNotNull('category_id')
                ->groupBy('category_id', 'name')
                ->havingRaw('COUNT(*) > 1')
                ->get();

            foreach ($dupes as $dupe) {
                $extras = DB::table('brands')
                    ->where('category_id', $dupe->category_id)
                    ->where('name', $dupe->name)
                    ->where('id', '!=', $dupe->keep_id)
                    ->pluck('id');

                foreach ($extras as $extraId) {
                    // Rename extras so the unique index can be applied safely.
                    DB::table('brands')->where('id', $extraId)->update([
                        'name' => $dupe->name.' #'.$extraId,
                    ]);
                }
            }
        }

        Schema::table('brands', function (Blueprint $table) {
            // Same name allowed in different categories; blocked twice in the same category.
            if (! $this->indexExists('brands', 'unique_category_brand')) {
                $table->unique(['category_id', 'name'], 'unique_category_brand');
            }
        });
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            if ($this->indexExists('brands', 'unique_category_brand')) {
                $table->dropUnique('unique_category_brand');
            }
        });
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        if (! $this->indexExists($table, $index)) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $blueprint) use ($index) {
                $blueprint->dropUnique($index);
            });
        } catch (\Throwable) {
            // Index may already be gone or named differently on some environments.
        }
    }

    /**
     * Drop any unique index that is only on brands.name (legacy global uniqueness).
     */
    private function dropLegacyNameUniques(): void
    {
        $database = DB::getDatabaseName();
        $indexes = DB::select(
            'SELECT INDEX_NAME, COUNT(*) AS col_count,
                    SUM(CASE WHEN COLUMN_NAME = ? THEN 1 ELSE 0 END) AS name_cols
             FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND NON_UNIQUE = 0
             GROUP BY INDEX_NAME',
            ['name', $database, 'brands']
        );

        foreach ($indexes as $index) {
            if ((int) $index->col_count === 1 && (int) $index->name_cols === 1) {
                $this->dropIndexIfExists('brands', (string) $index->INDEX_NAME);
            }
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        $database = DB::getDatabaseName();
        $row = DB::selectOne(
            'SELECT COUNT(*) AS c FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $index]
        );

        return (int) ($row->c ?? 0) > 0;
    }
};
