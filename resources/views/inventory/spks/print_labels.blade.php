<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Label Stiker Kemasan - SPK #{{ $spk->no_spk }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&family=JetBrains+Mono:wght@700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: #f1f5f9;
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
            gap: 16px;
            max-width: 960px;
            margin: 0 auto;
        }

        /* Individual Sticker Card (Optimized for packaging 70mm x 50mm or A4 sheet) */
        .sticker-card {
            background: #ffffff;
            border: 1.5px solid #cbd5e1;
            border-radius: 10px;
            padding: 12px;
            page-break-inside: avoid;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
        }

        .sticker-header {
            border-bottom: 1.5px solid #0f172a;
            padding-bottom: 4px;
            margin-bottom: 8px;
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
            color: #475569;
            font-weight: 600;
            text-transform: uppercase;
        }

        .sticker-body {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }

        .qr-wrapper {
            width: 76px;
            height: 76px;
            flex-shrink: 0;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .qr-wrapper img {
            width: 76px;
            height: 76px;
        }

        .product-info {
            flex-grow: 1;
            overflow: hidden;
        }

        .product-name {
            font-size: 12px;
            font-weight: 800;
            line-height: 1.25;
            color: #0f172a;
            margin-bottom: 4px;
        }

        .sku-tag {
            font-family: 'JetBrains Mono', monospace;
            font-size: 10px;
            color: #2563eb;
            font-weight: 700;
            word-break: break-all;
            display: block;
            margin-bottom: 4px;
        }

        .size-box {
            display: inline-block;
            background: #0f172a;
            color: #ffffff;
            font-size: 14px;
            font-weight: 900;
            padding: 2px 8px;
            border-radius: 4px;
            letter-spacing: 0.5px;
        }

        .barcode-footer {
            text-align: center;
            border-top: 1px dashed #cbd5e1;
            padding-top: 6px;
        }
        .barcode-footer svg {
            width: 100%;
            max-height: 38px;
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
                border: 1px solid #000;
                box-shadow: none;
                border-radius: 4px;
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
                🏷️ Label Stiker Kemasan SPK #{{ $spk->no_spk }}
            </h3>
            <p style="font-size: 12px; color: #64748b; margin: 0;">
                Cetak label stiker untuk ditempelkan pada kemasan pakaian sebelum di-scan saat penerimaan.
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
                $codeVal = $item->sku ?: ($item->masterProduct->sku ?? ('SPK-ITEM-' . $item->id));
                $repeatQty = max(1, (int) $item->quantity);
            @endphp

            @for($i = 1; $i <= $repeatQty; $i++)
                <div class="sticker-card">
                    <div class="sticker-header">
                        <span class="spk-num">{{ $spk->no_spk }}</span>
                        <span class="client-name">{{ \Illuminate\Support\Str::limit($spk->pemesan ?: 'GUDANG', 16) }}</span>
                    </div>

                    <div class="sticker-body">
                        <div class="qr-wrapper" id="qr-box-{{ $item->id }}-{{ $i }}"></div>
                        <div class="product-info">
                            <div class="product-name">{{ $item->nama_produk }}</div>
                            <span class="sku-tag">{{ $codeVal }}</span>
                            <div>
                                <span class="size-box">SIZE {{ $item->ukuran ?: 'ALL' }}</span>
                                <span style="font-size: 9px; color: #64748b; font-weight: 700; margin-left: 6px;">
                                    #{{ $i }}/{{ $repeatQty }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="barcode-footer">
                        <svg id="barcode-svg-{{ $item->id }}-{{ $i }}"></svg>
                    </div>
                </div>
            @endfor
        @endforeach
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            @foreach($spk->items as $item)
                @php
                    $codeVal = $item->sku ?: ($item->masterProduct->sku ?? ('SPK-ITEM-' . $item->id));
                    $repeatQty = max(1, (int) $item->quantity);
                @endphp
                @for($i = 1; $i <= $repeatQty; $i++)
                    try {
                        // Generate QR Code
                        new QRCode(document.getElementById("qr-box-{{ $item->id }}-{{ $i }}"), {
                            text: "{{ $codeVal }}",
                            width: 76,
                            height: 76,
                            correctLevel: QRCode.CorrectLevel.M
                        });

                        // Generate Barcode
                        JsBarcode("#barcode-svg-{{ $item->id }}-{{ $i }}", "{{ $codeVal }}", {
                            format: "CODE128",
                            height: 26,
                            fontSize: 10,
                            margin: 0,
                            displayValue: false
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
