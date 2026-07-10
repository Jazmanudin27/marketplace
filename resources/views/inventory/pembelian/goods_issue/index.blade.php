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
                            <td class="small text-muted text-wrap" style="max-width: 300px;">
                                {{ $row->notes ?: '—' }}
                            </td>
                            <td class="text-center fw-bold text-dark small">
                                {{ number_format($row->items->count()) }}
                            </td>
                            <td class="small text-muted">
                                {{ $row->createdBy->name ?? 'System' }}
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="{{ route('pembelian.goods_issue.show', $row) }}"
                                        class="btn btn-sm btn-outline-success">
                                        <i class="fas fa-eye"></i> Detail
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
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
