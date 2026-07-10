@extends('layouts.app')
@section('title', 'Laporan Mutasi Barang - Pembelian')
@section('page-title', 'Laporan Mutasi Barang')

@section('content')
<div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
    <div class="card-body p-4">
        <h5 class="fw-bold text-dark mb-4">
            <i class="fas fa-file-invoice me-2" style="color:#10b981"></i>Filter Laporan Barang Masuk &amp; Keluar
        </h5>

        <form method="GET" action="{{ route('pembelian.report_mutation') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-muted">Tipe Mutasi</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="all" {{ $type === 'all' ? 'selected' : '' }}>Semua Mutasi (Masuk &amp; Keluar)</option>
                    <option value="in"  {{ $type === 'in' ? 'selected' : '' }}>Hanya Barang Masuk</option>
                    <option value="out" {{ $type === 'out' ? 'selected' : '' }}>Hanya Barang Keluar</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold text-muted">Kategori Barang</label>
                <select name="item_type" class="form-select form-select-sm">
                    <option value="all" {{ $itemType === 'all' ? 'selected' : '' }}>Semua Tipe</option>
                    <option value="bahan" {{ $itemType === 'bahan' ? 'selected' : '' }}>Bahan Baku</option>
                    <option value="kemasan" {{ $itemType === 'kemasan' ? 'selected' : '' }}>Kemasan</option>
                    <option value="atk" {{ $itemType === 'atk' ? 'selected' : '' }}>ATK</option>
                    <option value="inventaris" {{ $itemType === 'inventaris' ? 'selected' : '' }}>Inventaris</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold text-muted">Dari Tanggal</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold text-muted">Sampai Tanggal</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo }}">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-success btn-sm px-4 w-100 fw-semibold">
                    <i class="fas fa-filter me-1"></i> Tampilkan
                </button>
                <a href="{{ route('pembelian.print_report_mutation', ['type' => $type, 'item_type' => $itemType, 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}"
                   target="_blank" class="btn btn-sm px-4 w-100 fw-semibold text-white"
                   style="background:linear-gradient(135deg,#10b981,#059669)">
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
                <thead style="background:#ecfdf5">
                    <tr class="small text-uppercase text-muted text-success">
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
                    @forelse($mutations as $row)
                        @php
                            $catColors = [
                                'bahan' => 'background:#e0f2fe;color:#0369a1',
                                'kemasan' => 'background:#fef3c7;color:#b45309',
                                'atk' => 'background:#ede9fe;color:#5b21b6',
                                'inventaris' => 'background:#dbeafe;color:#1e40af'
                            ];
                            $cs = $catColors[$row->inventoryItem->type] ?? 'background:#f1f5f9;color:#475569';
                            $mDate = $row->warehouseMutation->mutation_date ? $row->warehouseMutation->mutation_date->format('d M Y') : '—';
                        @endphp
                        <tr>
                            <td class="small text-muted py-3 px-3">{{ $mDate }}</td>
                            <td class="font-monospace fw-bold small text-dark">
                                {{ $row->warehouseMutation->mutation_number }}
                            </td>
                            <td>
                                <div class="fw-semibold text-dark small">{{ $row->inventoryItem->name }}</div>
                                <div class="text-muted font-monospace" style="font-size:10px">SKU: {{ $row->inventoryItem->sku ?: '—' }}</div>
                            </td>
                            <td>
                                <span class="badge text-uppercase" style="{{ $cs }};font-size:9px">{{ $row->inventoryItem->type }}</span>
                            </td>
                            <td class="text-center">
                                @if($row->warehouseMutation->type === 'in')
                                    <span class="badge bg-success text-uppercase" style="font-size:9px">Masuk</span>
                                @else
                                    <span class="badge bg-warning text-dark text-uppercase" style="font-size:9px">Keluar</span>
                                @endif
                            </td>
                            <td class="small text-muted">
                                {{ $row->warehouseMutation->fromDepartment ? $row->warehouseMutation->fromDepartment->name : 'Gudang Utama' }}
                            </td>
                            <td class="small text-muted">
                                {{ $row->warehouseMutation->toDepartment ? $row->warehouseMutation->toDepartment->name : 'Gudang Utama' }}
                            </td>
                            <td class="text-center fw-bold text-dark px-3 small">{{ number_format($row->quantity) }} {{ $row->inventoryItem->unit }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fas fa-file-invoice fa-2x mb-3 opacity-25 d-block"></i>
                                Tidak ada data mutasi untuk periode ini.
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
