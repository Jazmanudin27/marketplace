@extends('layouts.app')
@section('title', 'Manajemen Inventory')
@section('page-title', 'Inventory & Kartu Stok')

@section('content')

    {{-- ── Stat Cards ─────────────────────────────────────────────── --}}
    <div class="row g-3 mb-3">

        {{-- Total SKU --}}
        <div class="col-6 col-md-3">
            <div class="dashboard-card h-100 d-flex align-items-center gap-3 py-3 {{ !request('status') ? 'border border-primary border-opacity-25' : '' }}"
                style="cursor:pointer" id="statAll">
                <div class="stat-icon flex-shrink-0"><i class="fas fa-boxes"></i></div>
                <div class="min-width-0">
                    <div class="fw-bold fs-4 text-white">{{ number_format($stats['total_skus']) }}</div>
                    <div class="text-muted small">Total SKU Produk</div>
                </div>
            </div>
        </div>

        {{-- Total Unit --}}
        <div class="col-6 col-md-3">
            <div class="dashboard-card h-100 d-flex align-items-center gap-3 py-3" style="cursor:pointer" id="statUnits">
                <div class="stat-icon flex-shrink-0" style="background:rgba(139,92,246,.15);color:#a78bfa"><i
                        class="fas fa-warehouse"></i></div>
                <div class="min-width-0">
                    <div class="fw-bold fs-4 text-white">{{ number_format($stats['total_stock']) }}</div>
                    <div class="text-muted small">Total Unit Stok</div>
                </div>
            </div>
        </div>

        {{-- Stok Menipis --}}
        <div class="col-6 col-md-3">
            <div class="dashboard-card h-100 d-flex align-items-center gap-3 py-3 {{ request('status') === 'low' ? 'border border-warning border-opacity-25' : '' }}"
                style="cursor:pointer" id="statLow">
                <div class="stat-icon flex-shrink-0" style="background:rgba(245,158,11,.15);color:#fbbf24"><i
                        class="fas fa-exclamation-triangle"></i></div>
                <div class="min-width-0">
                    <div class="fw-bold fs-4 text-warning">{{ number_format($stats['low_stock']) }}</div>
                    <div class="text-muted small">Stok Menipis</div>
                </div>
            </div>
        </div>

        {{-- Stok Habis --}}
        <div class="col-6 col-md-3">
            <div class="dashboard-card h-100 d-flex align-items-center gap-3 py-3 {{ request('status') === 'empty' ? 'border border-danger border-opacity-25' : '' }}"
                style="cursor:pointer" id="statEmpty">
                <div class="stat-icon flex-shrink-0" style="background:rgba(239,68,68,.15);color:#f87171"><i
                        class="fas fa-times-circle"></i></div>
                <div class="min-width-0">
                    <div class="fw-bold fs-4 text-danger">{{ number_format($stats['out_of_stock']) }}</div>
                    <div class="text-muted small">Stok Habis</div>
                </div>
            </div>
        </div>

    </div>

    {{-- ── Filter Card ─────────────────────────────────────────────── --}}
    <div class="dashboard-card mb-3 py-3">
        <form action="{{ route('inventory.index') }}" method="GET" id="inventoryFilterForm">
            @if (request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}" id="hiddenStatus">
            @endif

            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-8 col-lg-9">
                    <label class="form-label form-label-sm fw-semibold mb-1 text-muted">
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

            {{-- Active Filter Badges --}}
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
                                [
                                    'low' =>
                                        'bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25',
                                    'empty' =>
                                        'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25',
                                    'safe' =>
                                        'bg-success bg-opacity-10 text-success border border-success border-opacity-25',
                                ][request('status')] ?? 'bg-secondary';
                        @endphp
                        <span
                            class="badge {{ $statusClass }} d-inline-flex align-items-center gap-1 py-1 px-2 rounded-pill fw-medium">
                            Status: {{ $statusLabel }}
                            <a href="{{ route('inventory.index', request()->except('status')) }}"
                                class="text-reset text-decoration-none ms-1 opacity-75">
                                <i class="fas fa-times-circle"></i>
                            </a>
                        </span>
                    @endif
                    @if (request('search'))
                        <span
                            class="badge bg-secondary bg-opacity-15 text-secondary border border-secondary border-opacity-25 d-inline-flex align-items-center gap-1 py-1 px-2 rounded-pill fw-medium">
                            Kata Kunci: "{{ request('search') }}"
                            <a href="{{ route('inventory.index', request()->except('search')) }}"
                                class="text-reset text-decoration-none ms-1 opacity-75">
                                <i class="fas fa-times-circle"></i>
                            </a>
                        </span>
                    @endif
                </div>
            @endif
        </form>
    </div>

    {{-- ── Main Table Card ──────────────────────────────────────────── --}}
    <div class="dashboard-card">
        <div class="card-header-line d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold d-flex align-items-center gap-2">
                    <i class="fas fa-boxes text-primary"></i> Daftar Persediaan Barang
                </h5>
                <p class="text-muted mb-0 mt-1 small">
                    Menampilkan {{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }}
                    dari {{ $products->total() }} produk
                </p>
            </div>
            <a href="{{ route('stock_opnames.create') }}"
                class="btn btn-success btn-sm fw-semibold d-none d-md-inline-flex align-items-center gap-1">
                <i class="fas fa-clipboard-check"></i> Mulai Stock Opname
            </a>
        </div>

        <div class="table-responsive rounded border border-secondary border-opacity-10 mt-3">
            <table class="table table-sm table-bordered table-premium-dark align-middle mb-0">
                <thead>
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
                            {{-- Detail Produk --}}
                            <td class="ps-3 py-2">
                                <div class="d-flex align-items-center gap-3">
                                    {{-- Thumbnail --}}
                                    @if ($product->image_url)
                                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                                            class="rounded border border-secondary border-opacity-25"
                                            style="width:46px;height:46px;object-fit:cover;flex-shrink:0">
                                    @else
                                        <div class="rounded border border-secondary border-opacity-25 d-flex align-items-center justify-content-center text-muted"
                                            style="width:46px;height:46px;flex-shrink:0;background:rgba(255,255,255,0.03)">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    @endif

                                    {{-- Info --}}
                                    <div class="min-width-0">
                                        {{-- SKU --}}
                                        <div class="d-flex align-items-center gap-1 flex-wrap mb-1">
                                            <code class="text-muted small px-1 rounded"
                                                style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.08);font-size:.75rem">
                                                {{ $product->sku }}
                                            </code>
                                            @if ($product->sku_induk)
                                                <code class="text-muted" style="font-size:.7rem;opacity:.7">
                                                    Induk: {{ $product->sku_induk }}
                                                </code>
                                            @endif
                                        </div>
                                        {{-- Name --}}
                                        <div class="fw-semibold text-white small">{{ $product->name }}</div>
                                        {{-- Attribute Badges --}}
                                        <div class="d-flex flex-wrap gap-1 mt-1">
                                            @if ($product->category)
                                                <span class="badge border"
                                                    style="font-size:.68rem;background:rgba(255,255,255,0.07);color:#cbd5e1;border-color:rgba(255,255,255,0.12)!important">
                                                    <i
                                                        class="fas fa-folder me-1 opacity-75"></i>{{ $product->category->name }}
                                                </span>
                                            @endif
                                            @if ($product->brand)
                                                <span class="badge border"
                                                    style="font-size:.68rem;background:rgba(255,255,255,0.07);color:#cbd5e1;border-color:rgba(255,255,255,0.12)!important">
                                                    <i class="fas fa-tag me-1 opacity-75"></i>{{ $product->brand->name }}
                                                </span>
                                            @endif
                                            @if ($product->sub_kategori)
                                                <span class="badge border"
                                                    style="font-size:.68rem;background:rgba(255,255,255,0.05);color:#94a3b8;border-color:rgba(255,255,255,0.08)!important">
                                                    Sub: {{ $product->sub_kategori }}
                                                </span>
                                            @endif
                                            @if ($product->ukuran)
                                                <span class="badge border"
                                                    style="font-size:.68rem;background:rgba(6,182,212,0.12);color:#67e8f9;border-color:rgba(6,182,212,0.25)!important">
                                                    {{ $product->ukuran }}
                                                </span>
                                            @endif
                                            @if ($product->warna)
                                                <span class="badge border"
                                                    style="font-size:.68rem;background:rgba(245,158,11,0.12);color:#fcd34d;border-color:rgba(245,158,11,0.25)!important">
                                                    {{ $product->warna }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- HPP --}}
                            <td class="text-end text-secondary small fw-semibold font-monospace">
                                Rp {{ number_format($product->cost_price, 0, ',', '.') }}
                            </td>

                            {{-- Harga Jual --}}
                            <td class="text-end text-white fw-bold small font-monospace">
                                Rp {{ number_format($product->price, 0, ',', '.') }}
                            </td>

                            {{-- Status Stok --}}
                            <td class="text-center">
                                @if ($product->stock <= 0)
                                    <span
                                        class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1 rounded-pill fw-semibold">
                                        <i class="fas fa-times-circle me-1"></i>Habis (0)
                                    </span>
                                @elseif($product->stock <= $product->min_stock)
                                    <span
                                        class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1 rounded-pill fw-semibold">
                                        <i class="fas fa-exclamation-triangle me-1"></i>Menipis
                                        ({{ number_format($product->stock) }})
                                    </span>
                                @else
                                    <span
                                        class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 rounded-pill fw-semibold">
                                        <i class="fas fa-check-circle me-1"></i>Aman
                                        ({{ number_format($product->stock) }})
                                    </span>
                                @endif
                                <div class="text-muted mt-1" style="font-size:.7rem">
                                    Min. Stok: {{ number_format($product->min_stock) }}
                                </div>
                            </td>

                            {{-- Aksi --}}
                            <td class="text-center pe-3">
                                <a href="{{ route('inventory.ledger', $product->id) }}"
                                    class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1">
                                    <i class="fas fa-clipboard-list"></i> Kartu Stok
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-5">
                                <i class="fas fa-boxes fa-2x mb-3 d-block opacity-25"></i>
                                <div class="fw-semibold text-light mb-1">Tidak Ada Produk Ditemukan</div>
                                <div class="small text-muted">Sesuaikan filter atau tambah produk baru.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($products->hasPages())
            <div class="d-flex justify-content-between align-items-center mt-3">
                <span class="text-muted" style="font-size:.75rem">
                    Menampilkan {{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }}
                    dari {{ $products->total() }} produk
                </span>
                {{ $products->withQueryString()->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>

@endsection

@push('scripts')
    <script>
        $(function() {

            // ── Stat card click → filter by status ─────────────────────────
            const baseUrl = '{{ route('inventory.index') }}';
            const search = '{{ request('search') }}';

            function goFilter(status) {
                let url = baseUrl;
                const params = new URLSearchParams();
                if (search) params.set('search', search);
                if (status) params.set('status', status);
                const qs = params.toString();
                window.location.href = url + (qs ? '?' + qs : '');
            }

            $('#statAll').on('click', () => goFilter(''));
            $('#statUnits').on('click', () => goFilter(''));
            $('#statLow').on('click', () => goFilter('low'));
            $('#statEmpty').on('click', () => goFilter('empty'));

            // ── Auto-submit search with debounce ────────────────────────────
            let timer;
            $('#inventorySearch').on('input', function() {
                clearTimeout(timer);
                timer = setTimeout(() => $('#inventoryFilterForm').submit(), 500);
            });
        });
    </script>
@endpush
