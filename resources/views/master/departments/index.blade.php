@extends('layouts.app')
@section('title', 'Master Departemen')
@section('page-title', 'Master Departemen')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card border shadow-sm overflow-hidden">

                {{-- Header --}}
                <div
                    class="card-header bg-info bg-opacity-10 d-flex justify-content-between align-items-center border-bottom py-2 px-3">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="fas fa-building me-2 text-info"></i>Daftar Departemen
                        </h6>
                        <small class="text-muted d-block">Kelola unit kerja / departemen perusahaan</small>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm px-3 rounded-3" data-bs-toggle="modal"
                        data-bs-target="#createDepartmentModal">
                        <i class="fas fa-plus me-1"></i> Tambah Departemen
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

                    {{-- Validation/Delete Errors --}}
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                            <strong><i class="fas fa-exclamation-triangle me-2"></i>Terjadi Kesalahan:</strong>
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
                        <form method="GET" action="{{ route('departments.index') }}">
                            <div class="row g-2 align-items-end">
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                    <label class="form-label small">
                                        <i class="fas fa-building me-1"></i>Nama Departemen
                                    </label>
                                    <input type="text" name="name" class="form-control form-control-sm"
                                        placeholder="Cari nama departemen..." value="{{ request('name') }}">
                                </div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                    <label class="form-label small">
                                        <i class="fas fa-barcode me-1"></i>Kode Departemen
                                    </label>
                                    <input type="text" name="code" class="form-control form-control-sm"
                                        placeholder="Cari kode..." value="{{ request('code') }}">
                                </div>
                                <div class="col-12 col-sm-6 col-md-auto d-flex gap-2">
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="fas fa-search me-1"></i> Cari
                                    </button>
                                    @if (request()->anyFilled(['name', 'code']))
                                        <a href="{{ route('departments.index') }}" class="btn btn-secondary btn-sm">
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
                                <tr class="small text-uppercase">
                                    <th class="text-center" style="width: 70px;">#</th>
                                    <th style="width: 150px;">Kode</th>
                                    <th>Nama Departemen</th>
                                    <th class="text-center" style="width: 120px;">Status</th>
                                    <th class="text-center" style="width: 150px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($departments as $i => $dept)
                                    <tr>
                                        <td class="text-center">
                                            <span class="badge bg-light text-secondary border small">
                                                {{ $departments->firstItem() + $i }}
                                            </span>
                                        </td>
                                        <td>
                                            <code class="bg-light text-primary px-2 py-1 rounded border small fw-bold">{{ $dept->code ?: '-' }}</code>
                                        </td>
                                        <td>
                                            <i class="fas fa-building me-2 text-secondary"></i>
                                            <span class="fw-semibold text-dark small">{{ $dept->name }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if ($dept->is_active)
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 small">
                                                    Aktif
                                                </span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 small">
                                                    Non-Aktif
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <button type="button" class="btn btn-warning btn-sm edit-dept-btn"
                                                    title="Edit" data-bs-toggle="modal"
                                                    data-bs-target="#editDepartmentModal" data-id="{{ $dept->id }}"
                                                    data-name="{{ $dept->name }}" data-code="{{ $dept->code }}"
                                                    data-status="{{ $dept->is_active ? 1 : 0 }}"
                                                    data-action="{{ route('departments.update', $dept) }}">
                                                    <i class="fas fa-pen"></i>
                                                </button>
                                                <form action="{{ route('departments.destroy', $dept) }}" method="POST"
                                                    class="confirm-delete d-inline"
                                                    data-message="Departemen ini akan dihapus secara permanen!">
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
                                        <td colspan="5" class="text-center py-5">
                                            <i class="fas fa-building fa-2x mb-3 d-block text-secondary opacity-25"></i>
                                            <p class="text-muted mb-0 small">Belum ada data departemen.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    <div class="mt-3">
                        {{ $departments->links('pagination::bootstrap-5') }}
                    </div>

                </div> {{-- End card-body --}}
            </div> {{-- End card --}}
        </div>
    </div>

    {{-- Modal Tambah Departemen --}}
    <div class="modal fade" id="createDepartmentModal" tabindex="-1" aria-labelledby="createDepartmentModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content overflow-hidden">
                <div class="d-flex align-items-center gap-3 p-3 border-bottom bg-primary bg-opacity-10">
                    <div class="bg-primary text-white rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 fs-5 p-2"
                        style="width: 38px; height: 38px;">
                        <i class="fas fa-plus"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="modal-title fw-bold fs-6 mb-0 text-dark" id="createDepartmentModalLabel">Tambah Departemen</h5>
                        <p class="mb-0 text-muted small">Tambahkan departemen baru dalam unit perusahaan</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('departments.store') }}" method="POST">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label for="create-code" class="form-label fw-bold small text-dark">
                                <i class="fas fa-barcode me-1 text-primary"></i>
                                Kode Departemen
                            </label>
                            <input type="text" id="create-code" name="code"
                                class="form-control form-control-sm @error('code') is-invalid @enderror"
                                placeholder="Contoh: FIN, MKT, WH-FGD..." value="{{ old('code') }}">
                            @error('code')
                                <div class="invalid-feedback small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="create-name" class="form-label fw-bold small text-dark">
                                <i class="fas fa-building me-1 text-primary"></i>
                                Nama Departemen <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="create-name" name="name"
                                class="form-control form-control-sm @error('name') is-invalid @enderror"
                                placeholder="Contoh: Keuangan, Pemasaran, Produksi..." value="{{ old('name') }}"
                                required>
                            @error('name')
                                <div class="invalid-feedback small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" id="create-is-active" name="is_active" value="1" checked>
                            <label class="form-check-label small fw-bold text-dark" for="create-is-active">Departemen Aktif</label>
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
                            <i class="fas fa-save"></i> Simpan Departemen
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Edit Departemen --}}
    <div class="modal fade" id="editDepartmentModal" tabindex="-1" aria-labelledby="editDepartmentModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content overflow-hidden">
                <div class="d-flex align-items-center gap-3 p-3 border-bottom bg-warning bg-opacity-10">
                    <div class="bg-warning text-dark rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 fs-5 p-2"
                        style="width: 38px; height: 38px;">
                        <i class="fas fa-pen text-warning"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="modal-title fw-bold fs-6 mb-0 text-dark" id="editDepartmentModalLabel">Edit Departemen</h5>
                        <p class="mb-0 text-muted small">Perbarui data departemen perusahaan</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="edit-form" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label for="edit-code" class="form-label fw-bold small text-dark">
                                <i class="fas fa-barcode me-1 text-primary"></i>
                                Kode Departemen
                            </label>
                            <input type="text" id="edit-code" name="code"
                                class="form-control form-control-sm @error('code') is-invalid @enderror"
                                placeholder="Contoh: FIN, MKT, WH-FGD...">
                            @error('code')
                                <div class="invalid-feedback small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="edit-name" class="form-label fw-bold small text-dark">
                                <i class="fas fa-building me-1 text-primary"></i>
                                Nama Departemen <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="edit-name" name="name"
                                class="form-control form-control-sm @error('name') is-invalid @enderror"
                                placeholder="Contoh: Keuangan, Pemasaran, Produksi..." required>
                            @error('name')
                                <div class="invalid-feedback small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" id="edit-is-active" name="is_active" value="1">
                            <label class="form-check-label small fw-bold text-dark" for="edit-is-active">Departemen Aktif</label>
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
                $('.edit-dept-btn').on('click', function() {
                    const name = $(this).data('name');
                    const code = $(this).data('code');
                    const status = $(this).data('status');
                    const action = $(this).data('action');

                    $('#edit-name').val(name);
                    $('#edit-code').val(code);
                    $('#edit-is-active').prop('checked', status == 1);
                    $('#edit-form').attr('action', action);
                });
            });
        </script>
    @endpush
@endsection
