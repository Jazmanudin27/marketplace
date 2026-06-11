<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fund_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('source'); // kas_besar | kas_kecil
            $table->string('destination'); // kas_besar | kas_kecil
            $table->decimal('amount', 15, 2)->default(0); // Nominal transfer
            $table->date('transfer_date'); // Tanggal transfer
            $table->text('description')->nullable(); // Deskripsi tambahan
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fund_transfers');
    }
};
