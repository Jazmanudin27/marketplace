@extends('layouts.app')
@section('title', 'Hari Libur')
@section('page-title', 'Pengaturan Hari Libur')

@push('styles')
    <style>
        .calendar-badge {
            width: 48px;
            height: 52px;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid var(--border);
            background: var(--bg-card2);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .calendar-badge:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px var(--primary-glow);
        }

        .holiday-row {
            transition: background-color 0.2s ease;
        }

        .holiday-row:hover {
            background-color: rgba(255, 255, 255, 0.015) !important;
        }

        /* Custom modal employee layout styling */
        .employee-search-container {
            position: relative;
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid var(--border);
            border-radius: 10px;
            overflow: hidden;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .employee-search-container:focus-within {
            border-color: var(--primary) !important;
            box-shadow: 0 0 10px var(--primary-glow);
        }

        .employee-search-container input {
            box-shadow: none !important;
        }

        .employee-card-list {
            max-height: 360px;
            overflow-y: auto;
            padding-right: 5px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .employee-card-list::-webkit-scrollbar {
            width: 6px;
        }

        .employee-card-list::-webkit-scrollbar-track {
            background: transparent;
        }

        .employee-card-list::-webkit-scrollbar-thumb {
            background: var(--border-light);
            border-radius: 10px;
        }

        .employee-card-list::-webkit-scrollbar-thumb:hover {
            background: var(--text-muted);
        }

        .employee-card {
            background: var(--bg-card2) !important;
            border: 1px solid var(--border) !important;
            border-radius: 10px;
            padding: 12px 16px;
            cursor: pointer;
            transition: all 0.2s ease;
            user-select: none;
        }

        .employee-card:hover {
            background: rgba(255, 255, 255, 0.03) !important;
            border-color: rgba(255, 255, 255, 0.15) !important;
            transform: translateY(-1px);
        }

        .employee-card.selected {
            background: rgba(108, 99, 255, 0.08) !important;
            border-color: rgba(108, 99, 255, 0.4) !important;
            box-shadow: 0 4px 15px rgba(108, 99, 255, 0.15);
        }

        .employee-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #fff;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .employee-name {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .employee-position {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        /* Custom styled switch toggle */
        .employee-card .form-check-input {
            width: 2.8em;
            height: 1.5em;
            margin-top: 0;
            background-color: rgba(255, 255, 255, 0.1);
            border-color: var(--border);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .employee-card .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
            box-shadow: 0 0 10px var(--primary-glow);
        }
    </style>
@endpush

@section('content')
    <div class="row">
        <!-- Form Hari Libur (Kiri) -->
        <div class="col-lg-4 mb-4">
            <div class="dashboard-card p-0 overflow-hidden h-100">
                <div class="card-header-line d-flex align-items-center p-3 mb-0 bg-primary bg-opacity-10">
                    <div class="bg-primary text-white rounded p-2 me-3 d-flex align-items-center justify-content-center"
                        style="width: 36px; height: 36px;">
                        <i class="fas fa-calendar-plus text-white"></i>
                    </div>
                    <h5 class="mb-0 fw-bold text-white" id="formTitle">Tambah Hari Libur</h5>
                </div>
                <div class="p-4">
                    <form id="holidayForm" method="POST" action="{{ route('hr.holidays.store') }}">
                        @csrf
                        <input type="hidden" name="_method" id="formMethod" value="POST">

                        <div class="mb-4">
                            <label class="form-label"><i class="fas fa-calendar-day text-primary me-1"></i> Nama Libur / Keterangan</label>
                            <input type="text" name="name" id="name" class="form-control"
                                placeholder="Contoh: Hari Raya Idul Fitri" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label"><i class="fas fa-calendar-alt text-primary me-1"></i> Tanggal Libur</label>
                            <input type="date" name="date" id="date" class="form-control" required>
                        </div>

                        <div class="d-flex gap-2 pt-2">
                            <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold">
                                <i class="fas fa-save me-1"></i> Simpan
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm px-3" id="btnCancel"
                                style="display:none;" onclick="resetForm()">Batal</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Tabel Daftar Hari Libur (Kanan) -->
        <div class="col-lg-8 mb-4">
            <div class="dashboard-card p-0 overflow-hidden h-100">
                <div class="card-header-line d-flex align-items-center p-3 mb-0 bg-success bg-opacity-10">
                    <div class="bg-success text-white rounded p-2 me-3 d-flex align-items-center justify-content-center"
                        style="width: 36px; height: 36px;">
                        <i class="fas fa-calendar-alt text-white"></i>
                    </div>
                    <h5 class="mb-0 fw-bold text-white">Kalender Hari Libur Perusahaan</h5>
                </div>
                <div class="table-responsive p-3 pt-0">
                    <table class="table table-bordered table-premium-dark align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 55%;">Nama Libur / Keterangan</th>
                                <th style="width: 25%;">Hari / Tanggal</th>
                                <th style="width: 20%;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($holidays as $hol)
                                <tr class="holiday-row">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <!-- Mini Calendar Badge -->
                                            <div class="calendar-badge me-3 text-center d-flex flex-column">
                                                <div class="bg-danger text-white py-1 small fw-bold text-uppercase"
                                                    style="font-size: 0.6rem; line-height: 1.2; letter-spacing: 0.5px;">
                                                    {{ $hol->date->isoFormat('MMM') }}
                                                </div>
                                                <div class="flex-grow-1 d-flex align-items-center justify-content-center fs-5 fw-bold text-white"
                                                    style="line-height: 1;">
                                                    {{ $hol->date->format('d') }}
                                                </div>
                                            </div>
                                            <div>
                                                <strong class="text-white d-block"
                                                    style="font-size: 0.85rem;">{{ $hol->name }}</strong>
                                                <!-- List Assigned Employees -->
                                                <div class="mt-1 d-flex flex-wrap gap-1">
                                                    @if ($hol->employees->isEmpty())
                                                        <span
                                                            class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"
                                                            style="font-size: 0.65rem; padding: 0.25em 0.5em;">
                                                            <i class="fas fa-globe me-1"></i> Semua Karyawan
                                                        </span>
                                                    @else
                                                        <span
                                                            class="badge bg-primary-subtle text-primary border border-primary-subtle"
                                                            style="font-size: 0.65rem; padding: 0.25em 0.5em; border-radius: 4px;"
                                                            title="Atur Karyawan Libur">
                                                            <i class="fas fa-user-friends me-1"></i>
                                                            {{ $hol->employees->count() }} Karyawan
                                                        </span>
                                                        @foreach ($hol->employees->take(3) as $emp)
                                                            <span
                                                                class="badge bg-dark-subtle text-white border border-light-subtle"
                                                                style="font-size: 0.65rem; padding: 0.25em 0.5em; border-radius: 4px; background: rgba(255,255,255,0.03);">
                                                                {{ $emp->name }}
                                                            </span>
                                                        @endforeach
                                                        @if ($hol->employees->count() > 3)
                                                            <span
                                                                class="badge bg-dark-subtle text-white border border-light-subtle"
                                                                style="font-size: 0.65rem; padding: 0.25em 0.5em; border-radius: 4px; background: rgba(255,255,255,0.03);">
                                                                +{{ $hol->employees->count() - 3 }} lainnya
                                                            </span>
                                                        @endif
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-white fw-semibold" style="font-size: 0.82rem;">
                                            {{ $hol->date->format('d-m-Y') }}
                                        </span>
                                        <small class="text-muted d-block" style="font-size: 0.72rem;">
                                            {{ $hol->date->isoFormat('dddd') }}
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-inline-flex gap-1 align-items-center">
                                            <button class="btn btn-info text-white btn-action-sm"
                                                title="Atur Karyawan Libur"
                                                onclick="openManageEmployeesModal({{ $hol->id }}, '{{ addslashes($hol->name) }}', {{ json_encode($hol->employees->pluck('id')) }})">
                                                <i class="fas fa-users"></i>
                                            </button>
                                            <button class="btn btn-warning btn-action-sm"
                                                title="Edit Hari Libur"
                                                onclick="editHoliday({{ $hol->id }}, '{{ addslashes($hol->name) }}', '{{ $hol->date->format('Y-m-d') }}')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form action="{{ route('hr.holidays.destroy', $hol->id) }}" method="POST"
                                                style="display:inline;"
                                                onsubmit="return confirm('Hapus hari libur ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-action-sm"
                                                    title="Hapus"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center py-5 text-muted">
                                        <i class="far fa-calendar-times fa-2x mb-3 d-block text-secondary opacity-25"></i>
                                        Belum ada hari libur yang terdaftar.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Atur Karyawan Libur -->
    <div class="modal fade" id="manageEmployeesModal" tabindex="-1" aria-labelledby="manageEmployeesModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold text-white fs-6" id="manageEmployeesModalLabel">
                        <i class="fas fa-user-tag me-2 text-primary"></i>Atur Karyawan Libur
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form id="manageEmployeesForm" method="POST" action="">
                    @csrf
                    <div class="modal-body p-4">
                        <!-- Holiday Info Header -->
                        <div class="mb-4 p-3 rounded border border-light-subtle d-flex align-items-center justify-content-between">
                            <div>
                                <small class="text-muted d-block text-uppercase fw-semibold mb-1"
                                    style="font-size: 0.65rem; letter-spacing: 0.5px;">Hari Libur / Acara</small>
                                <span id="modalHolidayName" class="text-white fw-bold fs-6"></span>
                            </div>
                            <div class="text-end">
                                <small class="text-muted d-block text-uppercase fw-semibold mb-1"
                                    style="font-size: 0.65rem; letter-spacing: 0.5px;">Status Seleksi</small>
                                <span id="selectedCounterBadge"
                                    class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 fw-bold"
                                    style="font-size: 0.75rem; border-radius: 6px;">0 Terpilih</span>
                            </div>
                        </div>

                        <!-- Live Search Input -->
                        <div class="mb-3">
                            <div class="input-group employee-search-container">
                                <span class="input-group-text border-0 bg-transparent text-muted pe-2"><i
                                        class="fas fa-search"></i></span>
                                <input type="text" id="modalEmployeeSearch"
                                    class="form-control border-0 bg-transparent text-white placeholder-muted py-2"
                                    placeholder="Cari nama atau jabatan karyawan..."
                                    style="font-size: 0.9rem; box-shadow: none;">
                                <button type="button" class="btn border-0 text-muted p-2" id="clearSearchBtn"
                                    style="display: none;"><i class="fas fa-times"></i></button>
                            </div>
                        </div>

                        <!-- Header List & Quick Actions -->
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <label class="form-label text-white-50 fw-semibold small text-uppercase mb-0"
                                style="font-size: 0.7rem; letter-spacing: 0.5px;">Daftar Karyawan</label>
                            <div class="d-flex gap-2">
                                <button type="button"
                                    class="btn btn-sm btn-outline-primary py-1 px-2 text-uppercase fw-bold"
                                    id="btnSelectAll"
                                    style="font-size: 0.65rem; border-radius: 6px; transition: all 0.2s ease;">
                                    <i class="fas fa-check-double me-1"></i> Pilih Semua
                                </button>
                                <button type="button"
                                    class="btn btn-sm btn-outline-secondary py-1 px-2 text-uppercase fw-bold"
                                    id="btnClearAll"
                                    style="font-size: 0.65rem; border-radius: 6px; transition: all 0.2s ease;">
                                    <i class="fas fa-times me-1"></i> Kosongkan
                                </button>
                            </div>
                        </div>

                        <!-- Scrollable Cards Container -->
                        @php
                            $colors = [
                                'linear-gradient(135deg, #6C63FF, #8B5CF6)', // Purple
                                'linear-gradient(135deg, #10B981, #059669)', // Emerald
                                'linear-gradient(135deg, #F59E0B, #D97706)', // Amber
                                'linear-gradient(135deg, #06B6D4, #0891B2)', // Cyan
                                'linear-gradient(135deg, #EC4899, #BE185D)', // Pink
                                'linear-gradient(135deg, #3B82F6, #1D4ED8)', // Blue
                            ];
                        @endphp
                        <div class="employee-card-list mb-3" id="modalEmployeeList">
                            @foreach ($employees as $index => $emp)
                                @php
                                    $avatarBg = $colors[$index % count($colors)];
                                    $words = explode(' ', $emp->name);
                                    $initials = '';
                                    foreach ($words as $w) {
                                        if (strlen($initials) < 2) {
                                            $initials .= strtoupper(substr($w, 0, 1));
                                        }
                                    }
                                @endphp
                                <div class="employee-card d-flex align-items-center justify-content-between"
                                    data-id="{{ $emp->id }}" data-name="{{ strtolower($emp->name) }}"
                                    data-position="{{ strtolower($emp->position ?? '') }}">
                                    <div class="d-flex align-items-center">
                                        <div class="employee-avatar me-3" style="background: {{ $avatarBg }};">
                                            {{ $initials }}
                                        </div>
                                        <div>
                                            <div class="employee-name">{{ $emp->name }}</div>
                                            <div class="employee-position">{{ $emp->position ?: 'Staf / Karyawan' }}</div>
                                        </div>
                                    </div>
                                    <div class="form-check form-switch p-0 m-0">
                                        <input class="form-check-input employee-checkbox" type="checkbox"
                                            name="employee_ids[]" value="{{ $emp->id }}"
                                            id="emp_check_{{ $emp->id }}">
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Footer Info Notice -->
                        <div class="rounded p-3 border d-flex align-items-start"
                            style="background: rgba(6, 182, 212, 0.03); border-color: rgba(6, 182, 212, 0.15) !important;">
                            <i class="fas fa-info-circle text-info me-2 mt-1" style="font-size: 0.85rem;"></i>
                            <small class="text-white-50" style="font-size: 0.75rem; line-height: 1.45;">
                                <strong class="text-white">Petunjuk:</strong> Kosongkan jika hari libur berlaku secara
                                nasional (seluruh karyawan diliburkan otomatis).
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer border-top py-3">
                        <button type="button" class="btn btn-secondary btn-sm px-3"
                            data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold"><i
                                class="fas fa-save me-1"></i> Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function updateSelectedCount() {
                var checkedCount = $('.employee-checkbox:checked').length;
                $('#selectedCounterBadge').text(checkedCount + ' Terpilih');
            }

            function updateCardStyle(card) {
                var checkbox = card.find('.employee-checkbox');
                if (checkbox.prop('checked')) {
                    card.addClass('selected');
                } else {
                    card.removeClass('selected');
                }
            }

            $(document).ready(function() {
                // Handle employee card clicking
                $(document).on('click', '.employee-card', function(e) {
                    if ($(e.target).is('.employee-checkbox')) {
                        // Direct switch clicks
                        updateCardStyle($(this));
                        updateSelectedCount();
                        return;
                    }
                    var checkbox = $(this).find('.employee-checkbox');
                    checkbox.prop('checked', !checkbox.prop('checked'));
                    updateCardStyle($(this));
                    updateSelectedCount();
                });

                // Clear styling / update if checkbox changes manually
                $(document).on('change', '.employee-checkbox', function() {
                    var card = $(this).closest('.employee-card');
                    updateCardStyle(card);
                    updateSelectedCount();
                });

                // Search filtering
                $('#modalEmployeeSearch').on('keyup input', function() {
                    var query = $(this).val().toLowerCase().trim();
                    if (query.length > 0) {
                        $('#clearSearchBtn').show();
                    } else {
                        $('#clearSearchBtn').hide();
                    }

                    $('.employee-card').each(function() {
                        var name = $(this).data('name');
                        var position = $(this).data('position');
                        if (name.indexOf(query) > -1 || position.indexOf(query) > -1) {
                            $(this).show();
                        } else {
                            $(this).hide();
                        }
                    });
                });

                // Clear search button click
                $('#clearSearchBtn').on('click', function() {
                    $('#modalEmployeeSearch').val('').trigger('input');
                });

                // Select all visible employees
                $('#btnSelectAll').on('click', function() {
                    $('.employee-card:visible').each(function() {
                        var checkbox = $(this).find('.employee-checkbox');
                        checkbox.prop('checked', true);
                        updateCardStyle($(this));
                    });
                    updateSelectedCount();
                });

                // Clear all selections
                $('#btnClearAll').on('click', function() {
                    $('.employee-checkbox').prop('checked', false);
                    $('.employee-card').removeClass('selected');
                    updateSelectedCount();
                });
            });

            function openManageEmployeesModal(id, name, employeeIds) {
                document.getElementById('modalHolidayName').innerText = name;
                document.getElementById('manageEmployeesForm').action = '/hr/holidays/' + id + '/employees';

                // Clear search
                $('#modalEmployeeSearch').val('');
                $('#clearSearchBtn').hide();
                $('.employee-card').show();

                // Uncheck all & reset class styling
                $('.employee-checkbox').prop('checked', false);
                $('.employee-card').removeClass('selected');

                // Check matching employee checkboxes
                if (employeeIds && Array.isArray(employeeIds)) {
                    employeeIds.forEach(function(empId) {
                        var checkbox = $('#emp_check_' + empId);
                        if (checkbox.length) {
                            checkbox.prop('checked', true);
                            checkbox.closest('.employee-card').addClass('selected');
                        }
                    });
                }

                updateSelectedCount();

                // Show modal
                var modal = new bootstrap.Modal(document.getElementById('manageEmployeesModal'));
                modal.show();
            }

            function editHoliday(id, name, date) {
                document.getElementById('formTitle').innerText = 'Edit Hari Libur';
                document.getElementById('formMethod').value = 'PUT';
                document.getElementById('holidayForm').action = '/hr/holidays/' + id;

                document.getElementById('name').value = name;
                document.getElementById('date').value = date;

                document.getElementById('btnCancel').style.display = 'inline-block';
            }

            function resetForm() {
                document.getElementById('formTitle').innerText = 'Tambah Hari Libur';
                document.getElementById('formMethod').value = 'POST';
                document.getElementById('holidayForm').action = '{{ route('hr.holidays.store') }}';

                document.getElementById('name').value = '';
                document.getElementById('date').value = '';

                document.getElementById('btnCancel').style.display = 'none';
            }
        </script>
    @endpush
@endsection
