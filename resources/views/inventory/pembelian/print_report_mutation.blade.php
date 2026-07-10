<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Mutasi Barang - Pembelian</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #1e293b; margin: 20px; }
        h2 { text-align: center; color: #059669; margin-bottom: 4px; }
        .subtitle { text-align: center; color: #64748b; margin-bottom: 16px; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #ecfdf5; color: #047857; padding: 7px 8px; text-align: left; font-size: 10px; text-transform: uppercase; }
        td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; }
        tr:nth-child(even) { background: #fbfdfc; }
        .badge-in  { background: #10b981; color: white; padding: 2px 7px; border-radius: 99px; font-size: 9px; text-transform: uppercase; }
        .badge-out { background: #f59e0b; color: white; padding: 2px 7px; border-radius: 99px; font-size: 9px; text-transform: uppercase; }
        @media print { @page { margin: 15mm; } }
    </style>
</head>
<body>
    <h2>LAPORAN MUTASI BARANG — PEMBELIAN / GUDANG</h2>
    <div class="subtitle">
        Periode: {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} s/d {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}
        | Tipe Mutasi: {{ $type === 'all' ? 'Semua' : ($type === 'in' ? 'Masuk' : 'Keluar') }}
        | Kategori: {{ $itemType === 'all' ? 'Semua' : strtoupper($itemType) }}
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
            @forelse($mutations as $i => $row)
                @php
                    $mDate = $row->warehouseMutation->mutation_date ? $row->warehouseMutation->mutation_date->format('d M Y') : '—';
                @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $mDate }}</td>
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
