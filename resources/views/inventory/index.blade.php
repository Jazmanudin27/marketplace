@extends('layouts.app')
@section('title', 'Manajemen Inventory')
@section('page-title', 'Inventory & Kartu Stok')

@section('content')

    {{-- Stat Cards --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100 {{ !request('status') ? 'border-start border-primary border-4' : '' }}"
                style="cursor:pointer" id="statAll">
                <div class="card-body py-3 d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-primary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0"
                        style="width:42px;height:42px;">
                        <i class="fas fa-boxes text-primary"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-4">{{ number_format($stats['total_skus']) }}</div>
                        <div class="text-muted small">Total SKU Produk</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="cursor:pointer" id="statUnits">
                <div class="card-body py-3 d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0"
                        style="width:42px;height:42px;background:rgba(139,92,246,.1);">
                        <i class="fas fa-warehouse" style="color:#a78bfa;"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-4">{{ number_format($stats['total_stock']) }}</div>
                        <div class="text-muted small">Total Unit Stok</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100 {{ request('status') === 'low' ? 'border-start border-warning border-4' : '' }}"
                style="cursor:pointer" id="statLow">
                <div class="card-body py-3 d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-warning bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0"
                        style="width:42px;height:42px;">
                        <i class="fas fa-exclamation-triangle text-warning"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-4 text-warning">{{ number_format($stats['low_stock']) }}</div>
                        <div class="text-muted small">Stok Menipis</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100 {{ request('status') === 'empty' ? 'border-start border-danger border-4' : '' }}"
                style="cursor:pointer" id="statEmpty">
                <div class="card-body py-3 d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-danger bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0"
                        style="width:42px;height:42px;">
                        <i class="fas fa-times-circle text-danger"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-4 text-danger">{{ number_format($stats['out_of_stock']) }}</div>
                        <div class="text-muted small">Stok Habis</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2 px-3">
            <form action="{{ route('inventory.index') }}" method="GET" id="inventoryFilterForm">
                @if (request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}" id="hiddenStatus">
                @endif
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-8 col-lg-9">
                        <label class="form-label form-label-sm fw-semibold mb-1">
                            <i class="fas fa-search me-1"></i>Cari Produk
                        </label>
                        <input type="text" name="search" class="form-control form-control-sm"
                            placeholder="Cari SKU, Nama, Kategori, atau SKU Induk…" value="{{ request('search') }}"
                            id="inventorySearch">
                    </div>
                    <div class="col-6 col-md-2 col-lg-1">
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="fas fa-search me-1"></i>Cari
                        </button>
                    </div>
                    <div class="col-6 col-md-2 col-lg-2 d-flex gap-2">
                        @if (request('search') || request('status'))
                            <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary btn-sm flex-fill">
                                <i class="fas fa-times me-1"></i>Reset
                            </a>
                        @endif
                        <a href="{{ route('stock_opnames.create') }}" class="btn btn-success btn-sm flex-fill">
                            <i class="fas fa-clipboard-check me-1"></i>Stock Opname
                        </a>
                    </div>
                </div>

                @if (request('search') || request('status'))
                    <div class="d-flex flex-wrap gap-2 mt-2 align-items-center">
                        <span class="text-muted small"><i class="fas fa-filter me-1"></i>Filter Aktif:</span>
                        @if (request('status'))
                            @php
                                $statusLabel =
                                    ['low' => 'Stok Menipis', 'empty' => 'Stok Habis', 'safe' => 'Stok Aman'][
                                        request('status')
                                    ] ?? request('status');
                                $statusClass =
                                    ['low' => 'bg-warning text-dark', 'empty' => 'bg-danger', 'safe' => 'bg-success'][
                                        request('status')
                                    ] ?? 'bg-secondary';
                            @endphp
                            <span class="badge {{ $statusClass }} d-inline-flex align-items-center gap-1 py-1 px-2">
                                Status: {{ $statusLabel }}
                                <a href="{{ route('inventory.index', request()->except('status')) }}"
                                    class="text-white text-decoration-none ms-1 opacity-75">
                                    <i class="fas fa-times-circle"></i>
                                </a>
                            </span>
                        @endif
                        @if (request('search'))
                            <span class="badge bg-secondary d-inline-flex align-items-center gap-1 py-1 px-2">
                                Kata Kunci: "{{ request('search') }}"
                                <a href="{{ route('inventory.index', request()->except('search')) }}"
                                    class="text-white text-decoration-none ms-1 opacity-75">
                                    <i class="fas fa-times-circle"></i>
                                </a>
                            </span>
                        @endif
                    </div>
                @endif
            </form>
        </div>
    </div>

    {{-- Main Table Card --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-info bg-opacity-10 d-flex justify-content-between align-items-center py-2 px-3">
            <div>
                <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-boxes text-info me-2"></i>Daftar Persediaan Barang</h6>
                <small class="text-muted">
                    Menampilkan {{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }}
                    dari {{ $products->total() }} produk
                </small>
            </div>
            <a href="{{ route('stock_opnames.create') }}"
                class="btn btn-success btn-sm fw-semibold d-none d-md-inline-flex align-items-center gap-1">
                <i class="fas fa-clipboard-check"></i> Mulai Stock Opname
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Detail Produk</th>
                            <th class="text-end" style="width:14%">Harga Modal (HPP)</th>
                            <th class="text-end" style="width:14%">Harga Jual</th>
                            <th class="text-center" style="width:18%">Stok Tersedia</th>
                            <th class="text-center pe-3" style="width:13%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                            <tr>
                                <td class="ps-3 py-2">
                                    <div class="d-flex align-items-center gap-3">
                                        @if ($product->image_url)
                                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                                                class="rounded border"
                                                style="width:46px;height:46px;object-fit:cover;flex-shrink:0">
                                        @else
                                            <div class="rounded border bg-light d-flex align-items-center justify-content-center text-muted"
                                                style="width:46px;height:46px;flex-shrink:0;">
                                                <i class="fas fa-image"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <div class="d-flex align-items-center gap-1 flex-wrap mb-1">
                                                <code class="bg-light text-secondary px-1 rounded small border"
                                                    style="font-size:.75rem">{{ $product->sku }}</code>
                                                @if ($product->sku_induk)
                                                    <code class="text-muted small" style="font-size:.7rem;">Induk:
                                                        {{ $product->sku_induk }}</code>
                                                @endif
                                            </div>
                                            <div class="fw-semibold small">{{ $product->name }}</div>
                                            <div class="d-flex flex-wrap gap-1 mt-1">
                                                @if ($product->category)
                                                    <span
                                                        class="badge bg-secondary bg-opacity-25 text-secondary border small"
                                                        style="font-size:.68rem;">
                                                        <i
                                                            class="fas fa-folder me-1 opacity-75"></i>{{ $product->category->name }}
                                                    </span>
                                                @endif
                                                @if ($product->brand)
                                                    <span
                                                        class="badge bg-secondary bg-opacity-25 text-secondary border small"
                                                        style="font-size:.68rem;">
                                                        <i
                                                            class="fas fa-tag me-1 opacity-75"></i>{{ $product->brand->name }}
                                                    </span>
                                                @endif
                                                @if ($product->ukuran)
                                                    <span class="badge bg-info bg-opacity-25 text-info border small"
                                                        style="font-size:.68rem;">{{ $product->ukuran }}</span>
                                                @endif
                                                @if ($product->warna)
                                                    <span class="badge bg-warning bg-opacity-25 text-warning border small"
                                                        style="font-size:.68rem;">{{ $product->warna }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end text-muted small fw-semibold font-monospace">
                                    Rp {{ number_format($product->cost_price, 0, ',', '.') }}
                                </td>
                                <td class="text-end fw-bold small font-monospace">
                                    Rp {{ number_format($product->price, 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    @if ($product->stock <= 0)
                                        <span
                                            class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1 small">
                                            <i class="fas fa-times-circle me-1"></i>Habis (0)
                                        </span>
                                    @elseif ($product->stock <= $product->min_stock)
                                        <span
                                            class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1 small">
                                            <i class="fas fa-exclamation-triangle me-1"></i>Menipis
                                            ({{ number_format($product->stock) }})
                                        </span>
                                    @else
                                        <span
                                            class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 small">
                                            <i class="fas fa-check-circle me-1"></i>Aman
                                            ({{ number_format($product->stock) }})
                                        </span>
                                    @endif
                                    <div class="text-muted mt-1" style="font-size:.7rem">Min. Stok:
                                        {{ number_format($product->min_stock) }}</div>
                                </td>
                                <td class="text-center pe-3">
                                    <a href="{{ route('inventory.ledger', $product->id) }}"
                                        class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1">
                                        <i class="fas fa-clipboard-list"></i> Kartu Stok
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-5">
                                    <i class="fas fa-boxes fa-2x mb-3 d-block opacity-25"></i>
                                    <div class="fw-semibold mb-1">Tidak Ada Produk Ditemukan</div>
                                    <div class="small">Sesuaikan filter atau tambah produk baru.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($products->hasPages())
                <div class="d-flex justify-content-between align-items-center px-3 py-2">
                    <span class="text-muted small">
                        Menampilkan {{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }}
                        dari {{ $products->total() }} produk
                    </span>
                    {{ $products->withQueryString()->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        $(function() {
            const baseUrl = '{{ route('inventory.index') }}';
            const search = '{{ request('search') }}';

            function goFilter(status) {
                const params = new URLSearchParams();
                if (search) params.set('search', search);
                if (status) params.set('status', status);
                const qs = params.toString();
                window.location.href = baseUrl + (qs ? '?' + qs : '');
            }

            $('#statAll').on('click', () => goFilter(''));
            $('#statUnits').on('click', () => goFilter(''));
            $('#statLow').on('click', () => goFilter('low'));
            $('#statEmpty').on('click', () => goFilter('empty'));

            let timer;
            $('#inventorySearch').on('input', function() {
                clearTimeout(timer);
                timer = setTimeout(() => $('#inventoryFilterForm').submit(), 500);
            });
        });
    </script>
@endpush
