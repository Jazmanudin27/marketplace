@extends('layouts.mobile')

@section('title', 'Gudang Dashboard')
@section('header-title', 'Gudang Dashboard')

@section('styles')
<style>
    /* Stat Cards */
    .stat-card {
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0.01) 100%);
        border: 1px solid var(--border-card);
        border-radius: 16px;
        padding: 15px;
        transition: all 0.3s ease;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        border-color: rgba(129, 140, 248, 0.3);
    }
    .stat-icon {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }

    /* Product Row Card */
    .product-row-card {
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid var(--border-card);
        border-radius: 16px;
        padding: 16px;
        margin-bottom: 12px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .product-row-card:hover {
        background: rgba(255, 255, 255, 0.04);
        border-color: rgba(255, 255, 255, 0.15);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
    }
    .product-img {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        object-fit: cover;
        border: 1px solid rgba(255, 255, 255, 0.1);
        background: rgba(255, 255, 255, 0.05);
    }
    .product-avatar-placeholder {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.1rem;
        background: linear-gradient(135deg, #4f46e5, #312e81);
        color: white;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    /* Glowing Badges */
    .badge-glow-success {
        background: rgba(16, 185, 129, 0.12);
        color: #34d399;
        border: 1px solid rgba(16, 185, 129, 0.25);
        box-shadow: 0 0 10px rgba(16, 185, 129, 0.15);
        font-weight: 600;
        font-size: 0.75rem;
        padding: 5px 10px;
        border-radius: 20px;
        display: inline-flex;
        align-items: center;
    }
    .badge-glow-danger {
        background: rgba(239, 68, 68, 0.12);
        color: #f87171;
        border: 1px solid rgba(239, 68, 68, 0.25);
        box-shadow: 0 0 10px rgba(239, 68, 68, 0.15);
        font-weight: 600;
        font-size: 0.75rem;
        padding: 5px 10px;
        border-radius: 20px;
        display: inline-flex;
        align-items: center;
    }

    /* Action Area (Expansion Transition) */
    .action-area {
        max-height: 0;
        opacity: 0;
        overflow: hidden;
        transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s ease, padding 0.3s ease, border-color 0.3s ease;
        border: 0 solid transparent;
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.01);
        padding: 0 16px;
    }
    .action-area.show {
        max-height: 450px;
        opacity: 1;
        padding: 16px;
        border: 1px solid var(--border-card);
        background: rgba(255, 255, 255, 0.025);
        margin-top: 15px;
    }

    /* Segmented Tabs */
    .segmented-control {
        background: rgba(0, 0, 0, 0.2);
        border-radius: 12px;
        padding: 4px;
        border: 1px solid var(--border-card);
    }
    .segmented-control .nav-link {
        border-radius: 8px;
        color: var(--text-muted);
        font-weight: 500;
        transition: all 0.2s ease;
        border: none !important;
        background: transparent !important;
    }
    .segmented-control .nav-link.active {
        background: rgba(79, 70, 229, 0.2) !important;
        color: #c7d2fe !important;
        border: 1px solid rgba(79, 70, 229, 0.4) !important;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.15);
    }
    .segmented-control .nav-link:not(.active):hover {
        color: var(--text-main);
        background: rgba(255, 255, 255, 0.05);
    }

    /* Input Icon Wrapper */
    .input-icon-wrapper {
        position: relative;
    }
    .input-icon-wrapper i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
        font-size: 0.8rem;
        pointer-events: none;
    }
    .input-icon-wrapper .custom-input {
        padding-left: 32px !important;
    }

    /* Queue & History Items */
    .queue-item {
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 16px;
        padding: 14px 16px;
        transition: all 0.2s ease;
    }
    .queue-item:hover {
        background: rgba(255, 255, 255, 0.04);
        border-color: rgba(255, 255, 255, 0.1);
    }
    .queue-icon {
        width: 38px;
        height: 38px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
    }

    /* Pulse animation */
    @keyframes pulse-glowing {
        0% { box-shadow: 0 0 0 0 rgba(14, 165, 233, 0.3); }
        70% { box-shadow: 0 0 0 8px rgba(14, 165, 233, 0); }
        100% { box-shadow: 0 0 0 0 rgba(14, 165, 233, 0); }
    }
    .pulse-producing {
        animation: pulse-glowing 2s infinite;
    }

    /* Micro hover animation for icons */
    .fa-spin-hover:hover {
        animation: fa-spin 2s infinite linear;
    }

    /* Custom Paginator override */
    .pagination {
        --bs-pagination-bg: rgba(255, 255, 255, 0.02);
        --bs-pagination-border-color: var(--border-card);
        --bs-pagination-color: var(--text-muted);
        --bs-pagination-hover-color: var(--text-main);
        --bs-pagination-hover-bg: rgba(255, 255, 255, 0.08);
        --bs-pagination-hover-border-color: rgba(255, 255, 255, 0.15);
        --bs-pagination-active-bg: var(--primary);
        --bs-pagination-active-border-color: var(--primary);
        --bs-pagination-disabled-bg: transparent;
        --bs-pagination-disabled-border-color: rgba(255, 255, 255, 0.04);
        --bs-pagination-disabled-color: rgba(255, 255, 255, 0.2);
    }
    .page-link {
        border-radius: 8px;
        margin: 0 2px;
    }
