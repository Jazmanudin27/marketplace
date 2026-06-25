@extends('layouts.app')
@section('title', 'Pengeluaran & Biaya')
@section('page-title', 'Pengeluaran & Biaya Operasional')

@section('content')
    {{-- KPI Cards --}}
    <div class="row g-3 mb-4">
        <!-- Total Pengeluaran -->
        <div class="col-12 col-md-4">
            <div class="dashboard-card h-100 d-flex align-items-center gap-3 py-3">
                <div class="stat-icon flex-shrink-0" style="background:rgba(59,130,246,.15);color:#60a5fa">
                    <i class="fas fa-arrow-down"></i>
                </div>
                <div class="min-width-0">
                    <div class="fw-bold fs-4 text-white">Rp {{ number_format($expenses->sum('amount'), 0, ',', '.') }}</div>
                    <div class="text-muted small">Total Pengeluaran (Halaman Ini)</div>
                </div>
            </div>
        </div>
        <!-- Sumber: Kas Besar -->
        <div class="col-12 col-md-4">
            <div class="dashboard-card h-100 d-flex align-items-center gap-3 py-3">
                <div class="stat-icon flex-shrink-0" style="background:rgba(16,185,129,.15);color:#34d399">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="min-width-0">
                    <div class="fw-bold fs-4 text-white">Rp
                        {{ number_format($expenses->where('payment_source', 'kas_besar')->sum('amount'), 0, ',', '.') }}
                    </div>
                    <div class="text-muted small">Sumber: Kas Besar</div>
                </div>
            </div>
        </div>
        <!-- Sumber: Kas Kecil -->
        <div class="col-12 col-md-4">
            <div class="dashboard-card h-100 d-flex align-items-center gap-3 py-3">
                <div class="stat-icon flex-shrink-0" style="background:rgba(245,158,11,.15);color:#fbbf24">
                    <i class="fas fa-coins"></i>
                </div>
                <div class="min-width-0">
                    <div class="fw-bold fs-4 text-white">Rp
                        {{ number_format($expenses->where('payment_source', 'kas_kecil')->sum('amount'), 0, ',', '.') }}
                    </div>
                    <div class="text-muted small">Sumber: Kas Kecil</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="dashboard-card mb-4 py-3">
        <form method="GET" action="{{ route('finance.expenses.index') }}">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label form-label-sm fw-semibold mb-1 text-muted">Cari Deskripsi</label>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Cari deskripsi biaya..."
                        class="form-control form-control-sm form-control-dark">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label form-label-sm fw-semibold mb-1 text-muted">Kategori</label>
                    <select name="category" class="form-select form-select-sm form-select-dark">
                        <option value="">Semua Kategori</option>
                        <option value="salary" {{ $category === 'salary' ? 'selected' : '' }}>Gaji Karyawan</option>
                        <option value="rent" {{ $category === 'rent' ? 'selected' : '' }}>Sewa Tempat</option>
                        <option value="utilities" {{ $category === 'utilities' ? 'selected' : '' }}>Utilitas</option>
                        <option value="other" {{ $category === 'other' ? 'selected' : '' }}>Lain-lain</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label form-label-sm fw-semibold mb-1 text-muted">Kas Asal</label>
                    <select name="payment_source" class="form-select form-select-sm form-select-dark">
                        <option value="">Semua Kas</option>
                        <option value="kas_besar" {{ $paymentSource === 'kas_besar' ? 'selected' : '' }}>Kas Besar</option>
                        <option value="kas_kecil" {{ $paymentSource === 'kas_kecil' ? 'selected' : '' }}>Kas Kecil</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label form-label-sm fw-semibold mb-1 text-muted">Dari</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}"
                        class="form-control form-control-sm form-control-dark">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label form-label-sm fw-semibold mb-1 text-muted">Sampai</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}"
                        class="form-control form-control-sm form-control-dark">
                </div>
                <div class="col-12 col-md-1 d-flex gap-1 justify-content-end">
                    <button type="submit" class="btn btn-sm btn-primary flex-fill" title="Filter"><i
                            class="fas fa-filter"></i></button>
                    <a href="{{ route('finance.expenses.index') }}" class="btn btn-sm btn-outline-secondary flex-fill"
                        title="Reset"><i class="fas fa-undo"></i></a>
                </div>
            </div>
        </form>
    </div>

    {{-- Main Table --}}
    <div class="dashboard-card">
        <div class="card-header-line d-flex justify-content-between align-items-center mb-3">
            <h3 class="mb-0"><i class="fas fa-arrow-down text-primary me-2"></i>Daftar Pengeluaran & Biaya</h3>
            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
                <i class="fas fa-plus me-1"></i> Tambah Pengeluaran
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-bordered table-premium-dark align-middle mb-0">
                <thead>
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
                            <td class="mono">{{ $exp->expense_date->format('d/m/Y') }}</td>
                            <td>
                                <span class="badge badge-secondary px-2 py-1">{{ $exp->category_label }}</span>
                            </td>
                            <td>
                                <div class="fw-semibold text-white">{{ $exp->title }}</div>
                                @if ($exp->description)
                                    <small class="text-muted">{{ $exp->description }}</small>
                                @endif
                            </td>
                            <td>
                                <span
                                    class="badge {{ $exp->payment_source === 'kas_kecil' ? 'badge-warning' : 'badge-success' }} px-2 py-1">
                                    {{ $exp->payment_source_label }}
                                </span>
                            </td>
                            <td>{{ $exp->employee ? $exp->employee->name : '-' }}</td>
                            <td class="mono fw-bold text-danger text-end">
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
        <div class="mt-3">
            {{ $expenses->links() }}
        </div>
    </div>

    {{-- MODAL TAMBAH EXPENSE --}}
    <div class="modal fade" id="addExpenseModal" tabindex="-1" aria-labelledby="addExpenseModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('finance.expenses.store') }}" method="POST" class="modal-content overflow-hidden">
                @csrf
                <div class="modal-header d-flex align-items-center gap-3 p-3 bg-success bg-opacity-10 border-0">
                    <div class="bg-success text-white rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 fs-5 p-2"
                        style="width: 38px; height: 38px;">
                        <i class="fas fa-plus"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="modal-title fw-bold fs-6 mb-0 text-white" id="addExpenseModalLabel">Tambah Pengeluaran
                        </h5>
                        <p class="mb-0 text-muted small">Catat pengeluaran atau biaya operasional baru</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Judul Pengeluaran / Deskripsi Singkat</label>
                        <input type="text" name="title" class="form-control form-control-sm form-control-dark"
                            required placeholder="Contoh: Bayar Listrik Juni 2026">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Kategori</label>
                            <select name="category" class="form-select form-select-sm form-select-dark" required>
                                <option value="utilities">Utilitas & Operasional</option>
                                <option value="salary">Gaji Karyawan</option>
                                <option value="rent">Sewa Tempat</option>
                                <option value="other">Lain-lain</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Kas Asal</label>
                            <select name="payment_source" class="form-select form-select-sm form-select-dark" required>
                                <option value="kas_besar">Kas Besar (Utama)</option>
                                <option value="kas_kecil">Kas Kecil (Operasional)</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Nominal (Rp)</label>
                            <input type="number" name="amount" min="0" step="any"
                                class="form-control form-control-sm form-control-dark" required placeholder="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Tanggal</label>
                            <input type="date" name="expense_date" value="{{ date('Y-m-d') }}"
                                class="form-control form-control-sm form-control-dark" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Karyawan (Opsional - Penerima/PJ)</label>
                        <select name="employee_id" class="form-select form-select-sm form-select-dark">
                            <option value="">-- Tanpa Hubungan Karyawan --</option>
                            @foreach ($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->position }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold small">Keterangan Tambahan</label>
                        <textarea name="description" rows="3" class="form-control form-control-sm form-control-dark"
                            placeholder="Tulis catatan tambahan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 p-3">
                    <button type="button" class="btn btn-sm btn-outline-secondary"
                        data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-success">Simpan Catatan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL EDIT EXPENSE --}}
    <div class="modal fade" id="editExpenseModal" tabindex="-1" aria-labelledby="editExpenseModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="editExpenseForm" method="POST" class="modal-content overflow-hidden">
                @csrf
                @method('PUT')
                <div class="modal-header d-flex align-items-center gap-3 p-3 bg-primary bg-opacity-10 border-0">
                    <div class="bg-primary text-white rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 fs-5 p-2"
                        style="width: 38px; height: 38px;">
                        <i class="fas fa-edit"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="modal-title fw-bold fs-6 mb-0 text-white" id="editExpenseModalLabel">Edit Pengeluaran
                        </h5>
                        <p class="mb-0 text-muted small">Ubah detail catatan pengeluaran</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Judul Pengeluaran / Deskripsi Singkat</label>
                        <input type="text" name="title" id="edit_title"
                            class="form-control form-control-sm form-control-dark" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Kategori</label>
                            <select name="category" id="edit_category"
                                class="form-select form-select-sm form-select-dark" required>
                                <option value="utilities">Utilitas & Operasional</option>
                                <option value="salary">Gaji Karyawan</option>
                                <option value="rent">Sewa Tempat</option>
                                <option value="other">Lain-lain</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Kas Asal</label>
                            <select name="payment_source" id="edit_payment_source"
                                class="form-select form-select-sm form-select-dark" required>
                                <option value="kas_besar">Kas Besar (Utama)</option>
                                <option value="kas_kecil">Kas Kecil (Operasional)</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Nominal (Rp)</label>
                            <input type="number" name="amount" id="edit_amount" min="0" step="any"
                                class="form-control form-control-sm form-control-dark" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Tanggal</label>
                            <input type="date" name="expense_date" id="edit_expense_date"
                                class="form-control form-control-sm form-control-dark" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Karyawan (Opsional - Penerima/PJ)</label>
                        <select name="employee_id" id="edit_employee_id"
                            class="form-select form-select-sm form-select-dark">
                            <option value="">-- Tanpa Hubungan Karyawan --</option>
                            @foreach ($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->position }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold small">Keterangan Tambahan</label>
                        <textarea name="description" id="edit_description" rows="3"
                            class="form-control form-control-sm form-control-dark"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 p-3">
                    <button type="button" class="btn btn-sm btn-outline-secondary"
                        data-bs-dismiss="modal">Batal</button>
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
                    const title = $(this).data('title');
                    const category = $(this).data('category');
                    const paymentSource = $(this).data('payment_source');
                    const amount = $(this).data('amount');
                    const expenseDate = $(this).data('expense_date');
                    const employeeId = $(this).data('employee_id');
                    const description = $(this).data('description');

                    $('#editExpenseForm').attr('action', `/finance/expenses/${id}`);
                    $('#edit_title').val(title);
                    $('#edit_category').val(category).trigger('change');
                    $('#edit_payment_source').val(paymentSource).trigger('change');
                    $('#edit_amount').val(amount);
                    $('#edit_expense_date').val(expenseDate);
                    $('#edit_employee_id').val(employeeId || '').trigger('change');
                    $('#edit_description').val(description);
                });
            });
        </script>
    @endpush
@endsection
