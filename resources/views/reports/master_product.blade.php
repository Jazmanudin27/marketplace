<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Master Produk (Single &amp; Set Bundling)</title>
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
        table.report-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5px;
        }

        table.report-table th,
        table.report-table td {
            border: 1px solid #000;
            padding: 4px 5px;
            text-align: left;
            vertical-align: top;
        }

        /* Blue Header Column Styles */
        .th-blue {
            background-color: #2563eb !important;
            color: #ffffff !important;
            font-weight: 800;
            font-size: 9.5px;
            text-transform: uppercase;
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
        <div style="font-weight: 700; font-size: 13px;">
            📊 Laporan Master Produk (Single &amp; Set Bundling)
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
        <h1>LAPORAN MASTER PRODUK (SINGLE &amp; SET BUNDLING)</h1>
        <p>Tanggal Dicetak: {{ date('d-m-Y H:i:s') }} | Perusahaan: {{ Auth::user()->tenant->name ?? 'ERP System' }}</p>
    </div>

    {{-- ERP Info Box Standard --}}
    <div class="info-box">
        <table>
            <tr>
                <td width="20%"><strong>Total Master Produk</strong></td>
                <td width="30%">: <strong>{{ number_format($totalCount) }}</strong></td>
                <td width="20%"><strong>Produk Single</strong></td>
                <td width="30%">: <strong style="color: #0284c7;">{{ number_format($singleCount) }}</strong></td>
            </tr>
            <tr>
                <td><strong>Produk Set / Bundle</strong></td>
                <td>: <strong style="color: #6f42c1;">{{ number_format($bundleCount) }}</strong></td>
                <td><strong>Est. Total Modal Stok</strong></td>
                <td>: <strong style="color: #16a34a;">Rp {{ number_format($totalStockValue, 0, ',', '.') }}</strong></td>
            </tr>
        </table>
    </div>

    <table class="report-table">
        <thead>
            <tr>
                <th class="th-blue" style="width: 3%; text-align: center;">NO</th>
                <th class="th-blue" style="width: 12%;">SKU</th>
                <th class="th-blue" style="width: 20%;">NAMA PRODUK</th>
                <th class="th-blue" style="width: 10%; text-align: right;">HARGA JUAL</th>
                <th class="th-blue" style="width: 10%; text-align: right;">HARGA HPP</th>
                <th class="th-blue" style="width: 8%; text-align: right;">EST. KAIN</th>
                <th class="th-blue" style="width: 10%; text-align: right;">EST. PRODUKSI</th>
                <th class="th-blue" style="width: 7%; text-align: right;">STOK</th>
                <th class="th-blue" style="width: 8%; text-align: center;">STATUS</th>
                <th class="th-blue" style="width: 12%;">MARKETPLACE TERHUBUNG</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $index => $p)
                @php
                    $mpCount = $mpCountMap[$p->id] ?? 0;
                    $mpStoresStr = isset($mpMap[$p->id]) ? implode(', ', $mpMap[$p->id]) : '';
                    $compStr = isset($compMap[$p->id]) ? implode(', ', $compMap[$p->id]) : '';
                @endphp
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td style="font-family: monospace; font-weight: bold;">
                        {{ $p->sku }}
                        @if($p->sku_induk)
                            <div style="font-size: 8px; color: #64748b;">Induk: {{ $p->sku_induk }}</div>
                        @endif
                    </td>
                    <td>
                        <strong>{{ $p->name }}</strong>
                        @if($p->ukuran || $p->warna || $p->category || $p->brand)
                            <div style="margin-top: 2px; font-size: 8.5px; color: #475569;">
                                @if($p->ukuran) [{{ $p->ukuran }}] @endif
                                @if($p->warna) [{{ $p->warna }}] @endif
                                @if($p->category) {{ $p->category->name }} @endif
                                @if($p->brand) | {{ $p->brand->name }} @endif
                            </div>
                        @endif
                        @if($p->is_bundle && !empty($compStr))
                            <div style="margin-top: 2px; font-size: 8px; color: #6f42c1;">
                                <strong>Komponen:</strong> {{ $compStr }}
                            </div>
                        @endif
                    </td>
                    <td style="text-align: right; font-family: monospace; font-weight: bold; color: #0284c7;">
                        Rp {{ number_format($p->price, 0, ',', '.') }}
                    </td>
                    <td style="text-align: right; font-family: monospace;">
                        Rp {{ number_format($p->cost_price, 0, ',', '.') }}
                    </td>
                    <td style="text-align: right; font-family: monospace;">
                        {{ $p->est_kain > 0 ? number_format($p->est_kain, 2, ',', '.') . ' m' : '-' }}
                    </td>
                    <td style="text-align: right; font-family: monospace;">
                        {{ $p->est_biaya_produksi > 0 ? 'Rp ' . number_format($p->est_biaya_produksi, 0, ',', '.') : '-' }}
                    </td>
                    <td style="text-align: right; font-family: monospace; font-weight: bold; color: #16a34a;">
                        {{ number_format($p->stock, 0, ',', '.') }}
                    </td>
                    <td style="text-align: center;">
                        <span style="display: inline-block; padding: 1px 4px; border-radius: 3px; font-size: 8.5px; font-weight: bold; {{ $p->is_active ? 'background: #dcfce7; color: #15803d; border: 1px solid #86efac;' : 'background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;' }}">
                            {{ $p->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                        <div style="margin-top: 2px;">
                            <span style="display: inline-block; padding: 1px 4px; border-radius: 3px; font-size: 8.5px; font-weight: bold; {{ $p->is_preorder ? 'background: #fef3c7; color: #92400e; border: 1px solid #fde68a;' : 'background: #dcfce7; color: #15803d; border: 1px solid #86efac;' }}">
                                {{ $p->is_preorder ? 'PO' : 'Ready' }}
                            </span>
                        </div>
                        <div style="margin-top: 2px;">
                            <span style="display: inline-block; padding: 1px 4px; border-radius: 3px; font-size: 8.5px; font-weight: bold; {{ $p->is_bundle ? 'background: #f3ebff; color: #6f42c1; border: 1px solid #d8b4fe;' : 'background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd;' }}">
                                {{ $p->is_bundle ? 'Set' : 'Single' }}
                            </span>
                        </div>
                    </td>
                    <td style="font-size: 8.5px;">
                        @if($mpCount > 0)
                            <div><strong>{{ $mpCount }} Toko:</strong></div>
                            <div>{{ $mpStoresStr }}</div>
                        @else
                            <span style="color: #94a3b8; font-style: italic;">Belum Ditautkan</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" style="text-align: center; padding: 20px; color: #64748b;">Tidak ada data master produk.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>

</html>
