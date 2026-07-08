@extends('layouts.app')
@section('title', 'Detail Mutasi — ' . $warehouseMutation->mutation_number)
@section('page-title', 'Detail Mutasi Gudang Logistik')

@section('content')
<div class="row g-3">
    {{-- Kiri: Detail Informasi --}}
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 bg-white mb-3">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="fas fa-file-alt me-2" style="color:#8b5cf6"></i>Detail Informasi
                    </h6>
                    <span class="badge py-2 px-3 small text-uppercase text-white"
                        style="background:{{ $warehouseMutation->type === 'in' ? 'linear-gradient(135deg,#8b5cf6,#6d28d9)' : 'linear-gradient(135deg,#f59e0b,#d97706)' }}">
                        {{ $warehouseMutation->type === 'in' ? 'Barang Masuk' : 'Barang Keluar' }}
                    </span>
                </div>

                <div class="border rounded-2 overflow-hidden mb-3">
                    <div class="d-flex justify-content-between px-3 py-2 border-bottom" style="background:#f8fafc">
                        <span class="text-muted small">No. Transaksi</span>
                        <span class="font-monospace fw-bold text-dark small">{{ $warehouseMutation->mutation_number }}</span>
                    </div>
                    <div class="d-flex justify-content-between px-3 py-2 border-bottom">
                        <span class="text-muted small">Tanggal</span>
                        <span class="small fw-semibold text-dark">{{ $warehouseMutation->mutation_date->format('d M Y') }}</span>
                    </div>
                    <div class="d-flex justify-content-between px-3 py-2 border-bottom">
                        <span class="text-muted small">Asal (Sumber)</span>
                        <span class="small fw-semibold text-dark text-end">
                            {{ $warehouseMutation->fromDepartment ? $warehouseMutation->fromDepartment->name : 'Gudang Inventory' }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between px-3 py-2 border-bottom">
                        <span class="text-muted small">Tujuan</span>
                        <span class="small fw-semibold text-dark text-end">
                            {{ $warehouseMutation->toDepartment ? $warehouseMutation->toDepartment->name : 'Gudang Inventory' }}
                        </span>
                    </div>
                    @if($warehouseMutation->notes)
                        <div class="px-3 py-2">
                            <span class="text-muted small d-block mb-1">Catatan / Keterangan</span>
                            <span class="small text-muted">{{ $warehouseMutation->notes }}</span>
                        </div>
                    @endif
                </div>

                <div class="text-muted small">
                    <i class="fas fa-user me-1"></i> Dicatat oleh: <strong>{{ $warehouseMutation->createdBy?->name ?? 'Sistem' }}</strong>
                </div>
            </div>
        </div>

        <a href="{{ $warehouseMutation->type === 'in' ? route('ga_mutations.index_in') : route('ga_mutations.index_out') }}"
            class="btn btn-outline-secondary btn-sm w-100">
            <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar
        </a>
    </div>

    {{-- Kanan: Detail Barang --}}
    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-3 bg-white">
            <div class="card-header border-0 py-3 px-4"
                style="background:{{ $warehouseMutation->type === 'in' ? 'linear-gradient(135deg,#8b5cf6,#6d28d9)' : 'linear-gradient(135deg,#f59e0b,#d97706)' }}">
                <h6 class="fw-bold text-white mb-0">
                    <i class="fas fa-boxes me-2"></i>Daftar Barang Mutasi
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead style="background:#f8fafc">
                            <tr class="small text-uppercase text-muted">
                                <th class="py-2 px-4">Nama Barang</th>
                                <th class="text-center py-2">Tipe</th>
                                <th class="text-center py-2">Satuan</th>
                                <th class="text-center py-2 px-4" style="width:150px">Qty Mutasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($warehouseMutation->items as $item)
                                @php
                                    $tc = match($item->inventoryItem?->type) {
                                        'atk'        => ['bg' => '#ede9fe', 'color' => '#5b21b6'],
                                        'inventaris' => ['bg' => '#dbeafe', 'color' => '#1e40af'],
                                        default      => ['bg' => '#f1f5f9', 'color' => '#475569'],
                                    };
                                @endphp
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="fw-semibold text-dark small">{{ $item->inventoryItem?->name ?? '— (Barang Terhapus)' }}</div>
                                        <div class="font-monospace text-muted" style="font-size:11px">{{ $item->inventoryItem?->sku ?? '-' }}</div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge rounded-pill" style="font-size:11px;background:{{ $tc['bg'] }};color:{{ $tc['color'] }}">
                                            {{ ucfirst($item->inventoryItem?->type ?? 'Barang') }}
                                        </span>
                                    </td>
                                    <td class="text-center small text-muted">
                                        {{ $item->inventoryItem?->unit ?? 'pcs' }}
                                    </td>
                                    <td class="text-center fw-bold px-4" style="font-size:15px;color:{{ $warehouseMutation->type === 'in' ? '#059669' : '#d97706' }}">
                                        {{ $warehouseMutation->type === 'in' ? '+' : '-' }}{{ number_format($item->quantity) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
