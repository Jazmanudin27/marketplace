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

    <div class="no-print" style="margin-bottom: 20px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px 16px;">
        <form method="GET" action="{{ route('marketplace_products.print_report') }}" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
            @if(request('name')) <input type="hidden" name="name" value="{{ request('name') }}"> @endif
            @if(request('search')) <input type="hidden" name="search" value="{{ request('search') }}"> @endif
            @if(request('sku')) <input type="hidden" name="sku" value="{{ request('sku') }}"> @endif
            @if(request('channel_id')) <input type="hidden" name="channel_id" value="{{ request('channel_id') }}"> @endif
            @if(request('channel')) <input type="hidden" name="channel" value="{{ request('channel') }}"> @endif
            @if(request('store_id')) <input type="hidden" name="store_id" value="{{ request('store_id') }}"> @endif

            <div>
                <label style="font-size: 11px; font-weight: bold; display: block; margin-bottom: 4px;">📊 Status Sinkronisasi Stok</label>
                <select name="filter" style="padding: 6px 10px; font-size: 12px; border-radius: 4px; border: 1px solid #ccc;">
                    <option value="">— Semua Status Stok —</option>
                    <option value="match" {{ (request('filter')==='match' || request('sync_filter')==='match') ? 'selected':'' }}>✅ Stok Sinkron</option>
                    <option value="diff"  {{ (request('filter')==='diff'  || request('sync_filter')==='diff')  ? 'selected':'' }}>⚠️ Stok Berbeda / Perlu Sync</option>
                    <option value="po"    {{ (request('filter')==='po'    || request('sync_filter')==='po')    ? 'selected':'' }}>⏳ Pre-Order (PO)</option>
                    <option value="nomap" {{ (request('filter')==='nomap' || request('sync_filter')==='nomap') ? 'selected':'' }}>🔗 Belum Map ke Master</option>
                </select>
            </div>

            <div>
                <label style="font-size: 11px; font-weight: bold; display: block; margin-bottom: 4px;">⏳ Tipe Produk (PO / Reguler)</label>
                <select name="is_po" style="padding: 6px 10px; font-size: 12px; border-radius: 4px; border: 1px solid #ccc;">
                    <option value="">— Semua Tipe —</option>
                    <option value="0" {{ request('is_po')==='0' ? 'selected':'' }}>Reguler (Non Pre-Order)</option>
                    <option value="1" {{ request('is_po')==='1' ? 'selected':'' }}>Pre-Order (PO) Sahaja</option>
                </select>
            </div>

            <button type="submit" style="padding: 6px 14px; background: #0284c7; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">
                🔍 Terapkan Filter
            </button>

            <button type="button" onclick="window.print()" style="padding: 6px 16px; background: #15803d; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; margin-left: auto;">
                🖨️ Cetak Halaman Ini
            </button>
        </form>
    </div>

    <div class="header">
        <h1>LAPORAN PRODUK MARKETPLACE & STOK SINKRONISASI</h1>
        <p>Tanggal Cetak: {{ date('d-m-Y H:i:s') }} | Perusahaan: {{ Auth::user()->tenant->name ?? 'ERP System' }}</p>
    </div>

    @php
        $appliedFilters = [];
        $filterVal = request('filter') ?? request('sync_filter');
        if ($filterVal === 'match') $appliedFilters[] = "Status Stok: ✅ Sinkron";
        elseif ($filterVal === 'diff') $appliedFilters[] = "Status Stok: ⚠️ Berbeda / Perlu Sync";
        elseif ($filterVal === 'po') $appliedFilters[] = "Status Stok: ⏳ Pre-Order (PO)";
        elseif ($filterVal === 'nomap') $appliedFilters[] = "Status Stok: 🔗 Belum Map";

        if (request('is_po') === '1') $appliedFilters[] = "Tipe: Pre-Order (PO)";
        elseif (request('is_po') === '0') $appliedFilters[] = "Tipe: Reguler (Non PO)";

        if (request('status') === 'mapped') $appliedFilters[] = "Tautan: Sudah Ditautkan";
        elseif (request('status') === 'unmapped') $appliedFilters[] = "Tautan: Belum Ditautkan";

        if (request('name')) $appliedFilters[] = "Nama: \"" . request('name') . "\"";
        if (request('search')) $appliedFilters[] = "Cari: \"" . request('search') . "\"";
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
            <label>Belum Ditautkan</label>
            <span style="color: #6c757d;">🔗 {{ number_format($unmappedCount) }}</span>
        </div>
        <div class="summary-item">
            <label>Total Stok MP</label>
            <span style="color: #0d6efd;">{{ number_format($totalStock) }}</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="4%" class="text-center">NO</th>
                <th width="24%">NAMA PRODUK MARKETPLACE</th>
                <th width="14%">CHANNEL & TOKO</th>
                <th width="14%">SKU MARKETPLACE</th>
                <th width="10%" class="text-center">STOK GUDANG (ERP)</th>
                <th width="10%" class="text-center">STOK MARKETPLACE</th>
                <th width="8%" class="text-center">SELISIH</th>
                <th width="16%" class="text-center">STATUS SINKRON</th>
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
                            <div style="font-size:9px; color:#555;">Master: {{ $p->masterProduct->name }} (SKU: {{ $p->masterProduct->sku }})</div>
                        @endif
                    </td>
                    <td>
                        <strong>{{ $p->store->channel->name ?? '-' }}</strong><br>
                        <span style="color:#555;">{{ $p->store->store_name ?? '-' }}</span>
                    </td>
                    <td class="font-mono">{{ $p->marketplace_sku ?: '-' }}</td>
                    <td class="text-center font-mono" style="font-weight:bold;">
                        {{ $localStock !== null ? number_format($localStock) : '-' }}
                        @if($safetyStock > 0)
                            <div style="font-size:8px; color:#6b21a8;">(Safety: {{ $safetyStock }})</div>
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
                        @if($isPo)
                            <span class="badge-po">⏳ PRE-ORDER</span>
                        @elseif($isNoMap)
                            <span class="badge-unmapped">🔗 BELUM MAP</span>
                        @elseif($isSinkron)
                            <span class="badge-sinkron">✅ SINKRON</span>
                        @else
                            <span class="badge-diff">⚠️ PERLU SYNC</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 15px;">Tidak ada data produk marketplace yang ditemukan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
