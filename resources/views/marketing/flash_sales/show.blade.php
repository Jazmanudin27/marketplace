@extends('layouts.app')
@section('title', 'Detail Flash Sale - ' . $flashSale->title)
@section('page-title', 'Detail Flash Sale')

@section('topbar-actions')
    <a href="{{ route('public.promo.flash_sale', $flashSale->id) }}" target="_blank" class="btn btn-sm btn-light text-primary me-2 fw-bold">
        <i class="bi bi-globe me-1"></i> Landing Page Publik
    </a>
    <form action="{{ route('marketing.flash_sales.sync', $flashSale->id) }}" method="POST" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-sm btn-warning me-2 fw-bold text-dark" onclick="return confirm('Proses sinkronisasi promo Flash Sale ke Shopee & TikTok Seller Center?')">
            <i class="bi bi-arrow-repeat me-1"></i> Sync ke Marketplace
        </button>
    </form>
    <a href="{{ route('marketing.flash_sales.edit', $flashSale->id) }}" class="btn btn-sm btn-outline-light me-2 fw-bold">
        <i class="bi bi-pencil me-1"></i> Edit Event
    </a>
    <a href="{{ route('marketing.flash_sales.index') }}" class="btn btn-sm btn-light text-primary fw-bold px-3">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
@endsection

