<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Rekap Persediaan Gudang</title>
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
            padding: 4px 5px;
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

        .text-right {
            text-align: right !important;
        }

        .text-center {
            text-align: center !important;
        }

        a.product-link {
            color: #0f172a;
            text-decoration: none !important;
            font-weight: 600;
            cursor: pointer;
        }

        a.product-link:hover {
            color: #2563eb;
            text-decoration: underline !important;
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

            a.product-link {
                color: #000 !important;
                text-decoration: none !important;
            }
        }
    </style>
</head>

<body>

    <div class="no-print">
        <div style="font-weight: 700; font-size: 13px; display: flex; align-items: center; gap: 8px;">
            📊 Laporan Rekap Persediaan Gudang (Stock Summary)
        </div>
        <div>
            <button onclick="window.print()" style="padding: 6px 16px; background:#22c55e; color:#fff; border:none; border-radius:6px; cursor:pointer; font-weight:800; font-size:12px;">
                🖨️ Cetak Rekap Persediaan
            </button>
            <button onclick="window.close()" style="padding: 6px 14px; background:#475569; color:#fff; border:none; border-radius:6px; cursor:pointer; margin-left:8px; font-weight:700; font-size:12px;">
                ✕ Tutup
            </button>
        </div>
    </div>

    {{-- ERP Header Standard --}}
    <div class="header">
        <h1>LAPORAN REKAP PERSEDIAAN GUDANG</h1>
        <p>Tanggal Dicetak: {{ date('d-m-Y H:i:s') }}</p>
    </div>

    {{-- ERP Info Box Standard --}}
    <div class="info-box">
        <table>
            <tr>
                <td width="15%"><strong>Periode</strong></td>
                <td width="35%">:
                    @if ($startDate || $endDate)
                        {{ $startDate ? $startDate->format('d-m-Y') : 'Awal' }} s/d {{ $endDate ? $endDate->format('d-m-Y') : 'Sekarang' }}
                    @else
                        Semua Periode Mutasi
                    @endif
                </td>
                <td width="15%"><strong>Tipe / Jenis</strong></td>
                <td width="35%">:
                    @if(request()->filled('is_bundle'))
                        {{ request('is_bundle') == '1' ? 'Paket (BUNDLE) Saja' : 'Single (Utama) Saja' }}
                    @else
                        Semua Jenis (Single &amp; BUNDLE)
                    @endif
                    @if(request()->filled('po_status'))
                        - {{ request('po_status') == '1' ? 'Khusus Pre-Order (PO)' : 'Khusus Reguler (Non-PO)' }}
                    @endif
                </td>
            </tr>
            <tr>
                <td><strong>Kategori / Brand</strong></td>
                <td>: 
                    {{ $selectedCategory ? $selectedCategory->name : 'Semua Kategori' }} / 
                    {{ $selectedBrand ? $selectedBrand->name : 'Semua Brand' }}
                </td>
                <td><strong>Total Produk</strong></td>
                <td>: <strong>{{ number_format(count($reportData)) }} SKU Ditampilkan</strong></td>
            </tr>
        </table>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th rowspan="2" class="th-blue" style="width: 2.5%;">No</th>
                <th rowspan="2" class="th-blue" style="width: 10%;">SKU</th>
                <th rowspan="2" class="th-blue" style="width: 18%;">Nama Barang</th>
                <th rowspan="2" class="th-blue" style="width: 4%;">Satuan</th>
                <th rowspan="2" class="th-blue" style="width: 7%;">Kategori</th>
                <th rowspan="2" class="th-blue" style="width: 7%;">Merk</th>
                <th rowspan="2" class="th-blue" style="width: 6%;">Stok Awal</th>
                <th colspan="3" class="th-green">PENERIMAAN (MASUK)</th>
                <th colspan="6" class="th-red">PENGELUARAN (KELUAR)</th>
                <th rowspan="2" class="th-blue" style="width: 6.5%;">Stok Akhir</th>
            </tr>
            <tr>
                <!-- PENERIMAAN -->
                <th class="th-green" style="width: 5%;">Pembelian</th>
                <th class="th-green" style="width: 5%;">Penyesuaian (+)</th>
                <th class="th-green" style="width: 4.5%;">Lain (+)</th>
                <!-- PENGELUARAN -->
                <th class="th-red" style="width: 4.5%;">Shopee</th>
                <th class="th-red" style="width: 4.5%;">TikTok</th>
                <th class="th-red" style="width: 4.5%;">Tokopedia</th>
                <th class="th-red" style="width: 4%;">Lazada</th>
                <th class="th-red" style="width: 5%;">Penyesuaian (-)</th>
                <th class="th-red" style="width: 4.5%;">Lain (-)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reportData as $index => $row)
                @php
                    $product = $row['product'];
                @endphp
                <tr style="background: {{ $index % 2 === 0 ? '#ffffff' : '#f8fafc' }};">
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td style="font-family: monospace; font-weight: bold;">
                        <a href="{{ route('reports.ledger.print', array_filter(['product_id' => $product->id, 'start_date' => request('start_date'), 'end_date' => request('end_date')])) }}"
                            target="_blank" class="product-link"
                            title="Klik untuk membuka Kartu Stok {{ $product->name }}">
                            {{ $product->sku }}
                        </a>
                    </td>
                    <td>
                        <a href="{{ route('reports.ledger.print', array_filter(['product_id' => $product->id, 'start_date' => request('start_date'), 'end_date' => request('end_date')])) }}"
                            target="_blank" class="product-link"
                            title="Klik untuk membuka Kartu Stok {{ $product->name }}">
                            {{ $product->name }}
                        </a>
                        @if($product->is_bundle)
                            <span style="display:inline-block; font-size:7.5px; padding:0 3px; border-radius:3px; background:#fef3c7; color:#92400e; border:1px solid #fde68a; font-weight:bold; margin-left:3px;">BUNDLE</span>
                        @endif
                    </td>
                    <td class="text-center">{{ strtoupper($product->unit ?: 'PCS') }}</td>
                    <td class="text-center">{{ $product->category->name ?? '—' }}</td>
                    <td class="text-center">{{ $product->brand->name ?? '—' }}</td>
                    <td class="text-right"><strong>{{ number_format($row['stok_awal']) }}</strong></td>

                    <td class="text-right" style="color: #15803d; font-weight: 600;">{{ $row['in_pembelian'] > 0 ? number_format($row['in_pembelian']) : '—' }}</td>
                    <td class="text-right" style="color: #15803d;">{{ $row['in_penyesuaian'] > 0 ? number_format($row['in_penyesuaian']) : '—' }}</td>
                    <td class="text-right" style="color: #15803d;">{{ $row['in_lainnya'] > 0 ? number_format($row['in_lainnya']) : '—' }}</td>

                    <td class="text-right" style="color: #ea580c; font-weight: 600;">{{ $row['out_shopee'] > 0 ? number_format($row['out_shopee']) : '—' }}</td>
                    <td class="text-right" style="color: #0f172a; font-weight: 600;">{{ $row['out_tiktok'] > 0 ? number_format($row['out_tiktok']) : '—' }}</td>
                    <td class="text-right" style="color: #16a34a; font-weight: 600;">{{ $row['out_tokopedia'] > 0 ? number_format($row['out_tokopedia']) : '—' }}</td>
                    <td class="text-right" style="color: #0284c7; font-weight: 600;">{{ $row['out_lazada'] > 0 ? number_format($row['out_lazada']) : '—' }}</td>
                    <td class="text-right" style="color: #b91c1c;">{{ $row['out_penyesuaian'] > 0 ? number_format($row['out_penyesuaian']) : '—' }}</td>
                    <td class="text-right" style="color: #64748b;">{{ $row['out_lain'] > 0 ? number_format($row['out_lain']) : '—' }}</td>

                    <td class="text-right" style="font-weight: 800; background: #f1f5f9; color: #0f172a;">{{ number_format($row['stok_akhir']) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="17" class="text-center" style="padding: 25px; color: #64748b;">
                        Tidak ada data produk yang ditemukan dengan filter ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if(count($reportData) > 0)
            <tfoot>
                <tr style="font-weight: bold; background-color: #e2e8f0; border-top: 2px solid #000;">
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
                    <td class="text-right" style="background: #cbd5e1;"><strong>{{ number_format(collect($reportData)->sum('stok_akhir')) }}</strong></td>
                </tr>
            </tfoot>
        @endif
    </table>

</body>

</html>
