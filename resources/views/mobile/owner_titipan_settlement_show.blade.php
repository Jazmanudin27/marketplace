@extends('layouts.mobile')

@section('title', 'Bukti Setoran Titipan')
@section('header-title', 'Detail Setoran')

@section('content')
<div class="mb-3">
    <a href="{{ route('mobile.owner.titipan_settlement') }}" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1 mb-1">
        <i class="fas fa-arrow-left"></i> Kembali ke Daftar
    </a>
    <div class="d-flex justify-content-between align-items-center">
        <h5 class="fw-bold text-dark mb-0">Bukti Pembayaran Setoran</h5>
        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-1">
            <i class="fas fa-check-circle me-1"></i> APPROVED
        </span>
    </div>
</div>

<!-- Receipt Header Card -->
<div class="glass-card p-3 mb-3" style="background: #ffffff; border-radius: 14px; border: 1px solid rgba(0,0,0,0.06);">
    <div class="text-center py-2 border-bottom mb-3">
        <div class="text-muted small text-uppercase fw-bold" style="font-size: 0.65rem;">Nomor Transaksi Setoran</div>
        <div class="fw-bold fs-5 text-primary font-monospace mt-0.5">{{ $settlement->settlement_number }}</div>
        <div class="text-muted small">{{ $settlement->settlement_date ? $settlement->settlement_date->format('d F Y') : '-' }}</div>
    </div>

    <div class="d-flex flex-column gap-2" style="font-size: 0.8rem;">
        <div class="d-flex justify-content-between">
            <span class="text-muted">Supplier:</span>
            <span class="fw-bold text-dark">{{ $settlement->supplier ? $settlement->supplier->name : '-' }}</span>
        </div>
        <div class="d-flex justify-content-between">
            <span class="text-muted">Metode Bayar:</span>
            <span class="badge {{ $settlement->payment_method === 'transfer' ? 'bg-info bg-opacity-10 text-info' : 'bg-success bg-opacity-10 text-success' }} border">
                {{ $settlement->payment_method === 'transfer' ? 'Transfer Bank' : 'Kas / Tunai' }}
            </span>
        </div>
        @if($settlement->bankAccount)
            <div class="d-flex justify-content-between">
                <span class="text-muted">Rekening Sumber:</span>
                <span class="fw-semibold text-dark">{{ $settlement->bankAccount->bank_name }} ({{ $settlement->bankAccount->account_number }})</span>
            </div>
        @endif
        @if($settlement->reference_number)
            <div class="d-flex justify-content-between">
                <span class="text-muted">No. Referensi:</span>
                <span class="font-monospace text-dark">{{ $settlement->reference_number }}</span>
            </div>
        @endif
        <div class="d-flex justify-content-between">
            <span class="text-muted">Dicatat Oleh:</span>
            <span class="text-dark">{{ $settlement->creator ? $settlement->creator->name : '-' }} ({{ $settlement->created_at->format('H:i') }})</span>
        </div>
        @if($settlement->notes)
            <div class="pt-2 border-top">
                <span class="text-muted d-block small">Catatan:</span>
                <span class="text-dark small">{{ $settlement->notes }}</span>
            </div>
        @endif
    </div>
</div>

<!-- Item Breakdown Card -->
<div class="glass-card p-3 mb-4" style="background: #ffffff; border-radius: 14px; border: 1px solid rgba(0,0,0,0.06);">
    <h6 class="fw-bold text-dark mb-3" style="font-size: 0.85rem;">
        <i class="fas fa-list-check text-primary me-1"></i> Rincian Produk yang Disetor
    </h6>

    <div class="d-flex flex-column gap-2 mb-3">
        @foreach($settlement->items as $idx => $sItem)
            <div class="p-2.5 rounded-3 bg-light border-0">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="fw-bold text-dark" style="font-size: 0.82rem;">
                            {{ $sItem->masterProduct ? $sItem->masterProduct->name : 'Produk' }}
                        </div>
                        <small class="text-muted font-monospace" style="font-size: 0.7rem;">
                            SKU: {{ $sItem->masterProduct ? $sItem->masterProduct->sku : '-' }}
                        </small>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold text-dark" style="font-size: 0.85rem;">
                            Rp {{ number_format($sItem->subtotal, 0, ',', '.') }}
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-between text-muted small mt-1 pt-1 border-top" style="font-size: 0.72rem;">
                    <span>Qty Disetor: <strong>{{ $sItem->qty_settled }} pcs</strong></span>
                    <span>@ Rp {{ number_format($sItem->unit_cost_price, 0, ',', '.') }}</span>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Total Bill -->
    <div class="p-3 rounded-3 bg-success bg-opacity-10 border border-success border-opacity-25 d-flex justify-content-between align-items-center">
        <div>
            <div class="text-success small fw-bold text-uppercase" style="font-size: 0.68rem;">Total Pembayaran</div>
            <div class="fw-bold fs-5 text-success">
                Rp {{ number_format($settlement->total_amount_paid, 0, ',', '.') }}
            </div>
        </div>
        <div class="text-end text-muted small">
            Total Item: <strong>{{ number_format($settlement->total_qty_settled) }}</strong> pcs
        </div>
    </div>
</div>

<div class="d-flex gap-2 mb-4">
    <a href="{{ route('mobile.owner.titipan_settlement') }}" class="btn btn-outline-secondary w-100 py-2 rounded-pill fw-bold">
        <i class="fas fa-arrow-left me-1"></i> Kembali
    </a>
    <a href="{{ route('mobile.owner.titipan_settlement.create') }}" class="btn btn-primary w-100 py-2 rounded-pill fw-bold" style="background: linear-gradient(135deg, #4f46e5, #4338ca); border:none;">
        <i class="fas fa-plus me-1"></i> Setor Baru
    </a>
</div>

@endsection
