@extends('layouts.app')
@section('title', 'Produk Marketplace')
@section('page-title', 'Produk Marketplace')

@section('content')
    @if (session('success'))
        <div class="alert alert-success d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="fas fa-check-circle"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="fas fa-exclamation-circle"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- Tab Navigation --}}
    @php
        $tenantId = Auth::user()->tenant_id;
        $baseQuery = \App\Models\MarketplaceProduct::whereHas('store', function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId);
        });

        $totalCount = (clone $baseQuery)->count();
        $unmappedCount = (clone $baseQuery)->whereDoesntHave('masterProduct')->count();
        $mappedCount = (clone $baseQuery)->whereHas('masterProduct')->count();
    @endphp

    <ul class="nav nav-pills gap-1 p-1 d-inline-flex border border-white border-opacity-10 rounded-pill mb-4 bg-black bg-opacity-25"
        id="marketplaceTab" role="tablist">
        <li class="nav-item" role="presentation">
            <a href="{{ route('marketplace_products.index', request()->except(['status', 'page'])) }}"
                class="nav-link {{ !request('status') ? 'active' : 'text-secondary' }} d-flex align-items-center gap-2 rounded-pill"
                style="font-size: 0.8rem; padding: 0.5rem 1.2rem;">
                <i class="fas fa-store"></i> Semua
                <span
                    class="badge {{ !request('status') ? 'bg-white text-primary' : 'bg-secondary' }}">{{ $totalCount }}</span>
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a href="{{ route('marketplace_products.index', array_merge(request()->except('page'), ['status' => 'unmapped'])) }}"
                class="nav-link {{ request('status') === 'unmapped' ? 'active' : 'text-secondary' }} d-flex align-items-center gap-2 rounded-pill"
                style="font-size: 0.8rem; padding: 0.5rem 1.2rem;">
                <i class="fas fa-unlink"></i> Belum Ditautkan
                <span class="badge bg-warning text-dark">{{ $unmappedCount }}</span>
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a href="{{ route('marketplace_products.index', array_merge(request()->except('page'), ['status' => 'mapped'])) }}"
                class="nav-link {{ request('status') === 'mapped' ? 'active' : 'text-secondary' }} d-flex align-items-center gap-2 rounded-pill"
                style="font-size: 0.8rem; padding: 0.5rem 1.2rem;">
                <i class="fas fa-link"></i> Sudah Ditautkan
                <span class="badge bg-success">{{ $mappedCount }}</span>
            </a>
        </li>
    </ul>


    {{-- Tab Content --}}
    @if (!request('status'))
        @include('marketplace_products.partials.tab-semua')
    @elseif (request('status') === 'unmapped')
        @include('marketplace_products.partials.tab-unmapped')
    @elseif (request('status') === 'mapped')
        @include('marketplace_products.partials.tab-mapped')
    @endif
@endsection
