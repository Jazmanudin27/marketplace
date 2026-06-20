<div id="tab-riwayat" class="tab-pane">
    <!-- Filter Periode Bulan & Koreksi Absen -->
    <div class="glass-card" style="margin-bottom: 16px; padding: 12px 16px;">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;">
            <form method="GET" action="{{ route('employee.dashboard') }}" id="riwayatFilterForm"
                style="display: flex; align-items: center; gap: 10px; margin: 0;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
                        style="color: var(--color-primary);">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <span style="font-size: 13px; font-weight: 600; color: white;">Periode:</span>
                </div>
                <input type="month" name="period" value="{{ $period }}"
                    onchange="document.getElementById('riwayatFilterForm').submit()"
                    style="background: rgba(0, 0, 0, 0.25); color: white; border: 1px solid var(--border-color); border-radius: 8px; padding: 5px 10px; font-size: 13px; outline: none; cursor: pointer; font-family: var(--font-display); font-weight: 600;">
            </form>
        </div>
    </div>

    <!-- Rekap Absen Bulanan -->
    <div class="glass-card" style="margin-bottom: 20px;">
        <div class="section-title">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="20" x2="18" y2="10"></line>
                <line x1="12" y1="20" x2="12" y2="4"></line>
                <line x1="6" y1="20" x2="6" y2="14"></line>
            </svg>
            Rekap Kehadiran - {{ formatPeriodStr($period, $monthNamesIndo) }}
        </div>

        <div class="rekap-grid">
            <div class="rekap-card hadir">
                <div class="rekap-icon-box">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
                <div class="rekap-data">
                    <h3>{{ $stats['hadir'] }}</h3>
                    <span>Hari Masuk</span>
                </div>
            </div>

            <div class="rekap-card lambat">
                <div class="rekap-icon-box">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                </div>
                <div class="rekap-data">
                    <h3>{{ $stats['terlambat'] }}</h3>
                    <span>Terlambat</span>
                </div>
            </div>

            <div class="rekap-card sakit">
                <div class="rekap-icon-box">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
                    </svg>
                </div>
                <div class="rekap-data">
                    <h3>{{ $stats['sakit'] }}</h3>
                    <span>Sakit</span>
                </div>
            </div>

            <div class="rekap-card izin">
                <div class="rekap-icon-box">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                    </svg>
                </div>
                <div class="rekap-data">
                    <h3>{{ $stats['izin'] }}</h3>
                    <span>Izin</span>
                </div>
            </div>

            <div class="rekap-card cuti">
                <div class="rekap-icon-box">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                </div>
                <div class="rekap-data">
                    <h3>{{ $stats['cuti'] }}</h3>
                    <span>Cuti</span>
                </div>
            </div>

            <div class="rekap-card alpha">
                <div class="rekap-icon-box">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                    </svg>
                </div>
                <div class="rekap-data">
                    <h3>{{ $stats['alpha'] }}</h3>
                    <span>Mangkir (Alpha)</span>
                </div>
            </div>
        </div>

        @if ($stats['late_minutes'] > 0)
            <div class="rekap-summary-bar">
                <span>Total Keterlambatan Waktu:</span>
                <strong style="color: var(--color-warning);">{{ $stats['late_minutes'] }} menit</strong>
            </div>
        @endif
    </div>

    <!-- Riwayat Detail Harian -->
    <div class="glass-card">
        <div class="section-title">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
            Riwayat Presensi - {{ formatPeriodStr($period, $monthNamesIndo) }}
        </div>
        @php
            // Generate all dates in the selected month period (up to today if it's the current month, else full month)
$startDate = \Carbon\Carbon::parse($period . '-01')->startOfMonth();
$endDate = \Carbon\Carbon::parse($period . '-01')->endOfMonth();
if ($endDate->greaterThan(today())) {
    $endDate = today();
}
$filteredHistory = collect();

