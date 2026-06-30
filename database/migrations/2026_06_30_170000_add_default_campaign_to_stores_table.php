<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->unsignedBigInteger('default_campaign_id')->nullable()->after('status');
            $table->foreign('default_campaign_id')->references('id')->on('ads_campaigns')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropForeign(['default_campaign_id']);
            $table->dropColumn('default_campaign_id');
        });
    }
};