</style>
@endsection

@section('content')
<!-- Search Form -->
<form action="{{ route('mobile.gudang') }}" method="GET" class="mb-4">
    <div class="position-relative">
        <span class="position-absolute" style="left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-muted); z-index: 10;">
            <i class="fas fa-search"></i>
        </span>
        <input type="text" name="search" class="form-control custom-input w-100" style="padding-left: 45px; padding-right: 45px; border-radius: 16px;" placeholder="Cari SKU atau nama produk..." value="{{ $search }}">
        @if($search)
            <a href="{{ route('mobile.gudang') }}" class="position-absolute d-flex align-items-center justify-content-center" style="right: 16px; top: 50%; transform: translateY(-50%); color: var(--text-muted); z-index: 10; width: 24px; height: 24px;">
                <i class="fas fa-times-circle" style="font-size: 1.1rem;"></i>
            </a>
        @endif
    </div>
</form>

<!-- Quick Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-6">
        <div class="stat-card d-flex align-items-center gap-3">
            <div class="stat-icon" style="background: rgba(14, 165, 233, 0.15); color: var(--accent-blue);">
                <i class="fas fa-spinner fa-spin-hover"></i>
            </div>
            <div>
                <div style="font-size: 0.72rem; color: var(--text-muted);">Antrean Aktif</div>
                <div style="font-size: 1.05rem; font-weight: 700; color: white;">
                    {{ count($activeProductionRequests) }} <span style="font-size:0.75rem; font-weight:normal; color:var(--text-muted)">Order</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6">
        <div class="stat-card d-flex align-items-center gap-3">
            <div class="stat-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--accent-green);">
                <i class="fas fa-check-circle"></i>
            </div>
            <div>
                <div style="font-size: 0.72rem; color: var(--text-muted);">Riwayat Selesai</div>
                <div style="font-size: 1.05rem; font-weight: 700; color: white;">
                    {{ count($productionHistory) }} <span style="font-size:0.75rem; font-weight:normal; color:var(--text-muted)">Item</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Product List / Stock Management -->
