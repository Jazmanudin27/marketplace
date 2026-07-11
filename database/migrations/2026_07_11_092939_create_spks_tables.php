<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('no_produksi')->nullable();
            $table->string('no_spk')->unique();
            $table->date('tanggal');
            $table->date('deadline');
            $table->string('pemesan')->nullable();
            $table->string('no_hp_pemesan')->nullable();
            $table->string('instansi')->nullable();
            $table->text('tambahan')->nullable();
            $table->string('image_url')->nullable();
            $table->unsignedBigInteger('penginput_id')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('penginput_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('spk_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('spk_id');
            $table->unsignedBigInteger('master_product_id')->nullable();

            // Identitas
            $table->string('nama_produk');
            $table->string('sku')->nullable();
            $table->string('sku_induk')->nullable();
            $table->string('ukuran')->nullable();
            $table->integer('quantity')->default(1);
            $table->string('penjahit')->nullable();
            $table->string('alur_proses')->default('Langsung Jahit');

            // Biaya Jasa
            $table->decimal('jasa_konveksi', 15, 2)->default(0);
            $table->decimal('jasa_potong', 15, 2)->default(0);
            $table->decimal('jasa_printing', 15, 2)->default(0);
            $table->decimal('jasa_jahit', 15, 2)->default(0);
            $table->decimal('jasa_labsas', 15, 2)->default(0);

            // Biaya Bahan
            $table->decimal('kebutuhan_kain', 10, 2)->default(0); // dalam meter
            $table->decimal('biaya_kain', 15, 2)->default(0);
            $table->decimal('biaya_sbs', 15, 2)->default(0);
            $table->decimal('biaya_pitta', 15, 2)->default(0);

            // Komponen Kecil
            $table->decimal('biaya_kancing', 15, 2)->default(0);
            $table->decimal('biaya_kancing_kait', 15, 2)->default(0);
            $table->decimal('biaya_karet', 15, 2)->default(0);
            $table->decimal('biaya_plastik', 15, 2)->default(0);
            $table->decimal('biaya_string', 15, 2)->default(0);

            // Finishing
            $table->decimal('biaya_bordir', 15, 2)->default(0);
            $table->decimal('biaya_servis', 15, 2)->default(0);
            $table->decimal('biaya_finishing', 15, 2)->default(0);

            // Lain-lain
            $table->decimal('biaya_pengiriman', 15, 2)->default(0);

            // Total HPP (dihitung otomatis)
            $table->decimal('hpp', 15, 2)->default(0);

            $table->timestamps();

            $table->foreign('spk_id')->references('id')->on('spks')->onDelete('cascade');
            $table->foreign('master_product_id')->references('id')->on('master_products')->onDelete('set null');
        });

        // Tabel untuk biaya/jasa tambahan dinamis per item
        Schema::create('spk_item_extras', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('spk_item_id');
            $table->string('keterangan');
            $table->decimal('nominal', 15, 2)->default(0);
            $table->timestamps();

            $table->foreign('spk_item_id')->references('id')->on('spk_items')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spk_item_extras');
        Schema::dropIfExists('spk_items');
        Schema::dropIfExists('spks');
    }
};
