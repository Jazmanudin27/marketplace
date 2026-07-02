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
        // 1. Add balance column to customers
        Schema::table('customers', function (Blueprint $table) {
            $table->decimal('balance', 15, 2)->default(0)->after('address');
        });

        // 2. Create reseller_balance_transactions table
        Schema::create('reseller_balance_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('customer_id');
            $table->enum('type', ['in', 'out']);
            $table->decimal('amount', 15, 2);
            $table->string('description');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reseller_balance_transactions');

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('balance');
        });
    }
};
