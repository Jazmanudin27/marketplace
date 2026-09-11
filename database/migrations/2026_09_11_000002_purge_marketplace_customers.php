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
        if (Schema::hasTable('customers')) {
            // Ambil ID pelanggan yang murni berasal dari marketplace (tidak memiliki penjualan offline dan transaksi saldo)
            $query = DB::table('customers')
                ->where(function ($q) {
                    $q->where('category', 'marketplace')
                      ->orWhere(function ($sub) {
                          $sub->whereNotNull('marketplace_username')
                              ->where('marketplace_username', '!=', '');
                      });
                });

            // Proteksi: jangan sentuh pelanggan yang memiliki riwayat penjualan offline
            if (Schema::hasTable('offline_sales')) {
                $query->whereNotExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('offline_sales')
                        ->whereColumn('offline_sales.customer_id', 'customers.id');
                });
            }

            // Proteksi: jangan sentuh pelanggan yang memiliki riwayat saldo reseller
            if (Schema::hasTable('reseller_balance_transactions')) {
                $query->whereNotExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('reseller_balance_transactions')
                        ->whereColumn('reseller_balance_transactions.customer_id', 'customers.id');
                });
            }

            $marketplaceCustomerIds = $query->pluck('id')->toArray();

            if (!empty($marketplaceCustomerIds)) {
                // Lepaskan relasi customer_id pada tabel orders marketplace
                if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'customer_id')) {
                    DB::table('orders')
                        ->whereIn('customer_id', $marketplaceCustomerIds)
                        ->update(['customer_id' => null]);
                }

                // Hapus data dummy pelanggan marketplace dari tabel customers
                DB::table('customers')
                    ->whereIn('id', $marketplaceCustomerIds)
                    ->delete();
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
