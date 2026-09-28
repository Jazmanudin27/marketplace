<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Label Barcode Barang Titipan - #{{ $consignment->reference_number }}</title>
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
            padding: 14px 20px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06);
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto 16px auto;
            border: 1px solid #e2e8f0;
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
            transition: all 0.15s ease;
        }
        .btn-print:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        .btn-back {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 8px 14px;
            font-weight: 600;
            font-size: 12px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-back:hover {
            background: #e2e8f0;
            color: #1e293b;
        }

        /* Stickers Grid Container - 6 Columns Grid */
        .labels-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 8px;
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Micro Compact Sticker Card */
        .sticker-card {
            background: #ffffff;
            border: 1.5px solid #000000;
            border-radius: 6px;
            padding: 7px 4px;
            page-break-inside: avoid;
            break-inside: avoid;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 3px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            position: relative;
        }

        .badge-titipan {
            font-size: 7px;
            font-weight: 800;
            background: #000000;
            color: #ffffff;
            padding: 1px 5px;
            border-radius: 3px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 1px;
        }

        .ref-tag {
            font-family: 'JetBrains Mono', monospace;
            font-size: 8px;
            color: #000000;
            font-weight: 800;
            word-break: break-all;
            line-height: 1.15;
            max-width: 100%;
            padding: 0 2px;
        }

        .supplier-tag {
            font-size: 7.5px;
            color: #334155;
            font-weight: 600;
            max-width: 95%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.1;
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
            margin: 2px 0;
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

        .product-name-tag {
            font-size: 7px;
            color: #475569;
            font-weight: 600;
            max-width: 95%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.1;
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
                padding: 5px 3px;
            }
        }
    </style>
</head>
<body>

    <!-- Top Non-Print Control Toolbar -->
    <div class="no-print-bar">
        <div>
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 2px;">
                <span style="background: #e0f2fe; color: #0284c7; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 700;">
                    TITIPAN BARANG KONSINYASI
                </span>
                <h3 style="font-size: 15px; font-weight: 800; color: #0f172a; margin: 0;">
                    🏷️ Cetak Label Stiker Barcode Produk #{{ $consignment->reference_number }}
                </h3>
            </div>
            <p style="font-size: 11px; color: #64748b; margin: 0;">
                Supplier: <strong>{{ $consignment->supplier ? $consignment->supplier->name : 'Supplier' }}</strong> &bull;
                Total: <strong>{{ number_format($consignment->total_qty_received) }} label</strong>.
                Barcode ini dapat discan saat kemas pesanan untuk memotong stok dari titipan ini secara otomatis.
            </p>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <a href="{{ route('supplier_consignments.show', $consignment) }}" class="btn-back">
                &larr; Kembali ke Faktur
            </a>
            <button onclick="window.print()" class="btn-print">
                🖨️ Cetak Semua Label (Print)
            </button>
        </div>
    </div>

    <!-- Sticker Labels Grid (6 Columns) -->
    <div class="labels-grid">
        @foreach($consignment->items as $item)
            @php
                $product = $item->masterProduct;
                $skuDisplay = $product ? $product->sku : ('ITEM-' . $item->id);
                $productName = $product ? $product->name : 'Produk Titipan';
                // Format Barcode Titipan: KNS|{reference_number}|ITEM-{item_id}|{sku}
                $barcodeVal = "KNS|" . $consignment->reference_number . "|ITEM-" . $item->id . "|" . $skuDisplay;
                $repeatQty = max(1, (int) $item->qty_received);
            @endphp

            @for($i = 1; $i <= $repeatQty; $i++)
                <div class="sticker-card">
                    <span class="badge-titipan">TITIPAN</span>
                    <div class="ref-tag" title="{{ $consignment->reference_number }}">{{ $consignment->reference_number }}</div>
                    <div class="supplier-tag" title="{{ $consignment->supplier ? $consignment->supplier->name : '' }}">
                        {{ $consignment->supplier ? \Illuminate\Support\Str::limit($consignment->supplier->name, 18) : '-' }}
                    </div>

                    <div class="qr-wrapper">
                        <canvas id="qr-canvas-{{ $item->id }}-{{ $i }}"></canvas>
                    </div>

                    <div class="sku-tag" title="{{ $skuDisplay }}">{{ $skuDisplay }}</div>
                    <div class="product-name-tag" title="{{ $productName }}">
                        {{ \Illuminate\Support\Str::limit($productName, 18) }}
                    </div>
                </div>
            @endfor
        @endforeach
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            @foreach($consignment->items as $item)
                @php
                    $product = $item->masterProduct;
                    $skuDisplay = $product ? $product->sku : ('ITEM-' . $item->id);
                    $barcodeVal = "KNS|" . $consignment->reference_number . "|ITEM-" . $item->id . "|" . $skuDisplay;
                    $repeatQty = max(1, (int) $item->qty_received);
                @endphp
                @for($i = 1; $i <= $repeatQty; $i++)
                    try {
                        new QRious({
                            element: document.getElementById("qr-canvas-{{ $item->id }}-{{ $i }}"),
                            value: @json($barcodeVal),
                            size: 140,
                            level: 'M'
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
