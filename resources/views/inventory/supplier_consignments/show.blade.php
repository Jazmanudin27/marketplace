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

    {{-- Kanan: Tabel Item --}}
    <div class="col-12 col-lg-9">
        <div class="v2-card shadow-sm h-100 d-flex flex-column overflow-hidden">
            <div class="px-3 py-2.5 border-bottom bg-light d-flex align-items-center justify-content-between">
                <span class="fw-bold text-secondary small text-uppercase" style="font-size:0.73rem; letter-spacing:0.04em;">
                    <i class="bi bi-boxes text-warning me-1"></i>
                    Daftar Item Penerimaan — {{ $consignment->items->count() }} SKU
                </span>
            </div>
            <div class="table-responsive flex-grow-1">
                <table class="table table-hover align-middle mb-0" style="font-size:0.82rem;">
                    <thead class="bg-light text-muted">
                        <tr>
                            <th class="ps-3 py-2.5">BARANG / SKU</th>
                            <th class="text-center py-2.5">QTY TITIP</th>
                            <th class="text-center py-2.5">TERJUAL (SCAN)</th>
                            <th class="text-center py-2.5">SISA GUDANG</th>
                            <th class="text-end py-2.5">HARGA TITIP (HPP)</th>
                            <th class="text-end pe-3 py-2.5">SUBTOTAL HPP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($consignment->items as $idx => $item)
                            @php
                                $qtyTitip     = (int) $item->qty_received;
                                $qtySold      = (int) ($item->qty_sold ?? 0);
                                $qtyRemaining = max(0, $qtyTitip - $qtySold);
                                $subtotalHpp  = $qtyTitip * $item->unit_cost_price;
                            @endphp
                            <tr>
                                <td class="ps-3 py-2.5">
                                    <div class="fw-semibold text-dark">{{ $item->masterProduct ? $item->masterProduct->name : 'Produk Terhapus' }}</div>
                                    <span class="badge bg-light text-secondary border font-monospace" style="font-size:0.66rem;">
                                        {{ $item->masterProduct ? $item->masterProduct->sku : '-' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1 fw-bold" style="font-size:0.78rem;">
                                        {{ number_format($qtyTitip) }} PCS
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if($qtySold > 0)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 fw-bold" style="font-size:0.78rem;">
                                            <i class="bi bi-check me-1"></i>{{ number_format($qtySold) }} PCS
                                        </span>
                                    @else
                                        <span class="text-muted small">— 0 PCS</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $qtyRemaining > 0 ? 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' : 'bg-light text-muted border' }} px-2.5 py-1 fw-bold" style="font-size:0.78rem;">
                                        {{ number_format($qtyRemaining) }} PCS
                                    </span>
                                </td>
                                <td class="text-end font-monospace text-muted" style="font-size:0.8rem;">
                                    Rp {{ number_format($item->unit_cost_price, 0, ',', '.') }}
                                </td>
                                <td class="text-end pe-3 font-monospace fw-bold text-dark" style="font-size:0.8rem;">
                                    Rp {{ number_format($subtotalHpp, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-warning-subtle">
                        <tr class="fw-bold">
                            <td class="ps-3 py-2.5 text-dark">TOTAL</td>
                            <td class="text-center text-primary">{{ number_format($grandTotalQty) }} PCS</td>
                            <td class="text-center text-success">{{ number_format($grandTotalSold) }} PCS</td>
                            <td class="text-center text-warning-emphasis">{{ number_format($grandTotalRemaining) }} PCS</td>
                            <td></td>
                            <td class="text-end pe-3 font-monospace text-dark" style="font-size:0.88rem;">
                                Rp {{ number_format($grandTotalHpp, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
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
