@extends('layouts.app')
@section('title', 'Laporan Produk dari Iklan')
@section('page-title', 'Laporan Produk dari Iklan')

@section('topbar-actions')
    <a href="{{ route('marketing.ads.index') }}" class="btn btn-sm btn-light text-primary fw-bold px-3">
        <i class="bi bi-arrow-left me-1"></i> Dashboard Iklan
    </a>
    <a href="{{ route('marketing.ads.product_report.export', request()->all()) }}" class="btn btn-sm btn-success fw-bold px-3 ms-2">
        <i class="bi bi-download me-1"></i> Export CSV
    </a>
@endsection

@section('content')
    {{-- Filter Bar --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2 px-3">
            <form method="GET" action="{{ route('marketing.ads.product_report') }}">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-3">
                        <label class="form-label form-label-sm fw-semibold text-secondary text-uppercase mb-1" style="font-size:.68rem;letter-spacing:.5px;">Periode</label>
                        <select name="period" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="7"            {{ ($period ?? '30') === '7'          ? 'selected' : '' }}>7 Hari Terakhir</option>
                            <option value="30"           {{ ($period ?? '30') === '30'         ? 'selected' : '' }}>30 Hari Terakhir</option>
                            <option value="this_month"   {{ ($period ?? '30') === 'this_month' ? 'selected' : '' }}>Bulan Ini</option>
                            <option value="last_month"   {{ ($period ?? '30') === 'last_month' ? 'selected' : '' }}>Bulan Lalu</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label form-label-sm fw-semibold text-secondary text-uppercase mb-1" style="font-size:.68rem;letter-spacing:.5px;">Platform</label>
                        <select name="platform" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">🌐 Semua Platform</option>
                            <option value="shopee" {{ ($platform ?? '') === 'shopee' ? 'selected' : '' }}>🟠 Shopee Ads</option>
                            <option value="tiktok" {{ ($platform ?? '') === 'tiktok' ? 'selected' : '' }}>⚫ TikTok Ads</option>
                            <option value="meta"   {{ ($platform ?? '') === 'meta'   ? 'selected' : '' }}>📘 Meta Ads</option>
                            <option value="google" {{ ($platform ?? '') === 'google' ? 'selected' : '' }}>🔴 Google Ads</option>
                            <option value="manual" {{ ($platform ?? '') === 'manual' ? 'selected' : '' }}>⚙️ Manual</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label form-label-sm fw-semibold text-secondary text-uppercase mb-1" style="font-size:.68rem;letter-spacing:.5px;">Campaign</label>
                        <select name="campaign_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Semua Campaign</option>
                            @foreach($campaigns as $c)
                                <option value="{{ $c->id }}" {{ ($campaignId ?? '') == $c->id ? 'selected' : '' }}>
                                    {{ $c->name }} ({{ strtoupper($c->adsAccount->platform) }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <label class="form-label form-label-sm mb-1 d-block" style="font-size:.68rem;">&nbsp;</label>
                        <span class="badge bg-secondary bg-opacity-10 text-secondary border rounded-pill px-3 py-2 small">
                            {{ $dateStart->format('d M') }} — {{ $dateEnd->format('d M Y') }}
                        </span>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Stat Cards --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="text-muted small text-uppercase fw-semibold mb-1" style="font-size:.68rem;letter-spacing:.5px;">Total Spend</div>
                    <div class="fw-bold fs-5 text-danger">Rp {{ number_format($totalSpend, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="text-muted small text-uppercase fw-semibold mb-1" style="font-size:.68rem;letter-spacing:.5px;">Total Revenue</div>
                    <div class="fw-bold fs-5 text-success">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="text-muted small text-uppercase fw-semibold mb-1" style="font-size:.68rem;letter-spacing:.5px;">Gross Profit</div>
                    @php $gp = $totalRevenue - $totalHpp; @endphp
                    <div class="fw-bold fs-5 {{ $gp >= 0 ? 'text-success' : 'text-danger' }}">
                        Rp {{ number_format($gp, 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 bg-primary text-white shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="text-white-50 small text-uppercase fw-semibold mb-1" style="font-size:.68rem;letter-spacing:.5px;">Overall ROAS</div>
                    <div class="fw-bold fs-5">{{ number_format($overallRoas, 2) }}x</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Product Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent d-flex align-items-center gap-2 py-2 px-3">
            <div class="rounded-3 bg-primary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0"
                 style="width:36px;height:36px;">
                <i class="bi bi-box-seam text-primary"></i>
            </div>
            <h6 class="fw-bold mb-0">Performa Produk via Iklan</h6>
            <span class="badge bg-primary bg-opacity-10 text-primary ms-auto border">{{ $products->count() }} Produk</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0" style="font-size:.82rem;">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width:5%">No.</th>
                            <th style="width:12%">SKU</th>
                            <th>Nama Produk</th>
                            <th class="text-center" style="width:8%">QTY</th>
                            <th class="text-end" style="width:14%">Revenue</th>
                            <th class="text-end" style="width:14%">HPP Total</th>
                            <th class="text-end" style="width:14%">Gross Profit</th>
                            <th class="text-center" style="width:9%">Margin</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $i => $p)
                            <tr>
                                <td class="ps-3 text-muted">{{ $i + 1 }}</td>
                                <td><code class="bg-light text-secondary px-1 rounded border small">{{ $p->sku }}</code></td>
                                <td class="fw-semibold">{{ $p->product_name }}</td>
                                <td class="text-center fw-bold text-primary">{{ number_format($p->total_qty) }}</td>
                                <td class="text-end font-monospace text-success">Rp {{ number_format($p->total_revenue, 0, ',', '.') }}</td>
                                <td class="text-end font-monospace text-muted">Rp {{ number_format($p->total_hpp, 0, ',', '.') }}</td>
                                <td class="text-end font-monospace fw-bold {{ $p->gross_profit >= 0 ? 'text-success' : 'text-danger' }}">
                                    Rp {{ number_format($p->gross_profit, 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    @php
                                        $m = $p->gross_margin;
                                        $mc = $m >= 30 ? 'success' : ($m >= 15 ? 'warning' : 'danger');
                                    @endphp
                                    <span class="badge bg-{{ $mc }} bg-opacity-10 text-{{ $mc }} border rounded-pill px-2">
                                        {{ $m }}%
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <i class="bi bi-box fs-2 d-block mb-2 opacity-25"></i>
                                    Belum ada produk yang terjual melalui iklan pada periode ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($products->count() > 0)
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="3" class="ps-3 fw-bold">TOTAL</td>
                            <td class="text-center text-primary fw-bold">{{ number_format($totalQty) }}</td>
                            <td class="text-end font-monospace text-success">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</td>
                            <td class="text-end font-monospace text-muted">Rp {{ number_format($totalHpp, 0, ',', '.') }}</td>
                            <td class="text-end font-monospace {{ ($totalRevenue-$totalHpp) >= 0 ? 'text-success' : 'text-danger' }}">
                                Rp {{ number_format($totalRevenue - $totalHpp, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                @php $tm = $totalRevenue > 0 ? round((($totalRevenue-$totalHpp)/$totalRevenue)*100,1) : 0; @endphp
                                <span class="badge bg-primary bg-opacity-10 text-primary border rounded-pill px-2">{{ $tm }}%</span>
                            </td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
@endsection
