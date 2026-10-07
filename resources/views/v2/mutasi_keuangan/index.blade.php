@extends('v2.layouts.app')

@section('title', 'Mutasi Keuangan')

@push('styles')
<style>
/* ─── Mutasi Keuangan V2 Custom Styles (Matching Barang Keluar) ─── */
.mks-kpi-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 14px 16px;
    position: relative;
    overflow: hidden;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.mks-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}
.mks-kpi-title {
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: #6b7280;
    margin-bottom: 4px;
}
.mks-kpi-value {
    font-size: 1.15rem;
    font-weight: 700;
    line-height: 1.2;
}
.mks-kpi-sub {
    font-size: 0.7rem;
    color: #9ca3af;
    margin-top: 4px;
}
.mks-kpi-icon {
    position: absolute;
    right: 12px;
    bottom: 12px;
    font-size: 2rem;
    opacity: 0.12;
    pointer-events: none;
}

.ref-code {
    font-weight: 700;
    color: #2563eb;
    background: #eff6ff;
    padding: 2px 7px;
    border-radius: 6px;
    font-size: 0.75rem;
    display: inline-block;
}

.amount-inflow {
    font-weight: 700;
    color: #16a34a;
}

.amount-outflow {
    font-weight: 700;
    color: #dc2626;
}

.amount-balance {
    font-weight: 700;
    color: #0f172a;
}
</style>
@endpush

@section('content')

{{-- ── Page Header ── --}}
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2 mb-0">
            <i class="bi bi-journal-text text-primary fs-5"></i> Mutasi Keuangan
        </h1>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('v2.mutasi_keuangan.index') }}" class="btn btn-sm py-1.5 px-3 shadow-sm fw-semibold" style="background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; border-radius:8px;" title="Refresh Data">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </a>
        <a href="{{ route('v2.mutasi_keuangan.print', request()->query()) }}" target="_blank" class="btn btn-sm py-1.5 px-3 shadow-sm fw-semibold text-white" style="background:#1e293b; border:none; border-radius:8px;">
            <i class="bi bi-printer me-1"></i> Cetak Laporan
        </a>
        <button type="button" class="btn btn-sm py-1.5 px-3 shadow-sm fw-semibold text-white" style="background:#16a34a; border:none; border-radius:8px;" data-bs-toggle="modal" data-bs-target="#addIncomeModal">
            <i class="bi bi-plus-lg me-1"></i> Input Pemasukan
        </button>
        <button type="button" class="btn btn-sm py-1.5 px-3 shadow-sm fw-semibold text-white" style="background:#dc2626; border:none; border-radius:8px;" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
            <i class="bi bi-dash-lg me-1"></i> Input Pengeluaran
        </button>
        <button type="button" class="btn btn-sm py-1.5 px-3 shadow-sm fw-semibold text-white" style="background:#d97706; border:none; border-radius:8px;" data-bs-toggle="modal" data-bs-target="#addTransferModal">
            <i class="bi bi-arrow-left-right me-1"></i> Transfer Dana
        </button>
    </div>
</div>

{{-- ── Alert Messages ── --}}
@foreach(['success','error','info'] as $type)
    @if(session($type))
        <div class="alert alert-{{ $type === 'error' ? 'danger' : ($type === 'info' ? 'info' : 'success') }} alert-dismissible fade show mb-3 border-0 shadow-sm" role="alert" style="border-radius:10px;">
            <i class="bi bi-{{ $type === 'error' ? 'exclamation-triangle' : ($type === 'info' ? 'info-circle' : 'check-circle') }} me-2"></i>
            {!! session($type) !!}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
@endforeach

