<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('token')->unique();
            $table->string('platform')->default('android'); // android|ios|web
            $table->string('device_name')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });

        Schema::table('app_notifications', function (Blueprint $table) {
            $table->string('type')->default('announcement')->after('id'); // announcement|sale|system
            $table->nullableMorphs('related');
            $table->unsignedInteger('fcm_success_count')->default(0)->after('sent_at');
            $table->unsignedInteger('fcm_failure_count')->default(0)->after('fcm_success_count');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->boolean('notify_users')->default(false)->after('status');
            $table->timestamp('notification_sent_at')->nullable()->after('notify_users');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['notify_users', 'notification_sent_at']);
        });

        Schema::table('app_notifications', function (Blueprint $table) {
            $table->dropMorphs('related');
            $table->dropColumn(['type', 'fcm_success_count', 'fcm_failure_count']);
        });

        Schema::dropIfExists('device_tokens');
    }
};
