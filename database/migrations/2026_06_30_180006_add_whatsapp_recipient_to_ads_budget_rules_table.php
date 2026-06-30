<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ads_budget_rules', function (Blueprint $table) {
            $table->string('whatsapp_recipient')->nullable()->after('action')
                ->comment('Nomor WhatsApp penerima alert untuk rule ini (format E.164)');
        });
    }

    public function down(): void
    {
        Schema::table('ads_budget_rules', function (Blueprint $table) {
            $table->dropColumn('whatsapp_recipient');
        });
    }
};
