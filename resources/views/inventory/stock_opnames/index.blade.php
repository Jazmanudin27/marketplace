@extends('layouts.app')
@section('title', 'Histori Stock Opname')
@section('page-title', 'Histori Stock Opname')

@section('content')

    {{-- ── Filter Card ─────────────────────────────────────────────── --}}
    <div class="dashboard-card mb-3 py-3">
        <form action="{{ route('stock_opnames.index') }}" method="GET">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label form-label-sm fw-semibold mb-1 text-muted">
                        <i class="fas fa-search me-1"></i>Cari Produk / PIC
                    </label>
                    <input type="text" name="search" class="form-control form-control-sm"
                        placeholder="Cari SKU, Nama, atau Petugas…" value="{{ request('search') }}">
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
                        <i class="fas fa-filter me-1"></i>Filter
                    </button>
                    @if (request()->anyFilled(['search', 'start_date', 'end_date']))
                        <a href="{{ route('stock_opnames.index') }}" class="btn btn-outline-secondary btn-sm">
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
                <i class="fas fa-history text-primary"></i> Riwayat Stock Opname
            </h5>
            <div class="d-flex gap-2">
                <a href="{{ route('inventory.index') }}"
                    class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                    <i class="fas fa-arrow-left"></i> Inventory
                </a>
                <a href="{{ route('stock_opnames.create') }}"
                    class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1">
                    <i class="fas fa-plus"></i> Tambah Opname
                </a>
            </div>
        </div>

        <div class="table-responsive rounded border border-secondary border-opacity-10 mt-3">
            <table class="table table-sm table-bordered table-premium-dark align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Waktu Opname</th>
                        <th>Petugas (PIC)</th>
                        <th>SKU</th>
                        <th>Nama Produk</th>
                        <th class="text-center">Stok Sebelum</th>
                        <th class="text-center">Penyesuaian</th>
                        <th class="text-center pe-3" style="background:rgba(99,102,241,0.08)">Stok Akhir</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($histories as $history)
                        @php
                            $pic = str_replace('Stock Opname Massal - ', '', $history->reference);
                            if ($pic === 'Stock Opname Massal') {
                                $pic = $history->user->name ?? 'Sistem';
                            }
                        @endphp
                        <tr>
                            <td class="ps-3 small text-muted">
                                {{ $history->created_at->format('d M Y H:i') }}
                            </td>
                            <td class="text-light">
                                <i class="fas fa-user-circle text-secondary me-1"></i> {{ $pic }}
                            </td>
                            <td class="font-monospace text-nowrap small">
                                {{ $history->masterProduct->sku ?? '-' }}
                            </td>
                            <td class="fw-semibold text-white">
                                {{ $history->masterProduct->name ?? '-' }}
                            </td>
                            <td class="text-center font-monospace text-muted">
                                {{ number_format($history->balance_after - $history->quantity) }}
                            </td>
                            <td class="text-center font-monospace fw-bold">
                                @if ($history->quantity > 0)
                                    <span class="badge badge-success"><i
                                            class="fas fa-caret-up me-1"></i>+{{ number_format($history->quantity) }}</span>
                                @elseif($history->quantity < 0)
                                    <span class="badge badge-danger"><i
                                            class="fas fa-caret-down me-1"></i>{{ number_format($history->quantity) }}</span>
                                @else
                                    <span class="badge badge-secondary">0</span>
                                @endif
                            </td>
                            <td class="text-center font-monospace fw-bold text-white pe-3"
                                style="background:rgba(99,102,241,0.05)">
                                {{ number_format($history->balance_after) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-5">
                                <i class="fas fa-history fa-2x mb-3 d-block opacity-25"></i>
                                <div class="fw-semibold text-light mb-1">Belum Ada Riwayat</div>
                                <div class="small text-muted">Mulai stock opname untuk mencatat data fisik.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($histories->hasPages())
            <div class="d-flex justify-content-between align-items-center mt-3">
                <span class="text-muted" style="font-size:.75rem">
                    Menampilkan {{ $histories->firstItem() ?? 0 }}–{{ $histories->lastItem() ?? 0 }}
                    dari {{ $histories->total() }} data
                </span>
                {{ $histories->withQueryString()->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>

@endsection
