<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_notifications', function (Blueprint $table) {
            $table->boolean('is_active')->default(false)->after('status');
            $table->timestamp('starts_at')->nullable()->after('is_active');
            $table->timestamp('ends_at')->nullable()->after('starts_at');
            $table->index(['is_active', 'status', 'starts_at', 'ends_at'], 'app_notifications_publish_idx');
        });

        DB::table('app_notifications')
            ->where('status', 'sent')
            ->update(['is_active' => true]);
    }

    public function down(): void
    {
        Schema::table('app_notifications', function (Blueprint $table) {
            $table->dropIndex('app_notifications_publish_idx');
            $table->dropColumn(['is_active', 'starts_at', 'ends_at']);
        });
    }
};
