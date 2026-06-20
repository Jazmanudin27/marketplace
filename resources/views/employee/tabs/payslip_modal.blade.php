<div id="payslipModal" class="modal-overlay" onclick="closePayslipModal(event)">
    <div class="modal-card" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3 class="modal-title">Rincian Slip Gaji</h3>
            <button class="modal-close" onclick="closePayslipModal()">&times;</button>
        </div>

        <div class="modal-body">
            <div class="payslip-wrap" id="printablePayslip">
                <div class="payslip-header-text">
                    <div class="payslip-title" id="psTenantName">{{ $employee->tenant->name ?? 'ERP Marketplace' }}
                    </div>
                    <div class="payslip-meta">SLIP GAJI KARYAWAN RESMI</div>
                    <div class="payslip-meta" id="psPeriod" style="font-weight: 700; color: white; margin-top: 4px;">
                        Periode</div>
                </div>

                <div class="payslip-info-grid">
                    <div>
                        <span
                            style="color: var(--text-secondary); display:block; font-size: 9px; text-transform: uppercase;">Nama
                            Karyawan</span>
                        <strong id="psEmployeeName" style="color: white;">-</strong>
                    </div>
                    <div>
                        <span
                            style="color: var(--text-secondary); display:block; font-size: 9px; text-transform: uppercase;">Jabatan</span>
                        <strong id="psPosition" style="color: white;">-</strong>
                    </div>
                    <div>
                        <span
                            style="color: var(--text-secondary); display:block; font-size: 9px; text-transform: uppercase;">Metode
                            Gaji</span>
                        <strong id="psSalaryType" style="color: white; text-transform: capitalize;">-</strong>
                    </div>
                    <div>
                        <span
                            style="color: var(--text-secondary); display:block; font-size: 9px; text-transform: uppercase;">Tgl
                            Pembayaran</span>
                        <strong id="psPaymentDate" style="color: white;">-</strong>
                    </div>
                </div>

                <!-- PENDAPATAN -->
                <div class="payslip-section">
                    <div class="payslip-section-title">A. Pendapatan / Penerimaan</div>
                    <div class="payslip-row">
                        <span>Gaji Pokok</span>
                        <span id="psBasicSalary">Rp 0</span>
                    </div>
                    <div class="payslip-row" id="psHoursWorkedRow" style="display:none;">
                        <span>Jumlah Jam Kerja</span>
                        <span id="psHoursWorked">0 jam</span>
                    </div>
                    <div class="payslip-row">
                        <span>Tunjangan (Master)</span>
                        <span id="psAllowance">Rp 0</span>
                    </div>

                    <!-- Dynamic Allowances Breakdown -->
                    <div id="psDynamicAllowancesBox"></div>

                    <div class="payslip-row">
                        <span>Uang Lembur</span>
                        <span id="psOvertimePay">Rp 0</span>
                    </div>
                    <div class="payslip-row" id="psAdjustmentAdditionRow" style="display:none;">
                        <span>Penyesuaian Penambah</span>
                        <span id="psAdjustmentAddition" style="color: var(--color-success);">Rp 0</span>
                    </div>
                    <div class="payslip-row total">
                        <span>Total Penerimaan Kotor</span>
                        <span id="psTotalGross">Rp 0</span>
                    </div>
                </div>

                <!-- POTONGAN -->
                <div class="payslip-section">
                    <div class="payslip-section-title">B. Potongan / Pemotongan</div>
                    <div class="payslip-row">
                        <span>Potongan Kasbon</span>
                        <span id="psCashAdvanceDeduction" style="color: var(--color-danger);">Rp 0</span>
                    </div>
                    <div class="payslip-row">
                        <span>Potongan Absensi</span>
                        <span id="psAttendanceDeduction" style="color: var(--color-danger);">Rp 0</span>
                    </div>
                    <div class="payslip-row">
                        <span>Denda Keterlambatan</span>
                        <span id="psLateDeduction" style="color: var(--color-danger);">Rp 0</span>
                    </div>
                    <div class="payslip-row">
                        <span>Potongan Lainnya</span>
                        <span id="psOtherDeductions" style="color: var(--color-danger);">Rp 0</span>
                    </div>
                    <div class="payslip-row" id="psAdjustmentDeductionRow" style="display:none;">
                        <span>Penyesuaian Pengurang</span>
                        <span id="psAdjustmentDeduction" style="color: var(--color-danger);">Rp 0</span>
                    </div>
                    <div class="payslip-row total">
                        <span>Total Potongan</span>
                        <span id="psTotalDeductions" style="color: var(--color-danger);">Rp 0</span>
                    </div>
                </div>

                <div class="payslip-section" id="psNotesSection"
                    style="display:none; margin-top: 12px; border-top: 1px dashed var(--border-color); padding-top: 10px;">
                    <div class="payslip-section-title" style="color: var(--text-secondary); font-size: 10px;">Catatan
                        Penyesuaian</div>
                    <p id="psAdjustmentNotes"
                        style="font-size: 11px; color: var(--text-secondary); line-height: 1.4; margin: 4px 0 0; font-style: italic;">
                    </p>
                </div>

                <!-- TOTAL BERSIH -->
                <div class="payslip-row grand-total">
                    <span>GAJI BERSIH (NETTO)</span>
                    <span id="psNetSalary">Rp 0</span>
                </div>
            </div>
        </div>

        <div class="modal-actions">
            <button class="btn-modal btn-modal-close" onclick="closePayslipModal()">Tutup</button>
            <button class="btn-modal btn-modal-print" onclick="window.print()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                    <rect x="6" y="14" width="12" height="8"></rect>
                </svg>
                Cetak Slip
            </button>
        </div>
    </div>
</div>
