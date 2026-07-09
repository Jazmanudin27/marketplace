<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan HPP Produksi</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #000;
            margin: 0;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
        }

        .header h1 {
            margin: 0 0 10px 0;
            font-size: 22px;
            font-weight: bold;
        }

        .header p {
            margin: 0;
            font-size: 13px;
            color: #444;
        }

        .info {
            margin-bottom: 20px;
            font-size: 13px;
            line-height: 1.6;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 6px 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .fw-bold {
            font-weight: bold;
        }

        .footer-summary {
            margin-top: 30px;
            font-size: 12px;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }

        @media print {
            body {
                padding: 0;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body>

    <div class="header">
        <h1>LAPORAN HITUNGAN HPP PRODUKSI (SPK SELESAI)</h1>
        <p>Dicetak pada: {{ date('d-m-Y H:i:s') }}</p>
    </div>

    <div class="info">
        @if (request('start_date') || request('end_date'))
            <p><strong>Periode:</strong> 
                {{ request('start_date') ? \Carbon\Carbon::parse(request('start_date'))->format('d-m-Y') : 'Awal' }} 
                s.d. 
                {{ request('end_date') ? \Carbon\Carbon::parse(request('end_date'))->format('d-m-Y') : 'Akhir' }}
            </p>
        @endif
        @if (request('product_id'))
            <p><strong>Filter Produk Jadi:</strong> 
                {{ \App\Models\MasterProduct::find(request('product_id'))->name ?? '-' }}
            </p>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th width="4%" class="text-center">No</th>
                <th width="8%" class="text-center">SPK</th>
                <th width="12%" class="text-center">Tgl Selesai</th>
                <th width="28%">Nama Produk Master</th>
                <th width="8%" class="text-center">Qty (PCS)</th>
                <th width="12%" class="text-right">Biaya Bahan (Rp)</th>
                <th width="12%" class="text-right">Biaya Jasa/Tenaga (Rp)</th>
                <th width="12%" class="text-right">Total Biaya (Rp)</th>
                <th width="10%" class="text-right">HPP / Unit (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalQty = 0;
                $totalMaterial = 0;
                $totalLabor = 0;
                $totalOverallCost = 0;
            @endphp
            @forelse($reportData as $index => $row)
                @php
                    $totalQty += $row['quantity'];
                    $totalMaterial += $row['material_cost'];
                    $totalLabor += $row['labor_cost'];
                    $totalOverallCost += $row['total_cost'];
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center fw-bold">#{{ $row['id'] }}</td>
                    <td class="text-center">{{ \Carbon\Carbon::parse($row['completed_at'])->format('d-m-Y H:i') }}</td>
                    <td>
                        <span class="fw-bold">{{ $row['sku'] }}</span><br>
                        {{ $row['product_name'] }}
                    </td>
                    <td class="text-center font-monospace">{{ number_format($row['quantity'], 0, ',', '.') }}</td>
                    <td class="text-right font-monospace">{{ number_format($row['material_cost'], 0, ',', '.') }}</td>
                    <td class="text-right font-monospace">{{ number_format($row['labor_cost'], 0, ',', '.') }}</td>
                    <td class="text-right font-monospace fw-bold">{{ number_format($row['total_cost'], 0, ',', '.') }}</td>
                    <td class="text-right font-monospace fw-bold bg-light" style="background-color: #fafafa;">
                        {{ number_format($row['hpp_per_unit'], 0, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center" style="padding: 25px; color: #666;">
                        Tidak ada transaksi pesanan produksi selesai dalam periode ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if(count($reportData) > 0)
            <tfoot>
                <tr style="background-color: #f9f9f9; font-weight: bold;">
                    <td colspan="4" class="text-right fw-bold">TOTAL AKUMULASI:</td>
                    <td class="text-center font-monospace fw-bold">{{ number_format($totalQty, 0, ',', '.') }}</td>
                    <td class="text-right font-monospace fw-bold">{{ number_format($totalMaterial, 0, ',', '.') }}</td>
                    <td class="text-right font-monospace fw-bold">{{ number_format($totalLabor, 0, ',', '.') }}</td>
                    <td class="text-right font-monospace fw-bold">{{ number_format($totalOverallCost, 0, ',', '.') }}</td>
                    <td class="text-right font-monospace fw-bold" style="background-color: #f2f2f2;">
                        {{ number_format($totalQty > 0 ? ($totalOverallCost / $totalQty) : 0, 0, ',', '.') }}
                    </td>
                </tr>
            </tfoot>
        @endif
    </table>

    @if(count($reportData) > 0)
        <div class="footer-summary">
            <p><strong>Ringkasan Analisis HPP:</strong></p>
            <ul>
                <li>Total Hasil Produksi Selesai: <strong>{{ number_format($totalQty, 0, ',', '.') }} PCS</strong></li>
                <li>Rata-rata HPP Hasil Produksi Baru (Weighted Average): <strong>Rp {{ number_format($totalQty > 0 ? ($totalOverallCost / $totalQty) : 0, 0, ',', '.') }} / PCS</strong></li>
                <li>Kontribusi Biaya Bahan Baku: <strong>{{ $totalOverallCost > 0 ? number_format(($totalMaterial / $totalOverallCost) * 100, 1) : 0 }}%</strong></li>
                <li>Kontribusi Biaya Jasa / Tenaga Kerja / QC: <strong>{{ $totalOverallCost > 0 ? number_format(($totalLabor / $totalOverallCost) * 100, 1) : 0 }}%</strong></li>
            </ul>
        </div>
    @endif

</body>

</html>