{{-- ── KPI Summary Cards ── --}}
<div class="row g-2.5 mb-3">
    <!-- Saldo Awal -->
    <div class="col-12 col-sm-6 col-lg">
        <div class="mks-kpi-card border-start border-secondary border-3">
            <div class="mks-kpi-title text-secondary">Saldo Awal</div>
            <div class="mks-kpi-value text-dark">
                Rp {{ number_format($beginningBalance, 0, ',', '.') }}
            </div>
            <div class="mks-kpi-sub">Per {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }}</div>
            <i class="bi bi-wallet2 mks-kpi-icon text-secondary"></i>
        </div>
    </div>
    <!-- Total Uang Masuk -->
    <div class="col-12 col-sm-6 col-lg">
        <div class="mks-kpi-card border-start border-success border-3">
            <div class="mks-kpi-title text-success">Total Uang Masuk</div>
            <div class="mks-kpi-value text-success">
                + Rp {{ number_format($totalInflow, 0, ',', '.') }}
            </div>
            <div class="mks-kpi-sub">{{ $mutations->where('inflow', '>', 0)->count() }} Transaksi Masuk</div>
            <i class="bi bi-arrow-down-left-circle mks-kpi-icon text-success"></i>
        </div>
    </div>
    <!-- Total Uang Keluar -->
    <div class="col-12 col-sm-6 col-lg">
        <div class="mks-kpi-card border-start border-danger border-3">
            <div class="mks-kpi-title text-danger">Total Uang Keluar</div>
            <div class="mks-kpi-value text-danger">
                - Rp {{ number_format($totalOutflow, 0, ',', '.') }}
            </div>
            <div class="mks-kpi-sub">{{ $mutations->where('outflow', '>', 0)->count() }} Transaksi Keluar</div>
            <i class="bi bi-arrow-up-right-circle mks-kpi-icon text-danger"></i>
        </div>
    </div>
    <!-- Net Cashflow -->
    <div class="col-12 col-sm-6 col-lg">
        <div class="mks-kpi-card border-start {{ $netCashFlow >= 0 ? 'border-success' : 'border-danger' }} border-3">
            <div class="mks-kpi-title {{ $netCashFlow >= 0 ? 'text-success' : 'text-danger' }}">Arus Kas Bersih</div>
            <div class="mks-kpi-value {{ $netCashFlow >= 0 ? 'text-success' : 'text-danger' }}">
                {{ $netCashFlow >= 0 ? '+' : '-' }}Rp {{ number_format(abs($netCashFlow), 0, ',', '.') }}
            </div>
            <div class="mks-kpi-sub">Masuk dikurangi Keluar</div>
            <i class="bi bi-activity mks-kpi-icon {{ $netCashFlow >= 0 ? 'text-success' : 'text-danger' }}"></i>
        </div>
    </div>
    <!-- Saldo Akhir -->
    <div class="col-12 col-lg">
        <div class="mks-kpi-card border-start border-primary border-3">
            <div class="mks-kpi-title text-primary">Saldo Akhir Periode</div>
            <div class="mks-kpi-value text-primary">
                Rp {{ number_format($endingBalance, 0, ',', '.') }}
            </div>
            <div class="mks-kpi-sub">Per {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}</div>
            <i class="bi bi-bank mks-kpi-icon text-primary"></i>
        </div>
    </div>
</div>

{{-- ── Filter Section ── --}}
<div class="v2-card p-3 mb-3 shadow-sm">
    <form method="GET" action="{{ route('v2.mutasi_keuangan.index') }}" class="row g-2 align-items-end">
        <div class="col-6 col-md-4 col-xl-2">
            <label class="form-label small fw-semibold text-muted mb-1" style="font-size: 0.72rem;">Dari Tanggal</label>
            <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control form-control-sm px-2" style="font-size: 0.78rem;">
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <label class="form-label small fw-semibold text-muted mb-1" style="font-size: 0.72rem;">Sampai Tanggal</label>
            <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control form-control-sm px-2" style="font-size: 0.78rem;">
        </div>
        <div class="col-12 col-md-4 col-xl-2">
            <label class="form-label small fw-semibold text-muted mb-1" style="font-size: 0.72rem;">Akun Kas / Bank</label>
            <select name="account" class="form-select form-select-sm" style="font-size: 0.78rem;">
                <option value="all" {{ $account === 'all' ? 'selected' : '' }}>Semua Akun Kas/Bank</option>
                @if(isset($bankAccounts) && $bankAccounts->isNotEmpty())
                    @foreach($bankAccounts as $bank)
                        <option value="{{ $bank->bank_name }}" {{ strcasecmp((string)$account, (string)$bank->bank_name) === 0 || (string)$account === (string)$bank->id ? 'selected' : '' }}>
                            {{ $bank->bank_name }} {{ $bank->account_number ? '('.$bank->account_number.')' : '' }}
                        </option>
                    @endforeach
                @else
                    <option value="kas_besar" {{ $account === 'kas_besar' ? 'selected' : '' }}>Kas Besar</option>
                    <option value="kas_kecil" {{ $account === 'kas_kecil' ? 'selected' : '' }}>Kas Kecil</option>
                @endif
            </select>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <label class="form-label small fw-semibold text-muted mb-1" style="font-size: 0.72rem;">Kategori Keuangan</label>
            <select name="category" class="form-select form-select-sm" style="font-size: 0.78rem;">
                <option value="all" {{ ($category ?? 'all') === 'all' ? 'selected' : '' }}>Semua Kategori</option>
                <option value="transfer" {{ ($category ?? '') === 'transfer' ? 'selected' : '' }}>-- Transfer Internal --</option>
                @if(isset($allCategories) && $allCategories->isNotEmpty())
                    @foreach($allCategories as $cat)
                        <option value="{{ $cat->code }}" {{ ($category ?? '') === $cat->code ? 'selected' : '' }}>
                            {{ $cat->name }} ({{ strtoupper($cat->type) }})
                        </option>
                    @endforeach
                @else
                    <option value="salary" {{ ($category ?? '') === 'salary' ? 'selected' : '' }}>Gaji Karyawan</option>
                    <option value="rent" {{ ($category ?? '') === 'rent' ? 'selected' : '' }}>Sewa Tempat</option>
                    <option value="utilities" {{ ($category ?? '') === 'utilities' ? 'selected' : '' }}>Utilitas & Operasional</option>
                    <option value="pembelian_supplier" {{ ($category ?? '') === 'pembelian_supplier' ? 'selected' : '' }}>Bayar Hutang Supplier</option>
                    <option value="investment" {{ ($category ?? '') === 'investment' ? 'selected' : '' }}>Investasi / Modal</option>
                    <option value="other" {{ ($category ?? '') === 'other' ? 'selected' : '' }}>Lain-lain</option>
                @endif
            </select>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <label class="form-label small fw-semibold text-muted mb-1" style="font-size: 0.72rem;">Arah Mutasi</label>
            <select name="direction" class="form-select form-select-sm" style="font-size: 0.78rem;">
                <option value="all" {{ $direction === 'all' ? 'selected' : '' }}>Semua (Masuk/Keluar)</option>
                <option value="in" {{ $direction === 'in' ? 'selected' : '' }}>Uang Masuk (+)</option>
                <option value="out" {{ $direction === 'out' ? 'selected' : '' }}>Uang Keluar (-)</option>
            </select>
        </div>
        <!-- Action Buttons Inline -->
        <div class="col-12 col-md-4 col-xl-2 d-flex gap-1">
            <button type="submit" class="btn btn-sm text-white fw-semibold flex-grow-1" style="background:#1e293b; border:none; height:31px; font-size:0.78rem;" title="Terapkan Filter">
                <i class="bi bi-funnel me-1"></i> Filter
            </button>
            <a href="{{ route('v2.mutasi_keuangan.index') }}" class="btn btn-sm text-white fw-semibold d-flex align-items-center justify-content-center px-2.5" style="background:#64748b; border:none; height:31px;" title="Reset Filter">
                <i class="bi bi-arrow-counterclockwise"></i>
            </a>
        </div>
    </form>
