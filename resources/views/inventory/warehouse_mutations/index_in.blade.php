@extends('layouts.app')
@section('title', 'Log Barang Masuk')
@section('page-title', 'Barang Masuk Gudang')

@section('content')
<div class="card border-0 shadow-sm rounded-3 bg-white">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle d-flex align-items-center justify-content-center"
                    style="width:42px;height:42px;background:linear-gradient(135deg,#10b981,#059669)">
                    <i class="fas fa-sign-in-alt text-white"></i>
                </div>
                <div>
                    <h5 class="fw-bold text-dark mb-0">Barang Masuk Gudang (WMI)</h5>
                    <div class="text-muted small">Log transaksi barang masuk ke Gudang Bahan &amp; Kemasan</div>
                </div>
            </div>
            <a href="{{ route('warehouse_mutations.create_in') }}" class="btn fw-semibold btn-sm px-3 text-white"
                style="background:linear-gradient(135deg,#10b981,#059669)">
                <i class="fas fa-plus me-1"></i> Catat Barang Masuk
            </a>
        </div>

        {{-- Filter --}}
        <form method="GET" class="row g-2 mb-4 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label small fw-semibold text-muted">Cari No. Mutasi</label>
                <input type="text" name="search" class="form-control form-control-sm"
                    value="{{ request('search') }}" placeholder="WMI-2026...">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small fw-semibold text-muted">Departemen Asal</label>
                <select name="department_id" class="form-select form-select-sm">
                    <option value="">Semua Departemen</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label small fw-semibold text-muted">Dari Tanggal</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label small fw-semibold text-muted">Sampai</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
            </div>
            <div class="col-12 col-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm px-3">
                    <i class="fas fa-search me-1"></i> Filter
                </button>
                @if(request()->anyFilled(['search','department_id','date_from','date_to']))
                    <a href="{{ route('warehouse_mutations.index_in') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                @endif
            </div>
        </form>

        {{-- Table --}}
        <div class="table-responsive">
            <table class="table table-hover border align-middle mb-0 rounded-2 overflow-hidden">
                <thead style="background:#ecfdf5">
                    <tr class="small text-uppercase text-muted">
                        <th class="py-2 px-3">No. Mutasi Masuk</th>
                        <th>Departemen Asal</th>
                        <th>Tujuan</th>
                        <th>Tanggal</th>
                        <th>Referensi / Catatan</th>
                        <th class="text-center">Total Item</th>
                        <th class="text-center" style="width:100px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mutations as $m)
                        <tr>
                            <td class="font-monospace fw-bold text-dark px-3 py-3" style="font-size:13px">
                                {{ $m->mutation_number }}
                            </td>
                            <td>
                                @if($m->fromDepartment)
                                    <span class="badge bg-light text-dark border">{{ $m->fromDepartment->name }}</span>
                                @elseif($m->goodsReceipt)
                                    <span class="badge bg-success text-white">Pembelian / Supplier</span>
                                @else
                                    <span class="text-muted small">Eksternal / Toko</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-info text-white">{{ $m->toDepartment ? $m->toDepartment->name : 'Gudang Bahan Kemasan' }}</span>
                            </td>
                            <td class="small text-muted">{{ $m->mutation_date->format('d M Y') }}</td>
                            <td class="small text-muted" style="max-width:250px">
                                <div class="text-truncate" title="{{ $m->notes }}">{{ $m->notes ?: '—' }}</div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary rounded-pill small">
                                    {{ $m->items->count() }} item
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="{{ route('warehouse_mutations.show', $m) }}"
                                        class="btn btn-info btn-sm text-white" title="Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if(!$m->goods_receipt_id)
                                        <form action="{{ route('warehouse_mutations.destroy', $m) }}" method="POST"
                                            onsubmit="return confirm('Hapus/Batalkan mutasi ini? Stok barang akan dikurangi kembali.')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" title="Batal & Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fas fa-sign-in-alt fa-2x mb-3 opacity-25 d-block"></i>
                                Belum ada log barang masuk yang tercatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">{{ $mutations->links() }}</div>
    </div>
</div>
@endsection
