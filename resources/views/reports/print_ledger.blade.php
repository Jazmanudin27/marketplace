<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartu Stok - {{ $product->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #000;
            margin: 0;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
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

        .info-box {
            border: 1px solid #000;
            padding: 10px;
            margin-bottom: 20px;
        }

        .info-box table {
            width: 100%;
            border: none;
        }

        .info-box table td {
            border: none;
            padding: 4px 8px;
            font-size: 14px;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        table.data-table th,
        table.data-table td {
            border: 1px solid #000;
            padding: 8px 10px;
            text-align: left;
        }

        table.data-table th {
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
        <h1>LAPORAN KARTU STOK (BIN CARD)</h1>
        <p>Tanggal Dicetak: {{ date('d-m-Y H:i:s') }}</p>
    </div>

    <div class="info-box">
        <table>
            <tr>
                <td width="15%"><strong>SKU / Kode</strong></td>
                <td width="35%">: {{ $product->sku ?? '-' }}</td>
                <td width="15%"><strong>Periode</strong></td>
                <td width="35%">:
                    @if (request('start_date') || request('end_date'))
                        {{ request('start_date') ? \Carbon\Carbon::parse(request('start_date'))->format('d-m-Y') : 'Awal' }}
                        s/d
                        {{ request('end_date') ? \Carbon\Carbon::parse(request('end_date'))->format('d-m-Y') : 'Sekarang' }}
                    @else
                        Semua Waktu
                    @endif
                </td>
            </tr>
            <tr>
                <td><strong>Nama Barang</strong></td>
                <td>: {{ $product->name }}</td>
                <td><strong>Total Stok Terkini</strong></td>
                <td>: {{ number_format($product->stock) }}</td>
            </tr>
        </table>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th width="5%" class="text-center">No</th>
                <th width="15%">Tanggal & Waktu</th>
                <th width="10%" class="text-center">Tipe</th>
                <th width="30%">Referensi / Keterangan</th>
                <th width="15%" class="text-right">Mutasi Qty</th>
                <th width="10%" class="text-right">Sisa Stok</th>
                <th width="15%">PIC / User</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="5" class="text-right"><strong>Saldo Awal</strong></td>
                <td class="text-right"><strong>{{ number_format($saldoAwal) }}</strong></td>
                <td></td>
            </tr>
            @forelse($movements as $index => $mov)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $mov->created_at->format('d/m/Y H:i') }}</td>
                    <td class="text-center">
                        @if ($mov->type == 'in')
                            Masuk
                        @elseif($mov->type == 'out')
                            Keluar
                        @else
                            Penyesuaian
                        @endif
                    </td>
                    <td>{{ $mov->reference }}</td>
                    <td class="text-right">
                        @if ($mov->quantity > 0)
                            <span style="color: green;">+{{ number_format($mov->quantity) }}</span>
                        @elseif($mov->quantity < 0)
                            <span style="color: red;">{{ number_format($mov->quantity) }}</span>
                        @else
                            0
                        @endif
                    </td>
                    <td class="text-right"><strong>{{ number_format($mov->balance_after) }}</strong></td>
                    <td>{{ $mov->user->name ?? 'Sistem' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 20px;">Tidak ada pergerakan stok pada periode
                        ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>

</html>
