@extends('layouts.app')
@section('title', 'Laporan Rekap Persediaan')
@section('page-title', 'Laporan Rekap Persediaan')

@section('content')
<div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
    <div class="card-body p-4">
        <h5 class="fw-bold text-dark mb-4">
            <i class="fas fa-boxes text-success me-2"></i>Filter Rekap Persediaan (Bahan &amp; Kemasan)
        </h5>

        <form method="GET" action="{{ route('warehouse_mutations.report_summary') }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold text-muted">Dari Tanggal</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold text-muted">Sampai Tanggal</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo }}">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm px-4 w-100 fw-semibold">
                    <i class="fas fa-filter me-1"></i> Tampilkan
                </button>
                <a href="{{ route('warehouse_mutations.print_report_summary', ['date_from' => $dateFrom, 'date_to' => $dateTo]) }}" 
                   target="_blank" class="btn btn-success btn-sm px-4 w-100 fw-semibold">
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
                <thead style="background:#f8fafc">
                    <tr class="small text-uppercase text-muted">
                        <th class="py-2 px-3">SKU</th>
                        <th>Nama Barang</th>
                        <th>Kategori</th>
                        <th class="text-center">Stok Awal</th>
                        <th class="text-center text-success">Masuk (+)</th>
                        <th class="text-center text-danger">Keluar (-)</th>
                        <th class="text-center fw-bold">Stok Akhir</th>
                        <th>Satuan</th>
                        <th class="text-end">Harga Pokok</th>
                        <th class="text-end px-3">Total Nilai</th>
                    </tr>
                </thead>
                <tbody>
                    @php $grandValueTotal = 0; @endphp
                    @forelse($rekap as $row)
                        @php $grandValueTotal += $row['total_value']; @endphp
                        <tr>
                            <td class="font-monospace fw-bold text-muted py-3 px-3" style="font-size:12px">{{ $row['sku'] ?: '—' }}</td>
                            <td class="fw-semibold text-dark small">{{ $row['name'] }}</td>
                            <td>
                                <span class="badge bg-light text-dark border rounded-pill">{{ ucfirst($row['type']) }}</span>
                            </td>
                            <td class="text-center font-monospace text-secondary">{{ number_format($row['stok_awal']) }}</td>
                            <td class="text-center font-monospace text-success">+{{ number_format($row['qty_masuk']) }}</td>
                            <td class="text-center font-monospace text-danger">-{{ number_format($row['qty_keluar']) }}</td>
                            <td class="text-center font-monospace fw-bold text-dark" style="font-size:14px">{{ number_format($row['stok_akhir']) }}</td>
                            <td class="small text-muted">{{ $row['unit'] ?: 'pcs' }}</td>
                            <td class="font-monospace text-end text-muted small">Rp {{ number_format($row['cost_price'], 0, ',', '.') }}</td>
                            <td class="font-monospace text-end fw-bold text-success px-3 small">Rp {{ number_format($row['total_value'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                Tidak ada data barang terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot style="background:#f0fdf4">
                    <tr class="fw-bold">
                        <td colspan="9" class="text-end py-3 px-3 text-dark">TOTAL NILAI PERSEDIAAN GUDANG</td>
                        <td class="text-end font-monospace text-success px-3" style="font-size:16px">Rp {{ number_format($grandValueTotal, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
