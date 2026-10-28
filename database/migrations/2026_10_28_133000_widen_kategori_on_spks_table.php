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
        if (Schema::hasTable('spks') && Schema::hasColumn('spks', 'kategori')) {
            try {
                DB::statement("ALTER TABLE `spks` MODIFY COLUMN `kategori` VARCHAR(255) NULL");
            } catch (\Throwable $e) {
                // Ignore if driver doesn't support statement
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to shrink column
    }
};
