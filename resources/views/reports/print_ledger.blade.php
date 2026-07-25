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
            font-size: 12px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
        }

        .header h1 {
            margin: 0 0 10px 0;
            font-size: 22px;
        }

        .header p {
            margin: 0;
            font-size: 13px;
            color: #555;
        }

        .info-box {
            border: 1px solid #000;
            padding: 10px;
            margin-bottom: 16px;
        }

        .info-box table {
            width: 100%;
            border: none;
        }

        .info-box table td {
            border: none;
            padding: 4px 8px;
            font-size: 13px;
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

        table.data-table th {
            background-color: #e8e8e8;
            font-weight: bold;
            font-size: 11px;
        }

        .text-right  { text-align: right; }
        .text-center { text-align: center; }
        .color-in    { color: #166534; font-weight: bold; }
        .color-out   { color: #991b1b; font-weight: bold; }

        .badge-channel {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 10px;
            font-size: 9.5px;
            font-weight: bold;
            color: #fff;
        }
        .badge-shopee    { background: #ee4d2d; }
        .badge-tiktok    { background: #111; }
        .badge-tokopedia { background: #00aa5b; }
        .badge-lazada    { background: #0f146d; }
        .badge-offline   { background: #555; }
        .badge-internal  { background: #6b7280; }

        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>

<body>

    <div class="no-print" style="margin-bottom: 16px;">
        <button onclick="window.print()" style="padding: 6px 18px; background:#0d6efd; color:#fff; border:none; border-radius:4px; cursor:pointer; font-size:13px;">
            🖨️ Cetak
        </button>
        <button onclick="window.close()" style="padding: 6px 14px; background:#6c757d; color:#fff; border:none; border-radius:4px; cursor:pointer; margin-left:8px; font-size:13px;">
            ✕ Tutup
        </button>
    </div>

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
                <th width="4%"  class="text-center">No</th>
                <th width="13%">Tanggal & Waktu</th>
                <th width="8%"  class="text-center">Tipe</th>
                <th width="27%">Referensi / Keterangan</th>
                <th width="12%">Channel</th>
                <th width="12%">Toko</th>
                <th width="9%"  class="text-right">Mutasi Qty</th>
                <th width="9%"  class="text-right">Sisa Stok</th>
                <th width="6%">PIC</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="7" class="text-right"><strong>Saldo Awal</strong></td>
                <td class="text-right"><strong>{{ number_format($saldoAwal) }}</strong></td>
                <td></td>
            </tr>
            @php
                $orderRefPrefixes = [
                    'Pesanan Masuk: ',
                    'Pembatalan Pesanan: ',
                    'Terima Retur (Layak Jual): ',
                    'Penggantian Retur: ',
                ];
                $channelBadges = [
                    'shopee'    => ['label' => 'Shopee',    'class' => 'badge-shopee'],
                    'tiktok'    => ['label' => 'TikTok',    'class' => 'badge-tiktok'],
                    'tokopedia' => ['label' => 'Tokopedia', 'class' => 'badge-tokopedia'],
                    'lazada'    => ['label' => 'Lazada',    'class' => 'badge-lazada'],
                ];
            @endphp

            @forelse($movements as $index => $mov)
                @php
                    // Resolve order dari reference
                    $orderRef = null;
                    foreach ($orderRefPrefixes as $prefix) {
                        if (str_starts_with($mov->reference, $prefix)) {
                            $orderRef = substr($mov->reference, strlen($prefix));
                            break;
                        }
                    }
                    $order       = $orderRef ? ($orderMap[$orderRef] ?? null) : null;
                    $channelCode = $order?->store?->channel?->code;
                    $storeName   = $order?->store?->store_name;

                    // Deteksi internal movements tanpa order
                    $isOffline   = !$order && str_contains(strtolower($mov->reference ?? ''), 'offline');
                    $isOpname    = str_contains($mov->reference ?? '', 'Opname') || str_contains($mov->reference ?? '', 'opname');
                    $isGR        = str_contains($mov->reference ?? '', 'Penerimaan Barang') || str_contains($mov->reference ?? '', 'Pembelian');
                    $isRetur     = str_contains($mov->reference ?? '', 'Retur');
                    $isMutation  = str_contains($mov->reference ?? '', 'Mutasi');
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $mov->created_at->format('d/m/Y H:i') }}</td>
                    <td class="text-center">
                        @if ($mov->type == 'in')
                            <span class="color-in">▲ Masuk</span>
                        @elseif($mov->type == 'out')
                            <span class="color-out">▼ Keluar</span>
                        @else
                            ↔ Sesuaian
                        @endif
                    </td>
                    <td>{{ $mov->reference }}</td>

                    {{-- Kolom Channel --}}
                    <td class="text-center">
                        @if ($channelCode && isset($channelBadges[$channelCode]))
                            <span class="badge-channel {{ $channelBadges[$channelCode]['class'] }}">
                                {{ $channelBadges[$channelCode]['label'] }}
                            </span>
                        @elseif ($isOffline)
                            <span class="badge-channel badge-offline">Offline</span>
                        @elseif ($isOpname || $isGR || $isMutation)
                            <span class="badge-channel badge-internal">Internal</span>
                        @else
                            <span style="color:#aaa; font-size:10px;">—</span>
                        @endif
                    </td>

                    {{-- Kolom Toko --}}
                    <td>
                        @if ($storeName)
                            {{ $storeName }}
                        @elseif ($isOffline)
                            Toko Offline
                        @else
                            <span style="color:#aaa; font-size:10px;">—</span>
                        @endif
                    </td>

                    <td class="text-right">
                        @if ($mov->quantity > 0)
                            <span class="color-in">+{{ number_format($mov->quantity) }}</span>
                        @elseif($mov->quantity < 0)
                            <span class="color-out">{{ number_format($mov->quantity) }}</span>
                        @else
                            0
                        @endif
                    </td>
                    <td class="text-right"><strong>{{ number_format($mov->balance_after) }}</strong></td>
                    <td>{{ $mov->user->name ?? 'Sistem' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center" style="padding: 20px;">
                        Tidak ada pergerakan stok pada periode ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>

</html>
