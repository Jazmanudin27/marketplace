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
    <div class="col-lg-4 col-md-12">
        <div class="card border shadow-sm rounded-4 bg-white mb-3">
            <div class="card-header bg-primary bg-opacity-10 py-3 px-4 border-bottom d-flex align-items-center gap-2">
                <span class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 28px; height: 28px;">
                    <i class="bi bi-sliders"></i>
                </span>
                <div>
                    <h6 class="mb-0 fw-bold text-dark">Parameter Keuangan</h6>
                    <small class="text-muted" style="font-size: 0.72rem;">Masukkan harga produk & HPP toko Anda.</small>
                </div>
            </div>
            <div class="card-body p-4">
                <form id="roasCalcForm">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size:.65rem;">Harga Jual Rata-rata Produk (Rp)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light">Rp</span>
                            <input type="number" id="salePrice" class="form-control rounded-end-3" value="230000" min="1" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size:.65rem;">HPP / COGS Rata-rata Produk (Rp)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light">Rp</span>
                            <input type="number" id="cogs" class="form-control rounded-end-3" value="180143" min="0" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size:.65rem;">Biaya Admin Marketplace & Operasional (%)</label>
                        <div class="input-group input-group-sm">
                            <input type="number" id="adminFee" class="form-control rounded-start-3" value="0" min="0" max="90" step="0.1">
                            <span class="input-group-text bg-light">%</span>
                        </div>
                        <div class="form-text text-muted" style="font-size: .65rem;">Potongan komisi platform Shopee/TikTok & biaya packing.</div>
                    </div>
                </form>

                <hr class="my-4">

                <!-- BEP Result Box -->
                <div class="card border-0 bg-danger bg-opacity-10 text-danger-emphasis rounded-3">
                    <div class="card-body p-3 text-center">
                        <span class="text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.5px;">BEP (Break Even Point) ROAS</span>
                        <h2 id="bepRoasVal" class="fw-extrabold my-2 text-danger">4.61</h2>
                        <div style="font-size: 0.72rem;">
                            Gross Margin: <strong id="gpmVal">Rp 49.857</strong> (<strong id="gpmPct">21.68%</strong>)
                        </div>
                        <div class="text-muted mt-2" style="font-size: 0.65rem; line-height: 1.3;">
                            *Jika iklan berjalan dengan ROAS di bawah angka ini, toko Anda mengalami **rugi**.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Strategy Matrix & Estimator -->
    <div class="col-lg-8 col-md-12">
        <!-- Strategy Matrix Card -->
        <div class="card border shadow-sm rounded-4 bg-white mb-3">
            <div class="card-header bg-info bg-opacity-10 py-3 px-4 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="bg-info rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 28px; height: 28px;">
                        <i class="bi bi-grid-3x3-gap-fill"></i>
                    </span>
                    <div>
                        <h6 class="mb-0 fw-bold text-dark">Matriks Strategi Iklan & Target ROAS</h6>
                        <small class="text-muted" style="font-size: 0.72rem;">Rencana ROAS berdasarkan skenario kompetitif hingga prospektif.</small>
                    </div>
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
                            <table class="table table-sm table-bordered align-middle text-center mb-0" style="font-size: 0.8rem;">
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
                                        <td id="compRoas" class="fw-bold">7.84</td>
                                        <td id="compAdCostPct">12.76%</td>
                                        <td id="compAdCostNom">Rp 29.327</td>
                                        <td id="compProfitPct" class="text-success fw-bold">8.93%</td>
                                        <td id="compProfitNom" class="text-success fw-bold">Rp 20.529</td>
                                        <td id="compBep" class="fw-bold bg-light">11.20</td>
                                    </tr>
                                    <tr>
                                        <td class="text-start bg-light">Akselerasi ROAS (-30%)</td>
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
                            <table class="table table-sm table-bordered align-middle text-center mb-0" style="font-size: 0.8rem;">
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
                                        <td id="consRoas" class="fw-bold">9.22</td>
                                        <td id="consAdCostPct">10.84%</td>
                                        <td id="consAdCostNom">Rp 24.928</td>
                                        <td id="consProfitPct" class="text-success fw-bold">10.84%</td>
                                        <td id="consProfitNom" class="text-success fw-bold">Rp 24.928</td>
                                        <td id="consBep" class="fw-bold bg-light">9.22</td>
                                    </tr>
                                    <tr>
                                        <td class="text-start bg-light">Akselerasi ROAS (-30%)</td>
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
                            <table class="table table-sm table-bordered align-middle text-center mb-0" style="font-size: 0.8rem;">
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
                                        <td id="prosRoas" class="fw-bold">23.06</td>
                                        <td id="prosAdCostPct">4.34%</td>
                                        <td id="prosAdCostNom">Rp 9.971</td>
                                        <td id="prosProfitPct" class="text-success fw-bold">17.35%</td>
                                        <td id="prosProfitNom" class="text-success fw-bold">Rp 39.885</td>
                                        <td id="prosBep" class="fw-bold bg-light">5.76</td>
                                    </tr>
                                    <tr>
                                        <td class="text-start bg-light">Akselerasi ROAS (-30%)</td>
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

        <!-- Interactive Simulator Card -->
        <div class="card border shadow-sm rounded-4 bg-white">
            <div class="card-header bg-warning bg-opacity-10 py-3 px-4 border-bottom d-flex align-items-center gap-2">
                <span class="bg-warning rounded-circle d-flex align-items-center justify-content-center text-dark" style="width: 28px; height: 28px;">
                    <i class="bi bi-sliders2"></i>
                </span>
                <div>
                    <h6 class="mb-0 fw-bold text-dark">Estimator Hasil Real-Time</h6>
                    <small class="text-muted" style="font-size: 0.72rem;">Ubah target ROAS untuk memproyeksikan profitabilitas nominal.</small>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="row g-4 align-items-center">
                    <div class="col-md-5">
                        <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size:.65rem;">Masukkan ROAS Target</label>
                        <input type="number" id="simRoas" class="form-control form-control-sm rounded-3" value="20" min="0.1" step="0.1">
                        <div class="form-text text-muted" style="font-size: .65rem;">Ketik angka ROAS target yang ingin Anda estimasi.</div>
                    </div>
                    <div class="col-md-7">
                        <div class="table-responsive border rounded-3">
                            <table class="table table-bordered align-middle text-center mb-0" style="font-size: 0.82rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th>Target</th>
                                        <th>Biaya Iklan</th>
                                        <th>Profit Bersih</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="fw-bold text-dark">
                                        <td>Normal ROAS (<span id="simRoasLbl">20</span>)</td>
                                        <td><span id="simAdCostPct">5.00%</span><br><small class="text-muted fw-normal" id="simAdCostNom">Rp 11.500</small></td>
                                        <td><span id="simProfitPct" class="text-success">16.68%</span><br><small class="text-success fw-bold" id="simProfitNom">Rp 38.362</small></td>
                                    </tr>
                                    <tr class="text-muted">
                                        <td>Akselerasi (-30%) (<span id="simRoasAccLbl">14</span>)</td>
                                        <td><span id="simAdCostPctAcc">7.14%</span><br><small class="text-muted fw-normal" id="simAdCostNomAcc">Rp 16.429</small></td>
                                        <td><span id="simProfitPctAcc" class="text-success">14.54%</span><br><small class="text-success fw-bold" id="simProfitNomAcc">Rp 33.438</small></td>
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
    const inputs = ['salePrice', 'cogs', 'adminFee', 'simRoas'];
    inputs.forEach(id => {
        document.getElementById(id).addEventListener('input', calculateMatrix);
    });

    // Inisiasi pertama
    calculateMatrix();
});

