@extends('layouts.app')
@section('title', 'Pengeluaran & Biaya')
@section('page-title', 'Pengeluaran & Biaya Operasional')

@section('content')
    {{-- KPI Cards --}}
    <div class="row g-3 mb-3">
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
                <div class="card-body py-3 d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-primary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:42px;height:42px;">
                        <i class="fas fa-arrow-down text-primary"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-5 font-monospace">Rp {{ number_format($expenses->sum('amount'), 0, ',', '.') }}</div>
                        <div class="text-muted small">Total Pengeluaran (Halaman Ini)</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
                <div class="card-body py-3 d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-success bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:42px;height:42px;">
                        <i class="fas fa-wallet text-success"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-5 font-monospace">Rp {{ number_format($expenses->where('payment_source', 'kas_besar')->sum('amount'), 0, ',', '.') }}</div>
                        <div class="text-muted small">Sumber: Kas Besar</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
                <div class="card-body py-3 d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-warning bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:42px;height:42px;">
                        <i class="fas fa-coins text-warning"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-5 font-monospace">Rp {{ number_format($expenses->where('payment_source', 'kas_kecil')->sum('amount'), 0, ',', '.') }}</div>
                        <div class="text-muted small">Sumber: Kas Kecil</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2 px-3">
            <form method="GET" action="{{ route('finance.expenses.index') }}">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-3">
                        <label class="form-label form-label-sm fw-semibold mb-1">Cari Deskripsi</label>
                        <input type="text" name="search" value="{{ $search }}" placeholder="Cari deskripsi biaya..."
                            class="form-control form-control-sm">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label form-label-sm fw-semibold mb-1">Kategori</label>
                        <select name="category" class="form-select form-select-sm">
                            <option value="">Semua Kategori</option>
                            <option value="salary" {{ $category === 'salary' ? 'selected' : '' }}>Gaji Karyawan</option>
                            <option value="rent" {{ $category === 'rent' ? 'selected' : '' }}>Sewa Tempat</option>
                            <option value="utilities" {{ $category === 'utilities' ? 'selected' : '' }}>Utilitas</option>
                            <option value="pembelian_supplier" {{ $category === 'pembelian_supplier' ? 'selected' : '' }}>Bayar Hutang Supplier</option>
                            <option value="other" {{ $category === 'other' ? 'selected' : '' }}>Lain-lain</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label form-label-sm fw-semibold mb-1">Kas Asal</label>
                        <select name="payment_source" class="form-select form-select-sm">
                            <option value="">Semua Kas</option>
                            <option value="kas_besar" {{ $paymentSource === 'kas_besar' ? 'selected' : '' }}>Kas Besar</option>
                            <option value="kas_kecil" {{ $paymentSource === 'kas_kecil' ? 'selected' : '' }}>Kas Kecil</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label form-label-sm fw-semibold mb-1">Dari</label>
                        <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label form-label-sm fw-semibold mb-1">Sampai</label>
                        <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-12 col-md-1 d-flex gap-1 justify-content-end">
                        <button type="submit" class="btn btn-sm btn-primary flex-fill" title="Filter"><i class="fas fa-filter"></i></button>
                        <a href="{{ route('finance.expenses.index') }}" class="btn btn-sm btn-outline-secondary flex-fill" title="Reset"><i class="fas fa-undo"></i></a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Main Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-danger bg-opacity-10 d-flex justify-content-between align-items-center py-2 px-3">
            <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-arrow-down text-danger me-2"></i>Daftar Pengeluaran & Biaya</h6>
            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
                <i class="fas fa-plus me-1"></i> Tambah Pengeluaran
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>Kategori</th>
                            <th>Judul / Deskripsi</th>
                            <th>Kas Asal</th>
                            <th>Karyawan (Penerima)</th>
                            <th class="text-end">Nominal</th>
                            <th class="text-center" style="width:120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($expenses as $exp)
                            <tr>
                                <td class="font-monospace small">{{ $exp->expense_date->format('d/m/Y') }}</td>
                                <td><span class="badge bg-secondary small">{{ $exp->category_label }}</span></td>
                                <td>
                                    <div class="fw-semibold small">{{ $exp->title }}</div>
                                    @if ($exp->description)
                                        <small class="text-muted">{{ $exp->description }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $exp->payment_source === 'kas_kecil' ? 'bg-warning text-dark' : 'bg-success' }} small">
                                        {{ $exp->payment_source_label }}
                                    </span>
                                </td>
                                <td class="small text-muted">{{ $exp->employee ? $exp->employee->name : '-' }}</td>
                                <td class="font-monospace fw-bold text-danger text-end small">
                                    Rp {{ number_format($exp->amount, 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary edit-btn" data-bs-toggle="modal"
                                        data-bs-target="#editExpenseModal" data-id="{{ $exp->id }}"
                                        data-title="{{ $exp->title }}" data-category="{{ $exp->category }}"
                                        data-payment_source="{{ $exp->payment_source }}" data-amount="{{ $exp->amount }}"
                                        data-expense_date="{{ $exp->expense_date->format('Y-m-d') }}"
                                        data-employee_id="{{ $exp->employee_id }}"
                                        data-description="{{ $exp->description }}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form action="{{ route('finance.expenses.destroy', $exp) }}" method="POST"
                                        style="display:inline;"
                                        onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengeluaran ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">Belum ada catatan pengeluaran.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-3 py-2">{{ $expenses->links() }}</div>
        </div>
    </div>

    {{-- MODAL TAMBAH EXPENSE --}}
    <div class="modal fade" id="addExpenseModal" tabindex="-1" aria-labelledby="addExpenseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('finance.expenses.store') }}" method="POST" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="addExpenseModalLabel">
                        <i class="fas fa-plus text-success me-2"></i>Tambah Pengeluaran
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Judul Pengeluaran / Deskripsi Singkat</label>
                        <input type="text" name="title" class="form-control form-control-sm" required placeholder="Contoh: Bayar Listrik Juni 2026">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Kategori</label>
                            <select name="category" class="form-select form-select-sm" required>
                                <option value="utilities">Utilitas & Operasional</option>
                                <option value="salary">Gaji Karyawan</option>
                                <option value="rent">Sewa Tempat</option>
                                <option value="pembelian_supplier">Bayar Hutang Supplier</option>
                                <option value="other">Lain-lain</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Kas Asal</label>
                            <select name="payment_source" class="form-select form-select-sm" required>
                                <option value="kas_besar">Kas Besar (Utama)</option>
                                <option value="kas_kecil">Kas Kecil (Operasional)</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Nominal (Rp)</label>
                            <input type="number" name="amount" min="0" step="any" class="form-control form-control-sm" required placeholder="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Tanggal</label>
                            <input type="date" name="expense_date" value="{{ date('Y-m-d') }}" class="form-control form-control-sm" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Karyawan (Opsional - Penerima/PJ)</label>
                        <select name="employee_id" class="form-select form-select-sm">
                            <option value="">-- Tanpa Hubungan Karyawan --</option>
                            @foreach ($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->position }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold small">Keterangan Tambahan</label>
                        <textarea name="description" rows="3" class="form-control form-control-sm" placeholder="Tulis catatan tambahan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-success">Simpan Catatan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL EDIT EXPENSE --}}
    <div class="modal fade" id="editExpenseModal" tabindex="-1" aria-labelledby="editExpenseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="editExpenseForm" method="POST" class="modal-content">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="editExpenseModalLabel">
                        <i class="fas fa-edit text-primary me-2"></i>Edit Pengeluaran
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Judul Pengeluaran / Deskripsi Singkat</label>
                        <input type="text" name="title" id="edit_title" class="form-control form-control-sm" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Kategori</label>
                            <select name="category" id="edit_category" class="form-select form-select-sm" required>
                                <option value="utilities">Utilitas & Operasional</option>
                                <option value="salary">Gaji Karyawan</option>
                                <option value="rent">Sewa Tempat</option>
                                <option value="pembelian_supplier">Bayar Hutang Supplier</option>
                                <option value="other">Lain-lain</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Kas Asal</label>
                            <select name="payment_source" id="edit_payment_source" class="form-select form-select-sm" required>
                                <option value="kas_besar">Kas Besar (Utama)</option>
                                <option value="kas_kecil">Kas Kecil (Operasional)</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Nominal (Rp)</label>
                            <input type="number" name="amount" id="edit_amount" min="0" step="any" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Tanggal</label>
                            <input type="date" name="expense_date" id="edit_expense_date" class="form-control form-control-sm" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Karyawan (Opsional - Penerima/PJ)</label>
                        <select name="employee_id" id="edit_employee_id" class="form-select form-select-sm">
                            <option value="">-- Tanpa Hubungan Karyawan --</option>
                            @foreach ($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->position }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold small">Keterangan Tambahan</label>
                        <textarea name="description" id="edit_description" rows="3" class="form-control form-control-sm"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                $('.edit-btn').on('click', function() {
                    const id = $(this).data('id');
                    $('#editExpenseForm').attr('action', `/finance/expenses/${id}`);
                    $('#edit_title').val($(this).data('title'));
                    $('#edit_category').val($(this).data('category')).trigger('change');
                    $('#edit_payment_source').val($(this).data('payment_source')).trigger('change');
                    $('#edit_amount').val($(this).data('amount'));
                    $('#edit_expense_date').val($(this).data('expense_date'));
                    $('#edit_employee_id').val($(this).data('employee_id') || '').trigger('change');
                    $('#edit_description').val($(this).data('description'));
                });
            });
        </script>
    @endpush
@endsection
