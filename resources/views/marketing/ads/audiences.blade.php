@extends('layouts.app')

@section('title', 'TikTok Custom Audience Sync')
@section('page-title', 'TikTok Custom Audience')

@section('topbar-actions')
    <a href="{{ route('marketing.ads.index') }}" class="btn btn-sm btn-light text-primary fw-bold px-3">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard Iklan
    </a>
@endsection

@section('content')
<div class="row g-3">

    {{-- ══ LEFT SIDE: CREATE AUDIENCE COHORT ══ --}}
    <div class="col-lg-4">
        <div class="card border shadow-sm rounded-3">
            <div class="card-header bg-primary bg-opacity-10 border-bottom py-2 px-3 d-flex align-items-center gap-2">
                <span class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center"
                    style="width:28px;height:28px;flex-shrink:0;">
                    <i class="bi bi-people-fill text-white small"></i>
                </span>
                <div>
                    <div class="fw-bold text-dark small lh-sm">Buat Kohort Pemirsa</div>
                    <div class="text-muted" style="font-size:.72rem;">Custom Audience target iklan</div>
                </div>
            </div>
            <div class="card-body p-3">
                <form action="{{ route('marketing.ads.audiences.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="name" class="form-label fw-bold text-secondary small text-uppercase"
                            style="letter-spacing:.5px;font-size:.7rem;">Nama Audience (di TikTok Manager)</label>
                        <input type="text" name="name" id="name"
                            class="form-control form-control-sm rounded-3"
                            placeholder="Contoh: ERP Buyers - All Time" required>
                    </div>

                    <div class="mb-3">
                        <label for="ads_account_id" class="form-label fw-bold text-secondary small text-uppercase"
                            style="letter-spacing:.5px;font-size:.7rem;">Pilih Akun TikTok Ads</label>
                        <select name="ads_account_id" id="ads_account_id" class="form-select form-select-sm rounded-3" required>
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}">
                                    {{ $acc->account_name }} (Adv ID: {{ $acc->advertiser_id ?? 'Belum diset' }})
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text text-muted" style="font-size:.7rem;">
                            Pastikan Advertiser ID sudah diisi di halaman Target Campaign.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="type" class="form-label fw-bold text-secondary small text-uppercase"
                            style="letter-spacing:.5px;font-size:.7rem;">Segmentasi Pelanggan</label>
                        <select name="type" id="type" class="form-select form-select-sm rounded-3" required>
                            <option value="purchasers">🛍️ Semua Pembeli (Berdasarkan order masuk)</option>
                            <option value="high_value_customers">💎 Pembeli High-Value (Belanja ≥ Rp500.000)</option>
                        </select>
                        <div class="form-text text-muted" style="font-size:.7rem;">
                            Sistem akan mengekstrak nomor telepon pelanggan dari data pesanan ERP, men-hash datanya, dan menguploadnya secara anonim ke TikTok DMP.
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 rounded-3 fw-bold py-2">
                        <i class="bi bi-plus-lg me-1"></i> Buat Target Pemirsa
                    </button>
                </form>
            </div>
        </div>
    </div>{{-- /col-lg-4 --}}

    {{-- ══ RIGHT SIDE: AUDIENCE LIST ══ --}}
    <div class="col-lg-8">
        <div class="card border shadow-sm rounded-3">
            <div class="card-header bg-white border-bottom py-2 px-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <span class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center"
                        style="width:28px;height:28px;">
                        <i class="bi bi-cloud-arrow-up-fill text-white small"></i>
                    </span>
                    <div>
                        <div class="fw-bold text-dark small lh-sm">Daftar TikTok Custom Audience</div>
                        <div class="text-muted" style="font-size:.7rem;">
                            Sinkronisasi data pemirsa ke panel TikTok DMP
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size:.82rem;">
                        <thead class="table-light">
                            <tr class="text-uppercase text-muted" style="font-size:.7rem; letter-spacing:.5px; font-weight:700;">
                                <th class="px-3 py-2.5">Nama Audience</th>
                                <th class="px-3 py-2.5">Tipe Segmentasi</th>
                                <th class="px-3 py-2.5">Advertiser ID</th>
                                <th class="px-3 py-2.5">TikTok Audience ID</th>
                                <th class="px-3 py-2.5">Ukuran</th>
                                <th class="px-3 py-2.5">Status</th>
                                <th class="px-3 py-2.5">Sync Terakhir</th>
                                <th class="px-3 py-2.5 text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($audiences as $aud)
                                <tr>
                                    <td class="px-3 py-3 fw-bold text-dark">
                                        {{ $aud->name }}
                                        @if($aud->error_message)
                                            <div class="text-danger fw-normal" style="font-size:.7rem;">
                                                <i class="bi bi-exclamation-circle me-1"></i>{{ $aud->error_message }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3">
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary fw-semibold rounded-pill px-2.5 py-1">
                                            {{ \App\Models\TiktokAudience::typeLabels()[$aud->type] ?? $aud->type }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 text-muted">
                                        {{ $aud->adsAccount->advertiser_id ?? '—' }}
                                    </td>
                                    <td class="px-3 py-3">
                                        <code class="text-dark small">{{ $aud->tiktok_audience_id ?? 'Belum Sinkron' }}</code>
                                    </td>
                                    <td class="px-3 py-3 fw-semibold">
                                        {{ number_format($aud->customer_count) }} kontak
                                    </td>
                                    <td class="px-3 py-3">
                                        @if($aud->status === 'active')
                                            <span class="badge bg-success bg-opacity-10 text-success fw-bold rounded-pill px-2.5 py-1">
                                                <i class="bi bi-check2-all me-1"></i>ACTIVE
                                            </span>
                                        @elseif($aud->status === 'uploading')
                                            <span class="badge bg-warning bg-opacity-10 text-warning fw-bold rounded-pill px-2.5 py-1">
                                                UPLOADING...
                                            </span>
                                        @elseif($aud->status === 'failed')
                                            <span class="badge bg-danger bg-opacity-10 text-danger fw-bold rounded-pill px-2.5 py-1">
                                                FAILED
                                            </span>
                                        @else
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary fw-semibold rounded-pill px-2.5 py-1">
                                                PENDING
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-muted">
                                        {{ $aud->last_synced_at ? $aud->last_synced_at->format('d M H:i') : '—' }}
                                    </td>
                                    <td class="px-3 py-3 text-end">
                                        <div class="d-flex gap-1 justify-content-end">
                                            <form action="{{ route('marketing.ads.audiences.sync', $aud->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-primary px-3 rounded-pill fw-bold text-nowrap" style="font-size:.72rem;">
                                                    <i class="bi bi-arrow-repeat me-1"></i> Sync
                                                </button>
                                            </form>
                                            <form action="{{ route('marketing.ads.audiences.destroy', $aud->id) }}" method="POST"
                                                onsubmit="return confirm('Hapus pelacakan audience ini di ERP?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger border-0 p-1">
                                                    <i class="bi bi-trash-fill fs-6"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="bi bi-people d-block fs-1 mb-2 opacity-25"></i>
                                        <div class="fw-bold text-dark small mb-1">Belum Ada Custom Audience</div>
                                        <div class="small">Definisikan target pemirsa di sebelah kiri dan sinkronkan ke TikTok.</div>
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
