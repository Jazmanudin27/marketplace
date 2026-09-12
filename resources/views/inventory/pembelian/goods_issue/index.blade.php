@extends('layouts.app')
@section('title', 'Pengeluaran Barang - Pembelian')
@section('page-title', 'Pengeluaran Barang')

@section('content')
<div class="container-fluid px-0">

    {{-- Top Action & Header Banner --}}
    <div class="card border-0 shadow-sm mb-4 rounded-3 overflow-hidden bg-white">
        <div class="card-body p-4 border-start border-4 border-danger">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h5 class="fw-bold text-dark mb-1">
                        <i class="fas fa-sign-out-alt text-danger me-2"></i> Pengeluaran Barang (Goods Issue)
                    </h5>
                    <p class="text-muted small mb-0">
                        Pencatatan pengeluaran dan pengurangan stok bahan baku, kemasan, ATK, dan inventaris untuk produksi atau operasional.
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('pembelian.goods_issue.create') }}" class="btn btn-danger btn-sm px-3 rounded-3 fw-semibold">
                        <i class="fas fa-plus-circle me-1.5"></i> + Catat Pengeluaran Baru
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Summary Stat Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm h-100 rounded-3">
                <div class="card-body py-3 px-4 d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-primary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px;">
                        <i class="fas fa-list-alt text-primary fs-5"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-4 text-dark">{{ number_format($totalTransactions) }}</div>
                        <div class="text-muted small">Total Transaksi Pengeluaran</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm h-100 rounded-3 border-start border-danger border-4">
                <div class="card-body py-3 px-4 d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-danger bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px;">
                        <i class="fas fa-boxes text-danger fs-5"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-4 text-danger">-{{ number_format($totalItemsCount) }} <small class="fs-6 fw-normal text-muted">unit</small></div>
                        <div class="text-muted small">Total Barang Dikeluarkan</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm h-100 rounded-3 border-start border-info border-4">
                <div class="card-body py-3 px-4 d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-info bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px;">
                        <i class="fas fa-coins text-info fs-5"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-4 text-dark font-monospace">Rp {{ number_format($totalValue, 0, ',', '.') }}</div>
                        <div class="text-muted small">Total Nilai Pengeluaran</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="card border-0 shadow-sm mb-4 rounded-3">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('pembelian.goods_issue.index') }}">
                <div class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label form-label-sm fw-semibold mb-1">
                            <i class="fas fa-search me-1 text-muted"></i> Cari No. Transaksi / SPK / Catatan
                        </label>
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari keyword transaksi..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label form-label-sm fw-semibold mb-1">
                            <i class="fas fa-building me-1 text-muted"></i> Tujuan Pengeluaran
                        </label>
                        <select name="to_department_id" class="form-select form-select-sm">
                            <option value="">-- Semua Tujuan --</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ request('to_department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label form-label-sm fw-semibold mb-1">
                            <i class="fas fa-calendar me-1 text-muted"></i> Dari Tanggal
                        </label>
                        <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label form-label-sm fw-semibold mb-1">
                            <i class="fas fa-calendar me-1 text-muted"></i> Sampai Tanggal
                        </label>
                        <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-12 text-end mt-2">
                        <button type="submit" class="btn btn-danger btn-sm px-3">
                            <i class="fas fa-filter me-1"></i> Terapkan Filter
                        </button>
                        @if (request()->anyFilled(['search', 'to_department_id', 'date_from', 'date_to']))
                            <a href="{{ route('pembelian.goods_issue.index') }}" class="btn btn-secondary btn-sm px-3 ms-1">
                                <i class="fas fa-times me-1"></i> Reset Filter
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Main Table Card --}}
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light border-bottom">
                    <tr class="small text-uppercase text-muted fw-bold">
                        <th class="ps-3" style="width: 130px;">TANGGAL</th>
                        <th style="width: 170px;">NO. TRANSAKSI</th>
                        <th style="width: 140px;">TUJUAN</th>
                        <th>KETERANGAN / SPK</th>
                        <th class="text-center" style="width: 100px;">ITEM</th>
                        <th style="width: 140px;">OPERATOR</th>
                        <th class="text-center pe-3" style="width: 140px;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mutations as $row)
                        @php
                            $mDate = $row->mutation_date ? $row->mutation_date->format('d/m/Y') : '—';
                        @endphp
                        <tr>
                            <td class="ps-3 text-nowrap">
                                <div class="fw-semibold text-dark small">{{ $mDate }}</div>
                                <div class="text-muted" style="font-size: 0.73rem;">Approved</div>
                            </td>
                            <td>
                                <code class="font-monospace fw-bold text-danger small">{{ $row->mutation_number }}</code>
                            </td>
                            <td>
                                @if($row->toDepartment)
                                    @php
                                        $badgeColor = match($row->toDepartment->name) {
                                            'Produksi' => 'bg-success bg-opacity-10 text-success border border-success border-opacity-25',
                                            'Percetakan' => 'bg-info bg-opacity-10 text-info border border-info border-opacity-25',
                                            default => 'bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25'
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeColor }} px-2.5 py-1 rounded-pill fw-bold">
                                        {{ $row->toDepartment->name }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2.5 py-1 rounded-pill fw-bold">Lain-lain</span>
                                @endif
                            </td>
                            <td>
                                <div class="text-dark small text-wrap" style="max-width: 320px;">
                                    {{ $row->notes ?: '—' }}
                                    @if($row->spk)
                                        <div class="mt-1">
                                            <a href="{{ route('spks.show', $row->spk) }}" class="badge bg-primary text-white text-decoration-none">
                                                <i class="fas fa-file-alt me-1"></i> SPK #{{ $row->spk->no_spk }}
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="text-center fw-bold text-dark small">
                                <span class="badge bg-light text-dark border px-2 py-1 font-monospace">
                                    {{ number_format($row->items->count()) }} item
                                </span>
                            </td>
                            <td class="small text-muted">
                                <i class="fas fa-user-circle me-1 text-secondary"></i>{{ $row->createdBy->name ?? 'System' }}
                            </td>
                            <td class="text-center pe-3">
                                <div class="d-flex justify-content-center gap-1">
                                    <button type="button" class="btn btn-xs btn-outline-success py-1 px-2.5 fw-semibold rounded-2" data-bs-toggle="modal" data-bs-target="#showModal-{{ $row->id }}" title="Detail">
                                        <i class="fas fa-eye me-1"></i> Detail
                                    </button>
                                    @if(auth()->user()->isSuperAdmin() || auth()->user()->role === 'admin')
                                        <button type="button" class="btn btn-xs btn-outline-warning text-dark fw-semibold py-1 px-2.5 rounded-2" data-bs-toggle="modal" data-bs-target="#editModal-{{ $row->id }}" title="Edit">
                                            <i class="fas fa-edit me-1"></i> Edit
                                        </button>
                                        <button type="button" class="btn btn-xs btn-outline-danger py-1 px-2.5 rounded-2 btn-delete-issue" data-form-id="delete-form-{{ $row->id }}" title="Hapus">
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
                                                    <div class="modal-header text-white py-3 px-4" style="background:linear-gradient(135deg,#ef4444,#dc2626)">
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
                                                                    <span class="font-monospace fw-bold text-danger fs-6">{{ $row->mutation_number }}</span>
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

                                                        <h6 class="fw-bold text-dark mb-2"><i class="fas fa-boxes me-2 text-danger"></i>Daftar Barang yang Dikeluarkan ({{ number_format($row->items->count()) }} Item)</h6>
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
                                                                <label class="form-label small fw-semibold">Tanggal Mutasi</label>
                                                                <input type="date" name="mutation_date" class="form-control form-control-sm" value="{{ $row->mutation_date ? $row->mutation_date->format('Y-m-d') : '' }}" required>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label small fw-semibold">Catatan</label>
                                                                <input type="text" name="notes" class="form-control form-control-sm" value="{{ $row->notes }}">
                                                            </div>
                                                        </div>
                                                        <h6 class="fw-bold small text-dark mb-2">Item Barang</h6>
                                                        <div class="table-responsive">
                                                            <table class="table table-sm border align-middle">
                                                                <thead class="bg-light small text-uppercase">
                                                                    <tr>
                                                                        <th>Nama Barang</th>
                                                                        <th style="width:120px;" class="text-center">Qty Keluar</th>
                                                                        <th>Catatan Item</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach($row->items as $idx => $item)
                                                                        <tr>
                                                                            <td class="small fw-semibold">
                                                                                <input type="hidden" name="items[{{ $idx }}][id]" value="{{ $item->id }}">
                                                                                {{ $item->inventoryItem->name ?? '—' }}
                                                                            </td>
                                                                            <td>
                                                                                <input type="number" name="items[{{ $idx }}][quantity]" class="form-control form-control-sm text-center" value="{{ $item->quantity }}" min="1" required>
                                                                            </td>
                                                                            <td>
                                                                                <input type="text" name="items[{{ $idx }}][notes]" class="form-control form-control-sm" value="{{ $item->notes }}" placeholder="Catatan opsional...">
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer py-2 px-3 bg-light">
                                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn btn-primary btn-sm">Simpan Perubahan</button>
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
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="fas fa-sign-out-alt d-block mb-2 opacity-25 fs-1"></i>
                                Belum ada transaksi pengeluaran barang yang sesuai filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($mutations->hasPages())
            <div class="card-footer bg-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        Menampilkan {{ $mutations->firstItem() }} - {{ $mutations->lastItem() }} dari {{ $mutations->total() }} transaksi
                    </small>
                    {{ $mutations->links() }}
                </div>
            </div>
        @endif
    </div>

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.btn-delete-issue').forEach(btn => {
            btn.addEventListener('click', function () {
                const formId = this.dataset.formId;
                if (confirm('Apakah Anda yakin ingin membatalkan/menghapus pengeluaran barang ini? Stok barang akan dikembalikan.')) {
                    document.getElementById(formId)?.submit();
                }
            });
        });
    });
</script>
@endpush
