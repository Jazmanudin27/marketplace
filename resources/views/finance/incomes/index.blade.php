@extends('layouts.app')
@section('title', 'Pemasukan Lain-Lain')
@section('page-title', 'Pemasukan Lain-Lain (Non-Penjualan)')

@section('content')
<div class="stats-grid mb-4" style="display:grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
    <div class="stat-card stat-success">
        <div class="stat-icon"><i class="fas fa-arrow-up"></i></div>
        <div class="stat-body">
            <div class="stat-value">Rp {{ number_format($incomes->sum('amount'), 0, ',', '.') }}</div>
            <div class="stat-label">Total Pemasukan (Halaman Ini)</div>
        </div>
        <div class="stat-glow"></div>
    </div>
    <div class="stat-card stat-primary">
        <div class="stat-icon"><i class="fas fa-wallet"></i></div>
        <div class="stat-body">
            <div class="stat-value">Rp {{ number_format($incomes->where('payment_destination', 'kas_besar')->sum('amount'), 0, ',', '.') }}</div>
            <div class="stat-label">Tujuan: Kas Besar</div>
        </div>
        <div class="stat-glow"></div>
    </div>
    <div class="stat-card stat-warning">
        <div class="stat-icon"><i class="fas fa-coins"></i></div>
        <div class="stat-body">
            <div class="stat-value">Rp {{ number_format($incomes->where('payment_destination', 'kas_kecil')->sum('amount'), 0, ',', '.') }}</div>
            <div class="stat-label">Tujuan: Kas Kecil</div>
        </div>
        <div class="stat-glow"></div>
    </div>
</div>

<div class="dashboard-card mb-4">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
        <form method="GET" action="{{ route('finance.incomes.index') }}" style="display:flex; flex-wrap:wrap; gap:0.75rem; align-items:flex-end; flex:1;">
            <div style="min-width:180px; flex:1;">
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari deskripsi pemasukan..." class="form-control form-control-sm form-control-dark">
            </div>
            <div style="width:150px;">
                <select name="category" class="form-select form-select-sm form-select-dark">
                    <option value="">Semua Kategori</option>
                    <option value="investment" {{ $category === 'investment' ? 'selected' : '' }}>Investasi / Modal</option>
                    <option value="refund" {{ $category === 'refund' ? 'selected' : '' }}>Refund / Pengembalian</option>
                    <option value="services" {{ $category === 'services' ? 'selected' : '' }}>Jasa / Layanan</option>
                    <option value="other" {{ $category === 'other' ? 'selected' : '' }}>Lain-lain</option>
                </select>
            </div>
            <div style="width:140px;">
                <select name="payment_destination" class="form-select form-select-sm form-select-dark">
                    <option value="">Semua Kas</option>
                    <option value="kas_besar" {{ $paymentDestination === 'kas_besar' ? 'selected' : '' }}>Kas Besar</option>
                    <option value="kas_kecil" {{ $paymentDestination === 'kas_kecil' ? 'selected' : '' }}>Kas Kecil</option>
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
                <a href="{{ route('finance.incomes.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-undo"></i></a>
            </div>
        </form>
        <div>
            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addIncomeModal">
                <i class="fas fa-plus me-1"></i> Tambah Pemasukan
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
                    <th>Judul / Keterangan</th>
                    <th>Kas Tujuan</th>
                    <th style="text-align:right;">Nominal</th>
                    <th style="text-align:center; width:120px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($incomes as $inc)
                <tr>
                    <td class="mono">{{ $inc->income_date->format('d/m/Y') }}</td>
                    <td>
                        <span class="badge bg-secondary">{{ $inc->category_label }}</span>
                    </td>
                    <td>
                        <div style="font-weight:600; color:var(--text-primary);">{{ $inc->title }}</div>
                        @if($inc->description)
                            <small class="text-muted">{{ $inc->description }}</small>
                        @endif
                    </td>
                    <td>
                        <span class="badge {{ $inc->payment_destination === 'kas_kecil' ? 'bg-warning text-dark' : 'bg-success' }}">
                            {{ $inc->payment_destination_label }}
                        </span>
                    </td>
                    <td class="mono fw-bold text-success" style="text-align:right;">
                        Rp {{ number_format($inc->amount, 0, ',', '.') }}
                    </td>
                    <td style="text-align:center;">
                        <button class="btn btn-sm btn-outline-primary edit-btn" 
                                data-bs-toggle="modal" 
                                data-bs-target="#editIncomeModal"
                                data-id="{{ $inc->id }}"
                                data-title="{{ $inc->title }}"
                                data-category="{{ $inc->category }}"
                                data-payment_destination="{{ $inc->payment_destination }}"
                                data-amount="{{ $inc->amount }}"
                                data-income_date="{{ $inc->income_date->format('Y-m-d') }}"
                                data-description="{{ $inc->description }}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <form action="{{ route('finance.incomes.destroy', $inc) }}" method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pemasukan ini?')">
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
                    <td colspan="6" class="text-center text-muted" style="padding:3rem;">Belum ada catatan pemasukan lain-lain.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:1rem;">
        {{ $incomes->links() }}
    </div>