@section('content')
    @php
        $compStatus = $flashSale->computed_status;
        $isLive = $compStatus === 'ACTIVE';
        $isUpcoming = $compStatus === 'UPCOMING';
    @endphp

    {{-- Header Banner & Status --}}
    <div class="card border-0 shadow-sm mb-4 {{ $isLive ? 'border-start border-5 border-danger' : '' }}">
        <div class="card-body p-4">
            <div class="row align-items-center g-3">
                <div class="col-12 col-md-7">
                    <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                        <span class="badge {{ $flashSale->status_badge_class }} fw-bold rounded-pill px-3 py-1">
                            {{ $flashSale->status_label }}
                        </span>
                        @if($flashSale->is_synced)
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2 py-1 small" title="Terakhir di-sync: {{ $flashSale->last_synced_at ? $flashSale->last_synced_at->format('d M Y H:i') : '' }}">
                                <i class="bi bi-check-circle-fill me-1"></i> Synced to Marketplace
                            </span>
                        @else
                            <span class="badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25 rounded-pill px-2 py-1 small">
                                <i class="bi bi-exclamation-circle me-1"></i> Belum Sync Marketplace
                            </span>
                        @endif
                        <span class="text-muted small"><i class="bi bi-shop me-1"></i>{{ $flashSale->store ? $flashSale->store->name : 'Semua Toko (Global Promo)' }}</span>
                    </div>
                    <h3 class="fw-bold text-dark mb-2">{{ $flashSale->title }}</h3>
                    <div class="d-flex flex-wrap align-items-center gap-3 text-muted small">
                        <div><i class="bi bi-calendar-check text-primary me-1"></i> Mulai: <strong>{{ $flashSale->start_time->format('d M Y, H:i') }} WIB</strong></div>
                        <div><i class="bi bi-calendar-x text-danger me-1"></i> Selesai: <strong>{{ $flashSale->end_time->format('d M Y, H:i') }} WIB</strong></div>
                    </div>
                    @if($flashSale->notes)
                        <div class="mt-2 text-secondary small fst-italic"><i class="bi bi-info-circle me-1"></i>{{ $flashSale->notes }}</div>
                    @endif
                    @if($flashSale->sync_notes)
                        <div class="mt-1 text-success small fw-semibold"><i class="bi bi-cloud-check me-1"></i>{{ $flashSale->sync_notes }}</div>
                    @endif
                </div>

                <div class="col-12 col-md-5 text-md-end">
                    @if($isLive)
                        <div class="p-3 bg-danger bg-opacity-10 rounded-3 text-danger border border-danger border-opacity-25 d-inline-block text-start" style="min-width: 240px;">
                            <small class="fw-bold text-uppercase d-block mb-1" style="font-size:.68rem;"><i class="bi bi-alarm-fill me-1"></i> Sisa Waktu Flash Sale:</small>
                            <div class="fw-bold font-monospace fs-3 flash-countdown" data-target="{{ $flashSale->end_time->toIso8601String() }}">00:00:00</div>
                        </div>
                    @elseif($isUpcoming)
                        <div class="p-3 bg-warning bg-opacity-10 rounded-3 text-warning-emphasis border border-warning border-opacity-25 d-inline-block text-start" style="min-width: 240px;">
                            <small class="fw-bold text-uppercase d-block mb-1" style="font-size:.68rem;"><i class="bi bi-clock-history me-1"></i> Dimulai Dalam:</small>
                            <div class="fw-bold font-monospace fs-3 flash-countdown" data-target="{{ $flashSale->start_time->toIso8601String() }}">00:00:00</div>
                        </div>
                    @else
                        <span class="badge bg-secondary fs-6 px-3 py-2 rounded-3">Event Telah Berakhir</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Analytics Dashboard Fitur #3 --}}
    <div class="row g-3 mb-4">
        {{-- Total Omset --}}
        <div class="col-12 col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <small class="text-secondary text-uppercase fw-bold" style="font-size:.7rem;">Total Omset Flash Sale</small>
                    <h4 class="fw-bold text-success mb-0 mt-1">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</h4>
                    <small class="text-muted" style="font-size:.72rem;">Dari {{ $totalSoldCount }} unit barang terjual</small>
                </div>
            </div>
        </div>

        {{-- Penyerapan Stok % --}}
        <div class="col-12 col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <small class="text-secondary text-uppercase fw-bold" style="font-size:.7rem;">Sell-Through Rate (Penyerapan)</small>
                    <h4 class="fw-bold text-primary mb-0 mt-1">{{ $sellThroughRate }}%</h4>
                    <div class="progress mt-2" style="height: 6px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: {{ min(100, $sellThroughRate) }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Estimasi Laba Kotor --}}
        <div class="col-12 col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <small class="text-secondary text-uppercase fw-bold" style="font-size:.7rem;">Estimasi Laba Kotor</small>
                    <h4 class="fw-bold {{ $estimatedProfit >= 0 ? 'text-dark' : 'text-danger' }} mb-0 mt-1">
                        Rp {{ number_format($estimatedProfit, 0, ',', '.') }}
                    </h4>
                    <small class="text-muted" style="font-size:.72rem;">Setiap unit dikurangi HPP produk</small>
                </div>
            </div>
        </div>

        {{-- Top Selling SKU --}}
        <div class="col-12 col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <small class="text-secondary text-uppercase fw-bold" style="font-size:.7rem;">Produk Terlaris</small>
                    @if($topSellingItem && $topSellingItem->sold_count > 0)
                        <h6 class="fw-bold text-dark mb-0 mt-1 text-truncate" title="{{ $topSellingItem->masterProduct->name }}">
                            {{ $topSellingItem->masterProduct->name }}
                        </h6>
                        <small class="text-muted" style="font-size:.72rem;">{{ $topSellingItem->sold_count }} unit terjual</small>
                    @else
                        <h6 class="fw-bold text-muted mb-0 mt-1">— Belum ada order —</h6>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content: Tambah SKU & Tabel Produk Flash Sale --}}
    <div class="row g-4">
        {{-- Form Tambah SKU #2 --}}
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent py-3 px-3 border-bottom">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-plus-square text-primary me-2"></i>Tambah Produk Ke Flash Sale</h6>
                </div>
                <div class="card-body p-3">
                    @if($masterProducts->isEmpty())
                        <div class="alert alert-info small mb-0">
                            <i class="bi bi-info-circle me-1"></i> Semua produk aktif sudah didaftarkan pada event ini.
                        </div>
                    @else
                        <form action="{{ route('marketing.flash_sales.items.store', $flashSale->id) }}" method="POST">
                            @csrf

                            <div class="mb-3">
                                <label for="master_product_id" class="form-label fw-bold text-secondary small text-uppercase" style="font-size:.7rem;">Pilih Produk Master <span class="text-danger">*</span></label>
                                <select name="master_product_id" id="master_product_id" class="form-select form-select-sm" required>
                                    <option value="">-- Pilih Produk --</option>
                                    @foreach($masterProducts as $p)
                                        <option value="{{ $p->id }}" data-price="{{ (int)$p->price }}" data-cost="{{ (int)$p->cost_price }}" data-stock="{{ $p->stock }}">
                                            {{ $p->name }} (SKU: {{ $p->sku }}) — Rp {{ number_format($p->price, 0, ',', '.') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Preview Info Produk --}}
                            <div id="product-info-box" class="p-2 bg-light rounded mb-3 small d-none">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Harga Normal:</span>
                                    <strong id="preview-price">Rp 0</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">HPP / Modal:</span>
                                    <strong id="preview-cost">Rp 0</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Stok Gudang:</span>
                                    <strong id="preview-stock">0 unit</strong>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="flash_sale_price" class="form-label fw-bold text-secondary small text-uppercase" style="font-size:.7rem;">Harga Flash Sale (Rp) <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" name="flash_sale_price" id="flash_sale_price" class="form-control" placeholder="0" min="1" required>
                                </div>
                                <div id="discount-preview" class="form-text fw-semibold text-danger small mt-1"></div>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label for="quota" class="form-label fw-bold text-secondary small text-uppercase" style="font-size:.7rem;">Kuota Promo <span class="text-danger">*</span></label>
                                    <input type="number" name="quota" id="quota" class="form-control form-control-sm" placeholder="10" min="1" value="10" required>
                                </div>
                                <div class="col-6">
                                    <label for="max_purchase" class="form-label fw-bold text-secondary small text-uppercase" style="font-size:.7rem;">Max / User</label>
                                    <input type="number" name="max_purchase" id="max_purchase" class="form-control form-control-sm" placeholder="0 = bebas" min="0" value="0">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold py-2">
                                <i class="bi bi-plus-circle me-1"></i> Tambahkan ke Promo
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        {{-- Tabel Daftar SKU Flash Sale #2 --}}
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-list-stars text-danger me-2"></i>Daftar Produk Promo Flash Sale ({{ $items->count() }} SKU)</h6>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr class="text-uppercase text-muted" style="font-size:.72rem;letter-spacing:.5px;">
                                    <th class="border-0 px-3 py-3">Produk / SKU</th>
                                    <th class="border-0 px-3 py-3">Harga Normal</th>
                                    <th class="border-0 px-3 py-3">Harga Flash Sale</th>
                                    <th class="border-0 px-3 py-3">Diskon</th>
                                    <th class="border-0 px-3 py-3">Kuota & Terjual</th>
                                    <th class="border-0 px-3 py-3 text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($items as $item)
                                    @php
                                        $p = $item->masterProduct;
                                        $soldPercent = $item->sell_through_rate;
                                        $unitMargin = $item->margin_percentage;
                                        $isLoss = $item->unit_profit < 0;
                                    @endphp
                                    <tr>
                                        <td class="px-3 py-3">
                                            <div class="fw-bold text-dark small">{{ $p->name }}</div>
                                            <div class="text-muted" style="font-size:.72rem;">SKU: {{ $p->sku }}</div>
                                        </td>
                                        <td class="px-3 py-3 text-muted small text-decoration-line-through">
                                            Rp {{ number_format($item->original_price, 0, ',', '.') }}
                                        </td>
                                        <td class="px-3 py-3">
                                            <div class="fw-bold text-danger small">
                                                Rp {{ number_format($item->flash_sale_price, 0, ',', '.') }}
                                            </div>
                                            @if($isLoss)
                                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25" style="font-size:.65rem;">⚠️ Rugi HPP</span>
                                            @elseif($unitMargin < 5)
                                                <span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25" style="font-size:.65rem;">Margin Tipis ({{ $unitMargin }}%)</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3">
                                            <span class="badge bg-danger fw-bold rounded-pill px-2 py-1" style="font-size:.7rem;">
                                                -{{ $item->discount_percentage }}%
                                            </span>
                                        </td>
                                        <td class="px-3 py-3" style="min-width: 140px;">
                                            <div class="d-flex justify-content-between align-items-center mb-1" style="font-size:.72rem;">
                                                <span class="fw-bold text-dark">{{ $item->sold_count }} / {{ $item->quota }}</span>
                                                <span class="text-muted">{{ $soldPercent }}%</span>
                                            </div>
                                            <div class="progress" style="height: 6px;">
                                                <div class="progress-bar bg-danger" role="progressbar" style="width: {{ min(100, $soldPercent) }}%"></div>
                                            </div>
                                        </td>
                                        <td class="px-3 py-3 text-end">
                                            <form action="{{ route('marketing.flash_sales.items.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Keluarkan produk ini dari Flash Sale?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-3 px-2" title="Hapus dari Promo">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted small">
                                            Belum ada produk yang didaftarkan ke dalam event Flash Sale ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Countdown
        function updateCountdowns() {
            document.querySelectorAll('.flash-countdown').forEach(el => {
                const targetDate = new Date(el.getAttribute('data-target')).getTime();
                const now = new Date().getTime();
                const diff = targetDate - now;

                if (diff <= 0) {
                    el.innerHTML = "00:00:00";
                    return;
                }

                const hours = Math.floor(diff / (1000 * 60 * 60));
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((diff % (1000 * 60)) / 1000);

                el.innerHTML = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            });
        }
        updateCountdowns();
        setInterval(updateCountdowns, 1000);

        // Product selector dynamic price & discount calculator
        const productSelect = document.getElementById('master_product_id');
        const flashPriceInput = document.getElementById('flash_sale_price');
        const discountPreview = document.getElementById('discount-preview');
        const infoBox = document.getElementById('product-info-box');

        if (productSelect) {
            productSelect.addEventListener('change', function() {
                const opt = this.options[this.selectedIndex];
                if (!this.value) {
                    infoBox.classList.add('d-none');
                    return;
                }

                const price = parseFloat(opt.getAttribute('data-price')) || 0;
                const cost = parseFloat(opt.getAttribute('data-cost')) || 0;
                const stock = opt.getAttribute('data-stock') || 0;

                document.getElementById('preview-price').innerText = 'Rp ' + price.toLocaleString('id-ID');
                document.getElementById('preview-cost').innerText = 'Rp ' + cost.toLocaleString('id-ID');
                document.getElementById('preview-stock').innerText = stock + ' unit';
                infoBox.classList.remove('d-none');

                // Default flash sale price: 10% discount
                if (!flashPriceInput.value) {
                    flashPriceInput.value = Math.floor(price * 0.9);
                }
                calcDiscount();
            });
        }

        if (flashPriceInput) {
            flashPriceInput.addEventListener('input', calcDiscount);
        }

        function calcDiscount() {
            const opt = productSelect.options[productSelect.selectedIndex];
            if (!productSelect.value) return;

            const price = parseFloat(opt.getAttribute('data-price')) || 0;
            const flashPrice = parseFloat(flashPriceInput.value) || 0;
            const cost = parseFloat(opt.getAttribute('data-cost')) || 0;

            if (price > 0 && flashPrice > 0) {
                const disc = Math.round(((price - flashPrice) / price) * 100);
                const unitProfit = flashPrice - cost;

                let msg = `Hemat ${disc}% dari harga normal.`;
                if (unitProfit < 0) {
                    msg += ` ⚠️ Peringatan: Rugi Rp ${Math.abs(unitProfit).toLocaleString('id-ID')} per unit!`;
                    discountPreview.className = "form-text fw-semibold text-danger small mt-1";
                } else {
                    msg += ` Laba kotor per unit: Rp ${unitProfit.toLocaleString('id-ID')}`;
                    discountPreview.className = "form-text fw-semibold text-success small mt-1";
                }
                discountPreview.innerText = msg;
            } else {
                discountPreview.innerText = '';
            }
        }
    });
    </script>
    @endpush
@endsection
