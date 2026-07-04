@extends('layouts.app')
@section('title', 'Simulator Profitability Flash Sale')
@section('page-title', 'Simulator Profitability Flash Sale')

@section('topbar-actions')
    <a href="{{ route('marketing.flash_sales.index') }}" class="btn btn-sm btn-light text-primary fw-bold px-3">
        <i class="bi bi-arrow-left me-1"></i> Dashboard Flash Sale
    </a>
@endsection

@section('content')
    <div class="row g-4">
        {{-- Form Inputs --}}
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent py-3 px-3 border-bottom">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-sliders text-primary me-2"></i>Parameter Simulator Promo</h6>
                </div>
                <div class="card-body p-3">
                    {{-- Select Product --}}
                    <div class="mb-3">
                        <label for="product_select" class="form-label fw-bold text-secondary small text-uppercase" style="font-size:.7rem;">Pilih Produk (Opsional untuk auto-fill)</label>
                        <select id="product_select" class="form-select form-select-sm">
                            <option value="">-- Manual Input / Pilih Produk --</option>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}" data-price="{{ (int)$p->price }}" data-cost="{{ (int)$p->cost_price }}">
                                    {{ $p->name }} (SKU: {{ $p->sku }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Harga Normal & HPP --}}
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size:.7rem;">Harga Normal (Rp)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">Rp</span>
                                <input type="number" id="calc_price" class="form-control" value="150000" min="0">
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size:.7rem;">HPP / Modal (Rp)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">Rp</span>
                                <input type="number" id="calc_cost" class="form-control" value="80000" min="0">
                            </div>
                        </div>
                    </div>

                    {{-- Diskon & Admin Fee --}}
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size:.7rem;">Diskon Flash Sale (%)</label>
                            <div class="input-group input-group-sm">
                                <input type="number" id="calc_disc" class="form-control" value="25" min="0" max="100" step="0.5">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size:.7rem;">Komisi Marketplace (%)</label>
                            <div class="input-group input-group-sm">
                                <input type="number" id="calc_admin" class="form-control" value="4.5" min="0" max="50" step="0.1">
                                <span class="input-group-text">%</span>
                            </div>
                            <div class="form-text" style="font-size:.68rem;">Estimasi biaya admin Shopee/TikTok</div>
                        </div>
                    </div>

                    {{-- Target Kuota / Volume --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size:.7rem;">Rencana Kuota Penjualan (Unit)</label>
                        <input type="number" id="calc_volume" class="form-control form-control-sm" value="50" min="1">
                    </div>
                </div>
            </div>
        </div>

        {{-- Interactive Results Output --}}
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-graph-up-arrow text-success me-2"></i>Hasil Proyeksi Keuntungan Promo</h6>
                    <div id="status_badge"></div>
                </div>

                <div class="card-body p-4">
                    {{-- Main Alert Result Box --}}
                    <div id="result_box" class="p-3 rounded-3 mb-4 text-center">
                        <small class="text-uppercase fw-bold opacity-75" style="font-size:.72rem;" id="result_subtitle">PROYEKSI LABA BERSIH PER UNIT</small>
                        <h2 class="fw-bold mb-1 mt-1" id="result_unit_profit">Rp 0</h2>
                        <div class="fw-semibold small" id="result_margin_percent">Margin: 0%</div>
                    </div>

                    {{-- Breakdown Table --}}
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-sm align-middle mb-0" style="font-size:.85rem;">
                            <tbody class="table-group-divider">
                                <tr>
                                    <td class="bg-light text-secondary fw-semibold">Harga Flash Sale Setelah Diskon</td>
                                    <td class="text-end fw-bold text-danger" id="out_flash_price">Rp 0</td>
                                </tr>
                                <tr>
                                    <td class="bg-light text-secondary fw-semibold">Biaya Komisi & Admin Marketplace</td>
                                    <td class="text-end text-muted" id="out_admin_fee">Rp 0</td>
                                </tr>
                                <tr>
                                    <td class="bg-light text-secondary fw-semibold">HPP / Modal Produk</td>
                                    <td class="text-end text-muted" id="out_cost_price">Rp 0</td>
                                </tr>
                                <tr class="table-active">
                                    <td class="fw-bold text-dark">Laba Bersih per Unit</td>
                                    <td class="text-end fw-bold fs-6" id="out_net_unit">Rp 0</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- Volume & Gross Profit Summary --}}
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3 text-center border">
                                <small class="text-muted text-uppercase fw-bold" style="font-size:.68rem;">Total Proyeksi Omset</small>
                                <h5 class="fw-bold text-dark mb-0 mt-1" id="out_total_rev">Rp 0</h5>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3 text-center border">
                                <small class="text-muted text-uppercase fw-bold" style="font-size:.68rem;">Total Proyeksi Laba Kotor</small>
                                <h5 class="fw-bold text-success mb-0 mt-1" id="out_total_profit">Rp 0</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const productSelect = document.getElementById('product_select');
        const calcPrice     = document.getElementById('calc_price');
        const calcCost      = document.getElementById('calc_cost');
        const calcDisc      = document.getElementById('calc_disc');
        const calcAdmin     = document.getElementById('calc_admin');
        const calcVolume    = document.getElementById('calc_volume');

        if (productSelect) {
            productSelect.addEventListener('change', function() {
                const opt = this.options[this.selectedIndex];
                if (this.value) {
                    calcPrice.value = opt.getAttribute('data-price') || 0;
                    calcCost.value  = opt.getAttribute('data-cost')  || 0;
                    calculate();
                }
            });
        }

        [calcPrice, calcCost, calcDisc, calcAdmin, calcVolume].forEach(input => {
            input.addEventListener('input', calculate);
        });

        function calculate() {
            const price  = parseFloat(calcPrice.value)  || 0;
            const cost   = parseFloat(calcCost.value)   || 0;
            const disc   = parseFloat(calcDisc.value)   || 0;
            const admin  = parseFloat(calcAdmin.value)  || 0;
            const volume = parseInt(calcVolume.value)  || 1;

            const flashPrice = price * (1 - (disc / 100));
            const adminFee   = flashPrice * (admin / 100);
            const netUnit    = flashPrice - cost - adminFee;
            const margin     = flashPrice > 0 ? (netUnit / flashPrice) * 100 : 0;

            const totalRev    = flashPrice * volume;
            const totalProfit = netUnit * volume;

            // Render text
            document.getElementById('out_flash_price').innerText = 'Rp ' + Math.round(flashPrice).toLocaleString('id-ID');
            document.getElementById('out_admin_fee').innerText   = 'Rp ' + Math.round(adminFee).toLocaleString('id-ID');
            document.getElementById('out_cost_price').innerText  = 'Rp ' + Math.round(cost).toLocaleString('id-ID');
            document.getElementById('out_net_unit').innerText    = 'Rp ' + Math.round(netUnit).toLocaleString('id-ID');

            document.getElementById('result_unit_profit').innerText   = 'Rp ' + Math.round(netUnit).toLocaleString('id-ID');
            document.getElementById('result_margin_percent').innerText = `Margin: ${margin.toFixed(1)}% dari harga promo`;

            document.getElementById('out_total_rev').innerText    = 'Rp ' + Math.round(totalRev).toLocaleString('id-ID');
            document.getElementById('out_total_profit').innerText = 'Rp ' + Math.round(totalProfit).toLocaleString('id-ID');

            // Status Badge & Styling
            const resultBox  = document.getElementById('result_box');
            const statusBadge = document.getElementById('status_badge');

            if (netUnit < 0) {
                resultBox.className = "p-3 rounded-3 mb-4 text-center bg-danger text-white";
                statusBadge.innerHTML = '<span class="badge bg-danger fs-6 px-3 py-1 rounded-pill"><i class="bi bi-exclamation-triangle-fill me-1"></i> PROMO RUGI</span>';
            } else if (margin < 5) {
                resultBox.className = "p-3 rounded-3 mb-4 text-center bg-warning text-dark";
                statusBadge.innerHTML = '<span class="badge bg-warning text-dark fs-6 px-3 py-1 rounded-pill"><i class="bi bi-exclamation-circle-fill me-1"></i> MARGIN TIPIS (< 5%)</span>';
            } else {
                resultBox.className = "p-3 rounded-3 mb-4 text-center bg-success text-white";
                statusBadge.innerHTML = '<span class="badge bg-success fs-6 px-3 py-1 rounded-pill"><i class="bi bi-check-circle-fill me-1"></i> PROFITABEL & AMAN</span>';
            }
        }

        calculate();
    });
    </script>
    @endpush
@endsection
