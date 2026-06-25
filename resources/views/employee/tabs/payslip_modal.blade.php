<div class="modal fade" id="payslipModal" tabindex="-1" aria-labelledby="payslipModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content overflow-hidden">
            <div class="modal-header d-flex align-items-center gap-3 p-3 bg-primary bg-opacity-10 border-bottom">
                <div class="bg-primary text-white rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 p-2 fs-5" style="width: 40px; height: 40px;">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <div class="flex-grow-1">
                    <h5 class="modal-title fw-bold fs-6 mb-0 text-dark" id="payslipModalLabel">Rincian Slip Gaji</h5>
                    <p class="mb-0 text-muted small">Rincian penerimaan dan potongan gaji</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <div id="printablePayslip" class="p-3 border rounded bg-white">
                    <div class="text-center mb-3 pb-2 border-bottom">
                        <h6 class="fw-bold text-dark mb-0" id="psTenantName">{{ $employee->tenant->name ?? 'ERP Marketplace' }}</h6>
                        <small class="text-muted text-uppercase tracking-wider" style="font-size: 0.68rem; letter-spacing: 0.05em;">SLIP GAJI KARYAWAN RESMI</small>
                        <div class="fw-bold text-primary small mt-1" id="psPeriod">Periode</div>
                    </div>

                    <div class="row g-2 mb-3 bg-light p-2.5 rounded text-center small border">
                        <div class="col-6 mb-1 text-start">
                            <span class="text-muted d-block" style="font-size: 9px; text-transform: uppercase;">Nama Karyawan</span>
                            <strong id="psEmployeeName" class="text-dark small">-</strong>
                        </div>
                        <div class="col-6 mb-1 text-start">
                            <span class="text-muted d-block" style="font-size: 9px; text-transform: uppercase;">Jabatan</span>
                            <strong id="psPosition" class="text-dark small">-</strong>
                        </div>
                        <div class="col-6 text-start">
                            <span class="text-muted d-block" style="font-size: 9px; text-transform: uppercase;">Metode Gaji</span>
                            <strong id="psSalaryType" class="text-dark small text-capitalize">-</strong>
                        </div>
                        <div class="col-6 text-start">
                            <span class="text-muted d-block" style="font-size: 9px; text-transform: uppercase;">Tgl Pembayaran</span>
                            <strong id="psPaymentDate" class="text-dark small">-</strong>
                        </div>
                    </div>

                    <!-- PENDAPATAN -->
                    <div class="mb-3">
                        <div class="fw-bold text-dark small mb-2 border-bottom pb-1">A. Pendapatan / Penerimaan</div>
                        <div class="d-flex justify-content-between py-1 small border-bottom border-light">
                            <span class="text-secondary">Gaji Pokok</span>
                            <span id="psBasicSalary" class="text-dark fw-semibold">Rp 0</span>
                        </div>
                        <div class="d-flex justify-content-between py-1 small border-bottom border-light" id="psHoursWorkedRow" style="display:none;">
                            <span class="text-secondary">Jumlah Jam Kerja</span>
                            <span id="psHoursWorked" class="text-dark fw-semibold">0 jam</span>
                        </div>
                        <div class="d-flex justify-content-between py-1 small border-bottom border-light">
                            <span class="text-secondary">Tunjangan (Master)</span>
                            <span id="psAllowance" class="text-dark fw-semibold">Rp 0</span>
                        </div>

                        <!-- Dynamic Allowances Breakdown -->
                        <div id="psDynamicAllowancesBox"></div>

                        <div class="d-flex justify-content-between py-1 small border-bottom border-light">
                            <span class="text-secondary">Uang Lembur</span>
                            <span id="psOvertimePay" class="text-dark fw-semibold">Rp 0</span>
                        </div>
                        <div class="d-flex justify-content-between py-1 small border-bottom border-light" id="psAdjustmentAdditionRow" style="display:none;">
                            <span class="text-secondary">Penyesuaian Penambah</span>
                            <span id="psAdjustmentAddition" class="text-success fw-semibold">Rp 0</span>
                        </div>
                        <div class="d-flex justify-content-between py-1.5 fw-bold text-dark border-bottom small bg-light px-2 rounded-2 mt-1">
                            <span>Total Penerimaan Kotor</span>
                            <span id="psTotalGross">Rp 0</span>
                        </div>
                    </div>

                    <!-- POTONGAN -->
                    <div class="mb-3">
                        <div class="fw-bold text-dark small mb-2 border-bottom pb-1">B. Potongan / Pemotongan</div>
                        <div class="d-flex justify-content-between py-1 small border-bottom border-light">
                            <span class="text-secondary">Potongan Kasbon</span>
                            <span id="psCashAdvanceDeduction" class="text-danger fw-semibold">Rp 0</span>
                        </div>
                        <div class="d-flex justify-content-between py-1 small border-bottom border-light">
                            <span class="text-secondary">Potongan Absensi</span>
                            <span id="psAttendanceDeduction" class="text-danger fw-semibold">Rp 0</span>
                        </div>
                        <div class="d-flex justify-content-between py-1 small border-bottom border-light">
                            <span class="text-secondary">Denda Keterlambatan</span>
                            <span id="psLateDeduction" class="text-danger fw-semibold">Rp 0</span>
                        </div>
                        <div class="d-flex justify-content-between py-1 small border-bottom border-light">
                            <span class="text-secondary">Potongan Lainnya</span>
                            <span id="psOtherDeductions" class="text-danger fw-semibold">Rp 0</span>
                        </div>
                        <div class="d-flex justify-content-between py-1 small border-bottom border-light" id="psAdjustmentDeductionRow" style="display:none;">
                            <span class="text-secondary">Penyesuaian Pengurang</span>
                            <span id="psAdjustmentDeduction" class="text-danger fw-semibold">Rp 0</span>
                        </div>
                        <div class="d-flex justify-content-between py-1.5 fw-bold text-danger border-bottom small bg-light px-2 rounded-2 mt-1">
                            <span>Total Potongan</span>
                            <span id="psTotalDeductions">Rp 0</span>
                        </div>
                    </div>

                    <div id="psNotesSection" style="display:none;" class="mt-2 border-top border-dashed pt-2">
                        <div class="text-muted small" style="font-size: 10px; font-weight: 600;">Catatan Penyesuaian</div>
                        <p id="psAdjustmentNotes" class="text-muted fst-italic mb-0 small mt-1" style="font-size: 11px; line-height: 1.4;"></p>
                    </div>

                    <!-- TOTAL BERSIH -->
                    <div class="d-flex justify-content-between py-2 border-top border-dark border-2 fw-bold text-primary mt-3" style="font-size: 1rem;">
                        <span>GAJI BERSIH (NETTO)</span>
                        <span id="psNetSalary">Rp 0</span>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light px-4 py-3 d-flex justify-content-between border-top">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary btn-sm px-3" onclick="window.print()">
                    <i class="fas fa-print me-1"></i> Cetak Slip
                </button>
            </div>
        </div>
    </div>
</div>
