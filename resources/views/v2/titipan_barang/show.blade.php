@extends('v2.layouts.app')

@section('title', 'Detail Barang Titipan — ' . $consignment->reference_number)

@section('content')

{{-- ── Page Header ── --}}
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2 mb-0">
            <i class="bi bi-box-seam text-warning fs-5"></i> Detail Titipan: {{ $consignment->reference_number }}
        </h1>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('supplier_consignments.print_labels', $consignment) }}" target="_blank"
            class="btn btn-sm btn-outline-primary fw-semibold px-3">
            <i class="bi bi-upc-scan me-1"></i> Barcode Label
        </a>
        <button type="button" onclick="window.print()" class="btn btn-sm btn-outline-secondary fw-semibold px-3">
            <i class="bi bi-printer me-1"></i> Cetak Faktur
        </button>
        <a href="{{ route('supplier_consignments.edit', $consignment) }}"
            class="btn btn-sm fw-semibold text-dark px-3" style="background:#fbbf24; border:none;">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        <form action="{{ route('supplier_consignments.destroy', $consignment) }}" method="POST" class="d-inline"
            onsubmit="return confirm('Hapus transaksi {{ $consignment->reference_number }}? Stok akan dikurangi kembali.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm text-white fw-semibold px-3" style="background:#dc2626; border:none;">
                <i class="bi bi-trash me-1"></i> Hapus
            </button>
        </form>
        <a href="{{ route('supplier_consignments.index') }}" class="btn btn-sm text-white fw-semibold py-1.5 px-3" style="background:#64748b; border:none;">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>
</div>

