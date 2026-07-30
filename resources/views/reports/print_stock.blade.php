<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Stok Barang</title>
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
            border-bottom: 2px solid #000;
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

        .info-box {
            margin-bottom: 15px;
            font-size: 12px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            padding: 10px;
            border-radius: 6px;
        }

        table.data-table {
            min-width: 100%;
            max-width: 150%;
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        table.data-table th,
        table.data-table td {
            border: 1px solid #000;
            padding: 6px 6px;
            text-align: left;
        }

        /* Group Headers */
        table.data-table th.bg-blue {
            background-color: #3b82f6 !important;
            color: white;
            text-align: center;
            border-color: #000;
        }

        table.data-table th.bg-green {
            background-color: #22c55e !important;
            color: white;
            text-align: center;
            border-color: #000;
        }

        table.data-table th.bg-info-header {
            background-color: #0284c7 !important;
            color: white;
            text-align: center;
            border-color: #000;
        }

        table.data-table th.bg-gray {
            background-color: #64748b !important;
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

        a.product-link {
            color: #1e293b;
            text-decoration: none !important;
            font-weight: 600;
            cursor: pointer;
        }

        a.product-link:hover {
            color: #0284c7;
            text-decoration: none !important;
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

            a.product-link {
                color: #000 !important;
                text-decoration: none !important;
            }
        }
    </style>
</head>

<body>

    <div class="header">
        <h1>LAPORAN STOK BARANG (GUDANG &amp; MARKETPLACE)</h1>
        <p>Tanggal Dicetak: {{ date('d-m-Y H:i:s') }}</p>
    </div>

    <div class="info-box">
        <strong>Filter Laporan:</strong>
        @if (request('category_id'))
            | Kategori: {{ App\Models\Category::find(request('category_id'))->name ?? '-' }}
        @endif
        @if (request('brand_id'))
            | Merk: {{ App\Models\Brand::find(request('brand_id'))->name ?? '-' }}
        @endif
        @if (request()->filled('is_bundle'))
            | Jenis: {{ request('is_bundle') === '1' ? '🎁 BUNDLE / Paket Set' : '📦 Single (Produk Standar)' }}
        @endif
        @if (request()->filled('is_preorder'))
            | Tipe: {{ request('is_preorder') === '1' ? '⏳ Pre-Order (PO)' : '📦 Ready Stock' }}
        @endif
        @if (request('search'))
            | Pencarian: "{{ request('search') }}"
        @endif
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th class="bg-blue" style="width: 4%;">No</th>
                <th class="bg-blue" style="width: 14%;">SKU</th>
                <th class="bg-blue" style="width: 30%;">Nama Produk</th>
                <th class="bg-blue" style="width: 15%;">Kategori / Merk</th>
                <th class="bg-blue" style="width: 11%;">Status &amp; PO</th>
                <th class="bg-green" style="width: 9%;">Stok Gudang</th>
                <th class="bg-info-header" style="width: 9%;">Stok MP</th>
                <th class="bg-gray" style="width: 8%;">Total Stok</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $index => $product)
                @php
                    $stokGudang = (int) $product->stock;
                    $stokMp = (int) $product->marketplaceProducts->sum('stock');
                    $totalStok = $stokGudang + $stokMp;
                    $ledgerUrl = route('reports.ledger.print', ['product_id' => $product->id]);
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        <a href="{{ $ledgerUrl }}" target="_blank" class="product-link" title="Buka Kartu Stok {{ $product->name }}">
                            {{ $product->sku ?? '-' }}
                        </a>
                    </td>
                    <td>
                        <a href="{{ $ledgerUrl }}" target="_blank" class="product-link" title="Buka Kartu Stok {{ $product->name }}">
                            {{ $product->name }}
                        </a>
                    </td>
                    <td>
                        <strong>{{ $product->category->name ?? '-' }}</strong>
                        <small style="color: #64748b; display: block;">{{ $product->brand->name ?? '-' }}</small>
                    </td>
                    <td class="text-center">
                        @if($product->is_preorder)
                            <span style="color: #c2410c; font-weight: bold;">⏳ PO ({{ $product->preorder_days ?: 7 }}hr)</span>
                        @else
                            <span style="color: #16a34a; font-weight: bold;">📦 Ready Stock</span>
                        @endif
                    </td>
                    <td class="text-right"><strong>{{ number_format($stokGudang, 0, ',', '.') }}</strong></td>
                    <td class="text-right">{{ number_format($stokMp, 0, ',', '.') }}</td>
                    <td class="text-right">
                        <strong style="color: {{ $totalStok <= 0 ? '#dc2626' : '#16a34a' }};">
                            {{ number_format($totalStok, 0, ',', '.') }}
                        </strong>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 20px;">Tidak ada data barang yang sesuai dengan filter.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
