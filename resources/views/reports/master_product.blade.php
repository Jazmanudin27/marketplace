@extends('layouts.app')
@section('title', 'Laporan Master Produk (Single & Set Bundling)')
@section('page-title', 'Laporan Master Produk')

@section('content')
<div class="container-fluid px-0">
    {{-- Summary Cards --}}
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card border shadow-sm rounded-3 bg-white">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-3">
                        <i class="fas fa-boxes fs-4"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block text-uppercase fw-semibold">Total Master Produk</small>
                        <h4 class="fw-bold mb-0 text-dark">{{ number_format($totalCount) }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border shadow-sm rounded-3 bg-white">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="bg-purple bg-opacity-10 p-3 rounded-3" style="color: #6f42c1; background-color: #f3ebff;">
                        <i class="fas fa-layer-group fs-4"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block text-uppercase fw-semibold">Produk Set / Bundling</small>
                        <h4 class="fw-bold mb-0" style="color: #6f42c1;">{{ number_format($bundleCount) }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border shadow-sm rounded-3 bg-white">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="bg-info bg-opacity-10 text-info p-3 rounded-3">
                        <i class="fas fa-box fs-4"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block text-uppercase fw-semibold">Produk Single</small>
                        <h4 class="fw-bold mb-0 text-info">{{ number_format($singleCount) }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border shadow-sm rounded-3 bg-white">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="bg-success bg-opacity-10 text-success p-3 rounded-3">
                        <i class="fas fa-coins fs-4"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block text-uppercase fw-semibold">Est. Total Modal Stok</small>
                        <h4 class="fw-bold mb-0 text-success">Rp {{ number_format($totalStockValue, 0, ',', '.') }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Card --}}
    <div class="card border shadow-sm rounded-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center py-2 px-3 border-bottom">
            <div>
                <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-file-alt me-2 text-primary"></i>Laporan Detail Master Produk</h6>
                <small class="text-muted">Status bundling, detail komponen set, HPP, stok, dan harga jual</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('reports.master_product.export', request()->all()) }}" class="btn btn-outline-success btn-sm px-3 rounded-3">
                    <i class="fas fa-file-excel me-1"></i>Ekspor CSV / Excel
                </a>
                <a href="{{ route('reports.master_product.print', request()->all()) }}" target="_blank" class="btn btn-primary btn-sm px-3 rounded-3">
                    <i class="fas fa-print me-1"></i>Cetak Laporan
                </a>
            </div>
        </div>

        <div class="card-body p-3">
            {{-- Filter Bar --}}
            <form method="GET" action="{{ route('reports.master_product') }}" class="mb-3">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label form-label-sm fw-semibold mb-1"><i class="fas fa-search text-muted me-1"></i>Pencarian</label>
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Nama / SKU Produk..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label form-label-sm fw-semibold mb-1"><i class="fas fa-layer-group text-muted me-1"></i>Tipe Produk</label>
                        <select name="is_bundle" class="form-select form-select-sm">
                            <option value="">-- Semua Tipe --</option>
                            <option value="1" {{ request('is_bundle') === '1' ? 'selected' : '' }}>Set / Bundling (Paket)</option>
                            <option value="0" {{ request('is_bundle') === '0' ? 'selected' : '' }}>Single (Non-Bundle)</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label form-label-sm fw-semibold mb-1"><i class="fas fa-tags text-muted me-1"></i>Kategori</label>
                        <select name="category_id" class="form-select form-select-sm">
                            <option value="">-- Semua Kategori --</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label form-label-sm fw-semibold mb-1"><i class="fas fa-copyright text-muted me-1"></i>Merk</label>
                        <select name="brand_id" class="form-select form-select-sm">
                            <option value="">-- Semua Merk --</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}" {{ request('brand_id') == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label form-label-sm fw-semibold mb-1"><i class="fas fa-toggle-on text-muted me-1"></i>Status</label>
                        <select name="is_active" class="form-select form-select-sm">
                            <option value="">-- Semua Status --</option>
                            <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Aktif</option>
                            <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Nonaktif</option>
                        </select>
                    </div>
                    <div class="col-md-auto">
                        <button type="submit" class="btn btn-primary btn-sm px-3">
                            <i class="fas fa-filter me-1"></i>Filter
                        </button>
                        @if(request()->anyFilled(['search', 'is_bundle', 'category_id', 'brand_id', 'is_active']))
                            <a href="{{ route('reports.master_product') }}" class="btn btn-secondary btn-sm px-3 ms-1">
                                <i class="fas fa-undo me-1"></i>Reset
                            </a>
                        @endif
                    </div>
                </div>
            </form>

            {{-- Table --}}
            <div class="table-responsive border rounded">
                <table class="table table-sm table-striped table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 40px;">NO</th>
                            <th style="width: 160px;">SKU VARIASI</th>
                            <th>NAMA PRODUK</th>
                            <th class="text-center" style="width: 130px;">TIPE PRODUK</th>
                            <th>DETAIL KOMPONEN SET</th>
                            <th style="width: 130px;">KATEGORI / MERK</th>
                            <th class="text-end" style="width: 110px;">HPP (MODAL)</th>
                            <th class="text-end" style="width: 110px;">HARGA JUAL</th>
                            <th class="text-center" style="width: 80px;">STOK</th>
                            <th class="text-center" style="width: 80px;">STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $index => $product)
                            <tr>
                                <td class="text-center fw-semibold text-muted">{{ $index + 1 }}</td>
                                <td>
                                    <code class="text-primary font-monospace fw-bold">{{ $product->sku }}</code>
                                    @if($product->sku_induk)
                                        <div class="small text-muted font-monospace"><i class="fas fa-code-branch me-1"></i>{{ $product->sku_induk }}</div>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $product->name }}</div>
                                    @if($product->ukuran || $product->warna)
                                        <small class="text-muted">
                                            @if($product->ukuran) Sz: <strong>{{ $product->ukuran }}</strong> @endif
                                            @if($product->warna) Wrn: <strong>{{ $product->warna }}</strong> @endif
                                        </small>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($product->is_bundle)
                                        <span class="badge rounded-pill px-2 py-1" style="background-color: #6f42c1; color: #fff;">
                                            <i class="fas fa-layer-group me-1"></i>Set / Bundle
                                        </span>
                                    @else
                                        <span class="badge bg-info text-dark rounded-pill px-2 py-1">
                                            <i class="fas fa-box me-1"></i>Single
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($product->is_bundle)
                                        @if($product->components->isNotEmpty())
                                            <ul class="list-unstyled mb-0 small">
                                                @foreach($product->components as $comp)
                                                    <li class="mb-1">
                                                        <span class="badge bg-secondary font-monospace me-1">{{ $comp->pivot->quantity }}x</span>
                                                        <code class="text-dark font-monospace fw-semibold">{{ $comp->sku }}</code>
                                                        <span class="text-muted">({{ $comp->name }})</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <span class="text-danger small"><i class="fas fa-exclamation-triangle me-1"></i>Belum set komponen!</span>
                                        @endif
                                    @else
                                        <span class="text-muted opacity-50">—</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="small">
                                        <div class="text-dark">{{ $product->category->name ?? '—' }}</div>
                                        <div class="text-muted">{{ $product->brand->name ?? '—' }}</div>
                                    </div>
                                </td>
                                <td class="text-end font-monospace text-muted small">
                                    Rp {{ number_format($product->cost_price, 0, ',', '.') }}
                                </td>
                                <td class="text-end font-monospace fw-semibold text-primary">
                                    Rp {{ number_format($product->price, 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $product->stock <= $product->min_stock ? 'bg-danger' : 'bg-success' }} font-monospace">
                                        {{ number_format($product->stock) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if($product->is_active)
                                        <span class="badge bg-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary">Nonaktif</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fs-2 d-block mb-2 opacity-25"></i>
                                    Tidak ada data produk yang sesuai filter.
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
