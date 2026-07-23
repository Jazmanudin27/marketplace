<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Produk Marketplace</title>
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

        .filter-info {
            margin-bottom: 12px;
            font-size: 10px;
            color: #444;
            background-color: #f1f3f5;
            padding: 6px 10px;
            border-radius: 4px;
            border-left: 3px solid #0d6efd;
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
            flex: 1;
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

        .badge-mapped {
            background-color: #198754;
            color: #fff;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            display: inline-block;
        }

        .badge-unmapped {
            background-color: #ffc107;
            color: #000;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            display: inline-block;
        }

        .badge-active {
            background-color: #0d6efd;
            color: #fff;
            padding: 1px 4px;
            border-radius: 2px;
            font-size: 8px;
        }

        .badge-inactive {
            background-color: #6c757d;
            color: #fff;
            padding: 1px 4px;
            border-radius: 2px;
            font-size: 8px;
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
        <h1>LAPORAN PRODUK MARKETPLACE</h1>
        <p>Tanggal Cetak: {{ date('d-m-Y H:i:s') }} | Perusahaan: {{ Auth::user()->tenant->name ?? 'ERP System' }}</p>
    </div>

    @php
        $appliedFilters = [];
        if (request('status') === 'mapped') $appliedFilters[] = "Status: Sudah Ditautkan";
        elseif (request('status') === 'unmapped') $appliedFilters[] = "Status: Belum Ditautkan";
        if (request('name')) $appliedFilters[] = "Nama: \"" . request('name') . "\"";
        if (request('sku')) $appliedFilters[] = "SKU: \"" . request('sku') . "\"";
        if ($selectedChannel) $appliedFilters[] = "Channel: " . $selectedChannel->name;
        if ($selectedStore) $appliedFilters[] = "Toko: " . $selectedStore->store_name;
    @endphp

    @if (count($appliedFilters) > 0)
        <div class="filter-info">
            <strong>Filter Aktif:</strong> {{ implode(' | ', $appliedFilters) }}
        </div>
    @endif

    <div class="summary-box">
        <div class="summary-item">
            <label>Total Produk</label>
            <span>{{ number_format($totalCount) }}</span>
        </div>
        <div class="summary-item">
            <label>Sudah Ditautkan</label>
            <span style="color: #198754;">{{ number_format($mappedCount) }}</span>
        </div>
        <div class="summary-item">
            <label>Belum Ditautkan</label>
            <span style="color: #d97706;">{{ number_format($unmappedCount) }}</span>
        </div>
        <div class="summary-item">
            <label>Total Stok Marketplace</label>
            <span style="color: #0d6efd;">{{ number_format($totalStock) }}</span>
        </div>
        <div class="summary-item">
            <label>Total Nilai Marketplace</label>
            <span style="color: #059669;">Rp {{ number_format($totalValue, 0, ',', '.') }}</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="4%" class="text-center">NO</th>
                <th width="26%">NAMA PRODUK MARKETPLACE</th>
                <th width="14%">SKU MARKETPLACE</th>
                <th width="15%">CHANNEL & TOKO</th>
                <th width="10%" class="text-right">HARGA</th>
                <th width="7%" class="text-center">STOK</th>
                <th width="16%">MASTER PRODUCT TERTAUT</th>
                <th width="8%" class="text-center">SINKRONISASI</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $index => $p)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $p->name }}</strong>
                        @if($p->marketplace_product_id)
                            <br><span style="color: #777; font-size: 8px;">ID MP: {{ $p->marketplace_product_id }}</span>
                        @endif
                    </td>
                    <td class="font-mono">
                        {{ $p->marketplace_sku ?: '-' }}
                    </td>
                    <td>
                        <strong>{{ $p->store->store_name ?? '-' }}</strong>
                        <br><span style="color: #555; font-size: 9px;">Channel: {{ $p->store->channel->name ?? '-' }}</span>
                    </td>
                    <td class="text-right font-mono">Rp {{ number_format($p->price, 0, ',', '.') }}</td>
                    <td class="text-center font-mono"><strong>{{ number_format($p->stock) }}</strong></td>
                    <td>
                        @if($p->masterProduct)
                            <span class="badge-mapped">TERTAUT</span>
                            <br><strong>{{ $p->masterProduct->name }}</strong>
                            <br><span class="font-mono" style="font-size: 9px; color: #555;">SKU: {{ $p->masterProduct->sku }}</span>
                        @else
                            <span class="badge-unmapped">BELUM DITAUTKAN</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <span style="font-size: 8px;">
                            Stok: {!! $p->sync_stock ? '<span class="badge-active">Ya</span>' : '<span class="badge-inactive">Tidak</span>' !!}
                            <br>Harga: {!! $p->sync_price ? '<span class="badge-active">Ya</span>' : '<span class="badge-inactive">Tidak</span>' !!}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 15px;">Tidak ada data produk marketplace yang ditemukan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
