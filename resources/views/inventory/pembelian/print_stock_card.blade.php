<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kartu Stok Barang - {{ $selectedItem->name }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #1e293b; margin: 20px; }
        h2 { text-align: center; color: #059669; margin-bottom: 4px; }
        .subtitle { text-align: center; color: #64748b; margin-bottom: 16px; font-size: 10px; }
        .info-box { border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; margin-bottom: 15px; background: #fbfdfc; }
        .info-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
        .info-label { color: #64748b; font-size: 9px; text-transform: uppercase; font-weight: bold; }
        .info-value { color: #1e293b; font-size: 12px; font-weight: bold; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #ecfdf5; color: #047857; padding: 7px 8px; text-align: left; font-size: 10px; text-transform: uppercase; }
        td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; }
        tr:nth-child(even) { background: #fbfdfc; }
        .badge-in  { background: #10b981; color: white; padding: 2px 7px; border-radius: 99px; font-size: 8px; font-weight: bold; text-transform: uppercase; }
        .badge-out { background: #dc2626; color: white; padding: 2px 7px; border-radius: 99px; font-size: 8px; font-weight: bold; text-transform: uppercase; }
        .badge-adj { background: #f59e0b; color: white; padding: 2px 7px; border-radius: 99px; font-size: 8px; font-weight: bold; text-transform: uppercase; }
        .text-success { color: #059669; font-weight: bold; }
        .text-danger  { color: #dc2626; font-weight: bold; }
        @media print { @page { margin: 15mm; } }
    </style>
</head>
<body>
    <h2>KARTU STOK BARANG</h2>
    <div class="subtitle">
        Periode: {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} s/d {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}
        | Dicetak: {{ date('d M Y, H:i') }}
    </div>

    <div class="info-box">
        <div class="info-grid">
            <div>
                <div class="info-label">Nama Barang</div>
                <div class="info-value">{{ $selectedItem->name }}</div>
            </div>
            <div>
                <div class="info-label">SKU / Kode</div>
                <div class="info-value" style="font-family:monospace">{{ $selectedItem->sku ?: '—' }}</div>
            </div>
            <div>
                <div class="info-label">Stok Akhir Saat Ini</div>
                <div class="info-value" style="color:#059669">{{ number_format($selectedItem->stock) }} {{ $selectedItem->unit }}</div>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Tanggal / Jam</th>
                <th>Tipe</th>
                <th style="text-align:right">Qty</th>
                <th style="text-align:right">Sisa Stok</th>
                <th>Referensi / Keterangan</th>
                <th>User</th>
            </tr>
        </thead>
        <tbody>
            @forelse($movements as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $row->created_at->format('d M Y H:i') }}</td>
                    <td>
                        @if($row->type === 'in')
                            <span class="badge-in">MASUK</span>
                        @elseif($row->type === 'out')
                            <span class="badge-out">KELUAR</span>
                        @else
                            <span class="badge-adj">ADJUST</span>
                        @endif
                    </td>
                    <td style="text-align:right" class="{{ $row->quantity > 0 ? 'text-success' : 'text-danger' }}">
                        {{ $row->quantity > 0 ? '+' : '' }}{{ number_format($row->quantity) }}
                    </td>
                    <td style="text-align:right;font-weight:bold">{{ number_format($row->balance_after) }}</td>
                    <td>{{ $row->reference }}</td>
                    <td>{{ $row->user->name ?? 'System' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align:center;padding:20px;color:#94a3b8">Tidak ada pergerakan stok untuk barang ini pada periode terpilih.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
