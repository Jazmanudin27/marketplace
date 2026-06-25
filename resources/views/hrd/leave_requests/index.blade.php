@extends('layouts.app')
@section('title', 'Izin & Cuti Karyawan')
@section('page-title', 'Pengajuan Izin & Cuti')

@section('content')
    <!-- Row Statistik (Atas) -->
    <div class="row g-3 mb-4">
        <!-- Pending Card -->
        <div class="col-xl-3 col-md-6">
            <div class="card border shadow-sm h-100 d-flex flex-row align-items-center gap-3 py-2 px-3">
                <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                    style="width: 40px; height: 40px; font-size: 1.1rem;">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="min-width-0">
                    <div class="fw-bold fs-5 text-dark">{{ $leaveRequests->where('status', 'pending')->count() }}</div>
                    <div class="text-muted small">Pending Persetujuan</div>
                </div>
            </div>
        </div>

        <!-- Approved Sick Card -->
        <div class="col-xl-3 col-md-6">
            <div class="card border shadow-sm h-100 d-flex flex-row align-items-center gap-3 py-2 px-3">
                <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                    style="width: 40px; height: 40px; font-size: 1.1rem;">
                    <i class="fas fa-heartbeat"></i>
                </div>
                <div class="min-width-0">
                    <div class="fw-bold fs-5 text-dark">
                        {{ $leaveRequests->where('status', 'approved')->where('type', 'sick')->count() }}</div>
                    <div class="text-muted small">Sakit Disetujui</div>
                </div>
            </div>
        </div>

        <!-- Approved Permission Card -->
        <div class="col-xl-3 col-md-6">
            <div class="card border shadow-sm h-100 d-flex flex-row align-items-center gap-3 py-2 px-3">
                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                    style="width: 40px; height: 40px; font-size: 1.1rem;">
                    <i class="fas fa-envelope-open"></i>
                </div>
                <div class="min-width-0">
                    <div class="fw-bold fs-5 text-dark">
                        {{ $leaveRequests->where('status', 'approved')->where('type', 'permission')->count() }}</div>
                    <div class="text-muted small">Izin Disetujui</div>
                </div>
            </div>
        </div>

        <!-- Approved Leave Card -->
        <div class="col-xl-3 col-md-6">
            <div class="card border shadow-sm h-100 d-flex flex-row align-items-center gap-3 py-2 px-3">
                <div class="bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                    style="width: 40px; height: 40px; font-size: 1.1rem;">
                    <i class="fas fa-plane-departure"></i>
                </div>
                <div class="min-width-0">
                    <div class="fw-bold fs-5 text-dark">
                        {{ $leaveRequests->where('status', 'approved')->where('type', 'leave')->count() }}</div>
                    <div class="text-muted small">Cuti Disetujui</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Form Pengajuan Izin/Cuti (Kiri) -->
        <div class="col-lg-4 mb-4">
            <div class="card border shadow-sm overflow-hidden">
                <div class="card-header bg-primary bg-opacity-10 d-flex align-items-center gap-3 p-3 border-bottom">
                    <div class="bg-primary text-white rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 fs-5 p-2"
                        style="width: 36px; height: 36px;">
                        <i class="fas fa-file-medical text-white" style="font-size: 1rem;"></i>
                    </div>
                    <h6 class="mb-0 fw-bold text-dark" id="formTitle">Buat Pengajuan</h6>
                </div>
                <div class="p-3">
                    <form id="leaveForm" method="POST" action="{{ route('hr.leaves.store') }}">
                        @csrf
                        <input type="hidden" name="_method" id="formMethod" value="POST">

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-dark"><i class="fas fa-user-tie text-primary me-1"></i>
                                Karyawan</label>
                            <select name="employee_id" id="employee_id" class="form-select form-select-sm" required>
                                <option value="">Pilih Karyawan...</option>
                                @foreach ($employees as $emp)
                                    <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->position ?? 'Staf' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-dark"><i class="fas fa-tags text-primary me-1"></i> Jenis
                                Pengajuan</label>
                            <select name="type" id="type" class="form-select form-select-sm" required>
                                <option value="sick">Sakit (Sick Leave)</option>
                                <option value="permission">Izin (Permit)</option>
                                <option value="leave">Cuti (Paid Leave)</option>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small text-dark"><i
                                        class="fas fa-calendar-alt text-primary me-1"></i> Mulai Tanggal</label>
                                <input type="date" name="start_date" id="start_date" class="form-control form-control-sm"
                                    required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small text-dark"><i
                                        class="fas fa-calendar-check text-primary me-1"></i> Selesai Tanggal</label>
                                <input type="date" name="end_date" id="end_date" class="form-control form-control-sm"
                                    required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch p-0 m-0 d-flex align-items-center justify-content-between">
                                <label class="form-label fw-bold small text-dark mb-0"><i
                                        class="fas fa-hand-holding-usd text-primary me-1"></i>
                                    Potong Gaji?</label>
                                <input class="form-check-input" type="checkbox" name="is_deducted" id="is_deducted"
                                    value="1">
                            </div>
                            <small class="text-muted d-block mt-2" style="font-size: 0.72rem; line-height: 1.4;">
                                <i class="fas fa-info-circle text-primary me-1"></i> Centang jika hari izin/cuti ini
                                memotong gaji pokok karyawan secara pro-rata.
                            </small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-dark"><i class="fas fa-pen-fancy text-primary me-1"></i>
                                Alasan / Keterangan</label>
                            <textarea name="notes" id="notes" class="form-control form-control-sm" rows="2"
                                placeholder="Tulis alasan pengajuan di sini..."></textarea>
                        </div>

                        <div class="d-flex gap-2 pt-2">
                            <button type="submit" class="btn btn-primary btn-sm px-3 rounded-3">
                                <i class="fas fa-save me-1"></i> Ajukan
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm px-3" id="btnCancel"
                                style="display:none;" onclick="resetForm()">Batal</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Tabel Daftar Pengajuan (Kanan) -->
        <div class="col-lg-8 mb-4">
            <div class="card border shadow-sm overflow-hidden">
                <div class="card-header bg-info bg-opacity-10 d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between p-3 border-bottom gap-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-info text-white rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 fs-5 p-2 me-3"
                            style="width: 36px; height: 36px;">
                            <i class="fas fa-clipboard-list text-white" style="font-size: 1rem;"></i>
                        </div>
                        <h6 class="mb-0 fw-bold text-dark">Daftar Pengajuan</h6>
                    </div>
                    <!-- Navigation Tabs using standard Bootstrap 5 light styling -->
                    <ul class="nav nav-pills gap-1 p-1 bg-light border rounded-pill" id="leaveTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active rounded-pill d-flex align-items-center gap-2 py-1 px-3" id="pending-tab"
                                data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab"
                                aria-controls="pending" aria-selected="true"
                                style="font-size: 0.75rem;">
                                <i class="fas fa-clock"></i> Pending
                                <span class="badge bg-secondary ms-1">{{ $leaveRequests->where('status', 'pending')->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link rounded-pill d-flex align-items-center gap-2 py-1 px-3" id="history-tab"
                                data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab"
                                aria-controls="history" aria-selected="false"
                                style="font-size: 0.75rem;">
                                <i class="fas fa-history"></i> Riwayat
                                <span class="badge bg-secondary ms-1">{{ $leaveRequests->where('status', '!=', 'pending')->count() }}</span>
                            </button>
                        </li>
                    </ul>
                </div>
                
                <div class="card-body p-3">
                    <!-- Search & Filter Bar -->
                    <div class="row g-2 mb-3">
                        <div class="col-md-8">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" id="searchInput" class="form-control"
                                    placeholder="Cari nama karyawan..." onkeyup="filterLeaves()">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <select id="typeFilter" class="form-select form-select-sm" onchange="filterLeaves()">
                                <option value="all">Semua Tipe</option>
                                <option value="sick">Sakit</option>
                                <option value="permission">Izin</option>
                                <option value="leave">Cuti</option>
                            </select>
                        </div>
                    </div>

                    <div class="tab-content" id="leaveTabsContent">
                        <!-- PENDING TAB -->
                        <div class="tab-pane fade show active" id="pending" role="tabpanel"
                            aria-labelledby="pending-tab">
                            <div class="table-responsive rounded border">
                                <table class="table table-sm table-striped table-bordered align-middle mb-0">
                                    <thead>
                                        <tr class="small">
                                            <th style="width: 25%;">Karyawan</th>
                                            <th style="width: 20%;">Jenis & Tanggal</th>
                                            <th style="width: 15%;" class="text-center">Potong Gaji?</th>
                                            <th style="width: 20%;">Alasan</th>
                                            <th style="width: 20%;" class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($leaveRequests->where('status', 'pending') as $leave)
                                            <tr class="leave-row" data-employee-name="{{ $leave->employee->name }}"
                                                data-leave-type="{{ $leave->type }}">
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        @php
                                                            $words = explode(' ', $leave->employee->name);
                                                            $initials = '';
                                                            if (count($words) >= 2) {
                                                                $initials = strtoupper(
                                                                    substr($words[0], 0, 1) . substr($words[1], 0, 1),
                                                                );
                                                            } else {
                                                                $initials = strtoupper(
                                                                    substr($leave->employee->name, 0, 2),
                                                                );
                                                            }
                                                            $colors = [
                                                                '#6C63FF',
                                                                '#10B981',
                                                                '#F59E0B',
                                                                '#EF4444',
                                                                '#06B6D4',
                                                                '#EC4899',
                                                            ];
                                                            $bg = $colors[$leave->employee->id % count($colors)];
                                                        @endphp
                                                        <div class="text-white rounded-circle d-flex align-items-center justify-content-center fw-bold me-3"
                                                            style="background: {{ $bg }}; width: 32px; height: 32px; font-size: 0.8rem;">
                                                            {{ $initials }}
                                                        </div>
                                                        <div>
                                                            <strong class="text-dark d-block small">{{ $leave->employee->name }}</strong>
                                                            <span
                                                                class="text-muted small" style="font-size:0.7rem;">{{ $leave->employee->position ?? 'Staf' }}</span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    @if ($leave->type === 'sick')
                                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle mb-1" style="font-size:0.65rem; padding: 0.25em 0.5em;"><i
                                                                class="fas fa-file-medical me-1"></i> Sakit</span>
                                                    @elseif($leave->type === 'permission')
                                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle mb-1" style="font-size:0.65rem; padding: 0.25em 0.5em;"><i
                                                                class="fas fa-envelope-open me-1"></i> Izin</span>
                                                    @else
                                                        <span class="badge bg-info-subtle text-info border border-info-subtle mb-1" style="font-size:0.65rem; padding: 0.25em 0.5em;"><i
                                                                class="fas fa-plane-departure me-1"></i> Cuti</span>
                                                    @endif
                                                    <small class="text-dark d-block mt-1"
                                                        style="font-size: 0.72rem; font-weight: 500;">
                                                        {{ $leave->start_date->format('d/m/Y') }} s/d
                                                        {{ $leave->end_date->format('d/m/Y') }}
                                                    </small>
                                                    <small class="text-muted d-block" style="font-size: 0.68rem;">
                                                        ({{ $leave->start_date->diffInDays($leave->end_date) + 1 }} Hari)
                                                    </small>
                                                </td>
                                                <td class="text-center">
                                                    @if ($leave->is_deducted)
                                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size:0.65rem; padding: 0.25em 0.5em;"><i
                                                                class="fas fa-check-circle me-1"></i> Ya (Potong)</span>
                                                    @else
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:0.65rem; padding: 0.25em 0.5em;"><i
                                                                class="fas fa-times-circle me-1"></i> Tidak</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="text-dark small d-block"
                                                        style="max-height: 50px; overflow-y: auto;">
                                                        {{ $leave->notes ?: '-' }}
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <div class="d-inline-flex gap-1 align-items-center">
                                                        <form action="{{ route('hr.leaves.approve', $leave->id) }}"
                                                            method="POST" style="display:inline;">
                                                            @csrf
                                                            <button type="submit" class="btn btn-success btn-sm"
                                                                title="Setujui">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        </form>
                                                        <form action="{{ route('hr.leaves.reject', $leave->id) }}"
                                                            method="POST" style="display:inline;">
                                                            @csrf
                                                            <button type="submit" class="btn btn-danger btn-sm"
                                                                title="Tolak">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </form>
                                                        <button class="btn btn-warning btn-sm"
                                                            title="Edit"
                                                            onclick="editLeave({{ $leave->id }}, {{ $leave->employee_id }}, '{{ $leave->type }}', '{{ $leave->start_date->format('Y-m-d') }}', '{{ $leave->end_date->format('Y-m-d') }}', {{ $leave->is_deducted ? 1 : 0 }}, '{{ addslashes($leave->notes) }}')">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <form action="{{ route('hr.leaves.destroy', $leave->id) }}"
                                                            method="POST" style="display:inline;"
                                                            onsubmit="return confirm('Hapus pengajuan ini?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-danger btn-sm"
                                                                title="Hapus">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center py-5 text-muted">
                                                    <i
                                                        class="far fa-clipboard fa-2x mb-3 d-block text-secondary opacity-25"></i>
                                                    <p class="mb-0 small">Tidak ada pengajuan izin/cuti yang pending.</p>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- HISTORY TAB -->
                        <div class="tab-pane fade" id="history" role="tabpanel" aria-labelledby="history-tab">
                            <div class="table-responsive rounded border">
                                <table class="table table-sm table-striped table-bordered align-middle mb-0">
                                    <thead>
                                        <tr class="small">
                                            <th style="width: 25%;">Karyawan</th>
                                            <th style="width: 20%;">Jenis & Tanggal</th>
                                            <th style="width: 15%;" class="text-center">Potong Gaji?</th>
                                            <th style="width: 15%;" class="text-center">Status</th>
                                            <th style="width: 15%;">Alasan / Penyetuju</th>
                                            <th style="width: 10%;" class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($leaveRequests->where('status', '!=', 'pending') as $leave)
                                            <tr class="leave-row" data-employee-name="{{ $leave->employee->name }}"
                                                data-leave-type="{{ $leave->type }}">
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        @php
                                                            $words = explode(' ', $leave->employee->name);
                                                            $initials = '';
                                                            if (count($words) >= 2) {
                                                                $initials = strtoupper(
                                                                    substr($words[0], 0, 1) . substr($words[1], 0, 1),
                                                                );
                                                            } else {
                                                                $initials = strtoupper(
                                                                    substr($leave->employee->name, 0, 2),
                                                                );
                                                            }
                                                            $colors = [
                                                                '#6C63FF',
                                                                '#10B981',
                                                                '#F59E0B',
                                                                '#EF4444',
                                                                '#06B6D4',
                                                                '#EC4899',
                                                            ];
                                                            $bg = $colors[$leave->employee->id % count($colors)];
                                                        @endphp
                                                        <div class="text-white rounded-circle d-flex align-items-center justify-content-center fw-bold me-3"
                                                            style="background: {{ $bg }}; width: 32px; height: 32px; font-size: 0.8rem;">
                                                            {{ $initials }}
                                                        </div>
                                                        <div>
                                                            <strong class="text-dark d-block small">{{ $leave->employee->name }}</strong>
                                                            <span
                                                                class="text-muted small" style="font-size:0.7rem;">{{ $leave->employee->position ?? 'Staf' }}</span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    @if ($leave->type === 'sick')
                                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle mb-1" style="font-size:0.65rem; padding: 0.25em 0.5em;"><i
                                                                class="fas fa-file-medical me-1"></i> Sakit</span>
                                                    @elseif($leave->type === 'permission')
                                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle mb-1" style="font-size:0.65rem; padding: 0.25em 0.5em;"><i
                                                                class="fas fa-envelope-open me-1"></i> Izin</span>
                                                    @else
                                                        <span class="badge bg-info-subtle text-info border border-info-subtle mb-1" style="font-size:0.65rem; padding: 0.25em 0.5em;"><i
                                                                class="fas fa-plane-departure me-1"></i> Cuti</span>
                                                    @endif
                                                    <small class="text-dark d-block mt-1"
                                                        style="font-size: 0.72rem; font-weight: 500;">
                                                        {{ $leave->start_date->format('d/m/Y') }} s/d
                                                        {{ $leave->end_date->format('d/m/Y') }}
                                                    </small>
                                                    <small class="text-muted d-block" style="font-size: 0.68rem;">
                                                        ({{ $leave->start_date->diffInDays($leave->end_date) + 1 }} Hari)
                                                    </small>
                                                </td>
                                                <td class="text-center">
                                                    @if ($leave->is_deducted)
                                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size:0.65rem; padding: 0.25em 0.5em;"><i
                                                                class="fas fa-check-circle me-1"></i> Ya (Potong)</span>
                                                    @else
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:0.65rem; padding: 0.25em 0.5em;"><i
                                                                class="fas fa-times-circle me-1"></i> Tidak</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    @if ($leave->status === 'approved')
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:0.65rem; padding: 0.25em 0.5em;"><i
                                                                class="fas fa-check-double me-1"></i> Disetujui</span>
                                                    @else
                                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size:0.65rem; padding: 0.25em 0.5em;"><i class="fas fa-times me-1"></i>
                                                            Ditolak</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="text-dark small d-block">
                                                        {{ $leave->notes ?: '-' }}
                                                    </span>
                                                    @if ($leave->approvedBy)
                                                        <small class="text-muted d-block mt-1" style="font-size:0.7rem;">
                                                            Oleh: {{ $leave->approvedBy->name }}
                                                        </small>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    <div class="d-inline-flex gap-1 align-items-center">
                                                        <button class="btn btn-warning btn-sm"
                                                            title="Edit"
                                                            onclick="editLeave({{ $leave->id }}, {{ $leave->employee_id }}, '{{ $leave->type }}', '{{ $leave->start_date->format('Y-m-d') }}', '{{ $leave->end_date->format('Y-m-d') }}', {{ $leave->is_deducted ? 1 : 0 }}, '{{ addslashes($leave->notes) }}')">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <form action="{{ route('hr.leaves.destroy', $leave->id) }}"
                                                            method="POST" style="display:inline;"
                                                            onsubmit="return confirm('Hapus pengajuan ini?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-danger btn-sm"
                                                                title="Hapus">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-5 text-muted">
                                                    <i
                                                        class="far fa-folder-open fa-2x mb-3 d-block text-secondary opacity-25"></i>
                                                    <p class="mb-0 small">Belum ada riwayat pengajuan izin/cuti.</p>
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
        </div>
    </div>

    @push('scripts')
        <script>
            function filterLeaves() {
                const query = document.getElementById('searchInput').value.toLowerCase();
                const typeFilter = document.getElementById('typeFilter').value;
                const activeTab = document.querySelector('#leaveTabs button.active').getAttribute('aria-controls');

                // Get all rows in the active tab table
                const rows = document.querySelectorAll(`#${activeTab} tbody tr.leave-row`);

                rows.forEach(row => {
                    const employeeName = row.getAttribute('data-employee-name').toLowerCase();
                    const leaveType = row.getAttribute('data-leave-type');

                    const matchesName = employeeName.includes(query);
                    const matchesType = (typeFilter === 'all' || leaveType === typeFilter);

                    if (matchesName && matchesType) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }

            // Ensure filters reset or run when tab changes
            document.querySelectorAll('#leaveTabs button[data-bs-toggle="tab"]').forEach(tab => {
                tab.addEventListener('shown.bs.tab', function() {
                    filterLeaves();
                });
            });

            function editLeave(id, employee_id, type, start_date, end_date, is_deducted, notes) {
                document.getElementById('formTitle').innerText = 'Edit Pengajuan';
                document.getElementById('formMethod').value = 'PUT';
                document.getElementById('leaveForm').action = '/hr/leaves/' + id;

                document.getElementById('employee_id').value = employee_id;
                document.getElementById('type').value = type;
                document.getElementById('start_date').value = start_date;
                document.getElementById('end_date').value = end_date;
                document.getElementById('is_deducted').checked = (is_deducted == 1);
                document.getElementById('notes').value = notes;

                document.getElementById('btnCancel').style.display = 'inline-block';

                // Scroll to form smoothly
                document.getElementById('formTitle').scrollIntoView({
                    behavior: 'smooth'
                });
            }

            function resetForm() {
                document.getElementById('formTitle').innerText = 'Buat Pengajuan';
                document.getElementById('formMethod').value = 'POST';
                document.getElementById('leaveForm').action = '{{ route('hr.leaves.store') }}';

                document.getElementById('employee_id').value = '';
                document.getElementById('type').value = 'sick';
                document.getElementById('start_date').value = '';
                document.getElementById('end_date').value = '';
                document.getElementById('is_deducted').checked = false;
                document.getElementById('notes').value = '';

                document.getElementById('btnCancel').style.display = 'none';
            }
        </script>
    @endpush
@endsection
