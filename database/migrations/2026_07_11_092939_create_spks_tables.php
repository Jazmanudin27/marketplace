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
            $table->string('nama_produk');
            $table->string('sku')->nullable();
            $table->string('sku_induk')->nullable();
            $table->string('ukuran')->nullable();
            $table->integer('quantity');
            $table->string('penjahit')->nullable();
            $table->decimal('biaya_bahan', 15, 2)->default(0);
            $table->decimal('ongkos_jahit', 15, 2)->default(0);
            $table->decimal('ongkos_printing', 15, 2)->default(0);
            $table->decimal('hpp', 15, 2)->default(0);
            $table->string('alur_proses')->default('Langsung Jahit');
            $table->timestamps();

            $table->foreign('spk_id')->references('id')->on('spks')->onDelete('cascade');
            $table->foreign('master_product_id')->references('id')->on('master_products')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spk_items');
        Schema::dropIfExists('spks');
    }
};
