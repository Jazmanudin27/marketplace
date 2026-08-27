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
        .table-container { width: 100%; overflow-x: auto; }
        .table-print { min-width: 100%; width: 125%; max-width: 130%; }
        @media print {
            .no-print { display: none !important; }
            @page { size: landscape; margin: 10mm; }
            .table-print { width: 100% !important; min-width: 100% !important; max-width: 100% !important; }
        }
    </style>
</head>
<body class="p-3">
    <div class="no-print mb-3 text-end">
        <button onclick="window.print()" class="btn btn-primary btn-sm px-3"><i class="fas fa-print"></i> Cetak / Save PDF</button>
    </div>

    <div class="text-center mb-3">
        <h4 class="fw-bold mb-1">{{ strtoupper($title ?? 'LAPORAN REKAP PENJUALAN PRODUK') }}</h4>
        <div class="small text-muted">Periode Penjualan: {{ date('d F Y', strtotime($dateFrom)) }} s/d {{ date('d F Y', strtotime($dateTo)) }}</div>
    </div>



    <div class="row mb-3">
        <div class="col-6">
            <div class="p-2 border rounded text-center">
                <small class="text-muted d-block" style="font-size: 9px;">TOTAL OMSET KOTOR</small>
                <strong class="fs-6 text-primary">Rp {{ number_format($grandTotalOmset, 0, ',', '.') }}</strong>
            </div>
        </div>
        <div class="col-6">
            <div class="p-2 border rounded text-center bg-success bg-opacity-10">
                <small class="text-muted d-block" style="font-size: 9px;">TOTAL ITEM TERJUAL</small>
                <strong class="fs-6 text-success">{{ number_format($grandTotalQty) }} Pcs</strong>
            </div>
        </div>
    </div>

    <div class="table-container">
        <table class="table table-print align-middle">
            <thead>
                <tr>
                    <th style="width: 30px;">No</th>
                    <th>SKU</th>
                    <th>Nama Produk</th>
                    <th class="text-center">Qty</th>
                    <th class="text-end">Harga</th>
                    <th class="text-end">Omset Kotor</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $idx => $row)
                    @php
                        $hargaUnit = $row['qty_total'] > 0 ? $row['total_omset'] / $row['qty_total'] : 0;
                    @endphp
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td class="font-monospace small">{{ $row['sku'] ?: '—' }}</td>
                        <td class="fw-bold">{{ $row['name'] }}</td>
                        <td class="text-center font-monospace fw-bold">{{ number_format($row['qty_total']) }}</td>
                        <td class="text-end font-monospace">Rp {{ number_format($hargaUnit, 0, ',', '.') }}</td>
                        <td class="text-end font-monospace fw-bold text-primary">Rp {{ number_format($row['total_omset'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="fw-bold bg-light">
                    <td colspan="3" class="text-end">TOTAL REKAPITULASI:</td>
                    <td class="text-center font-monospace">{{ number_format($grandTotalQty) }}</td>
                    <td></td>
                    <td class="text-end text-primary">Rp {{ number_format($grandTotalOmset, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</body>
</html>
