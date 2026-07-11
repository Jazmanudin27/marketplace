<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add missing columns to spk_items table if they don't exist
        Schema::table('spk_items', function (Blueprint $table) {
            if (!Schema::hasColumn('spk_items', 'alur_proses')) {
                $table->string('alur_proses')->default('Langsung Jahit')->after('penjahit');
            }
            if (!Schema::hasColumn('spk_items', 'status')) {
                $table->string('status')->default('Belum Mulai')->after('alur_proses');
            }

            // Biaya Jasa
            if (!Schema::hasColumn('spk_items', 'jasa_konveksi')) {
                $table->decimal('jasa_konveksi', 15, 2)->default(0)->after('status');
            }
            if (!Schema::hasColumn('spk_items', 'jasa_potong')) {
                $table->decimal('jasa_potong', 15, 2)->default(0)->after('jasa_konveksi');
            }
            if (!Schema::hasColumn('spk_items', 'jasa_printing')) {
                $table->decimal('jasa_printing', 15, 2)->default(0)->after('jasa_potong');
            }
            if (!Schema::hasColumn('spk_items', 'jasa_jahit')) {
                $table->decimal('jasa_jahit', 15, 2)->default(0)->after('jasa_printing');
            }
            if (!Schema::hasColumn('spk_items', 'jasa_labsas')) {
                $table->decimal('jasa_labsas', 15, 2)->default(0)->after('jasa_jahit');
            }

            // Biaya Bahan
            if (!Schema::hasColumn('spk_items', 'kebutuhan_kain')) {
                $table->decimal('kebutuhan_kain', 10, 2)->default(0)->after('jasa_labsas');
            }
            if (!Schema::hasColumn('spk_items', 'biaya_kain')) {
                $table->decimal('biaya_kain', 15, 2)->default(0)->after('kebutuhan_kain');
            }
            if (!Schema::hasColumn('spk_items', 'biaya_sbs')) {
                $table->decimal('biaya_sbs', 15, 2)->default(0)->after('biaya_kain');
            }
            if (!Schema::hasColumn('spk_items', 'biaya_pitta')) {
                $table->decimal('biaya_pitta', 15, 2)->default(0)->after('biaya_sbs');
            }

            // Komponen Kecil
            if (!Schema::hasColumn('spk_items', 'biaya_kancing')) {
                $table->decimal('biaya_kancing', 15, 2)->default(0)->after('biaya_pitta');
            }
            if (!Schema::hasColumn('spk_items', 'biaya_kancing_kait')) {
                $table->decimal('biaya_kancing_kait', 15, 2)->default(0)->after('biaya_kancing');
            }
            if (!Schema::hasColumn('spk_items', 'biaya_karet')) {
                $table->decimal('biaya_karet', 15, 2)->default(0)->after('biaya_kancing_kait');
            }
            if (!Schema::hasColumn('spk_items', 'biaya_plastik')) {
                $table->decimal('biaya_plastik', 15, 2)->default(0)->after('biaya_karet');
            }
            if (!Schema::hasColumn('spk_items', 'biaya_string')) {
                $table->decimal('biaya_string', 15, 2)->default(0)->after('biaya_plastik');
            }

            // Finishing
            if (!Schema::hasColumn('spk_items', 'biaya_bordir')) {
                $table->decimal('biaya_bordir', 15, 2)->default(0)->after('biaya_string');
            }
            if (!Schema::hasColumn('spk_items', 'biaya_servis')) {
                $table->decimal('biaya_servis', 15, 2)->default(0)->after('biaya_bordir');
            }
            if (!Schema::hasColumn('spk_items', 'biaya_finishing')) {
                $table->decimal('biaya_finishing', 15, 2)->default(0)->after('biaya_servis');
            }

            // Lain-lain
            if (!Schema::hasColumn('spk_items', 'biaya_pengiriman')) {
                $table->decimal('biaya_pengiriman', 15, 2)->default(0)->after('biaya_finishing');
            }

            // HPP
            if (!Schema::hasColumn('spk_items', 'hpp')) {
                $table->decimal('hpp', 15, 2)->default(0)->after('biaya_pengiriman');
            }
        });

        // 2. Create spk_item_extras table if it doesn't exist
        if (!Schema::hasTable('spk_item_extras')) {
            Schema::create('spk_item_extras', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('spk_item_id');
                $table->string('keterangan');
                $table->decimal('nominal', 15, 2)->default(0);
                $table->timestamps();

                $table->foreign('spk_item_id')->references('id')->on('spk_items')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('spk_item_extras');

        Schema::table('spk_items', function (Blueprint $table) {
            $columns = [
                'alur_proses', 'status', 'jasa_konveksi', 'jasa_potong', 'jasa_printing', 'jasa_jahit', 'jasa_labsas',
                'kebutuhan_kain', 'biaya_kain', 'biaya_sbs', 'biaya_pitta', 'biaya_kancing', 'biaya_kancing_kait',
                'biaya_karet', 'biaya_plastik', 'biaya_string', 'biaya_bordir', 'biaya_servis', 'biaya_finishing',
                'biaya_pengiriman', 'hpp'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('spk_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
