@extends('layouts.app')
@section('title', 'Laporan Stok Barang')
@section('page-title', 'Laporan Stok Barang')

@section('content')
    <style>
        a.product-link {
            color: #1e293b !important;
            text-decoration: none !important;
            font-weight: 600;
            cursor: pointer;
        }
        a.product-link:hover {
            color: #0284c7 !important;
            text-decoration: none !important;
        }
    </style>
    {{-- FILTER CARD --}}
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
        <div class="card-header bg-info bg-opacity-10 py-3 px-4 border-0 rounded-top-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                    <i class="fas fa-boxes text-info fs-5"></i>
                    <span>Laporan Stok Barang (Gudang &amp; Marketplace)</span>
                </h6>
                <a href="{{ route('reports.stock.print', request()->all()) }}" target="_blank"
                    class="btn btn-sm btn-primary fw-bold px-3 py-1.5 rounded-pill shadow-2xs d-inline-flex align-items-center gap-1.5">
                    <i class="fas fa-print"></i>
                    <span>Cetak Laporan Stok</span>
                </a>
            </div>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('reports.stock') }}" method="GET" class="m-0">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-3">
                        <label class="form-label form-label-sm fw-semibold text-muted">Kategori Produk</label>
                        <select name="category_id" id="category_id" class="form-select form-select-sm select2">
                            <option value="">Semua Kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label form-label-sm fw-semibold text-muted">Merk Produk</label>
                        <select name="brand_id" id="brand_id" class="form-select form-select-sm select2">
                            <option value="">Semua Merk</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->id }}" {{ request('brand_id') == $brand->id ? 'selected' : '' }}>
                                    {{ $brand->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label form-label-sm fw-semibold text-muted">Jenis Produk</label>
                        <select name="is_bundle" id="is_bundle" class="form-select form-select-sm select2">
                            <option value="" {{ request('is_bundle') === null || request('is_bundle') === '' ? 'selected' : '' }}>Semua Jenis (Single &amp; BUNDLE)</option>
                            <option value="0" {{ request('is_bundle') === '0' ? 'selected' : '' }}>📦 Single (Produk Standar)</option>
                            <option value="1" {{ request('is_bundle') === '1' ? 'selected' : '' }}>🎁 BUNDLE / Paket Set</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label form-label-sm fw-semibold text-muted">Tipe Pre-Order (PO)</label>
                        <select name="is_preorder" id="is_preorder" class="form-select form-select-sm select2">
                            <option value="" {{ request('is_preorder') === null || request('is_preorder') === '' ? 'selected' : '' }}>Semua Tipe (PO &amp; Ready)</option>
                            <option value="1" {{ request('is_preorder') === '1' ? 'selected' : '' }}>⏳ Pre-Order (PO)</option>
                            <option value="0" {{ request('is_preorder') === '0' ? 'selected' : '' }}>📦 Ready Stock (Bukan PO)</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-6 col-lg-5">
                        <label class="form-label form-label-sm fw-semibold text-muted">Cari Nama / SKU</label>
                        <div class="position-relative">
                            <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted opacity-75"></i>
                            <input type="text" name="search" class="form-control form-control-sm ps-5 rounded-3"
                                value="{{ request('search') }}" placeholder="Ketik kata kunci nama produk atau SKU...">
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="form-check form-switch mt-3">
                            <input class="form-check-input" type="checkbox" name="hide_zero_stock" value="1" id="hideZeroStock" {{ request('hide_zero_stock') ? 'checked' : '' }}>
                            <label class="form-check-label small fw-semibold text-dark" for="hideZeroStock">
                                Sembunyikan / Hilangkan Produk Stok 0
                            </label>
                        </div>
                    </div>

                    <div class="col-12 col-md-12 col-lg-3 d-flex gap-2 justify-content-end">
                        <button type="submit" class="btn btn-sm btn-primary fw-bold px-3 rounded-3 flex-fill">
                            <i class="fas fa-filter me-1"></i> Filter Data
                        </button>
                        @if(request()->anyFilled(['category_id', 'brand_id', 'is_bundle', 'is_preorder', 'product_id', 'search', 'hide_zero_stock']))
                            <a href="{{ route('reports.stock') }}" class="btn btn-sm btn-outline-secondary px-3 rounded-3">
                                <i class="fas fa-undo me-1"></i> Reset
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- STOCK REPORT TABLE CONTAINER --}}
    <div class="card border-0 shadow-sm rounded-4 bg-white">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                    <thead class="bg-light">
                        <tr class="text-uppercase text-secondary fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                            <th class="py-3 px-3 text-center" style="width: 50px;">No</th>
                            <th class="py-3" style="width: 140px;">SKU</th>
                            <th class="py-3">Nama Produk</th>
                            <th class="py-3">Kategori / Merk</th>
                            <th class="py-3 text-center" style="width: 110px;">Tipe Order</th>
                            <th class="py-3 text-end text-primary" style="width: 120px;">Stok Gudang</th>
                            <th class="py-3 text-end text-info" style="width: 140px;">Stok Marketplace</th>
                            <th class="py-3 text-end text-success" style="width: 120px;">Total Stok</th>
                            <th class="py-3 text-center" style="width: 140px;">Kartu Stok</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($products as $index => $row)
                            @php
                                $stokGudang = (int) $row->stock;
                                $stokMp = (int) $row->marketplaceProducts->sum('stock');
                                $totalStok = $stokGudang + $stokMp;
                                $ledgerUrl = route('reports.ledger', ['product_id' => $row->id]);
                                $ledgerPrintUrl = route('reports.ledger.print', ['product_id' => $row->id]);
                            @endphp
                            <tr>
                                <td class="text-center text-muted fw-bold">
                                    {{ ($products->currentPage() - 1) * $products->perPage() + $index + 1 }}
                                </td>
                                <td>
                                    <a href="{{ $ledgerUrl }}" target="_blank" class="product-link font-monospace"
                                       title="Klik untuk membuka Kartu Stok {{ $row->name }}">
                                        {{ $row->sku ?: '—' }}
                                    </a>
                                </td>
                                <td>
                                    <a href="{{ $ledgerUrl }}" target="_blank" class="product-link"
                                       title="Klik untuk melihat histori Kartu Stok produk ini">
                                        {{ $row->name }}
                                    </a>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $row->category->name ?? '—' }}</div>
                                    <small class="text-muted" style="font-size: 0.75rem;">{{ $row->brand->name ?? 'Tanpa Merk' }}</small>
                                </td>
                                <td class="text-center">
                                    @if ($row->is_preorder)
                                        <span class="badge bg-warning bg-opacity-15 text-warning-emphasis border border-warning-subtle rounded-pill px-2 py-1 fw-bold" style="font-size: 9px;">
                                            📦 PO ({{ $row->preorder_days ?: 7 }}hr)
                                        </span>
                                    @else
                                         <span class="badge rounded-pill px-2 py-1 fw-bold" style="background-color: #dcfce7; color: #15803d; border: 1px solid #86efac; font-size: 9px;">
                                             ⚡ Ready Stock
                                         </span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ $ledgerUrl }}" class="fw-extrabold text-primary text-decoration-none fs-6"
                                       title="Stok Fisik ERP Gudang (Klik untuk Buka Kartu Stok)">
                                        {{ number_format($stokGudang, 0, ',', '.') }}
                                    </a>
                                </td>
                                <td class="text-end">
                                    @if ($row->marketplaceProducts->isNotEmpty())
                                        <span class="fw-bold text-info cursor-pointer" 
                                              data-bs-toggle="tooltip" 
                                              data-bs-html="true" 
                                              title="Rincian Stok Toko:<br>@foreach($row->marketplaceProducts as $mp){{ $mp->store->channel->name ?? 'MP' }} ({{ $mp->store->store_name ?? '' }}): <b>{{ number_format($mp->stock) }}</b><br>@endforeach">
                                            {{ number_format($stokMp, 0, ',', '.') }}
                                        </span>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <span class="fw-extrabold fs-6 {{ $totalStok <= 0 ? 'text-danger' : 'text-success' }}">
                                        {{ number_format($totalStok, 0, ',', '.') }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ $ledgerUrl }}"
                                        class="btn btn-xs btn-outline-info rounded-pill px-2.5 py-1 fw-bold d-inline-flex align-items-center gap-1 shadow-2xs"
                                        title="Buka Mutasi & Kartu Stok Produk">
                                        <i class="fas fa-history text-info"></i>
                                        <span>Kartu Stok</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <div class="mb-2 opacity-30"><i class="fas fa-boxes fa-3x"></i></div>
                                    <div class="fw-bold fs-6 text-dark">Tidak Ada Data Stok Barang</div>
                                    <small>Tidak ada produk yang sesuai dengan filter atau kata kunci pencarian Anda.</small>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($products->hasPages())
                <div class="p-3 border-top">
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                theme: 'bootstrap-5',
                width: '100%',
                allowClear: true,
                placeholder: '-- Pilih / Ketik untuk Cari --'
            });

            // Initialize Bootstrap Tooltips for Marketplace Stock breakdowns
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
@endpush
