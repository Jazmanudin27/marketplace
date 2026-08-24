@extends('layouts.app')

@section('title', 'Detail Transaksi - ' . $marketingTeam->name)

@section('content')
<div class="container-fluid px-4 py-4 bg-light">
    
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            @php
                $backParams = [
                    'month' => request('month'),
                    'year' => request('year'),
                    'date_from' => request('date_from'),
                    'date_to' => request('date_to')
                ];
            @endphp
            <a href="{{ route('marketing.teams.index', $backParams) }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3 mb-2 fw-medium">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Target Komisi
            </a>
            
            <h3 class="fw-bold text-dark mb-1">
                <i class="bi bi-list-check text-primary me-2"></i>Detail Transaksi: {{ $marketingTeam->name }}
            </h3>
            
            <p class="text-secondary small mb-1">
                Daftar pesanan dari toko marketplace terhubung yang masuk dalam perhitungan realisasi target & komisi.
            </p>

            <div class="d-flex align-items-center gap-2 mt-2 flex-wrap">
                <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2.5 py-1 small fw-semibold">
                    Komisi: Rp {{ number_format($rewardPerQty, 0, ',', '.') }} / Qty
                </span>
                
                @if(request()->filled('month') || request()->filled('year'))
                    <span class="badge bg-info bg-opacity-10 text-info rounded-pill px-2.5 py-1 small fw-semibold">
                        <i class="bi bi-calendar3 me-1"></i>
                        Periode: {{ request('month') ? date('F', mktime(0,0,0,request('month'),1)) : '—' }} {{ request('year') ?? '' }}
                    </span>
                @else
                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2.5 py-1 small fw-semibold">
                        <i class="bi bi-calendar-range me-1"></i>
                        Range: {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} s/d {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}
                    </span>
                @endif
            </div>
        </div>
    </div>

    <!-- KPI Summary Card Row -->
    <div class="row g-3 mb-4">
        <!-- Total Pesanan / Orders -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 rounded-3 shadow-sm bg-white p-3 h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-secondary small fw-medium d-block mb-1">Total Transaksi</span>
                        <h4 class="fw-bold text-dark mb-0">{{ number_format($orders->count()) }}</h4>
                        <span class="text-muted small mt-2 d-block">Pesanan Selesai</span>
                    </div>
                    <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-3 d-flex align-items-center justify-content-center">
                        <i class="bi bi-receipt fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Item Qty -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 rounded-3 shadow-sm bg-white p-3 h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-secondary small fw-medium d-block mb-1">Total Qty Produk</span>
                        <h4 class="fw-bold text-dark mb-0">{{ number_format($totalQty) }} <span class="fs-6 fw-normal text-muted">pcs</span></h4>
                        <span class="text-muted small mt-2 d-block">Basis komisi per Qty</span>
                    </div>
                    <div class="bg-warning bg-opacity-10 text-warning rounded-3 p-3 d-flex align-items-center justify-content-center">
                        <i class="bi bi-box-seam fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Omset -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 rounded-3 shadow-sm bg-white p-3 h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-secondary small fw-medium d-block mb-1">Total Omset</span>
                        <h4 class="fw-bold text-dark mb-0">Rp {{ number_format($totalOmset, 0, ',', '.') }}</h4>
                        <span class="text-muted small mt-2 d-block">Total Nilai Transaksi</span>
                    </div>
                    <div class="bg-info bg-opacity-10 text-info rounded-3 p-3 d-flex align-items-center justify-content-center">
                        <i class="bi bi-graph-up fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Komisi / Insentif -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 rounded-3 shadow-sm bg-white p-3 h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-secondary small fw-medium d-block mb-1">Total Akumulasi Komisi</span>
                        <h4 class="fw-bold text-success mb-0">Rp {{ number_format($totalEarnedReward, 0, ',', '.') }}</h4>
                        <span class="text-muted small mt-2 d-block">Qty × Rp {{ number_format($rewardPerQty, 0, ',', '.') }}</span>
                    </div>
                    <div class="bg-success bg-opacity-10 text-success rounded-3 p-3 d-flex align-items-center justify-content-center">
                        <i class="bi bi-cash-coin fs-4"></i>
                    </div>
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
                        <th class="py-3 text-end">Jumlah Qty</th>
                        <th class="py-3 text-end">Total Omset</th>
                        <th class="py-3 text-end pe-4">Komisi Tim</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $index => $order)
                        @php
                            // Hitung total Qty barang dalam order ini
                            $totalQtyInOrder = $order->items->sum('quantity');
                             // Hitung Qty barang yang masuk hitungan komisi
                             $commQty = $order->items->filter(function($item) {
                                 $isExcluded = false;
                                 if ($item->masterProduct && $item->masterProduct->exclude_commission) {
                                     $isExcluded = true;
                                 } elseif ($item->marketplaceProduct && $item->marketplaceProduct->masterProduct && $item->marketplaceProduct->masterProduct->exclude_commission) {
                                     $isExcluded = true;
                                 }
                                 return !$isExcluded;
                             })->sum('quantity');
                             $orderComm = $commQty * $rewardPerQty;
                            
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
                                {{ number_format($commQty) }}
                                @if($totalQtyInOrder > $commQty)
                                    <span class="text-muted small d-block" style="font-size:0.72rem; font-weight:normal;">
                                        dari {{ number_format($totalQtyInOrder) }} pcs
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 text-end fw-semibold text-primary">
                                Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                            </td>
                            <td class="py-3 text-end fw-bold text-success pe-4">
                                Rp {{ number_format($orderComm, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5">
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
                            <td class="py-3 text-end">{{ number_format($totalQty) }}</td>
                            <td class="py-3 text-end text-primary">Rp {{ number_format($totalOmset, 0, ',', '.') }}</td>
                            <td class="py-3 text-end text-success pe-4">Rp {{ number_format($totalEarnedReward, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection
