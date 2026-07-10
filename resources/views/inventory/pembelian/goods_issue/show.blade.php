@extends('layouts.app')
@section('title', 'Detail Pengeluaran Barang - Pembelian')
@section('page-title', 'Detail Pengeluaran')

@section('content')
<div class="mb-3 d-flex justify-content-between align-items-center">
    <a href="{{ route('pembelian.goods_issue.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar
    </a>
    
    @if(auth()->user()->isSuperAdmin() || auth()->user()->role === 'admin')
        <form action="{{ route('pembelian.goods_issue.destroy', $warehouseMutation) }}" method="POST" id="delete-form">
            @csrf
            @method('DELETE')
            <button type="button" class="btn btn-danger btn-sm px-3" id="btn-cancel">
                <i class="fas fa-times-circle me-1"></i> Batalkan & Kembalikan Stok
            </button>
        </form>
    @endif
</div>

<div class="row g-3">
    {{-- Info Transaksi --}}
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 bg-white mb-3">
            <div class="card-header border-0 py-3 px-4" style="background:linear-gradient(135deg,#10b981,#059669)">
                <h6 class="fw-bold text-white mb-0">
                    <i class="fas fa-info-circle me-2"></i>Informasi Transaksi
                </h6>
            </div>
            <div class="card-body p-4">
                <div class="mb-3">
                    <label class="small text-muted fw-semibold d-block">No. Transaksi</label>
                    <code class="font-monospace text-success fw-bold" style="font-size:14px">{{ $warehouseMutation->mutation_number }}</code>
                </div>
                <div class="mb-3">
                    <label class="small text-muted fw-semibold d-block">Tanggal Transaksi</label>
                    <div class="fw-bold text-dark">{{ $warehouseMutation->mutation_date ? $warehouseMutation->mutation_date->format('d F Y') : '—' }}</div>
                </div>
                <div class="mb-3">
                    <label class="small text-muted fw-semibold d-block">Status</label>
                    <span class="badge bg-success text-uppercase">Approved</span>
                </div>
                <div class="mb-3">
                    <label class="small text-muted fw-semibold d-block">Operator Pencatat</label>
                    <div class="fw-bold text-dark">{{ $warehouseMutation->createdBy->name ?? 'System' }}</div>
                </div>
                <div class="mb-0">
                    <label class="small text-muted fw-semibold d-block">Catatan / Alasan</label>
                    <div class="p-2 rounded bg-light small text-muted text-wrap">{{ $warehouseMutation->notes ?: 'Tidak ada catatan.' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Detail Item --}}
    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-3 bg-white">
            <div class="card-header border-0 py-3 px-4" style="background:linear-gradient(135deg,#10b981,#059669)">
                <h6 class="fw-bold text-white mb-0">
                    <i class="fas fa-cubes me-2"></i>Rincian Barang Dikeluarkan
                </h6>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover border align-middle mb-0 rounded-2 overflow-hidden">
                        <thead style="background:#ecfdf5">
                            <tr class="small text-uppercase text-muted text-success">
                                <th class="py-2 px-3">Barang</th>
                                <th>Kategori</th>
                                <th class="text-end">Qty Keluar</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($warehouseMutation->items as $row)
                                @php
                                    $catColors = [
                                        'bahan' => 'background:#e0f2fe;color:#0369a1',
                                        'kemasan' => 'background:#fef3c7;color:#b45309',
                                        'atk' => 'background:#ede9fe;color:#5b21b6',
                                        'inventaris' => 'background:#dbeafe;color:#1e40af'
                                    ];
                                    $cs = $catColors[$row->inventoryItem->type] ?? 'background:#f1f5f9;color:#475569';
                                @endphp
                                <tr>
                                    <td class="py-3 px-3">
                                        <div class="fw-bold text-dark small">{{ $row->inventoryItem->name }}</div>
                                        <div class="text-muted font-monospace" style="font-size:10px">SKU: {{ $row->inventoryItem->sku ?: '—' }}</div>
                                    </td>
                                    <td>
                                        <span class="badge text-uppercase" style="{{ $cs }};font-size:10px">{{ $row->inventoryItem->type }}</span>
                                    </td>
                                    <td class="text-end fw-bold text-danger font-monospace small">
                                        -{{ number_format($row->quantity) }} {{ $row->inventoryItem->unit }}
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

@push('scripts')
<script>
$(document).ready(function() {
    $('#btn-cancel').on('click', function() {
        Swal.fire({
            title: 'Batalkan Transaksi?',
            text: "Tindakan ini akan menghapus transaksi dan mengembalikan stok barang ke jumlah semula!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Ya, Batalkan!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $('#delete-form').submit();
            }
        });
    });
});
</script>
@endpush
