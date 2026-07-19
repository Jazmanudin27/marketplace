<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_payments', function (Blueprint $table) {
            // Status approval
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending')->after('expense_id');
            // Info bank untuk transfer/giro
            $table->string('bank_name')->nullable()->after('reference_number');    // Nama bank tujuan
            $table->string('account_number')->nullable()->after('bank_name');      // No. rekening
            $table->string('account_name')->nullable()->after('account_number');   // Nama pemilik rekening
            // Approval tracking
            $table->unsignedBigInteger('approved_by')->nullable()->after('approval_status');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->unsignedBigInteger('rejected_by')->nullable()->after('approved_at');
            $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            $table->text('rejection_reason')->nullable()->after('rejected_at');

            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('rejected_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('supplier_payments', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['rejected_by']);
            $table->dropColumn([
                'approval_status', 'bank_name', 'account_number', 'account_name',
                'approved_by', 'approved_at', 'rejected_by', 'rejected_at', 'rejection_reason',
            ]);
        });
    }
};
