<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\AdsAccount;
use App\Models\AdsCampaign;
use App\Models\AdsPerformanceLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Store;
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

        return view('marketing.ads.index', compact(
            'campaigns', 'totalSpend', 'totalRevenue', 'totalConversions',
            'overallRoas', 'topCampaigns', 'recommendations', 'topProducts',
            'unattributedOrders', 'chartLabels', 'chartSpend', 'chartRevenue'
        ));
    }

    public function campaigns()
    {
        $tenantId = Auth::user()->tenant_id;
        $campaigns = AdsCampaign::with('adsAccount')
            ->where('tenant_id', $tenantId)
            ->get();

        $accounts = AdsAccount::where('tenant_id', $tenantId)->get();

        return view('marketing.ads.campaigns', compact('campaigns', 'accounts'));
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

    public function syncShopee()
    {
        try {
            \Illuminate\Support\Facades\Artisan::call('shopee-ads:sync', ['--days' => 14]);
            $output = \Illuminate\Support\Facades\Artisan::output();

            Log::info('[Shopee Ads Web Sync] Manually triggered sync', ['output' => $output]);

            return redirect()->back()->with('success', 'Sinkronisasi data iklan Shopee 14 hari terakhir berhasil dilakukan!');
        } catch (\Exception $e) {
            Log::error('[Shopee Ads Web Sync] Error', ['message' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Gagal melakukan sinkronisasi iklan Shopee: ' . $e->getMessage());
        }
    }

    public function syncTiktok()
    {
        try {
            \Illuminate\Support\Facades\Artisan::call('tiktok-ads:sync', ['--days' => 14]);
            $output = \Illuminate\Support\Facades\Artisan::output();

            Log::info('[TikTok Ads Web Sync] Manually triggered sync', ['output' => $output]);

            return redirect()->back()->with('success', 'Sinkronisasi data iklan TikTok 14 hari terakhir berhasil dilakukan!');
        } catch (\Exception $e) {
            Log::error('[TikTok Ads Web Sync] Error', ['message' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Gagal melakukan sinkronisasi iklan TikTok: ' . $e->getMessage());
        }
    }
}
