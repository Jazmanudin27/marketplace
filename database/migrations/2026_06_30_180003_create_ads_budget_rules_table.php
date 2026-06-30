<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ads_budget_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('ads_campaign_id');
            $table->string('condition')
                ->comment('roas_below, roas_above, spend_exceeds_daily, spend_exceeds_total');
            $table->decimal('threshold_value', 10, 2);
            $table->string('action')->default('notify')
                ->comment('notify, pause_suggestion, increase_suggestion');
            $table->string('name')->nullable()->comment('Label rule yang mudah dibaca');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'ads_campaign_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ads_budget_rules');
    }
};
