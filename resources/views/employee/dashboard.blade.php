@extends('employee.layout')

@section('title', 'Dashboard Karyawan — Presensi & Keuangan')

@section('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
@endsection

@section('content')

    @php
        $monthNamesIndo = [
            '01' => 'Januari',
            '02' => 'Februari',
            '03' => 'Maret',
            '04' => 'April',
            '05' => 'Mei',
            '06' => 'Juni',
            '07' => 'Juli',
            '08' => 'Agustus',
            '09' => 'September',
            '10' => 'Oktober',
            '11' => 'November',
            '12' => 'Desember',
        ];

        $monthShortIndo = [
            1 => 'Jan',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Apr',
            5 => 'Mei',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Agu',
            9 => 'Sep',
            10 => 'Okt',
            11 => 'Nov',
            12 => 'Des',
        ];

        $dayNamesIndo = [
            'Sun' => 'Minggu',
            'Mon' => 'Senin',
            'Tue' => 'Selasa',
            'Wed' => 'Rabu',
            'Thu' => 'Kamis',
            'Fri' => 'Jumat',
            'Sat' => 'Sabtu',
        ];

        function formatPeriodStr($period, $months)
        {
            $parts = explode('-', $period);
            if (count($parts) === 2) {
                return ($months[$parts[1]] ?? '') . ' ' . $parts[0];
            }
            return $period;
        }

        $lateHours = floor($stats['late_minutes'] / 60);
        $lateMins = $stats['late_minutes'] % 60;
    @endphp

    <!-- Company Header -->
    <div class="d-flex justify-content-between align-items-center bg-white p-3 border rounded shadow-sm mb-3">
        <div class="d-flex align-items-center gap-2">
            <div class="bg-danger bg-opacity-10 p-2 rounded text-danger d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                <i class="fas fa-layer-group"></i>
            </div>
            <span class="fw-bold text-dark small">{{ $employee->tenant->name ?? 'PT. Geprek Miber' }}</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-dark border">ID</span>
            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 d-flex align-items-center gap-1.5 py-1.5 px-2">
                <span class="spinner-grow spinner-grow-sm text-success" role="status" style="width: 8px; height: 8px;"></span>
                Online
            </span>
        </div>
    </div>

    <!-- User Header -->
    <div class="d-flex justify-content-between align-items-center bg-white p-3 border rounded shadow-sm mb-3">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold fs-4 flex-shrink-0" style="width: 48px; height: 48px;">
                {{ strtoupper(substr($employee->name, 0, 1)) }}
            </div>
            <div>
                <h5 class="fw-bold text-dark mb-0" style="font-size: 1.05rem;">{{ $employee->name }}</h5>
                <p class="text-muted small mb-0">{{ $employee->position ?: 'Supervisor' }} &bull; {{ $employee->username }}</p>
            </div>
        </div>
        <button class="btn btn-outline-secondary btn-sm p-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;" type="button" onclick="switchTab('riwayat')" title="Notifikasi Kehadiran">
            <i class="fas fa-envelope"></i>
        </button>
    </div>

    <!-- Flash Messages -->
    @if (session('success'))
        <div class="alert alert-success d-flex align-items-center gap-2 small py-2 mb-3" role="alert">
            <i class="fas fa-check-circle"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger d-flex align-items-center gap-2 small py-2 mb-3" role="alert">
            <i class="fas fa-exclamation-circle"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2 small py-2 mb-3" role="alert">
            <i class="fas fa-exclamation-triangle mt-1 flex-shrink-0"></i>
            <div class="flex-grow-1">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <!-- Tab Navigation Menu -->
    <div class="nav nav-pills d-flex bg-white p-1.5 border rounded shadow-sm justify-content-between gap-1 mb-3" role="tablist">
        <button class="nav-link active flex-fill py-2 text-center tab-btn" onclick="switchTab('presensi')" style="font-size: 0.85rem;">
            <i class="fas fa-clock d-block mb-1 fs-5"></i> Presensi
        </button>
        <button class="nav-link flex-fill py-2 text-center tab-btn" onclick="switchTab('riwayat')" style="font-size: 0.85rem;">
            <i class="fas fa-calendar-alt d-block mb-1 fs-5"></i> Riwayat
        </button>
        <button class="nav-link flex-fill py-2 text-center tab-btn" onclick="switchTab('izin')" style="font-size: 0.85rem;">
            <i class="fas fa-file-alt d-block mb-1 fs-5"></i> Izin/Cuti
        </button>
        <button class="nav-link flex-fill py-2 text-center tab-btn" onclick="switchTab('kasbon')" style="font-size: 0.85rem;">
            <i class="fas fa-hand-holding-usd d-block mb-1 fs-5"></i> Kasbon
        </button>
        <button class="nav-link flex-fill py-2 text-center tab-btn" onclick="switchTab('gaji')" style="font-size: 0.85rem;">
            <i class="fas fa-file-invoice-dollar d-block mb-1 fs-5"></i> Slip Gaji
        </button>
    </div>

    @include('employee.tabs.presensi')
    @include('employee.tabs.riwayat')
    @include('employee.tabs.izin')
    @include('employee.tabs.kasbon')
    @include('employee.tabs.gaji')
    @include('employee.tabs.payslip_modal')

@endsection

@section('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        // Tab switching system
        function switchTab(tabId) {
            // Hide all panes
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('active');
            });

            // Deactivate all tab buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            // Show active pane
            const targetPane = document.getElementById('tab-' + tabId);
            if (targetPane) {
                targetPane.classList.add('active');
            }

            // Highlight active button
            const clickedBtn = Array.from(document.querySelectorAll('.tab-btn')).find(btn => {
                return btn.getAttribute('onclick').includes(tabId);
            });
            if (clickedBtn) {
                clickedBtn.classList.add('active');
            }

            // Store active tab state in localStorage to persist on reload
            localStorage.setItem('activeEmployeeTab', tabId);
        }

        // Persist active tab on reload
        document.addEventListener('DOMContentLoaded', () => {
            const savedTab = localStorage.getItem('activeEmployeeTab');
            if (savedTab && ['presensi', 'riwayat', 'izin', 'kasbon', 'gaji'].includes(savedTab)) {
                switchTab(savedTab);
            }
        });

        // Helper to format IDR in Javascript
        function formatRupiah(amount) {
            return 'Rp ' + Number(amount).toLocaleString('id-ID', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            });
        }

        // Date/Month translation maps for JS
        const MONTH_NAMES_INDO = {
            '01': 'Januari',
            '02': 'Februari',
            '03': 'Maret',
            '04': 'April',
            '05': 'Mei',
            '06': 'Juni',
            '07': 'Juli',
            '08': 'Agustus',
            '09': 'September',
            '10': 'Oktober',
            '11': 'November',
            '12': 'Desember'
        };

        function parsePeriodToIndo(period) {
            const parts = period.split('-');
            if (parts.length === 2) {
                return (MONTH_NAMES_INDO[parts[1]] || '') + ' ' + parts[0];
            }
            return period;
        }

        // Modal Payslip Controller
        function openPayslip(payroll) {
            // Basic Info
            document.getElementById('psPeriod').textContent = parsePeriodToIndo(payroll.period);
            document.getElementById('psEmployeeName').textContent = "{{ $employee->name }}";
            document.getElementById('psPosition').textContent = "{{ $employee->position ?: 'Karyawan' }}";
            document.getElementById('psSalaryType').textContent = payroll.salary_type === 'hourly' ? 'Harian/Jam' :
                'Bulanan';

            const payDate = payroll.payment_date ? new Date(payroll.payment_date) : null;
            document.getElementById('psPaymentDate').textContent = payDate ?
                payDate.toLocaleDateString('id-ID', {
                    day: '2-digit',
                    month: 'long',
                    year: 'numeric'
                }) :
                '-';

            // Earnings
            const basic = Number(payroll.basic_salary);
            const allowance = Number(payroll.allowance);
            const overtime = Number(payroll.overtime_pay);

            document.getElementById('psBasicSalary').textContent = formatRupiah(basic);
            document.getElementById('psAllowance').textContent = formatRupiah(allowance);
            document.getElementById('psOvertimePay').textContent = formatRupiah(overtime);

            // Hourly check
            const hoursWorkedRow = document.getElementById('psHoursWorkedRow');
            if (payroll.salary_type === 'hourly') {
                hoursWorkedRow.style.display = 'flex';
                document.getElementById('psHoursWorked').textContent = parseFloat(payroll.hours_worked) + ' jam';
            } else {
                hoursWorkedRow.style.display = 'none';
            }

            // Render dynamic allowances if loaded
            const dynamicBox = document.getElementById('psDynamicAllowancesBox');
            dynamicBox.innerHTML = '';
            let totalDynamic = 0;

            if (payroll.allowances && payroll.allowances.length > 0) {
                payroll.allowances.forEach(allow => {
                    const amt = Number(allow.amount);
                    totalDynamic += amt;
                    const row = document.createElement('div');
                    row.className = 'd-flex justify-content-between py-1 border-bottom border-light';
                    row.style.paddingLeft = '12px';
                    row.style.fontSize = '11.5px';
                    row.style.color = '#6c757d';
                    row.innerHTML = `<span>+ Tunj. ${allow.name}</span> <span>${formatRupiah(amt)}</span>`;
                    dynamicBox.appendChild(row);
                });
            }

            const adjustmentAddition = Number(payroll.salary_adjustment_addition || 0);
            const adjustmentDeduction = Number(payroll.salary_adjustment_deduction || 0);

            // Addition
            const adjAdditionRow = document.getElementById('psAdjustmentAdditionRow');
            if (adjustmentAddition > 0) {
                adjAdditionRow.style.display = 'flex';
                document.getElementById('psAdjustmentAddition').textContent = '+' + formatRupiah(adjustmentAddition);
            } else {
                adjAdditionRow.style.display = 'none';
            }

            // Deduction
            const adjDeductionRow = document.getElementById('psAdjustmentDeductionRow');
            if (adjustmentDeduction > 0) {
                adjDeductionRow.style.display = 'flex';
                document.getElementById('psAdjustmentDeduction').textContent = '-' + formatRupiah(adjustmentDeduction);
            } else {
                adjDeductionRow.style.display = 'none';
            }

            // Notes
            const notesSection = document.getElementById('psNotesSection');
            if (payroll.salary_adjustment_notes) {
                notesSection.style.display = 'block';
                document.getElementById('psAdjustmentNotes').textContent = payroll.salary_adjustment_notes;
            } else {
                notesSection.style.display = 'none';
            }

            const totalGross = basic + allowance + overtime + totalDynamic + adjustmentAddition;
            document.getElementById('psTotalGross').textContent = formatRupiah(totalGross);

            // Deductions
            const cashDeduct = Number(payroll.cash_advance_deduction);
            const attDeduct = Number(payroll.attendance_deduction);
            const lateDeduct = Number(payroll.late_deduction);
            const otherDeduct = Number(payroll.other_deductions);
            const totalDeducts = cashDeduct + attDeduct + lateDeduct + otherDeduct + adjustmentDeduction;

            document.getElementById('psCashAdvanceDeduction').textContent = formatRupiah(cashDeduct);
            document.getElementById('psAttendanceDeduction').textContent = formatRupiah(attDeduct);
            document.getElementById('psLateDeduction').textContent = formatRupiah(lateDeduct);
            document.getElementById('psOtherDeductions').textContent = formatRupiah(otherDeduct);
            document.getElementById('psTotalDeductions').textContent = formatRupiah(totalDeducts);

            // Net Salary
            document.getElementById('psNetSalary').textContent = formatRupiah(payroll.net_salary);

            // Open modal
            const modalElement = document.getElementById('payslipModal');
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        }

        // Live Clock precision
        const DAYS = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        const MONTHS = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober',
            'November', 'Desember'
        ];

        function tick() {
            const now = new Date();
            const hrs = String(now.getHours()).padStart(2, '0');
            const mins = String(now.getMinutes()).padStart(2, '0');
            const secs = String(now.getSeconds()).padStart(2, '0');

            const clockEl = document.getElementById('liveClock');
            if (clockEl) {
                clockEl.innerHTML = `${hrs}:${mins}<span class="small ms-1 text-muted" style="font-size:0.6em;">${secs}</span>`;
            }

            const dateEl = document.getElementById('liveDate');
            if (dateEl) {
                dateEl.textContent =
                    `${DAYS[now.getDay()]}, ${now.getDate()} ${MONTHS[now.getMonth()]} ${now.getFullYear()}`;
            }
        }

        tick();
        setInterval(tick, 1000);

        // Prevent Double form submit
        function disableSubmitButton(form) {
            form.querySelectorAll('button[type=submit]').forEach(btn => {
                btn.disabled = true;
                btn.innerHTML = 'Memproses...';
            });
        }
    </script>
@endsection
