@extends('layouts.app')

@section('title', 'Input Biaya Iklan Harian')
@section('page-title', 'Input Biaya Iklan Harian')

@section('topbar-actions')
    <a href="{{ route('marketing.ads.index') }}" class="btn btn-sm btn-light text-primary fw-bold px-3">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
    </a>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="row g-3">
                <!-- Form Add Log -->
                <div class="col-lg-4 col-md-12">
                    <div class="card border shadow-sm bg-white mb-3">
                        <div class="card-header bg-primary bg-opacity-10 p-3 border-bottom">
                            <h6 class="m-0 fw-bold text-dark"><i class="bi bi-pencil-fill text-primary me-2"></i> Catat Biaya
                                Iklan</h6>
                        </div>
                        <div class="card-body p-3">
                            <form action="{{ route('marketing.ads.logs.store') }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label for="ads_campaign_id" class="form-label fw-bold text-secondary small">Pilih
                                        Campaign Iklan</label>
                                    <select name="ads_campaign_id" id="ads_campaign_id"
                                        class="form-select form-select-sm rounded-3" required>
                                        <option value="">-- Pilih Campaign --</option>
                                        @foreach ($campaigns as $camp)
                                            <option value="{{ $camp->id }}">{{ $camp->name }}
                                                ({{ strtoupper($camp->adsAccount->platform) }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="date" class="form-label fw-bold text-secondary small">Tanggal
                                        Pengeluaran</label>
                                    <input type="date" name="date" id="date"
                                        class="form-control form-control-sm rounded-3" value="{{ date('Y-m-d') }}" required>
                                </div>
                                <div class="mb-3">
                                    <label for="ad_spend" class="form-label fw-bold text-secondary small">Jumlah Biaya Iklan
                                        (Rp)</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text rounded-start-3 bg-light">Rp</span>
                                        <input type="number" name="ad_spend" id="ad_spend"
                                            class="form-control rounded-end-3" placeholder="Contoh: 150000" min="0"
                                            required>
                                    </div>
                                </div>
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label for="clicks" class="form-label fw-bold text-secondary small">Clicks
                                            (Opsional)</label>
                                        <input type="number" name="clicks" id="clicks"
                                            class="form-control form-control-sm rounded-3" placeholder="0" min="0">
                                    </div>
                                    <div class="col-6">
                                        <label for="impressions" class="form-label fw-bold text-secondary small">Impressions
                                            (Opsional)</label>
                                        <input type="number" name="impressions" id="impressions"
                                            class="form-control form-control-sm rounded-3" placeholder="0" min="0">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary w-100 rounded-3 py-2 fw-bold shadow-sm">
                                    <i class="bi bi-plus-circle me-1"></i> Simpan Pengeluaran
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Log History -->
                <div class="col-lg-8 col-md-12">
                    <div class="card border shadow-sm bg-white">
                        <div class="card-header bg-info bg-opacity-10 p-3 border-bottom">
                            <h6 class="m-0 fw-bold text-dark"><i class="bi bi-history text-info me-2"></i> Riwayat
                                Pengeluaran Iklan</h6>
                        </div>
                        <div class="card-body p-3">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light text-uppercase fs-7 text-muted"
                                        style="letter-spacing: 0.5px; font-size: 0.75rem;">
                                        <tr>
                                            <th class="border-0 px-3 py-3">Tanggal</th>
                                            <th class="border-0 px-3 py-3">Nama Campaign</th>
                                            <th class="border-0 px-3 py-3">Platform</th>
                                            <th class="border-0 px-3 py-3">Biaya (Spend)</th>
                                            <th class="border-0 px-3 py-3">Clicks</th>
                                            <th class="border-0 px-3 py-3">Impressions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($logs as $log)
                                            <tr class="border-bottom border-light">
                                                <td class="px-3 py-3">{{ $log->date->format('d/m/Y') }}</td>
                                                <td class="px-3 py-3"><strong
                                                        class="text-dark">{{ $log->campaign->name }}</strong></td>
                                                <td class="px-3 py-3">
                                                    @php
                                                        $pf = $log->campaign->adsAccount->platform;
                                                        $pfBadge = 'bg-secondary';
                                                        if ($pf === 'meta') {
                                                            $pfBadge = 'bg-primary';
                                                        } elseif ($pf === 'google') {
                                                            $pfBadge = 'bg-danger';
                                                        } elseif ($pf === 'tiktok') {
                                                            $pfBadge = 'bg-dark';
                                                        }
                                                    @endphp
                                                    <span
                                                        class="badge {{ $pfBadge }} text-uppercase px-2.5 py-1 rounded"
                                                        style="font-size:0.65rem;">
                                                        {{ $pf }}
                                                    </span>
                                                </td>
                                                <td class="px-3 py-3"><strong class="text-danger">Rp
                                                        {{ number_format($log->ad_spend, 0, ',', '.') }}</strong></td>
                                                <td class="px-3 py-3">{{ number_format($log->clicks) }} Clicks</td>
                                                <td class="px-3 py-3">{{ number_format($log->impressions) }} Impr.</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-4 text-muted">
                                                    Belum ada riwayat biaya iklan terdaftar. Masukkan data pengeluaran
                                                    pertama Anda di form sebelah kiri.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @if ($logs->hasPages())
                            <div class="card-footer bg-white border-0 pt-0 pb-4 px-4">
                                {{ $logs->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
