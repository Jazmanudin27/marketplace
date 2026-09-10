<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spk_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('spk_id');
            $table->string('payment_number')->index();
            $table->date('payment_date');
            $table->decimal('amount', 15, 2);
            $table->string('payment_source')->default('kas_besar'); // kas_besar, kas_kecil, bank
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->string('recipient_name')->nullable(); // Nama vendor, penjahit, atau penerima dana
            $table->string('payment_type')->default('biaya_produksi'); // biaya_produksi, bahan, tambahan, umum
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('expense_id')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('spk_id')->references('id')->on('spks')->onDelete('cascade');
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('expense_id')->references('id')->on('expenses')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spk_payments');
    }
};
