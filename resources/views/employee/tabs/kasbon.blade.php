<div id="tab-kasbon" class="tab-pane">
    <div class="card border shadow-sm mb-3">
        <div class="card-header bg-light py-2.5 px-3 border-bottom">
            <h6 class="m-0 fw-bold text-primary">
                <i class="fas fa-hand-holding-usd me-2"></i> Total Kasbon Disetujui
            </h6>
        </div>
        @php
            $totalKasbonDisetujui = $cashAdvances->where('status', 'approved')->sum('amount');
        @endphp
        <div class="card-body p-3">
            <div class="fw-bold text-primary display-6 mb-2" style="font-size: 1.85rem;">
                Rp {{ number_format($totalKasbonDisetujui, 0, ',', '.') }}
            </div>
            <div class="text-muted small">
                Akumulasi kasbon bulan-bulan aktif yang akan/sudah dipotong dari slip gaji.
            </div>
        </div>
    </div>

    <div class="card border shadow-sm">
        <div class="card-header bg-light py-2.5 px-3 border-bottom">
            <h6 class="m-0 fw-bold text-primary">
                <i class="fas fa-list me-2"></i> Riwayat Pengajuan Kasbon
            </h6>
        </div>

        <div class="card-body p-3">
            @if ($cashAdvances->isEmpty())
                <div class="text-center text-muted py-4 small">
                    Belum ada riwayat pengajuan kasbon.
                </div>
            @else
                <div class="d-flex flex-column gap-3">
                    @foreach ($cashAdvances as $ca)
                        @php
                            $d = \Carbon\Carbon::parse($ca->date);
                            $statusLabels = [
                                'pending' => 'Menunggu',
                                'approved' => 'Disetujui',
                                'rejected' => 'Ditolak',
                            ];
                            $statusBadgeClasses = [
                                'pending' => 'bg-warning text-dark',
                                'approved' => 'bg-success bg-opacity-10 text-success border border-success border-opacity-25',
                                'rejected' => 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25',
                            ];
                        @endphp
                        <div class="d-flex justify-content-between align-items-center p-3 border rounded bg-light">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-primary bg-opacity-10 text-primary rounded p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="fas fa-file-invoice-dollar"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark small">Rp {{ number_format($ca->amount, 0, ',', '.') }}</div>
                                    <div class="text-muted small mt-1">
                                        Diajukan: {{ $d->format('d M Y') }}
                                        @if ($ca->notes)
                                            <span class="d-block fst-italic text-muted mt-1" style="font-size: 0.78rem;">
                                                "{{ $ca->notes }}"
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <span class="badge {{ $statusBadgeClasses[$ca->status] ?? 'bg-secondary' }} small">
                                {{ $statusLabels[$ca->status] ?? ucfirst($ca->status) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
