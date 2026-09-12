<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Label Stiker Kemasan - SPK #{{ $spk->no_spk }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&family=JetBrains+Mono:wght@700;800&display=swap" rel="stylesheet">
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
            padding: 10px 12px;
            page-break-inside: avoid;
            break-inside: avoid;
            display: flex;
            flex-direction: column;
            gap: 6px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
        }

        .sticker-header {
            border-bottom: 1px solid #e2e8f0;
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

        .product-info-row {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .product-name {
            font-size: 11px;
            font-weight: 800;
            line-height: 1.25;
            color: #0f172a;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .sku-tag {
            font-family: 'JetBrains Mono', monospace;
            font-size: 9.5px;
            color: #2563eb;
            font-weight: 700;
            word-break: break-all;
        }

        .barcode-container {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2px 0;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
        }

        .barcode-svg {
            max-width: 100%;
            height: auto;
            display: block;
        }

        .sticker-footer {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            margin-top: auto;
            padding-top: 2px;
        }

        .size-badge {
            background: #0f172a;
            color: #ffffff;
            padding: 4px 10px;
            border-radius: 6px;
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-width: 60px;
        }

        .size-label {
            font-size: 7.5px;
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
            line-height: 1.1;
            font-family: 'Inter', system-ui, sans-serif;
        }

        .item-count-badge {
            font-size: 9px;
            color: #475569;
            font-weight: 700;
            background: #f1f5f9;
            padding: 3px 6px;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
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
                border: 1.5px solid #000;
                box-shadow: none;
                border-radius: 6px;
                padding: 8px;
            }
        }
    </style>
</head>
<body>

    <!-- Top Non-Print Control Toolbar -->
    <div class="no-print-bar">
        <div>
            <h3 style="font-size: 16px; font-weight: 800; color: #0f172a; margin-bottom: 2px;">
                🏷️ Label Stiker Kemasan (1D Barcode) — SPK #{{ $spk->no_spk }}
            </h3>
            <p style="font-size: 12px; color: #64748b; margin: 0;">
                Cetak label stiker barcode 1D (Code 128) untuk ditempelkan pada kemasan pakaian sebelum di-scan.
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
                $barcodeValue = 'SPK-' . $spk->id . '-ITEM-' . $item->id;
                $repeatQty = max(1, (int) $item->quantity);
            @endphp

            @for($i = 1; $i <= $repeatQty; $i++)
                <div class="sticker-card">
                    <div class="sticker-header">
                        <span class="spk-num">SPK #{{ $spk->no_spk }}</span>
                        <span class="client-name">{{ \Illuminate\Support\Str::limit($spk->pemesan ?: 'GUDANG', 16) }}</span>
                    </div>

                    <div class="product-info-row">
                        <div class="product-name" title="{{ $item->nama_produk }}">{{ $item->nama_produk }}</div>
                        <div class="sku-tag">SKU: {{ $skuDisplay }}</div>
                    </div>

                    <div class="barcode-container">
                        <svg class="barcode-svg" id="barcode-{{ $item->id }}-{{ $i }}"></svg>
                    </div>

                    <div class="sticker-footer">
                        <div class="size-badge">
                            <span class="size-label">SIZE</span>
                            <span class="size-value">{{ $item->ukuran ?: 'ALL' }}</span>
                        </div>
                        <span class="item-count-badge">#{{ $i }}/{{ $repeatQty }} Pcs</span>
                    </div>
                </div>
            @endfor
        @endforeach
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            @foreach($spk->items as $item)
                @php
                    $barcodeValue = 'SPK-' . $spk->id . '-ITEM-' . $item->id;
                    $repeatQty = max(1, (int) $item->quantity);
                @endphp
                @for($i = 1; $i <= $repeatQty; $i++)
                    try {
                        JsBarcode("#barcode-{{ $item->id }}-{{ $i }}", "{{ $barcodeValue }}", {
                            format: "CODE128",
                            width: 1.5,
                            height: 40,
                            displayValue: true,
                            fontSize: 10,
                            fontOptions: "bold",
                            font: "JetBrains Mono",
                            margin: 2
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
