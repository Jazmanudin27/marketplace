<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Daftar tahapan produksi per SPK (custom, bisa tambah/hapus bebas)
        Schema::create('spk_proses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('spk_id');
            $table->string('nama_proses');   // "Potong", "Jahit", "Printing", "QC", dll
            $table->unsignedTinyInteger('urutan')->default(1);
            $table->timestamps();

            $table->foreign('spk_id')->references('id')->on('spks')->onDelete('cascade');
        });

        // Qty progress per item per tahapan
        Schema::create('spk_item_progres', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('spk_item_id');
            $table->unsignedBigInteger('spk_proses_id');
            $table->unsignedInteger('qty_done')->default(0);
            $table->timestamps();

            $table->foreign('spk_item_id')->references('id')->on('spk_items')->onDelete('cascade');
            $table->foreign('spk_proses_id')->references('id')->on('spk_proses')->onDelete('cascade');
            $table->unique(['spk_item_id', 'spk_proses_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spk_item_progres');
        Schema::dropIfExists('spk_proses');
    }
};
