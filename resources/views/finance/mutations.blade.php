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
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <button type="button" class="btn btn-sm btn-success fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#addIncomeModal">
                    <i class="bi bi-plus-circle me-1"></i> Input Pemasukan
                </button>
                <button type="button" class="btn btn-sm btn-danger fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
                    <i class="bi bi-dash-circle me-1"></i> Input Pengeluaran
                </button>
                <button type="button" class="btn btn-sm btn-warning text-dark fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#addTransferModal">
                    <i class="bi bi-arrow-left-right me-1"></i> Transfer Dana
                </button>
                <span class="badge bg-light text-dark border small py-2 px-2">Total {{ $mutations->count() }} Transaksi</span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0" style="font-size: 0.85rem;">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width: 40px;">No</th>
                        <th style="width: 105px;">Tanggal</th>
                        <th style="width: 130px;">No. Referensi</th>
                        <th style="width: 150px;">Jenis & Akun</th>
                        <th style="width: 130px;">Kategori</th>
                        <th>Keterangan</th>
                        <th class="text-end" style="width: 125px;">Masuk (Rp)</th>
                        <th class="text-end" style="width: 125px;">Keluar (Rp)</th>
                        <th class="text-end" style="width: 135px;">Saldo Berjalan (Rp)</th>
                        <th class="text-center" style="width: 90px;">Aksi</th>
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
                            <td class="text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    @if($row['model_type'] === 'income')
                                        <button type="button" class="btn btn-outline-primary edit-income-btn"
                                            title="Edit Pemasukan"
                                            data-bs-toggle="modal" data-bs-target="#editIncomeModal"
                                            data-id="{{ $row['id'] }}"
                                            data-title="{{ $row['raw_title'] }}"
                                            data-category="{{ $row['raw_category'] }}"
                                            data-payment_destination="{{ $row['raw_payment_destination'] }}"
                                            data-amount="{{ $row['raw_amount'] }}"
                                            data-income_date="{{ $row['raw_income_date'] }}"
                                            data-description="{{ $row['raw_description'] }}">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form action="{{ route('finance.incomes.destroy', $row['id']) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus catatan pemasukan {{ $row['reference'] }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
                                            <button type="submit" class="btn btn-outline-danger" title="Hapus Pemasukan">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @elseif($row['model_type'] === 'expense')
                                        <button type="button" class="btn btn-outline-primary edit-expense-btn"
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
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form action="{{ route('finance.expenses.destroy', $row['id']) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus catatan pengeluaran {{ $row['reference'] }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
                                            <button type="submit" class="btn btn-outline-danger" title="Hapus Pengeluaran">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @elseif($row['model_type'] === 'transfer')
                                        <button type="button" class="btn btn-outline-primary edit-transfer-btn"
                                            title="Edit Transfer"
                                            data-bs-toggle="modal" data-bs-target="#editTransferModal"
                                            data-id="{{ $row['id'] }}"
                                            data-source="{{ $row['raw_source'] }}"
                                            data-destination="{{ $row['raw_destination'] }}"
                                            data-amount="{{ $row['raw_amount'] }}"
                                            data-transfer_date="{{ $row['raw_transfer_date'] }}"
                                            data-description="{{ $row['raw_description'] }}">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form action="{{ route('finance.transfers.destroy', $row['id']) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus catatan transfer dana {{ $row['reference'] }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
                                            <button type="submit" class="btn btn-outline-danger" title="Hapus Transfer">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
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
                        <td class="bg-light"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- MODAL INPUT PEMASUKAN --}}
    <div class="modal fade" id="addIncomeModal" tabindex="-1" aria-labelledby="addIncomeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('finance.incomes.store') }}" method="POST" class="modal-content shadow-lg border-0">
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
                        <label class="form-label fw-semibold small">Judul / Sumber Pemasukan <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control form-control-sm" required placeholder="Contoh: Suntikan Modal Pemilik / Pendapatan Lain">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Kategori <span class="text-danger">*</span></label>
                            <select name="category" class="form-select form-select-sm" required>
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
                            <label class="form-label fw-semibold small">Kas / Bank Tujuan <span class="text-danger">*</span></label>
                            <select name="payment_destination" class="form-select form-select-sm" required>
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
                            <label class="form-label fw-semibold small">Nominal (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="amount" min="0" step="any" class="form-control form-control-sm font-monospace" required placeholder="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" name="income_date" value="{{ date('Y-m-d') }}" class="form-control form-control-sm" required>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold small">Keterangan Tambahan</label>
                        <textarea name="description" rows="3" class="form-control form-control-sm" placeholder="Catatan tambahan (opsional)..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-success px-3">
                        <i class="bi bi-check-circle me-1"></i> Simpan Pemasukan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL EDIT PEMASUKAN --}}
    <div class="modal fade" id="editIncomeModal" tabindex="-1" aria-labelledby="editIncomeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="editIncomeForm" method="POST" class="modal-content shadow-lg border-0">
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
                        <label class="form-label fw-semibold small">Judul / Sumber Pemasukan <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="edit_income_title" class="form-control form-control-sm" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Kategori <span class="text-danger">*</span></label>
                            <select name="category" id="edit_income_category" class="form-select form-select-sm" required>
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
                            <label class="form-label fw-semibold small">Kas / Bank Tujuan <span class="text-danger">*</span></label>
                            <select name="payment_destination" id="edit_income_payment_destination" class="form-select form-select-sm" required>
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
                            <label class="form-label fw-semibold small">Nominal (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="amount" id="edit_income_amount" min="0" step="any" class="form-control form-control-sm font-monospace" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" name="income_date" id="edit_income_date" class="form-control form-control-sm" required>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold small">Keterangan Tambahan</label>
                        <textarea name="description" id="edit_income_description" rows="3" class="form-control form-control-sm"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary px-3">
                        <i class="bi bi-check-circle me-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL INPUT PENGELUARAN --}}
    <div class="modal fade" id="addExpenseModal" tabindex="-1" aria-labelledby="addExpenseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('finance.expenses.store') }}" method="POST" class="modal-content shadow-lg border-0">
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
                        <label class="form-label fw-semibold small">Judul Pengeluaran / Deskripsi Singkat <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control form-control-sm" required placeholder="Contoh: Bayar Listrik / Biaya Lakban / Operasional">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Kategori <span class="text-danger">*</span></label>
                            <select name="category" class="form-select form-select-sm" required>
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
                            <label class="form-label fw-semibold small">Kas / Bank Asal <span class="text-danger">*</span></label>
                            <select name="payment_source" class="form-select form-select-sm" required>
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
                            <label class="form-label fw-semibold small">Nominal (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="amount" min="0" step="any" class="form-control form-control-sm font-monospace" required placeholder="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" name="expense_date" value="{{ date('Y-m-d') }}" class="form-control form-control-sm" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Karyawan (Opsional - Penerima/PJ)</label>
                        <select name="employee_id" class="form-select form-select-sm">
                            <option value="">-- Tanpa Hubungan Karyawan --</option>
                            @if(isset($employees) && $employees->isNotEmpty())
                                @foreach ($employees as $emp)
                                    <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->position }})</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold small">Keterangan Tambahan</label>
                        <textarea name="description" rows="3" class="form-control form-control-sm" placeholder="Catatan tambahan (opsional)..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-danger px-3">
                        <i class="bi bi-check-circle me-1"></i> Simpan Pengeluaran
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL EDIT PENGELUARAN --}}
    <div class="modal fade" id="editExpenseModal" tabindex="-1" aria-labelledby="editExpenseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="editExpenseForm" method="POST" class="modal-content shadow-lg border-0">
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
                        <label class="form-label fw-semibold small">Judul Pengeluaran / Deskripsi Singkat <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="edit_expense_title" class="form-control form-control-sm" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Kategori <span class="text-danger">*</span></label>
                            <select name="category" id="edit_expense_category" class="form-select form-select-sm" required>
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
                            <label class="form-label fw-semibold small">Kas / Bank Asal <span class="text-danger">*</span></label>
                            <select name="payment_source" id="edit_expense_payment_source" class="form-select form-select-sm" required>
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
                            <label class="form-label fw-semibold small">Nominal (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="amount" id="edit_expense_amount" min="0" step="any" class="form-control form-control-sm font-monospace" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" name="expense_date" id="edit_expense_date" class="form-control form-control-sm" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Karyawan (Opsional - Penerima/PJ)</label>
                        <select name="employee_id" id="edit_expense_employee_id" class="form-select form-select-sm">
                            <option value="">-- Tanpa Hubungan Karyawan --</option>
                            @if(isset($employees) && $employees->isNotEmpty())
                                @foreach ($employees as $emp)
                                    <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->position }})</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold small">Keterangan Tambahan</label>
                        <textarea name="description" id="edit_expense_description" rows="3" class="form-control form-control-sm"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary px-3">
                        <i class="bi bi-check-circle me-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL INPUT TRANSFER --}}
    <div class="modal fade" id="addTransferModal" tabindex="-1" aria-labelledby="addTransferModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('finance.transfers.store') }}" method="POST" class="modal-content shadow-lg border-0">
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
                            <label class="form-label fw-semibold small">Kas / Bank Asal <span class="text-danger">*</span></label>
                            <select name="source" class="form-select form-select-sm" required>
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
                            <label class="form-label fw-semibold small">Kas / Bank Tujuan <span class="text-danger">*</span></label>
                            <select name="destination" class="form-select form-select-sm" required>
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
                            <label class="form-label fw-semibold small">Nominal Transfer (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="amount" min="0.01" step="any" class="form-control form-control-sm font-monospace" required placeholder="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Tanggal Transfer <span class="text-danger">*</span></label>
                            <input type="date" name="transfer_date" value="{{ date('Y-m-d') }}" class="form-control form-control-sm" required>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold small">Keterangan / Memo Transfer</label>
                        <textarea name="description" rows="3" class="form-control form-control-sm" placeholder="Contoh: Pengisian petty cash operasional..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-warning text-dark px-3">
                        <i class="bi bi-check-circle me-1"></i> Simpan Transfer
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL EDIT TRANSFER --}}
    <div class="modal fade" id="editTransferModal" tabindex="-1" aria-labelledby="editTransferModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="editTransferForm" method="POST" class="modal-content shadow-lg border-0">
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
                            <label class="form-label fw-semibold small">Kas / Bank Asal <span class="text-danger">*</span></label>
                            <select name="source" id="edit_transfer_source" class="form-select form-select-sm" required>
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
                            <label class="form-label fw-semibold small">Kas / Bank Tujuan <span class="text-danger">*</span></label>
                            <select name="destination" id="edit_transfer_destination" class="form-select form-select-sm" required>
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
                            <label class="form-label fw-semibold small">Nominal Transfer (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="amount" id="edit_transfer_amount" min="0.01" step="any" class="form-control form-control-sm font-monospace" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Tanggal Transfer <span class="text-danger">*</span></label>
                            <input type="date" name="transfer_date" id="edit_transfer_date" class="form-control form-control-sm" required>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold small">Keterangan / Memo Transfer</label>
                        <textarea name="description" id="edit_transfer_description" rows="3" class="form-control form-control-sm"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary px-3">
                        <i class="bi bi-check-circle me-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

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
@endsection
