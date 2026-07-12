<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spk_items', function (Blueprint $table) {
            $columns = [
                'jasa_konveksi', 'jasa_potong', 'jasa_printing', 'jasa_jahit', 'jasa_labsas',
                'kebutuhan_kain', 'biaya_kain', 'biaya_sbs', 'biaya_pitta',
                'biaya_kancing', 'biaya_kancing_kait', 'biaya_karet', 'biaya_plastik', 'biaya_string',
                'biaya_bordir', 'biaya_servis', 'biaya_finishing', 'biaya_pengiriman'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('spk_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('spk_items', function (Blueprint $table) {
            $table->decimal('jasa_konveksi', 15, 2)->default(0);
            $table->decimal('jasa_potong', 15, 2)->default(0);
            $table->decimal('jasa_printing', 15, 2)->default(0);
            $table->decimal('jasa_jahit', 15, 2)->default(0);
            $table->decimal('jasa_labsas', 15, 2)->default(0);
            $table->decimal('kebutuhan_kain', 10, 2)->default(0);
            $table->decimal('biaya_kain', 15, 2)->default(0);
            $table->decimal('biaya_sbs', 15, 2)->default(0);
            $table->decimal('biaya_pitta', 15, 2)->default(0);
            $table->decimal('biaya_kancing', 15, 2)->default(0);
            $table->decimal('biaya_kancing_kait', 15, 2)->default(0);
            $table->decimal('biaya_karet', 15, 2)->default(0);
            $table->decimal('biaya_plastik', 15, 2)->default(0);
            $table->decimal('biaya_string', 15, 2)->default(0);
            $table->decimal('biaya_bordir', 15, 2)->default(0);
            $table->decimal('biaya_servis', 15, 2)->default(0);
            $table->decimal('biaya_finishing', 15, 2)->default(0);
            $table->decimal('biaya_pengiriman', 15, 2)->default(0);
        });
    }
};
