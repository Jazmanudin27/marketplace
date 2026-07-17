<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Master Produk (Single & Set Bundling)</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #000;
            margin: 0;
            padding: 20px;
            font-size: 11px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .header h1 {
            margin: 0 0 5px 0;
            font-size: 20px;
            text-transform: uppercase;
        }

        .header p {
            margin: 0;
            font-size: 12px;
            color: #555;
        }

        .summary-box {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            background-color: #f8f9fa;
            border: 1px solid #ccc;
            padding: 10px;
            border-radius: 4px;
        }

        .summary-item {
            text-align: center;
        }

        .summary-item label {
            display: block;
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
            font-weight: bold;
        }

        .summary-item span {
            font-size: 14px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        th, td {
            border: 1px solid #333;
            padding: 6px 8px;
            vertical-align: top;
        }

        th {
            background-color: #e9ecef;
            font-weight: bold;
            text-align: left;
            text-transform: uppercase;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .font-mono {
            font-family: 'Courier New', Courier, monospace;
        }

        .badge-bundle {
            background-color: #6f42c1;
            color: #fff;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }

        .badge-single {
            background-color: #17a2b8;
            color: #fff;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }

        .component-list {
            margin: 0;
            padding-left: 12px;
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
<body onload="window.print()">

    <div class="no-print" style="margin-bottom: 15px; text-align: right;">
        <button onclick="window.print()" style="padding: 6px 15px; cursor: pointer; font-weight: bold;">Cetak Halaman Ini</button>
    </div>

    <div class="header">
        <h1>LAPORAN MASTER PRODUK (SINGLE & SET BUNDLING)</h1>
        <p>Tanggal Cetak: {{ date('d-m-Y H:i:s') }} | Perusahaan: {{ Auth::user()->tenant->name ?? 'ERP System' }}</p>
    </div>

    <div class="summary-box">
        <div class="summary-item">
            <label>Total Produk</label>
            <span>{{ number_format($totalCount) }}</span>
        </div>
        <div class="summary-item">
            <label>Produk Set / Bundling</label>
            <span style="color: #6f42c1;">{{ number_format($bundleCount) }}</span>
        </div>
        <div class="summary-item">
            <label>Produk Single</label>
            <span style="color: #17a2b8;">{{ number_format($singleCount) }}</span>
        </div>
        <div class="summary-item">
            <label>Est. Total Modal Stok</label>
            <span style="color: #28a745;">Rp {{ number_format($totalStockValue, 0, ',', '.') }}</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="3%" class="text-center">NO</th>
                <th width="15%">SKU PRODUK</th>
                <th width="22%">NAMA PRODUK</th>
                <th width="10%" class="text-center">TIPE</th>
                <th width="25%">KOMPONEN SET / BUNDLE</th>
                <th width="9%" class="text-right">HPP (MODAL)</th>
                <th width="9%" class="text-right">HARGA JUAL</th>
                <th width="7%" class="text-center">STOK</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $index => $p)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="font-mono">
                        <strong>{{ $p->sku }}</strong>
                        @if($p->sku_induk)
                            <br><span style="color: #666; font-size: 9px;">Induk: {{ $p->sku_induk }}</span>
                        @endif
                    </td>
                    <td>
                        <strong>{{ $p->name }}</strong>
                        @if($p->ukuran || $p->warna)
                            <br><span style="color: #666; font-size: 9px;">{{ $p->ukuran }} {{ $p->warna }}</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($p->is_bundle)
                            <span class="badge-bundle">SET / BUNDLE</span>
                        @else
                            <span class="badge-single">SINGLE</span>
                        @endif
                    </td>
                    <td>
                        @if($p->is_bundle)
                            @if($p->components->isNotEmpty())
                                <ul class="component-list">
                                    @foreach($p->components as $c)
                                        <li>{{ $c->pivot->quantity }}x <span class="font-mono">{{ $c->sku }}</span> ({{ $c->name }})</li>
                                    @endforeach
                                </ul>
                            @else
                                <span style="color: red; font-style: italic;">Belum ada komponen</span>
                            @endif
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-right font-mono">Rp {{ number_format($p->cost_price, 0, ',', '.') }}</td>
                    <td class="text-right font-mono">Rp {{ number_format($p->price, 0, ',', '.') }}</td>
                    <td class="text-center font-mono"><strong>{{ number_format($p->stock) }}</strong></td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 15px;">Tidak ada data produk.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
