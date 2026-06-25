@extends('layouts.app')
@section('title', 'Data Pelanggan')
@section('page-title', 'Data Pelanggan')

@section('content')
    <div class="row">
        <div class="col-md-12">
            {{-- ── Filter Card ───────────────────────────────────────────── --}}
            <div class="card border shadow-sm p-3 mb-3">
                <form method="GET" action="{{ route('customers.index') }}">
                    <div class="row g-2 align-items-end">
                        {{-- Cari Pelanggan --}}
                        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                            <label class="form-label small">
                                <i class="fas fa-search me-1"></i>Cari Pelanggan
                            </label>
                            <input type="text" name="search" class="form-control form-control-sm"
                                placeholder="Cari nama, username, atau HP..." value="{{ $search }}">
                        </div>

                        {{-- Loyalitas --}}
                        <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                            <label class="form-label small">
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
                            <label class="form-label small">
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
                            <label class="form-label small">
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
                                <label class="form-label small">
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
            <div class="card border shadow-sm overflow-hidden">
                {{-- ── Header ──────────────────────────────────────────── --}}
                <div class="card-header bg-info bg-opacity-10 d-flex justify-content-between align-items-center border-bottom py-2 px-3">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-users me-2 text-info"></i>Daftar Pelanggan</h6>
                        <small class="text-muted d-block">
                            Kelola profil dan riwayat transaksi pelanggan CRM
                        </small>
                    </div>
                </div>

                <div class="card-body p-3">
                    {{-- Alert --}}
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    {{-- ── Tabel ────────────────────────────────────────────── --}}
                    <div class="table-responsive rounded border mt-3">
                        <table class="table table-sm table-striped table-bordered align-middle mb-0">
                            <thead>
                                <tr class="small">
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
                                            <span class="badge bg-light text-secondary border small">
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
                                                <strong class="text-dark small">{{ $c->name }}</strong>
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
                                                            class="text-decoration-none text-secondary">
                                                            <i class="fas fa-phone me-1 text-secondary"></i>{{ $c->phone }}
                                                        </a>
                                                    </div>
                                                @else
                                                    @if (!$c->marketplace_username)
                                                        <span class="text-muted opacity-50 small">—</span>
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
                                                        class="badge bg-light text-secondary border small px-1 py-0.5 text-truncate"
                                                        title="{{ $st->store_name }}">
                                                        <i class="fas fa-store me-1 text-secondary"></i>{{ $st->store_name }}
                                                    </span>
                                                @endforeach
                                            </div>
                                            @if ($uniqueChannels->isEmpty() && $uniqueStores->isEmpty())
                                                <span class="text-muted opacity-50 small">—</span>
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
                                                class="btn btn-info btn-sm text-white" title="Detail Profil"
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
    </div>
@endsection
