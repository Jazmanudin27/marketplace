<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ads_campaigns', function (Blueprint $table) {
            $table->decimal('target_cpo', 15, 2)->nullable()->after('target_roas');
        });
    }

    public function down(): void
    {
        Schema::table('ads_campaigns', function (Blueprint $table) {
            $table->dropColumn('target_cpo');
        });
    }
};
