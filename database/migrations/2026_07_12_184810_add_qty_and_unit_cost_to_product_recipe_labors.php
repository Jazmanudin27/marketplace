<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_recipe_labors', function (Blueprint $table) {
            $table->integer('qty')->default(1)->after('service_name');
            $table->decimal('unit_cost', 15, 2)->default(0.00)->after('qty');
        });

        // Copy default_cost to unit_cost for existing records
        DB::table('product_recipe_labors')->update([
            'unit_cost' => DB::raw('default_cost')
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_recipe_labors', function (Blueprint $table) {
            $table->dropColumn(['qty', 'unit_cost']);
        });
    }
};
