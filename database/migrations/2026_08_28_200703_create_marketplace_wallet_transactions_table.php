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
        Schema::create('marketplace_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('store_id')->index();
            $table->string('transaction_id')->index();
            $table->dateTime('transaction_date')->index();
            $table->string('type')->nullable();
            $table->text('description')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('direction', 5); // 'in' or 'out'
            $table->decimal('current_balance', 15, 2)->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();

            // Unique constraint to prevent duplicate syncs
            $table->unique(['store_id', 'transaction_id'], 'uq_store_tx');

            // Foreign keys
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('store_id')->references('id')->on('stores')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_wallet_transactions');
    }
};
