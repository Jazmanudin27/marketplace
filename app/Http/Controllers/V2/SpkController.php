<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Inventory\SpkController as BaseSpkController;
use App\Models\Spk;
use Illuminate\Http\Request;

class SpkController extends BaseSpkController
{
    /**
     * Display V2 SPK Produksi Index List with Tab Filtering & Stage Counts.
     */
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $query = Spk::with(['penginput', 'items.masterProduct', 'items.progres', 'proses'])
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('no_spk', 'like', '%' . $search . '%')
                  ->orWhere('no_produksi', 'like', '%' . $search . '%')
                  ->orWhere('pemesan', 'like', '%' . $search . '%')
                  ->orWhere('instansi', 'like', '%' . $search . '%')
                  ->orWhereHas('items', function ($i) use ($search) {
                      $i->where('nama_produk', 'like', '%' . $search . '%')
                        ->orWhere('sku', 'like', '%' . $search . '%');
                  });
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('tanggal', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('deadline', '<=', $request->date_to);
        }
        if ($request->filled('tipe_spk')) {
            $query->where('tipe_spk', $request->tipe_spk);
        }

        // Group SQL query by Nomor Produksi
        $groupExpr = DB::raw("COALESCE(NULLIF(TRIM(no_produksi), ''), NULLIF(TRIM(no_pesanan), ''), DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s'))");

        $subQuery = (clone $query)
            ->reorder()
            ->select($groupExpr, DB::raw('MAX(id) as max_id'))
            ->groupBy($groupExpr);

        $groupedMaxIds = $subQuery->pluck('max_id');

        $allGroupedSpks = Spk::with(['penginput', 'items.masterProduct', 'items.progres', 'items.pickups', 'proses'])
            ->whereIn('id', $groupedMaxIds)
            ->orderByDesc('id')
            ->get();

        // Attach sub_spks collection to each production group item
        $noProduksiList = $allGroupedSpks->pluck('no_produksi')->filter()->unique()->toArray();
        $siblingSpksMap = [];
        if (!empty($noProduksiList)) {
            $allSiblings = Spk::with(['items.masterProduct', 'items.progres', 'items.pickups', 'proses'])
                ->where('tenant_id', $tenantId)
                ->whereIn('no_produksi', $noProduksiList)
                ->orderBy('id')
                ->get();
            $siblingSpksMap = $allSiblings->groupBy('no_produksi');
        }

        foreach ($allGroupedSpks as $spkItem) {
            if (!empty($spkItem->no_produksi) && isset($siblingSpksMap[$spkItem->no_produksi])) {
                $spkItem->sub_spks = $siblingSpksMap[$spkItem->no_produksi];
            } else {
                $siblings = Spk::with(['items.masterProduct', 'items.progres', 'items.pickups', 'proses'])
                    ->where('tenant_id', $tenantId)
                    ->where('created_at', $spkItem->created_at)
                    ->orderBy('id')
                    ->get();
                $spkItem->sub_spks = $siblings->isNotEmpty() ? $siblings : collect([$spkItem]);
            }
        }

        // Helper to match stage
        $matchStage = function ($spkGroup, $stage) {
            if ($stage === 'urgent') {
                return $spkGroup->contains('is_urgent', true);
            }
            if ($stage === 'in_progress') {
                return $spkGroup->contains(function ($s) {
                    $curr = strtolower($s->current_stage_name ?? '');
                    return str_contains($curr, 'potong') || str_contains($curr, 'pemotongan') ||
                           str_contains($curr, 'sablon') || str_contains($curr, 'bordir') ||
                           str_contains($curr, 'jahit') ||
                           str_contains($curr, 'lkpk') || str_contains($curr, 'kancing') ||
                           str_contains($curr, 'qc') || str_contains($curr, 'quality') ||
                           str_contains($curr, 'packing') || str_contains($curr, 'finishing') ||
                           str_contains($curr, 'sampling');
                });
            }

            return $spkGroup->contains(function ($s) use ($stage) {
                $currName = strtolower($s->current_stage_name ?? '');
                $tahap = strtolower($s->tahap_saat_ini ?? '');

                if ($stage === 'draft') {
                    return str_contains($currName, 'draft') || $tahap === 'draft';
                } elseif ($stage === 'desain') {
                    return str_contains($currName, 'desain') || str_contains($currName, 'design') || str_contains($currName, 'mockup') || str_contains($tahap, 'desain');
                } elseif ($stage === 'pesanan_baru') {
                    return (str_contains($currName, 'pesanan') || str_contains($currName, 'perencanaan') || str_contains($currName, 'perancangan')) && !str_contains($currName, 'draft') && !str_contains($currName, 'desain');
                } elseif ($stage === 'sampling') {
                    return str_contains($currName, 'sampling') || str_contains($currName, 'antrian');
                } elseif ($stage === 'potong') {
                    return str_contains($currName, 'potong') || str_contains($currName, 'pemotongan');
                } elseif ($stage === 'sablon_bordir') {
                    return str_contains($currName, 'sablon') || str_contains($currName, 'bordir');
                } elseif ($stage === 'jahit') {
                    return str_contains($currName, 'jahit');
                } elseif ($stage === 'lkpk') {
                    return str_contains($currName, 'lkpk') || str_contains($currName, 'kancing');
                } elseif ($stage === 'qc') {
                    return str_contains($currName, 'qc') || str_contains($currName, 'quality');
                } elseif ($stage === 'packing') {
                    return str_contains($currName, 'packing') || str_contains($currName, 'finishing');
                } elseif ($stage === 'selesai') {
                    return str_contains($currName, 'selesai') || str_contains($currName, 'finished');
                } elseif ($stage === 'dikirim') {
                    return str_contains($currName, 'dikirim') || str_contains($currName, 'shipped');
                }
                return str_contains($currName, $stage);
            });
        };

