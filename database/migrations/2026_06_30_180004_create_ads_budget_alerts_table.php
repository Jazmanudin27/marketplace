<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ads_budget_alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('ads_campaign_id');
            $table->unsignedBigInteger('ads_budget_rule_id');
            $table->string('level')->default('warning')->comment('info, warning, critical');
            $table->text('message');
            $table->json('context')->nullable()->comment('Snapshot nilai ROAS, spend saat alert terpicu');
            $table->boolean('is_read')->default(false);
            $table->timestamp('triggered_at');
            $table->timestamps();

            $table->index(['tenant_id', 'is_read']);
            $table->index(['tenant_id', 'triggered_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ads_budget_alerts');
    }
};
