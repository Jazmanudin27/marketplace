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
        <div class="col-12 d-flex justify-content-between align-items-center">
            <a href="{{ route('hr.payroll.index', ['period' => $payroll->period]) }}" class="btn btn-secondary btn-sm rounded-3">
                <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar Gaji
            </a>
            <button onclick="window.print()" class="btn btn-primary btn-sm px-3 rounded-3">
                <i class="fas fa-print me-1"></i> Cetak Slip Gaji
            </button>
        </div>
    </div>

    <div class="card border shadow-sm mx-auto bg-white text-dark" id="payroll-slip-card" style="max-width: 800px;">
        <div class="card-body p-4">
            <!-- Header Slip -->
            <div class="row border-bottom pb-4 mb-4" style="border-color: #dee2e6 !important;">
                <div class="col-md-6 d-flex align-items-center">
                    <div class="bg-light border text-primary rounded-3 d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                        <i class="fas fa-store-alt" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <h3 class="mb-0 fw-bold text-dark" style="font-size: 1.25rem;">{{ Auth::user()->tenant->name }}</h3>
                        <small class="text-muted">ERP Marketplace Integrated Payroll</small>
                    </div>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <h5 class="fw-bold mb-0 text-primary">SLIP GAJI KARYAWAN</h5>
                    <span class="badge bg-secondary mt-1">{{ date('F Y', strtotime($payroll->period . '-01')) }}</span>
                </div>
            </div>

            <!-- Detail Karyawan & Status -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <table class="table table-borderless table-sm mb-0">
                        <tr>
                            <td class="text-muted px-0" style="width: 35%;">Nama Karyawan</td>
                            <td class="px-0" style="width: 5%;">:</td>
                            <td class="fw-bold px-0 text-dark">{{ $payroll->employee->name }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted px-0">Jabatan / Posisi</td>
                            <td class="px-0">:</td>
                            <td class="text-dark">{{ $payroll->employee->position ?? 'Karyawan' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted px-0">No. HP</td>
                            <td class="px-0">:</td>
                            <td class="text-dark">{{ $payroll->employee->phone ?? '-' }}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="p-3 rounded border d-inline-block text-start bg-light" style="border-color: #dee2e6 !important; min-width: 200px;">
                        <small class="text-muted d-block">Status Pembayaran:</small>
                        @if ($payroll->status === 'paid')
                            <strong class="text-success"><i class="fas fa-check-circle me-1"></i> DIBAYAR</strong>
                            <small class="d-block text-muted">Tgl:
                                {{ $payroll->payment_date ? $payroll->payment_date->format('d M Y') : '-' }}</small>
                        @else
                            <strong class="text-warning"><i class="fas fa-clock me-1"></i> PENDING (DRAFT)</strong>
                            <small class="d-block text-muted">Belum ditransfer</small>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Rincian Jam Kerja & Kehadiran (Attendance Stats) -->
            @if ($payroll->salary_type === 'monthly')
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="p-3 rounded border h-100 d-flex align-items-center bg-light" style="border-color: #dee2e6 !important;">
                            <div class="me-3 p-3 rounded-circle d-flex align-items-center justify-content-center"
                                style="width: 48px; height: 48px; background: rgba(59, 130, 246, 0.1) !important; color: #3b82f6;">
                                <i class="fas fa-calendar-alt fs-5"></i>
                            </div>
                            <div>
                                <small class="text-muted d-block text-uppercase fw-semibold"
                                    style="font-size: 0.7rem; letter-spacing: 0.5px;">Kerja Standar</small>
                                <h4 class="mb-0 fw-bold text-dark">{{ $standardHours }} <span
                                        class="fs-6 fw-normal text-muted">Jam</span></h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 rounded border h-100 d-flex align-items-center bg-light" style="border-color: #dee2e6 !important;">
                            <div class="me-3 p-3 rounded-circle d-flex align-items-center justify-content-center"
                                style="width: 48px; height: 48px; background: rgba(16, 185, 129, 0.1) !important; color: #10b981;">
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
                        <div class="p-3 rounded border h-100 d-flex align-items-center bg-light" style="border-color: #dee2e6 !important;">
                            <div class="me-3 p-3 rounded-circle d-flex align-items-center justify-content-center"
                                style="width: 48px; height: 48px; background: rgba(239, 68, 68, 0.1) !important; color: #ef4444;">
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
                <div class="p-3 rounded border mb-4 d-flex align-items-center"
                    style="background: #f0f7ff; border-color: #b6d4fe !important;">
                    <div class="me-3 p-3 rounded-circle d-flex align-items-center justify-content-center"
                        style="width: 40px; height: 40px; background: rgba(59, 130, 246, 0.1) !important; color: #3b82f6;">
                        <i class="fas fa-calculator"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold text-dark mb-1" style="font-size: 0.85rem;">Formula Rate per Jam (Deduction Basis):</div>
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
                    <div class="border rounded p-3 h-100 bg-light" style="border-color: #dee2e6 !important;">
                        <h6 class="border-bottom pb-2 fw-bold text-success" style="border-color: #dee2e6 !important;"><i class="fas fa-plus-circle me-1"></i>
                            PENDAPATAN (EARNINGS)</h6>
                        <table class="table table-borderless table-sm mb-0">
                            <tr>
                                <td class="px-0 text-muted small">
                                    @if ($payroll->salary_type === 'hourly')
                                        Gaji Per Jam (Rate)
                                        <small class="text-muted d-block">({{ floatval($payroll->hours_worked) }} jam @ Rp
                                            {{ number_format($payroll->employee->basic_salary, 0, ',', '.') }}/jam)</small>
                                    @else
                                        Gaji Pokok (Bulanan)
                                        <small class="text-muted d-block">Gaji bulanan tetap</small>
                                    @endif
                                </td>
                                <td class="text-end px-0 align-bottom fw-semibold text-dark small">Rp
                                    {{ number_format($payroll->basic_salary, 0, ',', '.') }}</td>
                            </tr>
                            @foreach ($payroll->allowances as $alw)
                                <tr>
                                    <td class="px-0 text-muted small">
                                        {{ $alw->name }}
                                    </td>
                                    <td class="text-end px-0 fw-semibold text-dark small">Rp {{ number_format($alw->amount, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                            <tr>
                                <td class="px-0 text-muted small">
                                    Uang Lembur
                                    <small class="text-muted d-block">({{ floatval($overtimeHours) }} jam @ Rp
                                        {{ number_format($payroll->employee->overtime_rate, 0, ',', '.') }}/jam)</small>
                                </td>
                                <td class="text-end px-0 align-bottom fw-semibold text-dark small">Rp
                                    {{ number_format($payroll->overtime_pay, 0, ',', '.') }}</td>
                            </tr>
                            @if ($payroll->salary_adjustment_addition > 0)
                                <tr>
                                    <td class="px-0 text-muted small">
                                        Penyesuaian Penambah
                                        @if ($payroll->salary_adjustment_notes)
                                            <small class="text-muted d-block">{{ $payroll->salary_adjustment_notes }}</small>
                                        @endif
                                    </td>
                                    <td class="text-end px-0 align-bottom text-success fw-semibold small">+Rp
                                        {{ number_format($payroll->salary_adjustment_addition, 0, ',', '.') }}</td>
                                </tr>
                            @endif
                            <tr class="border-top fw-bold text-success" style="border-color: #dee2e6 !important;">
                                <td class="px-0 pt-2 small">Total Pendapatan</td>
                                <td class="text-end px-0 pt-2 small">Rp
                                    {{ number_format($payroll->basic_salary + $payroll->allowance + $payroll->overtime_pay + $payroll->salary_adjustment_addition, 0, ',', '.') }}
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Potongan -->
                <div class="col-md-6">
                    <div class="border rounded p-3 h-100 bg-light" style="border-color: #dee2e6 !important;">
                        <h6 class="border-bottom pb-2 fw-bold text-danger" style="border-color: #dee2e6 !important;"><i class="fas fa-minus-circle me-1"></i>
                            POTONGAN (DEDUCTIONS)</h6>
                        <table class="table table-borderless table-sm mb-0">
                            <tr>
                                <td class="px-0 text-muted small">
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
                                <td class="text-end px-0 align-bottom text-danger fw-semibold small">-Rp
                                    {{ number_format($payroll->cash_advance_deduction, 0, ',', '.') }}</td>
                            </tr>
                            @if ($payroll->attendance_deduction > 0)
                                <tr>
                                    <td class="px-0 text-muted small">
                                        Potongan Absensi (Izin/Alpha)
                                        <small class="text-muted d-block">(Tidak hadir: {{ $deductionHours }} jam &times;
                                            Rp {{ number_format($hourlyRate, 0, ',', '.') }}/jam)</small>
                                    </td>
                                    <td class="text-end px-0 text-danger align-bottom fw-semibold small">-Rp
                                        {{ number_format($payroll->attendance_deduction, 0, ',', '.') }}</td>
                                </tr>
                            @endif
                            @if ($payroll->late_deduction > 0)
                                <tr>
                                    <td class="px-0 text-muted small">
                                        Denda Keterlambatan
                                        @if ($totalLateDays > 0)
                                            <small class="text-muted d-block">({{ $totalLateDays }}x terlambat, total
                                                {{ $totalLateMinutes }} menit)</small>
                                        @endif
                                    </td>
                                    <td class="text-end px-0 text-danger align-bottom fw-semibold small">-Rp
                                        {{ number_format($payroll->late_deduction, 0, ',', '.') }}</td>
                                </tr>
                            @endif
                            <tr>
                                <td class="px-0 text-muted small">Potongan Lainnya</td>
                                <td class="text-end px-0 text-danger fw-semibold small">-Rp
                                    {{ number_format($payroll->other_deductions, 0, ',', '.') }}</td>
                            </tr>
                            @if ($payroll->salary_adjustment_deduction > 0)
                                <tr>
                                    <td class="px-0 text-muted small">
                                        Penyesuaian Pengurang
                                        @if ($payroll->salary_adjustment_notes && $payroll->salary_adjustment_addition == 0)
                                            <small class="text-muted d-block">{{ $payroll->salary_adjustment_notes }}</small>
                                        @endif
                                    </td>
                                    <td class="text-end px-0 align-bottom text-danger fw-semibold small">-Rp
                                        {{ number_format($payroll->salary_adjustment_deduction, 0, ',', '.') }}</td>
                                </tr>
                            @endif
                            <tr class="border-top fw-bold text-danger" style="border-color: #dee2e6 !important;">
                                <td class="px-0 pt-2 small">Total Potongan</td>
                                <td class="text-end px-0 pt-2 small">-Rp
                                    {{ number_format($payroll->cash_advance_deduction + $payroll->attendance_deduction + $payroll->late_deduction + $payroll->other_deductions + $payroll->salary_adjustment_deduction, 0, ',', '.') }}
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Gaji Bersih Diterima -->
            <div class="p-3 rounded mb-5 text-center fw-bold border"
                style="background: #f0f7ff; border-color: #b6d4fe !important;">
                <span class="text-muted d-block small mb-1">TOTAL GAJI BERSIH DITERIMA (NET SALARY)</span>
                <h2 class="mb-0 fw-bold text-primary">Rp
                    {{ number_format($payroll->net_salary, 0, ',', '.') }}</h2>
                <small class="text-muted">Terbilang: {{ terbilang($payroll->net_salary) }} Rupiah</small>
            </div>

            <!-- Tanda Tangan -->
            <div class="row pt-4 mt-5">
                <div class="col-6 text-center">
                    <small class="text-muted d-block mb-5">Diterima Oleh,</small>
                    <div class="border-bottom mx-auto" style="width: 150px; border-color: #dee2e6 !important;"></div>
                    <strong class="d-block mt-2 text-dark">{{ $payroll->employee->name }}</strong>
                </div>
                <div class="col-6 text-center">
                    <small class="text-muted d-block mb-5">Disetujui Oleh,</small>
                    <div class="border-bottom mx-auto" style="width: 150px; border-color: #dee2e6 !important;"></div>
                    <strong class="d-block mt-2 text-dark">HRD & Payroll Manager</strong>
                </div>
            </div>
        </div>
    </div>

    @if ($payroll->status === 'draft')
        <div class="card border shadow-sm mx-auto mt-4 d-print-none" style="max-width: 800px;">
            <div class="card-header bg-primary bg-opacity-10 d-flex align-items-center gap-3 p-3 border-bottom">
                <div class="bg-primary text-white rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                    style="width: 36px; height: 36px;">
                    <i class="fas fa-edit" style="font-size: 1rem;"></i>
                </div>
                <h6 class="mb-0 fw-bold text-dark">Input Penyesuaian Gaji (Kurang / Lebih Bayar)</h6>
            </div>
            <div class="card-body p-3">
                <form action="{{ route('hr.payroll.update', $payroll->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="salary_adjustment_addition" class="form-label text-dark small fw-bold">PENAMBAH GAJI (Kurang Bayar Bulan Lalu)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="salary_adjustment_addition" id="salary_adjustment_addition" class="form-control form-control-sm" 
                                    value="{{ old('salary_adjustment_addition', $payroll->salary_adjustment_addition > 0 ? round($payroll->salary_adjustment_addition) : '') }}" 
                                    placeholder="0" min="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="salary_adjustment_deduction" class="form-label text-dark small fw-bold">PENGURANG GAJI (Lebih Bayar Bulan Lalu)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="salary_adjustment_deduction" id="salary_adjustment_deduction" class="form-control form-control-sm" 
                                    value="{{ old('salary_adjustment_deduction', $payroll->salary_adjustment_deduction > 0 ? round($payroll->salary_adjustment_deduction) : '') }}" 
                                    placeholder="0" min="0">
                            </div>
                        </div>
                        <div class="col-12">
                            <label for="salary_adjustment_notes" class="form-label text-dark small fw-bold">CATATAN PENYESUAIAN</label>
                            <textarea name="salary_adjustment_notes" id="salary_adjustment_notes" class="form-control form-control-sm" rows="2" 
                                placeholder="Contoh: Kurang bayar insentif periode Mei 2026">{{ old('salary_adjustment_notes', $payroll->salary_adjustment_notes) }}</textarea>
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary btn-sm px-3 rounded-3"><i class="fas fa-save me-1"></i> Simpan Penyesuaian</button>
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
