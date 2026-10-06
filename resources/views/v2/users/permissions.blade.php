@extends('v2.layouts.app')

@section('title', 'Hak Akses Khusus: ' . $user->name)

@section('content')
<form action="{{ route('v2.users.permissions.update', $user->id) }}" method="POST">
    @csrf
    @method('PUT')

    <!-- Page Header Compact -->
    <div class="v2-page-header align-items-center mb-3">
        <div>
            <h1 class="v2-page-title d-flex align-items-center gap-2">
                <i class="bi bi-shield-lock text-primary fs-5"></i> Hak Akses Khusus: {{ $user->name }}
            </h1>
            <p class="v2-page-subtitle mb-0">Atur hak akses langsung (direct bypass permissions) per modul aplikasi untuk pengguna ini</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('v2.users.index') }}" class="btn btn-sm btn-v2-secondary py-1.5 px-3">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
            <button type="submit" class="btn btn-sm btn-v2-primary py-1.5 px-3.5 shadow-sm">
                <i class="bi bi-check-lg me-1"></i> Simpan Perubahan
            </button>
        </div>
    </div>

    <!-- User Profile Banner Card -->
    <div class="v2-card mb-3">
        <div class="v2-card-body p-3">
            <div class="row align-items-center g-3">
                <div class="col-12 col-md-8">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm flex-shrink-0"
                            style="width: 52px; height: 52px; font-size: 1.15rem; background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);">
                            {{ strtoupper(substr($user->name, 0, 2)) }}
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h5 class="fw-bold text-dark mb-0" style="font-size: 1.05rem;">{{ $user->name }}</h5>
                                @if($user->roles->first())
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-0.5 text-capitalize" style="font-size: 0.72rem;">
                                        Role Utama: {{ $user->roles->first()->name }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-muted border border-secondary-subtle px-2 py-0.5" style="font-size: 0.72rem;">
                                        Belum Ada Role
                                    </span>
                                @endif
                            </div>
                            <div class="text-secondary font-monospace mt-0.5" style="font-size: 0.78rem;">
                                <i class="bi bi-envelope me-1"></i>{{ $user->email }}
                                @if($user->tenant)
                                    <span class="text-muted ms-2">&bull; Perusahaan: <strong>{{ $user->tenant->name }}</strong></span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-4 text-md-end">
                    <div class="alert alert-info py-2 px-3 mb-0 d-inline-block text-start border-0 shadow-2xs" style="font-size: 0.75rem;">
                        <i class="bi bi-info-circle-fill me-1 text-info"></i>
                        Hak akses khusus ini otomatis menggantikan atau melengkapi izin bawaan dari Role pengguna.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Permissions Grid -->
    <div class="row g-3 mb-3">
        @foreach($permissionGroups as $groupName => $perms)
            <div class="col-12 col-md-6">
                <div class="v2-card h-100 mb-0 perm-group-card">
                    <div class="v2-card-header bg-light py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-folder2-open text-primary"></i>
                            <span class="fw-bold text-dark" style="font-size: 0.8rem;">{{ $groupName }}</span>
                            <span class="badge bg-secondary-subtle text-secondary rounded-pill px-1.5 py-0.5" style="font-size: 0.65rem;">
                                {{ count($perms) }}
                            </span>
                        </div>
                        <button type="button" class="btn btn-link btn-xs text-primary p-0 text-decoration-none select-all-btn fw-semibold" style="font-size: 0.72rem;">
                            Pilih Semua
                        </button>
                    </div>

                    <div class="v2-card-body p-3">
                        <div class="row g-2">
                            @foreach($perms as $key => $label)
                                <div class="col-12">
                                    <div class="form-check form-switch d-flex align-items-center justify-content-between p-0 m-0">
                                        <label class="form-check-label text-dark pe-3 mb-0" for="user_perm_{{ $key }}" style="font-size: 0.78rem; cursor: pointer;">
                                            {{ $label }}
                                            <span class="text-muted font-monospace d-block" style="font-size: 0.68rem;">{{ $key }}</span>
                                        </label>
                                        <input class="form-check-input perm-checkbox m-0 flex-shrink-0" type="checkbox"
                                            name="permissions[]" value="{{ $key }}" id="user_perm_{{ $key }}"
                                            {{ in_array($key, $userPermissions) ? 'checked' : '' }}
                                            style="cursor: pointer; width: 2.2em; height: 1.2em;">
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Bottom Submit Bar -->
    <div class="v2-card mb-4 bg-white border shadow-sm">
        <div class="v2-card-body p-3 d-flex align-items-center justify-content-between">
            <span class="text-muted" style="font-size: 0.78rem;">
                Pastikan seluruh modul dan izin telah sesuai sebelum menyimpan konfigurasi.
            </span>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('v2.users.index') }}" class="btn btn-sm btn-secondary px-3" style="font-size: 0.78rem;">
                    Batal
                </a>
                <button type="submit" class="btn btn-sm btn-primary px-4 fw-semibold" style="font-size: 0.78rem;">
                    <i class="bi bi-save me-1"></i> Simpan Perubahan Hak Akses
                </button>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    function updateSelectAllButton(card) {
        const btn = card.querySelector('.select-all-btn');
        if (!btn) return;

        const checkboxes = card.querySelectorAll('.perm-checkbox');
        if (!checkboxes.length) return;

        let allChecked = true;
        checkboxes.forEach(function (cb) {
            if (!cb.checked) allChecked = false;
        });

        if (allChecked) {
            btn.textContent = 'Batal Pilih';
            btn.classList.remove('text-primary');
            btn.classList.add('text-danger');
        } else {
            btn.textContent = 'Pilih Semua';
            btn.classList.remove('text-danger');
            btn.classList.add('text-primary');
        }
    }

    // Check initial state per card
    document.querySelectorAll('.perm-group-card').forEach(function (card) {
        updateSelectAllButton(card);
    });

    // Checkbox change listener
    document.querySelectorAll('.perm-checkbox').forEach(function (cb) {
        cb.addEventListener('change', function () {
            const card = this.closest('.perm-group-card');
            if (card) {
                updateSelectAllButton(card);
            }
        });
    });

    // Toggle all button click
    document.querySelectorAll('.select-all-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const card = this.closest('.perm-group-card');
            if (!card) return;

            const checkboxes = card.querySelectorAll('.perm-checkbox');
            let allChecked = true;
            checkboxes.forEach(function (cb) {
                if (!cb.checked) allChecked = false;
            });

            checkboxes.forEach(function (cb) {
                cb.checked = !allChecked;
            });

            updateSelectAllButton(card);
        });
    });
});
</script>
@endpush
