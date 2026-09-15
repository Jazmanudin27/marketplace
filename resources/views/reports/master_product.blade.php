@extends('layouts.app')
@section('title', 'Laporan Rekap Persediaan')
@section('page-title', 'Laporan Rekap Persediaan')

@section('content')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">Laporan Rekap Persediaan</h4>
            <p class="text-muted mb-0 small">Ringkasan persediaan produk master (Single &amp; Set Bundling) serta estimasi modal stok.</p>
        </div>
        <div class="d-flex gap-2">
            @if(Route::has('reports.master_product.export'))
                <a href="{{ route('reports.master_product.export', request()->all()) }}" class="btn btn-outline-success px-3 py-2 rounded-3 fw-semibold shadow-sm">
                    <i class="fas fa-file-excel me-1"></i> Ekspor CSV / Excel
                </a>
            @endif
            <a href="{{ route('reports.master_product.print', request()->all()) }}" target="_blank" class="btn btn-primary px-3 py-2 rounded-3 fw-semibold shadow-sm">
                <i class="fas fa-print me-1"></i> Cetak / Print Laporan
            </a>
        </div>
    </div>

    <!-- Summary Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase mb-1">Total Master Produk</div>
                        <h3 class="fw-bold text-dark mb-0">{{ number_format($totalCount) }}</h3>
                    </div>
                    <div class="rounded-circle bg-primary bg-opacity-10 p-3 text-primary">
                        <i class="fas fa-boxes fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase mb-1">Produk Single</div>
                        <h3 class="fw-bold text-info mb-0">{{ number_format($singleCount) }}</h3>
                    </div>
                    <div class="rounded-circle bg-info bg-opacity-10 p-3 text-info">
                        <i class="fas fa-box fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase mb-1">Produk Set / Bundle</div>
                        <h3 class="fw-bold text-purple mb-0" style="color: #6f42c1;">{{ number_format($bundleCount) }}</h3>
                    </div>
                    <div class="rounded-circle bg-opacity-10 p-3" style="background-color: rgba(111, 66, 193, 0.1); color: #6f42c1;">
                        <i class="fas fa-cubes fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase mb-1">Est. Total Modal Stok</div>
                        <h3 class="fw-bold text-success mb-0">Rp {{ number_format($totalStockValue, 0, ',', '.') }}</h3>
                    </div>
                    <div class="rounded-circle bg-success bg-opacity-10 p-3 text-success">
                        <i class="fas fa-wallet fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Section Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold text-dark mb-0"><i class="fas fa-filter text-primary me-2"></i>Filter &amp; Cetak Laporan</h5>
            <span class="badge bg-light text-muted border">Atur Kriteria &amp; Cetak</span>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('reports.master_product') }}" method="GET" id="masterProductFilterForm">
                <div class="row g-3">
                    <div class="col-12 col-md-3">
                        <label class="form-label form-label-sm fw-semibold text-muted">Toko / Marketplace</label>
                        <select name="store_id" class="form-select form-select-sm rounded-3">
                            <option value="">Semua Toko Marketplace</option>
                            @foreach ($stores as $st)
                                <option value="{{ $st->id }}" {{ request('store_id') == $st->id ? 'selected' : '' }}>
                                    {{ $st->store_name }} ({{ ucfirst($st->channel->name ?? $st->channel->code ?? 'MP') }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label form-label-sm fw-semibold text-muted">Kategori Produk</label>
                        <select name="category_id" class="form-select form-select-sm rounded-3">
                            <option value="">Semua Kategori</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-2">
                        <label class="form-label form-label-sm fw-semibold text-muted">Merk / Brand</label>
                        <select name="brand_id" class="form-select form-select-sm rounded-3">
                            <option value="">Semua Brand</option>
                            @foreach ($brands as $b)
                                <option value="{{ $b->id }}" {{ request('brand_id') == $b->id ? 'selected' : '' }}>
                                    {{ $b->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-2">
                        <label class="form-label form-label-sm fw-semibold text-muted">Jenis Produk</label>
                        <select name="is_bundle" class="form-select form-select-sm rounded-3">
                            <option value="">Semua Jenis</option>
                            <option value="0" {{ request('is_bundle') === '0' ? 'selected' : '' }}>📦 Single</option>
                            <option value="1" {{ request('is_bundle') === '1' ? 'selected' : '' }}>🎁 BUNDLE / Set</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-2">
                        <label class="form-label form-label-sm fw-semibold text-muted">Tipe Pre-Order</label>
                        <select name="is_preorder" class="form-select form-select-sm rounded-3">
                            <option value="">Semua Tipe</option>
                            <option value="1" {{ request('is_preorder') === '1' ? 'selected' : '' }}>⏳ PO</option>
                            <option value="0" {{ request('is_preorder') === '0' ? 'selected' : '' }}>📦 Ready Stock</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label form-label-sm fw-semibold text-muted">Cari Nama / SKU</label>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm rounded-3" placeholder="Ketik nama produk atau SKU...">
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label form-label-sm fw-semibold text-muted">Status Produk</label>
                        <select name="is_active" class="form-select form-select-sm rounded-3">
                            <option value="">Semua Status</option>
                            <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>✅ Aktif</option>
                            <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>❌ Nonaktif</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-5 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-sm btn-primary flex-fill rounded-3 fw-semibold py-2">
                            <i class="fas fa-filter me-1"></i> Terapkan Filter
                        </button>
                        <button type="submit" formaction="{{ route('reports.master_product.print') }}" formtarget="_blank" class="btn btn-sm btn-success flex-fill rounded-3 fw-semibold py-2">
                            <i class="fas fa-print me-1"></i> Cetak Laporan Terfilter
                        </button>
                        <a href="{{ route('reports.master_product') }}" class="btn btn-sm btn-outline-secondary rounded-3 py-2">Reset</a>
                    </div>
                </div>

                <div class="d-flex gap-4 mt-4 pt-3 border-top">
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" name="hide_zero_stock" value="1" id="hideZeroStock" {{ request()->boolean('hide_zero_stock') ? 'checked' : '' }} onchange="this.form.submit()">
                        <label class="form-check-label small fw-semibold text-dark" for="hideZeroStock">
                            Sembunyikan Produk Stok 0
                        </label>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

