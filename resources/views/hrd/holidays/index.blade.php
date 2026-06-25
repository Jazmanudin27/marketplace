@extends('layouts.app')
@section('title', 'Hari Libur')
@section('page-title', 'Pengaturan Hari Libur')

@section('content')
    <div class="row">
        <!-- Form Hari Libur (Kiri) -->
        <div class="col-lg-4 mb-4">
            <div class="card border shadow-sm overflow-hidden">
                <div class="card-header bg-primary bg-opacity-10 d-flex align-items-center gap-3 p-3 border-bottom">
                    <div class="bg-primary text-white rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 fs-5 p-2"
                        style="width: 36px; height: 36px;">
                        <i class="fas fa-calendar-plus" style="font-size: 1rem;"></i>
                    </div>
                    <h6 class="mb-0 fw-bold text-dark" id="formTitle">Tambah Hari Libur</h6>
                </div>
                <div class="p-3">
                    <form id="holidayForm" method="POST" action="{{ route('hr.holidays.store') }}">
                        @csrf
                        <input type="hidden" name="_method" id="formMethod" value="POST">

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-dark"><i class="fas fa-calendar-day text-primary me-1"></i> Nama Libur / Keterangan</label>
                            <input type="text" name="name" id="name" class="form-control form-control-sm"
                                placeholder="Contoh: Hari Raya Idul Fitri" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-dark"><i class="fas fa-calendar-alt text-primary me-1"></i> Tanggal Libur</label>
                            <input type="date" name="date" id="date" class="form-control form-control-sm" required>
                        </div>

                        <div class="d-flex gap-2 pt-2">
                            <button type="submit" class="btn btn-primary btn-sm px-3 rounded-3">
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
            <div class="card border shadow-sm overflow-hidden">
                <div class="card-header bg-info bg-opacity-10 d-flex align-items-center gap-3 p-3 border-bottom">
                    <div class="bg-info text-white rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 fs-5 p-2"
                        style="width: 36px; height: 36px;">
                        <i class="fas fa-calendar-alt" style="font-size: 1rem;"></i>
                    </div>
                    <h6 class="mb-0 fw-bold text-dark">Kalender Hari Libur Perusahaan</h6>
                </div>
                
                <div class="card-body p-3">
                    <div class="table-responsive rounded border">
                        <table class="table table-sm table-striped table-bordered align-middle mb-0">
                            <thead>
                                <tr class="small">
                                    <th style="width: 55%;">Nama Libur / Keterangan</th>
                                    <th style="width: 25%;">Hari / Tanggal</th>
                                    <th style="width: 20%;" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($holidays as $hol)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <!-- Mini Calendar Badge using Bootstrap classes -->
                                                <div class="me-3 text-center d-flex flex-column border rounded overflow-hidden shadow-sm" style="width: 48px; height: 50px;">
                                                    <div class="bg-danger text-white py-1 small fw-bold text-uppercase"
                                                        style="font-size: 0.6rem; line-height: 1.2; letter-spacing: 0.5px;">
                                                        {{ $hol->date->isoFormat('MMM') }}
                                                    </div>
                                                    <div class="flex-grow-1 d-flex align-items-center justify-content-center fw-bold text-dark bg-light"
                                                        style="line-height: 1; font-size: 1.1rem;">
                                                        {{ $hol->date->format('d') }}
                                                    </div>
                                                </div>
                                                <div>
                                                    <strong class="text-dark d-block small">{{ $hol->name }}</strong>
                                                    <!-- List Assigned Employees -->
                                                    <div class="mt-1 d-flex flex-wrap gap-1">
                                                        @if ($hol->employees->isEmpty())
                                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"
                                                                style="font-size: 0.65rem; padding: 0.25em 0.5em;">
                                                                <i class="fas fa-globe me-1"></i> Semua Karyawan
                                                            </span>
                                                        @else
                                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle"
                                                                style="font-size: 0.65rem; padding: 0.25em 0.5em; border-radius: 4px;"
                                                                title="Atur Karyawan Libur">
                                                                <i class="fas fa-user-friends me-1"></i>
                                                                {{ $hol->employees->count() }} Karyawan
                                                            </span>
                                                            @foreach ($hol->employees->take(3) as $emp)
                                                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"
                                                                    style="font-size: 0.65rem; padding: 0.25em 0.5em; border-radius: 4px;">
                                                                    {{ $emp->name }}
                                                                </span>
                                                            @endforeach
                                                            @if ($hol->employees->count() > 3)
                                                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"
                                                                    style="font-size: 0.65rem; padding: 0.25em 0.5em; border-radius: 4px;">
                                                                    +{{ $hol->employees->count() - 3 }} lainnya
                                                                </span>
                                                            @endif
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-dark fw-semibold small">
                                                {{ $hol->date->format('d-m-Y') }}
                                            </span>
                                            <small class="text-muted d-block" style="font-size: 0.72rem;">
                                                {{ $hol->date->isoFormat('dddd') }}
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-inline-flex gap-1 align-items-center">
                                                <button class="btn btn-info text-white btn-sm"
                                                    title="Atur Karyawan Libur"
                                                    onclick="openManageEmployeesModal({{ $hol->id }}, '{{ addslashes($hol->name) }}', {{ json_encode($hol->employees->pluck('id')) }})">
                                                    <i class="fas fa-users"></i>
                                                </button>
                                                <button class="btn btn-warning btn-sm"
                                                    title="Edit Hari Libur"
                                                    onclick="editHoliday({{ $hol->id }}, '{{ addslashes($hol->name) }}', '{{ $hol->date->format('Y-m-d') }}')">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form action="{{ route('hr.holidays.destroy', $hol->id) }}" method="POST"
                                                    style="display:inline;"
                                                    onsubmit="return confirm('Hapus hari libur ini?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm"
                                                        title="Hapus"><i class="fas fa-trash"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-5 text-muted">
                                            <i class="far fa-calendar-times fa-2x mb-3 d-block text-secondary opacity-25"></i>
                                            <p class="mb-0 small">Belum ada hari libur yang terdaftar.</p>
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

    <!-- Modal Atur Karyawan Libur -->
    <div class="modal fade" id="manageEmployeesModal" tabindex="-1" aria-labelledby="manageEmployeesModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header bg-primary bg-opacity-10 border-bottom py-3">
                    <h5 class="modal-title fw-bold text-dark fs-6" id="manageEmployeesModalLabel">
                        <i class="fas fa-user-tag me-2 text-primary"></i>Atur Karyawan Libur
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form id="manageEmployeesForm" method="POST" action="">
                    @csrf
                    <div class="modal-body p-4">
                        <!-- Holiday Info Header -->
                        <div class="mb-3 p-3 rounded border border-secondary-subtle bg-light d-flex align-items-center justify-content-between">
                            <div>
                                <small class="text-muted d-block text-uppercase fw-semibold mb-1"
                                    style="font-size: 0.65rem; letter-spacing: 0.5px;">Hari Libur / Acara</small>
                                <span id="modalHolidayName" class="text-dark fw-bold small"></span>
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
                        <div class="input-group input-group-sm mb-3">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" id="modalEmployeeSearch"
                                class="form-control"
                                placeholder="Cari nama atau jabatan karyawan...">
                            <button type="button" class="btn btn-outline-secondary" id="clearSearchBtn"
                                style="display: none;"><i class="fas fa-times"></i></button>
                        </div>

                        <!-- Header List & Quick Actions -->
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <label class="form-label text-muted fw-semibold small text-uppercase mb-0"
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
                                '#6C63FF', // Purple
                                '#10B981', // Emerald
                                '#F59E0B', // Amber
                                '#06B6D4', // Cyan
                                '#EC4899', // Pink
                                '#3B82F6', // Blue
                            ];
                        @endphp
                        <div class="list-group list-group-flush border rounded overflow-hidden mb-3" id="modalEmployeeList" style="max-height: 300px; overflow-y: auto;">
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
                                <div class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-2 px-3 employee-card"
                                    data-id="{{ $emp->id }}" data-name="{{ strtolower($emp->name) }}"
                                    data-position="{{ strtolower($emp->position ?? '') }}" style="cursor: pointer;">
                                    <div class="d-flex align-items-center">
                                        <div class="text-white rounded-circle d-flex align-items-center justify-content-center fw-bold me-3" 
                                            style="background: {{ $avatarBg }}; width: 32px; height: 32px; font-size: 0.75rem;">
                                            {{ $initials }}
                                        </div>
                                        <div>
                                            <div class="fw-semibold text-dark small">{{ $emp->name }}</div>
                                            <div class="text-muted" style="font-size: 0.7rem;">{{ $emp->position ?: 'Staf / Karyawan' }}</div>
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
                        <div class="alert alert-info d-flex align-items-start p-2 mb-0 small">
                            <i class="fas fa-info-circle me-2 mt-1"></i>
                            <div>
                                <strong>Petunjuk:</strong> Kosongkan jika hari libur berlaku secara nasional (seluruh karyawan diliburkan otomatis).
                            </div>
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
                    card.addClass('bg-primary bg-opacity-10');
                } else {
                    card.removeClass('bg-primary bg-opacity-10');
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
                    $('.employee-card').removeClass('bg-primary bg-opacity-10');
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
                $('.employee-card').removeClass('bg-primary bg-opacity-10');

                // Check matching employee checkboxes
                if (employeeIds && Array.isArray(employeeIds)) {
                    employeeIds.forEach(function(empId) {
                        var checkbox = $('#emp_check_' + empId);
                        if (checkbox.length) {
                            checkbox.prop('checked', true);
                            checkbox.closest('.employee-card').addClass('bg-primary bg-opacity-10');
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
