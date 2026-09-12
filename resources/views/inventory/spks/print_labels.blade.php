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
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 12px;
            max-width: 900px;
            margin: 0 auto;
        }

        /* Individual Sticker Card (Compact Packaging Label - QR ONLY) */
        .sticker-card {
            background: #ffffff;
            border: 2px solid #000000;
            border-radius: 8px;
            padding: 12px 14px;
            page-break-inside: avoid;
            break-inside: avoid;
            display: flex;
            flex-direction: column;
            gap: 10px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
        }

        .sticker-header {
            border-bottom: 1px dashed #94a3b8;
            padding-bottom: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .spk-num {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 800;
            font-size: 13px;
            color: #000000;
        }

        .client-name {
            font-size: 10px;
            color: #475569;
            font-weight: 800;
            text-transform: uppercase;
        }

        .sticker-body {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .qr-wrapper {
            width: 105px;
            height: 105px;
            flex-shrink: 0;
            background: #ffffff;
            padding: 3px;
            border: 1.5px solid #000000;
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
            height: 105px;
        }

        .product-name {
            font-size: 12px;
            font-weight: 800;
            line-height: 1.25;
            color: #000000;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .sku-tag {
            font-family: 'JetBrains Mono', monospace;
            font-size: 10px;
            color: #1d4ed8;
            font-weight: 800;
            word-break: break-all;
            margin-top: 3px;
        }

        .size-container {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            margin-top: auto;
        }

        .size-badge {
            background: #000000;
            color: #ffffff;
            padding: 4px 10px;
            border-radius: 6px;
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-width: 56px;
        }

        .size-label {
            font-size: 7.5px;
            font-weight: 800;
            color: #cbd5e1;
            letter-spacing: 0.5px;
            line-height: 1;
            text-transform: uppercase;
        }

        .size-value {
            font-size: 21px;
            font-weight: 900;
            color: #ffffff;
            line-height: 1;
            font-family: 'Inter', system-ui, sans-serif;
        }

        .item-count-badge {
            font-size: 9.5px;
            color: #0f172a;
            font-weight: 800;
            background: #f1f5f9;
            padding: 3px 7px;
            border-radius: 4px;
            border: 1px solid #94a3b8;
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
                gap: 8px;
                max-width: 100%;
                margin: 0;
            }
            .sticker-card {
                border: 2px solid #000000;
                box-shadow: none;
                border-radius: 6px;
                padding: 10px;
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
                Label khusus QR Code HD Canvas untuk pemindaian langsung penerimaan hasil produksi.
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
                        // 2D QR Code Canvas (QRious HD)
                        new QRious({
                            element: document.getElementById("qr-canvas-{{ $item->id }}-{{ $i }}"),
                            value: @json($barcodeVal),
                            size: 160,
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
