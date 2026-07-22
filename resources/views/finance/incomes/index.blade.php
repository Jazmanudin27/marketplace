@extends('layouts.app')
@section('title', 'Pemasukan Lain-Lain')
@section('page-title', 'Pemasukan Lain-Lain (Non-Penjualan)')

@section('content')
    {{-- KPI Cards --}}
    <div class="row g-3 mb-4">
        <!-- Total Pemasukan -->
        <div class="col-12 col-md-4">
            <div class="card h-100 border rounded shadow-sm bg-white">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                        <i class="fas fa-arrow-up fs-5"></i>
                    </div>
                    <div class="min-width-0">
                        <div class="fw-bold fs-4 text-dark">Rp {{ number_format($incomes->sum('amount'), 0, ',', '.') }}</div>
                        <div class="text-muted small">Total Pemasukan (Halaman Ini)</div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Tujuan: Kas Besar -->
        <div class="col-12 col-md-4">
            <div class="card h-100 border rounded shadow-sm bg-white">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                        <i class="fas fa-wallet fs-5"></i>
                    </div>
                    <div class="min-width-0">
                        <div class="fw-bold fs-4 text-dark">Rp {{ number_format($incomes->where('payment_destination', 'kas_besar')->sum('amount'), 0, ',', '.') }}</div>
                        <div class="text-muted small">Tujuan: Kas Besar</div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Tujuan: Kas Kecil -->
        <div class="col-12 col-md-4">
            <div class="card h-100 border rounded shadow-sm bg-white">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                        <i class="fas fa-coins fs-5"></i>
                    </div>
                    <div class="min-width-0">
                        <div class="fw-bold fs-4 text-dark">Rp {{ number_format($incomes->where('payment_destination', 'kas_kecil')->sum('amount'), 0, ',', '.') }}</div>
                        <div class="text-muted small">Tujuan: Kas Kecil</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="card border rounded shadow-sm bg-white mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('finance.incomes.index') }}">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-3">
                        <label class="form-label form-label-sm fw-semibold mb-1 text-muted">Cari Deskripsi</label>
                        <input type="text" name="search" value="{{ $search }}" placeholder="Cari deskripsi pemasukan..."
                            class="form-control form-control-sm">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label form-label-sm fw-semibold mb-1 text-muted">Kategori</label>
                        <select name="category" class="form-select form-select-sm">
                            <option value="">Semua Kategori</option>
                            <option value="investment" {{ $category === 'investment' ? 'selected' : '' }}>Investasi / Modal</option>
                            <option value="refund" {{ $category === 'refund' ? 'selected' : '' }}>Refund / Pengembalian</option>
                            <option value="services" {{ $category === 'services' ? 'selected' : '' }}>Jasa / Layanan</option>
                            <option value="other" {{ $category === 'other' ? 'selected' : '' }}>Lain-lain</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label form-label-sm fw-semibold mb-1 text-muted">Kas / Rekening</label>
                        <select name="payment_destination" class="form-select form-select-sm">
                            <option value="">Semua Kas/Bank</option>
                            @if(isset($bankAccounts) && $bankAccounts->isNotEmpty())
                                @foreach($bankAccounts as $bank)
                                    <option value="{{ $bank->bank_name }}" {{ $paymentDestination === $bank->bank_name ? 'selected' : '' }}>{{ $bank->bank_name }}</option>
                                @endforeach
                            @else
                                <option value="kas_besar" {{ $paymentDestination === 'kas_besar' ? 'selected' : '' }}>Kas Besar</option>
                                <option value="kas_kecil" {{ $paymentDestination === 'kas_kecil' ? 'selected' : '' }}>Kas Kecil</option>
                            @endif
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label form-label-sm fw-semibold mb-1 text-muted">Dari</label>
                        <input type="date" name="date_from" value="{{ $dateFrom }}"
                            class="form-control form-control-sm">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label form-label-sm fw-semibold mb-1 text-muted">Sampai</label>
                        <input type="date" name="date_to" value="{{ $dateTo }}"
                            class="form-control form-control-sm">
                    </div>
                    <div class="col-12 col-md-1 d-flex gap-1 justify-content-end">
                        <button type="submit" class="btn btn-sm btn-primary flex-fill" title="Filter"><i class="fas fa-filter"></i></button>
                        <a href="{{ route('finance.incomes.index') }}" class="btn btn-sm btn-outline-secondary flex-fill" title="Reset"><i
                                class="fas fa-undo"></i></a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Main Table --}}
    <div class="card border rounded shadow-sm bg-white">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-arrow-up text-primary me-2"></i>Daftar Pemasukan Lain-Lain</h5>
                <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addIncomeModal">
                    <i class="fas fa-plus me-1"></i> Tambah Pemasukan
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover border align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>Kategori</th>
                            <th>Judul / Keterangan</th>
                            <th>Kas Tujuan</th>
                            <th class="text-end">Nominal</th>
                            <th class="text-center" style="width:120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($incomes as $inc)
                            <tr>
                                <td class="font-monospace">{{ $inc->income_date->format('d/m/Y') }}</td>
                                <td>
                                    <span class="badge bg-secondary px-2 py-1">{{ $inc->category_label }}</span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $inc->title }}</div>
                                    @if ($inc->description)
                                        <small class="text-muted">{{ $inc->description }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $inc->payment_destination === 'kas_kecil' ? 'bg-warning text-dark' : 'bg-success' }} px-2 py-1">
                                        {{ $inc->payment_destination_label }}
                                    </span>
                                </td>
                                <td class="font-monospace fw-bold text-success text-end">
                                    Rp {{ number_format($inc->amount, 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary edit-btn" data-bs-toggle="modal"
                                        data-bs-target="#editIncomeModal" data-id="{{ $inc->id }}"
                                        data-title="{{ $inc->title }}" data-category="{{ $inc->category }}"
                                        data-payment_destination="{{ $inc->payment_destination }}" data-amount="{{ $inc->amount }}"
                                        data-income_date="{{ $inc->income_date->format('Y-m-d') }}"
                                        data-description="{{ $inc->description }}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form action="{{ route('finance.incomes.destroy', $inc) }}" method="POST"
                                        style="display:inline;"
                                        onsubmit="return confirm('Apakah Anda yakin ingin menghapus pemasukan ini?')">
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
                                <td colspan="6" class="text-center text-muted py-5">Belum ada catatan pemasukan lain-lain.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $incomes->links() }}
            </div>
        </div>
    </div>

    {{-- MODAL TAMBAH INCOME --}}
    <div class="modal fade" id="addIncomeModal" tabindex="-1" aria-labelledby="addIncomeModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('finance.incomes.store') }}" method="POST" class="modal-content overflow-hidden border-0 shadow-lg">
                @csrf
                <div class="modal-header d-flex align-items-center gap-3 p-3 bg-success bg-opacity-10 border-0">
                    <div class="bg-success text-white rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 fs-5"
                        style="width: 38px; height: 38px;">
                        <i class="fas fa-plus"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="modal-title fw-bold fs-6 mb-0 text-dark" id="addIncomeModalLabel">Tambah Pemasukan</h5>
                        <p class="mb-0 text-muted small">Catat pemasukan atau sumber non-penjualan baru</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Judul / Sumber Pemasukan</label>
                        <input type="text" name="title" class="form-control form-control-sm"
                            required placeholder="Contoh: Suntikan Modal Pemilik">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Kategori</label>
                            <select name="category" class="form-select form-select-sm" required>
                                <option value="investment">Investasi / Modal</option>
                                <option value="refund">Refund / Pengembalian</option>
                                <option value="services">Jasa / Layanan</option>
                                <option value="other">Lain-lain</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Kas / Bank Tujuan</label>
                            <select name="payment_destination" class="form-select form-select-sm" required>
                                @if(isset($bankAccounts) && $bankAccounts->isNotEmpty())
                                    @foreach($bankAccounts as $bank)
                                        <option value="{{ $bank->bank_name }}">{{ $bank->bank_name }} {{ $bank->account_number ? '('.$bank->account_number.')' : '' }}</option>
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
                            <label class="form-label fw-semibold small">Nominal (Rp)</label>
                            <input type="number" name="amount" min="0" step="any"
                                class="form-control form-control-sm" required placeholder="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Tanggal</label>
                            <input type="date" name="income_date" value="{{ date('Y-m-d') }}"
                                class="form-control form-control-sm" required>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold small">Keterangan Tambahan</label>
                        <textarea name="description" rows="3" class="form-control form-control-sm"
                            placeholder="Tulis catatan tambahan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 p-3 bg-light">
                    <button type="button" class="btn btn-sm btn-outline-secondary"
                        data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-success">Simpan Catatan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL EDIT INCOME --}}
    <div class="modal fade" id="editIncomeModal" tabindex="-1" aria-labelledby="editIncomeModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="editIncomeForm" method="POST" class="modal-content overflow-hidden border-0 shadow-lg">
                @csrf
                @method('PUT')
                <div class="modal-header d-flex align-items-center gap-3 p-3 bg-primary bg-opacity-10 border-0">
                    <div class="bg-primary text-white rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 fs-5"
                        style="width: 38px; height: 38px;">
                        <i class="fas fa-edit"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="modal-title fw-bold fs-6 mb-0 text-dark" id="editIncomeModalLabel">Edit Pemasukan</h5>
                        <p class="mb-0 text-muted small">Ubah detail catatan pemasukan</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Judul / Sumber Pemasukan</label>
                        <input type="text" name="title" id="edit_title"
                            class="form-control form-control-sm" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Kategori</label>
                            <select name="category" id="edit_category"
                                class="form-select form-select-sm" required>
                                <option value="investment">Investasi / Modal</option>
                                <option value="refund">Refund / Pengembalian</option>
                                <option value="services">Jasa / Layanan</option>
                                <option value="other">Lain-lain</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Kas Tujuan</label>
                            <select name="payment_destination" id="edit_payment_destination"
                                class="form-select form-select-sm" required>
                                <option value="kas_besar">Kas Besar (Utama)</option>
                                <option value="kas_kecil">Kas Kecil (Operasional)</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Nominal (Rp)</label>
                            <input type="number" name="amount" id="edit_amount" min="0" step="any"
                                class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Tanggal</label>
                            <input type="date" name="income_date" id="edit_income_date"
                                class="form-control form-control-sm" required>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold small">Keterangan Tambahan</label>
                        <textarea name="description" id="edit_description" rows="3"
                            class="form-control form-control-sm"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 p-3 bg-light">
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
