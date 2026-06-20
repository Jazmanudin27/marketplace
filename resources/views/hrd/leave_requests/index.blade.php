@extends('layouts.app')
@section('title', 'Izin & Cuti Karyawan')
@section('page-title', 'Pengajuan Izin & Cuti')


@section('content')
    <!-- Row Statistik (Atas) -->
    <div class="row mb-4">
        <!-- Pending Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card-custom h-100" style="border-left: 4px solid var(--warning);">
                <div>
                    <span class="text-white-50 small fw-semibold d-block mb-1">Pending Persetujuan</span>
                    <h3 class="fw-bold mb-0 text-white">{{ $leaveRequests->where('status', 'pending')->count() }}</h3>
                </div>
                <div class="stat-icon-wrapper" style="background: rgba(245, 158, 11, 0.1); color: var(--warning);">
                    <i class="fas fa-hourglass-half"></i>
                </div>
            </div>
        </div>

        <!-- Approved Sick Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card-custom h-100" style="border-left: 4px solid var(--danger);">
                <div>
                    <span class="text-white-50 small fw-semibold d-block mb-1">Sakit Disetujui</span>
                    <h3 class="fw-bold mb-0 text-white">
                        {{ $leaveRequests->where('status', 'approved')->where('type', 'sick')->count() }}</h3>
                </div>
                <div class="stat-icon-wrapper" style="background: rgba(239, 68, 68, 0.1); color: var(--danger);">
                    <i class="fas fa-heartbeat"></i>
                </div>
            </div>
        </div>

        <!-- Approved Permission Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card-custom h-100" style="border-left: 4px solid var(--purple);">
                <div>
                    <span class="text-white-50 small fw-semibold d-block mb-1">Izin Disetujui</span>
                    <h3 class="fw-bold mb-0 text-white">
                        {{ $leaveRequests->where('status', 'approved')->where('type', 'permission')->count() }}</h3>
                </div>
                <div class="stat-icon-wrapper" style="background: rgba(139, 92, 246, 0.1); color: var(--purple);">
                    <i class="fas fa-envelope-open"></i>
                </div>
            </div>
        </div>

        <!-- Approved Leave Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card-custom h-100" style="border-left: 4px solid var(--info);">
                <div>
                    <span class="text-white-50 small fw-semibold d-block mb-1">Cuti Disetujui</span>
                    <h3 class="fw-bold mb-0 text-white">
                        {{ $leaveRequests->where('status', 'approved')->where('type', 'leave')->count() }}</h3>
                </div>
                <div class="stat-icon-wrapper" style="background: rgba(6, 182, 212, 0.1); color: var(--info);">
                    <i class="fas fa-plane-departure"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Form Pengajuan Izin/Cuti (Kiri) -->
        <div class="col-lg-4 mb-4">
            <div class="card h-100" style="background:var(--bg-card); border-color:var(--border); border-radius: 14px;">
                <div class="card-header border-bottom d-flex align-items-center py-3"
                    style="border-color: var(--border) !important;">
                    <div class="brand-icon bg-primary-subtle text-primary me-3 p-2 rounded d-flex align-items-center justify-content-center"
                        style="width: 36px; height: 36px; background: rgba(108, 99, 255, 0.1) !important;">
                        <i class="fas fa-file-medical text-primary"></i>
                    </div>
                    <h5 class="mb-0 fw-bold text-white" id="formTitle">Buat Pengajuan</h5>
                </div>
                <div class="card-body p-4">
                    <form id="leaveForm" method="POST" action="{{ route('hr.leaves.store') }}">
                        @csrf
                        <input type="hidden" name="_method" id="formMethod" value="POST">

                        <div class="mb-3">
                            <label class="form-label-custom"><i class="fas fa-user-tie text-primary"></i> Karyawan</label>
                            <select name="employee_id" id="employee_id" class="form-select form-control-sm" required>
                                <option value="">Pilih Karyawan...</option>
                                @foreach ($employees as $emp)
                                    <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->position ?? 'Staf' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label-custom"><i class="fas fa-tags text-primary"></i> Jenis
                                Pengajuan</label>
                            <select name="type" id="type" class="form-select form-control-sm" required>
                                <option value="sick">Sakit (Sick Leave)</option>
                                <option value="permission">Izin (Permit)</option>
                                <option value="leave">Cuti (Paid Leave)</option>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label-custom"><i class="fas fa-calendar-alt text-primary"></i> Mulai
                                    Tanggal</label>
                                <input type="date" name="start_date" id="start_date" class="form-control form-control-sm"
                                    required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label-custom"><i class="fas fa-calendar-check text-primary"></i> Selesai
                                    Tanggal</label>
                                <input type="date" name="end_date" id="end_date" class="form-control form-control-sm"
                                    required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="form-check form-switch p-0 m-0 d-flex align-items-center justify-content-between">
                                <label class="form-label-custom mb-0"><i class="fas fa-hand-holding-usd text-primary"></i>
                                    Potong Gaji?</label>
                                <input class="form-check-input" type="checkbox" name="is_deducted" id="is_deducted"
                                    value="1"
                                    style="width: 2.8em; height: 1.5em; cursor: pointer; accent-color: var(--primary);">
                            </div>
                            <small class="text-muted d-block mt-2" style="font-size: 0.75rem; line-height: 1.4;">
                                <i class="fas fa-info-circle text-primary me-1"></i> Centang jika hari izin/cuti ini
                                memotong gaji pokok karyawan secara pro-rata.
                            </small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label-custom"><i class="fas fa-pen-fancy text-primary"></i> Alasan /
                                Keterangan</label>
                            <textarea name="notes" id="notes" class="form-control form-control-sm" rows="3"
                                placeholder="Tulis alasan pengajuan di sini..."></textarea>
                        </div>

                        <div class="d-flex gap-2 pt-2">
                            <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold"
                                style="border-radius: 8px;">
                                <i class="fas fa-save me-1"></i> Ajukan
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm px-3" id="btnCancel"
                                style="display:none; border-radius: 8px;" onclick="resetForm()">Batal</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Tabel Daftar Pengajuan (Kanan) -->
        <div class="col-lg-8 mb-4">
            <div class="card h-100" style="background:var(--bg-card); border-color:var(--border); border-radius: 14px;">
                <div class="card-header border-bottom d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between py-3 gap-3"
                    style="border-color: var(--border) !important;">
                    <div class="d-flex align-items-center">
                        <div class="brand-icon bg-success-subtle text-success me-3 p-2 rounded d-flex align-items-center justify-content-center"
                            style="width: 36px; height: 36px; background: rgba(16, 185, 129, 0.1) !important;">
                            <i class="fas fa-clipboard-list text-success"></i>
                        </div>
                        <h5 class="mb-0 fw-bold text-white">Daftar Pengajuan & Persetujuan</h5>
                    </div>
                    <!-- Navigation Tabs -->
                    <ul class="nav nav-pills nav-pills-custom" id="leaveTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="pending-tab" data-bs-toggle="tab"
                                data-bs-target="#pending" type="button" role="tab" aria-controls="pending"
                                aria-selected="true">
                                <i class="fas fa-clock me-1"></i> Pending
                                ({{ $leaveRequests->where('status', 'pending')->count() }})
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history"
                                type="button" role="tab" aria-controls="history" aria-selected="false">
                                <i class="fas fa-history me-1"></i> Riwayat
                                ({{ $leaveRequests->where('status', '!=', 'pending')->count() }})
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body p-4">
                    <!-- Search & Filter Bar -->
                    <div class="search-filter-bar">
                        <div class="search-input-group">
                            <i class="fas fa-search"></i>
                            <input type="text" id="searchInput" class="form-control form-control-sm"
                                placeholder="Cari nama karyawan..." onkeyup="filterLeaves()">
                        </div>
                        <div style="min-width: 160px;">
                            <select id="typeFilter" class="form-select form-control-sm" onchange="filterLeaves()">
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
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-dark" style="background: var(--bg-card2);">
                                        <tr>
                                            <th style="width: 25%; padding: 1rem 1.2rem;">Karyawan</th>
                                            <th style="width: 20%; padding: 1rem 1.2rem;">Jenis & Tanggal</th>
                                            <th style="width: 15%; padding: 1rem 1.2rem; text-align: center;">Potong Gaji?
                                            </th>
                                            <th style="width: 20%; padding: 1rem 1.2rem;">Alasan</th>
                                            <th style="width: 20%; padding: 1rem 1.2rem; text-align: center;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($leaveRequests->where('status', 'pending') as $leave)
                                            <tr class="leave-row" data-employee-name="{{ $leave->employee->name }}"
                                                data-leave-type="{{ $leave->type }}"
                                                style="border-bottom-color: var(--border);">
                                                <td style="padding: 1rem 1.2rem;">
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
                                                            $gradients = [
                                                                'linear-gradient(135deg, #6C63FF, #8B5CF6)',
                                                                'linear-gradient(135deg, #10B981, #059669)',
                                                                'linear-gradient(135deg, #F59E0B, #D97706)',
                                                                'linear-gradient(135deg, #EF4444, #DC2626)',
                                                                'linear-gradient(135deg, #06B6D4, #0891B2)',
                                                                'linear-gradient(135deg, #EC4899, #BE185D)',
                                                            ];
                                                            $grad =
                                                                $gradients[$leave->employee->id % count($gradients)];
                                                        @endphp
                                                        <div class="avatar-circle me-3"
                                                            style="background: {{ $grad }};">
                                                            {{ $initials }}
                                                        </div>
                                                        <div>
                                                            <strong class="text-white d-block"
                                                                style="font-size: 0.95rem;">{{ $leave->employee->name }}</strong>
                                                            <span
                                                                class="text-muted small">{{ $leave->employee->position ?? 'Staf' }}</span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td style="padding: 1rem 1.2rem;">
                                                    @if ($leave->type === 'sick')
                                                        <span class="badge bg-danger-subtle text-danger mb-1"
                                                            style="font-size: 0.75rem;"><i
                                                                class="fas fa-file-medical me-1"></i> Sakit</span>
                                                    @elseif($leave->type === 'permission')
                                                        <span class="badge bg-warning-subtle text-warning mb-1"
                                                            style="font-size: 0.75rem;"><i
                                                                class="fas fa-envelope-open me-1"></i> Izin</span>
                                                    @else
                                                        <span class="badge bg-info-subtle text-info mb-1"
                                                            style="font-size: 0.75rem;"><i
                                                                class="fas fa-plane-departure me-1"></i> Cuti</span>
                                                    @endif
                                                    <small class="text-white-50 d-block mt-1"
                                                        style="font-size: 0.75rem; font-weight: 500;">
                                                        {{ $leave->start_date->format('d/m/Y') }} s/d
                                                        {{ $leave->end_date->format('d/m/Y') }}
                                                    </small>
                                                    <small class="text-muted d-block" style="font-size: 0.7rem;">
                                                        ({{ $leave->start_date->diffInDays($leave->end_date) + 1 }} Hari)
                                                    </small>
                                                </td>
                                                <td style="padding: 1rem 1.2rem; text-align: center;">
                                                    @if ($leave->is_deducted)
                                                        <span class="badge bg-danger-subtle text-danger"
                                                            style="font-size: 0.75rem;"><i
                                                                class="fas fa-check-circle me-1"></i> Ya (Potong)</span>
                                                    @else
                                                        <span class="badge bg-success-subtle text-success"
                                                            style="font-size: 0.75rem;"><i
                                                                class="fas fa-times-circle me-1"></i> Tidak</span>
                                                    @endif
                                                </td>
                                                <td style="padding: 1rem 1.2rem;">
                                                    <span class="text-white-50 small d-block"
                                                        style="max-height: 50px; overflow-y: auto;">
                                                        {{ $leave->notes ?: '-' }}
                                                    </span>
                                                </td>
                                                <td style="padding: 1rem 1.2rem; text-align: center;">
                                                    <div class="d-inline-flex gap-1 align-items-center">
                                                        <form action="{{ route('hr.leaves.approve', $leave->id) }}"
                                                            method="POST" style="display:inline;">
                                                            @csrf
                                                            <button type="submit"
                                                                class="btn btn-action-custom btn-action-approve"
                                                                title="Setujui">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        </form>
                                                        <form action="{{ route('hr.leaves.reject', $leave->id) }}"
                                                            method="POST" style="display:inline;">
                                                            @csrf
                                                            <button type="submit"
                                                                class="btn btn-action-custom btn-action-reject"
                                                                title="Tolak">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </form>
                                                        <button class="btn btn-action-custom btn-action-edit"
                                                            title="Edit"
                                                            onclick="editLeave({{ $leave->id }}, {{ $leave->employee_id }}, '{{ $leave->type }}', '{{ $leave->start_date->format('Y-m-d') }}', '{{ $leave->end_date->format('Y-m-d') }}', {{ $leave->is_deducted ? 1 : 0 }}, '{{ addslashes($leave->notes) }}')">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <form action="{{ route('hr.leaves.destroy', $leave->id) }}"
                                                            method="POST" style="display:inline;"
                                                            onsubmit="return confirm('Hapus pengajuan ini?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                class="btn btn-action-custom btn-action-delete"
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
                                                    <div class="fs-4 mb-2"><i class="far fa-clipboard"></i></div>
                                                    Tidak ada pengajuan izin/cuti yang pending.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- HISTORY TAB -->
                        <div class="tab-pane fade" id="history" role="tabpanel" aria-labelledby="history-tab">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-dark" style="background: var(--bg-card2);">
                                        <tr>
                                            <th style="width: 25%; padding: 1rem 1.2rem;">Karyawan</th>
                                            <th style="width: 20%; padding: 1rem 1.2rem;">Jenis & Tanggal</th>
                                            <th style="width: 15%; padding: 1rem 1.2rem; text-align: center;">Potong Gaji?
                                            </th>
                                            <th style="width: 15%; padding: 1rem 1.2rem; text-align: center;">Status</th>
                                            <th style="width: 15%; padding: 1rem 1.2rem;">Alasan / Penyetuju</th>
                                            <th style="width: 10%; padding: 1rem 1.2rem; text-align: center;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($leaveRequests->where('status', '!=', 'pending') as $leave)
                                            <tr class="leave-row" data-employee-name="{{ $leave->employee->name }}"
                                                data-leave-type="{{ $leave->type }}"
                                                style="border-bottom-color: var(--border);">
                                                <td style="padding: 1rem 1.2rem;">
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
                                                            $gradients = [
                                                                'linear-gradient(135deg, #6C63FF, #8B5CF6)',
                                                                'linear-gradient(135deg, #10B981, #059669)',
                                                                'linear-gradient(135deg, #F59E0B, #D97706)',
                                                                'linear-gradient(135deg, #EF4444, #DC2626)',
                                                                'linear-gradient(135deg, #06B6D4, #0891B2)',
                                                                'linear-gradient(135deg, #EC4899, #BE185D)',
                                                            ];
                                                            $grad =
                                                                $gradients[$leave->employee->id % count($gradients)];
                                                        @endphp
                                                        <div class="avatar-circle me-3"
                                                            style="background: {{ $grad }};">
                                                            {{ $initials }}
                                                        </div>
                                                        <div>
                                                            <strong class="text-white d-block"
                                                                style="font-size: 0.95rem;">{{ $leave->employee->name }}</strong>
                                                            <span
                                                                class="text-muted small">{{ $leave->employee->position ?? 'Staf' }}</span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td style="padding: 1rem 1.2rem;">
                                                    @if ($leave->type === 'sick')
                                                        <span class="badge bg-danger-subtle text-danger mb-1"
                                                            style="font-size: 0.75rem;"><i
                                                                class="fas fa-file-medical me-1"></i> Sakit</span>
                                                    @elseif($leave->type === 'permission')
                                                        <span class="badge bg-warning-subtle text-warning mb-1"
                                                            style="font-size: 0.75rem;"><i
                                                                class="fas fa-envelope-open me-1"></i> Izin</span>
                                                    @else
                                                        <span class="badge bg-info-subtle text-info mb-1"
                                                            style="font-size: 0.75rem;"><i
                                                                class="fas fa-plane-departure me-1"></i> Cuti</span>
                                                    @endif
                                                    <small class="text-white-50 d-block mt-1"
                                                        style="font-size: 0.75rem; font-weight: 500;">
                                                        {{ $leave->start_date->format('d/m/Y') }} s/d
                                                        {{ $leave->end_date->format('d/m/Y') }}
                                                    </small>
                                                    <small class="text-muted d-block" style="font-size: 0.7rem;">
                                                        ({{ $leave->start_date->diffInDays($leave->end_date) + 1 }} Hari)
                                                    </small>
                                                </td>
                                                <td style="padding: 1rem 1.2rem; text-align: center;">
                                                    @if ($leave->is_deducted)
                                                        <span class="badge bg-danger-subtle text-danger"
                                                            style="font-size: 0.75rem;"><i
                                                                class="fas fa-check-circle me-1"></i> Ya (Potong)</span>
                                                    @else
                                                        <span class="badge bg-success-subtle text-success"
                                                            style="font-size: 0.75rem;"><i
                                                                class="fas fa-times-circle me-1"></i> Tidak</span>
                                                    @endif
                                                </td>
                                                <td style="padding: 1rem 1.2rem; text-align: center;">
                                                    @if ($leave->status === 'approved')
                                                        <span class="badge-premium badge-premium-approved"><i
                                                                class="fas fa-check-double me-1"></i> Disetujui</span>
                                                    @else
                                                        <span class="badge-premium badge-premium-rejected"><i
                                                                class="fas fa-times me-1"></i> Ditolak</span>
                                                    @endif
                                                </td>
                                                <td style="padding: 1rem 1.2rem;">
                                                    <span class="text-white-50 small d-block">
                                                        {{ $leave->notes ?: '-' }}
                                                    </span>
                                                    @if ($leave->approvedBy)
                                                        <small class="text-muted d-block mt-1">
                                                            Oleh: {{ $leave->approvedBy->name }}
                                                        </small>
                                                    @endif
                                                </td>
                                                <td style="padding: 1rem 1.2rem; text-align: center;">
                                                    <div class="d-inline-flex gap-1 align-items-center">
                                                        <button class="btn btn-action-custom btn-action-edit"
                                                            title="Edit"
                                                            onclick="editLeave({{ $leave->id }}, {{ $leave->employee_id }}, '{{ $leave->type }}', '{{ $leave->start_date->format('Y-m-d') }}', '{{ $leave->end_date->format('Y-m-d') }}', {{ $leave->is_deducted ? 1 : 0 }}, '{{ addslashes($leave->notes) }}')">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <form action="{{ route('hr.leaves.destroy', $leave->id) }}"
                                                            method="POST" style="display:inline;"
                                                            onsubmit="return confirm('Hapus pengajuan ini?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                class="btn btn-action-custom btn-action-delete"
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
                                                    <div class="fs-4 mb-2"><i class="far fa-folder-open"></i></div>
                                                    Belum ada riwayat pengajuan izin/cuti.
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