        // Calculate accurate tab counts across all production groups
        $definedStages = [
            'urgent', 'in_progress', 'potong', 'sablon_bordir', 'jahit',
            'lkpk', 'qc', 'packing', 'desain', 'pesanan_baru',
            'sampling', 'draft', 'selesai', 'dikirim'
        ];

        $tabCounts = ['__all__' => $allGroupedSpks->count()];
        foreach ($definedStages as $st) {
            $tabCounts[$st] = 0;
        }

        foreach ($allGroupedSpks as $row) {
            $grp = $row->sub_spks ?? collect([$row]);
            foreach ($definedStages as $st) {
                if ($matchStage($grp, $st)) {
                    $tabCounts[$st]++;
                }
            }
        }

        // Deadline status quick filter
        if ($request->filled('deadline_filter')) {
            $dlFilter = $request->deadline_filter;
            $allGroupedSpks = $allGroupedSpks->filter(function ($row) use ($dlFilter) {
                if (!$row->deadline) return false;
                $diff = (int) now()->startOfDay()->diffInDays($row->deadline->startOfDay(), false);
                if ($dlFilter === 'overdue') return $diff < 0;
                if ($dlFilter === 'near') return $diff >= 0 && $diff <= 3;
                return true;
            });
        }

        // Apply active stage tab filter
        $activeStage = $request->filled('urgent') && $request->urgent == '1' ? 'urgent' : ($request->stage ?: '');
        if (!empty($activeStage)) {
            $allGroupedSpks = $allGroupedSpks->filter(function ($row) use ($matchStage, $activeStage) {
                $grp = $row->sub_spks ?? collect([$row]);
                return $matchStage($grp, $activeStage);
            });
        }

        // Manual Pagination
        $page = \Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1;
        $perPage = 12;
        $paginatedItems = $allGroupedSpks->slice(($page - 1) * $perPage, $perPage)->values();

        $spks = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedItems,
            $allGroupedSpks->count(),
            $perPage,
            $page,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        $stats = [
            'total_produksi' => $spks->total(),
            'total_urgent'   => $tabCounts['urgent'] ?? 0,
            'total_pcs'      => (int) DB::table('spk_items')
                                    ->join('spks', 'spk_items.spk_id', '=', 'spks.id')
                                    ->where('spks.tenant_id', $tenantId)
                                    ->sum('spk_items.quantity'),
        ];

        return view('v2.spk.index', compact('spks', 'stats', 'tabCounts'));
    }

    /**
     * Display V2 SPK Produksi Create Form.
     */
    public function create(Request $request)
    {
        $response = parent::create($request);

        if ($response instanceof \Illuminate\View\View) {
            if (view()->exists('v2.spk.create')) {
                return view('v2.spk.create', $response->getData());
            }
            // Fallback to V1 view if v2 create template is not yet initialized
            return $response;
        }

        return $response;
    }

    /**
     * Display V2 SPK Produksi Show/Detail Page.
     */
    public function show(Spk $spk)
    {
        $response = parent::show($spk);

        if ($response instanceof \Illuminate\View\View) {
            if (view()->exists('v2.spk.show')) {
                return view('v2.spk.show', $response->getData());
            }
            // Fallback to V1 view if v2 show template is not yet initialized
            return $response;
        }

        return $response;
    }

    /**
     * Display V2 SPK Payments Page.
     */
    public function paymentsIndex(Request $request)
    {
        $response = parent::paymentsIndex($request);

        if ($response instanceof \Illuminate\View\View) {
            if (view()->exists('v2.spk.payments')) {
                return view('v2.spk.payments', $response->getData());
            }
            return $response;
        }

        return $response;
    }

    /**
     * Display V2 Scan Karung Page.
     */
    public function scanKarungPage(Request $request)
    {
        $response = parent::scanKarungPage($request);

        if ($response instanceof \Illuminate\View\View) {
            if (view()->exists('v2.spk.scan_karung')) {
                return view('v2.spk.scan_karung', $response->getData());
            }
            return $response;
        }

        return $response;
    }

    /**
     * Display V2 Scan Pickup Page.
     */
    public function scanPickupPage(Spk $spk)
    {
        $response = parent::scanPickupPage($spk);

        if ($response instanceof \Illuminate\View\View) {
            if (view()->exists('v2.spk.scan_pickup')) {
                return view('v2.spk.scan_pickup', $response->getData());
            }
            return $response;
        }

        return $response;
    }
}
