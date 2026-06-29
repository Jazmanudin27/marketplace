<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ads_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('platform'); // 'meta', 'google', 'tiktok', 'manual'
            $table->string('account_name');
            $table->string('account_id')->nullable();
            $table->text('access_token')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        Schema::create('ads_campaigns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('ads_account_id');
            $table->string('campaign_id_platform')->nullable();
            $table->string('name');
            $table->decimal('target_omzet', 15, 2)->default(0);
            $table->decimal('target_roas', 8, 2)->default(1.00);
            $table->string('status')->default('ACTIVE');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('ads_account_id')->references('id')->on('ads_accounts')->onDelete('cascade');
        });

        Schema::create('ads_performance_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('ads_campaign_id');
            $table->date('date');
            $table->decimal('ad_spend', 15, 2)->default(0);
            $table->integer('clicks')->default(0);
            $table->integer('impressions')->default(0);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('ads_campaign_id')->references('id')->on('ads_campaigns')->onDelete('cascade');
            $table->unique(['ads_campaign_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ads_performance_logs');
        Schema::dropIfExists('ads_campaigns');
        Schema::dropIfExists('ads_accounts');
    }
};
