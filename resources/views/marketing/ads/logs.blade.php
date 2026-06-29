@extends('layouts.app')

@section('title', 'Input Biaya Iklan Harian')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex flex-column gap-2 mb-4">
        <div>
            <a href="{{ route('marketing.ads.index') }}" class="btn btn-sm btn-outline-secondary rounded-3 px-3 py-1.5 fw-semibold mb-2">
                <i class="bi bi-arrow-left"></i> Kembali ke Dashboard Iklan
            </a>
            <h1 class="h3 mb-1 text-dark fw-bold">Input Biaya Iklan Harian (Ad Spend Ledger)</h1>
            <p class="text-muted mb-0">Catat biaya iklan harian secara manual untuk menghitung ROAS dan profitabilitas campaign Anda secara instan.</p>
        </div>
    </div>

    <div class="row g-4">
        <!-- Form Add Log -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                    <h6 class="m-0 fw-bold text-dark"><i class="bi bi-pencil-fill text-primary me-2"></i> Catat Biaya Iklan</h6>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('marketing.ads.logs.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="ads_campaign_id" class="form-label fw-semibold text-secondary small">Pilih Campaign Iklan</label>
                            <select name="ads_campaign_id" id="ads_campaign_id" class="form-select rounded-3 border-secondary border-opacity-25" required style="padding: 0.6rem 2.25rem 0.6rem 0.75rem;">
                                <option value="">-- Pilih Campaign --</option>
                                @foreach($campaigns as $camp)
                                    <option value="{{ $camp->id }}">{{ $camp->name }} ({{ strtoupper($camp->adsAccount->platform) }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="date" class="form-label fw-semibold text-secondary small">Tanggal Pengeluaran</label>
                            <input type="date" name="date" id="date" class="form-control rounded-3 border-secondary border-opacity-25" value="{{ date('Y-m-d') }}" required style="padding: 0.6rem 0.75rem;">
                        </div>
                        <div class="mb-3">
                            <label for="ad_spend" class="form-label fw-semibold text-secondary small">Jumlah Biaya Iklan (Rp)</label>
                            <div class="input-group">
                                <span class="input-group-text rounded-start-3 border-secondary border-opacity-25 bg-light">Rp</span>
                                <input type="number" name="ad_spend" id="ad_spend" class="form-control rounded-end-3 border-secondary border-opacity-25" placeholder="Contoh: 150000" min="0" required style="padding: 0.6rem 0.75rem;">
                            </div>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-6">
                                <label for="clicks" class="form-label fw-semibold text-secondary small">Clicks (Opsional)</label>
                                <input type="number" name="clicks" id="clicks" class="form-control rounded-3 border-secondary border-opacity-25" placeholder="0" min="0" style="padding: 0.6rem 0.75rem;">
                            </div>
                            <div class="col-6">
                                <label for="impressions" class="form-label fw-semibold text-secondary small">Impressions (Opsional)</label>
                                <input type="number" name="impressions" id="impressions" class="form-control rounded-3 border-secondary border-opacity-25" placeholder="0" min="0" style="padding: 0.6rem 0.75rem;">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 rounded-3 py-2.5 fw-semibold shadow-sm">
                            <i class="bi bi-plus-circle me-1"></i> Simpan Pengeluaran
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Log History -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                    <h6 class="m-0 fw-bold text-dark"><i class="bi bi-history text-primary me-2"></i> Riwayat Pengeluaran Iklan</h6>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-uppercase fs-7 text-muted" style="letter-spacing: 0.5px; font-size: 0.75rem;">
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
                                        <td class="px-3 py-3"><strong class="text-dark">{{ $log->campaign->name }}</strong></td>
                                        <td class="px-3 py-3">
                                            @php
                                                $pf = $log->campaign->adsAccount->platform;
                                                $pfBadge = 'bg-secondary';
                                                if ($pf === 'meta') $pfBadge = 'bg-primary';
                                                elseif ($pf === 'google') $pfBadge = 'bg-danger';
                                                elseif ($pf === 'tiktok') $pfBadge = 'bg-dark';
                                            @endphp
                                            <span class="badge {{ $pfBadge }} text-uppercase px-2.5 py-1 rounded" style="font-size:0.65rem;">
                                                {{ $pf }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-3"><strong class="text-danger">Rp {{ number_format($log->ad_spend, 0, ',', '.') }}</strong></td>
                                        <td class="px-3 py-3">{{ number_format($log->clicks) }} Clicks</td>
                                        <td class="px-3 py-3">{{ number_format($log->impressions) }} Impr.</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            Belum ada riwayat biaya iklan terdaftar. Masukkan data pengeluaran pertama Anda di form sebelah kiri.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($logs->hasPages())
                    <div class="card-footer bg-white border-0 pt-0 pb-4 px-4">
                        {{ $logs->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
