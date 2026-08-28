@extends('layouts.app')

@section('title', 'Mutasi Dompet — ' . $store->store_name)
@section('page-title', 'Mutasi Dompet — ' . $store->store_name)

@section('content')
    <div class="row">
        <div class="col-md-12">

            {{-- ── Filter Bar (Styled like Users Page Filter) ──────────────────────── --}}
            <div class="card border shadow-sm mb-3">
                <div class="card-body py-3 px-3">
                    <form action="{{ route('finance.marketplace_wallets.mutasi', $store) }}" method="GET" id="filterForm">
                        <div class="row g-2 align-items-end">
                            {{-- Mulai Tanggal --}}
                            <div class="col-md-3">
                                <label class="form-label form-label-sm fw-semibold mb-1 text-dark">
                                    <i class="fas fa-calendar text-muted me-1"></i>Mulai Tanggal
                                </label>
                                <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control form-control-sm" required>
                            </div>

                            {{-- Sampai Tanggal --}}
                            <div class="col-md-3">
                                <label class="form-label form-label-sm fw-semibold mb-1 text-dark">
                                    <i class="fas fa-calendar-check text-muted me-1"></i>Sampai Tanggal
                                </label>
                                <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control form-control-sm" required>
                            </div>

                            {{-- Buttons --}}
                            <div class="col-md-auto">
                                <button type="submit" class="btn btn-primary btn-sm px-3">
                                    <i class="fas fa-filter me-1"></i>Filter
                                </button>
                                <a href="{{ route('finance.marketplace_wallets.mutasi', $store) }}" class="btn btn-secondary btn-sm px-3 ms-1" title="Reset">
                                    <i class="fas fa-undo me-1"></i>Reset
                                </a>
                            </div>

                            {{-- Summary Count --}}
                            <div class="col-md ms-auto text-end">
                                <span class="text-muted small">
                                    Ditemukan <strong class="text-dark">{{ count($mutasiList) }}</strong> transaksi
                                </span>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Alert Messages --}}
            @foreach(['success','error','info'] as $type)
                @if(session($type))
                    <div class="alert alert-{{ $type === 'error' ? 'danger' : ($type === 'info' ? 'info' : 'success') }} alert-dismissible fade show mb-3 rounded-3" role="alert">
                        <i class="fas fa-{{ $type === 'error' ? 'exclamation-triangle' : ($type === 'info' ? 'info-circle' : 'check-circle') }} me-2"></i>
                        {!! session($type) !!}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
            @endforeach

            {{-- ── Tabel Utama (Styled like Users Page Table) ──────────────────────── --}}
            <div class="card border shadow-sm">
                {{-- Header --}}
                <div class="card-header bg-light d-flex justify-content-between align-items-center py-2.5 px-3 border-bottom">
                    <div>
                        <h6 class="m-0 fw-bold text-primary">
                            <i class="fas fa-history me-2"></i>Mutasi Dompet — {{ $store->store_name }}
                        </h6>
                        <p class="text-muted mb-0 small mt-1">
                            Platform: <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill small fw-semibold px-2 py-0.5">{{ strtoupper($store->channel->code) }}</span> | ID Toko: {{ $store->marketplace_store_id }}
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('finance.marketplace_wallets.sync', [$store, 'days' => 60]) }}" class="btn btn-success btn-sm px-3 rounded-2" onclick="return confirm('Tarik data mutasi terbaru dari marketplace? Proses ini mungkin memakan waktu beberapa detik.')">
                            <i class="fas fa-sync-alt me-1"></i> Tarik Data Baru
                        </a>
                        <a href="{{ route('finance.marketplace_wallets.index') }}" class="btn btn-outline-secondary btn-sm px-3 rounded-2">
                            <i class="fas fa-arrow-left me-1"></i> Kembali
                        </a>
                    </div>
                </div>

                <div class="card-body p-3">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr class="text-uppercase small font-monospace text-muted">
                                    <th class="text-center" style="width: 50px;">No</th>
                                    <th>Waktu Transaksi</th>
                                    <th>ID Transaksi</th>
                                    <th>Jenis Transaksi</th>
                                    <th>Keterangan / Rincian</th>
                                    <th class="text-end">Jumlah</th>
                                    <th class="text-end pe-3">Saldo Akhir</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($mutasiList as $i => $m)
                                    <tr>
                                        <td class="text-center text-muted small">{{ $i + 1 }}</td>
                                        <td class="small text-secondary">
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
                                        <td class="text-end font-monospace small fw-semibold pe-3 text-dark">
                                            @if($m['current_balance'] !== null)
                                                Rp {{ number_format($m['current_balance'], 0, ',', '.') }}
                                            @else
                                                <span class="text-muted opacity-50">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4 small">
                                            <i class="fas fa-inbox me-2 opacity-50"></i>
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
    </div>
@endsection
