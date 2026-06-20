@extends('layouts.app')
@section('title', 'Data Pelanggan')
@section('page-title', 'Data Pelanggan')

@section('content')
    <div class="row">
        <div class="col-md-12">
            {{-- ── Filter Card ───────────────────────────────────────────── --}}
            <div class="dashboard-card mb-3 py-3">
                <form method="GET" action="{{ route('customers.index') }}">
                    <div class="row g-2 align-items-end">
                        {{-- Cari Pelanggan --}}
                        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                            <label class="form-label form-label-sm">
                                <i class="fas fa-search me-1"></i>Cari Pelanggan
                            </label>
                            <input type="text" name="search" class="form-control form-control-sm"
                                placeholder="Cari nama, username, atau HP..." value="{{ $search }}">
                        </div>

                        {{-- Loyalitas --}}
                        <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                            <label class="form-label form-label-sm">
                                <i class="fas fa-crown me-1"></i>Loyalitas
                            </label>
                            <select name="loyalty" class="form-select form-select-sm select2">
                                <option value="">-- Semua --</option>
                                <option value="loyal" {{ $loyalty === 'loyal' ? 'selected' : '' }}>Loyal Customer (>= 3)
                                </option>
                                <option value="regular" {{ $loyalty === 'regular' ? 'selected' : '' }}>Regular Customer
                                </option>
                            </select>
                        </div>

                        {{-- Saluran / Marketplace --}}
                        <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                            <label class="form-label form-label-sm">
                                <i class="fas fa-shopping-bag me-1"></i>Saluran / Marketplace
                            </label>
                            <select name="channel_id" class="form-select form-select-sm select2">
                                <option value="">-- Semua --</option>
                                @foreach ($channels as $ch)
                                    <option value="{{ $ch->id }}" {{ $channelId == $ch->id ? 'selected' : '' }}>
                                        {{ ucfirst($ch->name) }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Toko --}}
                        <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                            <label class="form-label form-label-sm">
                                <i class="fas fa-store me-1"></i>Toko
                            </label>
                            <select name="store_id" class="form-select form-select-sm select2">
                                <option value="">-- Semua Toko --</option>
                                @foreach ($stores as $st)
                                    <option value="{{ $st->id }}" {{ $storeId == $st->id ? 'selected' : '' }}>
                                        {{ $st->store_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Perusahaan / Toko (Super Admin) --}}
                        @if ($isSuperAdmin)
                            <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                                <label class="form-label form-label-sm">
                                    <i class="fas fa-building me-1"></i>Perusahaan
                                </label>
                                <select name="tenant_id" class="form-select form-select-sm select2">
                                    <option value="">-- Semua --</option>
                                    @foreach ($tenants as $t)
                                        <option value="{{ $t->id }}"
                                            {{ $tenantIdQuery == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        {{-- Button Actions --}}
                        <div class="col-12 col-sm-6 col-md-auto d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-search me-1"></i> Cari
                            </button>
                            @if ($search || $tag || $loyalty || $channelId || $storeId || $tenantIdQuery)
                                <a href="{{ route('customers.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-times me-1"></i> Reset
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            {{-- ── Table Card ────────────────────────────────────────────── --}}
            <div class="dashboard-card">
                {{-- ── Header ──────────────────────────────────────────── --}}
                <div class="card-header-line d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0"><i class="fas fa-users me-2 text-primary"></i>Daftar Pelanggan</h5>
                        <p class="text-muted mb-0 mt-1" style="font-size:0.75rem;">
                            Kelola profil dan riwayat transaksi pelanggan CRM
                        </p>
                    </div>
                </div>

                {{-- Alert --}}
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                {{-- ── Tabel ────────────────────────────────────────────── --}}
                <div class="table-responsive rounded border border-secondary border-opacity-10 mt-3">
                    <table class="table table-sm table-bordered table-premium-dark align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="text-center">#</th>
                                @if ($isSuperAdmin)
                                    <th>PERUSAHAAN / TOKO</th>
                                @endif
                                <th>PELANGGAN</th>
                                <th>USERNAME &amp; NO. HP</th>
                                <th>SALURAN &amp; TOKO</th>
                                <th class="text-end">PESANAN &amp; LTV</th>
                                <th class="text-center">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($customers as $index => $c)
                                <tr>
                                    {{-- Index # --}}
                                    <td class="text-center">
                                        <span
                                            class="badge rounded-circle bg-secondary bg-opacity-25 text-secondary d-inline-flex align-items-center justify-content-center"
                                            style="width:20px;height:20px;">
                                            {{ $customers->firstItem() + $index }}
                                        </span>
                                    </td>

                                    {{-- Tenant (Super Admin) --}}
                                    @if ($isSuperAdmin)
                                        <td>
                                            <span
                                                class="badge rounded-pill bg-primary bg-opacity-10 text-primary border border-primary border-opacity-10 small fw-medium">
                                                <i class="fas fa-building me-1"></i>
                                                {{ $c->tenant->name ?? '—' }}
                                            </span>
                                        </td>
                                    @endif

                                    {{-- Pelanggan --}}
                                    <td>
                                        <div class="lh-sm">
                                            <strong class="text-white small">{{ $c->name }}</strong>
                                            <div class="d-flex flex-wrap gap-1 mt-1">
                                                @if ($c->tags)
                                                    @foreach (explode(',', $c->tags) as $tagVal)
                                                        <span
                                                            class="badge bg-secondary-subtle text-secondary border border-secondary-subtle small px-1 py-0.5">
                                                            <i class="fas fa-tag me-1"></i>{{ trim($tagVal) }}
                                                        </span>
                                                    @endforeach
                                                @endif
                                                @if ($c->orders_count >= 3)
                                                    <span
                                                        class="badge bg-warning-subtle text-warning border border-warning-subtle small px-1 py-0.5">
                                                        <i class="fas fa-star me-1"></i>Loyal
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Username & No. HP --}}
                                    <td>
                                        <div class="lh-sm">
                                            @if ($c->marketplace_username)
                                                <div class="text-muted small">
                                                    <i class="fas fa-shopping-bag me-1"></i>{{ $c->marketplace_username }}
                                                </div>
                                            @endif
                                            @if ($c->phone)
                                                <div class="mt-1 small">
                                                    <a href="tel:{{ $c->phone }}"
                                                        class="text-decoration-none text-light text-opacity-75">
                                                        <i class="fas fa-phone me-1 text-secondary"></i>{{ $c->phone }}
                                                    </a>
                                                </div>
                                            @else
                                                @if (!$c->marketplace_username)
                                                    <span class="text-muted opacity-50">—</span>
                                                @endif
                                            @endif
                                        </div>
                                    </td>

                                    {{-- Saluran & Toko --}}
                                    <td>
                                        @php
                                            $uniqueChannels = $c->orders
                                                ->map(fn($o) => $o->store->channel ?? null)
                                                ->filter()
                                                ->unique('id');
                                            $uniqueStores = $c->orders
                                                ->map(fn($o) => $o->store ?? null)
                                                ->filter()
                                                ->unique('id');
                                        @endphp

                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach ($uniqueChannels as $ch)
                                                <span
                                                    class="badge bg-info-subtle text-info border border-info-subtle small px-1 py-0.5">
                                                    <i class="fas fa-shopping-bag me-1"></i>{{ ucfirst($ch->name) }}
                                                </span>
                                            @endforeach
                                        </div>
                                        <div class="d-flex flex-wrap gap-1 mt-1">
                                            @foreach ($uniqueStores as $st)
                                                <span
                                                    class="badge bg-secondary bg-opacity-10 text-light border border-secondary border-opacity-25 small px-1 py-0.5 text-truncate"
                                                    title="{{ $st->store_name }}">
                                                    <i class="fas fa-store me-1"></i>{{ $st->store_name }}
                                                </span>
                                            @endforeach
                                        </div>
                                        @if ($uniqueChannels->isEmpty() && $uniqueStores->isEmpty())
                                            <span class="text-muted opacity-50">—</span>
                                        @endif
                                    </td>

                                    {{-- Pesanan & LTV --}}
                                    <td class="text-end">
                                        <div class="lh-sm">
                                            <div>
                                                <span
                                                    class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill font-monospace small px-1 py-0.5">
                                                    {{ $c->orders_count }}x Order
                                                </span>
                                            </div>
                                            <div class="mt-1 text-success font-monospace fw-semibold small">
                                                Rp {{ number_format($c->total_spent, 0, ',', '.') }}
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Aksi --}}
                                    <td class="text-center">
                                        <a href="{{ route('customers.show', $c->id) }}"
                                            class="btn btn-info btn-action-sm text-white" title="Detail Profil"
                                            data-bs-toggle="tooltip">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $isSuperAdmin ? 7 : 6 }}" class="text-center py-5">
                                        <i class="fas fa-users fa-2x mb-3 d-block text-secondary opacity-25"></i>
                                        <p class="text-muted mb-0 small">Belum ada data pelanggan.</p>
                                        <p class="text-muted mb-0 small opacity-75">Pelanggan akan otomatis ditambahkan
                                            saat Anda menarik pesanan dari Marketplace.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- ── Pagination ───────────────────────────────────────── --}}
                @if ($customers->hasPages())
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <span class="text-muted small">
                            Halaman {{ $customers->currentPage() }} dari {{ $customers->lastPage() }}
                            &mdash; {{ $customers->total() }} total pelanggan
                        </span>
                        {{ $customers->withQueryString()->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
