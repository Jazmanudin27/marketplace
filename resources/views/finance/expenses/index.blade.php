@extends('layouts.app')
@section('title', 'Pengeluaran & Biaya')
@section('page-title', 'Pengeluaran & Biaya Operasional')

@section('content')
<div class="stats-grid mb-4" style="display:grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
    <div class="stat-card stat-primary">
        <div class="stat-icon"><i class="fas fa-arrow-down"></i></div>
        <div class="stat-body">
            <div class="stat-value">Rp {{ number_format($expenses->sum('amount'), 0, ',', '.') }}</div>
            <div class="stat-label">Total Pengeluaran (Halaman Ini)</div>
        </div>
        <div class="stat-glow"></div>
    </div>
    <div class="stat-card stat-success">
        <div class="stat-icon"><i class="fas fa-wallet"></i></div>
        <div class="stat-body">
            <div class="stat-value">Rp {{ number_format($expenses->where('payment_source', 'kas_besar')->sum('amount'), 0, ',', '.') }}</div>
            <div class="stat-label">Sumber: Kas Besar</div>
        </div>
        <div class="stat-glow"></div>
    </div>
    <div class="stat-card stat-warning">
        <div class="stat-icon"><i class="fas fa-coins"></i></div>
        <div class="stat-body">
            <div class="stat-value">Rp {{ number_format($expenses->where('payment_source', 'kas_kecil')->sum('amount'), 0, ',', '.') }}</div>
            <div class="stat-label">Sumber: Kas Kecil</div>
        </div>
        <div class="stat-glow"></div>
    </div>
</div>

<div class="dashboard-card mb-4">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
        <form method="GET" action="{{ route('finance.expenses.index') }}" style="display:flex; flex-wrap:wrap; gap:0.75rem; align-items:flex-end; flex:1;">
            <div style="min-width:180px; flex:1;">
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari deskripsi biaya..." class="form-control form-control-sm form-control-dark">
            </div>
            <div style="width:140px;">
                <select name="category" class="form-select form-select-sm form-select-dark">
                    <option value="">Semua Kategori</option>
                    <option value="salary" {{ $category === 'salary' ? 'selected' : '' }}>Gaji Karyawan</option>
                    <option value="rent" {{ $category === 'rent' ? 'selected' : '' }}>Sewa Tempat</option>
                    <option value="utilities" {{ $category === 'utilities' ? 'selected' : '' }}>Utilitas</option>
                    <option value="other" {{ $category === 'other' ? 'selected' : '' }}>Lain-lain</option>
                </select>
            </div>
            <div style="width:140px;">
                <select name="payment_source" class="form-select form-select-sm form-select-dark">
                    <option value="">Semua Kas</option>
                    <option value="kas_besar" {{ $paymentSource === 'kas_besar' ? 'selected' : '' }}>Kas Besar</option>
                    <option value="kas_kecil" {{ $paymentSource === 'kas_kecil' ? 'selected' : '' }}>Kas Kecil</option>
                </select>
            </div>
            <div style="width:130px;">
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control form-control-sm form-control-dark" placeholder="Dari">
            </div>
            <div style="width:130px;">
                <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control form-control-sm form-control-dark" placeholder="Sampai">
            </div>
            <div>
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter"></i></button>
                <a href="{{ route('finance.expenses.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-undo"></i></a>
            </div>
        </form>
        <div>
            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
                <i class="fas fa-plus me-1"></i> Tambah Pengeluaran
            </button>
        </div>
    </div>
</div>

<div class="dashboard-card">
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Kategori</th>
                    <th>Judul / Deskripsi</th>
                    <th>Kas Asal</th>
                    <th>Karyawan (Penerima)</th>
                    <th style="text-align:right;">Nominal</th>
                    <th style="text-align:center; width:120px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($expenses as $exp)
                <tr>
                    <td class="mono">{{ $exp->expense_date->format('d/m/Y') }}</td>
                    <td>
                        <span class="badge bg-secondary">{{ $exp->category_label }}</span>
                    </td>
                    <td>
                        <div style="font-weight:600; color:var(--text-primary);">{{ $exp->title }}</div>
                        @if($exp->description)
                            <small class="text-muted">{{ $exp->description }}</small>
                        @endif
                    </td>
                    <td>
                        <span class="badge {{ $exp->payment_source === 'kas_kecil' ? 'bg-warning text-dark' : 'bg-success' }}">
                            {{ $exp->payment_source_label }}
                        </span>
                    </td>
                    <td>{{ $exp->employee ? $exp->employee->name : '-' }}</td>
                    <td class="mono fw-bold text-danger" style="text-align:right;">
                        Rp {{ number_format($exp->amount, 0, ',', '.') }}
                    </td>
                    <td style="text-align:center;">
                        <button class="btn btn-sm btn-outline-primary edit-btn" 
                                data-bs-toggle="modal" 
                                data-bs-target="#editExpenseModal"
                                data-id="{{ $exp->id }}"
                                data-title="{{ $exp->title }}"
                                data-category="{{ $exp->category }}"
                                data-payment_source="{{ $exp->payment_source }}"
                                data-amount="{{ $exp->amount }}"
                                data-expense_date="{{ $exp->expense_date->format('Y-m-d') }}"
                                data-employee_id="{{ $exp->employee_id }}"
                                data-description="{{ $exp->description }}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <form action="{{ route('finance.expenses.destroy', $exp) }}" method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengeluaran ini?')">
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
                    <td colspan="7" class="text-center text-muted" style="padding:3rem;">Belum ada catatan pengeluaran.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:1rem;">
        {{ $expenses->links() }}
    </div>
