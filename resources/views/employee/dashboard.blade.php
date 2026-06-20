@extends('employee.layout')

@section('title', 'Dashboard Karyawan — Presensi & Keuangan')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/employee-dashboard.css') }}">
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

    <!-- Mockup Company Header -->
    <div class="company-header">
        <div class="company-left">
            <div class="company-logo-box">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                    stroke-linecap="round" stroke-linejoin="round" style="color: #ff4757;">
                    <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                    <path d="M2 17l10 5 10-5"></path>
                    <path d="M2 12l10 5 10-5"></path>
                </svg>
            </div>
            <span class="company-name">{{ $employee->tenant->name ?? 'PT. Geprek Miber' }}</span>
        </div>
        <div class="company-right">
            <span class="badge-lang">ID</span>
            <span class="badge-status-online">
                <span class="status-dot-pulse"></span>
                Online
            </span>
        </div>
    </div>

    <!-- Mockup User Header -->
    <div class="user-header">
        <div class="user-left">
            <div class="user-avatar-box">
                {{ strtoupper(substr($employee->name, 0, 1)) }}
            </div>
            <div class="user-info-text">
                <h3>{{ $employee->name }}</h3>
                <p>{{ $employee->position ?: 'Supervisor' }} &bull; {{ $employee->username }}</p>
            </div>
        </div>
        <button class="btn-mail-icon" type="button" onclick="switchTab('riwayat')" title="Notifikasi Kehadiran">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                <polyline points="22,6 12,13 2,6"></polyline>
            </svg>
        </button>
    </div>

    <!-- Flash Messages -->
    @if (session('success'))
        <div class="alert alert-success">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" style="align-items: flex-start;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                stroke-linecap="round" stroke-linejoin="round" style="margin-top: 2px; flex-shrink: 0;">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <div style="flex: 1; padding-left: 8px;">
                <ul style="margin: 0; padding-left: 16px; font-size: 13px; text-align: left; line-height: 1.5;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <!-- Tab Navigation Menu -->
    <div class="tab-nav">
        <button class="tab-btn active" onclick="switchTab('presensi')">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
            Presensi
        </button>
        <button class="tab-btn" onclick="switchTab('riwayat')">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
            Riwayat
        </button>
        <button class="tab-btn" onclick="switchTab('izin')">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="9" y1="15" x2="15" y2="15"></line>
                <line x1="9" y1="11" x2="15" y2="11"></line>
                <line x1="9" y1="18" x2="15" y2="18"></line>
            </svg>
            Izin/Cuti
        </button>
        <button class="tab-btn" onclick="switchTab('kasbon')">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="1" x2="12" y2="23"></line>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
            </svg>
            Kasbon
        </button>
        <button class="tab-btn" onclick="switchTab('gaji')">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="4" width="20" height="16" rx="2" ry="2"></rect>
                <line x1="12" y1="4" x2="12" y2="20"></line>
            </svg>
            Slip Gaji
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
                    row.className = 'payslip-row';
                    row.style.paddingLeft = '12px';
                    row.style.fontSize = '11.5px';
                    row.style.color = '#9ca3af';
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
            const modal = document.getElementById('payslipModal');
            modal.style.display = 'flex';
        }

        function closePayslipModal(event) {
            document.getElementById('payslipModal').style.display = 'none';
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
                clockEl.innerHTML = `${hrs}:${mins}<span class="clock-seconds">${secs}</span>`;
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
