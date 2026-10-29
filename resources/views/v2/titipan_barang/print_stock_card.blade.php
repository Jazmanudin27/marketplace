<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Kartu Stok Titipan Barang</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 6mm 8mm;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            font-family: Arial, sans-serif;
            color: #0f172a;
            margin: 0;
            padding: 15px;
            font-size: 10px;
            background: #fff;
            line-height: 1.2;
        }

        /* ERP Header Standard */
        .header {
            text-align: center;
            margin-bottom: 12px;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
        }

        .header h1 {
            margin: 0 0 4px 0;
            font-size: 20px;
            font-weight: 900;
            letter-spacing: 0.5px;
            color: #000;
            text-transform: uppercase;
        }

        .header p {
            margin: 0;
            font-size: 11px;
            color: #64748b;
        }

        /* ERP Info Box Standard */
        .info-box {
            border: 1px solid #0f172a;
            padding: 8px 12px;
            margin-bottom: 12px;
            background: #f8fafc;
        }

        .info-box table {
            width: 100%;
            border: none;
            border-collapse: collapse;
        }

        .info-box table td {
            border: none;
            padding: 3px 6px;
            font-size: 11px;
            color: #0f172a;
        }

        /* Main Data Table */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }

        table.data-table th,
        table.data-table td {
            border: 1px solid #000;
            padding: 5px 6px;
            text-align: left;
            vertical-align: middle;
        }

        /* Header Color Themes */
        .th-blue {
            background-color: #2563eb !important;
            color: #ffffff !important;
            font-weight: 800;
            font-size: 9px;
            text-transform: uppercase;
            text-align: center !important;
        }

        .th-green {
            background-color: #16a34a !important;
            color: #ffffff !important;
            font-weight: 800;
            font-size: 9px;
            text-transform: uppercase;
            text-align: center !important;
        }

        .th-red {
            background-color: #dc2626 !important;
            color: #ffffff !important;
            font-weight: 800;
            font-size: 9px;
            text-transform: uppercase;
            text-align: center !important;
        }

        .th-orange {
            background-color: #ea580c !important;
            color: #ffffff !important;
            font-weight: 800;
            font-size: 9px;
            text-transform: uppercase;
            text-align: center !important;
        }

        .text-right {
            text-align: right !important;
        }

        .text-center {
            text-align: center !important;
        }

        .font-mono {
            font-family: 'Courier New', Courier, monospace;
            font-weight: 600;
        }

        .no-print {
            margin-bottom: 12px;
            background: #1e293b;
            padding: 10px 16px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #fff;
        }

        @media print {
            body {
                padding: 0;
            }

            .no-print {
                display: none !important;
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

    <div class="no-print">
        <div style="font-weight: 700; font-size: 13px; display: flex; align-items: center; gap: 8px;">
            📊 Laporan Kartu Stok Titipan Barang (Diurutkan berdasarkan SKU)
        </div>
        <div>
            <button onclick="window.print()" style="padding: 6px 16px; background:#22c55e; color:#fff; border:none; border-radius:6px; cursor:pointer; font-weight:800; font-size:12px;">
                🖨️ Cetak Kartu Stok
            </button>
            <button onclick="window.close()" style="padding: 6px 14px; background:#475569; color:#fff; border:none; border-radius:6px; cursor:pointer; margin-left:8px; font-weight:700; font-size:12px;">
                ✕ Tutup
            </button>
        </div>
    </div>

    {{-- ERP Header Standard --}}
    <div class="header">
        <h1>LAPORAN KARTU STOK TITIPAN BARANG</h1>
        <p>Tanggal Dicetak: {{ date('d-m-Y H:i:s') }}</p>
    </div>

    {{-- ERP Info Box Standard --}}
    <div class="info-box">
        <table>
            <tr>
                <td width="15%"><strong>Supplier</strong></td>
                <td width="35%">: {{ $selectedSupplier ? strtoupper($selectedSupplier->name) : 'SEMUA SUPPLIER' }}</td>
                <td width="15%"><strong>Urutan</strong></td>
                <td width="35%">: SKU (A - Z)</td>
            </tr>
            <tr>
                <td><strong>Total Produk</strong></td>
                <td>: <strong>{{ number_format(count($reportData)) }} SKU Ditampilkan</strong></td>
                <td><strong>Status Barang</strong></td>
                <td>: Konsinyasi / Titipan Disetujui</td>
            </tr>
        </table>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th rowspan="2" class="th-blue" style="width: 3%;">NO</th>
                <th rowspan="2" class="th-blue" style="width: 13%;">SKU</th>
                <th rowspan="2" class="th-blue" style="width: 26%;">NAMA PRODUK</th>
                <th colspan="2" class="th-green">JML TITIPAN (MASUK)</th>
                <th colspan="2" class="th-red">JML TERJUAL (KELUAR)</th>
                <th colspan="2" class="th-orange">SISA STOK (GUDANG)</th>
            </tr>
            <tr>
                {{-- TITIPAN --}}
                <th class="th-green text-center" style="width: 8%;">JML TITIPAN</th>
                <th class="th-green text-center" style="width: 11%;">JML TITIPAN (RP)</th>
                {{-- TERJUAL --}}
                <th class="th-red text-center" style="width: 8%;">JML TERJUAL</th>
                <th class="th-red text-center" style="width: 11%;">JML TERJUAL (RP)</th>
                {{-- SISA STOK --}}
                <th class="th-orange text-center" style="width: 8%;">SISA STOK</th>
                <th class="th-orange text-center" style="width: 12%;">SISA STOK (RP)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reportData as $index => $row)
                <tr style="background-color: {{ $index % 2 == 0 ? '#ffffff' : '#f8fafc' }};">
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="font-mono" style="font-weight: 700;">{{ $row['sku'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td class="text-right font-mono">{{ number_format($row['qty_received']) }} {{ $row['unit'] }}</td>
                    <td class="text-right font-mono">Rp {{ number_format($row['subtotal_hpp'], 0, ',', '.') }}</td>
                    <td class="text-right font-mono">{{ number_format($row['qty_sold']) }} {{ $row['unit'] }}</td>
                    <td class="text-right font-mono">Rp {{ number_format($row['subtotal_sold_hpp'], 0, ',', '.') }}</td>
                    <td class="text-right font-mono" style="font-weight: 700;">{{ number_format($row['qty_remaining']) }} {{ $row['unit'] }}</td>
                    <td class="text-right font-mono" style="font-weight: 700;">Rp {{ number_format($row['subtotal_hpp_rem'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center" style="padding: 20px; color: #64748b;">
                        Tidak ada data titipan barang yang disetujui.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if(count($reportData) > 0)
            <tfoot>
                <tr style="background-color: #e2e8f0; font-weight: 800;">
                    <td colspan="3" class="text-center" style="font-weight: 800; font-size: 9.5px;">TOTAL</td>
                    <td class="text-right font-mono">{{ number_format($totalReceivedAll) }} PCS</td>
                    <td class="text-right font-mono">Rp {{ number_format($totalHppAll, 0, ',', '.') }}</td>
                    <td class="text-right font-mono">{{ number_format($totalSoldAll) }} PCS</td>
                    <td class="text-right font-mono">Rp {{ number_format($totalSoldHppAll, 0, ',', '.') }}</td>
                    <td class="text-right font-mono">{{ number_format($totalRemainingAll) }} PCS</td>
                    <td class="text-right font-mono">Rp {{ number_format($totalHppRemainingAll, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

</body>
</html>
