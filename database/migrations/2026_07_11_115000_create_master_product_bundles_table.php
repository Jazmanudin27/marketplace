<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambahkan kolom is_bundle ke master_products
        if (!Schema::hasColumn('master_products', 'is_bundle')) {
            Schema::table('master_products', function (Blueprint $table) {
                $table->boolean('is_bundle')->default(false)->after('is_active');
            });
        }

        // 2. Buat tabel master_product_bundles
        Schema::create('master_product_bundles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id'); // Product Set (e.g. SET-BATIK-M)
            $table->unsignedBigInteger('child_id');  // Component (e.g. BAJU-BATIK-M)
            $table->integer('quantity')->default(1);
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('master_products')->onDelete('cascade');
            $table->foreign('child_id')->references('id')->on('master_products')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_product_bundles');

        if (Schema::hasColumn('master_products', 'is_bundle')) {
            Schema::table('master_products', function (Blueprint $table) {
                $table->dropColumn('is_bundle');
            });
        }
    }
};
