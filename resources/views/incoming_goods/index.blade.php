@extends('layouts.app')
@section('title', 'Riwayat Barang Masuk')
@section('page-title', 'Riwayat Penerimaan Barang')

@section('content')
    <div class="dashboard-card">
        <div class="card-header-line d-flex justify-content-between align-items-center mb-4">
            <h3><i class="fas fa-history"></i> Riwayat Barang Masuk</h3>
            <a href="{{ route('incoming_goods.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Tambah Penerimaan
            </a>
        </div>

        <form action="{{ route('incoming_goods.index') }}" method="GET" class="mb-4">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label text-muted" style="font-size: 0.85rem;">Pencarian Referensi</label>
                    <input type="text" name="search" class="form-control form-control-sm form-control-dark"
                           placeholder="Cari referensi penerimaan..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted" style="font-size: 0.85rem;">Dari Tanggal</label>
                    <input type="date" name="start_date" class="form-control form-control-sm form-control-dark"
                           value="{{ request('start_date') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted" style="font-size: 0.85rem;">Sampai Tanggal</label>
                    <input type="date" name="end_date" class="form-control form-control-sm form-control-dark"
                           value="{{ request('end_date') }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-search"></i> Filter
                    </button>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table erp-table">
                <thead>
                    <tr>
                        <th>Tanggal Penerimaan</th>
                        <th>Nomor Referensi</th>
                        <th class="text-center">Total Jenis Item</th>
                        <th class="text-center">Total Qty Masuk</th>
                        <th>Diterima Oleh</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($incomings as $in)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($in->created_at)->format('d M Y H:i') }}</td>
                            <td>
                                <strong>{{ $in->reference }}</strong>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary">{{ $in->total_items }} Item</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-success">{{ number_format($in->total_qty) }} Pcs</span>
                            </td>
                            <td>{{ $in->user->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Belum ada data riwayat penerimaan barang.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $incomings->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection
