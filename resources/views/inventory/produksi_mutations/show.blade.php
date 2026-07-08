@extends('layouts.app')
@section('title', 'Detail Mutasi — ' . $warehouseMutation->mutation_number)
@section('page-title', 'Detail Mutasi Produksi')

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
                        <span class="text-muted small">Status</span>
                        <span class="badge bg-{{ $warehouseMutation->status_badge }}">{{ $warehouseMutation->status_label }}</span>
                    </div>
                    <div class="d-flex justify-content-between px-3 py-2 border-bottom">
                        <span class="text-muted small">Tanggal</span>
                        <span class="small fw-semibold text-dark">{{ $warehouseMutation->mutation_date->format('d M Y') }}</span>
                    </div>
                    <div class="d-flex justify-content-between px-3 py-2 border-bottom">
                        <span class="text-muted small">Asal (Sumber)</span>
                        <span class="small fw-semibold text-dark text-end">
                            {{ $warehouseMutation->fromDepartment ? $warehouseMutation->fromDepartment->name : 'Gudang Utama' }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between px-3 py-2 border-bottom">
                        <span class="text-muted small">Tujuan</span>
                        <span class="small fw-semibold text-dark text-end">
                            {{ $warehouseMutation->toDepartment ? $warehouseMutation->toDepartment->name : 'Gudang Utama' }}
                        </span>
                    </div>
                    @if($warehouseMutation->notes)
                        <div class="px-3 py-2">
                            <span class="text-muted small d-block mb-1">Catatan / Keterangan</span>
                            <span class="small text-muted">{{ $warehouseMutation->notes }}</span>
                        </div>
                    @endif
                </div>

                @if($warehouseMutation->status === 'pending' && $warehouseMutation->type === 'out')
                    <div class="alert alert-warning py-2 small mb-3">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        Barang ini belum disetujui untuk masuk ke produksi.
                    </div>
                    <form action="{{ route('produksi_mutations.approve', $warehouseMutation) }}" method="POST"
                        onsubmit="return confirm('Setujui penerimaan barang ini? Stok akan dimasukkan ke Produksi.')" class="mb-3">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm w-100 fw-bold py-2">
                            <i class="fas fa-check me-1"></i> Setujui &amp; Terima Barang
                        </button>
                    </form>
                @endif

                <div class="text-muted small">
                    <i class="fas fa-user me-1"></i> Dicatat oleh: <strong>{{ $warehouseMutation->createdBy?->name ?? 'Sistem' }}</strong>
                </div>
            </div>
        </div>

        @if($warehouseMutation->status === 'pending')
            <a href="{{ route('produksi_mutations.pending_approvals') }}" class="btn btn-outline-secondary btn-sm w-100">
                <i class="fas fa-arrow-left me-1"></i> Kembali ke Approval
            </a>
        @else
            <a href="{{ $warehouseMutation->type === 'in' ? route('produksi_mutations.index_in') : route('produksi_mutations.index_out') }}"
                class="btn btn-outline-secondary btn-sm w-100">
                <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar
            </a>
        @endif
    </div>

    {{-- Kanan: Rincian Barang --}}
    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-3 bg-white">
            <div class="card-header border-0 py-3 px-4 bg-light">
                <h6 class="fw-bold text-dark mb-0">Rincian Barang yang Dimutasi</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr class="small text-uppercase text-muted">
                                <th class="ps-4">No.</th>
                                <th>SKU</th>
                                <th>Nama Barang</th>
                                <th>Tipe Item</th>
                                <th class="text-center">Jumlah (Qty)</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($warehouseMutation->items as $i => $item)
                                <tr>
                                    <td class="ps-4 text-muted small">{{ $i + 1 }}</td>
                                    <td class="font-monospace text-muted small fw-bold">{{ $item->inventoryItem->sku ?: '—' }}</td>
                                    <td class="fw-semibold text-dark small">{{ $item->inventoryItem->name }}</td>
                                    <td>
                                        @php
                                            $typeColors = [
                                                'bahan' => 'bg-info text-dark',
                                                'kemasan' => 'bg-warning text-dark',
                                                'atk' => 'bg-secondary text-white',
                                                'inventaris' => 'bg-primary text-white'
                                            ];
                                            $color = $typeColors[$item->inventoryItem->type] ?? 'bg-light text-dark';
                                        @endphp
                                        <span class="badge {{ $color }} text-uppercase" style="font-size:10px">
                                            {{ $item->inventoryItem->type }}
                                        </span>
                                    </td>
                                    <td class="text-center fw-bold text-dark small">
                                        {{ number_format($item->quantity) }} {{ $item->inventoryItem->unit }}
                                    </td>
                                    <td class="small text-muted">{{ $item->notes ?: '—' }}</td>
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
