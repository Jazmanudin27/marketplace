<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Laporan Penjualan Per Tanggal ({{ date('d/m/Y', strtotime($dateFrom)) }} - {{ date('d/m/Y', strtotime($dateTo)) }})</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #000; background: #fff; }
        .table-print th, .table-print td { padding: 5px 8px; border: 1px solid #ddd; }
        .table-print th { background-color: #f2f2f2 !important; text-transform: uppercase; font-size: 10px; }
        @media print {
            .no-print { display: none !important; }
            @page { size: landscape; margin: 10mm; }
        }
    </style>
</head>
<body class="p-3">
    <div class="no-print mb-3 text-end">
        <button onclick="window.print()" class="btn btn-primary btn-sm px-3"><i class="fas fa-print"></i> Cetak / Save PDF</button>
    </div>

    <div class="text-center mb-3">
        <h4 class="fw-bold mb-1">{{ strtoupper($title ?? 'LAPORAN REKAP PENJUALAN PER TANGGAL') }}</h4>
        <div class="small text-muted">Periode Penjualan: {{ date('d F Y', strtotime($dateFrom)) }} s/d {{ date('d F Y', strtotime($dateTo)) }}</div>
    </div>

    <div class="row mb-3">
        <div class="col-6">
            <div class="p-2.5 border rounded text-center">
                <small class="text-muted d-block">TOTAL OMSET PENJUALAN</small>
                <strong class="fs-6 text-primary">Rp {{ number_format($grandTotalOmset, 0, ',', '.') }}</strong>
            </div>
        </div>
        <div class="col-6">
            <div class="p-2.5 border rounded text-center">
                <small class="text-muted d-block">TOTAL QTY TERJUAL</small>
                <strong class="fs-6 text-success">{{ number_format($grandTotalQty) }} Pcs</strong>
            </div>
        </div>
    </div>

    <table class="table table-print w-100 align-middle">
        <thead>
            <tr>
                <th style="width: 30px;">No</th>
                <th>Tanggal Harian</th>
                <th class="text-center">Qty POS Offline</th>
                <th class="text-end">Omset POS Offline</th>
                <th class="text-center">Qty Online MP</th>
                <th class="text-end">Omset Online MP</th>
                <th class="text-center fw-bold">Total Qty Terjual</th>
                <th class="text-end fw-bold">Total Omset Harian</th>
            </tr>
        </thead>
        <tbody>
            @forelse($dates as $idx => $row)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="fw-bold font-monospace">{{ date('d F Y (l)', strtotime($row['date'])) }}</td>
                    <td class="text-center font-monospace">{{ number_format($row['qty_offline']) }}</td>
                    <td class="text-end font-monospace">Rp {{ number_format($row['omset_offline'], 0, ',', '.') }}</td>
                    <td class="text-center font-monospace">{{ number_format($row['qty_online']) }}</td>
                    <td class="text-end font-monospace">Rp {{ number_format($row['omset_online'], 0, ',', '.') }}</td>
                    <td class="text-center font-monospace fw-bold">{{ number_format($row['total_qty']) }}</td>
                    <td class="text-end font-monospace fw-bold text-primary">Rp {{ number_format($row['total_omset'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center py-3 text-muted">Tidak ada data harian penjualan ditemukan.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="fw-bold bg-light">
                <td colspan="6" class="text-end">TOTAL REKAPITULASI HARIAN:</td>
                <td class="text-center font-monospace">{{ number_format($grandTotalQty) }}</td>
                <td class="text-end font-monospace text-primary fs-6">Rp {{ number_format($grandTotalOmset, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
