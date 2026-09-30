@extends('v2.layouts.app')

@section('title', 'Detail Barang Titipan V2')

@section('content')
<div class="container-fluid py-4">
    <!-- Notifications -->
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

    <!-- 1. HEADER BANNER (Pure Bootstrap 5 - White Card with Primary Accent) -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="bi bi-file-earmark-text fs-3"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <h4 class="fw-bold mb-0 text-dark">Penerimaan Barang Konsinyasi: {{ $consignment->reference_number }}</h4>
                            <span class="badge bg-success rounded-pill px-3 py-1"><i class="bi bi-check-circle me-1"></i>Selesai / Stok Bertambah</span>
                        </div>
                        <p class="text-muted small mb-0">Detail rincian penerimaan barang titipan dari supplier ke gudang master</p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <a href="{{ route('supplier_consignments.print_labels', $consignment) }}" target="_blank" class="btn btn-primary btn-sm rounded-pill px-3 fw-bold shadow-sm">
                        <i class="bi bi-upc-scan me-1"></i> Cetak Barcode Produk
                    </a>

                    <button type="button" onclick="window.print()" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                        <i class="bi bi-printer me-1"></i> Cetak Faktur
                    </button>

                    <a href="{{ route('supplier_consignments.edit', $consignment) }}" class="btn btn-warning text-dark btn-sm rounded-pill px-3 fw-bold">
                        <i class="bi bi-pencil me-1"></i> Edit Faktur
                    </a>

                    <form action="{{ route('supplier_consignments.destroy', $consignment) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus transaksi penerimaan konsinyasi {{ $consignment->reference_number }}? Stok produk master akan dikurangi kembali.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3">
                            <i class="bi bi-trash me-1"></i> Hapus
                        </button>
                    </form>

                    <a href="{{ route('supplier_consignments.index') }}" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. DATA KONSINYASI & SUPPLIER (Pure Bootstrap 5 Cards) -->
    <div class="row g-4 mb-4">
        <!-- Data Konsinyasi -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-header bg-white py-3 border-bottom-0">
                    <h6 class="fw-bold mb-0 text-primary text-uppercase small"><i class="bi bi-info-circle me-2"></i>DATA KONSINYASI / FAKTUR</h6>
                </div>
                <div class="card-body pt-0">
                    <table class="table table-borderless table-sm mb-0">
                        <tr>
                            <td class="text-muted small" style="width: 40%;">No. Referensi</td>
                            <td class="fw-bold text-dark">: {{ $consignment->reference_number }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Tanggal Penerimaan</td>
                            <td class="fw-semibold text-dark">: {{ $consignment->consignment_date->format('d-m-Y') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Status</td>
                            <td>: 
                                @if($consignment->status === 'approved')
                                    <span class="badge bg-success text-white">Approved</span>
                                @elseif($consignment->status === 'pending')
                                    <span class="badge bg-warning text-dark">Pending Approval</span>
                                @else
                                    <span class="badge bg-secondary">Batal</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Dibuat Oleh</td>
                            <td class="text-dark">: {{ $consignment->creator ? $consignment->creator->name : 'Sistem' }} ({{ $consignment->created_at->format('d/m/Y H:i') }})</td>
                        </tr>
                        @if($consignment->status === 'approved')
                            <tr>
                                <td class="text-muted small">Disetujui Oleh</td>
                                <td class="text-success fw-bold">: {{ $consignment->approver ? $consignment->approver->name : '-' }} ({{ $consignment->approved_at ? $consignment->approved_at->format('d/m/Y H:i') : '' }})</td>
                            </tr>
                        @endif
                        <tr>
                            <td class="text-muted small">Keterangan / Catatan</td>
                            <td class="text-dark">: {{ $consignment->notes ?: '-' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Data Supplier -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-header bg-white py-3 border-bottom-0">
                    <h6 class="fw-bold mb-0 text-success text-uppercase small"><i class="bi bi-building me-2"></i>DATA SUPPLIER (PEMILIK BARANG)</h6>
                </div>
                <div class="card-body pt-0">
                    <table class="table table-borderless table-sm mb-0">
                        <tr>
                            <td class="text-muted small" style="width: 40%;">Nama Supplier</td>
                            <td class="fw-bold text-dark fs-6">: {{ $consignment->supplier ? $consignment->supplier->name : '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Kontak Person</td>
                            <td class="text-dark">: {{ $consignment->supplier ? ($consignment->supplier->contact_person ?: '-') : '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted small">No. HP / Telepon</td>
                            <td class="text-dark">: {{ $consignment->supplier ? ($consignment->supplier->phone ?: '-') : '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Alamat Supplier</td>
                            <td class="text-dark">: {{ $consignment->supplier ? ($consignment->supplier->address ?: '-') : '-' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. DAFTAR ITEM PENERIMAAN BARANG (Pure Bootstrap 5 Table) -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
        <div class="card-header bg-white py-3 border-bottom-0">
            <h6 class="fw-bold mb-0 text-dark text-uppercase small"><i class="bi bi-boxes text-primary me-2"></i>DAFTAR ITEM PENERIMAAN BARANG</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase small fw-bold text-muted">
                        <tr>
                            <th class="ps-4 text-center" style="width: 40px;">NO</th>
                            <th style="width: 14%;">SKU</th>
                            <th style="width: 26%;">NAMA BARANG</th>
                            <th class="text-center" style="width: 10%;">QTY TITIP</th>
                            <th class="text-center" style="width: 12%;">TERJUAL (SCAN KEMAS)</th>
                            <th class="text-center" style="width: 11%;">SISA STOK</th>
                            <th class="text-end" style="width: 13%;">HARGA TITIP</th>
                            <th class="text-end pe-4" style="width: 14%;">SUBTOTAL HPP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $grandTotalQty = 0;
                            $grandTotalSold = 0;
                            $grandTotalRemaining = 0;
                            $grandTotalHpp = 0;
                            $grandTotalProfit = 0;
                            $allDeductions = collect();
                        @endphp
                        @foreach($consignment->items as $index => $item)
                            @php
                                $qtyTitip = (int) $item->qty_received;
                                $qtySold = (int) ($item->qty_sold ?? 0);
                                $qtyRemaining = max(0, $qtyTitip - $qtySold);
                                $subtotalHpp = $qtyTitip * $item->unit_cost_price;
                                $potentialProfit = $qtyTitip * ($item->unit_selling_price - $item->unit_cost_price);
                                
                                $grandTotalQty += $qtyTitip;
                                $grandTotalSold += $qtySold;
                                $grandTotalRemaining += $qtyRemaining;
                                $grandTotalHpp += $subtotalHpp;
                                $grandTotalProfit += $potentialProfit;

                                if ($item->deductions && $item->deductions->isNotEmpty()) {
                                    $allDeductions = $allDeductions->concat($item->deductions);
                                }
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
                                <td class="text-center">
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2.5 py-1 font-monospace fs-7">
                                        {{ number_format($qtyTitip) }} PCS
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if($qtySold > 0)
                                        <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-25 px-2.5 py-1 font-monospace fs-7">
                                            <i class="bi bi-box-seam me-1"></i>{{ number_format($qtySold) }} PCS
                                        </span>
                                    @else
                                        <span class="text-muted small font-monospace">0 PCS</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $qtyRemaining > 0 ? 'bg-warning bg-opacity-15 text-dark border border-warning' : 'bg-secondary bg-opacity-10 text-muted' }} px-2.5 py-1 font-monospace fs-7">
                                        {{ number_format($qtyRemaining) }} PCS
                                    </span>
                                </td>
                                <td class="text-end text-muted">
                                    Rp {{ number_format($item->unit_cost_price, 0, ',', '.') }}
                                </td>
                                <td class="text-end pe-4 fw-bold text-dark">
                                    Rp {{ number_format($subtotalHpp, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($allDeductions->isNotEmpty())
        <!-- Riwayat Potongan Stok Saat Scan Kemas -->
        <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-success text-uppercase small">
                    <i class="bi bi-qr-code-scan me-2"></i>RIWAYAT PENGURANGAN STOK SAAT SCAN KEMAS PESANAN
                </h6>
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1">
                    {{ $allDeductions->count() }} Kali Terpotong Scan
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.85rem;">
                        <thead class="table-light text-muted small">
                            <tr>
                                <th class="ps-4" style="width: 50px;">NO</th>
                                <th>WAKTU SCAN</th>
                                <th>INVOICE / NO PESANAN</th>
                                <th>PRODUK</th>
                                <th class="text-center">JUMLAH POTONG</th>
                                <th>KODE SCAN</th>
                                <th>PETUGAS KEMAS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($allDeductions->sortByDesc('created_at') as $idx => $ded)
                                <tr>
                                    <td class="ps-4 text-muted">{{ $idx + 1 }}</td>
                                    <td class="text-muted">{{ $ded->created_at->format('d/m/Y H:i:s') }}</td>
                                    <td>
                                        @if($ded->order)
                                            <a href="{{ route('orders.show', $ded->order_id) }}" class="fw-bold text-primary text-decoration-none" target="_blank">
                                                {{ $ded->order->invoice_number ?: $ded->order->order_marketplace_id }}
                                            </a>
                                            <small class="text-muted d-block">{{ $ded->order->store ? $ded->order->store->store_name : '' }}</small>
                                        @else
                                            <span class="text-muted">Order #{{ $ded->order_id }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $ded->consignmentItem && $ded->consignmentItem->masterProduct ? $ded->consignmentItem->masterProduct->name : '-' }}</strong>
                                        <small class="text-muted d-block font-monospace">{{ $ded->consignmentItem && $ded->consignmentItem->masterProduct ? $ded->consignmentItem->masterProduct->sku : '' }}</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 font-monospace">
                                            -{{ $ded->quantity }} PCS
                                        </span>
                                    </td>
                                    <td>
                                        <code class="small text-dark">{{ $ded->scanned_barcode ?: '-' }}</code>
                                    </td>
                                    <td class="text-muted">
                                        {{ $ded->user ? $ded->user->name : '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <!-- 4. RINCIAN PERHITUNGAN (Pure Bootstrap 5 Card - Right Aligned) -->
    <div class="row justify-content-end">
        <div class="col-md-5 col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 bg-white p-3">
                <div class="card-body">
                    <h6 class="fw-bold text-muted text-uppercase mb-3 small">
                        Rincian Perhitungan
                    </h6>

                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">Total Barang Received</span>
                        <span class="fw-bold text-dark">{{ number_format($grandTotalQty) }} PCS</span>
                    </div>

                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">Subtotal Modal HPP</span>
                        <span class="fw-bold text-dark">Rp {{ number_format($grandTotalHpp, 0, ',', '.') }}</span>
                    </div>

                    <div class="d-flex justify-content-between mb-3 small text-success">
                        <span>Potensi Profit Toko</span>
                        <span class="fw-bold">+Rp {{ number_format($grandTotalProfit, 0, ',', '.') }}</span>
                    </div>

                    <div class="pt-3 border-top d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-dark text-uppercase">Grand Total HPP</span>
                        <span class="fw-bold text-primary fs-5">Rp {{ number_format($grandTotalHpp, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
