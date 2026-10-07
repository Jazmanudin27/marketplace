@extends('v2.layouts.app')

@section('title', 'Rincian Saldo Tertahan — ' . $store->store_name)

@push('styles')
<style>
/* KPI Cards */
.smk-kpi-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 14px 16px;
    position: relative;
    overflow: hidden;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.smk-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.05);
}
.smk-kpi-title {
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #64748b;
    margin-bottom: 4px;
}
.smk-kpi-value {
    font-size: 1.25rem;
    font-weight: 800;
    line-height: 1.2;
    margin-bottom: 2px;
}
.smk-kpi-sub {
    font-size: 0.72rem;
    color: #64748b;
    margin-top: 2px;
}
.smk-kpi-icon {
    position: absolute;
    right: 14px;
    bottom: 12px;
    font-size: 2rem;
    opacity: 0.12;
    pointer-events: none;
}
</style>
@endpush

@section('content')

{{-- ── Page Header ── --}}
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2 mb-0.5 fs-5">
            <i class="bi bi-hourglass-split text-warning"></i> Rincian Saldo Tertahan — {{ $store->store_name }}
        </h1>
        <div class="d-flex align-items-center gap-1.5">
            <span class="badge bg-secondary-subtle text-secondary-emphasis border rounded-pill px-2 py-0.5" style="font-size:0.65rem; font-weight:700;">
                {{ strtoupper($store->channel->code ?? 'OTHER') }}
            </span>
            <span class="text-muted" style="font-size:0.72rem;">ID Toko: #{{ $store->marketplace_store_id }}</span>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('v2.saldo_marketplace.mutasi', $store) }}" class="btn btn-sm btn-v2-primary shadow-sm fw-semibold">
            <i class="bi bi-clock-history me-1"></i> Lihat Mutasi Dompet
        </a>
        <a href="{{ route('v2.saldo_marketplace.index') }}" class="btn btn-sm btn-dark shadow-sm fw-semibold">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Dompet
        </a>
    </div>
</div>

{{-- ── Summary Cards ── --}}
<div class="row g-2.5 mb-3">
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="smk-kpi-card border-start border-warning border-4">
            <div class="smk-kpi-title text-warning">Total Saldo Tertahan</div>
            <div class="smk-kpi-value text-warning">
                Rp {{ number_format($totalPendingAmount, 0, ',', '.') }}
            </div>
            <div class="smk-kpi-sub">Estimasi bersih yang akan cair</div>
            <i class="bi bi-hourglass-split smk-kpi-icon text-warning"></i>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="smk-kpi-card border-start border-primary border-4">
            <div class="smk-kpi-title text-primary">Pesanan Pending</div>
            <div class="smk-kpi-value text-primary">
                {{ count($pendingList) }} <span class="fs-6 text-muted fw-normal">Pesanan</span>
            </div>
            <div class="smk-kpi-sub">Sedang diproses / dikirim</div>
            <i class="bi bi-box-seam smk-kpi-icon text-primary"></i>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="smk-kpi-card border-start border-dark border-4">
            <div class="smk-kpi-title text-dark">Total Nilai Kotor (Gross)</div>
            <div class="smk-kpi-value text-dark">
                Rp {{ number_format($totalGrossAmount, 0, ',', '.') }}
            </div>
            <div class="smk-kpi-sub">Nilai total belanja pembeli</div>
            <i class="bi bi-receipt smk-kpi-icon text-dark"></i>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="smk-kpi-card border-start border-danger border-4">
            <div class="smk-kpi-title text-danger">Est. Fee & Potongan</div>
            <div class="smk-kpi-value text-danger">
                Rp {{ number_format($totalFeeAmount, 0, ',', '.') }}
            </div>
            <div class="smk-kpi-sub">Komisi, biaya layanan, dll</div>
            <i class="bi bi-percent smk-kpi-icon text-danger"></i>
        </div>
    </div>
</div>

