<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('bank_name'); // e.g. BCA, Mandiri, BRI, BNI, BSI, Kas Tunai, QRIS, etc.
            $table->string('account_number')->nullable(); // e.g. 1234567890
            $table->string('account_name')->nullable(); // e.g. PT ASPARTECH ERP
            $table->string('branch_name')->nullable(); // e.g. KCP Sudirman
            $table->decimal('initial_balance', 15, 2)->default(0);
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
