<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('store_id')->nullable()->comment('Jika null, berlaku untuk semua toko');
            $table->string('name');                                  // Nama voucher (tampilan internal)
            $table->string('code')->unique();                       // Kode voucher unik
            $table->enum('type', ['percentage', 'fixed']);          // Jenis diskon
            $table->decimal('value', 15, 2);                        // Nilai diskon (% atau Rp)
            $table->decimal('min_purchase', 15, 2)->default(0);     // Min. belanja untuk menggunakan voucher
            $table->decimal('max_discount', 15, 2)->nullable();     // Maks. potongan (untuk tipe %)
            $table->timestamp('start_date')->nullable();                        // Mulai berlaku
            $table->timestamp('end_date')->nullable();                          // Berakhir
            $table->unsignedInteger('usage_limit')->nullable();     // Maks. penggunaan (null = tidak terbatas)
            $table->unsignedInteger('used_count')->default(0);      // Sudah berapa kali digunakan
            $table->boolean('is_active')->default(true);
            $table->string('marketplace_voucher_id')->nullable();   // ID voucher di Shopee (setelah sync)
            $table->string('marketplace_status')->nullable();       // Status di Shopee: upcoming/ongoing/expired/cancelled
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('store_id')->references('id')->on('stores')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
