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
        if (Schema::hasTable('offline_sales') && !Schema::hasColumn('offline_sales', 'institution_name')) {
            Schema::table('offline_sales', function (Blueprint $table) {
                $table->string('institution_name', 150)->nullable()->after('customer_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('offline_sales') && Schema::hasColumn('offline_sales', 'institution_name')) {
            Schema::table('offline_sales', function (Blueprint $table) {
                $table->dropColumn('institution_name');
            });
        }
    }
};
