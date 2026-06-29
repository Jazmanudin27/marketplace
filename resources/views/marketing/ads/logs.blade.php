@extends('layouts.app')

@section('title', 'Input Biaya Iklan Harian')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('marketing.ads.index') }}" class="btn btn-sm btn-outline-secondary mb-2">
                <i class="bi bi-arrow-left"></i> Kembali ke Dashboard Iklan
            </a>
            <h1 class="h3 mb-1 text-dark fw-bold">Input Biaya Iklan Harian (Ad Spend Ledger)</h1>
            <p class="text-muted mb-0">Catat biaya iklan harian secara manual untuk menghitung ROAS dan profitabilitas campaign Anda secara instan.</p>
        </div>
    </div>

    <div class="row">
        <!-- Form Add Log -->
        <div class="col-lg-4 mb-4">
            <div class="card border shadow-sm">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="m-0 fw-bold text-dark"><i class="bi bi-pencil-fill text-primary me-2"></i> Catat Biaya Iklan</h6>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('marketing.ads.logs.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="ads_campaign_id" class="form-label fw-bold text-secondary small">Pilih Campaign Iklan</label>
                            <select name="ads_campaign_id" id="ads_campaign_id" class="form-select" required>
                                <option value="">-- Pilih Campaign --</option>
                                @foreach($campaigns as $camp)
                                    <option value="{{ $camp->id }}">{{ $camp->name }} ({{ strtoupper($camp->adsAccount->platform) }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="date" class="form-label fw-bold text-secondary small">Tanggal Pengeluaran</label>
                            <input type="date" name="date" id="date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="ad_spend" class="form-label fw-bold text-secondary small">Jumlah Biaya Iklan (Rp)</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="ad_spend" id="ad_spend" class="form-control" placeholder="Contoh: 150000" min="0" required>
                            </div>
                        </div>
                        <div class="row g-2 mb-4">
                            <div class="col-6">
                                <label for="clicks" class="form-label fw-bold text-secondary small">Clicks (Opsional)</label>
                                <input type="number" name="clicks" id="clicks" class="form-control" placeholder="0" min="0">
                            </div>
                            <div class="col-6">
                                <label for="impressions" class="form-label fw-bold text-secondary small">Impressions (Opsional)</label>
                                <input type="number" name="impressions" id="impressions" class="form-control" placeholder="0" min="0">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 fw-semibold">
                            <i class="bi bi-plus-circle me-1"></i> Simpan Pengeluaran
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Log History -->
        <div class="col-lg-8">
            <div class="card border shadow-sm">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="m-0 fw-bold text-dark"><i class="bi bi-history text-primary me-2"></i> Riwayat Pengeluaran Iklan</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Nama Campaign</th>
                                    <th>Platform</th>
                                    <th>Biaya (Spend)</th>
                                    <th>Clicks</th>
                                    <th>Impressions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($logs as $log)
                                    <tr>
                                        <td>{{ $log->date->format('d/m/Y') }}</td>
                                        <td><strong class="text-dark">{{ $log->campaign->name }}</strong></td>
                                        <td>
                                            <span class="badge bg-{{ $log->campaign->adsAccount->platform === 'meta' ? 'primary' : ($log->campaign->adsAccount->platform === 'google' ? 'danger' : 'dark') }} text-uppercase" style="font-size:0.7rem;">
                                                {{ $log->campaign->adsAccount->platform }}
                                            </span>
                                        </td>
                                        <td><strong class="text-danger">Rp {{ number_format($log->ad_spend, 0, ',', '.') }}</strong></td>
                                        <td>{{ number_format($log->clicks) }} Clicks</td>
                                        <td>{{ number_format($log->impressions) }} Impr.</td>
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
                    <div class="card-footer bg-white border-top py-3 px-4">
                        {{ $logs->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
