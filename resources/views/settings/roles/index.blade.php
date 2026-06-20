@extends('layouts.app')
@section('title', 'Hak Akses & Role')
@section('page-title', 'Hak Akses & Role')

@section('content')
    <div class="row">
        <!-- Sidebar Deskripsi Hak Akses -->
        <div class="col-md-4 mb-4">
            <div class="dashboard-card h-100">
                <h5 class="text-primary mb-3">
                    <i class="fas fa-shield-alt me-2"></i> Manajemen Hak Akses
                </h5>
                <p class="text-muted small">
                    Di halaman ini, Anda dapat mengelola level pengguna (*Role*) dan permission untuk
                    setiap peran di sistem Anda.
                </p>
                <hr class="border-secondary border-opacity-10 my-3">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-primary text-white rounded p-2 me-3 d-flex align-items-center justify-content-center fs-5">
                        <i class="fas fa-lock-open"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-semibold text-white small">Izin Modular</h6>
                        <small class="text-muted">Akses dibatasi per modul operasional.</small>
                    </div>
                </div>
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-success text-white rounded p-2 me-3 d-flex align-items-center justify-content-center fs-5">
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-semibold text-white small">Multi-Tenant</h6>
                        <small class="text-muted">Perubahan hanya berdampak pada tenant Anda.</small>
                    </div>
                </div>
                <div class="d-flex align-items-center">
                    <div class="bg-warning text-dark rounded p-2 me-3 d-flex align-items-center justify-content-center fs-5">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-semibold text-white small">Akses Admin Absolut</h6>
                        <small class="text-muted">Role 'admin' selalu memiliki semua permission.</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Daftar Role -->
        <div class="col-md-8 mb-4">
            <div class="dashboard-card">
                <div class="card-header-line d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0"><i class="fas fa-users-cog me-2 text-primary"></i>Daftar Role Hak Akses</h5>
                        <p class="text-muted mb-0 mt-1 small">Kelola level pengguna (role) dan permission</p>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm px-3" data-bs-toggle="modal"
                        data-bs-target="#addRoleModal">
                        <i class="fas fa-plus me-1"></i> Tambah Role
                    </button>
                </div>

                <div class="table-responsive rounded border border-secondary border-opacity-10 mt-3">
                    <table class="table table-sm table-bordered table-premium-dark align-middle mb-0">
                        <thead>
                            <tr>
                                <th>NAMA ROLE</th>
                                <th>GUARD</th>
                                <th>PERMISSION</th>
                                <th class="text-center">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($roles as $r)
                                <tr>
                                    <td>
                                        <strong class="text-white text-capitalize small">{{ $r->name }}</strong>
                                    </td>
                                    <td><code class="text-info font-monospace small">{{ $r->guard_name }}</code></td>
                                    <td>
                                        @if ($r->name === 'admin')
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-10 small">
                                                Semua Izin (Absolut)
                                            </span>
                                        @else
                                            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-10 small">
                                                {{ $r->permissions->count() }} Izin
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex gap-1 justify-content-center">
                                            <button type="button" class="btn btn-warning btn-action-sm edit-role-btn"
                                                title="Edit Izin" data-id="{{ $r->id }}"
                                                data-name="{{ $r->name }}"
                                                data-permissions="{{ json_encode($r->permissions->pluck('name')) }}">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            @if ($r->name !== 'admin')
                                                <form action="{{ route('roles.destroy', $r->id) }}" method="POST"
                                                    class="confirm-delete d-inline"
                                                    data-message="Hapus role ini? User yang menggunakan role ini tidak akan memiliki akses hingga role baru ditetapkan.">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-action-sm"
                                                        title="Hapus">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Role -->
    <div class="modal fade" id="addRoleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form action="{{ route('roles.store') }}" method="POST">
                @csrf
                <div class="modal-content overflow-hidden">
                    <div class="modal-header d-flex align-items-center gap-3 p-3 bg-primary bg-opacity-10">
                        <div class="bg-primary text-white rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 p-2 fs-5">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="modal-title fw-bold fs-6 mb-0">Tambah Role Baru</h5>
                            <p class="mb-0 text-muted small">Buat level akses pengguna baru</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label form-label-sm fw-semibold">Nama Role <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control form-control-sm" required
                                placeholder="Contoh: supervisor, staf-packing">
                        </div>

                        <h6 class="mt-4 mb-3 fw-bold small text-secondary"><i class="fas fa-key me-2"></i>Tetapkan Permission</h6>

                        @foreach ($permissionGroups as $groupName => $perms)
                            <div class="card mb-3 border border-secondary border-opacity-10 bg-transparent overflow-hidden">
                                <div class="card-header d-flex justify-content-between align-items-center py-2 bg-light bg-opacity-10 border-bottom border-secondary border-opacity-10">
                                    <span class="fw-semibold text-white small"><i
                                            class="fas fa-folder me-1 text-primary"></i>
                                        {{ $groupName }}</span>
                                    <button type="button"
                                        class="btn btn-link btn-xs text-info p-0 text-decoration-none select-all-btn small">Pilih Semua</button>
                                </div>
                                <div class="card-body py-2">
                                    <div class="row">
                                        @foreach ($perms as $key => $label)
                                            <div class="col-md-6 my-1">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input perm-checkbox" type="checkbox"
                                                        name="permissions[]" value="{{ $key }}"
                                                        id="add_{{ $key }}">
                                                    <label class="form-check-label small" for="add_{{ $key }}">{{ $label }}</label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="modal-footer bg-primary bg-opacity-10 px-4 py-3 d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary btn-sm px-3"
                            data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4">Simpan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Role & Izin -->
    <div class="modal fade" id="editRoleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form id="editRoleForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-content overflow-hidden">
                    <div class="modal-header d-flex align-items-center gap-3 p-3 bg-warning bg-opacity-10">
                        <div class="bg-warning text-dark rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 p-2 fs-5">
                            <i class="fas fa-edit"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="modal-title fw-bold fs-6 mb-0" id="editModalTitle">Edit Permission Role</h5>
                            <p class="mb-0 text-muted small">Perbarui konfigurasi permission role</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label form-label-sm fw-semibold">Nama Role <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="name" id="editRoleName" class="form-control form-control-sm"
                                required>
                            <small class="text-warning d-none" id="adminWarning">Nama role 'admin' tidak dapat diubah
                                namanya.</small>
                        </div>

                        <h6 class="mt-4 mb-3 fw-bold small text-secondary"><i class="fas fa-key me-2"></i>Konfigurasi Permission</h6>

                        <div id="adminPermissionsOverlay" class="alert alert-info py-2 d-none small">
                            <i class="fas fa-info-circle me-1"></i> Pengguna dengan level <strong>Admin</strong> selalu
                            memiliki semua hak akses sistem tanpa batas.
                        </div>

                        <div id="permissionsContainer">
                            @foreach ($permissionGroups as $groupName => $perms)
                                <div
                                    class="card mb-3 border border-secondary border-opacity-10 bg-transparent overflow-hidden">
                                    <div class="card-header d-flex justify-content-between align-items-center py-2 bg-light bg-opacity-10 border-bottom border-secondary border-opacity-10">
                                        <span class="fw-semibold text-white small"><i
                                                class="fas fa-folder me-1 text-primary"></i>
                                            {{ $groupName }}</span>
                                        <button type="button"
                                            class="btn btn-link btn-xs text-info p-0 text-decoration-none select-all-btn small">Pilih Semua</button>
                                    </div>
                                    <div class="card-body py-2">
                                        <div class="row">
                                            @foreach ($perms as $key => $label)
                                                <div class="col-md-6 my-1">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input perm-checkbox edit-perm-checkbox"
                                                            type="checkbox" name="permissions[]"
                                                            value="{{ $key }}" id="edit_{{ $key }}">
                                                        <label class="form-check-label small"
                                                            for="edit_{{ $key }}">{{ $label }}</label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="modal-footer bg-warning bg-opacity-10 px-4 py-3 d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary btn-sm px-3"
                            data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4">Simpan Perubahan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                // Select/Deselect All buttons inside permission cards
                $(document).on('click', '.select-all-btn', function() {
                    const card = $(this).closest('.card');
                    const checkboxes = card.find('.perm-checkbox');
                    let allChecked = true;
                    
                    checkboxes.each(function() {
                        if (!this.checked) allChecked = false;
                    });

                    checkboxes.each(function() {
                        $(this).prop('checked', !allChecked);
                    });

                    $(this).text(allChecked ? 'Pilih Semua' : 'Batal Pilih');
                });

                // Edit role handler
                $(document).on('click', '.edit-role-btn', function() {
                    const id = $(this).data('id');
                    const name = $(this).data('name');
                    const permissions = $(this).data('permissions');

                    $('#editRoleForm').attr('action', '/roles/' + id);
                    $('#editRoleName').val(name);
                    $('#editModalTitle').text('Edit Permission: ' + name.charAt(0).toUpperCase() + name.slice(1));

                    // Reset all edit checkboxes
                    $('.edit-perm-checkbox').prop('checked', false);
                    $('#editRoleModal .select-all-btn').text('Pilih Semua');

                    if (name === 'admin') {
                        $('#adminWarning').removeClass('d-none');
                        $('#adminPermissionsOverlay').removeClass('d-none');
                        $('#permissionsContainer').addClass('d-none');
                        $('#editRoleName').prop('readonly', true);
                    } else {
                        $('#adminWarning').addClass('d-none');
                        $('#adminPermissionsOverlay').addClass('d-none');
                        $('#permissionsContainer').removeClass('d-none');
                        $('#editRoleName').prop('readonly', false);

                        // Check the role's current permissions
                        if (permissions && permissions.length > 0) {
                            permissions.forEach(function(permName) {
                                $('#edit_' + permName).prop('checked', true);
                            });
                        }
                    }

                    var myModal = new bootstrap.Modal(document.getElementById('editRoleModal'));
                    myModal.show();
                });
            });
        </script>
    @endpush
@endsection
