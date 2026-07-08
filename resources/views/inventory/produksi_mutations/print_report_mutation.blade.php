<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Mutasi Produksi</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #1e293b; margin: 20px; }
        h2 { text-align: center; color: #6d28d9; margin-bottom: 4px; }
        .subtitle { text-align: center; color: #64748b; margin-bottom: 16px; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #ede9fe; color: #5b21b6; padding: 7px 8px; text-align: left; font-size: 10px; text-transform: uppercase; }
        td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; }
        tr:nth-child(even) { background: #f8f7ff; }
        .badge-in  { background: #10b981; color: white; padding: 2px 7px; border-radius: 99px; font-size: 9px; text-transform: uppercase; }
        .badge-out { background: #f59e0b; color: white; padding: 2px 7px; border-radius: 99px; font-size: 9px; text-transform: uppercase; }
        @media print { @page { margin: 15mm; } }
    </style>
</head>
<body>
    <h2>LAPORAN MUTASI BARANG — GUDANG PRODUKSI</h2>
    <div class="subtitle">
        Periode: {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} s/d {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}
        | Tipe: {{ $type === 'all' ? 'Semua' : ($type === 'in' ? 'Masuk' : 'Keluar') }}
        | Dicetak: {{ date('d M Y, H:i') }}
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Tanggal</th>
                <th>No. Transaksi</th>
                <th>Barang</th>
                <th>SKU</th>
                <th>Tipe</th>
                <th>Asal</th>
                <th>Tujuan</th>
                <th style="text-align:center">Qty</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $row->warehouseMutation->mutation_date->format('d M Y') }}</td>
                    <td style="font-weight:bold">{{ $row->warehouseMutation->mutation_number }}</td>
                    <td>{{ $row->inventoryItem->name }}</td>
                    <td style="font-family:monospace">{{ $row->inventoryItem->sku ?: '—' }}</td>
                    <td>
                        @if($row->warehouseMutation->type === 'in')
                            <span class="badge-in">Masuk</span>
                        @else
                            <span class="badge-out">Keluar</span>
                        @endif
                    </td>
                    <td>{{ $row->warehouseMutation->fromDepartment ? $row->warehouseMutation->fromDepartment->name : 'Gudang Utama' }}</td>
                    <td>{{ $row->warehouseMutation->toDepartment ? $row->warehouseMutation->toDepartment->name : 'Gudang Utama' }}</td>
                    <td style="text-align:center;font-weight:bold">{{ number_format($row->quantity) }} {{ $row->inventoryItem->unit }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align:center;padding:20px;color:#94a3b8">Tidak ada data mutasi.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
