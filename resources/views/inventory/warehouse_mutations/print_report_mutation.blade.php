<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Barang Masuk & Keluar</title>
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
        .badge { display: inline-block; padding: 2px 5px; font-size: 10px; font-weight: bold; border-radius: 3px; text-transform: uppercase; }
        .badge-in { background-color: #e6f4ea; color: #137333; }
        .badge-out { background-color: #fce8e6; color: #c5221f; }
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h2>LAPORAN BARANG MASUK &amp; KELUAR</h2>
        <p>Gudang Bahan &amp; Kemasan</p>
        <p style="font-size: 11px; margin-top: 5px;">Periode: {{ date('d M Y', strtotime($dateFrom)) }} s/d {{ date('d M Y', strtotime($dateTo)) }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>No. Transaksi</th>
                <th>Barang / SKU</th>
                <th class="text-center">Tipe</th>
                <th>Asal / Pengirim</th>
                <th>Tujuan / Penerima</th>
                <th class="text-center">Qty</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $row)
                <tr>
                    <td>{{ $row->warehouseMutation->mutation_date->format('d/m/Y') }}</td>
                    <td class="font-mono">{{ $row->warehouseMutation->mutation_number }}</td>
                    <td>
                        <strong>{{ $row->inventoryItem?->name ?? '—' }}</strong><br>
                        <span class="font-mono" style="font-size: 10px; color: #666;">{{ $row->inventoryItem?->sku ?? '-' }}</span>
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $row->warehouseMutation->type === 'in' ? 'badge-in' : 'badge-out' }}">
                            {{ $row->warehouseMutation->type === 'in' ? 'Masuk' : 'Keluar' }}
                        </span>
                    </td>
                    <td>{{ $row->warehouseMutation->fromDepartment ? $row->warehouseMutation->fromDepartment->name : 'Gudang / Eksternal' }}</td>
                    <td>{{ $row->warehouseMutation->toDepartment ? $row->warehouseMutation->toDepartment->name : 'Gudang / Eksternal' }}</td>
                    <td class="text-center">{{ number_format($row->quantity) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 30px;">Tidak ada data mutasi.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
