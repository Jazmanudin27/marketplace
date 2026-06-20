@extends('layouts.app')
@section('title', 'Slip Gaji Karyawan')
@section('page-title', 'Slip Gaji Karyawan')

@push('styles')
    <style>
        @media print {
            body {
                background: #ffffff !important;
                color: #000000 !important;
            }

            .app-wrapper,
            .sidebar,
            .topbar,
            .btn-print-group,
            .alert {
                display: none !important;
            }

            .main-content {
                margin-left: 0 !important;
                padding: 0 !important;
            }

            .page-content {
                padding: 0 !important;
            }

            #payroll-slip-card {
                background: #ffffff !important;
                color: #000000 !important;
                border: none !important;
                box-shadow: none !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .table-light {
                background-color: #f8f9fa !important;
                color: #000000 !important;
            }

            .text-muted {
                color: #555555 !important;
            }

            .border,
            .border-light-subtle {
                border: 1px solid #000000 !important;
            }

            .text-white,
            .text-success,
            .text-danger,
            .text-primary,
            .text-success-emphasis,
            .text-danger-emphasis {
                color: #000000 !important;
            }

            .print-dark-text {
                color: #000000 !important;
            }

            .bg-secondary-subtle,
            .bg-success-subtle,
            .bg-danger-subtle,
            .bg-primary-subtle {
                background: transparent !important;
            }

            /* Make sure everything prints nicely in light mode colors */
            .card-body,
            .table,
            td,
            th,
            h3,
            h4,
            h5,
            h6,
            strong,
            span,
            small {
                color: #000000 !important;
                background: transparent !important;
            }
        }
    </style>
@endpush

