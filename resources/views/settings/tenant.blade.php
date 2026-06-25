@extends('layouts.app')
@section('title', 'Pengaturan Perusahaan')
@section('page-title', 'Pengaturan Perusahaan')

@section('content')
    <div class="row justify-content-start">

        {{-- ── Filter Bar (Super Admin Only) ─────────────────────────── --}}
        @if (auth()->user()->isSuperAdmin() && $tenants)
            <div class="col-md-8 mb-3">
                <div class="card border shadow-sm">
                    <div class="card-body py-3 px-3">
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                <i class="fas fa-filter text-primary small"></i>
                                <span class="fw-semibold small text-dark text-opacity-75 text-nowrap">Filter Perusahaan:</span>
                            </div>
                            <div class="flex-grow-1">
                                <select id="tenantFilter" class="form-select form-select-sm select2"
                                    data-placeholder="-- Pilih Perusahaan / Toko --">
                                    @foreach ($tenants as $t)
                                        <option value="{{ $t->id }}" {{ $selectedTenantId == $t->id ? 'selected' : '' }}>
                                            {{ $t->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="d-flex align-items-center gap-2 ms-auto flex-shrink-0">
                                <span class="badge bg-primary bg-opacity-15 text-primary border border-primary border-opacity-25 small">
                                    <i class="fas fa-shield-alt me-1"></i>Super Admin
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="col-md-8">
            <div class="card border shadow-sm">
                <div class="card-header bg-light d-flex align-items-center justify-content-between py-2.5 px-3 border-bottom mb-4">
                    <div>
                        <h6 class="m-0 fw-bold text-primary"><i class="fas fa-building me-2"></i>Profil & Pengaturan Perusahaan</h6>
                        <p class="text-muted mb-0 small mt-1">
                            Kelola data, jadwal gaji, dan koordinat kantor
                            @if (auth()->user()->isSuperAdmin())
                                &mdash; <span class="text-primary fw-semibold">{{ $tenant->name }}</span>
                            @endif
                        </p>
                    </div>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 small">
                        {{ $tenant->status ?? 'Aktif' }}
                    </span>
                </div>

                <div class="card-body p-3">
                    {{-- Alert --}}
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                            <strong><i class="fas fa-exclamation-triangle me-2"></i>Periksa inputan Anda:</strong>
                            <ul class="mb-0 mt-1 ps-3 small">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('settings.tenant.update') }}" method="POST" id="tenantForm">
                        @csrf
                        @method('PUT')

                        {{-- Hidden tenant_id for Super Admin --}}
                        @if (auth()->user()->isSuperAdmin())
                            <input type="hidden" name="tenant_id" id="hiddenTenantId" value="{{ $selectedTenantId }}">
                        @endif

                        {{-- ── Nama Perusahaan ──────────────────── --}}
                        <div class="mb-3">
                            <label for="name" class="form-label form-label-sm fw-semibold text-dark">
                                Nama Perusahaan <span class="text-danger">*</span>
                            </label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light">
                                    <i class="fas fa-building text-muted"></i>
                                </span>
                                <input type="text" id="name" name="name"
                                    class="form-control form-control-sm @error('name') is-invalid @enderror"
                                    value="{{ old('name', $tenant->name) }}" required>
                            </div>
                            @error('name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- ── Cutoff Gaji ──────────────────────── --}}
                        <div class="mb-4">
                            <label for="cutoff_start_day" class="form-label form-label-sm fw-semibold text-dark">
                                Hari Mulai Cut-off Presensi & Gaji <span class="text-danger">*</span>
                            </label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light">
                                    <i class="fas fa-calendar-alt text-muted"></i>
                                </span>
                                <select id="cutoff_start_day" name="cutoff_start_day"
                                    class="form-select form-select-sm @error('cutoff_start_day') is-invalid @enderror">
                                    @for ($day = 1; $day <= 28; $day++)
                                        <option value="{{ $day }}"
                                            {{ old('cutoff_start_day', $tenant->cutoff_start_day ?? 21) == $day ? 'selected' : '' }}>
                                            @if ($day === 1)
                                                Tanggal 1 (Full Month / 1 s.d Akhir Bulan)
                                            @else
                                                Tanggal {{ $day }} (Periode: {{ $day }} s.d
                                                {{ $day - 1 }} bulan berikutnya)
                                            @endif
                                        </option>
                                    @endfor
                                </select>
                            </div>
                            <div class="form-text text-muted small mt-2">
                                <i class="fas fa-info-circle text-info me-1"></i>
                                Digunakan secara global untuk menghitung lembur, kasbon, absensi, dan slip gaji karyawan.
                            </div>
                            @error('cutoff_start_day')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- ── Divider: Koordinat Kantor ──────────── --}}
                        <hr class="my-4">
                        <div class="mb-4">
                            <h6 class="mb-1 fw-bold text-primary">
                                <i class="fas fa-map-marker-alt me-2"></i>Koordinat & Radius Lokasi Kantor
                            </h6>
                            <p class="text-muted mb-0 small">
                                Digunakan untuk validasi clock-in/clock-out karyawan berbasis GPS
                            </p>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="office_latitude" class="form-label form-label-sm fw-semibold text-dark">Latitude Kantor</label>
                                <input type="text" id="office_latitude" name="office_latitude"
                                    class="form-control form-control-sm @error('office_latitude') is-invalid @enderror"
                                    value="{{ old('office_latitude', $tenant->office_latitude) }}"
                                    placeholder="Contoh: -6.175392">
                                @error('office_latitude')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="office_longitude" class="form-label form-label-sm fw-semibold text-dark">Longitude Kantor</label>
                                <input type="text" id="office_longitude" name="office_longitude"
                                    class="form-control form-control-sm @error('office_longitude') is-invalid @enderror"
                                    value="{{ old('office_longitude', $tenant->office_longitude) }}"
                                    placeholder="Contoh: 106.827153">
                                @error('office_longitude')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-12">
                                <label for="office_radius" class="form-label form-label-sm fw-semibold text-dark">Radius Absensi Maksimal (Meter)</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" id="office_radius" name="office_radius"
                                        class="form-control form-control-sm @error('office_radius') is-invalid @enderror"
                                        value="{{ old('office_radius', $tenant->office_radius ?? 20) }}"
                                        placeholder="Default: 20">
                                    <span class="input-group-text bg-light text-muted">meter</span>
                                </div>
                                <div class="form-text text-muted small mt-1">
                                    <i class="fas fa-info-circle text-info me-1"></i>
                                    Biarkan koordinat kosong jika ingin melewati pengecekan radius.
                                </div>
                                @error('office_radius')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-end align-items-center">
                            <button type="submit" class="btn btn-success btn-sm px-4 fw-semibold">
                                <i class="fas fa-save me-1"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                // Init Select2 untuk filter toko (Super Admin only)
                $('#tenantFilter').select2({
                    theme: 'bootstrap-5',
                    placeholder: '-- Pilih Perusahaan / Toko --',
                    width: '100%',
                });

                // Redirect ke tenant yang dipilih saat filter berubah
                $('#tenantFilter').on('change', function() {
                    const tenantId = $(this).val();
                    window.location.href = '{{ route('settings.tenant.edit') }}?tenant_id=' + tenantId;
                });

                // Sinkronisasi hidden input tenant_id dengan filter
                $('#tenantFilter').on('change', function() {
                    $('#hiddenTenantId').val($(this).val());
                });
            });
        </script>
    @endpush
@endsection
