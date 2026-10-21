<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Laporan Penjualan Per Tanggal V2 ({{ date('d/m/Y', strtotime($dateFrom)) }} - {{ date('d/m/Y', strtotime($dateTo)) }})</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #000; background: #fff; }
        .table-print th, .table-print td { padding: 5px 8px; border: 1px solid #ddd; }
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
        <h4 class="fw-bold mb-1">{{ strtoupper($title ?? 'LAPORAN REKAP PENJUALAN PER TANGGAL DILEPAS') }}</h4>
        <div class="small text-muted">Periode Penjualan: {{ date('d F Y', strtotime($dateFrom)) }} s/d {{ date('d F Y', strtotime($dateTo)) }}</div>
    </div>

    {{-- 8 METRIC CARDS KONSISTEN DENGAN DETAIL TRANSAKSI --}}
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
                <strong class="text-warning font-monospace" style="font-size: 13px;">-Rp {{ number_format($grandMarketplaceFee ?? $grandTotalTotalFee ?? 0, 0, ',', '.') }}</strong>
            </div>
        </div>
        <div class="col">
            <div class="p-2 border rounded text-center" style="background-color: #f8fafc;">
                <small class="text-muted d-block fw-bold" style="font-size: 9px; letter-spacing: 0.5px;">TOTAL DANA DILEPAS NET</small>
                <strong class="text-success font-monospace" style="font-size: 13px;">Rp {{ number_format($grandNetReleased ?? 0, 0, ',', '.') }}</strong>
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

    <div class="table-container">
        <table class="table table-print align-middle">
            <thead>
                <tr>
                    <th style="width: 25px;">No.</th>
                    <th>Tanggal Harian</th>
                    <th class="text-center" style="width: 6%;">Qty</th>
                    <th class="text-end" style="width: 11%;">Omset Kotor (Gross)</th>
                    <th class="text-end text-danger" style="width: 8%;">Refund</th>
                    <th class="text-end text-danger fw-bold" style="width: 10%;">Total Potongan</th>
                    <th class="text-end text-success fw-bold" style="width: 11%;">Dana Dilepas (Net)</th>
                    <th class="text-end text-secondary" style="width: 10%;">HPP</th>
                    <th class="text-end text-primary fw-bold" style="width: 11%;">Margin (Rp)</th>
                    <th class="text-center text-primary fw-bold" style="width: 7%;">Margin %</th>
                </tr>
            </thead>
            <tbody>
                @forelse($dates as $idx => $row)
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td class="fw-bold font-monospace">{{ date('d F Y (l)', strtotime($row['date'])) }}</td>
                        <td class="text-center font-monospace fw-bold">{{ number_format($row['total_qty']) }}</td>
                        <td class="text-end font-monospace fw-bold text-primary">Rp {{ number_format($row['total_omset'], 0, ',', '.') }}</td>
                        <td class="text-end font-monospace {{ ($row['refund'] ?? 0) > 0 ? 'text-danger fw-bold' : 'text-muted' }}">{{ ($row['refund'] ?? 0) > 0 ? '-Rp ' . number_format($row['refund'], 0, ',', '.') : '0' }}</td>
                        <td class="text-end font-monospace fw-bold text-danger">{{ ($row['total_fee'] ?? 0) > 0 ? '-Rp ' . number_format($row['total_fee'], 0, ',', '.') : '0' }}</td>
                        <td class="text-end font-monospace fw-bold text-success">Rp {{ number_format($row['net_released'], 0, ',', '.') }}</td>
                        <td class="text-end font-monospace text-secondary">Rp {{ number_format($row['hpp'] ?? 0, 0, ',', '.') }}</td>
                        <td class="text-end font-monospace fw-bold text-primary">Rp {{ number_format($row['margin_rp'] ?? 0, 0, ',', '.') }}</td>
                        <td class="text-center font-monospace fw-bold text-primary">{{ number_format($row['margin_pct'] ?? 0, 2, ',', '.') }}%</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center py-3 text-muted">Tidak ada data harian penjualan ditemukan.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="fw-bold bg-light">
                    <td colspan="2" class="text-end">TOTAL REKAPITULASI HARIAN:</td>
                    <td class="text-center font-monospace">{{ number_format($grandTotalQty) }}</td>
                    <td class="text-end font-monospace text-primary">Rp {{ number_format($grandTotalOmset, 0, ',', '.') }}</td>
                    <td class="text-end font-monospace text-danger">{{ ($grandTotalRefund ?? 0) > 0 ? '-Rp ' . number_format($grandTotalRefund, 0, ',', '.') : '0' }}</td>
                    <td class="text-end font-monospace text-danger fs-6">-Rp {{ number_format($grandMarketplaceFee ?? $grandTotalTotalFee ?? 0, 0, ',', '.') }}</td>
                    <td class="text-end font-monospace text-success fs-6">Rp {{ number_format($grandNetReleased ?? 0, 0, ',', '.') }}</td>
                    <td class="text-end font-monospace text-secondary">Rp {{ number_format($grandTotalHpp ?? 0, 0, ',', '.') }}</td>
                    <td class="text-end font-monospace text-primary">Rp {{ number_format($grandTotalMarginRp ?? 0, 0, ',', '.') }}</td>
                    <td class="text-center font-monospace text-primary">{{ number_format($grandOverallMarginPct ?? 0, 2, ',', '.') }}%</td>
                </tr>
            </tfoot>
        </table>
    </div>
</body>
</html>