@section('content')
    <div class="row mb-4 btn-print-group">
        <div class="col-12 d-flex justify-content-between">
            <a href="{{ route('hr.payroll.index', ['period' => $payroll->period]) }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Kembali ke Daftar Gaji
            </a>
        </div>
    </div>

    <div class="card max-w-4xl mx-auto" id="payroll-slip-card" style="background:var(--bg-card); border-color:var(--border);">
        <div class="card-body p-5">
            <!-- Header Slip -->
            <div class="row border-bottom pb-4 mb-4">
                <div class="col-md-6 d-flex align-items-center">
                    <div class="brand-icon me-3">
                        <i class="fas fa-store-alt text-white"></i>
                    </div>
                    <div>
                        <h3 class="mb-0 fw-bold print-dark-text">{{ Auth::user()->tenant->name }}</h3>
                        <small class="text-muted">ERP Marketplace Integrated Payroll</small>
                    </div>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <h5 class="fw-bold mb-0 text-primary print-dark-text">SLIP GAJI KARYAWAN</h5>
                    <span class="badge bg-secondary">{{ date('F Y', strtotime($payroll->period . '-01')) }}</span>
                </div>
            </div>

            <!-- Detail Karyawan & Status -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <table class="table table-borderless table-sm">
                        <tr>
                            <td class="text-muted px-0" style="width: 35%;">Nama Karyawan</td>
                            <td class="px-0" style="width: 5%;">:</td>
                            <td class="fw-bold px-0">{{ $payroll->employee->name }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted px-0">Jabatan / Posisi</td>
                            <td class="px-0">:</td>
                            <td>{{ $payroll->employee->position ?? 'Karyawan' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted px-0">No. HP</td>
                            <td class="px-0">:</td>
                            <td>{{ $payroll->employee->phone ?? '-' }}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="p-3 rounded border border-light-subtle d-inline-block text-start"
                        style="background:rgba(255,255,255,0.02); min-width: 200px;">
                        <small class="text-muted d-block">Status Pembayaran:</small>
                        @if ($payroll->status === 'paid')
                            <strong class="text-success"><i class="fas fa-check-circle"></i> DIBAYAR</strong>
                            <small class="d-block text-muted">Tgl:
                                {{ $payroll->payment_date ? $payroll->payment_date->format('d M Y') : '-' }}</small>
                        @else
                            <strong class="text-warning"><i class="fas fa-clock"></i> PENDING (DRAFT)</strong>
                            <small class="d-block text-muted">Belum ditransfer</small>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Rincian Jam Kerja & Kehadiran (Attendance Stats) -->
            @if ($payroll->salary_type === 'monthly')
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="p-3 rounded border border-light-subtle h-100 d-flex align-items-center"
                            style="background:rgba(255, 255, 255, 0.02); border-color: var(--border) !important;">
                            <div class="brand-icon me-3 p-3 rounded-circle d-flex align-items-center justify-content-center"
                                style="width: 48px; height: 48px; background: rgba(108, 99, 255, 0.1) !important; color: var(--primary);">
                                <i class="fas fa-calendar-alt fs-5"></i>
                            </div>
                            <div>
                                <small class="text-muted d-block text-uppercase fw-semibold"
                                    style="font-size: 0.7rem; letter-spacing: 0.5px;">Kerja Standar</small>
                                <h4 class="mb-0 fw-bold text-white">{{ $standardHours }} <span
                                        class="fs-6 fw-normal text-muted">Jam</span></h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 rounded border border-light-subtle h-100 d-flex align-items-center"
                            style="background:rgba(255, 255, 255, 0.02); border-color: var(--border) !important;">
                            <div class="brand-icon me-3 p-3 rounded-circle d-flex align-items-center justify-content-center"
                                style="width: 48px; height: 48px; background: rgba(16, 185, 129, 0.1) !important; color: var(--success);">
                                <i class="fas fa-user-check fs-5"></i>
                            </div>
                            <div>
                                <small class="text-success d-block text-uppercase fw-semibold"
                                    style="font-size: 0.7rem; letter-spacing: 0.5px;">Hadir Kerja</small>
                                <h4 class="mb-0 fw-bold text-success">{{ max(0, $standardHours - $deductionHours) }} <span
                                        class="fs-6 fw-normal text-success-emphasis">Jam</span></h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 rounded border border-light-subtle h-100 d-flex align-items-center"
                            style="background:rgba(255, 255, 255, 0.02); border-color: var(--border) !important;">
                            <div class="brand-icon me-3 p-3 rounded-circle d-flex align-items-center justify-content-center"
                                style="width: 48px; height: 48px; background: rgba(239, 68, 68, 0.1) !important; color: var(--danger);">
                                <i class="fas fa-user-times fs-5"></i>
                            </div>
                            <div>
                                <small class="text-danger d-block text-uppercase fw-semibold"
                                    style="font-size: 0.7rem; letter-spacing: 0.5px;">Mangkir (Alfa)</small>
                                <h4 class="mb-0 fw-bold text-danger">{{ $deductionHours }} <span
                                        class="fs-6 fw-normal text-danger-emphasis">Jam</span></h4>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Banner Perhitungan Rate Upah Per Jam -->
                <div class="p-3 rounded border border-light-subtle mb-4 d-flex align-items-center"
                    style="background:rgba(108, 99, 255, 0.03); border-color: rgba(108, 99, 255, 0.15) !important;">
                    <div class="brand-icon me-3 p-3 rounded-circle d-flex align-items-center justify-content-center"
                        style="width: 40px; height: 40px; background: rgba(108, 99, 255, 0.1) !important; color: var(--primary);">
                        <i class="fas fa-calculator"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold text-white mb-1" style="font-size: 0.85rem;">Formula Rate per Jam (Deduction
                            Basis):</div>
                        <div class="text-muted small" style="font-size: 0.8rem; line-height: 1.5;">
                            Rate = (Gaji Pokok + Total Tunjangan) &div; Jam Kerja Standar <br>
                            Rate = (Rp {{ number_format($payroll->basic_salary, 0, ',', '.') }} + Rp
                            {{ number_format($payroll->allowance, 0, ',', '.') }}) &div; {{ $standardHours }} jam =
                            <strong class="text-primary">Rp {{ number_format($hourlyRate, 2, ',', '.') }}/jam</strong>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Komponen Pendapatan & Potongan -->
            <div class="row g-4 mb-4">
                <!-- Pendapatan -->
                <div class="col-md-6">
                    <div class="border rounded p-3 h-100"
                        style="background:rgba(255,255,255,0.01); border-color: var(--border) !important;">
                        <h6 class="border-bottom pb-2 fw-bold text-success"><i class="fas fa-plus-circle me-1"></i>
                            PENDAPATAN (EARNINGS)</h6>
                        <table class="table table-borderless table-sm mb-0">
                            <tr>
                                <td class="px-0">
                                    @if ($payroll->salary_type === 'hourly')
                                        Gaji Per Jam (Rate)
                                        <small class="text-muted d-block">({{ floatval($payroll->hours_worked) }} jam @ Rp
                                            {{ number_format($payroll->employee->basic_salary, 0, ',', '.') }}/jam)</small>
                                    @else
                                        Gaji Pokok (Bulanan)
                                        <small class="text-muted d-block">Gaji bulanan tetap</small>
                                    @endif
                                </td>
                                <td class="text-end px-0 align-bottom fw-semibold">Rp
                                    {{ number_format($payroll->basic_salary, 0, ',', '.') }}</td>
                            </tr>
                            @foreach ($payroll->allowances as $alw)
                                <tr>
                                    <td class="px-0">
                                        {{ $alw->name }}
                                    </td>
                                    <td class="text-end px-0 fw-semibold">Rp {{ number_format($alw->amount, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                            <tr>
                                <td class="px-0">
                                    Uang Lembur
                                    <small class="text-muted d-block">({{ floatval($overtimeHours) }} jam @ Rp
                                        {{ number_format($payroll->employee->overtime_rate, 0, ',', '.') }}/jam)</small>
                                </td>
                                <td class="text-end px-0 align-bottom fw-semibold">Rp
                                    {{ number_format($payroll->overtime_pay, 0, ',', '.') }}</td>
                            </tr>
                            @if ($payroll->salary_adjustment_addition > 0)
                                <tr>
                                    <td class="px-0">
                                        Penyesuaian Penambah
                                        @if ($payroll->salary_adjustment_notes)
                                            <small class="text-muted d-block">{{ $payroll->salary_adjustment_notes }}</small>
                                        @endif
                                    </td>
                                    <td class="text-end px-0 align-bottom text-success fw-semibold">+Rp
                                        {{ number_format($payroll->salary_adjustment_addition, 0, ',', '.') }}</td>
                                </tr>
                            @endif
                            <tr class="border-top fw-bold text-success">
                                <td class="px-0 pt-2">Total Pendapatan</td>
                                <td class="text-end px-0 pt-2 fs-6">Rp
                                    {{ number_format($payroll->basic_salary + $payroll->allowance + $payroll->overtime_pay + $payroll->salary_adjustment_addition, 0, ',', '.') }}
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Potongan -->
                <div class="col-md-6">
                    <div class="border rounded p-3 h-100"
                        style="background:rgba(255,255,255,0.01); border-color: var(--border) !important;">
                        <h6 class="border-bottom pb-2 fw-bold text-danger"><i class="fas fa-minus-circle me-1"></i>
                            POTONGAN (DEDUCTIONS)</h6>
                        <table class="table table-borderless table-sm mb-0">
                            <tr>
                                <td class="px-0">
                                    Pengembalian Kasbon
                                    @if ($cashAdvances->isNotEmpty())
                                        <small class="text-muted d-block">
                                            (@foreach ($cashAdvances as $ca)
                                                Rp{{ number_format($ca->amount, 0, ',', '.') }}@if (!$loop->last)
                                                    ,
                                                @endif
                                            @endforeach)
                                        </small>
                                    @endif
                                </td>
                                <td class="text-end px-0 align-bottom text-danger fw-semibold">-Rp
                                    {{ number_format($payroll->cash_advance_deduction, 0, ',', '.') }}</td>
                            </tr>
                            @if ($payroll->attendance_deduction > 0)
                                <tr>
                                    <td class="px-0">
                                        Potongan Absensi (Izin/Alpha)
                                        <small class="text-muted d-block">(Tidak hadir: {{ $deductionHours }} jam &times;
                                            Rp {{ number_format($hourlyRate, 0, ',', '.') }}/jam)</small>
                                    </td>
                                    <td class="text-end px-0 text-danger align-bottom fw-semibold">-Rp
                                        {{ number_format($payroll->attendance_deduction, 0, ',', '.') }}</td>
                                </tr>
                            @endif
                            @if ($payroll->late_deduction > 0)
                                <tr>
                                    <td class="px-0">
                                        Denda Keterlambatan
                                        @if ($totalLateDays > 0)
                                            <small class="text-muted d-block">({{ $totalLateDays }}x terlambat, total
                                                {{ $totalLateMinutes }} menit)</small>
                                        @endif
                                    </td>
                                    <td class="text-end px-0 text-danger align-bottom fw-semibold">-Rp
                                        {{ number_format($payroll->late_deduction, 0, ',', '.') }}</td>
                                </tr>
                            @endif
                            <tr>
                                <td class="px-0">Potongan Lainnya</td>
                                <td class="text-end px-0 text-danger fw-semibold">-Rp
                                    {{ number_format($payroll->other_deductions, 0, ',', '.') }}</td>
                            </tr>
                            @if ($payroll->salary_adjustment_deduction > 0)
                                <tr>
                                    <td class="px-0">
                                        Penyesuaian Pengurang
                                        @if ($payroll->salary_adjustment_notes && $payroll->salary_adjustment_addition == 0)
                                            <small class="text-muted d-block">{{ $payroll->salary_adjustment_notes }}</small>
                                        @endif
                                    </td>
                                    <td class="text-end px-0 align-bottom text-danger fw-semibold">-Rp
                                        {{ number_format($payroll->salary_adjustment_deduction, 0, ',', '.') }}</td>
                                </tr>
                            @endif
                            <tr class="border-top fw-bold text-danger">
                                <td class="px-0 pt-2">Total Potongan</td>
                                <td class="text-end px-0 pt-2 fs-6">-Rp
                                    {{ number_format($payroll->cash_advance_deduction + $payroll->attendance_deduction + $payroll->late_deduction + $payroll->other_deductions + $payroll->salary_adjustment_deduction, 0, ',', '.') }}
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Gaji Bersih Diterima -->
            <div class="p-3 rounded mb-5 text-center fw-bold border border-primary-subtle"
                style="background:rgba(108, 99, 255, 0.05);">
                <span class="text-muted d-block small mb-1">TOTAL GAJI BERSIH DITERIMA (NET SALARY)</span>
                <h2 class="mb-0 fw-extrabold text-primary print-dark-text">Rp
                    {{ number_format($payroll->net_salary, 0, ',', '.') }}</h2>
                <small class="text-muted">Terbilang: {{ terbilang($payroll->net_salary) }} Rupiah</small>
            </div>

            <!-- Tanda Tangan -->
            <div class="row pt-4 mt-5">
                <div class="col-6 text-center">
                    <small class="text-muted d-block mb-5">Diterima Oleh,</small>
                    <div class="border-bottom mx-auto" style="width: 150px;"></div>
                    <strong class="d-block mt-2">{{ $payroll->employee->name }}</strong>
                </div>
                <div class="col-6 text-center">
                    <small class="text-muted d-block mb-5">Disetujui Oleh,</small>
                    <div class="border-bottom mx-auto" style="width: 150px;"></div>
                    <strong class="d-block mt-2">HRD & Payroll Manager</strong>
                </div>
            </div>
        </div>
    </div>

    @if ($payroll->status === 'draft')
        <div class="card max-w-4xl mx-auto mt-4 d-print-none" style="background:var(--bg-card); border-color:var(--border); border-radius: 14px;">
            <div class="card-header py-3 border-bottom d-flex align-items-center" style="border-color: var(--border) !important;">
                <div class="brand-icon bg-primary-subtle text-primary me-3 p-2 rounded d-flex align-items-center justify-content-center"
                    style="width: 32px; height: 32px; background: rgba(108, 99, 255, 0.1) !important;">
                    <i class="fas fa-edit text-primary"></i>
                </div>
                <h6 class="mb-0 fw-bold text-white">Input Penyesuaian Gaji (Kurang / Lebih Bayar)</h6>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('hr.payroll.update', $payroll->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="salary_adjustment_addition" class="form-label text-muted small fw-semibold">PENAMBAH GAJI (Kurang Bayar Bulan Lalu)</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background: rgba(255,255,255,0.05); border-color: var(--border); color: var(--text-secondary);">Rp</span>
                                <input type="number" name="salary_adjustment_addition" id="salary_adjustment_addition" class="form-control" 
                                    value="{{ old('salary_adjustment_addition', $payroll->salary_adjustment_addition > 0 ? round($payroll->salary_adjustment_addition) : '') }}" 
                                    placeholder="0" min="0" style="background: rgba(255,255,255,0.02); border-color: var(--border); color: #fff;">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="salary_adjustment_deduction" class="form-label text-muted small fw-semibold">PENGURANG GAJI (Lebih Bayar Bulan Lalu)</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background: rgba(255,255,255,0.05); border-color: var(--border); color: var(--text-secondary);">Rp</span>
                                <input type="number" name="salary_adjustment_deduction" id="salary_adjustment_deduction" class="form-control" 
                                    value="{{ old('salary_adjustment_deduction', $payroll->salary_adjustment_deduction > 0 ? round($payroll->salary_adjustment_deduction) : '') }}" 
                                    placeholder="0" min="0" style="background: rgba(255,255,255,0.02); border-color: var(--border); color: #fff;">
                            </div>
                        </div>
                        <div class="col-12">
                            <label for="salary_adjustment_notes" class="form-label text-muted small fw-semibold">CATATAN PENYESUAIAN</label>
                            <textarea name="salary_adjustment_notes" id="salary_adjustment_notes" class="form-control" rows="2" 
                                placeholder="Contoh: Kurang bayar insentif periode Mei 2026" 
                                style="background: rgba(255,255,255,0.02); border-color: var(--border); color: #fff;">{{ old('salary_adjustment_notes', $payroll->salary_adjustment_notes) }}</textarea>
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary px-4 fw-semibold" style="border-radius: 10px;"><i class="fas fa-save me-1"></i> Simpan Penyesuaian</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @php
        function terbilang($angka)
        {
            $angka = (int) round(abs($angka));
            $baca = [
                '',
                'satu',
                'dua',
                'tiga',
                'empat',
                'lima',
                'enam',
                'tujuh',
                'delapan',
                'sembilan',
                'sepuluh',
                'sebelas',
            ];
            $terbilang = '';

            if ($angka < 12) {
                $terbilang = ' ' . $baca[$angka];
            } elseif ($angka < 20) {
                $terbilang = terbilang($angka - 10) . ' belas';
            } elseif ($angka < 100) {
                $terbilang = terbilang((int) ($angka / 10)) . ' puluh' . terbilang($angka % 10);
            } elseif ($angka < 200) {
                $terbilang = ' seratus' . terbilang($angka - 100);
            } elseif ($angka < 1000) {
                $terbilang = terbilang((int) ($angka / 100)) . ' ratus' . terbilang($angka % 100);
            } elseif ($angka < 2000) {
                $terbilang = ' seribu' . terbilang($angka - 1000);
            } elseif ($angka < 1000000) {
                $terbilang = terbilang((int) ($angka / 1000)) . ' ribu' . terbilang($angka % 1000);
            } elseif ($angka < 1000000000) {
                $terbilang = terbilang((int) ($angka / 1000000)) . ' juta' . terbilang($angka % 1000000);
            }

            return trim($terbilang);
        }
    @endphp
@endsection
