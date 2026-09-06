<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event', 80);
            $table->string('screen', 120)->nullable();
            $table->json('properties')->nullable();
            $table->string('session_id', 64)->nullable()->index();
            $table->string('platform', 20)->nullable();
            $table->string('app_version', 40)->nullable();
            $table->string('device_id', 120)->nullable()->index();
            $table->timestamp('occurred_at')->nullable()->index();
            $table->timestamps();

            $table->index(['event', 'created_at']);
        });

        Schema::create('crash_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('level', 20)->default('error');
            $table->string('message', 500);
            $table->text('stack')->nullable();
            $table->string('screen', 120)->nullable();
            $table->json('context')->nullable();
            $table->string('platform', 20)->nullable();
            $table->string('app_version', 40)->nullable();
            $table->string('device_id', 120)->nullable()->index();
            $table->boolean('is_fatal')->default(false);
            $table->timestamps();

            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crash_reports');
        Schema::dropIfExists('analytics_events');
    }
};
