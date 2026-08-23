<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\MarketingTeam;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MarketingTeamController extends Controller
{
    /**
     * Tampilkan daftar Tim Marketing & Targetnya
     */
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $teams = MarketingTeam::forTenant($tenantId)
            ->with(['stores.channel'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            })
            ->orderBy('id', 'desc')
            ->get();

        // Ambil daftar tahun unik dari data tim & orders milik tenant untuk dropdown option
        $currentYear = (int) date('Y');
        $teamYears = MarketingTeam::forTenant($tenantId)
            ->whereNotNull('period_year')
            ->pluck('period_year')
            ->toArray();
        $orderYears = DB::table('orders')
            ->where('tenant_id', $tenantId)
            ->whereNotNull('order_date')
            ->selectRaw('YEAR(order_date) as yr')
            ->pluck('yr')
            ->toArray();

        $availableYears = array_values(array_unique(array_filter(array_merge([$currentYear], $teamYears, $orderYears))));
        rsort($availableYears);

        // Parameter Filter (Default Bulan & Tahun sekarang jika tidak diisi)
        $reqMonth = $request->filled('month') ? (int) $request->month : (int) date('n');
        $reqYear  = $request->filled('year') ? (int) $request->year : $currentYear;
        $dateFrom = $request->filled('date_from') ? $request->date_from : date('Y-m-01');
        $dateTo   = $request->filled('date_to') ? $request->date_to : date('Y-m-d');

        // Hitung nilai dinamis aktual per tim berdasarkan filter
        foreach ($teams as $team) {
            if ($request->filled('month') || $request->filled('year')) {
                // Jika user memilih filter spesifik Bulan & Tahun
                $team->custom_actual_qty = $team->calculateActualQty($reqMonth, $reqYear);
                $team->custom_actual_omset = $team->calculateActualOmset($reqMonth, $reqYear);
            } else {
                // Menggunakan Range Tanggal (dateFrom s/d dateTo)
                $team->custom_actual_qty = $team->calculateActualQty(null, null, $dateFrom, $dateTo);
                $team->custom_actual_omset = $team->calculateActualOmset(null, null, $dateFrom, $dateTo);
            }

            $team->custom_total_reward = $team->custom_actual_qty * $team->reward_per_qty;
            $team->custom_progress_percent = $team->target_qty > 0 
                ? min(100.0, round(($team->custom_actual_qty / $team->target_qty) * 100, 1)) 
                : 0.0;
        }

        // Ambil daftar seluruh Toko milik tenant
        $stores = Store::where('tenant_id', $tenantId)
            ->with('channel')
            ->orderBy('store_name')
            ->get();

        // Summary KPI Metrics
        $totalTeams = $teams->count();
        $activeTeams = $teams->where('is_active', true)->count();
        $totalStoresLinked = $teams->pluck('stores')->flatten()->unique('id')->count();
        $totalTargetQty = $teams->where('is_active', true)->sum('target_qty');
        $totalTargetOmset = $teams->where('is_active', true)->sum('target_omset');
        $totalActualQty = $teams->where('is_active', true)->sum('custom_actual_qty');
        $totalActualOmset = $teams->where('is_active', true)->sum('custom_actual_omset');
        $totalEarnedReward = $teams->where('is_active', true)->sum('custom_total_reward');

        return view('marketing.teams.index', compact(
            'teams',
            'stores',
            'totalTeams',
            'activeTeams',
            'totalStoresLinked',
            'totalTargetQty',
            'totalTargetOmset',
            'totalActualQty',
            'totalActualOmset',
            'totalEarnedReward',
            'reqMonth',
            'reqYear',
            'dateFrom',
            'dateTo',
            'availableYears'
        ));
    }

    /**
     * Simpan Tim Marketing baru & Targetnya
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'target_qty' => 'required|numeric|min:0',
            'reward_per_qty' => 'required|numeric|min:0',
            'target_omset' => 'nullable|numeric|min:0',
            'period_month' => 'nullable|integer|between:1,12',
            'period_year' => 'nullable|integer|min:2020|max:2099',
            'store_ids' => 'nullable|array',
            'store_ids.*' => 'exists:stores,id',
            'description' => 'nullable|string',
        ]);

        $tenantId = Auth::user()->tenant_id;

        $team = MarketingTeam::create([
            'tenant_id' => $tenantId,
            'name' => $request->name,
            'code' => $request->code ?? null,
            'description' => $request->description,
            'target_qty' => $request->target_qty ?? 0,
            'reward_per_qty' => $request->reward_per_qty ?? 0,
            'target_omset' => $request->target_omset ?? 0,
            'period_month' => $request->period_month ?? date('n'),
            'period_year' => $request->period_year ?? date('Y'),
            'is_active' => $request->has('is_active') ? (bool) $request->is_active : true,
        ]);

        if ($request->has('store_ids')) {
            $team->stores()->sync($request->store_ids);
        }

        return redirect()->route('marketing.teams.index')
            ->with('success', "Tim Marketing '{$team->name}' & Target berhasil dibuat!");
    }

    /**
     * Update data Tim Marketing & Targetnya
     */
    public function update(Request $request, MarketingTeam $marketingTeam)
    {
        abort_unless($marketingTeam->tenant_id === Auth::user()->tenant_id, 403);

        $request->validate([
            'name' => 'required|string|max:255',
            'target_qty' => 'required|numeric|min:0',
            'reward_per_qty' => 'required|numeric|min:0',
            'target_omset' => 'nullable|numeric|min:0',
            'period_month' => 'nullable|integer|between:1,12',
            'period_year' => 'nullable|integer|min:2020|max:2099',
            'store_ids' => 'nullable|array',
            'store_ids.*' => 'exists:stores,id',
            'description' => 'nullable|string',
        ]);

        $marketingTeam->update([
            'name' => $request->name,
            'code' => $request->code ?? $marketingTeam->code,
            'description' => $request->description,
            'target_qty' => $request->target_qty ?? 0,
            'reward_per_qty' => $request->reward_per_qty ?? 0,
            'target_omset' => $request->target_omset ?? 0,
            'period_month' => $request->period_month ?? $marketingTeam->period_month,
            'period_year' => $request->period_year ?? $marketingTeam->period_year,
            'is_active' => $request->has('is_active') ? (bool) $request->is_active : false,
        ]);

        $marketingTeam->stores()->sync($request->input('store_ids', []));

        return redirect()->route('marketing.teams.index')
            ->with('success', "Data Tim Marketing '{$marketingTeam->name}' berhasil diperbarui!");
    }

    /**
     * Hapus Tim Marketing
     */
    public function destroy(MarketingTeam $marketingTeam)
    {
        abort_unless($marketingTeam->tenant_id === Auth::user()->tenant_id, 403);

        $name = $marketingTeam->name;
        $marketingTeam->delete();

        return redirect()->route('marketing.teams.index')
            ->with('success', "Tim Marketing '{$name}' berhasil dihapus.");
    }

    /**
     * Toggle Status Aktif Tim Marketing
     */
    public function toggleStatus(MarketingTeam $marketingTeam)
    {
        abort_unless($marketingTeam->tenant_id === Auth::user()->tenant_id, 403);

        $marketingTeam->is_active = !$marketingTeam->is_active;
        $marketingTeam->save();

        $statusStr = $marketingTeam->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->route('marketing.teams.index')
            ->with('success', "Status Tim '{$marketingTeam->name}' berhasil {$statusStr}.");
    }
}
