<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Laporan Rekap Penjualan Produk ({{ date('d/m/Y', strtotime($dateFrom)) }} - {{ date('d/m/Y', strtotime($dateTo)) }})</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #000; background: #fff; }
        .table-print th, .table-print td { padding: 4px 6px; border: 1px solid #ddd; }
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
        <h4 class="fw-bold mb-1">LAPORAN REKAP PENJUALAN PRODUK</h4>
        <div class="small text-muted">Periode Penjualan: {{ date('d F Y', strtotime($dateFrom)) }} s/d {{ date('d F Y', strtotime($dateTo)) }}</div>
    </div>



    <table class="table table-print w-100">
        <thead>
            <tr>
                <th style="width: 30px;">No</th>
                <th>SKU</th>
                <th>Nama Produk</th>
                <th>Kategori</th>
                <th>Brand</th>
                <th class="text-center">Stok</th>
                <th class="text-center">Qty POS</th>
                <th class="text-center">Qty MP</th>
                <th class="text-center">Total Terjual</th>
                <th class="text-end">HPP Modal</th>
                <th class="text-end">Total Omset</th>
                <th class="text-end">Total HPP</th>
                <th class="text-end">Laba Kotor</th>
                <th class="text-center">Margin</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $idx => $row)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="font-monospace">{{ $row['sku'] ?: '—' }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['category_name'] }}</td>
                    <td>{{ $row['brand_name'] }}</td>
                    <td class="text-center">{{ number_format($row['stock']) }}</td>
                    <td class="text-center">{{ number_format($row['qty_offline']) }}</td>
                    <td class="text-center">{{ number_format($row['qty_online']) }}</td>
                    <td class="text-center fw-bold">{{ number_format($row['qty_total']) }}</td>
                    <td class="text-end">Rp {{ number_format($row['cost_price'], 0, ',', '.') }}</td>
                    <td class="text-end fw-bold">Rp {{ number_format($row['total_omset'], 0, ',', '.') }}</td>
                    <td class="text-end">Rp {{ number_format($row['total_hpp'], 0, ',', '.') }}</td>
                    <td class="text-end fw-bold">Rp {{ number_format($row['gross_profit'], 0, ',', '.') }}</td>
                    <td class="text-center">{{ number_format($row['profit_margin'], 1) }}%</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="fw-bold bg-light">
                <td colspan="6" class="text-end">TOTAL REKAPITULASI:</td>
                <td class="text-center"></td>
                <td class="text-center"></td>
                <td class="text-center">{{ number_format($grandTotalQty) }}</td>
                <td></td>
                <td class="text-end">Rp {{ number_format($grandTotalOmset, 0, ',', '.') }}</td>
                <td class="text-end">Rp {{ number_format($grandTotalHpp, 0, ',', '.') }}</td>
                <td class="text-end">Rp {{ number_format($grandTotalProfit, 0, ',', '.') }}</td>
                <td class="text-center">{{ number_format($overallMargin, 1) }}%</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
