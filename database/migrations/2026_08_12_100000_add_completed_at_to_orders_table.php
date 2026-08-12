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
        if (!Schema::hasColumn('orders', 'completed_at')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->timestamp('completed_at')->nullable()->after('order_date');
                $table->index(['tenant_id', 'completed_at']);
            });

            // Backfill completed_at for existing COMPLETED orders using updated_at
            DB::table('orders')
                ->whereIn('order_status', ['COMPLETED', 'DELIVERED', 'SELESAI', 'FINISHED'])
                ->whereNull('completed_at')
                ->update(['completed_at' => DB::raw('updated_at')]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('orders', 'completed_at')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropIndex(['tenant_id', 'completed_at']);
                $table->dropColumn('completed_at');
            });
        }
    }
};
