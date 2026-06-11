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
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
        }

        .header h1 {
            margin: 0 0 10px 0;
            font-size: 24px;
        }

        .header p {
            margin: 0;
            font-size: 14px;
            color: #555;
        }

        .info {
            margin-bottom: 20px;
            font-size: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 8px 10px;
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
        <h1>LAPORAN STOK BARANG</h1>
        <p>Tanggal Dicetak: {{ date('d-m-Y H:i:s') }}</p>
    </div>

    <div class="info">
        @if (request('category_id'))
            <p><strong>Filter Kategori:</strong> {{ App\Models\Category::find(request('category_id'))->name ?? '-' }}
            </p>
        @endif
        @if (request('brand_id'))
            <p><strong>Filter Merk:</strong> {{ App\Models\Brand::find(request('brand_id'))->name ?? '-' }}</p>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%" class="text-center">No</th>
                <th width="15%">SKU</th>
                <th width="35%">Nama Produk</th>
                <th width="15%">Kategori</th>
                <th width="10%">Merk</th>
                <th width="10%" class="text-right">Stok</th>
                <th width="10%" class="text-center">Satuan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $index => $product)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $product->sku ?? '-' }}</td>
                    <td>{{ $product->name }}</td>
                    <td>{{ $product->category->name ?? '-' }}</td>
                    <td>{{ $product->brand->name ?? '-' }}</td>
                    <td class="text-right"><strong>{{ number_format($product->stock, 0, ',', '.') }}</strong></td>
                    <td class="text-center">{{ $product->unit ?? 'Pcs' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 20px;">Tidak ada data barang yang sesuai
                        dengan filter.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
