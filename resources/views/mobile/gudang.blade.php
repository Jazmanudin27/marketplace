@extends('layouts.mobile')

@section('title', 'Gudang Dashboard')
@section('header-title', 'Gudang Dashboard')

@section('content')
<!-- Search Form -->
<form action="{{ route('mobile.gudang') }}" method="GET" class="mb-4">
    <div class="input-group">
        <input type="text" name="search" class="form-control custom-input" placeholder="Cari SKU atau nama produk..." value="{{ $search }}">
        <button class="btn btn-premium" type="submit">
            <i class="fas fa-search"></i>
        </button>
        @if($search)
            <a href="{{ route('mobile.gudang') }}" class="btn btn-secondary-custom d-flex align-items-center justify-content-center">
                <i class="fas fa-times"></i>
            </a>
        @endif
    </div>
</form>

<!-- Product List / Stock Management -->
<div class="glass-card p-4 mb-4">
    <h4 class="mb-3" style="font-size: 1rem; font-weight: 600; color:#818cf8;">
        <i class="fas fa-boxes me-1"></i> Daftar & Penyesuaian Stok
    </h4>
    
    <div class="d-flex flex-column gap-3">
        @forelse($products as $product)
            <div class="border-bottom border-light border-opacity-10 pb-3">
                <div class="d-flex justify-content-between align-items-start" onclick="toggleProductActions({{ $product->id }})" style="cursor: pointer;">
                    <div>
                        <div style="font-size: 0.9rem; font-weight: 600;">{{ $product->name }}</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);" class="mt-0.5">
                            SKU: <span class="mono">{{ $product->sku }}</span>
                        </div>
                    </div>
                    <div class="text-end">
                        <span class="badge {{ $product->stock <= $product->min_stock ? 'bg-danger bg-opacity-20 text-danger border border-danger border-opacity-30' : 'bg-success bg-opacity-20 text-success border border-success border-opacity-30' }}" style="font-size: 0.75rem; padding: 4px 8px; border-radius: 8px;">
                            {{ $product->stock }} {{ $product->unit ?: 'pcs' }}
                        </span>
                        <div style="font-size: 0.65rem; color: var(--text-muted);" class="mt-1">
                            Min: {{ $product->min_stock }}
                        </div>
                    </div>
                </div>

                <!-- Accordion Actions (hidden by default) -->
                <div id="actions-{{ $product->id }}" class="d-none mt-3 p-3" style="background: rgba(255, 255, 255, 0.02); border: 1px solid var(--border-card); border-radius: 12px;">
                    <!-- Tabs -->
                    <ul class="nav nav-pills mb-3 d-flex gap-2" id="pills-tab-{{ $product->id }}" role="tablist">
                        <li class="nav-item flex-fill" role="presentation">
                            <button class="nav-link active btn-sm w-100 py-1.5" style="font-size:0.75rem;" id="pills-adjust-tab-{{ $product->id }}" data-bs-toggle="pill" data-bs-target="#pills-adjust-{{ $product->id }}" type="button" role="tab" aria-controls="pills-adjust-{{ $product->id }}" aria-selected="true">
                                <i class="fas fa-edit me-1"></i> Sesuaikan Stok
                            </button>
                        </li>
                        <li class="nav-item flex-fill" role="presentation">
                            <button class="nav-link btn-sm w-100 py-1.5" style="font-size:0.75rem; background-color: rgba(79, 70, 229, 0.08); color: #c7d2fe;" id="pills-produce-tab-{{ $product->id }}" data-bs-toggle="pill" data-bs-target="#pills-produce-{{ $product->id }}" type="button" role="tab" aria-controls="pills-produce-{{ $product->id }}" aria-selected="false">
                                <i class="fas fa-hammer me-1"></i> Pesan Produksi
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
                                        <select name="type" class="form-select custom-input py-1.5" style="font-size:0.8rem;" required>
                                            <option value="in">Tambah</option>
                                            <option value="out">Kurang</option>
                                            <option value="adj">Setel</option>
                                        </select>
                                    </div>
                                    <div class="col-4">
                                        <input type="number" name="quantity" class="form-control custom-input py-1.5" style="font-size:0.8rem;" placeholder="Jumlah" required>
                                    </div>
                                    <div class="col-4">
                                        <input type="text" name="reference" class="form-control custom-input py-1.5" style="font-size:0.8rem;" placeholder="Ket (ex: Opname)" required>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-premium w-100 btn-sm mt-3 py-2" style="font-size: 0.75rem;">
                                    <i class="fas fa-save me-1"></i> Simpan Penyesuaian
                                </button>
                            </form>
                        </div>

                        <!-- Request Production Form -->
                        <div class="tab-pane fade" id="pills-produce-{{ $product->id }}" role="tabpanel" aria-labelledby="pills-produce-tab-{{ $product->id }}">
                            <form action="{{ route('mobile.gudang.request_production') }}" method="POST">
                                @csrf
                                <input type="hidden" name="master_product_id" value="{{ $product->id }}">
                                <div class="mb-3">
                                    <label class="form-label text-muted" style="font-size: 0.75rem;">Jumlah yang Dipesan ke Produksi</label>
                                    <input type="number" name="quantity" class="form-control custom-input" placeholder="Masukkan Qty Produksi" min="1" required>
                                </div>
                                <button type="submit" class="btn btn-premium w-100 btn-sm py-2" style="font-size: 0.75rem; background: linear-gradient(135deg, #0ea5e9, #0284c7); box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);">
                                    <i class="fas fa-paper-plane me-1"></i> Kirim ke Produksi
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-4 text-muted" style="font-size: 0.85rem;">
                Tidak ada produk ditemukan.
            </div>
        @endforelse
    </div>
    
    <div class="mt-3 d-flex justify-content-center" style="font-size: 0.8rem;">
        {{ $products->links() }}
    </div>
