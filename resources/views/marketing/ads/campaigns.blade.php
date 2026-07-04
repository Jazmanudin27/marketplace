@extends('layouts.app')

@section('title', 'Atur Target Campaign Iklan')
@section('page-title', 'Target Campaign Iklan')

@section('topbar-actions')
    <form action="{{ route('marketing.ads.sync') }}" method="POST" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-sm btn-light text-primary fw-bold px-3 me-2">
            <i class="bi bi-arrow-repeat me-1"></i> Sync Semua Iklan
        </button>
    </form>
    <a href="{{ route('marketing.ads.index') }}" class="btn btn-sm btn-light text-primary fw-bold px-3">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard Iklan
    </a>
@endsection

@section('content')
    <div class="row g-3">

        {{-- ══ LEFT SIDEBAR ══ --}}
        <div class="col-lg-4">

            {{-- 1. Koneksi Akun Iklan --}}
            <div class="card border shadow-sm rounded-3 mb-3">
                <div class="card-header bg-primary bg-opacity-10 border-bottom py-2 px-3 d-flex align-items-center gap-2">
                    <span class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center"
                        style="width:28px;height:28px;flex-shrink:0;">
                        <i class="bi bi-plug-fill text-white small"></i>
                    </span>
                    <div>
                        <div class="fw-bold text-dark small lh-sm">Koneksi Akun Iklan</div>
                        <div class="text-muted" style="font-size:.72rem;">Integrasi platform ads</div>
                    </div>
                </div>
                <div class="card-body p-3">

                    {{-- Shopee --}}
                    <div class="d-flex justify-content-between align-items-center py-2">
                        <div class="d-flex align-items-center gap-2">
                            <span
                                class="bg-warning bg-opacity-10 rounded-2 d-inline-flex align-items-center justify-content-center"
                                style="width:34px;height:34px;">
                                <i class="bi bi-bag-fill text-warning"></i>
                            </span>
                            <div>
                                <div class="fw-semibold text-dark small">Shopee Ads</div>
                                <span class="badge bg-success bg-opacity-10 text-success fw-semibold rounded-pill"
                                    style="font-size:.68rem;">
                                    <i class="bi bi-circle-fill me-1" style="font-size:.4rem;"></i>Otomatis Aktif
                                </span>
                            </div>
                        </div>
                        <i class="bi bi-check-circle-fill text-success fs-5"></i>
                    </div>

                    <hr class="my-1 opacity-25">

                    {{-- TikTok --}}
                    @php
                        $tiktokAccount = $accounts->where('platform', 'tiktok')->where('is_active', true)->first();
                    @endphp
                    <div class="d-flex justify-content-between align-items-center py-2">
                        <div class="d-flex align-items-center gap-2">
                            <span
                                class="bg-dark bg-opacity-10 rounded-2 d-inline-flex align-items-center justify-content-center"
                                style="width:34px;height:34px;">
                                <i class="bi bi-tiktok text-dark"></i>
                            </span>
                            <div>
                                <div class="fw-semibold text-dark small">TikTok Ads</div>
                                @if ($tiktokAccount)
                                    <span class="badge bg-success bg-opacity-10 text-success fw-semibold rounded-pill"
                                        style="font-size:.68rem;">
                                        <i class="bi bi-circle-fill me-1"
                                            style="font-size:.4rem;"></i>{{ $tiktokAccount->account_name }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary fw-semibold rounded-pill"
                                        style="font-size:.68rem;">
                                        <i class="bi bi-circle me-1" style="font-size:.4rem;"></i>Belum Terhubung
                                    </span>
                                @endif
                            </div>
                        </div>
                        @if ($tiktokAccount)
                            <a href="{{ route('marketing.ads.tiktok.connect') }}"
                                class="btn btn-sm btn-outline-secondary rounded-pill fw-semibold px-3">Ganti</a>
                        @else
                            <a href="{{ route('marketing.ads.tiktok.connect') }}"
                                class="btn btn-sm btn-dark rounded-pill fw-semibold px-3">Hubungkan</a>
                        @endif
                    </div>

                    @if ($tiktokAccount)
                        <div class="mt-2 p-2 bg-light rounded-3">
                            <form action="{{ route('marketing.ads.tiktok.capi_settings') }}" method="POST">
                                @csrf
                                <div class="mb-2">
                                    <label class="form-label fw-bold text-secondary text-uppercase mb-1" style="font-size:.65rem; letter-spacing:.5px;">TikTok Pixel ID</label>
                                    <input type="text" name="pixel_id" class="form-control form-control-sm rounded-3" 
                                        placeholder="Contoh: CD83JBKC8..." value="{{ $tiktokAccount->pixel_id }}">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label fw-bold text-secondary text-uppercase mb-1" style="font-size:.65rem; letter-spacing:.5px;">Events Access Token</label>
                                    <textarea name="events_access_token" rows="2" class="form-control form-control-sm rounded-3" 
                                        placeholder="TikTok Developer Events Access Token...">{{ $tiktokAccount->events_access_token }}</textarea>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label fw-bold text-secondary text-uppercase mb-1" style="font-size:.65rem; letter-spacing:.5px;">Advertiser ID</label>
                                    <input type="text" name="advertiser_id" class="form-control form-control-sm rounded-3" 
                                        placeholder="TikTok Ads Advertiser ID..." value="{{ $tiktokAccount->advertiser_id }}">
                                </div>
                                <button type="submit" class="btn btn-sm btn-primary w-100 rounded-pill fw-semibold mt-1" style="font-size:.72rem;">
                                    <i class="bi bi-save2 me-1"></i> Simpan CAPI & DMP
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>

            {{-- 2. Default Campaign per Toko --}}
            <div class="card border shadow-sm rounded-3 mb-3">
                <div class="card-header bg-success bg-opacity-10 border-bottom py-2 px-3 d-flex align-items-center gap-2">
                    <span class="bg-success rounded-circle d-inline-flex align-items-center justify-content-center"
                        style="width:28px;height:28px;flex-shrink:0;">
                        <i class="bi bi-shop-window text-white small"></i>
                    </span>
                    <div>
                        <div class="fw-bold text-dark small lh-sm">Default Campaign per Toko</div>
                        <div class="text-muted" style="font-size:.72rem;">Auto-Atribusi Layer 2</div>
                    </div>
                </div>
                <div class="card-body p-3">
                    @forelse($stores as $store)
                        <form action="{{ route('marketing.ads.store_default_campaign') }}" method="POST" class="mb-2">
                            @csrf
                            <input type="hidden" name="store_id" value="{{ $store->id }}">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <div class="d-flex align-items-center gap-1 flex-wrap">
                                    <i class="bi bi-shop text-success small"></i>
                                    <span class="fw-semibold text-dark small">{{ $store->store_name }}</span>
                                    @if ($store->channel)
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill"
                                            style="font-size:.65rem;">{{ strtoupper($store->channel->code) }}</span>
                                    @endif
                                </div>
                                <button type="submit" class="btn btn-sm btn-success rounded-pill fw-semibold px-3 ms-2">
                                    <i class="bi bi-check2"></i> Set
                                </button>
                            </div>
                            <select name="default_campaign_id" class="form-select form-select-sm rounded-3">
                                <option value="">— Tidak Ada Default —</option>
                                @foreach ($campaigns as $cp)
                                    <option value="{{ $cp->id }}"
                                        {{ $store->default_campaign_id == $cp->id ? 'selected' : '' }}>
                                        {{ $cp->name }} · {{ strtoupper($cp->adsAccount->platform) }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                        @if (!$loop->last)
                            <hr class="my-2 opacity-25">
                        @endif
                    @empty
                        <div class="text-center text-muted py-3 small">
                            <i class="bi bi-shop d-block fs-3 mb-1 opacity-25"></i>
                            Belum ada toko terdaftar
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- 3. Tambah Campaign Baru --}}
            <div class="card border shadow-sm rounded-3">
                <div class="card-header bg-info bg-opacity-10 border-bottom py-2 px-3 d-flex align-items-center gap-2">
                    <span class="bg-info rounded-circle d-inline-flex align-items-center justify-content-center"
                        style="width:28px;height:28px;flex-shrink:0;">
                        <i class="bi bi-plus-lg text-white small"></i>
                    </span>
                    <div>
                        <div class="fw-bold text-dark small lh-sm">Tambah Campaign Baru</div>
                        <div class="text-muted" style="font-size:.72rem;">Daftarkan campaign iklan</div>
                    </div>
                </div>
                <div class="card-body p-3">
                    <form action="{{ route('marketing.ads.campaigns.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="name" class="form-label fw-bold text-secondary small text-uppercase"
                                style="letter-spacing:.5px;font-size:.7rem;">Nama Campaign</label>
                            <input type="text" name="name" id="name"
                                class="form-control form-control-sm rounded-3"
                                placeholder="Contoh: Meta Ads Promo Ramadhan" required>
                        </div>
                        <div class="mb-3">
                            <label for="platform" class="form-label fw-bold text-secondary small text-uppercase"
                                style="letter-spacing:.5px;font-size:.7rem;">Platform Iklan</label>
                            <select name="platform" id="platform" class="form-select form-select-sm rounded-3" required>
                                <option value="meta">📘 Meta Ads (Facebook & Instagram)</option>
                                <option value="google">🔴 Google Ads</option>
                                <option value="tiktok">⚫ TikTok Ads</option>
                                <option value="shopee">🟠 Shopee Ads</option>
                                <option value="manual">⚙️ Platform Lain (Manual)</option>
                            </select>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-4">
                                <label for="target_roas" class="form-label fw-bold text-secondary small text-uppercase"
                                    style="letter-spacing:.5px;font-size:.7rem;">Target ROAS (×)</label>
                                <input type="number" name="target_roas" id="target_roas"
                                    class="form-control form-control-sm rounded-3" placeholder="2.00" step="0.01"
                                    min="0.1" value="2.00" required>
                            </div>
                            <div class="col-4">
                                <label for="target_omzet" class="form-label fw-bold text-secondary small text-uppercase"
                                    style="letter-spacing:.5px;font-size:.7rem;">Target Omzet (Rp)</label>
                                <input type="number" name="target_omzet" id="target_omzet"
                                    class="form-control form-control-sm rounded-3" placeholder="0" min="0"
                                    value="0">
                            </div>
                            <div class="col-4">
                                <label for="target_cpo" class="form-label fw-bold text-secondary small text-uppercase"
                                    style="letter-spacing:.5px;font-size:.7rem;">Target CPO (Rp)</label>
                                <input type="number" name="target_cpo" id="target_cpo"
                                    class="form-control form-control-sm rounded-3" placeholder="0" min="0"
                                    value="0">
                            </div>
                        </div>
                        <div class="form-text text-muted mb-3">
                            <i class="bi bi-info-circle me-1 text-info"></i>
                            Peringatan muncul jika ROAS riil di bawah target.
                        </div>
                        <button type="submit" class="btn btn-primary w-100 rounded-3 fw-bold py-2">
                            <i class="bi bi-save2 me-1"></i> Simpan Campaign
                        </button>
                    </form>
                </div>
            </div>

        </div>{{-- /col-lg-4 --}}


        {{-- ══ RIGHT: CAMPAIGN TABLE ══ --}}
        <div class="col-lg-8">
            <div class="card border shadow-sm rounded-3 h-100">

                {{-- Header --}}
                <div
                    class="card-header bg-white border-bottom py-2 px-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <span class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center"
                            style="width:28px;height:28px;">
                            <i class="bi bi-grid-3x3-gap-fill text-white small"></i>
                        </span>
                        <div>
                            <div class="fw-bold text-dark small lh-sm">Daftar Campaign & KPI</div>
                            <div class="text-muted" style="font-size:.7rem;">
                                {{ $campaigns->count() }} campaign terdaftar
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <form method="GET" action="{{ route('marketing.ads.campaigns') }}" class="d-inline">
                            <select name="platform" class="form-select form-select-sm rounded-pill fw-semibold border border-secondary border-opacity-25" onchange="this.form.submit()" style="font-size: 0.72rem; min-width: 150px;">
                                <option value="">🌐 Semua Platform</option>
                                <option value="shopee" {{ ($platform ?? '') === 'shopee' ? 'selected' : '' }}>🟠 Shopee Ads</option>
                                <option value="tiktok" {{ ($platform ?? '') === 'tiktok' ? 'selected' : '' }}>⚫ TikTok Ads</option>
                                <option value="meta" {{ ($platform ?? '') === 'meta' ? 'selected' : '' }}>📘 Meta Ads</option>
                                <option value="google" {{ ($platform ?? '') === 'google' ? 'selected' : '' }}>🔴 Google Ads</option>
                                <option value="manual" {{ ($platform ?? '') === 'manual' ? 'selected' : '' }}>⚙️ Manual/Lainnya</option>
                            </select>
                        </form>
                        <span class="badge bg-success bg-opacity-10 text-success fw-semibold rounded-pill px-3 py-2 d-none d-sm-inline-block">
                            <i class="bi bi-activity me-1"></i>Live Tracking
                        </span>
                    </div>
                </div>

                {{-- Table --}}
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr class="text-uppercase text-muted"
                                    style="font-size:.72rem;letter-spacing:.6px;font-weight:700;">
                                    <th class="border-0 px-3 py-3">Campaign</th>
                                    <th class="border-0 px-3 py-3">Platform</th>
                                    <th class="border-0 px-3 py-3">Target ROAS</th>
                                    <th class="border-0 px-3 py-3">Target Omzet</th>
                                    <th class="border-0 px-3 py-3">CPO Aktual</th>
                                    <th class="border-0 px-3 py-3">Status</th>
                                    <th class="border-0 px-3 py-3 text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($campaigns as $camp)
                                    @php
                                        $pf = $camp->adsAccount->platform;
                                        [$pfBg, $pfText] = match ($pf) {
                                            'meta' => ['bg-primary', 'text-primary'],
                                            'google' => ['bg-danger', 'text-danger'],
                                            'tiktok' => ['bg-dark', 'text-dark'],
                                            'shopee' => ['bg-warning', 'text-warning'],
                                            default => ['bg-secondary', 'text-secondary'],
                                        };
                                        $pfIcon = match ($pf) {
                                            'meta' => '📘',
                                            'google' => '🔴',
                                            'tiktok' => '⚫',
                                            'shopee' => '🟠',
                                            default => '⚙️',
                                        };
                                    @endphp
                                    <tr>
                                        {{-- Campaign Name --}}
                                        <td class="px-3 py-3">
                                            <div class="fw-bold text-dark small">{{ $camp->name }}</div>
                                            <div class="text-muted" style="font-size:.73rem;">
                                                <i class="bi bi-building me-1"></i>{{ $camp->adsAccount->account_name }}
                                            </div>
                                        </td>

                                        {{-- Platform --}}
                                        <td class="px-3 py-3">
                                            <span
                                                class="badge {{ $pfBg }} bg-opacity-10 {{ $pfText }} fw-bold rounded-pill px-2 py-1 border border-opacity-25"
                                                style="font-size:.68rem;letter-spacing:.4px;text-transform:uppercase;">
                                                {{ $pfIcon }} {{ strtoupper($pf) }}
                                            </span>
                                        </td>

                                        {{-- Target ROAS --}}
                                        <td class="px-3 py-3">
                                            <span
                                                class="badge bg-success bg-opacity-10 text-success fw-bold rounded-pill px-2 py-1">
                                                <i
                                                    class="bi bi-bullseye me-1"></i>{{ number_format($camp->target_roas, 2) }}×
                                            </span>
                                        </td>

                                        {{-- Target Omzet --}}
                                        <td class="px-3 py-3">
                                            <span class="fw-semibold text-dark small">
                                                Rp {{ number_format($camp->target_omzet, 0, ',', '.') }}
                                            </span>
                                        </td>

                                        {{-- CPO Aktual #5 --}}
                                        <td class="px-3 py-3">
                                            @php
                                                $campConv = $camp->orders()->whereNotIn('order_status', ['CANCELLED'])->count();
                                                $campCpo  = $campConv > 0 ? $camp->total_spend / $campConv : 0;
                                                $cpoAlert = $camp->target_cpo && $campCpo > $camp->target_cpo;
                                            @endphp
                                            @if($campConv > 0)
                                                <div class="small fw-semibold {{ $cpoAlert ? 'text-danger' : 'text-dark' }}">
                                                    Rp {{ number_format($campCpo, 0, ',', '.') }}
                                                </div>
                                                @if($cpoAlert)
                                                    <div class="text-danger" style="font-size:.68rem;">
                                                        <i class="bi bi-exclamation-triangle-fill me-1"></i>Target: Rp {{ number_format($camp->target_cpo, 0, ',', '.') }}
                                                    </div>
                                                @elseif($camp->target_cpo)
                                                    <div class="text-muted" style="font-size:.68rem;">Target: Rp {{ number_format($camp->target_cpo, 0, ',', '.') }}</div>
                                                @endif
                                            @else
                                                <span class="text-muted" style="font-size:.75rem;">—</span>
                                            @endif
                                        </td>

                                        {{-- Status --}}
                                        <td class="px-3 py-3">
                                            @if ($camp->status === 'ACTIVE')
                                                <span
                                                    class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 fw-bold rounded-pill px-2 py-1"
                                                    style="font-size:.7rem;">
                                                    <i class="bi bi-circle-fill me-1" style="font-size:.45rem;"></i>ACTIVE
                                                </span>
                                            @else
                                                <span
                                                    class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 fw-bold rounded-pill px-2 py-1"
                                                    style="font-size:.7rem;">
                                                    <i class="bi bi-circle me-1" style="font-size:.45rem;"></i>PAUSED
                                                </span>
                                            @endif
                                        </td>

                                        {{-- Actions --}}
                                        <td class="px-3 py-3 text-end">
                                            <div class="d-flex gap-1 justify-content-end">
                                                <a href="{{ route('marketing.ads.campaign.detail', $camp->id) }}"
                                                    class="btn btn-sm btn-outline-info rounded-3 px-2" title="Lihat Detail">
                                                    <i class="bi bi-eye-fill"></i>
                                                </a>
                                                <button class="btn btn-sm btn-outline-primary rounded-3 px-2"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editModal-{{ $camp->id }}" title="Edit Target">
                                                    <i class="bi bi-pencil-fill"></i>
                                                </button>
                                                <form action="{{ route('marketing.ads.campaigns.destroy', $camp->id) }}"
                                                    method="POST"
                                                    onsubmit="return confirm('Yakin hapus campaign ini? Log biaya iklan ikut terhapus.')"
                                                    class="d-inline">
                                                    @csrf @method('DELETE')
                                                    <button type="submit"
                                                        class="btn btn-sm btn-outline-danger rounded-3 px-2"
                                                        title="Hapus">
                                                        <i class="bi bi-trash-fill"></i>
                                                    </button>
                                                </form>
                                            </div>

                                            {{-- Edit Modal --}}
                                            <div class="modal fade text-start" id="editModal-{{ $camp->id }}"
                                                tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content border-0 shadow-lg rounded-4">
                                                        <div
                                                            class="modal-header bg-primary bg-opacity-10 border-bottom px-4 py-3">
                                                            <div>
                                                                <h5 class="modal-title fw-bold text-dark mb-0"
                                                                    id="editModalLabel-{{ $camp->id }}">
                                                                    Edit Target KPI
                                                                </h5>
                                                                <div class="text-muted small">
                                                                    {{ $camp->name }}
                                                                    <span
                                                                        class="badge {{ $pfBg }} bg-opacity-10 {{ $pfText }} rounded-pill ms-1"
                                                                        style="font-size:.65rem;">{{ strtoupper($pf) }}</span>
                                                                </div>
                                                            </div>
                                                            <button type="button" class="btn-close"
                                                                data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form
                                                            action="{{ route('marketing.ads.campaigns.update', $camp->id) }}"
                                                            method="POST">
                                                            @csrf @method('PUT')
                                                            <div class="modal-body p-4">
                                                                <div class="mb-3">
                                                                    <label for="target_roas-{{ $camp->id }}"
                                                                        class="form-label fw-bold text-secondary small text-uppercase"
                                                                        style="letter-spacing:.5px;font-size:.7rem;">
                                                                        Target ROAS Minimum (×)
                                                                    </label>
                                                                    <input type="number" name="target_roas"
                                                                        id="target_roas-{{ $camp->id }}"
                                                                        class="form-control rounded-3"
                                                                        value="{{ $camp->target_roas }}" step="0.01"
                                                                        min="0.1" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="target_omzet-{{ $camp->id }}"
                                                                        class="form-label fw-bold text-secondary small text-uppercase"
                                                                        style="letter-spacing:.5px;font-size:.7rem;">
                                                                        Target Omzet (Rp)
                                                                    </label>
                                                                    <input type="number" name="target_omzet"
                                                                        id="target_omzet-{{ $camp->id }}"
                                                                        class="form-control rounded-3"
                                                                        value="{{ (int) $camp->target_omzet }}"
                                                                        min="0" required>
                                                                </div>
                                                                <div class="mb-0">
                                                                    <label for="target_cpo-{{ $camp->id }}"
                                                                        class="form-label fw-bold text-secondary small text-uppercase"
                                                                        style="letter-spacing:.5px;font-size:.7rem;">
                                                                        Target CPO / Cost Per Order (Rp)
                                                                    </label>
                                                                    <div class="input-group">
                                                                        <span class="input-group-text bg-light border-end-0">Rp</span>
                                                                        <input type="number" name="target_cpo"
                                                                            id="target_cpo-{{ $camp->id }}"
                                                                            class="form-control border-start-0"
                                                                            value="{{ (int) $camp->target_cpo }}"
                                                                            min="0" placeholder="Opsional — 0 = tidak ada target">
                                                                    </div>
                                                                    <div class="form-text text-muted">Jika CPO aktual melebihi nilai ini, akan muncul peringatan merah.</div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer border-top px-4 py-3">
                                                                <button type="button"
                                                                    class="btn btn-light rounded-3 fw-semibold px-4"
                                                                    data-bs-dismiss="modal">Batal</button>
                                                                <button type="submit"
                                                                    class="btn btn-primary rounded-3 fw-bold px-4">
                                                                    <i class="bi bi-save2 me-1"></i>Simpan
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="bi bi-megaphone d-block fs-1 mb-2 opacity-25"></i>
                                            <div class="fw-bold text-dark small mb-1">Belum Ada Campaign Terdaftar</div>
                                            <div class="small">Buat campaign baru melalui form di sebelah kiri.</div>
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
