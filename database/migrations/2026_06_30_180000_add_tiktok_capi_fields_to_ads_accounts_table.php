<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ads_accounts', function (Blueprint $table) {
            $table->string('pixel_id')->nullable()->after('access_token')
                ->comment('TikTok Pixel ID dari Ads Manager');
            $table->text('events_access_token')->nullable()->after('pixel_id')
                ->comment('TikTok Events API Access Token untuk CAPI');
            $table->string('advertiser_id')->nullable()->after('events_access_token')
                ->comment('TikTok Ads Manager Advertiser ID untuk Custom Audience');
        });
    }

    public function down(): void
    {
        Schema::table('ads_accounts', function (Blueprint $table) {
            $table->dropColumn(['pixel_id', 'events_access_token', 'advertiser_id']);
        });
    }
};
