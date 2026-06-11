<?php
 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('employee_id')->nullable(); // Opsional: karyawan penerima gaji/biaya
            $table->string('title');                              // Nama/deskripsi singkat biaya
            $table->string('category');                           // salary | rent | utilities | other dll.
            $table->string('payment_source')->default('kas_besar'); // kas_besar | kas_kecil
            $table->decimal('amount', 15, 2)->default(0);         // Nominal biaya
            $table->date('expense_date');                         // Tanggal transaksi biaya
            $table->text('description')->nullable();              // Detail tambahan
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