{{-- ── Alert Messages ── --}}
@foreach(['success','error'] as $type)
    @if(session($type))
        <div class="alert alert-{{ $type === 'error' ? 'danger' : 'success' }} alert-dismissible fade show mb-3 border-0 shadow-sm" style="border-radius:10px;" role="alert">
            <i class="bi bi-{{ $type === 'error' ? 'exclamation-triangle' : 'check-circle' }} me-2"></i>
            {{ session($type) }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
@endforeach

<div class="row g-3">
    {{-- Kiri: Info Card --}}
    <div class="col-12 col-lg-3">
        {{-- Meta Dokumen --}}
        <div class="v2-card p-3 shadow-sm mb-3">
            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                <i class="bi bi-info-circle text-warning me-1.5"></i> Informasi Dokumen
            </h6>
            <div class="border rounded-3 overflow-hidden" style="font-size:0.78rem;">
                <div class="d-flex justify-content-between p-2 border-bottom bg-light">
                    <span class="text-muted">No. Referensi</span>
                    <strong class="font-monospace text-dark">{{ $consignment->reference_number }}</strong>
                </div>
                <div class="d-flex justify-content-between p-2 border-bottom">
                    <span class="text-muted">Tanggal</span>
                    <strong class="text-dark">{{ $consignment->consignment_date->format('d F Y') }}</strong>
                </div>
                <div class="d-flex justify-content-between p-2 border-bottom">
                    <span class="text-muted">Status</span>
                    @if($consignment->status === 'approved')
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0.5" style="font-size:0.65rem;">
                            <i class="bi bi-check-circle-fill me-1"></i>Approved
                        </span>
                    @else
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2 py-0.5" style="font-size:0.65rem;">
                            <i class="bi bi-hourglass-split me-1"></i>Pending
                        </span>
                    @endif
                </div>
                <div class="d-flex justify-content-between p-2 border-bottom">
                    <span class="text-muted">Supplier</span>
                    <strong class="text-dark text-end" style="max-width:60%;">{{ $consignment->supplier ? $consignment->supplier->name : '-' }}</strong>
                </div>
                @if($consignment->notes)
                <div class="p-2 border-bottom">
                    <span class="text-muted d-block mb-1">Catatan:</span>
                    <span class="text-dark">{{ $consignment->notes }}</span>
                </div>
                @endif
                <div class="d-flex justify-content-between p-2 border-bottom bg-warning-subtle">
                    <span class="fw-bold text-dark">Total HPP Modal</span>
                    <strong class="font-monospace text-warning-emphasis" style="font-size:0.88rem;">
                        Rp {{ number_format($consignment->total_amount_hpp, 0, ',', '.') }}
                    </strong>
                </div>
            </div>

            <div class="text-muted small mt-3" style="font-size:0.72rem;">
                <div><i class="bi bi-person me-1"></i> Dicatat: <strong>{{ $consignment->creator ? $consignment->creator->name : 'Sistem' }}</strong></div>
                <div class="ms-3 text-muted" style="font-size:0.65rem;">{{ $consignment->created_at->format('d/m/Y H:i') }}</div>
                @if($consignment->status === 'approved' && $consignment->approver)
                    <div class="mt-1"><i class="bi bi-check2-all me-1 text-success"></i> Disetujui: <strong>{{ $consignment->approver->name }}</strong></div>
                    <div class="ms-3 text-muted" style="font-size:0.65rem;">{{ $consignment->approved_at ? $consignment->approved_at->format('d/m/Y H:i') : '-' }}</div>
                @endif
            </div>
        </div>

        {{-- Data Supplier --}}
        @if($consignment->supplier)
        <div class="v2-card p-3 shadow-sm mb-3">
            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                <i class="bi bi-building text-warning me-1.5"></i> Data Supplier
            </h6>
            <div style="font-size:0.78rem;">
                <div class="fw-bold text-dark mb-1">{{ $consignment->supplier->name }}</div>
                @if($consignment->supplier->phone)
                    <div class="text-muted"><i class="bi bi-telephone me-1"></i>{{ $consignment->supplier->phone }}</div>
                @endif
                @if($consignment->supplier->contact_person)
                    <div class="text-muted"><i class="bi bi-person me-1"></i>{{ $consignment->supplier->contact_person }}</div>
                @endif
                @if($consignment->supplier->address)
                    <div class="text-muted mt-1"><i class="bi bi-geo-alt me-1"></i>{{ $consignment->supplier->address }}</div>
                @endif
            </div>
        </div>
        @endif

        {{-- Ringkasan Kalkulasi --}}
        @php
            $grandTotalQty = 0; $grandTotalSold = 0; $grandTotalRemaining = 0;
            $grandTotalHpp = 0; $grandTotalProfit = 0; $allDeductions = collect();
            foreach($consignment->items as $item) {
                $qTitip = (int)$item->qty_received;
                $qSold  = (int)($item->qty_sold ?? 0);
                $grandTotalQty       += $qTitip;
                $grandTotalSold      += $qSold;
                $grandTotalRemaining += max(0, $qTitip - $qSold);
                $grandTotalHpp       += $qTitip * $item->unit_cost_price;
                $grandTotalProfit    += $qTitip * ($item->unit_selling_price - $item->unit_cost_price);
                if ($item->deductions && $item->deductions->isNotEmpty()) {
                    $allDeductions = $allDeductions->concat($item->deductions);
                }
            }
        @endphp
        <div class="v2-card p-3 shadow-sm" style="font-size:0.8rem;">
            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                <i class="bi bi-calculator text-success me-1.5"></i> Ringkasan
            </h6>
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Total Qty Titipan</span>
                <strong class="text-dark">{{ number_format($grandTotalQty) }} PCS</strong>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Total Terjual</span>
                <strong class="text-info">{{ number_format($grandTotalSold) }} PCS</strong>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Sisa Stok Gudang</span>
                <strong class="text-warning">{{ number_format($grandTotalRemaining) }} PCS</strong>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Modal HPP</span>
                <strong class="text-dark font-monospace">Rp {{ number_format($grandTotalHpp, 0, ',', '.') }}</strong>
            </div>
            <div class="d-flex justify-content-between pt-2 border-top">
                <span class="fw-bold text-success">Potensi Profit</span>
                <strong class="text-success font-monospace">+Rp {{ number_format($grandTotalProfit, 0, ',', '.') }}</strong>
            </div>
        </div>
    </div>

    {{-- Kanan: Card List Item (Full Width Nama Barang & Sub-Metrics) --}}
    <div class="col-12 col-lg-9">
        <div class="v2-card shadow-sm h-100 d-flex flex-column overflow-hidden">
            <div class="px-3 py-2.5 border-bottom bg-light d-flex align-items-center justify-content-between">
                <span class="fw-bold text-secondary small text-uppercase" style="font-size:0.75rem; letter-spacing:0.04em;">
                    <i class="bi bi-boxes text-warning me-1.5 fs-6"></i>
                    Daftar Item Penerimaan — {{ $consignment->items->count() }} SKU
                </span>
                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2.5 py-1 fw-bold" style="font-size:0.75rem;">
                    Total HPP: Rp {{ number_format($grandTotalHpp, 0, ',', '.') }}
                </span>
            </div>

            <div class="p-3 d-flex flex-column gap-3 flex-grow-1" style="background: #f8fafc;">
                @foreach($consignment->items as $idx => $item)
                    @php
                        $qtyTitip     = (int) $item->qty_received;
                        $qtySold      = (int) ($item->qty_sold ?? 0);
                        $qtyRemaining = max(0, $qtyTitip - $qtySold);
                        $subtotalHpp  = $qtyTitip * $item->unit_cost_price;
                        $product      = $item->masterProduct;
                    @endphp
                    <div class="card border border-slate-200 shadow-sm rounded-3 overflow-hidden bg-white">
                        {{-- Top Header: Full Width Product Name & SKU --}}
                        <div class="p-3 border-bottom bg-light-subtle d-flex align-items-start justify-content-between gap-3">
                            <div class="d-flex align-items-start gap-2.5 flex-grow-1">
                                <div class="rounded-2 p-2 bg-primary-subtle text-primary fw-bold text-center d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; min-width: 36px;">
                                    <i class="bi bi-box-seam fs-6"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="fw-bold text-dark mb-1.5 lh-sm" style="font-size: 0.95rem; word-break: break-word;">
                                        {{ $product ? $product->name : 'Produk Terhapus' }}
                                    </h6>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span class="badge bg-secondary-subtle text-secondary border font-monospace px-2 py-0.5" style="font-size:0.72rem;">
                                            <i class="bi bi-barcode me-1"></i>SKU: {{ $product ? $product->sku : '-' }}
                                        </span>
                                        @if($product && $product->unit)
                                            <span class="badge bg-light text-muted border px-2 py-0.5" style="font-size:0.7rem;">
                                                Satuan: {{ $product->unit }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="text-end ps-2 flex-shrink-0">
                                <span class="text-muted d-block small" style="font-size:0.7rem;">Subtotal HPP</span>
                                <strong class="font-monospace text-warning-emphasis fw-bold fs-6">
                                    Rp {{ number_format($subtotalHpp, 0, ',', '.') }}
                                </strong>
                            </div>
                        </div>

                        {{-- Bottom Grid: Metrics Below Product Name --}}
                        <div class="p-3 bg-white">
                            <div class="row g-2.5 align-items-center" style="font-size: 0.8rem;">
                                <div class="col-6 col-md-3">
                                    <div class="p-2 rounded-2 bg-light border text-center">
                                        <span class="text-muted d-block" style="font-size: 0.68rem; font-weight: 600;">QTY TITIP</span>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1 fw-bold mt-1" style="font-size: 0.82rem;">
                                            {{ number_format($qtyTitip) }} PCS
                                        </span>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="p-2 rounded-2 bg-light border text-center">
                                        <span class="text-muted d-block" style="font-size: 0.68rem; font-weight: 600;">TERJUAL (SCAN)</span>
                                        @if($qtySold > 0)
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 fw-bold mt-1" style="font-size: 0.82rem;">
                                                <i class="bi bi-check-lg me-0.5"></i>{{ number_format($qtySold) }} PCS
                                            </span>
                                        @else
                                            <span class="text-muted fw-semibold d-block mt-1" style="font-size: 0.8rem;">0 PCS</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="p-2 rounded-2 bg-light border text-center">
                                        <span class="text-muted d-block" style="font-size: 0.68rem; font-weight: 600;">SISA GUDANG</span>
                                        <span class="badge {{ $qtyRemaining > 0 ? 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' : 'bg-light text-muted border' }} px-2.5 py-1 fw-bold mt-1" style="font-size: 0.82rem;">
                                            {{ number_format($qtyRemaining) }} PCS
                                        </span>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="p-2 rounded-2 bg-light border text-center">
                                        <span class="text-muted d-block" style="font-size: 0.68rem; font-weight: 600;">HARGA TITIP (HPP)</span>
                                        <span class="font-monospace fw-bold text-dark d-block mt-1" style="font-size: 0.85rem;">
                                            Rp {{ number_format($item->unit_cost_price, 0, ',', '.') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Grand Total Footer --}}
            <div class="p-3 bg-light border-top mt-auto">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3" style="font-size: 0.85rem;">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="fw-bold text-dark me-1">TOTAL AKUMULASI:</span>
                        <span class="badge bg-primary text-white px-2.5 py-1" style="font-size:0.75rem;">Titip: {{ number_format($grandTotalQty) }} PCS</span>
                        <span class="badge bg-success text-white px-2.5 py-1" style="font-size:0.75rem;">Terjual: {{ number_format($grandTotalSold) }} PCS</span>
                        <span class="badge bg-warning text-dark px-2.5 py-1" style="font-size:0.75rem;">Sisa: {{ number_format($grandTotalRemaining) }} PCS</span>
                    </div>
                    <div class="text-end">
                        <span class="text-muted small me-2">Grand Total HPP:</span>
                        <strong class="font-monospace text-warning-emphasis fs-5">
                            Rp {{ number_format($grandTotalHpp, 0, ',', '.') }}
                        </strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Riwayat Scan Kemas ── --}}
