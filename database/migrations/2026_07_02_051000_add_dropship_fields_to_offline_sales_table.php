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
        Schema::table('offline_sales', function (Blueprint $table) {
            $table->boolean('is_dropship')->default(false)->after('customer_id');
            $table->string('dropshipper_name')->nullable()->after('is_dropship');
            $table->string('dropshipper_phone')->nullable()->after('dropshipper_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offline_sales', function (Blueprint $table) {
            $table->dropColumn(['is_dropship', 'dropshipper_name', 'dropshipper_phone']);
        });
    }
};
