@extends('layouts.app')
@section('title', 'Detail Pengeluaran Barang - Pembelian')
@section('page-title', 'Detail Pengeluaran')

@section('content')
<div class="mb-3 d-flex justify-content-between align-items-center">
    <a href="{{ route('pembelian.goods_issue.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar
    </a>
    
    @if(auth()->user()->isSuperAdmin() || auth()->user()->role === 'admin')
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-warning btn-sm px-3 text-dark fw-bold" data-bs-toggle="modal" data-bs-target="#editGoodsIssueModal">
                <i class="fas fa-edit me-1"></i> Edit Pengeluaran
            </button>
            <form action="{{ route('pembelian.goods_issue.destroy', $warehouseMutation) }}" method="POST" id="delete-form">
                @csrf
                @method('DELETE')
                <button type="button" class="btn btn-danger btn-sm px-3" id="btn-cancel">
                    <i class="fas fa-times-circle me-1"></i> Batalkan & Kembalikan Stok
                </button>
            </form>
        </div>
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
                    <label class="small text-muted fw-semibold d-block">Tujuan Pengeluaran</label>
                    <div class="fw-bold text-dark">
                        @if($warehouseMutation->toDepartment)
                            {{ $warehouseMutation->toDepartment->name }}
                        @else
                            Lain-lain
                        @endif
                    </div>
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

{{-- Modal Edit Pengeluaran --}}
<div class="modal fade text-start" id="editGoodsIssueModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form action="{{ route('pembelian.goods_issue.update', $warehouseMutation->id) }}" method="POST" class="modal-content">
            @csrf
            @method('PUT')
            <div class="modal-header bg-light py-2 px-3">
                <h6 class="modal-title fw-bold text-dark mb-0">
                    <i class="fas fa-edit me-1 text-warning"></i> Edit Transaksi Pengeluaran Barang
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small text-muted">Tanggal Keluar <span class="text-danger">*</span></label>
                        <input type="date" name="mutation_date" class="form-control form-control-sm" value="{{ old('mutation_date', $warehouseMutation->mutation_date ? $warehouseMutation->mutation_date->format('Y-m-d') : date('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-6">
                        @php
                            $currentDept = strtolower($warehouseMutation->toDepartment->name ?? '');
                            $selectedTujuan = 'lain_lain';
                            if (str_contains($currentDept, 'produksi')) {
                                $selectedTujuan = 'produksi';
                            } elseif (str_contains($currentDept, 'cetak') || str_contains($currentDept, 'print')) {
                                $selectedTujuan = 'percetakan';
                            }
                        @endphp
                        <label class="form-label fw-semibold small text-muted">Tujuan Keluar <span class="text-danger">*</span></label>
                        <select name="tujuan" class="form-select form-select-sm" required>
                            <option value="produksi" {{ $selectedTujuan === 'produksi' ? 'selected' : '' }}>🏭 Ke Produksi</option>
                            <option value="percetakan" {{ $selectedTujuan === 'percetakan' ? 'selected' : '' }}>🖨️ Ke Percetakan</option>
                            <option value="lain_lain" {{ $selectedTujuan === 'lain_lain' ? 'selected' : '' }}>❓ Lain-lain</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold small text-muted">Catatan / Alasan Keluar</label>
                        <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Catatan pengeluaran...">{{ old('notes', $warehouseMutation->notes) }}</textarea>
                    </div>
                </div>

                <hr class="my-3">

                <h6 class="fw-bold text-dark mb-2" style="font-size: 13px;">
                    <i class="fas fa-cubes me-1 text-success"></i> Edit Kuantitas Barang Dikeluarkan:
                </h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0" style="font-size: 12px;">
                        <thead class="table-light">
                            <tr>
                                <th>Barang</th>
                                <th>Kategori</th>
                                <th class="text-center" style="width: 140px;">Stok Gudang</th>
                                <th class="text-center" style="width: 170px;">Qty Dikeluarkan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($warehouseMutation->items as $idx => $row)
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $row->inventoryItem->name }}</div>
                                        <input type="hidden" name="items[{{ $idx }}][id]" value="{{ $row->id }}">
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary text-uppercase" style="font-size: 9px;">{{ $row->inventoryItem->type }}</span>
                                    </td>
                                    <td class="text-center font-monospace">
                                        {{ number_format($row->inventoryItem->stock) }} {{ $row->inventoryItem->unit }}
                                    </td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <input type="number" step="any" name="items[{{ $idx }}][qty]" class="form-control form-control-sm text-end font-monospace fw-bold" value="{{ number_format($row->quantity, 0, '', '') }}" required min="0.01">
                                            <span class="input-group-text small">{{ $row->inventoryItem->unit }}</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer py-2 bg-light">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-sm btn-warning text-dark fw-bold px-3">
                    <i class="fas fa-save me-1"></i> Simpan Perubahan
                </button>
            </div>
        </form>
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
