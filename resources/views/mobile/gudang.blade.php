@extends('layouts.mobile')

@section('title', 'Gudang Dashboard')
@section('header-title', 'Gudang Dashboard')

@section('content')
<!-- Search Form -->
<form action="{{ route('mobile.gudang') }}" method="GET" class="mb-4">
    <div class="input-group">
        <span class="input-group-text"><i class="fas fa-search text-muted"></i></span>
        <input type="text" name="search" class="form-control" placeholder="Cari SKU atau nama produk..." value="{{ $search }}">
        @if($search)
            <a href="{{ route('mobile.gudang') }}" class="btn btn-outline-secondary d-flex align-items-center justify-content-center">
                <i class="fas fa-times"></i>
            </a>
        @endif
    </div>
</form>

<!-- Quick Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-6">
        <div class="card border shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3 py-2.5 px-3">
                <div class="rounded-3 bg-primary bg-opacity-10 text-primary p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="fas fa-spinner fa-spin"></i>
                </div>
                <div>
                    <small class="text-muted d-block">Antrean Aktif</small>
                    <strong class="text-dark fs-5">{{ count($activeProductionRequests) }}</strong> <span class="text-muted small">Order</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6">
        <div class="card border shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3 py-2.5 px-3">
                <div class="rounded-3 bg-success bg-opacity-10 text-success p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div>
                    <small class="text-muted d-block">Riwayat Selesai</small>
                    <strong class="text-dark fs-5">{{ count($productionHistory) }}</strong> <span class="text-muted small">Item</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Product List / Stock Management -->
<div class="card border shadow-sm mb-4">
    <div class="card-header bg-primary bg-opacity-10 d-flex justify-content-between align-items-center py-2 px-3 border-bottom">
        <h6 class="m-0 fw-bold text-primary">
            <i class="fas fa-boxes me-2"></i> Daftar & Penyesuaian Stok
        </h6>
        <span class="text-muted small">Total: {{ $products->total() }} SKU</span>
    </div>
    
    <div class="card-body p-3">
        <div class="d-flex flex-column gap-2">
            @forelse($products as $product)
                <div class="card border shadow-sm mb-2">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#actions-{{ $product->id }}" aria-expanded="false" style="cursor: pointer;">
                            <div class="d-flex align-items-center gap-3">
                                @if($product->image_url)
                                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="rounded border" style="width: 48px; height: 48px; object-fit: cover;">
                                @else
                                    <div class="rounded bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 48px; height: 48px;">
                                        {{ strtoupper(substr($product->name, 0, 2)) }}
                                    </div>
                                @endif
                                <div>
                                    <div class="fw-bold text-dark">{{ $product->name }}</div>
                                    <small class="text-muted d-block">
                                        SKU: <code class="text-primary font-monospace">{{ $product->sku }}</code>
                                    </small>
                                </div>
                            </div>
                            <div class="text-end">
                                @if($product->stock <= $product->min_stock)
                                    <span class="badge bg-danger">
                                        <i class="fas fa-exclamation-triangle me-1"></i>{{ $product->stock }} {{ $product->unit ?: 'pcs' }}
                                    </span>
                                @else
                                    <span class="badge bg-success">
                                        <i class="fas fa-check-circle me-1"></i>{{ $product->stock }} {{ $product->unit ?: 'pcs' }}
                                    </span>
                                @endif
                                <div class="text-muted small mt-1">
                                    Min: {{ $product->min_stock }}
                                </div>
                            </div>
                        </div>

                        <!-- Accordion Actions (Collapse) -->
                        <div id="actions-{{ $product->id }}" class="collapse mt-3 border-top pt-3">
                            <!-- Nav Tabs/Pills -->
                            <ul class="nav nav-pills mb-3 d-flex gap-1" id="pills-tab-{{ $product->id }}" role="tablist">
                                <li class="nav-item flex-fill" role="presentation">
                                    <button class="nav-link active btn-sm w-100" id="pills-adjust-tab-{{ $product->id }}" data-bs-toggle="pill" data-bs-target="#pills-adjust-{{ $product->id }}" type="button" role="tab" aria-selected="true">
                                        <i class="fas fa-sliders-h me-1.5"></i>Sesuaikan Stok
                                    </button>
                                </li>
                                <li class="nav-item flex-fill" role="presentation">
                                    <button class="nav-link btn-sm w-100" id="pills-produce-tab-{{ $product->id }}" data-bs-toggle="pill" data-bs-target="#pills-produce-{{ $product->id }}" type="button" role="tab" aria-selected="false">
                                        <i class="fas fa-hammer me-1.5"></i>Pesan Produksi
                                    </button>
                                </li>
                            </ul>

                            <div class="tab-content" id="pills-tabContent-{{ $product->id }}">
                                <!-- Adjust Stock Form -->
                                <div class="tab-pane fade show active" id="pills-adjust-{{ $product->id }}" role="tabpanel">
                                    <form action="{{ route('mobile.gudang.adjust_stock', $product->id) }}" method="POST">
                                        @csrf
                                        <div class="row g-2">
                                            <div class="col-4">
                                                <select name="type" class="form-select form-select-sm" required>
                                                    <option value="in">Tambah</option>
                                                    <option value="out">Kurang</option>
                                                    <option value="adj">Setel</option>
                                                </select>
                                            </div>
                                            <div class="col-4">
                                                <input type="number" name="quantity" class="form-control form-control-sm" placeholder="Jumlah" required>
                                            </div>
                                            <div class="col-4">
                                                <input type="text" name="reference" class="form-control form-control-sm" placeholder="Keterangan" required>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-primary w-100 btn-sm mt-3">
                                            <i class="fas fa-save me-1"></i>Simpan Penyesuaian
                                        </button>
                                    </form>
                                </div>

                                <!-- Request Production Form -->
                                <div class="tab-pane fade" id="pills-produce-{{ $product->id }}" role="tabpanel">
                                    <form action="{{ route('mobile.gudang.request_production') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="master_product_id" value="{{ $product->id }}">
                                        <div class="mb-3">
                                            <label class="form-label text-muted small">Jumlah yang Dipesan ke Produksi</label>
                                            <input type="number" name="quantity" class="form-control form-control-sm" placeholder="Masukkan Qty Produksi" min="1" required>
                                        </div>
                                        <button type="submit" class="btn btn-info text-white w-100 btn-sm">
                                            <i class="fas fa-paper-plane me-1"></i>Kirim ke Produksi
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-search d-block mb-2 opacity-50" style="font-size: 2rem;"></i>
                    Tidak ada produk ditemukan.
                </div>
            @endforelse
        </div>
        
        <div class="mt-3 d-flex justify-content-center">
            {{ $products->links() }}
        </div>
    </div>
