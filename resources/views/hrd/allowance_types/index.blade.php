@extends('layouts.app')
@section('title', 'Master Tunjangan')
@section('page-title', 'Pengaturan Tunjangan Kustom')

@section('content')
    {{-- Filter Row (Terpisah di Atas) --}}
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="card border shadow-sm p-3">
                <form method="GET" action="{{ route('hr.allowance-types.index') }}">
                    <div class="row g-2 align-items-end">
                        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                            <label class="form-label small">
                                <i class="fas fa-search me-1"></i>Nama Tunjangan
                            </label>
                            <input type="text" name="name" class="form-control form-control-sm"
                                placeholder="Cari nama tunjangan..." value="{{ request('name') }}">
                        </div>

                        @if ($isSuperAdmin)
                            <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                                <label class="form-label small">
                                    <i class="fas fa-building me-1"></i>Perusahaan / Toko
                                </label>
                                <select name="tenant_id" class="form-select form-select-sm select2">
                                    <option value="">-- Semua --</option>
                                    @foreach ($tenants as $t)
                                        <option value="{{ $t->id }}"
                                            {{ $selectedTenantId == $t->id ? 'selected' : '' }}>
                                            {{ $t->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="col-12 col-sm-6 col-md-auto d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-search me-1"></i> Cari
                            </button>
                            @if (request()->filled('name') || request()->filled('tenant_id'))
                                <a href="{{ route('hr.allowance-types.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-times me-1"></i> Reset
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Form Master Tunjangan (Kiri) -->
        <div class="col-md-4 mb-4">
            <div class="card border shadow-sm p-0 overflow-hidden">
                <div class="card-header bg-primary bg-opacity-10 d-flex align-items-center gap-3 p-3 border-bottom">
                    <div class="bg-primary text-white rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 fs-5 p-2"
                        style="width: 36px; height: 36px;">
                        <i class="fas fa-coins" style="font-size: 1rem;"></i>
                    </div>
                    <h6 class="mb-0 fw-bold text-dark" id="formTitle">Tambah Jenis Tunjangan</h6>
                </div>

                {{-- Validation Errors --}}
                @if ($errors->any())
                    <div class="p-3 pb-0">
                        <div class="alert alert-danger alert-dismissible fade show small" role="alert">
                            <strong><i class="fas fa-exclamation-triangle me-2"></i>Periksa inputan Anda:</strong>
                            <ul class="mb-0 mt-1 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    </div>
                @endif

                <form id="allowanceTypeForm" method="POST" action="{{ route('hr.allowance-types.store') }}" class="p-3">
                    @csrf
                    <input type="hidden" name="_method" id="formMethod" value="POST">

                    @if ($isSuperAdmin)
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-dark"><i
                                    class="fas fa-building text-primary me-1"></i> Perusahaan / Toko <span
                                    class="text-danger">*</span></label>
                            <select name="tenant_id" id="tenant_id" class="form-select form-select-sm" required>
                                <option value="">-- Pilih Perusahaan --</option>
                                @foreach ($tenants as $t)
                                    <option value="{{ $t->id }}" {{ $selectedTenantId == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark"><i class="fas fa-tag text-primary me-1"></i>
                            Nama Tunjangan <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control form-control-sm"
                            placeholder="Contoh: Tunjangan Makan" required>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm px-3 rounded-3">Simpan</button>
                        <button type="button" class="btn btn-secondary btn-sm px-3" id="btnCancel"
                            style="display:none;">Batal</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabel Daftar Jenis Tunjangan (Kanan) -->
        <div class="col-md-8">
            <div class="card border shadow-sm p-0 overflow-hidden">
                <div class="card-header bg-info bg-opacity-10 d-flex align-items-center gap-3 p-3 border-bottom">
                    <div class="bg-info text-white rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 fs-5 p-2"
                        style="width: 36px; height: 36px;">
                        <i class="fas fa-list" style="font-size: 1rem;"></i>
                    </div>
                    <h6 class="mb-0 fw-bold text-dark">Daftar Tunjangan Aktif</h6>
                </div>

                <div class="card-body p-3">
                    {{-- Alert --}}
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="table-responsive rounded border">
                        <table class="table table-sm table-striped table-bordered align-middle mb-0">
                            <thead>
                                <tr class="small">
                                    @if ($isSuperAdmin)
                                        <th>PERUSAHAAN / TOKO</th>
                                    @endif
                                    <th>NAMA TUNJANGAN</th>
                                    <th class="text-center" style="width: 120px;">AKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($allowanceTypes as $type)
                                    <tr>
                                        @if ($isSuperAdmin)
                                            <td>
                                                <span
                                                    class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                                    <i class="fas fa-building me-1" style="font-size:0.65rem;"></i>
                                                    {{ $type->tenant->name ?? '—' }}
                                                </span>
                                            </td>
                                        @endif
                                        <td><strong class="text-dark small">{{ $type->name }}</strong></td>
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <button type="button"
                                                    class="btn btn-warning btn-sm edit-allowance-btn"
                                                    title="Edit" data-id="{{ $type->id }}"
                                                    data-name="{{ $type->name }}"
                                                    @if ($isSuperAdmin) data-tenant-id="{{ $type->tenant_id }}" @endif>
                                                    <i class="fas fa-pen"></i>
                                                </button>
                                                <form action="{{ route('hr.allowance-types.destroy', $type->id) }}"
                                                    method="POST" class="confirm-delete d-inline"
                                                    data-message="Hapus jenis tunjangan ini? Semua tunjangan ini yang melekat pada karyawan juga akan terhapus.">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm"
                                                        title="Hapus">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $isSuperAdmin ? 3 : 2 }}" class="text-center py-5 text-muted">
                                            <i class="fas fa-folder-open fa-2x mb-3 d-block text-secondary opacity-25"></i>
                                            <p class="mb-0 small">Belum ada jenis tunjangan kustom yang terdaftar.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div> {{-- End card-body --}}
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                // Edit Handler
                $('.edit-allowance-btn').on('click', function() {
                    const id = $(this).data('id');
                    const name = $(this).data('name');
                    const tenantId = $(this).data('tenant-id');

                    $('#formTitle').text('Edit Jenis Tunjangan');
                    $('#formMethod').val('PUT');
                    $('#allowanceTypeForm').attr('action', '/hr/allowance-types/' + id);

                    $('#name').val(name);

                    if (tenantId !== undefined) {
                        $('#tenant_id').val(tenantId).trigger('change');
                    }

                    $('#btnCancel').show();
                });

                // Cancel Button Click
                $('#btnCancel').on('click', function() {
                    resetForm();
                });

                function resetForm() {
                    $('#formTitle').text('Tambah Jenis Tunjangan');
                    $('#formMethod').val('POST');
                    $('#allowanceTypeForm').attr('action', '{{ route('hr.allowance-types.store') }}');

                    $('#name').val('');
                    if ($('#tenant_id').length) {
                        $('#tenant_id').val('').trigger('change');
                    }

                    $('#btnCancel').hide();
                }
            });
        </script>
    @endpush
@endsection
