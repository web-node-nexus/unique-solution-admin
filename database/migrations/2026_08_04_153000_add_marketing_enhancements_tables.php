<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('used_count');
            $table->string('image')->nullable()->after('expiry_date');
            $table->string('title')->nullable()->after('code');
            $table->text('description')->nullable()->after('title');
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->string('link_type')->default('none'); // none|category|product|url|coupon
            $table->string('link_value')->nullable();
            $table->boolean('status')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['status', 'starts_at', 'ends_at']);
        });

        Schema::create('app_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->string('image')->nullable();
            $table->string('link_type')->default('none'); // none|category|product|url|sale|coupon
            $table->string('link_value')->nullable();
            $table->string('audience')->default('all'); // all|customers
            $table->string('status')->default('draft'); // draft|sent
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_notifications');
        Schema::dropIfExists('sales');

        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'image', 'title', 'description']);
        });
    }
};