</div>

<!-- Active Production Requests -->
<div class="card border shadow-sm mb-4">
    <div class="card-header bg-primary bg-opacity-10 py-2 px-3 border-bottom">
        <h6 class="m-0 fw-bold text-primary">
            <i class="fas fa-spinner me-2"></i> Antrean Permintaan Produksi
        </h6>
    </div>
    
    <div class="card-body p-3">
        <div class="d-flex flex-column gap-2">
            @forelse($activeProductionRequests as $req)
                <div class="d-flex align-items-center gap-3 p-2 border rounded bg-light bg-opacity-50">
                    <div class="rounded-3 p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background-color: {{ $req->status === 'producing' ? 'rgba(14, 165, 233, 0.15)' : 'rgba(245, 158, 11, 0.15)' }}; color: {{ $req->status === 'producing' ? '#0ea5e9' : '#f59e0b' }};">
                        @if($req->status === 'producing')
                            <i class="fas fa-cog fa-spin"></i>
                        @else
                            <i class="fas fa-hourglass-half"></i>
                        @endif
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold text-dark small">{{ $req->masterProduct->name }}</div>
                        <small class="text-muted d-block">
                            Dipesan: <strong class="text-primary">{{ $req->quantity }} pcs</strong>
                            <span class="mx-1">•</span>
                            <span>{{ $req->created_at->format('d/m H:i') }}</span>
                        </small>
                    </div>
                    <div>
                        <span class="badge {{ $req->status === 'producing' ? 'bg-info' : 'bg-warning text-dark' }}">
                            {{ $req->status === 'pending' ? 'Menunggu' : 'Diproses' }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="text-center py-4 text-muted border border-dashed rounded bg-light bg-opacity-20">
                    <i class="fas fa-clipboard-list d-block mb-2 opacity-50" style="font-size: 1.5rem;"></i>
                    Tidak ada permintaan produksi aktif.
                </div>
            @endforelse
        </div>
    </div>
</div>

<!-- Production History -->
<div class="card border shadow-sm">
    <div class="card-header bg-primary bg-opacity-10 py-2 px-3 border-bottom">
        <h6 class="m-0 fw-bold text-primary">
            <i class="fas fa-history me-2"></i> Riwayat Produksi
        </h6>
    </div>
    
    <div class="card-body p-3">
        <div class="d-flex flex-column gap-2">
            @forelse($productionHistory as $hist)
                <div class="d-flex align-items-center gap-3 p-2 border rounded bg-light bg-opacity-50">
                    <div class="rounded-3 p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background-color: {{ $hist->status === 'completed' ? 'rgba(16, 185, 129, 0.15)' : 'rgba(239, 68, 68, 0.15)' }}; color: {{ $hist->status === 'completed' ? '#10b981' : '#ef4444' }};">
                        @if($hist->status === 'completed')
                            <i class="fas fa-check-circle"></i>
                        @else
                            <i class="fas fa-times-circle"></i>
                        @endif
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold text-dark small">{{ $hist->masterProduct->name }}</div>
                        <small class="text-muted d-block">
                            Jumlah: <strong class="text-primary">{{ $hist->quantity }} pcs</strong>
                            <span class="mx-1">•</span>
                            <span>{{ $hist->updated_at->format('d/m H:i') }}</span>
                        </small>
                    </div>
                    <div>
                        <span class="badge {{ $hist->status === 'completed' ? 'bg-success' : 'bg-danger' }}">
                            {{ $hist->status === 'completed' ? 'Selesai' : 'Batal' }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="text-center py-4 text-muted border border-dashed rounded bg-light bg-opacity-20">
                    <i class="fas fa-folder-open d-block mb-2 opacity-50" style="font-size: 1.5rem;"></i>
                    Belum ada riwayat produksi.
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
