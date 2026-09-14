@extends('layouts.app')
@section('title', 'Laporan Master Produk')
@section('page-title', 'Laporan Master Produk')

@section('content')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">Laporan Master Produk</h4>
            <p class="text-muted mb-0 small">Data master produk lengkap dengan SKU, Harga Jual, HPP, Estimasi Kain, Estimasi Harga Produksi, Stok, dan Toko Marketplace.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('reports.master_product.export', request()->all()) }}" class="btn btn-outline-success px-3 py-2 rounded-3 fw-semibold shadow-sm">
                <i class="fas fa-file-excel me-1"></i> Ekspor CSV / Excel
            </a>
            <a href="{{ route('reports.master_product.print', request()->all()) }}" target="_blank" class="btn btn-primary px-3 py-2 rounded-3 fw-semibold shadow-sm">
                <i class="fas fa-print me-1"></i> Cetak / Print Laporan
            </a>
        </div>
    </div>

    <!-- Stat Cards Row -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-4">
                        <i class="fas fa-boxes fs-4"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.75rem;">Total Master Produk</small>
                        <h4 class="fw-bold mb-0 text-dark">{{ number_format($totalCount) }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="p-3 rounded-4" style="color: #6f42c1; background-color: #f3ebff;">
                        <i class="fas fa-layer-group fs-4"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.75rem;">Produk Set / Bundling</small>
                        <h4 class="fw-bold mb-0" style="color: #6f42c1;">{{ number_format($bundleCount) }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="bg-info bg-opacity-10 text-info p-3 rounded-4">
                        <i class="fas fa-box fs-4"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.75rem;">Produk Single</small>
                        <h4 class="fw-bold mb-0 text-info">{{ number_format($singleCount) }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="bg-success bg-opacity-10 text-success p-3 rounded-4">
                        <i class="fas fa-coins fs-4"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.75rem;">Est. Total Modal Stok</small>
                        <h4 class="fw-bold mb-0 text-success">Rp {{ number_format($totalStockValue, 0, ',', '.') }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Section Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form action="{{ route('reports.master_product') }}" method="GET" id="masterProductFilterForm">
                <div class="row g-3">
                    <div class="col-12 col-md-2">
                        <label class="form-label form-label-sm fw-semibold text-muted">Toko / Marketplace</label>
                        <select name="store_id" class="form-select form-select-sm rounded-3">
                            <option value="">Semua Toko</option>
                            @foreach ($stores as $st)
                                <option value="{{ $st->id }}" {{ request('store_id') == $st->id ? 'selected' : '' }}>
                                    {{ $st->store_name }} ({{ ucfirst($st->channel->name ?? $st->channel->code ?? 'MP') }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label form-label-sm fw-semibold text-muted">Jenis Produk</label>
                        <select name="is_bundle" class="form-select form-select-sm rounded-3">
                            <option value="">Semua Jenis</option>
                            <option value="0" {{ request('is_bundle') === '0' ? 'selected' : '' }}>📦 Single</option>
                            <option value="1" {{ request('is_bundle') === '1' ? 'selected' : '' }}>🎁 Set / Bundling</option>
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
                    <div class="col-12 col-md-2">
                        <label class="form-label form-label-sm fw-semibold text-muted">Kategori</label>
                        <select name="category_id" class="form-select form-select-sm rounded-3">
                            <option value="">Semua Kategori</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label form-label-sm fw-semibold text-muted">Status</label>
                        <select name="is_active" class="form-select form-select-sm rounded-3">
                            <option value="">Semua Status</option>
                            <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Aktif</option>
                            <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Nonaktif</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label form-label-sm fw-semibold text-muted">Cari Nama / SKU</label>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm rounded-3" placeholder="Ketik Nama / SKU...">
                    </div>
                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-3 pt-2 border-top">
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" name="hide_zero_stock" value="1" id="hideZeroStock" {{ request()->boolean('hide_zero_stock') ? 'checked' : '' }} onchange="this.form.submit()">
                        <label class="form-check-label small fw-semibold text-dark" for="hideZeroStock">
                            Sembunyikan Produk Stok 0
                        </label>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-primary px-3 rounded-3 fw-semibold">
                            <i class="fas fa-filter me-1"></i> Filter
                        </button>
                        <a href="{{ route('reports.master_product') }}" class="btn btn-sm btn-outline-secondary rounded-3 px-3">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                <thead class="table-dark">
                    <tr>
                        <th class="text-center" style="width: 40px;">#</th>
                        <th style="width: 140px;">SKU</th>
                        <th>Nama Produk</th>
                        <th class="text-end" style="width: 120px;">Harga Jual</th>
                        <th class="text-end" style="width: 120px;">Harga HPP</th>
                        <th class="text-end" style="width: 110px;">Estimasi Kain</th>
                        <th class="text-end" style="width: 140px;">Est. Harga Produksi</th>
                        <th class="text-end bg-success bg-opacity-25" style="width: 100px;">Stok Gudang</th>
                        <th class="text-center" style="width: 120px;">Status</th>
                        <th style="width: 220px;">Toko Marketplace Terhubung</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $index => $product)
                        @php
                            $mpCount = $product->marketplaceProducts->count();
                            $mpStockTotal = $product->marketplaceProducts->sum('stock');
                        @endphp
                        <tr>
                            <td class="text-center text-muted fw-semibold">{{ $index + 1 }}</td>
                            <td>
                                <div class="fw-bold text-dark font-monospace">{{ $product->sku }}</div>
                                @if($product->sku_induk)
                                    <small class="text-muted d-block font-monospace" style="font-size: 0.75rem;">Induk: {{ $product->sku_induk }}</small>
                                @endif
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $product->name }}</div>
                                <div class="d-flex flex-wrap gap-1 align-items-center mt-1">
                                    @if($product->ukuran)
                                        <span class="badge bg-light text-dark border">{{ $product->ukuran }}</span>
                                    @endif
                                    @if($product->warna)
                                        <span class="badge bg-light text-dark border">{{ $product->warna }}</span>
                                    @endif
                                    @if($product->category)
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25">{{ $product->category->name }}</span>
                                    @endif
                                    @if($product->brand)
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">{{ $product->brand->name }}</span>
                                    @endif
                                </div>
                                @if($product->is_bundle && $product->components->isNotEmpty())
                                    <div class="mt-1 small">
                                        <span class="text-muted me-1"><i class="fas fa-layer-group me-1 text-purple" style="color:#6f42c1;"></i>Komponen Set:</span>
                                        @foreach($product->components as $comp)
                                            <span class="badge bg-light text-dark border font-monospace" style="font-size: 0.7rem;">
                                                @if($comp->pivot->quantity > 1)<strong class="text-primary">{{ $comp->pivot->quantity }}x</strong> @endif{{ $comp->sku }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="text-end fw-bold text-primary font-monospace">
                                Rp {{ number_format($product->price, 0, ',', '.') }}
                            </td>
                            <td class="text-end font-monospace text-muted">
                                Rp {{ number_format($product->cost_price, 0, ',', '.') }}
                            </td>
                            <td class="text-end font-monospace text-dark">
                                @if($product->est_kain > 0)
                                    <span class="fw-semibold text-dark">{{ number_format($product->est_kain, 2, ',', '.') }}</span> <small class="text-muted">m</small>
                                @else
                                    <span class="text-muted opacity-50">-</span>
                                @endif
                            </td>
                            <td class="text-end font-monospace text-dark">
                                @if($product->est_biaya_produksi > 0)
                                    <span class="fw-semibold text-dark">Rp {{ number_format($product->est_biaya_produksi, 0, ',', '.') }}</span>
                                @else
                                    <span class="text-muted opacity-50">-</span>
                                @endif
                            </td>
                            <td class="text-end fw-bold text-success bg-success bg-opacity-10 fs-6">
                                {{ number_format($product->stock, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                <div class="d-flex flex-column align-items-center gap-1">
                                    @if($product->is_active)
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2" style="font-size: 0.7rem;">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill px-2" style="font-size: 0.7rem;">Nonaktif</span>
                                    @endif

                                    @if($product->is_preorder)
                                        <span class="badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25 rounded-pill px-2" style="font-size: 0.7rem;">⏳ PO</span>
                                    @else
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2" style="font-size: 0.7rem;">📦 Ready</span>
                                    @endif

                                    @if($product->is_bundle)
                                        <span class="badge rounded-pill px-2" style="font-size: 0.7rem; background-color: #6f42c1; color: #fff;">🎁 Set</span>
                                    @else
                                        <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-2" style="font-size: 0.7rem;">🏷️ Single</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if($mpCount > 0)
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($product->marketplaceProducts as $mp)
                                            <span class="badge bg-light text-dark border p-1" style="font-size: 0.7rem;" title="SKU MP: {{ $mp->marketplace_sku ?? '—' }}">
                                                <i class="fas fa-store me-1 text-primary"></i>{{ $mp->store->channel->name ?? '' }}: {{ $mp->store->store_name ?? '' }}
                                                <span class="badge bg-secondary ms-1" style="font-size: 0.65rem;">Stok: {{ number_format($mp->stock) }}</span>
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-muted small fst-italic">- Belum Terhubung -</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="fas fa-box-open fa-3x mb-3 opacity-50"></i>
                                <h6>Tidak ada data produk yang sesuai dengan filter.</h6>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
