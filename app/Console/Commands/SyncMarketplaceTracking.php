<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\Store;
use App\Services\ShopeeService;
use App\Services\TiktokService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SyncMarketplaceTracking extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'marketplace:sync-tracking 
                            {--tenant= : ID Tenant tertentu (opsional)}
                            {--days=14 : Batas hari pesanan aktif (default 14 hari)}
                            {--limit=200 : Maksimal pesanan yang dicek per eksekusi}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Secara otomatis menarik nomor resi & memperbarui status pesanan aktif (Shopee & TikTok) di latar belakang';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        Cache::forever('marketplace_tracking_last_run', now()->toIso8601String());

        $tenantId = $this->option('tenant') ? (int) $this->option('tenant') : null;
        $days     = max(1, min(60, (int) ($this->option('days') ?? 14)));
        $limit    = max(10, min(500, (int) ($this->option('limit') ?? 200)));

        $this->info("======================================================================");
        $this->info("  OTOMATISASI TARIK RESI & STATUS PESANAN (SHOPEE & TIKTOK)");
        $this->info("======================================================================");
        $this->info("  Batas Hari : {$days} hari ke belakang | Limit: {$limit} pesanan");
        if ($tenantId) {
            $this->info("  Tenant ID  : #{$tenantId}");
        }
        $this->info("======================================================================\n");

        Log::info('[Cron] Memulai marketplace:sync-tracking');

        $shopeeService = app(ShopeeService::class);
        $tiktokService = app(TiktokService::class);

        $targetStatuses = ['READY_TO_SHIP', 'UNPAID', 'PENDING', 'TO_SHIP', 'PROCESSED', 'PROCESSING', 'PROSES', 'RETRY_SHIP'];

        // 🔒 Normalisasi otomatis: Kembalikan semua pesanan yang sempat berstatus PROSES/PROCESSED ke READY_TO_SHIP
        $normalizedCnt = Order::query()
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->whereIn(DB::raw('UPPER(order_status)'), ['PROCESSED', 'PROSES', 'PROCESSING'])
            ->update(['order_status' => 'READY_TO_SHIP']);

        if ($normalizedCnt > 0) {
            $this->info("🔄 [NORMALISASI] {$normalizedCnt} pesanan dinormalkan statusnya tetap => READY_TO_SHIP.");
        }

        // Ambil pesanan aktif yang belum selesai/batal dalam periode X hari terakhir
        $query = Order::query()
            ->whereNotNull('order_marketplace_id')
            ->where('order_date', '>=', now()->subDays($days)->startOfDay())
            ->where(function($q) use ($targetStatuses) {
                $q->whereIn(DB::raw('UPPER(order_status)'), $targetStatuses)
                  ->orWhereNull('tracking_number')
                  ->orWhere('tracking_number', '');
            })
            ->whereNotIn(DB::raw('UPPER(order_status)'), ['COMPLETED', 'FINISHED', 'SELESAI', 'CANCELLED', 'BATAL', 'CANCELED', 'RETURNED', 'REFUNDED'])
            ->with(['store.channel'])
            ->orderByDesc('order_date')
            ->limit($limit);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $activeOrders = $query->get();

        if ($activeOrders->isEmpty()) {
            $this->info("✅ Tidak ada pesanan siap kirim yang tertunda atau belum ada resi.");
            return self::SUCCESS;
        }

        $this->info("🔍 Ditemukan {$activeOrders->count()} pesanan aktif untuk diperiksa resi & statusnya...\n");

        $shopeeUpdated = 0;
        $tiktokUpdated = 0;

        $grouped = $activeOrders->groupBy('store_id');

        foreach ($grouped as $storeId => $orders) {
            $store = $orders->first()->store;
            if (!$store || !$store->channel) continue;

            $chCode = strtolower($store->channel->code ?? '');

            // --- SHOPEE ---
            if ($chCode === 'shopee') {
                $this->line("📦 Toko Shopee: <comment>{$store->store_name}</comment> ({$orders->count()} pesanan)");
                try {
                    $accessToken = $store->getValidAccessToken();
                    $orderSns = $orders->pluck('order_marketplace_id')->filter()->unique()->values()->toArray();

                    $chunks = array_chunk($orderSns, 50);
                    foreach ($chunks as $chunk) {
                        $detailRes = $shopeeService->getOrderDetail(
                            $accessToken,
                            (int) $store->marketplace_store_id,
                            $chunk
                        );

                        $ordersList = $detailRes['order_list'] ?? [];
                        foreach ($ordersList as $shopeeOrder) {
                            $sn = $shopeeOrder['order_sn'] ?? null;
                            if (!$sn) continue;

                            $dbOrd = $orders->firstWhere('order_marketplace_id', $sn);
                            if (!$dbOrd) continue;

                            $changed = false;

                            // 1. Cek nomor resi dari package_list
                            $trackingNo = (!empty($shopeeOrder['package_list']) && !empty(current($shopeeOrder['package_list'])['tracking_number'])) 
                                ? current($shopeeOrder['package_list'])['tracking_number'] 
                                : null;

                            // 2. Jika resi masih kosong di package_list, panggil getTrackingNumber secara proaktif
                            if (empty($trackingNo) || str_starts_with($trackingNo, 'PSG') || str_starts_with($trackingNo, 'psg')) {
                                try {
                                    $trackRes = $shopeeService->getTrackingNumber(
                                        $accessToken,
                                        (int) $store->marketplace_store_id,
                                        $sn
                                    );
                                    $trackingNo = $trackRes['tracking_number'] ?? $trackRes['package_list'][0]['tracking_number'] ?? null;
                                } catch (\Throwable $e) {
                                    // Resi belum diterbitkan oleh kurir / belum di-arrange
                                }
                            }

                            if (!empty($trackingNo) && !(str_starts_with($trackingNo, 'PSG') || str_starts_with($trackingNo, 'psg'))) {
                                if ($dbOrd->tracking_number !== $trackingNo) {
                                    $dbOrd->tracking_number = $trackingNo;
                                    $changed = true;
                                    $this->line("   <info>-> [RESI] Order #{$sn}: Resi Shopee diperbarui => {$trackingNo}</info>");
                                    Log::info("[Cron:Tracking] Order Shopee {$sn} resi diperbarui: {$trackingNo}");
                                }
                            }

                            // 🔒 STATUS RESMI: Jangan pernah ubah status ke PROSES/PROCESSED. Tetap READY_TO_SHIP!
                            $spStatusRaw = strtoupper((string)($shopeeOrder['order_status'] ?? ''));
                            if (in_array($spStatusRaw, ['READY_TO_SHIP', 'PROCESSED', 'PROSES', 'PROCESSING', 'TO_SHIP', 'RETRY_SHIP', 'TO_RETRY_LOGISTICS', 'UNPAID'])) {
                                if (strtoupper((string)$dbOrd->order_status) !== 'READY_TO_SHIP') {
                                    $dbOrd->order_status = 'READY_TO_SHIP';
                                    $changed = true;
                                    $this->line("   <info>-> [STATUS] Order #{$sn}: Status distandarisasi tetap => READY_TO_SHIP</info>");
                                }
                            } elseif (in_array($spStatusRaw, ['SHIPPED', 'COMPLETED', 'CANCELLED'])) {
                                if ($dbOrd->order_status !== $spStatusRaw) {
                                    $dbOrd->order_status = $spStatusRaw;
                                    $changed = true;
                                    $this->line("   <comment>-> [STATUS] Order #{$sn}: Status diperbarui => {$spStatusRaw}</comment>");
                                    Log::info("[Cron:Tracking] Order Shopee {$sn} status diperbarui: {$spStatusRaw}");
                                }
                            }

                            if ($changed) {
                                $dbOrd->save();
                                $shopeeUpdated++;
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    $this->error("   ❌ Error Shopee Toko {$store->store_name}: " . $e->getMessage());
                    Log::error("[Cron:Tracking] Error Shopee {$store->store_name}: " . $e->getMessage());
                }
            }

            // --- TIKTOK / TOKOPEDIA ---
            elseif (in_array($chCode, ['tiktok', 'tokopedia'])) {
                $this->line("🎵 Toko TikTok: <comment>{$store->store_name}</comment> ({$orders->count()} pesanan)");
                try {
                    $accessToken = $store->getValidAccessToken();
                    $orderIds = $orders->pluck('order_marketplace_id')->filter()->unique()->values()->toArray();

                    $chunks = array_chunk($orderIds, 50);
                    foreach ($chunks as $chunk) {
                        $detailRes = $tiktokService->getOrderDetail(
                            $accessToken,
                            $store->shop_cipher,
                            $chunk
                        );

                        $ordersList = $detailRes['order_list'] ?? [];
                        foreach ($ordersList as $ttOrder) {
                            $oid = (string)($ttOrder['id'] ?? $ttOrder['order_id'] ?? '');
                            if (!$oid) continue;

                            $dbOrd = $orders->firstWhere('order_marketplace_id', $oid);
                            if (!$dbOrd) continue;

                            $changed = false;
                            $trackingNumber = $ttOrder['tracking_number'] ?? $ttOrder['tracking_no'] ?? null;
                            if (!empty($trackingNumber) && $dbOrd->tracking_number !== $trackingNumber) {
                                $dbOrd->tracking_number = $trackingNumber;
                                $changed = true;
                                $this->line("   <info>-> [RESI] Order #{$oid}: Resi TikTok diperbarui => {$trackingNumber}</info>");
                                Log::info("[Cron:Tracking] Order TikTok {$oid} resi diperbarui: {$trackingNumber}");
                            }

                            // 🔒 STATUS RESMI: Jangan pernah ubah status ke PROSES/PROCESSED. Tetap READY_TO_SHIP!
                            $ttStatus = strtoupper((string)($ttOrder['order_status'] ?? $ttOrder['status'] ?? ''));
                            if (in_array($ttStatus, ['READY_TO_SHIP', 'AWAITING_SHIPMENT', 'AWAITING_COLLECTION', '111', '112', 'PROCESSED', 'PROSES', 'PROCESSING'])) {
                                if (strtoupper((string)$dbOrd->order_status) !== 'READY_TO_SHIP') {
                                    $dbOrd->order_status = 'READY_TO_SHIP';
                                    $changed = true;
                                    $this->line("   <info>-> [STATUS] Order #{$oid}: Status distandarisasi tetap => READY_TO_SHIP</info>");
                                }
                            } elseif (in_array($ttStatus, ['SHIPPED', 'IN_TRANSIT', 'DELIVERED', 'COMPLETED', 'CANCELLED', '121', '122', '130', '140'])) {
                                $mappedStatus = in_array($ttStatus, ['IN_TRANSIT', 'SHIPPED', '121']) ? 'SHIPPED' : (in_array($ttStatus, ['DELIVERED', 'COMPLETED', '122', '130']) ? 'COMPLETED' : 'CANCELLED');
                                if ($dbOrd->order_status !== $mappedStatus) {
                                    $dbOrd->order_status = $mappedStatus;
                                    $changed = true;
                                    $this->line("   <comment>-> [STATUS] Order #{$oid}: Status diperbarui => {$mappedStatus}</comment>");
                                    Log::info("[Cron:Tracking] Order TikTok {$oid} status diperbarui: {$mappedStatus}");
                                }
                            }

                            if ($changed) {
                                $dbOrd->save();
                                $tiktokUpdated++;
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    $this->error("   ❌ Error TikTok Toko {$store->store_name}: " . $e->getMessage());
                    Log::error("[Cron:Tracking] Error TikTok {$store->store_name}: " . $e->getMessage());
                }
            }
        }

        $this->info("\n======================================================================");
        $this->info("✨ Selesai! Berhasil memperbarui:");
        $this->info("   • Shopee : {$shopeeUpdated} pesanan");
        $this->info("   • TikTok : {$tiktokUpdated} pesanan");
        $this->info("======================================================================\n");

        return self::SUCCESS;
    }
}