</div>

{{-- MODAL TAMBAH EXPENSE --}}
<div class="modal fade" id="addExpenseModal" tabindex="-1" aria-labelledby="addExpenseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('finance.expenses.store') }}" method="POST" class="modal-content" style="background-color: var(--bg-card); color: var(--text-primary); border: 1px solid var(--border);">
            @csrf
            <div class="modal-header" style="border-bottom: 1px solid var(--border);">
                <h5 class="modal-title" id="addExpenseModalLabel"><i class="fas fa-plus text-success me-2"></i>Tambah Pengeluaran</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="display:flex; flex-direction:column; gap:1rem;">
                <div>
                    <label class="form-label small text-muted">Judul Pengeluaran / Deskripsi Singkat</label>
                    <input type="text" name="title" class="form-control form-control-sm form-control-dark" required placeholder="Contoh: Bayar Listrik Juni 2026">
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                    <div>
                        <label class="form-label small text-muted">Kategori</label>
                        <select name="category" class="form-select form-select-sm form-select-dark" required>
                            <option value="utilities">Utilitas & Operasional</option>
                            <option value="salary">Gaji Karyawan</option>
                            <option value="rent">Sewa Tempat</option>
                            <option value="other">Lain-lain</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label small text-muted">Kas Asal</label>
                        <select name="payment_source" class="form-select form-select-sm form-select-dark" required>
                            <option value="kas_besar">Kas Besar (Utama)</option>
                            <option value="kas_kecil">Kas Kecil (Operasional)</option>
                        </select>
                    </div>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                    <div>
                        <label class="form-label small text-muted">Nominal (Rp)</label>
                        <input type="number" name="amount" min="0" step="any" class="form-control form-control-sm form-control-dark" required placeholder="0">
                    </div>
                    <div>
                        <label class="form-label small text-muted">Tanggal</label>
                        <input type="date" name="expense_date" value="{{ date('Y-m-d') }}" class="form-control form-control-sm form-control-dark" required>
                    </div>
                </div>
                <div>
                    <label class="form-label small text-muted">Karyawan (Opsional - Penerima/PJ)</label>
                    <select name="employee_id" class="form-select form-select-sm form-select-dark">
                        <option value="">-- Tanpa Hubungan Karyawan --</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->position }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label small text-muted">Keterangan Tambahan</label>
                    <textarea name="description" rows="3" class="form-control form-control-sm form-control-dark" placeholder="Tulis catatan tambahan..."></textarea>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid var(--border);">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-sm btn-success">Simpan Catatan</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL EDIT EXPENSE --}}
<div class="modal fade" id="editExpenseModal" tabindex="-1" aria-labelledby="editExpenseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="editExpenseForm" method="POST" class="modal-content" style="background-color: var(--bg-card); color: var(--text-primary); border: 1px solid var(--border);">
            @csrf
            @method('PUT')
            <div class="modal-header" style="border-bottom: 1px solid var(--border);">
                <h5 class="modal-title" id="editExpenseModalLabel"><i class="fas fa-edit text-primary me-2"></i>Edit Pengeluaran</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="display:flex; flex-direction:column; gap:1rem;">
                <div>
                    <label class="form-label small text-muted">Judul Pengeluaran / Deskripsi Singkat</label>
                    <input type="text" name="title" id="edit_title" class="form-control form-control-sm form-control-dark" required>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                    <div>
                        <label class="form-label small text-muted">Kategori</label>
                        <select name="category" id="edit_category" class="form-select form-select-sm form-select-dark" required>
                            <option value="utilities">Utilitas & Operasional</option>
                            <option value="salary">Gaji Karyawan</option>
                            <option value="rent">Sewa Tempat</option>
                            <option value="other">Lain-lain</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label small text-muted">Kas Asal</label>
                        <select name="payment_source" id="edit_payment_source" class="form-select form-select-sm form-select-dark" required>
                            <option value="kas_besar">Kas Besar (Utama)</option>
                            <option value="kas_kecil">Kas Kecil (Operasional)</option>
                        </select>
                    </div>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                    <div>
                        <label class="form-label small text-muted">Nominal (Rp)</label>
                        <input type="number" name="amount" id="edit_amount" min="0" step="any" class="form-control form-control-sm form-control-dark" required>
                    </div>
                    <div>
                        <label class="form-label small text-muted">Tanggal</label>
                        <input type="date" name="expense_date" id="edit_expense_date" class="form-control form-control-sm form-control-dark" required>
                    </div>
                </div>
                <div>
                    <label class="form-label small text-muted">Karyawan (Opsional - Penerima/PJ)</label>
                    <select name="employee_id" id="edit_employee_id" class="form-select form-select-sm form-select-dark">
                        <option value="">-- Tanpa Hubungan Karyawan --</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->position }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label small text-muted">Keterangan Tambahan</label>
                    <textarea name="description" id="edit_description" rows="3" class="form-control form-control-sm form-control-dark"></textarea>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid var(--border);">
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
