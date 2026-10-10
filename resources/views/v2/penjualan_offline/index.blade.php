@extends('v2.layouts.app')

@section('title', 'Penjualan Offline (POS Kasir Toko) V2')

@push('styles')
<style>
/* ─── Pesanan-style Tab & Filter Bar Overrides ─── */
.pos-tab-bar {
    display: flex;
    overflow-x: auto;
    scrollbar-width: none;
    background: #fff;
    border-bottom: 1px solid #e5e7eb;
    gap: 0;
}
.pos-tab-bar::-webkit-scrollbar { display: none; }

.pos-tab {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 12px 18px;
    font-size: 0.8rem;
    font-weight: 500;
    color: #6b7280;
    white-space: nowrap;
    text-decoration: none;
    border-bottom: 2px solid transparent;
    transition: color .15s, border-color .15s;
    position: relative;
}
.pos-tab:hover { color: #3b82f6; text-decoration: none; }
.pos-tab.active {
    color: #3b82f6;
    border-bottom-color: #3b82f6;
    font-weight: 600;
    background: #eff6ff;
}
.pos-tab .tab-badge {
    font-size: 0.65rem;
    font-weight: 700;
    border-radius: 999px;
    padding: 1px 6px;
    background: #3b82f6;
    color: #fff;
    min-width: 18px;
    text-align: center;
    line-height: 1.5;
}
.pos-tab:not(.active) .tab-badge {
    background: #e5e7eb;
    color: #6b7280;
}
.pos-tab.active .tab-badge { background: #2563eb; }

/* Sub-tabs pills */
.pos-sub-bar {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
    flex-wrap: wrap;
}
.pos-sub-label { font-size: 0.74rem; font-weight: 600; color: #6b7280; white-space: nowrap; }
.pos-sub-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 12px;
    border-radius: 999px;
    font-size: 0.74rem;
    font-weight: 500;
    border: 1px solid #e5e7eb;
    background: #fff;
    color: #6b7280;
    text-decoration: none;
    transition: all .15s;
    white-space: nowrap;
}
.pos-sub-pill:hover { border-color: #3b82f6; color: #3b82f6; text-decoration: none; }
.pos-sub-pill.active {
    background: #3b82f6;
    border-color: #3b82f6;
    color: #fff;
    box-shadow: 0 2px 6px rgba(59,130,246,.3);
}
.pos-sub-pill .pill-n {
    background: rgba(255,255,255,.25);
    border-radius: 999px;
    padding: 0 5px;
    font-size: 0.68rem;
    font-weight: 700;
}
.pos-sub-pill:not(.active) .pill-n { background: #f0f0f0; color: #555; }

/* Filter bar */
.pos-filter-bar {
    padding: 10px 14px;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
}
.pos-filter-bar .form-label { font-size: 0.72rem; font-weight: 600; color: #6b7280; margin-bottom: 3px; }
.pos-filter-bar .form-control,
.pos-filter-bar .form-select {
    font-size: 0.79rem;
    border-radius: 6px;
    border: 1px solid #d1d5db;
    padding: 5px 9px;
    height: 31px;
}
.pos-filter-bar .form-control:focus,
.pos-filter-bar .form-select:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 2px rgba(59,130,246,.15);
}

/* Summary bar */
.pos-summary-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 14px;
    background: #fff;
    border-bottom: 1px solid #e5e7eb;
    font-size: 0.79rem;
    color: #6b7280;
}

/* KPI Cards */
.pos-kpi-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 14px 16px;
    position: relative;
    overflow: hidden;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.pos-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}
.pos-kpi-title {
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: #6b7280;
    margin-bottom: 4px;
}
.pos-kpi-value {
    font-size: 1.25rem;
    font-weight: 700;
    line-height: 1.2;
}
.pos-kpi-sub {
    font-size: 0.7rem;
    color: #6b7280;
    margin-top: 4px;
}
.pos-kpi-icon {
    position: absolute;
    right: 12px;
    bottom: 12px;
    font-size: 2.2rem;
    opacity: 0.12;
    pointer-events: none;
}

.sale-no {
    font-weight: 700;
    color: #2563eb;
    background: #eff6ff;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 0.78rem;
    font-family: 'Courier New', monospace;
}
</style>
@endpush

@section('content')

{{-- ── Page Header ── --}}
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2 mb-0">
            <i class="bi bi-shop-window text-primary fs-5"></i> Penjualan Offline (POS Store)
        </h1>
        <p class="text-muted small mb-0">Kelola daftar transaksi toko offline, kasir POS, dan status pembayaran nota</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('v2.penjualan_offline.index') }}" class="btn btn-sm py-1.5 px-3 shadow-sm fw-semibold" style="background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; border-radius:8px;" title="Refresh Data">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </a>
        <a href="{{ route('v2.penjualan_offline.create') }}" class="btn btn-sm py-1.5 px-3 shadow-sm fw-semibold text-white" style="background:#16a34a; border:none; border-radius:8px;">
            <i class="bi bi-plus-circle me-1"></i> Buka Kasir POS Baru
        </a>
    </div>