for ($d = $startDate->copy(); $d->lte($endDate); $d->addDay()) {
    $dateStr = $d->toDateString();

    // Find matching record in $history
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
            // Check if it's today
                        $att = new \App\Models\Attendance([
                            'date' => $dateStr,
                            'status' => 'present',
                            'clock_in' => null,
                            'clock_out' => null,
                            'late_minutes' => 0,
                            'notes' => null,
                        ]);
                    } else {
                        // Check if day off
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
            <div style="text-align: center; color: var(--text-muted); padding: 30px 0; font-size: 13px;">
                Belum ada data presensi terdaftar.
            </div>
        @else
            <div class="list-wrapper">
                @foreach ($filteredHistory as $att)
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
                    @endphp
                    <div class="item-card">
                        <div class="item-left">
                            <div class="item-date-badge">
                                <div class="item-date-day">{{ $d->format('d') }}</div>
                                <div class="item-date-month">{{ $monthShortIndo[$d->month] ?? '' }}</div>
                            </div>
                            <div>
                                <div class="item-title">{{ $dayNameIndo }}, {{ $d->format('d/m/Y') }}</div>
                                <div class="item-subtext">
                                    @if ($att->clock_in)
                                        Masuk: {{ date('H:i', strtotime($att->clock_in)) }}
                                        · Pulang:
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
                                        <span
                                            style="color: var(--color-warning); font-weight: 600; display: block; margin-top: 2px;">
                                            ⏰ Terlambat {{ $att->late_minutes }} mnt
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <span
                            class="status-pill {{ $att->status }} {{ !$att->clock_in && $att->status === 'present' ? 'pending' : '' }}">
                            {{ !$att->clock_in && $att->status === 'present' ? 'Belum Scan' : $statusLabels[$att->status] ?? ucfirst($att->status) }}
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Riwayat Pengajuan Koreksi Section -->
    <div style="margin-top: 28px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; padding: 0 4px;">
        <span style="font-family: var(--font-display); font-size: 14px; font-weight: 700; color: white;">Riwayat Pengajuan Koreksi Absen</span>
    </div>

    <div class="list-wrapper" style="margin-bottom: 30px;">
        @if ($corrections->isEmpty())
            <div style="text-align: center; color: var(--text-muted); padding: 20px 0; font-size: 12.5px;">
                Belum ada pengajuan koreksi absen.
            </div>
        @else
            @foreach ($corrections as $corr)
                @php
                    $dCorr = \Carbon\Carbon::parse($corr->date);
                    $statusLabels = [
                        'pending' => 'Menunggu',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                    ];
                @endphp
                <div class="item-card" style="margin-bottom: 10px;">
                    <div class="item-left">
                        <div class="item-date-badge" style="background: rgba(255, 255, 255, 0.03); border-color: var(--color-primary); display: flex; flex-direction: column; align-items: center; justify-content: center; width: 42px; height: 42px; border: 1px solid var(--border-color); border-radius: 10px; margin-right: 12px; flex-shrink: 0;">
                            <span style="font-size: 11px; font-weight: 700; color: white; line-height: 1;">
                                {{ $dCorr->format('d') }}
                            </span>
                            <span style="font-size: 7.5px; text-transform: uppercase; color: var(--text-secondary); margin-top: 1px; line-height: 1;">
                                {{ $monthShortIndo[$dCorr->month] ?? '' }}
                            </span>
                        </div>
                        <div>
                            <div class="item-title">
                                Koreksi Tanggal {{ $dCorr->format('d/m/Y') }}
                            </div>
                            <div class="item-subtext" style="font-size: 11.5px; line-height: 1.4; color: var(--text-secondary);">
                                @if ($corr->clock_in)
                                    <div>Masuk Baru: <strong style="color: white; font-family: var(--font-display);">{{ date('H:i', strtotime($corr->clock_in)) }} WIB</strong></div>
                                @endif
                                @if ($corr->clock_out)
                                    <div>Pulang Baru: <strong style="color: white; font-family: var(--font-display);">{{ date('H:i', strtotime($corr->clock_out)) }} WIB</strong></div>
                                @endif
                                <div style="font-style: italic; color: var(--text-muted); margin-top: 2px;">"{{ $corr->reason }}"</div>
                                @if ($corr->admin_notes)
                                    <div style="margin-top: 4px; color: #f59e0b; font-weight: 600;">HRD: "{{ $corr->admin_notes }}"</div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <span class="status-pill {{ $corr->status }}">
                        {{ $statusLabels[$corr->status] ?? ucfirst($corr->status) }}
                    </span>
                </div>
            @endforeach
        @endif
    </div>
</div>
