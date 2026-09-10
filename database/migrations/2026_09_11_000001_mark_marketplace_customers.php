<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('customers') && Schema::hasColumn('customers', 'category')) {
            // Update existing customers who have marketplace orders and no offline sales to 'marketplace'
            if (Schema::hasTable('orders')) {
                DB::table('customers')
                    ->whereExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('orders')
                            ->whereColumn('orders.customer_id', 'customers.id');
                    })
                    ->where(function ($query) {
                        if (Schema::hasTable('offline_sales')) {
                            $query->whereNotExists(function ($sub) {
                                $sub->select(DB::raw(1))
                                    ->from('offline_sales')
                                    ->whereColumn('offline_sales.customer_id', 'customers.id');
                            });
                        }
                    })
                    ->update(['category' => 'marketplace']);
            }

            // Also update customers who have a marketplace_username populated
            if (Schema::hasColumn('customers', 'marketplace_username')) {
                DB::table('customers')
                    ->whereNotNull('marketplace_username')
                    ->where('marketplace_username', '!=', '')
                    ->update(['category' => 'marketplace']);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback needed
    }
};
