@extends('v2.layouts.app')

@section('title', 'Mutasi Dompet — ' . $store->store_name)

@push('styles')
<style>
/* Table styling */
.smk-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.82rem;
}
.smk-table thead tr {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}
.smk-table thead th {
    padding: 12px 14px;
    font-size: 0.72rem;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    white-space: nowrap;
}
.smk-table tbody tr {
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.1s;
}
.smk-table tbody tr:hover {
    background: #f0f7ff;
}
.smk-table td {
    padding: 12px 14px;
    vertical-align: middle;
    color: #334155;
}

.amount-inflow {
    font-weight: 700;
    color: #16a34a;
}
.amount-outflow {
    font-weight: 700;
    color: #dc2626;
}
.ref-code {
    font-weight: 700;
    color: #2563eb;
    background: #eff6ff;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 0.78rem;
    display: inline-block;
}
</style>
@endpush

@section('content')

{{-- ── Page Header ── --}}
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2 mb-1">
            <i class="bi bi-clock-history text-primary fs-5"></i> Mutasi Dompet — {{ $store->store_name }}
        </h1>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-dark border rounded-pill px-2.5 py-0.5" style="font-size:0.68rem; font-weight:600;">
                {{ strtoupper($store->channel->code ?? 'OTHER') }}
            </span>
            <span class="text-muted" style="font-size:0.75rem;">ID Toko: #{{ $store->marketplace_store_id }}</span>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('v2.saldo_marketplace.sync', [$store, 'days' => 60]) }}" class="btn btn-sm py-2 px-3 shadow-sm fw-semibold text-white" style="background:#16a34a; border:none; border-radius:8px;" onclick="return confirm('Tarik data mutasi terbaru dari marketplace?')">
            <i class="bi bi-arrow-repeat me-1.5"></i> Tarik Data Baru
        </a>
        <a href="{{ route('v2.saldo_marketplace.index') }}" class="btn btn-sm py-2 px-3 shadow-sm fw-semibold text-white" style="background:#1e293b; border:none; border-radius:8px;">
            <i class="bi bi-arrow-left me-1.5"></i> Kembali ke Dompet
        </a>
    </div>
</div>

{{-- ── Filter Card ── --}}
<div class="v2-card p-3.5 mb-3 shadow-sm" style="padding: 16px;">
    <form action="{{ route('v2.saldo_marketplace.mutasi', $store) }}" method="GET">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-sm-6 col-md-3">
                <label class="v2-form-label mb-1 fw-semibold text-muted small">Mulai Tanggal</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control form-control-sm v2-input" required>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <label class="v2-form-label mb-1 fw-semibold text-muted small">Sampai Tanggal</label>
                <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control form-control-sm v2-input" required>
            </div>
            <div class="col-12 col-md-6 d-flex gap-2 align-items-center">
                <button type="submit" class="btn btn-sm btn-v2-primary py-1.5 px-3.5 shadow-sm fw-semibold" style="border-radius:6px;">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                <a href="{{ route('v2.saldo_marketplace.mutasi', $store) }}" class="btn btn-sm py-1.5 px-3 fw-semibold" style="background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; border-radius:6px;">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                </a>
                <span class="text-muted ms-auto" style="font-size:0.76rem;">
                    Ditemukan <strong class="text-dark">{{ count($mutasiList) }}</strong> transaksi
                </span>
            </div>
        </div>
    </form>
</div>

{{-- ── Table Card ── --}}
<div class="v2-card p-0 shadow-sm overflow-hidden mb-3">
    <div class="py-3 px-3 bg-white border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-journals text-primary"></i>
            <span class="fw-bold text-dark" style="font-size:0.88rem;">
                Riwayat Mutasi Dompet {{ $store->store_name }}
            </span>
        </div>
        <span class="badge bg-light text-dark border py-1.5 px-3 rounded-pill" style="font-size:0.72rem; font-weight:600;">
            Total {{ count($mutasiList) }} Transaksi
        </span>
    </div>

    <div class="table-responsive">
        <table class="smk-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 45px;">No</th>
                    <th style="width: 150px;">Waktu Transaksi</th>
                    <th style="width: 170px;">ID Transaksi</th>
                    <th style="width: 150px;">Jenis Transaksi</th>
                    <th>Keterangan / Rincian</th>
                    <th class="text-end" style="width: 150px;">Jumlah</th>
                    <th class="text-end" style="width: 150px;">Saldo Akhir</th>
                </tr>
            </thead>
            <tbody>
                @forelse($mutasiList as $i => $m)
                    <tr>
                        <td class="text-center text-muted" style="font-size: 0.75rem;">{{ $i + 1 }}</td>
                        <td class="fw-medium" style="font-size:0.78rem;">{{ $m['date'] }}</td>
                        <td>
                            <span class="ref-code">{{ $m['id'] }}</span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border px-2 py-1" style="font-size:0.7rem; font-weight:600;">
                                {{ $m['type'] }}
                            </span>
                        </td>
                        <td>
                            <div class="text-wrap" style="font-size:0.78rem; line-height:1.35;">
                                {{ $m['description'] }}
                            </div>
                        </td>
                        <td class="text-end">
                            <span class="{{ $m['direction'] === 'in' ? 'amount-inflow' : 'amount-outflow' }}">
                                {{ $m['direction'] === 'in' ? '+' : '-' }} Rp {{ number_format(abs($m['amount']), 0, ',', '.') }}
                            </span>
                        </td>
                        <td class="text-end fw-bold text-dark font-monospace">
                            @if($m['current_balance'] !== null)
                                Rp {{ number_format($m['current_balance'], 0, ',', '.') }}
                            @else
                                <span class="text-muted opacity-50">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            Tidak ada data mutasi yang cocok dengan rentang tanggal ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
