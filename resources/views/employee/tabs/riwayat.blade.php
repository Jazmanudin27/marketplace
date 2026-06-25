<div id="tab-riwayat" class="tab-pane">
    <!-- Filter Periode Bulan & Koreksi Absen -->
    <div class="card mb-3 border rounded shadow-sm">
        <div class="card-body py-2 px-3">
            <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                <form method="GET" action="{{ route('employee.dashboard') }}" id="riwayatFilterForm"
                    class="d-flex align-items-center gap-2 m-0 w-100 justify-content-between justify-content-md-start">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-calendar-alt text-primary"></i>
                        <span class="fw-bold text-dark small">Periode:</span>
                    </div>
                    <input type="month" name="period" value="{{ $period }}"
                        onchange="document.getElementById('riwayatFilterForm').submit()"
                        class="form-control form-control-sm w-auto fw-bold text-dark">
                </form>
            </div>
        </div>
    </div>

    <!-- Rekap Absen Bulanan -->
    <div class="card mb-3 border rounded shadow-sm">
        <div class="card-body">
            <h6 class="card-title fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                <i class="fas fa-chart-bar text-primary"></i>
                Rekap Kehadiran - {{ formatPeriodStr($period, $monthNamesIndo) }}
            </h6>

            <div class="row g-2">
                <div class="col-4 col-md-2">
                    <div class="p-2 border rounded bg-light text-center h-100 d-flex flex-column justify-content-center">
                        <i class="fas fa-check-circle text-success fs-5 mb-1"></i>
                        <h4 class="fw-bold mb-0 text-dark">{{ $stats['hadir'] }}</h4>
                        <small class="text-muted d-block" style="font-size: 0.7rem;">Hadir</small>
                    </div>
                </div>

                <div class="col-4 col-md-2">
                    <div class="p-2 border rounded bg-light text-center h-100 d-flex flex-column justify-content-center">
                        <i class="fas fa-clock text-warning fs-5 mb-1"></i>
                        <h4 class="fw-bold mb-0 text-dark">{{ $stats['terlambat'] }}</h4>
                        <small class="text-muted d-block" style="font-size: 0.7rem;">Terlambat</small>
                    </div>
                </div>

                <div class="col-4 col-md-2">
                    <div class="p-2 border rounded bg-light text-center h-100 d-flex flex-column justify-content-center">
                        <i class="fas fa-heartbeat text-danger fs-5 mb-1"></i>
                        <h4 class="fw-bold mb-0 text-dark">{{ $stats['sakit'] }}</h4>
                        <small class="text-muted d-block" style="font-size: 0.7rem;">Sakit</small>
                    </div>
                </div>

                <div class="col-4 col-md-2">
                    <div class="p-2 border rounded bg-light text-center h-100 d-flex flex-column justify-content-center">
                        <i class="fas fa-file-alt text-primary fs-5 mb-1"></i>
                        <h4 class="fw-bold mb-0 text-dark">{{ $stats['izin'] }}</h4>
                        <small class="text-muted d-block" style="font-size: 0.7rem;">Izin</small>
                    </div>
                </div>

                <div class="col-4 col-md-2">
                    <div class="p-2 border rounded bg-light text-center h-100 d-flex flex-column justify-content-center">
                        <i class="fas fa-calendar-check text-info fs-5 mb-1"></i>
                        <h4 class="fw-bold mb-0 text-dark">{{ $stats['cuti'] }}</h4>
                        <small class="text-muted d-block" style="font-size: 0.7rem;">Cuti</small>
                    </div>
                </div>

                <div class="col-4 col-md-2">
                    <div class="p-2 border rounded bg-light text-center h-100 d-flex flex-column justify-content-center">
                        <i class="fas fa-times-circle text-secondary fs-5 mb-1"></i>
                        <h4 class="fw-bold mb-0 text-dark">{{ $stats['alpha'] }}</h4>
                        <small class="text-muted d-block" style="font-size: 0.7rem;">Alpha</small>
                    </div>
                </div>
            </div>

            @if ($stats['late_minutes'] > 0)
                <div class="mt-3 p-2 bg-warning bg-opacity-10 border border-warning border-opacity-25 rounded d-flex justify-content-between align-items-center">
                    <span class="text-dark small">Total Keterlambatan Waktu:</span>
                    <strong class="text-warning small">{{ $stats['late_minutes'] }} menit</strong>
                </div>
            @endif
        </div>
    </div>

    <!-- Riwayat Detail Harian -->
    <div class="card mb-3 border rounded shadow-sm">
        <div class="card-body">
            <h6 class="card-title fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                <i class="fas fa-history text-primary"></i>
                Riwayat Presensi - {{ formatPeriodStr($period, $monthNamesIndo) }}
            </h6>

            @php
                $startDate = \Carbon\Carbon::parse($period . '-01')->startOfMonth();
                $endDate = \Carbon\Carbon::parse($period . '-01')->endOfMonth();
                if ($endDate->greaterThan(today())) {
                    $endDate = today();
                }
                $filteredHistory = collect();

                for ($d = $startDate->copy(); $d->lte($endDate); $d->addDay()) {
                    $dateStr = $d->toDateString();

                    $att = $history->first(function ($item) use ($dateStr) {
                        return \Carbon\Carbon::parse($item->date)->toDateString() === $dateStr;
                    });

                    if (!$att) {
                        if ($d->isSunday()) {
                            $att = new \App\Models\Attendance([
                                'date' => $dateStr,
                                'status' => 'sunday',
                                'clock_in' => null,
                                'clock_out' => null,
                                'late_minutes' => 0,
                                'notes' => 'Hari Minggu (libur)',
                            ]);
                        } elseif ($dateStr === today()->toDateString()) {
                            $att = new \App\Models\Attendance([
                                'date' => $dateStr,
                                'status' => 'present',
                                'clock_in' => null,
                                'clock_out' => null,
                                'late_minutes' => 0,
                                'notes' => null,
                            ]);
                        } else {
                            $sched = $employee->getScheduleForDate($dateStr);
                            if ($sched && $sched->is_off) {
                                $att = new \App\Models\Attendance([
                                    'date' => $dateStr,
                                    'status' => 'off',
                                    'clock_in' => null,
                                    'clock_out' => null,
                                    'late_minutes' => 0,
                                    'notes' => 'Jadwal Libur',
                                ]);
                            } else {
                                $att = new \App\Models\Attendance([
                                    'date' => $dateStr,
                                    'status' => 'alpha',
                                    'clock_in' => null,
                                    'clock_out' => null,
                                    'late_minutes' => 0,
                                    'notes' => null,
                                ]);
                            }
                        }
                    }
                    $filteredHistory->push($att);
                }
            @endphp

            @if ($filteredHistory->isEmpty())
                <div class="text-center text-muted py-4 small">
                    Belum ada data presensi terdaftar.
                </div>
            @else
                <div class="list-group list-group-flush">
                    @foreach ($filteredHistory->reverse() as $att)
                        @php
                            $d = \Carbon\Carbon::parse($att->date);
                            $dayShortName = $d->format('D');
                            $dayNameIndo = $dayNamesIndo[$dayShortName] ?? $dayShortName;
                            $statusLabels = [
                                'present' => 'Hadir',
                                'alpha' => 'Alpha',
                                'izin' => 'Izin',
                                'sakit' => 'Sakit',
                                'cuti' => 'Cuti',
                                'sick' => 'Sakit',
                                'permission' => 'Izin',
                                'leave' => 'Cuti',
                                'off' => 'Libur',
                                'sunday' => 'Libur',
                            ];

                            $badgeClass = 'bg-secondary';
                            if ($att->status === 'present') {
                                $badgeClass = $att->clock_in ? 'bg-success' : 'bg-warning';
                            } elseif (in_array($att->status, ['sick', 'sakit'])) {
                                $badgeClass = 'bg-danger';
                            } elseif (in_array($att->status, ['permission', 'izin'])) {
                                $badgeClass = 'bg-primary';
                            } elseif (in_array($att->status, ['leave', 'cuti'])) {
                                $badgeClass = 'bg-info';
                            } elseif (in_array($att->status, ['off', 'sunday'])) {
                                $badgeClass = 'bg-light text-dark border';
                            }
                        @endphp
                        <div class="list-group-item px-0 py-3 d-flex align-items-center justify-content-between gap-2">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-light border rounded text-center d-flex flex-column justify-content-center" style="width: 44px; height: 44px; flex-shrink: 0;">
                                    <span class="fw-bold lh-1 text-dark fs-6">{{ $d->format('d') }}</span>
                                    <small class="text-muted text-uppercase" style="font-size: 0.65rem;">{{ $monthShortIndo[$d->month] ?? '' }}</small>
                                </div>
                                <div>
                                    <div class="fw-semibold text-dark small">{{ $dayNameIndo }}, {{ $d->format('d/m/Y') }}</div>
                                    <div class="text-muted small">
                                        @if ($att->clock_in)
                                            Masuk: {{ date('H:i', strtotime($att->clock_in)) }}
                                            &bull; Pulang:
                                            {{ $att->clock_out ? date('H:i', strtotime($att->clock_out)) : 'Belum Scan' }}
                                        @elseif (in_array($att->status, ['sick', 'permission', 'leave', 'sakit', 'izin', 'cuti']))
                                            Keterangan: {{ $att->notes ?: 'Telah disetujui HRD' }}
                                        @elseif ($att->status === 'sunday')
                                            Hari Minggu (libur)
                                        @elseif ($att->status === 'off')
                                            Jadwal Libur
                                        @elseif ($d->isToday())
                                            Belum Scan Masuk
                                        @else
                                            Absen kosong / Tanpa keterangan
                                        @endif

                                        @if ($att->late_minutes > 0)
                                            <span class="text-warning d-block fw-semibold mt-1">
                                                <i class="fas fa-hourglass-half me-1"></i> Terlambat {{ $att->late_minutes }} mnt
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <span class="badge {{ $badgeClass }} py-1.5 px-2.5 small flex-shrink-0">
                                {{ !$att->clock_in && $att->status === 'present' ? 'Belum Scan' : $statusLabels[$att->status] ?? ucfirst($att->status) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Riwayat Pengajuan Koreksi Section -->
    <div class="d-flex justify-content-between align-items-center mb-2 px-1">
        <span class="fw-bold text-dark small">Riwayat Pengajuan Koreksi Absen</span>
    </div>

    <div class="card mb-3 border rounded shadow-sm">
        <div class="card-body">
            @if ($corrections->isEmpty())
                <div class="text-center text-muted py-4 small">
                    Belum ada pengajuan koreksi absen.
                </div>
            @else
                <div class="list-group list-group-flush">
                    @foreach ($corrections as $corr)
                        @php
                            $dCorr = \Carbon\Carbon::parse($corr->date);
                            $statusLabels = [
                                'pending' => 'Menunggu',
                                'approved' => 'Disetujui',
                                'rejected' => 'Ditolak',
                            ];
                            $badgeClass = 'bg-warning';
                            if ($corr->status === 'approved') {
                                $badgeClass = 'bg-success';
                            } elseif ($corr->status === 'rejected') {
                                $badgeClass = 'bg-danger';
                            }
                        @endphp
                        <div class="list-group-item px-0 py-3 d-flex align-items-center justify-content-between gap-2">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-light border rounded text-center d-flex flex-column justify-content-center" style="width: 44px; height: 44px; flex-shrink: 0;">
                                    <span class="fw-bold lh-1 text-dark fs-6">{{ $dCorr->format('d') }}</span>
                                    <small class="text-muted text-uppercase" style="font-size: 0.65rem;">{{ $monthShortIndo[$dCorr->month] ?? '' }}</small>
                                </div>
                                <div>
                                    <div class="fw-semibold text-dark small">Koreksi Tanggal {{ $dCorr->format('d/m/Y') }}</div>
                                    <div class="text-muted small">
                                        @if ($corr->clock_in)
                                            <div>Masuk Baru: <strong class="text-dark">{{ date('H:i', strtotime($corr->clock_in)) }} WIB</strong></div>
                                        @endif
                                        @if ($corr->clock_out)
                                            <div>Pulang Baru: <strong class="text-dark">{{ date('H:i', strtotime($corr->clock_out)) }} WIB</strong></div>
                                        @endif
                                        <div class="fst-italic text-muted mt-1">"{{ $corr->reason }}"</div>
                                        @if ($corr->admin_notes)
                                            <div class="mt-1 text-warning fw-semibold">HRD: "{{ $corr->admin_notes }}"</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <span class="badge {{ $badgeClass }} py-1.5 px-2.5 small flex-shrink-0">
                                {{ $statusLabels[$corr->status] ?? ucfirst($corr->status) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
