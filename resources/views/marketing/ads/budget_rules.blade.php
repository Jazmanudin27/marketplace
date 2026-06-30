@extends('layouts.app')

@section('title', 'Smart Budget Rules & Alerts')
@section('page-title', 'Smart Budget Rules')

@section('topbar-actions')
    <a href="{{ route('marketing.ads.index') }}" class="btn btn-sm btn-light text-primary fw-bold px-3">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard Iklan
    </a>
@endsection

@section('content')
<div class="row g-3">

    {{-- ══ LEFT SIDE: ADD BUDGET RULE FORM ══ --}}
    <div class="col-lg-4">
        <div class="card border shadow-sm rounded-3">
            <div class="card-header bg-primary bg-opacity-10 border-bottom py-2 px-3 d-flex align-items-center gap-2">
                <span class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center"
                    style="width:28px;height:28px;flex-shrink:0;">
                    <i class="bi bi-plus-lg text-white small"></i>
                </span>
                <div>
                    <div class="fw-bold text-dark small lh-sm">Tambah Smart Rule</div>
                    <div class="text-muted" style="font-size:.72rem;">Otomatisasi pengawasan iklan</div>
                </div>
            </div>
            <div class="card-body p-3">
                <form action="{{ route('marketing.ads.budget_rules.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="name" class="form-label fw-bold text-secondary small text-uppercase"
                            style="letter-spacing:.5px;font-size:.7rem;">Nama Aturan / Label</label>
                        <input type="text" name="name" id="name"
                            class="form-control form-control-sm rounded-3"
                            placeholder="Contoh: ROAS Meta Drop di bawah 2" required>
                    </div>

                    <div class="mb-3">
                        <label for="ads_campaign_id" class="form-label fw-bold text-secondary small text-uppercase"
                            style="letter-spacing:.5px;font-size:.7rem;">Pilih Campaign</label>
                        <select name="ads_campaign_id" id="ads_campaign_id" class="form-select form-select-sm rounded-3" required>
                            @foreach($campaigns as $camp)
                                <option value="{{ $camp->id }}">
                                    {{ $camp->name }} · {{ strtoupper($camp->adsAccount->platform) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="condition" class="form-label fw-bold text-secondary small text-uppercase"
                            style="letter-spacing:.5px;font-size:.7rem;">Kondisi Pemicu (Trigger)</label>
                        <select name="condition" id="condition" class="form-select form-select-sm rounded-3" required>
                            <option value="roas_below">📉 ROAS di bawah target threshold</option>
                            <option value="roas_above">📈 ROAS di atas target threshold</option>
                            <option value="spend_exceeds_daily">💸 Pengeluaran Harian melebihi batas</option>
                            <option value="spend_exceeds_total">💰 Pengeluaran Total melebihi batas</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="threshold_value" class="form-label fw-bold text-secondary small text-uppercase"
                            style="letter-spacing:.5px;font-size:.7rem;">Nilai Batas (Threshold)</label>
                        <input type="number" name="threshold_value" id="threshold_value"
                            class="form-control form-control-sm rounded-3"
                            placeholder="2.00 atau 500000" step="0.01" min="0" required>
                        <div class="form-text text-muted" style="font-size:.7rem;">
                            Isi angka ROAS (misal: 2.00) atau nominal uang dalam rupiah (misal: 500000).
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-secondary small text-uppercase"
                            style="letter-spacing:.5px;font-size:.7rem;">Tindakan Otomatis</label>
                        <select name="action" class="form-select form-select-sm rounded-3" required>
                            <option value="notify">🔔 Kirim Alert & Dashboard Notifikasi</option>
                            <option value="pause_suggestion">⚠️ Tampilkan Saran Jeda Iklan (Pause)</option>
                            <option value="increase_suggestion">📈 Tampilkan Saran Naikkan Budget (Scale)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="whatsapp_recipient" class="form-label fw-bold text-secondary small text-uppercase"
                            style="letter-spacing:.5px;font-size:.7rem;">Nomor WA Penerima Alert (Opsional)</label>
                        <input type="text" name="whatsapp_recipient" id="whatsapp_recipient"
                            class="form-control form-control-sm rounded-3"
                            placeholder="Contoh: 08123456789">
                        <div class="form-text text-muted" style="font-size:.7rem;">
                            Kosongkan jika ingin dikirim ke nomor default di <code>.env</code> (<code>WHATSAPP_ALERT_RECIPIENT</code>).
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 rounded-3 fw-bold py-2">
                        <i class="bi bi-save2 me-1"></i> Aktifkan Rule
                    </button>
                </form>
            </div>
        </div>
    </div>{{-- /col-lg-4 --}}

    {{-- ══ RIGHT SIDE: RULES LIST & ALERTS LOG ══ --}}
    <div class="col-lg-8">
        
        {{-- List Rules --}}
        <div class="card border shadow-sm rounded-3 mb-3">
            <div class="card-header bg-white border-bottom py-2 px-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <span class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center"
                        style="width:28px;height:28px;">
                        <i class="bi bi-gear-fill text-white small"></i>
                    </span>
                    <div>
                        <div class="fw-bold text-dark small lh-sm">Aturan Pengawasan Aktif</div>
                        <div class="text-muted" style="font-size:.7rem;">
                            {{ $rules->count() }} aturan di-monitoring
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size:.82rem;">
                        <thead class="table-light">
                            <tr class="text-uppercase text-muted" style="font-size:.7rem; letter-spacing:.5px; font-weight:700;">
                                <th class="px-3 py-2.5">Rule / Label</th>
                                <th class="px-3 py-2.5">Campaign</th>
                                <th class="px-3 py-2.5">Trigger Condition</th>
                                <th class="px-3 py-2.5">Batas Nilai</th>
                                <th class="px-3 py-2.5">Tindakan</th>
                                <th class="px-3 py-2.5">WA Alert Ke</th>
                                <th class="px-3 py-2.5 text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rules as $rule)
                                <tr>
                                    <td class="px-3 py-3 fw-bold text-dark">{{ $rule->name }}</td>
                                    <td class="px-3 py-3">
                                        <div class="fw-semibold text-dark">{{ $rule->campaign->name }}</div>
                                        <div class="text-muted" style="font-size:.73rem;">
                                            {{ strtoupper($rule->campaign->adsAccount->platform) }}
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 text-secondary">
                                        {{ \App\Models\AdsBudgetRule::conditionLabels()[$rule->condition] ?? $rule->condition }}
                                    </td>
                                    <td class="px-3 py-3 fw-semibold">
                                        @if(str_contains($rule->condition, 'roas'))
                                            {{ number_format($rule->threshold_value, 2) }}x
                                        @else
                                            Rp {{ number_format($rule->threshold_value, 0, ',', '.') }}
                                        @endif
                                    </td>
                                    <td class="px-3 py-3">
                                        <span class="badge bg-info bg-opacity-10 text-info fw-semibold rounded-pill px-2.5 py-1">
                                            {{ \App\Models\AdsBudgetRule::actionLabels()[$rule->action] ?? $rule->action }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 text-muted">
                                        {{ $rule->whatsapp_recipient ?: 'Default (.env)' }}
                                    </td>
                                    <td class="px-3 py-3 text-end">
                                        <form action="{{ route('marketing.ads.budget_rules.destroy', $rule->id) }}" method="POST"
                                            onsubmit="return confirm('Hapus aturan pengawasan ini?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger border-0 p-1">
                                                <i class="bi bi-trash-fill fs-6"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <i class="bi bi-shield-check d-block fs-3 mb-1 opacity-25"></i>
                                        Belum ada aturan pengawasan dibuat.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Alerts Log --}}
        <div class="card border shadow-sm rounded-3">
            <div class="card-header bg-white border-bottom py-2 px-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <span class="bg-danger rounded-circle d-inline-flex align-items-center justify-content-center"
                        style="width:28px;height:28px;">
                        <i class="bi bi-bell-fill text-white small"></i>
                    </span>
                    <div>
                        <div class="fw-bold text-dark small lh-sm">Riwayat Alert Terpicu</div>
                        <div class="text-muted" style="font-size:.7rem;">
                            Log peringatan anggaran dan performa
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size:.82rem;">
                        <thead class="table-light">
                            <tr class="text-uppercase text-muted" style="font-size:.7rem; letter-spacing:.5px; font-weight:700;">
                                <th class="px-3 py-2.5">Waktu</th>
                                <th class="px-3 py-2.5">Campaign</th>
                                <th class="px-3 py-2.5">Pesan Peringatan</th>
                                <th class="px-3 py-2.5">Status</th>
                                <th class="px-3 py-2.5 text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($alerts as $alert)
                                <tr>
                                    <td class="px-3 py-3 text-muted text-nowrap">
                                        {{ $alert->triggered_at->format('d M H:i') }}
                                    </td>
                                    <td class="px-3 py-3">
                                        <div class="fw-semibold text-dark">{{ $alert->campaign->name }}</div>
                                        <div class="text-muted" style="font-size:.73rem;">
                                            {{ strtoupper($alert->campaign->adsAccount->platform) }}
                                        </div>
                                    </td>
                                    <td class="px-3 py-3">
                                        <span class="text-dark">{{ $alert->message }}</span>
                                    </td>
                                    <td class="px-3 py-3">
                                        @if($alert->is_read)
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary fw-semibold rounded-pill px-2.5 py-1">
                                                Dibaca
                                            </span>
                                        @else
                                            <span class="badge bg-danger bg-opacity-10 text-danger fw-semibold rounded-pill px-2.5 py-1 animate-pulse">
                                                Baru!
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-end">
                                        @if(!$alert->is_read)
                                            <form action="{{ route('marketing.ads.budget_alerts.read', $alert->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-secondary px-2 rounded-pill fw-semibold" style="font-size:.72rem;">
                                                    Tandai Dibaca
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        <i class="bi bi-bell-slash d-block fs-3 mb-1 opacity-25"></i>
                                        Tidak ada riwayat alert.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>{{-- /col-lg-8 --}}

</div>
@endsection
