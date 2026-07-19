<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order - {{ $purchaseOrder->po_number }}</title>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            color: #333;
            margin: 0;
            padding: 20px;
            font-size: 12px;
            line-height: 1.5;
        }
        .header {
            display: flex;
            justify-content: space-between;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .company-info h2 {
            margin: 0 0 5px 0;
            color: #1e3a8a;
        }
        .company-info p {
            margin: 0;
            color: #555;
        }
        .po-title {
            text-align: right;
        }
        .po-title h1 {
            margin: 0 0 5px 0;
            color: #3b82f6;
            font-size: 24px;
        }
        .po-title p {
            margin: 0;
            font-family: monospace;
            font-size: 14px;
            font-weight: bold;
        }
        .details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
            gap: 40px;
        }
        .details-box {
            flex: 1;
            padding: 10px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
        }
        .details-box h3 {
            margin: 0 0 8px 0;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 3px;
            font-size: 12px;
            color: #1e293b;
            text-uppercase;
        }
        .details-box table {
            width: 100%;
        }
        .details-box table td {
            padding: 2px 0;
            vertical-align: top;
        }
        .details-box table td:first-child {
            color: #64748b;
            width: 100px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .items-table th {
            background: #e2e8f0;
            border: 1px solid #cbd5e1;
            padding: 8px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
        }
        .items-table td {
            border: 1px solid #cbd5e1;
            padding: 8px;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .font-mono {
            font-family: monospace;
        }
        .total-row {
            font-weight: bold;
            background: #f1f5f9;
        }
        .notes-section {
            margin-bottom: 40px;
            padding: 10px;
            border-left: 3px solid #3b82f6;
            background: #eff6ff;
        }
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
        }
        .signature-box {
            width: 200px;
            text-align: center;
        }
        .signature-box .line {
            margin-top: 60px;
            border-top: 1px solid #333;
            padding-top: 5px;
            font-weight: bold;
        }
        @media print {
            body {
                padding: 0;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()" style="padding: 6px 15px; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
            Cetak Dokumen PO
        </button>
    </div>

    <div class="header">
        <div class="company-info">
            <h2>{{ $purchaseOrder->tenant->name }}</h2>
            <p>Sistem ERP Marketplace ASPARTECH</p>
        </div>
        <div class="po-title">
            <h1>PURCHASE ORDER</h1>
            <p>No: {{ $purchaseOrder->po_number }}</p>
        </div>
    </div>

    <div class="details">
        <div class="details-box">
            <h3>Informasi PO</h3>
            <table>
                <tr>
                    <td>Tanggal PO</td>
                    <td>: <strong>{{ $purchaseOrder->po_date->format('d F Y') }}</strong></td>
                </tr>
                <tr>
                    <td>Status PO</td>
                    <td>: {{ $purchaseOrder->status_label }}</td>
                </tr>
            </table>
        </div>
        <div class="details-box">
            <h3>Supplier Penerima</h3>
            <table>
                <tr>
                    <td>Nama Supplier</td>
                    <td>: <strong>{{ $purchaseOrder->supplier->name }}</strong></td>
                </tr>
                <tr>
                    <td>Telepon</td>
                    <td>: {{ $purchaseOrder->supplier->phone }}</td>
                </tr>
                <tr>
                    <td>Alamat</td>
                    <td>: {{ $purchaseOrder->supplier->address }}</td>
                </tr>
            </table>
        </div>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 50px;" class="text-center">No</th>
                <th style="width: 120px;">SKU</th>
                <th>Nama Produk / Barang</th>
                <th style="width: 80px;" class="text-center">Jumlah (Qty)</th>
                <th style="width: 120px;" class="text-right">Harga Satuan</th>
                <th style="width: 150px;" class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchaseOrder->items as $index => $item)
                @php
                    $sku = $item->item_sku;
                    $name = $item->item_name;
                    $type = 'product';
                    if ($item->material_id) {
                        $type = 'material';
                    } elseif ($item->inventory_item_id) {
                        $type = 'inventory';
                    }
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="font-mono">{{ $sku }}</td>
                    <td><strong>{{ $name }}</strong> <span style="font-size: 9px; color: #666; text-transform: uppercase;">({{ $type }})</span></td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right font-mono">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                    <td class="text-right font-mono">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="4" class="text-right">GRAND TOTAL</td>
                <td colspan="2" class="text-right font-mono" style="font-size: 13px; color: #15803d;">
                    Rp {{ number_format($purchaseOrder->total_amount, 0, ',', '.') }}
                </td>
            </tr>
        </tbody>
    </table>

    @if($purchaseOrder->notes)
        <div class="notes-section">
            <strong>Catatan Tambahan:</strong><br>
            {{ $purchaseOrder->notes }}
        </div>
    @endif

    <div class="signatures">
        <div class="signature-box">
            <p>Diajukan Oleh,</p>
            <div class="line">Bagian Pengadaan (Purchasing)</div>
        </div>
        <div class="signature-box">
            <p>Disetujui Oleh,</p>
            <div class="line">Owner / Direktur Utama</div>
        </div>
    </div>

    <script>
        // Trigger print immediately on load if query contains print=true
        if (window.location.search.includes('print=true')) {
            window.print();
        }
    </script>
</body>
</html>
