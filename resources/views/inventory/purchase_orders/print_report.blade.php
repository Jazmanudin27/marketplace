<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Analisis Pembelian - Cetak</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h2 {
            margin: 0 0 5px 0;
            font-size: 18px;
            text-transform: uppercase;
        }
        .header p {
            margin: 0;
            font-size: 11px;
            color: #666;
        }
        .meta-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            font-size: 11px;
        }
        .meta-info div span {
            font-weight: bold;
        }
        .kpi-row {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 25px;
        }
        .kpi-card {
            flex: 1;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            background-color: #fafafa;
            text-align: center;
        }
        .kpi-title {
            font-size: 10px;
            text-transform: uppercase;
            color: #777;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .kpi-value {
            font-size: 16px;
            font-weight: bold;
            color: #111;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            border-bottom: 1px solid #777;
            padding-bottom: 3px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 6px 8px;
            text-align: left;
        }
        table th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .font-bold {
            font-weight: bold;
        }
        .font-mono {
            font-family: Courier, monospace;
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
        <button onclick="window.print()" style="padding: 6px 15px; font-weight: bold; cursor: pointer; background-color: #ef4444; color: white; border: none; border-radius: 4px;">Cetak Halaman (Print / PDF)</button>
        <button onclick="window.close()" style="padding: 6px 15px; cursor: pointer; border: 1px solid #ccc; background: white; border-radius: 4px; margin-left: 5px;">Tutup</button>
    </div>

    <div class="header">
        <h2>Laporan Analisis Pembelian & PO</h2>
        <p>Aplikasi ERP Marketplace - Tenant ID: {{ Auth::user()->tenant_id }}</p>
    </div>

    <div class="meta-info">
        <div>
            <span>Rentang Tanggal:</span> {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
        </div>
        <div>
            <span>Tanggal Cetak:</span> {{ now()->format('d-m-Y H:i') }}
        </div>
    </div>

    <div class="kpi-row">
        <div class="kpi-card">
            <div class="kpi-title">Total Belanja PO</div>
            <div class="kpi-value">Rp {{ number_format($totalBelanja, 0, ',', '.') }}</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-title">PO Sedang Berjalan</div>
            <div class="kpi-value">{{ number_format($totalPoTerbuka) }} PO</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-title">PO Selesai Diterima</div>
            <div class="kpi-value">{{ number_format($totalPoSelesai) }} PO</div>
        </div>
    </div>

    <div class="section-title">Rincian Pembelian Barang</div>
    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 40px;">#</th>
                <th style="width: 120px;">SKU</th>
                <th>Nama Barang</th>
                <th class="text-center" style="width: 120px;">Jenis</th>
                <th class="text-center" style="width: 70px;">Satuan</th>
                <th class="text-right" style="width: 100px;">Qty Dipesan</th>
                <th class="text-right" style="width: 100px;">Qty Diterima</th>
                <th class="text-right" style="width: 120px;">Total Belanja</th>
            </tr>
        </thead>
        <tbody>
            @forelse($itemsBreakdown as $i => $item)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td class="font-mono">{{ $item['sku'] }}</td>
                    <td class="font-bold">{{ $item['name'] }}</td>
                    <td class="text-center">{{ $item['type'] }}</td>
                    <td class="text-center">{{ $item['unit'] }}</td>
                    <td class="text-right font-bold">{{ number_format($item['qty_ordered']) }}</td>
                    <td class="text-right text-bold" style="color: green;">{{ number_format($item['qty_received']) }}</td>
                    <td class="text-right font-mono font-bold">Rp {{ number_format($item['total_amount'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">Tidak ada transaksi pembelian barang dalam rentang tanggal ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Daftar Transaksi Purchase Order</div>
    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 40px;">#</th>
                <th style="width: 120px;">No PO</th>
                <th style="width: 100px;">Tanggal</th>
                <th>Supplier</th>
                <th>Departemen Pemohon</th>
                <th class="text-right" style="width: 120px;">Total Nilai PO</th>
                <th class="text-center" style="width: 100px;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $i => $order)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td class="font-mono font-bold">{{ $order->po_number }}</td>
                    <td>{{ \Carbon\Carbon::parse($order->po_date)->format('d-m-Y') }}</td>
                    <td>{{ $order->supplier->name ?? 'N/A' }}</td>
                    <td>{{ $order->department->name ?? 'Umum / Operasional' }}</td>
                    <td class="text-right font-mono font-bold">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                    <td class="text-center">
                        @if ($order->status === 'draft')
                            Draft
                        @elseif ($order->status === 'ordered')
                            Dipesan
                        @elseif ($order->status === 'partially_received')
                            Diterima Sebagian
                        @elseif ($order->status === 'received')
                            Selesai
                        @elseif ($order->status === 'cancelled')
                            Dibatalkan
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">Tidak ada data Purchase Order dalam rentang tanggal ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <script>
        // Auto trigger print in window mode
        window.addEventListener('DOMContentLoaded', () => {
            // setTimeout(() => { window.print(); }, 500);
        });
    </script>
</body>
</html>
