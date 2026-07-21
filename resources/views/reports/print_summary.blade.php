<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Rekap Persediaan</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #000;
            margin: 0;
            padding: 20px;
        }

        .header {
            margin-bottom: 20px;
            padding-bottom: 15px;
        }

        .header h1 {
            margin: 0 0 5px 0;
            font-size: 24px;
        }

        .header p {
            margin: 0;
            font-size: 14px;
            color: #555;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        table.data-table th,
        table.data-table td {
            border: 1px solid #000;
            padding: 6px 4px;
            text-align: left;
        }

        /* Group Headers */
        table.data-table th.bg-blue {
            background-color: #3b82f6 !important;
            /* Biru */
            color: white;
            text-align: center;
            border-color: #000;
        }

        table.data-table th.bg-green {
            background-color: #22c55e !important;
            /* Hijau */
            color: white;
            text-align: center;
            border-color: #000;
        }

        table.data-table th.bg-red {
            background-color: #ef4444 !important;
            /* Merah */
            color: white;
            text-align: center;
            border-color: #000;
        }

        table.data-table th.bg-gray {
            background-color: #64748b !important;
            /* Abu-abu */
            color: white;
            text-align: center;
            border-color: #000;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        @media print {
            @page {
                size: landscape;
            }

            body {
                padding: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body>

    <div class="header">
        <h1>Laporan Rekap Persediaan</h1>
        <p>
            Periode:
            @if ($startDate || $endDate)
                {{ $startDate ? $startDate->format('d-m-Y') : 'Awal' }} s/d
                {{ $endDate ? $endDate->format('d-m-Y') : 'Sekarang' }}
            @else
                Semua Waktu
            @endif
        </p>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th rowspan="2" class="bg-blue">No</th>
                <th rowspan="2" class="bg-blue">SKU</th>
                <th rowspan="2" class="bg-blue" width="15%">Nama Barang</th>
                <th rowspan="2" class="bg-blue">Satuan</th>
                <th rowspan="2" class="bg-blue">Kategori</th>
                <th rowspan="2" class="bg-blue">Merk</th>
                <th rowspan="2" class="bg-blue">Stok Awal</th>
                <th colspan="3" class="bg-green">PENERIMAAN</th>
                <th colspan="6" class="bg-red">PENGELUARAN</th>
                <th rowspan="2" class="bg-gray">Stok Akhir</th>
            </tr>
            <tr>
                <!-- PENERIMAAN -->
                <th class="bg-green">Pembelian</th>
                <th class="bg-green">Penyesuaian<br>(+)</th>
                <th class="bg-green">Lainnya<br>(+)</th>
                <!-- PENGELUARAN -->
                <th class="bg-red">Shopee</th>
                <th class="bg-red">TikTok</th>
                <th class="bg-red">Tokopedia</th>
                <th class="bg-red">Lazada</th>
                <th class="bg-red">Penyesuaian<br>(-)</th>
                <th class="bg-red">Lainnya<br>(-)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reportData as $index => $row)
                @php
                    $product = $row['product'];
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $product->sku }}</td>
                    <td>{{ $product->name }}</td>
                    <td class="text-center">{{ $product->unit ?? 'PCS' }}</td>
                    <td class="text-center">{{ $product->category->name ?? '-' }}</td>
                    <td class="text-center">{{ $product->brand->name ?? '-' }}</td>
                    <td class="text-right"><strong>{{ number_format($row['stok_awal']) }}</strong></td>

                    <td class="text-right">{{ $row['in_pembelian'] > 0 ? number_format($row['in_pembelian']) : '' }}
                    </td>
                    <td class="text-right">
                        {{ $row['in_penyesuaian'] > 0 ? number_format($row['in_penyesuaian']) : '' }}</td>
                    <td class="text-right">{{ $row['in_lainnya'] > 0 ? number_format($row['in_lainnya']) : '' }}</td>

                    <td class="text-right">{{ $row['out_shopee'] > 0 ? number_format($row['out_shopee']) : '' }}</td>
                    <td class="text-right">{{ $row['out_tiktok'] > 0 ? number_format($row['out_tiktok']) : '' }}</td>
                    <td class="text-right">{{ $row['out_tokopedia'] > 0 ? number_format($row['out_tokopedia']) : '' }}
                    </td>
                    <td class="text-right">{{ $row['out_lazada'] > 0 ? number_format($row['out_lazada']) : '' }}</td>
                    <td class="text-right">
                        {{ $row['out_penyesuaian'] > 0 ? number_format($row['out_penyesuaian']) : '' }}</td>
                    <td class="text-right">{{ $row['out_lain'] > 0 ? number_format($row['out_lain']) : '' }}</td>

                    <td class="text-right"><strong>{{ number_format($row['stok_akhir']) }}</strong></td>
                </tr>
            @empty
                <tr>
                    <td colspan="17" class="text-center" style="padding: 20px;">Tidak ada data produk yang ditemukan.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if(count($reportData) > 0)
            <tfoot>
                <tr style="font-weight:bold; background-color:#e2e8f0; border-top:2px solid #000;">
                    <td colspan="6" class="text-right"><strong>TOTAL:</strong></td>
                    <td class="text-right"><strong>{{ number_format(collect($reportData)->sum('stok_awal')) }}</strong></td>
                    <td class="text-right">{{ number_format(collect($reportData)->sum('in_pembelian')) }}</td>
                    <td class="text-right">{{ number_format(collect($reportData)->sum('in_penyesuaian')) }}</td>
                    <td class="text-right">{{ number_format(collect($reportData)->sum('in_lainnya')) }}</td>
                    <td class="text-right">{{ number_format(collect($reportData)->sum('out_shopee')) }}</td>
                    <td class="text-right">{{ number_format(collect($reportData)->sum('out_tiktok')) }}</td>
                    <td class="text-right">{{ number_format(collect($reportData)->sum('out_tokopedia')) }}</td>
                    <td class="text-right">{{ number_format(collect($reportData)->sum('out_lazada')) }}</td>
                    <td class="text-right">{{ number_format(collect($reportData)->sum('out_penyesuaian')) }}</td>
                    <td class="text-right">{{ number_format(collect($reportData)->sum('out_lain')) }}</td>
                    <td class="text-right"><strong>{{ number_format(collect($reportData)->sum('stok_akhir')) }}</strong></td>
                </tr>
            </tfoot>
        @endif
    </table>


</body>

</html>
