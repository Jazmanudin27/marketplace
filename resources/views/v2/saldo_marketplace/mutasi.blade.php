@extends('v2.layouts.app')

@section('title', 'Mutasi Dompet — ' . $store->store_name)

@push('styles')
<style>
.ref-code {
    font-weight: 700;
    color: #2563eb;
    background: #eff6ff;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.73rem;
    display: inline-block;
}
.amount-inflow {
    font-weight: 700;
    color: #16a34a;
}
.amount-outflow {
    font-weight: 700;
    color: #dc2626;
}
</style>
@endpush

@section('content')

{{-- ── Page Header ── --}}
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2 mb-0.5 fs-5">
            <i class="bi bi-clock-history text-primary"></i> Mutasi Dompet — {{ $store->store_name }}
        </h1>
        <div class="d-flex align-items-center gap-1.5">
            <span class="badge bg-secondary-subtle text-secondary-emphasis border rounded-pill px-2 py-0.5" style="font-size:0.65rem; font-weight:700;">
                {{ strtoupper($store->channel->code ?? 'OTHER') }}
            </span>
            <span class="text-muted" style="font-size:0.72rem;">ID Toko: #{{ $store->marketplace_store_id }}</span>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('v2.saldo_marketplace.sync', [$store, 'days' => 60]) }}" class="btn btn-sm btn-success shadow-sm fw-semibold" onclick="return confirm('Tarik data mutasi terbaru dari marketplace?')">
            <i class="bi bi-arrow-repeat me-1"></i> Tarik Data Baru
        </a>
        <a href="{{ route('v2.saldo_marketplace.index') }}" class="btn btn-sm btn-dark shadow-sm fw-semibold">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Dompet
        </a>
    </div>
</div>

{{-- ── Filter Card ── --}}
<div class="v2-card p-3 mb-3 shadow-sm">
    <form action="{{ route('v2.saldo_marketplace.mutasi', $store) }}" method="GET">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label mb-1 fw-semibold text-muted small" style="font-size:0.75rem;">Mulai Tanggal</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control form-control-sm v2-input" required>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label mb-1 fw-semibold text-muted small" style="font-size:0.75rem;">Sampai Tanggal</label>
                <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control form-control-sm v2-input" required>
            </div>
            <div class="col-12 col-md-6 d-flex gap-2 align-items-center">
                <button type="submit" class="btn btn-sm btn-v2-primary py-1 px-3 shadow-sm fw-semibold" style="border-radius:6px;">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                <a href="{{ route('v2.saldo_marketplace.mutasi', $store) }}" class="btn btn-sm btn-light border py-1 px-2.5 fw-semibold" style="border-radius:6px;">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                </a>
                <span class="text-muted ms-auto" style="font-size:0.75rem;">
                    Ditemukan <strong class="text-dark">{{ count($mutasiList) }}</strong> transaksi
                </span>
            </div>
        </div>
    </form>
</div>

{{-- ── Table Card ── --}}
<div class="v2-card p-0 shadow-sm overflow-hidden mb-3">
    <div class="py-2.5 px-3 bg-white border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-journals text-primary"></i>
            <span class="fw-bold text-dark" style="font-size:0.85rem;">
                Riwayat Mutasi Dompet {{ $store->store_name }}
            </span>
        </div>
        <span class="badge bg-secondary-subtle text-secondary-emphasis border py-1 px-2.5 rounded-pill" style="font-size:0.68rem; font-weight:600;">
            Total {{ count($mutasiList) }} Transaksi
        </span>
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0" style="font-size:0.78rem;">
            <thead class="table-light">
                <tr>
                    <th class="text-center py-2" style="width: 40px; font-size:0.7rem;">No</th>
                    <th class="py-2" style="width: 140px; font-size:0.7rem;">Waktu Transaksi</th>
                    <th class="py-2" style="width: 160px; font-size:0.7rem;">ID Transaksi</th>
                    <th class="py-2" style="width: 140px; font-size:0.7rem;">Jenis Transaksi</th>
                    <th class="py-2" style="font-size:0.7rem;">Keterangan / Rincian</th>
                    <th class="text-end py-2" style="width: 140px; font-size:0.7rem;">Jumlah</th>
                    <th class="text-end py-2" style="width: 140px; font-size:0.7rem;">Saldo Akhir</th>
                </tr>
            </thead>
            <tbody>
                @forelse($mutasiList as $i => $m)
                    <tr>
                        <td class="text-center text-muted" style="font-size: 0.72rem;">{{ $i + 1 }}</td>
                        <td class="fw-medium" style="font-size:0.75rem;">{{ $m['date'] }}</td>
                        <td>
                            <span class="ref-code">{{ $m['id'] }}</span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border px-2 py-0.5" style="font-size:0.68rem; font-weight:600;">
                                {{ $m['type'] }}
                            </span>
                        </td>
                        <td>
                            <div class="text-wrap" style="font-size:0.75rem; line-height:1.35;">
                                {{ $m['description'] }}
                            </div>
                        </td>
                        <td class="text-end">
                            <span class="{{ $m['direction'] === 'in' ? 'amount-inflow' : 'amount-outflow' }}" style="font-size:0.78rem;">
                                {{ $m['direction'] === 'in' ? '+' : '-' }} Rp {{ number_format(abs($m['amount']), 0, ',', '.') }}
                            </span>
                        </td>
                        <td class="text-end fw-bold text-dark font-monospace" style="font-size:0.78rem;">
                            @if($m['current_balance'] !== null)
                                Rp {{ number_format($m['current_balance'], 0, ',', '.') }}
                            @else
                                <span class="text-muted opacity-50">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-1 text-secondary opacity-50"></i>
                            Tidak ada data mutasi yang cocok dengan rentang tanggal ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
