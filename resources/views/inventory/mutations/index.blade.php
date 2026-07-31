@extends('layouts.app')
@section('title', 'Mutasi Gudang Jadi')
@section('page-title', 'Mutasi Barang Masuk & Keluar Gudang Jadi')

@section('content')
<div class="container-fluid px-0">

    {{-- Top Action & Header Banner --}}
    <div class="card border-0 shadow-sm mb-4 rounded-3 overflow-hidden bg-white">
        <div class="card-body p-4 border-start border-4 border-info">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h5 class="fw-bold text-dark mb-1">
                        <i class="fas fa-exchange-alt text-info me-2"></i> Mutasi Barang Gudang Jadi
                    </h5>
                    <p class="text-muted small mb-0">
                        Catat dan pantau transaksi masuk & keluar stok barang jadi (Master Produk). Stok akan otomatis tersinkronisasi ke marketplace.
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('inventory.mutations.create') }}" class="btn btn-primary btn-sm px-3 rounded-3 fw-semibold">
                        <i class="fas fa-plus-circle me-1.5"></i> + Input Mutasi Barang
                    </a>
                    <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary btn-sm px-3 rounded-3">
                        <i class="fas fa-boxes me-1"></i> Stok Gudang
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Summary Stat Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm h-100 rounded-3">
                <div class="card-body py-3 px-4 d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-primary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px;">
                        <i class="fas fa-list-alt text-primary fs-5"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-4 text-dark">{{ number_format($totalTransactions) }}</div>
                        <div class="text-muted small">Total Transaksi Mutasi</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm h-100 rounded-3 border-start border-success border-4">
                <div class="card-body py-3 px-4 d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-success bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px;">
                        <i class="fas fa-arrow-down text-success fs-5"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-4 text-success">+{{ number_format($totalInbound) }} <small class="fs-6 fw-normal text-muted">unit</small></div>
                        <div class="text-muted small">Total Barang Masuk (Inbound)</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm h-100 rounded-3 border-start border-danger border-4">
                <div class="card-body py-3 px-4 d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-danger bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px;">
                        <i class="fas fa-arrow-up text-danger fs-5"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-4 text-danger">-{{ number_format($totalOutbound) }} <small class="fs-6 fw-normal text-muted">unit</small></div>
                        <div class="text-muted small">Total Barang Keluar (Outbound)</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="card border-0 shadow-sm mb-4 rounded-3">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('inventory.mutations.index') }}">
                <div class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label form-label-sm fw-semibold mb-1">
                            <i class="fas fa-search me-1 text-muted"></i> Cari SKU / Nama / Ket.
                        </label>
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari keyword mutasi..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label form-label-sm fw-semibold mb-1">
                            <i class="fas fa-filter me-1 text-muted"></i> Jenis Mutasi
                        </label>
                        <select name="type" class="form-select form-select-sm">
                            <option value="">-- Semua Jenis --</option>
                            <option value="in" {{ request('type') === 'in' ? 'selected' : '' }}>🟢 Barang Masuk (Inbound)</option>
                            <option value="out" {{ request('type') === 'out' ? 'selected' : '' }}>🔴 Barang Keluar (Outbound)</option>
                            <option value="adj" {{ request('type') === 'adj' ? 'selected' : '' }}>🟡 Penyesuaian (Adjust)</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label form-label-sm fw-semibold mb-1">
                            <i class="fas fa-calendar me-1 text-muted"></i> Dari Tanggal
                        </label>
                        <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label form-label-sm fw-semibold mb-1">
                            <i class="fas fa-calendar me-1 text-muted"></i> Sampai Tanggal
                        </label>
                        <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
                    </div>
                    <div class="col-12 text-end mt-2">
                        <button type="submit" class="btn btn-primary btn-sm px-3">
                            <i class="fas fa-filter me-1"></i> Terapkan Filter
                        </button>
                        @if (request()->anyFilled(['search', 'type', 'start_date', 'end_date']))
                            <a href="{{ route('inventory.mutations.index') }}" class="btn btn-secondary btn-sm px-3 ms-1">
                                <i class="fas fa-times me-1"></i> Reset Filter
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Main Mutations Table --}}
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light border-bottom">
                    <tr>
                        <th class="ps-3" style="width: 140px;">TANGGAL</th>
                        <th style="width: 130px;" class="text-center">JENIS</th>
                        <th>NAMA MASTER PRODUK</th>
                        <th>SKU</th>
                        <th class="text-center" style="width: 100px;">QTY</th>
                        <th class="text-center" style="width: 120px;">STOK AKHIR</th>
                        <th>KETERANGAN / ALASAN</th>
                        <th class="text-end pe-3" style="width: 130px;">OPERATOR</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mutations as $m)
                        <tr>
                            <td class="ps-3 text-nowrap">
                                <div class="fw-semibold text-dark small">{{ $m->created_at->format('d/m/Y') }}</div>
                                <div class="text-muted" style="font-size: 0.73rem;">{{ $m->created_at->format('H:i') }} WIB</div>
                            </td>
                            <td class="text-center">
                                @if($m->type === 'in')
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2.5 py-1 rounded-pill fw-bold">
                                        <i class="fas fa-arrow-down me-1"></i> MASUK
                                    </span>
                                @elseif($m->type === 'out')
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2.5 py-1 rounded-pill fw-bold">
                                        <i class="fas fa-arrow-up me-1"></i> KELUAR
                                    </span>
                                @else
                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2.5 py-1 rounded-pill fw-bold">
                                        <i class="fas fa-sliders-h me-1"></i> ADJUST
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($m->masterProduct)
                                    <div class="fw-bold text-dark">{{ $m->masterProduct->name }}</div>
                                @else
                                    <span class="text-muted opacity-50">Produk Dihapus</span>
                                @endif
                            </td>
                            <td>
                                @if($m->masterProduct)
                                    <code class="text-primary font-monospace">{{ $m->masterProduct->sku }}</code>
                                @else
                                    <span class="text-muted opacity-50">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($m->type === 'in')
                                    <span class="fw-bold text-success font-monospace">+{{ number_format(abs($m->quantity)) }}</span>
                                @elseif($m->type === 'out')
                                    <span class="fw-bold text-danger font-monospace">-{{ number_format(abs($m->quantity)) }}</span>
                                @else
                                    <span class="fw-bold text-dark font-monospace">{{ $m->quantity > 0 ? '+'.$m->quantity : $m->quantity }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark font-monospace border px-2 py-1">
                                    {{ number_format($m->balance_after) }}
                                </span>
                            </td>
                            <td>
                                <div class="text-dark small text-wrap" style="max-width: 320px;">
                                    {{ $m->reference ?? '-' }}
                                </div>
                            </td>
                            <td class="text-end pe-3">
                                <small class="text-muted fw-semibold">
                                    <i class="fas fa-user-circle me-1"></i>{{ $m->user->name ?? 'Sistem' }}
                                </small>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="fas fa-exchange-alt d-block mb-2 opacity-25 fs-1"></i>
                                Belum ada riwayat mutasi gudang jadi yang sesuai filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($mutations->hasPages())
            <div class="card-footer bg-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        Menampilkan {{ $mutations->firstItem() }} - {{ $mutations->lastItem() }} dari {{ $mutations->total() }} mutasi
                    </small>
                    {{ $mutations->links() }}
                </div>
            </div>
        @endif
    </div>

</div>
@endsection
