@extends('layouts.app')

@section('title', 'Penjualan Offline')
@section('page-title', 'Penjualan Offline')

@section('content')
    <div class="container-fluid">

        {{-- HEADER --}}
        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <div
                    style="width:52px;height:52px;background:linear-gradient(135deg,#10b981,#059669);border-radius:14px;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-store-slash text-white fs-5"></i>
                </div>
                <div>
                    <h1 class="fs-4 fw-bold mb-0">Penjualan Offline</h1>
                    <p class="text-muted mb-0 small">Catat penjualan langsung / offline secara manual</p>
                </div>
            </div>
            <a href="{{ route('offline_sales.create') }}" class="btn btn-sm btn-success">
                <i class="fas fa-plus me-2"></i>Transaksi Baru
            </a>
        </div>

        {{-- SUMMARY CARDS --}}
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card text-center" style="border-left:4px solid #10b981;">
                    <div class="card-body py-3">
                        <div class="fs-4 fw-bold text-success">{{ number_format($summary->total_count ?? 0) }}</div>
                        <div class="text-muted small">Total Transaksi Selesai</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center" style="border-left:4px solid #6366f1;">
                    <div class="card-body py-3">
                        <div class="fs-5 fw-bold text-primary">Rp
                            {{ number_format($summary->total_revenue ?? 0, 0, ',', '.') }}</div>
                        <div class="text-muted small">Total Pendapatan Offline</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center" style="border-left:4px solid #f59e0b;">
                    <div class="card-body py-3">
                        <div class="fs-4 fw-bold text-warning">
                            Rp
                            {{ $summary->total_count > 0 ? number_format(($summary->total_revenue ?? 0) / $summary->total_count, 0, ',', '.') : '0' }}
                        </div>
                        <div class="text-muted small">Rata-rata per Transaksi</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- FILTER --}}
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-500">Cari</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text form-control-dark"><i class="fas fa-search"></i></span>
                            <input type="text" name="search" class="form-control form-control-sm form-control-dark"
                                placeholder="No. transaksi / nama..." value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-500">Status</label>
                        <select name="status" class="form-select form-select-sm form-select-dark">
                            <option value="">Semua</option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Selesai
                            </option>
                            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Dibatalkan
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-500">Pembayaran</label>
                        <select name="payment_method" class="form-select form-select-sm form-select-dark">
                            <option value="">Semua</option>
                            @foreach (\App\Models\OfflineSale::PAYMENT_METHODS as $key => $label)
                                <option value="{{ $key }}"
                                    {{ request('payment_method') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-500">Dari Tanggal</label>
                        <input type="date" name="date_from" class="form-control form-control-sm form-control-dark"
                            value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-500">Sampai</label>
                        <input type="date" name="date_to" class="form-control form-control-sm form-control-dark"
                            value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-sm btn-primary w-100"><i class="fas fa-filter"></i></button>
                    </div>
                </form>
            </div>
        </div>

        {{-- TABLE --}}
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">No. Transaksi</th>
                                <th>Pembeli</th>
                                <th>Kasir</th>
                                <th>Pembayaran</th>
                                <th class="text-end">Grand Total</th>
                                <th class="text-center">Status</th>
                                <th>Waktu</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($sales as $sale)
                                <tr>
                                    <td class="ps-3">
                                        <a href="{{ route('offline_sales.show', $sale->id) }}"
                                            class="fw-600 text-decoration-none text-primary font-monospace">
                                            {{ $sale->sale_number }}
                                        </a>
                                    </td>
                                    <td>
                                        <div class="fw-500">{{ $sale->buyer_name ?: '(Umum)' }}</div>
                                        <div class="text-muted small">{{ $sale->buyer_phone ?? '' }}</div>
                                    </td>
                                    <td class="text-muted small">{{ $sale->user->name ?? '-' }}</td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $sale->payment_method_label }}</span>
                                    </td>
                                    <td class="text-end fw-700 text-success">
                                        Rp {{ number_format($sale->grand_total, 0, ',', '.') }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $sale->status_badge }}">{{ $sale->status_label }}</span>
                                    </td>
                                    <td class="text-muted small">
                                        {{ $sale->sold_at ? $sale->sold_at->format('d M Y, H:i') : '-' }}
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex gap-1 justify-content-center">
                                            <a href="{{ route('offline_sales.show', $sale->id) }}"
                                                class="btn btn-sm btn-outline-primary" title="Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('offline_sales.print', $sale->id) }}" target="_blank"
                                                class="btn btn-sm btn-outline-secondary" title="Cetak Struk">
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
            </div>
            @if ($sales->hasPages())
                <div class="card-footer">
                    {{ $sales->links() }}
                </div>
            @endif
        </div>

    </div>
@endsection
