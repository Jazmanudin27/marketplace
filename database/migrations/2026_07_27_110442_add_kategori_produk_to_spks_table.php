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
        Schema::table('spks', function (Blueprint $table) {
            if (!Schema::hasColumn('spks', 'kategori')) {
                $table->string('kategori', 255)->nullable()->after('tipe_spk');
            }
        });
    }

    public function down(): void
    {
        Schema::table('spks', function (Blueprint $table) {
            if (Schema::hasColumn('spks', 'kategori')) {
                $table->dropColumn('kategori');
            }
        });
    }
};
