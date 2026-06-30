<?php

namespace App\Services;

use App\Models\AdsCampaign;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Support\Facades\Log;

class AutoAttributionService
{
    /**
     * Coba atribusikan satu order ke campaign iklan menggunakan logika 3 layer waterfall.
     *
     * Layer 1: UTM Tracking   — cocokkan utm_campaign ke nama/ID campaign di database
     * Layer 2: Store Default  — gunakan default_campaign_id dari toko asal pesanan
     * Layer 3: Platform Match — cocokkan platform channel toko ke campaign aktif di platform yang sama
     *
     * @return array{attributed: bool, layer: int|null, reason: string}
     */
    public function attributeOrder(Order $order): array
    {
        // Skip jika sudah teratribusi
        if ($order->ads_campaign_id) {
            return ['attributed' => false, 'layer' => null, 'reason' => 'Sudah teratribusi'];
        }

        $tenantId = $order->tenant_id;

        // ─────────────────────────────────────────────────────────────
        // LAYER 1: UTM Tracking
        // ─────────────────────────────────────────────────────────────
        if (!empty($order->utm_campaign)) {
            $campaign = AdsCampaign::where('tenant_id', $tenantId)
                ->where('status', 'ACTIVE')
                ->where(function ($q) use ($order) {
                    $q->where('campaign_id_platform', $order->utm_campaign)
                      ->orWhere('name', 'like', '%' . $order->utm_campaign . '%');
                })
                ->first();

            if ($campaign) {
                $order->update(['ads_campaign_id' => $campaign->id]);
                Log::info('[AutoAttribution] Layer 1 (UTM) matched', [
                    'order_id'    => $order->id,
                    'campaign_id' => $campaign->id,
                    'utm'         => $order->utm_campaign,
                ]);
                $this->triggerTiktokCapi($order);
                return ['attributed' => true, 'layer' => 1, 'reason' => "UTM match: {$order->utm_campaign}"];
            }
        }

        // ─────────────────────────────────────────────────────────────
        // LAYER 2: Store Default Campaign
        // ─────────────────────────────────────────────────────────────
        $store = $order->store;
        if ($store && $store->default_campaign_id) {
            $campaign = AdsCampaign::where('tenant_id', $tenantId)
                ->where('id', $store->default_campaign_id)
                ->where('status', 'ACTIVE')
                ->first();

            if ($campaign) {
                $order->update(['ads_campaign_id' => $campaign->id]);
                Log::info('[AutoAttribution] Layer 2 (Store Default) matched', [
                    'order_id'    => $order->id,
                    'campaign_id' => $campaign->id,
                    'store_id'    => $store->id,
                ]);
                $this->triggerTiktokCapi($order);
                return ['attributed' => true, 'layer' => 2, 'reason' => "Store default: {$store->store_name}"];
            }
        }

        // ─────────────────────────────────────────────────────────────
        // LAYER 3: Platform Match
        // ─────────────────────────────────────────────────────────────
        if ($store && $store->channel) {
            $channelCode = $store->channel->code; // e.g. 'shopee', 'tiktok', 'lazada'

            // Map channel code ke platform ads
            $platformMap = [
                'shopee'    => 'shopee',
                'tiktok'    => 'tiktok',
                'tokopedia' => 'tiktok', // Tokopedia merged ke TikTok Shop
                'lazada'    => 'lazada',
                'manual'    => 'manual',
            ];

            $platform = $platformMap[$channelCode] ?? null;

            if ($platform) {
                // Ambil semua campaign aktif di platform ini milik tenant ini
                $campaigns = AdsCampaign::whereHas('adsAccount', function ($q) use ($tenantId, $platform) {
                        $q->where('tenant_id', $tenantId)
                          ->where('platform', $platform)
                          ->where('is_active', true);
                    })
                    ->where('tenant_id', $tenantId)
                    ->where('status', 'ACTIVE')
                    ->get();

                if ($campaigns->count() === 1) {
                    // Hanya 1 campaign → langsung assign
                    $campaign = $campaigns->first();
                    $order->update(['ads_campaign_id' => $campaign->id]);
                    Log::info('[AutoAttribution] Layer 3 (Platform, single campaign) matched', [
                        'order_id'    => $order->id,
                        'campaign_id' => $campaign->id,
                        'platform'    => $platform,
                    ]);
                    $this->triggerTiktokCapi($order);
                    return ['attributed' => true, 'layer' => 3, 'reason' => "Platform match (1 campaign): {$platform}"];

                } elseif ($campaigns->count() > 1) {
                    // Lebih dari 1 → pilih yang ROAS-nya tertinggi (paling profitable)
                    $best = $campaigns->sortByDesc(fn($c) => $c->actual_roas)->first();
                    if ($best) {
                        $order->update(['ads_campaign_id' => $best->id]);
                        Log::info('[AutoAttribution] Layer 3 (Platform, best ROAS) matched', [
                            'order_id'    => $order->id,
                            'campaign_id' => $best->id,
                            'platform'    => $platform,
                            'roas'        => $best->actual_roas,
                        ]);
                        $this->triggerTiktokCapi($order);
                        return ['attributed' => true, 'layer' => 3, 'reason' => "Platform match (best ROAS): {$platform} → {$best->name}"];
                    }
                }
            }
        }

        return ['attributed' => false, 'layer' => null, 'reason' => 'Tidak ada campaign yang cocok'];
    }

