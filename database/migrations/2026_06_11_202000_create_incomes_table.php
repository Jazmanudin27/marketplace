<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incomes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('title'); // Deskripsi/sumber pemasukan
            $table->string('category'); // investment | refund | services | other
            $table->string('payment_destination')->default('kas_besar'); // kas_besar | kas_kecil
            $table->decimal('amount', 15, 2)->default(0); // Nominal pemasukan
            $table->date('income_date'); // Tanggal pemasukan
            $table->text('description')->nullable(); // Detail tambahan
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incomes');
    }
};
