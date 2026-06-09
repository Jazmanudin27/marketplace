@extends('layouts.app')
@section('title', 'Produk Marketplace')
@section('page-title', 'Produk Marketplace')

@section('content')
<div class="dashboard-card">
    <div class="card-header-line">
        <h3><i class="fas fa-boxes"></i> Daftar Produk Marketplace</h3>
        <div>
            <a href="{{ route('marketplace_products.index') }}" class="btn-primary-sm {{ !request('status') ? 'active' : '' }}" style="background: {{ !request('status') ? '#333' : '#eee' }}; color: {{ !request('status') ? '#fff' : '#333' }}">Semua</a>
            <a href="{{ route('marketplace_products.index', ['status' => 'unmapped']) }}" class="btn-primary-sm" style="background: {{ request('status') === 'unmapped' ? '#333' : '#eee' }}; color: {{ request('status') === 'unmapped' ? '#fff' : '#333' }}">Belum Ditautkan</a>
            <a href="{{ route('marketplace_products.index', ['status' => 'mapped']) }}" class="btn-primary-sm" style="background: {{ request('status') === 'mapped' ? '#333' : '#eee' }}; color: {{ request('status') === 'mapped' ? '#fff' : '#333' }}">Sudah Ditautkan</a>
        </div>
    </div>
    
    <div class="table-responsive" style="margin-top: 1rem;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Toko / Channel</th>
                    <th>Produk Marketplace</th>
                    <th>SKU / Harga</th>
                    <th>Stok</th>
                    <th>Status Master</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($marketplaceProducts as $product)
                <tr>
                    <td>
                        <div style="font-weight: 500;">{{ $product->store->store_name }}</div>
                        <div style="font-size: 0.8rem; color: #666; margin-top: 0.2rem;">
                            <span class="channel-badge channel-{{ $product->store->channel->code }}">
                                {{ $product->store->channel->name }}
                            </span>
                        </div>
                    </td>
                    <td>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            @if($product->image_url)
                                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid #eee;">
                            @else
                                <div style="width: 50px; height: 50px; background: #f5f5f5; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #bbb;">
                                    <i class="fas fa-image"></i>
                                </div>
                            @endif
                            <div>
                                <div style="font-weight: 500;">{{ $product->name }}</div>
                                <div style="font-size: 0.8rem; color: #888;">ID: {{ $product->marketplace_product_id }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div><span style="background: #eee; padding: 2px 6px; border-radius: 4px; font-size: 0.85rem; font-family: monospace;">{{ $product->marketplace_sku ?? 'Tidak ada SKU' }}</span></div>
                        <div style="margin-top: 0.4rem; color: #E53935; font-weight: 600;">Rp {{ number_format($product->price, 0, ',', '.') }}</div>
                    </td>
                    <td>
                        <div style="font-size: 1.1rem; font-weight: 600;">{{ $product->stock }}</div>
                    </td>
                    <td>
                        @if($product->master_product_id)
                            <div style="color: #2E7D32; font-size: 0.85rem;">
                                <i class="fas fa-link"></i> Tertaut ke:<br>
                                <strong style="color: #333;">{{ $product->masterProduct->name }}</strong>
                            </div>
                        @else
                            <div style="color: #F57C00; font-size: 0.85rem;">
                                <i class="fas fa-unlink"></i> Belum ditautkan
                            </div>
                        @endif
                    </td>
                    <td>
                        @if(!$product->master_product_id)
                            <div style="display: flex; gap: 0.5rem; flex-direction: column;">
                                <!-- Jadikan Master -->
                                <form action="{{ route('marketplace_products.promote', $product->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn-primary-sm" style="width: 100%; text-align: center; background: #2196F3; border: none; color: white; padding: 6px; border-radius: 4px; cursor: pointer;" onclick="return confirm('Jadikan produk ini sebagai Master Product baru?');">
                                        <i class="fas fa-star"></i> Jadikan Master
                                    </button>
                                </form>
                                
                                <!-- Tautkan ke Master yang sudah ada -->
                                <button type="button" onclick="document.getElementById('link-form-{{ $product->id }}').style.display='block'" style="width: 100%; text-align: center; background: #fff; border: 1px solid #ccc; color: #333; padding: 6px; border-radius: 4px; cursor: pointer;">
                                    <i class="fas fa-link"></i> Tautkan...
                                </button>
                                
                                <form id="link-form-{{ $product->id }}" action="{{ route('marketplace_products.link', $product->id) }}" method="POST" style="display: none; background: #f9f9f9; padding: 10px; border: 1px solid #ddd; border-radius: 4px; margin-top: 5px;">
                                    @csrf
                                    <select name="master_product_id" style="width: 100%; padding: 6px; margin-bottom: 8px; border: 1px solid #ccc; border-radius: 4px;" required>
                                        <option value="">-- Pilih Master Product --</option>
                                        @foreach($masterProducts as $master)
                                            <option value="{{ $master->id }}">{{ $master->name }} (SKU: {{ $master->sku }})</option>
                                        @endforeach
                                    </select>
                                    <div style="display: flex; gap: 5px;">
                                        <button type="submit" style="flex: 1; background: #4CAF50; color: white; border: none; padding: 5px; border-radius: 3px; cursor: pointer;">Simpan</button>
                                        <button type="button" onclick="document.getElementById('link-form-{{ $product->id }}').style.display='none'" style="flex: 1; background: #ccc; color: #333; border: none; padding: 5px; border-radius: 3px; cursor: pointer;">Batal</button>
                                    </div>
                                </form>
                            </div>
                        @else
                            <button disabled style="background: #e0e0e0; border: none; color: #888; padding: 6px 12px; border-radius: 4px; cursor: not-allowed; width: 100%;">
                                <i class="fas fa-check"></i> Selesai
                            </button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 2rem;">
                        <i class="fas fa-box-open" style="font-size: 2rem; color: #ccc; margin-bottom: 1rem; display: block;"></i>
                        Belum ada data produk marketplace yang ditarik.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div style="margin-top: 1rem;">
        {{ $marketplaceProducts->links('pagination::bootstrap-4') }}
    </div>
</div>
@endsection
