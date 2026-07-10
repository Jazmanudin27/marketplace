<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Stok Barang - Pembelian</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #1e293b; margin: 20px; }
        h2 { text-align: center; color: #059669; margin-bottom: 4px; }
        .subtitle { text-align: center; color: #64748b; margin-bottom: 20px; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #ecfdf5; color: #047857; padding: 8px 10px; text-align: left; font-size: 11px; text-transform: uppercase; }
        td { padding: 7px 10px; border-bottom: 1px solid #e2e8f0; }
        tr:nth-child(even) { background: #fbfdfc; }
        .badge-kategori { background: #f1f5f9; color: #475569; padding: 2px 7px; border-radius: 99px; font-size: 10px; text-transform: uppercase; }
        .stock-ok { color: #059669; font-weight: bold; }
        .stock-low { color: #d97706; font-weight: bold; }
        .stock-empty { color: #dc2626; font-weight: bold; }
        @media print { @page { margin: 15mm; } }
    </style>
</head>
<body>
    <h2>LAPORAN STOK BARANG — PEMBELIAN / GUDANG</h2>
    <div class="subtitle">Tanggal Cetak: {{ date('d F Y, H:i') }}</div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>SKU</th>
                <th>Nama Barang</th>
                <th>Kategori</th>
                <th style="text-align:center">Stok Fisik</th>
                <th>Satuan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td style="font-family:monospace">{{ $item->sku ?: '—' }}</td>
                    <td style="font-weight:bold">{{ $item->name }}</td>
                    <td><span class="badge-kategori">{{ $item->type }}</span></td>
                    <td style="text-align:center">
                        @if($item->stock <= 0)
                            <span class="stock-empty">0</span>
                        @elseif($item->stock <= $item->min_stock)
                            <span class="stock-low">{{ number_format($item->stock) }}</span>
                        @else
                            <span class="stock-ok">{{ number_format($item->stock) }}</span>
                        @endif
                    </td>
                    <td>{{ $item->unit }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center;padding:20px;color:#94a3b8">Tidak ada data stok barang.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
