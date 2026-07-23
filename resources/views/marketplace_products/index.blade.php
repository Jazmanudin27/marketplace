@extends('layouts.app')
@section('title', 'Produk Marketplace')
@section('page-title', 'Produk Marketplace')
@section('topbar-actions')
    <div class="d-flex gap-2">
        <a href="{{ route('marketplace_products.print_report', request()->all()) }}" target="_blank"
            class="btn btn-outline-dark btn-sm fw-bold">
            <i class="fas fa-print me-1"></i> Cetak Laporan
        </a>
        <form action="{{ route('marketplace_products.auto_link') }}" method="POST" class="d-inline"
            onsubmit="return confirm('Tautkan semua produk marketplace secara otomatis berdasarkan kesamaan SKU?');">
            @csrf
            <button type="submit" class="btn btn-success btn-sm fw-bold text-white">
                <i class="fas fa-magic me-1"></i> Tautkan Otomatis (Masal)
            </button>
        </form>
        <form action="{{ route('marketplace_products.bulk_promote') }}" method="POST" class="d-inline"
            onsubmit="return confirm('Jadikan semua produk marketplace yang belum ditautkan sebagai Master Product baru? (SKU kosong akan otomatis dibuatkan acak)');">
            @csrf
            <button type="submit" class="btn btn-primary btn-sm fw-bold text-white">
                <i class="fas fa-star me-1 text-warning"></i> Jadikan Master (Masal)
            </button>
        </form>
    </div>
@endsection

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @php
        $tenantId = Auth::user()->tenant_id;
        $baseQuery = \App\Models\MarketplaceProduct::whereHas('store', function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId);
        });
        $totalCount    = (clone $baseQuery)->count();
        $unmappedCount = (clone $baseQuery)->whereDoesntHave('masterProduct')->count();
        $mappedCount   = (clone $baseQuery)->whereHas('masterProduct')->count();
    @endphp

    <ul class="nav nav-tabs mb-0" id="marketplaceTab" role="tablist">
        <li class="nav-item" role="presentation">
            <a href="{{ route('marketplace_products.index', request()->except(['status', 'page'])) }}"
                class="nav-link small fw-semibold {{ !request('status') ? 'active' : '' }}">
                <i class="fas fa-store me-1"></i>Semua
                <span class="badge {{ !request('status') ? 'bg-primary' : 'bg-secondary' }} ms-1">{{ $totalCount }}</span>
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a href="{{ route('marketplace_products.index', array_merge(request()->except('page'), ['status' => 'unmapped'])) }}"
                class="nav-link small fw-semibold {{ request('status') === 'unmapped' ? 'active' : '' }}">
                <i class="fas fa-unlink me-1"></i>Belum Ditautkan
                <span class="badge bg-warning text-dark ms-1">{{ $unmappedCount }}</span>
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a href="{{ route('marketplace_products.index', array_merge(request()->except('page'), ['status' => 'mapped'])) }}"
                class="nav-link small fw-semibold {{ request('status') === 'mapped' ? 'active' : '' }}">
                <i class="fas fa-link me-1"></i>Sudah Ditautkan
                <span class="badge bg-success ms-1">{{ $mappedCount }}</span>
            </a>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active">
            @if (!request('status'))
                @include('marketplace_products.partials.tab-semua')
            @elseif (request('status') === 'unmapped')
                @include('marketplace_products.partials.tab-unmapped')
            @elseif (request('status') === 'mapped')
                @include('marketplace_products.partials.tab-mapped')
            @endif
        </div>
    </div>
@endsection
