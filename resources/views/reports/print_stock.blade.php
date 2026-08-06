<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Laporan Stok Barang (Gudang & Marketplace)</title>
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
            font-size: 22px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .header p {
            margin: 0;
            font-size: 13px;
            color: #444;
        }

        .info-box {
            margin-bottom: 15px;
            font-size: 11px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            padding: 8px 12px;
            border-radius: 4px;
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

        .btn-print:hover {
            background-color: #1d4ed8;
        }

        .btn-sync-all {
            background-color: #16a34a;
            color: #ffffff;
        }

        .btn-sync-all:hover {
            background-color: #15803d;
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
            padding: 4px 10px;
            border-radius: 4px;
            font-weight: 700;
            font-size: 11px;
            cursor: pointer;
            transition: background 0.2s;
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
            border: 1px solid #000;
            padding: 6px 8px;
            text-align: left;
            vertical-align: middle;
        }

        table.data-table th.bg-blue {
            background-color: #3b82f6 !important;
            color: #ffffff;
            text-align: center;
            font-weight: 700;
            border: 1px solid #000;
            vertical-align: middle;
        }

        table.data-table th.bg-green {
            background-color: #22c55e !important;
            color: #ffffff;
            text-align: center;
            font-weight: 700;
            border: 1px solid #000;
            vertical-align: middle;
        }

        table.data-table th.bg-cyan {
            background-color: #0284c7 !important;
            color: #ffffff;
            text-align: center;
            font-weight: 700;
            border: 1px solid #000;
            vertical-align: middle;
        }

        .bg-danger-cell {
            background-color: #ef4444 !important;
            color: #ffffff !important;
            font-weight: bold !important;
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
            bottom: 20px;
            right: 20px;
            background: #0f172a;
            color: #fff;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.3);
            font-size: 13px;
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
                border: 1px solid #000 !important;
            }

            a.product-link {
                color: #000 !important;
                text-decoration: none !important;
            }

            .bg-danger-cell {
                background-color: #ef4444 !important;
                color: #ffffff !important;
            }
        }
    </style>
</head>

<body>

    {{-- Top Bar Navigation & Batch Action --}}
    <div class="action-bar no-print">
        <div style="font-weight: 700; font-size: 14px;">
            📄 Laporan Stok Barang (Gudang &amp; Marketplace)
        </div>
        <div style="display: flex; gap: 8px; align-items: center;">
            <button type="button" class="btn-action btn-sync-all" id="btnSyncAll">
                🔄 Sinkronkan Semua Produk (Mass Sync)
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
        <h1>LAPORAN STOK BARANG (GUDANG &amp; MARKETPLACE)</h1>
        <p>Tanggal Dicetak: {{ date('d-m-Y H:i:s') }}</p>
    </div>

    <div class="info-box">
        <strong>Filter Laporan:</strong>
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
        @if (request()->boolean('only_different'))
            | ⚠️ Filter: Hanya Stok Berbeda (Beda Gudang vs Toko)
        @endif
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th class="bg-blue" style="width: 3%;">No</th>
                <th class="bg-blue" style="width: 13%;">SKU</th>
                <th class="bg-blue">Nama Produk</th>
                <th class="bg-blue" style="width: 14%;">Kategori / Merk</th>
                <th class="bg-blue" style="width: 10%;">Status &amp; PO</th>
                <th class="bg-green" style="width: 9%;">Stok Gudang</th>
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
                <th class="bg-blue text-center no-print" style="width: 70px;">AKSI</th>
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
                    <td>
                        <strong>{{ $product->category->name ?? '-' }}</strong>
                        <small style="color: #64748b; display: block;">{{ $product->brand->name ?? '-' }}</small>
                    </td>
                    <td class="text-center">
                        @if($product->is_preorder)
                            <span style="color: #c2410c; font-weight: bold;">⏳ PO ({{ $product->preorder_days ?: 7 }}hr)</span>
                        @else
                            <span style="color: #16a34a; font-weight: bold;">📦 Ready Stock</span>
                        @endif
                    </td>
                    <td class="text-right" style="background-color: #f0fdf4;">
                        <strong style="color: #15803d;">{{ number_format($stokGudang, 0, ',', '.') }}</strong>
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
                            @if($isDifferent)
                                <span style="color: #fde047; font-weight: bold; margin-right: 2px;">⚠️</span>
                            @endif
                            <span style="{{ $isDifferent ? 'color: #ffffff !important;' : ($storeStock > 0 ? 'font-weight: bold; color: #0369a1;' : 'color: #94a3b8;') }}">
                                {{ number_format($storeStock, 0, ',', '.') }}
                            </span>
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
                    <td colspan="{{ 7 + count($stores) }}" class="text-center" style="padding: 20px;">Tidak ada data barang yang sesuai dengan filter.</td>
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

                    fetch(`/reports/stock/${productId}/sync`, {
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

            // Batch sync all single buttons
            const btnSyncAll = document.getElementById('btnSyncAll');
            if (btnSyncAll) {
                btnSyncAll.addEventListener('click', function () {
                    const buttons = Array.from(document.querySelectorAll('.btn-sync-single'));
                    if (!buttons.length) {
                        showToast('Semua produk pada laporan ini sudah sinkron! ✅');
                        return;
                    }

                    if (!confirm(`Sinkronkan ${buttons.length} produk yang berbeda stok ke marketplace?`)) {
                        return;
                    }

                    btnSyncAll.disabled = true;
                    btnSyncAll.textContent = `🔄 Memproses 0/${buttons.length}...`;

                    let completedCount = 0;
                    buttons.forEach((btn, idx) => {
                        setTimeout(() => {
                            btn.click();
                            completedCount++;
                            btnSyncAll.textContent = `🔄 Memproses ${completedCount}/${buttons.length}...`;
                            if (completedCount === buttons.length) {
                                setTimeout(() => {
                                    btnSyncAll.disabled = false;
                                    btnSyncAll.textContent = '🔄 Sinkronkan Semua Produk (Mass Sync)';
                                    showToast(`🎉 Selesai! ${completedCount} produk telah disinkronkan ke marketplace.`);
                                }, 1000);
                            }
                        }, idx * 400); // Stagger requests by 400ms
                    });
                });
            }
        });
    </script>
</body>

</html>
