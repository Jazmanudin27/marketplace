@extends('layouts.app')

@section('title', 'Kalkulator Target ROAS & Profitabilitas Iklan')
@section('page-title', 'Kalkulator Target ROAS & BEP')

@section('topbar-actions')
    <a href="{{ route('marketing.ads.index') }}" class="btn btn-sm btn-light text-primary fw-bold px-3">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard Iklan
    </a>
@endsection

@section('content')
<div class="row g-3">
    <!-- Left Column: Inputs & BEP Summary -->
    <div class="col-lg-5 col-md-12">
        <div class="card border shadow-sm rounded-4 bg-white mb-3">
            <div class="card-header bg-primary bg-opacity-10 py-3 px-4 border-bottom d-flex align-items-center gap-2">
                <span class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 28px; height: 28px;">
                    <i class="bi bi-sliders"></i>
                </span>
                <div>
                    <h6 class="mb-0 fw-bold text-dark">Parameter Keuangan Produk</h6>
                    <small class="text-muted" style="font-size: 0.72rem;">Pilih produk atau masukkan data manual.</small>
                </div>
            </div>
            <div class="card-body p-4">
                <form id="roasCalcForm">
                    <!-- Dropdown Pilih Produk -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-primary small text-uppercase" style="font-size:.68rem;">Pilih Produk Toko (Mengambil Harga & HPP Otomatis)</label>
                        <select id="productSelect" class="form-select form-select-sm rounded-3 border-primary">
                            <option value="">-- Input Manual --</option>
                            @foreach($products as $prod)
                                <option value="{{ $prod->id }}" data-price="{{ $prod->price }}" data-cost="{{ $prod->cost ?: 0 }}">
                                    {{ $prod->name }} (SKU: {{ $prod->sku ?: '-' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size:.65rem;">Harga Jual Produk (Rp)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light">Rp</span>
                                <input type="number" id="salePrice" class="form-control rounded-end-3" value="229900" min="1" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size:.65rem;">COGS / HPP Produk (Rp)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light">Rp</span>
                                <input type="number" id="cogs" class="form-control rounded-end-3" value="85000" min="0" required>
                            </div>
                        </div>
                    </div>

                    <!-- Marketplace Fees & Programs Accordion -->
                    <div class="accordion border rounded-3 mb-4" id="feeAccordion">
                        <div class="accordion-item border-0">
                            <h2 class="accordion-header">
                                <button class="accordion-button bg-light py-2.5 px-3 fw-bold text-dark collapsed" style="font-size: 0.78rem;" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFees" aria-expanded="false" aria-controls="collapseFees">
                                    <i class="bi bi-wallet2 text-primary me-2"></i> Rincian Potongan Marketplace (Shopee/TikTok)
                                </button>
                            </h2>
                            <div id="collapseFees" class="accordion-collapse collapse" data-bs-parent="#feeAccordion">
                                <div class="accordion-body p-3 bg-light bg-opacity-25" style="font-size: 0.8rem;">
                                    <h6 class="fw-bold text-secondary small text-uppercase border-bottom pb-1 mb-2" style="font-size:.65rem;">Biaya Administrasi & Layanan</h6>
                                    <div class="row g-2 mb-3">
                                        <div class="col-6">
                                            <label class="form-label text-muted small mb-1">Biaya Admin (%)</label>
                                            <input type="number" id="adminFee" class="form-control form-control-sm" value="8.25" step="0.01">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label text-muted small mb-1">Premi (%)</label>
                                            <input type="number" id="premiFee" class="form-control form-control-sm" value="0.50" step="0.01">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label text-muted small mb-1">Biaya Tetap Per Pesanan (Rp)</label>
                                            <input type="number" id="fixedFee" class="form-control form-control-sm" value="1250">
                                        </div>
                                    </div>

                                    <h6 class="fw-bold text-secondary small text-uppercase border-bottom pb-1 mb-2" style="font-size:.65rem;">Program Pemasaran (Ikut Serta)</h6>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label text-muted small mb-1">Gratis Ongkir Xtra (%)</label>
                                            <input type="number" id="ongkirXtra" class="form-control form-control-sm" value="7.50" step="0.01">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label text-muted small mb-1">Promo Xtra (%)</label>
                                            <input type="number" id="promoXtra" class="form-control form-control-sm" value="4.50" step="0.01">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label text-muted small mb-1">Promo Xtra+ / Plus (%)</label>
                                            <input type="number" id="promoPlus" class="form-control form-control-sm" value="2.00" step="0.01">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label text-muted small mb-1">Live Xtra (%)</label>
                                            <input type="number" id="liveXtra" class="form-control form-control-sm" value="2.00" step="0.01">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label text-muted small mb-1">Spaylater Buyer (3 Bulan) (%)</label>
                                            <input type="number" id="spaylater" class="form-control form-control-sm" value="2.50" step="0.01">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- BEP Result Box -->
                <div class="card border-0 bg-danger bg-opacity-10 text-danger-emphasis rounded-3">
                    <div class="card-body p-3 text-center">
                        <span class="text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.5px;">BEP (Break Even Point) ROAS</span>
                        <h2 id="bepRoasVal" class="fw-extrabold my-2 text-danger">4.61</h2>
                        <div style="font-size: 0.72rem;">
                            Organik Margin: <strong id="gpmVal">Rp 73.755</strong> (<strong id="gpmPct">32.08%</strong>)
                        </div>
                        <div class="text-muted mt-2" style="font-size: 0.65rem; line-height: 1.35;">
                            *BEP ROAS dihitung berdasarkan harga jual setelah dikurangi HPP dan total biaya marketplace (Rp <span id="totalFeeNomVal">71.145</span>).
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Strategy Matrix & Estimator -->
    <div class="col-lg-7 col-md-12">
        <!-- Strategy Matrix Card -->
        <div class="card border shadow-sm rounded-4 bg-white mb-3">
            <div class="card-header bg-info bg-opacity-10 py-3 px-4 border-bottom d-flex align-items-center gap-2">
                <span class="bg-info rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 28px; height: 28px;">
                    <i class="bi bi-grid-3x3-gap-fill"></i>
                </span>
                <div>
                    <h6 class="mb-0 fw-bold text-dark">Matriks Target ROAS & Profit Bersih</h6>
                    <small class="text-muted" style="font-size: 0.72rem;">Skenario target ROAS Kompetitif, Konservatif, dan Prospektif.</small>
                </div>
            </div>
            <div class="card-body p-3">
                <!-- Kompetitif -->
                <div class="border rounded-3 mb-3">
                    <div class="bg-danger bg-opacity-75 text-white fw-bold px-3 py-1.5 text-uppercase" style="font-size: 0.72rem; border-top-left-radius: 7px; border-top-right-radius: 7px;">
                         Skenario Kompetitif (Volume Maksimal / ROAS Rendah)
                    </div>
                    <div class="p-3">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle text-center mb-0" style="font-size: 0.78rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th>Keterangan</th>
                                        <th>ROAS</th>
                                        <th>Biaya Iklan (%)</th>
                                        <th>Biaya Iklan (Nominal)</th>
                                        <th>Profit Bersih (%)</th>
                                        <th>Profit Bersih (Nominal)</th>
                                        <th>BEP Kalkulator</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="text-start bg-light fw-bold">Akselerasi ROAS Normal</td>
                                        <td id="compRoas" class="fw-bold text-dark">7.84</td>
                                        <td id="compAdCostPct">12.76%</td>
                                        <td id="compAdCostNom">Rp 29.327</td>
                                        <td id="compProfitPct" class="text-success fw-bold">8.93%</td>
                                        <td id="compProfitNom" class="text-success fw-bold">Rp 20.529</td>
                                        <td id="compBep" class="fw-bold bg-light">11.20</td>
                                    </tr>
                                    <tr>
                                        <td class="text-start bg-light">Setelah Akselerasi ROAS (-30%)</td>
                                        <td id="compRoasAcc" class="text-muted">5.49</td>
                                        <td id="compAdCostPctAcc">18.22%</td>
                                        <td id="compAdCostNomAcc">Rp 41.896</td>
                                        <td id="compProfitPctAcc" class="text-danger fw-bold">3.46%</td>
                                        <td id="compProfitNomAcc" class="text-danger fw-bold">Rp 7.960</td>
                                        <td id="compBepAcc" class="fw-bold bg-light">28.88</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Konservatif -->
                <div class="border rounded-3 mb-3">
                    <div class="bg-success bg-opacity-75 text-white fw-bold px-3 py-1.5 text-uppercase" style="font-size: 0.72rem; border-top-left-radius: 7px; border-top-right-radius: 7px;">
                         Skenario Konservatif (Balanced / Profit & Volume)
                    </div>
                    <div class="p-3">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle text-center mb-0" style="font-size: 0.78rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th>Keterangan</th>
                                        <th>ROAS</th>
                                        <th>Biaya Iklan (%)</th>
                                        <th>Biaya Iklan (Nominal)</th>
                                        <th>Profit Bersih (%)</th>
                                        <th>Profit Bersih (Nominal)</th>
                                        <th>BEP Kalkulator</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="text-start bg-light fw-bold">Kejar Profit Stabil</td>
                                        <td id="consRoas" class="fw-bold text-dark">9.22</td>
                                        <td id="consAdCostPct">10.84%</td>
                                        <td id="consAdCostNom">Rp 24.928</td>
                                        <td id="consProfitPct" class="text-success fw-bold">10.84%</td>
                                        <td id="consProfitNom" class="text-success fw-bold">Rp 24.928</td>
                                        <td id="consBep" class="fw-bold bg-light">9.22</td>
                                    </tr>
                                    <tr>
                                        <td class="text-start bg-light">Setelah Akselerasi ROAS (-30%)</td>
                                        <td id="consRoasAcc" class="text-muted">6.46</td>
                                        <td id="consAdCostPctAcc">15.49%</td>
                                        <td id="consAdCostNomAcc">Rp 35.612</td>
                                        <td id="consProfitPctAcc" class="text-danger fw-bold">6.20%</td>
                                        <td id="consProfitNomAcc" class="text-danger fw-bold">Rp 14.245</td>
                                        <td id="consBepAcc" class="fw-bold bg-light">16.14</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Prospektif -->
                <div class="border rounded-3">
                    <div class="bg-primary bg-opacity-75 text-white fw-bold px-3 py-1.5 text-uppercase" style="font-size: 0.72rem; border-top-left-radius: 7px; border-top-right-radius: 7px;">
                         Skenario Prospektif (Skala Hoki / Profit Maksimal)
                    </div>
                    <div class="p-3">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle text-center mb-0" style="font-size: 0.78rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th>Keterangan</th>
                                        <th>ROAS</th>
                                        <th>Biaya Iklan (%)</th>
                                        <th>Biaya Iklan (Nominal)</th>
                                        <th>Profit Bersih (%)</th>
                                        <th>Profit Bersih (Nominal)</th>
                                        <th>BEP Kalkulator</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="text-start bg-light fw-bold">Kejar Hasil Tinggi</td>
                                        <td id="prosRoas" class="fw-bold text-dark">23.06</td>
                                        <td id="prosAdCostPct">4.34%</td>
                                        <td id="prosAdCostNom">Rp 9.971</td>
                                        <td id="prosProfitPct" class="text-success fw-bold">17.35%</td>
                                        <td id="prosProfitNom" class="text-success fw-bold">Rp 39.885</td>
                                        <td id="prosBep" class="fw-bold bg-light">5.76</td>
                                    </tr>
                                    <tr>
                                        <td class="text-start bg-light">Setelah Akselerasi ROAS (-30%)</td>
                                        <td id="prosRoasAcc" class="text-muted">16.14</td>
                                        <td id="prosAdCostPctAcc">6.20%</td>
                                        <td id="prosAdCostNomAcc">Rp 14.245</td>
                                        <td id="prosProfitPctAcc" class="text-danger fw-bold">15.49%</td>
                                        <td id="prosProfitNomAcc" class="text-danger fw-bold">Rp 35.612</td>
                                        <td id="prosBepAcc" class="fw-bold bg-light">6.46</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estimator Dashboard Iklan -->
        <div class="card border shadow-sm rounded-4 bg-white">
            <div class="card-header bg-warning bg-opacity-10 py-3 px-4 border-bottom d-flex align-items-center gap-2">
                <span class="bg-warning rounded-circle d-flex align-items-center justify-content-center text-dark" style="width: 28px; height: 28px;">
                    <i class="bi bi-sliders2"></i>
                </span>
                <div>
                    <h6 class="mb-0 fw-bold text-dark">Estimasi Hasil Dashboard Iklan</h6>
                    <small class="text-muted" style="font-size: 0.72rem;">Ubah target ROAS untuk menguji profitabilitas langsung.</small>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="row g-3 align-items-center">
                    <div class="col-md-5">
                        <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size:.65rem;">Masukkan ROAS Target</label>
                        <input type="number" id="simRoas" class="form-control form-control-sm rounded-3 border-warning" value="20" min="0.1" step="0.1">
                        <div class="form-text text-muted" style="font-size: .65rem;">Ketik angka ROAS untuk melihat simulasi profit.</div>
                    </div>
                    <div class="col-md-7">
                        <div class="table-responsive border rounded-3">
                            <table class="table table-bordered align-middle text-center mb-0" style="font-size: 0.8rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th>Target</th>
                                        <th>Biaya Iklan</th>
                                        <th>Profit Bersih</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="fw-bold text-dark">
                                        <td>ROAS Normal (<span id="simRoasLbl">20</span>)</td>
                                        <td><span id="simAdCostPct">5.00%</span><br><small class="text-muted fw-normal" id="simAdCostNom">Rp 11.495</small></td>
                                        <td><span id="simProfitPct" class="text-success">16.69%</span><br><small class="text-success fw-bold" id="simProfitNom">Rp 38.362</small></td>
                                    </tr>
                                    <tr class="text-muted">
                                        <td>Akselerasi (-30%) (<span id="simRoasAccLbl">14</span>)</td>
                                        <td><span id="simAdCostPctAcc">7.14%</span><br><small class="text-muted fw-normal" id="simAdCostNomAcc">Rp 16.421</small></td>
                                        <td><span id="simProfitPctAcc" class="text-success">14.54%</span><br><small class="text-success fw-bold" id="simProfitNomAcc">Rp 33.435</small></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const inputs = [
        'salePrice', 'cogs', 'simRoas',
        'adminFee', 'premiFee', 'fixedFee',
        'ongkirXtra', 'promoXtra', 'promoPlus', 'liveXtra', 'spaylater'
    ];
    inputs.forEach(id => {
        document.getElementById(id).addEventListener('input', calculateMatrix);
    });

    // Event listener untuk dropdown produk
    document.getElementById('productSelect').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value) {
            const price = parseFloat(selectedOption.getAttribute('data-price')) || 0;
            const cost = parseFloat(selectedOption.getAttribute('data-cost')) || 0;
            document.getElementById('salePrice').value = Math.round(price);
            document.getElementById('cogs').value = Math.round(cost);
            calculateMatrix();
        }
    });

    // Inisiasi pertama
    calculateMatrix();
});

