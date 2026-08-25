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
    protected $signature = 'shopee:sync-escrow {--store_id= : ID Toko Shopee tertentu (opsional)} {--order_sn= : Nomor pesanan Shopee tertentu untuk dites (opsional)} {--limit=100 : Jumlah orderan per batch} {--date_from= : Tanggal awal order (contoh: 2026-08-01)} {--date_to= : Tanggal akhir order (contoh: 2026-08-18)} {--all : Paksa sinkronisasi semua order termasuk yang sudah match atau tanggal lama}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menembak API Shopee get_escrow_detail untuk pesanan yang belum sinkron atau berbeda (mismatch) antara ERP dan API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai sinkronisasi Rincian Biaya Escrow Langsung dari API Shopee...');

        $shopeeService  = app(ShopeeService::class);
        $storeId        = $this->option('store_id');
        $orderSnOption  = $this->option('order_sn');
        $dateFromOption = $this->option('date_from');
        $dateToOption   = $this->option('date_to');
        $forceAll       = $this->option('all');

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

            // Ambil orderan Shopee di toko ini (abaikan yang dibatalkan)
            $ordersQuery = Order::where('store_id', $store->id)
                ->whereNotNull('order_marketplace_id')
                ->whereNotIn('order_status', ['CANCELLED', 'BATAL', 'CANCELED']);

            if ($orderSnOption) {
                $ordersQuery->where('order_marketplace_id', $orderSnOption);
            } else {
                if ($dateFromOption) {
                    $ordersQuery->whereDate('order_date', '>=', $dateFromOption);
                }
                if ($dateToOption) {
                    $ordersQuery->whereDate('order_date', '<=', $dateToOption);
                }
                if (!$dateFromOption && !$dateToOption && !$forceAll) {
                    $ordersQuery->whereDate('order_date', '>=', now()->subDays(30)->toDateString());
                }
            }

            $allOrders = $ordersQuery->get();

            // 🎯 FILTER HANYA PESANAN MISMATCH / BEDA DENGAN API
            if (!$orderSnOption && !$forceAll) {
                $orders = $allOrders->filter(function($ord) {
                    $status = strtoupper($ord->order_status);
                    if (!in_array($status, ['COMPLETED', 'SELESAI', 'CANCELLED', 'BATAL', 'CANCELED', 'RETURNED', 'REFUNDED', 'RETURN', 'REFUND'])) {
                        return true;
                    }

                    $fb = $ord->financial_breakdown;
                    if (empty($fb)) return true; // Jika kosong, wajib disinkronkan agar terisi

                    if (is_string($fb)) {
                        $fb = json_decode($fb, true);
                    }
                    if (!is_array($fb) || empty($fb)) return true;

                    $sellerDisc = (float)($fb['voucher_from_seller'] ?? $fb['seller_discount'] ?? 0);
                    $cogs = (float)($fb['cost_of_goods_sold'] ?? $fb['order_selling_price'] ?? $ord->total_amount);
                    $apiOmset = ($sellerDisc > 0 && $cogs > $sellerDisc) ? ($cogs - $sellerDisc) : $cogs;
                    $apiNet = (float)($fb['escrow_amount'] ?? $ord->net_amount);
                    $apiFee = abs((float)($ord->fee_breakdown_details['total_fee'] ?? $ord->marketplace_fee));

                    $diffOmset = abs((float)$ord->total_amount - $apiOmset);
                    $diffNet   = abs((float)$ord->net_amount - $apiNet);
                    $diffFee   = abs((float)$ord->marketplace_fee - $apiFee);

                    return ($diffOmset > 100 || $diffNet > 100 || $diffFee > 100);
                });

                $skippedCount = $allOrders->count() - $orders->count();
                $this->info("Total Pesanan Aktif: {$allOrders->count()} | Ditemukan Mismatch: {$orders->count()} ({$skippedCount} pesanan sudah match dilewati)");
            } else {
                $orders = $allOrders;
                $this->info("Menemukan {$orders->count()} pesanan untuk disinkronkan dengan API Escrow Shopee...");
            }

            if ($orders->isEmpty()) {
                $this->info("✨ Semua pesanan Shopee di toko ini sudah MATCH 100%!");
                continue;
            }

            foreach ($orders as $order) {
                try {
                    $orderSn = $order->order_marketplace_id;
                    $escrowResponse = $shopeeService->getEscrowDetail($accessToken, (int) $store->marketplace_store_id, $orderSn);

                    if (!empty($escrowResponse['order_income'])) {
                        $income = $escrowResponse['order_income'];
                        if ($orderSnOption) {
                            $this->line("\n[DEBUG] Shopee Escrow Income for {$orderSn}:");
                            $this->line(json_encode($income, JSON_PRETTY_PRINT) . "\n");
                        }
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

                        // Ambil Subtotal Produk Penjual setelah diskon penjual (Net Sales)
                        $sellerDisc = (float) ($income['voucher_from_seller'] ?? $income['seller_discount'] ?? 0);
                        if (isset($income['order_selling_price']) && (float)$income['order_selling_price'] > 0) {
                            $merchantSubtotal = (float)$income['order_selling_price'];
                        } elseif (isset($income['cost_of_goods_sold']) && (float)$income['cost_of_goods_sold'] > 0) {
                            $cogs = (float)$income['cost_of_goods_sold'];
                            $merchantSubtotal = ($sellerDisc > 0 && $cogs > $sellerDisc) ? max(0.0, $cogs - $sellerDisc) : $cogs;
                        } else {
                            $merchantSubtotal = (float) ($income['order_original_price'] ?? $order->total_amount);
                        }

                        if ($merchantSubtotal > 0) {
                            $order->total_amount = $merchantSubtotal;
                        }

                        $escrowAmount = (float) ($income['escrow_amount'] ?? 0);

                        // Hitung refund jika ada
                        $refundAmt = 0.0;
                        $refundKeys = ['customer_refund_amount', 'gross_sales_refund_amount', 'seller_return_refund', 'buyer_return_refund_amount', 'refund_amount', 'return_amount', 'customer_order_refund_amount'];
                        foreach ($refundKeys as $rk) {
                            if (!empty($income[$rk]) && (float)$income[$rk] != 0) {
                                $refundAmt = abs((float)$income[$rk]);
                                break;
                            }
                        }

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
                        $order->net_amount = $escrowAmount > 0 ? ($escrowAmount - $refundAmt) : max(0.0, $merchantSubtotal - $refundAmt - $totalFee);

                        // Jika terdeteksi ada refund/retur, ubah status pesanan menjadi RETURN
                        if ($refundAmt > 0) {
                            $order->order_status = 'RETURN';
                        } else {
                            // 🔒 PRESERVE ACTIVE STATUS: Hanya ubah ke COMPLETED jika pesanan memang sudah dikirim/selesai!
                            // Jangan pernah menimpa pesanan yang masih READY_TO_SHIP atau SHIPPED.
                            if (in_array(strtoupper((string)$order->order_status), ['DELIVERED', 'COMPLETED', 'FINISHED', 'SELESAI'])) {
                                $order->order_status = 'COMPLETED';
                                if (!$order->completed_at) {
                                    $order->completed_at = now();
                                }
                            }
                        }

                        $order->recon_status = 'RECONCILED';
                        $order->saveQuietly();

                        // Update rincian item produk agar nilainya selaras
                        $existItems = $order->items;
                        if ($existItems->count() === 1 && $merchantSubtotal > 0) {
                            $singleItem = $existItems->first();
                            $iQty = $singleItem->quantity ?: 1;
                            \DB::table('order_items')->where('id', $singleItem->id)->update([
                                'price'       => round($merchantSubtotal / $iQty, 2),
                                'total_price' => $merchantSubtotal,
                                'updated_at'  => now(),
                            ]);
                        }

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

        // Clear reconciliation web cache so dashboard reflects updates immediately
        \Illuminate\Support\Facades\Cache::flush();
    }
}
