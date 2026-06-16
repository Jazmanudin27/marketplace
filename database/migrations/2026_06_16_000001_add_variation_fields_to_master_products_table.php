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
        Schema::table('master_products', function (Blueprint $table) {
            if (!Schema::hasColumn('master_products', 'sku_induk')) {
                $table->string('sku_induk', 100)->nullable()->after('sku');
            }
            if (!Schema::hasColumn('master_products', 'sub_kategori')) {
                $table->string('sub_kategori', 100)->nullable()->after('category_id');
            }
            if (!Schema::hasColumn('master_products', 'ukuran')) {
                $table->string('ukuran', 100)->nullable()->after('sub_kategori');
            }
            if (!Schema::hasColumn('master_products', 'warna')) {
                $table->string('warna', 100)->nullable()->after('ukuran');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_products', function (Blueprint $table) {
            $table->dropColumn(['sku_induk', 'sub_kategori', 'ukuran', 'warna']);
        });
    }
};
