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

    {{-- Top Action Navigation --}}
    <div class="action-bar no-print">
        <div style="font-weight: 700; font-size: 14px; display: flex; align-items: center; gap: 8px;">
            📄 <span>Laporan Stok Barang (Gudang &amp; Marketplace) V2</span>
            <span style="font-size: 11px; background: rgba(255,255,255,0.15); padding: 2px 8px; border-radius: 999px;">
                Total: {{ count($products) }} SKU
            </span>
        </div>
        <div style="display: flex; gap: 8px; align-items: center;">
            <button type="button" class="btn-action btn-sync-all" id="btnSyncAll">
                🔄 Sync Semua Selisih
            </button>
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
            <h1>LAPORAN STOK BARANG (GUDANG &amp; MARKETPLACE) V2</h1>
            <p>Tanggal Dicetak: {{ date('d-m-Y H:i:s') }} | Dicetak Oleh: {{ auth()->user()->name ?? 'Administrator' }}</p>
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
        @if (request('search'))
            | Pencarian: "{{ request('search') }}"
        @endif
        @if (request()->boolean('hide_zero_stock'))
            | Sembunyikan Stok 0: Ya
        @endif
        @if (request()->boolean('only_different'))
            | <strong style="color: #dc2626;">⚠️ Filter: Hanya Stok Berbeda (Beda Gudang vs Toko)</strong>
        @endif
    </div>

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
                            $isDifferent = (!$product->is_preorder && $storeMpProducts->isNotEmpty() && $storeStock !== $stokGudang);
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
                        @if($product->is_preorder)
                            <span style="color: #64748b; font-size: 11px;">PO (Non-Sync)</span>
                        @elseif($hasDiscrepancy)
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
