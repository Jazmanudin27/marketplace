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
        if (Schema::hasTable('master_products') && !Schema::hasColumn('master_products', 'barcode')) {
            Schema::table('master_products', function (Blueprint $table) {
                $table->string('barcode', 100)->nullable()->after('sku')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('master_products') && Schema::hasColumn('master_products', 'barcode')) {
            Schema::table('master_products', function (Blueprint $table) {
                $table->dropIndex(['barcode']);
                $table->dropColumn('barcode');
            });
        }
    }
};
