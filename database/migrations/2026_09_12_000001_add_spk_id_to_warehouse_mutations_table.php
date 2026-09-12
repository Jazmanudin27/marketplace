<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('warehouse_mutations') && !Schema::hasColumn('warehouse_mutations', 'spk_id')) {
            Schema::table('warehouse_mutations', function (Blueprint $table) {
                $table->unsignedBigInteger('spk_id')->nullable()->after('goods_receipt_id');
                $table->foreign('spk_id')->references('id')->on('spks')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('warehouse_mutations') && Schema::hasColumn('warehouse_mutations', 'spk_id')) {
            Schema::table('warehouse_mutations', function (Blueprint $table) {
                $table->dropForeign(['spk_id']);
                $table->dropColumn('spk_id');
            });
        }
    }
};
