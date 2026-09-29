<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Katalog Master Produk - {{ $tenant->name ?? 'ASPARTECH ERP' }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #000000;
            margin: 0;
            padding: 20px;
            background-color: #ffffff;
        }

        .text-center { text-align: center; }
        .text-start { text-align: left; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }

        /* Report Header Styling matching sample screenshot */
        .report-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .report-header h2 {
            margin: 0 0 4px 0;
            font-size: 18px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #000000;
        }

        .report-header p {
            margin: 0;
            font-size: 11px;
            font-weight: bold;
            color: #222222;
            text-transform: uppercase;
        }

        /* Report Table Styling matching sample screenshot */
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .report-table th,
        .report-table td {
            border: 1px solid #000000;
            padding: 5px 7px;
            font-size: 10px;
            vertical-align: middle;
            line-height: 1.3;
        }

        .report-table th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            color: #000000;
        }

        @media print {
            body {
                padding: 0;
            }

            .report-table th {
                background-color: #f2f2f2 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>

    <!-- Main Report Header -->
    <div class="report-header">
        <h2>LAPORAN KATALOG MASTER PRODUK</h2>
        <p>{{ strtoupper($tenant->name ?? 'ASPARTECH ERP') }} • TANGGAL CETAK: {{ date('d-m-Y H:i') }}</p>
    </div>

    <!-- Main Report Table -->
    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 30px;">No</th>
                <th style="width: 110px;">SKU Master</th>
                <th>Nama Produk Master</th>
                <th>Kategori</th>
                <th>Model / Brand</th>
                <th style="width: 80px;" class="text-end">HPP (Rp)</th>
                <th style="width: 85px;" class="text-end">Harga Jual</th>
                <th style="width: 70px;" class="text-end">Est. Kain</th>
                <th style="width: 90px;" class="text-end">Est. Produksi</th>
                <th style="width: 55px;" class="text-center">Stok</th>
                <th style="width: 55px;" class="text-center">Jenis</th>
                <th style="width: 55px;" class="text-center">Tipe</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $index => $prod)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center fw-bold">{{ $prod->sku }}</td>
                    <td>
                        <span class="fw-bold" title="{{ $prod->name }}">{{ \Illuminate\Support\Str::limit($prod->name, 50) }}</span>
                        @if($prod->sku_induk && $prod->sku_induk !== $prod->sku)
                            <div style="font-size: 9px; color: #555;">(Induk: {{ $prod->sku_induk }})</div>
                        @endif
                    </td>
                    <td>{{ $prod->category->name ?? '-' }}</td>
                    <td>{{ $prod->brand->name ?? '-' }}</td>
                    <td class="text-end">{{ isset($prod->cost_price) && $prod->cost_price > 0 ? number_format($prod->cost_price, 0, ',', '.') : '-' }}</td>
                    <td class="text-end fw-bold">{{ number_format($prod->selling_price ?? $prod->price ?? 0, 0, ',', '.') }}</td>
                    <td class="text-end">{{ $prod->est_kain > 0 ? number_format($prod->est_kain, 2, ',', '.') . ' m' : '-' }}</td>
                    <td class="text-end">{{ $prod->est_biaya_produksi > 0 ? 'Rp ' . number_format($prod->est_biaya_produksi, 0, ',', '.') : '-' }}</td>
                    <td class="text-center fw-bold">{{ number_format($prod->stock ?? 0) }} {{ $prod->unit ?? 'pcs' }}</td>
                    <td class="text-center">{{ $prod->is_bundle ? 'Bundle' : 'Single' }}</td>
                    <td class="text-center">{{ $prod->is_preorder ? 'PO' : 'Ready' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" class="text-center py-3">Tidak ada data produk master yang sesuai dengan filter.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
