<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('marketing_teams', function (Blueprint $table) {
            $table->string('commission_type')->default('percentage')->after('target_omset')->comment('Tipe Komisi: percentage, nominal, qty');
            $table->decimal('commission_rate', 8, 2)->default(0)->after('commission_type')->comment('Persentase Komisi dari Nilai Penjualan Dilepas (%)');
            $table->decimal('reward_fixed_nominal', 15, 2)->default(0)->after('commission_rate')->comment('Bonus/Komisi Nominal Tetap jika Target Tercapai (Rp)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marketing_teams', function (Blueprint $table) {
            $table->dropColumn(['commission_type', 'commission_rate', 'reward_fixed_nominal']);
        });
    }
};
