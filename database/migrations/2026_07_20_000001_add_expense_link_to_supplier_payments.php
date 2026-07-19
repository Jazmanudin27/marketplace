<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah kolom expense_id di supplier_payments agar bisa link ke jurnal kas
        Schema::table('supplier_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('expense_id')->nullable()->after('created_by');
            $table->string('payment_source')->nullable()->after('payment_method'); // kas_besar | kas_kecil (khusus tunai)
            $table->foreign('expense_id')->references('id')->on('expenses')->onDelete('set null');
        });

        // Tambah kategori pembelian_supplier ke expenses (via check constraint tidak didukung MySQL,
        // jadi kita expand di model saja — tidak perlu migrasi kolom category)
    }

    public function down(): void
    {
        Schema::table('supplier_payments', function (Blueprint $table) {
            $table->dropForeign(['expense_id']);
            $table->dropColumn(['expense_id', 'payment_source']);
        });
    }
};
