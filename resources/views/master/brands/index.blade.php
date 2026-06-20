@extends('layouts.app')
@section('title', 'Master Merk')
@section('page-title', 'Master Merk')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="dashboard-card">

                {{-- Header --}}
                <div class="card-header-line d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">
                            <i class="fas fa-certificate me-2 text-primary"></i>Daftar Merk
                        </h5>
                        <p class="text-muted mb-0 mt-1" style="font-size:0.75rem;">Kelola merk / brand produk</p>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm px-3" data-bs-toggle="modal"
                        data-bs-target="#createBrandModal">
                        <i class="fas fa-plus me-1"></i> Tambah Merk
                    </button>
                </div>

                {{-- Alert --}}
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                {{-- Validation Errors --}}
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                        <strong><i class="fas fa-exclamation-triangle me-2"></i>Periksa kembali inputan Anda:</strong>
                        <ul class="mb-0 mt-1 ps-3 small">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                {{-- Filter --}}
                <form method="GET" action="{{ route('brands.index') }}" class="mt-3 mb-3">
                    <div class="row g-2 align-items-end">
                        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                            <label class="form-label form-label-sm fw-semibold text-uppercase">
                                <i class="fas fa-certificate me-1"></i>Nama Merk
                            </label>
                            <input type="text" name="name" class="form-control form-control-sm"
                                placeholder="Cari nama merk..." value="{{ request('name') }}">
                        </div>
                        <div class="col-12 col-sm-6 col-md-auto d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-search me-1"></i> Cari
                            </button>
                            @if (request()->filled('name'))
                                <a href="{{ route('brands.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-times me-1"></i> Reset
                                </a>
                            @endif
                        </div>
                    </div>
                </form>

                {{-- Info bar --}}
                <div class="d-flex justify-content-between align-items-center mt-3 mb-2">
                    <small class="text-muted" style="font-size:0.75rem;">
                        Total <strong>{{ $brands->total() }}</strong> merk
                    </small>
                </div>

                {{-- Tabel --}}
                <div class="table-responsive rounded border border-secondary border-opacity-10">
                    <table class="table table-bordered table-premium-dark align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 70px;">#</th>
                                <th>Nama Merk</th>
                                <th>Jumlah Produk</th>
                                <th class="text-center" style="width: 150px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($brands as $i => $brand)
                                <tr>
                                    <td class="text-center">
                                        <span
                                            class="badge rounded-circle bg-secondary bg-opacity-25 text-secondary fw-semibold"
                                            style="width:20px;height:20px;display:inline-flex;align-items:center;justify-content:center;font-size:0.65rem;">
                                            {{ $brands->firstItem() + $i }}
                                        </span>
                                    </td>
                                    <td>
                                        <i class="fas fa-certificate me-2 text-primary"></i>
                                        <span class="fw-semibold" style="font-size:0.82rem;">{{ $brand->name }}</span>
                                    </td>
                                    <td>
                                        @php $prodCount = $brand->products()->count(); @endphp
                                        @if ($prodCount > 0)
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                                <i class="fas fa-box me-1"></i>{{ $prodCount }} produk
                                            </span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex gap-1 justify-content-center">
                                            <button type="button" class="btn btn-warning btn-action-sm edit-brand-btn"
                                                title="Edit" data-bs-toggle="modal" data-bs-target="#editBrandModal"
                                                data-id="{{ $brand->id }}" data-name="{{ $brand->name }}"
                                                data-action="{{ route('brands.update', $brand) }}"
                                                data-delete-action="{{ route('brands.destroy', $brand) }}">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            <form action="{{ route('brands.destroy', $brand) }}" method="POST"
                                                class="confirm-delete d-inline"
                                                data-message="Merk ini akan dihapus secara permanen!">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-action-sm" title="Hapus"
                                                    data-bs-toggle="tooltip">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-5">
                                        <i class="fas fa-certificate fa-2x mb-3 d-block text-secondary opacity-25"></i>
                                        <p class="text-muted mb-0 small">Belum ada merk.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="mt-3">
                    {{ $brands->links('pagination::bootstrap-5') }}
                </div>

            </div>
        </div>
    </div>

    {{-- Modal Tambah Merk --}}
    <div class="modal fade" id="createBrandModal" tabindex="-1" aria-labelledby="createBrandModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content overflow-hidden">
                <div class="d-flex align-items-center gap-3 p-3 border-bottom bg-primary bg-opacity-10">
                    <div class="bg-primary text-white rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 fs-5 p-2"
                        style="width: 38px; height: 38px;">
                        <i class="fas fa-plus"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="modal-title fw-bold fs-6 mb-0" id="createBrandModalLabel">Tambah Merk</h5>
                        <p class="mb-0 text-muted small">Tambahkan merk produk baru</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('brands.store') }}" method="POST">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label for="create-name" class="form-label fw-semibold">
                                <i class="fas fa-certificate me-1 text-primary"></i>
                                Nama Merk <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="create-name" name="name"
                                class="form-control form-control-sm @error('name') is-invalid @enderror"
                                placeholder="Contoh: Asus, Samsung, Polytron..." value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer bg-primary bg-opacity-10 px-4 py-3 border-top d-flex justify-content-between">
                        <button type="button"
                            class="btn btn-secondary btn-sm px-3 d-inline-flex align-items-center gap-1"
                            data-bs-dismiss="modal">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-primary btn-sm px-4 d-inline-flex align-items-center gap-1">
                            <i class="fas fa-save"></i> Tambah Merk
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Edit Merk --}}
    <div class="modal fade" id="editBrandModal" tabindex="-1" aria-labelledby="editBrandModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content overflow-hidden">
                <div class="d-flex align-items-center gap-3 p-3 border-bottom bg-warning bg-opacity-10">
                    <div class="bg-warning text-dark rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 fs-5 p-2"
                        style="width: 38px; height: 38px;">
                        <i class="fas fa-pencil-alt"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="modal-title fw-bold fs-6 mb-0" id="editBrandModalLabel">Edit Merk</h5>
                        <p class="mb-0 text-muted small">Perbarui nama merk produk</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="edit-form" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label for="edit-name" class="form-label fw-semibold">
                                <i class="fas fa-certificate me-1 text-primary"></i>
                                Nama Merk <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="edit-name" name="name"
                                class="form-control form-control-sm @error('name') is-invalid @enderror"
                                placeholder="Contoh: Asus, Samsung, Polytron..." required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer bg-warning bg-opacity-10 px-4 py-3 border-top d-flex justify-content-between">
                        <button type="button"
                            class="btn btn-secondary btn-sm px-3 d-inline-flex align-items-center gap-1"
                            data-bs-dismiss="modal">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-primary btn-sm px-4 d-inline-flex align-items-center gap-1">
                            <i class="fas fa-save"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>

                {{-- Danger Zone (Hapus) --}}
                <div class="border-top px-4 py-3 bg-danger bg-opacity-5">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="fw-semibold text-danger small">
                                <i class="fas fa-exclamation-triangle me-1"></i> Hapus Merk
                            </div>
                            <div class="text-muted small" style="font-size: 0.75rem;">
                                Pastikan tidak ada produk menggunakan merk ini
                            </div>
                        </div>
                        <form id="delete-form" class="confirm-delete"
                            data-message="Merk ini akan dihapus secara permanen!" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger px-3 ms-3">
                                <i class="fas fa-trash me-1"></i> Hapus
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                $('.edit-brand-btn').on('click', function() {
                    const name = $(this).data('name');
                    const action = $(this).data('action');
                    const deleteAction = $(this).data('delete-action');

                    $('#edit-name').val(name);
                    $('#edit-form').attr('action', action);
                    $('#delete-form').attr('action', deleteAction);
                });
            });
        </script>
    @endpush
@endsection