</div>

<!-- Active Production Requests -->
<div class="glass-card p-4 mb-4">
    <h4 class="mb-3" style="font-size: 1rem; font-weight: 600; color:#818cf8;">
        <i class="fas fa-spinner me-1"></i> Antrean Permintaan Produksi
    </h4>
    
    <div class="d-flex flex-column gap-3">
        @forelse($activeProductionRequests as $req)
            <div class="d-flex justify-content-between align-items-center border-bottom border-light border-opacity-10 pb-2">
                <div>
                    <div style="font-size: 0.85rem; font-weight: 600;">{{ $req->masterProduct->name }}</div>
                    <div style="font-size: 0.7rem; color: var(--text-muted);" class="mt-1">
                        Dipesan: <span class="fw-bold">{{ $req->quantity }} pcs</span>
                        <span class="mx-1">•</span>
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
            <div class="text-center py-3 text-muted" style="font-size: 0.8rem;">
                Tidak ada permintaan produksi aktif.
            </div>
        @endforelse
    </div>
</div>

<!-- Production History -->
<div class="glass-card p-4">
    <h4 class="mb-3" style="font-size: 1rem; font-weight: 600; color:#818cf8;">
        <i class="fas fa-history me-1"></i> Riwayat Produksi
    </h4>
    
    <div class="d-flex flex-column gap-3">
        @forelse($productionHistory as $hist)
            <div class="d-flex justify-content-between align-items-center border-bottom border-light border-opacity-10 pb-2">
                <div>
                    <div style="font-size: 0.85rem; font-weight: 600;">{{ $hist->masterProduct->name }}</div>
                    <div style="font-size: 0.7rem; color: var(--text-muted);" class="mt-1">
                        Selesai: <span class="fw-bold">{{ $hist->quantity }} pcs</span>
                        <span class="mx-1">•</span>
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
            <div class="text-center py-3 text-muted" style="font-size: 0.8rem;">
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
        if (actionEl.classList.contains('d-none')) {
            actionEl.classList.remove('d-none');
        } else {
            actionEl.classList.add('d-none');
        }
    }
</script>
@endsection
