@extends('layouts.app')
@section('title', 'Detail Penerimaan — ' . $goodsReceipt->receipt_number)
@section('page-title', 'Detail Penerimaan Barang')

@section('content')
<div class="row g-3">
    {{-- Kiri: Info Ringkasan --}}
    <div class="col-md-4">
        {{-- Status Card --}}
        <div class="card border-0 shadow-sm rounded-3 bg-white mb-3">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="fas fa-truck text-success me-2"></i>Status Penerimaan
                    </h6>
                    <span class="badge bg-{{ $goodsReceipt->status_badge }} py-2 px-3 small text-uppercase">
                        {{ $goodsReceipt->status_label }}
                    </span>
                </div>

                @if($goodsReceipt->status === 'pending')
                    <div class="alert alert-warning py-2 px-3 small mb-0">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        Barang belum masuk ke stok. Menunggu persetujuan (approval) di halaman utama.
                    </div>
                @else
                    <div class="alert alert-success py-2 px-3 small mb-0">
                        <i class="fas fa-check-circle me-1"></i>
                        Stok telah berhasil dimasukkan ke departemen.
                    </div>
                @endif
            </div>
        </div>

        {{-- Detail Card --}}
        <div class="card border-0 shadow-sm rounded-3 bg-white mb-3">
            <div class="card-body p-3">
                <h6 class="fw-bold text-dark mb-3 small"><i class="fas fa-info-circle me-2 text-secondary"></i>Informasi Dokumen</h6>
                <div class="border rounded-2 overflow-hidden mb-3">
                    <div class="d-flex justify-content-between px-3 py-2 border-bottom" style="background:#f8fafc">
                        <span class="text-muted small">No. Penerimaan</span>
                        <span class="font-monospace fw-bold text-dark small">{{ $goodsReceipt->receipt_number }}</span>
                    </div>
                    <div class="d-flex justify-content-between px-3 py-2 border-bottom">
                        <span class="text-muted small">Tanggal</span>
                        <span class="small fw-semibold text-dark">{{ $goodsReceipt->receipt_date->format('d M Y') }}</span>
                    </div>
                    <div class="d-flex justify-content-between px-3 py-2 border-bottom">
                        <span class="text-muted small">Sumber</span>
                        <span class="badge bg-{{ $goodsReceipt->source_badge }} py-1 px-2 small">{{ $goodsReceipt->source_label }}</span>
                    </div>
                    @if($goodsReceipt->purchaseOrder)
                        <div class="d-flex justify-content-between px-3 py-2 border-bottom">
                            <span class="text-muted small">PO Referensi</span>
                            <a href="{{ route('purchase_orders.show', $goodsReceipt->purchase_order_id) }}" class="small fw-bold text-primary">
                                {{ $goodsReceipt->purchaseOrder->po_number }}
                            </a>
                        </div>
                    @endif
                    <div class="d-flex justify-content-between px-3 py-2 border-bottom">
                        <span class="text-muted small">Supplier / Toko</span>
                        <span class="small fw-semibold text-dark text-end">
                            {{ $goodsReceipt->supplier ? $goodsReceipt->supplier->name : '— (Toko Umum)' }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between px-3 py-2 border-bottom">
                        <span class="text-muted small">Departemen Tujuan</span>
                        <span class="small fw-bold text-dark">
                            {{ $goodsReceipt->department ? $goodsReceipt->department->name : 'Umum' }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between px-3 py-2 border-bottom">
                        <span class="text-muted small">Total Nilai</span>
                        <span class="font-monospace fw-bold text-success small">
                            Rp {{ number_format($goodsReceipt->total_amount, 0, ',', '.') }}
                        </span>
                    </div>
                    @if($goodsReceipt->notes)
                        <div class="px-3 py-2">
                            <span class="text-muted small d-block mb-1">Catatan</span>
                            <span class="small text-muted">{{ $goodsReceipt->notes }}</span>
                        </div>
                    @endif
                </div>

                <div class="text-muted small">
                    <div><i class="fas fa-user me-1"></i> Dicatat oleh: <strong>{{ $goodsReceipt->createdBy?->name ?? 'Sistem' }}</strong></div>
                    @if($goodsReceipt->approvedBy)
                        <div class="mt-1"><i class="fas fa-check-double me-1"></i> Disetujui oleh: <strong>{{ $goodsReceipt->approvedBy->name }}</strong></div>
                        <div class="text-muted" style="font-size:10px; padding-left:16px;">Pada {{ $goodsReceipt->approved_at->format('d M Y H:i') }}</div>
                    @endif
                </div>
            </div>
        </div>

        @if($goodsReceipt->status === 'pending')
            <a href="{{ route('goods_receipts.edit', $goodsReceipt) }}" class="btn btn-warning btn-sm w-100 fw-semibold mb-2 text-white">
                <i class="fas fa-edit me-1"></i> Edit Penerimaan
            </a>
        @endif

        <form action="{{ route('goods_receipts.destroy', $goodsReceipt) }}" method="POST"
            onsubmit="return confirm('Apakah Anda yakin ingin membatalkan/menghapus penerimaan barang ini? Jika sudah disetujui, stok barang akan dikurangi kembali.')"
            class="mb-2">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm w-100 fw-semibold">
                <i class="fas fa-trash me-1"></i> {{ $goodsReceipt->status === 'approved' ? 'Batalkan & Tarik Stok' : 'Hapus Penerimaan' }}
            </button>
        </form>

        <a href="{{ route('goods_receipts.index') }}" class="btn btn-outline-secondary btn-sm w-100">
            <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar
        </a>
    </div>

    {{-- Kanan: Daftar Barang --}}
    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-3 bg-white">
            <div class="card-header border-0 py-3 px-4"
                style="background:linear-gradient(135deg,#10b981,#059669)">
                <h6 class="fw-bold text-white mb-0">
                    <i class="fas fa-boxes me-2"></i>Barang yang Diterima
                    <span class="badge bg-white text-success ms-2" style="font-size:11px">
                        {{ $goodsReceipt->items->count() }} item
                    </span>
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead style="background:#f8fafc">
                            <tr class="small text-uppercase text-muted">
                                <th class="py-2 px-4">Barang / SKU</th>
                                <th class="text-center py-2">Tipe</th>
                                <th class="text-center py-2">Qty</th>
                                <th class="text-end py-2">Harga Satuan</th>
                                <th class="text-end py-2 px-4">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($goodsReceipt->items as $item)
                                @php
                                    $tc = match($item->inventoryItem?->type) {
                                        'bahan'     => ['bg' => '#fef3c7', 'color' => '#92400e'],
                                        'kemasan'   => ['bg' => '#dbeafe', 'color' => '#1e40af'],
                                        'atk'       => ['bg' => '#d1fae5', 'color' => '#065f46'],
                                        'inventaris'=> ['bg' => '#f3e8ff', 'color' => '#6d28d9'],
                                        default     => ['bg' => '#f1f5f9', 'color' => '#475569'],
                                    };
                                @endphp
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="fw-semibold text-dark small">{{ $item->item_name }}</div>
                                        <div class="font-monospace text-muted" style="font-size:11px">{{ $item->item_sku }}</div>
                                        @if($item->inventoryItem)
                                            <span class="badge" style="font-size:10px;background:#d1fae5;color:#065f46">
                                                {{ $item->inventoryItem->unit }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($item->inventoryItem)
                                            <span class="badge rounded-pill" style="font-size:11px;background:{{ $tc['bg'] }};color:{{ $tc['color'] }}">
                                                {{ ucfirst($item->inventoryItem->type) }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">Produk</span>
                                        @endif
                                    </td>
                                    <td class="text-center fw-bold text-success" style="font-size:15px">
                                        +{{ number_format($item->quantity) }}
                                    </td>
                                    <td class="font-monospace text-end text-muted small">
                                        Rp {{ number_format($item->unit_price, 0, ',', '.') }}
                                    </td>
                                    <td class="font-monospace text-end fw-bold text-dark small px-4">
                                        Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot style="background:#ecfdf5">
                            <tr>
                                <td colspan="4" class="text-end fw-bold text-dark px-4 py-3">TOTAL PENERIMAAN</td>
                                <td class="font-monospace text-end fw-bold text-success px-4 py-3">
                                    Rp {{ number_format($goodsReceipt->total_amount, 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
