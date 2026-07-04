<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\AdsAccount;
use App\Models\AdsCampaign;
use App\Models\AdsPerformanceLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Store;
use App\Services\AutoAttributionService;
use App\Models\AdsBudgetRule;
use App\Models\AdsBudgetAlert;
use App\Models\TiktokAudience;
use App\Services\TiktokAudienceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdsController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $platform = $request->get('platform');

        // Period filter (#4)
        $period   = $request->get('period', '30'); // 7, 30, this_month, last_month
        [$dateStart, $dateEnd, $prevStart, $prevEnd] = $this->resolvePeriod($period);

        // Ensure at least one Manual Account exists for simple spend logging
        AdsAccount::firstOrCreate(
            ['tenant_id' => $tenantId, 'platform' => 'manual'],
            ['account_name' => 'Akun Iklan Manual', 'is_active' => true]
        );

        $campaignQuery = AdsCampaign::with('performanceLogs')
            ->where('tenant_id', $tenantId);

        if ($platform) {
            $campaignQuery->whereHas('adsAccount', function($q) use ($platform) {
                $q->where('platform', $platform);
            });
        }

        $campaigns = $campaignQuery->get();

        // Current period stats
        [$totalSpend, $totalRevenue, $totalConversions] = $this->calcPeriodStats(
            $tenantId, $platform, $dateStart, $dateEnd
        );
        // Previous period stats (for delta)
        [$prevSpend, $prevRevenue, $prevConversions] = $this->calcPeriodStats(
            $tenantId, $platform, $prevStart, $prevEnd
        );

        $overallRoas = $totalSpend > 0 ? $totalRevenue / $totalSpend : 0;
        $prevRoas    = $prevSpend  > 0 ? $prevRevenue  / $prevSpend  : 0;

        // Delta helper (returns signed %)
        $delta = fn($cur, $prv) => $prv > 0 ? round((($cur - $prv) / $prv) * 100, 1) : null;

        $deltas = [
            'spend'       => $delta($totalSpend, $prevSpend),
            'revenue'     => $delta($totalRevenue, $prevRevenue),
            'roas'        => $delta($overallRoas, $prevRoas),
            'conversions' => $delta($totalConversions, $prevConversions),
        ];

        // Top Campaigns by ROAS
        $topCampaigns = $campaigns->sortByDesc(fn($c) => $c->actual_roas)->take(5);

        // Optimization Recommendations
        $recommendations = [];
        foreach ($campaigns as $camp) {
            $actualRoas = $camp->actual_roas;
            $targetRoas = (float) $camp->target_roas;
            $spend      = $camp->total_spend;
            $conversions = $camp->orders()->whereNotIn('order_status', [Order::STATUS_CANCELLED])->count();
            $cpo         = $conversions > 0 ? $spend / $conversions : 0;
            $targetCpo   = (float) $camp->target_cpo;

            if ($spend > 0 && $actualRoas < $targetRoas) {
                $recommendations[] = [
                    'campaign'    => $camp,
                    'issue'       => "ROAS riil ({$actualRoas}) di bawah target ({$targetRoas})",
                    'action'      => 'Jeda Campaign',
                    'action_code' => 'pause',
                    'severity'    => 'danger'
                ];
            } elseif ($spend > 0 && $actualRoas >= $targetRoas * 1.5) {
                $recommendations[] = [
                    'campaign'    => $camp,
                    'issue'       => "ROAS sangat tinggi ({$actualRoas}), performa luar biasa!",
                    'action'      => 'Naikkan Budget',
                    'action_code' => 'scale',
                    'severity'    => 'success'
                ];
            } elseif ($targetCpo > 0 && $cpo > $targetCpo) {
                $recommendations[] = [
                    'campaign'    => $camp,
                    'issue'       => "CPO aktual (Rp " . number_format($cpo, 0, ',', '.') . ") melebihi target (Rp " . number_format($targetCpo, 0, ',', '.') . ")",
                    'action'      => 'Optimalkan Targeting',
                    'action_code' => 'pause',
                    'severity'    => 'warning'
                ];
            }
        }

        // Top products sold via Ads
        $topProducts = OrderItem::whereHas('order', function ($q) use ($tenantId, $platform) {
                $q->where('tenant_id', $tenantId)
                  ->whereNotNull('ads_campaign_id')
                  ->whereNotIn('order_status', [Order::STATUS_CANCELLED]);
                if ($platform) {
                    $q->whereHas('adsCampaign.adsAccount', fn($ac) => $ac->where('platform', $platform));
                }
            })
            ->selectRaw('master_product_id, sku, product_name, SUM(quantity) as total_qty, SUM(total_price) as total_revenue')
            ->groupBy('master_product_id', 'sku', 'product_name')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        // Recent unattributed orders
        $unattributedOrders = Order::with('store')
            ->where('tenant_id', $tenantId)
            ->whereNull('ads_campaign_id')
            ->whereNotIn('order_status', [Order::STATUS_CANCELLED])
            ->orderByDesc('order_date')
            ->limit(10)
            ->get();

        // Chart Data: Spend vs Revenue for selected period
        $chartLabels  = [];
        $chartSpend   = [];
        $chartRevenue = [];
        $days = (int) min(30, $dateStart->diffInDays($dateEnd) + 1);
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = $dateEnd->copy()->subDays($i);
            $chartLabels[] = $d->format('d M');

            $spendQ = AdsPerformanceLog::where('tenant_id', $tenantId)->whereDate('date', $d->format('Y-m-d'));
            if ($platform) $spendQ->whereHas('campaign.adsAccount', fn($q) => $q->where('platform', $platform));
            $chartSpend[] = (float) $spendQ->sum('ad_spend');

            $revQ = Order::where('tenant_id', $tenantId)->whereNotNull('ads_campaign_id')
                ->whereDate('order_date', $d->format('Y-m-d'))
                ->whereNotIn('order_status', [Order::STATUS_CANCELLED]);
            if ($platform) $revQ->whereHas('adsCampaign.adsAccount', fn($q) => $q->where('platform', $platform));
            $chartRevenue[] = (float) $revQ->sum('net_amount');
        }

        // Platform grouping
        $platformsList = ['shopee', 'tiktok', 'meta', 'google', 'manual'];
        $platformStats = [];
        foreach ($platformsList as $pf) {
            $platformStats[$pf] = ['spend' => 0.0, 'revenue' => 0.0, 'conversions' => 0, 'roas' => 0.0, 'cpc' => 0.0];
        }

        $allCampaigns = AdsCampaign::with('performanceLogs')->where('tenant_id', $tenantId)->get();
        foreach ($allCampaigns as $camp) {
            $pf = $camp->adsAccount->platform;
            if (!in_array($pf, $platformsList)) $pf = 'manual';
            $spend       = $camp->total_spend;
            $revenue     = $camp->total_revenue;
            $conversions = $camp->orders()->whereNotIn('order_status', [Order::STATUS_CANCELLED])->count();
            $platformStats[$pf]['spend']       += $spend;
            $platformStats[$pf]['revenue']     += $revenue;
            $platformStats[$pf]['conversions'] += $conversions;
        }
        foreach ($platformsList as $pf) {
            $s = $platformStats[$pf]['spend'];
            $c = $platformStats[$pf]['conversions'];
            $platformStats[$pf]['roas'] = $s > 0 ? $platformStats[$pf]['revenue'] / $s : 0.0;
            $platformStats[$pf]['cpc']  = $c > 0 ? $s / $c : 0.0;
        }

        // Unread Budget alerts
        $unreadAlerts = AdsBudgetAlert::where('tenant_id', $tenantId)
            ->where('is_read', false)->with('campaign.adsAccount')->latest()->get();

        return view('marketing.ads.index', compact(
            'campaigns', 'totalSpend', 'totalRevenue', 'totalConversions',
            'overallRoas', 'topCampaigns', 'recommendations', 'topProducts',
            'unattributedOrders', 'chartLabels', 'chartSpend', 'chartRevenue',
            'unreadAlerts', 'platformStats', 'platform', 'period', 'deltas',
            'prevSpend', 'prevRevenue', 'prevConversions', 'prevRoas'
        ));
    }

    /**
     * Resolve date range from period string.
     * Returns [$start, $end, $prevStart, $prevEnd] as Carbon instances.
     */
    private function resolvePeriod(string $period): array
    {
        switch ($period) {
            case '7':
                $start     = Carbon::today()->subDays(6);
                $end       = Carbon::today();
                $prevStart = Carbon::today()->subDays(13);
                $prevEnd   = Carbon::today()->subDays(7);
                break;
            case 'this_month':
                $start     = Carbon::now()->startOfMonth();
                $end       = Carbon::now()->endOfMonth();
                $prevStart = Carbon::now()->subMonth()->startOfMonth();
                $prevEnd   = Carbon::now()->subMonth()->endOfMonth();
                break;
            case 'last_month':
                $start     = Carbon::now()->subMonth()->startOfMonth();
                $end       = Carbon::now()->subMonth()->endOfMonth();
                $prevStart = Carbon::now()->subMonths(2)->startOfMonth();
                $prevEnd   = Carbon::now()->subMonths(2)->endOfMonth();
                break;
            default: // 30
                $start     = Carbon::today()->subDays(29);
                $end       = Carbon::today();
                $prevStart = Carbon::today()->subDays(59);
                $prevEnd   = Carbon::today()->subDays(30);
        }
        return [$start, $end, $prevStart, $prevEnd];
    }

    /**
     * Calculate spend, revenue, conversions for a given period.
     */
    private function calcPeriodStats(int $tenantId, ?string $platform, Carbon $start, Carbon $end): array
    {
        $spendQ = AdsPerformanceLog::where('tenant_id', $tenantId)
            ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')]);
        if ($platform) $spendQ->whereHas('campaign.adsAccount', fn($q) => $q->where('platform', $platform));
        $spend = (float) $spendQ->sum('ad_spend');

        $revQ = Order::where('tenant_id', $tenantId)
            ->whereNotNull('ads_campaign_id')
            ->whereBetween('order_date', [$start, $end])
            ->whereNotIn('order_status', [Order::STATUS_CANCELLED]);
        if ($platform) $revQ->whereHas('adsCampaign.adsAccount', fn($q) => $q->where('platform', $platform));
        $revenue = (float) $revQ->sum('net_amount');

        $convQ = Order::where('tenant_id', $tenantId)
            ->whereNotNull('ads_campaign_id')
            ->whereBetween('order_date', [$start, $end])
            ->whereNotIn('order_status', [Order::STATUS_CANCELLED]);
        if ($platform) $convQ->whereHas('adsCampaign.adsAccount', fn($q) => $q->where('platform', $platform));
        $conversions = $convQ->count();

        return [$spend, $revenue, $conversions];
    }

    public function campaigns(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $platform = $request->get('platform');

        $query = AdsCampaign::with('adsAccount')
            ->where('tenant_id', $tenantId);

        if ($platform) {
            $query->whereHas('adsAccount', function($q) use ($platform) {
                $q->where('platform', $platform);
            });
        }

        $campaigns = $query->get();
        $accounts = AdsAccount::where('tenant_id', $tenantId)->get();

        $stores = Store::with(['channel', 'defaultCampaign'])
            ->where('tenant_id', $tenantId)
            ->get();

        return view('marketing.ads.campaigns', compact('campaigns', 'accounts', 'stores', 'platform'));
    }

    public function storeCampaign(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'platform' => 'required|string',
            'target_omzet' => 'nullable|numeric|min:0',
            'target_roas' => 'nullable|numeric|min:0.1',
            'target_cpo' => 'nullable|numeric|min:0',
        ]);

        $tenantId = Auth::user()->tenant_id;

        // Get or create account for the selected platform
        $account = AdsAccount::firstOrCreate(
            ['tenant_id' => $tenantId, 'platform' => $request->platform],
            ['account_name' => 'Akun ' . ucfirst($request->platform), 'is_active' => true]
        );

        AdsCampaign::create([
            'tenant_id' => $tenantId,
            'ads_account_id' => $account->id,
            'name' => $request->name,
            'target_omzet' => $request->target_omzet ?? 0,
            'target_roas' => $request->target_roas ?? 1.00,
            'target_cpo' => $request->target_cpo ?? 0,
            'status' => 'ACTIVE',
            'is_active' => true
        ]);

        return redirect()->back()->with('success', 'Campaign berhasil ditambahkan.');
    }

    public function updateCampaign(Request $request, AdsCampaign $campaign)
    {
        $request->validate([
            'target_omzet' => 'required|numeric|min:0',
            'target_roas' => 'required|numeric|min:0.1',
            'target_cpo' => 'nullable|numeric|min:0',
        ]);

        $campaign->update([
            'target_omzet' => $request->target_omzet,
            'target_roas' => $request->target_roas,
            'target_cpo' => $request->target_cpo ?? 0,
        ]);

        return redirect()->back()->with('success', 'Target Campaign berhasil diperbarui.');
    }

    public function toggleCampaign(Request $request, AdsCampaign $campaign)
    {
        $newStatus = $campaign->status === 'ACTIVE' ? 'PAUSED' : 'ACTIVE';
        
        $campaign->update([
            'status' => $newStatus,
            'is_active' => $newStatus === 'ACTIVE'
        ]);

        // Simulasikan pemanggilan API ke Meta/Google Ads
        $platform = ucfirst($campaign->adsAccount->platform);
        $message = "Campaign '{$campaign->name}' berhasil di-{$newStatus} di database.";
        
        if ($campaign->adsAccount->platform !== 'manual') {
            $message .= " Koneksi API {$platform} disimulasikan: Campaign juga telah di-{$newStatus} di panel {$platform} Ads Manager.";
        }

        return redirect()->back()->with('success', $message);
    }

    public function destroyCampaign(AdsCampaign $campaign)
    {
        $campaign->delete();
        return redirect()->back()->with('success', 'Campaign berhasil dihapus.');
    }

    public function logs()
    {
        $tenantId = Auth::user()->tenant_id;
        $campaigns = AdsCampaign::where('tenant_id', $tenantId)->get();

        $logs = AdsPerformanceLog::with('campaign')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('date')
            ->paginate(15);

        return view('marketing.ads.logs', compact('logs', 'campaigns'));
    }

    public function exportCsv()
    {
        $tenantId = \Illuminate\Support\Facades\Auth::user()->tenant_id;
        $logs = \App\Models\AdsPerformanceLog::with('campaign')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('date')
            ->get();

        $headers = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename=ads_performance_report_' . date('Y-m-d') . '.csv',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0'
        ];

        $columns = ['Tanggal', 'Campaign', 'Platform', 'Spend (Rp)', 'Attributed Revenue (Rp)', 'ROAS', 'Clicks', 'Impressions'];

        $callback = function() use($logs, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($logs as $log) {
                $roas = $log->ad_spend > 0 ? round($log->attributed_revenue / $log->ad_spend, 2) : 0;
                fputcsv($file, [
                    $log->date->format('Y-m-d'),
                    $log->campaign->name ?? 'Deleted Campaign',
                    strtoupper($log->campaign->adsAccount->platform ?? 'MANUAL'),
                    $log->ad_spend,
                    $log->attributed_revenue,
                    $roas . 'x',
                    $log->clicks ?? 0,
                    $log->impressions ?? 0
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function storeLog(Request $request)
    {
        $request->validate([
            'ads_campaign_id' => 'required|exists:ads_campaigns,id',
            'date' => 'required|date',
            'ad_spend' => 'required|numeric|min:0',
            'clicks' => 'nullable|integer|min:0',
            'impressions' => 'nullable|integer|min:0',
        ]);

        $tenantId = Auth::user()->tenant_id;

        AdsPerformanceLog::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'ads_campaign_id' => $request->ads_campaign_id,
                'date' => $request->date,
            ],
            [
                'ad_spend' => $request->ad_spend,
                'clicks' => $request->clicks ?? 0,
                'impressions' => $request->impressions ?? 0,
            ]
        );

        return redirect()->back()->with('success', 'Log biaya iklan berhasil disimpan.');
    }

    public function attributeOrder(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'ads_campaign_id' => 'required|exists:ads_campaigns,id',
        ]);

        $order = Order::findOrFail($request->order_id);
        $order->update([
            'ads_campaign_id' => $request->ads_campaign_id
        ]);

        return redirect()->back()->with('success', 'Pesanan berhasil dikaitkan ke Campaign iklan.');
    }

    /**
     * Jalankan auto-attribution on-demand dari UI.
     */
    public function autoAttributeNow(AutoAttributionService $service)
    {
        $tenantId = Auth::user()->tenant_id;

        try {
            $result = $service->attributeBatch($tenantId, 200);

            $message = "Auto-atribusi selesai: {$result['attributed']} dari {$result['total']} pesanan berhasil ditautkan ke campaign iklan.";
            if ($result['skipped'] > 0) {
                $message .= " {$result['skipped']} pesanan tidak dapat dicocokkan (tidak ada campaign yang sesuai).";
            }

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            Log::error('[AutoAttribution] On-demand error', ['message' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Gagal menjalankan auto-atribusi: ' . $e->getMessage());
        }
    }

    /**
     * Simpan default campaign untuk sebuah toko.
     */
    public function setStoreDefaultCampaign(Request $request)
    {
        $request->validate([
            'store_id'            => 'required|exists:stores,id',
            'default_campaign_id' => 'nullable|exists:ads_campaigns,id',
        ]);

        $store = Store::where('tenant_id', Auth::user()->tenant_id)
            ->findOrFail($request->store_id);

        $store->update(['default_campaign_id' => $request->default_campaign_id ?: null]);

        return redirect()->back()->with('success', 'Default campaign toko berhasil disimpan.');
    }

    public function syncAll()
    {
        try {
            // 1. Sync Shopee Ads
            \Illuminate\Support\Facades\Artisan::call('shopee-ads:sync', ['--days' => 14]);
            $shopeeOutput = \Illuminate\Support\Facades\Artisan::output();

            // 2. Sync TikTok Ads
            \Illuminate\Support\Facades\Artisan::call('tiktok-ads:sync', ['--days' => 14]);
            $tiktokOutput = \Illuminate\Support\Facades\Artisan::output();

            Log::info('[Ads Web Sync] Manually triggered sync for all platforms', [
                'shopee' => $shopeeOutput,
                'tiktok' => $tiktokOutput
            ]);

            return redirect()->back()->with('success', 'Sinkronisasi seluruh data iklan (Shopee & TikTok) 14 hari terakhir berhasil dilakukan!');
        } catch (\Exception $e) {
            Log::error('[Ads Web Sync] Error', ['message' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Gagal melakukan sinkronisasi iklan: ' . $e->getMessage());
        }
    }

    public function catalogFeed($tenantId)
    {
        $tenant = \App\Models\Tenant::findOrFail($tenantId);
        $products = \App\Models\MasterProduct::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with(['brand', 'category'])
            ->get();

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><rss version="2.0" xmlns:g="http://base.google.com/ns/1.0"/>');
        $channel = $xml->addChild('channel');
        $channel->addChild('title', htmlspecialchars($tenant->name . ' Product Catalog'));
        $channel->addChild('link', config('app.url'));
        $channel->addChild('description', 'Auto-generated product feed for TikTok and Meta Ads');

        foreach ($products as $product) {
            $item = $channel->addChild('item');
            $item->addChild('g:id', 'prod_' . $product->id, 'http://base.google.com/ns/1.0');
            $item->addChild('g:title', htmlspecialchars($product->name), 'http://base.google.com/ns/1.0');
            $item->addChild('g:description', htmlspecialchars($product->description ?? 'Produk ' . $product->name), 'http://base.google.com/ns/1.0');
            
            // availability
            $availability = $product->stock > $product->min_stock ? 'in stock' : 'out of stock';
            $item->addChild('g:availability', $availability, 'http://base.google.com/ns/1.0');
            
            // price (example: 150000 IDR)
            $item->addChild('g:price', ((int)$product->price) . ' IDR', 'http://base.google.com/ns/1.0');
            
            // link & image_link
            $item->addChild('g:link', config('app.url') . '/products/' . $product->id, 'http://base.google.com/ns/1.0');
            
            $imageUrl = $product->image_url ?: 'https://placehold.co/600x600/png?text=' . urlencode($product->name);
            if (!str_starts_with($imageUrl, 'http')) {
                $imageUrl = asset($imageUrl);
            }
            $item->addChild('g:image_link', $imageUrl, 'http://base.google.com/ns/1.0');
            
            // brand
            $brandName = $product->brand ? $product->brand->name : 'Generic';
            $item->addChild('g:brand', htmlspecialchars($brandName), 'http://base.google.com/ns/1.0');
            
            // condition
            $item->addChild('g:condition', 'new', 'http://base.google.com/ns/1.0');
        }

        return response($xml->asXML(), 200, [
            'Content-Type' => 'application/xml',
        ]);
    }

    public function saveTiktokCapiSettings(Request $request)
    {
        $request->validate([
            'pixel_id' => 'nullable|string|max:255',
            'events_access_token' => 'nullable|string',
            'advertiser_id' => 'nullable|string|max:255',
        ]);

        $tenantId = Auth::user()->tenant_id;
        $account = AdsAccount::firstOrCreate(
            ['tenant_id' => $tenantId, 'platform' => 'tiktok'],
            ['account_name' => 'Akun TikTok Ads', 'is_active' => true]
        );

        $account->update([
            'pixel_id' => $request->pixel_id,
            'events_access_token' => $request->events_access_token,
            'advertiser_id' => $request->advertiser_id,
        ]);

        return redirect()->back()->with('success', 'Pengaturan TikTok CAPI & Advertiser ID berhasil disimpan.');
    }

    public function budgetRules()
    {
        $tenantId = Auth::user()->tenant_id;
        $rules = AdsBudgetRule::where('tenant_id', $tenantId)->with('campaign')->get();
        $campaigns = AdsCampaign::where('tenant_id', $tenantId)->get();
        $alerts = AdsBudgetAlert::where('tenant_id', $tenantId)->with('campaign')->latest()->get();

        return view('marketing.ads.budget_rules', compact('rules', 'campaigns', 'alerts'));
    }

    public function storeBudgetRule(Request $request)
    {
        $request->validate([
            'ads_campaign_id' => 'required|exists:ads_campaigns,id',
            'name' => 'required|string|max:255',
            'condition' => 'required|string',
            'threshold_value' => 'required|numeric|min:0',
            'action' => 'required|string',
            'whatsapp_recipient' => 'nullable|string|max:50',
        ]);

        $tenantId = Auth::user()->tenant_id;

        // Normalisasi nomor telepon jika diisi
        $recipient = $request->whatsapp_recipient;
        if (!empty($recipient)) {
            $recipient = preg_replace('/\D/', '', $recipient);
            if (str_starts_with($recipient, '0')) {
                $recipient = '62' . substr($recipient, 1);
            } elseif (!str_starts_with($recipient, '62')) {
                $recipient = '62' . $recipient;
            }
        }

        AdsBudgetRule::create([
            'tenant_id' => $tenantId,
            'ads_campaign_id' => $request->ads_campaign_id,
            'name' => $request->name,
            'condition' => $request->condition,
            'threshold_value' => $request->threshold_value,
            'action' => $request->action,
            'whatsapp_recipient' => $recipient ?: null,
            'is_active' => true,
        ]);

        return redirect()->back()->with('success', 'Aturan budget berhasil ditambahkan.');
    }

    public function destroyBudgetRule(AdsBudgetRule $rule)
    {
        if ($rule->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }
        $rule->delete();
        return redirect()->back()->with('success', 'Aturan budget berhasil dihapus.');
    }

    public function markAlertRead(AdsBudgetAlert $alert)
    {
        if ($alert->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }
        $alert->markAsRead();
        return redirect()->back()->with('success', 'Alert ditandai sebagai dibaca.');
    }

    public function audiences()
    {
        $tenantId = Auth::user()->tenant_id;
        $audiences = TiktokAudience::where('tenant_id', $tenantId)->with('adsAccount')->get();
        $accounts = AdsAccount::where('tenant_id', $tenantId)->where('platform', 'tiktok')->get();

        return view('marketing.ads.audiences', compact('audiences', 'accounts'));
    }

    public function storeAudience(Request $request)
    {
        $request->validate([
            'ads_account_id' => 'required|exists:ads_accounts,id',
            'name' => 'required|string|max:255',
            'type' => 'required|string',
        ]);

        $tenantId = Auth::user()->tenant_id;

        TiktokAudience::create([
            'tenant_id' => $tenantId,
            'ads_account_id' => $request->ads_account_id,
            'name' => $request->name,
            'type' => $request->type,
            'status' => TiktokAudience::STATUS_PENDING,
        ]);

        return redirect()->back()->with('success', 'Custom Audience baru berhasil ditambahkan di ERP. Klik Sync untuk sinkronisasi ke TikTok.');
    }

    public function syncAudience(TiktokAudience $audience, TiktokAudienceService $service)
    {
        if ($audience->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        try {
            $success = $service->syncAudience($audience);
            if ($success) {
                return redirect()->back()->with('success', 'Sinkronisasi ke TikTok Custom Audience berhasil!');
            } else {
                return redirect()->back()->with('error', 'Gagal sinkronisasi: ' . ($audience->error_message ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal sinkronisasi: ' . $e->getMessage());
        }
    }

    public function destroyAudience(TiktokAudience $audience)
    {
        if ($audience->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }
        $audience->delete();
        return redirect()->back()->with('success', 'Audience berhasil dihapus dari ERP.');
    }

    public function affiliates()
    {
        $tenantId = Auth::user()->tenant_id;

        // Ambil data performa affiliate kreator TikTok
        $affiliates = Order::where('tenant_id', $tenantId)
            ->whereNotNull('tiktok_creator_id')
            ->selectRaw('tiktok_creator_id, tiktok_creator_name, count(id) as total_orders, sum(net_amount) as total_revenue, sum(affiliate_commission) as total_commission')
            ->groupBy('tiktok_creator_id', 'tiktok_creator_name')
            ->orderByDesc('total_revenue')
            ->get();

        // Ambil data performa affiliate Shopee (UTM Keyword / Sub-ID)
        $shopeeAffiliates = Order::where('tenant_id', $tenantId)
            ->whereNotNull('shopee_utm_keyword')
            ->selectRaw('shopee_utm_keyword, count(id) as total_orders, sum(net_amount) as total_revenue, sum(discount_amount) as total_discounts')
            ->groupBy('shopee_utm_keyword')
            ->orderByDesc('total_revenue')
            ->get();

        // Ambil daftar pesanan terbaru yang dihasilkan affiliate (TikTok & Shopee)
        $recentOrders = Order::where('tenant_id', $tenantId)
            ->where(function($q) {
                $q->whereNotNull('tiktok_creator_id')
                  ->orWhereNotNull('shopee_utm_keyword');
            })
            ->with('store')
            ->orderByDesc('order_date')
            ->limit(20)
            ->get();

        return view('marketing.ads.affiliates', compact('affiliates', 'shopeeAffiliates', 'recentOrders'));
    }

    public function liveSessions()
    {
        $tenantId = Auth::user()->tenant_id;

        // Ambil toko TikTok yang connected
        $stores = Store::where('tenant_id', $tenantId)
            ->whereHas('channel', function ($q) {
                $q->where('code', 'tiktok');
            })->get();

        // Ambil semua sesi live
        $sessions = \App\Models\TiktokLiveSession::where('tenant_id', $tenantId)
            ->with('store')
            ->latest()
            ->get();

        // Agregasi performa host menggunakan PHP collection
        $hosts = [];
        foreach ($sessions as $session) {
            $host = $session->host_name;
            if (!isset($hosts[$host])) {
                $hosts[$host] = [
                    'host_name' => $host,
                    'total_sessions' => 0,
                    'total_orders' => 0,
                    'total_revenue' => 0,
                ];
            }
            $hosts[$host]['total_sessions']++;
            $hosts[$host]['total_orders'] += $session->total_orders;
            $hosts[$host]['total_revenue'] += $session->total_revenue;
        }

        $hosts = collect($hosts)->sortByDesc('total_revenue');

        return view('marketing.ads.live_sessions', compact('stores', 'sessions', 'hosts'));
    }

    public function startLiveSession(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'title' => 'required|string|max:255',
            'host_name' => 'required|string|max:255',
        ]);

        $tenantId = Auth::user()->tenant_id;

        // Pastikan tidak ada sesi live yang sedang aktif untuk toko yang sama
        $activeExists = \App\Models\TiktokLiveSession::where('tenant_id', $tenantId)
            ->where('store_id', $request->store_id)
            ->where('status', \App\Models\TiktokLiveSession::STATUS_LIVE)
            ->exists();

        if ($activeExists) {
            return redirect()->back()->with('error', 'Sesi LIVE untuk toko ini sedang berjalan. Selesaikan sesi sebelumnya terlebih dahulu.');
        }

        \App\Models\TiktokLiveSession::create([
            'tenant_id' => $tenantId,
            'store_id' => $request->store_id,
            'title' => $request->title,
            'host_name' => $request->host_name,
            'start_time' => now(),
            'status' => \App\Models\TiktokLiveSession::STATUS_LIVE,
        ]);

        return redirect()->back()->with('success', 'Sesi LIVE TikTok berhasil dimulai! Pesanan masuk akan otomatis ditautkan.');
    }

    public function endLiveSession(\App\Models\TiktokLiveSession $session)
    {
        if ($session->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $session->update([
            'status' => \App\Models\TiktokLiveSession::STATUS_COMPLETED,
            'end_time' => now(),
        ]);

        return redirect()->back()->with('success', 'Sesi LIVE TikTok berhasil diakhiri. Performa host tercatat.');
    }

    public function destroyLiveSession(\App\Models\TiktokLiveSession $session)
    {
        if ($session->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $session->delete();
        return redirect()->back()->with('success', 'Sesi LIVE berhasil dihapus.');
    }

    public function rfm(\App\Services\RfmService $rfmService)
    {
        $tenantId = Auth::user()->tenant_id;
        $segments = $rfmService->analyze($tenantId);

        // Ambil akun iklan tiktok untuk opsi sinkronisasi
        $adsAccounts = AdsAccount::where('tenant_id', $tenantId)
            ->where('platform', 'tiktok')
            ->where('is_active', true)
            ->get();

        return view('marketing.ads.rfm', compact('segments', 'adsAccounts'));
    }

    public function syncRfmSegment(Request $request, \App\Services\RfmService $rfmService, TiktokAudienceService $syncService)
    {
        $request->validate([
            'segment_name' => 'required|string',
            'ads_account_id' => 'required|exists:ads_accounts,id',
        ]);

        $tenantId = Auth::user()->tenant_id;
        $segments = $rfmService->analyze($tenantId);
        $segmentName = $request->segment_name;

        if (!isset($segments[$segmentName])) {
            return redirect()->back()->with('error', 'Segmen tidak ditemukan.');
        }

        $customers = $segments[$segmentName];
        if ($customers->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada pelanggan dalam segmen ini untuk disinkronisasikan.');
        }

        // Ambil daftar nomor telepon
        $phones = $customers->pluck('phone')->toArray();

        // Cari atau buat TiktokAudience
        $audience = TiktokAudience::firstOrCreate([
            'tenant_id' => $tenantId,
            'ads_account_id' => $request->ads_account_id,
            'name' => 'RFM - ' . $segmentName . ' (' . now()->format('d M Y') . ')',
        ], [
            'type' => 'customer_list',
            'status' => TiktokAudience::STATUS_PENDING,
        ]);

        try {
            $success = $syncService->syncAudience($audience, $phones);
            if ($success) {
                return redirect()->route('marketing.ads.audiences')->with('success', "Segmen RFM {$segmentName} berhasil dikirim ke TikTok Custom Audience!");
            } else {
                return redirect()->back()->with('error', 'Gagal mengirim data ke TikTok: ' . ($audience->error_message ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal sinkronisasi: ' . $e->getMessage());
        }
    }

    public function abTest()
    {
        return view('marketing.ads.ab_test');
    }

    public function shopeeLiveSessions()
    {
        $tenantId = Auth::user()->tenant_id;

        // Ambil toko Shopee yang connected
        $stores = Store::where('tenant_id', $tenantId)
            ->whereHas('channel', function ($q) {
                $q->where('code', 'shopee');
            })->get();

        // Ambil semua sesi live
        $sessions = \App\Models\ShopeeLiveSession::where('tenant_id', $tenantId)
            ->with('store')
            ->latest()
            ->get();

        // Agregasi performa host menggunakan PHP collection
        $hosts = [];
        foreach ($sessions as $session) {
            $host = $session->host_name;
            if (!isset($hosts[$host])) {
                $hosts[$host] = [
                    'host_name' => $host,
                    'total_sessions' => 0,
                    'total_orders' => 0,
                    'total_revenue' => 0,
                ];
            }
            $hosts[$host]['total_sessions']++;
            $hosts[$host]['total_orders'] += $session->total_orders;
            $hosts[$host]['total_revenue'] += $session->total_revenue;
        }

        $hosts = collect($hosts)->sortByDesc('total_revenue');

        return view('marketing.ads.shopee_live', compact('stores', 'sessions', 'hosts'));
    }

    public function startShopeeLiveSession(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'title' => 'required|string|max:255',
            'host_name' => 'required|string|max:255',
        ]);

        $tenantId = Auth::user()->tenant_id;

        // Pastikan tidak ada sesi live yang sedang aktif untuk toko yang sama
        $activeExists = \App\Models\ShopeeLiveSession::where('tenant_id', $tenantId)
            ->where('store_id', $request->store_id)
            ->where('status', \App\Models\ShopeeLiveSession::STATUS_LIVE)
            ->exists();

        if ($activeExists) {
            return redirect()->back()->with('error', 'Sesi LIVE Shopee untuk toko ini sedang berjalan. Selesaikan sesi sebelumnya terlebih dahulu.');
        }

        \App\Models\ShopeeLiveSession::create([
            'tenant_id' => $tenantId,
            'store_id' => $request->store_id,
            'title' => $request->title,
            'host_name' => $request->host_name,
            'start_time' => now(),
            'status' => \App\Models\ShopeeLiveSession::STATUS_LIVE,
        ]);

        return redirect()->back()->with('success', 'Sesi LIVE Shopee berhasil dimulai! Pesanan masuk akan otomatis ditautkan.');
    }

    public function endShopeeLiveSession(\App\Models\ShopeeLiveSession $session)
    {
        if ($session->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $session->update([
            'status' => \App\Models\ShopeeLiveSession::STATUS_COMPLETED,
            'end_time' => now(),
        ]);

        return redirect()->back()->with('success', 'Sesi LIVE Shopee berhasil diakhiri. Performa host tercatat.');
    }

    public function destroyShopeeLiveSession(\App\Models\ShopeeLiveSession $session)
    {
        if ($session->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $session->delete();
        return redirect()->back()->with('success', 'Sesi LIVE Shopee berhasil dihapus.');
    }

    public function roasCalculator()
    {
        $tenantId = Auth::user()->tenant_id;
        $products = \App\Models\MasterProduct::where('tenant_id', $tenantId)->get(['id', 'name', 'sku', 'price', 'cost_price']);
        return view('marketing.ads.roas_calculator', compact('products'));
    }

    // =========================================================================
    // #1 — LAPORAN PER PRODUK
    // =========================================================================
    public function productReport(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $platform  = $request->get('platform');
        $period    = $request->get('period', '30');
        $campaignId = $request->get('campaign_id');

        [$dateStart, $dateEnd] = array_slice($this->resolvePeriod($period), 0, 2);

        $query = OrderItem::with('masterProduct')
            ->whereHas('order', function ($q) use ($tenantId, $platform, $campaignId, $dateStart, $dateEnd) {
                $q->where('tenant_id', $tenantId)
                  ->whereNotNull('ads_campaign_id')
                  ->whereNotIn('order_status', [Order::STATUS_CANCELLED])
                  ->whereBetween('order_date', [$dateStart, $dateEnd]);
                if ($platform) {
                    $q->whereHas('adsCampaign.adsAccount', fn($a) => $a->where('platform', $platform));
                }
                if ($campaignId) {
                    $q->where('ads_campaign_id', $campaignId);
                }
            })
            ->selectRaw('
                master_product_id,
                sku,
                product_name,
                SUM(quantity)      as total_qty,
                SUM(total_price)   as total_revenue,
                SUM(hpp_subtotal)  as total_hpp,
                AVG(cost_price)    as avg_cost
            ')
            ->groupBy('master_product_id', 'sku', 'product_name')
            ->orderByDesc('total_revenue');

        $products = $query->get()->map(function ($p) {
            $p->gross_profit  = $p->total_revenue - $p->total_hpp;
            $p->gross_margin  = $p->total_revenue > 0
                ? round(($p->gross_profit / $p->total_revenue) * 100, 1)
                : 0;
            return $p;
        });

        // Total spend yang teratribusi (hanya campaign yang punya order di periode ini)
        $spendQ = AdsPerformanceLog::where('tenant_id', $tenantId)
            ->whereBetween('date', [$dateStart->format('Y-m-d'), $dateEnd->format('Y-m-d')]);
        if ($platform) $spendQ->whereHas('campaign.adsAccount', fn($q) => $q->where('platform', $platform));
        if ($campaignId) $spendQ->where('ads_campaign_id', $campaignId);
        $totalSpend = (float) $spendQ->sum('ad_spend');

        $totalRevenue = $products->sum('total_revenue');
        $totalHpp     = $products->sum('total_hpp');
        $totalQty     = $products->sum('total_qty');
        $overallRoas  = $totalSpend > 0 ? round($totalRevenue / $totalSpend, 2) : 0;

        $campaigns = AdsCampaign::where('tenant_id', $tenantId)->with('adsAccount')->get();

        return view('marketing.ads.product_report', compact(
            'products', 'campaigns', 'platform', 'period', 'campaignId',
            'totalSpend', 'totalRevenue', 'totalHpp', 'totalQty', 'overallRoas',
            'dateStart', 'dateEnd'
        ));
    }

    // #2 — EXPORT per-produk CSV
    public function exportProductReport(Request $request)
    {
        $tenantId   = Auth::user()->tenant_id;
        $platform   = $request->get('platform');
        $period     = $request->get('period', '30');
        $campaignId = $request->get('campaign_id');

        [$dateStart, $dateEnd] = array_slice($this->resolvePeriod($period), 0, 2);

        $rows = OrderItem::whereHas('order', function ($q) use ($tenantId, $platform, $campaignId, $dateStart, $dateEnd) {
                $q->where('tenant_id', $tenantId)
                  ->whereNotNull('ads_campaign_id')
                  ->whereNotIn('order_status', [Order::STATUS_CANCELLED])
                  ->whereBetween('order_date', [$dateStart, $dateEnd]);
                if ($platform) $q->whereHas('adsCampaign.adsAccount', fn($a) => $a->where('platform', $platform));
                if ($campaignId) $q->where('ads_campaign_id', $campaignId);
            })
            ->selectRaw('sku, product_name, SUM(quantity) as total_qty, SUM(total_price) as total_revenue, SUM(hpp_subtotal) as total_hpp')
            ->groupBy('sku', 'product_name')
            ->orderByDesc('total_revenue')
            ->get();

        $headers = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename=ads_product_report_' . date('Y-m-d') . '.csv',
            'Pragma'              => 'no-cache',
        ];

        $callback = function () use ($rows) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['SKU', 'Nama Produk', 'Total QTY', 'Total Revenue (Rp)', 'Total HPP (Rp)', 'Gross Profit (Rp)', 'Gross Margin (%)']);
            foreach ($rows as $r) {
                $gp     = $r->total_revenue - $r->total_hpp;
                $margin = $r->total_revenue > 0 ? round(($gp / $r->total_revenue) * 100, 1) : 0;
                fputcsv($file, [$r->sku, $r->product_name, $r->total_qty, $r->total_revenue, $r->total_hpp, $gp, $margin . '%']);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // =========================================================================
    // #3 — CAMPAIGN DETAIL DRILL-DOWN
    // =========================================================================
    public function campaignDetail(AdsCampaign $campaign)
    {
        $tenantId = Auth::user()->tenant_id;
        if ($campaign->tenant_id !== $tenantId) abort(403);

        $campaign->load('adsAccount', 'performanceLogs');

        // Orders teratribusi ke campaign ini
        $orders = Order::with('store')
            ->where('ads_campaign_id', $campaign->id)
            ->whereNotIn('order_status', [Order::STATUS_CANCELLED])
            ->orderByDesc('order_date')
            ->paginate(20);

        // Produk terlaris dari campaign ini
        $topProducts = OrderItem::whereHas('order', fn($q) =>
                $q->where('ads_campaign_id', $campaign->id)
                  ->whereNotIn('order_status', [Order::STATUS_CANCELLED])
            )
            ->selectRaw('sku, product_name, SUM(quantity) as total_qty, SUM(total_price) as total_revenue, SUM(hpp_subtotal) as total_hpp')
            ->groupBy('sku', 'product_name')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get();

        // Chart 30 hari: Spend vs Revenue
        $chartLabels  = [];
        $chartSpend   = [];
        $chartRevenue = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = Carbon::today()->subDays($i);
            $chartLabels[]  = $d->format('d M');
            $chartSpend[]   = (float) $campaign->performanceLogs()->whereDate('date', $d)->sum('ad_spend');
            $chartRevenue[] = (float) Order::where('ads_campaign_id', $campaign->id)
                ->whereDate('order_date', $d)
                ->whereNotIn('order_status', [Order::STATUS_CANCELLED])
                ->sum('net_amount');
        }

        // Stats aggregate
        $totalSpend   = $campaign->total_spend;
        $totalRevenue = $campaign->total_revenue;
        $totalConversions = Order::where('ads_campaign_id', $campaign->id)
            ->whereNotIn('order_status', [Order::STATUS_CANCELLED])->count();
        $cpo  = $totalConversions > 0 ? round($totalSpend / $totalConversions, 0) : 0;
        $roas = $totalSpend > 0 ? round($totalRevenue / $totalSpend, 2) : 0;

        return view('marketing.ads.campaign_detail', compact(
            'campaign', 'orders', 'topProducts',
            'chartLabels', 'chartSpend', 'chartRevenue',
            'totalSpend', 'totalRevenue', 'totalConversions', 'cpo', 'roas'
        ));
    }

    // =========================================================================
    // #6 — HEATMAP JAM & HARI
    // =========================================================================
    public function heatmap(Request $request)
    {
        $tenantId   = Auth::user()->tenant_id;
        $platform   = $request->get('platform');
        $campaignId = $request->get('campaign_id');

        $query = Order::where('tenant_id', $tenantId)
            ->whereNotNull('ads_campaign_id')
            ->whereNotIn('order_status', [Order::STATUS_CANCELLED])
            ->whereNotNull('order_date');

        if ($platform) {
            $query->whereHas('adsCampaign.adsAccount', fn($q) => $q->where('platform', $platform));
        }
        if ($campaignId) {
            $query->where('ads_campaign_id', $campaignId);
        }

        // Aggregate by day-of-week (1=Sun..7=Sat) and hour (0-23)
        $rawData = $query->selectRaw('
                DAYOFWEEK(order_date) as dow,
                HOUR(order_date)      as hour,
                COUNT(*)              as total_orders,
                SUM(net_amount)       as total_revenue
            ')
            ->groupBy('dow', 'hour')
            ->get();

        // Build 7×24 matrix
        $matrix = [];
        $days   = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
        for ($dow = 1; $dow <= 7; $dow++) {
            for ($h = 0; $h <= 23; $h++) {
                $matrix[$dow][$h] = ['orders' => 0, 'revenue' => 0];
            }
        }

        $maxOrders = 0;
        foreach ($rawData as $row) {
            $matrix[$row->dow][$row->hour] = [
                'orders'  => $row->total_orders,
                'revenue' => $row->total_revenue,
            ];
            if ($row->total_orders > $maxOrders) $maxOrders = $row->total_orders;
        }

        $campaigns = AdsCampaign::where('tenant_id', $tenantId)->with('adsAccount')->get();

        return view('marketing.ads.heatmap', compact(
            'matrix', 'days', 'maxOrders', 'campaigns', 'platform', 'campaignId'
        ));
    }
}

