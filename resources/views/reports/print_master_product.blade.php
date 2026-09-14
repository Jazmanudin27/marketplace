<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Master Produk (Single & Set Bundling)</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #000;
            margin: 0;
            padding: 15px;
            background-color: #fff;
        }

        .header {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #000;
        }

        .header h1 {
            margin: 0 0 5px 0;
            font-size: 20px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .header p {
            margin: 0;
            font-size: 12px;
            color: #444;
        }

        .summary-box {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            padding: 10px 15px;
            border-radius: 6px;
        }

        .summary-item {
            text-align: center;
        }

        .summary-item label {
            display: block;
            font-size: 10px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 700;
        }

        .summary-item span {
            font-size: 14px;
            font-weight: 800;
        }

        .action-bar {
            background-color: #0f172a;
            color: #ffffff;
            padding: 10px 16px;
            border-radius: 8px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-print {
            background-color: #2563eb;
            color: #ffffff;
        }

        .btn-close {
            background-color: #475569;
            color: #ffffff;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        table.data-table th,
        table.data-table td {
            border: 1px solid #000;
            padding: 5px 7px;
            text-align: left;
            vertical-align: top;
        }

        table.data-table th {
            background-color: #1e293b;
            color: #ffffff;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 9.5px;
            border: 1px solid #000;
        }

        .text-right {
            text-align: right !important;
        }

        .text-center {
            text-align: center !important;
        }

        .font-mono {
            font-family: 'Courier New', Courier, monospace;
        }

        .badge {
            display: inline-block;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 8.5px;
            font-weight: 700;
            line-height: 1;
        }

        .badge-success { background-color: #dcfce7; color: #15803d; border: 1px solid #86efac; }
        .badge-secondary { background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
        .badge-warning { background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .badge-purple { background-color: #f3ebff; color: #6f42c1; border: 1px solid #d8b4fe; }
        .badge-info { background-color: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }

        @media print {
            body {
                padding: 0;
            }

            .action-bar,
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>

<body>

    <div class="action-bar no-print">
        <div style="font-weight: 600; font-size: 13px;">
            📄 Cetak Laporan Master Produk
        </div>
        <div style="display: flex; gap: 8px;">
            <button onclick="window.print()" class="btn-action btn-print">
                🖨️ Cetak Halaman Ini
            </button>
            <button onclick="window.close()" class="btn-action btn-close">
                ❌ Tutup
            </button>
        </div>
    </div>

    <div class="header">
        <h1>LAPORAN MASTER PRODUK (SINGLE & SET BUNDLING)</h1>
        <p>Tanggal Cetak: {{ date('d-m-Y H:i:s') }} | Perusahaan: {{ Auth::user()->tenant->name ?? 'ERP System' }}</p>
    </div>

    <div class="summary-box">
        <div class="summary-item">
            <label>Total Master Produk</label>
            <span>{{ number_format($totalCount) }}</span>
        </div>
        <div class="summary-item">
            <label>Produk Set / Bundling</label>
            <span style="color: #6f42c1;">{{ number_format($bundleCount) }}</span>
        </div>
        <div class="summary-item">
            <label>Produk Single</label>
            <span style="color: #0284c7;">{{ number_format($singleCount) }}</span>
        </div>
        <div class="summary-item">
            <label>Est. Total Modal Stok</label>
            <span style="color: #16a34a;">Rp {{ number_format($totalStockValue, 0, ',', '.') }}</span>
        </div>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th width="3%" class="text-center">NO</th>
                <th width="12%">SKU</th>
                <th width="20%">NAMA PRODUK</th>
                <th width="9%" class="text-right">HARGA JUAL</th>
                <th width="9%" class="text-right">HARGA HPP</th>
                <th width="8%" class="text-right">EST. KAIN</th>
                <th width="10%" class="text-right">EST. PRODUKSI</th>
                <th width="6%" class="text-right">STOK</th>
                <th width="8%" class="text-center">STATUS</th>
                <th width="15%">MARKETPLACE TERHUBUNG</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $index => $p)
                @php
                    $mpCount = $p->marketplaceProducts->count();
                    $mpStores = $p->marketplaceProducts
                        ->map(function ($m) {
                            $ch = $m->store->channel->name ?? '';
                            $st = $m->store->store_name ?? '';
                            $stk = number_format($m->stock);
                            return $ch ? "{$ch} ({$st}: {$stk} Pcs)" : "{$st} ({$stk} Pcs)";
                        })
                        ->implode(', ');
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="font-mono">
                        <strong>{{ $p->sku }}</strong>
                        @if($p->sku_induk)
                            <div style="font-size: 8px; color: #64748b;">Induk: {{ $p->sku_induk }}</div>
                        @endif
                    </td>
                    <td>
                        <strong>{{ $p->name }}</strong>
                        @if($p->ukuran || $p->warna || $p->category || $p->brand)
                            <div style="margin-top: 2px; font-size: 8.5px; color: #475569;">
                                @if($p->ukuran) [{{ $p->ukuran }}] @endif
                                @if($p->warna) [{{ $p->warna }}] @endif
                                @if($p->category) {{ $p->category->name }} @endif
                                @if($p->brand) | {{ $p->brand->name }} @endif
                            </div>
                        @endif
                        @if($p->is_bundle && $p->components->isNotEmpty())
                            <div style="margin-top: 2px; font-size: 8px; color: #6f42c1;">
                                Komponen: {{ $p->components->map(fn($c) => ($c->pivot->quantity > 1 ? $c->pivot->quantity . 'x ' : '') . $c->sku)->implode(', ') }}
                            </div>
                        @endif
                    </td>
                    <td class="text-right font-mono" style="font-weight: 700; color: #0284c7;">
                        Rp {{ number_format($p->price, 0, ',', '.') }}
                    </td>
                    <td class="text-right font-mono">
                        Rp {{ number_format($p->cost_price, 0, ',', '.') }}
                    </td>
                    <td class="text-right font-mono">
                        {{ $p->est_kain > 0 ? number_format($p->est_kain, 2, ',', '.') . ' m' : '-' }}
                    </td>
                    <td class="text-right font-mono">
                        {{ $p->est_biaya_produksi > 0 ? 'Rp ' . number_format($p->est_biaya_produksi, 0, ',', '.') : '-' }}
                    </td>
                    <td class="text-right font-mono" style="font-weight: 700; color: #16a34a;">
                        {{ number_format($p->stock, 0, ',', '.') }}
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $p->is_active ? 'badge-success' : 'badge-secondary' }}">
                            {{ $p->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                        <div style="margin-top: 2px;">
                            <span class="badge {{ $p->is_preorder ? 'badge-warning' : 'badge-success' }}">
                                {{ $p->is_preorder ? 'PO' : 'Ready' }}
                            </span>
                        </div>
                        <div style="margin-top: 2px;">
                            <span class="badge {{ $p->is_bundle ? 'badge-purple' : 'badge-info' }}">
                                {{ $p->is_bundle ? 'Set' : 'Single' }}
                            </span>
                        </div>
                    </td>
                    <td style="font-size: 8.5px;">
                        @if($mpCount > 0)
                            <div><strong>{{ $mpCount }} Toko:</strong></div>
                            <div>{{ $mpStores }}</div>
                        @else
                            <span style="color: #94a3b8; font-style: italic;">Belum Ditautkan</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center" style="padding: 20px;">Tidak ada data master produk.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>

</html>
