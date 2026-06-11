<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Resi - {{ $order->tracking_number ?? $order->invoice_number }}</title>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 20px;
            font-size: 12px;
        }

        .waybill {
            max-width: 420px;
            margin: 0 auto;
            border: 3px solid #000;
            padding: 10px;
            box-sizing: border-box;
        }

        .fw-bold {
            font-weight: bold;
        }

        .text-center {
            text-align: center;
        }

        .divider {
            border-bottom: 2px dashed #000;
            margin: 8px 0;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 2px;
        }

        .header-logo {
            width: 30%;
            font-size: 20px;
            font-weight: bold;
            color: #EE4D2D;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .header-logo svg {
            width: 28px;
            height: 28px;
        }

        .header-barcode {
            width: 40%;
            text-align: center;
        }

        .header-barcode svg {
            width: 100%;
            height: 50px;
            display: block;
            margin: 0 auto;
        }

        .header-courier {
            width: 30%;
            text-align: right;
            font-weight: bold;
            font-size: 14px;
            color: #D00;
            text-transform: uppercase;
            line-height: 1.1;
        }

        .resi-text {
            font-size: 11px;
            margin-top: 2px;
            text-align: center;
        }

        .main-content {
            display: flex;
            justify-content: space-between;
            margin-top: 8px;
        }

        .info-grid {
            width: 65%;
        }

        .qr-section {
            width: 32%;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        .order-no {
            font-size: 11px;
            margin-bottom: 8px;
        }

        .info-row {
            display: flex;
            margin-bottom: 6px;
            font-size: 11px;
            line-height: 1.3;
        }

        .info-label {
            width: 65px;
            font-weight: bold;
        }

        .info-value {
            flex: 1;
            border-left: 2px solid #ccc;
            padding-left: 6px;
        }

        .sort-code {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
            text-align: right;
        }

        .qr-container {
            width: 90px;
            height: 90px;
            margin-bottom: 10px;
        }

        .qr-container img {
            width: 100%;
            height: 100%;
        }

        .box-text {
            border: 1px solid #000;
            padding: 4px 0;
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            width: 100%;
            margin-bottom: 5px;
            letter-spacing: 1px;
        }

        .service-type {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            width: 100%;
            letter-spacing: 1px;
        }

        .cut-line {
            border-bottom: 2px dashed #000;
            position: relative;
            margin: 15px 0 10px 0;
        }

        .cut-icon {
            position: absolute;
            left: 0;
            top: -11px;
            background: #fff;
            padding-right: 5px;
            font-size: 16px;
        }

        .product-header {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 5px;
        }

        .product-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin-bottom: 10px;
        }

        .product-table th {
            border-bottom: 1px solid #000;
            padding: 4px 0;
            text-align: left;
        }

        .product-table td {
            padding: 4px 0;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }

        .product-table .text-center {
            text-align: center;
        }

        .note {
            font-weight: bold;
            font-size: 11px;
        }

        @media print {
            body {
                padding: 0;
            }

            .waybill {
                border: none;
                max-width: 100%;
                width: 100%;
            }
        }
    </style>
</head>