</div>

{{-- ── KPI Summary Cards ── --}}
<div class="row g-3 mb-4">
    <!-- Card 1: Total Omset POS -->
    <div class="col-md-3 col-sm-6 col-12">
        <div class="pos-kpi-card border-start border-primary border-3">
            <div class="pos-kpi-title text-primary">Total Omset Penjualan</div>
            <div class="pos-kpi-value text-primary">Rp {{ number_format($totalSalesOmset, 0, ',', '.') }}</div>
            <div class="pos-kpi-sub">Dari {{ $totalSalesCount }} Transaksi Toko</div>
            <i class="bi bi-cart-check pos-kpi-icon text-primary"></i>
        </div>
    </div>
    <!-- Card 2: Total Uang Terbayar -->
    <div class="col-md-3 col-sm-6 col-12">
        <div class="pos-kpi-card border-start border-success border-3">
            <div class="pos-kpi-title text-success">Total Terbayar (Lunas/DP)</div>
            <div class="pos-kpi-value text-success">Rp {{ number_format($totalSalesPaid, 0, ',', '.') }}</div>
            <div class="pos-kpi-sub">Uang Masuk Kasir / Bank</div>
            <i class="bi bi-check-circle pos-kpi-icon text-success"></i>
        </div>
    </div>
    <!-- Card 3: Total Sisa Piutang -->
    <div class="col-md-3 col-sm-6 col-12">
        <div class="pos-kpi-card border-start border-danger border-3">
            <div class="pos-kpi-title text-danger">Sisa Piutang Pelanggan</div>
            <div class="pos-kpi-value text-danger">Rp {{ number_format($totalPiutang, 0, ',', '.') }}</div>
            <div class="pos-kpi-sub">Belum Dilunasi</div>
            <i class="bi bi-exclamation-circle pos-kpi-icon text-danger"></i>
        </div>
    </div>
    <!-- Card 4: Total Nota / Transaksi -->
    <div class="col-md-3 col-sm-6 col-12">
        <div class="pos-kpi-card border-start border-info border-3">
            <div class="pos-kpi-title text-info">Total Nota Diterbitkan</div>
            <div class="pos-kpi-value text-info">{{ number_format($totalSalesCount, 0, ',', '.') }} Nota</div>
            <div class="pos-kpi-sub">Transaksi Non-Batal</div>
            <i class="bi bi-receipt pos-kpi-icon text-info"></i>
        </div>
    </div>
</div>

