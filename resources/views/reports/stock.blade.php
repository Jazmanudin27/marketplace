@extends('layouts.app')
@section('title', 'Laporan Stok Barang (Gudang & Marketplace)')
@section('page-title', 'Laporan Stok Barang (Gudang & Marketplace)')

@section('content')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">Laporan Stok Barang (Gudang & Marketplace)</h4>
            <p class="text-muted mb-0 small">Pantau stok fisik di gudang vs stok terhubung di seluruh toko marketplace secara real-time.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('reports.stock.print', request()->all()) }}" target="_blank" class="btn btn-primary px-3 py-2 rounded-3 fw-semibold shadow-sm">
                <i class="fas fa-print me-1"></i> Cetak / Print Laporan
            </a>
        </div>
    </div>

    <!-- Filter Section Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form action="{{ route('reports.stock') }}" method="GET" id="stockFilterForm">
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
                    <div class="col-12 col-md-3">
                        <label class="form-label form-label-sm fw-semibold text-muted">Cari Nama / SKU</label>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm rounded-3" placeholder="Ketik nama produk atau SKU...">
                    </div>
                    <div class="col-12 col-md-2 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-sm btn-primary w-100 rounded-3 fw-semibold">
                            <i class="fas fa-filter me-1"></i> Filter
                        </button>
                        <a href="{{ route('reports.stock') }}" class="btn btn-sm btn-outline-secondary rounded-3">Reset</a>
                    </div>
                </div>

                <div class="d-flex gap-4 mt-3 pt-2 border-top">
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" name="hide_zero_stock" value="1" id="hideZeroStock" {{ request()->boolean('hide_zero_stock') ? 'checked' : '' }} onchange="this.form.submit()">
                        <label class="form-check-label small fw-semibold text-dark" for="hideZeroStock">
                            Sembunyikan Produk Stok 0
                        </label>
                    </div>
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" name="only_different" value="1" id="onlyDifferent" {{ request()->boolean('only_different') ? 'checked' : '' }} onchange="this.form.submit()">
                        <label class="form-check-label small fw-bold text-danger" for="onlyDifferent">
                            ⚠️ Hanya Stok Berbeda (Beda Gudang vs Toko)
                        </label>
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
                        <th class="text-center" style="width: 110px;">Status / PO</th>
                        <th style="width: 250px;">Toko / Marketplace Terhubung</th>
                        <th class="text-end bg-success bg-opacity-25" style="width: 110px;">Stok Gudang</th>
                        @foreach($stores as $store)
                            @php
                                $channelCode = strtolower($store->channel->code ?? $store->channel->name ?? '');
                                $channelShort = match(true) {
                                    str_contains($channelCode, 'shopee') => 'Shopee',
                                    str_contains($channelCode, 'tiktok') => 'TikTok',
                                    str_contains($channelCode, 'lazada') => 'Lazada',
                                    str_contains($channelCode, 'tokopedia') => 'Tokopedia',
                                    default => ucfirst($store->channel->name ?? 'MP'),
                                };
                            @endphp
                            <th class="text-end" style="min-width: 100px;">
                                <div>{{ $store->short_name }}</div>
                                <small class="fw-normal opacity-75 d-block" style="font-size: 0.7rem;">({{ $channelShort }})</small>
                            </th>
                        @endforeach
                        <th class="text-center" style="width: 80px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $index => $product)
                        @php
                            $stokGudang = (int) $product->stock;
                            $ledgerUrl = route('reports.ledger.print', [
                                'product_id' => $product->id,
                                'start_date' => now()->startOfMonth()->format('Y-m-d'),
                                'end_date'   => now()->format('Y-m-d'),
                            ]);
                            $hasDiscrepancy = false;
                        @endphp
                        <tr>
                            <td class="text-center text-muted">{{ $products->firstItem() + $index }}</td>
                            <td>
                                <a href="{{ $ledgerUrl }}" target="_blank" class="fw-bold text-dark text-decoration-none font-monospace">
                                    {{ $product->sku ?? '-' }}
                                </a>
                            </td>
                            <td>
                                <a href="{{ $ledgerUrl }}" target="_blank" class="fw-semibold text-dark text-decoration-none">
                                    {{ $product->name }}
                                </a>
                            </td>
                            <td class="text-center">
                                @if($product->is_preorder)
                                    <span class="badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25 rounded-pill px-2">⏳ PO</span>
                                @else
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2">📦 Ready</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $connectedStores = $product->marketplaceProducts
                                        ->map(fn($mp) => $mp->store)
                                        ->filter()
                                        ->unique('id');
                                @endphp
                                @forelse($connectedStores as $st)
                                    @php
                                        $chName = ucfirst($st->channel->name ?? $st->channel->code ?? 'MP');
                                        $badgeBg = match(strtolower($st->channel->code ?? '')) {
                                            'shopee' => 'bg-danger bg-opacity-10 text-danger border-danger border-opacity-25',
                                            'tiktok' => 'bg-dark bg-opacity-10 text-dark border-dark border-opacity-25',
                                            'tokopedia' => 'bg-success bg-opacity-10 text-success border-success border-opacity-25',
                                            'lazada' => 'bg-primary bg-opacity-10 text-primary border-primary border-opacity-25',
                                            default => 'bg-secondary bg-opacity-10 text-secondary border-secondary border-opacity-25',
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeBg }} border rounded-2 me-1 mb-1" style="font-size: 0.72rem; font-weight: 500;">
                                        <i class="fas fa-store me-1"></i>{{ $st->store_name }} ({{ $chName }})
                                    </span>
                                @empty
                                    <span class="text-muted small fst-italic">- Belum Terhubung -</span>
                                @endforelse
                            </td>
                            <td class="text-end fw-bold text-success bg-success bg-opacity-10 fs-6">
                                {{ number_format($stokGudang, 0, ',', '.') }}
                            </td>
                            @foreach($stores as $store)
                                @php
                                    $storeMpProducts = $product->marketplaceProducts->where('store_id', $store->id);
                                    $storeStock = $storeMpProducts->isNotEmpty() ? (int) $storeMpProducts->max('stock') : 0;
                                    $isDifferent = ($storeMpProducts->isNotEmpty() && $storeStock !== $stokGudang);
                                    if ($isDifferent) {
                                        $hasDiscrepancy = true;
                                    }
                                @endphp
                                <td class="text-end {{ $isDifferent ? 'bg-danger text-white fw-bold' : '' }}">
                                    @if($isDifferent)
                                        <span class="me-1" title="Stok beda dengan gudang">⚠️</span>
                                    @endif
                                    <span class="{{ $isDifferent ? 'text-white' : ($storeStock > 0 ? 'fw-bold text-primary' : 'text-muted') }}">
                                        {{ number_format($storeStock, 0, ',', '.') }}
                                    </span>
                                </td>
                            @endforeach
                            <td class="text-center">
                                @if($hasDiscrepancy)
                                    <button type="button" class="btn btn-sm btn-danger px-2 py-1 btn-sync-single" data-product-id="{{ $product->id }}" data-sku="{{ $product->sku ?? '-' }}">
                                        <i class="fas fa-sync-alt me-1"></i>Sync
                                    </button>
                                @else
                                    <span class="text-success fw-bold small"><i class="fas fa-check-circle me-1"></i>OK</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 7 + count($stores) }}" class="text-center py-5 text-muted">
                                <i class="fas fa-box-open fa-3x mb-3 opacity-50"></i>
                                <h6>Tidak ada data produk yang sesuai dengan filter.</h6>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($products->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $products->links() }}
            </div>
        @endif
    </div>
@endsection
