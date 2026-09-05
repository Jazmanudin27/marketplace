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
        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'is_substituted')) {
                $table->boolean('is_substituted')->default(false)->after('quantity');
            }
            if (!Schema::hasColumn('order_items', 'original_sku')) {
                $table->string('original_sku', 100)->nullable()->after('is_substituted');
            }
            if (!Schema::hasColumn('order_items', 'original_product_name')) {
                $table->string('original_product_name', 255)->nullable()->after('original_sku');
            }
            if (!Schema::hasColumn('order_items', 'substitution_note')) {
                $table->text('substitution_note')->nullable()->after('original_product_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('order_items', 'is_substituted')) {
                $columns[] = 'is_substituted';
            }
            if (Schema::hasColumn('order_items', 'original_sku')) {
                $columns[] = 'original_sku';
            }
            if (Schema::hasColumn('order_items', 'original_product_name')) {
                $columns[] = 'original_product_name';
            }
            if (Schema::hasColumn('order_items', 'substitution_note')) {
                $columns[] = 'substitution_note';
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
