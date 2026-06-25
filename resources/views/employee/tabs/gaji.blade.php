<div id="tab-gaji" class="tab-pane">
    <div class="card border shadow-sm">
        <div class="card-header bg-light py-2.5 px-3 border-bottom">
            <h6 class="m-0 fw-bold text-primary">
                <i class="fas fa-file-invoice-dollar me-2"></i> Daftar Gaji Bulanan Anda
            </h6>
        </div>

        <div class="card-body p-3">
            @if ($payrolls->isEmpty())
                <div class="text-center text-muted py-4 small">
                    Belum ada data slip gaji yang dirilis oleh admin HRD.
                </div>
            @else
                <div class="d-flex flex-column gap-3">
                    @foreach ($payrolls as $pr)
                        @php
                            $statusLabels = [
                                'paid' => 'Dibayar',
                                'unpaid' => 'Belum Dibayar',
                            ];
                        @endphp
                        <div class="d-flex justify-content-between align-items-center p-3 border rounded bg-light">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-success bg-opacity-10 text-success rounded p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark small">{{ formatPeriodStr($pr->period, $monthNamesIndo) }}</div>
                                    <div class="text-muted small mt-1">
                                        Gaji Bersih: <strong class="text-success">Rp {{ number_format($pr->net_salary, 0, ',', '.') }}</strong>
                                    </div>
                                </div>
                            </div>
                            <div class="text-end d-flex flex-column align-items-end gap-2">
                                <span class="badge {{ $pr->status === 'paid' ? 'bg-success bg-opacity-10 text-success' : 'bg-warning text-dark' }} small">
                                    {{ $statusLabels[$pr->status] ?? ucfirst($pr->status) }}
                                </span>
                                <button type="button" class="btn btn-primary btn-sm py-1 px-3" onclick='openPayslip({{ json_encode($pr) }})'>
                                    Detail Slip
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
