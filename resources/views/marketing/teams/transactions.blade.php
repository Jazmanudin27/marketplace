@extends('v2.layouts.app')

@section('title', 'Detail Transaksi - ' . $marketingTeam->name)

@section('content')
<div class="container-fluid px-3 py-3">
    
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            @php
                $backParams = [];
                if (request()->filled('month')) $backParams['month'] = request('month');
                if (request()->filled('year')) $backParams['year'] = request('year');
            @endphp
            <a href="{{ route('marketing.teams.index', $backParams) }}" class="btn btn-outline-secondary btn-sm rounded-2 px-2.5 py-1 mb-2 fw-medium d-inline-flex align-items-center gap-1" style="font-size: 0.78rem;">
                <i class="bi bi-arrow-left"></i> Kembali ke Target Komisi
            </a>

            <h5 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-list-check text-primary"></i>Detail Transaksi: {{ $marketingTeam->name }}
            </h5>

            <p class="text-muted small mb-1">
                Daftar pesanan toko marketplace yang masuk realisasi komisi berdasarkan tanggal <strong>Dana Cair / Selesai (<code>completed_at</code>)</strong>.
            </p>

            <div class="d-flex align-items-center gap-2 mt-2 flex-wrap">
                @php
                    $cType = $marketingTeam->commission_type ?: 'percentage';
                @endphp
                @if($cType === 'percentage')
                    <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2.5 py-1 small fw-semibold">
                        <i class="bi bi-percent me-1"></i>Komisi: {{ number_format($marketingTeam->commission_rate, 2) }}% dari Margin (Rp)
                        @if($marketingTeam->reward_fixed_nominal > 0)
                            (+ Bonus Rp {{ number_format($marketingTeam->reward_fixed_nominal, 0, ',', '.') }})
                        @endif
                    </span>
                @elseif($cType === 'nominal')
                    <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2.5 py-1 small fw-semibold">
                        <i class="bi bi-trophy me-1"></i>Bonus Target Margin: Rp {{ number_format($marketingTeam->reward_fixed_nominal, 0, ',', '.') }}
                    </span>
                @else
                    <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2.5 py-1 small fw-semibold">
                        Komisi: Rp {{ number_format($rewardPerQty, 0, ',', '.') }} / Qty
                    </span>
                @endif

                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
                    <i class="bi bi-lock-fill me-1"></i>
                    Periode: {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} s/d {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}
                </span>
            </div>
        </div>
    </div>

    <!-- KPI Summary Card Row V2 -->
    <div class="row g-2 mb-3">
        <!-- Total Pesanan / Orders -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="v2-stat-widget widget-blue">
                <div class="v2-stat-icon-wrapper blue">
                    <i class="bi bi-receipt"></i>
                </div>
                <div class="v2-stat-info">
                    <span class="v2-stat-num">{{ number_format($orders->count()) }}</span>
                    <span class="v2-stat-lbl">Pesanan Selesai / Dilepas</span>
                </div>
            </div>
        </div>

        <!-- Target Margin (Rp) -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="v2-stat-widget widget-amber">
                <div class="v2-stat-icon-wrapper amber">
                    <i class="bi bi-bullseye"></i>
                </div>
                <div class="v2-stat-info">
                    <span class="v2-stat-num" style="font-size: 0.95rem;">Rp {{ number_format($marketingTeam->target_omset, 0, ',', '.') }}</span>
                    @php
                        $progressMarginPct = $marketingTeam->target_omset > 0 ? min(100.0, round(($totalMargin / $marketingTeam->target_omset) * 100, 1)) : 0;
                    @endphp
                    <span class="v2-stat-lbl text-primary fw-semibold">Progress Target: {{ $progressMarginPct }}%</span>
                </div>
            </div>
        </div>

        <!-- Realisasi Margin (Rp) -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="v2-stat-widget widget-green">
                <div class="v2-stat-icon-wrapper green">
                    <i class="bi bi-cash-coin"></i>
                </div>
                <div class="v2-stat-info">
                    <span class="v2-stat-num" style="font-size: 0.95rem;">Rp {{ number_format($totalMargin, 0, ',', '.') }}</span>
                    <span class="v2-stat-lbl" style="font-size: 0.68rem;">Dilepas: Rp {{ number_format($totalValue, 0, ',', '.') }} | HPP: Rp {{ number_format($totalHpp, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <!-- Total Komisi / Insentif -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="v2-stat-widget widget-purple">
                <div class="v2-stat-icon-wrapper purple">
                    <i class="bi bi-wallet2"></i>
                </div>
                <div class="v2-stat-info">
                    <span class="v2-stat-num text-success" style="font-size: 0.95rem;">Rp {{ number_format($totalEarnedReward, 0, ',', '.') }}</span>
                    <span class="v2-stat-lbl">Akumulasi Komisi</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Connected Stores Alert -->
    <div class="card border-0 rounded-3 shadow-sm bg-white mb-4">
        <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h6 class="fw-bold text-dark mb-1 small"><i class="bi bi-shop me-1 text-primary"></i>Toko Terhubung Tim ini:</h6>
                    <div class="d-flex flex-wrap gap-1 mt-1">
                        @forelse($marketingTeam->stores as $store)
                            @php
                                $chName = strtolower($store->channel->name ?? '');
                                $badgeClass = 'bg-secondary text-white';
                                $icon = 'bi-shop';
                                if (str_contains($chName, 'shopee')) {
                                    $badgeClass = 'bg-danger text-white';
                                    $icon = 'bi-bag-check-fill';
                                } elseif (str_contains($chName, 'tiktok')) {
                                    $badgeClass = 'bg-dark text-white';
                                    $icon = 'bi-tiktok';
                                } elseif (str_contains($chName, 'tokopedia')) {
                                    $badgeClass = 'bg-success text-white';
                                    $icon = 'bi-shop-window';
                                }
                            @endphp
                            <span class="badge {{ $badgeClass }} rounded-pill px-2.5 py-1 small fw-normal">
                                <i class="bi {{ $icon }} me-1"></i>{{ $store->store_name }}
                            </span>
                        @empty
                            <span class="text-muted small fst-italic"><i class="bi bi-info-circle me-1"></i>Belum ada toko yang dihubungkan ke tim ini</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main List Card -->
    <div class="card border-0 rounded-3 shadow-sm bg-white">
        <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-dark mb-0">
                <i class="bi bi-receipt-cutoff text-primary me-2"></i>Rincian Transaksi
            </h6>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-uppercase fw-semibold">
                    <tr>
                        <th class="ps-4 py-3">No</th>
                        <th class="py-3">No. Invoice / Marketplace ID</th>
                        <th class="py-3">Toko / Channel</th>
                        <th class="py-3">Tanggal Diterima (`completed_at`)</th>
                        <th class="py-3">Pembeli</th>
                        <th class="py-3 text-center">Status</th>
                        <th class="py-3 text-end">Qty Bersih</th>
                        <th class="py-3 text-end"><i class="bi bi-cash-stack me-1 text-primary"></i>Dilepas (Rp)</th>
                        <th class="py-3 text-end"><i class="bi bi-box me-1 text-secondary"></i>HPP (Modal)</th>
                        <th class="py-3 text-end"><i class="bi bi-cash-coin me-1 text-success"></i>Margin (Rp)</th>
                        <th class="py-3 text-end pe-4"><i class="bi bi-wallet2 me-1 text-primary"></i>Komisi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $index => $order)
                        @php
                            $totalQtyInOrder = $order->items->sum('quantity');
                            $commQty = 0;
                            foreach ($order->items as $item) {
                                $isExcluded = false;
                                if ($item->masterProduct && $item->masterProduct->exclude_commission) {
                                    $isExcluded = true;
                                } elseif ($item->marketplaceProduct && $item->marketplaceProduct->masterProduct && $item->marketplaceProduct->masterProduct->exclude_commission) {
                                    $isExcluded = true;
                                }

                                if (!$isExcluded) {
                                    $returnedQty = 0;
                                    if ($order->returnOrder) {
                                        $returnedQty = $order->returnOrder->items
                                            ->where('order_item_id', $item->id)
                                            ->sum('quantity');
                                    }
                                    
                                    if ($returnedQty == 0 && $order->refund_amount > 0 && $order->total_amount > 0) {
                                        if ($order->refund_amount >= $order->total_amount) {
                                            $returnedQty = $item->quantity;
                                        } else {
                                            $ratio = (float)$order->refund_amount / (float)$order->total_amount;
                                            $returnedQty = min($item->quantity, (int) round($item->quantity * $ratio));
                                        }
                                    }
                                    
                                    $commQty += max(0, $item->quantity - $returnedQty);
                                }
                            }

                            $orderVal    = $order->calculated_released_value ?? (float)($order->net_amount > 0 ? $order->net_amount : max(0.0, (float)$order->total_amount - (float)$order->refund_amount));
                            $orderHpp    = $order->calculated_hpp ?? 0;
                            $orderMargin = $order->calculated_margin ?? round($orderVal - $orderHpp, 2);
                            $orderComm   = $order->calculated_commission ?? 0;
                            
                            $chName = strtolower($order->store->channel->name ?? '');
                            $badgeClass = 'bg-secondary text-white';
                            if (str_contains($chName, 'shopee')) {
                                $badgeClass = 'bg-danger';
                            } elseif (str_contains($chName, 'tiktok')) {
                                $badgeClass = 'bg-dark';
                            } elseif (str_contains($chName, 'tokopedia')) {
                                $badgeClass = 'bg-success';
                            }
                        @endphp
                        <tr>
                            <td class="ps-4 text-muted small fw-medium">
                                {{ $index + 1 }}
                            </td>
                            <td class="py-3">
                                <span class="fw-semibold text-dark d-block" style="font-size:0.875rem;">
                                    {{ $order->invoice_number ?: ($order->order_marketplace_id ?: '—') }}
                                </span>
                                @if($order->invoice_number && $order->order_marketplace_id)
                                    <span class="text-muted small fst-italic" style="font-size:0.75rem;">
                                        ID: {{ $order->order_marketplace_id }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3">
                                <span class="fw-bold text-dark d-block" style="font-size:0.82rem;">
                                    {{ $order->store ? $order->store->store_name : ('Toko ID #' . $order->store_id) }}
                                </span>
                                <span class="badge {{ $badgeClass }} rounded-pill px-2 py-0.5" style="font-size:0.68rem;">
                                    {{ $order->store && $order->store->channel ? $order->store->channel->name : 'Marketplace' }}
                                </span>
                            </td>
                            <td class="py-3 text-muted small">
                                <div>{{ $order->completed_at ? \Carbon\Carbon::parse($order->completed_at)->format('d M Y H:i') : ($order->order_date ? \Carbon\Carbon::parse($order->order_date)->format('d M Y H:i') : '—') }}</div>
                            </td>
                            <td class="py-3">
                                <span class="fw-medium text-dark d-block" style="font-size:0.82rem;">
                                    {{ $order->buyer_name ?: '—' }}
                                </span>
                                @if($order->buyer_phone)
                                    <span class="text-muted small d-block" style="font-size:0.72rem;">
                                        <i class="bi bi-telephone me-1"></i>{{ $order->buyer_phone }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 text-center">
                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-1 small fw-semibold">
                                    {{ strtoupper($order->order_status ?: 'SELESAI') }}
                                </span>
                            </td>
                            <td class="py-3 text-end fw-semibold text-dark">
                                {{ number_format($commQty) }} pcs
                                @php
                                    $allReturnedQty = 0;
                                    if ($order->returnOrder) {
                                        $allReturnedQty = $order->returnOrder->items->sum('quantity');
                                    }
                                    if ($allReturnedQty == 0 && $order->refund_amount > 0 && $order->total_amount > 0) {
                                        $ratio = (float)$order->refund_amount / (float)$order->total_amount;
                                        $allReturnedQty = (int) round($totalQtyInOrder * $ratio);
                                    }
                                @endphp
                                @if($allReturnedQty > 0)
                                    <span class="text-danger small d-block" style="font-size:0.72rem; font-weight:normal;">
                                        (Retur {{ number_format($allReturnedQty) }} pcs)
                                    </span>
                                @elseif($totalQtyInOrder > $commQty)
                                    <span class="text-muted small d-block" style="font-size:0.72rem; font-weight:normal;">
                                        dari {{ number_format($totalQtyInOrder) }} pcs
                                    </span>
                                @endif
                            </td>
                            <!-- Nilai Penjualan Dilepas -->
                            <td class="py-3 text-end fw-semibold text-dark">
                                Rp {{ number_format($orderVal, 0, ',', '.') }}
                                @if($order->refund_amount > 0)
                                    <span class="text-danger small d-block" style="font-size:0.72rem; font-weight:normal;">
                                        (Dipotong refund)
                                    </span>
                                @endif
                            </td>
                            <!-- HPP Modal -->
                            <td class="py-3 text-end fw-normal text-muted">
                                Rp {{ number_format($orderHpp, 0, ',', '.') }}
                            </td>
                            <!-- Margin (Rp) -->
                            <td class="py-3 text-end fw-semibold {{ $orderMargin >= 0 ? 'text-primary' : 'text-danger' }}">
                                Rp {{ number_format($orderMargin, 0, ',', '.') }}
                            </td>
                            <!-- Komisi Transaksi -->
                            <td class="py-3 text-end fw-bold text-success pe-4">
                                Rp {{ number_format($orderComm, 0, ',', '.') }}
                                @if($cType === 'percentage' && $marketingTeam->commission_rate > 0)
                                    <span class="text-muted small d-block fw-normal" style="font-size:0.72rem;">
                                        ({{ number_format($marketingTeam->commission_rate, 2) }}%)
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center py-5">
                                <div class="py-4 text-muted">
                                    <i class="bi bi-receipt fs-1 opacity-50 d-block mb-2"></i>
                                    <h6 class="fw-semibold">Tidak Ada Transaksi Ditemukan</h6>
                                    <p class="small mb-0">Tidak ada data transaksi selesai yang sesuai dengan kriteria filter.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($orders->isNotEmpty())
                    <tfoot class="table-light border-top-2 fw-bold text-dark" style="border-top: 2px solid #dee2e6; font-size: 0.9rem;">
                        <tr>
                            <td colspan="6" class="ps-4 py-3 text-start text-uppercase fw-bold">TOTAL</td>
                            <td class="py-3 text-end">{{ number_format($totalQty) }} pcs</td>
                            <td class="py-3 text-end text-dark">Rp {{ number_format($totalValue, 0, ',', '.') }}</td>
                            <td class="py-3 text-end text-muted">Rp {{ number_format($totalHpp, 0, ',', '.') }}</td>
                            <td class="py-3 text-end text-primary">Rp {{ number_format($totalMargin, 0, ',', '.') }}</td>
                            <td class="py-3 text-end text-success pe-4">Rp {{ number_format($totalEarnedReward, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection
