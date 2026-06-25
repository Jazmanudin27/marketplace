@extends('layouts.app')
@section('title', 'Master Kategori')
@section('page-title', 'Master Kategori')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card border shadow-sm overflow-hidden">

                {{-- Header --}}
                <div
                    class="card-header bg-info bg-opacity-10 d-flex justify-content-between align-items-center border-bottom py-2 px-3">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="fas fa-folder me-2 text-info"></i>Daftar Kategori
                        </h6>
                        <small class="text-muted d-block">Kelola kategori produk</small>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm px-3 rounded-3" data-bs-toggle="modal"
                        data-bs-target="#createCategoryModal">
                        <i class="fas fa-plus me-1"></i> Tambah Kategori
                    </button>
                </div>

                <div class="card-body p-3">
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
                    <div class="card border shadow-sm p-3 mb-3">
                        <form method="GET" action="{{ route('categories.index') }}">
                            <div class="row g-2 align-items-end">
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                    <label class="form-label small">
                                        <i class="fas fa-folder me-1"></i>Nama Kategori
                                    </label>
                                    <input type="text" name="name" class="form-control form-control-sm"
                                        placeholder="Cari nama kategori..." value="{{ request('name') }}">
                                </div>
                                <div class="col-12 col-sm-6 col-md-auto d-flex gap-2">
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="fas fa-search me-1"></i> Cari
                                    </button>
                                    @if (request()->filled('name'))
                                        <a href="{{ route('categories.index') }}" class="btn btn-secondary btn-sm">
                                            <i class="fas fa-times me-1"></i> Reset
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </form>
                    </div>

                    {{-- Tabel --}}
                    <div class="table-responsive rounded border mt-3">
                        <table class="table table-sm table-striped table-bordered align-middle mb-0">
                            <thead>
                                <tr class="small">
                                    <th class="text-center" style="width: 70px;">#</th>
                                    <th>Nama Kategori</th>
                                    <th>Jumlah Produk</th>
                                    <th class="text-center" style="width: 150px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($categories as $i => $category)
                                    <tr>
                                        <td class="text-center">
                                            <span class="badge bg-light text-secondary border small">
                                                {{ $categories->firstItem() + $i }}
                                            </span>
                                        </td>
                                        <td>
                                            <i class="fas fa-folder me-2 text-secondary"></i>
                                            <span class="fw-semibold text-dark small">{{ $category->name }}</span>
                                        </td>
                                        <td>
                                            @php $prodCount = $category->products()->count(); @endphp
                                            @if ($prodCount > 0)
                                                <span
                                                    class="badge bg-primary-subtle text-primary border border-primary-subtle small">
                                                    <i class="fas fa-box me-1"></i>{{ $prodCount }} produk
                                                </span>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <button type="button" class="btn btn-warning btn-sm edit-category-btn"
                                                    title="Edit" data-bs-toggle="modal"
                                                    data-bs-target="#editCategoryModal" data-id="{{ $category->id }}"
                                                    data-name="{{ $category->name }}"
                                                    data-action="{{ route('categories.update', $category) }}"
                                                    data-delete-action="{{ route('categories.destroy', $category) }}">
                                                    <i class="fas fa-pen"></i>
                                                </button>
                                                <form action="{{ route('categories.destroy', $category) }}" method="POST"
                                                    class="confirm-delete d-inline"
                                                    data-message="Kategori ini akan dihapus secara permanen!">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" title="Hapus"
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
                                            <i class="fas fa-folder-open fa-2x mb-3 d-block text-secondary opacity-25"></i>
                                            <p class="text-muted mb-0 small">Belum ada kategori.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    <div class="mt-3">
                        {{ $categories->links('pagination::bootstrap-5') }}
                    </div>

                </div> {{-- End card-body --}}
            </div> {{-- End card --}}
        </div>
    </div>

    {{-- Modal Tambah Kategori --}}
    <div class="modal fade" id="createCategoryModal" tabindex="-1" aria-labelledby="createCategoryModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content overflow-hidden">
                <div class="d-flex align-items-center gap-3 p-3 border-bottom bg-primary bg-opacity-10">
                    <div class="bg-primary text-white rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 fs-5 p-2"
                        style="width: 38px; height: 38px;">
                        <i class="fas fa-folder-plus"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="modal-title fw-bold fs-6 mb-0 text-dark" id="createCategoryModalLabel">Tambah Kategori
                        </h5>
                        <p class="mb-0 text-muted small">Tambahkan kategori produk baru</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('categories.store') }}" method="POST">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label for="create-name" class="form-label fw-bold small text-dark">
                                <i class="fas fa-folder me-1 text-primary"></i>
                                Nama Kategori <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="create-name" name="name"
                                class="form-control form-control-sm @error('name') is-invalid @enderror"
                                placeholder="Contoh: Elektronik, Bahan Baku, Peralatan..." value="{{ old('name') }}"
                                required>
                            @error('name')
                                <div class="invalid-feedback small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer bg-primary bg-opacity-10 px-4 py-3 border-top d-flex justify-content-between">
                        <button type="button"
                            class="btn btn-secondary btn-sm px-3 d-inline-flex align-items-center gap-1"
                            data-bs-dismiss="modal">
                            Batal
                        </button>
                        <button type="submit"
                            class="btn btn-primary btn-sm px-4 d-inline-flex align-items-center gap-1 rounded-3">
                            <i class="fas fa-save"></i> Simpan Kategori
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Edit Kategori --}}
    <div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content overflow-hidden">
                <div class="d-flex align-items-center gap-3 p-3 border-bottom bg-warning bg-opacity-10">
                    <div class="bg-warning text-dark rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 fs-5 p-2"
                        style="width: 38px; height: 38px;">
                        <i class="fas fa-folder-open text-warning"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="modal-title fw-bold fs-6 mb-0 text-dark" id="editCategoryModalLabel">Edit Kategori</h5>
                        <p class="mb-0 text-muted small">Perbarui nama kategori produk</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="edit-form" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label for="edit-name" class="form-label fw-bold small text-dark">
                                <i class="fas fa-folder me-1 text-primary"></i>
                                Nama Kategori <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="edit-name" name="name"
                                class="form-control form-control-sm @error('name') is-invalid @enderror"
                                placeholder="Contoh: Elektronik, Bahan Baku, Peralatan..." required>
                            @error('name')
                                <div class="invalid-feedback small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer bg-warning bg-opacity-10 px-4 py-3 border-top d-flex justify-content-between">
                        <button type="button"
                            class="btn btn-secondary btn-sm px-3 d-inline-flex align-items-center gap-1"
                            data-bs-dismiss="modal">
                            Batal
                        </button>
                        <button type="submit"
                            class="btn btn-primary btn-sm px-4 d-inline-flex align-items-center gap-1 rounded-3">
                            <i class="fas fa-save"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                $('.edit-category-btn').on('click', function() {
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
