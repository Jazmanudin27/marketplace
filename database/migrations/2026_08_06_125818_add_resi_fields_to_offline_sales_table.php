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
            $table->string('resi_number')->nullable()->after('dropshipper_phone');
            $table->string('resi_file')->nullable()->after('resi_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offline_sales', function (Blueprint $table) {
            $table->dropColumn(['resi_number', 'resi_file']);
        });
    }
};
