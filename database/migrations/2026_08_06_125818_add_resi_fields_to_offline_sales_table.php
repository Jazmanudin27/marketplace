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
            if (!Schema::hasColumn('offline_sales', 'resi_number')) {
                $table->string('resi_number')->nullable()->after('dropshipper_phone');
            }
            if (!Schema::hasColumn('offline_sales', 'resi_file')) {
                $table->string('resi_file')->nullable()->after('resi_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offline_sales', function (Blueprint $table) {
            if (Schema::hasColumn('offline_sales', 'resi_number')) {
                $table->dropColumn('resi_number');
            }
            if (Schema::hasColumn('offline_sales', 'resi_file')) {
                $table->dropColumn('resi_file');
            }
        });
    }
};
