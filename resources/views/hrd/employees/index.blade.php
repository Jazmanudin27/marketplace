@extends('layouts.app')
@section('title', 'Data Karyawan')
@section('page-title', 'Data Karyawan')

@section('content')
    <div class="row">
        <div class="col-md-12">
            {{-- ── Filter Card ───────────────────────────────────────────── --}}
            <div class="dashboard-card mb-3 py-3">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <label class="form-label form-label-sm">
                            <i class="fas fa-search me-1"></i>Cari Nama / Posisi
                        </label>
                        <input type="text" id="filterSearch" class="form-control form-control-sm"
                            placeholder="Ketik nama atau posisi...">
                    </div>
                    <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                        <label class="form-label form-label-sm">
                            <i class="fas fa-toggle-on me-1"></i>Status Keaktifan
                        </label>
                        <select id="filterStatus" class="form-select form-select-sm">
                            <option value="">-- Semua --</option>
                            <option value="active">Aktif</option>
                            <option value="inactive">Nonaktif</option>
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                        <label class="form-label form-label-sm">
                            <i class="fas fa-money-bill-wave me-1"></i>Jenis Gaji
                        </label>
                        <select id="filterSalaryType" class="form-select form-select-sm">
                            <option value="">-- Semua --</option>
                            <option value="monthly">Per Bulan</option>
                            <option value="hourly">Per Jam</option>
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-md-auto d-flex gap-2">
                        <button type="button" class="btn btn-secondary btn-sm" id="btnResetFilter">
                            <i class="fas fa-undo me-1"></i> Reset
                        </button>
                    </div>
                </div>
            </div>

            {{-- ── Table Card ────────────────────────────────────────────── --}}
            <div class="dashboard-card">

                {{-- Header --}}
                <div class="card-header-line d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">
                            <i class="fas fa-users me-2 text-primary"></i>Daftar Karyawan
                        </h5>
                        <p class="text-muted mb-0 mt-1 small">Kelola data profil, jadwal kerja, dan penggajian karyawan</p>
                    </div>
                    <a href="{{ route('employees.create') }}" class="btn btn-primary btn-sm px-3">
                        <i class="fas fa-plus me-1"></i> Tambah Karyawan
                    </a>
                </div>

                {{-- Alert --}}
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                {{-- Validation Errors --}}
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                        <strong><i class="fas fa-exclamation-triangle me-2"></i>Periksa kembali inputan Anda:</strong>
                        <ul class="mb-0 mt-1 ps-3 small">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                {{-- Info bar --}}
                <div class="d-flex justify-content-between align-items-center mt-3 mb-2">
                    <span class="text-muted small">
                        Total <strong id="totalKaryawanCount">{{ count($employees) }}</strong> Karyawan
                    </span>
                </div>

                {{-- Tabel --}}
                <div class="table-responsive rounded border border-secondary border-opacity-10 mt-3">
                    <table class="table table-sm table-bordered table-premium-dark align-middle mb-0">
                        <thead>
                            <tr>
                                <th>KARYAWAN</th>
                                <th>POSISI</th>
                                <th class="text-end">GAJI &amp; LEMBUR</th>
                                <th class="text-center">AKUN MOBILE</th>
                                <th class="text-center">STATUS</th>
                                <th class="text-center">AKSI</th>
                            </tr>
                        </thead>
                        <tbody id="employeeTableBody">
                            @forelse($employees as $emp)
                                <tr data-name="{{ $emp->name }}" data-position="{{ $emp->position ?? '' }}"
                                    data-status="{{ $emp->is_active ? 'active' : 'inactive' }}"
                                    data-salary-type="{{ $emp->salary_type }}">
                                    <td>
                                        <strong class="text-white small">{{ $emp->name }}</strong>
                                        @if ($emp->email)
                                            <div class="text-muted small mt-1">
                                                <i class="fas fa-envelope me-1 text-secondary"></i>{{ $emp->email }}
                                            </div>
                                        @endif
                                        @if ($emp->phone)
                                            <div class="small mt-1">
                                                <a href="tel:{{ $emp->phone }}" class="text-decoration-none text-light text-opacity-75">
                                                    <i class="fas fa-phone me-1 text-secondary"></i>{{ $emp->phone }}
                                                </a>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="small text-white-50">
                                            <i class="fas fa-briefcase me-1 text-secondary"></i>{{ $emp->position ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="lh-sm">
                                            <span class="badge {{ $emp->salary_type === 'hourly' ? 'bg-info-subtle text-info border border-info-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle' }} small mb-1">
                                                {{ $emp->salary_type === 'hourly' ? 'Per Jam' : 'Per Bulan' }}
                                            </span>
                                            <div class="text-white-50 small mt-1">
                                                Gaji Pokok: <strong class="text-white">Rp {{ number_format($emp->basic_salary, 0, ',', '.') }}</strong>
                                            </div>
                                            <div class="text-white-50 small">
                                                Tunjangan: <strong>Rp {{ number_format($emp->allowance, 0, ',', '.') }}</strong>
                                            </div>
                                            <div class="text-white-50 small">
                                                Lembur: <strong>Rp {{ number_format($emp->overtime_rate, 0, ',', '.') }}/jam</strong>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @if ($emp->username)
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-10 small">
                                                <i class="fas fa-mobile-alt me-1"></i>{{ $emp->username }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-10 small">
                                                Belum Ada
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($emp->is_active)
                                            <span class="badge bg-success-subtle text-success border border-success-subtle small">
                                                Aktif
                                            </span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle small">
                                                Nonaktif
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex gap-1 justify-content-center">
                                            <a href="{{ route('employees.edit', $emp->id) }}"
                                                class="btn btn-warning btn-action-sm text-white" title="Edit Profil">
                                                <i class="fas fa-pen"></i>
                                            </a>
                                            <button type="button" class="btn btn-success btn-action-sm edit-salary-btn"
                                                title="Atur Gaji & Tunjangan" data-id="{{ $emp->id }}"
                                                data-name="{{ $emp->name }}" data-salary-type="{{ $emp->salary_type }}"
                                                data-schedules="{{ $emp->schedules->toJson() }}"
                                                data-basic-salary="{{ $emp->basic_salary }}"
                                                data-allowances="{{ $emp->allowances->pluck('amount', 'allowance_type_id')->toJson() }}"
                                                data-overtime-rate="{{ $emp->overtime_rate }}">
                                                <i class="fas fa-money-bill-wave"></i>
                                            </button>
                                            <button type="button"
                                                class="btn btn-info btn-action-sm edit-credential-btn text-white"
                                                title="Akun Mobile" data-id="{{ $emp->id }}"
                                                data-name="{{ $emp->name }}"
                                                data-username="{{ $emp->username ?? '' }}">
                                                <i class="fas fa-key"></i>
                                            </button>
                                            <form action="{{ route('employees.destroy', $emp->id) }}" method="POST"
                                                class="confirm-delete d-inline"
                                                data-message="Data karyawan ini akan dihapus secara permanen!">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-action-sm"
                                                    title="Hapus">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <i class="fas fa-users fa-2x mb-3 d-block text-secondary opacity-25"></i>
                                        <p class="text-muted mb-0 small">Belum ada data karyawan.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>

    {{-- Modal Atur Gaji & Tunjangan --}}
    <div class="modal fade" id="salaryModal" tabindex="-1" aria-labelledby="salaryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content overflow-hidden">
                <div class="modal-header d-flex align-items-center gap-3 p-3 bg-success bg-opacity-10">
                    <div class="bg-success text-white rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 fs-5 p-2"
                        style="width: 38px; height: 38px;">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="modal-title fw-bold fs-6 mb-0" id="salaryModalLabel">Atur Gaji & Tunjangan</h5>
                        <p class="mb-0 text-muted small">Karyawan: <strong id="salaryEmployeeName"
                                class="text-success"></strong></p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="salaryForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body p-4">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold small">Jenis Gaji</label>
                                <select name="salary_type" id="salary_salary_type" class="form-select form-select-sm">
                                    <option value="monthly">Per Bulan (Fixed)</option>
                                    <option value="hourly">Per Jam</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold small" id="salary_label_basic_salary">Gaji Pokok
                                    (Bulanan)</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" name="basic_salary" id="salary_basic_salary"
                                        class="form-control form-control-sm formatted-currency text-end" value="0">
                                </div>
                            </div>
                        </div>

                        {{-- Jadwal Kerja --}}
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label fw-semibold small mb-2">Jadwal Kerja Mingguan</label>
                                <div class="table-responsive border border-secondary border-opacity-10 rounded">
                                    <table class="table table-sm table-bordered table-premium-dark align-middle mb-0"
                                        style="font-size: 0.8rem;">
                                        <thead>
                                            <tr>
                                                <th>Hari</th>
                                                <th>Masuk</th>
                                                <th>Pulang</th>
                                                <th class="text-center">Libur</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $daysMapping = [
                                                    1 => 'Senin',
                                                    2 => 'Selasa',
                                                    3 => 'Rabu',
                                                    4 => 'Kamis',
                                                    5 => 'Jumat',
                                                    6 => 'Sabtu',
                                                    7 => 'Minggu',
                                                ];
                                            @endphp
                                            @foreach ($daysMapping as $dayNum => $dayName)
                                                <tr>
                                                    <td>
                                                        <input type="hidden"
                                                            name="schedules[{{ $dayNum }}][day_of_week]"
                                                            value="{{ $dayNum }}">
                                                        <strong>{{ $dayName }}</strong>
                                                    </td>
                                                    <td>
                                                        <input type="time"
                                                            name="schedules[{{ $dayNum }}][clock_in]"
                                                            id="salary_schedule_in_{{ $dayNum }}"
                                                            class="form-control form-control-sm py-0 px-1">
                                                    </td>
                                                    <td>
                                                        <input type="time"
                                                            name="schedules[{{ $dayNum }}][clock_out]"
                                                            id="salary_schedule_out_{{ $dayNum }}"
                                                            class="form-control form-control-sm py-0 px-1">
                                                    </td>
                                                    <td class="text-center">
                                                        <input type="hidden"
                                                            name="schedules[{{ $dayNum }}][is_off]" value="0">
                                                        <input type="checkbox"
                                                            name="schedules[{{ $dayNum }}][is_off]"
                                                            id="salary_schedule_off_{{ $dayNum }}" value="1"
                                                            class="form-check-input schedule-off-check"
                                                            data-day="{{ $dayNum }}" style="cursor: pointer;">
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-semibold small">Tarif Lembur (/jam)</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" name="overtime_rate" id="salary_overtime_rate"
                                        class="form-control form-control-sm formatted-currency text-end" value="0">
                                </div>
                            </div>
                        </div>

                        <h6 class="mt-4 mb-3 pb-2 border-bottom fw-bold small text-secondary">Tunjangan Kustom</h6>

                        @if ($allowanceTypes->isEmpty())
                            <div class="alert alert-info text-center py-2 px-3 mb-0 small">
                                <i class="fas fa-info-circle me-1"></i> Belum ada jenis tunjangan kustom.
                                <a href="{{ route('hr.allowance-types.index') }}"
                                    class="alert-link text-decoration-underline">Atur Tunjangan</a>
                            </div>
                        @else
                            <div class="row g-2">
                                @foreach ($allowanceTypes as $type)
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label fw-semibold small text-truncate d-block mb-1"
                                            title="{{ $type->name }}">{{ $type->name }}</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">Rp</span>
                                            <input type="text" name="allowances[{{ $type->id }}]"
                                                id="salary_allowance_{{ $type->id }}"
                                                class="form-control form-control-sm salary-allowance-input formatted-currency text-end"
                                                value="0">
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer bg-success bg-opacity-10 px-4 py-3 d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary btn-sm px-3"
                            data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Akun Mobile --}}
    <div class="modal fade" id="credentialModal" tabindex="-1" aria-labelledby="credentialModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content overflow-hidden">
                <div class="modal-header d-flex align-items-center gap-3 p-3 bg-warning bg-opacity-10">
                    <div class="bg-warning text-dark rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 fs-5 p-2"
                        style="width: 38px; height: 38px;">
                        <i class="fas fa-key"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="modal-title fw-bold fs-6 mb-0" id="credentialModalLabel">Akun Mobile</h5>
                        <p class="mb-0 text-muted small">Karyawan: <strong id="credEmployeeName"
                                class="text-warning"></strong></p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="credentialForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body p-4">
                        <div class="alert alert-warning py-2 px-3 mb-3 small" style="font-size: 0.8rem;">
                            <i class="fas fa-info-circle me-1"></i>
                            Karyawan akan menggunakan kredensial ini untuk login aplikasi presensi di HP.
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Username</label>
                            <input type="text" name="username" id="cred_username"
                                class="form-control form-control-sm" required placeholder="budi.santoso">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Password Baru</label>
                            <input type="password" name="password" id="cred_password"
                                class="form-control form-control-sm" placeholder="Kosongkan jika tidak ingin diubah">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Konfirmasi Password Baru</label>
                            <input type="password" name="password_confirmation" id="cred_password_confirm"
                                class="form-control form-control-sm" placeholder="Ulangi password baru">
                        </div>
                    </div>
                    <div class="modal-footer bg-warning bg-opacity-10 px-4 py-3 d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary btn-sm px-3"
                            data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4">Simpan Akun</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                // Formatting currency helper
                function formatNumber(num) {
                    if (num === null || num === undefined) return '';
                    let str = num.toString().replace(/\D/g, '');
                    if (str === '') return '';
                    return parseInt(str, 10).toLocaleString('id-ID');
                }

                // Format inputs on type
                $(document).on('input', '.formatted-currency', function() {
                    $(this).val(formatNumber($(this).val()));
                });

                // Strip formatting on submit
                $('#salaryForm').on('submit', function() {
                    $(this).find('.formatted-currency').each(function() {
                        $(this).val($(this).val().replace(/\./g, ''));
                    });
                });

                // Filter logic
                function filterEmployees() {
                    let search = $('#filterSearch').val().toLowerCase();
                    let status = $('#filterStatus').val();
                    let salaryType = $('#filterSalaryType').val();

                    $('#employeeTableBody tr').each(function() {
                        if ($(this).hasClass('no-data-row')) return;

                        let name = $(this).attr('data-name').toLowerCase();
                        let position = $(this).attr('data-position').toLowerCase();
                        let rowStatus = $(this).attr('data-status');
                        let rowSalaryType = $(this).attr('data-salary-type');

                        let matchesSearch = !search || name.includes(search) || position.includes(search);
                        let matchesStatus = !status || rowStatus === status;
                        let matchesSalaryType = !salaryType || rowSalaryType === salaryType;

                        if (matchesSearch && matchesStatus && matchesSalaryType) {
                            $(this).show();
                        } else {
                            $(this).hide();
                        }
                    });

                    // Count total visible and update info bar
                    let visibleRows = $('#employeeTableBody tr:not(.no-data-row):visible');
                    $('#totalKaryawanCount').text(visibleRows.length);

                    // Show no data row if all are hidden
                    if (visibleRows.length === 0) {
                        if ($('#noDataRow').length === 0) {
                            $('#employeeTableBody').append(
                                `<tr id="noDataRow" class="no-data-row"><td colspan="6" class="text-center py-5 text-muted"><i class="fas fa-users-slash fa-2x mb-3 d-block text-secondary opacity-25"></i><p class="mb-0 small">Karyawan tidak ditemukan.</p></td></tr>`
                            );
                        }
                    } else {
                        $('#noDataRow').remove();
                    }
                }

                $('#filterSearch').on('input', filterEmployees);
                $('#filterStatus, #filterSalaryType').on('change', filterEmployees);

                $('#btnResetFilter').on('click', function() {
                    $('#filterSearch').val('');
                    $('#filterStatus').val('').trigger('change');
                    $('#filterSalaryType').val('').trigger('change');
                    filterEmployees();
                });

                // Edit Salary handler
                $(document).on('click', '.edit-salary-btn', function() {
                    const id = $(this).data('id');
                    const name = $(this).data('name');
                    const salaryType = $(this).data('salary-type') || 'monthly';
                    const schedulesArr = $(this).data('schedules');
                    const basicSalary = $(this).data('basic-salary');
                    const allowancesObj = $(this).data('allowances');
                    const overtimeRate = $(this).data('overtime-rate');

                    $('#salaryEmployeeName').text(name);
                    $('#salaryForm').attr('action', '/employees/' + id + '/salary');
                    $('#salary_salary_type').val(salaryType).trigger('change');

                    // Update label based on type
                    updateSalaryLabel(salaryType);

                    // Set schedules defaults
                    const defaults = {
                        1: {
                            in: '08:00',
                            out: '16:00',
                            off: false
                        },
                        2: {
                            in: '08:00',
                            out: '16:00',
                            off: false
                        },
                        3: {
                            in: '08:00',
                            out: '16:00',
                            off: false
                        },
                        4: {
                            in: '08:00',
                            out: '16:00',
                            off: false
                        },
                        5: {
                            in: '08:00',
                            out: '16:00',
                            off: false
                        },
                        6: {
                            in: '07:00',
                            out: '12:00',
                            off: false
                        },
                        7: {
                            in: '',
                            out: '',
                            off: true
                        },
                    };

                    for (let d = 1; d <= 7; d++) {
                        $('#salary_schedule_in_' + d).val(defaults[d].in).prop('disabled', defaults[d].off);
                        $('#salary_schedule_out_' + d).val(defaults[d].out).prop('disabled', defaults[d].off);
                        $('#salary_schedule_off_' + d).prop('checked', defaults[d].off);
                    }

                    // Apply custom schedules if present
                    if (schedulesArr && schedulesArr.length > 0) {
                        schedulesArr.forEach(function(sched) {
                            const d = sched.day_of_week;
                            const isOff = !!sched.is_off;

                            $('#salary_schedule_off_' + d).prop('checked', isOff);
                            $('#salary_schedule_in_' + d).prop('disabled', isOff);
                            $('#salary_schedule_out_' + d).prop('disabled', isOff);

                            if (isOff) {
                                $('#salary_schedule_in_' + d).val('');
                                $('#salary_schedule_out_' + d).val('');
                            } else {
                                $('#salary_schedule_in_' + d).val(sched.clock_in ? sched.clock_in
                                    .substring(0, 5) : '');
                                $('#salary_schedule_out_' + d).val(sched.clock_out ? sched.clock_out
                                    .substring(0, 5) : '');
                            }
                        });
                    }

                    // Populate basic salary and overtime
                    let parsedBasicSalary = Math.round(parseFloat(basicSalary) || 0);
                    let parsedOvertimeRate = Math.round(parseFloat(overtimeRate) || 0);

                    $('#salary_basic_salary').val(formatNumber(parsedBasicSalary));
                    $('#salary_overtime_rate').val(formatNumber(parsedOvertimeRate));

                    // Reset and populate dynamic allowances
                    $('.salary-allowance-input').val('0');
                    if (allowancesObj) {
                        for (let typeId in allowancesObj) {
                            let input = $('#salary_allowance_' + typeId);
                            if (input.length) {
                                let parsedAllowance = Math.round(parseFloat(allowancesObj[typeId]) || 0);
                                input.val(formatNumber(parsedAllowance));
                            }
                        }
                    }

                    var modal = new bootstrap.Modal(document.getElementById('salaryModal'));
                    modal.show();
                });

                function updateSalaryLabel(type) {
                    $('#salary_label_basic_salary').text(type === 'hourly' ? 'Gaji Per Jam (Rate)' :
                        'Gaji Pokok (Bulanan)');
                }

                $('#salary_salary_type').on('change', function() {
                    updateSalaryLabel($(this).val());
                });

                // Toggle day off schedules click
                $(document).on('change', '.schedule-off-check', function() {
                    const dayNum = $(this).data('day');
                    const isOff = $(this).is(':checked');
                    const inInput = $('#salary_schedule_in_' + dayNum);
                    const outInput = $('#salary_schedule_out_' + dayNum);

                    if (isOff) {
                        inInput.val('').prop('disabled', true);
                        outInput.val('').prop('disabled', true);
                    } else {
                        inInput.prop('disabled', false);
                        outInput.prop('disabled', false);
                        if (!inInput.value) {
                            inInput.value = (dayNum === 6) ? '07:00' : ((dayNum === 7) ? '' : '08:00');
                        }
                        if (!outInput.value) {
                            outInput.value = (dayNum === 6) ? '12:00' : ((dayNum === 7) ? '' : '16:00');
                        }
                    }
                });

                // Edit Credentials
                $(document).on('click', '.edit-credential-btn', function() {
                    const id = $(this).data('id');
                    const name = $(this).data('name');
                    const username = $(this).data('username');

                    $('#credEmployeeName').text(name);
                    $('#credentialForm').attr('action', '/employees/' + id + '/credentials');
                    $('#cred_username').val(username || '');
                    $('#cred_password').val('');
                    $('#cred_password_confirm').val('');

                    var modal = new bootstrap.Modal(document.getElementById('credentialModal'));
                    modal.show();
                });
            });
        </script>
    @endpush
@endsection
