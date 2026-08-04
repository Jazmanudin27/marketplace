<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Stok Barang (Gudang & Marketplace)</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #000;
            margin: 0;
            padding: 15px;
            background-color: #fff;
        }

        .header {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #000;
        }

        .header h1 {
            margin: 0 0 5px 0;
            font-size: 22px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .header p {
            margin: 0;
            font-size: 13px;
            color: #444;
        }

        .info-box {
            margin-bottom: 15px;
            font-size: 11px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            padding: 8px 12px;
            border-radius: 4px;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        table.data-table th,
        table.data-table td {
            border: 1px solid #000;
            padding: 6px 8px;
            text-align: left;
            vertical-align: middle;
        }

        /* Group Headers matching user screenshot */
        table.data-table th.bg-blue {
            background-color: #3b82f6 !important;
            color: #ffffff;
            text-align: center;
            font-weight: 700;
            border: 1px solid #000;
            vertical-align: middle;
        }

        table.data-table th.bg-green {
            background-color: #22c55e !important;
            color: #ffffff;
            text-align: center;
            font-weight: 700;
            border: 1px solid #000;
            vertical-align: middle;
        }

        table.data-table th.bg-cyan {
            background-color: #0284c7 !important;
            color: #ffffff;
            text-align: center;
            font-weight: 700;
            border: 1px solid #000;
            vertical-align: middle;
        }

        table.data-table th.bg-dark {
            background-color: #334155 !important;
            color: #ffffff;
            text-align: center;
            font-weight: 700;
            border: 1px solid #000;
            vertical-align: middle;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        a.product-link {
            color: #0f172a;
            text-decoration: none !important;
            font-weight: 600;
        }

        a.product-link:hover {
            color: #0284c7;
        }

        @media print {
            @page {
                size: landscape;
                margin: 8mm;
            }

            body {
                padding: 0;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .no-print {
                display: none !important;
            }

            table.data-table th,
            table.data-table td {
                border: 1px solid #000 !important;
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
                <th class="bg-blue" style="width: 3%;">No</th>
                <th class="bg-blue" style="width: 12%;">SKU</th>
                <th class="bg-blue">Nama Produk</th>
                <th class="bg-blue" style="width: 13%;">Kategori / Merk</th>
                <th class="bg-blue" style="width: 10%;">Status &amp; PO</th>
                <th class="bg-green" style="width: 8%;">Stok Gudang</th>
                @foreach($stores as $store)
                    <th class="bg-cyan">
                        {{ $store->store_name }}
                        <span style="font-weight: normal; font-size: 10px; display: block;">
                            ({{ ucfirst($store->channel->name ?? $store->channel->code ?? 'Marketplace') }})
                        </span>
                    </th>
                @endforeach
                <th class="bg-dark" style="width: 8%;">Total Stok</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $index => $product)
                @php
                    $stokGudang = (int) $product->stock;
                    $totalStok = $stokGudang;
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
                    <td class="text-right" style="background-color: #f0fdf4;">
                        <strong style="color: #15803d;">{{ number_format($stokGudang, 0, ',', '.') }}</strong>
                    </td>
                    @foreach($stores as $store)
                        @php
                            $storeMpProducts = $product->marketplaceProducts->where('store_id', $store->id);
                            $storeStock = $storeMpProducts->isNotEmpty() ? (int) $storeMpProducts->max('stock') : 0;
                        @endphp
                        <td class="text-right">
                            <span style="font-weight: {{ $storeStock > 0 ? 'bold' : 'normal' }}; color: {{ $storeStock > 0 ? '#0369a1' : '#94a3b8' }};">
                                {{ number_format($storeStock, 0, ',', '.') }}
                            </span>
                        </td>
                    @endforeach
                    <td class="text-right" style="background-color: #f8fafc;">
                        <strong style="color: {{ $stokGudang <= 0 ? '#dc2626' : '#0f172a' }};">
                            {{ number_format($stokGudang, 0, ',', '.') }}
                        </strong>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 7 + count($stores) }}" class="text-center" style="padding: 20px;">Tidak ada data barang yang sesuai dengan filter.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
