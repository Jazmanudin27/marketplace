@extends('layouts.app')
@section('title', 'Presensi & Absensi Karyawan')
@section('page-title', 'Presensi & Absensi Karyawan')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <style>
        .leaflet-popup-content-wrapper {
            background: #ffffff !important;
            border: 1px solid #dee2e6 !important;
            color: #212529 !important;
            font-family: inherit !important;
            border-radius: 8px !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
        }

        .leaflet-popup-tip {
            background: #ffffff !important;
            border: 1px solid #dee2e6 !important;
        }
    </style>
@endpush

@section('content')
    @php
        $isOwner = Auth::user()->hasPermissionTo('approve-attendance-corrections');
    @endphp
    
    {{-- Filter Date --}}
    <div class="row mb-3">
        <div class="col-lg-4 col-md-6">
            <div class="card border shadow-sm p-3">
                <form method="GET" action="{{ route('hr.attendance.index') }}" id="dateFilterForm">
                    <label class="form-label fw-bold small text-dark"><i class="fas fa-calendar-day text-primary me-1"></i> Pilih Tanggal Presensi</label>
                    <div class="input-group input-group-sm">
                        <input type="date" name="date" class="form-control form-control-sm"
                            value="{{ $date }}" onchange="document.getElementById('dateFilterForm').submit()">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search me-1"></i> Tampilkan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Main Attendance List --}}
    <div class="card border shadow-sm overflow-hidden">
        <div class="card-header bg-info bg-opacity-10 d-flex justify-content-between align-items-center p-3 border-bottom">
            <div class="d-flex align-items-center">
                <div class="bg-info text-white rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 fs-5 p-2 me-3"
                    style="width: 36px; height: 36px;">
                    <i class="fas fa-calendar-check" style="font-size: 1rem;"></i>
                </div>
                <div>
                    <h6 class="mb-0 fw-bold text-dark">Daftar Kehadiran — {{ date('d M Y', strtotime($date)) }}</h6>
                </div>
            </div>
            <div class="text-muted small d-none d-md-block"><i class="fas fa-info-circle text-info me-1"></i> Karyawan dengan izin/sakit/cuti yang disetujui ditampilkan terpisah di bawah.</div>
        </div>
        
        <div class="card-body p-3">
            <div class="table-responsive rounded border">
                <table class="table table-sm table-striped table-bordered align-middle mb-0">
                    <thead>
                        <tr class="small">
                            <th style="width: 25%;">Nama Karyawan</th>
                            <th style="width: 15%;">Jam Masuk</th>
                            <th style="width: 15%;">Jam Pulang</th>
                            <th style="width: 25%;">Verifikasi Selfie & GPS</th>
                            <th style="width: 20%;">Keterangan / Catatan</th>
                            <th style="width: 10%;" class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $emp)
                            @php
                                $approvedLeave = isset($approvedLeaves) ? $approvedLeaves->get($emp->id) : null;
                                $attendance = $attendances->get($emp->id);
                            @endphp
                            @continue($approvedLeave)
                            @php
                                $sched = $emp->getScheduleForDate($date);

                                // Default schedule values
                                $defaultIn = $sched->clock_in ? date('H:i', strtotime($sched->clock_in)) : '';
                                $defaultOut = $sched->clock_out ? date('H:i', strtotime($sched->clock_out)) : '';

                                // Displayed schedule values (might be overridden on attendance record)
                                $schedInVal =
                                    $attendance && $attendance->schedule_clock_in
                                        ? date('H:i', strtotime($attendance->schedule_clock_in))
                                        : $defaultIn;
                                $schedOutVal =
                                    $attendance && $attendance->schedule_clock_out
                                        ? date('H:i', strtotime($attendance->schedule_clock_out))
                                        : $defaultOut;

                                $clockIn = $attendance
                                    ? ($attendance->clock_in
                                        ? date('H:i', strtotime($attendance->clock_in))
                                        : '')
                                    : $defaultIn;
                                $clockOut = $attendance
                                    ? ($attendance->clock_out
                                        ? date('H:i', strtotime($attendance->clock_out))
                                        : '')
                                    : $defaultOut;
                                $isDeducted = $attendance ? $attendance->is_deducted : false;
                                $notes = $attendance ? $attendance->notes : '';
                            @endphp
                            <tr>
                                <td>
                                    <input type="hidden" name="attendance[{{ $emp->id }}][status]" value="present">
                                    <div class="d-flex align-items-center">
                                        @php
                                            $words = explode(' ', $emp->name);
                                            $initials = '';
                                            if (count($words) >= 2) {
                                                $initials = strtoupper(
                                                    substr($words[0], 0, 1) . substr($words[1], 0, 1),
                                                );
                                            } else {
                                                $initials = strtoupper(substr($emp->name, 0, 2));
                                            }
                                            $gradients = [
                                                '#6C63FF',
                                                '#10B981',
                                                '#F59E0B',
                                                '#EF4444',
                                                '#06B6D4',
                                                '#EC4899',
                                            ];
                                            $grad = $gradients[$emp->id % count($gradients)];
                                        @endphp
                                        <div class="text-white rounded-circle d-flex align-items-center justify-content-center fw-bold me-2"
                                            style="background: {{ $grad }}; width:30px; height:30px; font-size:0.7rem; flex-shrink:0;">
                                            {{ $initials }}
                                        </div>
                                        <div>
                                            <strong class="text-dark d-block small">{{ $emp->name }}</strong>
                                            <span class="text-muted small" style="font-size:0.7rem;">{{ $emp->position ?? 'Karyawan' }}</span>
                                            <div class="d-flex flex-wrap gap-1 align-items-center mt-1">
                                                <span class="badge {{ $attendance ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-warning-subtle text-warning border border-warning-subtle' }} attendance-status-badge"
                                                    id="status_badge_{{ $emp->id }}" style="font-size: 0.65rem; padding: 0.25em 0.5em;">
                                                    <i class="fas {{ $attendance ? 'fa-check-circle' : 'fa-info-circle' }} me-1"></i>
                                                    {{ $attendance ? 'Tersimpan' : 'Belum Disimpan' }}
                                                </span>
                                                @if ($attendance && $attendance->late_minutes > 0)
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 0.65rem; padding: 0.25em 0.5em;">
                                                        <i class="fas fa-exclamation-circle me-1"></i> Terlambat {{ $attendance->late_minutes }}m (Denda: Rp {{ number_format($attendance->late_penalty, 0, ',', '.') }})
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark small">{{ $clockIn ?: '-' }}</div>
                                    <div class="mt-1 text-muted" style="font-size: 0.7rem; white-space: nowrap;">Jadwal: {{ $schedInVal ?: '-' }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark small">{{ $clockOut ?: '-' }}</div>
                                    <div class="mt-1 text-muted" style="font-size: 0.7rem; white-space: nowrap;">Jadwal: {{ $schedOutVal ?: '-' }}</div>
                                </td>
                                <td>
                                    @if ($attendance && ($attendance->photo_in || $attendance->photo_out || $attendance->latitude_in || $attendance->latitude_out))
                                        <div class="d-flex flex-column gap-1">
                                            <!-- Check In Proof -->
                                            @if ($attendance->photo_in || $attendance->latitude_in)
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle py-1" style="font-size: 0.65rem; min-width: 45px;">Masuk:</span>
                                                    @if ($attendance->photo_in)
                                                        <a href="javascript:void(0)"
                                                            onclick="showProofModal('{{ asset($attendance->photo_in) }}', {{ $attendance->latitude_in ?? 'null' }}, {{ $attendance->longitude_in ?? 'null' }}, 'Bukti Check In - {{ $emp->name }}')"
                                                            title="Lihat Foto Selfie">
                                                            <img src="{{ asset($attendance->photo_in) }}" class="rounded border border-secondary-subtle"
                                                                style="width: 28px; height: 28px; object-fit: cover; cursor: pointer;">
                                                        </a>
                                                    @endif
                                                    @if ($attendance->latitude_in && $attendance->longitude_in)
                                                        <a href="https://www.google.com/maps?q={{ $attendance->latitude_in }},{{ $attendance->longitude_in }}"
                                                            target="_blank" class="btn btn-outline-info btn-sm p-1 py-0" title="Google Maps">
                                                            <i class="fas fa-map-marker-alt" style="font-size: 0.65rem;"></i>
                                                        </a>
                                                        <a href="javascript:void(0)"
                                                            onclick="showMapModal({{ $attendance->latitude_in }}, {{ $attendance->longitude_in }}, 'Peta Check In - {{ $emp->name }}')"
                                                            class="btn btn-outline-success btn-sm p-1 py-0" title="Lihat Peta">
                                                            <i class="fas fa-map" style="font-size: 0.65rem;"></i>
                                                        </a>
                                                    @endif
                                                </div>
                                            @endif

                                            <!-- Check Out Proof -->
                                            @if ($attendance->photo_out || $attendance->latitude_out)
                                                <div class="d-flex align-items-center gap-2 mt-1">
                                                    <span class="badge bg-info-subtle text-info border border-info-subtle py-1" style="font-size: 0.65rem; min-width: 45px;">Pulang:</span>
                                                    @if ($attendance->photo_out)
                                                        <a href="javascript:void(0)"
                                                            onclick="showProofModal('{{ asset($attendance->photo_out) }}', {{ $attendance->latitude_out ?? 'null' }}, {{ $attendance->longitude_out ?? 'null' }}, 'Bukti Check Out - {{ $emp->name }}')"
                                                            title="Lihat Foto Selfie">
                                                            <img src="{{ asset($attendance->photo_out) }}" class="rounded border border-secondary-subtle"
                                                                style="width: 28px; height: 28px; object-fit: cover; cursor: pointer;">
                                                        </a>
                                                    @endif
                                                    @if ($attendance->latitude_out && $attendance->longitude_out)
                                                        <a href="https://www.google.com/maps?q={{ $attendance->latitude_out }},{{ $attendance->longitude_out }}"
                                                            target="_blank" class="btn btn-outline-info btn-sm p-1 py-0" title="Google Maps">
                                                            <i class="fas fa-map-marker-alt" style="font-size: 0.65rem;"></i>
                                                        </a>
                                                        <a href="javascript:void(0)"
                                                            onclick="showMapModal({{ $attendance->latitude_out }}, {{ $attendance->longitude_out }}, 'Peta Check Out - {{ $emp->name }}')"
                                                            class="btn btn-outline-success btn-sm p-1 py-0" title="Lihat Peta">
                                                            <i class="fas fa-map" style="font-size: 0.65rem;"></i>
                                                        </a>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted small font-italic" style="font-size: 0.75rem;">Tidak ada bukti</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-muted small">{{ $notes ?: '-' }}</span>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size:0.75rem;"
                                        onclick="openProposeCorrectionModal({{ $emp->id }}, '{{ addslashes($emp->name) }}', '{{ $clockIn }}', '{{ $clockOut }}')">
                                        <i class="fas fa-edit me-1"></i> Koreksi
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="far fa-user-circle fa-2x mb-3 text-secondary opacity-25"></i>
                                    <p class="mb-0 small">Belum ada data karyawan aktif. Tambahkan karyawan terlebih dahulu.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Pending Corrections Section --}}
    @if ($pendingCorrections->isNotEmpty())
        <div class="card border shadow-sm overflow-hidden mt-3">
            <div class="card-header bg-warning bg-opacity-10 d-flex justify-content-between align-items-center p-3 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-edit text-warning"></i>
                    <h6 class="mb-0 fw-bold text-dark">Pengajuan Koreksi Kehadiran (Menunggu Persetujuan)</h6>
                </div>
                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">{{ $pendingCorrections->count() }} Pengajuan</span>
            </div>
            
            <div class="card-body p-3">
                <div class="table-responsive rounded border">
                    <table class="table table-sm table-striped table-bordered align-middle mb-0">
                        <thead>
                            <tr class="small">
                                <th style="width: 25%;">Nama Karyawan</th>
                                <th style="width: 15%;">Tanggal Absen</th>
                                <th style="width: 20%;">Waktu Koreksi</th>
                                <th style="width: 25%;">Alasan Pengajuan</th>
                                <th style="width: 15%;" class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pendingCorrections as $corr)
                                <tr>
                                    <td>
                                        <strong class="text-dark d-block small">{{ $corr->employee->name }}</strong>
                                        <small class="text-muted" style="font-size: 0.7rem;">{{ $corr->employee->position ?? 'Karyawan' }}</small>
                                    </td>
                                    <td>
                                        <span class="text-dark fw-semibold small">{{ $corr->date->format('d M Y') }}</span>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.75rem;">
                                            @if ($corr->clock_in)
                                                <span class="badge bg-success-subtle text-success border border-success-subtle py-1">Masuk: {{ date('H:i', strtotime($corr->clock_in)) }}</span>
                                            @endif
                                            @if ($corr->clock_out)
                                                <span class="badge bg-info-subtle text-info border border-info-subtle py-1 {{ $corr->clock_in ? 'mt-1' : '' }}">Pulang: {{ date('H:i', strtotime($corr->clock_out)) }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-muted small italic">"{{ $corr->reason }}"</span>
                                    </td>
                                    <td class="text-end">
                                        @if ($isOwner)
                                            <button type="button" class="btn btn-sm btn-outline-success py-0 px-2 me-1" 
                                                    onclick="openCorrectionActionModal('approve', '{{ route('hr.attendance.corrections.approve', $corr->id) }}', '{{ $corr->employee->name }}', '{{ $corr->date->format('d/m/Y') }}')">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2"
                                                    onclick="openCorrectionActionModal('reject', '{{ route('hr.attendance.corrections.reject', $corr->id) }}', '{{ $corr->employee->name }}', '{{ $corr->date->format('d/m/Y') }}')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size:0.7rem;">
                                                <i class="fas fa-clock me-1"></i> Menunggu Owner
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- Approved Leave Info Section --}}
    @php
        $leaveEmployees = $employees->filter(fn($e) => isset($approvedLeaves) && $approvedLeaves->has($e->id));
    @endphp
    @if ($leaveEmployees->isNotEmpty())
        <div class="card border shadow-sm overflow-hidden mt-3">
            <div class="card-header bg-info bg-opacity-10 d-flex justify-content-between align-items-center p-3 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-file-medical text-info"></i>
                    <h6 class="mb-0 fw-bold text-dark">Karyawan dengan Izin / Sakit / Cuti Disetujui — {{ date('d M Y', strtotime($date)) }}</h6>
                </div>
            </div>
            
            <div class="card-body p-3">
                <div class="table-responsive rounded border">
                    <table class="table table-sm table-striped table-bordered align-middle mb-0">
                        <thead>
                            <tr class="small">
                                <th>Nama Karyawan</th>
                                <th>Jenis</th>
                                <th>Keterangan</th>
                                <th>Potong Gaji?</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($leaveEmployees as $emp)
                                @php
                                    $lv = $approvedLeaves->get($emp->id);
                                    $lvLabels = ['sick' => 'Sakit', 'permission' => 'Izin', 'leave' => 'Cuti'];
                                    $lvLabel = $lvLabels[$lv->type] ?? ucfirst($lv->type);
                                    $lvColor = $lv->type === 'leave' ? 'bg-success-subtle text-success border border-success-subtle' : ($lv->type === 'sick' ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-info-subtle text-info border border-info-subtle');
                                @endphp
                                <tr>
                                    <td>
                                        <strong class="text-dark d-block small">{{ $emp->name }}</strong>
                                        <small class="d-block text-muted" style="font-size:0.7rem;">{{ $emp->position ?? 'Karyawan' }}</small>
                                    </td>
                                    <td>
                                        <span class="badge {{ $lvColor }}" style="font-size: 0.65rem; padding: 0.25em 0.5em;">
                                            <i class="fas fa-file-medical me-1"></i> {{ $lvLabel }}
                                        </span>
                                    </td>
                                    <td class="text-muted small">{{ $lv->notes ?: '-' }}</td>
                                    <td>
                                        @if ($lv->is_deducted)
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 0.65rem; padding: 0.25em 0.5em;"><i class="fas fa-minus-circle me-1"></i> Dipotong</span>
                                        @else
                                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.65rem; padding: 0.25em 0.5em;"><i class="fas fa-check me-1"></i> Tidak Dipotong</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <!-- Verification Proof Modal (Selfie & Map) -->
    <div class="modal fade" id="attendanceProofModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary bg-opacity-10 border-bottom py-3">
                    <h5 class="modal-title fw-bold text-dark fs-6" id="proofModalTitle">Bukti Presensi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-5 text-center d-flex flex-column align-items-center justify-content-center">
                            <label class="form-label text-muted mb-2 small fw-bold text-uppercase">Foto Selfie Wajah</label>
                            <img id="proofModalImage" src="" class="img-fluid rounded border border-secondary-subtle"
                                style="max-height: 280px; object-fit: contain; background: #000; width: 100%;">
                        </div>
                        <div class="col-md-7 d-flex flex-column">
                            <label class="form-label text-muted mb-2 small fw-bold text-uppercase">Lokasi GPS Terkunci</label>
                            <div id="proofModalMap" class="rounded border border-secondary-subtle" style="height: 280px;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Correction Modal -->
    <div class="modal fade" id="correctionActionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary bg-opacity-10 border-bottom py-3">
                    <h5 class="modal-title fw-bold text-dark fs-6" id="correctionModalTitle">Tindakan Koreksi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="correctionActionForm" method="POST" action="">
                    @csrf
                    <div class="modal-body p-4">
                        <p class="text-dark mb-3 small" id="correctionModalText">Apakah Anda yakin ingin memproses pengajuan ini?</p>
                        <div class="form-group mb-0">
                            <label class="form-label text-muted mb-1 small fw-bold text-uppercase">Catatan Admin (Opsional)</label>
                            <textarea name="admin_notes" class="form-control form-control-sm" rows="3" placeholder="Masukkan alasan penyetujuan atau penolakan..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top p-3 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm px-4 fw-semibold" id="correctionSubmitBtn">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Propose Correction Modal -->
    <div class="modal fade" id="proposeCorrectionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary bg-opacity-10 border-bottom py-3">
                    <h5 class="modal-title fw-bold text-dark fs-6"><i class="fas fa-edit text-primary me-2"></i>Ajukan Koreksi Presensi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="proposeCorrectionForm" method="POST" action="{{ route('hr.attendance.corrections.store') }}">
                    @csrf
                    <input type="hidden" name="employee_id" id="propose_employee_id">
                    <input type="hidden" name="date" value="{{ $date }}">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label text-muted mb-1 small fw-bold text-uppercase">Nama Karyawan</label>
                            <input type="text" id="propose_employee_name" class="form-control form-control-sm bg-light text-muted" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted mb-1 small fw-bold text-uppercase">Tanggal Presensi</label>
                            <input type="text" class="form-control form-control-sm bg-light text-muted" value="{{ date('d-m-Y', strtotime($date)) }}" readonly>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label text-muted mb-1 small fw-bold text-uppercase">Jam Masuk Baru</label>
                                <input type="time" name="clock_in" id="propose_clock_in" class="form-control form-control-sm" placeholder="--:--">
                            </div>
                            <div class="col-6">
                                <label class="form-label text-muted mb-1 small fw-bold text-uppercase">Jam Pulang Baru</label>
                                <input type="time" name="clock_out" id="propose_clock_out" class="form-control form-control-sm" placeholder="--:--">
                            </div>
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label text-muted mb-1 small fw-bold text-uppercase">Alasan Koreksi</label>
                            <textarea name="reason" class="form-control form-control-sm" rows="3" required placeholder="Masukkan alasan pengajuan koreksi (misal: lupa scan masuk, salah input, dll)..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top p-3 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold">Kirim Pengajuan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        <script>
            let proofMap = null;
            let proofMarker = null;

            function openProposeCorrectionModal(employeeId, employeeName, currentClockIn, currentClockOut) {
                document.getElementById('propose_employee_id').value = employeeId;
                document.getElementById('propose_employee_name').value = employeeName;
                document.getElementById('propose_clock_in').value = currentClockIn;
                document.getElementById('propose_clock_out').value = currentClockOut;
                
                // Clear reason textarea
                const form = document.getElementById('proposeCorrectionForm');
                form.querySelector('textarea[name="reason"]').value = '';

                const modal = new bootstrap.Modal(document.getElementById('proposeCorrectionModal'));
                modal.show();
            }

            function openCorrectionActionModal(action, url, employeeName, dateStr) {
                const form = document.getElementById('correctionActionForm');
                const title = document.getElementById('correctionModalTitle');
                const text = document.getElementById('correctionModalText');
                const submitBtn = document.getElementById('correctionSubmitBtn');

                form.action = url;

                // Reset textarea
                form.querySelector('textarea[name="admin_notes"]').value = '';

                if (action === 'approve') {
                    title.innerHTML = '<i class="fas fa-check-circle text-success me-2"></i>Setujui Koreksi Kehadiran';
                    text.innerHTML = `Apakah Anda yakin ingin <strong>menyetujui</strong> pengajuan koreksi presensi dari <strong>${employeeName}</strong> untuk tanggal <strong>${dateStr}</strong>? Kehadiran karyawan akan dibuat/diperbarui secara otomatis.`;
                    submitBtn.className = 'btn btn-success btn-sm px-4 fw-semibold';
                    submitBtn.innerHTML = '<i class="fas fa-check me-1"></i> Setujui';
                } else {
                    title.innerHTML = '<i class="fas fa-times-circle text-danger me-2"></i>Tolak Koreksi Kehadiran';
                    text.innerHTML = `Apakah Anda yakin ingin <strong>menolak</strong> pengajuan koreksi presensi dari <strong>${employeeName}</strong> untuk tanggal <strong>${dateStr}</strong>?`;
                    submitBtn.className = 'btn btn-danger btn-sm px-4 fw-semibold';
                    submitBtn.innerHTML = '<i class="fas fa-times me-1"></i> Tolak';
                }

                const myModal = new bootstrap.Modal(document.getElementById('correctionActionModal'));
                myModal.show();
            }

            function showProofModal(photoUrl, lat, lng, title) {
                // Set Title
                document.getElementById('proofModalTitle').textContent = title;

                // Handle Image
                const imgEl = document.getElementById('proofModalImage');
                if (photoUrl) {
                    imgEl.src = photoUrl;
                    imgEl.parentElement.style.display = 'block';
                } else {
                    imgEl.parentElement.style.display = 'none';
                }

                // Show Modal
                const myModal = new bootstrap.Modal(document.getElementById('attendanceProofModal'));
                myModal.show();

                // Initialize/Update Map on Modal Shown
                document.getElementById('attendanceProofModal').addEventListener('shown.bs.modal', function onModalShown() {
                    document.getElementById('attendanceProofModal').removeEventListener('shown.bs.modal', onModalShown);

                    if (lat && lng) {
                        document.getElementById('proofModalMap').style.display = 'block';

                        if (proofMap) {
                            proofMap.remove();
                        }

                        proofMap = L.map('proofModalMap', {
                            zoomControl: true,
                            attributionControl: false
                        }).setView([lat, lng], 16);

                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            maxZoom: 19
                        }).addTo(proofMap);

                        proofMarker = L.marker([lat, lng]).addTo(proofMap)
                            .bindPopup('<b>Lokasi Presensi</b>')
                            .openPopup();

                        setTimeout(() => {
                            proofMap.invalidateSize();
                        }, 100);
                    } else {
                        document.getElementById('proofModalMap').style.display = 'none';
                    }
                });
            }

            function showMapModal(lat, lng, title) {
                showProofModal(null, lat, lng, title);
            }

            document.addEventListener('DOMContentLoaded', function() {
                // Inline edits listeners removed since fields are display-only
            });
        </script>
    @endpush
@endsection
