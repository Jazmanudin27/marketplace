@extends('layouts.app')
@section('title', 'Data Pelanggan')
@section('page-title', 'Data Pelanggan (CRM)')

@section('content')
<div class="card dashboard-card">
    <div class="card-header-line" style="display:flex; justify-content:space-between; align-items:center;">
        <h3><i class="fas fa-users"></i> Daftar Pelanggan Setia</h3>
        <form action="{{ route('customers.index') }}" method="GET" class="d-flex" style="gap:0.5rem;">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari nama / username..." value="{{ $search }}">
            <button type="submit" class="btn-primary-sm"><i class="fas fa-search"></i> Cari</button>
        </form>
    </div>

    <table class="data-table mt-3">
        <thead>
            <tr>
                <th>Pelanggan</th>
                <th>Username Marketplace</th>
                <th>No. Telp</th>
                <th style="text-align:center;">Total Pesanan</th>
                <th style="text-align:right;">Total Belanja (LTV)</th>
                <th style="text-align:center;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($customers as $c)
            <tr>
                <td>
                    <div style="font-weight:600;">{{ $c->name }}</div>
                    @if($c->tags)
                        <span class="badge bg-info mt-1"><i class="fas fa-tag"></i> {{ $c->tags }}</span>
                    @endif
                    @if($c->orders_count >= 3)
                        <span class="badge bg-warning text-dark mt-1"><i class="fas fa-star"></i> Loyal Customer</span>
                    @endif
                </td>
                <td class="mono">{{ $c->marketplace_username ?? '-' }}</td>
                <td class="mono">{{ $c->phone ?? '-' }}</td>
                <td style="text-align:center;">
                    <span class="badge bg-primary rounded-pill">{{ $c->orders_count }}x</span>
                </td>
                <td class="mono fw-bold text-success" style="text-align:right;">
                    Rp {{ number_format($c->total_spent, 0, ',', '.') }}
                </td>
                <td style="text-align:center;">
                    <a href="{{ route('customers.show', $c->id) }}" class="btn-primary-sm" style="text-decoration:none;">
                        <i class="fas fa-eye"></i> Detail
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align:center; padding:2rem; color:var(--text-secondary);">
                    Belum ada data pelanggan. Pelanggan akan otomatis ditambahkan saat Anda menarik pesanan dari Marketplace.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-3">
        {{ $customers->withQueryString()->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection
