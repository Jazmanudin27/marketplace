@extends('layouts.app')
@section('title', 'Master Produk')
@section('page-title', 'Master Produk')

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
    @php $pendingCount = $publicationLogs->whereIn('status', ['pending','processing'])->count(); @endphp

    <ul class="nav nav-tabs mb-3" id="mainTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active small fw-semibold" id="tab-produk" data-bs-toggle="tab"
                data-bs-target="#panel-produk" type="button" role="tab">
                <i class="fas fa-box-open me-1"></i> Master Produk
                <span class="badge bg-secondary ms-1">{{ $products->total() }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link small fw-semibold" id="tab-riwayat" data-bs-toggle="tab" data-bs-target="#panel-riwayat"
                type="button" role="tab">
                <i class="fas fa-history me-1"></i> Riwayat Publikasi
                @if ($pendingCount > 0)
                    <span class="badge bg-warning text-dark ms-1">{{ $pendingCount }}</span>
                @else
                    <span class="badge bg-secondary ms-1">{{ $publicationLogs->count() }}</span>
                @endif
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link small fw-semibold" id="tab-pemetaan" data-bs-toggle="tab"
                data-bs-target="#panel-pemetaan" type="button" role="tab">
                <i class="fas fa-map-signs me-1"></i> Pemetaan Kategori
                <span class="badge bg-secondary ms-1">{{ $categoryMappings->count() }}</span>
            </button>
        </li>
    </ul>

    <div class="tab-content" id="mainTabContent">
        <div class="tab-pane fade show active" id="panel-produk" role="tabpanel">
            @include('products.partials.tab-produk')
        </div>
        <div class="tab-pane fade" id="panel-riwayat" role="tabpanel">
            @include('products.partials.tab-riwayat')
        </div>
        <div class="tab-pane fade" id="panel-pemetaan" role="tabpanel">
            @include('products.partials.tab-pemetaan')
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                // Restore active tab dari URL hash
                const hash = window.location.hash.replace('#', '');
                const tabMap = {
                    produk: 'tab-produk',
                    riwayat: 'tab-riwayat',
                    pemetaan: 'tab-pemetaan',
                };
                if (hash && tabMap[hash]) {
                    const trigger = document.getElementById(tabMap[hash]);
                    if (trigger) bootstrap.Tab.getOrCreateInstance(trigger).show();
                }

                document.querySelectorAll('#mainTab button[data-bs-toggle="tab"]').forEach(btn => {
                    btn.addEventListener('shown.bs.tab', function(e) {
                        const target = e.target.getAttribute('data-bs-target').replace('#panel-', '');
                        window.location.hash = target;
                    });
                });

                $(document).on('submit', '.confirm-delete', function(e) {
                    const msg = $(this).data('message') || 'Apakah Anda yakin?';
                    if (!confirm(msg)) e.preventDefault();
                });

                const pendingCount = {{ $pendingCount }};
                if (pendingCount > 0) {
                    setTimeout(function() {
                        const panel = document.getElementById('panel-riwayat');
                        if (panel && panel.classList.contains('show')) location.reload();
                    }, 10000);
                }
            });
        </script>
    @endpush
@endsection
