<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('utm_campaign')->nullable()->after('financial_breakdown');
            $table->string('utm_source')->nullable()->after('utm_campaign');
            $table->unsignedBigInteger('ads_campaign_id')->nullable()->after('utm_source');

            $table->foreign('ads_campaign_id')->references('id')->on('ads_campaigns')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['ads_campaign_id']);
            $table->dropColumn(['utm_campaign', 'utm_source', 'ads_campaign_id']);
        });
    }
};
