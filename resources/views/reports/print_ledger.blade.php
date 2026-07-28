<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartu / Riwayat Mutasi Stok - {{ $product->name }}</title>
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
            padding: 12px 15px;
            font-size: 10px;
            background: #fff;
            line-height: 1.2;
        }

        /* Top Title Header */
        .title-header {
            text-align: center;
            margin-bottom: 8px;
        }

        .title-header h1 {
            margin: 0;
            font-size: 20px;
            font-weight: 900;
            letter-spacing: 0.5px;
            color: #000;
            text-transform: uppercase;
        }

        .title-header h2 {
            margin: 4px 0 2px 0;
            font-size: 15px;
            font-weight: 800;
            color: #1e293b;
            text-transform: uppercase;
        }

        .title-header .sku-code {
            color: #475569;
            font-weight: 700;
        }

        .title-header .subtitle-period {
            font-size: 10.5px;
            font-weight: 700;
            color: #475569;
            margin-top: 2px;
        }

        .title-header .subtitle-printed {
            font-size: 9.5px;
            color: #64748b;
            margin-top: 1px;
        }

        /* Dashed Separator Line */
        .dashed-divider {
            border-top: 2px dashed #475569;
            margin: 8px 0 12px 0;
            width: 100%;
        }

        /* Ledger Main Table */
        table.ledger-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5px;
        }

        table.ledger-table th,
        table.ledger-table td {
            border: 1px solid #000;
            padding: 4px 5px;
            text-align: center;
            vertical-align: middle;
        }

        /* Blue Header Column Styles */
        .th-blue {
            background-color: #2563eb !important;
            color: #ffffff !important;
            font-weight: 800;
            font-size: 9.5px;
            text-transform: uppercase;
        }

        /* Green Header Column Styles (Penerimaan) */
        .th-green {
            background-color: #16a34a !important;
            color: #ffffff !important;
            font-weight: 800;
            font-size: 9.5px;
            text-transform: uppercase;
        }

        /* Red Header Column Styles (Pengeluaran) */
        .th-red {
            background-color: #dc2626 !important;
            color: #ffffff !important;
            font-weight: 800;
            font-size: 9.5px;
            text-transform: uppercase;
        }

        .td-muted {
            color: #dc2626;
            font-weight: 500;
        }

        .td-val-in {
            color: #16a34a;
            font-weight: 800;
        }

        .td-val-out {
            color: #dc2626;
            font-weight: 800;
        }

        .td-balance {
            font-weight: 900;
            color: #0f172a;
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
            📊 Kartu / Riwayat Mutasi Stok Barang
        </div>
        <div>
            <button onclick="window.print()" style="padding: 6px 16px; background:#22c55e; color:#fff; border:none; border-radius:6px; cursor:pointer; font-weight:800; font-size:12px;">
                🖨️ Cetak Kartu Stok
            </button>
            <button onclick="window.close()" style="padding: 6px 14px; background:#475569; color:#fff; border:none; border-radius:6px; cursor:pointer; margin-left:8px; font-weight:700; font-size:12px;">
                ✕ Tutup
            </button>
        </div>
    </div>

    {{-- Title Header --}}
    <div class="title-header">
        <h1>KARTU / RIWAYAT MUTASI STOK BARANG</h1>
        <h2>{{ strtoupper($product->name) }} <span class="sku-code">({{ $product->sku ?: 'NO-SKU' }})</span></h2>
        <div class="subtitle-period">
            Periode:
            @if (request('start_date') || request('end_date'))
                {{ request('start_date') ? \Carbon\Carbon::parse(request('start_date'))->format('d/m/Y') : 'Awal' }}
                s/d
                {{ request('end_date') ? \Carbon\Carbon::parse(request('end_date'))->format('d/m/Y') : 'Sekarang' }}
            @else
                Semua Periode Transaksi
            @endif
        </div>
        <div class="subtitle-printed">
            Tanggal Cetak: {{ date('d/m/Y H:i:s') }}
        </div>
    </div>

    <div class="dashed-divider"></div>

    <table class="ledger-table">
        <thead>
            <tr>
                <th class="th-blue" rowspan="2" style="width: 3%;">No</th>
                <th class="th-blue" rowspan="2" style="width: 8%;">Tanggal</th>
                <th class="th-blue" rowspan="2" style="width: 10%;">No Faktur</th>
                <th class="th-blue" rowspan="2" style="width: 12%;">Pelanggan</th>
                <th class="th-blue" rowspan="2" style="width: 8%;">Wilayah</th>
                <th class="th-blue" rowspan="2" style="width: 7%;">Kode Sales</th>
                <th class="th-blue" rowspan="2" style="width: 8%;">Nama Sales</th>
                <th class="th-blue" rowspan="2" style="width: 14%;">Keterangan</th>

                <!-- PENERIMAAN HEADER -->
                <th class="th-green" colspan="4">PENERIMAAN</th>

                <!-- PENGELUARAN HEADER -->
                <th class="th-red" colspan="3">PENGELUARAN</th>

                <!-- SALDO HEADER -->
                <th class="th-blue" rowspan="2" style="width: 8%;">Saldo</th>
            </tr>
            <tr>
                <!-- Sub-headers under PENERIMAAN -->
                <th class="th-green" style="width: 5%;">Pembelian</th>
                <th class="th-green" style="width: 5%;">Retur Jual</th>
                <th class="th-green" style="width: 5%;">Batal Jual</th>
                <th class="th-green" style="width: 6%;">Penyesuaian (+)</th>

                <!-- Sub-headers under PENGELUARAN -->
                <th class="th-red" style="width: 5%;">Penjualan</th>
                <th class="th-red" style="width: 5%;">Retur Beli</th>
                <th class="th-red" style="width: 6%;">Penyesuaian (-)</th>
            </tr>
        </thead>
        <tbody>
            @php
                $unitName = strtoupper($product->unit ?: 'Pcs');
            @endphp

            <!-- SALDO AWAL ROW -->
            <tr style="background: #ffffff; font-weight: bold;">
                <td>1</td>
                <td>{{ request('start_date') ? \Carbon\Carbon::parse(request('start_date'))->format('d M Y') : '—' }}</td>
                <td>—</td>
                <td>—</td>
                <td>—</td>
                <td>—</td>
                <td>—</td>
                <td style="text-align: left; padding-left: 6px; font-weight: bold;">SALDO AWAL PERIODE</td>
                <!-- Penerimaan -->
                <td class="td-muted">-</td>
                <td class="td-muted">-</td>
                <td class="td-muted">-</td>
                <td class="td-muted">-</td>
                <!-- Pengeluaran -->
                <td class="td-muted">-</td>
                <td class="td-muted">-</td>
                <td class="td-muted">-</td>
                <!-- Saldo -->
                <td class="td-balance" style="background: #e2e8f0;">{{ number_format($saldoAwal) }} {{ $unitName }}</td>
            </tr>

            @forelse($movements as $idx => $mov)
                @php
                    $movDate = $mov->created_at ? $mov->created_at->format('d M Y') : '—';
                    $qty = (float)$mov->quantity;
                    $ref = $mov->reference ?? '';

                    // Resolve order reference info if exists
                    $orderRef = null;
                    $prefixes = [
                        'Pesanan Masuk: ',
                        'Pembatalan Pesanan: ',
                        'Terima Retur (Layak Jual): ',
                        'Penggantian Retur: ',
                    ];
                    foreach ($prefixes as $pfx) {
                        if (str_starts_with($ref, $pfx)) {
                            $orderRef = substr($ref, strlen($pfx));
                            break;
                        }
                    }
                    $order = $orderRef ? ($orderMap[$orderRef] ?? null) : null;

                    // Extract fields
                    $noFaktur = $order ? ($order->invoice_number ?: $order->order_marketplace_id) : ($mov->reference_no ?: $ref);
                    if (str_contains($noFaktur, ':')) {
                        $parts = explode(':', $noFaktur);
                        $noFaktur = trim(end($parts));
                    }

                    $pelanggan = $order ? ($order->buyer_name ?: ($order->store ? $order->store->store_name : '—')) : '—';
                    $wilayah = $order ? ($order->region ?: ($order->city ?: '—')) : '—';
                    $kodeSales = $order && isset($order->sales_code) ? $order->sales_code : '—';
                    $namaSales = $order && !empty($order->sales_name) ? $order->sales_name : ($mov->user ? $mov->user->name : 'System');
                    $keterangan = $ref;

                    // Classify into columns
                    $pembelian = '-';
                    $returJual = '-';
                    $batalJual = '-';
                    $penyesuaianPlus = '-';

                    $penjualan = '-';
                    $returBeli = '-';
                    $penyesuaianMinus = '-';

                    if ($mov->type === 'in' || $qty > 0) {
                        $valStr = number_format(abs($qty)) . ' ' . $unitName;
                        if (str_contains($ref, 'Retur (Layak Jual)') || str_contains(strtolower($ref), 'retur jual')) {
                            $returJual = $valStr;
                        } elseif (str_contains($ref, 'Pembatalan Pesanan') || str_contains(strtolower($ref), 'batal')) {
                            $batalJual = $valStr;
                        } elseif (str_contains(strtolower($ref), 'opname') || str_contains(strtolower($ref), 'penyesuaian')) {
                            $penyesuaianPlus = $valStr;
                        } else {
                            $pembelian = $valStr;
                        }
                    } else {
                        $valStr = number_format(abs($qty)) . ' ' . $unitName;
                        if (str_contains(strtolower($ref), 'retur beli') || str_contains(strtolower($ref), 'retur supplier')) {
                            $returBeli = $valStr;
                        } elseif (str_contains(strtolower($ref), 'opname') || str_contains(strtolower($ref), 'penyesuaian')) {
                            $penyesuaianMinus = $valStr;
                        } else {
                            $penjualan = $valStr;
                        }
                    }
                @endphp
                <tr>
                    <td>{{ $idx + 2 }}</td>
                    <td>{{ $movDate }}</td>
                    <td style="font-weight: 700;">{{ $noFaktur }}</td>
                    <td style="text-align: left; padding-left: 4px;">{{ $pelanggan }}</td>
                    <td>{{ $wilayah }}</td>
                    <td>{{ $kodeSales }}</td>
                    <td>{{ $namaSales }}</td>
                    <td style="text-align: left; padding-left: 4px;">{{ $keterangan }}</td>

                    <!-- PENERIMAAN -->
                    <td class="{{ $pembelian !== '-' ? 'td-val-in' : 'td-muted' }}">{{ $pembelian }}</td>
                    <td class="{{ $returJual !== '-' ? 'td-val-in' : 'td-muted' }}">{{ $returJual }}</td>
                    <td class="{{ $batalJual !== '-' ? 'td-val-in' : 'td-muted' }}">{{ $batalJual }}</td>
                    <td class="{{ $penyesuaianPlus !== '-' ? 'td-val-in' : 'td-muted' }}">{{ $penyesuaianPlus }}</td>

                    <!-- PENGELUARAN -->
                    <td class="{{ $penjualan !== '-' ? 'td-val-out' : 'td-muted' }}">{{ $penjualan }}</td>
                    <td class="{{ $returBeli !== '-' ? 'td-val-out' : 'td-muted' }}">{{ $returBeli }}</td>
                    <td class="{{ $penyesuaianMinus !== '-' ? 'td-val-out' : 'td-muted' }}">{{ $penyesuaianMinus }}</td>

                    <!-- SALDO -->
                    <td class="td-balance">{{ number_format($mov->balance_after) }} {{ $unitName }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="16" style="padding: 20px;" class="td-muted">
                        Tidak ada riwayat pergerakan stok pada periode ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>

</html>
