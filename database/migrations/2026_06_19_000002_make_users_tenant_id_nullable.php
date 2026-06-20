<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Buat kolom tenant_id pada tabel users menjadi nullable.
 * Ini diperlukan agar Super Admin (yang tidak terikat ke satu perusahaan)
 * bisa dibuat dengan tenant_id = NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop foreign key lama terlebih dahulu sebelum ubah kolom
            $table->dropForeign(['tenant_id']);

            // Ubah menjadi nullable (Super Admin tidak punya tenant)
            $table->unsignedBigInteger('tenant_id')->nullable()->change();

            // Buat ulang foreign key dengan nullable
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->unsignedBigInteger('tenant_id')->nullable(false)->change();
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');
        });
    }
};
