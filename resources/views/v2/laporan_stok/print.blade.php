<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Laporan Stok Barang (Gudang &amp; Marketplace) V2</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #0f172a;
            margin: 0;
            padding: 16px;
            background-color: #ffffff;
            font-size: 11px;
        }

        .header {
            margin-bottom: 14px;
            padding-bottom: 10px;
            border-bottom: 2px solid #0f172a;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
        }

        .header h1 {
            margin: 0 0 4px 0;
            font-size: 20px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #0f172a;
        }

        .header p {
            margin: 0;
            font-size: 12px;
            color: #64748b;
        }

        .info-box {
            margin-bottom: 14px;
            font-size: 11px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            padding: 8px 12px;
            border-radius: 6px;
            line-height: 1.5;
        }

        .action-bar {
            background-color: #0f172a;
            color: #ffffff;
            padding: 10px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            font-size: 12px;
            font-weight: 700;
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
        .btn-print:hover {
            background-color: #1d4ed8;
        }

        .btn-sync-all {
            background-color: #ef4444;
            color: #ffffff;
        }
        .btn-sync-all:hover {
            background-color: #dc2626;
        }

        .btn-close {
            background-color: #475569;
            color: #ffffff;
        }
        .btn-close:hover {
            background-color: #334155;
        }

        .btn-sync-row {
            background-color: #ef4444;
            color: #ffffff;
            border: none;
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: 700;
            font-size: 10.5px;
            cursor: pointer;
            transition: background 0.15s;
        }
        .btn-sync-row:hover {
            background-color: #dc2626;
        }
        .btn-sync-row:disabled {
            background-color: #94a3b8;
            cursor: not-allowed;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        table.data-table th,
        table.data-table td {
            border: 1px solid #000000;
            padding: 6px 8px;
            text-align: left;
            vertical-align: middle;
        }

        table.data-table th.bg-blue {
            background-color: #1e293b !important;
            color: #ffffff !important;
            text-align: center;
            font-weight: 700;
            border: 1px solid #000000;
        }

        table.data-table th.bg-green {
            background-color: #047857 !important;
            color: #ffffff !important;
            text-align: center;
            font-weight: 700;
            border: 1px solid #000000;
        }

        table.data-table th.bg-cyan {
            background-color: #0284c7 !important;
            color: #ffffff !important;
            text-align: center;
            font-weight: 700;
            border: 1px solid #000000;
        }

        .bg-danger-cell {
            background-color: #fee2e2 !important;
            color: #dc2626 !important;
            font-weight: 800 !important;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        a.product-link {
            color: #0f172a;
            text-decoration: none !important;
            font-weight: 600;
        }
        a.product-link:hover {
            color: #0284c7;
        }

        /* Toast Alert Notification */
        #toastNotice {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: #0f172a;
            color: #ffffff;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.3);
            font-size: 13px;
            font-weight: 600;
            display: none;
            z-index: 99999;
        }

        .btn-format {
            color: #cbd5e1;
            text-decoration: none;
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 4px;
            transition: all 0.15s;
            white-space: nowrap;
        }
        .btn-format:hover, .btn-format.active {
            background: #2563eb;
            color: #ffffff;
            font-weight: 700;
        }

        @media print {
            @page {
                size: landscape;
                margin: 8mm;
            }

            body {
                padding: 0;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .no-print {
                display: none !important;
            }

            table.data-table th,
            table.data-table td {
                border: 1px solid #000000 !important;
            }

            a.product-link {
                color: #000000 !important;
                text-decoration: none !important;
            }

            .bg-danger-cell {
                background-color: #fee2e2 !important;
                color: #dc2626 !important;
            }
        }
    </style>
</head>

<body>

    @php
        $format = $format ?? request('report_format', 'standard');
        $title = match($format) {
            'warehouse'   => 'LAPORAN STOK FISIK GUDANG (STOCK OPNAME)',
            'discrepancy' => 'LAPORAN AUDIT SELISIH STOK (GUDANG VS MARKETPLACE)',
            'low_stock'   => 'LAPORAN STOK KRITIS & MENIPIS (< 10 PCS)',
            default       => 'LAPORAN STOK BARANG (GUDANG & MARKETPLACE) V2',
        };
        $subtitle = match($format) {
            'warehouse'   => 'Format Lembar Cek Lapangan, Rak & Stock Opname Gudang Fisik',
            'discrepancy' => 'Daftar Produk yang Memiliki Perbedaan Kuota Marketplace vs Stok Fisik',
            'low_stock'   => 'Daftar Produk Di Bawah Batas Minimum Kritis untuk Prioritas Restock / Belanja / Produksi',
            default       => 'Pemantauan Kuota Marketplace vs Stok Fisik Gudang & Status Sinkronisasi',
        };
    @endphp

    {{-- Top Action Navigation --}}
    <div class="action-bar no-print">
        <div style="font-weight: 700; font-size: 13px; display: flex; align-items: center; gap: 8px;">
            📄 <span>{{ $title }}</span>
            <span style="font-size: 11px; background: rgba(255,255,255,0.15); padding: 2px 8px; border-radius: 999px;">
                Total: {{ count($products) }} SKU
            </span>
        </div>
        <div style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap;">
            {{-- Format Switcher --}}
            <div style="display: flex; gap: 3px; align-items: center; background: rgba(255,255,255,0.1); padding: 3px 6px; border-radius: 6px; margin-right: 6px;">
                <span style="font-size: 11px; color: #94a3b8; margin-right: 4px;">Format:</span>
                <a href="{{ route('v2.reports.stock.print', array_merge(request()->query(), ['report_format' => 'standard'])) }}" class="btn-format {{ $format === 'standard' ? 'active' : '' }}">Lengkap</a>
                <a href="{{ route('v2.reports.stock.print', array_merge(request()->query(), ['report_format' => 'warehouse'])) }}" class="btn-format {{ $format === 'warehouse' ? 'active' : '' }}">Fisik Gudang</a>
                <a href="{{ route('v2.reports.stock.print', array_merge(request()->query(), ['report_format' => 'discrepancy'])) }}" class="btn-format {{ $format === 'discrepancy' ? 'active' : '' }}">Selisih Stok</a>
                <a href="{{ route('v2.reports.stock.print', array_merge(request()->query(), ['report_format' => 'low_stock'])) }}" class="btn-format {{ $format === 'low_stock' ? 'active' : '' }}">Stok Menipis</a>
            </div>

            <a href="{{ route('v2.reports.stock.export', request()->query()) }}" class="btn-action" style="background-color: #059669; color: #ffffff;" title="Unduh File CSV / Excel">
                📥 Export Excel
            </a>
            @if($format === 'standard' || $format === 'discrepancy')
                <button type="button" class="btn-action btn-sync-all" id="btnSyncAll">
                    🔄 Sync Semua Selisih
                </button>
            @endif
            <button type="button" class="btn-action btn-print" onclick="window.print();">
                🖨️ Cetak / Print Halaman
            </button>
            <button type="button" class="btn-action btn-close" onclick="window.close();">
                ❌ Tutup
            </button>
        </div>
    </div>

    <div class="header">
        <div>
            <h1>{{ $title }}</h1>
            <p>{{ $subtitle }} | Tanggal: {{ date('d-m-Y H:i:s') }} | Oleh: {{ auth()->user()->name ?? 'Administrator' }}</p>
        </div>
        <div style="text-align: right; font-size: 11px; color: #475569;">
            <strong>ERP RUANGTECH V2</strong>
        </div>
    </div>

    <div class="info-box">
        <strong>Filter Laporan:</strong>
        @if (request('store_id'))
            | Toko: {{ App\Models\Store::find(request('store_id'))->store_name ?? '-' }}
        @else
            | Toko: Semua Toko Marketplace
        @endif
        @if (request('category_id'))
            | Kategori: {{ App\Models\Category::find(request('category_id'))->name ?? '-' }}
        @endif
        @if (request('brand_id'))
            | Merk: {{ App\Models\Brand::find(request('brand_id'))->name ?? '-' }}
        @endif
        @if (request()->filled('is_bundle'))
            | Jenis: {{ request('is_bundle') === '1' ? '🎁 BUNDLE / Paket Set' : '📦 Single (Produk Standar)' }}
        @endif
        @if (request()->filled('is_preorder'))
            | Tipe: {{ request('is_preorder') === '1' ? '⏳ Pre-Order (PO)' : '📦 Ready Stock' }}
        @endif
        @if (request('search') || request('name') || request('sku'))
            | Pencarian: "{{ request('search') ?: (request('name') ?: request('sku')) }}"
        @endif
        @if (request()->boolean('hide_zero_stock'))
            | Sembunyikan Stok 0: Ya
        @endif
        @if (request()->boolean('only_different') || $format === 'discrepancy')
            | <strong style="color: #dc2626;">⚠️ Filter: Hanya Stok Berbeda (Beda Gudang vs Toko)</strong>
        @endif
        @if (request()->boolean('low_stock') || $format === 'low_stock')
            | <strong style="color: #d97706;">⚠️ Filter: Hanya Stok Menipis (&lt; 10 PCS)</strong>
        @endif
    </div>

    {{-- =========================================================================
         FORMAT 2: RINGKASAN STOK FISIK GUDANG (STOCK OPNAME)
         ========================================================================= --}}
    @if($format === 'warehouse')
        <table class="data-table">
            <thead>
                <tr>
                    <th class="bg-blue" style="width: 3%;">No</th>
                    <th class="bg-blue" style="width: 14%;">SKU Toko / Induk</th>
                    <th class="bg-blue">Nama Produk Master</th>
                    <th class="bg-blue" style="width: 13%;">Kategori &amp; Brand</th>
                    <th class="bg-blue" style="width: 8%; text-align: center;">Tipe</th>
                    <th class="bg-blue" style="width: 6%; text-align: center;">Min</th>
                    <th class="bg-green" style="width: 10%; text-align: right;">Stok Sistem</th>
                    <th class="bg-cyan" style="width: 12%; text-align: center;">Cek Fisik Rak</th>
                    <th class="bg-blue" style="width: 8%; text-align: center;">Selisih</th>
                    <th class="bg-blue" style="width: 14%;">Catatan / Kondisi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $index => $product)
                    @php
                        $stokGudang = (int) $product->stock;
                        $minStock    = (int) ($product->min_stock ?? 5);
                        $isLowStock  = ($stokGudang < 10);
                    @endphp
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>
                            <strong>{{ $product->sku ?: '-' }}</strong>
                            @if($product->sku_induk && $product->sku_induk !== $product->sku)
                                <div style="font-size: 9.5px; color: #64748b;">Induk: {{ $product->sku_induk }}</div>
                            @endif
                        </td>
                        <td>
                            <strong style="color: #0f172a;">{{ $product->name }}</strong>
                        </td>
                        <td>
                            <div>{{ $product->category->name ?? '-' }}</div>
                            <div style="font-size: 9.5px; color: #64748b;">{{ $product->brand->name ?? '-' }}</div>
                        </td>
                        <td class="text-center">
                            @if($product->is_preorder)
                                <span style="color: #c2410c; font-weight: bold; font-size: 10px;">⏳ PO</span>
                            @else
                                <span style="color: #16a34a; font-weight: bold; font-size: 10px;">📦 Ready</span>
                            @endif
                            @if($product->is_bundle)
                                <div style="color: #7c3aed; font-size: 9px; font-weight: bold;">🎁 Set</div>
                            @endif
                        </td>
                        <td class="text-center text-muted">{{ $minStock }}</td>
                        <td class="text-right" style="background-color: #f0fdf4;">
                            <strong style="color: {{ $isLowStock ? '#dc2626' : '#15803d' }}; font-size: 12px;">
                                {{ number_format($stokGudang, 0, ',', '.') }}
                            </strong>
                            <span style="font-size: 9px; color: #64748b;">pcs</span>
                        </td>
                        {{-- Blank fields for manual opname checklist --}}
                        <td style="background-color: #fafafa; border: 1px dashed #94a3b8; height: 28px;"></td>
                        <td style="background-color: #fafafa; border: 1px dashed #94a3b8;"></td>
                        <td style="background-color: #fafafa; border: 1px dashed #94a3b8;"></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center" style="padding: 30px; color: #64748b;">
                            Tidak ada data barang yang sesuai dengan filter yang dipilih.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    {{-- =========================================================================
         FORMAT 3: LAPORAN AUDIT SELISIH STOK (DISCREPANCY REPORT)
         ========================================================================= --}}
    @elseif($format === 'discrepancy')
        <table class="data-table">
            <thead>
                <tr>
                    <th class="bg-blue" style="width: 3%;">No</th>
                    <th class="bg-blue" style="width: 14%;">SKU</th>
                    <th class="bg-blue">Nama Produk</th>
                    <th class="bg-green" style="width: 10%; text-align: right;">Stok Gudang</th>
                    <th class="bg-cyan" style="width: 16%;">Toko Marketplace</th>
                    <th class="bg-blue" style="width: 9%; text-align: center;">Channel</th>
                    <th class="bg-blue" style="width: 10%; text-align: right;">Kuota MP</th>
                    <th class="bg-blue" style="width: 10%; text-align: center;">Selisih (MP - GD)</th>
                    <th class="bg-blue" style="width: 12%; text-align: center;">Status Sync</th>
                    <th class="bg-blue no-print" style="width: 8%; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @php $discrepancyRow = 1; @endphp
                @forelse($products as $product)
                    @php
                        $stokGudang = (int) $product->stock;
                        $hasAnyDiff = false;
                    @endphp
                    @foreach($product->marketplaceProducts as $mp)
                        @php
                            if (!$mp->store) continue;
                            $mpStock = (int) $mp->stock;
                            $diff = $mpStock - $stokGudang;
                            if ($diff === 0 && !$request->boolean('show_all')) continue;
                            $hasAnyDiff = true;
                            $chCode = strtolower($mp->store->channel->code ?? $mp->store->channel->name ?? 'default');
                        @endphp
                        <tr id="product-row-{{ $product->id }}">
                            <td class="text-center">{{ $discrepancyRow++ }}</td>
                            <td><strong>{{ $product->sku ?: '-' }}</strong></td>
                            <td>{{ $product->name }}</td>
                            <td class="text-right" style="background-color: #f0fdf4;">
                                <strong style="color: #15803d; font-size: 12px;">{{ number_format($stokGudang, 0, ',', '.') }}</strong>
                            </td>
                            <td><strong>{{ $mp->store->store_name }}</strong></td>
                            <td class="text-center">
                                <span style="font-weight: 600; text-transform: uppercase; font-size: 10px;">{{ $mp->store->channel->name ?? 'MP' }}</span>
                            </td>
                            <td class="text-right bg-danger-cell">
                                <strong>{{ number_format($mpStock, 0, ',', '.') }}</strong>
                            </td>
                            <td class="text-center" style="font-weight: 800; color: {{ $diff > 0 ? '#0284c7' : '#dc2626' }};">
                                {{ $diff > 0 ? "+{$diff}" : $diff }} pcs
                            </td>
                            <td class="text-center">
                                @if($mp->sync_stock)
                                    <span style="color: #dc2626; font-weight: bold; font-size: 10px;">⚠️ Belum Sinkron</span>
                                @else
                                    <span style="color: #64748b; font-size: 10px;">Sync Mati</span>
                                @endif
                            </td>
                            <td class="text-center no-print">
                                <button type="button" class="btn-sync-row btn-sync-single" data-product-id="{{ $product->id }}" data-sku="{{ $product->sku ?? '-' }}">
                                    🔄 Sync
                                </button>
                            </td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="10" class="text-center" style="padding: 30px; color: #64748b;">
                            Tidak ada produk yang memiliki selisih stok saat ini. Seluruh stok gudang &amp; marketplace sinkron!
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    {{-- =========================================================================
         FORMAT 4: LAPORAN STOK KRITIS & MENIPIS (< 10 PCS)
         ========================================================================= --}}
    @elseif($format === 'low_stock')
        <table class="data-table">
            <thead>
                <tr>
                    <th class="bg-blue" style="width: 3%;">No</th>
                    <th class="bg-blue" style="width: 14%;">SKU Toko / Induk</th>
                    <th class="bg-blue">Nama Produk</th>
                    <th class="bg-blue" style="width: 14%;">Kategori &amp; Brand</th>
                    <th class="bg-blue" style="width: 8%; text-align: center;">Status</th>
                    <th class="bg-blue" style="width: 8%; text-align: center;">Min Stok</th>
                    <th class="bg-green" style="width: 10%; text-align: right;">Sisa Stok Gudang</th>
                    <th class="bg-blue" style="width: 12%; text-align: center;">Defisit / Kurang</th>
                    <th class="bg-blue" style="width: 16%; text-align: center;">Status Urgensi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $index => $product)
                    @php
                        $stokGudang = (int) $product->stock;
                        $minStock   = (int) ($product->min_stock ?? 10);
                        $deficit    = max(0, $minStock - $stokGudang);
                        $isOut      = ($stokGudang <= 0);
                        $isCritical = ($stokGudang > 0 && $stokGudang < 5);
                    @endphp
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>
                            <strong>{{ $product->sku ?: '-' }}</strong>
                            @if($product->sku_induk && $product->sku_induk !== $product->sku)
                                <div style="font-size: 9.5px; color: #64748b;">Induk: {{ $product->sku_induk }}</div>
                            @endif
                        </td>
                        <td>
                            <strong style="color: #0f172a;">{{ $product->name }}</strong>
                        </td>
                        <td>
                            <div>{{ $product->category->name ?? '-' }}</div>
                            <div style="font-size: 9.5px; color: #64748b;">{{ $product->brand->name ?? '-' }}</div>
                        </td>
                        <td class="text-center">
                            @if($product->is_preorder)
                                <span style="color: #c2410c; font-weight: bold; font-size: 10px;">⏳ PO</span>
                            @else
                                <span style="color: #16a34a; font-weight: bold; font-size: 10px;">📦 Ready</span>
                            @endif
                        </td>
                        <td class="text-center text-muted">{{ $minStock }} pcs</td>
                        <td class="text-right" style="{{ $isOut ? 'background-color: #fee2e2;' : 'background-color: #fef3c7;' }}">
                            <strong style="color: {{ $isOut ? '#dc2626' : '#d97706' }}; font-size: 13px;">
                                {{ number_format($stokGudang, 0, ',', '.') }}
                            </strong>
                            <span style="font-size: 9.5px; color: #64748b;">pcs</span>
                        </td>
                        <td class="text-center" style="font-weight: 700; color: #dc2626;">
                            {{ $deficit > 0 ? "-{$deficit} pcs" : 'Tercukupi' }}
                        </td>
                        <td class="text-center">
                            @if($isOut)
                                <span style="background-color: #dc2626; color: #fff; padding: 2px 8px; border-radius: 4px; font-weight: 800; font-size: 10px;">
                                    🚨 STOK HABIS (0 PCS)
                                </span>
                            @elseif($isCritical)
                                <span style="background-color: #f97316; color: #fff; padding: 2px 8px; border-radius: 4px; font-weight: 800; font-size: 10px;">
                                    ⚠️ KRITIS (&lt; 5 PCS)
                                </span>
                            @else
                                <span style="background-color: #eab308; color: #000; padding: 2px 8px; border-radius: 4px; font-weight: 700; font-size: 10px;">
                                    ⚡ MENIPIS (&lt; 10 PCS)
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center" style="padding: 30px; color: #64748b;">
                            Tidak ada produk dengan stok menipis (&lt; 10 PCS). Semua stok produk aman!
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    {{-- =========================================================================
         FORMAT 1: STANDAR (LENGKAP GUDANG VS MARKETPLACE)
         ========================================================================= --}}
    @else
        <table class="data-table">
            <thead>
                <tr>
                    <th class="bg-blue" style="width: 3%;">No</th>
                    <th class="bg-blue" style="width: 14%;">SKU</th>
                    <th class="bg-blue">Nama Produk</th>
                    <th class="bg-blue" style="width: 10%;">Status &amp; PO</th>
                    <th class="bg-blue" style="width: 16%;">Toko / Marketplace Terhubung</th>
                    <th class="bg-green" style="width: 8%;">Stok Gudang</th>
                    @foreach($stores as $store)
                        @php
                            $channelCode = strtolower($store->channel->code ?? $store->channel->name ?? '');
                            $channelShort = match(true) {
                                str_contains($channelCode, 'shopee') => 'Shopee',
                                str_contains($channelCode, 'tiktok') => 'TikTok',
                                str_contains($channelCode, 'lazada') => 'Lazada',
                                str_contains($channelCode, 'tokopedia') => 'Tokopedia',
                                default => ucfirst($store->channel->name ?? 'MP'),
                            };
                        @endphp
                        <th class="bg-cyan text-center" style="font-size: 11px;">
                            {{ $store->short_name }}
                            <span style="font-weight: normal; font-size: 9px; display: block; opacity: 0.9;">
                                ({{ $channelShort }})
                            </span>
                        </th>
                    @endforeach
                    <th class="bg-blue text-center no-print" style="width: 75px;">AKSI</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $index => $product)
                    @php
                        $stokGudang = (int) $product->stock;
                        $ledgerUrl = route('reports.ledger.print', [
                            'product_id' => $product->id,
                            'start_date' => now()->startOfMonth()->format('Y-m-d'),
                            'end_date'   => now()->format('Y-m-d'),
                        ]);
                        $hasDiscrepancy = false;
                    @endphp
                    <tr id="product-row-{{ $product->id }}">
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>
                            <a href="{{ $ledgerUrl }}" target="_blank" class="product-link" title="Buka Kartu Stok {{ $product->name }}">
                                {{ $product->sku ?? '-' }}
                            </a>
                        </td>
                        <td>
                            <a href="{{ $ledgerUrl }}" target="_blank" class="product-link" title="Buka Kartu Stok {{ $product->name }}">
                                {{ $product->name }}
                            </a>
                        </td>
                        <td class="text-center">
                            @if($product->is_preorder)
                                <span style="color: #c2410c; font-weight: bold;">⏳ PO ({{ $product->preorder_days ?: 7 }}hr)</span>
                            @else
                                <span style="color: #16a34a; font-weight: bold;">📦 Ready</span>
                            @endif
                            @if($product->is_bundle)
                                <span style="color: #7c3aed; font-weight: bold; font-size: 10px; display: block;">🎁 Bundle</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $connectedStoreList = $product->marketplaceProducts
                                    ->map(fn($mp) => $mp->store)
                                    ->filter()
                                    ->unique('id')
                                    ->map(function($st) {
                                        $chName = ucfirst($st->channel->name ?? $st->channel->code ?? 'MP');
                                        return "{$st->store_name} ({$chName})";
                                    })
                                    ->implode(', ');
                            @endphp
                            @if ($connectedStoreList)
                                <span style="font-size: 10px; font-weight: 600; color: #0f172a;">{{ $connectedStoreList }}</span>
                            @else
                                <span style="color: #94a3b8; font-style: italic; font-size: 10px;">- Belum Terhubung -</span>
                            @endif
                        </td>
                        <td class="text-right" style="background-color: #f0fdf4;">
                            <strong style="color: #15803d; font-size: 12px;">{{ number_format($stokGudang, 0, ',', '.') }}</strong>
                        </td>
                        @foreach($stores as $store)
                            @php
                                $storeMpProducts = $product->marketplaceProducts->where('store_id', $store->id);
                                $storeStock = $storeMpProducts->isNotEmpty() ? (int) $storeMpProducts->max('stock') : 0;
                                $isDifferent = ($storeMpProducts->isNotEmpty() && $storeStock !== $stokGudang);
                                if ($isDifferent) {
                                    $hasDiscrepancy = true;
                                }
                            @endphp
                            <td class="text-right {{ $isDifferent ? 'bg-danger-cell' : '' }}">
                                @if($storeMpProducts->isEmpty())
                                    <span style="color: #cbd5e1;">-</span>
                                @else
                                    @if($isDifferent)
                                        <span style="color: #dc2626; font-weight: bold; margin-right: 2px;">⚠️</span>
                                    @endif
                                    <span style="{{ $isDifferent ? 'color: #dc2626 !important; font-weight: 800;' : ($storeStock > 0 ? 'font-weight: bold; color: #0369a1;' : 'color: #94a3b8;') }}">
                                        {{ number_format($storeStock, 0, ',', '.') }}
                                    </span>
                                @endif
                            </td>
                        @endforeach
                        <td class="text-center no-print">
                            @if($hasDiscrepancy)
                                <button type="button" class="btn-sync-row btn-sync-single" data-product-id="{{ $product->id }}" data-sku="{{ $product->sku ?? '-' }}">
                                    🔄 Sync
                                </button>
                            @else
                                <span style="color: #16a34a; font-weight: bold; font-size: 11px;">✓ Sinkron</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 7 + count($stores) }}" class="text-center" style="padding: 30px; color: #64748b;">
                            Tidak ada data barang yang sesuai dengan filter yang dipilih.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif

    <div id="toastNotice"></div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const toastNotice = document.getElementById('toastNotice');

            function showToast(message, isError = false) {
                toastNotice.textContent = message;
                toastNotice.style.backgroundColor = isError ? '#ef4444' : '#10b981';
                toastNotice.style.display = 'block';
                setTimeout(() => {
                    toastNotice.style.display = 'none';
                }, 3500);
            }

            // Sync single product via AJAX
            document.querySelectorAll('.btn-sync-single').forEach(btn => {
                btn.addEventListener('click', function () {
                    const productId = this.getAttribute('data-product-id');
                    const sku       = this.getAttribute('data-sku');
                    const originalText = this.innerHTML;

                    this.disabled = true;
                    this.innerHTML = '⏳ Syncing...';

                    fetch(`/v2/reports/stock/${productId}/sync`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            showToast(`✅ Produk SKU ${sku} berhasil disinkronkan ke marketplace!`);
                            this.outerHTML = '<span style="color: #16a34a; font-weight: bold; font-size: 11px;">✓ Sinkron</span>';
                        } else {
                            showToast(`❌ Gagal sinkron SKU ${sku}: ${data.message || 'Error'}`, true);
                            this.disabled = false;
                            this.innerHTML = originalText;
                        }
                    })
                    .catch(err => {
                        showToast(`❌ Terjadi kesalahan jaringan saat sinkron SKU ${sku}`, true);
                        this.disabled = false;
                        this.innerHTML = originalText;
                    });
                });
            });

            // Sync all discrepancy products
            const btnSyncAll = document.getElementById('btnSyncAll');
            if (btnSyncAll) {
                btnSyncAll.addEventListener('click', function () {
                    if (!confirm('Apakah Anda yakin ingin menyinkronkan seluruh produk yang memiliki selisih stok ke marketplace?')) {
                        return;
                    }

                    const originalText = btnSyncAll.innerHTML;
                    btnSyncAll.disabled = true;
                    btnSyncAll.innerHTML = '⏳ Mengirim Antrian...';

                    fetch('/v2/reports/stock/sync-batch', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({})
                    })
                    .then(res => res.json())
                    .then(data => {
                        btnSyncAll.disabled = false;
                        btnSyncAll.innerHTML = originalText;
                        if (data.success) {
                            showToast(`✅ ${data.message}`);
                            setTimeout(() => {
                                window.location.reload();
                            }, 1200);
                        } else {
                            showToast(`❌ ${data.message || 'Gagal memicu antrian'}`, true);
                        }
                    })
                    .catch(err => {
                        btnSyncAll.disabled = false;
                        btnSyncAll.innerHTML = originalText;
                        showToast('❌ Kesalahan jaringan saat memproses antrian.', true);
                    });
                });
            }
        });
    </script>
</body>

</html>
