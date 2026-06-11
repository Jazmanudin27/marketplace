<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk — {{ $offlineSale->sale_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            background: #f5f5f5;
            display: flex;
            justify-content: center;
            padding: 20px;
        }
        .receipt {
            background: white;
            width: 300px;
            padding: 20px 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,.1);
        }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .divider { border-top: 1px dashed #999; margin: 10px 0; }
        .row { display: flex; justify-content: space-between; margin: 4px 0; }
        .items-table { width: 100%; margin: 8px 0; }
        .items-table td { padding: 3px 0; vertical-align: top; }
        .items-table .price-col { text-align: right; white-space: nowrap; }
        .total-row { display: flex; justify-content: space-between; font-size: 14px; font-weight: bold; margin: 5px 0; }
        .footer { text-align: center; margin-top: 12px; font-size: 11px; color: #666; }
        @media print {
            body { background: white; padding: 0; }
            .receipt { box-shadow: none; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
<div class="receipt">
    <div class="center bold" style="font-size:15px;margin-bottom:4px;">
        {{ $tenant->name ?? 'Toko Kami' }}
    </div>
    <div class="center" style="font-size:11px;color:#555;">Penjualan Offline</div>

    <div class="divider"></div>

    <div class="row"><span>No. Transaksi</span><span class="bold">{{ $offlineSale->sale_number }}</span></div>
    <div class="row"><span>Tanggal</span><span>{{ $offlineSale->sold_at?->format('d/m/Y H:i') }}</span></div>
    <div class="row"><span>Kasir</span><span>{{ $offlineSale->user->name ?? '-' }}</span></div>
    @if($offlineSale->buyer_name)
        <div class="row"><span>Pembeli</span><span>{{ $offlineSale->buyer_name }}</span></div>
    @endif

    <div class="divider"></div>

    <table class="items-table">
        @foreach ($offlineSale->items as $item)
            <tr>
                <td style="width:55%">{{ $item->product_name }}</td>
                <td style="text-align:center;width:20%">{{ $item->quantity }}x</td>
                <td class="price-col">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td colspan="2" style="color:#666;font-size:11px;">@ Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                <td></td>
            </tr>
        @endforeach
    </table>

    <div class="divider"></div>

    <div class="row"><span>Subtotal</span><span>Rp {{ number_format($offlineSale->total_amount, 0, ',', '.') }}</span></div>
    @if($offlineSale->discount_amount > 0)
        <div class="row"><span>Diskon</span><span>- Rp {{ number_format($offlineSale->discount_amount, 0, ',', '.') }}</span></div>
    @endif
    <div class="divider"></div>
    <div class="total-row">
        <span>TOTAL</span>
        <span>Rp {{ number_format($offlineSale->grand_total, 0, ',', '.') }}</span>
    </div>
    <div class="row"><span>{{ $offlineSale->payment_method_label }}</span><span>Rp {{ number_format($offlineSale->paid_amount, 0, ',', '.') }}</span></div>
    <div class="row bold"><span>Kembalian</span><span>Rp {{ number_format($offlineSale->change_amount, 0, ',', '.') }}</span></div>

    <div class="divider"></div>

    <div class="footer">
        Terima kasih atas kunjungan Anda!<br>
        Barang yang sudah dibeli tidak dapat<br>dikembalikan / ditukar.
    </div>
</div>

<div class="no-print" style="text-align:center;margin-top:20px;">
    <button onclick="window.print()" style="padding:10px 30px;background:#10b981;color:white;border:none;border-radius:8px;cursor:pointer;font-size:14px;">
        🖨️ Cetak Struk
    </button>
    <button onclick="window.close()" style="padding:10px 20px;background:#6b7280;color:white;border:none;border-radius:8px;cursor:pointer;font-size:14px;margin-left:10px;">
        ✕ Tutup
    </button>
</div>

<script>
// Auto print saat halaman dibuka
window.addEventListener('load', () => setTimeout(() => window.print(), 300));
</script>
</body>
</html>
