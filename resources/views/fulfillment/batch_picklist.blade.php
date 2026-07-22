<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pick List & Rekap Pesanan - {{ now()->format('d/m/Y') }}</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #1e293b;
            margin: 0;
            padding: 15px;
            font-size: 11px;
            line-height: 1.3;
            background-color: #f8fafc;
        }
        .main-wrapper {
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px dashed #cbd5e1;
            padding-bottom: 12px;
            margin-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0.5px;
        }
        .header p {
            margin: 3px 0 0 0;
            color: #64748b;
            font-size: 10px;
        }
        .stats-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        .stat-badge {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 600;
        }
        .stat-badge.ready {
            background: #dcfce7;
            color: #15803d;
            border-color: #86efac;
        }
        .stat-badge.po {
            background: #f3e8ff;
            color: #7e22ce;
            border-color: #d8b4fe;
        }
        
        /* 2 Column Layout Grid */
        .grid-container {
            display: flex;
            gap: 18px;
            align-items: flex-start;
        }
        .col-left {
            flex: 1.1;
            min-width: 0;
        }
        .col-right {
            flex: 0.9;
            min-width: 0;
        }
        .section-title {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: #0f172a;
            color: #ffffff;
            padding: 6px 10px;
            border-radius: 4px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        /* Order Cards Layout */
        .order-card {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 8px 10px;
            margin-bottom: 8px;
            background: #ffffff;
            page-break-inside: avoid;
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 4px;
            margin-bottom: 5px;
        }
        .order-inv {
            font-family: monospace;
            font-weight: bold;
            font-size: 11px;
            color: #2563eb;
        }
        .order-store {
            font-size: 9px;
            background: #f1f5f9;
            color: #475569;
            padding: 1px 5px;
            border-radius: 3px;
            border: 1px solid #e2e8f0;
        }
        .order-meta {
            font-size: 9px;
            color: #64748b;
            margin-bottom: 5px;
        }
        .order-items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }
        .order-items-table td {
            padding: 3px 0;
            border-bottom: 1px dashed #f1f5f9;
        }
        .order-items-table td:last-child {
            text-align: right;
            font-weight: bold;
            color: #0f172a;
        }

        /* Rekap Table Layout */
        .rekap-table {
            width: 100%;
            border-collapse: collapse;
            background: #ffffff;
        }
        .rekap-table th {
            background: #e2e8f0;
            border: 1px solid #94a3b8;
            padding: 6px 8px;
            text-align: left;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .rekap-table td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            vertical-align: middle;
        }
        .font-mono {
            font-family: monospace;
        }
        .sku-code {
            font-weight: 700;
            color: #0f172a;
            font-size: 11px;
        }
        .product-name {
            font-size: 10px;
            color: #334155;
            margin-top: 1px;
        }
        .qty-badge {
            font-size: 14px;
            font-weight: 900;
            color: #1e3a8a;
        }
        .badge-ready {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            display: inline-block;
        }
        .badge-po {
            background: #f3e8ff;
            color: #6b21a8;
            border: 1px solid #d8b4fe;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            display: inline-block;
        }

        /* Footer Signatures */
        .signature-grid {
            margin-top: 25px;
            display: flex;
            justify-content: space-between;
            border-top: 1px solid #cbd5e1;
            padding-top: 15px;
        }
        .sig-box {
            width: 140px;
            text-align: center;
            font-size: 10px;
        }
        .sig-space {
            height: 40px;
        }
        .sig-line {
            border-top: 1px solid #333;
            padding-top: 3px;
            font-weight: bold;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: #ffffff;
                padding: 0;
            }
            .main-wrapper {
                padding: 0;
                box-shadow: none;
            }
            @page {
                size: A4 portrait;
                margin: 10mm;
            }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 15px; text-align: right; display: flex; justify-content: flex-end; gap: 10px;">
        <a href="{{ route('fulfillment.interactive_picklist', request()->query()) }}" style="padding: 8px 18px; background: #16a34a; color: white; border: none; border-radius: 5px; text-decoration: none; font-weight: bold; font-size: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.15); display: inline-flex; align-items: center; gap: 5px;">
            📱 Buka Mode Scan / Klik Interaktif
        </a>
        <button onclick="window.print()" style="padding: 8px 18px; background: #2563eb; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; font-size: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.15);">
            🖨️ Cetak Lembar Pick List
        </button>
    </div>

    <div class="main-wrapper">
        <!-- Header -->
        <div class="header">
            <div>
                <h1>PICK LIST &amp; PEMENUHAN PESANAN</h1>
                <p>ASPARTECH ERP Marketplace — Gudang &amp; Operations</p>
            </div>
            <div style="text-align: right;">
                <div style="font-weight: bold; font-size: 11px;">Tanggal Cetak:</div>
                <div style="color: #475569; font-size: 10px;">{{ now()->format('d M Y H:i') }} WIB</div>
            </div>
        </div>

        <!-- Stats Bar -->
        <div class="stats-bar">
            <div class="stat-badge">
                Total Order: <strong>{{ count($orders) }} Pesanan</strong>
            </div>
            <div class="stat-badge ready">
                Ready Stock: <strong>{{ $readyItemCount ?? 0 }} SKU</strong>
            </div>
            <div class="stat-badge po">
                Pre-Order (PO/SPK): <strong>{{ $poItemCount ?? 0 }} SKU</strong>
            </div>
        </div>

        <!-- 2 Column Layout Container -->
        <div class="grid-container">
            
            <!-- LEFT COLUMN: Rincian Per Order -->
            <div class="col-left">
                <div class="section-title">
                    <span>📦 RINCIAN PESANAN</span>
                    <span>({{ count($orders) }} ORDER)</span>
                </div>

                @foreach($orders as $order)
                    <div class="order-card">
                        <div class="order-header">
                            <span class="order-inv">{{ $order->invoice_number ?? $order->order_marketplace_id }}</span>
                            <span class="order-store">{{ $order->store->store_name }} ({{ strtoupper($order->store->channel->code) }})</span>
                        </div>
                        <div class="order-meta">
                            Pembeli: <strong>{{ $order->buyer_name ?: 'Pelanggan' }}</strong> | Kurir: <strong>{{ $order->courier ?: '—' }}</strong>
                        </div>
                        <table class="order-items-table">
                            @foreach($order->items as $it)
                                <tr>
                                    <td style="color: #334155;">
                                        {{ $it->product_name }}
                                        @if($it->sku)
                                            <span style="font-family: monospace; font-size: 8.5px; color: #64748b;">({{ $it->sku }})</span>
                                        @endif
                                    </td>
                                    <td>{{ $it->quantity }}x</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                @endforeach
            </div>

            <!-- RIGHT COLUMN: Rekap Total Barang (Summary Picking List) -->
            <div class="col-right">
                <div class="section-title">
                    <span>📋 REKAP AMBIL BARANG (SUMMARY)</span>
                    <span>({{ count($aggregated) }} SKU)</span>
                </div>

                <table class="rekap-table">
                    <thead>
                        <tr>
                            <th style="width: 25px;" class="text-center">No</th>
                            <th>SKU &amp; NAMA PRODUK</th>
                            <th style="width: 65px;" class="text-center">TIPE</th>
                            <th style="width: 45px;" class="text-center">QTY</th>
                            <th style="width: 35px;" class="text-center">CEK</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $no = 1; @endphp
                        @foreach($aggregated as $sku => $item)
                            <tr>
                                <td class="text-center" style="font-size: 10px; color: #64748b;">{{ $no++ }}</td>
                                <td>
                                    <div class="sku-code font-mono">{{ $item['sku'] }}</div>
                                    <div class="product-name">{{ $item['name'] }}</div>
                                </td>
                                <td class="text-center">
                                    @if($item['is_po'])
                                        <span class="badge-po" title="Barang Pre-Order">PO/SPK</span>
                                    @else
                                        <span class="badge-ready" title="Barang Ready Stock">READY</span>
                                    @endif
                                </td>
                                <td class="text-center qty-badge">{{ $item['qty'] }}</td>
                                <td class="text-center" style="font-size: 14px; color: #cbd5e1;">[ &nbsp; ]</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <!-- Signatures -->
                <div class="signature-grid">
                    <div class="sig-box">
                        <div>Disiapkan Oleh,</div>
                        <div class="sig-space"></div>
                        <div class="sig-line">Staf Gudang (Picker)</div>
                    </div>
                    <div class="sig-box">
                        <div>Diperiksa Oleh,</div>
                        <div class="sig-space"></div>
                        <div class="sig-line">Kepala Gudang</div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        if (window.location.search.includes('print=true')) {
            window.print();
        }
    </script>
</body>
</html>
