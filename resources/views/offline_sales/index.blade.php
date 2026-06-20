@extends('layouts.app')

@section('title', 'Penjualan Offline')
@section('page-title', 'Penjualan Offline')

@section('content')
    <div class="row">
        <div class="col-md-12">

            {{-- HEADER --}}
            <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-success bg-opacity-10 text-success rounded border border-success border-opacity-10 d-flex align-items-center justify-content-center"
                        style="width:48px;height:48px;font-size:1.25rem;">
                        <i class="fas fa-store"></i>
                    </div>
                    <div>
                        <h4 class="mb-0 text-white fw-bold">Penjualan Offline</h4>
                        <p class="text-muted mb-0 small">Catat penjualan langsung / offline secara manual</p>
                    </div>
                </div>
            </div>

            {{-- SUMMARY CARDS --}}
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="dashboard-card text-center"
                        style="background:linear-gradient(135deg,rgba(16,185,129,.18),rgba(16,185,129,.06)); border-color:rgba(16,185,129,.35);">
                        <div class="fs-4 fw-bold text-success">{{ number_format($summary->total_count ?? 0) }}</div>
                        <div class="text-muted small">Total Transaksi Selesai</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="dashboard-card text-center"
                        style="background:linear-gradient(135deg,rgba(99,102,241,.18),rgba(99,102,241,.06)); border-color:rgba(99,102,241,.35);">
                        <div class="fs-5 fw-bold text-primary font-monospace">Rp
                            {{ number_format($summary->total_revenue ?? 0, 0, ',', '.') }}</div>
                        <div class="text-muted small">Total Pendapatan Offline</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="dashboard-card text-center"
                        style="background:linear-gradient(135deg,rgba(245,158,11,.18),rgba(245,158,11,.06)); border-color:rgba(245,158,11,.35);">
                        <div class="fs-4 fw-bold text-warning font-monospace">
                            Rp
                            {{ $summary->total_count > 0 ? number_format(($summary->total_revenue ?? 0) / $summary->total_count, 0, ',', '.') : '0' }}
                        </div>
                        <div class="text-muted small">Rata-rata per Transaksi</div>
                    </div>
                </div>
            </div>

            {{-- FILTER --}}
            <div class="dashboard-card mb-3 py-3">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-12 col-sm-6 col-md-3">
                        <label class="form-label form-label-sm fw-semibold mb-1 text-muted">
                            <i class="fas fa-search me-1"></i>Cari
                        </label>
                        <input type="text" name="search"
                            class="form-control form-control-sm bg-dark bg-opacity-50 text-white border-secondary border-opacity-25"
                            placeholder="No. transaksi / nama..." value="{{ request('search') }}">
                    </div>
                    <div class="col-12 col-sm-6 col-md-2">
                        <label class="form-label form-label-sm fw-semibold mb-1 text-muted">
                            <i class="fas fa-info-circle me-1"></i>Status
                        </label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">Semua Status</option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Selesai
                            </option>
                            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Dibatalkan
                            </option>
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-md-2">
                        <label class="form-label form-label-sm fw-semibold mb-1 text-muted">
                            <i class="fas fa-wallet me-1"></i>Pembayaran
                        </label>
                        <select name="payment_method" class="form-select form-select-sm">
                            <option value="">Semua Metode</option>
                            @foreach (\App\Models\OfflineSale::PAYMENT_METHODS as $key => $label)
                                <option value="{{ $key }}"
                                    {{ request('payment_method') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-md-2">
                        <label class="form-label form-label-sm fw-semibold mb-1 text-muted">
                            <i class="fas fa-calendar-alt me-1"></i>Dari Tanggal
                        </label>
                        <input type="date" name="date_from"
                            class="form-control form-control-sm bg-dark bg-opacity-50 text-white border-secondary border-opacity-25"
                            value="{{ request('date_from') }}">
                    </div>
                    <div class="col-12 col-sm-6 col-md-2">
                        <label class="form-label form-label-sm fw-semibold mb-1 text-muted">
                            <i class="fas fa-calendar-alt me-1"></i>Sampai
                        </label>
                        <input type="date" name="date_to"
                            class="form-control form-control-sm bg-dark bg-opacity-50 text-white border-secondary border-opacity-25"
                            value="{{ request('date_to') }}">
                    </div>
                    <div class="col-12 col-sm-6 col-md-auto d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm px-3">
                            <i class="fas fa-search me-1"></i> Cari
                        </button>
                        @if (request()->hasAny(['search', 'status', 'payment_method', 'date_from', 'date_to']))
                            <a href="{{ route('offline_sales.index') }}" class="btn btn-secondary btn-sm px-2">
                                <i class="fas fa-times"></i> Reset
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- TABLE --}}
            <div class="dashboard-card">
                <div class="card-header-line d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h5 class="mb-0"><i class="fas fa-store-alt me-2 text-primary"></i>Daftar Penjualan Offline</h5>
                        <p class="text-muted mb-0 mt-1 small">
                            Kelola data penjualan manual yang terjadi secara langsung
                        </p>
                    </div>
                    <a href="{{ route('offline_sales.create') }}" class="btn btn-primary btn-sm px-3">
                        <i class="fas fa-plus me-1"></i> Transaksi Baru
                    </a>
                </div>

                <div class="table-responsive rounded border border-secondary border-opacity-10 mt-3">
                    <table class="table table-sm table-bordered table-premium-dark align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">NO. TRANSAKSI</th>
                                <th>PEMBELI</th>
                                <th>KASIR</th>
                                <th>PEMBAYARAN</th>
                                <th class="text-end">GRAND TOTAL</th>
                                <th class="text-center">STATUS</th>
                                <th>WAKTU</th>
                                <th class="text-center">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($sales as $sale)
                                <tr>
                                    <td class="ps-3">
                                        <a href="{{ route('offline_sales.show', $sale->id) }}"
                                            class="fw-bold text-decoration-none text-primary font-monospace">
                                            {{ $sale->sale_number }}
                                        </a>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-white">{{ $sale->buyer_name ?: '(Umum)' }}</div>
                                        <div class="text-muted small">{{ $sale->buyer_phone ?? '' }}</div>
                                    </td>
                                    <td class="text-white-50 small">{{ $sale->user->name ?? '-' }}</td>
                                    <td>
                                        <span
                                            class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-10 small fw-medium">
                                            {{ $sale->payment_method_label }}
                                        </span>
                                    </td>
                                    <td class="text-end fw-bold text-success font-monospace">
                                        Rp {{ number_format($sale->grand_total, 0, ',', '.') }}
                                    </td>
                                    <td class="text-center">
                                        <span
                                            class="badge bg-{{ $sale->status_badge }} bg-opacity-10 text-{{ $sale->status_badge }} border border-{{ $sale->status_badge }} border-opacity-10 small fw-medium">
                                            {{ $sale->status_label }}
                                        </span>
                                    </td>
                                    <td class="text-white-50 small">
                                        {{ $sale->sold_at ? $sale->sold_at->format('d M Y, H:i') : '-' }}
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex gap-1 justify-content-center">
                                            <a href="{{ route('offline_sales.show', $sale->id) }}"
                                                class="btn btn-sm btn-outline-primary" title="Detail"
                                                style="padding: 2px 8px; font-size: 0.75rem;">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('offline_sales.print', $sale->id) }}" target="_blank"
                                                class="btn btn-sm btn-outline-secondary" title="Cetak Struk"
                                                style="padding: 2px 8px; font-size: 0.75rem;">
                                                <i class="fas fa-print"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="fas fa-store-slash fa-3x mb-3 d-block opacity-25"></i>
                                        Belum ada transaksi penjualan offline.
                                        <div class="mt-2">
                                            <a href="{{ route('offline_sales.create') }}"
                                                class="btn btn-success btn-sm mt-2">
                                                <i class="fas fa-plus me-1"></i>Buat Transaksi Pertama
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- ── Pagination ───────────────────────────────────────── --}}
                @if ($sales->hasPages())
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <span class="text-muted small">
                            Halaman {{ $sales->currentPage() }} dari {{ $sales->lastPage() }}
                            &mdash; {{ $sales->total() }} total transaksi
                        </span>
                        {{ $sales->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
@endsection
