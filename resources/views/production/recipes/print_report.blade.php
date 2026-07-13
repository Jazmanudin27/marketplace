<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Daftar Formula &amp; HPP Produk</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            font-size: 11px;
            color: #000;
            margin: 20px 30px;
            line-height: 1.4;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .header-table td {
            vertical-align: top;
        }

        .header-left {
            width: 60%;
        }

        .header-right {
            width: 40%;
            text-align: right;
        }

        .company-name {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0 0 5px 0;
        }

        .doc-title {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0 0 5px 0;
            color: #1e293b;
        }

        .filter-info {
            font-size: 9px;
            color: #555;
            background: #f8fafc;
            padding: 6px 10px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #ddd;
            padding: 6px 8px;
            text-align: left;
        }

        .data-table th {
            background: #f8fafc;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 2px solid #000;
        }

        .data-table td.text-center {
            text-align: center;
        }

        .data-table td.text-end {
            text-align: right;
        }

        .data-table th.text-center {
            text-align: center;
        }

        .data-table th.text-end {
            text-align: right;
        }

        .font-mono {
            font-family: monospace;
        }

        .btn-print-container {
            text-align: right;
            margin-bottom: 20px;
        }

        .btn-print {
            padding: 6px 15px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            font-size: 11px;
        }

        @media print {
            .no-print {
                display: none;
            }

            body {
                margin: 10mm 15mm;
            }
        }
    </style>
</head>

<body>

    <div class="btn-print-container no-print">
        <button class="btn-print" onclick="window.print()"><i class="fas fa-print"></i> Cetak Halaman (Print /
            PDF)</button>
    </div>

    <table class="header-table">
        <tr>
            <td class="header-left">
                <div class="company-name">{{ Auth::user()->tenant->name ?? 'ERP Marketplace' }}</div>
                <div style="font-size: 10px; color: #555;">Laporan Ringkasan Biaya Produksi</div>
            </td>
            <td class="header-right">
                <h1 class="doc-title">Laporan Daftar HPP Formula</h1>
                <div style="font-size: 9px; color: #777;">Dicetak pada: {{ date('d/m/Y H:i') }}</div>
            </td>
        </tr>
    </table>

    @if (request()->anyFilled(['search', 'status']))
        <div class="filter-info">
            <strong>Filter Aktif:</strong>
            @if (request('search'))
                Pencarian: "{{ request('search') }}" &nbsp;&nbsp;&nbsp;
            @endif
            @if (request('status'))
                Status: {{ request('status') === 'has_recipe' ? 'Sudah Ada Formula' : 'Belum Ada Formula' }}
            @endif
        </div>
    @endif

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 3%;" class="text-center">#</th>
                <th style="width: 12%;">SKU</th>
                <th>Nama Produk</th>
                <th style="width: 7%;" class="text-center">Tipe Produk</th>
                <th class="text-end">Total HPP (Per Pcs)</th>
            </tr>
        </thead>
        <tbody>
            @php
                $grandTotalHpp = 0;
                $countWithRecipe = 0;
            @endphp
            @forelse($products as $i => $p)
                @php
                    $recipe = $p->activeRecipe;
                    $materialsCount = $recipe ? $recipe->items->count() : 0;
                    $laborsCount = $recipe ? $recipe->labors->count() : 0;

                    // Calculate total materials cost
                    $materialsCost = 0;
                    if ($recipe) {
                        foreach ($recipe->items as $item) {
                            $materialsCost += $item->quantity * ($item->inventoryItem->cost_price ?? 0);
                        }
                    }

                    // Calculate total labor cost
                    $laborCost = $recipe ? $recipe->labors->sum('default_cost') : 0;

                    // Total cost (HPP)
                    $totalCost = 0;
                    if ($recipe) {
                        $totalCost = ($materialsCost + $laborCost) / ($recipe->batch_qty ?? 1);
                        $grandTotalHpp += $totalCost;
                        $countWithRecipe++;
                    }
                @endphp
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ $p->sku }}</td>
                    <td>{{ $p->name }}</td>
                    <td class="text-center">
                        {{ $p->is_bundle ? 'Set / Bundle' : 'Single' }}
                    </td>
                    <td class="text-end font-mono" style="font-weight: bold;">
                        @if ($recipe)
                            {{ number_format($totalCost, 0, ',', '.') }}
                        @else
                            —
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted">Tidak ditemukan produk yang cocok.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="data-table" style="width: auto; min-width: 300px; margin-top: 20px;">
        <thead>
            <tr>
                <th colspan="2">Ringkasan Statistik Laporan</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="width: 180px;">Total Item Produk Diambil</td>
                <td class="text-center"><strong>{{ $products->count() }}</strong></td>
            </tr>
            <tr>
                <td>Produk Memiliki Formula (BOM)</td>
                <td class="text-center"><strong>{{ $countWithRecipe }}</strong></td>
            </tr>
            <tr>
                <td>Produk Belum Memiliki Formula</td>
                <td class="text-center"><strong>{{ $products->count() - $countWithRecipe }}</strong></td>
            </tr>
            @if ($countWithRecipe > 0)
                <tr style="font-weight: bold;">
                    <td>Rata-rata HPP Terpasang</td>
                    <td class="text-end font-mono">Rp
                        {{ number_format($grandTotalHpp / $countWithRecipe, 0, ',', '.') }}</td>
                </tr>
            @endif
        </tbody>
    </table>

</body>

</html>
