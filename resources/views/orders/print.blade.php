<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Resi - {{ $order->tracking_number ?? $order->order_marketplace_id }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
            background: #fff;
            padding: 10px;
            font-size: 11px;
        }

        .waybill-container {
            width: 100%;
            max-width: 450px;
            margin: 0 auto;
            background: #fff;
        }

        /* ─── SHOPEE THERMAL LABEL STYLES ─── */
        .shopee-label-wrapper {
            border: 2px solid #000;
            padding: 4px;
            position: relative;
        }

        .shopee-top-repeat {
            display: flex;
            justify-content: space-around;
            font-size: 9px;
            font-weight: bold;
            font-family: monospace;
            padding: 2px 0;
        }

        .shopee-side-repeat {
            position: absolute;
            font-size: 8px;
            font-weight: bold;
            font-family: monospace;
            white-space: nowrap;
        }

        .shopee-side-left {
            transform: rotate(-90deg);
            transform-origin: left top;
            left: -12px;
            top: 60%;
        }

        .shopee-side-right {
            transform: rotate(90deg);
            transform-origin: right top;
            right: -12px;
            top: 40%;
        }

        .shopee-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px dashed #000;
            padding-bottom: 4px;
        }

        .shopee-logo {
            font-size: 18px;
            font-weight: 900;
            color: #EE4D2D;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .shopee-service {
            font-size: 22px;
            font-weight: 900;
            letter-spacing: 1px;
        }

        .shopee-courier {
            font-size: 18px;
            font-weight: 900;
            color: #d0011b;
            font-style: italic;
        }

        .shopee-routing-row {
            display: flex;
            border-bottom: 2px dashed #000;
        }

        .shopee-hub-box {
            width: 32%;
            border-right: 2px solid #000;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            font-weight: 900;
            padding: 6px;
        }

        .shopee-barcode-box {
            width: 68%;
            padding: 4px;
        }

        .shopee-barcode-subhead {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .shopee-sub-code {
            border: 1px solid #000;
            padding: 1px 6px;
            font-size: 11px;
            font-weight: 900;
        }

        .shopee-barcode-img svg {
            width: 100%;
            height: 48px;
            display: block;
        }

        .shopee-address-box {
            border-bottom: 2px dashed #000;
            padding: 6px 4px;
            font-size: 10.5px;
            line-height: 1.3;
        }

        .shopee-people-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
        }

        .shopee-tag-home {
            display: inline-block;
            border: 1px solid #000;
            padding: 1px 5px;
            font-size: 9px;
            font-weight: bold;
            margin-top: 2px;
        }

        .shopee-district-boxes {
            display: flex;
            gap: 4px;
            margin-top: 6px;
        }

        .shopee-district-box {
            flex: 1;
            border: 1px solid #000;
            padding: 2px;
            text-align: center;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .shopee-weight-qr-row {
            display: flex;
            border-bottom: 2px solid #000;
            padding: 6px 4px;
        }

        .shopee-weight-info {
            width: 65%;
            font-size: 11px;
            line-height: 1.45;
        }

        .shopee-qr-box {
            width: 35%;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .shopee-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-top: 4px;
        }

        .shopee-table th {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 4px 2px;
            text-align: left;
            font-weight: bold;
        }

        .shopee-table td {
            padding: 4px 2px;
            border-bottom: 1px dashed #ccc;
            vertical-align: top;
        }

        .shopee-bottom-notes {
            font-size: 9.5px;
            font-weight: bold;
            margin-top: 6px;
            padding: 4px;
        }

        /* ─── TIKTOK / TOKOPEDIA THERMAL LABEL STYLES ─── */
        .tiktok-label-wrapper {
            border: 2px solid #000;
            padding: 6px;
            position: relative;
        }

        .tiktok-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 6px;
        }

        .tiktok-courier-logo {
            font-size: 20px;
            font-weight: 900;
            color: #d0011b;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .tiktok-service-ez {
            font-size: 26px;
            font-weight: 900;
            margin-left: 15px;
        }

        .tiktok-qr-top {
            width: 75px;
            height: 75px;
        }

        .tiktok-people-grid {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 6px 0;
            margin-bottom: 6px;
        }

        .tiktok-people-row {
            display: flex;
            justify-content: space-between;
            font-size: 10.5px;
            margin-bottom: 3px;
        }

        .tiktok-full-address {
            font-size: 12px;
            font-weight: 900;
            line-height: 1.35;
            margin-top: 4px;
            text-transform: lowercase;
        }

        .tiktok-weight-row {
            display: flex;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            font-size: 10px;
        }

        .tiktok-weight-col {
            flex: 1;
            padding: 3px 6px;
            border-right: 1px solid #000;
        }

        .tiktok-weight-col:last-child {
            border-right: none;
        }

        .tiktok-item-summary-row {
            font-size: 10.5px;
            padding: 4px 0;
        }

        .tiktok-cod-banner-box {
            text-align: center;
            margin: 4px 0;
        }

        .tiktok-cod-title {
            font-size: 32px;
            font-weight: 900;
            letter-spacing: 2px;
            line-height: 1;
        }

        .tiktok-black-bar {
            background: #000;
            color: #fff;
            font-weight: 900;
            font-size: 12px;
            padding: 3px 0;
            text-align: center;
            letter-spacing: 1px;
            margin-top: 2px;
        }

        .tiktok-routing-border-box {
            border: 2px solid #000;
            padding: 6px;
            text-align: center;
            margin: 6px 0;
        }

        .tiktok-routing-code {
            font-size: 26px;
            font-weight: 900;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }

        .tiktok-barcode-main svg {
            width: 100%;
            height: 55px;
            display: block;
        }

        .tiktok-tracking-str {
            font-size: 20px;
            font-weight: 900;
            letter-spacing: 1px;
            margin-top: 2px;
        }

        .tiktok-disclaimer {
            font-size: 8px;
            margin-top: 4px;
        }

        .tiktok-order-est-row {
            display: flex;
            justify-content: space-between;
            border: 1px solid #000;
            padding: 3px 6px;
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .tiktok-packing-header {
            font-size: 10px;
            margin-bottom: 4px;
        }

        .tiktok-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-bottom: 6px;
        }

        .tiktok-table th {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 4px 2px;
            text-align: left;
            font-weight: bold;
        }

        .tiktok-table td {
            padding: 4px 2px;
            border-bottom: 1px dashed #eee;
            vertical-align: top;
        }

        .tiktok-qty-total-row {
            text-align: right;
            font-weight: 900;
            font-size: 11px;
            border-top: 1px solid #000;
            padding-top: 4px;
            margin-bottom: 8px;
        }

        .tiktok-footer-logos {
            border-top: 1.5px solid #000;
            padding-top: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .tiktok-logo-brand {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 16px;
            font-weight: 900;
        }

        @media print {
            body {
                padding: 0;
                background: #fff;
            }

            .waybill-container {
                max-width: 100%;
                width: 100%;
            }
        }
    </style>
</head>

<body onload="initPrint()">

    @php
        $channelCode = $order->store->channel->code ?? 'shopee';
        $trackingNo = $order->tracking_number ?? ($order->order_marketplace_id ?? 'NO-RESI');
        
        // Weight calculation
        $totalWeightGram = 0;
        $totalItemsCount = 0;
        $sizeSummaryParts = [];

        foreach ($order->items as $item) {
            $w = $item->masterProduct->weight ?? 0.2;
            $totalWeightGram += ($w * 1000) * $item->quantity;
            $totalItemsCount += $item->quantity;
            if (!empty($item->masterProduct->ukuran)) {
                $sizeSummaryParts[] = $item->masterProduct->ukuran;
            }
        }
        $weightKgStr = number_format($totalWeightGram / 1000, 3);
        $sizeSummaryStr = !empty($sizeSummaryParts) ? implode(', ', array_unique($sizeSummaryParts)) : 'L';

        // COD Check
        $isCod = false;
        if ($order->financial_breakdown && isset($order->financial_breakdown['payment_method'])) {
            $isCod = stripos($order->financial_breakdown['payment_method'], 'cod') !== false;
        }

        // City & Postal parse
        $tujuanKota = 'KOTA TASIKMALAYA';
        if (preg_match('/(?:KOTA|KABUPATEN|KAB\.)\s+([^,]+)/i', $order->shipping_address ?? '', $mCity)) {
            $tujuanKota = strtoupper($mCity[0]);
        }

        $kecamatanStr = 'TEBING TINGGI';
        if (preg_match('/(?:KECAMATAN|KEC\.)\s+([^,]+)/i', $order->shipping_address ?? '', $mKec)) {
            $kecamatanStr = strtoupper($mKec[1]);
        }

        $kabupatenStr = 'KAB. KEPULAUAN MERANTI';
        if (preg_match('/(?:KABUPATEN|KAB\.)\s+([^,]+)/i', $order->shipping_address ?? '', $mKab)) {
            $kabupatenStr = strtoupper($mKab[0]);
        }

        // Ship date
        $shipDateStr = $order->created_at ? $order->created_at->addDays(2)->format('d-m-Y') : date('d-m-Y');

        // Courier
        $courierName = strtoupper($order->courier ?: 'SPX Express');
        $serviceName = 'REG';
        if (stripos($courierName, 'ECO') !== false || stripos($courierName, 'HEMAT') !== false) {
            $serviceName = 'ECO';
        } elseif (stripos($courierName, 'EZ') !== false) {
            $serviceName = 'EZ';
        }
    @endphp

    <div class="waybill-container">

        @if ($channelCode === 'shopee')
            {{-- ════════════════════════════════════════════════════════════════════ --}}
            {{-- ── TEMPLATE RESI SHOPEE (MATCHING IMAGE 2) ───────────────────────── --}}
            {{-- ════════════════════════════════════════════════════════════════════ --}}
            <div class="shopee-top-repeat">
                <span>{{ $trackingNo }}</span>
                <span>{{ $trackingNo }}</span>
                <span>{{ $trackingNo }}</span>
            </div>

            <div class="shopee-label-wrapper">
                <!-- Header -->
                <div class="shopee-header">
                    <div class="shopee-logo">
                        <i class="fas fa-shopping-bag"></i> Shopee
                    </div>
                    <div class="shopee-service">{{ $serviceName }}</div>
                    <div class="shopee-courier">
                        @if(stripos($courierName, 'SPX') !== false)
                            SPX <span style="font-size:12px;font-style:normal;">EXPRESS</span>
                        @else
                            {{ $courierName }}
                        @endif
                    </div>
                </div>

                <!-- Hub & Barcode Row -->
                <div class="shopee-routing-row">
                    <div class="shopee-hub-box">
                        Q - 37
                    </div>
                    <div class="shopee-barcode-box">
                        <div class="shopee-barcode-subhead">
                            <span class="shopee-sub-code">TTR-A-05</span>
                            <span>Resi: <strong>{{ $trackingNo }}</strong></span>
                        </div>
                        <div class="shopee-barcode-img">
                            <svg id="shopee-barcode-main"></svg>
                        </div>
                    </div>
                </div>

                <!-- Address & Sender Box -->
                <div class="shopee-address-box">
                    <div class="shopee-people-row">
                        <div>
                            <strong>Penerima: {{ $order->buyer_name }}</strong><br>
                            <span class="shopee-tag-home">HOME</span>
                        </div>
                        <div class="text-end">
                            <strong>Pengirim: {{ $order->store->store_name }}</strong><br>
                            <span>{{ $order->buyer_phone ?? '6282321358006' }}</span><br>
                            <span style="text-transform:uppercase;">{{ $order->store->city ?? 'KOTA TASIKMALAYA' }}</span>
                        </div>
                    </div>
                    <div style="margin-top: 4px; font-weight: 500;">
                        {{ $order->shipping_address }}
                    </div>

                    <div class="shopee-district-boxes">
                        <div class="shopee-district-box">{{ $kabupatenStr }}</div>
                        <div class="shopee-district-box">{{ $kecamatanStr }}</div>
                        <div class="shopee-district-box"></div>
                    </div>
                </div>

                <!-- Weight, Batas Kirim & QR Code -->
                <div class="shopee-weight-qr-row">
                    <div class="shopee-weight-info">
                        <div><strong>Berat:</strong> &nbsp; {{ number_format($totalWeightGram) }} gr &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <strong>COD Cek Dulu:</strong> {{ $isCod ? 'Ya' : 'Tidak' }}</div>
                        <div><strong>Batas Kirim:</strong> {{ $shipDateStr }}</div>
                        <div><strong>No.Pesanan:</strong> <span style="font-weight:900;">{{ $order->order_marketplace_id }}</span></div>

                        <div style="margin-top: 4px;">
                            <svg id="shopee-barcode-order"></svg>
                        </div>
                    </div>
                    <div class="shopee-qr-box">
                        <div id="shopee-qrcode" style="width:90px;height:90px;"></div>
                    </div>
                </div>

                <!-- Item Table -->
                <table class="shopee-table">
                    <thead>
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 50%;">Nama Produk</th>
                            <th style="width: 25%;">SKU</th>
                            <th style="width: 12%;">Variasi</th>
                            <th style="width: 8%;" class="text-center">Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->items as $idx => $item)
                            <tr>
                                <td>{{ $idx + 1 }}</td>
                                <td>{{ $item->product_name }}</td>
                                <td style="font-family:monospace;">{{ $item->sku ?? ($item->masterProduct->sku ?? '-') }}</td>
                                <td>{{ $item->masterProduct->ukuran ?? 'L' }}</td>
                                <td class="text-center" style="font-weight:bold;">{{ $item->quantity }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="shopee-bottom-notes">
                    Pesan: ({{ $order->order_marketplace_id }}) ({{ $trackingNo }})
                </div>

                <div class="shopee-top-repeat" style="border-top: 1px dashed #000; margin-top: 4px; padding-top: 4px;">
                    <span>{{ $trackingNo }}</span>
                    <span>{{ $trackingNo }}</span>
                    <span>{{ $trackingNo }}</span>
                </div>
            </div>

        @else
            {{-- ════════════════════════════════════════════════════════════════════ --}}
            {{-- ── TEMPLATE RESI TIKTOK SHOP / TOKOPEDIA (MATCHING IMAGE 1) ─────── --}}
            {{-- ════════════════════════════════════════════════════════════════════ --}}
            <div class="tiktok-label-wrapper">
                <!-- Header -->
                <div class="tiktok-header">
                    <div>
                        <div class="tiktok-courier-logo">
                            <span style="color:#d0011b;font-weight:900;">J&T</span><span style="color:#000;font-size:14px;font-style:italic;">EXPRESS</span>
                        </div>
                        <div style="font-size:9px;color:#d0011b;font-weight:bold;margin-top:1px;">
                            <i class="fas fa-phone-alt"></i> (021) 80661888
                        </div>
                    </div>

                    <div class="tiktok-service-ez">
                        {{ $serviceName }}
                    </div>

                    <div id="tiktok-qrcode-top" class="tiktok-qr-top"></div>
                </div>

                <!-- Pengirim & Penerima -->
                <div class="tiktok-people-grid">
                    <div class="tiktok-people-row">
                        <div><strong>Pengirim :</strong> {{ $order->store->store_name }}</div>
                        <div>(+62){{ substr($order->buyer_phone ?? '83896458438', -10) }}</div>
                    </div>
                    <div style="font-size:9.5px;color:#333;margin-bottom:4px;">
                        JAWA BARAT, TASIKMALAYA
                    </div>

                    <div class="tiktok-people-row" style="margin-top: 4px;">
                        <div><strong>Penerima :</strong> {{ $order->buyer_name }}</div>
                        <div>(+62){{ substr($order->buyer_phone ?? '8377777728', -10) }}</div>
                    </div>
                    <div style="font-size:9.5px;color:#333;">
                        {{ $tujuanKota }}
                    </div>

                    <div class="tiktok-full-address">
                        {{ $order->shipping_address }}
                    </div>
                </div>

                <!-- Weight & Ship Date Row -->
                <div class="tiktok-weight-row">
                    <div class="tiktok-weight-col">Weight : &nbsp; <strong>{{ $weightKgStr }} KG</strong></div>
                    <div class="tiktok-weight-col">Ship : &nbsp; <strong>{{ $shipDateStr }}</strong></div>
                </div>

                <div class="tiktok-item-summary-row">
                    Jumlah : <strong>{{ $totalItemsCount }}pcs</strong>, Barang : <strong>{{ $sizeSummaryStr }}</strong>
                </div>

                <!-- COD Badge & Black Bar -->
                <div class="tiktok-cod-banner-box">
                    <div class="tiktok-cod-title" style="{{ $isCod ? '' : 'color:#555;' }}">
                        {{ $isCod ? 'COD' : 'NON-COD' }}
                    </div>
                    <div class="tiktok-black-bar">
                        RT 02 RW11
                    </div>
                </div>

                <!-- Routing Code & Barcode Box -->
                <div class="tiktok-routing-border-box">
                    <div class="tiktok-routing-code">
                        350-CJR07B-07C
                    </div>

                    <div class="tiktok-barcode-main">
                        <svg id="tiktok-barcode-main"></svg>
                    </div>

                    <div class="tiktok-tracking-str">
                        {{ $trackingNo }}
                    </div>

                    <div class="tiktok-disclaimer">
                        Syarat dan ketentuan pengiriman dapat dilihat pada website www.jet.co.id
                    </div>
                </div>

                <!-- Order ID & Estimated Date -->
                <div class="tiktok-order-est-row">
                    <div>Order Id : {{ $order->order_marketplace_id }}</div>
                    <div>Estimated Date:</div>
                </div>

                <!-- Packing List Table -->
                <div class="tiktok-packing-header">
                    In transit by: {{ $shipDateStr }} 23:59
                </div>

                <table class="tiktok-table">
                    <thead>
                        <tr>
                            <th style="width: 45%;">Product Name</th>
                            <th style="width: 15%;">SKU</th>
                            <th style="width: 30%;">Seller SKU</th>
                            <th style="width: 10%;" class="text-center">Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->items as $item)
                            <tr>
                                <td>{{ $item->product_name }}</td>
                                <td style="font-family:monospace;">{{ $item->masterProduct->ukuran ?? 'L' }}</td>
                                <td style="font-family:monospace;">{{ $item->sku ?? 'BB-MI-JABAR-LPJ' }}</td>
                                <td class="text-center" style="font-weight:bold;">{{ $item->quantity }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="tiktok-qty-total-row">
                    Qty Total: {{ $totalItemsCount }}
                </div>

                <!-- Footer Logos -->
                <div class="tiktok-footer-logos">
                    <div class="tiktok-logo-brand">
                        <span style="color:#03ac0e;"><i class="fas fa-shopping-bag me-1"></i>tokopedia</span>
                        <span>|</span>
                        <span><i class="fab fa-tiktok me-1"></i>Shop</span>
                    </div>
                    <div style="font-size:10px;font-weight:bold;">
                        Order ID: {{ $order->order_marketplace_id }}
                    </div>
                </div>
            </div>
        @endif

    </div>

    <script>
        function initPrint() {
            @if ($channelCode === 'shopee')
                try {
                    JsBarcode("#shopee-barcode-main", "{{ $trackingNo }}", {
                        format: "CODE128",
                        width: 1.6,
                        height: 44,
                        displayValue: false,
                        margin: 0
                    });

                    JsBarcode("#shopee-barcode-order", "{{ $order->order_marketplace_id }}", {
                        format: "CODE128",
                        width: 1.2,
                        height: 30,
                        displayValue: false,
                        margin: 0
                    });

                    new QRCode(document.getElementById("shopee-qrcode"), {
                        text: "{{ $trackingNo }}",
                        width: 90,
                        height: 90,
                        colorDark: "#000000",
                        colorLight: "#ffffff",
                        correctLevel: QRCode.CorrectLevel.L
                    });
                } catch (e) {
                    console.error("Error generating Shopee barcodes", e);
                }
            @else
                try {
                    JsBarcode("#tiktok-barcode-main", "{{ $trackingNo }}", {
                        format: "CODE128",
                        width: 1.8,
                        height: 52,
                        displayValue: false,
                        margin: 0
                    });

                    new QRCode(document.getElementById("tiktok-qrcode-top"), {
                        text: "{{ $trackingNo }}",
                        width: 75,
                        height: 75,
                        colorDark: "#000000",
                        colorLight: "#ffffff",
                        correctLevel: QRCode.CorrectLevel.L
                    });
                } catch (e) {
                    console.error("Error generating TikTok barcodes", e);
                }
            @endif
        }
    </script>
</body>

</html>
