<div id="tab-gaji" class="tab-pane">
    <div class="glass-card">
        <div class="section-title">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="4" width="20" height="16" rx="2" ry="2"></rect>
                <line x1="12" y1="4" x2="12" y2="20"></line>
            </svg>
            Daftar Gaji Bulanan Anda
        </div>

        @if ($payrolls->isEmpty())
            <div style="text-align: center; color: var(--text-muted); padding: 30px 0; font-size: 13px;">
                Belum ada data slip gaji yang dirilis oleh admin HRD.
            </div>
        @else
            <div class="list-wrapper">
                @foreach ($payrolls as $pr)
                    @php
                        $statusLabels = [
                            'paid' => 'Dibayar',
                            'unpaid' => 'Belum Dibayar',
                        ];
                    @endphp
                    <div class="item-card">
                        <div class="item-left">
                            <div class="item-date-badge" style="background: rgba(16, 185, 129, 0.05);">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" style="color: var(--color-success);">
                                    <line x1="12" y1="1" x2="12" y2="23"></line>
                                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="item-title">{{ formatPeriodStr($pr->period, $monthNamesIndo) }}</div>
                                <div class="item-subtext">
                                    Gaji Bersih: <strong>Rp {{ number_format($pr->net_salary, 0, ',', '.') }}</strong>
                                </div>
                            </div>
                        </div>
                        <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 6px;">
                            <span class="status-pill {{ $pr->status }}" style="font-size: 10px;">
                                {{ $statusLabels[$pr->status] ?? ucfirst($pr->status) }}
                            </span>
                            <button type="button" class="btn-detail-payslip"
                                onclick='openPayslip({{ json_encode($pr) }})'>
                                Detail Slip
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
