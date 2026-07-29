<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Produk Marketplace</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #000;
            margin: 0;
            padding: 20px;
            font-size: 11px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .header h1 {
            margin: 0 0 5px 0;
            font-size: 20px;
            text-transform: uppercase;
        }

        .header p {
            margin: 0;
            font-size: 12px;
            color: #555;
        }

        .filter-info {
            margin-bottom: 12px;
            font-size: 10px;
            color: #444;
            background-color: #f1f3f5;
            padding: 6px 10px;
            border-radius: 4px;
            border-left: 3px solid #0d6efd;
        }

        .summary-box {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            background-color: #f8f9fa;
            border: 1px solid #ccc;
            padding: 10px;
            border-radius: 4px;
        }

        .summary-item {
            text-align: center;
            flex: 1;
        }

        .summary-item label {
            display: block;
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
            font-weight: bold;
        }

        .summary-item span {
            font-size: 14px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        th, td {
            border: 1px solid #333;
            padding: 6px 8px;
            vertical-align: top;
        }

        th {
            background-color: #e9ecef;
            font-weight: bold;
            text-align: left;
            text-transform: uppercase;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .font-mono {
            font-family: 'Courier New', Courier, monospace;
        }

        .badge-sinkron {
            background-color: #d1e7dd;
            color: #0f5132;
            border: 1px solid #badbcc;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            display: inline-block;
        }

        .badge-diff {
            background-color: #fff3cd;
            color: #664d03;
            border: 1px solid #ffecb5;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            display: inline-block;
        }

        .badge-po {
            background-color: #f3e8ff;
            color: #6b21a8;
            border: 1px solid #d8b4fe;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            display: inline-block;
        }

        .badge-reguler {
            background-color: #e7f5ff;
            color: #0c85d0;
            border: 1px solid #a5d8ff;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            display: inline-block;
        }

        .badge-unmapped {
            background-color: #e2e3e5;
            color: #41464b;
            border: 1px solid #d3d6d8;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            display: inline-block;
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
<body onload="window.print()">

    <div class="no-print" style="margin-bottom: 15px; text-align: right;">
        <button onclick="window.print()" style="padding: 6px 15px; cursor: pointer; font-weight: bold;">Cetak Halaman Ini</button>
    </div>

    <div class="header">
        <h1>LAPORAN PRODUK MARKETPLACE & STOK SINKRONISASI</h1>
        <p>Tanggal Cetak: {{ date('d-m-Y H:i:s') }} | Perusahaan: {{ Auth::user()->tenant->name ?? 'ERP System' }}</p>
    </div>

    @php
        $appliedFilters = [];
        if (request('status') === 'mapped') $appliedFilters[] = "Status: Sudah Ditautkan";
        elseif (request('status') === 'unmapped') $appliedFilters[] = "Status: Belum Ditautkan";
        elseif (request('status') === 'match' || request('status') === 'sinkron') $appliedFilters[] = "Status Stok: ✅ Sinkron";
        elseif (request('status') === 'diff' || request('status') === 'beda') $appliedFilters[] = "Status Stok: ⚠️ Berbeda (Perlu Sync)";

        if (request('is_po') === '1' || request('is_po') === 'po') $appliedFilters[] = "Tipe Produk: ⏳ Pre-Order (PO)";
        elseif (request('is_po') === '0' || request('is_po') === 'reguler') $appliedFilters[] = "Tipe Produk: 📦 Reguler (Non-PO)";

        if (request('name')) $appliedFilters[] = "Nama: \"" . request('name') . "\"";
        if (request('sku')) $appliedFilters[] = "SKU: \"" . request('sku') . "\"";
        if ($selectedChannel) $appliedFilters[] = "Channel: " . $selectedChannel->name;
        if ($selectedStore) $appliedFilters[] = "Toko: " . $selectedStore->store_name;
    @endphp

    @if (count($appliedFilters) > 0)
        <div class="filter-info">
            <strong>Filter Aktif:</strong> {{ implode(' | ', $appliedFilters) }}
        </div>
    @endif

    <div class="summary-box">
        <div class="summary-item">
            <label>Total Produk</label>
            <span>{{ number_format($totalCount) }}</span>
        </div>
        <div class="summary-item">
            <label>Stok Sinkron</label>
            <span style="color: #198754;">✅ {{ number_format($sinkronCount ?? 0) }}</span>
        </div>
        <div class="summary-item">
            <label>Stok Berbeda (Perlu Sync)</label>
            <span style="color: #d97706;">⚠️ {{ number_format($bedaCount ?? 0) }}</span>
        </div>
        <div class="summary-item">
            <label>Pre-Order (PO)</label>
            <span style="color: #6b21a8;">⏳ {{ number_format($preorderCount ?? 0) }}</span>
        </div>
        <div class="summary-item">
            <label>Reguler (Non-PO)</label>
            <span style="color: #0c85d0;">📦 {{ number_format($regulerCount ?? 0) }}</span>
        </div>
        <div class="summary-item">
            <label>Belum Ditautkan</label>
            <span style="color: #6c757d;">🔗 {{ number_format($unmappedCount) }}</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="4%" class="text-center">NO</th>
                <th width="22%">NAMA PRODUK MARKETPLACE</th>
                <th width="12%">CHANNEL & TOKO</th>
                <th width="14%">SKU MARKETPLACE</th>
                <th width="11%" class="text-center">TIPE PRODUK</th>
                <th width="9%" class="text-center">STOK GUDANG</th>
                <th width="9%" class="text-center">STOK MP</th>
                <th width="6%" class="text-center">SELISIH</th>
                <th width="13%" class="text-center">STATUS SINKRON</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $index => $p)
                @php
                    $isPo        = $p->isPreOrder();
                    $isNoMap     = !$p->masterProduct;
                    $localStock  = $p->masterProduct ? (int)$p->masterProduct->stock : null;
                    $safetyStock = (int)($p->safety_stock ?? 0);
                    $expectedMp  = $localStock !== null ? max(0, $localStock - $safetyStock) : null;
                    $marketStock = (int)$p->stock;
                    $selisih     = $expectedMp !== null ? ($marketStock - $expectedMp) : null;
                    $isSinkron   = ($expectedMp !== null && $selisih === 0 && !$isPo);
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $p->name }}</strong>
                        @if($p->masterProduct)
                            <div style="font-size:8px; color:#555;">Master: {{ $p->masterProduct->name }} (SKU: {{ $p->masterProduct->sku }})</div>
                        @endif
                    </td>
                    <td>
                        <strong>{{ $p->store->channel->name ?? '-' }}</strong><br>
                        <span style="color:#555;">{{ $p->store->store_name ?? '-' }}</span>
                    </td>
                    <td class="font-mono">{{ $p->marketplace_sku ?: '-' }}</td>
                    <td class="text-center">
                        @if($isPo)
                            <span class="badge-po">⏳ PRE-ORDER</span>
                        @else
                            <span class="badge-reguler">📦 REGULER</span>
                        @endif
                    </td>
                    <td class="text-center font-mono" style="font-weight:bold;">
                        {{ $localStock !== null ? number_format($localStock) : '-' }}
                        @if($safetyStock > 0)
                            <div style="font-size:7px; color:#6b21a8;">(Safety: {{ $safetyStock }})</div>
                        @endif
                    </td>
                    <td class="text-center font-mono" style="font-weight:bold;">{{ number_format($marketStock) }}</td>
                    <td class="text-center font-mono" style="font-weight:bold;">
                        @if($selisih === null)
                            -
                        @elseif($selisih === 0)
                            <span style="color:#198754;">±0</span>
                        @elseif($selisih > 0)
                            <span style="color:#dc3545;">+{{ $selisih }}</span>
                        @else
                            <span style="color:#dc3545;">{{ $selisih }}</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($isNoMap)
                            <span class="badge-unmapped">🔗 BELUM MAP</span>
                        @elseif($isPo)
                            <span class="badge-po">⏳ PO (SKIP)</span>
                        @elseif($isSinkron)
                            <span class="badge-sinkron">✅ SINKRON</span>
                        @else
                            <span class="badge-diff">⚠️ PERLU SYNC</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center" style="padding: 15px;">Tidak ada data produk marketplace yang ditemukan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