<body onload="initPrint()">

    @php
        // 1. Parse Tujuan City
        $tujuan = 'KOTA TUJUAN';
        if ($order->shipping_address) {
            if (preg_match('/(?:KOTA|KABUPATEN|KAB\.)\s+([^,]+)/i', $order->shipping_address, $matches)) {
                $tujuan = strtoupper($matches[0]);
            } else {
                $addressParts = array_map('trim', explode(',', $order->shipping_address));
                if (count($addressParts) > 1) {
                    $foundCity = false;
                    for ($i = count($addressParts) - 1; $i >= 0; $i--) {
                        if (preg_match('/\b\d{5}\b/', $addressParts[$i])) {
                            if (isset($addressParts[$i - 1])) {
                                $tujuan = strtoupper($addressParts[$i - 1]);
                                $foundCity = true;
                                break;
                            }
                        }
                    }
                    if (!$foundCity) {
                        $tujuan = strtoupper($addressParts[count($addressParts) - 2] ?? $addressParts[0]);
                    }
                }
            }
        }

        // 2. Parse Sort Code / Postal Code
        $postalCode = '';
        if ($order->shipping_address && preg_match('/\b\d{5}\b/', $order->shipping_address, $matches)) {
            $postalCode = $matches[0];
        }
        $sortCode = $postalCode ? 'SUB' . $postalCode : 'SUB10028';

        // 3. Service Type (REG, HEM, CAR)
        $serviceType = 'REG';
        $courierLower = strtolower($order->courier);
        if (strpos($courierLower, 'hemat') !== false || strpos($courierLower, 'economy') !== false) {
            $serviceType = 'HEM';
        } elseif (strpos($courierLower, 'cargo') !== false || strpos($courierLower, 'trucking') !== false) {
            $serviceType = 'CAR';
        }

        // 4. Calculate total weight in grams
        $totalWeight = 0;
        foreach ($order->items as $item) {
            $weight = $item->masterProduct->weight ?? 1; // default 1 kg
            $totalWeight += $weight * $item->quantity;
        }
        $totalWeightGram = $totalWeight * 1000;

        // 5. COD check
        $isCod = false;
        if ($order->financial_breakdown && isset($order->financial_breakdown['payment_method'])) {
            $isCod = stripos($order->financial_breakdown['payment_method'], 'cod') !== false;
        }
    @endphp

    <div class="waybill">
        <!-- HEADER -->
        <div class="header">
            <div class="header-logo">
                @if ($order->store->channel->code === 'shopee')
                    <svg viewBox="0 0 24 24">
                        <path
                            d="M16.2 3.8l-1.3-1.6c-.2-.2-.5-.3-.8-.3H9.9c-.3 0-.6.1-.8.3L7.8 3.8h8.4zM22.5 6H1.5c-.5 0-.8.4-.8.8l1.6 13.9c.1.6.6 1.1 1.2 1.1h17.1c.6 0 1.1-.5 1.2-1.1L23.3 6.8c0-.4-.4-.8-.8-.8zm-10.5 12c-2.8 0-4.8-1.2-4.8-1.2l.7-1.4s1.6 1 4 1c1.5 0 2.2-.6 2.2-1.4 0-.8-.7-1.1-2.1-1.6-1.9-.6-3.2-1.6-3.2-3.3 0-2 1.7-3.4 4.3-3.4 2.5 0 4.1 1 4.1 1l-.7 1.5s-1.4-.8-3.4-.8c-1.2 0-2 .5-2 1.2 0 .7.6 1 2 1.4 2 .6 3.4 1.5 3.4 3.4-.1 2.2-1.9 3.6-4.5 3.6z"
                            fill="#EE4D2D" />
                    </svg>
                    Shopee
                @elseif($order->store->channel->code === 'tiktok')
                    <i class="fab fa-tiktok" style="font-size: 20px; color: #000; margin-right: 4px;"></i>
                    TikTok
                @else
                    <i class="fas fa-store" style="font-size: 18px; color: #000; margin-right: 4px;"></i>
                    {{ $order->store->channel->name }}
                @endif
            </div>
            <div class="header-barcode">
                @if ($order->tracking_number)
                    <svg id="barcode"></svg>
                @else
                    <div style="font-size: 8px; font-weight: bold; border: 1px dashed #000; padding: 4px 0;">BELUM ADA RESI</div>
                @endif
            </div>
            <div class="header-courier">
                {{ $order->courier ?? 'REGULER' }}
            </div>
        </div>

        <div class="resi-text">
            No.Resi {{ $order->tracking_number ?? 'Belum ada resi' }}
        </div>

        <div class="divider"></div>

        <div class="main-content">
            <div class="info-grid">
                <div class="order-no">No. Pesanan: <span class="fw-bold">{{ $order->order_marketplace_id }}</span></div>

                <div class="info-row">
                    <div class="info-label">Asal</div>
                    <div class="info-value">
                        {{ $order->store->city ?? 'KOTA JAKARTA' }}
                    </div>
                </div>

                <div class="info-row">
                    <div class="info-label">Tujuan</div>
                    <div class="info-value">
                        {{ $tujuan }}
                    </div>
                </div>

                <div class="info-row">
                    <div class="info-label">Total COD</div>
                    <div class="info-value">
                        @if ($isCod)
                            Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                        @else
                            Rp0
                        @endif
                    </div>
                </div>

                <div class="info-row">
                    <div class="info-label">Biaya</div>
                    <div class="info-value">
                        Asuransi: -<br>
                        Biaya kirim: {{ number_format($order->shipping_fee, 0, ',', '.') }}
                    </div>
                </div>

                <div class="info-row">
                    <div class="info-label">Penerima</div>
                    <div class="info-value">
                        <span class="fw-bold">{{ $order->buyer_name }}</span>, {{ $order->buyer_phone ?? '-' }}<br>
                        {{ $order->shipping_address }}
                    </div>
                </div>

                <div class="info-row">
                    <div class="info-label">Pengirim</div>
                    <div class="info-value">
                        <span class="fw-bold">{{ $order->store->store_name }}</span>
                    </div>
                </div>

                <div class="info-row">
                    <div class="info-label">Total Berat</div>
                    <div class="info-value">{{ number_format($totalWeightGram) }} gr</div>
                </div>
            </div>

            <div class="qr-section">
                <div class="sort-code">{{ $sortCode }}</div>
                <div class="qr-container" id="qrcode"></div>
                <div class="box-text">
                    @if ($isCod)
                        COD
                    @else
                        NON-COD
                    @endif
                </div>
                <div class="service-type">{{ $serviceType }}</div>
            </div>
        </div>

        <div class="cut-line">
            <div class="cut-icon">✂</div>
        </div>

        <div class="product-header">
            <div>DAFTAR PRODUK</div>
            <div style="font-weight: normal; color: #666;">NO.PESANAN <span class="fw-bold" style="color: #000;">{{ $order->order_marketplace_id }}</span></div>
        </div>

        <table class="product-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 55%;">Nama Produk</th>
                    <th style="width: 15%;">SKU</th>
                    <th style="width: 15%;">Variasi</th>
                    <th style="width: 10%;" class="text-center">Qty</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $itemIndex => $item)
                    <tr>
                        <td>{{ $itemIndex + 1 }}</td>
                        <td>{{ $item->product_name }}</td>
                        <td>{{ $item->sku ?? '-' }}</td>
                        <td>-</td>
                        <td class="text-center fw-bold">{{ $item->quantity }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="note">KOMENTAR PEMBELI: -</div>
    </div>

    <script>
        function initPrint() {
            @if ($order->tracking_number)
                try {
                    JsBarcode("#barcode", "{{ $order->tracking_number }}", {
                        format: "CODE128",
                        width: 1.5,
                        height: 40,
                        displayValue: false,
                        margin: 0
                    });

                    new QRCode(document.getElementById("qrcode"), {
                        text: "{{ $order->tracking_number }}",
                        width: 90,
                        height: 90,
                        colorDark: "#000000",
                        colorLight: "#ffffff",
                        correctLevel: QRCode.CorrectLevel.L
                    });
                } catch (e) {
                    console.error("Error generating code", e);
                }
            @endif

            setTimeout(function() {
                window.print();
            }, 800);
        }
    </script>
</body>

</html>
