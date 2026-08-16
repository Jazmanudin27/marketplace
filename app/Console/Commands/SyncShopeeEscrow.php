<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Store;
use App\Models\Order;
use App\Services\ShopeeService;
use Illuminate\Support\Facades\Log;

class SyncShopeeEscrow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shopee:sync-escrow {--store_id= : ID Toko Shopee tertentu (opsional)} {--order_sn= : Nomor pesanan Shopee tertentu untuk dites (opsional)} {--limit=100 : Jumlah orderan per batch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menembak API Shopee get_escrow_detail secara massal dan mengupdate rincian biaya resmi untuk semua orderan di database ERP';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai sinkronisasi Rincian Biaya Escrow Langsung dari API Shopee...');

        $shopeeService = app(ShopeeService::class);
        $storeId = $this->option('store_id');
        $orderSnOption = $this->option('order_sn');

        $query = Store::whereHas('channel', function ($q) {
            $q->where('code', 'shopee');
        });

        if ($storeId) {
            $query->where('id', $storeId);
        }

        $stores = $query->get();

        if ($stores->isEmpty()) {
            $this->error('Tidak ada toko Shopee yang terhubung.');
            return;
        }

        $totalSuccess = 0;
        $totalFailed = 0;

        foreach ($stores as $store) {
            $this->info("\nMemproses Toko: {$store->store_name} (Shop ID: {$store->marketplace_store_id})");

            try {
                $accessToken = $store->getValidAccessToken();
            } catch (\Exception $e) {
                $this->error("Gagal mendapatkan access token untuk toko {$store->store_name}: " . $e->getMessage());
                continue;
            }

            // Ambil orderan Shopee di toko ini
            $ordersQuery = Order::where('store_id', $store->id)
                ->whereNotNull('order_marketplace_id');

            if ($orderSnOption) {
                $ordersQuery->where('order_marketplace_id', $orderSnOption);
            }

            $orders = $ordersQuery->get();

            $this->info("Menemukan {$orders->count()} pesanan untuk disinkronkan dengan API Escrow Shopee...");

            foreach ($orders as $order) {
                try {
                    $orderSn = $order->order_marketplace_id;
                    $escrowResponse = $shopeeService->getEscrowDetail($accessToken, (int) $store->marketplace_store_id, $orderSn);

                    if (!empty($escrowResponse['order_income'])) {
                        $income = $escrowResponse['order_income'];
                        $order->financial_breakdown = $income;

                        // Ambil Tanggal Selesai / Tanggal Diterima Resmi dari Shopee API (update_time)
                        try {
                            $orderDetailRes = $shopeeService->getOrderDetail($accessToken, (int) $store->marketplace_store_id, [$orderSn]);
                            $shopeeOrder = $orderDetailRes['order_list'][0] ?? [];
                            if (!empty($shopeeOrder['update_time'])) {
                                $order->completed_at = date('Y-m-d H:i:s', $shopeeOrder['update_time']);
                            }
                        } catch (\Exception $exDetail) {
                            if (!$order->completed_at) {
                                $order->completed_at = now();
                            }
                        }

                        // Ambil Subtotal Produk Penjual (merchant_subtotal / cost_of_goods_sold)
                        $merchantSubtotal = (float) ($income['cost_of_goods_sold'] ?? $income['order_selling_price'] ?? $income['order_original_price'] ?? $order->total_amount);
                        if ($merchantSubtotal > 0) {
                            $order->total_amount = $merchantSubtotal;
                        }

                        $escrowAmount = (float) ($income['escrow_amount'] ?? 0);

                        // Hitung total fee dari breakdown rincian atau selisih Subtotal - Escrow Amount
                        $details = $order->fee_breakdown_details;
                        $totalFee = abs($details['total_fee'] ?? 0);

                        if ($totalFee <= 0 && $escrowAmount > 0 && $merchantSubtotal > $escrowAmount) {
                            $totalFee = max(0.0, $merchantSubtotal - $escrowAmount);
                        }

                        if ($totalFee <= 0 && $merchantSubtotal > 0) {
                            $totalFee = round($merchantSubtotal * 0.095);
                        }

                        $order->marketplace_fee = $totalFee;
                        $order->net_amount = $escrowAmount > 0 ? $escrowAmount : max(0.0, $merchantSubtotal - $totalFee);
                        $order->order_status = 'COMPLETED';
                        if (!$order->completed_at) {
                            $order->completed_at = now();
                        }

                        $order->saveQuietly();
                        $totalSuccess++;
                        $this->line("  [OK] Order {$orderSn} -> Tgl Selesai: " . ($order->completed_at ? $order->completed_at->format('Y-m-d H:i:s') : '-') . " | Subtotal: Rp " . number_format($order->total_amount, 0) . " | Fee: Rp " . number_format($order->marketplace_fee, 0) . " | Escrow Cair: Rp " . number_format($order->net_amount, 0));
                    } else {
                        $this->warn("  [SKIP] Order {$orderSn} belum memiliki data order_income dari Shopee (Pesanan belum selesai).");
                    }
                } catch (\Exception $e) {
                    $totalFailed++;
                    $this->error("  [ERROR] Order {$order->order_marketplace_id}: " . $e->getMessage());
                }
            }
        }

        $this->info("\n========================================================");
        $this->info("Selesai! Berhasil memperbarui {$totalSuccess} pesanan dari Shopee API Live.");
        if ($totalFailed > 0) {
            $this->warn("Gagal / Lewati {$totalFailed} pesanan.");
        }
        $this->info("========================================================");
    }
}
