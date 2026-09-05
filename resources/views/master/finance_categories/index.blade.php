@extends('layouts.app')

@section('title', 'Master Kategori Keuangan (Biaya & Pemasukan)')
@section('page-title', 'Master Kategori Keuangan')

@section('content')
<div class="container-fluid px-0">

    {{-- Alert Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><strong>Terjadi kesalahan:</strong>
            <ul class="mb-0 mt-1 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- STATS CARDS --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm bg-white rounded-3">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Total Kategori</div>
                        <div class="fs-4 fw-bold text-dark font-monospace">{{ $totalCount }}</div>
                        <small class="text-muted">Seluruh pos keuangan</small>
                    </div>
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi bi-tags-fill fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm bg-white rounded-3">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-danger small fw-semibold text-uppercase">Kategori Pengeluaran (Biaya)</div>
                        <div class="fs-4 fw-bold text-danger font-monospace">{{ $expenseCount }}</div>
                        <small class="text-muted">Biaya Operasional & Non-HPP</small>
                    </div>
                    <div class="bg-danger bg-opacity-10 text-danger rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi bi-arrow-down-circle-fill fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm bg-white rounded-3">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-success small fw-semibold text-uppercase">Kategori Pemasukan</div>
                        <div class="fs-4 fw-bold text-success font-monospace">{{ $incomeCount }}</div>
                        <small class="text-muted">Pemasukan lain di luar penjualan</small>
                    </div>
                    <div class="bg-success bg-opacity-10 text-success rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi bi-arrow-up-circle-fill fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MAIN CARD --}}
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
            {{-- FILTER TABS --}}
            <ul class="nav nav-pills card-header-pills mb-0 small">
                <li class="nav-item">
                    <a class="nav-link px-3 py-1 fw-semibold {{ empty($type) ? 'active bg-primary' : 'text-secondary' }}"
                       href="{{ route('finance-categories.index', array_merge(request()->except('type', 'page'))) }}">
                        <i class="bi bi-grid-fill me-1"></i> Semua ({{ $totalCount }})
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3 py-1 fw-semibold {{ $type === 'expense' ? 'active bg-danger text-white' : 'text-danger' }}"
                       href="{{ route('finance-categories.index', array_merge(request()->except('page'), ['type' => 'expense'])) }}">
                        <i class="bi bi-arrow-down-circle-fill me-1"></i> Pengeluaran / Biaya ({{ $expenseCount }})
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3 py-1 fw-semibold {{ $type === 'income' ? 'active bg-success text-white' : 'text-success' }}"
                       href="{{ route('finance-categories.index', array_merge(request()->except('page'), ['type' => 'income'])) }}">
                        <i class="bi bi-arrow-up-circle-fill me-1"></i> Pemasukan ({{ $incomeCount }})
                    </a>
                </li>
            </ul>

            {{-- ACTION BUTTONS --}}
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('finance.expenses.index') }}" class="btn btn-sm btn-outline-secondary" title="Kembali ke Pengeluaran">
                    <i class="bi bi-arrow-left me-1"></i> Ke Pengeluaran
                </a>
                <button type="button" class="btn btn-sm btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Kategori
                </button>
            </div>
        </div>

        {{-- SEARCH & FILTER BAR --}}
        <div class="card-body py-2 px-3 bg-light bg-opacity-50 border-bottom">
            <form method="GET" action="{{ route('finance-categories.index') }}">
                @if($type)
                    <input type="hidden" name="type" value="{{ $type }}">
                @endif
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-md-5">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" name="search" value="{{ $search }}" class="form-control form-control-sm border-start-0 ps-0" placeholder="Cari nama kategori atau keterangan...">
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Semua Status</option>
                            <option value="1" {{ $status === '1' ? 'selected' : '' }}>Aktif Saja</option>
                            <option value="0" {{ $status === '0' ? 'selected' : '' }}>Non-Aktif</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-4 d-flex justify-content-end gap-1">
                        <button type="submit" class="btn btn-sm btn-primary px-3"><i class="bi bi-funnel-fill me-1"></i> Filter</button>
                        @if($search || $status !== null)
                            <a href="{{ route('finance-categories.index', $type ? ['type' => $type] : []) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-circle me-1"></i> Reset</a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        {{-- TABLE LIST --}}
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;" class="text-center">No.</th>
                            <th>Nama Kategori</th>
                            <th style="width: 160px;">Tipe Kategori</th>
                            <th style="width: 140px;">Kode / Slug</th>
                            <th>Deskripsi / Keterangan</th>
                            <th style="width: 110px;" class="text-center">Status</th>
                            <th style="width: 130px;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $idx => $cat)
                            <tr>
                                <td class="text-center font-monospace text-muted">{{ $categories->firstItem() + $idx }}</td>
                                <td>
                                    <div class="fw-bold text-dark fs-6">{{ $cat->name }}</div>
                                </td>
                                <td>
                                    @if($cat->type === 'expense')
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle px-2 py-1">
                                            <i class="bi bi-arrow-down-circle me-1"></i> Pengeluaran / Biaya
                                        </span>
                                    @else
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle px-2 py-1">
                                            <i class="bi bi-arrow-up-circle me-1"></i> Pemasukan
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark font-monospace border">{{ $cat->code }}</span>
                                </td>
                                <td>
                                    <span class="text-muted">{{ $cat->description ?: '—' }}</span>
                                </td>
                                <td class="text-center">
                                    <form action="{{ route('finance-categories.toggle', $cat) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm p-0 border-0" title="Klik untuk ubah status">
                                            @if($cat->is_active)
                                                <span class="badge bg-success px-2 py-1"><i class="bi bi-check-circle me-1"></i> Aktif</span>
                                            @else
                                                <span class="badge bg-secondary px-2 py-1"><i class="bi bi-dash-circle me-1"></i> Non-Aktif</span>
                                            @endif
                                        </button>
                                    </form>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editCategoryModal{{ $cat->id }}"
                                                title="Edit Kategori">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form action="{{ route('finance-categories.destroy', $cat) }}" method="POST"
                                              onsubmit="return confirm('Yakin ingin menghapus/menonaktifkan kategori {{ $cat->name }}?')"
                                              class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm" title="Hapus Kategori">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>

                                    {{-- MODAL EDIT KATEGORI --}}
                                    <div class="modal fade text-start" id="editCategoryModal{{ $cat->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <form action="{{ route('finance-categories.update', $cat) }}" method="POST" class="modal-content">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-header">
                                                    <h5 class="modal-title fw-bold">
                                                        <i class="bi bi-pencil-square text-primary me-2"></i>Edit Kategori Keuangan
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold small">Nama Kategori <span class="text-danger">*</span></label>
                                                        <input type="text" name="name" class="form-control" value="{{ $cat->name }}" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold small">Tipe Kategori <span class="text-danger">*</span></label>
                                                        <select name="type" class="form-select" required>
                                                            <option value="expense" {{ $cat->type === 'expense' ? 'selected' : '' }}>🔴 Pengeluaran / Biaya Operasional</option>
                                                            <option value="income" {{ $cat->type === 'income' ? 'selected' : '' }}>🟢 Pemasukan Lain-lain</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold small">Deskripsi / Keterangan</label>
                                                        <textarea name="description" rows="3" class="form-control" placeholder="Contoh: Pembelian lakban, plastik bubble, kardus packing...">{{ $cat->description }}</textarea>
                                                    </div>
                                                    <div class="mb-0 form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="switchActive{{ $cat->id }}" {{ $cat->is_active ? 'checked' : '' }}>
                                                        <label class="form-check-label fw-semibold small" for="switchActive{{ $cat->id }}">Kategori Aktif (Dapat dipilih di form)</label>
                                                    </div>
                                                </div>
                                                <div class="modal-footer bg-light py-2">
                                                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-sm btn-primary fw-bold"><i class="bi bi-save me-1"></i> Simpan Perubahan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block text-secondary opacity-50 mb-2"></i>
                                    Belum ada kategori ditemukan. Silakan klik tombol <strong>Tambah Kategori</strong>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($categories->hasPages())
                <div class="card-footer bg-white border-top py-2 px-3">
                    {{ $categories->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

{{-- MODAL TAMBAH KATEGORI BARU --}}
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('finance-categories.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="addCategoryModalLabel">
                    <i class="bi bi-plus-circle text-primary me-2"></i>Tambah Kategori Keuangan
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Nama Kategori <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="Contoh: Biaya Packing & Ekspedisi" required>
                    <small class="text-muted">Nama kategori yang akan tampil di pilihan form transaksi.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Tipe Kategori <span class="text-danger">*</span></label>
                    <select name="type" class="form-select" required>
                        <option value="expense" {{ (isset($type) && $type === 'expense') ? 'selected' : '' }}>🔴 Pengeluaran / Biaya Operasional</option>
                        <option value="income" {{ (isset($type) && $type === 'income') ? 'selected' : '' }}>🟢 Pemasukan Lain-lain</option>
                    </select>
                </div>
                <div class="mb-0">
                    <label class="form-label fw-semibold small">Deskripsi / Keterangan (Opsional)</label>
                    <textarea name="description" rows="3" class="form-control" placeholder="Tuliskan peruntukan kategori ini..."></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-sm btn-primary fw-bold"><i class="bi bi-check-circle me-1"></i> Simpan Kategori</button>
            </div>
        </form>
    </div>
</div>
@endsection
