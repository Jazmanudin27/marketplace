<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offline_sales', function (Blueprint $table) {
            $table->string('discount_type', 20)->default('fixed')->after('discount_amount'); // fixed, percentage
            $table->decimal('discount_value', 15, 2)->default(0)->after('discount_type');
        });

        Schema::table('offline_sale_items', function (Blueprint $table) {
            $table->string('discount_type', 20)->default('fixed')->after('unit_price');
            $table->decimal('discount_value', 15, 2)->default(0)->after('discount_type');
            $table->decimal('discount_amount', 15, 2)->default(0)->after('discount_value');
        });
    }

    public function down(): void
    {
        Schema::table('offline_sales', function (Blueprint $table) {
            $table->dropColumn(['discount_type', 'discount_value']);
        });

        Schema::table('offline_sale_items', function (Blueprint $table) {
            $table->dropColumn(['discount_type', 'discount_value', 'discount_amount']);
        });
    }
};