@if($allDeductions->isNotEmpty())
<div class="v2-card shadow-sm overflow-hidden mt-3">
    <div class="px-3 py-2.5 border-bottom bg-light d-flex align-items-center justify-content-between">
        <span class="fw-bold text-secondary small text-uppercase" style="font-size:0.73rem; letter-spacing:0.04em;">
            <i class="bi bi-qr-code-scan text-success me-1"></i>
            Riwayat Pengurangan Stok Saat Scan Kemas
        </span>
        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:0.7rem;">
            {{ $allDeductions->count() }} Kali Scan
        </span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:0.8rem;">
            <thead class="bg-light text-muted">
                <tr>
                    <th class="ps-3 py-2.5">WAKTU SCAN</th>
                    <th class="py-2.5">INVOICE / PESANAN</th>
                    <th class="py-2.5">PRODUK (SKU)</th>
                    <th class="text-center py-2.5">JUMLAH POTONG</th>
                    <th class="py-2.5">KODE SCAN</th>
                    <th class="pe-3 py-2.5">PETUGAS KEMAS</th>
                </tr>
            </thead>
            <tbody>
                @foreach($allDeductions->sortByDesc('created_at') as $ded)
                <tr>
                    <td class="ps-3 text-muted">{{ $ded->created_at->format('d/m/Y H:i:s') }}</td>
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
                        <div class="fw-semibold text-dark">{{ $ded->consignmentItem && $ded->consignmentItem->masterProduct ? $ded->consignmentItem->masterProduct->name : '-' }}</div>
                        <span class="badge bg-light text-secondary border font-monospace" style="font-size:0.65rem;">
                            {{ $ded->consignmentItem && $ded->consignmentItem->masterProduct ? $ded->consignmentItem->masterProduct->sku : '' }}
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1 fw-bold font-monospace">
                            -{{ $ded->quantity }} PCS
                        </span>
                    </td>
                    <td><code class="small text-dark">{{ $ded->scanned_barcode ?: '-' }}</code></td>
                    <td class="pe-3 text-muted">{{ $ded->user ? $ded->user->name : '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endsection
