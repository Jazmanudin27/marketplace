@extends('layouts.app')
@section('title', 'Laporan Presensi Karyawan')
@section('page-title', 'Laporan Presensi Karyawan')

@push('styles')
    <style>
        /* ===== Fix Horizontal Scroll ===== */
        /* Override body overflow-x:hidden yang ada di app.css agar tabel bisa scroll */
        body {
            overflow-x: auto !important;
        }

        .main-content {
            overflow-x: auto;
            min-width: 0;
        }

        .page-content {
            min-width: 0;
            overflow-x: visible;
        }

        /* Spreadsheet Grid Styles */
        .spreadsheet-container {
            display: block;
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }

        .table-spreadsheet {
            border-collapse: collapse;
            width: max-content !important;
            min-width: 100%;
            font-size: 0.75rem;
            color: var(--text-primary);
        }

        .table-spreadsheet th,
        .table-spreadsheet td {
            border: 1px solid var(--border) !important;
            padding: 0.4rem 0.5rem;
            text-align: center;
            vertical-align: middle;
            white-space: nowrap;
        }

        .table-spreadsheet thead th {
            background: var(--bg-card2) !important;
            color: #fff;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table-spreadsheet tbody tr:hover {
            background: rgba(255, 255, 255, 0.02);
        }

        /* Fixed column styling */
        .col-sticky {
            position: sticky;
            left: 0;
            background: var(--bg-card) !important;
            z-index: 2;
            text-align: left !important;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.2);
        }

        .col-sticky-2 {
            position: sticky;
            left: 40px;
            background: var(--bg-card) !important;
            z-index: 2;
            text-align: left !important;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.2);
        }

        .col-sticky-3 {
            position: sticky;
            left: 130px;
            background: var(--bg-card) !important;
            z-index: 2;
            text-align: left !important;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.2);
        }

        /* Status cell badges */
        .status-cell {
            font-weight: 700;
            min-width: 28px;
            width: auto;
            height: 28px;
            padding: 0 4px;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
        }

        .status-h {
            background: rgba(16, 185, 129, 0.15);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .status-s {
            background: rgba(6, 182, 212, 0.15);
            color: var(--info);
            border: 1px solid rgba(6, 182, 212, 0.3);
        }

        .status-i {
            background: rgba(245, 158, 11, 0.15);
            color: var(--warning);
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .status-c {
            background: rgba(139, 92, 246, 0.15);
            color: var(--purple);
            border: 1px solid rgba(139, 92, 246, 0.3);
        }

        .status-a {
            background: rgba(239, 68, 68, 0.15);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .cell-sunday {
            background: rgba(239, 68, 68, 0.05) !important;
            color: rgba(239, 68, 68, 0.6) !important;
        }

        .cell-holiday {
            background: rgba(108, 99, 255, 0.06) !important;
            color: var(--primary) !important;
            font-style: italic;
        }

        /* Print Media Query */
        @media print {
            body {
                background: #ffffff !important;
                color: #000000 !important;
            }

            .app-wrapper,
            .sidebar,
            .topbar,
            .btn-print-group,
            .card-header,
            .alert,
            .filter-section {
                display: none !important;
            }

            .main-content {
                margin-left: 0 !important;
                padding: 0 !important;
            }

            .page-content {
                padding: 0 !important;
            }

            .spreadsheet-container {
                overflow: visible !important;
                border: none !important;
                box-shadow: none !important;
                width: 100% !important;
            }

            .table-spreadsheet {
                width: 100% !important;
                color: #000000 !important;
                font-size: 0.6rem !important;
            }

            .table-spreadsheet th,
            .table-spreadsheet td {
                border: 1px solid #000000 !important;
                background: transparent !important;
                color: #000000 !important;
            }

            .table-spreadsheet thead th {
                background: #f0f0f0 !important;
                color: #000000 !important;
            }

            .col-sticky,
            .col-sticky-2,
            .col-sticky-3 {
                position: static !important;
                background: transparent !important;
                box-shadow: none !important;
            }

            .status-cell {
                border: none !important;
                background: transparent !important;
                color: #000000 !important;
            }
        }
    </style>
@endpush

@section('content')
    <!-- Filter Section & Actions -->
    <div class="row g-3 mb-4 filter-section">
        <div class="col-md-8">
            <div class="card" style="border-radius: 14px; background: var(--bg-card); border: 1px solid var(--border);">
                <div class="card-body p-3">
                    <form method="GET" action="{{ route('hr.attendance.report') }}" id="reportFilterForm"
                        class="row g-2 align-items-center">
                        <div class="col-auto">
                            <label class="form-label-custom mb-0 text-white-50"><i
                                    class="far fa-calendar-alt text-primary"></i> Periode Bulan:</label>
                        </div>
                        <div class="col-md-3">
                            <input type="month" name="period"
                                class="form-control form-control-custom form-control-sm py-1" value="{{ $period }}"
                                onchange="document.getElementById('reportFilterForm').submit()">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary btn-sm px-3 fw-semibold"
                                style="border-radius: 8px;"><i class="fas fa-search"></i> Tampilkan</button>
                        </div>
                        <div class="col-auto text-muted small ps-2">
                            <i class="fas fa-info-circle text-info me-1"></i> Range Cut-off:
                            <strong>{{ date('d M Y', strtotime($startDate)) }}</strong> s.d.
                            <strong>{{ date('d M Y', strtotime($endDate)) }}</strong>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-4 text-md-end d-flex align-items-center justify-content-md-end gap-2 btn-print-group">
            @if (Auth::user()->hasPermissionTo('print-attendance-report'))
                <button onclick="window.print()" class="btn btn-info btn-sm text-white px-4 fw-semibold"
                    style="border-radius: 8px;">
                    <i class="fas fa-print me-1"></i> Cetak Laporan
                </button>
            @endif
            <a href="{{ route('hr.attendance.index') }}" class="btn btn-secondary btn-sm px-3" style="border-radius: 8px;">
                <i class="fas fa-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    <!-- Spreadsheet Grid Card -->
    <div class="spreadsheet-container">
        <table class="table-spreadsheet">
            <thead>
                <!-- Row 1: Main Headers -->
                <tr>
                    <th rowspan="2" style="width: 40px; min-width: 40px;">No</th>
                    <th rowspan="2" style="width: 100px; min-width: 100px;">Nik</th>
                    <th rowspan="2" style="width: 180px; min-width: 180px;">Nama Karyawan</th>
                    <th rowspan="2" style="width: 70px; min-width: 70px;">Cabang</th>
                    <th colspan="{{ count($dates) }}">Tanggal</th>
                    <th colspan="9">Tidak Masuk Karena</th>
                    <th rowspan="2">Σ Jam<br>(1 Bulan)</th>
                    <th rowspan="2">Telat</th>
                    <th rowspan="2">Denda</th>
                    <th rowspan="2">OT1</th>
                    <th rowspan="2">OT2</th>
                    <th rowspan="2">OTL</th>
                    <th rowspan="2">TOTAL<br>HADIR</th>
                </tr>
                <!-- Row 2: Date Details (Day Names & Date Numbers) -->
                <tr>
                    @foreach ($dates as $d)
                        <th class="{{ $d['is_sunday'] ? 'cell-sunday' : '' }} {{ isset($holidays[$d['date']]) ? 'cell-holiday' : '' }}"
                            style="width: 32px; min-width: 32px;"
                            title="{{ $d['day_name'] }}, {{ date('d M Y', strtotime($d['date'])) }}">
                            <div>{{ substr($d['day_name'], 0, 3) }}</div>
                            <div class="mt-1 fw-bold fs-7">{{ $d['day_num'] }}</div>
                        </th>
                    @endforeach
                    <!-- Status columns -->
                    <th style="width: 30px;" title="Hadir (H)">H</th>
                    <th style="width: 30px;" title="Sakit (S)">S</th>
                    <th style="width: 30px;" title="Mangkir / Alfa (A)">A</th>
                    <th style="width: 30px;" title="Izin Dengan Surat (SID)">SID</th>
                    <th style="width: 30px;" title="Sakit Dengan Surat (SKT)">SKT</th>
                    <th style="width: 30px;" title="Izin Khusus (IK)">IK</th>
                    <th style="width: 30px;" title="Cuti (C)">C</th>
                    <th style="width: 30px;" title="Izin Tidak Dibayar (ITH)">ITH</th>
                    <th style="width: 30px;" title="Lainnya">L</th>
                </tr>
            </thead>
            <tbody>
                @forelse($employees as $idx => $emp)
                    @php
                        // Formatted NIK dynamically using employee ID (mimicking user template NIKs like '00.01.029)
$nik = '00.01.' . str_pad($emp->id + 28, 3, '0', STR_PAD_LEFT);

$empAttendances = $attendances->get($emp->id) ?? collect();
$empOvertimes = $overtimes->get($emp->id) ?? collect();
$empLeaves = $leaveRequests->get($emp->id) ?? collect();

$countH = 0;
$countS = 0;
$countA = 0;
$countSID = 0; // Izin/Permission
$countSKT = 0; // Sakit resmi
$countIK = 0; // Izin Khusus
$countC = 0; // Cuti
$countITH = 0; // Izin tanpa gaji
$countL = 0; // Lainnya

$totalLateDays = 0;
$totalLatePenalty = 0;
$totalWorkHours = 0;
$totalOvertimeHours = $empOvertimes->sum('hours');
                    @endphp
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td><strong>{{ $nik }}</strong></td>
                        <td class="text-start">
                            <strong class="text-white">{{ $emp->name }}</strong>
                            <div class="text-muted" style="font-size: 0.65rem;">{{ $emp->position ?? 'Karyawan' }}</div>
                        </td>
                        <td>PST</td>

                        <!-- Loop Tanggal -->
                        @foreach ($dates as $d)
                            @php
                                $dateStr = $d['date'];
                                $isHoliday = isset($holidays[$dateStr]);
                                $isSunday = $d['is_sunday'];

                                // Ambil presensi
                                $att = $empAttendances->first(fn($a) => $a->date->toDateString() === $dateStr);

                                // Ambil data pengajuan izin/cuti
                                $leave = $empLeaves->first(
                                    fn($l) => $dateStr >= $l->start_date->toDateString() &&
                                        $dateStr <= $l->end_date->toDateString(),
                                );
                                 $sched = $emp->getScheduleForDate($dateStr);
                                 $dayHours = 0;
                                 $schedDurationMinutes = 0;
                                 if (!$sched->is_off && $sched->clock_in && $sched->clock_out) {
                                     $schedIn = \Carbon\Carbon::parse($dateStr . ' ' . $sched->clock_in);
                                     $schedOut = \Carbon\Carbon::parse($dateStr . ' ' . $sched->clock_out);
                                     if ($schedOut->greaterThan($schedIn)) {
                                         $schedDurationMinutes = $schedOut->diffInMinutes($schedIn, true);
                                         $schedBreak = 0;
                                         if ($schedDurationMinutes > 300) {
                                             $breakStart = \Carbon\Carbon::parse($dateStr . ' 11:00:00');
                                             $breakEnd = \Carbon\Carbon::parse($dateStr . ' 12:00:00');
                                             $overlapStart = $schedIn->greaterThan($breakStart) ? $schedIn : $breakStart;
                                             $overlapEnd = $schedOut->lessThan($breakEnd) ? $schedOut : $breakEnd;
                                             if ($overlapEnd->greaterThan($overlapStart)) {
                                                 $schedBreak = $overlapEnd->diffInMinutes($overlapStart, true);
                                             }
                                         }
                                         $dayHours = round(($schedDurationMinutes - $schedBreak) / 60, 1);
                                     }
                                 }

                                 $cellChar = '';
                                 $cellClass = '';
                                 $cellTooltip = '';

                                 if ($att) {
                                     if ($att->status === 'present') {
                                         // Hitung jam kerja
                                         $workHours = $dayHours;

                                         if ($att->clock_in && $att->clock_out) {
                                             try {
                                                 $inTime = \Carbon\Carbon::parse($dateStr . ' ' . $att->clock_in);
                                                 $outTime = \Carbon\Carbon::parse($dateStr . ' ' . $att->clock_out);

                                                  // Jika clock_in sebelum jam 11:00, bulatkan ke jam masuk standar agar keterlambatan tidak memotong jam kerja
                                                  $calcInTime = $inTime;
                                                  $schedInLimit = \Carbon\Carbon::parse($dateStr . ' 11:00:00');
                                                  if ($inTime->lessThanOrEqualTo($schedInLimit) && $sched->clock_in) {
                                                      $schedInTime = \Carbon\Carbon::parse($dateStr . ' ' . $sched->clock_in);
                                                      $calcInTime = $schedInTime;
                                                  }

                                                 if ($outTime->greaterThan($calcInTime)) {
                                                     $rawMinutes = $outTime->diffInMinutes($calcInTime, true);

                                                     // Hitung potongan istirahat hanya jika durasi jam kerja terjadwal > 5 jam (300 menit)
                                                     $breakMinutes = 0;
                                                     if ($schedDurationMinutes > 300) {
                                                         // Istirahat dari jam 11:00 sampai 12:00
                                                         $breakStart = \Carbon\Carbon::parse($dateStr . ' 11:00:00');
                                                         $breakEnd = \Carbon\Carbon::parse($dateStr . ' 12:00:00');

                                                         // Overlap interval [calcInTime, outTime] dengan [breakStart, breakEnd]
                                                         $overlapStart = $calcInTime->greaterThan($breakStart)
                                                             ? $calcInTime
                                                             : $breakStart;
                                                         $overlapEnd = $outTime->lessThan($breakEnd)
                                                             ? $outTime
                                                             : $breakEnd;

                                                         if ($overlapEnd->greaterThan($overlapStart)) {
                                                             $breakMinutes = $overlapEnd->diffInMinutes(
                                                                 $overlapStart,
                                                                 true,
                                                             );
                                                         }
                                                     }

                                                     $workHours = round(($rawMinutes - $breakMinutes) / 60, 1);
                                                 }
                                             } catch (\Exception $e) {
                                                 $workHours = $dayHours;
                                             }
                                         }

                                         if ($dayHours > 0 && $workHours >= $dayHours) {
                                             $cellChar = 'H';
                                         } elseif ($dayHours > 0) {
                                             $formattedHours = (float) $workHours;
                                             $cellChar = 'H' . $formattedHours;
                                         } else {
                                             $cellChar = 'H';
                                         }

                                         $cellClass = 'status-h';
                                         $countH++;

                                         $totalWorkHours += $workHours;

                                         if ($att->late_minutes > 0) {
                                             $totalLateDays++;
                                             $totalLatePenalty += $att->late_penalty;
                                         }
                                     } elseif ($att->status === 'sick') {
                                         $cellChar = 'S';
                                         $cellClass = 'status-s';
                                         $countS++;
                                     } elseif ($att->status === 'permission') {
                                         $cellChar = 'I';
                                         $cellClass = 'status-i';
                                         $countSID++;
                                     } elseif ($att->status === 'leave') {
                                         $cellChar = 'C';
                                         $cellClass = 'status-c';
                                         $countC++;
                                     } elseif ($att->status === 'alpha') {
                                         $cellChar = 'A';
                                         $cellClass = 'status-a';
                                         $countA++;
                                     }
                                 } elseif ($leave) {
                                     // Jika ada izin disetujui
                                     if ($leave->type === 'sick') {
                                         $cellChar = 'SKT';
                                         $cellClass = 'status-s';
                                         $countSKT++;
                                     } elseif ($leave->type === 'permission') {
                                         $cellChar = 'SID';
                                         $cellClass = 'status-i';
                                         $countSID++;
                                     } elseif ($leave->type === 'leave') {
                                         $cellChar = 'C';
                                         $cellClass = 'status-c';
                                         $countC++;
                                     }
                                 } else {
                                     // Tanpa data absensi dan izin
                                     if ($isHoliday) {
                                         $cellChar = 'L';
                                         $cellClass = 'cell-holiday';
                                         $cellTooltip = $holidays[$dateStr]->name;
                                     } elseif ($sched->is_off) {
                                         $cellChar = '';
                                         $cellClass = 'cell-sunday';
                                     } else {
                                         // Untuk tanggal masa lalu yang tidak ada absen dianggap Alpha (A)
                                         if ($dateStr <= today()->format('Y-m-d')) {
                                             $cellChar = 'A';
                                             $cellClass = 'status-a';
                                             $countA++;
                                         } else {
                                             $cellChar = '';
                                             $cellClass = '';
                                         }
                                     }
                                 }
                            @endphp
                            <td class="{{ $cellClass }}" title="{{ $cellTooltip }}">
                                @if ($cellChar)
                                    <span
                                        class="status-cell {{ strpos($cellChar, 'H') === 0 || in_array($cellChar, ['S', 'I', 'C', 'A', 'SKT', 'SID']) ? $cellClass : '' }}">{{ $cellChar }}</span>
                                @endif
                            </td>
                        @endforeach

                        <!-- Counters (Tidak Masuk Karena) -->
                        <td class="text-success fw-bold">{{ $countH }}</td>
                        <td class="text-info">{{ $countS }}</td>
                        <td class="text-danger fw-bold">{{ $countA }}</td>
                        <td class="text-warning">{{ $countSID }}</td>
                        <td class="text-info">{{ $countSKT }}</td>
                        <td>{{ $countIK }}</td>
                        <td class="text-purple">{{ $countC }}</td>
                        <td>{{ $countITH }}</td>
                        <td>{{ $countL }}</td>

                        <!-- Summaries -->
                        <td class="fw-bold">{{ $totalWorkHours }} jam</td>
                        <td class="{{ $totalLateDays > 0 ? 'text-danger fw-bold' : 'text-muted' }}">{{ $totalLateDays }}x
                        </td>
                        <td class="{{ $totalLatePenalty > 0 ? 'text-danger fw-bold' : 'text-muted' }}">Rp
                            {{ number_format($totalLatePenalty, 0, ',', '.') }}</td>

                        <!-- Overtime splits (OT1, OT2, OTL) -->
                        @php
                            $ot1 = $totalOvertimeHours > 0 ? min(1, $totalOvertimeHours) : 0;
                            $ot2 = $totalOvertimeHours > 1 ? min(1, $totalOvertimeHours - 1) : 0;
                            $otl = $totalOvertimeHours > 2 ? $totalOvertimeHours - 2 : 0;
                        @endphp
                        <td class="text-success">{{ $ot1 > 0 ? $ot1 : '-' }}</td>
                        <td class="text-success">{{ $ot2 > 0 ? $ot2 : '-' }}</td>
                        <td class="text-success fw-bold">{{ $otl > 0 ? $otl : '-' }}</td>

                        <!-- Total Hadir -->
                        <td class="text-success fw-bold" style="font-size: 0.85rem;">{{ $countH }} hr</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($dates) + 17 }}" class="text-center py-5 text-muted">
                            <div class="fs-4 mb-2"><i class="far fa-folder-open"></i></div>
                            Tidak ada data karyawan aktif untuk periode ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