function calculateMatrix() {
    const price = parseFloat(document.getElementById('salePrice').value) || 0;
    const cogs = parseFloat(document.getElementById('cogs').value) || 0;
    const feePct = parseFloat(document.getElementById('adminFee').value) || 0;
    const simRoas = parseFloat(document.getElementById('simRoas').value) || 0.1;

    // 1. Hitung Gross Profit Margin (GPM)
    const gpmNominal = price - cogs;
    let gpmPercentage = price > 0 ? (gpmNominal / price) * 100 : 0;
    
    // Potong admin fee dari GPM
    const netGpmPercentage = gpmPercentage - feePct;
    const netGpmNominal = (netGpmPercentage / 100) * price;

    // 2. BEP ROAS = 1 / Net GPM %
    const bepRoas = netGpmPercentage > 0 ? (100 / netGpmPercentage) : 0;

    // Render BEP Box
    document.getElementById('bepRoasVal').innerText = bepRoas > 0 ? bepRoas.toFixed(2) : '-';
    document.getElementById('gpmVal').innerText = 'Rp ' + gpmNominal.toLocaleString('id-ID');
    document.getElementById('gpmPct').innerText = gpmPercentage.toFixed(2) + '%';

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

        // Normal
        const adCostPct = targetRoas > 0 ? (100 / targetRoas) : 0;
        const adCostNom = (adCostPct / 100) * price;
        const profitPct = netGpmPercentage - adCostPct;
        const profitNom = (profitPct / 100) * price;
        const bepCalc = profitNom > 0 ? (price / profitNom) : 0;

        // Acceleration (-30%)
        const adCostPctAcc = targetRoasAcc > 0 ? (100 / targetRoasAcc) : 0;
        const adCostNomAcc = (adCostPctAcc / 100) * price;
        const profitPctAcc = netGpmPercentage - adCostPctAcc;
        const profitNomAcc = (profitPctAcc / 100) * price;
        const bepCalcAcc = profitNomAcc > 0 ? (price / profitNomAcc) : 0;

        // Update DOM
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

    // 4. Simulator Real-Time
    const simRoasAcc = simRoas * 0.7;

    const simAdCostPctVal = simRoas > 0 ? (100 / simRoas) : 0;
    const simAdCostNomVal = (simAdCostPctVal / 100) * price;
    const simProfitPctVal = netGpmPercentage - simAdCostPctVal;
    const simProfitNomVal = (simProfitPctVal / 100) * price;

    const simAdCostPctValAcc = simRoasAcc > 0 ? (100 / simRoasAcc) : 0;
    const simAdCostNomValAcc = (simAdCostPctValAcc / 100) * price;
    const simProfitPctValAcc = netGpmPercentage - simAdCostPctValAcc;
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