</div>

{{-- MODAL TAMBAH INCOME --}}
<div class="modal fade" id="addIncomeModal" tabindex="-1" aria-labelledby="addIncomeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('finance.incomes.store') }}" method="POST" class="modal-content" style="background-color: var(--bg-card); color: var(--text-primary); border: 1px solid var(--border);">
            @csrf
            <div class="modal-header" style="border-bottom: 1px solid var(--border);">
                <h5 class="modal-title" id="addIncomeModalLabel"><i class="fas fa-plus text-success me-2"></i>Tambah Pemasukan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="display:flex; flex-direction:column; gap:1rem;">
                <div>
                    <label class="form-label small text-muted">Judul / Sumber Pemasukan</label>
                    <input type="text" name="title" class="form-control form-control-sm form-control-dark" required placeholder="Contoh: Suntikan Modal Pemilik">
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                    <div>
                        <label class="form-label small text-muted">Kategori</label>
                        <select name="category" class="form-select form-select-sm form-select-dark" required>
                            <option value="investment">Investasi / Modal</option>
                            <option value="refund">Refund / Pengembalian</option>
                            <option value="services">Jasa / Layanan</option>
                            <option value="other">Lain-lain</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label small text-muted">Kas Tujuan</label>
                        <select name="payment_destination" class="form-select form-select-sm form-select-dark" required>
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
                        <input type="date" name="income_date" value="{{ date('Y-m-d') }}" class="form-control form-control-sm form-control-dark" required>
                    </div>
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

{{-- MODAL EDIT INCOME --}}
<div class="modal fade" id="editIncomeModal" tabindex="-1" aria-labelledby="editIncomeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="editIncomeForm" method="POST" class="modal-content" style="background-color: var(--bg-card); color: var(--text-primary); border: 1px solid var(--border);">
            @csrf
            @method('PUT')
            <div class="modal-header" style="border-bottom: 1px solid var(--border);">
                <h5 class="modal-title" id="editIncomeModalLabel"><i class="fas fa-edit text-primary me-2"></i>Edit Pemasukan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="display:flex; flex-direction:column; gap:1rem;">
                <div>
                    <label class="form-label small text-muted">Judul / Sumber Pemasukan</label>
                    <input type="text" name="title" id="edit_title" class="form-control form-control-sm form-control-dark" required>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                    <div>
                        <label class="form-label small text-muted">Kategori</label>
                        <select name="category" id="edit_category" class="form-select form-select-sm form-select-dark" required>
                            <option value="investment">Investasi / Modal</option>
                            <option value="refund">Refund / Pengembalian</option>
                            <option value="services">Jasa / Layanan</option>
                            <option value="other">Lain-lain</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label small text-muted">Kas Tujuan</label>
                        <select name="payment_destination" id="edit_payment_destination" class="form-select form-select-sm form-select-dark" required>
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
                        <input type="date" name="income_date" id="edit_income_date" class="form-control form-control-sm form-control-dark" required>
                    </div>
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
            const paymentDestination = $(this).data('payment_destination');
            const amount = $(this).data('amount');
            const incomeDate = $(this).data('income_date');
            const description = $(this).data('description');

            $('#editIncomeForm').attr('action', `/finance/incomes/${id}`);
            $('#edit_title').val(title);
            $('#edit_category').val(category).trigger('change');
            $('#edit_payment_destination').val(paymentDestination).trigger('change');
            $('#edit_amount').val(amount);
            $('#edit_income_date').val(incomeDate);
            $('#edit_description').val(description);
        });
    });
</script>
@endpush
@endsection
