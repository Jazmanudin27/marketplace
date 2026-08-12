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
        <h4 class="fw-bold mb-1">{{ strtoupper($title ?? 'LAPORAN REKAP PENJUALAN PRODUK') }}</h4>
        <div class="small text-muted">Periode Penjualan: {{ date('d F Y', strtotime($dateFrom)) }} s/d {{ date('d F Y', strtotime($dateTo)) }}</div>
    </div>



    <div class="row mb-3">
        <div class="col-4">
            <div class="p-2 border rounded text-center">
                <small class="text-muted d-block" style="font-size: 9px;">TOTAL OMSET KOTOR</small>
                <strong class="fs-6 text-primary">Rp {{ number_format($grandTotalOmset, 0, ',', '.') }}</strong>
            </div>
        </div>
        <div class="col-4">
            <div class="p-2 border rounded text-center">
                <small class="text-muted d-block" style="font-size: 9px;">TOTAL POTONGAN MARKETPLACE</small>
                <strong class="fs-6 text-danger">Rp {{ number_format($grandMarketplaceFee ?? 0, 0, ',', '.') }}</strong>
            </div>
        </div>
        <div class="col-4">
            <div class="p-2 border rounded text-center bg-success bg-opacity-10">
                <small class="text-muted d-block" style="font-size: 9px;">TOTAL DANA DILEPAS (NET)</small>
                <strong class="fs-6 text-success">Rp {{ number_format($grandNetReleased ?? $grandTotalOmset, 0, ',', '.') }}</strong>
            </div>
        </div>
    </div>

    <table class="table table-print w-100 align-middle">
        <thead>
            <tr>
                <th style="width: 30px;">No</th>
                <th>SKU</th>
                <th>Nama Produk</th>
                <th class="text-center">Qty</th>
                <th class="text-end">Omset Kotor</th>
                <th class="text-end text-danger">Biaya Platform</th>
                <th class="text-end text-danger">Gratis Ongkir</th>
                <th class="text-end text-danger">Biaya Layanan</th>
                <th class="text-end text-danger">Biaya Promosi</th>
                <th class="text-end text-danger">Biaya Lainnya</th>
                <th class="text-end text-danger fw-bold">Total Potongan</th>
                <th class="text-end text-success fw-bold">Dana Dilepas (Net)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $idx => $row)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="font-monospace small">{{ $row['sku'] ?: '—' }}</td>
                    <td class="fw-bold">{{ $row['name'] }}</td>
                    <td class="text-center font-monospace fw-bold">{{ number_format($row['qty_total']) }}</td>
                    <td class="text-end font-monospace fw-bold text-primary">Rp {{ number_format($row['total_omset'], 0, ',', '.') }}</td>
                    <td class="text-end font-monospace text-danger">Rp {{ number_format($row['fee_platform'] ?? 0, 0, ',', '.') }}</td>
                    <td class="text-end font-monospace text-danger">Rp {{ number_format($row['fee_free_shipping'] ?? 0, 0, ',', '.') }}</td>
                    <td class="text-end font-monospace text-danger">Rp {{ number_format($row['fee_service'] ?? 0, 0, ',', '.') }}</td>
                    <td class="text-end font-monospace text-danger">Rp {{ number_format($row['fee_promo'] ?? 0, 0, ',', '.') }}</td>
                    <td class="text-end font-monospace text-danger">Rp {{ number_format($row['fee_other'] ?? 0, 0, ',', '.') }}</td>
                    <td class="text-end font-monospace fw-bold text-danger">Rp {{ number_format($row['total_fee'] ?? 0, 0, ',', '.') }}</td>
                    <td class="text-end font-monospace fw-bold text-success">Rp {{ number_format($row['net_released'] ?? 0, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="fw-bold bg-light">
                <td colspan="3" class="text-end">TOTAL REKAPITULASI:</td>
                <td class="text-center font-monospace">{{ number_format($grandTotalQty) }}</td>
                <td class="text-end text-primary">Rp {{ number_format($grandTotalOmset, 0, ',', '.') }}</td>
                <td class="text-end text-danger">Rp {{ number_format($grandPlatformFee ?? 0, 0, ',', '.') }}</td>
                <td class="text-end text-danger">Rp {{ number_format($grandFreeShippingFee ?? 0, 0, ',', '.') }}</td>
                <td class="text-end text-danger">Rp {{ number_format($grandServiceFee ?? 0, 0, ',', '.') }}</td>
                <td class="text-end text-danger">Rp {{ number_format($grandPromoFee ?? 0, 0, ',', '.') }}</td>
                <td class="text-end text-danger">Rp {{ number_format($grandOtherFee ?? 0, 0, ',', '.') }}</td>
                <td class="text-end text-danger fs-6">Rp {{ number_format($grandMarketplaceFee ?? 0, 0, ',', '.') }}</td>
                <td class="text-end text-success fs-6">Rp {{ number_format($grandNetReleased ?? $grandTotalOmset, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
