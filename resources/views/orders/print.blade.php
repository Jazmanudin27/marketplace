<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Resi - {{ $order->tracking_number ?? $order->invoice_number }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 20px;
            font-size: 11px;
        }

        .waybill {
            max-width: 450px;
            margin: 0 auto;
            border: 2px solid #000;
            padding: 12px;
            box-sizing: border-box;
        }

        .fw-bold {
            font-weight: bold;
        }

        .text-center {
            text-align: center;
        }

        .divider {
            border-bottom: 1.5px dashed #000;
            margin: 10px 0;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .header-logo {
            font-size: 18px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .header-logo svg {
            width: 24px;
            height: 24px;
        }

        .header-barcode {
            text-align: center;
            flex-grow: 1;
            padding: 0 10px;
        }

        .header-barcode svg {
            width: 100%;
            height: 50px;
            display: block;
            margin: 0 auto;
        }

        .header-courier {
            text-align: right;
            font-weight: 800;
            font-size: 16px;
            color: #000;
            text-transform: uppercase;
        }

        .resi-text {
            font-size: 12px;
            font-weight: bold;
            text-align: center;
            margin-top: 4px;
            letter-spacing: 0.5px;
        }

        .main-content {
            display: flex;
            justify-content: space-between;
            margin-top: 8px;
        }

        .info-grid {
            width: 68%;
        }

        .qr-section {
            width: 28%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .order-no {
            font-size: 10px;
            margin-bottom: 8px;
            color: #333;
        }

        .info-row {
            display: flex;
            margin-bottom: 6px;
            font-size: 10.5px;
            line-height: 1.35;
        }

        .info-label {
            width: 70px;
            font-weight: bold;
            color: #444;
        }

        .info-value {
            flex: 1;
            border-left: 2px solid #ddd;
            padding-left: 8px;
            word-break: break-all;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .sort-code {
            font-size: 20px;
            font-weight: 900;
            margin-bottom: 6px;
            text-align: center;
            letter-spacing: 0.5px;
        }

        .qr-container {
            width: 90px;
            height: 90px;
            margin-bottom: 8px;
        }

        .qr-container img {
            width: 100%;
            height: 100%;
        }

        .box-text {
            border: 2px solid #000;
            padding: 4px 8px;
            font-size: 14px;
            font-weight: 900;
            text-align: center;
            width: 100%;
            margin-bottom: 6px;
            letter-spacing: 1px;
            box-sizing: border-box;
        }

        .service-type {
            font-size: 18px;
            font-weight: 900;
            text-align: center;
            width: 100%;
            letter-spacing: 1px;
        }

        .cut-line {
            border-bottom: 1.5px dashed #000;
            position: relative;
            margin: 15px 0 10px 0;
        }

        .cut-icon {
            position: absolute;
            left: 10px;
            top: -11px;
            background: #fff;
            padding: 0 4px;
            font-size: 14px;
        }

        .product-header {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            font-size: 10.5px;
            margin-bottom: 6px;
        }

        .product-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-bottom: 10px;
        }

        .product-table th {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 5px 0;
            text-align: left;
            font-weight: bold;
        }

        .product-table td {
            padding: 5px 0;
            border-bottom: 1px dashed #ddd;
            vertical-align: top;
        }

        .product-table .text-center {
            text-align: center;
        }

        .note {
            font-weight: bold;
            font-size: 10.5px;
            border: 1px solid #000;
            padding: 6px;
            margin-top: 6px;
        }

        @media print {
            body {
                padding: 0;
            }

            .waybill {
                border: 2px solid #000;
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
                    <span style="color:#EE4D2D;">Shopee</span>
                @elseif($order->store->channel->code === 'tiktok')
                    <i class="fab fa-tiktok" style="font-size: 18px; color: #000; margin-right: 2px;"></i>
                    <span>TikTok</span>
                @else
                    <i class="fas fa-store" style="font-size: 16px; color: #000; margin-right: 2px;"></i>
                    <span>{{ $order->store->channel->name }}</span>
                @endif
            </div>
            <div class="header-barcode">
                @if ($order->tracking_number)
                    <svg id="barcode"></svg>
                @else
                    <div style="font-size: 8px; font-weight: bold; border: 1px dashed #000; padding: 4px 0;">BELUM ADA
                        RESI</div>
                @endif
            </div>
            <div class="header-courier">
                {{ $order->courier ?? 'REGULER' }}
            </div>
        </div>

        <div class="resi-text">
            No.Resi: {{ $order->tracking_number ?? 'Belum ada resi' }}
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
                    <div class="info-value fw-bold">
                        @if ($isCod)
                            Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                        @else
                            Rp0
                        @endif
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
                        @if ($order->is_dropship && $order->dropshipper_name)
                            <span class="fw-bold">{{ $order->dropshipper_name }} (Dropship)</span>
                            @if ($order->dropshipper_phone)
                                <br>{{ $order->dropshipper_phone }}
                            @endif
                        @else
                            <span class="fw-bold">{{ $order->store->store_name }}</span>
                        @endif
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
            <div class="fw-bold">DAFTAR PRODUK</div>
            <div style="font-weight: normal; color: #666;">NO.PESANAN: <span class="fw-bold"
                    style="color: #000;">{{ $order->order_marketplace_id }}</span></div>
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
                        <td class="font-monospace">{{ $item->sku ?? '-' }}</td>
                        <td>-</td>
                        <td class="text-center fw-bold">{{ $item->quantity }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="note">Catatan : -</div>
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

        }
    </script>
</body>

</html>
