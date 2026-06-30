@extends('layouts.app')

@section('title', 'RFM Customer Segmentation')
@section('page-title', 'RFM Customer Segmentation')

@section('topbar-actions')
    <a href="{{ route('marketing.ads.index') }}" class="btn btn-sm btn-light text-primary fw-bold px-3">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard Iklan
    </a>
@endsection

@section('content')
<div class="row g-3">
    <!-- Summary Header Cards (Stripe Style Premium Layout) -->
    <div class="col-12">
        <div class="row g-3">
            <!-- Champions -->
            <div class="col-md-2.4 col-lg col-6">
                <div class="card border-0 shadow-sm bg-white h-100" style="border-left: 4px solid #0d6efd !important; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='none'">
                    <div class="card-body p-3 d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-uppercase fw-bold text-secondary" style="font-size: 0.68rem; letter-spacing: 0.5px;">Champions</span>
                            <h3 class="mb-0 fw-extrabold text-dark mt-1">{{ count($segments['Champions'] ?? []) }}</h3>
                        </div>
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 42px; height: 42px; flex-shrink: 0;">
                            <i class="bi bi-trophy-fill fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Loyal -->
            <div class="col-md-2.4 col-lg col-6">
                <div class="card border-0 shadow-sm bg-white h-100" style="border-left: 4px solid #198754 !important; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='none'">
                    <div class="card-body p-3 d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-uppercase fw-bold text-secondary" style="font-size: 0.68rem; letter-spacing: 0.5px;">Loyal</span>
                            <h3 class="mb-0 fw-extrabold text-dark mt-1">{{ count($segments['Loyal'] ?? []) }}</h3>
                        </div>
                        <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 42px; height: 42px; flex-shrink: 0;">
                            <i class="bi bi-heart-fill fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- At Risk -->
            <div class="col-md-2.4 col-lg col-6">
                <div class="card border-0 shadow-sm bg-white h-100" style="border-left: 4px solid #ffc107 !important; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='none'">
                    <div class="card-body p-3 d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-uppercase fw-bold text-secondary" style="font-size: 0.68rem; letter-spacing: 0.5px;">At Risk</span>
                            <h3 class="mb-0 fw-extrabold text-dark mt-1">{{ count($segments['At Risk'] ?? []) }}</h3>
                        </div>
                        <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center" style="width: 42px; height: 42px; flex-shrink: 0;">
                            <i class="bi bi-exclamation-triangle-fill fs-5 text-warning-emphasis"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hibernating -->
            <div class="col-md-2.4 col-lg col-6">
                <div class="card border-0 shadow-sm bg-white h-100" style="border-left: 4px solid #dc3545 !important; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='none'">
                    <div class="card-body p-3 d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-uppercase fw-bold text-secondary" style="font-size: 0.68rem; letter-spacing: 0.5px;">Hibernating</span>
                            <h3 class="mb-0 fw-extrabold text-dark mt-1">{{ count($segments['Hibernating'] ?? []) }}</h3>
                        </div>
                        <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-flex align-items-center justify-content-center" style="width: 42px; height: 42px; flex-shrink: 0;">
                            <i class="bi bi-moon-stars-fill fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- New Customers -->
            <div class="col-md-2.4 col-lg col-12">
                <div class="card border-0 shadow-sm bg-white h-100" style="border-left: 4px solid #6c757d !important; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='none'">
                    <div class="card-body p-3 d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-uppercase fw-bold text-secondary" style="font-size: 0.68rem; letter-spacing: 0.5px;">New Customers</span>
                            <h3 class="mb-0 fw-extrabold text-dark mt-1">{{ count($segments['New Customers'] ?? []) }}</h3>
                        </div>
                        <div class="bg-secondary bg-opacity-10 text-secondary rounded-circle d-flex align-items-center justify-content-center" style="width: 42px; height: 42px; flex-shrink: 0;">
                            <i class="bi bi-person-plus-fill fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Tab Layout -->
    <div class="col-12">
        <div class="card border shadow-sm rounded-4 bg-white">
            <div class="card-header bg-light py-3 px-4 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="bi bi-funnel-fill"></i>
                    </span>
                    <div>
                        <h6 class="mb-0 fw-bold text-dark">Detail Segmentasi RFM & Sinkronisasi DMP</h6>
                        <small class="text-muted" style="font-size: 0.72rem;">Kelompokkan customer loyal vs pasif dan targetkan langsung lewat iklan retargeting.</small>
                    </div>
                </div>
                
                <!-- Search & Filters (Interactive Dynamic Filter) -->
                <div class="d-flex align-items-center gap-2 flex-grow-1 flex-md-grow-0 justify-content-end">
                    <div class="input-group input-group-sm" style="max-width: 250px;">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="customerSearch" class="form-control border-start-0 ps-0 rounded-end-3" placeholder="Cari Nama / HP...">
                    </div>
                </div>

                <!-- Navigation Tabs -->
                <ul class="nav nav-pills card-header-pills" id="rfmTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold small py-1 px-3" id="champions-tab" data-bs-toggle="tab" data-bs-target="#champions" type="button" role="tab" aria-controls="champions" aria-selected="true">🏆 Champions</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold small py-1 px-3 ms-1" id="loyal-tab" data-bs-toggle="tab" data-bs-target="#loyal" type="button" role="tab" aria-controls="loyal" aria-selected="false">❤️ Loyal</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold small py-1 px-3 ms-1" id="atRisk-tab" data-bs-toggle="tab" data-bs-target="#atRisk" type="button" role="tab" aria-controls="atRisk" aria-selected="false">⚠️ At Risk</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold small py-1 px-3 ms-1" id="hibernating-tab" data-bs-toggle="tab" data-bs-target="#hibernating" type="button" role="tab" aria-controls="hibernating" aria-selected="false">🌙 Hibernating</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold small py-1 px-3 ms-1" id="newCustomers-tab" data-bs-toggle="tab" data-bs-target="#newCustomers" type="button" role="tab" aria-controls="newCustomers" aria-selected="false">✨ New</button>
                    </li>
                </ul>
            </div>

            <div class="card-body p-4">
                <div class="tab-content" id="rfmTabContent">
                    @foreach (['Champions', 'Loyal', 'At Risk', 'Hibernating', 'New Customers'] as $segmentKey)
                        @php
                            $tabId = str_replace(' ', '', $segmentKey);
                            $tabCustomers = $segments[$segmentKey] ?? collect();
                            $colorMap = [
                                'Champions' => 'primary',
                                'Loyal' => 'success',
                                'At Risk' => 'warning',
                                'Hibernating' => 'danger',
                                'New Customers' => 'secondary'
                            ];
                            $themeColor = $colorMap[$segmentKey];
                        @endphp
                        <div class="tab-pane fade @if($loop->first) show active @endif" id="{{ $tabId }}" role="tabpanel" aria-labelledby="{{ $tabId }}-tab">
                            
                            <div class="row g-4">
                                <!-- Form Box: Sync this segment to TikTok -->
                                <div class="col-lg-4">
                                    <div class="card border border-{{ $themeColor }} border-opacity-25 rounded-3 bg-{{ $themeColor }} bg-opacity-5">
                                        <div class="card-body p-3">
                                            <h6 class="fw-bold text-dark mb-1 d-flex align-items-center gap-1">
                                                <i class="bi bi-cloud-upload-fill text-{{ $themeColor }}"></i>
                                                Sync Segmen {{ $segmentKey }}
                                            </h6>
                                            <p class="text-muted" style="font-size: 0.72rem; line-height: 1.35;">
                                                Kirim seluruh database kontak segmen <strong>{{ $segmentKey }}</strong> ini langsung ke audiens khusus iklan Anda.
                                            </p>
                                            
                                            <form action="{{ route('marketing.ads.rfm.sync') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="segment_name" value="{{ $segmentKey }}">
                                                
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size:.65rem;">Pilih Akun Iklan TikTok</label>
                                                    <select name="ads_account_id" class="form-select form-select-sm rounded-3" required>
                                                        <option value="">-- Pilih Akun --</option>
                                                        @foreach($adsAccounts as $acc)
                                                            <option value="{{ $acc->id }}">{{ $acc->account_name }} ({{ $acc->account_id }})</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <button type="submit" class="btn btn-{{ $themeColor }} btn-sm w-100 rounded-pill fw-bold" @if($tabCustomers->isEmpty() || $adsAccounts->isEmpty()) disabled @endif>
                                                    <i class="bi bi-send-fill me-1"></i> Sync ke Custom Audience
                                                </button>
                                                @if($adsAccounts->isEmpty())
                                                    <div class="form-text text-danger mt-1 text-center" style="font-size: .65rem;">
                                                        <i class="bi bi-x-circle-fill"></i> Akun TikTok Ads belum terhubung.
                                                    </div>
                                                @endif
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Customer list table -->
                                <div class="col-lg-8">
                                    <div class="table-responsive border rounded-3">
                                        <table class="table table-hover align-middle mb-0" style="font-size: 0.82rem;">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Nama Pelanggan</th>
                                                    <th>Nomor Handphone</th>
                                                    <th class="text-center">Recency</th>
                                                    <th class="text-center">Frequency</th>
                                                    <th class="text-end">Monetary</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($tabCustomers as $cust)
                                                    <tr class="customer-row">
                                                        <td>
                                                            <div class="fw-semibold text-dark search-name">{{ $cust['name'] }}</div>
                                                        </td>
                                                        <td>
                                                            <code class="search-phone">{{ substr($cust['phone'], 0, 5) }}****{{ substr($cust['phone'], -4) }}</code>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge bg-light text-dark rounded-pill border">
                                                                {{ $cust['recency'] }} Hari Lalu
                                                            </span>
                                                        </td>
                                                        <td class="text-center fw-bold">
                                                            {{ $cust['frequency'] }}x Order
                                                        </td>
                                                        <td class="text-end fw-bold text-dark">
                                                            Rp {{ number_format($cust['monetary'], 0, ',', '.') }}
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="5" class="text-center py-4 text-muted">
                                                            <i class="bi bi-inbox fs-4 d-block mb-1"></i>
                                                            Tidak ada pelanggan dalam segmen ini.
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const searchInput = document.getElementById('customerSearch');
    
    // Live Search Filter
    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        const activeTabPane = document.querySelector('.tab-pane.active');
        if (!activeTabPane) return;
        
        const rows = activeTabPane.querySelectorAll('.customer-row');
        
        rows.forEach(row => {
            const name = row.querySelector('.search-name').textContent.toLowerCase();
            const phone = row.querySelector('.search-phone').textContent.toLowerCase();
            
            if (name.includes(query) || phone.includes(query)) {
                row.style.setProperty('display', '', 'important');
            } else {
                row.style.setProperty('display', 'none', 'important');
            }
        });
    });

    // Clear search input when switching tabs
    const tabButtons = document.querySelectorAll('button[data-bs-toggle="tab"]');
    tabButtons.forEach(btn => {
        btn.addEventListener('shown.bs.tab', function() {
            searchInput.value = '';
            const activeTabPane = document.querySelector('.tab-pane.active');
            if (activeTabPane) {
                const rows = activeTabPane.querySelectorAll('.customer-row');
                rows.forEach(row => row.style.setProperty('display', '', 'important'));
            }
        });
    });
});
</script>
@endsection
