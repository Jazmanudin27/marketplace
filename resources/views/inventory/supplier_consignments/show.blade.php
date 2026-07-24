@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h3 class="fw-bold mb-0">Penerimaan Barang Konsinyasi: {{ $consignment->reference_number }}</h3>
                @if($consignment->status === 'approved')
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1">Approved</span>
                @elseif($consignment->status === 'pending')
                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3 py-1">Pending Approval</span>
                @else
                    <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-1">Batal</span>
                @endif
            </div>
            <p class="text-muted small mb-0">Dibuat oleh: {{ $consignment->creator ? $consignment->creator->name : 'Sistem' }} pada {{ $consignment->created_at->format('d M Y, H:i') }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('supplier_consignments.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
            @if($consignment->status === 'pending')
                <form action="{{ route('supplier_consignments.approve', $consignment) }}" method="POST" onsubmit="return confirm('Setujui penerimaan barang titipan ini? Stok master produk akan otomatis bertambah.')">
                    @csrf
                    <button type="submit" class="btn btn-success shadow-sm">
                        <i class="bi bi-check-circle me-1"></i> Setujui & Tambah Stok
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">
        <!-- Header Info Card -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-building me-2 text-primary"></i>Informasi Supplier</h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless table-sm mb-0">
                        <tr>
                            <td class="text-muted small" style="width: 40%;">Nama Supplier:</td>
                            <td class="fw-bold text-dark">{{ $consignment->supplier ? $consignment->supplier->name : '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Kontak Person:</td>
                            <td>{{ $consignment->supplier ? $consignment->supplier->contact_person : '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Telepon:</td>
                            <td>{{ $consignment->supplier ? $consignment->supplier->phone : '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Tanggal Penerimaan:</td>
                            <td class="fw-semibold">{{ $consignment->consignment_date->format('d M Y') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Total Barang:</td>
                            <td class="fw-bold text-primary fs-6">{{ number_format($consignment->total_qty_received) }} PCS</td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Total Modal HPP:</td>
                            <td class="fw-bold text-dark fs-6">Rp {{ number_format($consignment->total_amount_hpp, 0, ',', '.') }}</td>
                        </tr>
                        @if($consignment->status === 'approved')
                            <tr>
                                <td class="text-muted small">Disetujui Oleh:</td>
                                <td class="text-success fw-semibold">{{ $consignment->approver ? $consignment->approver->name : '-' }} ({{ $consignment->approved_at ? $consignment->approved_at->format('d M Y H:i') : '' }})</td>
                            </tr>
                        @endif
                    </table>

                    @if($consignment->notes)
                        <div class="mt-3 p-3 bg-light rounded border">
                            <small class="fw-bold text-uppercase text-muted d-block mb-1">Catatan:</small>
                            <p class="mb-0 small text-secondary">{{ $consignment->notes }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Detail Item Table -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-list-check me-2 text-primary"></i>Rincian Barang Dititipkan</h6>
                    <a href="{{ route('supplier_consignments.stock_card', ['supplier_id' => $consignment->supplier_id]) }}" class="btn btn-sm btn-outline-info">
                        <i class="bi bi-card-checklist me-1"></i> Lihat Kartu Stok Supplier
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="bg-light text-muted small text-uppercase fw-semibold">
                                <tr>
                                    <th class="ps-3">Produk Master</th>
                                    <th class="text-center">Qty Terima</th>
                                    <th class="text-end">Harga Titip (HPP)</th>
                                    <th class="text-end">Harga Jual Toko</th>
                                    <th class="text-end">Total HPP</th>
                                    <th class="text-end pe-3">Potensi Profit</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $grandTotalHpp = 0;
                                    $grandTotalProfit = 0;
                                @endphp
                                @foreach($consignment->items as $item)
                                    @php
                                        $subtotalHpp = $item->qty_received * $item->unit_cost_price;
                                        $potentialProfit = $item->qty_received * ($item->unit_selling_price - $item->unit_cost_price);
                                        $grandTotalHpp += $subtotalHpp;
                                        $grandTotalProfit += $potentialProfit;
                                    @endphp
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-bold text-dark">{{ $item->masterProduct ? $item->masterProduct->name : 'Produk Terhapus' }}</div>
                                            <div class="small text-muted">SKU: {{ $item->masterProduct ? $item->masterProduct->sku : '-' }}</div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3">
                                                {{ number_format($item->qty_received) }} PCS
                                            </span>
                                        </td>
                                        <td class="text-end">Rp {{ number_format($item->unit_cost_price, 0, ',', '.') }}</td>
                                        <td class="text-end">Rp {{ number_format($item->unit_selling_price, 0, ',', '.') }}</td>
                                        <td class="text-end fw-semibold">Rp {{ number_format($subtotalHpp, 0, ',', '.') }}</td>
                                        <td class="text-end pe-3 fw-bold text-success">
                                            +Rp {{ number_format($potentialProfit, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-light fw-bold">
                                <tr>
                                    <td class="ps-3" colspan="4">TOTAL KESELURUHAN</td>
                                    <td class="text-end fs-6">Rp {{ number_format($grandTotalHpp, 0, ',', '.') }}</td>
                                    <td class="text-end pe-3 fs-6 text-success">+Rp {{ number_format($grandTotalProfit, 0, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
