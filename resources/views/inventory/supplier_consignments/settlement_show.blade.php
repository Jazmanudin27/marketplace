@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header Banner (Pure Bootstrap 5 White Card) -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-success bg-opacity-10 text-success rounded-3 p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="bi bi-receipt fs-3"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <h4 class="fw-bold mb-0 text-dark">Bukti Setoran Supplier: {{ $settlement->settlement_number }}</h4>
                            <span class="badge bg-success rounded-pill px-3 py-1"><i class="bi bi-check-circle me-1"></i>Approved / Lunas</span>
                        </div>
                        <p class="text-muted small mb-0">Rincian bukti pembayaran setoran hasil penjualan barang titipan kepada supplier</p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" onclick="window.print()" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                        <i class="bi bi-printer me-1"></i> Cetak Bukti Setoran
                    </button>

                    <form action="{{ route('supplier_consignments.settlement.destroy', $settlement) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus bukti setoran ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3">
                            <i class="bi bi-trash me-1"></i> Hapus
                        </button>
                    </form>

                    <a href="{{ route('supplier_consignments.settlement.index') }}" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Details Card (Pure Bootstrap 5) -->
    <div class="row g-4 mb-4">
        <!-- Info Setoran -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-header bg-white py-3 border-bottom-0">
                    <h6 class="fw-bold mb-0 text-success text-uppercase small"><i class="bi bi-info-circle me-2"></i>DATA PEMBAYARAN SETORAN</h6>
                </div>
                <div class="card-body pt-0">
                    <table class="table table-borderless table-sm mb-0">
                        <tr>
                            <td class="text-muted small" style="width: 40%;">No. Setoran</td>
                            <td class="fw-bold text-dark">: {{ $settlement->settlement_number }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Tanggal Setoran</td>
                            <td class="fw-semibold text-dark">: {{ $settlement->settlement_date->format('d-m-Y') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Metode Pembayaran</td>
                            <td class="fw-bold text-dark">: 
                                @if($settlement->payment_method === 'transfer')
                                    Transfer Bank ({{ $settlement->bankAccount ? $settlement->bankAccount->bank_name . ' - ' . $settlement->bankAccount->account_number : 'Bank' }})
                                @else
                                    Kas / Tunai
                                @endif
                            </td>
                        </tr>
                        @if($settlement->reference_number)
                            <tr>
                                <td class="text-muted small">No. Ref Transfer</td>
                                <td class="font-monospace text-primary">: {{ $settlement->reference_number }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td class="text-muted small">Dicatat Oleh</td>
                            <td class="text-dark">: {{ $settlement->creator ? $settlement->creator->name : 'Sistem' }} ({{ $settlement->created_at->format('d/m/Y H:i') }})</td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Catatan</td>
                            <td class="text-dark">: {{ $settlement->notes ?: '-' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Info Supplier -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-header bg-white py-3 border-bottom-0">
                    <h6 class="fw-bold mb-0 text-primary text-uppercase small"><i class="bi bi-building me-2"></i>DATA SUPPLIER (PENERIMA)</h6>
                </div>
                <div class="card-body pt-0">
                    <table class="table table-borderless table-sm mb-0">
                        <tr>
                            <td class="text-muted small" style="width: 40%;">Nama Supplier</td>
                            <td class="fw-bold text-dark fs-6">: {{ $settlement->supplier ? $settlement->supplier->name : '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Kontak Person</td>
                            <td class="text-dark">: {{ $settlement->supplier ? ($settlement->supplier->contact_person ?: '-') : '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted small">No. HP / Telepon</td>
                            <td class="text-dark">: {{ $settlement->supplier ? ($settlement->supplier->phone ?: '-') : '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Alamat Supplier</td>
                            <td class="text-dark">: {{ $settlement->supplier ? ($settlement->supplier->address ?: '-') : '-' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Items Table Card (Pure Bootstrap 5) -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
        <div class="card-header bg-white py-3 border-bottom-0">
            <h6 class="fw-bold mb-0 text-dark text-uppercase small"><i class="bi bi-card-checklist text-success me-2"></i>RINCIAN BARANG YANG DISETORKAN</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase small fw-bold text-muted">
                        <tr>
                            <th class="ps-4 text-center" style="width: 50px;">NO</th>
                            <th style="width: 18%;">SKU</th>
                            <th style="width: 37%;">NAMA BARANG</th>
                            <th class="text-center" style="width: 12%;">QTY DISETOR</th>
                            <th class="text-end" style="width: 15%;">HARGA TITIP (HPP)</th>
                            <th class="text-end pe-4" style="width: 18%;">SUBTOTAL SETORAN</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $totalQty = 0;
                            $totalAmount = 0;
                        @endphp
                        @foreach($settlement->items as $index => $item)
                            @php
                                $subtotal = $item->qty_settled * $item->unit_cost_price;
                                $totalQty += $item->qty_settled;
                                $totalAmount += $subtotal;
                            @endphp
                            <tr>
                                <td class="ps-4 text-center text-muted fw-semibold">{{ $index + 1 }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border font-monospace">
                                        {{ $item->masterProduct ? $item->masterProduct->sku : '-' }}
                                    </span>
                                </td>
                                <td class="fw-bold text-dark">
                                    {{ $item->masterProduct ? $item->masterProduct->name : 'Produk Terhapus' }}
                                </td>
                                <td class="text-center fw-bold text-dark font-monospace">
                                    {{ number_format($item->qty_settled) }} PCS
                                </td>
                                <td class="text-end text-muted font-monospace">
                                    Rp {{ number_format($item->unit_cost_price, 0, ',', '.') }}
                                </td>
                                <td class="text-end pe-4 fw-bold text-success font-monospace">
                                    Rp {{ number_format($subtotal, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Summary Box (Right Aligned) -->
    <div class="row justify-content-end">
        <div class="col-md-5 col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 bg-white p-3">
                <div class="card-body">
                    <h6 class="fw-bold text-muted text-uppercase mb-3 small">
                        Rincian Total Setoran
                    </h6>

                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">Total Qty Disetorkan</span>
                        <span class="fw-bold text-dark">{{ number_format($totalQty) }} PCS</span>
                    </div>

                    <div class="pt-3 border-top d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-dark text-uppercase">Total Pembayaran</span>
                        <span class="fw-bold text-success fs-4 font-monospace">Rp {{ number_format($totalAmount, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