</div>

{{-- ── Data Table Section ── --}}
<div class="v2-card shadow-sm overflow-hidden mb-3">
    <div class="py-2.5 px-3 bg-white border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-journals text-primary"></i>
            <span class="fw-bold text-dark" style="font-size:0.85rem;">
                Buku Mutasi Kas & Keuangan: {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} s/d {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}
            </span>
        </div>
        <span class="badge bg-light text-dark border py-1.5 px-3 rounded-pill" style="font-size:0.72rem; font-weight:600;">
            Total {{ $mutations->count() }} Transaksi
        </span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 0.82rem;">
            <thead class="bg-light text-muted">
                <tr>
                    <th class="ps-3 py-2.5" style="width: 45px;">NO</th>
                    <th class="py-2.5" style="width: 200px;">TRANSAKSI & AKUN</th>
                    <th class="py-2.5" style="width: 130px;">KATEGORI</th>
                    <th class="py-2.5">KETERANGAN</th>
                    <th class="text-end py-2.5" style="width: 130px;">MASUK (RP)</th>
                    <th class="text-end py-2.5" style="width: 130px;">KELUAR (RP)</th>
                    <th class="text-end py-2.5" style="width: 140px;">SALDO BERJALAN</th>
                    <th class="text-end pe-3 py-2.5" style="width: 100px;">AKSI</th>
                </tr>
            </thead>
            <tbody>
                @forelse($mutations as $index => $row)
                    <tr>
                        <td class="ps-3 py-2.5 text-muted" style="font-size: 0.75rem;">{{ $index + 1 }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-1.5 mb-1 flex-wrap">
                                <span class="ref-code">{{ $row['reference'] }}</span>
                                <span class="text-muted fw-medium" style="font-size:0.75rem;">{{ $row['date_formatted'] }}</span>
                            </div>
                            <div class="text-dark fw-semibold text-truncate" style="font-size: 0.74rem; max-width: 220px;" title="{{ $row['account_label'] }}">
                                <i class="bi bi-wallet2 me-1 text-secondary"></i>{{ $row['account_label'] }}
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border px-2 py-1" style="font-size:0.7rem; font-weight:600;">
                                {{ $row['category_label'] }}
                            </span>
                        </td>
                        <td>
                            <div class="text-wrap" style="font-size: 0.78rem; line-height: 1.35;">
                                {{ $row['description'] }}
                            </div>
                        </td>
                        <td class="text-end">
                            @if($row['inflow'] > 0)
                                <span class="amount-inflow">+ {{ number_format($row['inflow'], 0, ',', '.') }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if($row['outflow'] > 0)
                                <span class="amount-outflow">- {{ number_format($row['outflow'], 0, ',', '.') }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <span class="amount-balance {{ $row['running_balance'] < 0 ? 'text-danger' : '' }} font-monospace">
                                Rp {{ number_format($row['running_balance'], 0, ',', '.') }}
                            </span>
                        </td>
                        <td class="text-end pe-3">
                            <div class="d-flex justify-content-end gap-1">
                                @if($row['model_type'] === 'income')
                                    <button type="button" class="btn btn-sm px-2 py-0.5 edit-income-btn"
                                        style="background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe;"
                                        title="Edit Pemasukan"
                                        data-bs-toggle="modal" data-bs-target="#editIncomeModal"
                                        data-id="{{ $row['id'] }}"
                                        data-title="{{ $row['raw_title'] }}"
                                        data-category="{{ $row['raw_category'] }}"
                                        data-payment_destination="{{ $row['raw_payment_destination'] }}"
                                        data-amount="{{ $row['raw_amount'] }}"
                                        data-income_date="{{ $row['raw_income_date'] }}"
                                        data-description="{{ $row['raw_description'] }}">
                                        <i class="bi bi-pencil" style="font-size:0.72rem;"></i>
                                    </button>
                                    <form action="{{ route('finance.incomes.destroy', $row['id']) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus catatan pemasukan {{ $row['reference'] }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
                                        <button type="submit" class="btn btn-sm px-2 py-0.5" style="background:#dc2626; color:#ffffff; border:none;" title="Hapus Pemasukan">
                                            <i class="bi bi-trash" style="font-size:0.72rem;"></i>
                                        </button>
                                    </form>
                                @elseif($row['model_type'] === 'expense')
                                    <button type="button" class="btn btn-sm px-2 py-0.5 edit-expense-btn"
                                        style="background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe;"
                                        title="Edit Pengeluaran"
                                        data-bs-toggle="modal" data-bs-target="#editExpenseModal"
                                        data-id="{{ $row['id'] }}"
                                        data-title="{{ $row['raw_title'] }}"
                                        data-category="{{ $row['raw_category'] }}"
                                        data-payment_source="{{ $row['raw_payment_source'] }}"
                                        data-amount="{{ $row['raw_amount'] }}"
                                        data-expense_date="{{ $row['raw_expense_date'] }}"
                                        data-employee_id="{{ $row['raw_employee_id'] }}"
                                        data-description="{{ $row['raw_description'] }}">
                                        <i class="bi bi-pencil" style="font-size:0.72rem;"></i>
                                    </button>
                                    <form action="{{ route('finance.expenses.destroy', $row['id']) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus catatan pengeluaran {{ $row['reference'] }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
                                        <button type="submit" class="btn btn-sm px-2 py-0.5" style="background:#dc2626; color:#ffffff; border:none;" title="Hapus Pengeluaran">
                                            <i class="bi bi-trash" style="font-size:0.72rem;"></i>
                                        </button>
                                    </form>
                                @elseif($row['model_type'] === 'transfer')
                                    <button type="button" class="btn btn-sm px-2 py-0.5 edit-transfer-btn"
                                        style="background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe;"
                                        title="Edit Transfer"
                                        data-bs-toggle="modal" data-bs-target="#editTransferModal"
                                        data-id="{{ $row['id'] }}"
                                        data-source="{{ $row['raw_source'] }}"
                                        data-destination="{{ $row['raw_destination'] }}"
                                        data-amount="{{ $row['raw_amount'] }}"
                                        data-transfer_date="{{ $row['raw_transfer_date'] }}"
                                        data-description="{{ $row['raw_description'] }}">
                                        <i class="bi bi-pencil" style="font-size:0.72rem;"></i>
                                    </button>
                                    <form action="{{ route('finance.transfers.destroy', $row['id']) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus catatan transfer dana {{ $row['reference'] }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
                                        <button type="submit" class="btn btn-sm px-2 py-0.5" style="background:#dc2626; color:#ffffff; border:none;" title="Hapus Transfer">
                                            <i class="bi bi-trash" style="font-size:0.72rem;"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-3 d-block mb-1 text-secondary opacity-50"></i>
                            Tidak ada transaksi mutasi kas/keuangan pada periode atau filter yang dipilih.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot class="bg-light fw-bold" style="border-top:2px solid #cbd5e1;">
                <tr>
                    <td colspan="4" class="text-end text-uppercase ps-3 py-2.5" style="font-size:0.75rem; letter-spacing:0.04em;">Total Periode Ini</td>
                    <td class="text-end py-2.5 amount-inflow">+ {{ number_format($totalInflow, 0, ',', '.') }}</td>
                    <td class="text-end py-2.5 amount-outflow">- {{ number_format($totalOutflow, 0, ',', '.') }}</td>
                    <td class="text-end py-2.5 amount-balance text-primary font-monospace">Rp {{ number_format($endingBalance, 0, ',', '.') }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

{{-- ── MODAL INPUT PEMASUKAN ── --}}
<div class="modal fade" id="addIncomeModal" tabindex="-1" aria-labelledby="addIncomeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('finance.incomes.store') }}" method="POST" class="modal-content border-0 shadow-lg" style="border-radius:12px; overflow:hidden;">
            @csrf
            <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
            <div class="modal-header bg-success text-white py-3">
                <h5 class="modal-title fw-bold fs-6 mb-0" id="addIncomeModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>Input Pemasukan Kas / Bank
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="v2-form-label">Judul / Sumber Pemasukan <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control form-control-sm v2-input" required placeholder="Contoh: Suntikan Modal / Pendapatan Lain">
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="v2-form-label">Kategori <span class="text-danger">*</span></label>
                        <select name="category" class="form-select form-select-sm v2-input" required>
                            @if(isset($incomeCategories) && $incomeCategories->isNotEmpty())
                                @foreach($incomeCategories as $cat)
                                    <option value="{{ $cat->code }}">{{ $cat->name }}</option>
                                @endforeach
                            @else
                                <option value="investment">Investasi / Modal</option>
                                <option value="refund">Refund / Pengembalian</option>
                                <option value="services">Jasa / Layanan</option>
                                <option value="other">Lain-lain</option>
                            @endif
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="v2-form-label">Kas / Bank Tujuan <span class="text-danger">*</span></label>
                        <select name="payment_destination" class="form-select form-select-sm v2-input" required>
                            @if(isset($bankAccounts) && $bankAccounts->isNotEmpty())
                                @foreach($bankAccounts as $bank)
                                    <option value="{{ $bank->bank_name }}" {{ strcasecmp((string)$account, (string)$bank->bank_name) === 0 ? 'selected' : '' }}>
                                        {{ $bank->bank_name }} {{ $bank->account_number ? '('.$bank->account_number.')' : '' }}
                                    </option>
                                @endforeach
                            @else
                                <option value="kas_besar">Kas Besar (Utama)</option>
                                <option value="kas_kecil">Kas Kecil (Operasional)</option>
                            @endif
                        </select>
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="v2-form-label">Nominal (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" min="0" step="any" class="form-control form-control-sm v2-input font-monospace" required placeholder="0">
                    </div>
                    <div class="col-md-6">
                        <label class="v2-form-label">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" name="income_date" value="{{ date('Y-m-d') }}" class="form-control form-control-sm v2-input" required>
                    </div>
                </div>
                <div class="mb-0">
                    <label class="v2-form-label">Keterangan Tambahan</label>
                    <textarea name="description" rows="3" class="form-control form-control-sm v2-input" placeholder="Catatan tambahan (opsional)..."></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light py-2.5 px-4 border-top">
                <button type="button" class="btn btn-sm btn-v2-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-sm btn-success px-4 fw-semibold">
                    <i class="bi bi-check-circle me-1"></i> Simpan Pemasukan
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── MODAL EDIT PEMASUKAN ── --}}
<div class="modal fade" id="editIncomeModal" tabindex="-1" aria-labelledby="editIncomeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="editIncomeForm" method="POST" class="modal-content border-0 shadow-lg" style="border-radius:12px; overflow:hidden;">
            @csrf
            @method('PUT')
            <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title fw-bold fs-6 mb-0" id="editIncomeModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>Edit Pemasukan Kas / Bank
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="v2-form-label">Judul / Sumber Pemasukan <span class="text-danger">*</span></label>
                    <input type="text" name="title" id="edit_income_title" class="form-control form-control-sm v2-input" required>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="v2-form-label">Kategori <span class="text-danger">*</span></label>
                        <select name="category" id="edit_income_category" class="form-select form-select-sm v2-input" required>
                            @if(isset($incomeCategories) && $incomeCategories->isNotEmpty())
                                @foreach($incomeCategories as $cat)
                                    <option value="{{ $cat->code }}">{{ $cat->name }}</option>
                                @endforeach
                            @else
                                <option value="investment">Investasi / Modal</option>
                                <option value="refund">Refund / Pengembalian</option>
                                <option value="services">Jasa / Layanan</option>
                                <option value="other">Lain-lain</option>
                            @endif
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="v2-form-label">Kas / Bank Tujuan <span class="text-danger">*</span></label>
                        <select name="payment_destination" id="edit_income_payment_destination" class="form-select form-select-sm v2-input" required>
                            @if(isset($bankAccounts) && $bankAccounts->isNotEmpty())
                                @foreach($bankAccounts as $bank)
                                    <option value="{{ $bank->bank_name }}">
                                        {{ $bank->bank_name }} {{ $bank->account_number ? '('.$bank->account_number.')' : '' }}
                                    </option>
                                @endforeach
                            @else
                                <option value="kas_besar">Kas Besar (Utama)</option>
                                <option value="kas_kecil">Kas Kecil (Operasional)</option>
                            @endif
                        </select>
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="v2-form-label">Nominal (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" id="edit_income_amount" min="0" step="any" class="form-control form-control-sm v2-input font-monospace" required>
                    </div>
                    <div class="col-md-6">
                        <label class="v2-form-label">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" name="income_date" id="edit_income_date" class="form-control form-control-sm v2-input" required>
                    </div>
                </div>
                <div class="mb-0">
                    <label class="v2-form-label">Keterangan Tambahan</label>
                    <textarea name="description" id="edit_income_description" rows="3" class="form-control form-control-sm v2-input"></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light py-2.5 px-4 border-top">
                <button type="button" class="btn btn-sm btn-v2-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-sm btn-v2-primary px-4 fw-semibold">
                    <i class="bi bi-check-circle me-1"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── MODAL INPUT PENGELUARAN ── --}}
<div class="modal fade" id="addExpenseModal" tabindex="-1" aria-labelledby="addExpenseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('finance.expenses.store') }}" method="POST" class="modal-content border-0 shadow-lg" style="border-radius:12px; overflow:hidden;">
            @csrf
            <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
            <div class="modal-header bg-danger text-white py-3">
                <h5 class="modal-title fw-bold fs-6 mb-0" id="addExpenseModalLabel">
                    <i class="bi bi-dash-circle me-2"></i>Input Pengeluaran & Biaya
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="v2-form-label">Judul Pengeluaran / Deskripsi Singkat <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control form-control-sm v2-input" required placeholder="Contoh: Bayar Listrik / Biaya Operasional">
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="v2-form-label">Kategori <span class="text-danger">*</span></label>
                        <select name="category" class="form-select form-select-sm v2-input" required>
                            @if(isset($expenseCategories) && $expenseCategories->isNotEmpty())
                                @foreach($expenseCategories as $cat)
                                    <option value="{{ $cat->code }}">{{ $cat->name }}</option>
                                @endforeach
                            @else
                                <option value="utilities">Utilitas & Operasional</option>
                                <option value="salary">Gaji Karyawan</option>
                                <option value="rent">Sewa Tempat</option>
                                <option value="pembelian_supplier">Bayar Hutang Supplier</option>
                                <option value="other">Lain-lain</option>
                            @endif
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="v2-form-label">Kas / Bank Asal <span class="text-danger">*</span></label>
                        <select name="payment_source" class="form-select form-select-sm v2-input" required>
                            @if(isset($bankAccounts) && $bankAccounts->isNotEmpty())
                                @foreach($bankAccounts as $bank)
                                    <option value="{{ $bank->bank_name }}" {{ strcasecmp((string)$account, (string)$bank->bank_name) === 0 ? 'selected' : '' }}>
                                        {{ $bank->bank_name }} {{ $bank->account_number ? '('.$bank->account_number.')' : '' }}
                                    </option>
                                @endforeach
                            @else
                                <option value="kas_besar">Kas Besar (Utama)</option>
                                <option value="kas_kecil">Kas Kecil (Operasional)</option>
                            @endif
                        </select>
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="v2-form-label">Nominal (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" min="0" step="any" class="form-control form-control-sm v2-input font-monospace" required placeholder="0">
                    </div>
                    <div class="col-md-6">
                        <label class="v2-form-label">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" name="expense_date" value="{{ date('Y-m-d') }}" class="form-control form-control-sm v2-input" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="v2-form-label">Karyawan (Opsional - Penerima/PJ)</label>
                    <select name="employee_id" class="form-select form-select-sm v2-input">
                        <option value="">-- Tanpa Hubungan Karyawan --</option>
                        @if(isset($employees) && $employees->isNotEmpty())
                            @foreach ($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->position }})</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="mb-0">
                    <label class="v2-form-label">Keterangan Tambahan</label>
                    <textarea name="description" rows="3" class="form-control form-control-sm v2-input" placeholder="Catatan tambahan (opsional)..."></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light py-2.5 px-4 border-top">
                <button type="button" class="btn btn-sm btn-v2-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-sm btn-danger px-4 fw-semibold">
                    <i class="bi bi-check-circle me-1"></i> Simpan Pengeluaran
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── MODAL EDIT PENGELUARAN ── --}}
<div class="modal fade" id="editExpenseModal" tabindex="-1" aria-labelledby="editExpenseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="editExpenseForm" method="POST" class="modal-content border-0 shadow-lg" style="border-radius:12px; overflow:hidden;">
            @csrf
            @method('PUT')
            <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title fw-bold fs-6 mb-0" id="editExpenseModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>Edit Pengeluaran Kas / Bank
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="v2-form-label">Judul Pengeluaran / Deskripsi Singkat <span class="text-danger">*</span></label>
                    <input type="text" name="title" id="edit_expense_title" class="form-control form-control-sm v2-input" required>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="v2-form-label">Kategori <span class="text-danger">*</span></label>
                        <select name="category" id="edit_expense_category" class="form-select form-select-sm v2-input" required>
                            @if(isset($expenseCategories) && $expenseCategories->isNotEmpty())
                                @foreach($expenseCategories as $cat)
                                    <option value="{{ $cat->code }}">{{ $cat->name }}</option>
                                @endforeach
                            @else
                                <option value="utilities">Utilitas & Operasional</option>
                                <option value="salary">Gaji Karyawan</option>
                                <option value="rent">Sewa Tempat</option>
                                <option value="pembelian_supplier">Bayar Hutang Supplier</option>
                                <option value="other">Lain-lain</option>
                            @endif
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="v2-form-label">Kas / Bank Asal <span class="text-danger">*</span></label>
                        <select name="payment_source" id="edit_expense_payment_source" class="form-select form-select-sm v2-input" required>
                            @if(isset($bankAccounts) && $bankAccounts->isNotEmpty())
                                @foreach($bankAccounts as $bank)
                                    <option value="{{ $bank->bank_name }}">
                                        {{ $bank->bank_name }} {{ $bank->account_number ? '('.$bank->account_number.')' : '' }}
                                    </option>
                                @endforeach
                            @else
                                <option value="kas_besar">Kas Besar (Utama)</option>
                                <option value="kas_kecil">Kas Kecil (Operasional)</option>
                            @endif
                        </select>
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="v2-form-label">Nominal (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" id="edit_expense_amount" min="0" step="any" class="form-control form-control-sm v2-input font-monospace" required>
                    </div>
                    <div class="col-md-6">
                        <label class="v2-form-label">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" name="expense_date" id="edit_expense_date" class="form-control form-control-sm v2-input" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="v2-form-label">Karyawan (Opsional - Penerima/PJ)</label>
                    <select name="employee_id" id="edit_expense_employee_id" class="form-select form-select-sm v2-input">
                        <option value="">-- Tanpa Hubungan Karyawan --</option>
                        @if(isset($employees) && $employees->isNotEmpty())
                            @foreach ($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->position }})</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="mb-0">
                    <label class="v2-form-label">Keterangan Tambahan</label>
                    <textarea name="description" id="edit_expense_description" rows="3" class="form-control form-control-sm v2-input"></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light py-2.5 px-4 border-top">
                <button type="button" class="btn btn-sm btn-v2-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-sm btn-v2-primary px-4 fw-semibold">
                    <i class="bi bi-check-circle me-1"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── MODAL INPUT TRANSFER ── --}}
