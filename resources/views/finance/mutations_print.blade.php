<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Mutasi Keuangan ({{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }})</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 11px; color: #111; background: #fff; }
        .table-print { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .table-print th, .table-print td { padding: 5px 8px; border: 1px solid #bbb; }
        .table-print thead th { background-color: #f1f5f9 !important; font-weight: bold; color: #1e293b; }
        .kpi-box { border: 1px solid #cbd5e1; border-radius: 4px; padding: 8px 12px; background: #f8fafc; }
        .kpi-title { font-size: 10px; text-transform: uppercase; color: #64748b; font-weight: 600; }
        .kpi-val { font-size: 13px; font-weight: 700; font-family: monospace; }
        @media print {
            .no-print { display: none !important; }
            @page { size: landscape; margin: 8mm; }
            body { padding: 0 !important; }
        }
    </style>
</head>
<body class="p-4">
    <div class="no-print mb-3 text-end">
        <button onclick="window.print()" class="btn btn-primary btn-sm px-3 shadow-sm">
            <i class="bi bi-printer"></i> Cetak / Simpan PDF
        </button>
        <button onclick="window.close()" class="btn btn-secondary btn-sm px-3 ms-1 shadow-sm">
            Tutup
        </button>
    </div>

    {{-- HEADER PERUSAHAAN --}}
    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
        <div>
            <h4 class="fw-bold mb-1 text-dark text-uppercase" style="letter-spacing: 0.5px;">
                {{ auth()->user()->tenant->name ?? config('app.name', 'ERP Marketplace') }}
            </h4>
            <div class="text-muted" style="font-size: 12px;">Laporan Buku Kas &amp; Mutasi Keuangan (Masuk &amp; Keluar)</div>
        </div>
        <div class="text-end">
            <table style="font-size: 10.5px;" class="text-start">
                <tr>
                    <td class="text-muted pe-2">Periode:</td>
                    <td class="fw-bold">{{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} s/d {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}</td>
                </tr>
                <tr>
                    <td class="text-muted pe-2">Akun:</td>
                    <td class="fw-bold">{{ $selectedAccountLabel }}</td>
                </tr>
                <tr>
                    <td class="text-muted pe-2">Dicetak:</td>
                    <td>{{ now()->format('d/m/Y H:i') }} ({{ auth()->user()->name }})</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- RINGKASAN SALDO (KPI) --}}
    <div class="row g-2 mb-3">
        <div class="col">
            <div class="kpi-box">
                <div class="kpi-title">Saldo Awal</div>
                <div class="kpi-val text-dark">Rp {{ number_format($beginningBalance, 0, ',', '.') }}</div>
            </div>
        </div>
        <div class="col">
            <div class="kpi-box">
                <div class="kpi-title text-success">Total Uang Masuk</div>
                <div class="kpi-val text-success">+ Rp {{ number_format($totalInflow, 0, ',', '.') }}</div>
            </div>
        </div>
        <div class="col">
            <div class="kpi-box">
                <div class="kpi-title text-danger">Total Uang Keluar</div>
                <div class="kpi-val text-danger">- Rp {{ number_format($totalOutflow, 0, ',', '.') }}</div>
            </div>
        </div>
        <div class="col">
            <div class="kpi-box">
                <div class="kpi-title">Arus Kas Bersih (Net)</div>
                <div class="kpi-val {{ $netCashFlow >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ $netCashFlow >= 0 ? '+' : '-' }}Rp {{ number_format(abs($netCashFlow), 0, ',', '.') }}
                </div>
            </div>
        </div>
        <div class="col">
            <div class="kpi-box border-primary">
                <div class="kpi-title text-primary">Saldo Akhir</div>
                <div class="kpi-val text-primary">Rp {{ number_format($endingBalance, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    {{-- TABEL MUTASI --}}
    <table class="table-print">
        <thead>
            <tr>
                <th class="text-center" style="width: 30px;">No</th>
                <th style="width: 75px;">Tanggal</th>
                <th style="width: 110px;">No. Referensi</th>
                <th style="width: 120px;">Jenis Transaksi</th>
                <th style="width: 120px;">Akun Kas / Bank</th>
                <th style="width: 110px;">Kategori</th>
                <th>Keterangan / Uraian</th>
                <th class="text-end" style="width: 110px;">Masuk (Rp)</th>
                <th class="text-end" style="width: 110px;">Keluar (Rp)</th>
                <th class="text-end" style="width: 120px;">Saldo Berjalan (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($mutations as $idx => $row)
                <tr>
                    <td class="text-center text-muted">{{ $idx + 1 }}</td>
                    <td>{{ $row['date_formatted'] }}</td>
                    <td class="font-monospace fw-semibold">{{ $row['reference'] }}</td>
                    <td>{{ $row['type_label'] }}</td>
                    <td>{{ $row['account_label'] }}</td>
                    <td>{{ $row['category_label'] }}</td>
                    <td>{{ $row['description'] }}</td>
                    <td class="text-end font-monospace {{ $row['inflow'] > 0 ? 'text-success fw-bold' : 'text-muted' }}">
                        {{ $row['inflow'] > 0 ? '+ ' . number_format($row['inflow'], 0, ',', '.') : '-' }}
                    </td>
                    <td class="text-end font-monospace {{ $row['outflow'] > 0 ? 'text-danger fw-bold' : 'text-muted' }}">
                        {{ $row['outflow'] > 0 ? '- ' . number_format($row['outflow'], 0, ',', '.') : '-' }}
                    </td>
                    <td class="text-end font-monospace fw-bold">
                        Rp {{ number_format($row['running_balance'], 0, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center py-4 text-muted">
                        Tidak ada transaksi mutasi keuangan pada periode ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="background-color: #f8fafc; font-weight: bold;">
                <td colspan="7" class="text-end text-uppercase">Total Mutasi Periode Ini</td>
                <td class="text-end font-monospace text-success">+ Rp {{ number_format($totalInflow, 0, ',', '.') }}</td>
                <td class="text-end font-monospace text-danger">- Rp {{ number_format($totalOutflow, 0, ',', '.') }}</td>
                <td class="text-end font-monospace text-primary">Rp {{ number_format($endingBalance, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- TANDA TANGAN PENGESAHAN --}}
    <div class="row mt-4 pt-3 text-center" style="page-break-inside: avoid;">
        <div class="col-4">
            <div class="text-muted small mb-5">Dibuat Oleh,</div>
            <div class="fw-bold border-top pt-1 mx-auto" style="width: 180px;">( Staff Keuangan / Kasir )</div>
        </div>
        <div class="col-4">
            <div class="text-muted small mb-5">Diperiksa Oleh,</div>
            <div class="fw-bold border-top pt-1 mx-auto" style="width: 180px;">( Accounting / Supervisor )</div>
        </div>
        <div class="col-4">
            <div class="text-muted small mb-5">Disetujui Oleh,</div>
            <div class="fw-bold border-top pt-1 mx-auto" style="width: 180px;">( Pimpinan / Owner )</div>
        </div>
    </div>
</body>
</html>
