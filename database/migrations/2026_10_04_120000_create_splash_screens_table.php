<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('splash_screens')) {
            Schema::create('splash_screens', function (Blueprint $table) {
                $table->id();
                $table->boolean('is_default')->default(false);
                $table->string('image_path');
                $table->date('starts_on')->nullable();
                $table->date('ends_on')->nullable();
                $table->boolean('is_enabled')->default(true);
                $table->timestamps();
            });
        }

        $source = public_path('assets/splash/default.jpg');
        $relative = 'splashes/default.jpg';
        $dest = storage_path('app/public/'.$relative);
        if (is_file($source)) {
            if (! is_dir(dirname($dest))) {
                mkdir(dirname($dest), 0755, true);
            }
            if (! is_file($dest)) {
                copy($source, $dest);
            }
        }

        $hasDefault = DB::table('splash_screens')->where('is_default', true)->exists();
        if (! $hasDefault) {
            DB::table('splash_screens')->insert([
                'is_default' => true,
                'image_path' => $relative,
                'starts_on' => null,
                'ends_on' => null,
                'is_enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('splash_screens');
    }
};
