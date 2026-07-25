<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spk_item_pickups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('spk_item_id');
            $table->unsignedInteger('qty_diambil');
            $table->dateTime('tanggal_ambil');
            $table->string('nama_pengambil');
            $table->unsignedBigInteger('pemberi_id');
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->foreign('spk_item_id')->references('id')->on('spk_items')->onDelete('cascade');
            $table->foreign('pemberi_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spk_item_pickups');
    }
};
