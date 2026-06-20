<div id="tab-izin" class="tab-pane">
    <!-- Form Pengajuan Izin/Sakit/Cuti -->
    <div class="glass-card" style="margin-bottom: 20px;">
        <div class="section-title">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="9" y1="15" x2="15" y2="15"></line>
                <line x1="9" y1="11" x2="15" y2="11"></line>
                <line x1="9" y1="18" x2="15" y2="18"></line>
            </svg>
            Form Pengajuan Izin / Sakit / Cuti
        </div>

        <form action="{{ route('employee.leaves.store') }}" method="POST" onsubmit="disableSubmitButton(this)">
            @csrf

            <div class="form-group">
                <label for="type">Jenis Pengajuan</label>
                <select name="type" id="type" class="form-control-custom" required>
                    <option value="permission">Izin</option>
                    <option value="sick">Sakit</option>
                    <option value="leave">Cuti</option>
                </select>
            </div>

            <div class="form-row-dates" style="margin-bottom: 16px;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="start_date">Tanggal Mulai</label>
                    <input type="date" name="start_date" id="start_date" class="form-control-custom"
                        value="{{ today()->toDateString() }}" required>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="end_date">Tanggal Selesai</label>
                    <input type="date" name="end_date" id="end_date" class="form-control-custom"
                        value="{{ today()->toDateString() }}" required>
                </div>
            </div>

            <div class="form-group">
                <label for="notes">Keterangan / Alasan</label>
                <textarea name="notes" id="notes" class="form-control-custom" rows="2"
                    placeholder="Contoh: Mengikuti acara keluarga, sakit demam, dll" required></textarea>
            </div>

            <button type="submit" class="btn-submit-custom">
                Kirim Pengajuan
            </button>
        </form>
    </div>

    <!-- Riwayat Pengajuan Izin/Sakit/Cuti -->
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
            Riwayat Pengajuan
        </div>

        @if ($leaveRequests->isEmpty())
            <div style="text-align: center; color: var(--text-muted); padding: 30px 0; font-size: 13px;">
                Belum ada riwayat pengajuan izin/sakit/cuti.
            </div>
        @else
            <div class="list-wrapper">
                @foreach ($leaveRequests as $lr)
                    @php
                        $start = \Carbon\Carbon::parse($lr->start_date);
                        $end = \Carbon\Carbon::parse($lr->end_date);
                        $diff = $start->diffInDays($end) + 1;

                        $typeLabels = [
                            'sick' => 'Sakit',
                            'permission' => 'Izin',
                            'leave' => 'Cuti',
                        ];

                        $statusLabels = [
                            'pending' => 'Menunggu',
                            'approved' => 'Disetujui',
                            'rejected' => 'Ditolak',
                        ];

                        $typeColors = [
                            'sick' => '#ef4444',
                            'permission' => '#7c6af7',
                            'leave' => '#f59e0b',
                        ];
                    @endphp
                    <div class="item-card">
                        <div class="item-left">
                            <div class="item-date-badge"
                                style="background: rgba(255, 255, 255, 0.03); border-color: {{ $typeColors[$lr->type] ?? 'var(--border-color)' }};">
                                <span
                                    style="font-size: 11px; font-weight: 700; color: {{ $typeColors[$lr->type] ?? 'white' }};">
                                    {{ $diff }}
                                </span>
                                <span
                                    style="font-size: 7.5px; text-transform: uppercase; color: var(--text-secondary); margin-top: 1px;">
                                    Hari
                                </span>
                            </div>
                            <div>
                                <div class="item-title">
                                    {{ $typeLabels[$lr->type] ?? ucfirst($lr->type) }}
                                </div>
                                <div class="item-subtext">
                                    @if ($diff === 1)
                                        {{ $start->format('d M Y') }}
                                    @else
                                        {{ $start->format('d M') }} s/d {{ $end->format('d M Y') }}
                                    @endif

                                    @if ($lr->notes)
                                        <span
                                            style="display: block; font-style: italic; margin-top: 2px; color: var(--text-muted);">
                                            "{{ $lr->notes }}"
                                        </span>
                                    @endif

                                    @if ($lr->status === 'approved' && $lr->is_deducted)
                                        <span
                                            style="display: inline-block; font-size: 9.5px; color: var(--color-danger); font-weight: 600; margin-top: 2px;">
                                            &bull; Memotong Gaji
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <span class="status-pill {{ $lr->status }}">
                            {{ $statusLabels[$lr->status] ?? ucfirst($lr->status) }}
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
