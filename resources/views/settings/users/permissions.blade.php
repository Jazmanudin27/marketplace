@extends('layouts.app')
@section('title', 'Hak Akses Khusus')
@section('page-title', 'Hak Akses Khusus')

@section('content')
    <form action="{{ route('users.permissions.update', $user->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row">
            {{-- Top Card: User Profile & Form Actions --}}
            <div class="col-12 mb-4">
                <div class="dashboard-card">
                    <div class="row align-items-center">
                        {{-- Left Column: Profile Info --}}
                        <div class="col-md-7 border-end border-secondary border-opacity-10 mb-3 mb-md-0">
                            <div class="d-flex align-items-center">
                                <div
                                    class="bg-primary text-white rounded p-3 me-3 d-flex align-items-center justify-content-center fs-3">
                                    <i class="fas fa-user-shield"></i>
                                </div>
                                <div>
                                    <h4 class="mb-1 text-white fw-bold">{{ $user->name }}</h4>
                                    <span class="font-monospace text-muted d-block small mb-2">{{ $user->email }}</span>
                                    @if ($user->roles->first())
                                        <span
                                            class="badge bg-primary-subtle text-primary border border-primary-subtle text-capitalize small">
                                            Role Utama: {{ $user->roles->first()->name }}
                                        </span>
                                    @else
                                        <span
                                            class="badge bg-secondary-subtle text-muted border border-secondary-subtle small">
                                            Belum ada role
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Right Column: Actions & Info Alert --}}
                        <div class="col-md-5 ps-md-4">
                            <div
                                class="alert alert-info py-2 px-3 small border border-info border-opacity-10 bg-info bg-opacity-5 mb-3">
                                <i class="fas fa-info-circle me-1 text-info"></i>
                                Perubahan izin langsung (bypass) hanya berlaku pada tenant aktif saat ini.
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('users.index') }}" class="btn btn-secondary btn-sm px-3 py-2 flex-grow-1">
                                    <i class="fas fa-arrow-left me-1"></i> Batal / Kembali
                                </a>
                                <button type="submit" class="btn btn-primary btn-sm px-4 py-2 flex-grow-1">
                                    <i class="fas fa-save me-1"></i> Simpan Perubahan
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bottom Grid: Grouped Permissions List --}}
            <div class="col-12">
                <div class="dashboard-card">
                    <h5 class="mb-4 text-white fw-bold">
                        <i class="fas fa-key me-2 text-primary"></i>Daftar Hak Akses Khusus
                    </h5>

                    <div class="row">
                        @foreach ($permissionGroups as $groupName => $perms)
                            <div class="col-md-6 mb-3">
                                <div
                                    class="card h-100 border border-secondary border-opacity-10 bg-transparent overflow-hidden">
                                    <div
                                        class="card-header d-flex justify-content-between align-items-center py-2 bg-dark bg-opacity-5 border-bottom border-secondary border-opacity-10">
                                        <span class="fw-semibold text-white small">
                                            <i class="fas fa-folder me-2 text-primary"></i>{{ $groupName }}
                                        </span>
                                        <button type="button"
                                            class="btn btn-link btn-xs text-info p-0 text-decoration-none select-all-btn small">Pilih
                                            Semua</button>
                                    </div>
                                    <div class="card-body py-2">
                                        <div class="row">
                                            @foreach ($perms as $key => $label)
                                                <div class="col-12 my-1">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input perm-checkbox" type="checkbox"
                                                            name="permissions[]" value="{{ $key }}"
                                                            id="user_perm_{{ $key }}"
                                                            {{ in_array($key, $userPermissions) ? 'checked' : '' }}>
                                                        <label class="form-check-label small text-white text-opacity-75"
                                                            for="user_perm_{{ $key }}">{{ $label }}</label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </form>

    @push('scripts')
        <script>
            $(document).ready(function() {
                // Function to update the select-all button text & color based on checkboxes state
                function updateSelectAllButton(card) {
                    const btn = card.find('.select-all-btn');
                    if (!btn.length) return;

                    const checkboxes = card.find('.perm-checkbox');
                    if (!checkboxes.length) return;

                    let allChecked = true;
                    checkboxes.each(function() {
                        if (!this.checked) allChecked = false;
                    });

                    if (allChecked) {
                        btn.text('Batal Pilih').removeClass('text-info').addClass('text-danger');
                    } else {
                        btn.text('Pilih Semua').removeClass('text-danger').addClass('text-info');
                    }
                }

                // Initial state check on page load
                $('.card').each(function() {
                    updateSelectAllButton($(this));
                });

                // Update select-all button when any check state changes manually
                $(document).on('change', '.perm-checkbox', function() {
                    updateSelectAllButton($(this).closest('.card'));
                });

                // Select/Deselect All buttons click handler
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

                    updateSelectAllButton(card);
                });
            });
        </script>
    @endpush
@endsection
