<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Laju Perputaran Stok Gudang</title>
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
        table.turnover-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5px;
        }

        table.turnover-table th,
        table.turnover-table td {
            border: 1px solid #000;
            padding: 4px 6px;
            text-align: center;
            vertical-align: middle;
        }

        .th-blue {
            background-color: #2563eb !important;
            color: #ffffff !important;
            font-weight: 800;
            font-size: 9.5px;
            text-transform: uppercase;
        }

        .th-green {
            background-color: #16a34a !important;
            color: #ffffff !important;
            font-weight: 800;
            font-size: 9.5px;
            text-transform: uppercase;
        }

        .th-amber {
            background-color: #d97706 !important;
            color: #ffffff !important;
            font-weight: 800;
            font-size: 9.5px;
            text-transform: uppercase;
        }

        .th-red {
            background-color: #dc2626 !important;
            color: #ffffff !important;
            font-weight: 800;
            font-size: 9.5px;
            text-transform: uppercase;
        }

        .badge-fast {
            background-color: #dcfce7;
            color: #15803d;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 4px;
            border: 1px solid #bbf7d0;
            font-size: 8.5px;
        }

        .badge-normal {
            background-color: #dbeafe;
            color: #1d4ed8;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 4px;
            border: 1px solid #bfdbfe;
            font-size: 8.5px;
        }

        .badge-slow {
            background-color: #fef3c7;
            color: #b45309;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 4px;
            border: 1px solid #fde68a;
            font-size: 8.5px;
        }

        .badge-dead {
            background-color: #fee2e2;
            color: #b91c1c;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 4px;
            border: 1px solid #fecaca;
            font-size: 8.5px;
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
        }
    </style>
</head>

<body>

    <div class="no-print">
        <div style="font-weight: 700; font-size: 13px; display: flex; align-items: center; gap: 8px;">
            📊 Laporan Laju Perputaran Stok Gudang (Inventory Turnover &amp; DSI)
        </div>
        <div>
            <button onclick="window.print()" style="padding: 6px 16px; background:#22c55e; color:#fff; border:none; border-radius:6px; cursor:pointer; font-weight:800; font-size:12px;">
                🖨️ Cetak Laporan
            </button>
            <button onclick="window.close()" style="padding: 6px 14px; background:#475569; color:#fff; border:none; border-radius:6px; cursor:pointer; margin-left:8px; font-weight:700; font-size:12px;">
                ✕ Tutup
            </button>
        </div>
    </div>

    {{-- ERP Header Standard --}}
    <div class="header">
        <h1>LAPORAN LAJU PERPUTARAN STOK GUDANG (INVENTORY TURNOVER)</h1>
        <p>Tanggal Dicetak: {{ date('d-m-Y H:i:s') }}</p>
    </div>

    {{-- ERP Info Box Standard --}}
    <div class="info-box">
        <table>
            <tr>
                <td width="18%"><strong>Periode Analisis</strong></td>
                <td width="32%">: {{ \Carbon\Carbon::parse($dateFrom)->format('d-m-Y') }} s/d {{ \Carbon\Carbon::parse($dateTo)->format('d-m-Y') }} ({{ $daysInPeriod }} Hari)</td>
                <td width="20%"><strong>Total HPP Keluar (COGS)</strong></td>
                <td width="30%">: <strong style="color: #dc2626;">Rp {{ number_format($totalCogsValue, 0, ',', '.') }}</strong></td>
            </tr>
            <tr>
                <td><strong>Rasio Putar Konsolidasi</strong></td>
                <td>: <strong style="color: #2563eb;">{{ number_format($totalTurnoverRatio, 2) }}x</strong></td>
                <td><strong>Nilai Rerata Persediaan</strong></td>
                <td>: <strong style="color: #2563eb;">Rp {{ number_format($totalAvgStockValue, 0, ',', '.') }}</strong></td>
            </tr>
            <tr>
                <td><strong>Rerata Hari Habis (DSI)</strong></td>
                <td>: <strong>{{ $totalDsi > 365 ? '>365 Hari (Sangat Lambat)' : number_format($totalDsi, 1) . ' Hari' }}</strong></td>
                <td><strong>Total Produk Terdaftar</strong></td>
                <td>: <strong>{{ number_format(count($turnoverData)) }} Master SKU</strong></td>
            </tr>
        </table>
    </div>

    <table class="turnover-table">
        <thead>
            <tr>
                <th class="th-blue" style="width: 3%;">No</th>
                <th class="th-blue" style="width: 13%;">SKU / Kode</th>
                <th class="th-blue" style="width: 25%; text-align: left; padding-left: 8px;">Nama Barang</th>
                <th class="th-blue" style="width: 7%;">Stok Awal</th>
                <th class="th-blue" style="width: 7%;">Stok Akhir</th>
                <th class="th-blue" style="width: 7%;">Stok Rerata</th>
                <th class="th-green" style="width: 7%;">Qty Keluar</th>
                <th class="th-green" style="width: 10%; text-align: right; padding-right: 8px;">Total COGS</th>
                <th class="th-amber" style="width: 7%;">Rasio Putar</th>
                <th class="th-amber" style="width: 7%;">DSI (Hari)</th>
                <th class="th-blue" style="width: 7%;">Indikator</th>
            </tr>
        </thead>
        <tbody>
            @forelse($turnoverData as $idx => $row)
                @php
                    $dsiLabel = $row['dsi'] > 365 ? '>365 Hari' : number_format($row['dsi'], 1) . ' Hari';
                    
                    if ($row['qty_sold'] == 0) {
                        $indicator = 'Dead Stock';
                        $badgeClass = 'badge-dead';
                    } elseif ($row['dsi'] <= 15) {
                        $indicator = 'Fast Moving';
                        $badgeClass = 'badge-fast';
                    } elseif ($row['dsi'] <= 45) {
                        $indicator = 'Normal';
                        $badgeClass = 'badge-normal';
                    } else {
                        $indicator = 'Slow Moving';
                        $badgeClass = 'badge-slow';
                    }
                @endphp
                <tr style="background: {{ $idx % 2 === 0 ? '#ffffff' : '#f8fafc' }};">
                    <td>{{ $idx + 1 }}</td>
                    <td style="font-family: monospace; font-weight: bold;">{{ $row['sku'] }}</td>
                    <td style="text-align: left; padding-left: 8px;">{{ $row['name'] }}</td>
                    <td>{{ number_format($row['starting_stock'], 1) }}</td>
                    <td>{{ number_format($row['ending_stock'], 1) }}</td>
                    <td style="color: #64748b;">{{ number_format($row['avg_stock'], 1) }}</td>
                    <td style="font-weight: bold; color: #15803d;">{{ number_format($row['qty_sold']) }} pcs</td>
                    <td style="text-align: right; padding-right: 8px; font-family: monospace;">Rp {{ number_format($row['cogs'], 0, ',', '.') }}</td>
                    <td style="font-weight: bold; color: #2563eb;">{{ number_format($row['ratio'], 2) }}x</td>
                    <td style="font-weight: bold; color: {{ $row['dsi'] > 90 ? '#dc2626' : '#0f172a' }};">{{ $dsiLabel }}</td>
                    <td><span class="{{ $badgeClass }}">{{ $indicator }}</span></td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" style="padding: 20px; color: #64748b; text-align: center;">
                        Tidak ada data produk master untuk dianalisis.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>

</html>
