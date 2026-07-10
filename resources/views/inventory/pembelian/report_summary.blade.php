@extends('layouts.app')
@section('title', 'Rekap Persediaan Barang - Pembelian')
@section('page-title', 'Rekap Persediaan')

@section('content')
<div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
    <div class="card-body p-4">
        <h5 class="fw-bold text-dark mb-4">
            <i class="fas fa-boxes me-2" style="color:#10b981"></i>Filter Rekap Persediaan
        </h5>

        <form method="GET" action="{{ route('pembelian.report_summary') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-muted">Kategori Barang</label>
                <select name="item_type" class="form-select form-select-sm">
                    <option value="all" {{ $itemType === 'all' ? 'selected' : '' }}>Semua Tipe</option>
                    <option value="bahan" {{ $itemType === 'bahan' ? 'selected' : '' }}>Bahan Baku</option>
                    <option value="kemasan" {{ $itemType === 'kemasan' ? 'selected' : '' }}>Kemasan</option>
                    <option value="atk" {{ $itemType === 'atk' ? 'selected' : '' }}>ATK</option>
                    <option value="inventaris" {{ $itemType === 'inventaris' ? 'selected' : '' }}>Inventaris</option>
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
                <button type="submit" class="btn btn-success btn-sm px-4 w-100 fw-semibold">
                    <i class="fas fa-filter me-1"></i> Tampilkan
                </button>
                <a href="{{ route('pembelian.print_report_summary', ['item_type' => $itemType, 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}"
                   target="_blank" class="btn btn-sm px-4 w-100 fw-semibold text-white"
                   style="background:linear-gradient(135deg,#10b981,#059669)">
                    <i class="fas fa-print me-1"></i> Cetak Rekap
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
                        <th class="py-2 px-3">SKU</th>
                        <th>Nama Barang</th>
                        <th>Kategori</th>
                        <th class="text-center">Stok Awal</th>
                        <th class="text-center text-success">Masuk (+)</th>
                        <th class="text-center text-danger">Keluar (-)</th>
                        <th class="text-center fw-bold">Stok Akhir</th>
                        <th>Satuan</th>
                        <th class="text-end">Nilai Barang</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rekap as $row)
                        @php
                            $catColors = [
                                'bahan' => 'background:#e0f2fe;color:#0369a1',
                                'kemasan' => 'background:#fef3c7;color:#b45309',
                                'atk' => 'background:#ede9fe;color:#5b21b6',
                                'inventaris' => 'background:#dbeafe;color:#1e40af'
                            ];
                            $cs = $catColors[$row['type']] ?? 'background:#f1f5f9;color:#475569';
                        @endphp
                        <tr>
                            <td class="font-monospace fw-bold text-muted py-3 px-3" style="font-size:12px">{{ $row['sku'] ?: '—' }}</td>
                            <td class="fw-semibold text-dark small">{{ $row['name'] }}</td>
                            <td>
                                <span class="badge text-uppercase" style="{{ $cs }};font-size:9px">{{ $row['type'] }}</span>
                            </td>
                            <td class="text-center small">{{ number_format($row['stok_awal']) }}</td>
                            <td class="text-center text-success small fw-semibold">+{{ number_format($row['qty_masuk']) }}</td>
                            <td class="text-center text-danger small fw-semibold">-{{ number_format($row['qty_keluar']) }}</td>
                            <td class="text-center fw-bold small">{{ number_format($row['stok_akhir']) }}</td>
                            <td class="text-muted small">{{ $row['unit'] }}</td>
                            <td class="text-end font-monospace small">Rp {{ number_format($row['total_value'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="fas fa-boxes fa-2x mb-3 opacity-25 d-block"></i>
                                Tidak ada data rekap persediaan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
