<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spk_items', function (Blueprint $table) {
            if (!Schema::hasColumn('spk_items', 'catatan')) {
                $table->text('catatan')->nullable()->after('ukuran');
            }
            if (!Schema::hasColumn('spk_items', 'pemotong')) {
                $table->string('pemotong')->nullable()->after('penjahit');
            }
        });
    }

    public function down(): void
    {
        Schema::table('spk_items', function (Blueprint $table) {
            if (Schema::hasColumn('spk_items', 'catatan')) {
                $table->dropColumn('catatan');
            }
            if (Schema::hasColumn('spk_items', 'pemotong')) {
                $table->dropColumn('pemotong');
            }
        });
    }
};
