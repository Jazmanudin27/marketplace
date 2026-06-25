<div id="tab-izin" class="tab-pane">
    <!-- Form Pengajuan Izin/Sakit/Cuti -->
    <div class="card border shadow-sm mb-3">
        <div class="card-header bg-light py-2.5 px-3 border-bottom">
            <h6 class="m-0 fw-bold text-primary">
                <i class="fas fa-file-signature me-2"></i> Form Pengajuan Izin / Sakit / Cuti
            </h6>
        </div>
        <div class="card-body p-3">
            <form action="{{ route('employee.leaves.store') }}" method="POST" onsubmit="disableSubmitButton(this)">
                @csrf

                <div class="mb-3">
                    <label for="type" class="form-label text-dark small fw-semibold">Jenis Pengajuan</label>
                    <select name="type" id="type" class="form-select form-select-sm" required>
                        <option value="permission">Izin</option>
                        <option value="sick">Sakit</option>
                        <option value="leave">Cuti</option>
                    </select>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label for="start_date" class="form-label text-dark small fw-semibold">Tanggal Mulai</label>
                        <input type="date" name="start_date" id="start_date" class="form-control form-control-sm"
                            value="{{ today()->toDateString() }}" required>
                    </div>
                    <div class="col-6">
                        <label for="end_date" class="form-label text-dark small fw-semibold">Tanggal Selesai</label>
                        <input type="date" name="end_date" id="end_date" class="form-control form-control-sm"
                            value="{{ today()->toDateString() }}" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="notes" class="form-label text-dark small fw-semibold">Keterangan / Alasan</label>
                    <textarea name="notes" id="notes" class="form-control form-control-sm" rows="2"
                        placeholder="Contoh: Mengikuti acara keluarga, sakit demam, dll" required></textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-sm w-100 py-2">
                    Kirim Pengajuan
                </button>
            </form>
        </div>
    </div>

    <!-- Riwayat Pengajuan Izin/Sakit/Cuti -->
    <div class="card border shadow-sm">
        <div class="card-header bg-light py-2.5 px-3 border-bottom">
            <h6 class="m-0 fw-bold text-primary">
                <i class="fas fa-list me-2"></i> Riwayat Pengajuan
            </h6>
        </div>
        <div class="card-body p-3">
            @if ($leaveRequests->isEmpty())
                <div class="text-center text-muted py-4 small">
                    Belum ada riwayat pengajuan izin/sakit/cuti.
                </div>
            @else
                <div class="d-flex flex-column gap-3">
                    @foreach ($leaveRequests as $lr)
                        @php
                            $start = \Carbon\Carbon::parse($lr->start_date);
                            $end = \Carbon\Carbon::parse($lr->end_date);
                            $diff = $start->diffInDays($end) + 1;

                            $typeLabels = [
                                'sick' => 'Sakit',
                                'permission' => 'Izin',
                                'leave' => 'Cuti',
                            ];

                            $statusLabels = [
                                'pending' => 'Menunggu',
                                'approved' => 'Disetujui',
                                'rejected' => 'Ditolak',
                            ];

                            $statusBadgeClasses = [
                                'pending' => 'bg-warning text-dark',
                                'approved' => 'bg-success bg-opacity-10 text-success border border-success border-opacity-25',
                                'rejected' => 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25',
                            ];

                            $typeBadgeColors = [
                                'sick' => 'bg-danger text-white',
                                'permission' => 'bg-primary text-white',
                                'leave' => 'bg-warning text-dark',
                            ];
                        @endphp
                        <div class="d-flex justify-content-between align-items-center p-3 border rounded bg-light">
                            <div class="d-flex align-items-center gap-3">
                                <div class="d-flex flex-column align-items-center justify-content-center border rounded-3 p-1 text-center bg-white" style="width: 46px; height: 46px;">
                                    <span class="fw-bold text-dark fs-6" style="line-height: 1.1;">{{ $diff }}</span>
                                    <span class="text-muted" style="font-size: 8px; text-transform: uppercase;">Hari</span>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark small">
                                        <span class="badge {{ $typeBadgeColors[$lr->type] ?? 'bg-secondary' }} me-1 small">{{ $typeLabels[$lr->type] ?? ucfirst($lr->type) }}</span>
                                    </div>
                                    <div class="text-muted small mt-1">
                                        @if ($diff === 1)
                                            {{ $start->format('d M Y') }}
                                        @else
                                            {{ $start->format('d M') }} s/d {{ $end->format('d M Y') }}
                                        @endif

                                        @if ($lr->notes)
                                            <span class="d-block fst-italic text-muted mt-1" style="font-size: 0.78rem;">
                                                "{{ $lr->notes }}"
                                            </span>
                                        @endif

                                        @if ($lr->status === 'approved' && $lr->is_deducted)
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 mt-1 small">
                                                Memotong Gaji
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <span class="badge {{ $statusBadgeClasses[$lr->status] ?? 'bg-secondary' }} small">
                                {{ $statusLabels[$lr->status] ?? ucfirst($lr->status) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
