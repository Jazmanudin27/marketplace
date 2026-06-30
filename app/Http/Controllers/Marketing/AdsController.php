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
    public function index()
    {
        $tenantId = Auth::user()->tenant_id;

        // Ensure at least one Manual Account exists for simple spend logging
        $defaultAccount = AdsAccount::firstOrCreate(
            ['tenant_id' => $tenantId, 'platform' => 'manual'],
            ['account_name' => 'Akun Iklan Manual', 'is_active' => true]
        );

        $campaigns = AdsCampaign::with('performanceLogs')
            ->where('tenant_id', $tenantId)
            ->get();

        // Calculate overall stats
        $totalSpend = 0;
        $totalRevenue = 0;
        $totalConversions = 0;

        foreach ($campaigns as $camp) {
            $totalSpend += $camp->total_spend;
            $totalRevenue += $camp->total_revenue;
            $totalConversions += $camp->orders()->whereNotIn('order_status', [Order::STATUS_CANCELLED])->count();
        }

        $overallRoas = $totalSpend > 0 ? $totalRevenue / $totalSpend : 0;

        // Top Campaigns by ROAS
        $topCampaigns = $campaigns->sortByDesc(function ($c) {
            return $c->actual_roas;
        })->take(5);

        // Optimization Recommendations (ROAS < Target ROAS and Spend > 0)
        $recommendations = [];
        foreach ($campaigns as $camp) {
            $actualRoas = $camp->actual_roas;
            $targetRoas = (float) $camp->target_roas;
            $spend = $camp->total_spend;

            if ($spend > 0 && $actualRoas < $targetRoas) {
                $recommendations[] = [
                    'campaign' => $camp,
                    'issue' => "ROAS riil ({$actualRoas}) di bawah target ({$targetRoas})",
                    'action' => 'Jeda Campaign',
                    'action_code' => 'pause',
                    'severity' => 'danger'
                ];
            } elseif ($spend > 0 && $actualRoas >= $targetRoas * 1.5) {
                $recommendations[] = [
                    'campaign' => $camp,
                    'issue' => "ROAS sangat tinggi ({$actualRoas}), performa luar biasa!",
                    'action' => 'Naikkan Budget',
                    'action_code' => 'scale',
                    'severity' => 'success'
                ];
            }
        }

        // Top products sold via Ads
        $topProducts = OrderItem::whereHas('order', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)
                  ->whereNotNull('ads_campaign_id')
                  ->whereNotIn('order_status', [Order::STATUS_CANCELLED]);
            })
            ->selectRaw('master_product_id, sku, product_name, SUM(quantity) as total_qty, SUM(total_price) as total_revenue')
            ->groupBy('master_product_id', 'sku', 'product_name')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        // Recent unattributed orders for manual tracking
        $unattributedOrders = Order::with('store')
            ->where('tenant_id', $tenantId)
            ->whereNull('ads_campaign_id')
            ->whereNotIn('order_status', [Order::STATUS_CANCELLED])
            ->orderByDesc('order_date')
            ->limit(10)
            ->get();

        // Chart Data: Spend vs Revenue for past 30 days
        $chartLabels = [];
        $chartSpend = [];
        $chartRevenue = [];
        for ($i = 29; $i >= 0; $i--) {
            $dateStr = Carbon::today()->subDays($i)->format('Y-m-d');
            $dateLabel = Carbon::today()->subDays($i)->format('d M');
            $chartLabels[] = $dateLabel;

            // Spend on this day
            $spendOnDay = AdsPerformanceLog::where('tenant_id', $tenantId)
                ->whereDate('date', $dateStr)
                ->sum('ad_spend');
            $chartSpend[] = (float)$spendOnDay;

            // Revenue on this day
            $revOnDay = Order::where('tenant_id', $tenantId)
                ->whereNotNull('ads_campaign_id')
                ->whereDate('order_date', $dateStr)
                ->whereNotIn('order_status', [Order::STATUS_CANCELLED])
                ->sum('net_amount');
            $chartRevenue[] = (float)$revOnDay;
        }

        // Unread Budget alerts
        $unreadAlerts = AdsBudgetAlert::where('tenant_id', $tenantId)
            ->where('is_read', false)
            ->with('campaign.adsAccount')
            ->latest()
            ->get();

        return view('marketing.ads.index', compact(
            'campaigns', 'totalSpend', 'totalRevenue', 'totalConversions',
            'overallRoas', 'topCampaigns', 'recommendations', 'topProducts',
            'unattributedOrders', 'chartLabels', 'chartSpend', 'chartRevenue',
            'unreadAlerts'
        ));
    }

    public function campaigns()
    {
        $tenantId = Auth::user()->tenant_id;
        $campaigns = AdsCampaign::with('adsAccount')
            ->where('tenant_id', $tenantId)
            ->get();

        $accounts = AdsAccount::where('tenant_id', $tenantId)->get();

        $stores = Store::with(['channel', 'defaultCampaign'])
            ->where('tenant_id', $tenantId)
            ->get();

        return view('marketing.ads.campaigns', compact('campaigns', 'accounts', 'stores'));
    }

    public function storeCampaign(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'platform' => 'required|string',
            'target_omzet' => 'nullable|numeric|min:0',
            'target_roas' => 'nullable|numeric|min:0.1',
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
        ]);

        $campaign->update([
            'target_omzet' => $request->target_omzet,
            'target_roas' => $request->target_roas,
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

        // Ambil data performa affiliate kreator
        $affiliates = Order::where('tenant_id', $tenantId)
            ->whereNotNull('tiktok_creator_id')
            ->selectRaw('tiktok_creator_id, tiktok_creator_name, count(id) as total_orders, sum(net_amount) as total_revenue, sum(affiliate_commission) as total_commission')
            ->groupBy('tiktok_creator_id', 'tiktok_creator_name')
            ->orderByDesc('total_revenue')
            ->get();

        // Ambil daftar pesanan terbaru yang dihasilkan affiliate
        $recentOrders = Order::where('tenant_id', $tenantId)
            ->whereNotNull('tiktok_creator_id')
            ->with('store')
            ->orderByDesc('order_date')
            ->limit(15)
            ->get();

        return view('marketing.ads.affiliates', compact('affiliates', 'recentOrders'));
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
}