function calculateMatrix() {
    const price = parseFloat(document.getElementById('salePrice').value) || 0;
    const cogs = parseFloat(document.getElementById('cogs').value) || 0;
    const simRoas = parseFloat(document.getElementById('simRoas').value) || 0.1;

    // Ambil semua persentase biaya marketplace
    const adminPct = parseFloat(document.getElementById('adminFee').value) || 0;
    const premiPct = parseFloat(document.getElementById('premiFee').value) || 0;
    const fixedFee = parseFloat(document.getElementById('fixedFee').value) || 0;

    // Ambil persentase program pemasaran
    const ongkirXtra = parseFloat(document.getElementById('ongkirXtra').value) || 0;
    const promoXtra = parseFloat(document.getElementById('promoXtra').value) || 0;
    const promoPlus = parseFloat(document.getElementById('promoPlus').value) || 0;
    const liveXtra = parseFloat(document.getElementById('liveXtra').value) || 0;
    const spaylater = parseFloat(document.getElementById('spaylater').value) || 0;

    // Total persentase potongan marketplace
    const totalFeePct = adminPct + premiPct + ongkirXtra + promoXtra + promoPlus + liveXtra + spaylater;
    const totalFeeNominal = ((totalFeePct / 100) * price) + fixedFee;

    // 1. Organik GPM (Profit Organik) = Harga Jual - HPP - Biaya Marketplace
    const gpmNominal = price - cogs - totalFeeNominal;
    let gpmPercentage = price > 0 ? (gpmNominal / price) * 100 : 0;

    // 2. BEP ROAS = Harga Jual / Organik GPM (Profit Organik)
    const bepRoas = gpmNominal > 0 ? (price / gpmNominal) : 0;

    // Render BEP Box
    document.getElementById('bepRoasVal').innerText = bepRoas > 0 ? bepRoas.toFixed(2) : '-';
    document.getElementById('gpmVal').innerText = 'Rp ' + Math.round(gpmNominal).toLocaleString('id-ID');
    document.getElementById('gpmPct').innerText = gpmPercentage.toFixed(2) + '%';
    document.getElementById('totalFeeNomVal').innerText = Math.round(totalFeeNominal).toLocaleString('id-ID');

    // 3. Skenario Multipliers
    const multipliers = {
        comp: 1.7,
        cons: 2.0,
        pros: 5.0
    };

    const scenarioPrefixes = ['comp', 'cons', 'pros'];

    scenarioPrefixes.forEach(prefix => {
        const mult = multipliers[prefix];
        const targetRoas = bepRoas * mult;
        const targetRoasAcc = targetRoas * 0.7; // -30%

        // Normal Target
        const adCostPct = targetRoas > 0 ? (100 / targetRoas) : 0;
        const adCostNom = (adCostPct / 100) * price;
        const profitPct = gpmPercentage - adCostPct;
        const profitNom = (profitPct / 100) * price;
        const bepCalc = profitNom > 0 ? (price / profitNom) : 0;

        // Acceleration Target (-30%)
        const adCostPctAcc = targetRoasAcc > 0 ? (100 / targetRoasAcc) : 0;
        const adCostNomAcc = (adCostPctAcc / 100) * price;
        const profitPctAcc = gpmPercentage - adCostPctAcc;
        const profitNomAcc = (profitPctAcc / 100) * price;
        const bepCalcAcc = profitNomAcc > 0 ? (price / profitNomAcc) : 0;

        // Update DOM Strategy Matrix Tables
        document.getElementById(`${prefix}Roas`).innerText = targetRoas.toFixed(2);
        document.getElementById(`${prefix}AdCostPct`).innerText = adCostPct.toFixed(2) + '%';
        document.getElementById(`${prefix}AdCostNom`).innerText = 'Rp ' + Math.round(adCostNom).toLocaleString('id-ID');
        document.getElementById(`${prefix}ProfitPct`).innerText = profitPct.toFixed(2) + '%';
        document.getElementById(`${prefix}ProfitNom`).innerText = 'Rp ' + Math.round(profitNom).toLocaleString('id-ID');
        document.getElementById(`${prefix}Bep`).innerText = bepCalc.toFixed(2);

        document.getElementById(`${prefix}RoasAcc`).innerText = targetRoasAcc.toFixed(2);
        document.getElementById(`${prefix}AdCostPctAcc`).innerText = adCostPctAcc.toFixed(2) + '%';
        document.getElementById(`${prefix}AdCostNomAcc`).innerText = 'Rp ' + Math.round(adCostNomAcc).toLocaleString('id-ID');
        document.getElementById(`${prefix}ProfitPctAcc`).innerText = profitPctAcc.toFixed(2) + '%';
        document.getElementById(`${prefix}ProfitNomAcc`).innerText = 'Rp ' + Math.round(profitNomAcc).toLocaleString('id-ID');
        document.getElementById(`${prefix}BepAcc`).innerText = bepCalcAcc.toFixed(2);
    });

    // 4. Simulator Estimasi
    const simRoasAcc = simRoas * 0.7;

    const simAdCostPctVal = simRoas > 0 ? (100 / simRoas) : 0;
    const simAdCostNomVal = (simAdCostPctVal / 100) * price;
    const simProfitPctVal = gpmPercentage - simAdCostPctVal;
    const simProfitNomVal = (simProfitPctVal / 100) * price;

    const simAdCostPctValAcc = simRoasAcc > 0 ? (100 / simRoasAcc) : 0;
    const simAdCostNomValAcc = (simAdCostPctValAcc / 100) * price;
    const simProfitPctValAcc = gpmPercentage - simAdCostPctValAcc;
    const simProfitNomValAcc = (simProfitPctValAcc / 100) * price;

    document.getElementById('simRoasLbl').innerText = simRoas.toFixed(1);
    document.getElementById('simAdCostPct').innerText = simAdCostPctVal.toFixed(2) + '%';
    document.getElementById('simAdCostNom').innerText = 'Rp ' + Math.round(simAdCostNomVal).toLocaleString('id-ID');
    document.getElementById('simProfitPct').innerText = simProfitPctVal.toFixed(2) + '%';
    document.getElementById('simProfitPct').className = simProfitPctVal >= 0 ? 'text-success' : 'text-danger';
    document.getElementById('simProfitNom').innerText = 'Rp ' + Math.round(simProfitNomVal).toLocaleString('id-ID');
    document.getElementById('simProfitNom').className = simProfitNomVal >= 0 ? 'text-success fw-bold' : 'text-danger fw-bold';

    document.getElementById('simRoasAccLbl').innerText = simRoasAcc.toFixed(1);
    document.getElementById('simAdCostPctAcc').innerText = simAdCostPctValAcc.toFixed(2) + '%';
    document.getElementById('simAdCostNomAcc').innerText = 'Rp ' + Math.round(simAdCostNomValAcc).toLocaleString('id-ID');
    document.getElementById('simProfitPctAcc').innerText = simProfitPctValAcc.toFixed(2) + '%';
    document.getElementById('simProfitPctAcc').className = simProfitPctValAcc >= 0 ? 'text-success' : 'text-danger';
    document.getElementById('simProfitNomAcc').innerText = 'Rp ' + Math.round(simProfitNomValAcc).toLocaleString('id-ID');
    document.getElementById('simProfitNomAcc').className = simProfitNomValAcc >= 0 ? 'text-success fw-bold' : 'text-danger fw-bold';
}
</script>
@endsection
