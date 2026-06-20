@extends('layouts.app')
@section('title', 'Master Produk')
@section('page-title', 'Master Produk')

@section('content')

    {{-- ── Alert Global ──────────────────────────────────────────── --}}
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

    @push('styles')
        <style>
            #mainTab .nav-link {
                color: #8a99ad;
                transition: all 0.2s ease;
            }

            #mainTab .nav-link:hover {
                color: #ffffff;
            }

            #mainTab .nav-link.active {
                color: #ffffff !important;
                background-color: #3b82f6 !important;
            }
        </style>
    @endpush

    <ul class="nav nav-pills gap-1 p-1 d-inline-flex border border-white border-opacity-10 rounded-pill mb-4 bg-black bg-opacity-25"
        id="mainTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active rounded-pill d-flex align-items-center gap-2" id="tab-produk"
                data-bs-toggle="tab" data-bs-target="#panel-produk" type="button" role="tab"
                style="font-size: 0.8rem; padding: 0.5rem 1.2rem;">
                <i class="fas fa-box-open"></i> Master Produk
                <span class="badge bg-secondary">{{ $products->total() }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill d-flex align-items-center gap-2" id="tab-riwayat" data-bs-toggle="tab"
                data-bs-target="#panel-riwayat" type="button" role="tab"
                style="font-size: 0.8rem; padding: 0.5rem 1.2rem;">
                <i class="fas fa-history"></i> Riwayat Publikasi
                @if ($pendingCount > 0)
                    <span class="badge bg-warning text-dark">{{ $pendingCount }}</span>
                @else
                    <span class="badge bg-secondary">{{ $publicationLogs->count() }}</span>
                @endif
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill d-flex align-items-center gap-2" id="tab-pemetaan" data-bs-toggle="tab"
                data-bs-target="#panel-pemetaan" type="button" role="tab"
                style="font-size: 0.8rem; padding: 0.5rem 1.2rem;">
                <i class="fas fa-map-signs"></i> Pemetaan Kategori
                <span class="badge bg-secondary">{{ $categoryMappings->count() }}</span>
            </button>
        </li>
    </ul>

    {{-- ── Tab Content ───────────────────────────────────────────── --}}
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

    </div>{{-- end tab-content --}}

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

                // Simpan tab aktif ke URL hash
                document.querySelectorAll('#mainTab button[data-bs-toggle="tab"]').forEach(btn => {
                    btn.addEventListener('shown.bs.tab', function(e) {
                        const target = e.target.getAttribute('data-bs-target').replace('#panel-', '');
                        window.location.hash = target;
                    });
                });

                // Confirm delete (pemetaan)
                $(document).on('submit', '.confirm-delete', function(e) {
                    const msg = $(this).data('message') || 'Apakah Anda yakin?';
                    if (!confirm(msg)) e.preventDefault();
                });

                // Auto-refresh tab riwayat jika ada job yang masih berjalan
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
