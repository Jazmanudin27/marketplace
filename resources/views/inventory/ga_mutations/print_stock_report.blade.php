<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Stok GA</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #1e293b; margin: 20px; }
        h2 { text-align: center; color: #6d28d9; margin-bottom: 4px; }
        .subtitle { text-align: center; color: #64748b; margin-bottom: 20px; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #ede9fe; color: #5b21b6; padding: 8px 10px; text-align: left; font-size: 11px; text-transform: uppercase; }
        td { padding: 7px 10px; border-bottom: 1px solid #e2e8f0; }
        tr:nth-child(even) { background: #f8f7ff; }
        .badge-atk { background: #ede9fe; color: #5b21b6; padding: 2px 7px; border-radius: 99px; font-size: 10px; }
        .badge-inventaris { background: #dbeafe; color: #1e40af; padding: 2px 7px; border-radius: 99px; font-size: 10px; }
        .stock-ok { color: #059669; font-weight: bold; }
        .stock-low { color: #d97706; font-weight: bold; }
        .stock-empty { color: #dc2626; font-weight: bold; }
        @media print { @page { margin: 15mm; } }
    </style>
</head>
<body>
    <h2>LAPORAN STOK — GENERAL AFFAIR</h2>
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
                    <td>{{ $item->name }}</td>
                    <td><span class="badge-{{ $item->type }}">{{ ucfirst($item->type) }}</span></td>
                    <td style="text-align:center">
                        @if($item->stock <= 0)
                            <span class="stock-empty">Habis</span>
                        @elseif($item->stock <= ($item->min_stock ?? 0))
                            <span class="stock-low">{{ number_format($item->stock) }} ⚠</span>
                        @else
                            <span class="stock-ok">{{ number_format($item->stock) }}</span>
                        @endif
                    </td>
                    <td>{{ $item->unit ?: 'pcs' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center;color:#64748b">Tidak ada data</td></tr>
            @endforelse
        </tbody>
    </table>

    <script>window.onload = () => window.print();</script>
</body>
</html>
