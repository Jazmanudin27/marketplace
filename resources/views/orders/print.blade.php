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
            font-size: 10px;
            font-weight: bold;
            line-height: 1.25;
            margin-top: 4px;
            word-break: break-word;
            word-wrap: break-word;
            overflow-wrap: anywhere;
            max-height: 38px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
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
        $channelCode = strtolower($order->store->channel->code ?? 'shopee');
        $trackingNo = $order->tracking_number ?? ($order->order_marketplace_id ?? 'NO-RESI');
        
        // Weight calculation
        $totalWeightGram = 0;
        $totalItemsCount = 0;
        $sizeSummaryParts = [];

        foreach ($order->items as $item) {
            $w = $item->masterProduct->weight ?? 0.11;
            $totalWeightGram += ($w * 1000) * $item->quantity;
            $totalItemsCount += $item->quantity;
            if (!empty($item->masterProduct->ukuran)) {
                $sizeSummaryParts[] = $item->masterProduct->ukuran;
            } elseif (!empty($item->product_name) && preg_match('/\b(S|M|L|XL|XXL|3XL|4XL|5XL|Panjang|Pendek)\b/i', $item->product_name, $mSz)) {
                $sizeSummaryParts[] = $mSz[0];
            }
        }
        $weightKgStr = number_format($totalWeightGram / 1000, 3);
        $sizeSummaryStr = !empty($sizeSummaryParts) ? implode(', ', array_unique($sizeSummaryParts)) : 'XL';

        // COD Check
        $isCod = (bool) $order->is_cod;

        // Clean Address
        $rawAddress = $order->shipping_address ?? '';
        $cleanAddress = preg_replace('/\*{4,}/', '***', $rawAddress);
        $cleanAddress = rtrim(trim($cleanAddress), ', ');

        // Clean Shopee Address: remove leading asterisks ***
        $cleanShopeeAddress = preg_replace('/^[\*\s]+/', '', $rawAddress);
        $cleanShopeeAddress = preg_replace('/\*{3,}/', '', $cleanShopeeAddress);
        $cleanShopeeAddress = rtrim(trim($cleanShopeeAddress), ', ');

        // Hub Code & Sub Route Code
        $shopeeHubCode = $order->financial_breakdown['shopee_hub_code'] ?? 'AC-29';
        $shopeeSubRoute = $order->financial_breakdown['shopee_sub_route'] ?? 'PGY-A-04';
        $shopeeBlackBarTag = $order->financial_breakdown['sorting_tag'] ?? 'Samudra Petuguran-15-9';

        // District Boxes for Shopee (Kabupaten, Kecamatan, Desa)
        $shopeeKab = 'KAB. BREBES';
        if (preg_match('/(?:KOTA|KABUPATEN|KAB\.)\s+([^,]+)/i', $cleanShopeeAddress, $mKb)) {
            $shopeeKab = 'KAB. ' . strtoupper(trim($mKb[1]));
        }

        $shopeeKec = 'PAGUYANGAN';
        if (preg_match('/(?:KECAMATAN|KEC\.)\s+([^,]+)/i', $cleanShopeeAddress, $mKc)) {
            $shopeeKec = strtoupper(trim($mKc[1]));
        } elseif (preg_match('/(PAGUYANGAN|TELUK JAMBE|CILEUNGSI|CIBINONG|SERPONG)/i', $cleanShopeeAddress, $mKc2)) {
            $shopeeKec = strtoupper(trim($mKc2[0]));
        }

        $shopeeDesa = 'Ragatunjung';
        if (preg_match('/(?:DESA|KELURAHAN|KEL\.|DS\.)\s+([^,]+)/i', $cleanShopeeAddress, $mDs)) {
            $shopeeDesa = ucfirst(strtolower(trim($mDs[1])));
        } elseif (preg_match('/(RAGATUNJUNG|PURWADANA|PETUGURAN|SUKAMAJU)/i', $cleanShopeeAddress, $mDs2)) {
            $shopeeDesa = ucfirst(strtolower(trim($mDs2[0])));
        }

        // Format Phone Numbers
        $formatPhone = function($phone) {
            if (!$phone) return '';
            $digits = preg_replace('/[^\d]/', '', $phone);
            if (str_starts_with($digits, '0')) {
                $digits = '62' . substr($digits, 1);
            }
            if (str_starts_with($digits, '62')) {
                return '(+62)' . substr($digits, 2);
            }
            return '(+62)' . $digits;
        };

        $senderPhoneFormatted = $formatPhone($order->store->phone ?? '085171010980');

        $shopeeSenderPhone = preg_replace('/[^\d]/', '', $order->store->phone ?? '6282321358006');
        if (str_starts_with($shopeeSenderPhone, '0')) {
            $shopeeSenderPhone = '62' . substr($shopeeSenderPhone, 1);
        }

        $rawBuyerPhone = preg_replace('/[^\d]/', '', $order->buyer_phone ?? '8377777728');
        if (str_starts_with($rawBuyerPhone, '0')) {
            $rawBuyerPhone = '62' . substr($rawBuyerPhone, 1);
        }
        if (strlen($rawBuyerPhone) >= 10) {
            $prefix = substr($rawBuyerPhone, 2, 2);
            $suffix = substr($rawBuyerPhone, -2);
            $buyerPhoneFormatted = "(+62){$prefix}*******{$suffix}";
        } else {
            $buyerPhoneFormatted = "(+62)" . $rawBuyerPhone;
        }

        // Regions
        $asalRegionStr = strtoupper($order->store->province ?? 'JAWA BARAT') . ', ' . strtoupper($order->store->city ?? 'TASIKMALAYA') . ',';

        $provStr = 'JAWA BARAT';
        if (preg_match('/(?:JAWA BARAT|JAWA TIMUR|JAWA TENGAH|DKI JAKARTA|BANTEN|DI YOGYAKARTA|BALI)[^,]*/i', $cleanAddress, $mProv)) {
            $provStr = strtoupper(trim($mProv[0]));
        }

        $kabStr = 'KARAWANG';
        if (preg_match('/(?:KOTA|KABUPATEN|KAB\.)\s+([^,]+)/i', $cleanAddress, $mKab)) {
            $kabStr = strtoupper(trim($mKab[1]));
        }

        $kecStr = 'TELUK JAMBE TIMUR';
        if (preg_match('/(?:KECAMATAN|KEC\.)\s+([^,]+)/i', $cleanAddress, $mKec)) {
            $kecStr = strtoupper(trim($mKec[1]));
        }

        $tujuanRegionStr = "{$provStr},{$kabStr},{$kecStr}";

        // RT / RW Extraction
        $rtRwStr = 'RT 05 / 03';
        if (preg_match('/RT\.?\s*(\d{1,3})\s*[\/|\-|\s]*\s*RW\.?\s*(\d{1,3})/i', $cleanAddress, $mRtRw)) {
            $rtRwStr = 'RT ' . sprintf('%02d', $mRtRw[1]) . ' / ' . sprintf('%02d', $mRtRw[2]);
        } elseif (preg_match('/RT\.?\s*(\d{1,3})\s*[\/|\-|\s]*\s*(\d{1,3})/i', $cleanAddress, $mRtRw2)) {
            $rtRwStr = 'RT ' . sprintf('%02d', $mRtRw2[1]) . ' / ' . sprintf('%02d', $mRtRw2[2]);
        }

        // Routing Code (e.g. 360-KRW02B-05A)
        $routingCode = '360-KRW02B-05A';
        if (!empty($order->financial_breakdown['routing_code'])) {
            $routingCode = $order->financial_breakdown['routing_code'];
        }

        // Ship & Estimated Dates
        $orderDateCarbon = $order->order_date ? \Carbon\Carbon::parse($order->order_date) : ($order->created_at ?: now());
        $shipDateStr = $orderDateCarbon->format('d-m-Y');
        $estimatedDateStr = $orderDateCarbon->copy()->addDays(2)->format('d-m-Y');
        $inTransitDateStr = $orderDateCarbon->copy()->addDays(2)->format('d/m/Y') . ' 23:59';

        // Courier & Service
        $courierName = strtoupper($order->courier ?: 'J&T EXPRESS');
        $serviceName = 'NDD';
        if (stripos($courierName, 'ECO') !== false || stripos($courierName, 'HEMAT') !== false) {
            $serviceName = 'ECO';
        } elseif (stripos($courierName, 'EZ') !== false) {
            $serviceName = 'EZ';
        } elseif (stripos($courierName, 'REG') !== false) {
            $serviceName = 'REG';
        }

        $shopeeService = 'STD';
        if (stripos($courierName, 'NDD') !== false) {
            $shopeeService = 'NDD';
        } elseif (stripos($courierName, 'ECO') !== false || stripos($courierName, 'HEMAT') !== false) {
            $shopeeService = 'ECO';
        }

        // For Shopee District Box
        $kecamatanStr = $kecStr;
        $kabupatenStr = "KAB. {$kabStr}";
    @endphp

    <div class="waybill-container">

        @if ($channelCode === 'shopee')
            {{-- ════════════════════════════════════════════════════════════════════ --}}
            {{-- ── TEMPLATE RESI SHOPEE (MATCHING MARKETPLACE IMAGE 2 100%) ──────── --}}
            {{-- ════════════════════════════════════════════════════════════════════ --}}
            <div style="position: relative; padding: 0 16px;">
                <!-- Vertical Outer Tracking Numbers on Margins -->
                <div style="position: absolute; left: -8px; top: 110px; transform: rotate(-90deg); transform-origin: left top; font-size: 10px; font-weight: bold; font-family: monospace; white-space: nowrap; color: #000;">
                    {{ $trackingNo }} &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; {{ $trackingNo }} &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; {{ $trackingNo }}
                </div>
                <div style="position: absolute; right: -24px; top: 110px; transform: rotate(90deg); transform-origin: right top; font-size: 10px; font-weight: bold; font-family: monospace; white-space: nowrap; color: #000;">
                    {{ $trackingNo }} &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; {{ $trackingNo }} &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; {{ $trackingNo }}
                </div>

                <div class="shopee-top-repeat">
                    <span>{{ $trackingNo }}</span>
                    <span>{{ $trackingNo }}</span>
                    <span>{{ $trackingNo }}</span>
                </div>

                <div class="shopee-label-wrapper" style="border: 2px solid #000; padding: 4px; background: #fff;">
                    <!-- Header -->
                    <div class="shopee-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px dashed #000; padding-bottom: 4px;">
                        <div class="shopee-logo" style="display: flex; align-items: center;">
                            <img src="{{ asset('images/logos/shopee.svg') }}" alt="Shopee" style="height: 28px; width: auto; object-fit: contain;">
                        </div>
                        <div class="shopee-service" style="font-size: 34px; font-weight: 900; letter-spacing: 1px; color: #000;">
                            {{ $shopeeService }}
                        </div>
                        <div class="shopee-courier" style="text-align: right; display: flex; justify-content: flex-end; align-items: center;">
                            @if (stripos($courierName, 'J&T') !== false || stripos($courierName, 'JNT') !== false)
                                <img src="{{ asset('images/logos/jnt-express.svg') }}" alt="J&T Express" style="height: 28px; width: auto; object-fit: contain;">
                            @else
                                <img src="{{ asset('images/logos/spx-express.svg') }}" alt="SPX Express" style="height: 34px; width: auto; object-fit: contain;">
                            @endif
                        </div>
                    </div>

                    <!-- Hub & Barcode Row -->
                    <div class="shopee-routing-row" style="display: flex; border-bottom: 2px dashed #000;">
                        <div class="shopee-hub-box" style="width: 32%; border-right: 2px solid #000; display: flex; align-items: center; justify-content: center; font-size: 34px; font-weight: 900; padding: 6px;">
                            {{ $shopeeHubCode }}
                        </div>
                        <div class="shopee-barcode-box" style="width: 68%; padding: 4px;">
                            <div class="shopee-barcode-subhead" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1.5px solid #000; padding-bottom: 2px; margin-bottom: 3px;">
                                <span class="shopee-sub-code" style="border: 1.5px solid #000; padding: 1px 6px; font-size: 13px; font-weight: 900;">{{ $shopeeSubRoute }}</span>
                                <span style="font-size: 11px; font-weight: bold;">No. Resi: <strong>{{ $trackingNo }}</strong></span>
                            </div>
                            <div class="shopee-barcode-img">
                                <svg id="shopee-barcode-main"></svg>
                            </div>
                        </div>
                    </div>

                    <!-- Address & Sender Box -->
                    <div class="shopee-address-box" style="border-bottom: 2px dashed #000; padding: 6px 4px;">
                        <div class="shopee-people-row" style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                            <div>
                                <strong style="font-size: 12px;">Penerima: {{ $order->buyer_name }}</strong><br>
                                <span class="shopee-tag-home" style="display: inline-block; border: 1px solid #000; padding: 1px 6px; font-size: 10px; font-weight: bold; margin-top: 2px;">HOME</span>
                            </div>
                            <div style="text-align: right; font-size: 11px;">
                                <strong>Pengirim: {{ $order->store->store_name }}</strong><br>
                                <span>{{ $shopeeSenderPhone }}</span><br>
                                <span style="text-transform: uppercase; font-weight: bold;">{{ $order->store->city ?? 'KOTA TASIKMALAYA' }}</span>
                            </div>
                        </div>
                        <div style="margin-top: 4px; font-weight: 700; font-size: 11.5px; line-height: 1.35; word-break: break-word; color: #000;">
                            {{ $cleanShopeeAddress }}
                        </div>

                        <!-- Black Bar Tag under address -->
                        <div style="background: #000; color: #fff; font-weight: 900; font-size: 13px; padding: 3px 8px; margin: 5px 0 3px 0; text-align: left; letter-spacing: 0.5px;">
                            {{ $shopeeBlackBarTag }}
                        </div>

                        <div class="shopee-district-boxes" style="display: flex; gap: 4px; margin-top: 4px;">
                            <div class="shopee-district-box" style="flex: 1; border: 1px solid #000; padding: 3px 2px; text-align: center; font-size: 10px; font-weight: bold; text-transform: uppercase;">{{ $shopeeKab }}</div>
                            <div class="shopee-district-box" style="flex: 1; border: 1px solid #000; padding: 3px 2px; text-align: center; font-size: 10px; font-weight: bold; text-transform: uppercase;">{{ $shopeeKec }}</div>
                            <div class="shopee-district-box" style="flex: 1; border: 1px solid #000; padding: 3px 2px; text-align: center; font-size: 10px; font-weight: bold; text-transform: uppercase;">{{ $shopeeDesa }}</div>
                        </div>
                    </div>

                    <!-- Weight, Batas Kirim & QR Code -->
                    <div class="shopee-weight-qr-row" style="display: flex; border-bottom: 2px solid #000; padding: 6px 4px;">
                        <div class="shopee-weight-info" style="width: 65%; font-size: 11px; line-height: 1.5;">
                            <div><strong>Berat:</strong> &nbsp;&nbsp;&nbsp; {{ number_format($totalWeightGram) }} gr &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <strong>COD Cek Dulu:</strong> {{ $isCod ? 'Ya' : 'Tidak' }}</div>
                            <div><strong>Batas Kirim:</strong> {{ $shipDateStr }}</div>
                            <div><strong>No.Pesanan:</strong> <span style="font-weight: 900;">{{ $order->order_marketplace_id }}</span></div>

                            <div style="margin-top: 4px;">
                                <svg id="shopee-barcode-order"></svg>
                            </div>
                        </div>
                        <div class="shopee-qr-box" style="width: 35%; display: flex; justify-content: center; align-items: center;">
                            <div id="shopee-qrcode" style="width: 90px; height: 90px;"></div>
                        </div>
                    </div>

                    <!-- Item Table -->
                    <table class="shopee-table" style="width: 100%; border-collapse: collapse; font-size: 10.5px; margin-top: 4px;">
                        <thead>
                            <tr>
                                <th style="width: 5%; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 4px 2px; text-align: left; font-weight: bold;">#</th>
                                <th style="width: 50%; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 4px 2px; text-align: left; font-weight: bold;">Nama Produk</th>
                                <th style="width: 25%; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 4px 2px; text-align: left; font-weight: bold;">SKU</th>
                                <th style="width: 12%; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 4px 2px; text-align: left; font-weight: bold;">Variasi</th>
                                <th style="width: 8%; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 4px 2px; text-align: center; font-weight: bold;">Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->items as $idx => $item)
                                <tr>
                                    <td style="padding: 4px 2px; border-bottom: 1px dashed #ccc; vertical-align: top;">{{ $idx + 1 }}</td>
                                    <td style="padding: 4px 2px; border-bottom: 1px dashed #ccc; vertical-align: top;">{{ $item->product_name }}</td>
                                    <td style="padding: 4px 2px; border-bottom: 1px dashed #ccc; vertical-align: top; font-family: monospace;">{{ $item->sku ?? ($item->masterProduct->sku ?? 'BB-BN-HIJAU-LPJ-XL') }}</td>
                                    <td style="padding: 4px 2px; border-bottom: 1px dashed #ccc; vertical-align: top;">{{ $item->masterProduct->ukuran ?? ($sizeSummaryStr ?: 'XL') }}</td>
                                    <td style="padding: 4px 2px; border-bottom: 1px dashed #ccc; vertical-align: top; text-align: center; font-weight: bold;">{{ $item->quantity }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="shopee-bottom-notes" style="font-size: 9.5px; font-weight: bold; margin-top: 6px; padding: 4px;">
                        Pesan: ({{ $order->order_marketplace_id }}) ({{ $trackingNo }})
                    </div>

                    <div class="shopee-top-repeat" style="border-top: 1px dashed #000; margin-top: 4px; padding-top: 4px;">
                        <span>{{ $trackingNo }}</span>
                        <span>{{ $trackingNo }}</span>
                        <span>{{ $trackingNo }}</span>
                    </div>
                </div>
            </div>

        @else
            {{-- ════════════════════════════════════════════════════════════════════ --}}
            {{-- ── TEMPLATE RESI TIKTOK SHOP / TOKOPEDIA (MATCHING IMAGE 2 100%) ─── --}}
            {{-- ════════════════════════════════════════════════════════════════════ --}}
            <div style="position: relative; padding: 0 16px;">
                <!-- Vertical Outer Tracking Numbers on Margins -->
                <div style="position: absolute; left: -8px; top: 110px; transform: rotate(-90deg); transform-origin: left top; font-size: 11px; font-weight: bold; font-family: monospace; white-space: nowrap; color: #000;">
                    {{ $trackingNo }} &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; {{ $trackingNo }}
                </div>
                <div style="position: absolute; right: -24px; top: 110px; transform: rotate(90deg); transform-origin: right top; font-size: 11px; font-weight: bold; font-family: monospace; white-space: nowrap; color: #000;">
                    {{ $trackingNo }} &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; {{ $trackingNo }}
                </div>
                <div style="position: absolute; right: 16px; top: -14px; font-size: 11px; font-weight: bold; font-family: monospace;">
                    {{ substr($trackingNo, -3) }}
                </div>

                <div class="tiktok-label-wrapper" style="border: 2.5px solid #000; padding: 6px; background: #fff;">
                    <!-- Header -->
                    <div class="tiktok-header" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px;">
                        <div>
                            <div class="tiktok-courier-logo" style="display: flex; align-items: center; gap: 4px;">
                                @if (stripos($courierName, 'J&T') !== false || stripos($courierName, 'JNT') !== false)
                                    <img src="{{ asset('images/logos/jnt-express.svg') }}" alt="J&T Express" style="height: 28px; width: auto; object-fit: contain;">
                                @elseif (stripos($courierName, 'SPX') !== false || stripos($courierName, 'SHOPEE') !== false)
                                    <img src="{{ asset('images/logos/spx-express.svg') }}" alt="SPX Express" style="height: 32px; width: auto; object-fit: contain;">
                                @else
                                    <div style="font-size: 22px; font-weight: 900; color: #d0011b; font-style: italic;">{{ $courierName }}</div>
                                @endif
                            </div>
                            <div style="font-size:10px; color:#d0011b; font-weight:bold; margin-top:1px;">
                                <i class="fas fa-phone-alt"></i> (021) 80661888
                            </div>
                        </div>

                        <!-- Service Badge in Solid Black Box -->
                        <div style="background: #000; color: #fff; font-size: 24px; font-weight: 900; padding: 3px 18px; letter-spacing: 1px; line-height: 1.1; margin-top: 2px;">
                            {{ $serviceName }}
                        </div>

                        <div id="tiktok-qrcode-top" class="tiktok-qr-top" style="width:75px; height:75px;"></div>
                    </div>

                    <!-- Pengirim & Penerima -->
                    <div class="tiktok-people-grid" style="border-top: 2px solid #000; border-bottom: 2px solid #000; padding: 6px 0; margin-bottom: 6px;">
                        <div class="tiktok-people-row" style="display: flex; justify-content: space-between; font-size: 11px; margin-bottom: 2px;">
                            <div><strong>Pengirim :</strong> {{ $order->store->store_name }}</div>
                            <div>{{ $senderPhoneFormatted }}</div>
                        </div>
                        <div style="font-size:10px; font-weight:bold; color:#000; margin-bottom:6px;">
                            {{ $asalRegionStr }}
                        </div>

                        <div class="tiktok-people-row" style="display: flex; justify-content: space-between; font-size: 11px; margin-bottom: 2px;">
                            <div><strong>Penerima :</strong> {{ $order->buyer_name }}</div>
                            <div>{{ $buyerPhoneFormatted }}</div>
                        </div>
                        <div style="font-size:10px; font-weight:bold; color:#000; margin-bottom:4px;">
                            {{ $tujuanRegionStr }}
                        </div>

                        <div class="tiktok-full-address" style="font-size: 14px; font-weight: 800; line-height: 1.35; margin-top: 4px; word-break: break-word; color: #000;">
                            {{ $cleanAddress }}
                        </div>
                    </div>

                    <!-- Weight & Ship Date Row -->
                    <div class="tiktok-weight-row" style="display: flex; border-bottom: 2px solid #000; font-size: 11px; padding: 3px 0;">
                        <div class="tiktok-weight-col" style="flex: 1; padding: 2px 6px; border-right: 1px solid #000;">Weight : &nbsp; <strong>{{ $weightKgStr }} KG</strong></div>
                        <div class="tiktok-weight-col" style="flex: 1; padding: 2px 6px;">Ship : &nbsp; <strong>{{ $shipDateStr }}</strong></div>
                    </div>

                    <div class="tiktok-item-summary-row" style="font-size: 11px; padding: 5px 0;">
                        Jumlah : <strong>{{ $totalItemsCount }}pcs</strong>, Barang : <strong>{{ $sizeSummaryStr }}</strong>
                    </div>

                    <!-- COD Title & Black Bar -->
                    <div class="tiktok-cod-banner-box" style="text-align: center; margin: 4px 0;">
                        <div class="tiktok-cod-title" style="font-size: 38px; font-weight: 900; letter-spacing: 2px; line-height: 1; color: {{ $isCod ? '#000' : '#555' }};">
                            {{ $isCod ? 'COD' : 'NON-COD' }}
                        </div>
                        <div class="tiktok-black-bar" style="background: #000; color: #fff; font-weight: 900; font-size: 13px; padding: 3px 0; text-align: center; letter-spacing: 1px; margin-top: 2px;">
                            {{ $rtRwStr }}
                        </div>
                    </div>

                    <!-- Routing Code & Barcode Box -->
                    <div class="tiktok-routing-border-box" style="border: 2px solid #000; padding: 6px; text-align: center; margin: 6px 0;">
                        <div class="tiktok-routing-code" style="font-family: 'Times New Roman', Georgia, serif; font-size: 28px; font-weight: 900; letter-spacing: 1px; margin-bottom: 4px;">
                            {{ $routingCode }}
                        </div>

                        <div class="tiktok-barcode-main">
                            <svg id="tiktok-barcode-main"></svg>
                        </div>

                        <div class="tiktok-tracking-str" style="font-family: 'Times New Roman', Georgia, serif; font-size: 24px; font-weight: 900; letter-spacing: 1px; margin-top: 2px;">
                            {{ $trackingNo }}
                        </div>

                        <div class="tiktok-disclaimer" style="font-size: 8.5px; margin-top: 4px;">
                            Syarat dan ketentuan pengiriman dapat dilihat pada website www.jet.co.id
                        </div>
                    </div>

                    <!-- Order ID & Estimated Date -->
                    <div class="tiktok-order-est-row" style="display: flex; justify-content: space-between; border: 1px solid #000; padding: 3px 6px; font-size: 11px; font-weight: bold; margin-bottom: 8px;">
                        <div>Order Id : {{ $order->order_marketplace_id }}</div>
                        <div>Estimated Date: &nbsp; {{ $estimatedDateStr }}</div>
                    </div>

                    <!-- Packing List Table -->
                    <div class="tiktok-packing-header" style="font-size: 11px; font-weight: bold; margin-bottom: 4px;">
                        In transit by: {{ $inTransitDateStr }}
                    </div>

                    <table class="tiktok-table" style="width: 100%; border-collapse: collapse; font-size: 10.5px; margin-bottom: 6px;">
                        <thead>
                            <tr>
                                <th style="width: 45%; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 4px 2px; text-align: left; font-weight: bold;">Product Name</th>
                                <th style="width: 15%; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 4px 2px; text-align: left; font-weight: bold;">SKU</th>
                                <th style="width: 30%; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 4px 2px; text-align: left; font-weight: bold;">Seller SKU</th>
                                <th style="width: 10%; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 4px 2px; text-align: center; font-weight: bold;">Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->items as $item)
                                <tr>
                                    <td style="padding: 4px 2px; border-bottom: 1px dashed #eee; vertical-align: top;">{{ $item->product_name }}</td>
                                    <td style="padding: 4px 2px; border-bottom: 1px dashed #eee; vertical-align: top;">{{ $item->masterProduct->ukuran ?? ($sizeSummaryStr ?: 'Panjang, XL') }}</td>
                                    <td style="padding: 4px 2px; border-bottom: 1px dashed #eee; vertical-align: top; font-family:monospace;">{{ $item->sku ?? ($item->masterProduct->sku ?? 'BB-BR-ABU-LPJ-XL') }}</td>
                                    <td style="padding: 4px 2px; border-bottom: 1px dashed #eee; vertical-align: top; text-align:center; font-weight:bold;">{{ $item->quantity }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="tiktok-qty-total-row" style="text-align: right; font-weight: 900; font-size: 11px; border-top: 1px solid #000; padding-top: 4px; margin-bottom: 8px;">
                        Qty Total: {{ $totalItemsCount }}
                    </div>

                    <!-- Footer Logos -->
                    <div class="tiktok-footer-logos" style="border-top: 1.5px solid #000; padding-top: 6px; display: flex; justify-content: space-between; align-items: center;">
                        <div class="tiktok-logo-brand" style="display: flex; align-items: center; gap: 8px; font-size: 16px; font-weight: 900;">
                            <span style="color:#03ac0e;"><i class="fas fa-shopping-bag me-1"></i>tokopedia</span>
                            <span>|</span>
                            <span><i class="fab fa-tiktok me-1"></i>Shop</span>
                        </div>
                        <div style="font-size:10px; font-weight:bold;">
                            Order ID: {{ $order->order_marketplace_id }}
                        </div>
                    </div>
                </div>

                <!-- Customer Message / Buyer Note Tear-Off Slip -->
                @if (!empty($order->buyer_message))
                    <div class="tiktok-customer-note-slip" style="margin-top: 15px; border-top: 2px dashed #000; padding-top: 12px;">
                        <div style="font-size: 11px; font-weight: bold; margin-bottom: 4px;">
                            In transit by: {{ $inTransitDateStr }}
                        </div>
                        <table class="tiktok-table" style="width: 100%; border-collapse: collapse; font-size: 10.5px; margin-bottom: 6px;">
                            <thead>
                                <tr>
                                    <th style="width: 45%; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 4px 2px; text-align: left; font-weight: bold;">Product Name</th>
                                    <th style="width: 15%; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 4px 2px; text-align: left; font-weight: bold;">SKU</th>
                                    <th style="width: 30%; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 4px 2px; text-align: left; font-weight: bold;">Seller SKU</th>
                                    <th style="width: 10%; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 4px 2px; text-align: center; font-weight: bold;">Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($order->items as $item)
                                    <tr>
                                        <td style="padding: 4px 2px; border-bottom: 1px dashed #eee; vertical-align: top;">{{ $item->product_name }}</td>
                                        <td style="padding: 4px 2px; border-bottom: 1px dashed #eee; vertical-align: top;">{{ $item->masterProduct->ukuran ?? ($sizeSummaryStr ?: 'Panjang, XL') }}</td>
                                        <td style="padding: 4px 2px; border-bottom: 1px dashed #eee; vertical-align: top; font-family:monospace;">{{ $item->sku ?? ($item->masterProduct->sku ?? 'BB-BR-ABU-LPJ-XL') }}</td>
                                        <td style="padding: 4px 2px; border-bottom: 1px dashed #eee; vertical-align: top; text-align:center; font-weight:bold;">{{ $item->quantity }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div style="font-size: 10px; font-weight: bold; margin: 4px 0;">
                            Order ID: {{ $order->order_marketplace_id }}
                        </div>
                        <div style="font-size: 10.5px; font-weight: bold; margin: 6px 0; padding: 6px 8px; border: 1px solid #000; background: #fff; word-break: break-word;">
                            Customer Message : {{ $order->buyer_message }}
                        </div>

                        <div class="tiktok-footer-logos" style="border-top: 1.5px solid #000; padding-top: 6px; display: flex; justify-content: space-between; align-items: center;">
                            <div class="tiktok-logo-brand" style="display: flex; align-items: center; gap: 8px; font-size: 16px; font-weight: 900;">
                                <span style="color:#03ac0e;"><i class="fas fa-shopping-bag me-1"></i>tokopedia</span>
                                <span>|</span>
                                <span><i class="fab fa-tiktok me-1"></i>Shop</span>
                            </div>
                            <div style="font-size:10px; font-weight:bold;">
                                Order ID: {{ $order->order_marketplace_id }}
                            </div>
                        </div>
                    </div>
                @endif
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