    /**
     * Kirim event Purchase ke TikTok CAPI jika platform campaign = tiktok
     */
    private function triggerTiktokCapi(Order $order)
    {
        try {
            $campaign = $order->adsCampaign;
            if (!$campaign) {
                $campaign = AdsCampaign::find($order->ads_campaign_id);
            }
            if ($campaign) {
                $account = $campaign->adsAccount;
                if ($account && $account->platform === 'tiktok') {
                    app(\App\Services\TiktokCapiService::class)->sendPurchaseEvent($order);
                }
            }
        } catch (\Throwable $e) {
            Log::error("[AutoAttribution] Gagal kirim TikTok CAPI: " . $e->getMessage());
        }
    }


    /**
     * Jalankan auto-attribution untuk semua order yang belum teratribusi milik satu tenant.
     *
     * @return array{total: int, attributed: int, skipped: int, results: array}
     */
    public function attributeBatch(int $tenantId, int $limit = 200, bool $dryRun = false): array
    {
        $orders = Order::with(['store.channel', 'store'])
            ->where('tenant_id', $tenantId)
            ->whereNull('ads_campaign_id')
            ->whereNotIn('order_status', [Order::STATUS_CANCELLED])
            ->orderByDesc('order_date')
            ->limit($limit)
            ->get();

        $total      = $orders->count();
        $attributed = 0;
        $skipped    = 0;
        $results    = [];

        foreach ($orders as $order) {
            if ($dryRun) {
                // Dry run: simulasikan tanpa menyimpan
                $result = $this->simulateAttribution($order, $tenantId);
            } else {
                $result = $this->attributeOrder($order);
            }

            if ($result['attributed']) {
                $attributed++;
            } else {
                $skipped++;
            }

            $results[] = [
                'order_id'       => $order->id,
                'invoice_number' => $order->invoice_number,
                'attributed'     => $result['attributed'],
                'layer'          => $result['layer'],
                'reason'         => $result['reason'],
            ];
        }

        Log::info('[AutoAttribution] Batch complete', [
            'tenant_id'  => $tenantId,
            'total'      => $total,
            'attributed' => $attributed,
            'skipped'    => $skipped,
            'dry_run'    => $dryRun,
        ]);

        return compact('total', 'attributed', 'skipped', 'results');
    }

    /**
     * Simulasikan atribusi tanpa menyimpan (untuk dry-run / preview).
     */
    private function simulateAttribution(Order $order, int $tenantId): array
    {
        // Layer 1
        if (!empty($order->utm_campaign)) {
            $campaign = AdsCampaign::where('tenant_id', $tenantId)
                ->where('status', 'ACTIVE')
                ->where(function ($q) use ($order) {
                    $q->where('campaign_id_platform', $order->utm_campaign)
                      ->orWhere('name', 'like', '%' . $order->utm_campaign . '%');
                })
                ->first();
            if ($campaign) {
                return ['attributed' => true, 'layer' => 1, 'reason' => "UTM match: {$order->utm_campaign}"];
            }
        }

        // Layer 2
        $store = $order->store;
        if ($store && $store->default_campaign_id) {
            $campaign = AdsCampaign::where('tenant_id', $tenantId)
                ->where('id', $store->default_campaign_id)
                ->where('status', 'ACTIVE')
                ->first();
            if ($campaign) {
                return ['attributed' => true, 'layer' => 2, 'reason' => "Store default: {$store->store_name}"];
            }
        }

        // Layer 3
        if ($store && $store->channel) {
            $channelCode = $store->channel->code;
            $platformMap = ['shopee' => 'shopee', 'tiktok' => 'tiktok', 'tokopedia' => 'tiktok', 'lazada' => 'lazada'];
            $platform    = $platformMap[$channelCode] ?? null;

            if ($platform) {
                $campaigns = AdsCampaign::whereHas('adsAccount', function ($q) use ($tenantId, $platform) {
                        $q->where('tenant_id', $tenantId)->where('platform', $platform)->where('is_active', true);
                    })
                    ->where('tenant_id', $tenantId)
                    ->where('status', 'ACTIVE')
                    ->get();

                if ($campaigns->count() >= 1) {
                    $best = $campaigns->sortByDesc(fn($c) => $c->actual_roas)->first();
                    return ['attributed' => true, 'layer' => 3, 'reason' => "Platform match: {$platform} → {$best->name}"];
                }
            }
        }

        return ['attributed' => false, 'layer' => null, 'reason' => 'Tidak ada campaign yang cocok'];
    }
}
