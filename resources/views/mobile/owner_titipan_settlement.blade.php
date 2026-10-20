@extends('layouts.mobile')

@section('title', 'Setoran Titipan Konsinyasi')
@section('header-title', 'Setoran Titipan')

@section('content')
<div class="mb-3 d-flex justify-content-between align-items-center">
    <div>
        <a href="{{ route('mobile.owner') }}" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1">
            <i class="fas fa-arrow-left"></i> Dashboard
        </a>
        <h5 class="fw-bold mb-0 text-dark mt-1">Pembayaran Titipan</h5>
    </div>
    <a href="{{ route('mobile.owner.titipan_settlement.create') }}" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm d-flex align-items-center gap-1" style="background: linear-gradient(135deg, #4f46e5, #4338ca); border:none;">
        <i class="fas fa-plus"></i> Setor Baru
    </a>
</div>

<!-- KPI Summary Cards -->
<div class="row g-2 mb-3">
    <div class="col-6">
        <div class="glass-card p-3 h-100" style="background: #ffffff; border-radius: 14px; border: 1px solid rgba(0,0,0,0.06);">
            <div class="text-muted small text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">Total Disetor</div>
            <div class="fw-bold fs-6 text-primary mt-1">
                Rp {{ number_format($totalAmountPaid, 0, ',', '.') }}
            </div>
            <div class="text-muted mt-1" style="font-size: 0.7rem;">
                <i class="fas fa-money-bill-wave text-success me-1"></i> {{ $totalCount }} Transaksi
            </div>
        </div>
    </div>
    <div class="col-6">
        <div class="glass-card p-3 h-100" style="background: #ffffff; border-radius: 14px; border: 1px solid rgba(0,0,0,0.06);">
            <div class="text-muted small text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">Volume Barang</div>
            <div class="fw-bold fs-6 text-success mt-1">
                {{ number_format($totalQtySettled) }} <span class="fs-7 text-muted fw-normal">PCS</span>
            </div>
            <div class="text-muted mt-1" style="font-size: 0.7rem;">
                <i class="fas fa-boxes text-info me-1"></i> Produk Terjual
            </div>
        </div>
    </div>
</div>

<!-- Filter Box -->
<div class="glass-card p-3 mb-3" style="background: #ffffff; border-radius: 14px; border: 1px solid rgba(0,0,0,0.06);">
    <form method="GET" action="{{ route('mobile.owner.titipan_settlement') }}" class="d-flex flex-column gap-2">
        <div class="input-group input-group-sm">
            <span class="input-group-text bg-light border-0"><i class="fas fa-search text-muted"></i></span>
            <input type="text" name="search" class="form-control bg-light border-0" placeholder="Cari No. Setoran / Ref..." value="{{ request('search') }}">
        </div>
        <div class="d-flex gap-2">
            <select name="supplier_id" class="form-select form-select-sm bg-light border-0">
                <option value="">Semua Supplier</option>
                @foreach($suppliers as $s)
                    <option value="{{ $s->id }}" {{ request('supplier_id') == $s->id ? 'selected' : '' }}>
                        {{ $s->name }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-sm btn-dark px-3 rounded-3">Filter</button>
            @if(request()->hasAny(['search', 'supplier_id']))
                <a href="{{ route('mobile.owner.titipan_settlement') }}" class="btn btn-sm btn-outline-secondary rounded-3">Reset</a>
            @endif
        </div>
    </form>
</div>

<!-- Daftar Transaksi Setoran -->
<div class="d-flex flex-column gap-2 mb-4">
    @forelse($settlements as $settlement)
        <a href="{{ route('mobile.owner.titipan_settlement.show', $settlement->id) }}" class="text-decoration-none">
            <div class="glass-card p-3" style="background: #ffffff; border-radius: 14px; border: 1px solid rgba(0,0,0,0.06); transition: all 0.2s;">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="badge bg-primary bg-opacity-10 text-primary border font-monospace" style="font-size: 0.7rem;">
                            {{ $settlement->settlement_number }}
                        </span>
                        <div class="fw-bold text-dark mt-1" style="font-size: 0.9rem;">
                            {{ $settlement->supplier ? $settlement->supplier->name : 'Supplier' }}
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold text-success fs-6">
                            Rp {{ number_format($settlement->total_amount_paid, 0, ',', '.') }}
                        </div>
                        <span class="badge {{ $settlement->payment_method === 'transfer' ? 'bg-info bg-opacity-10 text-info border-info border-opacity-25' : 'bg-success bg-opacity-10 text-success border-success border-opacity-25' }} border" style="font-size: 0.65rem;">
                            {{ $settlement->payment_method === 'transfer' ? 'Transfer Bank' : 'Kas / Tunai' }}
                        </span>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center text-muted small pt-2 border-top" style="font-size: 0.72rem;">
                    <div>
                        <i class="far fa-calendar-alt me-1"></i> {{ $settlement->settlement_date ? $settlement->settlement_date->format('d/m/Y') : '-' }}
                        @if($settlement->bankAccount)
                            • <i class="fas fa-university me-0.5"></i> {{ $settlement->bankAccount->bank_name }}
                        @endif
                    </div>
                    <div class="fw-semibold text-secondary">
                        {{ number_format($settlement->total_qty_settled) }} pcs <i class="fas fa-chevron-right ms-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </a>
    @empty
        <div class="text-center py-5 glass-card" style="background: #ffffff; border-radius: 14px; border: 1px dashed #cbd5e1;">
            <i class="fas fa-hand-holding-usd text-muted opacity-50 fs-1 mb-2 d-block"></i>
            <div class="fw-semibold text-dark">Belum ada riwayat setoran</div>
            <div class="text-muted small mt-1">Gunakan tombol "Setor Baru" untuk menyetorkan hasil penjualan titipan.</div>
        </div>
    @endforelse
</div>

<!-- Pagination -->
@if($settlements->hasPages())
    <div class="d-flex justify-content-center mb-4">
        {{ $settlements->links('pagination::bootstrap-5') }}
    </div>
@endif

@endsection
