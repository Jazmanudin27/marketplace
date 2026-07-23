@extends('layouts.app')

@section('title', 'Kalkulator & Setting Harga Masal')

@section('content')
<div class="container-fluid px-4 py-4">

    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1 text-dark">
                <i class="fas fa-calculator text-primary me-2"></i>Kalkulator & Setting Harga Masal
            </h3>
            <p class="text-muted small mb-0">Hitung, simulasikan, dan sesuaikan harga jual masal untuk produk Anda dan toko marketplace.</p>
        </div>
        <div>
            <a href="{{ route('products.index') }}" class="btn btn-outline-secondary rounded-3 btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Kembali ke Produk
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Filter Card -->
    <div class="card border shadow-sm mb-4 rounded-3">
        <div class="card-header bg-light p-3 border-bottom">
            <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-filter text-primary me-2"></i>Filter Produk</h6>
        </div>
        <div class="card-body p-3">
            <form method="GET" action="{{ route('products.bulk_price_calculator') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-secondary">Cari SKU / Nama</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Contoh: Baju Polos..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-secondary">Kategori</label>
                    <select name="category_id" class="form-select form-select-sm">
                        <option value="">Semua Kategori</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-secondary">Brand</label>
                    <select name="brand_id" class="form-select form-select-sm">
                        <option value="">Semua Brand</option>
                        @foreach ($brands as $b)
                            <option value="{{ $b->id }}" {{ request('brand_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-secondary">Range HPP (Cost)</label>
                    <div class="input-group input-group-sm">
                        <input type="number" name="min_cost_price" class="form-control" placeholder="Min" value="{{ request('min_cost_price') }}">
                        <input type="number" name="max_cost_price" class="form-control" placeholder="Max" value="{{ request('max_cost_price') }}">
                    </div>
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary btn-sm rounded-3 w-100 fw-semibold">
                        <i class="fas fa-search me-1"></i> Filter Data
                    </button>
                    <a href="{{ route('products.bulk_price_calculator') }}" class="btn btn-light btn-sm border rounded-3 text-muted">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Calculator Formula Control Panel -->
    <div class="card border shadow-sm mb-4 rounded-3 border-primary border-opacity-25 bg-primary bg-opacity-10">
        <div class="card-header bg-primary text-white p-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold"><i class="fas fa-square-root-variable me-2"></i>Formulasi Pengaturan Harga Masal</h6>
            <span class="badge bg-white text-primary fw-bold">Live Simulation</span>
        </div>
        <div class="card-body p-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-dark">Mode Kalkulasi</label>
                    <select id="calcMode" class="form-select form-select-sm fw-semibold">
                        <option value="cost_plus_percent">HPP (Cost) + Margin (%)</option>
                        <option value="cost_plus_flat">HPP (Cost) + Nominal Flat (Rp)</option>
                        <option value="percent_add">Harga Lama + Persentase (%)</option>
                        <option value="flat_add">Harga Lama + Nominal Flat (Rp)</option>
                        <option value="target_net_margin">Target Profit & Admin Marketplace (%)</option>
                        <option value="flat_set">Set Harga Seragam (Fixed Rp)</option>
                    </select>
                </div>

                <div class="col-md-2" id="valContainer">
                    <label class="form-label small fw-bold text-dark" id="valLabel">Margin (%)</label>
                    <input type="number" step="any" id="calcValue" class="form-control form-control-sm font-monospace fw-bold" value="30">
                </div>

                <div class="col-md-2 d-none" id="adminFeeContainer">
                    <label class="form-label small fw-bold text-dark">Est. Admin Fee Marketplace (%)</label>
                    <input type="number" step="any" id="adminFeeValue" class="form-control form-control-sm font-monospace fw-bold text-danger" value="5.5">
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-bold text-dark">Aturan Pembulatan Harga</label>
                    <select id="roundRule" class="form-select form-select-sm">
                        <option value="none">Tanpa Pembulatan</option>
                        <option value="round_100">Ratusan Terdekat (contoh: 15.420 -> 15.400)</option>
                        <option value="round_500">500 Terdekat (contoh: 15.420 -> 15.500)</option>
                        <option value="round_1000">Ribuan Terdekat (contoh: 15.420 -> 15.000)</option>
                        <option value="end_900">Akhiran Psikologis .900 (contoh: 15.420 -> 15.900)</option>
                        <option value="end_990">Akhiran Psikologis .990 (contoh: 15.420 -> 15.990)</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <button type="button" id="btnApplyCalc" class="btn btn-primary btn-sm w-100 fw-bold py-1.5 shadow-sm">
                        <i class="fas fa-bolt me-1"></i> Jalankan Simulasi
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Products Form -->
    <form method="POST" action="{{ route('products.bulk_price_calculator.update') }}" id="bulkPriceForm">
        @csrf
        <div class="card border shadow-sm rounded-3">
            <div class="card-header bg-white p-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <div class="form-check m-0">
                        <input class="form-check-input" type="checkbox" id="selectAll" checked>
                        <label class="form-check-label fw-bold text-dark small" for="selectAll">
                            Pilih Semua (<span id="selectedCount">{{ $products->count() }}</span> / {{ $products->count() }} produk)
                        </label>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" name="sync_to_marketplace" value="1" id="syncToMarketplace" checked>
                        <label class="form-check-label fw-bold small text-primary" for="syncToMarketplace">
                            <i class="fas fa-sync me-1"></i> Langsung Sync ke Shopee, TikTok, Tokopedia & Lazada
                        </label>
                    </div>
                    <button type="submit" class="btn btn-success btn-sm fw-bold px-4 rounded-3 shadow-sm" id="btnSubmitForm">
                        <i class="fas fa-save me-1"></i> Simpan & Terapkan Harga
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="productsTable">
                    <thead class="bg-light border-bottom">
                        <tr class="small text-muted text-uppercase">
                            <th class="ps-3" style="width: 40px;">#</th>
                            <th style="width: 250px;">Produk & SKU</th>
                            <th>Kategori & Brand</th>
                            <th class="text-end" style="width: 130px;">HPP (Cost)</th>
                            <th class="text-end" style="width: 130px;">Harga Lama</th>
                            <th class="text-end" style="width: 170px;">Harga Baru (Simulasi)</th>
                            <th class="text-end" style="width: 130px;">Selisih (Rp)</th>
                            <th class="text-end" style="width: 120px;">Est. Margin</th>
                            <th class="pe-3 text-center" style="width: 110px;">Marketplace</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $index => $product)
                            @php
                                $cost = (float) $product->cost_price;
                                $price = (float) $product->price;
                                $marginVal = $price - $cost;
                                $marginPct = $price > 0 ? round(($marginVal / $price) * 100, 1) : 0;
                            @endphp
                            <tr class="product-row" data-id="{{ $product->id }}" data-cost="{{ $cost }}" data-old-price="{{ $price }}">
                                <td class="ps-3">
                                    <input type="checkbox" class="form-check-input item-checkbox" name="products[{{ $index }}][id]" value="{{ $product->id }}" checked>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark small text-truncate" style="max-width: 230px;" title="{{ $product->name }}">
                                        {{ $product->name }}
                                    </div>
                                    <div class="font-monospace text-muted" style="font-size: 0.75rem;">
                                        SKU: {{ $product->sku }}
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border small fw-normal">{{ $product->category->name ?? '-' }}</span>
                                    @if ($product->brand)
                                        <span class="badge bg-secondary-subtle text-secondary small fw-normal">{{ $product->brand->name }}</span>
                                    @endif
                                </td>
                                <td class="text-end font-monospace small text-secondary">
                                    Rp {{ number_format($cost, 0, ',', '.') }}
                                </td>
                                <td class="text-end font-monospace small text-dark fw-semibold">
                                    Rp {{ number_format($price, 0, ',', '.') }}
                                </td>
                                <td class="text-end">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white small">Rp</span>
                                        <input type="number" step="any" name="products[{{ $index }}][new_price]" class="form-control form-control-sm text-end font-monospace fw-bold new-price-input text-primary" value="{{ $price }}">
                                    </div>
                                </td>
                                <td class="text-end font-monospace small diff-cell fw-semibold text-muted">
                                    Rp 0
                                </td>
                                <td class="text-end">
                                    <span class="badge bg-success-subtle text-success border border-success border-opacity-25 margin-badge font-monospace">
                                        {{ $marginPct }}%
                                    </span>
                                </td>
                                <td class="pe-3 text-center">
                                    @if ($product->marketplaceProducts->isNotEmpty())
                                        @foreach ($product->marketplaceProducts->groupBy('store.channel.code') as $code => $mps)
                                            <span class="badge bg-secondary channel-{{ $code }} text-uppercase me-0.5" style="font-size: 0.65rem;" title="{{ $mps->count() }} store">
                                                {{ $code }}
                                            </span>
                                        @endforeach
                                    @else
                                        <span class="text-muted" style="font-size: 0.75rem;">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="fas fa-box-open fa-3x mb-3 opacity-25 d-block"></i>
                                    Tidak ada produk master aktif yang sesuai dengan filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-footer bg-light p-3 d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Menampilkan <strong>{{ $products->count() }}</strong> produk siap diubah harganya.
                </div>
                <button type="submit" class="btn btn-success btn-sm fw-bold px-4 rounded-3 shadow-sm">
                    <i class="fas fa-save me-1"></i> Simpan & Terapkan Harga
                </button>
            </div>
        </div>
    </form>

</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const calcMode = document.getElementById('calcMode');
    const valContainer = document.getElementById('valContainer');
    const valLabel = document.getElementById('valLabel');
    const calcValue = document.getElementById('calcValue');
    const adminFeeContainer = document.getElementById('adminFeeContainer');
    const adminFeeValue = document.getElementById('adminFeeValue');
    const roundRule = document.getElementById('roundRule');
    const btnApplyCalc = document.getElementById('btnApplyCalc');
    
    const selectAll = document.getElementById('selectAll');
    const itemCheckboxes = document.querySelectorAll('.item-checkbox');
    const selectedCountSpan = document.getElementById('selectedCount');
    const productRows = document.querySelectorAll('.product-row');

    // Labels & fields toggle based on mode
    function updateCalcModeUI() {
        const mode = calcMode.value;
        adminFeeContainer.classList.add('d-none');

        if (mode === 'cost_plus_percent') {
            valLabel.innerText = 'Margin (%)';
            calcValue.value = calcValue.value || 30;
        } else if (mode === 'cost_plus_flat') {
            valLabel.innerText = 'Margin Flat (Rp)';
            calcValue.value = calcValue.value || 25000;
        } else if (mode === 'percent_add') {
            valLabel.innerText = 'Penyesuaian (%) (+/-)';
            calcValue.value = calcValue.value || 10;
        } else if (mode === 'flat_add') {
            valLabel.innerText = 'Penyesuaian (Rp) (+/-)';
            calcValue.value = calcValue.value || 5000;
        } else if (mode === 'target_net_margin') {
            valLabel.innerText = 'Target Profit (Rp)';
            calcValue.value = calcValue.value || 20000;
            adminFeeContainer.classList.remove('d-none');
        } else if (mode === 'flat_set') {
            valLabel.innerText = 'Set Harga (Rp)';
            calcValue.value = calcValue.value || 100000;
        }
    }

    calcMode.addEventListener('change', updateCalcModeUI);

    // Apply Rounding Rule
    function applyRounding(val, rule) {
        if (rule === 'none' || isNaN(val) || val <= 0) return Math.max(0, Math.round(val));

        if (rule === 'round_100') {
            return Math.round(val / 100) * 100;
        } else if (rule === 'round_500') {
            return Math.round(val / 500) * 500;
        } else if (rule === 'round_1000') {
            return Math.round(val / 1000) * 1000;
        } else if (rule === 'end_900') {
            let base = Math.floor(val / 1000) * 1000;
            return base + 900;
        } else if (rule === 'end_990') {
            let base = Math.floor(val / 1000) * 1000;
            return base + 990;
        }
        return Math.round(val);
    }

    // Run Live Calculation Simulation
    function runSimulation() {
        const mode = calcMode.value;
        const inputVal = parseFloat(calcValue.value) || 0;
        const adminFee = parseFloat(adminFeeValue.value) || 0;
        const rule = roundRule.value;

        productRows.forEach(row => {
            const checkbox = row.querySelector('.item-checkbox');
            if (!checkbox.checked) return;

            const cost = parseFloat(row.dataset.cost) || 0;
            const oldPrice = parseFloat(row.dataset.oldPrice) || 0;
            const newPriceInput = row.querySelector('.new-price-input');

            let calculatedPrice = oldPrice;

            if (mode === 'cost_plus_percent') {
                calculatedPrice = cost * (1 + (inputVal / 100));
            } else if (mode === 'cost_plus_flat') {
                calculatedPrice = cost + inputVal;
            } else if (mode === 'percent_add') {
                calculatedPrice = oldPrice * (1 + (inputVal / 100));
            } else if (mode === 'flat_add') {
                calculatedPrice = oldPrice + inputVal;
            } else if (mode === 'target_net_margin') {
                let feePct = adminFee / 100;
                if (feePct >= 1) feePct = 0.99; // guard against div by 0
                calculatedPrice = (cost + inputVal) / (1 - feePct);
            } else if (mode === 'flat_set') {
                calculatedPrice = inputVal;
            }

            calculatedPrice = applyRounding(calculatedPrice, rule);
            newPriceInput.value = calculatedPrice;

            updateRowMetrics(row);
        });
    }

    // Update single row difference & margin badge
    function updateRowMetrics(row) {
        const cost = parseFloat(row.dataset.cost) || 0;
        const oldPrice = parseFloat(row.dataset.oldPrice) || 0;
        const newPriceInput = row.querySelector('.new-price-input');
        const diffCell = row.querySelector('.diff-cell');
        const marginBadge = row.querySelector('.margin-badge');

        const newPrice = parseFloat(newPriceInput.value) || 0;
        const diff = newPrice - oldPrice;

        if (diff > 0) {
            diffCell.className = 'text-end font-monospace small diff-cell fw-semibold text-success';
            diffCell.innerText = '+ Rp ' + Math.abs(diff).toLocaleString('id-ID');
        } else if (diff < 0) {
            diffCell.className = 'text-end font-monospace small diff-cell fw-semibold text-danger';
            diffCell.innerText = '- Rp ' + Math.abs(diff).toLocaleString('id-ID');
        } else {
            diffCell.className = 'text-end font-monospace small diff-cell fw-semibold text-muted';
            diffCell.innerText = 'Rp 0';
        }

        const marginVal = newPrice - cost;
        const marginPct = newPrice > 0 ? ((marginVal / newPrice) * 100).toFixed(1) : 0;

        marginBadge.innerText = marginPct + '%';
        if (marginPct >= 20) {
            marginBadge.className = 'badge bg-success-subtle text-success border border-success border-opacity-25 margin-badge font-monospace';
        } else if (marginPct > 0) {
            marginBadge.className = 'badge bg-warning-subtle text-warning-emphasis border border-warning border-opacity-25 margin-badge font-monospace';
        } else {
            marginBadge.className = 'badge bg-danger-subtle text-danger border border-danger border-opacity-25 margin-badge font-monospace';
        }
    }

    // Listen to manual price edits in input
    document.querySelectorAll('.new-price-input').forEach(input => {
        input.addEventListener('input', function () {
            const row = this.closest('.product-row');
            updateRowMetrics(row);
        });
    });

    // Handle Select All
    selectAll.addEventListener('change', function () {
        itemCheckboxes.forEach(cb => cb.checked = this.checked);
        updateSelectedCount();
    });

    itemCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateSelectedCount);
    });

    function updateSelectedCount() {
        const checkedCount = document.querySelectorAll('.item-checkbox:checked').length;
        selectedCountSpan.innerText = checkedCount;
    }

    btnApplyCalc.addEventListener('click', runSimulation);

    // Initial trigger
    updateCalcModeUI();
    productRows.forEach(row => updateRowMetrics(row));
});
</script>
@endpush
@endsection
