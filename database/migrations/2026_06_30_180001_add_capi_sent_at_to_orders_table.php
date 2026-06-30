<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('capi_sent_at')->nullable()->after('ads_campaign_id')
                ->comment('Timestamp saat event Purchase dikirim ke TikTok CAPI');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('capi_sent_at');
        });
    }
};
