@extends('layouts.app')
@section('title', 'Laporan Mutasi Keuangan')
@section('page-title', 'Laporan Detail Mutasi Masuk & Keluar Keuangan')

@section('content')
<div class="container-fluid px-0">
    {{-- FILTER BAR --}}
    <div class="card border rounded shadow-sm bg-white mb-3">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('finance.mutations.index') }}">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-2">
                        <label class="form-label form-label-sm fw-semibold mb-1 text-muted small">Dari Tanggal</label>
                        <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label form-label-sm fw-semibold mb-1 text-muted small">Sampai Tanggal</label>
                        <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label form-label-sm fw-semibold mb-1 text-muted small">Akun Kas / Bank</label>
                        <select name="account" class="form-select form-select-sm">
                            <option value="all" {{ $account === 'all' ? 'selected' : '' }}>Semua Akun Kas / Bank (Master)</option>
                            @if(isset($bankAccounts) && $bankAccounts->isNotEmpty())
                                @foreach($bankAccounts as $bank)
                                    <option value="{{ $bank->bank_name }}" {{ strcasecmp((string)$account, (string)$bank->bank_name) === 0 || (string)$account === (string)$bank->id ? 'selected' : '' }}>
                                        {{ $bank->bank_name }} {{ $bank->account_number ? '('.$bank->account_number.')' : '' }} {{ $bank->account_name ? '- '.$bank->account_name : '' }}
                                    </option>
                                @endforeach
                            @else
                                <option value="kas_besar" {{ $account === 'kas_besar' ? 'selected' : '' }}>Kas Besar</option>
                                <option value="kas_kecil" {{ $account === 'kas_kecil' ? 'selected' : '' }}>Kas Kecil</option>
                            @endif
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label form-label-sm fw-semibold mb-1 text-muted small">Arah Mutasi</label>
                        <select name="direction" class="form-select form-select-sm">
                            <option value="all" {{ $direction === 'all' ? 'selected' : '' }}>Semua (Masuk & Keluar)</option>
                            <option value="in" {{ $direction === 'in' ? 'selected' : '' }}>Uang Masuk (+)</option>
                            <option value="out" {{ $direction === 'out' ? 'selected' : '' }}>Uang Keluar (-)</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label form-label-sm fw-semibold mb-1 text-muted small">Jenis Sumber</label>
                        <select name="source_type" class="form-select form-select-sm">
                            <option value="all" {{ $sourceType === 'all' ? 'selected' : '' }}>Semua Sumber</option>
                            <option value="income" {{ $sourceType === 'income' ? 'selected' : '' }}>Pemasukan Lain</option>
                            <option value="expense" {{ $sourceType === 'expense' ? 'selected' : '' }}>Pengeluaran Operasional</option>
                            <option value="transfer" {{ $sourceType === 'transfer' ? 'selected' : '' }}>Transfer Antar Kas</option>
                            <option value="offline_sale" {{ $sourceType === 'offline_sale' ? 'selected' : '' }}>Penjualan POS</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label form-label-sm fw-semibold mb-1 text-muted small">Pencarian</label>
                        <input type="text" name="search" value="{{ $search }}" class="form-control form-control-sm" placeholder="No. ref / keterangan...">
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                    <div class="text-muted small">
                        <i class="bi bi-info-circle me-1 text-primary"></i> Menampilkan mutasi untuk: <strong>{{ $selectedAccountLabel }}</strong>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('finance.mutations.index') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                        </a>
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="bi bi-funnel me-1"></i> Terapkan Filter
                        </button>
                        <a href="{{ route('finance.mutations.print', request()->query()) }}" target="_blank" class="btn btn-sm btn-outline-dark">
                            <i class="bi bi-printer me-1"></i> Cetak Laporan
                        </a>
                        <a href="{{ route('finance.mutations.export', request()->query()) }}" class="btn btn-sm btn-success">
                            <i class="bi bi-file-earmark-excel me-1"></i> Export CSV
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- SUMMARY KPI CARDS --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg">
            <div class="card border rounded shadow-sm bg-white h-100 border-start border-secondary border-4">
                <div class="card-body py-2 px-3">
                    <div class="text-muted small">Saldo Awal (Sebelum Periode)</div>
                    <div class="fw-bold fs-6 font-monospace text-dark mt-1">
                        Rp {{ number_format($beginningBalance, 0, ',', '.') }}
                    </div>
                    <small class="text-muted" style="font-size:0.75rem;">Per {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }}</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg">
            <div class="card border rounded shadow-sm bg-white h-100 border-start border-success border-4">
                <div class="card-body py-2 px-3">
                    <div class="text-success small fw-semibold">Total Uang Masuk</div>
                    <div class="fw-bold fs-6 font-monospace text-success mt-1">
                        + Rp {{ number_format($totalInflow, 0, ',', '.') }}
                    </div>
                    <small class="text-muted" style="font-size:0.75rem;">{{ $mutations->where('inflow', '>', 0)->count() }} transaksi masuk</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg">
            <div class="card border rounded shadow-sm bg-white h-100 border-start border-danger border-4">
                <div class="card-body py-2 px-3">
                    <div class="text-danger small fw-semibold">Total Uang Keluar</div>
                    <div class="fw-bold fs-6 font-monospace text-danger mt-1">
                        - Rp {{ number_format($totalOutflow, 0, ',', '.') }}
                    </div>
                    <small class="text-muted" style="font-size:0.75rem;">{{ $mutations->where('outflow', '>', 0)->count() }} transaksi keluar</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg">
            <div class="card border rounded shadow-sm bg-white h-100 border-start {{ $netCashFlow >= 0 ? 'border-success' : 'border-danger' }} border-4">
                <div class="card-body py-2 px-3">
                    <div class="small fw-semibold {{ $netCashFlow >= 0 ? 'text-success' : 'text-danger' }}">Arus Kas Bersih (Net)</div>
                    <div class="fw-bold fs-6 font-monospace mt-1 {{ $netCashFlow >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $netCashFlow >= 0 ? '+' : '-' }}Rp {{ number_format(abs($netCashFlow), 0, ',', '.') }}
                    </div>
                    <small class="text-muted" style="font-size:0.75rem;">Masuk dikurangi Keluar</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg">
            <div class="card border rounded shadow-sm bg-white h-100 border-start border-primary border-4">
                <div class="card-body py-2 px-3">
                    <div class="text-primary small fw-semibold">Saldo Akhir Periode</div>
                    <div class="fw-bold fs-6 font-monospace text-primary mt-1">
                        Rp {{ number_format($endingBalance, 0, ',', '.') }}
                    </div>
                    <small class="text-muted" style="font-size:0.75rem;">Per {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}</small>
                </div>
            </div>
        </div>
    </div>

    {{-- MUTATION TABLE --}}
    <div class="card border rounded shadow-sm bg-white mb-3">
        <div class="card-header bg-white py-3 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h6 class="fw-bold mb-0 text-dark">
                <i class="bi bi-journal-text text-primary me-2"></i>
                Buku Mutasi Kas & Keuangan: {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} s/d {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}
            </h6>
            <span class="badge bg-light text-dark border small">Total {{ $mutations->count() }} Transaksi</span>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0" style="font-size: 0.85rem;">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width: 40px;">No</th>
                        <th style="width: 110px;">Tanggal</th>
                        <th style="width: 140px;">No. Referensi</th>
                        <th style="width: 160px;">Jenis & Akun</th>
                        <th style="width: 140px;">Kategori</th>
                        <th>Keterangan</th>
                        <th class="text-end" style="width: 130px;">Masuk (Rp)</th>
                        <th class="text-end" style="width: 130px;">Keluar (Rp)</th>
                        <th class="text-end" style="width: 140px;">Saldo Berjalan (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mutations as $index => $row)
                        <tr>
                            <td class="text-center text-muted">{{ $index + 1 }}</td>
                            <td>{{ $row['date_formatted'] }}</td>
                            <td>
                                <span class="font-monospace fw-semibold">{{ $row['reference'] }}</span>
                            </td>
                            <td>
                                <span class="badge {{ $row['type_badge'] }} mb-1 d-inline-block">{{ $row['type_label'] }}</span>
                                <div class="text-muted small" style="font-size: 0.75rem;">{{ $row['account_label'] }}</div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $row['category_label'] }}</span>
                            </td>
                            <td>
                                <div class="text-wrap">{{ $row['description'] }}</div>
                            </td>
                            <td class="text-end font-monospace {{ $row['inflow'] > 0 ? 'text-success fw-bold' : 'text-muted' }}">
                                {{ $row['inflow'] > 0 ? '+ ' . number_format($row['inflow'], 0, ',', '.') : '-' }}
                            </td>
                            <td class="text-end font-monospace {{ $row['outflow'] > 0 ? 'text-danger fw-bold' : 'text-muted' }}">
                                {{ $row['outflow'] > 0 ? '- ' . number_format($row['outflow'], 0, ',', '.') : '-' }}
                            </td>
                            <td class="text-end font-monospace fw-bold {{ $row['running_balance'] >= 0 ? 'text-dark' : 'text-danger' }}">
                                Rp {{ number_format($row['running_balance'], 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                Tidak ada transaksi mutasi kas/keuangan pada periode atau filter yang dipilih.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="6" class="text-end text-uppercase">Total Periode Ini</td>
                        <td class="text-end font-monospace text-success">+ Rp {{ number_format($totalInflow, 0, ',', '.') }}</td>
                        <td class="text-end font-monospace text-danger">- Rp {{ number_format($totalOutflow, 0, ',', '.') }}</td>
                        <td class="text-end font-monospace text-primary">Rp {{ number_format($endingBalance, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
