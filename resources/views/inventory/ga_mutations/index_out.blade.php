@extends('layouts.app')
@section('title', 'Log Barang Keluar - Gudang Logistik')
@section('page-title', 'Barang Keluar')

@section('content')
    <div class="card border-0 shadow-sm rounded-3 bg-white">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                        style="width:42px;height:42px;background:linear-gradient(135deg,#f59e0b,#d97706)">
                        <i class="fas fa-sign-out-alt text-white"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-dark mb-0">Barang Keluar (WMO)</h5>
                        <div class="text-muted small">Log transaksi penyaluran barang dari Gudang Logistik</div>
                    </div>
                </div>
                <a href="{{ route('ga_mutations.create_out') }}" class="btn fw-semibold btn-sm px-3 text-white"
                    style="background:linear-gradient(135deg,#f59e0b,#d97706)">
                    <i class="fas fa-plus me-1"></i> Catat Barang Keluar
                </a>
            </div>

            {{-- Filter --}}
            <form method="GET" class="row g-2 mb-4 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold text-muted">Cari No. Mutasi</label>
                    <input type="text" name="search" class="form-control form-control-sm"
                        value="{{ request('search') }}" placeholder="WMO-2026...">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold text-muted">Departemen Tujuan</label>
                    <select name="department_id" class="form-select form-select-sm">
                        <option value="">Semua Departemen</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept->id }}"
                                {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label small fw-semibold text-muted">Dari Tanggal</label>
                    <input type="date" name="date_from" class="form-control form-control-sm"
                        value="{{ request('date_from') }}">
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label small fw-semibold text-muted">Sampai</label>
                    <input type="date" name="date_to" class="form-control form-control-sm"
                        value="{{ request('date_to') }}">
                </div>
                <div class="col-12 col-md-auto d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm px-3">
                        <i class="fas fa-search me-1"></i> Filter
                    </button>
                    @if (request()->anyFilled(['search', 'department_id', 'date_from', 'date_to']))
                        <a href="{{ route('ga_mutations.index_out') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                    @endif
                </div>
            </form>

            {{-- Table --}}
            <div class="table-responsive">
                <table class="table table-hover border align-middle mb-0 rounded-2 overflow-hidden">
                    <thead style="background:#fef9ec">
                        <tr class="small text-uppercase text-muted">
                            <th class="py-2 px-3">No. Mutasi Keluar</th>
                            <th>Gudang Inventory</th>
                            <th>Ke Departemen</th>
                            <th>Tanggal</th>
                            <th>Catatan</th>
                            <th class="text-center">Total Item</th>
                            <th class="text-center">Status</th>
                            <th class="text-center" style="width:80px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($mutations as $m)
                            <tr>
                                <td class="font-monospace fw-bold text-dark px-3 py-3" style="font-size:13px">
                                    {{ $m->mutation_number }}
                                </td>
                                <td>
                                    <span class="badge text-white" style="background:#8b5cf6">Gudang Inventory</span>
                                </td>
                                <td>
                                    @if ($m->toDepartment)
                                        <span class="badge bg-light text-dark border">{{ $m->toDepartment->name }}</span>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td>{{ $m->mutation_date->format('d M Y') }}</td>
                                <td class="small text-muted" style="max-width:250px">
                                    <div class="text-truncate" title="{{ $m->notes }}">{{ $m->notes ?: '—' }}</div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary rounded-pill small">
                                        {{ $m->items->count() }} item
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $m->status_badge }}">{{ $m->status_label }}</span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('ga_mutations.show', $m) }}" class="btn btn-info btn-sm text-white"
                                        title="Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fas fa-sign-out-alt fa-2x mb-3 opacity-25 d-block"></i>
                                    Belum ada log barang keluar yang tercatat.
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
