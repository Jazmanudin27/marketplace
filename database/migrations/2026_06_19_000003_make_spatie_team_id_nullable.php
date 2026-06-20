<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Migration ini awalnya dimaksudkan untuk membuat tenant_id nullable di tabel Spatie Permission.
 * Namun setelah evaluasi, MySQL PRIMARY KEY tidak bisa berisi NULL.
 *
 * Solusi yang digunakan: Super Admin menggunakan tenant_id = 0 (nol/global) bukan NULL.
 * Migration ini dipertahankan sebagai no-op agar urutan migration tidak berubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        // No-op: Dihandle di seeder dengan tenant_id = 0 untuk Super Admin global.
        // MySQL PRIMARY KEY tidak mendukung NULL, sehingga pendekatan NULL tidak bisa digunakan.
    }

    public function down(): void
    {
        // No-op
    }
};
