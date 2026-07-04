@extends('layouts.app')
@section('title', 'Riwayat Barang Masuk')
@section('page-title', 'Riwayat Penerimaan Barang')

@section('content')

    {{-- ── Stat Cards ─────────────────────────────────────────────── --}}
    <div class="row g-3 mb-3">
        <div class="col-12 col-sm-4">
            <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="rounded-3 bg-primary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0"
                        style="width:44px;height:44px;">
                        <i class="fas fa-truck-loading text-primary"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Total Penerimaan</div>
                        <div class="fw-bold fs-4 lh-1 mt-1">{{ number_format($incomings->total()) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-4">
            <div class="card border-0 shadow-sm h-100 border-start border-4" style="border-color:#8b5cf6!important;">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                        style="width:44px;height:44px;background:rgba(139,92,246,.12);">
                        <i class="fas fa-layer-group" style="color:#a78bfa;"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Total Jenis Item</div>
                        <div class="fw-bold fs-4 lh-1 mt-1">{{ number_format($incomings->sum('total_items')) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-4">
            <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="rounded-3 bg-success bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0"
                        style="width:44px;height:44px;">
                        <i class="fas fa-boxes text-success"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Total Qty Masuk</div>
                        <div class="fw-bold fs-4 lh-1 mt-1">{{ number_format($incomings->sum('total_qty')) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Filter Card ─────────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2 px-3">
            <form action="{{ route('incoming_goods.index') }}" method="GET" id="filterForm">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-4">
                        <label class="form-label form-label-sm fw-semibold mb-1">
                            <i class="fas fa-search me-1"></i>Pencarian Referensi
                        </label>
                        <input type="text" name="search" class="form-control form-control-sm"
                            placeholder="Cari nomor referensi…" value="{{ request('search') }}">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label form-label-sm fw-semibold mb-1">
                            <i class="far fa-calendar-alt me-1"></i>Dari Tanggal
                        </label>
                        <input type="date" name="start_date" class="form-control form-control-sm"
                            value="{{ request('start_date') }}">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label form-label-sm fw-semibold mb-1">
                            <i class="far fa-calendar-alt me-1"></i>Sampai Tanggal
                        </label>
                        <input type="date" name="end_date" class="form-control form-control-sm"
                            value="{{ request('end_date') }}">
                    </div>
                    <div class="col-12 col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm flex-fill">
                            <i class="fas fa-filter me-1"></i>Saring
                        </button>
                        @if (request()->anyFilled(['search', 'start_date', 'end_date']))
                            <a href="{{ route('incoming_goods.index') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Active filter badges --}}
                @if (request()->anyFilled(['search', 'start_date', 'end_date']))
                    <div class="d-flex flex-wrap gap-2 mt-2 align-items-center">
                        <span class="text-muted small"><i class="fas fa-filter me-1"></i>Filter aktif:</span>
                        @if (request('search'))
                            <span
                                class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 fw-normal d-inline-flex align-items-center gap-1 px-2 py-1">
                                <i class="fas fa-search" style="font-size:.6rem;"></i> "{{ request('search') }}"
                                <a href="{{ route('incoming_goods.index', request()->except('search')) }}"
                                    class="text-primary ms-1 opacity-75 text-decoration-none"><i
                                        class="fas fa-times-circle"></i></a>
                            </span>
                        @endif
                        @if (request('start_date'))
                            <span
                                class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 fw-normal d-inline-flex align-items-center gap-1 px-2 py-1">
                                <i class="fas fa-calendar" style="font-size:.6rem;"></i> Dari:
                                {{ \Carbon\Carbon::parse(request('start_date'))->format('d M Y') }}
                                <a href="{{ route('incoming_goods.index', request()->except('start_date')) }}"
                                    class="text-info ms-1 opacity-75 text-decoration-none"><i
                                        class="fas fa-times-circle"></i></a>
                            </span>
                        @endif
                        @if (request('end_date'))
                            <span
                                class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 fw-normal d-inline-flex align-items-center gap-1 px-2 py-1">
                                <i class="fas fa-calendar" style="font-size:.6rem;"></i> S/d:
                                {{ \Carbon\Carbon::parse(request('end_date'))->format('d M Y') }}
                                <a href="{{ route('incoming_goods.index', request()->except('end_date')) }}"
                                    class="text-info ms-1 opacity-75 text-decoration-none"><i
                                        class="fas fa-times-circle"></i></a>
                            </span>
                        @endif
                    </div>
                @endif
            </form>
        </div>
    </div>

    {{-- ── Main Table Card ──────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-2 px-3">
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-3 bg-primary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0"
                    style="width:36px;height:36px;">
                    <i class="fas fa-history text-primary" style="font-size:.85rem;"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-0">Riwayat Barang Masuk</h6>
                    <small class="text-muted">{{ $incomings->total() }} transaksi penerimaan</small>
                </div>
            </div>
            <a href="{{ route('incoming_goods.create') }}"
                class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1">
                <i class="fas fa-plus-circle"></i> Tambah Penerimaan
            </a>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Tanggal Penerimaan</th>
                            <th>Nomor Referensi</th>
                            <th class="text-center">Jenis Item</th>
                            <th class="text-center">Total Qty</th>
                            <th class="pe-3">Diterima Oleh</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($incomings as $in)
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-semibold small">
                                        <i class="far fa-calendar-alt text-muted me-1"></i>
                                        {{ \Carbon\Carbon::parse($in->created_at)->format('d M Y') }}
                                    </div>
                                    <div class="text-muted" style="font-size:.7rem;">
                                        <i
                                            class="far fa-clock me-1"></i>{{ \Carbon\Carbon::parse($in->created_at)->format('H:i') }}
                                        WIB
                                    </div>
                                </td>
                                <td>
                                    <span
                                        class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 fw-semibold font-monospace px-2 py-1">
                                        <i class="fas fa-hashtag me-1 opacity-50"
                                            style="font-size:.6rem;"></i>{{ $in->reference }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-info px-2 py-1">
                                        <i class="fas fa-layer-group me-1"
                                            style="font-size:.6rem;"></i>{{ $in->total_items }} Jenis
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-success px-2 py-1">
                                        <i class="fas fa-cubes me-1"
                                            style="font-size:.6rem;"></i>{{ number_format($in->total_qty) }} Pcs
                                    </span>
                                </td>
                                <td class="pe-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0 fw-bold text-primary"
                                            style="width:26px;height:26px;font-size:.65rem;">
                                            {{ strtoupper(substr($in->user->name ?? '?', 0, 1)) }}
                                        </div>
                                        <span class="small">{{ $in->user->name ?? '-' }}</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="d-inline-flex align-items-center justify-content-center rounded-3 bg-light mb-3"
                                        style="width:64px;height:64px;">
                                        <i class="fas fa-inbox fa-2x text-muted opacity-50"></i>
                                    </div>
                                    <div class="fw-semibold mb-1">Belum Ada Data Penerimaan</div>
                                    <div class="text-muted small mb-3">
                                        @if (request()->anyFilled(['search', 'start_date', 'end_date']))
                                            Tidak ada hasil yang cocok dengan filter yang diterapkan.
                                        @else
                                            Belum ada riwayat penerimaan barang masuk.
                                        @endif
                                    </div>
                                    @if (request()->anyFilled(['search', 'start_date', 'end_date']))
                                        <a href="{{ route('incoming_goods.index') }}"
                                            class="btn btn-outline-secondary btn-sm">
                                            <i class="fas fa-times me-1"></i> Hapus Filter
                                        </a>
                                    @else
                                        <a href="{{ route('incoming_goods.create') }}" class="btn btn-primary btn-sm">
                                            <i class="fas fa-plus me-1"></i> Tambah Penerimaan
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($incomings->hasPages())
            <div class="card-footer bg-transparent d-flex justify-content-between align-items-center py-2">
                <span class="text-muted small">
                    Menampilkan
                    <strong>{{ $incomings->firstItem() ?? 0 }}–{{ $incomings->lastItem() ?? 0 }}</strong>
                    dari <strong>{{ $incomings->total() }}</strong> data
                </span>
                {{ $incomings->withQueryString()->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>

@endsection
