<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Label Stiker Kemasan - SPK #{{ $spk->no_spk }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&family=JetBrains+Mono:wght@700;800&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

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
            padding: 20px;
        }

        /* Non-printable Control Bar */
        .no-print-bar {
            background: #ffffff;
            border-radius: 12px;
            padding: 14px 20px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06);
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 900px;
            margin: 0 auto 20px auto;
        }

        .btn-print {
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
        }
        .btn-print:hover {
            background: #1d4ed8;
        }

        /* Stickers Grid Container */
        .labels-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 12px;
            max-width: 900px;
            margin: 0 auto;
        }

        /* Individual Sticker Card (Compact Packaging Label) */
        .sticker-card {
            background: #ffffff;
            border: 1.5px solid #0f172a;
            border-radius: 8px;
            padding: 8px 10px;
            page-break-inside: avoid;
            break-inside: avoid;
            display: flex;
            flex-direction: column;
            gap: 6px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
        }

        .sticker-header {
            border-bottom: 1px dashed #cbd5e1;
            padding-bottom: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .spk-num {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 800;
            font-size: 11px;
            color: #0f172a;
        }

        .client-name {
            font-size: 9px;
            color: #64748b;
            font-weight: 700;
            text-transform: uppercase;
        }

        .sticker-body {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .qr-wrapper {
            width: 95px;
            height: 95px;
            flex-shrink: 0;
            background: #ffffff;
            padding: 2px;
            border: 1px solid #0f172a;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .qr-wrapper canvas {
            width: 100% !important;
            height: 100% !important;
            display: block;
        }

        .product-info {
            flex-grow: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 95px;
        }

        .product-name {
            font-size: 10.5px;
            font-weight: 800;
            line-height: 1.2;
            color: #0f172a;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .sku-tag {
            font-family: 'JetBrains Mono', monospace;
            font-size: 9px;
            color: #2563eb;
            font-weight: 700;
            word-break: break-all;
            margin-top: 2px;
        }

        .size-container {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            margin-top: auto;
        }

        .size-badge {
            background: #0f172a;
            color: #ffffff;
            padding: 3px 8px;
            border-radius: 5px;
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-width: 50px;
        }

        .size-label {
            font-size: 7px;
            font-weight: 800;
            color: #94a3b8;
            letter-spacing: 0.5px;
            line-height: 1;
            text-transform: uppercase;
        }

        .size-value {
            font-size: 18px;
            font-weight: 900;
            color: #ffffff;
            line-height: 1;
            font-family: 'Inter', system-ui, sans-serif;
        }

        .item-count-badge {
            font-size: 8.5px;
            color: #475569;
            font-weight: 700;
            background: #f1f5f9;
            padding: 2px 5px;
            border-radius: 4px;
            border: 1px solid #cbd5e1;
        }

        .barcode-1d-container {
            border-top: 1px dashed #cbd5e1;
            padding-top: 4px;
            text-align: center;
        }
        .barcode-1d-container svg {
            max-width: 100%;
            height: 28px;
            display: block;
            margin: 0 auto;
        }

        /* Print Media Styling */
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
                grid-template-columns: repeat(3, 1fr);
                gap: 6px;
                max-width: 100%;
                margin: 0;
            }
            .sticker-card {
                border: 1.5px solid #000;
                box-shadow: none;
                border-radius: 6px;
                padding: 6px;
            }
        }
    </style>
</head>
<body>

    <!-- Top Non-Print Control Toolbar -->
    <div class="no-print-bar">
        <div>
            <h3 style="font-size: 16px; font-weight: 800; color: #0f172a; margin-bottom: 2px;">
                🏷️ Label Stiker Kemasan SPK #{{ $spk->no_spk }}
            </h3>
            <p style="font-size: 12px; color: #64748b; margin: 0;">
                Label dilengkapi Dual Barcode (2D QR Code &amp; 1D Laser Barcode) untuk jaminan 100% terbaca semua mesin scanner.
            </p>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <button onclick="window.print()" class="btn-print">
                🖨️ Cetak Semua Label (Print)
            </button>
        </div>
    </div>

    <!-- Sticker Labels Grid -->
    <div class="labels-grid">
        @foreach($spk->items as $item)
            @php
                $skuDisplay = $item->sku ?: ($item->masterProduct->sku ?? ('ITEM-' . $item->id));
                $barcodeVal = $spk->no_spk . "|ITEM-" . $item->id;
                $repeatQty = max(1, (int) $item->quantity);
            @endphp

            @for($i = 1; $i <= $repeatQty; $i++)
                <div class="sticker-card">
                    <div class="sticker-header">
                        <span class="spk-num">{{ $spk->no_spk }}</span>
                        <span class="client-name">{{ \Illuminate\Support\Str::limit($spk->pemesan ?: 'GUDANG', 16) }}</span>
                    </div>

                    <div class="sticker-body">
                        <div class="qr-wrapper">
                            <canvas id="qr-canvas-{{ $item->id }}-{{ $i }}"></canvas>
                        </div>
                        <div class="product-info">
                            <div>
                                <div class="product-name" title="{{ $item->nama_produk }}">{{ $item->nama_produk }}</div>
                                <div class="sku-tag">{{ $skuDisplay }}</div>
                            </div>
                            
                            <div class="size-container">
                                <div class="size-badge">
                                    <span class="size-label">SIZE</span>
                                    <span class="size-value">{{ $item->ukuran ?: 'ALL' }}</span>
                                </div>
                                <span class="item-count-badge">#{{ $i }}/{{ $repeatQty }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="barcode-1d-container">
                        <svg id="barcode-1d-{{ $item->id }}-{{ $i }}"></svg>
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
                        // 1. QR Code 2D (QRious HD Canvas)
                        new QRious({
                            element: document.getElementById("qr-canvas-{{ $item->id }}-{{ $i }}"),
                            value: @json($barcodeVal),
                            size: 110,
                            level: 'L'
                        });

                        // 2. 1D Barcode CODE128 (JsBarcode - Sinar Laser Red Line compatible)
                        JsBarcode("#barcode-1d-{{ $item->id }}-{{ $i }}", @json($barcodeVal), {
                            format: "CODE128",
                            width: 1.4,
                            height: 32,
                            displayValue: false,
                            margin: 0
                        });
                    } catch(e) {
                        console.error("Barcode generation error: ", e);
                    }
                @endfor
            @endforeach
        });
    </script>
</body>
</html>
