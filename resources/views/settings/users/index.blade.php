@extends('layouts.app')
@section('title', 'Pengguna Sistem')
@section('page-title', 'Pengguna Sistem')

@section('content')
    <div class="row">
        <div class="col-md-12">

            {{-- ── Filter Bar ──────────────────────────────────────── --}}
            <div class="card border shadow-sm mb-3">
                <div class="card-body py-3 px-3">
                    <form method="GET" action="{{ route('users.index') }}" id="filterForm">

                        {{-- Super Admin: Pilih Tenant --}}
                        @if (auth()->user()->isSuperAdmin() && $tenants)
                            <div class="row g-2 mb-2">
                                <div class="col-12">
                                    <div class="d-flex align-items-center gap-3 flex-wrap">
                                        <span
                                            class="badge bg-primary bg-opacity-15 text-primary border border-primary border-opacity-25 flex-shrink-0 small">
                                            <i class="fas fa-shield-alt me-1"></i>Super Admin
                                        </span>
                                        <div class="d-flex align-items-center gap-2 flex-grow-1">
                                            <i class="fas fa-building text-muted small"></i>
                                            <span class="fw-semibold small text-dark text-nowrap">Filter Perusahaan:</span>
                                            <div class="flex-grow-1">
                                                <select name="tenant_id" id="filterTenant"
                                                    class="form-select form-select-sm select2"
                                                    data-placeholder="-- Semua Perusahaan --">
                                                    <option value="">-- Semua Perusahaan --</option>
                                                    @foreach ($tenants as $t)
                                                        <option value="{{ $t->id }}"
                                                            {{ request('tenant_id') == $t->id ? 'selected' : '' }}>
                                                            {{ $t->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <hr class="my-2">
                        @endif

                        {{-- Search + Role Filter --}}
                        <div class="row g-2 align-items-end">
                            {{-- Cari Nama / Email --}}
                            <div class="col-md-5">
                                <label class="form-label form-label-sm fw-semibold mb-1 text-dark">
                                    <i class="fas fa-search text-muted me-1"></i>Cari Nama / Email
                                </label>
                                <input type="text" name="search" id="filterSearch" class="form-control form-control-sm"
                                    placeholder="Ketik nama atau email..." value="{{ request('search') }}">
                            </div>

                            {{-- Filter Role --}}
                            <div class="col-md-3">
                                <label class="form-label form-label-sm fw-semibold mb-1 text-dark">
                                    <i class="fas fa-user-tag text-muted me-1"></i>Role / Posisi
                                </label>
                                <select name="role" id="filterRole" class="form-select form-select-sm select2"
                                    data-placeholder="-- Semua Role --">
                                    <option value="">-- Semua Role --</option>
                                    @foreach ($roleNames as $rn)
                                        <option value="{{ $rn }}" {{ request('role') === $rn ? 'selected' : '' }}>
                                            {{ $rn === 'admin' ? 'Admin (Owner)' : ($rn === 'warehouse' ? 'Staf Gudang' : ($rn === 'finance' ? 'Staf Keuangan' : ucfirst($rn))) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Tombol Filter --}}
                            <div class="col-md-auto">
                                <button type="submit" class="btn btn-primary btn-sm px-3">
                                    <i class="fas fa-search me-1"></i>Terapkan
                                </button>
                                <a href="{{ route('users.index') }}" class="btn btn-secondary btn-sm px-3 ms-1">
                                    <i class="fas fa-times me-1"></i>Reset
                                </a>
                            </div>

                            {{-- Info Hasil --}}
                            <div class="col-md ms-auto text-end">
                                <span class="text-muted small">
                                    Menampilkan <strong class="text-dark">{{ $users->count() }}</strong> pengguna
                                </span>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- ── Tabel Utama ─────────────────────────────────────── --}}
            <div class="card border shadow-sm">
                {{-- Header --}}
                <div class="card-header bg-light d-flex justify-content-between align-items-center py-2.5 px-3 border-bottom">
                    <div>
                        <h6 class="m-0 fw-bold text-primary"><i class="fas fa-user-cog me-2"></i>Daftar Pengguna Sistem</h6>
                        <p class="text-muted mb-0 small mt-1">
                            Kelola pengguna sistem, role, dan hak akses pengguna
                            @if (request('tenant_id') && auth()->user()->isSuperAdmin())
                                @php $tName = $tenants->firstWhere('id', request('tenant_id'))?->name; @endphp
                                @if ($tName)
                                    &mdash; <span class="text-primary fw-semibold">{{ $tName }}</span>
                                @endif
                            @endif
                        </p>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm px-3" data-bs-toggle="modal"
                        data-bs-target="#userModal">
                        <i class="fas fa-plus me-1"></i> Tambah Pengguna
                    </button>
                </div>

                <div class="card-body p-3">
                    {{-- Alert --}}
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                            <strong><i class="fas fa-exclamation-triangle me-2"></i>Periksa inputan Anda:</strong>
                            <ul class="mb-0 mt-1 ps-3 small">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    {{-- Tabel --}}
                    <div class="table-responsive rounded border mt-2">
                        <table class="table table-sm table-bordered table-striped table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>NAMA</th>
                                    <th>EMAIL</th>
                                    <th>ROLE / POSISI</th>
                                    @if (auth()->user()->isSuperAdmin())
                                        <th>PERUSAHAAN</th>
                                    @endif
                                    <th class="text-center">AKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $i => $u)
                                    <tr>
                                        <td class="text-center text-muted small">{{ $i + 1 }}</td>
                                        <td>
                                            <strong class="text-dark small">{{ $u->name }}</strong>
                                            @if ($u->id === Auth::id())
                                                <span
                                                    class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 ms-1 small">Anda</span>
                                            @endif
                                        </td>
                                        <td class="font-monospace small text-secondary">{{ $u->email }}</td>
                                        <td>
                                            @if ($u->roles->first())
                                                @php $roleName = $u->roles->first()->name; @endphp
                                                @if ($roleName === 'admin' || $roleName === 'owner')
                                                    <span
                                                        class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">Admin
                                                        (Owner)
                                                    </span>
                                                @elseif($roleName === 'warehouse')
                                                    <span
                                                        class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25">Staf
                                                        Gudang</span>
                                                @elseif($roleName === 'finance')
                                                    <span
                                                        class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Staf
                                                        Keuangan</span>
                                                @else
                                                    <span
                                                        class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 text-capitalize">{{ $roleName }}</span>
                                                @endif
                                            @else
                                                <span
                                                    class="badge bg-secondary bg-opacity-10 text-muted border border-secondary border-opacity-25">Belum
                                                    ada role</span>
                                            @endif

                                            @if ($u->permissions->count() > 0)
                                                <div class="mt-1">
                                                    <span
                                                        class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-10 small"
                                                        style="font-size: 0.7rem;" title="Hak akses khusus bypass/tambahan">
                                                        <i class="fas fa-user-shield me-1"></i>+{{ $u->permissions->count() }}
                                                        Izin Khusus
                                                    </span>
                                                </div>
                                            @endif
                                        </td>
                                        @if (auth()->user()->isSuperAdmin())
                                            <td class="small text-dark">{{ $u->tenant?->name ?? '-' }}</td>
                                        @endif
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <button type="button" class="btn btn-warning btn-sm edit-user-btn"
                                                    title="Edit Profil" data-id="{{ $u->id }}"
                                                    data-name="{{ $u->name }}" data-email="{{ $u->email }}"
                                                    data-role-id="{{ $u->roles->first()?->id ?? '' }}">
                                                    <i class="fas fa-pen"></i>
                                                </button>
                                                <a href="{{ route('users.permissions.edit', $u->id) }}"
                                                    class="btn btn-info text-white btn-sm" title="Hak Akses Khusus">
                                                    <i class="fas fa-key"></i>
                                                </a>
                                                @if ($u->id !== Auth::id())
                                                    <form action="{{ route('users.destroy', $u->id) }}" method="POST"
                                                        class="confirm-delete d-inline"
                                                        data-message="Apakah Anda yakin ingin menghapus pengguna ini?">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-sm"
                                                            title="Hapus">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ auth()->user()->isSuperAdmin() ? 6 : 5 }}"
                                            class="text-center text-muted py-4 small">
                                            <i class="fas fa-users-slash me-2 opacity-50"></i>
                                            Tidak ada pengguna yang cocok dengan filter.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Modal Form Tambah / Edit Profil ───────────────────── --}}
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form id="userForm" method="POST" action="{{ route('users.store') }}">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">

                {{-- Super Admin: kirim tenant_id ke store/update --}}
                @if (auth()->user()->isSuperAdmin())
                    <input type="hidden" name="tenant_id" id="modalTenantId" value="{{ request('tenant_id') }}">
                @endif

                <div class="modal-content overflow-hidden">
                    <div class="modal-header d-flex align-items-center gap-3 p-3 bg-primary bg-opacity-10 border-bottom">
                        <div class="bg-primary text-white rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 p-2 fs-5" style="width: 40px; height: 40px;">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="modal-title fw-bold fs-6 mb-0 text-dark" id="modalTitle">Tambah Pengguna</h5>
                            <p class="mb-0 text-muted small">Kelola data login pengguna sistem</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label form-label-sm fw-semibold text-dark">Nama <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="name" id="inputName" class="form-control form-control-sm"
                                required placeholder="Contoh: Budi Santoso">
                        </div>
                        <div class="mb-3">
                            <label class="form-label form-label-sm fw-semibold text-dark">Email <span
                                    class="text-danger">*</span></label>
                            <input type="email" name="email" id="inputEmail" class="form-control form-control-sm"
                                required placeholder="budi@example.com">
                        </div>
                        <div class="mb-3">
                            <label class="form-label form-label-sm fw-semibold text-dark">
                                Password
                                <span class="text-danger" id="pwdRequiredStar">*</span>
                                <small id="pwdHint" class="text-muted"></small>
                            </label>
                            <input type="password" name="password" id="inputPassword"
                                class="form-control form-control-sm" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label form-label-sm fw-semibold text-dark">Role / Posisi <span
                                    class="text-danger">*</span></label>
                            <select name="role_id" id="inputRole" class="form-select form-select-sm select2" required>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}">
                                        {{ $role->name === 'admin' ? 'Admin (Akses Penuh)' : ($role->name === 'owner' ? 'Owner (Akses Penuh)' : ($role->name === 'warehouse' ? 'Staf Gudang' : ($role->name === 'finance' ? 'Staf Keuangan' : ucfirst($role->name)))) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer bg-light px-4 py-3 d-flex justify-content-between border-top">
                        <button type="button" class="btn btn-secondary btn-sm px-3"
                            data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4">Simpan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {

                // Init Select2 di filter bar
                $('#filterTenant, #filterRole').select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                });

                // Init Select2 di modal
                $('#inputRole').select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    dropdownParent: $('#userModal'),
                });

                // Auto-submit saat tenant filter berubah (Super Admin)
                $('#filterTenant').on('change', function() {
                    $('#filterForm').submit();
                });

                // Edit user button
                $(document).on('click', '.edit-user-btn', function() {
                    const id = $(this).data('id');
                    const name = $(this).data('name');
                    const email = $(this).data('email');
                    const roleId = $(this).data('role-id');

                    $('#modalTitle').text('Edit Pengguna');
                    $('#formMethod').val('PUT');
                    $('#userForm').attr('action', '/users/' + id);

                    $('#inputName').val(name);
                    $('#inputEmail').val(email);
                    $('#inputRole').val(roleId).trigger('change');

                    $('#inputPassword').prop('required', false);
                    $('#pwdRequiredStar').hide();
                    $('#pwdHint').text('(Kosongkan jika tidak ingin mengubah password)');

                    new bootstrap.Modal(document.getElementById('userModal')).show();
                });

                // Reset modal saat ditutup
                $('#userModal').on('hidden.bs.modal', function() {
                    $('#modalTitle').text('Tambah Pengguna');
                    $('#formMethod').val('POST');
                    $('#userForm').attr('action', '{{ route('users.store') }}');

                    $('#inputName').val('');
                    $('#inputEmail').val('');
                    $('#inputRole').val(
                        '{{ $roles->first()?->id ?? '' }}'
                    ).trigger('change');

                    $('#inputPassword').val('').prop('required', true);
                    $('#pwdRequiredStar').show();
                    $('#pwdHint').text('');
                });

                // Confirm delete
                $(document).on('submit', '.confirm-delete', function(e) {
                    const msg = $(this).data('message') || 'Apakah Anda yakin?';
                    if (!confirm(msg)) e.preventDefault();
                });

            });
        </script>
    @endpush
@endsection
