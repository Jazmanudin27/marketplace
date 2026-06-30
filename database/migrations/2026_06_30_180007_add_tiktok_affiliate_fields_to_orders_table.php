<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('tiktok_creator_name')->nullable()->after('capi_sent_at')
                ->comment('Nama Kreator / Affiliate TikTok yang menghasilkan order');
            $table->string('tiktok_creator_id')->nullable()->after('tiktok_creator_name')
                ->comment('TikTok Creator ID / Affiliate ID');
            $table->decimal('affiliate_commission', 15, 2)->default(0)->after('tiktok_creator_id')
                ->comment('Nominal komisi affiliate yang dibayarkan');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['tiktok_creator_name', 'tiktok_creator_id', 'affiliate_commission']);
        });
    }
};
