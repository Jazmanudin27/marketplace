<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penghasilan &amp; Biaya Escrow ({{ date('d/m/Y', strtotime($dateFrom)) }} - {{ date('d/m/Y', strtotime($dateTo)) }})</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #000; background: #fff; }
        .table-statement { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table-statement th, .table-statement td { padding: 6px 10px; border: 1px solid #ccc; }
        .header-bg { background-color: #ee4d2d !important; color: #fff !important; font-weight: bold; }
        .section-bg { background-color: #f76841 !important; color: #fff !important; font-weight: bold; }
        .sub-total-bg { background-color: #e0e0e0 !important; font-weight: bold; }
        .grand-total-bg { background-color: #c8e6c9 !important; font-weight: bold; font-size: 14px; }
        .text-indent-1 { padding-left: 25px !important; }
        .text-indent-2 { padding-left: 45px !important; }
        @media print {
            .no-print { display: none !important; }
            @page { size: portrait; margin: 10mm; }
        }
    </style>
</head>
<body class="p-4">
    <div class="no-print mb-3 text-end">
        <button onclick="window.print()" class="btn btn-primary btn-sm px-3"><i class="fas fa-print me-1"></i> Cetak / Save PDF</button>
    </div>

    {{-- HEADER BAND --}}
    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
        <div class="d-flex align-items-center">
            <span class="fs-3 fw-bold text-danger me-2">🛍️ {{ $store->store_name ?? 'Marketplace Store' }}</span>
            <span class="badge bg-danger fs-6">{{ strtoupper($store->channel->name ?? 'Shopee') }}</span>
        </div>
        <div class="text-end">
            <h4 class="fw-bold mb-0 text-uppercase">Laporan Penghasilan</h4>
            <small class="text-muted">Format Resmi Escrow Marketplace</small>
        </div>
    </div>

    {{-- RINCIAN LAPORAN --}}
    <table class="table-statement mb-3">
        <thead>
            <tr>
                <th colspan="2" class="header-bg">Rincian Laporan</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="width: 250px;" class="fw-semibold">Nama Toko / Penjual</td>
                <td class="fw-bold">{{ $store->store_name ?? 'Semua Toko Marketplace' }}</td>
            </tr>
            <tr>
                <td class="fw-semibold">Dari Tanggal</td>
                <td>{{ date('Y-m-d', strtotime($dateFrom)) }}</td>
            </tr>
            <tr>
                <td class="fw-semibold">Ke Tanggal</td>
                <td>{{ date('Y-m-d', strtotime($dateTo)) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- RINGKASAN PENGHASILAN --}}
    <table class="table-statement">
        <thead>
            <tr>
                <th class="section-bg">Ringkasan Penghasilan</th>
                <th style="width: 180px;" class="section-bg text-end">Rp</th>
            </tr>
        </thead>
        <tbody>
            {{-- 1. TOTAL PENDAPATAN --}}
            <tr class="fw-bold bg-light">
                <td>1. Total Pendapatan</td>
                <td class="text-end font-monospace">{{ number_format($totalPendapatan, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="text-indent-1 fw-semibold">Subtotal Pesanan</td>
                <td class="text-end font-monospace">{{ number_format($subtotalPesanan, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="text-indent-2 text-muted">Harga Asli Produk (Omset Kotor)</td>
                <td class="text-end font-monospace text-muted">{{ number_format($grossSales, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="text-indent-2 text-muted">Jumlah Pengembalian Dana ke Pembeli (Retur)</td>
                <td class="text-end font-monospace text-muted">{{ number_format($refunds, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="text-indent-1 fw-semibold">Voucher &amp; Subsidi</td>
                <td class="text-end font-monospace">{{ number_format($vouchers, 0, ',', '.') }}</td>
            </tr>

            {{-- 2. TOTAL PENGELUARAN --}}
            <tr class="fw-bold bg-light">
                <td>2. Total Pengeluaran</td>
                <td class="text-end font-monospace text-danger">{{ number_format($totalPengeluaran, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="text-indent-1 fw-semibold">Biaya Platform</td>
                <td class="text-end font-monospace text-danger">{{ number_format($platformFee, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="text-indent-1 fw-semibold">Biaya Gratis Ongkir XTRA</td>
                <td class="text-end font-monospace text-danger">{{ number_format($freeShippingFee, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="text-indent-1 fw-semibold">Biaya Layanan</td>
                <td class="text-end font-monospace text-danger">{{ number_format($serviceFee, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="text-indent-1 fw-semibold">Biaya Promosi</td>
                <td class="text-end font-monospace text-danger">{{ number_format($promoFee, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="text-indent-1 fw-semibold">Biaya Lainnya (Koin, Premi, Admin Adjustment)</td>
                <td class="text-end font-monospace text-danger">{{ number_format($otherFee, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="text-indent-1 fw-semibold">Pajak (PPh)</td>
                <td class="text-end font-monospace text-danger">{{ number_format($tax, 0, ',', '.') }}</td>
            </tr>

            {{-- 3. TOTAL YANG DILEPAS --}}
            <tr class="grand-total-bg">
                <td class="text-uppercase">3. Total Dana yang Dilepas (Dana Cair Bersih)</td>
                <td class="text-end font-monospace text-success fs-6">Rp {{ number_format($totalDilepas, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
