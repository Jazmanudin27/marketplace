<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spks', function (Blueprint $table) {
            if (!Schema::hasColumn('spks', 'is_batal')) {
                $table->boolean('is_batal')->default(false)->after('tahap_saat_ini');
            }
            if (!Schema::hasColumn('spks', 'alasan_batal')) {
                $table->text('alasan_batal')->nullable()->after('is_batal');
            }
            if (!Schema::hasColumn('spks', 'batal_at')) {
                $table->timestamp('batal_at')->nullable()->after('alasan_batal');
            }
            if (!Schema::hasColumn('spks', 'batal_by')) {
                $table->unsignedBigInteger('batal_by')->nullable()->after('batal_at');
                $table->foreign('batal_by')->references('id')->on('users')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('spks', function (Blueprint $table) {
            if (Schema::hasColumn('spks', 'batal_by')) {
                $table->dropForeign(['batal_by']);
                $table->dropColumn('batal_by');
            }
            if (Schema::hasColumn('spks', 'batal_at')) {
                $table->dropColumn('batal_at');
            }
            if (Schema::hasColumn('spks', 'alasan_batal')) {
                $table->dropColumn('alasan_batal');
            }
            if (Schema::hasColumn('spks', 'is_batal')) {
                $table->dropColumn('is_batal');
            }
        });
    }
};
