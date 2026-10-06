@extends('v2.layouts.app')

@section('title', 'Pengguna Sistem V2')

@section('content')
<!-- Page Header Compact -->
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2">
            <i class="bi bi-people text-primary fs-5"></i> Pengguna Sistem
        </h1>
        <p class="v2-page-subtitle mb-0">Kelola akun pengguna, posisi role, dan konfigurasi hak akses login sistem</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('v2.users.index') }}" class="btn btn-sm btn-v2-secondary py-1.5 px-2.5" title="Refresh">
            <i class="bi bi-arrow-clockwise"></i>
        </a>
        <button type="button" class="btn btn-sm btn-v2-primary py-1.5 px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#userModal">
            <i class="bi bi-plus-lg me-1"></i> Tambah Pengguna
        </button>
    </div>
</div>

<!-- Alerts -->
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show py-2 px-3 mb-3 border-0 shadow-sm" style="font-size: 0.78rem;" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close py-2.5" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show py-2 px-3 mb-3 border-0 shadow-sm" style="font-size: 0.78rem;" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close py-2.5" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show py-2 px-3 mb-3 border-0 shadow-sm" style="font-size: 0.78rem;" role="alert">
        <strong><i class="bi bi-exclamation-triangle-fill me-2"></i> Periksa inputan Anda:</strong>
        <ul class="mb-0 mt-1 ps-3" style="font-size: 0.75rem;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close py-2.5" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Summary Widgets (KPI Cards) -->
<div class="row g-2 mb-3">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="v2-stat-widget widget-blue">
            <div class="v2-stat-icon-wrapper blue">
                <i class="bi bi-people-fill"></i>
            </div>
            <div class="v2-stat-info">
                <span class="v2-stat-num">{{ number_format($totalUsers ?? $users->count()) }}</span>
                <span class="v2-stat-lbl">Total Pengguna</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="v2-stat-widget widget-purple">
            <div class="v2-stat-icon-wrapper purple">
                <i class="bi bi-shield-check"></i>
            </div>
            <div class="v2-stat-info">
                <span class="v2-stat-num">{{ number_format($adminCount ?? 0) }}</span>
                <span class="v2-stat-lbl">Admin &amp; Owner</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="v2-stat-widget widget-amber">
            <div class="v2-stat-icon-wrapper amber">
                <i class="bi bi-boxes"></i>
            </div>
            <div class="v2-stat-info">
                <span class="v2-stat-num">{{ number_format($warehouseCount ?? 0) }}</span>
                <span class="v2-stat-lbl">Staf Gudang</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="v2-stat-widget widget-green">
            <div class="v2-stat-icon-wrapper green">
                <i class="bi bi-wallet2"></i>
            </div>
            <div class="v2-stat-info">
                <span class="v2-stat-num">{{ number_format($financeCount ?? 0) }}</span>
                <span class="v2-stat-lbl">Staf Keuangan</span>
            </div>
        </div>
    </div>
</div>

