<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tiktok_audiences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('ads_account_id');
            $table->string('tiktok_audience_id')->nullable()
                ->comment('ID audience dari TikTok Ads Manager');
            $table->string('name');
            $table->string('type')->default('purchasers')
                ->comment('purchasers, high_value_customers, all_buyers');
            $table->integer('customer_count')->default(0);
            $table->string('status')->default('pending')
                ->comment('pending, active, failed, uploading');
            $table->timestamp('last_synced_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'ads_account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tiktok_audiences');
    }
};
