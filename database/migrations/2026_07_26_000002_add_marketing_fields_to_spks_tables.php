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
        // Add new fields to spks table
        Schema::table('spks', function (Blueprint $table) {
            if (!Schema::hasColumn('spks', 'no_pesanan')) {
                $table->string('no_pesanan')->nullable()->after('no_spk');
            }
            if (!Schema::hasColumn('spks', 'tahap_saat_ini')) {
                $table->string('tahap_saat_ini')->default('DRAFT')->after('is_urgent');
            }
            if (!Schema::hasColumn('spks', 'nama_pic')) {
                $table->string('nama_pic')->nullable()->after('penginput_id');
            }
            if (!Schema::hasColumn('spks', 'referensi_klien_url')) {
                $table->string('referensi_klien_url')->nullable()->after('image_url');
            }
            if (!Schema::hasColumn('spks', 'mockup_url')) {
                $table->string('mockup_url')->nullable()->after('referensi_klien_url');
            }
            if (!Schema::hasColumn('spks', 'link_file_mentah')) {
                $table->text('link_file_mentah')->nullable()->after('mockup_url');
            }
            if (!Schema::hasColumn('spks', 'sku_kain')) {
                $table->string('sku_kain')->nullable()->after('link_file_mentah');
            }
        });

        // Add new operational fields to spk_items table
        Schema::table('spk_items', function (Blueprint $table) {
            if (!Schema::hasColumn('spk_items', 'sku_kain')) {
                $table->string('sku_kain')->nullable()->after('sku');
            }
            if (!Schema::hasColumn('spk_items', 'est_kain')) {
                $table->decimal('est_kain', 10, 2)->default(0)->after('sku_kain');
            }
            if (!Schema::hasColumn('spk_items', 'kain_pakai')) {
                $table->decimal('kain_pakai', 10, 2)->default(0)->after('est_kain');
            }
            if (!Schema::hasColumn('spk_items', 'kain_sisa')) {
                $table->decimal('kain_sisa', 10, 2)->default(0)->after('kain_pakai');
            }
            if (!Schema::hasColumn('spk_items', 'vendor_kancing')) {
                $table->string('vendor_kancing')->nullable()->after('penjahit');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spks', function (Blueprint $table) {
            $cols = ['no_pesanan', 'tahap_saat_ini', 'nama_pic', 'referensi_klien_url', 'mockup_url', 'link_file_mentah', 'sku_kain'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('spks', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('spk_items', function (Blueprint $table) {
            $cols = ['sku_kain', 'est_kain', 'kain_pakai', 'kain_sisa', 'vendor_kancing'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('spk_items', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
