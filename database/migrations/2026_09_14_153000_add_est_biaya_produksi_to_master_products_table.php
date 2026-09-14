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
        Schema::table('master_products', function (Blueprint $table) {
            if (!Schema::hasColumn('master_products', 'est_biaya_produksi')) {
                $table->decimal('est_biaya_produksi', 15, 2)->default(0)->after('est_kain');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_products', function (Blueprint $table) {
            if (Schema::hasColumn('master_products', 'est_biaya_produksi')) {
                $table->dropColumn('est_biaya_produksi');
            }
        });
    }
};
