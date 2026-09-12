@extends('layouts.app')
@section('title', 'Daftar Pengeluaran Barang - Pembelian')
@section('page-title', 'Pengeluaran Barang')

@section('content')
<div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle d-flex align-items-center justify-content-center"
                    style="width:42px;height:42px;background:linear-gradient(135deg,#10b981,#059669)">
                    <i class="fas fa-sign-out-alt text-white"></i>
                </div>
                <div>
                    <h5 class="fw-bold text-dark mb-0">Pengeluaran Barang</h5>
                    <div class="text-muted small">Pencatatan pengeluaran / pengurangan stok barang (Bahan, Kemasan, ATK, dll)</div>
                </div>
            </div>
            <a href="{{ route('pembelian.goods_issue.create') }}"
                class="btn fw-semibold btn-sm px-3 text-white" style="background:linear-gradient(135deg,#10b981,#059669)">
                <i class="fas fa-plus me-1"></i> Catat Pengeluaran Baru
            </a>
        </div>

        {{-- Filter --}}
        <form method="GET" class="row g-2 mb-2 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label small fw-semibold text-muted">Cari No. Transaksi</label>
                <input type="text" name="search" class="form-control form-control-sm"
                    value="{{ request('search') }}" placeholder="Ketik nomor transaksi...">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small fw-semibold text-muted">Dari Tanggal</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small fw-semibold text-muted">Sampai Tanggal</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-success btn-sm px-3 w-100">
                    <i class="fas fa-search me-1"></i> Filter
                </button>
                @if(request()->anyFilled(['search','date_from','date_to']))
                    <a href="{{ route('pembelian.goods_issue.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-3 bg-white">
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover border align-middle mb-0 rounded-2 overflow-hidden">
                <thead style="background:#ecfdf5">
                    <tr class="small text-uppercase text-muted text-success">
                        <th class="py-2 px-3">Tanggal</th>
                        <th>No. Transaksi</th>
                        <th>Tujuan</th>
                        <th>Keterangan</th>
                        <th class="text-center">Jumlah Item</th>
                        <th>Operator</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mutations as $row)
                        @php
                            $mDate = $row->mutation_date ? $row->mutation_date->format('d M Y') : '—';
                        @endphp
                        <tr>
                            <td class="small text-muted py-3 px-3">{{ $mDate }}</td>
                            <td class="font-monospace fw-bold small text-dark">
                                {{ $row->mutation_number }}
                            </td>
                            <td>
                                @if($row->toDepartment)
                                    @php
                                        $badgeColor = match($row->toDepartment->name) {
                                            'Produksi' => 'bg-success text-white',
                                            'Percetakan' => 'bg-info text-dark',
                                            default => 'bg-secondary text-white'
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeColor }}">{{ $row->toDepartment->name }}</span>
                                @else
                                    <span class="badge bg-secondary">Lain-lain</span>
                                @endif
                            </td>
                            <td class="small text-muted text-wrap" style="max-width: 300px;">
                                {{ $row->notes ?: '—' }}
                                @if($row->spk)
                                    <div class="mt-1">
                                        <a href="{{ route('spks.show', $row->spk) }}" class="badge bg-primary text-white text-decoration-none">
                                            <i class="fas fa-file-alt me-1"></i> SPK #{{ $row->spk->no_spk }}
                                        </a>
                                    </div>
                                @endif
                            </td>
                            <td class="text-center fw-bold text-dark small">
                                {{ number_format($row->items->count()) }}
                            </td>
                            <td class="small text-muted">
                                {{ $row->createdBy->name ?? 'System' }}
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <button type="button" class="btn btn-xs btn-outline-success py-1 px-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#showModal-{{ $row->id }}" title="Detail">
                                        <i class="fas fa-eye me-1"></i> Detail
                                    </button>
                                    @if(auth()->user()->isSuperAdmin() || auth()->user()->role === 'admin')
                                        <button type="button" class="btn btn-xs btn-outline-warning text-dark fw-semibold py-1 px-2" data-bs-toggle="modal" data-bs-target="#editModal-{{ $row->id }}" title="Edit">
                                            <i class="fas fa-edit me-1"></i> Edit
                                        </button>
                                        <button type="button" class="btn btn-xs btn-outline-danger py-1 px-2 btn-delete-issue" data-form-id="delete-form-{{ $row->id }}" title="Hapus">
                                            <i class="fas fa-trash-alt me-1"></i> Hapus
                                        </button>
                                        
                                        <form action="{{ route('pembelian.goods_issue.destroy', $row->id) }}" method="POST" id="delete-form-{{ $row->id }}" class="d-none">
                                            @csrf
                                            @method('DELETE')
                                        </form>

                                        {{-- Modal Detail Transaksi Pengeluaran --}}
                                        <div class="modal fade text-start" id="showModal-{{ $row->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                                <div class="modal-content border-0 shadow-lg rounded-3">
                                                    <div class="modal-header text-white py-3 px-4" style="background:linear-gradient(135deg,#10b981,#059669)">
                                                        <h6 class="modal-title fw-bold mb-0 d-flex align-items-center gap-2">
                                                            <i class="fas fa-info-circle"></i> Detail Pengeluaran Barang #{{ $row->mutation_number }}
                                                        </h6>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body p-4">
                                                        <div class="row g-3 mb-4">
                                                            <div class="col-md-6">
                                                                <div class="p-3 bg-light rounded-3 border">
                                                                    <small class="text-muted d-block fw-semibold mb-1">No. Transaksi</small>
                                                                    <span class="font-monospace fw-bold text-success fs-6">{{ $row->mutation_number }}</span>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="p-3 bg-light rounded-3 border">
                                                                    <small class="text-muted d-block fw-semibold mb-1">Tanggal & Status</small>
                                                                    <div class="d-flex justify-content-between align-items-center">
                                                                        <span class="fw-bold text-dark">{{ $row->mutation_date ? $row->mutation_date->format('d F Y') : '—' }}</span>
                                                                        <span class="badge bg-success text-uppercase">Approved</span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="p-3 bg-light rounded-3 border">
                                                                    <small class="text-muted d-block fw-semibold mb-1">Tujuan Pengeluaran</small>
                                                                    <span class="fw-bold text-dark">
                                                                        {{ $row->toDepartment ? $row->toDepartment->name : 'Lain-lain' }}
                                                                    </span>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="p-3 bg-light rounded-3 border">
                                                                    <small class="text-muted d-block fw-semibold mb-1">Operator Pencatat</small>
                                                                    <span class="fw-bold text-dark">{{ $row->createdBy->name ?? 'System' }}</span>
                                                                </div>
                                                            </div>
                                                            @if($row->spk)
                                                            <div class="col-12">
                                                                <div class="p-3 bg-primary-subtle border border-primary-subtle rounded-3">
                                                                    <small class="text-primary fw-bold d-block mb-1"><i class="fas fa-link me-1"></i> Terhubung dengan SPK</small>
                                                                    <a href="{{ route('spks.show', $row->spk) }}" class="btn btn-sm btn-primary fw-bold">
                                                                        <i class="fas fa-file-alt me-1"></i> Buka SPK #{{ $row->spk->no_spk }}
                                                                    </a>
                                                                </div>
                                                            </div>
                                                            @endif
                                                            <div class="col-12">
                                                                <div class="p-3 bg-light rounded-3 border">
                                                                    <small class="text-muted d-block fw-semibold mb-1">Catatan / Alasan</small>
                                                                    <span class="small text-muted">{{ $row->notes ?: 'Tidak ada catatan.' }}</span>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <h6 class="fw-bold text-dark mb-2"><i class="fas fa-boxes me-2 text-success"></i>Daftar Barang yang Dikeluarkan ({{ number_format($row->items->count()) }} Item)</h6>
                                                        <div class="table-responsive border rounded-2">
                                                            <table class="table table-sm table-hover align-middle mb-0" style="font-size:12px;">
                                                                <thead class="table-light">
                                                                    <tr class="text-muted text-uppercase">
                                                                        <th class="py-2 px-3">Nama Barang</th>
                                                                        <th class="text-center">Tipe</th>
                                                                        <th class="text-center">Qty Keluar</th>
                                                                        <th class="text-end">Harga Satuan</th>
                                                                        <th class="text-end px-3">Subtotal</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @php $grandTotal = 0; @endphp
                                                                    @foreach($row->items as $item)
                                                                        @php
                                                                            $subtotal = $item->quantity * $item->unit_price;
                                                                            $grandTotal += $subtotal;
                                                                        @endphp
                                                                        <tr>
                                                                            <td class="px-3 py-2">
                                                                                <div class="fw-bold text-dark">{{ $item->inventoryItem->name ?? '—' }}</div>
                                                                                @if(!empty($item->notes))
                                                                                    <small class="text-muted">{{ $item->notes }}</small>
                                                                                @endif
                                                                            </td>
                                                                            <td class="text-center">
                                                                                <span class="badge bg-secondary rounded-pill" style="font-size:10px;">
                                                                                    {{ ucfirst($item->inventoryItem->type ?? 'raw') }}
                                                                                </span>
                                                                            </td>
                                                                            <td class="text-center fw-bold text-danger">
                                                                                -{{ number_format($item->quantity) }} {{ $item->inventoryItem->unit ?? 'pcs' }}
                                                                            </td>
                                                                            <td class="font-monospace text-end text-muted">
                                                                                Rp {{ number_format($item->unit_price, 0, ',', '.') }}
                                                                            </td>
                                                                            <td class="font-monospace text-end fw-bold text-dark px-3">
                                                                                Rp {{ number_format($subtotal, 0, ',', '.') }}
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                                <tfoot class="table-light">
                                                                    <tr>
                                                                        <td colspan="4" class="text-end fw-bold px-3 py-2">Total Nilai Pengeluaran</td>
                                                                        <td class="font-monospace text-end fw-bold text-danger px-3 py-2">
                                                                            Rp {{ number_format($grandTotal, 0, ',', '.') }}
                                                                        </td>
                                                                    </tr>
                                                                </tfoot>
                                                            </table>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer bg-light py-2 px-3">
                                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Modal Edit Transaksi --}}
                                        <div class="modal fade text-start" id="editModal-{{ $row->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                                <form action="{{ route('pembelian.goods_issue.update', $row->id) }}" method="POST" class="modal-content">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-header bg-light py-2 px-3">
                                                        <h6 class="modal-title fw-bold text-dark mb-0">
                                                            <i class="fas fa-edit me-1 text-warning"></i> Edit Transaksi Pengeluaran #{{ $row->mutation_number }}
                                                        </h6>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body p-4">
                                                        <div class="row g-3 mb-3">
                                                            <div class="col-md-6">
                                                                <label class="form-label fw-semibold small text-muted">Tanggal Keluar <span class="text-danger">*</span></label>
                                                                <input type="date" name="mutation_date" class="form-control form-control-sm" value="{{ old('mutation_date', $row->mutation_date ? $row->mutation_date->format('Y-m-d') : date('Y-m-d')) }}" required>
                                                            </div>
                                                            <div class="col-md-6">
                                                                @php
                                                                    $currentDept = strtolower($row->toDepartment->name ?? '');
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
                                                                <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Catatan pengeluaran...">{{ old('notes', $row->notes) }}</textarea>
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
                                                                    @foreach($row->items as $itemIdx => $rItem)
                                                                        <tr>
                                                                            <td>
                                                                                <div class="fw-bold text-dark">{{ $rItem->inventoryItem->name ?? '—' }}</div>
                                                                                <input type="hidden" name="items[{{ $itemIdx }}][id]" value="{{ $rItem->id }}">
                                                                            </td>
                                                                            <td>
                                                                                <span class="badge bg-secondary text-uppercase" style="font-size: 9px;">{{ $rItem->inventoryItem->type ?? 'general' }}</span>
                                                                            </td>
                                                                            <td class="text-center font-monospace">
                                                                                {{ number_format($rItem->inventoryItem->stock ?? 0) }} {{ $rItem->inventoryItem->unit ?? '' }}
                                                                            </td>
                                                                            <td>
                                                                                <div class="input-group input-group-sm">
                                                                                    <input type="number" step="any" name="items[{{ $itemIdx }}][qty]" class="form-control form-control-sm text-end font-monospace fw-bold" value="{{ number_format($rItem->quantity, 0, '', '') }}" required min="0.01">
                                                                                    <span class="input-group-text small">{{ $rItem->inventoryItem->unit ?? '' }}</span>
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
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fas fa-sign-out-alt fa-2x mb-3 opacity-25 d-block"></i>
                                Tidak ada data pengeluaran barang ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $mutations->links() }}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $(document).on('click', '.btn-delete-issue', function() {
        const formId = $(this).data('form-id');
        Swal.fire({
            title: 'Batalkan Transaksi?',
            text: "Tindakan ini akan menghapus transaksi dan mengembalikan stok barang ke jumlah semula!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Ya, Batalkan & Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $('#' + formId).submit();
            }
        });
    });
});
</script>
@endpush
