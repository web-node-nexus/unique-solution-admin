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
        $this->dropIndexQuietly('brands', 'brands_name_unique');
        $this->dropIndexQuietly('brands', 'unique_category_brand');

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
                    DB::table('brands')->where('id', $extraId)->update([
                        'name' => $dupe->name.' #'.$extraId,
                    ]);
                }
            }
        }

        if (! $this->indexExists('brands', 'unique_category_brand')) {
            Schema::table('brands', function (Blueprint $table) {
                // Same name OK in different categories; blocked twice in the same category.
                $table->unique(['category_id', 'name'], 'unique_category_brand');
            });
        }
    }

    public function down(): void
    {
        $this->dropIndexQuietly('brands', 'unique_category_brand');
    }

    /**
     * Drop any unique index that is only on brands.name (legacy global uniqueness).
     */
    private function dropLegacyNameUniques(): void
    {
        foreach ($this->indexes('brands') as $index) {
            $name = (string) ($index['name'] ?? '');
            $columns = array_values($index['columns'] ?? []);
            $unique = (bool) ($index['unique'] ?? false);

            if ($unique && $columns === ['name']) {
                $this->dropIndexQuietly('brands', $name);
            }
        }
    }

    private function dropIndexQuietly(string $table, string $index): void
    {
        if ($index === '' || ! $this->indexExists($table, $index)) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $blueprint) use ($index) {
                $blueprint->dropUnique($index);
            });
        } catch (\Throwable) {
            try {
                // SQLite / some drivers prefer column-array form.
                Schema::table($table, function (Blueprint $blueprint) use ($index) {
                    if ($index === 'unique_category_brand') {
                        $blueprint->dropUnique(['category_id', 'name']);
                    } elseif ($index === 'brands_name_unique' || str_ends_with($index, '_name_unique')) {
                        $blueprint->dropUnique(['name']);
                    }
                });
            } catch (\Throwable) {
                // Already gone or unsupported.
            }
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        foreach ($this->indexes($table) as $row) {
            if (($row['name'] ?? null) === $index) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<array{name?: string, columns?: list<string>, unique?: bool}>
     */
    private function indexes(string $table): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }

        try {
            /** @var list<array{name?: string, columns?: list<string>, unique?: bool}> $indexes */
            $indexes = Schema::getIndexes($table);

            return $indexes;
        } catch (\Throwable) {
            return [];
        }
    }
};
