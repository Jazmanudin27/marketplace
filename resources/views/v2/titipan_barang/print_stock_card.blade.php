<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Kartu Stok Titipan Barang - {{ $selectedSupplier ? $selectedSupplier->name : 'Semua Supplier' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: #0f172a;
            background: #ffffff;
            font-size: 11px;
            padding: 16px;
        }

        /* ── Non-printable Action Bar ── */
        .no-print-bar {
            background: #0f172a;
            color: #ffffff;
            padding: 10px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.12);
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            font-size: 12px;
            font-weight: 700;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-print {
            background-color: #2563eb;
            color: #ffffff;
        }
        .btn-print:hover {
            background-color: #1d4ed8;
        }

        .btn-close {
            background-color: #475569;
            color: #ffffff;
        }
        .btn-close:hover {
            background-color: #334155;
        }

        /* ── Header ── */
        .report-header {
            margin-bottom: 14px;
            padding-bottom: 10px;
            border-bottom: 2px solid #0f172a;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
        }

        .report-header h1 {
            font-size: 18px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #0f172a;
            margin-bottom: 4px;
        }

        .report-header p {
            font-size: 11px;
            color: #64748b;
        }

        .report-meta {
            text-align: right;
            font-size: 10.5px;
            color: #475569;
            line-height: 1.4;
        }

        /* ── Meta Info Box ── */
        .info-box {
            display: flex;
            gap: 24px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 8px 12px;
            border-radius: 6px;
            margin-bottom: 14px;
            font-size: 11px;
        }

        .info-box span strong {
            color: #0f172a;
        }

        /* ── Table Styling ── */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        th {
            background-color: #f1f5f9;
            color: #1e293b;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 9.5px;
            letter-spacing: 0.03em;
            padding: 6px 8px;
            border: 1px solid #cbd5e1;
            text-align: center;
        }

        td {
            padding: 5px 8px;
            border: 1px solid #e2e8f0;
            vertical-align: middle;
        }

        tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .font-mono {
            font-family: 'JetBrains+Mono', monospace;
        }

        .text-start { text-align: left; }
        .text-center { text-align: center; }
        .text-end { text-align: right; }

        .fw-bold { font-weight: 700; }
        .text-muted { color: #64748b; }

        /* ── Footer Summary ── */
        tfoot tr {
            background-color: #e2e8f0 !important;
            font-weight: 700;
        }

        tfoot td {
            border: 1px solid #cbd5e1;
            padding: 7px 8px;
            font-size: 10px;
        }

        /* ── Print Media Query ── */
        @media print {
            .no-print-bar {
                display: none !important;
            }

            body {
                padding: 0;
                margin: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            @page {
                size: A4 landscape;
                margin: 10mm 8mm;
            }

            tr {
                page-break-inside: avoid;
            }

            thead {
                display: table-header-group;
            }

            tfoot {
                display: table-footer-group;
            }
        }
    </style>
</head>
<body>

    {{-- Non-printable Bar --}}
    <div class="no-print-bar">
        <div style="font-weight: 600; font-size: 13px;">
            Laporan Kartu Stok Titipan Barang (Diurutkan berdasarkan SKU)
        </div>
        <div style="display: flex; gap: 8px;">
            <button type="button" class="btn-action btn-print" onclick="window.print();">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                Cetak / Simpan PDF
            </button>
            <a href="{{ route('v2.titipan_barang.stock_card', ['supplier_id' => request('supplier_id')]) }}" class="btn-action btn-close">
                Tutup
            </a>
        </div>
    </div>

    {{-- Header --}}
    <div class="report-header">
        <div>
            <h1>Kartu Stok Titipan Barang</h1>
            <p>Laporan Persediaan, Mutasi Masuk, Terjual, dan Sisa Stok Konsinyasi</p>
        </div>
        <div class="report-meta">
            <div>Dicetak Pada: <strong>{{ date('d-m-Y H:i') }}</strong></div>
            <div>Supplier: <strong>{{ $selectedSupplier ? strtoupper($selectedSupplier->name) : 'SEMUA SUPPLIER' }}</strong></div>
        </div>
    </div>

    {{-- Info Box --}}
    <div class="info-box">
        <span>Supplier: <strong>{{ $selectedSupplier ? $selectedSupplier->name : 'Semua Supplier' }}</strong></span>
        <span>Total Produk: <strong>{{ count($reportData) }} SKU</strong></span>
        <span>Total Masuk: <strong>{{ number_format($totalReceivedAll) }} PCS</strong></span>
        <span>Total Terjual: <strong>{{ number_format($totalSoldAll) }} PCS</strong></span>
        <span>Sisa Stok: <strong>{{ number_format($totalRemainingAll) }} PCS</strong></span>
    </div>

    {{-- Table --}}
    <table>
        <thead>
            <tr>
                <th style="width: 32px;">No</th>
                <th style="width: 110px;" class="text-start">SKU</th>
                <th class="text-start">Nama Produk</th>
                <th style="width: 70px;" class="text-end">Jml Titipan</th>
                <th style="width: 100px;" class="text-end">Jml Titipan (Rp)</th>
                <th style="width: 70px;" class="text-end">Jml Terjual</th>
                <th style="width: 100px;" class="text-end">Jml Terjual (Rp)</th>
                <th style="width: 70px;" class="text-end">Sisa Stok</th>
                <th style="width: 100px;" class="text-end">Sisa Stok (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reportData as $index => $row)
                <tr>
                    <td class="text-center font-mono">{{ $index + 1 }}</td>
                    <td class="font-mono fw-bold text-start">{{ $row['sku'] }}</td>
                    <td class="text-start">{{ $row['name'] }}</td>
                    <td class="text-end font-mono">{{ number_format($row['qty_received']) }} {{ $row['unit'] }}</td>
                    <td class="text-end font-mono">Rp {{ number_format($row['subtotal_hpp'], 0, ',', '.') }}</td>
                    <td class="text-end font-mono">{{ number_format($row['qty_sold']) }} {{ $row['unit'] }}</td>
                    <td class="text-end font-mono">Rp {{ number_format($row['subtotal_sold_hpp'], 0, ',', '.') }}</td>
                    <td class="text-end font-mono fw-bold">{{ number_format($row['qty_remaining']) }} {{ $row['unit'] }}</td>
                    <td class="text-end font-mono fw-bold">Rp {{ number_format($row['subtotal_hpp_rem'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center text-muted" style="padding: 24px;">
                        Tidak ada data titipan barang yang disetujui.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if(count($reportData) > 0)
            <tfoot>
                <tr>
                    <td colspan="3" class="text-center fw-bold">TOTAL REKAPITULASI</td>
                    <td class="text-end font-mono fw-bold">{{ number_format($totalReceivedAll) }} PCS</td>
                    <td class="text-end font-mono fw-bold">Rp {{ number_format($totalHppAll, 0, ',', '.') }}</td>
                    <td class="text-end font-mono fw-bold">{{ number_format($totalSoldAll) }} PCS</td>
                    <td class="text-end font-mono fw-bold">Rp {{ number_format($totalSoldHppAll, 0, ',', '.') }}</td>
                    <td class="text-end font-mono fw-bold">{{ number_format($totalRemainingAll) }} PCS</td>
                    <td class="text-end font-mono fw-bold">Rp {{ number_format($totalHppRemainingAll, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

</body>
</html>
