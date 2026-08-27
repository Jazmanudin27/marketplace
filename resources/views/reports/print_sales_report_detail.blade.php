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

    <div class="row g-2 mb-3">
        <div class="col">
            <div class="p-2 border rounded text-center" style="background-color: #f8fafc;">
                <small class="text-muted d-block fw-bold" style="font-size: 9px; letter-spacing: 0.5px;">TOTAL OMSET KOTOR</small>
                <strong class="text-primary font-monospace" style="font-size: 13px;">Rp {{ number_format($grandTotalOmset, 0, ',', '.') }}</strong>
            </div>
        </div>
        <div class="col">
            <div class="p-2 border rounded text-center" style="background-color: #f8fafc;">
                <small class="text-muted d-block fw-bold" style="font-size: 9px; letter-spacing: 0.5px;">TOTAL REFUND / RETUR</small>
                <strong class="text-danger font-monospace" style="font-size: 13px;">-Rp {{ number_format($grandTotalRefund ?? 0, 0, ',', '.') }}</strong>
            </div>
        </div>
        <div class="col">
            <div class="p-2 border rounded text-center" style="background-color: #f8fafc;">
                <small class="text-muted d-block fw-bold" style="font-size: 9px; letter-spacing: 0.5px;">TOTAL POTONGAN</small>
                <strong class="text-warning font-monospace" style="font-size: 13px;">-Rp {{ number_format($grandTotalTotalFee ?? 0, 0, ',', '.') }}</strong>
            </div>
        </div>
        <div class="col">
            <div class="p-2 border rounded text-center" style="background-color: #f8fafc;">
                <small class="text-muted d-block fw-bold" style="font-size: 9px; letter-spacing: 0.5px;">TOTAL DANA DILEPAS NET</small>
                <strong class="text-success font-monospace" style="font-size: 13px;">Rp {{ number_format($grandTotalNetReleased ?? 0, 0, ',', '.') }}</strong>
            </div>
        </div>
        <div class="col">
            <div class="p-2 border rounded text-center" style="background-color: #f8fafc;">
                <small class="text-muted d-block fw-bold" style="font-size: 9px; letter-spacing: 0.5px;">TOTAL HPP</small>
                <strong class="text-secondary font-monospace" style="font-size: 13px;">Rp {{ number_format($grandTotalHpp ?? 0, 0, ',', '.') }}</strong>
            </div>
        </div>
        <div class="col">
            <div class="p-2 border rounded text-center" style="background-color: #f8fafc;">
                <small class="text-muted d-block fw-bold" style="font-size: 9px; letter-spacing: 0.5px;">TOTAL MARGIN (RP)</small>
                <strong class="text-primary font-monospace" style="font-size: 13px;">Rp {{ number_format($grandTotalMarginRp ?? 0, 0, ',', '.') }}</strong>
            </div>
        </div>
        <div class="col">
            <div class="p-2 border rounded text-center bg-primary bg-opacity-10">
                <small class="text-muted d-block fw-bold" style="font-size: 9px; letter-spacing: 0.5px;">OVERALL MARGIN %</small>
                <strong class="text-primary font-monospace" style="font-size: 13px;">{{ number_format($grandOverallMarginPct ?? 0, 2, ',', '.') }}%</strong>
            </div>
        </div>
        <div class="col">
            <div class="p-2 border rounded text-center" style="background-color: #f8fafc;">
                <small class="text-muted d-block fw-bold" style="font-size: 9px; letter-spacing: 0.5px;">TOTAL ITEM TERJUAL</small>
                <strong class="text-dark font-monospace" style="font-size: 13px;">{{ number_format($grandTotalQty) }} Pcs</strong>
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
                <th>Channel / Toko</th>
                <th>Ringkasan Produk</th>
                <th class="text-center">Qty</th>
                <th class="text-end">Omset Kotor</th>
                <th class="text-end text-danger">Refund</th>
                <th class="text-end text-danger fw-bold">Total Potongan</th>
                <th class="text-end text-success">Dana Dilepas Net</th>
                <th class="text-end text-secondary">HPP</th>
                <th class="text-end text-primary">Margin (Rp)</th>
                <th class="text-center text-primary">Margin %</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $idx => $row)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="font-monospace small">{{ $row['order_date'] }}</td>
                    <td class="font-monospace small text-success fw-bold">{{ $row['released_date'] }}</td>
                    <td class="font-monospace fw-bold" style="mso-number-format:'\@';">{{ $row['ref'] }}</td>
                    <td>{{ $row['channel'] }}</td>
                    <td class="small">{{ $row['items_summary'] }}</td>
                    <td class="text-center font-monospace">{{ number_format($row['total_qty']) }}</td>
                    <td class="text-end font-monospace">Rp {{ number_format($row['omset'], 0, ',', '.') }}</td>
                    <td class="text-end font-monospace {{ ($row['refund'] ?? 0) > 0 ? 'text-danger fw-bold' : 'text-muted' }}">{{ ($row['refund'] ?? 0) > 0 ? '-Rp ' . number_format($row['refund'], 0, ',', '.') : '0' }}</td>
                    <td class="text-end font-monospace text-danger fw-bold">{{ number_format(($row['total_fee'] ?? 0) < 0 ? $row['total_fee'] : -($row['total_fee'] ?? 0), 0, ',', '.') }}</td>
                    <td class="text-end font-monospace fw-bold text-success">Rp {{ number_format($row['net_released'], 0, ',', '.') }}</td>
                    <td class="text-end font-monospace text-secondary">Rp {{ number_format($row['hpp'] ?? 0, 0, ',', '.') }}</td>
                    <td class="text-end font-monospace fw-bold text-primary">Rp {{ number_format($row['margin_rp'] ?? 0, 0, ',', '.') }}</td>
                    <td class="text-center font-monospace fw-bold text-primary">{{ number_format($row['margin_pct'] ?? 0, 2, ',', '.') }}%</td>
                    <td class="text-center"><span class="badge bg-success">{{ $row['status'] }}</span></td>
                </tr>
            @empty
                <tr>
                    <td colspan="15" class="text-center py-3 text-muted">Tidak ada data detail transaksi ditemukan.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="fw-bold bg-light">
                <td colspan="6" class="text-end">TOTAL REKAPITULASI:</td>
                <td class="text-center font-monospace">{{ number_format($grandTotalQty) }}</td>
                <td class="text-end font-monospace text-primary">Rp {{ number_format($grandTotalOmset, 0, ',', '.') }}</td>
                <td class="text-end font-monospace text-danger">{{ ($grandTotalRefund ?? 0) > 0 ? '-Rp ' . number_format($grandTotalRefund, 0, ',', '.') : '0' }}</td>
                <td class="text-end font-monospace text-danger fw-bold">{{ number_format($grandTotalTotalFee ?? 0, 0, ',', '.') }}</td>
                <td class="text-end font-monospace text-success fs-6">Rp {{ number_format($grandTotalNetReleased ?? $grandTotalOmset, 0, ',', '.') }}</td>
                <td class="text-end font-monospace text-secondary">Rp {{ number_format($grandTotalHpp ?? 0, 0, ',', '.') }}</td>
                <td class="text-end font-monospace text-primary">Rp {{ number_format($grandTotalMarginRp ?? 0, 0, ',', '.') }}</td>
                <td class="text-center font-monospace text-primary">{{ number_format($grandOverallMarginPct ?? 0, 2, ',', '.') }}%</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
