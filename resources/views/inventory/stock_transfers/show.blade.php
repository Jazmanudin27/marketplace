@extends('layouts.app')
@section('title', 'Detail Transfer — ' . $stockTransfer->transfer_number)
@section('page-title', 'Detail Transfer Stok')

@section('content')
<div class="row g-3">
    {{-- Kiri: Info Transfer --}}
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 bg-white mb-3">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="fas fa-exchange-alt me-2" style="color:#8b5cf6"></i>Info Transfer
                    </h6>
                    <span class="badge bg-{{ $stockTransfer->status_badge }} py-2 px-3 text-uppercase small">
                        {{ $stockTransfer->status_label }}
                    </span>
                </div>

                <div class="border rounded-2 overflow-hidden mb-3">
                    <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom" style="background:#f8fafc">
                        <span class="text-muted small">No. Transfer</span>
                        <span class="font-monospace fw-bold text-dark small">{{ $stockTransfer->transfer_number }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                        <span class="text-muted small">Tanggal</span>
                        <span class="small text-dark fw-semibold">{{ $stockTransfer->transfer_date->format('d M Y') }}</span>
                    </div>
                    <div class="px-3 py-2 border-bottom">
                        <span class="text-muted small d-block mb-2">Asal → Tujuan</span>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge rounded-pill px-3" style="background:#ede9fe;color:#6d28d9">
                                <i class="fas fa-arrow-up me-1"></i>
                                {{ $stockTransfer->fromDepartment ? $stockTransfer->fromDepartment->name : 'Umum' }}
                            </span>
                            <i class="fas fa-long-arrow-alt-right text-muted"></i>
                            <span class="badge rounded-pill px-3" style="background:#d1fae5;color:#065f46">
                                <i class="fas fa-arrow-down me-1"></i>
                                {{ $stockTransfer->toDepartment ? $stockTransfer->toDepartment->name : '-' }}
                            </span>
                        </div>
                    </div>
                    @if($stockTransfer->notes)
                        <div class="px-3 py-2">
                            <span class="text-muted small d-block mb-1">Catatan</span>
                            <span class="small text-muted">{{ $stockTransfer->notes }}</span>
                        </div>
                    @endif
                </div>

                @if($stockTransfer->createdBy)
                    <div class="text-muted mb-1" style="font-size:11px">
                        <i class="fas fa-user me-1"></i> Dibuat: <strong>{{ $stockTransfer->createdBy->name }}</strong>
                    </div>
                @endif
                @if($stockTransfer->confirmedBy)
                    <div class="text-muted" style="font-size:11px">
                        <i class="fas fa-check me-1"></i> Dikonfirmasi: <strong>{{ $stockTransfer->confirmedBy->name }}</strong>
                        @if($stockTransfer->confirmed_at)
                            <span class="text-muted">({{ $stockTransfer->confirmed_at->format('d M Y H:i') }})</span>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <a href="{{ route('stock_transfers.index') }}" class="btn btn-outline-secondary btn-sm w-100">
            <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar
        </a>
    </div>

    {{-- Kanan: Item --}}
    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-3 bg-white">
            <div class="card-header border-0 py-3 px-4" style="background:linear-gradient(135deg,#8b5cf6,#6d28d9)">
                <h6 class="fw-bold text-white mb-0">
                    <i class="fas fa-boxes me-2"></i>Barang yang Ditransfer
                    <span class="badge bg-white text-dark ms-2" style="font-size:11px">{{ $stockTransfer->items->count() }} item</span>
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead style="background:#f8fafc">
                            <tr class="small text-uppercase text-muted">
                                <th class="py-2 px-4">Barang</th>
                                <th class="text-center py-2">Tipe</th>
                                <th class="text-center py-2">Satuan</th>
                                <th class="text-center py-2">Qty Transfer</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stockTransfer->items as $item)
                                @php
                                    $typeColor = match($item->inventoryItem?->type) {
                                        'bahan'     => ['bg' => '#fef3c7', 'text' => '#92400e'],
                                        'kemasan'   => ['bg' => '#dbeafe', 'text' => '#1e40af'],
                                        'atk'       => ['bg' => '#d1fae5', 'text' => '#065f46'],
                                        'inventaris'=> ['bg' => '#f3e8ff', 'text' => '#6d28d9'],
                                        default     => ['bg' => '#f1f5f9', 'text' => '#475569'],
                                    };
                                @endphp
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="fw-semibold text-dark small">{{ $item->inventoryItem?->name ?? 'Item Terhapus' }}</div>
                                        <div class="font-monospace text-muted" style="font-size:11px">{{ $item->inventoryItem?->sku ?: '-' }}</div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge rounded-pill px-2" style="font-size:11px;background:{{ $typeColor['bg'] }};color:{{ $typeColor['text'] }}">
                                            {{ ucfirst($item->inventoryItem?->type ?? '-') }}
                                        </span>
                                    </td>
                                    <td class="text-center text-muted small">{{ $item->inventoryItem?->unit ?? '-' }}</td>
                                    <td class="text-center fw-bold" style="color:#6d28d9;font-size:15px">
                                        {{ number_format($item->quantity) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot style="background:#f5f3ff">
                            <tr>
                                <td colspan="3" class="text-end fw-semibold text-dark px-4 py-2 small">TOTAL ITEM DITRANSFER</td>
                                <td class="text-center fw-bold py-2" style="color:#6d28d9">
                                    {{ $stockTransfer->items->sum('quantity') }}
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
