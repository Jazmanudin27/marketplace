@extends('layouts.app')
@section('title', 'Kartu Stok: ' . $product->name)
@section('page-title', 'Kartu Stok')

@section('content')
<div class="form-page-wrapper">
    <a href="{{ route('inventory.index') }}" class="btn-back">
        <i class="fas fa-arrow-left"></i> Kembali ke Inventory
    </a>

    @if(session('success'))
    <div class="alert alert-success" style="padding: 1rem; background: #d4edda; color: #155724; border-radius: 4px; margin-bottom: 1rem;">
        {{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger" style="padding: 1rem; background: #f8d7da; color: #721c24; border-radius: 4px; margin-bottom: 1rem;">
        <ul style="margin: 0; padding-left: 1.5rem;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div style="display:grid; grid-template-columns: 1fr 350px; gap:1.5rem; margin-top:1rem;">

        {{-- Kiri: Riwayat Pergerakan Stok --}}
        <div class="dashboard-card">
            <div class="card-header-line">
                <h3><i class="fas fa-history"></i> Riwayat Pergerakan Stok</h3>
            </div>
            
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Tipe</th>
                            <th style="text-align: right;">Qty</th>
                            <th style="text-align: right;">Sisa Stok</th>
                            <th>Referensi / Keterangan</th>
                            <th>User</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($movements as $mov)
                        <tr>
                            <td class="mono" style="font-size: 0.85rem;">{{ $mov->created_at->format('d M Y H:i') }}</td>
                            <td>
                                @if($mov->type == 'in') <span class="badge badge-success">IN</span>
                                @elseif($mov->type == 'out') <span class="badge badge-danger">OUT</span>
                                @else <span class="badge badge-warning">ADJ</span>
                                @endif
                            </td>
                            <td class="mono fw-bold {{ $mov->quantity > 0 ? 'text-success' : 'text-danger' }}" style="text-align: right;">
                                {{ $mov->quantity > 0 ? '+' : '' }}{{ number_format($mov->quantity) }}
                            </td>
                            <td class="mono fw-bold" style="text-align: right;">{{ number_format($mov->balance_after) }}</td>
                            <td>{{ $mov->reference }}</td>
                            <td>{{ $mov->user->name ?? 'System' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted" style="padding:2rem;">Belum ada riwayat stok</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div style="margin-top:1rem;">{{ $movements->links() }}</div>
        </div>

        {{-- Kanan: Detail & Form Penyesuaian --}}
        <div>
            <div class="dashboard-card" style="margin-bottom: 1.5rem;">
                <h3 style="font-size:1rem; font-weight:700; margin-bottom:1rem; color:var(--text-primary);">
                    <i class="fas fa-info-circle"></i> Info Produk
                </h3>
                <div style="display: flex; gap: 1rem; align-items: center; margin-bottom: 1rem;">
                    @if($product->image_url)
                        <img src="{{ Storage::url($product->image_url) }}" alt="{{ $product->name }}" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">
                    @else
                        <div style="width: 60px; height: 60px; background: #eee; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-image text-muted"></i>
                        </div>
                    @endif
                    <div>
                        <div class="fw-bold">{{ $product->name }}</div>
                        <div class="mono text-muted" style="font-size: 0.85rem;">{{ $product->sku }}</div>
                    </div>
                </div>
                
                <div style="background: #f8f9fa; padding: 1rem; border-radius: 4px; text-align: center; border: 1px solid var(--border);">
                    <div style="font-size: 0.85rem; color: var(--text-secondary); text-transform: uppercase; font-weight: bold;">Total Stok Saat Ini</div>
                    <div class="mono" style="font-size: 2.5rem; font-weight: 800; color: {{ $product->stock <= $product->min_stock ? 'var(--danger)' : 'var(--success)' }};">
                        {{ number_format($product->stock) }}
                    </div>
                </div>
            </div>

            <div class="dashboard-card">
                <h3 style="font-size:1rem; font-weight:700; margin-bottom:1rem; color:var(--text-primary);">
                    <i class="fas fa-sliders-h"></i> Penyesuaian Stok (Adjustment)
                </h3>
                
                <form action="{{ route('inventory.adjust', $product->id) }}" method="POST">
                    @csrf
                    
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-size: 0.85rem; font-weight: 600;">Jumlah Penyesuaian (Qty)</label>
                        <input type="number" name="quantity" required placeholder="Gunakan minus (-) untuk mengurangi" style="width: 100%; padding: 0.6rem; border: 1px solid var(--border); border-radius: 4px;">
                        <small class="text-muted" style="display: block; margin-top: 0.25rem;">Contoh: <b>5</b> untuk menambah, <b>-3</b> untuk mengurangi.</small>
                    </div>

                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-size: 0.85rem; font-weight: 600;">Keterangan / Alasan</label>
                        <textarea name="reference" required rows="3" placeholder="Misal: Stok Opname, Barang Rusak, Penambahan Manual..." style="width: 100%; padding: 0.6rem; border: 1px solid var(--border); border-radius: 4px; resize: vertical;"></textarea>
                    </div>

                    <button type="submit" class="btn-primary" style="width: 100%; background: var(--warning); border-color: var(--warning); color: #fff;">
                        <i class="fas fa-save"></i> Simpan Penyesuaian
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>
@endsection
