@extends('layouts.app')
@section('title', 'Daftar Transfer Stok')
@section('page-title', 'Transfer Stok Antar Departemen')

@section('content')
<div class="card border-0 shadow-sm rounded-3 bg-white">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:42px;height:42px;background:linear-gradient(135deg,#8b5cf6,#6d28d9)">
                    <i class="fas fa-exchange-alt text-white"></i>
                </div>
                <div>
                    <h5 class="fw-bold text-dark mb-0">Transfer Stok</h5>
                    <div class="text-muted small">Distribusi barang antar departemen</div>
                </div>
            </div>
            <a href="{{ route('stock_transfers.create') }}" class="btn fw-semibold btn-sm px-3 text-white" style="background:linear-gradient(135deg,#8b5cf6,#6d28d9)">
                <i class="fas fa-plus me-1"></i> Buat Transfer Baru
            </a>
        </div>

        {{-- Filter --}}
        <form method="GET" class="row g-2 mb-4 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label small fw-semibold text-muted">Cari No. Transfer</label>
                <input type="text" name="search" class="form-control form-control-sm" value="{{ request('search') }}" placeholder="TRF-2026...">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small fw-semibold text-muted">Dari Departemen</label>
                <select name="from_department_id" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ request('from_department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small fw-semibold text-muted">Ke Departemen</label>
                <select name="to_department_id" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ request('to_department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm px-3"><i class="fas fa-search me-1"></i> Filter</button>
                @if(request()->anyFilled(['search','from_department_id','to_department_id','status']))
                    <a href="{{ route('stock_transfers.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                @endif
            </div>
        </form>

        {{-- Table --}}
        <div class="table-responsive">
            <table class="table table-hover border align-middle mb-0 rounded-2 overflow-hidden">
                <thead style="background:#f5f3ff">
                    <tr class="small text-uppercase text-muted">
                        <th class="py-2 px-3">No. Transfer</th>
                        <th>Dari Dept.</th>
                        <th>Ke Dept.</th>
                        <th>Tanggal</th>
                        <th class="text-center">Jml Item</th>
                        <th>Status</th>
                        <th class="text-center" style="width:80px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transfers as $transfer)
                        <tr>
                            <td class="font-monospace fw-bold text-dark px-3 py-3 small">{{ $transfer->transfer_number }}</td>
                            <td>
                                <span class="badge rounded-pill" style="background:#ede9fe;color:#6d28d9;font-size:12px">
                                    <i class="fas fa-arrow-up me-1"></i>
                                    {{ $transfer->fromDepartment ? $transfer->fromDepartment->name : 'Umum' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge rounded-pill" style="background:#d1fae5;color:#065f46;font-size:12px">
                                    <i class="fas fa-arrow-down me-1"></i>
                                    {{ $transfer->toDepartment ? $transfer->toDepartment->name : '-' }}
                                </span>
                            </td>
                            <td class="small text-muted">{{ $transfer->transfer_date->format('d M Y') }}</td>
                            <td class="text-center">
                                <span class="badge bg-secondary rounded-pill">{{ $transfer->items->count() }} item</span>
                            </td>
                            <td>
                                <span class="badge bg-{{ $transfer->status_badge }} py-1 px-2 small text-uppercase">
                                    {{ $transfer->status_label }}
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('stock_transfers.show', $transfer) }}" class="btn btn-info btn-sm text-white" title="Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fas fa-exchange-alt fa-2x mb-3 opacity-25 d-block"></i>
                                Belum ada transfer stok yang tercatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">{{ $transfers->links() }}</div>
    </div>
</div>
@endsection
