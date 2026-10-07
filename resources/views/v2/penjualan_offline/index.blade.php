@extends('v2.layouts.app')

@section('title', 'Penjualan Offline (POS Kasir Toko) V2')

@push('styles')
<style>
/* ─── Penjualan Offline V2 Custom Styles ─── */
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
        <a href="{{ url('/offline-sales/create') }}" class="btn btn-sm py-1.5 px-3 shadow-sm fw-semibold text-white" style="background:#16a34a; border:none; border-radius:8px;">
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

{{-- ── Filter Section ── --}}
<div class="v2-card p-3 mb-3 shadow-sm">
    <form method="GET" action="{{ route('v2.penjualan_offline.index') }}" class="row g-2 align-items-end">
        <div class="col-12 col-md-3">
            <label class="form-label small fw-semibold text-muted mb-1" style="font-size: 0.72rem;">Cari Nota / Pembeli</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="No Nota / Nama / No HP..." class="form-control form-control-sm">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold text-muted mb-1" style="font-size: 0.72rem;">Status Nota</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">Semua Status</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Selesai (Completed)</option>
                <option value="pending_spk" {{ request('status') === 'pending_spk' ? 'selected' : '' }}>Pending SPK</option>
                <option value="spk_diproses" {{ request('status') === 'spk_diproses' ? 'selected' : '' }}>SPK Diproses</option>
                <option value="waiting_dp" {{ request('status') === 'waiting_dp' ? 'selected' : '' }}>Menunggu DP</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold text-muted mb-1" style="font-size: 0.72rem;">Status Bayar</label>
            <select name="payment_status" class="form-select form-select-sm">
                <option value="">Semua Pembayaran</option>
                <option value="lunas" {{ request('payment_status') === 'lunas' ? 'selected' : '' }}>Lunas</option>
                <option value="belum_lunas" {{ request('payment_status') === 'belum_lunas' ? 'selected' : '' }}>Belum Lunas / Piutang</option>
            </select>
        </div>
        <div class="col-6 col-md-1.5" style="width: 13.5%;">
            <label class="form-label small fw-semibold text-muted mb-1" style="font-size: 0.72rem;">Dari Tanggal</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
        </div>
        <div class="col-6 col-md-1.5" style="width: 13.5%;">
            <label class="form-label small fw-semibold text-muted mb-1" style="font-size: 0.72rem;">Sampai Tanggal</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
        </div>
        <div class="col-12 col-md-1.5 d-flex gap-1 ms-auto">
            <button type="submit" class="btn btn-sm text-white fw-semibold flex-grow-1" style="background:#1e293b; border:none;">
                <i class="bi bi-funnel me-1"></i> Filter
            </button>
            <a href="{{ route('v2.penjualan_offline.index') }}" class="btn btn-sm text-white fw-semibold" style="background:#64748b; border:none;">
                <i class="bi bi-arrow-counterclockwise"></i>
            </a>
        </div>
    </form>
</div>

{{-- ── Sales Table ── --}}
<div class="card border-0 shadow-sm rounded-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 0.82rem;">
            <thead class="table-light">
                <tr>
                    <th class="ps-3" style="width: 50px;">No</th>
                    <th>No Nota</th>
                    <th>Tanggal</th>
                    <th>Pembeli</th>
                    <th>Metode Bayar</th>
                    <th class="text-end">Grand Total</th>
                    <th class="text-end">Terbayar</th>
                    <th class="text-end">Sisa Piutang</th>
                    <th class="text-center">Status</th>
                    <th class="text-center" style="width: 100px;">Aksi</th>
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
                                <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-25 px-2 py-1">Selesai</span>
                            @elseif($sale->status === 'cancelled')
                                <span class="badge bg-danger bg-opacity-15 text-danger border border-danger border-opacity-25 px-2 py-1">Batal</span>
                            @elseif($sale->status === 'waiting_dp')
                                <span class="badge bg-warning bg-opacity-15 text-warning border border-warning border-opacity-25 px-2 py-1">Menunggu DP</span>
                            @else
                                <span class="badge bg-primary bg-opacity-15 text-primary border border-primary border-opacity-25 px-2 py-1">{{ strtoupper($sale->status) }}</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <a href="{{ route('v2.penjualan_offline.show', $sale->id) }}" class="btn btn-sm btn-outline-primary py-0.5 px-2" style="font-size: 0.75rem;" title="Lihat Detail Nota">
                                <i class="bi bi-eye me-1"></i> Detail
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary opacity-50"></i>
                            Belum ada data penjualan offline yang ditemukan.
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
