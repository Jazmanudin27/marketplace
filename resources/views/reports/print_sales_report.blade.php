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
        <h4 class="fw-bold mb-1">{{ strtoupper($title ?? 'LAPORAN REKAP PENJUALAN PRODUK DILEPAS') }}</h4>
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
                    <th style="width: 10%;">SKU</th>
                    <th>Nama Produk</th>
                    <th style="width: 10%;">Kategori / Merk</th>
                    <th class="text-center" style="width: 5%;">Qty</th>
                    <th class="text-end" style="width: 8%;">Harga Rata-rata</th>
                    <th class="text-end" style="width: 9%;">Omset Kotor</th>
                    <th class="text-end text-danger fw-bold" style="width: 9%;">Total Potongan</th>
                    <th class="text-end text-success fw-bold" style="width: 10%;">Dana Dilepas Net</th>
                    <th class="text-end text-secondary" style="width: 9%;">HPP</th>
                    <th class="text-end text-primary fw-bold" style="width: 9%;">Margin (Rp)</th>
                    <th class="text-center text-primary fw-bold" style="width: 6%;">Margin %</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $idx => $row)
                    @php
                        $hargaUnit = $row['qty_total'] > 0 ? $row['total_omset'] / $row['qty_total'] : 0;
                        $totalFeeItem = $row['total_fee'] ?? 0;
                    @endphp
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td class="font-monospace small">{{ $row['sku'] ?: '—' }}</td>
                        <td class="fw-bold">{{ $row['name'] }}</td>
                        <td class="small text-muted">{{ $row['category_name'] ?: '—' }} / {{ $row['brand_name'] ?: '—' }}</td>
                        <td class="text-center font-monospace fw-bold">{{ number_format($row['qty_total']) }}</td>
                        <td class="text-end font-monospace">Rp {{ number_format($hargaUnit, 0, ',', '.') }}</td>
                        <td class="text-end font-monospace fw-bold text-primary">Rp {{ number_format($row['total_omset'], 0, ',', '.') }}</td>
                        <td class="text-end font-monospace text-danger fw-bold">{{ $totalFeeItem > 0 ? '-Rp ' . number_format($totalFeeItem, 0, ',', '.') : '0' }}</td>
                        <td class="text-end font-monospace fw-bold text-success">Rp {{ number_format($row['net_released'], 0, ',', '.') }}</td>
                        <td class="text-end font-monospace text-secondary">Rp {{ number_format($row['total_hpp'] ?? 0, 0, ',', '.') }}</td>
                        <td class="text-end font-monospace fw-bold text-primary">Rp {{ number_format($row['margin_rp'] ?? 0, 0, ',', '.') }}</td>
                        <td class="text-center font-monospace fw-bold text-primary">{{ number_format($row['margin_pct'] ?? 0, 2, ',', '.') }}%</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" class="text-center py-3 text-muted">Tidak ada data rekap produk ditemukan.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="fw-bold bg-light">
                    <td colspan="4" class="text-end">TOTAL REKAPITULASI:</td>
                    <td class="text-center font-monospace">{{ number_format($grandTotalQty) }}</td>
                    <td></td>
                    <td class="text-end font-monospace text-primary">Rp {{ number_format($grandTotalOmset, 0, ',', '.') }}</td>
                    <td class="text-end font-monospace text-danger fw-bold">-Rp {{ number_format($grandMarketplaceFee ?? $grandTotalTotalFee ?? 0, 0, ',', '.') }}</td>
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