{{-- ── Filter Card ── --}}
<div class="v2-card p-3 mb-3 shadow-sm">
    <form action="{{ route('v2.saldo_marketplace.pending', $store) }}" method="GET">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-sm-6 col-md-2">
                <label class="form-label mb-1 fw-semibold text-muted small" style="font-size:0.75rem;">Mulai Tanggal</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control form-control-sm v2-input" required>
            </div>
            <div class="col-12 col-sm-6 col-md-2">
                <label class="form-label mb-1 fw-semibold text-muted small" style="font-size:0.75rem;">Sampai Tanggal</label>
                <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control form-control-sm v2-input" required>
            </div>
            <div class="col-12 col-sm-6 col-md-2">
                <label class="form-label mb-1 fw-semibold text-muted small" style="font-size:0.75rem;">Status Pesanan</label>
                <select name="status" class="form-select form-select-sm v2-input">
                    <option value="">Semua Status Aktif</option>
                    @foreach($availableStatuses as $st)
                        <option value="{{ $st }}" {{ ($statusFilter ?? '') === $st ? 'selected' : '' }}>
                            {{ $st }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label mb-1 fw-semibold text-muted small" style="font-size:0.75rem;">Cari Pesanan / Resi</label>
                <input type="text" name="search" value="{{ $search }}" class="form-control form-control-sm v2-input" placeholder="No. Pesanan / Resi / Pembeli...">
            </div>
            <div class="col-12 col-md-3 d-flex gap-1.5 align-items-center">
                <button type="submit" class="btn btn-sm btn-v2-primary py-1 px-3 shadow-sm fw-semibold flex-fill" style="border-radius:6px;">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                <a href="{{ route('v2.saldo_marketplace.pending', $store) }}" class="btn btn-sm btn-light border py-1 px-2.5 fw-semibold" style="border-radius:6px;">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                </a>
            </div>
        </div>
    </form>
</div>

{{-- ── Table Card ── --}}
<div class="v2-card p-0 shadow-sm overflow-hidden mb-3">
    <div class="py-2.5 px-3 bg-white border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-list-check text-primary"></i>
            <span class="fw-bold text-dark" style="font-size:0.85rem;">
                Daftar Pesanan Belum Cair (Escrow Pending)
            </span>
        </div>
        <span class="badge bg-secondary-subtle text-secondary-emphasis border py-1 px-2.5 rounded-pill" style="font-size:0.68rem; font-weight:600;">
            Total {{ count($pendingList) }} Pesanan
        </span>
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0" style="font-size:0.78rem;">
            <thead class="table-light">
                <tr>
                    <th class="text-center py-2" style="width: 40px; font-size:0.7rem;">No</th>
                    <th class="py-2" style="width: 120px; font-size:0.7rem;">Tanggal</th>
                    <th class="py-2" style="width: 190px; font-size:0.7rem;">No. Pesanan & Pembeli</th>
                    <th class="py-2" style="width: 130px; font-size:0.7rem;">Status</th>
                    <th class="text-end py-2" style="width: 130px; font-size:0.7rem;">Total Gross (Rp)</th>
                    <th class="text-end py-2" style="width: 130px; font-size:0.7rem;">Est. Fee (Rp)</th>
                    <th class="text-end py-2" style="width: 140px; font-size:0.7rem;">Est. Saldo Cair (Rp)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pendingList as $i => $item)
                    @php
                        $ord = $item['order'];
                    @endphp
                    <tr>
                        <td class="text-center text-muted" style="font-size: 0.72rem;">{{ $i + 1 }}</td>
                        <td class="fw-medium" style="font-size:0.75rem;">{{ $item['date_formatted'] }}</td>
                        <td>
                            <div class="fw-bold text-primary mb-0.5" style="font-size:0.78rem;">
                                {{ $ord->order_number }}
                            </div>
                            <div class="text-muted text-truncate" style="font-size:0.72rem; max-width:180px;" title="{{ $ord->customer_name }}">
                                <i class="bi bi-person me-1"></i>{{ $ord->customer_name ?? '-' }}
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-0.5" style="font-size:0.65rem; font-weight:600;">
                                {{ $item['status_label'] }}
                            </span>
                        </td>
                        <td class="text-end fw-bold text-dark font-monospace" style="font-size:0.78rem;">
                            Rp {{ number_format($item['gross_amount'], 0, ',', '.') }}
                        </td>
                        <td class="text-end text-danger fw-semibold font-monospace" style="font-size:0.78rem;">
                            - Rp {{ number_format($item['fee_amount'], 0, ',', '.') }}
                        </td>
                        <td class="text-end text-warning-emphasis fw-bold font-monospace" style="font-size:0.78rem;">
                            Rp {{ number_format($item['pending_amount'], 0, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-1 text-secondary opacity-50"></i>
                            Tidak ada pesanan pending yang cocok dengan filter ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
