@extends('layouts.app')
@section('title', 'Laporan Stok Barang (Gudang & Marketplace)')
@section('page-title', 'Laporan Stok Barang (Gudang & Marketplace)')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary bg-opacity-10 py-2.5 px-3 d-flex align-items-center justify-content-between">
                    <h6 class="fw-bold mb-0 text-primary"><i class="fas fa-boxes me-2"></i>Filter Laporan Stok Barang (Gudang & Marketplace)</h6>
                    <button class="btn btn-sm btn-outline-primary py-0.5 px-2 font-monospace text-xs" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                        <i class="fas fa-filter me-1"></i> Toggle Filter
                    </button>
                </div>
                <div class="card-body collapse show" id="filterCollapse">
                    <form action="{{ route('reports.stock') }}" method="GET" id="stockFilterForm">
                        <div class="row g-2">
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label form-label-sm fw-semibold mb-1">Kategori</label>
                                <select name="category_id" class="form-select form-select-sm">
                                    <option value="">Semua Kategori</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label form-label-sm fw-semibold mb-1">Merk</label>
                                <select name="brand_id" class="form-select form-select-sm">
                                    <option value="">Semua Merk</option>
                                    @foreach ($brands as $brand)
                                        <option value="{{ $brand->id }}" {{ request('brand_id') == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label form-label-sm fw-semibold mb-1">Toko / Marketplace</label>
                                <select name="store_id" class="form-select form-select-sm">
                                    <option value="">Semua Toko Marketplace</option>
                                    @foreach ($stores as $st)
                                        <option value="{{ $st->id }}" {{ request('store_id') == $st->id ? 'selected' : '' }}>
                                            {{ $st->store_name }} ({{ ucfirst($st->channel->name ?? $st->channel->code ?? 'MP') }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label form-label-sm fw-semibold mb-1">Jenis Produk</label>
                                <select name="is_bundle" class="form-select form-select-sm">
                                    <option value="">Semua Jenis (Single & BUNDLE)</option>
                                    <option value="0" {{ request('is_bundle') === '0' ? 'selected' : '' }}>📦 Single (Produk Standar)</option>
                                    <option value="1" {{ request('is_bundle') === '1' ? 'selected' : '' }}>🎁 BUNDLE / Paket Set</option>
                                </select>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label form-label-sm fw-semibold mb-1">Tipe Pre-Order (PO)</label>
                                <select name="is_preorder" class="form-select form-select-sm">
                                    <option value="">Semua Tipe (PO & Reguler)</option>
                                    <option value="1" {{ request('is_preorder') === '1' ? 'selected' : '' }}>⏳ Pre-Order (PO)</option>
                                    <option value="0" {{ request('is_preorder') === '0' ? 'selected' : '' }}>📦 Reguler (Bukan PO)</option>
                                </select>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <label class="form-label form-label-sm fw-semibold mb-1">Cari Nama / SKU</label>
                                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Ketik nama produk atau SKU...">
                            </div>
                            <div class="col-md-5 col-sm-12 d-flex align-items-end justify-content-between pt-2">
                                <div class="d-flex flex-column gap-1">
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" name="hide_zero_stock" value="1" id="hideZeroStock" {{ request()->boolean('hide_zero_stock') ? 'checked' : '' }}>
                                        <label class="form-check-label small fw-semibold text-dark" for="hideZeroStock">
                                            Hilangkan Produk Stok 0
                                        </label>
                                    </div>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" name="only_different" value="1" id="onlyDifferent" {{ request()->boolean('only_different') ? 'checked' : '' }}>
                                        <label class="form-check-label small fw-bold text-danger" for="onlyDifferent">
                                            ⚠️ Hanya Stok Berbeda (Beda Gudang vs Toko)
                                        </label>
                                    </div>
                                </div>
                                <div>
                                    <button type="submit" class="btn btn-sm btn-primary px-3 me-1">
                                        <i class="fas fa-search me-1"></i> Tampilkan
                                    </button>
                                    <a href="{{ route('reports.stock.print', request()->all()) }}" target="_blank" class="btn btn-sm btn-success px-3">
                                        <i class="fas fa-print me-1"></i> Cetak Laporan
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Data Table Matching User Request (Without Total Stok & With Red Background Discrepancy Indicator) --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0" style="font-size: 0.78rem;">
                    <thead>
                        <tr style="border-bottom: 2px solid #000;">
                            <th class="text-white text-center align-middle" style="background-color: #3b82f6; width: 40px;">No</th>
                            <th class="text-white align-middle" style="background-color: #3b82f6; width: 140px;">SKU</th>
                            <th class="text-white align-middle" style="background-color: #3b82f6;">Nama Produk</th>
                            <th class="text-white align-middle" style="background-color: #3b82f6; width: 150px;">Kategori / Merk</th>
                            <th class="text-white text-center align-middle" style="background-color: #3b82f6; width: 110px;">Status & PO</th>
                            <th class="text-white text-center align-middle" style="background-color: #22c55e; width: 100px;">Stok Gudang</th>
                            @foreach($stores as $st)
                                <th class="text-white text-center align-middle" style="background-color: #0284c7;">
                                    {{ $st->store_name }}
                                    <span class="d-block fw-normal opacity-75" style="font-size: 0.68rem;">
                                        ({{ ucfirst($st->channel->name ?? $st->channel->code ?? 'MP') }})
                                    </span>
                                </th>
                            @endforeach
                            <th class="text-white text-center align-middle" style="background-color: #4b5563; width: 90px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $index => $product)
                            @php
                                $stokGudang = (int) $product->stock;
                                $ledgerUrl = route('reports.ledger.print', [
                                    'product_id' => $product->id,
                                    'start_date' => now()->startOfMonth()->format('Y-m-d'),
                                    'end_date'   => now()->format('Y-m-d'),
                                ]);
                            @endphp
                            <tr>
                                <td class="text-center font-monospace text-muted">{{ $products->firstItem() + $index }}</td>
                                <td>
                                    <a href="{{ $ledgerUrl }}" target="_blank" class="fw-bold text-dark text-decoration-none" title="Buka Kartu Stok">
                                        {{ $product->sku ?? '-' }}
                                    </a>
                                </td>
                                <td>
                                    <a href="{{ $ledgerUrl }}" target="_blank" class="fw-semibold text-dark text-decoration-none" title="Buka Kartu Stok">
                                        {{ $product->name }}
                                    </a>
                                </td>
                                <td>
                                    <span class="fw-bold text-dark d-block">{{ $product->category->name ?? '-' }}</span>
                                    <span class="text-muted small">{{ $product->brand->name ?? '-' }}</span>
                                </td>
                                <td class="text-center">
                                    @if($product->is_preorder)
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size: 0.68rem;">
                                            ⏳ PO ({{ $product->preorder_days ?: 7 }}hr)
                                        </span>
                                    @else
                                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.68rem;">
                                            📦 Ready Stock
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end fw-bold text-success" style="background-color: #f0fdf4;">
                                    {{ number_format($stokGudang, 0, ',', '.') }}
                                </td>
                                @php
                                    $hasDiscrepancy = false;
                                @endphp
                                @foreach($stores as $st)
                                    @php
                                        $storeMpProducts = $product->marketplaceProducts->where('store_id', $st->id);
                                        $storeStock = $storeMpProducts->isNotEmpty() ? (int) $storeMpProducts->max('stock') : 0;
                                        $isDifferent = ($storeMpProducts->isNotEmpty() && $storeStock !== $stokGudang);
                                        if ($isDifferent) {
                                            $hasDiscrepancy = true;
                                        }
                                    @endphp
                                    <td data-store-id="{{ $st->id }}" class="text-end font-monospace align-middle {{ $isDifferent ? 'bg-danger text-white fw-bold' : ($storeStock > 0 ? 'fw-bold text-primary' : 'text-muted') }}"
                                        @if($isDifferent) title="Beda Stok! Gudang: {{ $stokGudang }}, Toko {{ $st->store_name }}: {{ $storeStock }}" @endif>
                                        @if($isDifferent)
                                            <i class="fas fa-exclamation-triangle text-warning me-1"></i>
                                        @endif
                                        {{ number_format($storeStock, 0, ',', '.') }}
                                    </td>
                                @endforeach
                                <td class="text-center align-middle">
                                    @if($hasDiscrepancy)
                                        <button type="button" 
                                            class="btn btn-sm btn-danger py-0.5 px-2 fw-semibold btn-sync-stock" 
                                            data-product-id="{{ $product->id }}" 
                                            data-product-sku="{{ $product->sku ?? '-' }}"
                                            data-product-name="{{ $product->name }}"
                                            data-stok-gudang="{{ $stokGudang }}"
                                            style="font-size: 0.72rem;">
                                            <i class="fas fa-sync me-1"></i> Sync
                                        </button>
                                    @else
                                        <button type="button" 
                                            class="btn btn-sm btn-outline-secondary py-0.5 px-2 fw-semibold" 
                                            disabled
                                            style="font-size: 0.72rem;">
                                            <i class="fas fa-check me-1 text-success"></i> Sinkron
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 7 + count($stores) }}" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block opacity-25"></i>
                                    Tidak ada data stok barang yang sesuai dengan filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($products->hasPages())
            <div class="card-footer bg-white border-0 py-2 px-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="small text-muted">
                        Menampilkan {{ $products->firstItem() }} - {{ $products->lastItem() }} dari total {{ $products->total() }} produk
                    </div>
                    <div>
                        {{ $products->links() }}
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.btn-sync-stock').on('click', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var productId = $btn.data('product-id');
        var sku = $btn.data('product-sku');
        var name = $btn.data('product-name');
        var stokGudang = parseInt($btn.data('stok-gudang'));
        var $row = $btn.closest('tr');
        
        // Show loading state on button
        var originalHtml = $btn.html();
        $btn.html('<i class="fas fa-spinner fa-spin me-1"></i> Syncing...').prop('disabled', true);
        
        $.ajax({
            url: '/reports/stock/' + productId + '/sync',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Show success toast
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: response.message,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true
                    });
                    
                    // Update the marketplace stock values in the row to match the warehouse stock
                    $row.find('td[data-store-id]').each(function() {
                        var $td = $(this);
                        if ($td.hasClass('bg-danger')) {
                            $td.removeClass('bg-danger text-white fw-bold')
                               .addClass('fw-bold text-primary')
                               .removeAttr('title')
                               .html(stokGudang.toLocaleString('id-ID'));
                        }
                    });
                    
                    // Replace the button with the disabled "Sinkron" success button
                    $btn.parent().html(
                        '<button type="button" class="btn btn-sm btn-outline-secondary py-0.5 px-2 fw-semibold" disabled style="font-size: 0.72rem;">' +
                        '<i class="fas fa-check me-1 text-success"></i> Sinkron' +
                        '</button>'
                    );
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: response.message || 'Gagal menyinkronkan stok.'
                    });
                    $btn.html(originalHtml).prop('disabled', false);
                }
            },
            error: function(xhr) {
                var errorMessage = 'Terjadi kesalahan sistem saat menyinkronkan stok.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: errorMessage
                });
                $btn.html(originalHtml).prop('disabled', false);
            }
        });
    });
});
</script>
@endpush
