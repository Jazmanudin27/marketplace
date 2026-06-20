@extends('layouts.app')
@section('title', 'Manajemen Inventory')
@section('page-title', 'Inventory & Kartu Stok')

@push('styles')
<style>
    .table-hover tbody tr:hover {
        background-color: rgba(255, 255, 255, 0.03) !important;
    }
    .badge {
        font-weight: 600;
        letter-spacing: 0.02em;
    }
    .mono {
        font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
    }
    .stat-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.08);
    }
    .input-group-text {
        border-color: rgba(255, 255, 255, 0.1) !important;
    }
    .form-control-custom {
        background: rgba(255, 255, 255, 0.03) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        color: var(--text-primary) !important;
        padding-left: 0.75rem;
    }
    .form-control-custom:focus {
        background: rgba(255, 255, 255, 0.05) !important;
        box-shadow: 0 0 0 3px rgba(108, 99, 255, 0.2) !important;
        border-color: var(--primary) !important;
    }
    .btn-action {
        padding: 5px 12px;
        font-size: 0.85rem;
        border-radius: 6px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
</style>
@endpush

@section('content')
    {{-- Statistics Row --}}
    <div class="stats-grid mb-4">
        {{-- Card 1: Total SKU --}}
        <div class="stat-card stat-primary {{ !request('status') ? 'border border-primary' : '' }}" style="cursor: pointer;" onclick="window.location.href='{{ route('inventory.index', request()->except(['status', 'page'])) }}'">
            <div class="stat-icon"><i class="fas fa-boxes"></i></div>
            <div>
                <div class="stat-value">{{ number_format($stats['total_skus']) }}</div>
                <div class="stat-label">Total SKU Produk</div>
            </div>
        </div>

        {{-- Card 2: Total Unit Stok --}}
        <div class="stat-card stat-purple" style="cursor: pointer;" onclick="window.location.href='{{ route('inventory.index', request()->except(['status', 'page'])) }}'">
            <div class="stat-icon"><i class="fas fa-warehouse"></i></div>
            <div>
                <div class="stat-value">{{ number_format($stats['total_stock']) }}</div>
                <div class="stat-label">Total Unit Stok</div>
            </div>
        </div>

        {{-- Card 3: Stok Menipis --}}
        <div class="stat-card stat-warning {{ request('status') === 'low' ? 'border border-warning' : '' }}" style="cursor: pointer;" onclick="window.location.href='{{ route('inventory.index', array_merge(request()->except('page'), ['status' => 'low'])) }}'">
            <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div>
                <div class="stat-value text-warning">{{ number_format($stats['low_stock']) }}</div>
                <div class="stat-label">Stok Menipis</div>
            </div>
        </div>

        {{-- Card 4: Stok Habis --}}
        <div class="stat-card {{ request('status') === 'empty' ? 'border border-danger' : '' }}" style="background: linear-gradient(135deg, rgba(239,68,68,0.15) 0%, rgba(220,38,38,0.08) 100%); border: 1px solid rgba(239,68,68,0.25); cursor: pointer;" onclick="window.location.href='{{ route('inventory.index', array_merge(request()->except('page'), ['status' => 'empty'])) }}'">
            <div class="stat-icon" style="background: rgba(239, 68, 68, 0.15); color: var(--danger);"><i class="fas fa-times-circle"></i></div>
            <div>
                <div class="stat-value text-danger">{{ number_format($stats['out_of_stock']) }}</div>
                <div class="stat-label">Stok Habis</div>
            </div>
        </div>
    </div>

    {{-- Filter & Search Card --}}
    <div class="card shadow-sm border-0 mb-4" style="background: var(--bg-card); border-radius: var(--radius); border: 1px solid var(--border);">
        <div class="card-body p-3">
            <form action="{{ route('inventory.index') }}" method="GET" class="row g-3 align-items-center">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                
                <div class="col-md-6 col-lg-7">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent text-muted" style="border-right: none;">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" name="search" placeholder="Cari SKU, Nama Produk, Kategori, atau SKU Induk..."
                            value="{{ request('search') }}" class="form-control form-control-custom ps-0" style="border-left: none;">
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">
                            Cari
                        </button>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-5 d-flex gap-2 justify-content-md-end align-items-center">
                    @if(request('search') || request('status'))
                        <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary px-3 fw-semibold">
                            <i class="fas fa-undo me-1"></i> Reset Filter
                        </a>
                    @endif
                    <a href="{{ route('stock_opnames.create') }}" class="btn btn-success shadow-sm px-3 fw-semibold">
                        <i class="fas fa-clipboard-check me-1"></i> Mulai Stock Opname
                    </a>
                </div>
            </form>
            
            {{-- Active Filter Badges --}}
            @if(request('search') || request('status'))
                <div class="d-flex flex-wrap gap-2 mt-3 align-items-center">
                    <span class="text-muted small"><i class="fas fa-filter me-1"></i>Filter Aktif:</span>
                    @if(request('status'))
                        @php
                            $statusLabel = [
                                'low' => 'Stok Menipis',
                                'empty' => 'Stok Habis',
                                'safe' => 'Stok Aman'
                            ][request('status')] ?? request('status');
                            
                            $statusBadgeClass = [
                                'low' => 'bg-warning-subtle text-warning border border-warning-subtle',
                                'empty' => 'bg-danger-subtle text-danger border border-danger-subtle',
                                'safe' => 'bg-success-subtle text-success border border-success-subtle'
                            ][request('status')] ?? 'bg-secondary';
                        @endphp
                        <span class="badge {{ $statusBadgeClass }} d-flex align-items-center gap-1.5 py-1.5 px-3 rounded-pill">
                            Status: {{ $statusLabel }}
                            <a href="{{ route('inventory.index', request()->except('status')) }}" class="text-reset text-decoration-none ms-1 opacity-75 hover-opacity-100">
                                <i class="fas fa-times-circle"></i>
                            </a>
                        </span>
                    @endif
                    @if(request('search'))
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle d-flex align-items-center gap-1.5 py-1.5 px-3 rounded-pill">
                            Kata Kunci: "{{ request('search') }}"
                            <a href="{{ route('inventory.index', request()->except('search')) }}" class="text-reset text-decoration-none ms-1 opacity-75 hover-opacity-100">
                                <i class="fas fa-times-circle"></i>
                            </a>
                        </span>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- Main Inventory Table Card --}}
    <div class="card shadow-sm border-0" style="background: var(--bg-card); border-radius: var(--radius); border: 1px solid var(--border);">
        <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-3 border-bottom" style="border-color: var(--border) !important;">
            <h5 class="card-title mb-0 fw-bold text-primary">
                <i class="fas fa-boxes me-2"></i>Daftar Persediaan Barang
            </h5>
            <span class="text-muted small fw-medium">Menampilkan {{ $products->firstItem() ?? 0 }}-{{ $products->lastItem() ?? 0 }} dari {{ $products->total() }} produk</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="background: transparent;">
                <thead style="background: rgba(16, 185, 129, 0.08); border-bottom: 2px solid rgba(16, 185, 129, 0.25);">
                    <tr>
                        <th class="ps-4 border-0" style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.03em; color: #34d399; font-weight: 700;">Detail Produk</th>
                        <th class="border-0 text-end" style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.03em; color: #34d399; font-weight: 700; width: 15%;">Harga Modal (HPP)</th>
                        <th class="border-0 text-end" style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.03em; color: #34d399; font-weight: 700; width: 15%;">Harga Jual</th>
                        <th class="border-0 text-center" style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.03em; color: #34d399; font-weight: 700; width: 18%;">Stok Tersedia</th>
                        <th class="border-0 text-center pe-4" style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.03em; color: #34d399; font-weight: 700; width: 15%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr style="border-bottom: 1px solid var(--border); transition: background-color 0.2s;">
                            {{-- Product Details with Thumbnail image and badges --}}
                            <td class="ps-4 py-3">
                                <div class="d-flex align-items-center gap-3">
                                    {{-- Image Thumbnail --}}
                                    @if ($product->image_url)
                                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="rounded shadow-sm border border-secondary"
                                             style="width: 52px; height: 52px; object-fit: cover; flex-shrink: 0; background-color: rgba(255, 255, 255, 0.05);">
                                    @else
                                        <div class="rounded border border-secondary d-flex align-items-center justify-content-center bg-dark text-muted shadow-sm"
                                             style="width: 52px; height: 52px; flex-shrink: 0; background-color: rgba(255, 255, 255, 0.02);">
                                            <i class="fas fa-image fa-lg"></i>
                                        </div>
                                    @endif
                                    
                                    {{-- Info and Badges --}}
                                    <div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="mono text-muted small fw-semibold bg-dark px-2 py-0.5 rounded border" style="border-color: var(--border) !important;">{{ $product->sku }}</span>
                                            @if ($product->sku_induk)
                                                <span class="mono text-muted small px-2 py-0.5 rounded bg-black" style="font-size: 0.75rem; opacity: 0.8;">
                                                    Induk: {{ $product->sku_induk }}
                                                </span>
                                            @endif
                                        </div>
                                        <div class="fw-bold text-white fs-6 mt-1">{{ $product->name }}</div>
                                        <div class="d-flex flex-wrap gap-1 mt-1.5">
                                            @if ($product->category)
                                                <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.72rem; padding: 3px 8px;">
                                                    <i class="fas fa-folder me-1"></i>{{ $product->category->name }}
                                                </span>
                                            @endif
                                            @if ($product->brand)
                                                <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.72rem; padding: 3px 8px;">
                                                    <i class="fas fa-tag me-1"></i>{{ $product->brand->name }}
                                                </span>
                                            @endif
                                            @if ($product->sub_kategori)
                                                <span class="badge bg-dark text-muted" style="font-size: 0.72rem; padding: 3px 8px; border: 1px solid var(--border);">
                                                    Sub: {{ $product->sub_kategori }}
                                                </span>
                                            @endif
                                            @if ($product->ukuran)
                                                <span class="badge bg-info-subtle text-info" style="font-size: 0.72rem; padding: 3px 8px;">
                                                    Ukuran: {{ $product->ukuran }}
                                                </span>
                                            @endif
                                            @if ($product->warna)
                                                <span class="badge bg-warning-subtle text-warning" style="font-size: 0.72rem; padding: 3px 8px;">
                                                    Warna: {{ $product->warna }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            
                            {{-- Cost Price (HPP) --}}
                            <td class="mono text-end fw-semibold text-secondary">
                                Rp {{ number_format($product->cost_price, 0, ',', '.') }}
                            </td>
                            
                            {{-- Sell Price --}}
                            <td class="mono text-end fw-bold text-white">
                                Rp {{ number_format($product->price, 0, ',', '.') }}
                            </td>
                            
                            {{-- Stock Status with custom pills --}}
                            <td class="text-center">
                                @if ($product->stock <= 0)
                                    <span class="badge bg-danger-subtle text-danger py-1.5 px-3 rounded-pill fw-bold border border-danger-subtle" style="font-size: 0.85rem;">
                                        <i class="fas fa-times-circle me-1"></i>Habis (0)
                                    </span>
                                @elseif ($product->stock <= $product->min_stock)
                                    <span class="badge bg-warning-subtle text-warning py-1.5 px-3 rounded-pill fw-bold border border-warning-subtle" style="font-size: 0.85rem;">
                                        <i class="fas fa-exclamation-triangle me-1"></i>Menipis ({{ number_format($product->stock) }})
                                    </span>
                                @else
                                    <span class="badge bg-success-subtle text-success py-1.5 px-3 rounded-pill fw-bold border border-success-subtle" style="font-size: 0.85rem;">
                                        <i class="fas fa-check-circle me-1"></i>Aman ({{ number_format($product->stock) }})
                                    </span>
                                @endif
                                <div class="text-muted small mt-1" style="font-size: 0.75rem;">Min. Stok: {{ number_format($product->min_stock) }}</div>
                            </td>
                            
                            {{-- Actions --}}
                            <td class="text-center pe-4">
                                <a href="{{ route('inventory.ledger', $product->id) }}"
                                    class="btn btn-outline-primary btn-action">
                                    <i class="fas fa-clipboard-list"></i> Kartu Stok
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <div class="d-flex flex-column align-items-center justify-content-center">
                                    <i class="fas fa-boxes text-muted opacity-25 fa-3x mb-3"></i>
                                    <h6 class="fw-semibold">Tidak Ada Produk Ditemukan</h6>
                                    <p class="text-muted small mb-0">Silakan sesuaikan filter pencarian Anda atau tambah produk baru.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($products->hasPages())
            <div class="card-footer bg-transparent border-top py-3 d-flex justify-content-center" style="border-color: var(--border) !important;">
                {{ $products->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
@endsection
