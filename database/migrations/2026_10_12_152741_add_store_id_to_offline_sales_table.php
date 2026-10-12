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
        if (Schema::hasTable('offline_sales') && !Schema::hasColumn('offline_sales', 'store_id')) {
            Schema::table('offline_sales', function (Blueprint $table) {
                $table->unsignedBigInteger('store_id')->nullable()->after('tenant_id');
                $table->foreign('store_id')->references('id')->on('stores')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('offline_sales') && Schema::hasColumn('offline_sales', 'store_id')) {
            Schema::table('offline_sales', function (Blueprint $table) {
                $table->dropForeign(['store_id']);
                $table->dropColumn('store_id');
            });
        }
    }
};
