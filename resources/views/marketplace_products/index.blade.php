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
                        @if($product->master_product_id && $product->sync_stock && $product->safety_stock > 0)
                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">
                                (Master: {{ $product->masterProduct->stock }} - Safety: {{ $product->safety_stock }})
                            </div>
                        @endif
                    </td>
                    <td>
                        @if($product->master_product_id)
                            <div style="color: var(--success); font-size: 0.85rem;">
                                <i class="fas fa-link"></i> Tertaut ke:<br>
                                <strong style="color: var(--text-primary);">{{ $product->masterProduct->name }}</strong>
                            </div>
                            @if($product->sync_stock)
                                <div style="margin-top: 0.3rem; color: var(--primary); font-size: 0.8rem; font-weight: 500;">
                                    <i class="fas fa-sync-alt"></i> Sync Aktif (Safety: {{ $product->safety_stock }})
                                </div>
                            @else
                                <div style="margin-top: 0.3rem; color: var(--text-muted); font-size: 0.8rem;">
                                    <i class="fas fa-sync-alt-slash"></i> Sync Mati
                                </div>
                            @endif
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
                                
                                <!-- Salin ke Toko Lain (Auto-promote + publish) -->
                                <form action="{{ route('marketplace_products.clone_and_publish', $product->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn-primary-sm" style="width: 100%; text-align: center; background: #4f46e5; border: none; color: white; padding: 6px; border-radius: 4px; cursor: pointer;">
                                        <i class="fas fa-copy"></i> Salin ke Toko Lain
                                    </button>
                                </form>
                                
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
                            <div style="display: flex; gap: 0.5rem; flex-direction: column;">
                                <button type="button" class="btn-primary-sm" style="width: 100%; text-align: center; background: rgba(108, 99, 255, 0.15); border: 1px solid var(--primary); color: var(--text-primary); padding: 6px; border-radius: 4px; cursor: pointer;" data-bs-toggle="modal" data-bs-target="#settingsModal-{{ $product->id }}">
                                    <i class="fas fa-cog"></i> Pengaturan Stok
                                </button>
                                <a href="{{ route('products.publish', $product->master_product_id) }}" class="btn-primary-sm" style="width: 100%; text-align: center; background: #6366f1; border: none; color: white; padding: 6px; border-radius: 4px; text-decoration: none; display: block;">
                                    <i class="fas fa-copy"></i> Salin ke Toko Lain
                                </a>
                            </div>

                            <!-- Modal Pengaturan Stok -->
                            <div class="modal fade" id="settingsModal-{{ $product->id }}" tabindex="-1" aria-labelledby="settingsModalLabel-{{ $product->id }}" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content" style="background: var(--bg-card); border: 1px solid var(--border); text-align: left;">
                                        <div class="modal-header" style="border-bottom: 1px solid var(--border);">
                                            <h5 class="modal-title" id="settingsModalLabel-{{ $product->id }}" style="color: var(--text-primary);">
                                                <i class="fas fa-cog text-primary me-2"></i> Pengaturan Stok: {{ $product->name }}
                                            </h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form action="{{ route('marketplace_products.update_settings', $product->id) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-body">
                                                <div class="mb-4">
                                                    <label class="form-label d-block fw-bold mb-2" style="color: var(--text-secondary);">Sinkronisasi Stok</label>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="sync_stock" id="syncStock-{{ $product->id }}" value="1" {{ $product->sync_stock ? 'checked' : '' }} style="cursor: pointer;">
                                                        <label class="form-check-label text-light" for="syncStock-{{ $product->id }}" style="cursor: pointer;">
                                                            Otomatis sinkronkan stok dari Master Product
                                                        </label>
                                                    </div>
                                                    <div class="form-text mt-1" style="font-size: 0.8rem; color: var(--text-muted);">
                                                        Jika dinonaktifkan, perubahan stok Master Product tidak akan didorong ke marketplace ini.
                                                    </div>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="safetyStock-{{ $product->id }}" class="form-label fw-bold mb-2" style="color: var(--text-secondary);">Stok Pengaman (Safety Stock)</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text" style="background: var(--bg-card2); border: 1px solid var(--border); color: var(--text-secondary);">
                                                            <i class="fas fa-shield-alt"></i>
                                                        </span>
                                                        <input type="number" class="form-control" name="safety_stock" id="safetyStock-{{ $product->id }}" min="0" value="{{ $product->safety_stock ?? 0 }}" required style="background: var(--bg); color: var(--text-primary); border: 1px solid var(--border);">
                                                    </div>
                                                    <div class="form-text mt-2" style="font-size: 0.8rem; color: var(--text-muted);">
                                                        Jumlah stok yang ditahan sebagai pengaman di gudang lokal.<br>
                                                        Stok yang dikirim ke toko = <strong>Stok Master ({{ $product->masterProduct->stock }}) - Stok Pengaman</strong>.
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer" style="border-top: 1px solid var(--border);">
                                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" style="background: #334155; border: none; padding: 6px 12px;">Batal</button>
                                                <button type="submit" class="btn btn-primary btn-sm" style="background: var(--primary); border: none; padding: 6px 12px;">Simpan Pengaturan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
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