{{-- ── Main Card (Tabs & Filter Mirip Pesanan Masuk) ── --}}
<div class="v2-card p-0 shadow-sm overflow-hidden mb-4">

    {{-- Status Tabs --}}
    @php
        $currentStatus = request('status', '');
        $tabStatuses = [
            ''              => ['label' => 'Semua', 'icon' => 'bi bi-list-ul', 'countKey' => '__all__'],
            'completed'     => ['label' => 'Selesai', 'icon' => 'bi bi-check-circle', 'countKey' => 'completed'],
            'pending_spk'   => ['label' => 'Pending SPK', 'icon' => 'bi bi-clock-history', 'countKey' => 'pending_spk'],
            'spk_diproses'  => ['label' => 'SPK Diproses', 'icon' => 'bi bi-gear-wide-connected', 'countKey' => 'spk_diproses'],
            'waiting_dp'    => ['label' => 'Menunggu DP', 'icon' => 'bi bi-hourglass-split', 'countKey' => 'waiting_dp'],
            'cancelled'     => ['label' => 'Dibatalkan', 'icon' => 'bi bi-x-circle', 'countKey' => 'cancelled'],
        ];
    @endphp
    <div class="pos-tab-bar" role="tablist">
        @foreach($tabStatuses as $tabKey => $tabInfo)
            @php
                $tabUrl = route('v2.penjualan_offline.index', array_merge(
                    request()->except(['status','page']),
                    $tabKey !== '' ? ['status' => $tabKey] : [],
                ));
                $isActive = $currentStatus === $tabKey;
                $count    = $tabCounts[$tabInfo['countKey']] ?? 0;
            @endphp
            <a class="pos-tab {{ $isActive ? 'active' : '' }}" href="{{ $tabUrl }}" role="tab">
                <i class="{{ $tabInfo['icon'] }}" style="font-size:.8rem;"></i>
                {{ $tabInfo['label'] }}
                @if($count > 0)
                    <span class="tab-badge">{{ $count > 999 ? '999+' : $count }}</span>
                @endif
            </a>
        @endforeach
    </div>

    {{-- Payment Sub-Tabs --}}
    @php
        $currentPayment = request('payment_status', '');
        $subPaymentTabs = [
            ''            => ['label' => 'Semua Status Bayar', 'countKey' => '__all__'],
            'lunas'       => ['label' => 'Lunas', 'countKey' => 'lunas'],
            'belum_lunas' => ['label' => 'Belum Lunas / Piutang', 'countKey' => 'belum_lunas'],
        ];
    @endphp
    <div class="pos-sub-bar">
        <span class="pos-sub-label"><i class="bi bi-funnel me-1"></i>Status Bayar:</span>
        @foreach($subPaymentTabs as $ptKey => $ptInfo)
            @php
                $ptUrl = route('v2.penjualan_offline.index', array_merge(
                    request()->except(['payment_status','page']),
                    $ptKey !== '' ? ['payment_status' => $ptKey] : [],
                ));
                $ptActive = $currentPayment === $ptKey;
                $ptCount  = $paymentCounts[$ptInfo['countKey']] ?? 0;
            @endphp
            <a href="{{ $ptUrl }}" class="pos-sub-pill {{ $ptActive ? 'active' : '' }}">
                {{ $ptInfo['label'] }}
                @if($ptCount > 0)
                    <span class="pill-n">{{ $ptCount > 999 ? '999+' : $ptCount }}</span>
                @endif
            </a>
        @endforeach
    </div>

    {{-- Filter Bar --}}
    <div class="pos-filter-bar">
        <form method="GET" action="{{ route('v2.penjualan_offline.index') }}" id="pos-filter-form">
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
            @if(request('payment_status'))
                <input type="hidden" name="payment_status" value="{{ request('payment_status') }}">
            @endif
            <div class="row g-2 align-items-end">
                {{-- Cari --}}
                <div class="col-12 col-md-3">
                    <label class="form-label"><i class="bi bi-search me-1"></i>No. Nota / Pembeli / HP</label>
                    <input type="text" name="search" class="form-control"
                           placeholder="Cari no nota, nama, nomor telepon..."
                           value="{{ request('search') }}">
                </div>

                {{-- Metode Pembayaran --}}
                <div class="col-6 col-md-2">
                    <label class="form-label"><i class="bi bi-credit-card me-1"></i>Metode Bayar</label>
                    <select name="payment_method" class="form-select">
                        <option value="">Semua Metode</option>
                        <option value="tunai" {{ request('payment_method') === 'tunai' ? 'selected' : '' }}>Tunai / Cash</option>
                        <option value="transfer" {{ request('payment_method') === 'transfer' ? 'selected' : '' }}>Transfer Bank</option>
                        <option value="qris" {{ request('payment_method') === 'qris' ? 'selected' : '' }}>QRIS</option>
                        <option value="piutang" {{ request('payment_method') === 'piutang' ? 'selected' : '' }}>Kredit / Piutang</option>
                    </select>
                </div>

                {{-- Tipe Transaksi PO --}}
                <div class="col-6 col-md-2">
                    <label class="form-label"><i class="bi bi-bag-check me-1"></i>Tipe Transaksi</label>
                    <select name="is_po" class="form-select">
                        <option value="">Semua Tipe</option>
                        <option value="walk_in" {{ request('is_po') === 'walk_in' ? 'selected' : '' }}>Langsung / Walk-in</option>
                        <option value="po" {{ request('is_po') === 'po' ? 'selected' : '' }}>Pre-Order (PO)</option>
                    </select>
                </div>

                {{-- Dari Tanggal --}}
                <div class="col-6 col-md-2">
                    <label class="form-label"><i class="bi bi-calendar-event me-1"></i>Dari Tanggal</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
                </div>

                {{-- Sampai Tanggal --}}
                <div class="col-6 col-md-2">
                    <label class="form-label"><i class="bi bi-calendar-event me-1"></i>Sampai Tanggal</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
                </div>

                {{-- Actions --}}
                <div class="col-12 col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary flex-grow-1" style="height: 31px;" title="Terapkan Filter">
                        <i class="bi bi-filter"></i>
                    </button>
                    @if(request()->hasAny(['search', 'payment_method', 'is_po', 'date_from', 'date_to', 'status', 'payment_status']))
                        <a href="{{ route('v2.penjualan_offline.index') }}" class="btn btn-sm btn-outline-secondary" style="height: 31px;" title="Reset Filter">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    {{-- Summary Bar --}}
    <div class="pos-summary-bar">
        <div>
            <i class="bi bi-list-check me-1"></i>
            Menampilkan <strong>{{ $sales->firstItem() ?? 0 }} - {{ $sales->lastItem() ?? 0 }}</strong> dari <strong>{{ $sales->total() }}</strong> transaksi
        </div>
        @if(request()->hasAny(['status', 'payment_status', 'search', 'date_from', 'date_to']))
            <div>
                <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1">
                    <i class="bi bi-funnel-fill text-primary me-1"></i>Filter Aktif
                </span>
            </div>
        @endif
    </div>

    {{-- Sales Table --}}
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 0.82rem;">
            <thead class="table-light text-uppercase fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.03em;">
                <tr>
                    <th class="ps-3 py-2.5" style="width: 50px;">No</th>
                    <th class="py-2.5">No. Nota</th>
                    <th class="py-2.5">Tanggal</th>
                    <th class="py-2.5">Pembeli</th>
                    <th class="py-2.5">Metode Bayar</th>
                    <th class="text-end py-2.5">Grand Total</th>
                    <th class="text-end py-2.5">Terbayar</th>
                    <th class="text-end py-2.5">Sisa Piutang</th>
                    <th class="text-center py-2.5">Status</th>
                    <th class="text-center pe-3 py-2.5" style="width: 100px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sales as $index => $sale)
                    @php
                        $sisa = max(0, (float)$sale->grand_total - (float)$sale->paid_amount);
                        $isLunas = (float)$sale->paid_amount >= (float)$sale->grand_total;
                    @endphp
                    <tr>
                        <td class="ps-3 text-muted">{{ $sales->firstItem() + $index }}</td>
                        <td>
                            <span class="sale-no">{{ $sale->sale_number }}</span>
                            @if($sale->is_po)
                                <span class="badge bg-purple text-white ms-1" style="font-size: 0.65rem; background:#8b5cf6;">PO</span>
                            @endif
                        </td>
                        <td>{{ \Carbon\Carbon::parse($sale->sold_at)->format('d/m/Y H:i') }}</td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $sale->buyer_name ?: 'Umum (Pelanggan Walk-in)' }}</div>
                            @if($sale->buyer_phone)
                                <small class="text-muted"><i class="bi bi-telephone me-1"></i>{{ $sale->buyer_phone }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                {{ strtoupper($sale->payment_method ?: 'TUNAI') }}
                            </span>
                        </td>
                        <td class="text-end font-monospace fw-bold">Rp {{ number_format($sale->grand_total, 0, ',', '.') }}</td>
                        <td class="text-end font-monospace text-success fw-semibold">Rp {{ number_format($sale->paid_amount, 0, ',', '.') }}</td>
                        <td class="text-end font-monospace {{ $sisa > 0 ? 'text-danger fw-bold' : 'text-muted' }}">
                            {{ $sisa > 0 ? 'Rp '.number_format($sisa, 0, ',', '.') : '-' }}
                        </td>
                        <td class="text-center">
                            @if($sale->status === 'completed')
                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2.5 py-1" style="font-size: 0.72rem; font-weight: 600;">
                                    <i class="bi bi-check-circle me-1"></i>Selesai
                                </span>
                            @elseif($sale->status === 'cancelled')
                                <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-2.5 py-1" style="font-size: 0.72rem; font-weight: 600;">
                                    <i class="bi bi-x-circle me-1"></i>Batal
                                </span>
                            @elseif($sale->status === 'waiting_dp')
                                <span class="badge bg-warning bg-opacity-15 text-dark rounded-pill px-2.5 py-1" style="font-size: 0.72rem; font-weight: 600;">
                                    <i class="bi bi-hourglass-split me-1"></i>Menunggu DP
                                </span>
                            @elseif($sale->status === 'pending_spk' || $sale->status === 'belum_spk')
                                <span class="badge bg-info bg-opacity-15 text-primary rounded-pill px-2.5 py-1" style="font-size: 0.72rem; font-weight: 600;">
                                    <i class="bi bi-clock-history me-1"></i>Pending SPK
                                </span>
                            @elseif($sale->status === 'spk_diproses')
                                <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2.5 py-1" style="font-size: 0.72rem; font-weight: 600;">
                                    <i class="bi bi-gear-wide-connected me-1"></i>SPK Diproses
                                </span>
                            @else
                                <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-2.5 py-1" style="font-size: 0.72rem; font-weight: 600;">
                                    {{ strtoupper(str_replace('_', ' ', $sale->status)) }}
                                </span>
                            @endif
                        </td>
                        <td class="text-center pe-3">
                            <a href="{{ route('v2.penjualan_offline.show', $sale->id) }}" class="btn btn-sm btn-outline-primary py-0.5 px-2 rounded-pill" style="font-size: 0.75rem;" title="Lihat Detail Nota">
                                <i class="bi bi-eye me-1"></i> Detail
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary opacity-50"></i>
                            Belum ada data penjualan offline yang sesuai dengan filter.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($sales->hasPages())
        <div class="card-footer bg-white py-2 border-top">
            {{ $sales->links() }}
        </div>
    @endif
</div>

@endsection
