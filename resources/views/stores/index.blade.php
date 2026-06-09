@extends('layouts.app')
@section('title', 'Kelola Toko')
@section('page-title', 'Kelola Toko Marketplace')
@section('content')
<div class="dashboard-card">
    <div class="card-header-line">
        <h3><i class="fas fa-plug"></i> Toko Terhubung</h3>
        <a href="{{ route('stores.create') }}" class="btn-primary-sm"><i class="fas fa-plus"></i> Tambah Toko</a>
    </div>
    <div class="stores-grid" style="margin-top:1rem;">
        @forelse($stores as $store)
        <div class="store-card">
            <div class="store-header">
                <div class="channel-badge channel-{{ $store->channel->code }}">
                    @if($store->channel->code==='shopee')<i class="fas fa-shopping-bag"></i>
                    @elseif($store->channel->code==='tiktok')<i class="fab fa-tiktok"></i>
                    @elseif($store->channel->code==='tokopedia')<i class="fas fa-store"></i>
                    @else<i class="fas fa-globe"></i>@endif
                    {{ $store->channel->name }}
                </div>
                <div class="store-status {{ $store->status === 'connected' ? 'status-connected' : 'status-disconnected' }}">
                    <span class="status-dot"></span>
                    {{ $store->status === 'connected' ? 'Terhubung' : 'Terputus' }}
                </div>
            </div>
            <div class="store-name">{{ $store->store_name }}</div>
            <div class="store-stat" style="margin-top:0.4rem;">
                <i class="fas fa-id-card"></i> ID: {{ $store->marketplace_store_id }}
            </div>
        </div>
        @empty
        <div class="empty-state">
            <i class="fas fa-plug"></i>
            <p>Belum ada toko yang terhubung. Tambahkan toko marketplace Anda sekarang.</p>
            <a href="{{ route('stores.create') }}" class="btn-primary-sm">Hubungkan Toko Pertama</a>
        </div>
        @endforelse
    </div>
</div>
@endsection
