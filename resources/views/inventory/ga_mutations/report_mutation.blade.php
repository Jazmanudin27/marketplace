@extends('layouts.app')
@section('title', 'Laporan Barang Masuk & Keluar GA')
@section('page-title', 'Laporan Mutasi General Affair')

@section('content')
<div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
    <div class="card-body p-4">
        <h5 class="fw-bold text-dark mb-4">
            <i class="fas fa-file-invoice me-2" style="color:#8b5cf6"></i>Filter Laporan Barang Masuk &amp; Keluar GA
        </h5>

        <form method="GET" action="{{ route('ga_mutations.report_mutation') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-muted">Tipe Mutasi</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="all" {{ $type === 'all' ? 'selected' : '' }}>Semua Mutasi (Masuk &amp; Keluar)</option>
                    <option value="in"  {{ $type === 'in' ? 'selected' : '' }}>Hanya Barang Masuk</option>
                    <option value="out" {{ $type === 'out' ? 'selected' : '' }}>Hanya Barang Keluar</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-muted">Dari Tanggal</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-muted">Sampai Tanggal</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo }}">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm px-4 w-100 fw-semibold">
                    <i class="fas fa-filter me-1"></i> Tampilkan
                </button>
                <a href="{{ route('ga_mutations.print_report_mutation', ['type' => $type, 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}"
                   target="_blank" class="btn btn-sm px-4 w-100 fw-semibold text-white"
                   style="background:linear-gradient(135deg,#8b5cf6,#6d28d9)">
                    <i class="fas fa-print me-1"></i> Cetak
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-3 bg-white">
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover border align-middle mb-0 rounded-2 overflow-hidden">
                <thead style="background:#f3f0ff">
                    <tr class="small text-uppercase text-muted">
                        <th class="py-2 px-3">Tanggal</th>
                        <th>No. Transaksi</th>
                        <th>Barang / SKU</th>
                        <th>Kategori</th>
                        <th class="text-center">Tipe</th>
                        <th>Asal</th>
                        <th>Tujuan</th>
                        <th class="text-center px-3">Qty</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $row)
                        <tr>
                            <td class="small text-muted py-3 px-3">{{ $row->warehouseMutation->mutation_date->format('d M Y') }}</td>
                            <td class="font-monospace fw-bold text-dark small">{{ $row->warehouseMutation->mutation_number }}</td>
                            <td>
                                <div class="fw-semibold text-dark small">{{ $row->inventoryItem?->name ?? '—' }}</div>
                                <span class="font-monospace text-muted" style="font-size:11px">{{ $row->inventoryItem?->sku ?? '-' }}</span>
                            </td>
                            <td>
                                @php
                                    $catColors = ['atk'=>'background:#ede9fe;color:#5b21b6','inventaris'=>'background:#dbeafe;color:#1e40af'];
                                    $cs = $catColors[$row->inventoryItem?->type] ?? 'background:#f1f5f9;color:#475569';
                                @endphp
                                <span class="badge rounded-pill" style="font-size:10px;{{ $cs }}">{{ ucfirst($row->inventoryItem?->type ?? '—') }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge text-white small text-uppercase"
                                    style="background:{{ $row->warehouseMutation->type === 'in' ? '#8b5cf6' : '#f59e0b' }}">
                                    {{ $row->warehouseMutation->type === 'in' ? 'Masuk' : 'Keluar' }}
                                </span>
                            </td>
                            <td class="small text-muted">
                                {{ $row->warehouseMutation->fromDepartment ? $row->warehouseMutation->fromDepartment->name : 'Gudang GA' }}
                            </td>
                            <td class="small text-muted">
                                {{ $row->warehouseMutation->toDepartment ? $row->warehouseMutation->toDepartment->name : 'Gudang GA' }}
                            </td>
                            <td class="text-center fw-bold text-dark px-3 small">{{ number_format($row->quantity) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fas fa-file-alt fa-2x mb-3 opacity-25 d-block"></i>
                                Tidak ada data mutasi GA pada periode terpilih.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
