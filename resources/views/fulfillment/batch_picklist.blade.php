<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pick List Gabungan - {{ now()->format('d/m/Y') }}</title>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            color: #333;
            margin: 0;
            padding: 20px;
            font-size: 12px;
            line-height: 1.4;
        }
        .header {
            display: flex;
            justify-content: space-between;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            color: #111;
        }
        .header p {
            margin: 5px 0 0 0;
            color: #666;
        }
        .meta-info {
            margin-bottom: 20px;
            font-size: 11px;
            background: #f1f5f9;
            padding: 8px 12px;
            border-radius: 4px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .items-table th {
            background: #e2e8f0;
            border: 1px solid #94a3b8;
            padding: 8px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
        }
        .items-table td {
            border: 1px solid #cbd5e1;
            padding: 8px;
            vertical-align: top;
        }
        .text-center {
            text-align: center;
        }
        .font-mono {
            font-family: monospace;
        }
        .qty-badge {
            font-size: 16px;
            font-weight: 800;
            color: #1e3a8a;
        }
        .orders-list {
            font-size: 10px;
            color: #64748b;
            margin-top: 4px;
        }
        @media print {
            .no-print {
                display: none;
            }
            body {
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()" style="padding: 6px 15px; background: #2563eb; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
            Cetak Pick List
        </button>
    </div>

    <div class="header">
        <div>
            <h1>PICK LIST GABUNGAN GUDANG</h1>
            <p>ASPARTECH ERP Marketplace</p>
        </div>
        <div style="text-align: right;">
            <strong>Tanggal:</strong> {{ now()->format('d M Y H:i') }}
        </div>
    </div>

    <div class="meta-info">
        <strong>Total Order Diproses:</strong> {{ count($orders) }} pesanan. 
        <span style="margin-left: 15px; background: #dcfce7; color: #166534; padding: 2px 6px; border-radius: 3px; font-weight: bold;">Ready Stock: {{ $readyItemCount ?? 0 }} SKU</span>
        <span style="margin-left: 8px; background: #f3e8ff; color: #6b21a8; padding: 2px 6px; border-radius: 3px; font-weight: bold;">PO / SPK: {{ $poItemCount ?? 0 }} SKU</span>
        <br>
        <strong>Daftar Invoice:</strong> 
        @foreach($orders as $index => $order)
            {{ $order->invoice_number ?? $order->order_marketplace_id }}{{ $index < count($orders) - 1 ? ', ' : '' }}
        @endforeach
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 35px;" class="text-center">No</th>
                <th style="width: 140px;">SKU / KODE BARANG</th>
                <th>NAMA PRODUK</th>
                <th style="width: 120px;" class="text-center">TIPE BARANG</th>
                <th style="width: 90px;" class="text-center">QTY DIAMBIL</th>
                <th style="width: 50px;" class="text-center">CHECK</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 1; @endphp
            @foreach($aggregated as $sku => $item)
                <tr>
                    <td class="text-center">{{ $no++ }}</td>
                    <td class="font-mono" style="font-size: 12px; font-weight: bold;">{{ $item['sku'] }}</td>
                    <td>
                        <strong style="font-size: 13px;">{{ $item['name'] }}</strong>
                        <div class="orders-list">
                            Digunakan untuk order: {{ implode(', ', array_unique($item['orders'])) }}
                        </div>
                    </td>
                    <td class="text-center">
                        @if($item['is_po'])
                            <span style="background: #f3e8ff; color: #6b21a8; border: 1px solid #d8b4fe; padding: 3px 6px; border-radius: 4px; font-weight: bold; font-size: 10px; display: inline-block;">
                                ⏱️ PO / SPK
                            </span>
                            @if(!empty($item['spk_no']))
                                <div style="font-size: 9px; color: #7e22ce; margin-top: 2px; font-family: monospace;">#{{ $item['spk_no'] }}</div>
                            @endif
                        @else
                            <span style="background: #dcfce7; color: #166534; border: 1px solid #86efac; padding: 3px 6px; border-radius: 4px; font-weight: bold; font-size: 10px; display: inline-block;">
                                 READY STOCK
                            </span>
                        @endif
                    </td>
                    <td class="text-center qty-badge">{{ $item['qty'] }}</td>
                    <td class="text-center" style="font-size: 18px; color: #cbd5e1;">[ &nbsp; ]</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 50px; display: flex; justify-content: space-between;">
        <div style="width: 200px; text-align: center;">
            <p>Disiapkan Oleh,</p>
            <div style="margin-top: 50px; border-top: 1px solid #333; padding-top: 5px;">Staf Gudang (Picker)</div>
        </div>
        <div style="width: 200px; text-align: center;">
            <p>Diperiksa Oleh,</p>
            <div style="margin-top: 50px; border-top: 1px solid #333; padding-top: 5px;">Kepala Gudang</div>
        </div>
    </div>

    <script>
        if (window.location.search.includes('print=true')) {
            window.print();
        }
    </script>
</body>
</html>
