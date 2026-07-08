<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Rekap Persediaan</title>
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
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h2>LAPORAN REKAP PERSEDIAAN GUDANG</h2>
        <p>Gudang Bahan &amp; Kemasan</p>
        <p style="font-size: 11px; margin-top: 5px;">Periode Mutasi: {{ date('d M Y', strtotime($dateFrom)) }} s/d {{ date('d M Y', strtotime($dateTo)) }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>SKU</th>
                <th>Nama Barang</th>
                <th>Kategori</th>
                <th class="text-center">Stok Awal</th>
                <th class="text-center">Masuk (+)</th>
                <th class="text-center">Keluar (-)</th>
                <th class="text-center">Stok Akhir</th>
                <th>Satuan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rekap as $row)
                <tr>
                    <td class="font-mono">{{ $row['sku'] ?: '—' }}</td>
                    <td><strong>{{ $row['name'] }}</strong></td>
                    <td>{{ ucfirst($row['type']) }}</td>
                    <td class="text-center font-mono">{{ number_format($row['stok_awal']) }}</td>
                    <td class="text-center font-mono" style="color: green;">+{{ number_format($row['qty_masuk']) }}</td>
                    <td class="text-center font-mono" style="color: red;">-{{ number_format($row['qty_keluar']) }}</td>
                    <td class="text-center font-mono fw-bold">{{ number_format($row['stok_akhir']) }}</td>
                    <td>{{ $row['unit'] ?: 'pcs' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 30px;">Tidak ada data barang.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
