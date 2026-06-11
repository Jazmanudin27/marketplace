<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Riwayat Opname</title>
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
        <h1>LAPORAN RIWAYAT STOK OPNAME</h1>
        <p>Tanggal Dicetak: {{ date('d-m-Y H:i:s') }}</p>
    </div>

    <div class="info">
        @if (request('category_id'))
            <p><strong>Filter Kategori:</strong> {{ App\Models\Category::find(request('category_id'))->name ?? '-' }}
            </p>
        @endif
        @if (request('start_date') || request('end_date'))
            <p><strong>Periode:</strong>
                {{ request('start_date') ? \Carbon\Carbon::parse(request('start_date'))->format('d-m-Y') : 'Awal' }}
                s/d
                {{ request('end_date') ? \Carbon\Carbon::parse(request('end_date'))->format('d-m-Y') : 'Sekarang' }}
            </p>
        @else
            <p><strong>Periode:</strong> Semua Waktu</p>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%" class="text-center">No</th>
                <th width="15%">Waktu Opname</th>
                <th width="15%">PIC / Referensi</th>
                <th width="35%">SKU - Nama Produk</th>
                <th width="10%" class="text-right">Stok Awal</th>
                <th width="10%" class="text-right">Opname</th>
                <th width="10%" class="text-right">Stok Akhir</th>
            </tr>
        </thead>
        <tbody>
            @forelse($histories as $index => $history)
                @php
                    // Ambil nama PIC dari string reference "Stock Opname Massal - Nama"
                    $pic = str_replace('Stock Opname Massal - ', '', $history->reference);
                    if ($pic == $history->reference) {
                        $pic = $history->user->name ?? 'Sistem';
                    }
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $history->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $pic }}</td>
                    <td>
                        @if ($history->masterProduct)
                            {{ $history->masterProduct->sku ? '[' . $history->masterProduct->sku . '] ' : '' }}
                            {{ $history->masterProduct->name }}
                        @else
                            <span style="color:red; font-style:italic;">Produk Dihapus</span>
                        @endif
                    </td>
                    <td class="text-right">
                        {{ $history->balance_after - $history->quantity }}
                    </td>
                    <td class="text-right">
                        @if ($history->quantity > 0)
                            <span style="color: green;">+{{ $history->quantity }}</span>
                        @elseif($history->quantity < 0)
                            <span style="color: red;">{{ $history->quantity }}</span>
                        @else
                            0
                        @endif
                    </td>
                    <td class="text-right"><strong>{{ $history->balance_after }}</strong></td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 20px;">Tidak ada data riwayat opname pada
                        periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
