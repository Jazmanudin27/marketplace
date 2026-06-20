<div id="tab-kasbon" class="tab-pane">
    <div class="glass-card" style="margin-bottom: 20px;">
        <div class="section-title">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="1" x2="12" y2="23"></line>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
            </svg>
            Total Kasbon Disetujui
        </div>
        @php
            $totalKasbonDisetujui = $cashAdvances->where('status', 'approved')->sum('amount');
        @endphp
        <div style="font-size: 28px; font-weight: 800; font-family: var(--font-display); color: var(--color-primary);">
            Rp {{ number_format($totalKasbonDisetujui, 0, ',', '.') }}
        </div>
        <div style="font-size: 12px; color: var(--text-secondary); margin-top: 4px;">
            Akumulasi kasbon bulan-bulan aktif yang akan/sudah dipotong dari slip gaji.
        </div>
    </div>

    <div class="glass-card">
        <div class="section-title">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="8" y1="6" x2="21" y2="6"></line>
                <line x1="8" y1="12" x2="21" y2="12"></line>
                <line x1="8" y1="18" x2="21" y2="18"></line>
                <line x1="3" y1="6" x2="3.01" y2="6"></line>
                <line x1="3" y1="12" x2="3.01" y2="12"></line>
                <line x1="3" y1="18" x2="3.01" y2="18"></line>
            </svg>
            Riwayat Pengajuan Kasbon
        </div>

        @if ($cashAdvances->isEmpty())
            <div style="text-align: center; color: var(--text-muted); padding: 30px 0; font-size: 13px;">
                Belum ada riwayat pengajuan kasbon.
            </div>
        @else
            <div class="list-wrapper">
                @foreach ($cashAdvances as $ca)
                    @php
                        $d = \Carbon\Carbon::parse($ca->date);
                        $statusLabels = [
                            'pending' => 'Menunggu',
                            'approved' => 'Disetujui',
                            'rejected' => 'Ditolak',
                        ];
                    @endphp
                    <div class="item-card">
                        <div class="item-left">
                            <div class="item-date-badge" style="background: rgba(124, 106, 247, 0.05);">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" style="color: var(--color-primary);">
                                    <rect x="2" y="4" width="20" height="16" rx="2" ry="2">
                                    </rect>
                                    <line x1="12" y1="4" x2="12" y2="20"></line>
                                </svg>
                            </div>
                            <div>
                                <div class="item-title">Rp {{ number_format($ca->amount, 0, ',', '.') }}</div>
                                <div class="item-subtext">
                                    Diajukan: {{ $d->format('d M Y') }}
                                    @if ($ca->notes)
                                        <span
                                            style="display: block; font-style: italic; margin-top: 2px; color: var(--text-muted);">
                                            "{{ $ca->notes }}"
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <span class="status-pill {{ $ca->status }}">
                            {{ $statusLabels[$ca->status] ?? ucfirst($ca->status) }}
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
