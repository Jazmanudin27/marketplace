<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Master Produk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
            padding: 20px;
            margin: 0;
            font-size: 13px;
        }
        .header-bar {
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .header-title h4 {
            margin: 0 0 4px 0;
            font-weight: 700;
            color: #0f172a;
        }
        .header-title p {
            margin: 0;
            color: #64748b;
            font-size: 12px;
        }
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-box {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        .stat-info .label {
            font-size: 10px;
            text-transform: uppercase;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 2px;
        }
        .stat-info .value {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
        }
        .filter-box {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
        }
        .table-container {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            overflow: hidden;
        }
        table.report-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        table.report-table th, table.report-table td {
            border: 1px solid #94a3b8;
            padding: 8px 10px;
            vertical-align: top;
        }
        table.report-table th {
            background-color: #1e293b;
            color: #ffffff;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.3px;
        }
        table.report-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }
        table.report-table tbody tr:hover {
            background-color: #f1f5f9;
        }
        .font-mono {
            font-family: 'Courier New', Courier, monospace;
        }
        .badge-tag {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10.5px;
            font-weight: 700;
            line-height: 1.2;
        }
        .bg-active { background-color: #dcfce7; color: #15803d; border: 1px solid #86efac; }
        .bg-inactive { background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
        .bg-po { background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .bg-ready { background-color: #dcfce7; color: #15803d; border: 1px solid #86efac; }
        .bg-bundle { background-color: #f3ebff; color: #6f42c1; border: 1px solid #d8b4fe; }
        .bg-single { background-color: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            .header-bar, .filter-box, .table-container {
                border: none;
                box-shadow: none;
                border-radius: 0;
            }
            table.report-table th {
                background-color: #000000 !important;
                color: #ffffff !important;
            }
        }
    </style>
</head>
<body>

    <!-- Header Action Bar -->
    <div class="header-bar">
        <div class="header-title">
            <h4><i class="fas fa-boxes me-2 text-primary"></i>Laporan Master Produk</h4>
            <p>Data master produk lengkap, HPP, harga jual, estimasi kain, estimasi biaya produksi, stok, dan toko marketplace.</p>
        </div>
        <div class="d-flex gap-2 no-print">
            <a href="{{ route('reports.master_product.export', request()->all()) }}" class="btn btn-sm btn-outline-success px-3 fw-semibold">
                <i class="fas fa-file-excel me-1"></i> Ekspor CSV / Excel
            </a>
            <button onclick="window.print()" class="btn btn-sm btn-primary px-3 fw-semibold">
                <i class="fas fa-print me-1"></i> Cetak / Print Laporan
            </button>
            <a href="{{ route('products.index') }}" class="btn btn-sm btn-outline-secondary px-3">
                <i class="fas fa-arrow-left me-1"></i> Master Produk
            </a>
        </div>
    </div>

    <!-- Summary Stats Grid -->
    <div class="stat-grid">
        <div class="stat-box">
            <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                <i class="fas fa-boxes"></i>
            </div>
            <div class="stat-info">
                <div class="label">Total Master Produk</div>
                <div class="value">{{ number_format($totalCount) }}</div>
            </div>
        </div>
        <div class="stat-box">
            <div class="stat-icon" style="background-color: #f3ebff; color: #6f42c1;">
                <i class="fas fa-layer-group"></i>
            </div>
            <div class="stat-info">
                <div class="label">Produk Set / Bundling</div>
                <div class="value" style="color: #6f42c1;">{{ number_format($bundleCount) }}</div>
            </div>
        </div>
        <div class="stat-box">
            <div class="stat-icon bg-info bg-opacity-10 text-info">
                <i class="fas fa-box"></i>
            </div>
            <div class="stat-info">
                <div class="label">Produk Single</div>
                <div class="value text-info">{{ number_format($singleCount) }}</div>
            </div>
        </div>
        <div class="stat-box">
            <div class="stat-icon bg-success bg-opacity-10 text-success">
                <i class="fas fa-coins"></i>
            </div>
            <div class="stat-info">
                <div class="label">Est. Total Modal Stok</div>
                <div class="value text-success">Rp {{ number_format($totalStockValue, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="filter-box no-print">
        <form action="{{ route('reports.master_product') }}" method="GET">
            <div class="row g-2 align-items-end">
                <div class="col-md-2 col-6">
                    <label class="form-label form-label-sm fw-bold text-muted mb-1">Toko Marketplace</label>
                    <select name="store_id" class="form-select form-select-sm">
                        <option value="">Semua Toko</option>
                        @foreach ($stores as $st)
                            <option value="{{ $st->id }}" {{ request('store_id') == $st->id ? 'selected' : '' }}>
                                {{ $st->store_name }} ({{ ucfirst($st->channel->name ?? $st->channel->code ?? 'MP') }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 col-6">
                    <label class="form-label form-label-sm fw-bold text-muted mb-1">Jenis Produk</label>
                    <select name="is_bundle" class="form-select form-select-sm">
                        <option value="">Semua Jenis</option>
                        <option value="0" {{ request('is_bundle') === '0' ? 'selected' : '' }}>Single</option>
                        <option value="1" {{ request('is_bundle') === '1' ? 'selected' : '' }}>Set / Bundling</option>
                    </select>
                </div>
                <div class="col-md-2 col-6">
                    <label class="form-label form-label-sm fw-bold text-muted mb-1">Tipe Pre-Order</label>
                    <select name="is_preorder" class="form-select form-select-sm">
                        <option value="">Semua Tipe</option>
                        <option value="1" {{ request('is_preorder') === '1' ? 'selected' : '' }}>PO</option>
                        <option value="0" {{ request('is_preorder') === '0' ? 'selected' : '' }}>Ready Stock</option>
                    </select>
                </div>
                <div class="col-md-2 col-6">
                    <label class="form-label form-label-sm fw-bold text-muted mb-1">Kategori</label>
                    <select name="category_id" class="form-select form-select-sm">
                        <option value="">Semua Kategori</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 col-6">
                    <label class="form-label form-label-sm fw-bold text-muted mb-1">Status</label>
                    <select name="is_active" class="form-select form-select-sm">
                        <option value="">Semua Status</option>
                        <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Aktif</option>
                        <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>
                <div class="col-md-2 col-6">
                    <label class="form-label form-label-sm fw-bold text-muted mb-1">Cari Nama / SKU</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Ketik Nama / SKU...">
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                <div class="form-check m-0">
                    <input class="form-check-input" type="checkbox" name="hide_zero_stock" value="1" id="hideZeroStock" {{ request()->boolean('hide_zero_stock') ? 'checked' : '' }} onchange="this.form.submit()">
                    <label class="form-check-label small fw-semibold text-dark" for="hideZeroStock">
                        Sembunyikan Produk Stok 0
                    </label>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary px-3 fw-semibold">
                        <i class="fas fa-filter me-1"></i> Filter
                    </button>
                    <a href="{{ route('reports.master_product') }}" class="btn btn-sm btn-outline-secondary px-3">Reset</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div class="table-container">
        <div class="table-responsive">
            <table class="report-table">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 40px;">NO</th>
                        <th style="width: 140px;">SKU</th>
                        <th>NAMA PRODUK</th>
                        <th class="text-end" style="width: 120px;">HARGA JUAL</th>
                        <th class="text-end" style="width: 120px;">HARGA HPP</th>
                        <th class="text-end" style="width: 110px;">ESTIMASI KAIN</th>
                        <th class="text-end" style="width: 140px;">EST. HARGA PRODUKSI</th>
                        <th class="text-end" style="width: 100px;">STOK GUDANG</th>
                        <th class="text-center" style="width: 130px;">STATUS</th>
                        <th style="width: 260px;">TOKO MARKETPLACE TERHUBUNG</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $index => $product)
                        @php
                            $mpStores = $product->marketplaceProducts
                                ->map(function ($m) {
                                    $ch = $m->store->channel->name ?? $m->store->channel->code ?? '';
                                    $st = $m->store->store_name ?? '';
                                    $stk = number_format($m->stock);
                                    return $ch ? "{$ch}: {$st} (Stok: {$stk})" : "{$st} (Stok: {$stk})";
                                })
                                ->implode(', ');
                        @endphp
                        <tr>
                            <td class="text-center text-muted fw-semibold">{{ $products->firstItem() + $index }}</td>
                            <td class="font-mono">
                                <strong>{{ $product->sku }}</strong>
                                @if($product->sku_induk)
                                    <div class="text-muted" style="font-size: 10px;">Induk: {{ $product->sku_induk }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $product->name }}</div>
                                <div class="text-muted" style="font-size: 11px;">
                                    @if($product->ukuran) [{{ $product->ukuran }}] @endif
                                    @if($product->warna) [{{ $product->warna }}] @endif
                                    @if($product->category) {{ $product->category->name }} @endif
                                    @if($product->brand) | {{ $product->brand->name }} @endif
                                </div>
                                @if($product->is_bundle && $product->components->isNotEmpty())
                                    <div style="font-size: 10.5px; color: #6f42c1; margin-top: 2px;">
                                        <strong>Komponen:</strong> {{ $product->components->map(fn($c) => ($c->pivot->quantity > 1 ? $c->pivot->quantity . 'x ' : '') . $c->sku)->implode(', ') }}
                                    </div>
                                @endif
                            </td>
                            <td class="text-end font-mono fw-bold text-primary">
                                Rp {{ number_format($product->price, 0, ',', '.') }}
                            </td>
                            <td class="text-end font-mono text-secondary">
                                Rp {{ number_format($product->cost_price, 0, ',', '.') }}
                            </td>
                            <td class="text-end font-mono">
                                @if($product->est_kain > 0)
                                    {{ number_format($product->est_kain, 2, ',', '.') }} m
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-end font-mono">
                                @if($product->est_biaya_produksi > 0)
                                    Rp {{ number_format($product->est_biaya_produksi, 0, ',', '.') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-end font-mono fw-bold text-success" style="font-size: 13px;">
                                {{ number_format($product->stock, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                <span class="badge-tag {{ $product->is_active ? 'bg-active' : 'bg-inactive' }}">
                                    {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                                <div>
                                    <span class="badge-tag {{ $product->is_preorder ? 'bg-po' : 'bg-ready' }}">
                                        {{ $product->is_preorder ? 'PO' : 'Ready' }}
                                    </span>
                                </div>
                                <div>
                                    <span class="badge-tag {{ $product->is_bundle ? 'bg-bundle' : 'bg-single' }}">
                                        {{ $product->is_bundle ? 'Set' : 'Single' }}
                                    </span>
                                </div>
                            </td>
                            <td style="font-size: 11px;">
                                @if(!empty($mpStores))
                                    {{ $mpStores }}
                                @else
                                    <span class="text-muted fst-italic">- Belum Ditautkan -</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">
                                Tidak ada data master produk yang sesuai dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination Links -->
    @if($products->hasPages())
        <div class="mt-3 no-print d-flex justify-content-center">
            {{ $products->links() }}
        </div>
    @endif

</body>
</html>
