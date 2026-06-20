@extends('layouts.app')
@section('title', 'Pengguna Sistem')
@section('page-title', 'Pengguna Sistem')

@section('content')
    <div class="row">
        <div class="col-md-12">

            {{-- ── Filter Bar ──────────────────────────────────────── --}}
            <div class="dashboard-card mb-3 py-3">
                <form method="GET" action="{{ route('users.index') }}" id="filterForm">

                    {{-- Super Admin: Pilih Tenant --}}
                    @if (auth()->user()->isSuperAdmin() && $tenants)
                        <div class="row g-2 mb-2">
                            <div class="col-12">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-primary bg-opacity-15 text-primary border border-primary border-opacity-25 flex-shrink-0 small">
                                        <i class="fas fa-shield-alt me-1"></i>Super Admin
                                    </span>
                                    <i class="fas fa-building text-muted small"></i>
                                    <span class="fw-semibold small text-light text-opacity-75 text-nowrap">Filter Perusahaan:</span>
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
                        <hr class="border-secondary border-opacity-10 my-2">
                    @endif

                    {{-- Search + Role Filter --}}
                    <div class="row g-2 align-items-end">
                        {{-- Cari Nama / Email --}}
                        <div class="col-md-5">
                            <label class="form-label form-label-sm fw-semibold mb-1">
                                <i class="fas fa-search text-muted me-1"></i>Cari Nama / Email
                            </label>
                            <input type="text" name="search" id="filterSearch" class="form-control form-control-sm"
                                placeholder="Ketik nama atau email..." value="{{ request('search') }}">
                        </div>

                        {{-- Filter Role --}}
                        <div class="col-md-3">
                            <label class="form-label form-label-sm fw-semibold mb-1">
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
                                Menampilkan <strong class="text-white">{{ $users->count() }}</strong> pengguna
                            </span>
                        </div>
                    </div>
                </form>
            </div>

            {{-- ── Tabel Utama ─────────────────────────────────────── --}}
            <div class="dashboard-card">
                {{-- Header --}}
                <div class="card-header-line d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0"><i class="fas fa-user-cog me-2 text-primary"></i>Daftar Pengguna Sistem</h5>
                        <p class="text-muted mb-0 mt-1 small">
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

                {{-- Alert --}}
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
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
                <div class="table-responsive rounded border border-secondary border-opacity-10 mt-3">
                    <table class="table table-sm table-bordered table-premium-dark align-middle mb-0">
                        <thead>
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
                                        <strong class="text-white small">{{ $u->name }}</strong>
                                        @if ($u->id === Auth::id())
                                            <span class="badge bg-info-subtle text-info border border-info-subtle ms-1 small">Anda</span>
                                        @endif
                                    </td>
                                    <td class="font-monospace small">{{ $u->email }}</td>
                                    <td>
                                        @if ($u->roles->first())
                                            @php $roleName = $u->roles->first()->name; @endphp
                                            @if ($roleName === 'admin' || $roleName === 'owner')
                                                <span
                                                    class="badge bg-primary-subtle text-primary border border-primary-subtle">Admin
                                                    (Owner)
                                                </span>
                                            @elseif($roleName === 'warehouse')
                                                <span
                                                    class="badge bg-warning-subtle text-warning border border-warning-subtle">Staf
                                                    Gudang</span>
                                            @elseif($roleName === 'finance')
                                                <span
                                                    class="badge bg-success-subtle text-success border border-success-subtle">Staf
                                                    Keuangan</span>
                                            @else
                                                <span
                                                    class="badge bg-secondary-subtle text-secondary border border-secondary-subtle text-capitalize">{{ $roleName }}</span>
                                            @endif
                                        @else
                                            <span
                                                class="badge bg-secondary-subtle text-muted border border-secondary-subtle">Belum
                                                ada role</span>
                                        @endif
                                    </td>
                                    @if (auth()->user()->isSuperAdmin())
                                        <td class="small">{{ $u->tenant?->name ?? '-' }}</td>
                                    @endif
                                    <td class="text-center">
                                        <div class="d-flex gap-1 justify-content-center">
                                            <button type="button" class="btn btn-warning btn-action-sm edit-user-btn"
                                                title="Edit" data-id="{{ $u->id }}"
                                                data-name="{{ $u->name }}" data-email="{{ $u->email }}"
                                                data-role-id="{{ $u->roles->first()?->id ?? '' }}">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            @if ($u->id !== Auth::id())
                                                <form action="{{ route('users.destroy', $u->id) }}" method="POST"
                                                    class="confirm-delete d-inline"
                                                    data-message="Apakah Anda yakin ingin menghapus pengguna ini?">
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

    {{-- ── Modal Form Tambah / Edit ──────────────────────────── --}}
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
                    <div class="modal-header d-flex align-items-center gap-3 p-3 bg-primary bg-opacity-10">
                        <div class="bg-primary text-white rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 p-2 fs-5">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="modal-title fw-bold fs-6 mb-0" id="modalTitle">Tambah Pengguna</h5>
                            <p class="mb-0 text-muted small">Kelola data login pengguna sistem</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label form-label-sm fw-semibold">Nama <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="name" id="inputName" class="form-control form-control-sm"
                                required placeholder="Contoh: Budi Santoso">
                        </div>
                        <div class="mb-3">
                            <label class="form-label form-label-sm fw-semibold">Email <span
                                    class="text-danger">*</span></label>
                            <input type="email" name="email" id="inputEmail" class="form-control form-control-sm"
                                required placeholder="budi@example.com">
                        </div>
                        <div class="mb-3">
                            <label class="form-label form-label-sm fw-semibold">
                                Password
                                <span class="text-danger" id="pwdRequiredStar">*</span>
                                <small id="pwdHint" class="text-muted"></small>
                            </label>
                            <input type="password" name="password" id="inputPassword"
                                class="form-control form-control-sm" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label form-label-sm fw-semibold">Role / Posisi <span
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
                    <div class="modal-footer bg-primary bg-opacity-10 px-4 py-3 d-flex justify-content-between">
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
