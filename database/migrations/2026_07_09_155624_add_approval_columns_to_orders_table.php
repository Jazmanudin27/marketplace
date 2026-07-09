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
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('approved_warehouse_at')->nullable();
            $table->foreignId('approved_warehouse_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_production_at')->nullable();
            $table->foreignId('approved_production_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['approved_warehouse_by']);
            $table->dropColumn('approved_warehouse_by');
            $table->dropColumn('approved_warehouse_at');
            $table->dropForeign(['approved_production_by']);
            $table->dropColumn('approved_production_by');
            $table->dropColumn('approved_production_at');
        });
    }
};
