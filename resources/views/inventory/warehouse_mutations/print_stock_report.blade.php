<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Stok Gudang Bahan & Kemasan</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h2 { margin: 0 0 5px; }
        .header p { margin: 0; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f5f5f5; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-mono { font-family: monospace; }
        .fw-bold { font-weight: bold; }
        @media print {
            body { margin: 0; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h2>LAPORAN KONDISI STOK BARANG GUDANG</h2>
        <p>Gudang Bahan &amp; Kemasan</p>
        <p style="font-size: 11px; margin-top: 5px;">Tanggal Cetak: {{ date('d M Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>SKU</th>
                <th>Nama Barang</th>
                <th>Kategori</th>
                <th class="text-center">Stok Fisik</th>
                <th>Satuan</th>
                <th class="text-right">Harga Pokok (Estimasi)</th>
                <th class="text-right">Total Nilai Persediaan</th>
            </tr>
        </thead>
        <tbody>
            @php $grandTotal = 0; @endphp
            @forelse($items as $item)
                @php
                    $subtotal = $item->stock * ($item->cost_price ?: 0);
                    $grandTotal += $subtotal;
                @endphp
                <tr>
                    <td class="font-mono">{{ $item->sku ?: '—' }}</td>
                    <td><strong>{{ $item->name }}</strong></td>
                    <td>{{ ucfirst($item->type) }}</td>
                    <td class="text-center font-mono">
                        {{ number_format($item->stock) }}
                        @if($item->stock <= ($item->min_stock ?? 0) && $item->stock > 0)
                            (Minim)
                        @elseif($item->stock <= 0)
                            (Habis)
                        @endif
                    </td>
                    <td>{{ $item->unit ?: 'pcs' }}</td>
                    <td class="text-right font-mono">Rp {{ number_format($item->cost_price, 0, ',', '.') }}</td>
                    <td class="text-right font-mono fw-bold">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 30px;">Tidak ada data barang.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="background-color: #f5f5f5;">
                <td colspan="6" class="text-right fw-bold">TOTAL ESTIMASI ASET GUDANG:</td>
                <td class="text-right font-mono fw-bold" style="font-size: 13px;">Rp {{ number_format($grandTotal, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
