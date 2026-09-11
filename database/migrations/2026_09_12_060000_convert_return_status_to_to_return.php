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
        if (Schema::hasTable('orders')) {
            DB::table('orders')
                ->where('order_status', 'RETURN')
                ->update(['order_status' => 'TO_RETURN']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('orders')) {
            DB::table('orders')
                ->where('order_status', 'TO_RETURN')
                ->update(['order_status' => 'RETURN']);
        }
    }
};
