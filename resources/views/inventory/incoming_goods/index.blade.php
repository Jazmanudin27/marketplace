@extends('layouts.app')
@section('title', 'Riwayat Barang Masuk')
@section('page-title', 'Riwayat Penerimaan Barang')

@section('content')

    {{-- ── Filter Card ─────────────────────────────────────────────── --}}
    <div class="dashboard-card mb-3 py-3">
        <form action="{{ route('incoming_goods.index') }}" method="GET" id="filterForm">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label form-label-sm fw-semibold mb-1 text-muted">
                        <i class="fas fa-search me-1"></i>Pencarian Referensi
                    </label>
                    <input type="text" name="search" class="form-control form-control-sm"
                           placeholder="Cari nomor referensi…" value="{{ request('search') }}">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label form-label-sm fw-semibold mb-1 text-muted">
                        <i class="far fa-calendar-alt me-1"></i>Dari Tanggal
                    </label>
                    <input type="date" name="start_date" class="form-control form-control-sm"
                           value="{{ request('start_date') }}">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label form-label-sm fw-semibold mb-1 text-muted">
                        <i class="far fa-calendar-alt me-1"></i>Sampai Tanggal
                    </label>
                    <input type="date" name="end_date" class="form-control form-control-sm"
                           value="{{ request('end_date') }}">
                </div>
                <div class="col-12 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill">
                        <i class="fas fa-filter me-1"></i>Saring
                    </button>
                    @if(request()->anyFilled(['search', 'start_date', 'end_date']))
                        <a href="{{ route('incoming_goods.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-times"></i>
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    {{-- ── Main Table Card ──────────────────────────────────────────── --}}
    <div class="dashboard-card">
        <div class="card-header-line d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold d-flex align-items-center gap-2">
                <i class="fas fa-history text-primary"></i> Riwayat Barang Masuk
            </h5>
            <a href="{{ route('incoming_goods.create') }}" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1">
                <i class="fas fa-plus"></i> Tambah Penerimaan
            </a>
        </div>

        <div class="table-responsive rounded border border-secondary border-opacity-10 mt-3">
            <table class="table table-sm table-bordered table-premium-dark align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Tanggal Penerimaan</th>
                        <th>Nomor Referensi</th>
                        <th class="text-center">Total Jenis Item</th>
                        <th class="text-center">Total Qty Masuk</th>
                        <th class="pe-3">Diterima Oleh</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($incomings as $in)
                        <tr>
                            <td class="ps-3">
                                <i class="far fa-calendar-alt text-secondary me-2"></i>
                                <span class="text-light">{{ \Carbon\Carbon::parse($in->created_at)->format('d M Y H:i') }}</span>
                            </td>
                            <td>
                                <i class="fas fa-file-invoice text-secondary me-2"></i>
                                <span class="text-white fw-semibold">{{ $in->reference }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-info px-2 py-1">
                                    {{ $in->total_items }} Jenis
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-success px-2 py-1">
                                    {{ number_format($in->total_qty) }} Pcs
                                </span>
                            </td>
                            <td class="pe-3">
                                <i class="far fa-user text-secondary me-2"></i>
                                <span class="text-light">{{ $in->user->name ?? '-' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-5">
                                <i class="fas fa-folder-open fa-2x mb-3 d-block opacity-25"></i>
                                <div class="fw-semibold text-light mb-1">Belum Ada Data</div>
                                <div class="small text-muted">Belum ada riwayat penerimaan barang masuk.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($incomings->hasPages())
            <div class="d-flex justify-content-between align-items-center mt-3">
                <span class="text-muted" style="font-size:.75rem">
                    Menampilkan {{ $incomings->firstItem() ?? 0 }}–{{ $incomings->lastItem() ?? 0 }}
                    dari {{ $incomings->total() }} data
                </span>
                {{ $incomings->withQueryString()->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>

@endsection
