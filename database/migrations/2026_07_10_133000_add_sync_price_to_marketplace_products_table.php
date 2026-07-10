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
        Schema::table('marketplace_products', function (Blueprint $table) {
            $table->boolean('sync_price')->default(true)->after('sync_stock');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marketplace_products', function (Blueprint $table) {
            $table->dropColumn('sync_price');
        });
    }
};
