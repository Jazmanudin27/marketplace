<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk — {{ $offlineSale->sale_number }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Outfit:wght@600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            background: #0c131d;
            color: #f8fafc;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        .receipt {
            background: white;
            color: black;
            width: 310px;
            padding: 25px 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,.5);
            border-radius: 6px;
        }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .divider { border-top: 1px dashed #777; margin: 12px 0; }
        .row { display: flex; justify-content: space-between; margin: 6px 0; }
        .items-table { width: 100%; margin: 8px 0; border-collapse: collapse; }
        .items-table td { padding: 4px 0; vertical-align: top; }
        .items-table .price-col { text-align: right; white-space: nowrap; }
        .total-row { display: flex; justify-content: space-between; font-size: 14px; font-weight: bold; margin: 6px 0; }
        .footer { text-align: center; margin-top: 16px; font-size: 11px; color: #555; line-height: 1.4; }
        
        .no-print {
            display: flex;
            justify-content: center;
            margin-top: 24px;
            gap: 12px;
        }
        .btn-print {
            padding: 10px 24px;
            background: #10b981;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-print:hover {
            background: #059669;
            box-shadow: 0 0 12px rgba(16,185,129,0.4);
        }
        .btn-close {
            padding: 10px 20px;
            background: #374151;
            color: #9ca3af;
            border: 1px solid #4b5563;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-close:hover {
            background: #4b5563;
            color: white;
        }
        
        @media print {
            body { background: white; padding: 0; min-height: auto; }
            .receipt { box-shadow: none; width: 100%; padding: 0; border-radius: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
<div class="receipt">
    <div class="center bold" style="font-size:16px;margin-bottom:4px;font-family:'Outfit',sans-serif;">
        {{ $tenant->name ?? 'Toko Kami' }}
    </div>
    <div class="center" style="font-size:11px;color:#555;margin-bottom:8px;">Penjualan Offline</div>

    <div class="divider"></div>

    <div class="row"><span>No. Transaksi</span><span class="bold">{{ $offlineSale->sale_number }}</span></div>
    <div class="row"><span>Tanggal</span><span>{{ $offlineSale->sold_at?->format('d/m/Y H:i') }}</span></div>
    <div class="row"><span>Kasir</span><span>{{ $offlineSale->user->name ?? '-' }}</span></div>
    @if($offlineSale->buyer_name)
        <div class="row"><span>Pembeli</span><span>{{ $offlineSale->buyer_name }}</span></div>
    @endif
    @if($offlineSale->buyer_phone)
        <div class="row"><span>No. HP</span><span>{{ $offlineSale->buyer_phone }}</span></div>
    @endif
    @if($offlineSale->customer && $offlineSale->customer->address)
        <div class="row" style="flex-direction: column; align-items: flex-start; margin-top: 4px;">
            <span>Alamat:</span>
            <span style="font-size: 11px; color: #555; text-align: left; width: 100%; white-space: normal; word-break: break-word;">{{ $offlineSale->customer->address }}</span>
        </div>
    @endif

    <div class="divider"></div>

    <table class="items-table">
        @foreach ($offlineSale->items as $item)
            <tr>
                <td style="width:60%">{{ $item->product_name }}</td>
                <td style="text-align:center;width:15%">{{ $item->quantity }}x</td>
                <td class="price-col">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td colspan="2" style="color:#555;font-size:11px;padding-top:0;">@ Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
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

<div class="no-print">
    <button class="btn-print" onclick="window.print()">
        🖨️ Cetak Struk
    </button>
    <button class="btn-close" onclick="window.close()">
        ✕ Tutup
    </button>
</div>

<script>
// Auto print saat halaman dibuka
window.addEventListener('load', () => setTimeout(() => window.print(), 300));
</script>
</body>
</html>
