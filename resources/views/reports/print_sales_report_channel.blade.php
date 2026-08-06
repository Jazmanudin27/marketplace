<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Laporan Penjualan Per Channel / Saluran ({{ date('d/m/Y', strtotime($dateFrom)) }} - {{ date('d/m/Y', strtotime($dateTo)) }})</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #000; background: #fff; }
        .table-print th, .table-print td { padding: 6px 8px; border: 1px solid #ddd; }
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
        <h4 class="fw-bold mb-1">LAPORAN PENJUALAN PER SALURAN / CHANNEL</h4>
        <div class="small text-muted">Periode: {{ date('d F Y', strtotime($dateFrom)) }} s/d {{ date('d F Y', strtotime($dateTo)) }}</div>
    </div>

    <table class="table table-print w-100 align-middle">
        <thead>
            <tr>
                <th style="width: 30px;">No</th>
                <th>Saluran Penjualan / Nama Toko</th>
                <th>Tipe Saluran</th>
                <th class="text-center">Jumlah Transaksi</th>
                <th class="text-center">Total Item Terjual</th>
                <th class="text-end">Total Omset Penjualan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($channels as $idx => $row)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="fw-bold">{{ $row['name'] }}</td>
                    <td><span class="badge bg-secondary">{{ $row['type'] }}</span></td>
                    <td class="text-center fw-bold">{{ number_format($row['orders']) }}</td>
                    <td class="text-center font-monospace">{{ number_format($row['qty']) }}</td>
                    <td class="text-end font-monospace fw-bold text-primary">Rp {{ number_format($row['omset'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="fw-bold bg-light">
                <td colspan="3" class="text-end">TOTAL REKAPITULASI:</td>
                <td class="text-center">{{ number_format($grandTotalOrders) }}</td>
                <td class="text-center">{{ number_format($grandTotalQty) }}</td>
                <td class="text-end fs-6 text-primary">Rp {{ number_format($grandTotalOmset, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
