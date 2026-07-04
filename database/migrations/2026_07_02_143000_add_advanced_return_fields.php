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
        Schema::table('return_orders', function (Blueprint $table) {
            $table->timestamp('sla_deadline')->nullable()->after('status');
            $table->foreignId('replacement_order_id')->nullable()->after('checked_by')->constrained('orders')->nullOnDelete();
        });

        Schema::table('return_order_items', function (Blueprint $table) {
            $table->string('inspection_photo')->nullable()->after('inspection_notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('return_orders', function (Blueprint $table) {
            if (\Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['replacement_order_id']);
            }
            $table->dropColumn(['sla_deadline', 'replacement_order_id']);
        });

        Schema::table('return_order_items', function (Blueprint $table) {
            $table->dropColumn(['inspection_photo']);
        });
    }
};
