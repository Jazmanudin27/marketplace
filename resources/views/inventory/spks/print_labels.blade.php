<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Label Stiker Kemasan - SPK #{{ $spk->no_spk }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&family=JetBrains+Mono:wght@700;800&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: #f8fafc;
            color: #0f172a;
            padding: 12px;
        }

        /* Non-printable Control Bar */
        .no-print-bar {
            background: #ffffff;
            border-radius: 12px;
            padding: 12px 18px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06);
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto 16px auto;
        }

        .btn-print {
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 9px 18px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
        }
        .btn-print:hover {
            background: #1d4ed8;
        }

        /* Stickers Grid Container - 6 Columns Grid */
        .labels-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 8px;
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Micro Compact Sticker Card: Barcode (QR), SKU, Size */
        .sticker-card {
            background: #ffffff;
            border: 1.5px solid #000000;
            border-radius: 6px;
            padding: 8px 4px;
            page-break-inside: avoid;
            break-inside: avoid;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        .qr-wrapper {
            width: 72px;
            height: 72px;
            flex-shrink: 0;
            background: #ffffff;
            padding: 1px;
            border: 1px solid #000000;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .qr-wrapper canvas {
            width: 100% !important;
            height: 100% !important;
            display: block;
        }

        .sku-tag {
            font-family: 'JetBrains Mono', monospace;
            font-size: 8.5px;
            color: #000000;
            font-weight: 800;
            word-break: break-all;
            line-height: 1.15;
            max-width: 100%;
            padding: 0 2px;
        }

        .size-badge {
            background: #000000;
            color: #ffffff;
            padding: 2px 8px;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            min-width: 46px;
        }

        .size-label {
            font-size: 7px;
            font-weight: 800;
            color: #cbd5e1;
            letter-spacing: 0.4px;
            text-transform: uppercase;
        }

        .size-value {
            font-size: 13px;
            font-weight: 900;
            color: #ffffff;
            line-height: 1;
            font-family: 'Inter', system-ui, sans-serif;
        }

        /* Print Media Styling - 6 Columns */
        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .no-print-bar {
                display: none !important;
            }
            .labels-grid {
                display: grid;
                grid-template-columns: repeat(6, 1fr);
                gap: 4px;
                max-width: 100%;
                margin: 0;
            }
            .sticker-card {
                border: 1px solid #000000;
                box-shadow: none;
                border-radius: 4px;
                padding: 6px 3px;
            }
        }
    </style>
</head>
<body>

    <!-- Top Non-Print Control Toolbar -->
    <div class="no-print-bar">
        <div>
            <h3 style="font-size: 15px; font-weight: 800; color: #0f172a; margin-bottom: 2px;">
                🏷️ Label Stiker Kemasan (6 Per Baris) - SPK #{{ $spk->no_spk }}
            </h3>
            <p style="font-size: 11px; color: #64748b; margin: 0;">
                Tampilan micro-compact: 6 stiker per baris dengan QR Code, SKU kecil, dan Ukuran.
            </p>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <button onclick="window.print()" class="btn-print">
                🖨️ Cetak Semua Label (Print)
            </button>
        </div>
    </div>

    <!-- Sticker Labels Grid (6 Columns) -->
    <div class="labels-grid">
        @foreach($spk->items as $item)
            @php
                $skuDisplay = $item->sku ?: ($item->masterProduct->sku ?? ('ITEM-' . $item->id));
                $barcodeVal = $spk->no_spk . "|ITEM-" . $item->id;
                $repeatQty = max(1, (int) $item->quantity);
            @endphp

            @for($i = 1; $i <= $repeatQty; $i++)
                <div class="sticker-card">
                    <div class="qr-wrapper">
                        <canvas id="qr-canvas-{{ $item->id }}-{{ $i }}"></canvas>
                    </div>
                    
                    <div class="sku-tag" title="{{ $skuDisplay }}">{{ $skuDisplay }}</div>
                    
                    <div class="size-badge">
                        <span class="size-label">SIZE</span>
                        <span class="size-value">{{ $item->ukuran ?: 'ALL' }}</span>
                    </div>
                </div>
            @endfor
        @endforeach
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            @foreach($spk->items as $item)
                @php
                    $barcodeVal = $spk->no_spk . "|ITEM-" . $item->id;
                    $repeatQty = max(1, (int) $item->quantity);
                @endphp
                @for($i = 1; $i <= $repeatQty; $i++)
                    try {
                        new QRious({
                            element: document.getElementById("qr-canvas-{{ $item->id }}-{{ $i }}"),
                            value: @json($barcodeVal),
                            size: 140,
                            level: 'L'
                        });
                    } catch(e) {
                        console.error("QR Code generation error: ", e);
                    }
                @endfor
            @endforeach
        });
    </script>
</body>
</html>