<div class="modal fade" id="addTransferModal" tabindex="-1" aria-labelledby="addTransferModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('finance.transfers.store') }}" method="POST" class="modal-content border-0 shadow-lg" style="border-radius:12px; overflow:hidden;">
            @csrf
            <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
            <div class="modal-header bg-warning text-dark py-3">
                <h5 class="modal-title fw-bold fs-6 mb-0" id="addTransferModalLabel">
                    <i class="bi bi-arrow-left-right me-2"></i>Input Transfer Antar Kas / Bank
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="v2-form-label">Kas / Bank Asal <span class="text-danger">*</span></label>
                        <select name="source" class="form-select form-select-sm v2-input" required>
                            @if(isset($bankAccounts) && $bankAccounts->isNotEmpty())
                                @foreach($bankAccounts as $bank)
                                    <option value="{{ $bank->bank_name }}">
                                        {{ $bank->bank_name }} {{ $bank->account_number ? '('.$bank->account_number.')' : '' }}
                                    </option>
                                @endforeach
                            @else
                                <option value="kas_besar">Kas Besar (Utama)</option>
                                <option value="kas_kecil">Kas Kecil (Operasional)</option>
                            @endif
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="v2-form-label">Kas / Bank Tujuan <span class="text-danger">*</span></label>
                        <select name="destination" class="form-select form-select-sm v2-input" required>
                            @if(isset($bankAccounts) && $bankAccounts->isNotEmpty())
                                @foreach($bankAccounts as $bank)
                                    <option value="{{ $bank->bank_name }}">
                                        {{ $bank->bank_name }} {{ $bank->account_number ? '('.$bank->account_number.')' : '' }}
                                    </option>
                                @endforeach
                            @else
                                <option value="kas_kecil">Kas Kecil (Operasional)</option>
                                <option value="kas_besar">Kas Besar (Utama)</option>
                            @endif
                        </select>
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="v2-form-label">Nominal Transfer (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" min="0.01" step="any" class="form-control form-control-sm v2-input font-monospace" required placeholder="0">
                    </div>
                    <div class="col-md-6">
                        <label class="v2-form-label">Tanggal Transfer <span class="text-danger">*</span></label>
                        <input type="date" name="transfer_date" value="{{ date('Y-m-d') }}" class="form-control form-control-sm v2-input" required>
                    </div>
                </div>
                <div class="mb-0">
                    <label class="v2-form-label">Keterangan / Memo Transfer</label>
                    <textarea name="description" rows="3" class="form-control form-control-sm v2-input" placeholder="Contoh: Pengisian petty cash operasional..."></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light py-2.5 px-4 border-top">
                <button type="button" class="btn btn-sm btn-v2-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-sm btn-warning text-dark px-4 fw-semibold">
                    <i class="bi bi-check-circle me-1"></i> Simpan Transfer
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── MODAL EDIT TRANSFER ── --}}
<div class="modal fade" id="editTransferModal" tabindex="-1" aria-labelledby="editTransferModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="editTransferForm" method="POST" class="modal-content border-0 shadow-lg" style="border-radius:12px; overflow:hidden;">
            @csrf
            @method('PUT')
            <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title fw-bold fs-6 mb-0" id="editTransferModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>Edit Transfer Antar Kas / Bank
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="v2-form-label">Kas / Bank Asal <span class="text-danger">*</span></label>
                        <select name="source" id="edit_transfer_source" class="form-select form-select-sm v2-input" required>
                            @if(isset($bankAccounts) && $bankAccounts->isNotEmpty())
                                @foreach($bankAccounts as $bank)
                                    <option value="{{ $bank->bank_name }}">
                                        {{ $bank->bank_name }} {{ $bank->account_number ? '('.$bank->account_number.')' : '' }}
                                    </option>
                                @endforeach
                            @else
                                <option value="kas_besar">Kas Besar (Utama)</option>
                                <option value="kas_kecil">Kas Kecil (Operasional)</option>
                            @endif
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="v2-form-label">Kas / Bank Tujuan <span class="text-danger">*</span></label>
                        <select name="destination" id="edit_transfer_destination" class="form-select form-select-sm v2-input" required>
                            @if(isset($bankAccounts) && $bankAccounts->isNotEmpty())
                                @foreach($bankAccounts as $bank)
                                    <option value="{{ $bank->bank_name }}">
                                        {{ $bank->bank_name }} {{ $bank->account_number ? '('.$bank->account_number.')' : '' }}
                                    </option>
                                @endforeach
                            @else
                                <option value="kas_kecil">Kas Kecil (Operasional)</option>
                                <option value="kas_besar">Kas Besar (Utama)</option>
                            @endif
                        </select>
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="v2-form-label">Nominal Transfer (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" id="edit_transfer_amount" min="0.01" step="any" class="form-control form-control-sm v2-input font-monospace" required>
                    </div>
                    <div class="col-md-6">
                        <label class="v2-form-label">Tanggal Transfer <span class="text-danger">*</span></label>
                        <input type="date" name="transfer_date" id="edit_transfer_date" class="form-control form-control-sm v2-input" required>
                    </div>
                </div>
                <div class="mb-0">
                    <label class="v2-form-label">Keterangan / Memo Transfer</label>
                    <textarea name="description" id="edit_transfer_description" rows="3" class="form-control form-control-sm v2-input"></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light py-2.5 px-4 border-top">
                <button type="button" class="btn btn-sm btn-v2-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-sm btn-v2-primary px-4 fw-semibold">
                    <i class="bi bi-check-circle me-1"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Edit Income
        document.querySelectorAll('.edit-income-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var id = this.getAttribute('data-id');
                var form = document.getElementById('editIncomeForm');
                form.action = "{{ url('finance/incomes') }}/" + id;
                document.getElementById('edit_income_title').value = this.getAttribute('data-title') || '';
                document.getElementById('edit_income_category').value = this.getAttribute('data-category') || '';
                document.getElementById('edit_income_payment_destination').value = this.getAttribute('data-payment_destination') || '';
                document.getElementById('edit_income_amount').value = this.getAttribute('data-amount') || '';
                document.getElementById('edit_income_date').value = this.getAttribute('data-income_date') || '';
                document.getElementById('edit_income_description').value = this.getAttribute('data-description') || '';
            });
        });

        // Edit Expense
        document.querySelectorAll('.edit-expense-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var id = this.getAttribute('data-id');
                var form = document.getElementById('editExpenseForm');
                form.action = "{{ url('finance/expenses') }}/" + id;
                document.getElementById('edit_expense_title').value = this.getAttribute('data-title') || '';
                document.getElementById('edit_expense_category').value = this.getAttribute('data-category') || '';
                document.getElementById('edit_expense_payment_source').value = this.getAttribute('data-payment_source') || '';
                document.getElementById('edit_expense_amount').value = this.getAttribute('data-amount') || '';
                document.getElementById('edit_expense_date').value = this.getAttribute('data-expense_date') || '';
                document.getElementById('edit_expense_employee_id').value = this.getAttribute('data-employee_id') || '';
                document.getElementById('edit_expense_description').value = this.getAttribute('data-description') || '';
            });
        });

        // Edit Transfer
        document.querySelectorAll('.edit-transfer-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var id = this.getAttribute('data-id');
                var form = document.getElementById('editTransferForm');
                form.action = "{{ url('finance/transfers') }}/" + id;
                document.getElementById('edit_transfer_source').value = this.getAttribute('data-source') || '';
                document.getElementById('edit_transfer_destination').value = this.getAttribute('data-destination') || '';
                document.getElementById('edit_transfer_amount').value = this.getAttribute('data-amount') || '';
                document.getElementById('edit_transfer_date').value = this.getAttribute('data-transfer_date') || '';
                document.getElementById('edit_transfer_description').value = this.getAttribute('data-description') || '';
            });
        });
    });
</script>
@endpush
