@extends('layouts.app')
@section('title', 'Hak Akses & Role')
@section('page-title', 'Hak Akses & Role')

@section('content')
    <div class="row">
        <!-- Sidebar Deskripsi Hak Akses -->
        <div class="col-md-4 mb-4">
            <div class="card h-100" style="background:var(--bg-card); border-color:var(--border);">
                <div class="card-body">
                    <h5 class="card-title text-primary mb-3">
                        <i class="fas fa-shield-alt me-2"></i> Manajemen Hak Akses
                    </h5>
                    <p class="text-muted small">
                        Di halaman ini, Anda dapat mengelola level pengguna (*Role*) dan izin kerja (*Permissions*) untuk setiap peran kerja di sistem Anda.
                    </p>
                    <hr style="border-color:var(--border);">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary text-white rounded p-2 me-3" style="width:40px; height:40px; display:grid; place-items:center;">
                            <i class="fas fa-lock-open"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 text-white">Izin Modular</h6>
                            <small class="text-muted">Akses dibatasi per modul operasional.</small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-success text-white rounded p-2 me-3" style="width:40px; height:40px; display:grid; place-items:center;">
                            <i class="fas fa-users-cog"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 text-white">Multi-Tenant</h6>
                            <small class="text-muted">Perubahan hanya berdampak pada tenant Anda.</small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="bg-warning text-dark rounded p-2 me-3" style="width:40px; height:40px; display:grid; place-items:center;">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 text-white">Akses Admin Absolut</h6>
                            <small class="text-muted">Role 'admin' selalu memiliki semua izin kerja.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Daftar Role -->
        <div class="col-md-8 mb-4">
            <div class="card" style="background:var(--bg-card); border-color:var(--border);">
                <div class="card-header d-flex justify-content-between align-items-center border-bottom-0">
                    <h5 class="mb-0 text-white">Daftar Role Hak Akses</h5>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addRoleModal">
                        <i class="fas fa-plus me-1"></i> Tambah Role
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="color:var(--text-primary);">
                            <thead class="table-light" style="background:rgba(255,255,255,0.05);">
                                <tr>
                                    <th>Nama Role</th>
                                    <th>Guard</th>
                                    <th>Izin Aktif</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($roles as $r)
                                    <tr>
                                        <td class="fw-semibold text-white text-capitalize">
                                            {{ $r->name }}
                                            @if ($r->name === 'admin')
                                                <span class="badge bg-primary ms-2">Bawaan</span>
                                            @elseif(in_array($r->name, ['warehouse', 'finance']))
                                                <span class="badge bg-secondary ms-2">Standar</span>
                                            @endif
                                        </td>
                                        <td><code>{{ $r->guard_name }}</code></td>
                                        <td>
                                            @if($r->name === 'admin')
                                                <span class="badge bg-success">Semua Izin (Absolut)</span>
                                            @else
                                                <span class="badge bg-info text-white">{{ $r->permissions->count() }} Izin</span>
                                            @endif
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-info text-white" 
                                                onclick="editRole({{ $r->id }}, '{{ $r->name }}', {{ json_encode($r->permissions->pluck('name')) }})">
                                                <i class="fas fa-edit"></i> Edit Izin
                                            </button>
                                            @if ($r->name !== 'admin')
                                                <form action="{{ route('roles.destroy', $r->id) }}" method="POST"
                                                    style="display:inline;" onsubmit="return confirm('Hapus role ini? User yang menggunakan role ini tidak akan memiliki akses hingga role baru ditetapkan.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Role -->
    <div class="modal fade" id="addRoleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form action="{{ route('roles.store') }}" method="POST">
                @csrf
                <div class="modal-content" style="background:var(--bg-card); color:var(--text-primary); border-color:var(--border);">
                    <div class="modal-header border-bottom-0">
                        <h5 class="modal-title text-white">Tambah Role Baru</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label text-white">Nama Role</label>
                            <input type="text" name="name" class="form-control" required placeholder="Contoh: supervisor, staf-packing"
                                style="background:var(--bg-app); color:var(--text-primary); border-color:var(--border);">
                        </div>
                        
                        <h6 class="text-white mt-4 mb-3"><i class="fas fa-key me-2"></i>Tetapkan Izin Kerja (Permissions)</h6>
                        
                        @foreach($permissionGroups as $groupName => $perms)
                            <div class="card mb-3 border-0" style="background:rgba(255,255,255,0.02); border:1px solid var(--border) !important;">
                                <div class="card-header d-flex justify-content-between align-items-center py-2" style="background:rgba(255,255,255,0.04);">
                                    <span class="fw-semibold text-white small"><i class="fas fa-folder me-1"></i> {{ $groupName }}</span>
                                    <button type="button" class="btn btn-link btn-xs text-info p-0 text-decoration-none select-all-btn" onclick="toggleSelectAll(this)">Pilih Semua</button>
                                </div>
                                <div class="card-body py-2">
                                    <div class="row">
                                        @foreach($perms as $key => $label)
                                            <div class="col-md-6 my-1">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input perm-checkbox" type="checkbox" name="permissions[]" value="{{ $key }}" id="add_{{ $key }}">
                                                    <label class="form-check-label small" for="add_{{ $key }}" style="cursor:pointer;">{{ $label }}</label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @@endforeach
                    </div>
                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Role & Izin -->
    <div class="modal fade" id="editRoleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form id="editRoleForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-content" style="background:var(--bg-card); color:var(--text-primary); border-color:var(--border);">
                    <div class="modal-header border-bottom-0">
                        <h5 class="modal-title text-white" id="editModalTitle">Edit Izin Role</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label text-white">Nama Role</label>
                            <input type="text" name="name" id="editRoleName" class="form-control" required
                                style="background:var(--bg-app); color:var(--text-primary); border-color:var(--border);">
                            <small class="text-warning d-none" id="adminWarning">Nama role 'admin' tidak dapat diubah namanya.</small>
                        </div>
                        
                        <h6 class="text-white mt-4 mb-3"><i class="fas fa-key me-2"></i>Konfigurasi Izin Kerja (Permissions)</h6>
                        
                        <div id="adminPermissionsOverlay" class="alert alert-info py-2 d-none">
                            <i class="fas fa-info-circle me-1"></i> Pengguna dengan level <strong>Admin</strong> selalu memiliki semua hak akses sistem tanpa batas.
                        </div>

                        <div id="permissionsContainer">
                            @foreach($permissionGroups as $groupName => $perms)
                                <div class="card mb-3 border-0" style="background:rgba(255,255,255,0.02); border:1px solid var(--border) !important;">
                                    <div class="card-header d-flex justify-content-between align-items-center py-2" style="background:rgba(255,255,255,0.04);">
                                        <span class="fw-semibold text-white small"><i class="fas fa-folder me-1"></i> {{ $groupName }}</span>
                                        <button type="button" class="btn btn-link btn-xs text-info p-0 text-decoration-none select-all-btn" onclick="toggleSelectAll(this)">Pilih Semua</button>
                                    </div>
                                    <div class="card-body py-2">
                                        <div class="row">
                                            @foreach($perms as $key => $label)
                                                <div class="col-md-6 my-1">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input perm-checkbox edit-perm-checkbox" type="checkbox" name="permissions[]" value="{{ $key }}" id="edit_{{ $key }}">
                                                        <label class="form-check-label small" for="edit_{{ $key }}" style="cursor:pointer;">{{ $label }}</label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm">Simpan Perubahan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            function toggleSelectAll(btn) {
                const card = btn.closest('.card');
                const checkboxes = card.querySelectorAll('.perm-checkbox');
                let allChecked = true;
                
                checkboxes.forEach(cb => {
                    if(!cb.checked) allChecked = false;
                });

                checkboxes.forEach(cb => {
                    cb.checked = !allChecked;
                });

                btn.innerText = allChecked ? 'Pilih Semua' : 'Batal Pilih';
            }

            function editRole(id, name, permissions) {
                document.getElementById('editRoleForm').action = '/roles/' + id;
                document.getElementById('editRoleName').value = name;
                document.getElementById('editModalTitle').innerText = 'Edit Izin: ' + name.charAt(0).toUpperCase() + name.slice(1);

                // Reset all checkboxes
                const checkboxes = document.querySelectorAll('.edit-perm-checkbox');
                checkboxes.forEach(cb => {
                    cb.checked = false;
                });

                const selectAllBtns = document.querySelectorAll('#editRoleModal .select-all-btn');
                selectAllBtns.forEach(btn => {
                    btn.innerText = 'Pilih Semua';
                });

                const adminWarning = document.getElementById('adminWarning');
                const adminOverlay = document.getElementById('adminPermissionsOverlay');
                const permContainer = document.getElementById('permissionsContainer');
                const roleNameInput = document.getElementById('editRoleName');

                if (name === 'admin') {
                    adminWarning.classList.remove('d-none');
                    adminOverlay.classList.remove('d-none');
                    permContainer.classList.add('d-none');
                    roleNameInput.readOnly = true;
                } else {
                    adminWarning.classList.add('d-none');
                    adminOverlay.classList.add('d-none');
                    permContainer.classList.remove('d-none');
                    roleNameInput.readOnly = false;

                    // Centang permissions yang dimiliki oleh role ini
                    permissions.forEach(permName => {
                        const cb = document.getElementById('edit_' + permName);
                        if (cb) {
                            cb.checked = true;
                        }
                    });
                }

                var modal = new bootstrap.Modal(document.getElementById('editRoleModal'));
                modal.show();
            }
        </script>
    @endpush
@endsection
