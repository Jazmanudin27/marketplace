<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice — {{ $offlineSale->sale_number }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&family=Outfit:wght@600;700;800;900&family=Courier+Prime:wght@400;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #eef2f6;
            color: #111827;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* ─── ACTION BAR (NO PRINT) ─── */
        .no-print-toolbar {
            position: sticky;
            top: 0;
            z-index: 999;
            background: #1e293b;
            color: #fff;
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .toolbar-title {
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .toolbar-badge {
            background: #0284c7;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 999px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .toolbar-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .btn-action {
            padding: 8px 18px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-print {
            background: #23829d;
            color: white;
        }
        .btn-print:hover {
            background: #1b687e;
        }

        .btn-switch {
            background: #334155;
            color: #cbd5e1;
            border: 1px solid #475569;
        }
        .btn-switch:hover {
            background: #475569;
            color: white;
        }

        .btn-close {
            background: #ef4444;
            color: white;
        }
        .btn-close:hover {
            background: #dc2626;
        }

        /* ─── INVOICE A4 WRAPPER ─── */
        .invoice-page {
            width: 210mm;
            min-height: 297mm;
            padding: 16mm 18mm;
            margin: 20px auto;
            background: #ffffff;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            position: relative;
            display: flex;
            flex-direction: column;
        }

        /* ─── TOP HEADER ─── */
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 24px;
        }

        /* LEFT: BRAND & ADDRESS & PAYMENT */
        .header-left {
            width: 54%;
        }

        .brand-block {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        .brand-logo-svg {
            width: 54px;
            height: 54px;
            flex-shrink: 0;
        }

        .brand-name-wrap {
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 22px;
            line-height: 1.05;
            color: #23829d;
            letter-spacing: 0.8px;
        }

        .brand-name-wrap .line-1 {
            display: block;
        }
        .brand-name-wrap .line-2 {
            display: block;
        }

        .company-address {
            font-size: 11px;
            line-height: 1.45;
            color: #222;
            margin-bottom: 16px;
        }

        .company-address .addr-italic {
            font-style: italic;
            font-size: 11.5px;
        }

        .company-address .addr-caps {
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .payment-details {
            font-size: 11px;
            line-height: 1.4;
            color: #111;
        }

        .payment-title {
            font-weight: 700;
            margin-bottom: 2px;
            color: #111;
        }

        .bank-item {
            font-size: 10.5px;
            margin-bottom: 2px;
        }
        .bank-item strong {
            font-weight: 700;
        }

        /* RIGHT: TEAL BAR, INVOICE TITLE, META */
        .header-right {
            width: 44%;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        .teal-top-bar {
            width: 100%;
            max-width: 275px;
            height: 22px;
            background-color: #2b819b;
            margin-bottom: 6px;
        }

        .invoice-title-text {
            width: 100%;
            max-width: 275px;
            text-align: center;
            font-size: 13px;
            font-weight: 800;
            color: #2b819b;
            letter-spacing: 4.5px;
            text-transform: uppercase;
            margin-bottom: 16px;
        }

        .meta-table {
            width: 100%;
            max-width: 275px;
            border-collapse: collapse;
            font-size: 12px;
        }

        .meta-table td {
            padding: 5px 0;
            vertical-align: middle;
        }

        .meta-table .meta-label {
            width: 95px;
            color: #111;
            font-weight: 500;
        }

        .meta-table .meta-value {
            text-align: right;
            color: #111;
        }

        .invoice-no-pill {
            background-color: #e5e7eb;
            color: #111827;
            font-weight: 700;
            padding: 3px 18px;
            border-radius: 999px;
            display: inline-block;
            font-size: 11.5px;
            letter-spacing: 0.5px;
        }

        .buyer-name-text {
            font-weight: 800;
            text-transform: uppercase;
            color: #111;
            font-size: 12px;
            letter-spacing: 0.3px;
        }

        /* ─── ITEMS TABLE ─── */
        .items-section {
            margin-top: 10px;
            flex-grow: 1;
        }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
        }

        .invoice-table thead th {
            border-top: 1.5px solid #222222;
            border-bottom: 1.5px solid #222222;
            padding: 6px 4px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #111;
        }

        .invoice-table tbody tr td {
            border-bottom: 1px solid #777777;
            padding: 5px 4px;
            font-size: 11.5px;
            color: #111;
        }

        .invoice-table .col-desc {
            text-align: left;
            padding-left: 2px;
        }

        .invoice-table .col-qty {
            text-align: center;
            width: 55px;
        }

        .invoice-table .col-unit {
            text-align: right;
            width: 120px;
            white-space: nowrap;
        }

        .invoice-table .col-total {
            text-align: right;
            width: 135px;
            white-space: nowrap;
            padding-right: 2px;
        }

        /* Empty lined rows for classic invoice structure */
        .empty-line-row td {
            height: 21px;
            border-bottom: 1px solid #777777 !important;
            padding: 0 !important;
        }

        /* ─── SUMMARY FOOTER ─── */
        .invoice-footer {
            margin-top: 12px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding-top: 6px;
        }

        .footer-left {
            width: 48%;
            font-size: 11px;
        }

        .signature-dash {
            font-size: 20px;
            color: #333;
            margin-bottom: 2px;
            line-height: 1;
        }

        .signature-label {
            font-size: 11px;
            color: #666;
        }

        .dropship-info {
            margin-top: 12px;
            padding: 6px 10px;
            background: #f8fafc;
            border-left: 3px solid #23829d;
            font-size: 10.5px;
            color: #333;
        }

        .order-notes {
            margin-top: 8px;
            font-size: 10px;
            color: #555;
            font-style: italic;
        }

        .footer-right {
            width: 46%;
            max-width: 320px;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        .summary-table td {
            padding: 3px 0;
            vertical-align: middle;
        }

        .summary-table .summary-label {
            text-align: left;
            color: #4b5563;
            font-weight: 500;
        }

        .summary-table .summary-val {
            text-align: right;
            font-weight: 700;
            color: #111;
            white-space: nowrap;
        }

        .summary-table .discount-label {
            color: #c0262d;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .summary-table .discount-val {
            color: #c0262d;
            font-weight: 700;
        }

        .summary-divider {
            border-bottom: 1.5px solid #222222;
            height: 4px;
        }

        .grand-total-wrap {
            text-align: right;
            padding-top: 6px;
        }

        .grand-total-amount {
            font-size: 20px;
            font-weight: 800;
            color: #1e7b95;
            letter-spacing: 0.3px;
        }

        /* ─── THERMAL POS VIEW ─── */
        .thermal-wrapper {
            display: none;
            width: 80mm;
            margin: 20px auto;
            background: white;
            padding: 15px 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            font-family: 'Courier Prime', Courier, monospace;
            font-size: 12px;
            color: #000;
        }

        /* ─── MEDIA PRINT ─── */
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .no-print-toolbar {
                display: none !important;
            }

            @page {
                size: A4 portrait;
                margin: 12mm 15mm;
            }

            .invoice-page {
                width: 100% !important;
                min-height: auto !important;
                padding: 0 !important;
                margin: 0 !important;
                box-shadow: none !important;
            }

            /* If thermal mode active on print */
            body.is-thermal @page {
                size: 80mm auto;
                margin: 2mm;
            }
            body.is-thermal .invoice-page {
                display: none !important;
            }
            body.is-thermal .thermal-wrapper {
                display: block !important;
                width: 100% !important;
                padding: 0 !important;
                box-shadow: none !important;
            }
        }
    </style>
</head>

<body class="{{ ($format ?? 'invoice') === 'thermal' ? 'is-thermal' : '' }}">

    {{-- TOP ACTION TOOLBAR (HIDDEN ON PRINT) --}}
    <div class="no-print-toolbar">
        <div class="toolbar-title">
            <span>📄 Cetak Penjualan Offline</span>
            <span class="toolbar-badge">{{ $offlineSale->sale_number }}</span>
        </div>
        <div class="toolbar-actions">
            @if (($format ?? 'invoice') === 'thermal')
                <a href="{{ route('offline_sales.print', ['offlineSale' => $offlineSale->id, 'format' => 'invoice']) }}" class="btn-action btn-switch">
                    📄 Beralih ke Invoice A4
                </a>
            @else
                <a href="{{ route('offline_sales.print', ['offlineSale' => $offlineSale->id, 'format' => 'thermal']) }}" class="btn-action btn-switch">
                    🧾 Format Struk Kasir (POS)
                </a>
            @endif

            <button class="btn-action btn-print" onclick="window.print()">
                🖨️ Cetak Sekarang
            </button>
            <button class="btn-action btn-close" onclick="window.close()">
                ✕ Tutup
            </button>
        </div>
    </div>

    @php
        // Bulan Indonesia
        $indonesianMonths = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $dateObj = $offlineSale->sold_at ?? now();
        $monthNum = (int) $dateObj->format('n');
        $formattedDate = $dateObj->format('j') . ' ' . ($indonesianMonths[$monthNum] ?? $dateObj->format('F')) . ' ' . $dateObj->format('Y');

        // Total calculation
        $subtotal = (float) $offlineSale->total_amount;
        $paid = (float) $offlineSale->paid_amount;
        $discount = (float) $offlineSale->discount_amount;
        $grandTotal = (float) $offlineSale->grand_total;

        // In sample image: Subtotal 11.647.000, DP 1.000.000 => Final: 10.647.000 (Remaining balance)
        if ($paid > 0 && $paid < $grandTotal) {
            $displayFinalTotal = max(0, $grandTotal - $paid);
        } else {
            $displayFinalTotal = $grandTotal;
        }

        // Fill empty lined rows (total 10 rows standard)
        $itemsCount = $offlineSale->items->count();
        $emptyRowsNeeded = max(3, 11 - $itemsCount);
    @endphp

    @if (($format ?? 'invoice') !== 'thermal')
    {{-- ========================================================================= --}}
    {{-- A4 INVOICE LAYOUT (EXACT REPLICA OF USER IMAGE)                          --}}
    {{-- ========================================================================= --}}
    <div class="invoice-page">

        {{-- HEADER --}}
        <div class="invoice-header">
            {{-- LEFT COLUMN --}}
            <div class="header-left">
                <div class="brand-block">
                    {{-- Needle & Thread Monogram Logo SVG in exact teal color --}}
                    <svg class="brand-logo-svg" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <!-- Needle -->
                        <line x1="48" y1="10" x2="48" y2="92" stroke="#23829d" stroke-width="2.6" stroke-linecap="round"/>
                        <rect x="46.7" y="16" width="2.6" height="12" rx="1.3" fill="#ffffff" stroke="#23829d" stroke-width="1.8"/>
                        <!-- Thread forming R loop on the left -->
                        <path d="M 48 25 C 22 25, 20 54, 48 54" stroke="#23829d" stroke-width="3" fill="none" stroke-linecap="round"/>
                        <!-- Lower curve loop S style -->
                        <path d="M 48 54 C 28 54, 28 82, 54 82 C 64 82, 68 76, 68 68" stroke="#23829d" stroke-width="3" fill="none" stroke-linecap="round"/>
                    </svg>

                    <div class="brand-name-wrap">
                        <span class="line-1">RUANG</span>
                        <span class="line-2">SERAGAM</span>
                    </div>
                </div>

                <div class="company-address">
                    <div class="addr-italic">Kp Ciherang Kec Cibeureum</div>
                    <div class="addr-caps">KP CIHERANG KEC CIBEUREUM</div>
                    <div class="addr-caps">KOTA TASIKMALAYA JAWA BARAT</div>
                </div>

                <div class="payment-details">
                    <div class="payment-title">Payment Details:</div>
                    @if(isset($bankAccounts) && $bankAccounts->isNotEmpty())
                        @foreach($bankAccounts as $bank)
                            <div class="bank-item">
                                <strong>{{ $bank->bank_name }} :</strong> {{ $bank->account_number }} a. n {{ $bank->account_name }}
                            </div>
                        @endforeach
                    @else
                        <div class="bank-item"><strong>Bank BCA</strong></div>
                        <div class="bank-item"><strong>BCA :</strong> 3210740332 a. n Yuda Yudistira</div>
                        <div class="bank-item"><strong>BRI :</strong> 4444 01011211 509 a. n Yuda Yudistira</div>
                    @endif
                </div>
            </div>

            {{-- RIGHT COLUMN --}}
            <div class="header-right">
                <div class="teal-top-bar"></div>
                <div class="invoice-title-text">I N V O I C E</div>

                <table class="meta-table">
                    <tr>
                        <td class="meta-label">Invoice No</td>
                        <td class="meta-value">
                            <span class="invoice-no-pill">{{ $offlineSale->sale_number }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="meta-label">Invoice Date</td>
                        <td class="meta-value" style="font-weight: 500;">{{ $formattedDate }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">To</td>
                        <td class="meta-value">
                            <span class="buyer-name-text">{{ strtoupper($offlineSale->buyer_name ?: ($offlineSale->customer->name ?? '-')) }}</span>
                            @if($offlineSale->institution_name)
                                <div style="font-size: 10.5px; color: #555; font-weight: 600;">{{ $offlineSale->institution_name }}</div>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- ITEMS TABLE --}}
        <div class="items-section">
            <table class="invoice-table">
                <thead>
                    <tr>
                        <th class="col-desc">DESCRIPTION</th>
                        <th class="col-qty">Qty</th>
                        <th class="col-unit">UNIT PRICE</th>
                        <th class="col-total">TOTAL PRICE</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($offlineSale->items as $item)
                        <tr>
                            <td class="col-desc">
                                {{ $item->product_name ?? ($item->masterProduct->name ?? '-') }}
                            </td>
                            <td class="col-qty">{{ $item->quantity }}</td>
                            <td class="col-unit">Rp{{ number_format($item->unit_price, 0, ',', '.') }}</td>
                            <td class="col-total">Rp{{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach

                    {{-- Classic lined paper rows --}}
                    @for ($i = 0; $i < $emptyRowsNeeded; $i++)
                        <tr class="empty-line-row">
                            <td colspan="4">&nbsp;</td>
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>

        {{-- SUMMARY FOOTER --}}
        <div class="invoice-footer">
            {{-- LEFT FOOTER --}}
            <div class="footer-left">
                <div class="signature-dash">—</div>
                <div class="signature-label">Tanda Terima / Hormat Kami</div>

                @if ($offlineSale->is_dropship)
                    <div class="dropship-info">
                        <strong>Pengirim (Dropshipper):</strong> {{ $offlineSale->dropshipper_name }}
                        @if($offlineSale->dropshipper_phone) ({{ $offlineSale->dropshipper_phone }}) @endif
                        @if($offlineSale->resi_number) <br><strong>No. Resi:</strong> {{ $offlineSale->resi_number }} @endif
                    </div>
                @endif

                @if ($offlineSale->notes)
                    <div class="order-notes">
                        <strong>Catatan:</strong> {{ $offlineSale->notes }}
                    </div>
                @endif
            </div>

            {{-- RIGHT FOOTER --}}
            <div class="footer-right">
                <table class="summary-table">
                    <tr>
                        <td class="summary-label">Sub Total</td>
                        <td class="summary-val">Rp{{ number_format($offlineSale->total_amount, 0, ',', '.') }},00</td>
                    </tr>

                    @if ($offlineSale->paid_amount > 0)
                        <tr>
                            <td class="summary-label">DP</td>
                            <td class="summary-val">Rp{{ number_format($offlineSale->paid_amount, 0, ',', '.') }}</td>
                        </tr>
                    @endif

                    @if ($offlineSale->discount_amount > 0)
                        <tr>
                            <td class="summary-label discount-label">DISKON</td>
                            <td class="summary-val discount-val">- Rp{{ number_format($offlineSale->discount_amount, 0, ',', '.') }}</td>
                        </tr>
                    @else
                        <tr>
                            <td class="summary-label discount-label">DISKON</td>
                            <td class="summary-val" style="color: #999; font-weight: normal;">-</td>
                        </tr>
                    @endif

                    <tr>
                        <td colspan="2" class="summary-divider"></td>
                    </tr>
                </table>

                <div class="grand-total-wrap">
                    <div class="grand-total-amount">
                        Rp {{ number_format($displayFinalTotal, 0, ',', '.') }}
                    </div>
                    @if($paid >= $grandTotal && $grandTotal > 0)
                        <div style="font-size: 11px; font-weight: 700; color: #16a34a; text-transform: uppercase; margin-top: 2px;">
                            ✓ LUNAS
                        </div>
                    @elseif($paid > 0 && $paid < $grandTotal)
                        <div style="font-size: 10.5px; font-weight: 600; color: #d97706; margin-top: 2px;">
                            (Sisa Tagihan)
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>
    @else
    {{-- ========================================================================= --}}
    {{-- THERMAL POS RECEIPT FORMAT (BACKWARDS-COMPATIBLE)                         --}}
    {{-- ========================================================================= --}}
    <div class="thermal-wrapper" style="display: block;">
        <div style="text-align: center; font-weight: bold; font-size: 15px; margin-bottom: 2px;">
            {{ $tenant->name ?? 'RUANG SERAGAM' }}
        </div>
        <div style="text-align: center; font-size: 10px; color: #555; margin-bottom: 8px;">
            Kp Ciherang Kec Cibeureum, Kota Tasikmalaya
        </div>
        <div style="border-top: 1px dashed #777; margin: 8px 0;"></div>
        <div style="display: flex; justify-content: space-between; font-size: 11px;">
            <span>No:</span>
            <strong>{{ $offlineSale->sale_number }}</strong>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 11px;">
            <span>Tgl:</span>
            <span>{{ $offlineSale->sold_at?->format('d/m/Y H:i') }}</span>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 11px;">
            <span>Pembeli:</span>
            <span>{{ $offlineSale->buyer_name ?: ($offlineSale->customer->name ?? '-') }}</span>
        </div>
        <div style="border-top: 1px dashed #777; margin: 8px 0;"></div>

        <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
            @foreach ($offlineSale->items as $item)
                <tr>
                    <td colspan="2" style="padding-top: 4px;">{{ $item->product_name }}</td>
                </tr>
                <tr>
                    <td style="color: #555; font-size: 10px;">{{ $item->quantity }} x Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                    <td style="text-align: right; font-weight: bold;">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </table>

        <div style="border-top: 1px dashed #777; margin: 8px 0;"></div>
        <div style="display: flex; justify-content: space-between; font-size: 11px;">
            <span>Subtotal:</span>
            <span>Rp {{ number_format($offlineSale->total_amount, 0, ',', '.') }}</span>
        </div>
        @if ($offlineSale->discount_amount > 0)
            <div style="display: flex; justify-content: space-between; font-size: 11px; color: #c0262d;">
                <span>Diskon:</span>
                <span>- Rp {{ number_format($offlineSale->discount_amount, 0, ',', '.') }}</span>
            </div>
        @endif
        @if ($offlineSale->paid_amount > 0)
            <div style="display: flex; justify-content: space-between; font-size: 11px;">
                <span>DP / Dibayar:</span>
                <span>Rp {{ number_format($offlineSale->paid_amount, 0, ',', '.') }}</span>
            </div>
        @endif
        <div style="display: flex; justify-content: space-between; font-size: 13px; font-weight: bold; margin-top: 4px;">
            <span>TOTAL:</span>
            <span>Rp {{ number_format($displayFinalTotal, 0, ',', '.') }}</span>
        </div>
        <div style="border-top: 1px dashed #777; margin: 10px 0;"></div>
        <div style="text-align: center; font-size: 10px; color: #555; line-height: 1.4;">
            Terima kasih atas kerja samanya!<br>
            Ruang Seragam Tasikmalaya
        </div>
    </div>
    @endif

    <script>
        // Auto trigger print dialog after fonts are loaded
        window.addEventListener('load', function() {
            setTimeout(function() {
                window.print();
            }, 500);
        });
    </script>
</body>

</html>