<!-- Filter Bar Card -->
<div class="v2-card mb-3">
    <div class="v2-card-body p-2.5">
        <form method="GET" action="{{ route('v2.users.index') }}" id="filterForm">
            {{-- Super Admin: Pilih Tenant --}}
            @if(auth()->user()->isSuperAdmin() && $tenants)
                <div class="row g-2 mb-2 pb-2 border-bottom">
                    <div class="col-12 d-flex align-items-center gap-2 flex-wrap">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1" style="font-size: 0.72rem;">
                            <i class="bi bi-shield-lock-fill me-1"></i>Super Admin
                        </span>
                        <div class="d-flex align-items-center gap-2 flex-grow-1">
                            <label class="form-label mb-0 text-muted small fw-semibold text-nowrap" style="font-size: 0.78rem;">
                                <i class="bi bi-building me-1"></i>Perusahaan:
                            </label>
                            <div class="flex-grow-1" style="max-width: 320px;">
                                <select name="tenant_id" id="filterTenant" class="form-select form-select-sm" style="font-size: 0.78rem;">
                                    <option value="">-- Semua Perusahaan --</option>
                                    @foreach($tenants as $t)
                                        <option value="{{ $t->id }}" {{ $selectedTenantId == $t->id ? 'selected' : '' }}>
                                            {{ $t->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="row g-2 align-items-end">
                {{-- Search Nama / Email --}}
                <div class="col-12 col-md-5">
                    <label class="form-label form-label-sm fw-semibold mb-1 text-muted" style="font-size: 0.74rem;">
                        <i class="bi bi-search me-1"></i>Cari Nama / Email
                    </label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" id="filterSearch" class="form-control border-start-0 ps-0"
                            placeholder="Ketik nama atau email..." value="{{ request('search') }}" style="font-size: 0.78rem;">
                    </div>
                </div>

                {{-- Filter Role --}}
                <div class="col-12 col-md-4">
                    <label class="form-label form-label-sm fw-semibold mb-1 text-muted" style="font-size: 0.74rem;">
                        <i class="bi bi-person-badge me-1"></i>Role / Posisi
                    </label>
                    <select name="role" id="filterRole" class="form-select form-select-sm" style="font-size: 0.78rem;">
                        <option value="">-- Semua Role --</option>
                        @foreach($roleNames as $rn)
                            <option value="{{ $rn }}" {{ request('role') === $rn ? 'selected' : '' }}>
                                {{ $rn === 'admin' ? 'Admin (Owner)' : ($rn === 'warehouse' ? 'Staf Gudang' : ($rn === 'finance' ? 'Staf Keuangan' : ucfirst($rn))) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Action Buttons --}}
                <div class="col-12 col-md-3 d-flex align-items-center gap-1.5 justify-content-md-end">
                    <button type="submit" class="btn btn-sm btn-primary py-1.5 px-3 fw-semibold">
                        <i class="bi bi-funnel me-1"></i> Terapkan
                    </button>
                    <a href="{{ route('v2.users.index') }}" class="btn btn-sm btn-outline-secondary py-1.5 px-2.5" title="Reset Filter">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                    <span class="text-muted ms-2 small d-none d-xl-inline" style="font-size: 0.72rem;">
                        Total: <strong>{{ $users->count() }}</strong>
                    </span>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Main User List Card -->
<div class="v2-card">
    <div class="v2-card-header bg-light">
        <div class="d-flex align-items-center gap-2">
            <h6 class="v2-card-title mb-0">
                <i class="bi bi-person-lines-fill text-primary"></i> Daftar Pengguna Sistem
            </h6>
            @if(request('tenant_id') && auth()->user()->isSuperAdmin())
                @php $tName = $tenants->firstWhere('id', request('tenant_id'))?->name; @endphp
                @if($tName)
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">{{ $tName }}</span>
                @endif
            @endif
        </div>
        <span class="text-muted" style="font-size: 0.75rem;">
            Menampilkan <strong>{{ $users->count() }}</strong> akun terdaftar
        </span>
    </div>

    <div class="v2-card-body p-0">
        <div class="table-responsive">
            <table class="table v2-table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 45px;">#</th>
                        <th>PENGGUNA</th>
                        <th>EMAIL</th>
                        <th>ROLE / POSISI</th>
                        @if(auth()->user()->isSuperAdmin())
                            <th>PERUSAHAAN</th>
                        @endif
                        <th class="text-center" style="width: 230px;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $index => $u)
                        <tr>
                            <td class="text-center text-muted" style="font-size: 0.75rem;">{{ $index + 1 }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2.5">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-2xs"
                                        style="width: 34px; height: 34px; font-size: 0.75rem; background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);">
                                        {{ strtoupper(substr($u->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark" style="font-size: 0.8rem;">
                                            {{ $u->name }}
                                            @if($u->id === Auth::id())
                                                <span class="badge bg-info-subtle text-info border border-info-subtle ms-1" style="font-size: 0.65rem;">Anda</span>
                                            @endif
                                        </div>
                                        <small class="text-muted" style="font-size: 0.68rem;">ID: #{{ $u->id }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="font-monospace text-secondary" style="font-size: 0.76rem;">{{ $u->email }}</span>
                            </td>
                            <td>
                                @if($u->roles->first())
                                    @php $roleName = $u->roles->first()->name; @endphp
                                    @if(in_array($roleName, ['admin', 'owner']))
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1" style="font-size: 0.72rem;">
                                            <i class="bi bi-shield-fill-check me-1"></i>Admin (Owner)
                                        </span>
                                    @elseif($roleName === 'warehouse')
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1" style="font-size: 0.72rem;">
                                            <i class="bi bi-box-seam me-1"></i>Staf Gudang
                                        </span>
                                    @elseif($roleName === 'finance')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size: 0.72rem;">
                                            <i class="bi bi-cash-stack me-1"></i>Staf Keuangan
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1 text-capitalize" style="font-size: 0.72rem;">
                                            {{ $roleName }}
                                        </span>
                                    @endif
                                @else
                                    <span class="badge bg-secondary-subtle text-muted border border-secondary-subtle px-2 py-1" style="font-size: 0.72rem;">
                                        Belum ada role
                                    </span>
                                @endif

                                @if($u->permissions->count() > 0)
                                    <div class="mt-1">
                                        <span class="badge bg-info-subtle text-info border border-info-subtle" style="font-size: 0.65rem;" title="Hak akses khusus tambahan">
                                            <i class="bi bi-key-fill me-0.5"></i> +{{ $u->permissions->count() }} Izin Khusus
                                        </span>
                                    </div>
                                @endif
                            </td>
                            @if(auth()->user()->isSuperAdmin())
                                <td style="font-size: 0.75rem;" class="text-dark fw-medium">
                                    {{ $u->tenant?->name ?? '-' }}
                                </td>
                            @endif
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center align-items-center flex-wrap">
                                    {{-- Simulasi User Button --}}
                                    @if($u->id !== Auth::id())
                                        <form action="{{ route('v2.users.impersonate', $u->id) }}" method="POST" class="d-inline m-0">
                                            @csrf
                                            <button type="submit" class="btn btn-sm text-white py-0.5 px-2 shadow-2xs" 
                                                style="background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); font-size: 0.72rem;"
                                                title="Masuk &amp; Simulasi Akun Sebagai {{ $u->name }}">
                                                <i class="bi bi-person-bounding-box me-1"></i>Simulasi
                                            </button>
                                        </form>
                                    @endif

                                    {{-- Edit Button --}}
                                    <button type="button" class="btn btn-sm btn-outline-warning py-0.5 px-1.5 edit-user-btn"
                                        title="Edit Profil Akun"
                                        data-id="{{ $u->id }}"
                                        data-name="{{ $u->name }}"
                                        data-email="{{ $u->email }}"
                                        data-role-id="{{ $u->roles->first()?->id ?? '' }}">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    {{-- Hak Akses Khusus --}}
                                    <a href="{{ route('v2.users.permissions.edit', $u->id) }}"
                                        class="btn btn-sm btn-outline-info py-0.5 px-1.5" title="Konfigurasi Hak Akses Khusus">
                                        <i class="bi bi-shield-lock"></i>
                                    </a>

                                    {{-- Hapus Button --}}
                                    @if($u->id !== Auth::id())
                                        <form action="{{ route('v2.users.destroy', $u->id) }}" method="POST"
                                            class="confirm-delete d-inline m-0"
                                            data-message="Yakin ingin menghapus pengguna {{ $u->name }}?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger py-0.5 px-1.5" title="Hapus Pengguna">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()->isSuperAdmin() ? 6 : 5 }}" class="text-center py-5 text-muted" style="font-size: 0.8rem;">
                                <i class="bi bi-people fs-2 d-block text-secondary opacity-50 mb-2"></i>
                                Tidak ada data pengguna yang sesuai dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Form Tambah / Edit Pengguna -->
<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="userForm" method="POST" action="{{ route('v2.users.store') }}">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">

            @if(auth()->user()->isSuperAdmin())
                <input type="hidden" name="tenant_id" id="modalTenantId" value="{{ request('tenant_id') }}">
            @endif

            <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
                <div class="modal-header py-3 px-3.5 bg-light border-bottom">
                    <div class="d-flex align-items-center gap-2.5">
                        <div class="rounded-3 p-2 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                            <i class="bi bi-person-fill-gear fs-5"></i>
                        </div>
                        <div>
                            <h6 class="modal-title fw-bold text-dark mb-0" id="modalTitle">Tambah Pengguna</h6>
                            <small class="text-muted" style="font-size: 0.72rem;">Konfigurasi akun dan hak akses login sistem</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-3.5">
                    <div class="mb-3">
                        <label class="form-label form-label-sm fw-semibold text-dark" style="font-size: 0.75rem;">
                            Nama Lengkap <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="name" id="inputName" class="form-control form-control-sm" required
                            placeholder="Contoh: Budi Santoso" style="font-size: 0.8rem;">
                    </div>

                    <div class="mb-3">
                        <label class="form-label form-label-sm fw-semibold text-dark" style="font-size: 0.75rem;">
                            Alamat Email (Login) <span class="text-danger">*</span>
                        </label>
                        <input type="email" name="email" id="inputEmail" class="form-control form-control-sm" required
                            placeholder="budi@example.com" style="font-size: 0.8rem;">
                    </div>

                    <div class="mb-3">
                        <label class="form-label form-label-sm fw-semibold text-dark" style="font-size: 0.75rem;">
                            Password
                            <span class="text-danger" id="pwdRequiredStar">*</span>
                            <small id="pwdHint" class="text-muted fw-normal"></small>
                        </label>
                        <input type="password" name="password" id="inputPassword" class="form-control form-control-sm" required
                            placeholder="Minimal 8 karakter" style="font-size: 0.8rem;">
                    </div>

                    <div class="mb-2">
                        <label class="form-label form-label-sm fw-semibold text-dark" style="font-size: 0.75rem;">
                            Role / Posisi <span class="text-danger">*</span>
                        </label>
                        <select name="role_id" id="inputRole" class="form-select form-select-sm" required style="font-size: 0.8rem;">
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}">
                                    {{ $role->name === 'admin' ? 'Admin (Akses Penuh)' : ($role->name === 'owner' ? 'Owner (Akses Penuh)' : ($role->name === 'warehouse' ? 'Staf Gudang' : ($role->name === 'finance' ? 'Staf Keuangan' : ucfirst($role->name)))) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="modal-footer bg-light px-3.5 py-2.5 d-flex justify-content-between border-top">
                    <button type="button" class="btn btn-sm btn-secondary px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary px-4 fw-semibold">
                        <i class="bi bi-save me-1"></i> Simpan Pengguna
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Auto-submit saat tenant filter berubah (Super Admin)
    const filterTenant = document.getElementById('filterTenant');
    if (filterTenant) {
        filterTenant.addEventListener('change', function () {
            document.getElementById('filterForm').submit();
        });
    }

    // Edit user click handler
    document.querySelectorAll('.edit-user-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const email = this.getAttribute('data-email');
            const roleId = this.getAttribute('data-role-id');

            document.getElementById('modalTitle').textContent = 'Edit Pengguna';
            document.getElementById('formMethod').value = 'PUT';
            document.getElementById('userForm').action = '/v2/users/' + id;

            document.getElementById('inputName').value = name;
            document.getElementById('inputEmail').value = email;
            if (roleId) {
                document.getElementById('inputRole').value = roleId;
            }

            document.getElementById('inputPassword').removeAttribute('required');
            document.getElementById('pwdRequiredStar').style.display = 'none';
            document.getElementById('pwdHint').textContent = '(Kosongkan jika password tidak diubah)';

            const modal = new bootstrap.Modal(document.getElementById('userModal'));
            modal.show();
        });
    });

    // Reset modal saat ditutup
    const userModalEl = document.getElementById('userModal');
    if (userModalEl) {
        userModalEl.addEventListener('hidden.bs.modal', function () {
            document.getElementById('modalTitle').textContent = 'Tambah Pengguna';
            document.getElementById('formMethod').value = 'POST';
            document.getElementById('userForm').action = "{{ route('v2.users.store') }}";

            document.getElementById('inputName').value = '';
            document.getElementById('inputEmail').value = '';
            const firstRole = "{{ $roles->first()?->id ?? '' }}";
            if (firstRole) {
                document.getElementById('inputRole').value = firstRole;
            }

            document.getElementById('inputPassword').value = '';
            document.getElementById('inputPassword').setAttribute('required', 'required');
            document.getElementById('pwdRequiredStar').style.display = 'inline';
            document.getElementById('pwdHint').textContent = '';
        });
    }

    // Confirm delete handler
    document.querySelectorAll('.confirm-delete').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            const msg = this.getAttribute('data-message') || 'Apakah Anda yakin ingin menghapus data ini?';
            if (!confirm(msg)) {
                e.preventDefault();
            }
        });
    });
});
</script>
@endpush
