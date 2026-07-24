@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm rounded-3 mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm rounded-3 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- MAIN DARK/EMERALD RECEIPT CONTAINER (Matching User Screenshot Layout) -->
    <div class="card border-0 shadow-lg rounded-4 overflow-hidden" style="background-color: #12181f; color: #e2e8f0;">
        
        <!-- 1. TOP HEADER BANNER (EMERALD GREEN ACCENT) -->
        <div class="card-header border-0 py-4 px-4" style="background: linear-gradient(135deg, #059669 0%, #047857 100%);">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-white bg-opacity-20 rounded-3 p-3 text-white d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px;">
                        <i class="bi bi-file-earmark-text-fill fs-3"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold text-white mb-0">Penerimaan Barang Konsinyasi</h3>
                        <p class="text-white text-opacity-75 mb-0 small">Detail rincian transaksi penerimaan barang titipan dari supplier ke gudang master</p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <button type="button" onclick="window.print()" class="btn btn-sm btn-dark bg-opacity-50 border-0 rounded-pill px-3 py-2 fw-semibold text-white">
                        <i class="bi bi-printer me-1"></i> Cetak Faktur
                    </button>

                    @if($consignment->status === 'pending')
                        <form action="{{ route('supplier_consignments.approve', $consignment) }}" method="POST" class="d-inline" onsubmit="return confirm('Setujui penerimaan barang titipan ini? Stok master produk akan otomatis bertambah.')">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-light text-success fw-bold rounded-pill px-4 py-2 shadow-sm">
                                <i class="bi bi-check-circle-fill me-1"></i> Setujui & Tambah Stok
                            </button>
                        </form>
                    @endif

                    <a href="{{ route('supplier_consignments.index') }}" class="btn btn-sm btn-light bg-opacity-20 text-white border-0 rounded-pill px-3 py-2 fw-semibold">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body p-4">
            <!-- 2. DATA TRANSAKSI & SUPPLIER (WITH STAMP BADGE) -->
            <div class="card border-0 rounded-4 p-4 mb-4 position-relative overflow-hidden" style="background-color: #1a232e; border: 1px solid #2a3646 !important;">
                <!-- Watermark Stamp Badge -->
                <div class="position-absolute end-0 top-50 translate-middle-y me-4 opacity-25 d-none d-md-block pointer-events-none text-center" style="transform: rotate(-15deg);">
                    @if($consignment->status === 'approved')
                        <div class="border border-4 border-success text-success fw-extrabold px-4 py-2 rounded-3 text-uppercase fs-3" style="letter-spacing: 3px;">
                            TERIMA APPROVED
                        </div>
                    @elseif($consignment->status === 'pending')
                        <div class="border border-4 border-warning text-warning fw-extrabold px-4 py-2 rounded-3 text-uppercase fs-3" style="letter-spacing: 3px;">
                            PENDING APPROVAL
                        </div>
                    @else
                        <div class="border border-4 border-secondary text-secondary fw-extrabold px-4 py-2 rounded-3 text-uppercase fs-3" style="letter-spacing: 3px;">
                            BATAL
                        </div>
                    @endif
                </div>

                <div class="row g-4">
                    <!-- Left: DATA FAKTUR / KONSINYASI -->
                    <div class="col-md-6 border-end border-secondary border-opacity-25 pe-md-4">
                        <h6 class="fw-bold text-primary mb-3 text-uppercase small" style="letter-spacing: 1px;">
                            <i class="bi bi-info-circle me-1"></i> DATA KONSINYASI
                        </h6>
                        <table class="table table-borderless table-sm mb-0 text-white-50 small">
                            <tr>
                                <td style="width: 40%;">No. Referensi</td>
                                <td class="text-white fw-bold">: {{ $consignment->reference_number }}</td>
                            </tr>
                            <tr>
                                <td>Tanggal Penerimaan</td>
                                <td class="text-white">: {{ $consignment->consignment_date->format('d-m-Y') }}</td>
                            </tr>
                            <tr>
                                <td>Status Penerimaan</td>
                                <td>: 
                                    @if($consignment->status === 'approved')
                                        <span class="badge bg-success text-white px-2 py-1">Approved</span>
                                    @elseif($consignment->status === 'pending')
                                        <span class="badge bg-warning text-dark px-2 py-1">Pending Approval</span>
                                    @else
                                        <span class="badge bg-secondary px-2 py-1">Batal</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td>Dibuat Oleh</td>
                                <td class="text-white">: {{ $consignment->creator ? $consignment->creator->name : 'Sistem' }} ({{ $consignment->created_at->format('d/m/Y H:i') }})</td>
                            </tr>
                            @if($consignment->status === 'approved')
                                <tr>
                                    <td>Disetujui Oleh</td>
                                    <td class="text-success fw-bold">: {{ $consignment->approver ? $consignment->approver->name : '-' }} ({{ $consignment->approved_at ? $consignment->approved_at->format('d/m/Y H:i') : '' }})</td>
                                </tr>
                            @endif
                            <tr>
                                <td>Keterangan / Catatan</td>
                                <td class="text-white">: {{ $consignment->notes ?: '-' }}</td>
                            </tr>
                        </table>
                    </div>

                    <!-- Right: DATA SUPPLIER -->
                    <div class="col-md-6 ps-md-4">
                        <h6 class="fw-bold text-success mb-3 text-uppercase small" style="letter-spacing: 1px;">
                            <i class="bi bi-building me-1"></i> DATA SUPPLIER (PEMILIK BARANG)
                        </h6>
                        <table class="table table-borderless table-sm mb-0 text-white-50 small">
                            <tr>
                                <td style="width: 40%;">Nama Supplier</td>
                                <td class="text-white fw-bold fs-6">: {{ $consignment->supplier ? $consignment->supplier->name : '-' }}</td>
                            </tr>
                            <tr>
                                <td>Kontak Person</td>
                                <td class="text-white">: {{ $consignment->supplier ? ($consignment->supplier->contact_person ?: '-') : '-' }}</td>
                            </tr>
                            <tr>
                                <td>No. HP / Telepon</td>
                                <td class="text-white">: {{ $consignment->supplier ? ($consignment->supplier->phone ?: '-') : '-' }}</td>
                            </tr>
                            <tr>
                                <td>Alamat Supplier</td>
                                <td class="text-white">: {{ $consignment->supplier ? ($consignment->supplier->address ?: '-') : '-' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 3. DAFTAR ITEM PENERIMAAN TABLE -->
            <div class="mb-4">
                <h6 class="fw-bold text-white mb-3 text-uppercase small" style="letter-spacing: 1px;">
                    <i class="bi bi-boxes text-primary me-2"></i> DAFTAR ITEM PENERIMAAN BARANG
                </h6>

                <div class="table-responsive rounded-4 overflow-hidden" style="border: 1px solid #2a3646;">
                    <table class="table align-middle mb-0" style="background-color: #1a232e; color: #cbd5e1;">
                        <thead style="background-color: #161e27; color: #94a3b8;" class="text-uppercase small fw-bold">
                            <tr>
                                <th class="ps-4 text-center" style="width: 50px;">NO</th>
                                <th style="width: 15%;">SKU</th>
                                <th style="width: 35%;">NAMA BARANG</th>
                                <th class="text-center" style="width: 8%;">SATUAN</th>
                                <th class="text-center" style="width: 10%;">QTY</th>
                                <th class="text-end" style="width: 12%;">HARGA TITIP (HPP)</th>
                                <th class="text-end" style="width: 12%;">HARGA JUAL TOKO</th>
                                <th class="text-end pe-4" style="width: 13%;">SUBTOTAL HPP</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y border-secondary border-opacity-10">
                            @php
                                $grandTotalQty = 0;
                                $grandTotalHpp = 0;
                                $grandTotalProfit = 0;
                            @endphp
                            @foreach($consignment->items as $index => $item)
                                @php
                                    $subtotalHpp = $item->qty_received * $item->unit_cost_price;
                                    $potentialProfit = $item->qty_received * ($item->unit_selling_price - $item->unit_cost_price);
                                    $grandTotalQty += $item->qty_received;
                                    $grandTotalHpp += $subtotalHpp;
                                    $grandTotalProfit += $potentialProfit;
                                @endphp
                                <tr style="border-bottom: 1px solid #232d3b;">
                                    <td class="ps-4 text-center text-muted fw-bold">{{ $index + 1 }}</td>
                                    <td>
                                        <span class="badge bg-dark text-info border border-info border-opacity-25 font-monospace">
                                            {{ $item->masterProduct ? $item->masterProduct->sku : '-' }}
                                        </span>
                                    </td>
                                    <td class="fw-bold text-white">
                                        {{ $item->masterProduct ? $item->masterProduct->name : 'Produk Terhapus' }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary bg-opacity-25 text-white-50 border border-secondary border-opacity-25">
                                            {{ $item->masterProduct && $item->masterProduct->unit ? strtoupper($item->masterProduct->unit) : 'PCS' }}
                                        </span>
                                    </td>
                                    <td class="text-center font-monospace fw-bold text-white fs-6">
                                        {{ number_format($item->qty_received) }}
                                    </td>
                                    <td class="text-end font-monospace text-white-50">
                                        Rp {{ number_format($item->unit_cost_price, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end font-monospace text-white-50">
                                        Rp {{ number_format($item->unit_selling_price, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end pe-4 font-monospace fw-bold text-white fs-6">
                                        Rp {{ number_format($subtotalHpp, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 4. BOTTOM SUMMARY SECTION (RINCIAN PERHITUNGAN - RIGHT ALIGNED) -->
            <div class="row justify-content-end">
                <div class="col-md-5 col-lg-4">
                    <div class="card border-0 rounded-4 p-4" style="background-color: #1a232e; border: 1px solid #2a3646 !important;">
                        <h6 class="fw-bold text-muted text-uppercase mb-3 small" style="letter-spacing: 1px;">
                            Rincian Perhitungan
                        </h6>

                        <div class="d-flex justify-content-between mb-2 small text-white-50">
                            <span>Total Barang Received</span>
                            <span class="fw-bold text-white">{{ number_format($grandTotalQty) }} PCS</span>
                        </div>

                        <div class="d-flex justify-content-between mb-2 small text-white-50">
                            <span>Subtotal Modal HPP</span>
                            <span class="fw-bold text-white">Rp {{ number_format($grandTotalHpp, 0, ',', '.') }}</span>
                        </div>

                        <div class="d-flex justify-content-between mb-3 small text-success">
                            <span>Potensi Profit Toko</span>
                            <span class="fw-bold">+Rp {{ number_format($grandTotalProfit, 0, ',', '.') }}</span>
                        </div>

                        <div class="pt-3 border-top border-secondary border-opacity-50 d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-white text-uppercase fs-6">Grand Total HPP</span>
                            <span class="fw-extrabold text-warning fs-4 font-monospace">Rp {{ number_format($grandTotalHpp, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
@media print {
    body {
        background-color: #fff !important;
        color: #000 !important;
    }
    #sidebar, .navbar, .btn, .alert {
        display: none !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
        background-color: #fff !important;
        color: #000 !important;
    }
}
</style>
@endsection
