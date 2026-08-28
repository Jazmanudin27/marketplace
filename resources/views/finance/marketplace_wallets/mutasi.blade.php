@extends('layouts.app')

@section('title', 'Mutasi Dompet — ' . $store->store_name)

@section('content')
<div class="container-fluid p-4">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('finance.marketplace_wallets.index') }}" class="btn btn-outline-secondary rounded-2">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h4 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                    <i class="fas fa-history text-primary"></i>
                    Mutasi Dompet — <span class="text-secondary">{{ $store->store_name }}</span>
                </h4>
                <p class="text-muted mb-0 small">
                    Platform: <span class="badge bg-secondary rounded-pill small">{{ strtoupper($store->channel->code) }}</span> | ID Toko: {{ $store->marketplace_store_id }}
                </p>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <form action="{{ route('finance.marketplace_wallets.mutasi', $store) }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label text-muted small fw-semibold">Mulai Tanggal</label>
                    <input type="date" name="date_from" class="form-control rounded-2 small" value="{{ $dateFrom }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted small fw-semibold">Sampai Tanggal</label>
                    <input type="date" name="date_to" class="form-control rounded-2 small" value="{{ $dateTo }}" required>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary rounded-2 py-2 px-4 w-100 fw-semibold small">
                        <i class="fas fa-filter me-1.5"></i> Filter
                    </button>
                    <a href="{{ route('finance.marketplace_wallets.mutasi', $store) }}" class="btn btn-outline-secondary rounded-2 py-2 px-3 fw-semibold small" title="Reset">
                        <i class="fas fa-undo"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Error Alert --}}
    @if($error)
    <div class="alert alert-danger rounded-3 mb-4 d-flex align-items-center small" role="alert">
        <i class="fas fa-exclamation-triangle me-2 fs-5"></i>
        <div>
            Gagal menarik data mutasi dari API: <strong>{{ $error }}</strong>. <br>
            Pastikan koneksi/token integrasi toko Anda valid.
        </div>
    </div>
    @endif

    {{-- Mutasi Table --}}
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-transparent border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
            <span class="fw-bold text-dark mb-0">
                <i class="fas fa-list text-secondary me-1.5"></i> Riwayat Transaksi Dompet
            </span>
            <span class="badge bg-secondary rounded-pill small">{{ count($mutasiList) }} transaksi ditemukan</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="text-uppercase small font-monospace text-muted">
                            <th class="ps-4" style="width: 15%;">Waktu Transaksi</th>
                            <th style="width: 20%;">ID Transaksi</th>
                            <th style="width: 15%;">Jenis Transaksi</th>
                            <th style="width: 20%;">Keterangan / Rincian</th>
                            <th class="text-end" style="width: 15%;">Jumlah</th>
                            <th class="text-end pe-4" style="width: 15%;">Saldo Akhir</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($mutasiList as $m)
                            <tr>
                                <td class="ps-4 small text-secondary">
                                    {{ $m['date'] }}
                                </td>
                                <td class="font-monospace small text-dark fw-semibold">
                                    {{ $m['id'] }}
                                </td>
                                <td>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill small">
                                        {{ $m['type'] }}
                                    </span>
                                </td>
                                <td class="small text-secondary text-wrap">
                                    {{ $m['description'] }}
                                </td>
                                <td class="text-end font-monospace small fw-bold {{ $m['direction'] === 'in' ? 'text-success' : 'text-danger' }}">
                                    {{ $m['direction'] === 'in' ? '+' : '-' }} Rp {{ number_format(abs($m['amount']), 0, ',', '.') }}
                                </td>
                                <td class="text-end font-monospace small fw-semibold pe-4">
                                    @if($m['current_balance'] !== null)
                                        Rp {{ number_format($m['current_balance'], 0, ',', '.') }}
                                    @else
                                        <span class="text-muted opacity-50">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5 small">
                                    <i class="fas fa-inbox me-2 opacity-50 fs-4"></i> <br>
                                    Tidak ada data mutasi yang cocok dengan rentang tanggal ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
