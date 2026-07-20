<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Persediaan Barang - Pembelian</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #1e293b; margin: 20px; }
        h2 { text-align: center; color: #059669; margin-bottom: 4px; }
        .subtitle { text-align: center; color: #64748b; margin-bottom: 16px; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #ecfdf5; color: #047857; padding: 7px 8px; text-align: left; font-size: 10px; text-transform: uppercase; }
        td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; }
        tr:nth-child(even) { background: #fbfdfc; }
        .text-success { color: #059669; font-weight: bold; }
        .text-danger  { color: #dc2626; font-weight: bold; }
        @media print { @page { margin: 15mm; } }
    </style>
</head>
<body>
    <h2>REKAP PERSEDIAAN BARANG — PEMBELIAN / GUDANG</h2>
    <div class="subtitle">
        Periode: {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} s/d {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}
        | Kategori: {{ $itemType === 'all' ? 'Semua' : strtoupper($itemType) }}
        | Dicetak: {{ date('d M Y, H:i') }}
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>SKU</th>
                <th>Nama Barang</th>
                <th>Kategori</th>
                <th style="text-align:center">Stok Awal</th>
                <th style="text-align:center">Pembelian</th>
                <th style="text-align:center">Retur Jual</th>
                <th style="text-align:center">Penyesuaian (+)</th>
                <th style="text-align:center">Produksi</th>
                <th style="text-align:center">Percetakan</th>
                <th style="text-align:center">Retur Beli</th>
                <th style="text-align:center">Penyesuaian (-)</th>
                <th style="text-align:center">Stok Akhir</th>
                <th>Satuan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rekap as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td style="font-family:monospace">{{ $row['sku'] ?: '—' }}</td>
                    <td style="font-weight:bold">{{ $row['name'] }}</td>
                    <td style="text-transform:uppercase">{{ $row['type'] }}</td>
                    <td style="text-align:center">{{ number_format($row['stok_awal']) }}</td>
                    <td style="text-align:center" class="text-success">+{{ number_format($row['pembelian']) }}</td>
                    <td style="text-align:center" class="text-success">+{{ number_format($row['retur_penjualan']) }}</td>
                    <td style="text-align:center" class="text-success">+{{ number_format($row['penyesuaian_masuk']) }}</td>
                    <td style="text-align:center" class="text-danger">-{{ number_format($row['produksi']) }}</td>
                    <td style="text-align:center" class="text-danger">-{{ number_format($row['percetakan']) }}</td>
                    <td style="text-align:center" class="text-danger">-{{ number_format($row['retur_pembelian']) }}</td>
                    <td style="text-align:center" class="text-danger">-{{ number_format($row['penyesuaian_keluar']) }}</td>
                    <td style="text-align:center;font-weight:bold">{{ number_format($row['stok_akhir']) }}</td>
                    <td>{{ $row['unit'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="15" style="text-align:center;padding:20px;color:#94a3b8">Tidak ada data rekap persediaan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
