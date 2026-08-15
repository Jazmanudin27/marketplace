<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Laporan Detail Transaksi Penjualan ({{ date('d/m/Y', strtotime($dateFrom)) }} - {{ date('d/m/Y', strtotime($dateTo)) }})</title>
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
        <h4 class="fw-bold mb-1">{{ strtoupper($title ?? 'LAPORAN DETAIL TRANSAKSI PENJUALAN') }}</h4>
        <div class="small text-muted">Periode Penjualan: {{ date('d F Y', strtotime($dateFrom)) }} s/d {{ date('d F Y', strtotime($dateTo)) }}</div>
    </div>

    <div class="row mb-3">
        <div class="col-4">
            <div class="p-2 border rounded text-center">
                <small class="text-muted d-block">TOTAL OMSET TRANSAKSI</small>
                <strong class="fs-6 text-primary">Rp {{ number_format($grandTotalOmset, 0, ',', '.') }}</strong>
            </div>
        </div>
        <div class="col-4">
            <div class="p-2 border rounded text-center">
                <small class="text-muted d-block">TOTAL REFUND / RETUR</small>
                <strong class="fs-6 text-danger">-Rp {{ number_format($grandTotalRefund ?? 0, 0, ',', '.') }}</strong>
            </div>
        </div>
        <div class="col-4">
            <div class="p-2 border rounded text-center">
                <small class="text-muted d-block">TOTAL ITEM TERJUAL</small>
                <strong class="fs-6 text-success">{{ number_format($grandTotalQty) }} Pcs</strong>
            </div>
        </div>
    </div>

    <table class="table table-print w-100 align-middle">
        <thead>
            <tr>
                <th style="width: 25px;">No.</th>
                <th>Tanggal Order</th>
                <th>Tanggal Dilepas</th>
                <th>No. Pesanan / Invoice</th>
                <th>Toko / Channel</th>
                <th>Pelanggan</th>
                <th>Ringkasan Produk</th>
                <th class="text-center">Qty</th>
                <th class="text-end">Omset Kotor</th>
                <th class="text-end text-danger">Refund</th>
                <th class="text-end text-danger">Biaya Platform</th>
                <th class="text-end text-danger">Biaya Gratis Ongkir</th>
                <th class="text-end text-danger">Biaya Layanan</th>
                <th class="text-end text-danger">Biaya Promosi</th>
                <th class="text-end text-danger">Biaya Lainnya</th>
                <th class="text-end text-danger fw-bold">Total Biaya Admin</th>
                <th class="text-end text-success">Dana Dilepas Net</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $idx => $row)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="font-monospace small">{{ $row['order_date'] }}</td>
                    <td class="font-monospace small text-success fw-bold">{{ $row['released_date'] }}</td>
                    <td class="font-monospace fw-bold">{{ $row['ref'] }}</td>
                    <td>{{ $row['channel'] }}</td>
                    <td>{{ $row['customer'] }}</td>
                    <td class="small">{{ $row['items_summary'] }}</td>
                    <td class="text-center font-monospace">{{ number_format($row['total_qty']) }}</td>
                    <td class="text-end font-monospace">Rp {{ number_format($row['omset'], 0, ',', '.') }}</td>
                    <td class="text-end font-monospace {{ ($row['refund'] ?? 0) > 0 ? 'text-danger fw-bold' : 'text-muted' }}">{{ ($row['refund'] ?? 0) > 0 ? '-Rp ' . number_format($row['refund'], 0, ',', '.') : '0' }}</td>
                    <td class="text-end font-monospace {{ $row['platform_fee'] < 0 ? 'text-danger' : 'text-muted' }}">{{ $row['platform_fee'] != 0 ? number_format($row['platform_fee'], 0, ',', '.') : '0' }}</td>
                    <td class="text-end font-monospace {{ $row['free_shipping_fee'] < 0 ? 'text-danger' : 'text-muted' }}">{{ $row['free_shipping_fee'] != 0 ? number_format($row['free_shipping_fee'], 0, ',', '.') : '0' }}</td>
                    <td class="text-end font-monospace {{ $row['service_fee'] < 0 ? 'text-danger' : 'text-muted' }}">{{ $row['service_fee'] != 0 ? number_format($row['service_fee'], 0, ',', '.') : '0' }}</td>
                    <td class="text-end font-monospace {{ $row['promo_fee'] < 0 ? 'text-danger' : 'text-muted' }}">{{ $row['promo_fee'] != 0 ? number_format($row['promo_fee'], 0, ',', '.') : '0' }}</td>
                    <td class="text-end font-monospace {{ $row['other_fee'] < 0 ? 'text-danger' : 'text-muted' }}">{{ $row['other_fee'] != 0 ? number_format($row['other_fee'], 0, ',', '.') : '0' }}</td>
                    <td class="text-end font-monospace text-danger fw-bold">{{ number_format(($row['total_fee'] ?? 0) < 0 ? $row['total_fee'] : -($row['total_fee'] ?? 0), 0, ',', '.') }}</td>
                    <td class="text-end font-monospace fw-bold text-success">Rp {{ number_format($row['net_released'], 0, ',', '.') }}</td>
                    <td class="text-center"><span class="badge bg-success">{{ $row['status'] }}</span></td>
                </tr>
            @empty
                <tr>
                    <td colspan="18" class="text-center py-3 text-muted">Tidak ada data detail transaksi ditemukan.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="fw-bold bg-light">
                <td colspan="7" class="text-end">TOTAL REKAPITULASI:</td>
                <td class="text-center font-monospace">{{ number_format($grandTotalQty) }}</td>
                <td class="text-end font-monospace text-primary">Rp {{ number_format($grandTotalOmset, 0, ',', '.') }}</td>
                <td class="text-end font-monospace text-danger">{{ ($grandTotalRefund ?? 0) > 0 ? '-Rp ' . number_format($grandTotalRefund, 0, ',', '.') : '0' }}</td>
                <td class="text-end font-monospace text-danger">{{ number_format($grandTotalPlatformFee ?? 0, 0, ',', '.') }}</td>
                <td class="text-end font-monospace text-danger">{{ number_format($grandTotalFreeShipping ?? 0, 0, ',', '.') }}</td>
                <td class="text-end font-monospace text-danger">{{ number_format($grandTotalServiceFee ?? 0, 0, ',', '.') }}</td>
                <td class="text-end font-monospace text-danger">{{ number_format($grandTotalPromoFee ?? 0, 0, ',', '.') }}</td>
                <td class="text-end font-monospace text-danger">{{ number_format($grandTotalOtherFee ?? 0, 0, ',', '.') }}</td>
                <td class="text-end font-monospace text-danger fw-bold">{{ number_format($grandTotalTotalFee ?? 0, 0, ',', '.') }}</td>
                <td class="text-end font-monospace text-success fs-6">Rp {{ number_format($grandTotalNetReleased ?? $grandTotalOmset, 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