<div class="glass-card p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="m-0" style="font-size: 1rem; font-weight: 600; color:#818cf8;">
            <i class="fas fa-boxes me-1.5"></i> Daftar & Penyesuaian Stok
        </h4>
        <span class="text-muted" style="font-size: 0.75rem;">Total: {{ $products->total() }} SKU</span>
    </div>
    
    <div class="d-flex flex-column gap-1">
        @forelse($products as $product)
            <div class="product-row-card">
                <div class="d-flex justify-content-between align-items-center" onclick="toggleProductActions({{ $product->id }})" style="cursor: pointer;">
                    <div class="d-flex align-items-center gap-3">
                        @if($product->image_url)
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="product-img">
                        @else
                            <div class="product-avatar-placeholder">
                                {{ strtoupper(substr($product->name, 0, 2)) }}
                            </div>
                        @endif
                        <div>
                            <div style="font-size: 0.95rem; font-weight: 600; color: white;">{{ $product->name }}</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);" class="mt-0.5">
                                SKU: <span class="mono" style="color: #a5b4fc;">{{ $product->sku }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="text-end">
                        @if($product->stock <= $product->min_stock)
                            <span class="badge-glow-danger">
                                <i class="fas fa-exclamation-triangle me-1"></i>{{ $product->stock }} {{ $product->unit ?: 'pcs' }}
                            </span>
                        @else
                            <span class="badge-glow-success">
                                <i class="fas fa-check-circle me-1"></i>{{ $product->stock }} {{ $product->unit ?: 'pcs' }}
                            </span>
                        @endif
                        <div style="font-size: 0.65rem; color: var(--text-muted);" class="mt-1.5">
                            Min: {{ $product->min_stock }}
                        </div>
                    </div>
                </div>

                <!-- Accordion Actions (hidden by default) -->
                <div id="actions-{{ $product->id }}" class="action-area">
                    <!-- Segmented Control Tabs -->
                    <ul class="nav nav-pills mb-3 segmented-control d-flex gap-1" id="pills-tab-{{ $product->id }}" role="tablist">
                        <li class="nav-item flex-fill" role="presentation">
                            <button class="nav-link active btn-sm w-100 py-2" style="font-size:0.75rem;" id="pills-adjust-tab-{{ $product->id }}" data-bs-toggle="pill" data-bs-target="#pills-adjust-{{ $product->id }}" type="button" role="tab" aria-controls="pills-adjust-{{ $product->id }}" aria-selected="true">
                                <i class="fas fa-sliders-h me-1.5"></i>Sesuaikan Stok
                            </button>
                        </li>
                        <li class="nav-item flex-fill" role="presentation">
                            <button class="nav-link btn-sm w-100 py-2" style="font-size:0.75rem;" id="pills-produce-tab-{{ $product->id }}" data-bs-toggle="pill" data-bs-target="#pills-produce-{{ $product->id }}" type="button" role="tab" aria-controls="pills-produce-{{ $product->id }}" aria-selected="false">
                                <i class="fas fa-hammer me-1.5"></i>Pesan Produksi
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="pills-tabContent-{{ $product->id }}">
                        <!-- Adjust Stock Form -->
                        <div class="tab-pane fade show active" id="pills-adjust-{{ $product->id }}" role="tabpanel" aria-labelledby="pills-adjust-tab-{{ $product->id }}">
                            <form action="{{ route('mobile.gudang.adjust_stock', $product->id) }}" method="POST">
                                @csrf
                                <div class="row g-2">
                                    <div class="col-4">
                                        <div class="input-icon-wrapper">
                                            <i class="fas fa-exchange-alt"></i>
                                            <select name="type" class="form-select custom-input py-2" style="font-size:0.8rem;" required>
                                                <option value="in">Tambah</option>
                                                <option value="out">Kurang</option>
                                                <option value="adj">Setel</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="input-icon-wrapper">
                                            <i class="fas fa-sort-amount-up"></i>
                                            <input type="number" name="quantity" class="form-control custom-input py-2" style="font-size:0.8rem;" placeholder="Jumlah" required>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="input-icon-wrapper">
                                            <i class="fas fa-tag"></i>
                                            <input type="text" name="reference" class="form-control custom-input py-2" style="font-size:0.8rem;" placeholder="Keterangan" required>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-premium w-100 btn-sm mt-3 py-2.5" style="font-size: 0.8rem; border-radius:12px;">
                                    <i class="fas fa-save me-1.5"></i>Simpan Penyesuaian
                                </button>
                            </form>
                        </div>

                        <!-- Request Production Form -->
                        <div class="tab-pane fade" id="pills-produce-{{ $product->id }}" role="tabpanel" aria-labelledby="pills-produce-tab-{{ $product->id }}">
                            <form action="{{ route('mobile.gudang.request_production') }}" method="POST">
                                @csrf
                                <input type="hidden" name="master_product_id" value="{{ $product->id }}">
                                <div class="mb-3">
                                    <label class="form-label text-muted" style="font-size: 0.75rem; font-weight: 500;">Jumlah yang Dipesan ke Produksi</label>
                                    <div class="input-icon-wrapper">
                                        <i class="fas fa-boxes"></i>
                                        <input type="number" name="quantity" class="form-control custom-input" placeholder="Masukkan Qty Produksi" min="1" required>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-premium w-100 btn-sm py-2.5" style="font-size: 0.8rem; background: linear-gradient(135deg, #0ea5e9, #0284c7); box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3); border-radius:12px;">
                                    <i class="fas fa-paper-plane me-1.5"></i>Kirim ke Produksi
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-5 text-muted" style="font-size: 0.85rem;">
                <i class="fas fa-search d-block mb-2 text-muted opacity-50" style="font-size: 2rem;"></i>
                Tidak ada produk ditemukan.
            </div>
        @endforelse
    </div>
    
    <div class="mt-4 d-flex justify-content-center" style="font-size: 0.8rem;">
        {{ $products->links() }}
    </div>
</div>

<!-- Active Production Requests -->
<div class="glass-card p-4 mb-4">
    <h4 class="mb-3" style="font-size: 1rem; font-weight: 600; color:#818cf8;">
        <i class="fas fa-spinner fa-spin-hover me-1.5"></i> Antrean Permintaan Produksi
    </h4>
    
    <div class="d-flex flex-column gap-3">
        @forelse($activeProductionRequests as $req)
            <div class="d-flex align-items-center gap-3 queue-item">
                <div class="queue-icon {{ $req->status === 'producing' ? 'pulse-producing' : '' }}" style="background: {{ $req->status === 'producing' ? 'rgba(14, 165, 233, 0.15)' : 'rgba(245, 158, 11, 0.15)' }}">
                    @if($req->status === 'producing')
                        <i class="fas fa-cog fa-spin" style="color: var(--accent-blue);"></i>
                    @else
                        <i class="fas fa-hourglass-half" style="color: var(--accent-yellow);"></i>
                    @endif
                </div>
                <div class="flex-grow-1">
                    <div style="font-size: 0.85rem; font-weight: 600; color: white;">{{ $req->masterProduct->name }}</div>
                    <div style="font-size: 0.7rem; color: var(--text-muted);" class="mt-0.5">
                        Dipesan: <span style="color: #a5b4fc; font-weight:600;">{{ $req->quantity }} pcs</span>
                        <span class="mx-1.5">•</span>
                        <span>{{ $req->created_at->format('d/m H:i') }}</span>
                    </div>
                </div>
                <div>
                    <span class="badge status-badge status-{{ $req->status }}">
                        {{ $req->status === 'pending' ? 'Menunggu' : 'Diproses' }}
                    </span>
                </div>
            </div>
        @empty
            <div class="text-center py-4 text-muted" style="font-size: 0.8rem; background: rgba(255,255,255,0.01); border-radius:12px; border: 1px dashed rgba(255,255,255,0.05);">
                <i class="fas fa-clipboard-list d-block mb-2 text-muted opacity-50" style="font-size: 1.5rem;"></i>
                Tidak ada permintaan produksi aktif.
            </div>
        @endforelse
    </div>
</div>

<!-- Production History -->
<div class="glass-card p-4">
    <h4 class="mb-3" style="font-size: 1rem; font-weight: 600; color:#818cf8;">
        <i class="fas fa-history me-1.5"></i> Riwayat Produksi
    </h4>
    
    <div class="d-flex flex-column gap-3">
        @forelse($productionHistory as $hist)
            <div class="d-flex align-items-center gap-3 queue-item">
                <div class="queue-icon" style="background: {{ $hist->status === 'completed' ? 'rgba(16, 185, 129, 0.15)' : 'rgba(239, 68, 68, 0.15)' }}">
                    @if($hist->status === 'completed')
                        <i class="fas fa-check-circle" style="color: var(--accent-green);"></i>
                    @else
                        <i class="fas fa-times-circle" style="color: var(--accent-red);"></i>
                    @endif
                </div>
                <div class="flex-grow-1">
                    <div style="font-size: 0.85rem; font-weight: 600; color: white;">{{ $hist->masterProduct->name }}</div>
                    <div style="font-size: 0.7rem; color: var(--text-muted);" class="mt-0.5">
                        Jumlah: <span style="color: #a5b4fc; font-weight:600;">{{ $hist->quantity }} pcs</span>
                        <span class="mx-1.5">•</span>
                        <span>{{ $hist->updated_at->format('d/m H:i') }}</span>
                    </div>
                </div>
                <div>
                    <span class="badge status-badge status-{{ $hist->status }}">
                        {{ $hist->status === 'completed' ? 'Selesai' : 'Batal' }}
                    </span>
                </div>
            </div>
        @empty
            <div class="text-center py-4 text-muted" style="font-size: 0.8rem; background: rgba(255,255,255,0.01); border-radius:12px; border: 1px dashed rgba(255,255,255,0.05);">
                <i class="fas fa-folder-open d-block mb-2 text-muted opacity-50" style="font-size: 1.5rem;"></i>
                Belum ada riwayat produksi.
            </div>
        @endforelse
    </div>
</div>
@endsection

@section('scripts')
<script>
    function toggleProductActions(id) {
        const actionEl = document.getElementById('actions-' + id);
        actionEl.classList.toggle('show');
    }
</script>
@endsection
