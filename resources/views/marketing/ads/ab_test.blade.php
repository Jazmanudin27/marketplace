@extends('layouts.app')

@section('title', 'A/B Testing Significance Calculator')
@section('page-title', 'A/B Testing Calculator')

@section('topbar-actions')
    <a href="{{ route('marketing.ads.index') }}" class="btn btn-sm btn-light text-primary fw-bold px-3">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard Iklan
    </a>
@endsection

@section('content')
<div class="row g-3">
    <!-- Left Column: Inputs -->
    <div class="col-lg-5 col-md-12">
        <div class="card border shadow-sm rounded-4 bg-white h-100">
            <div class="card-header bg-primary bg-opacity-10 py-3 px-4 border-bottom d-flex align-items-center gap-2">
                <span class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 28px; height: 28px;">
                    <i class="bi bi-calculator"></i>
                </span>
                <div>
                    <h6 class="mb-0 fw-bold text-dark">Data Pengujian Iklan</h6>
                    <small class="text-muted" style="font-size: 0.72rem;">Masukkan metrik impresi/klik untuk Variant A dan B.</small>
                </div>
            </div>
            <div class="card-body p-4">
                <form id="abCalculatorForm">
                    <!-- Variant A -->
                    <div class="border rounded-3 p-3 mb-4 bg-light bg-opacity-50">
                        <h6 class="fw-bold text-primary mb-3"><i class="bi bi-tag-fill me-1"></i> Variant A (Control)</h6>
                        <div class="mb-3">
                            <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size:.65rem;">Total Pengunjung / Impresi (N)</label>
                            <input type="number" id="impressionsA" class="form-control form-control-sm rounded-3" value="10000" min="1" required>
                        </div>
                        <div class="mb-0">
                            <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size:.65rem;">Total Konversi / Klik (Conversion)</label>
                            <input type="number" id="conversionsA" class="form-control form-control-sm rounded-3" value="200" min="0" required>
                        </div>
                    </div>

                    <!-- Variant B -->
                    <div class="border rounded-3 p-3 mb-4 bg-light bg-opacity-50">
                        <h6 class="fw-bold text-success mb-3"><i class="bi bi-lightning-fill me-1"></i> Variant B (Challenger)</h6>
                        <div class="mb-3">
                            <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size:.65rem;">Total Pengunjung / Impresi (N)</label>
                            <input type="number" id="impressionsB" class="form-control form-control-sm rounded-3" value="10500" min="1" required>
                        </div>
                        <div class="mb-0">
                            <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size:.65rem;">Total Konversi / Klik (Conversion)</label>
                            <input type="number" id="conversionsB" class="form-control form-control-sm rounded-3" value="260" min="0" required>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Right Column: Results & Graphs -->
    <div class="col-lg-7 col-md-12">
        <div class="card border shadow-sm rounded-4 bg-white h-100">
            <div class="card-header bg-info bg-opacity-10 py-3 px-4 border-bottom d-flex align-items-center gap-2">
                <span class="bg-info rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 28px; height: 28px;">
                    <i class="bi bi-bar-chart-line-fill"></i>
                </span>
                <div>
                    <h6 class="mb-0 fw-bold text-dark">Hasil Analisis Signifikansi</h6>
                    <small class="text-muted" style="font-size: 0.72rem;">Hasil perhitungan statistik real-time.</small>
                </div>
            </div>
            <div class="card-body p-4 d-flex flex-column justify-content-between">
                <div>
                    <!-- Dynamic Alert Recommendation Banner -->
                    <div id="resultBanner" class="alert d-flex align-items-start gap-3 rounded-4 p-3 mb-4" role="alert">
                        <div id="resultIcon" class="fs-4"></div>
                        <div>
                            <h6 id="resultTitle" class="fw-bold mb-1"></h6>
                            <p id="resultText" class="mb-0" style="font-size: 0.8rem; line-height: 1.4;"></p>
                        </div>
                    </div>

                    <!-- Side by Side Metrics -->
                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <div class="card border bg-light h-100 text-center p-3">
                                <span class="text-muted small fw-semibold">Conversion Rate A</span>
                                <h3 id="cvrA" class="fw-bold text-primary my-2">0.00%</h3>
                                <small id="rawA" class="text-secondary" style="font-size: 0.7rem;"></small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card border bg-light h-100 text-center p-3">
                                <span class="text-muted small fw-semibold">Conversion Rate B</span>
                                <h3 id="cvrB" class="fw-bold text-success my-2">0.00%</h3>
                                <small id="rawB" class="text-secondary" style="font-size: 0.7rem;"></small>
                            </div>
                        </div>
                    </div>

                    <!-- Statistical Details -->
                    <div class="border rounded-3 p-3 bg-white">
                        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-gear-wide-connected me-1"></i> Rincian Parameter Statistik</h6>
                        <div class="row g-2 text-dark" style="font-size: 0.82rem;">
                            <div class="col-6 d-flex justify-content-between border-bottom pb-2">
                                <span class="text-secondary">Peningkatan Relatif:</span>
                                <strong id="relativeImprovement" class="fw-bold text-dark">0.00%</strong>
                            </div>
                            <div class="col-6 d-flex justify-content-between border-bottom pb-2">
                                <span class="text-secondary">Z-Score:</span>
                                <strong id="zScore" class="fw-bold text-dark">0.00</strong>
                            </div>
                            <div class="col-6 d-flex justify-content-between pt-2">
                                <span class="text-secondary">Tingkat Kepercayaan:</span>
                                <strong id="confidenceLevel" class="fw-bold text-dark">0.00%</strong>
                            </div>
                            <div class="col-6 d-flex justify-content-between pt-2">
                                <span class="text-secondary">Nilai p (p-value):</span>
                                <strong id="pValue" class="fw-bold text-dark">0.000</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 border-top pt-3 text-muted text-center" style="font-size: 0.7rem;">
                    💡 <em>Kalkulator A/B test ini menggunakan uji signifikansi Z-test Dua Sisi (Two-Tailed) dengan tingkat ambang kepercayaan 95%.</em>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const inputs = ['impressionsA', 'conversionsA', 'impressionsB', 'conversionsB'];
    inputs.forEach(id => {
        document.getElementById(id).addEventListener('input', calculateABTest);
    });

    // Inisiasi kalkulasi pertama kali
    calculateABTest();
});

// Normal CDF (Cumulative Distribution Function) approximation
function normalCDF(z) {
    var t = 1 / (1 + 0.2316419 * Math.abs(z));
    var d = 0.3989423 * Math.exp(-z * z / 2);
    var p = d * t * (0.3193815 + t * (-0.3565638 + t * (1.781478 + t * (-1.821256 + t * 1.330274))));
    return z > 0 ? 1 - p : p;
}

function calculateABTest() {
    const nA = parseInt(document.getElementById('impressionsA').value) || 0;
    const cA = parseInt(document.getElementById('conversionsA').value) || 0;
    const nB = parseInt(document.getElementById('impressionsB').value) || 0;
    const cB = parseInt(document.getElementById('conversionsB').value) || 0;

    const banner = document.getElementById('resultBanner');
    const icon = document.getElementById('resultIcon');
    const title = document.getElementById('resultTitle');
    const text = document.getElementById('resultText');

    if (nA <= 0 || nB <= 0 || cA < 0 || cB < 0) {
        banner.className = "alert alert-secondary d-flex align-items-start gap-3 rounded-4 p-3 mb-4";
        icon.innerHTML = '<i class="bi bi-info-circle-fill text-secondary"></i>';
        title.innerHTML = "Menunggu Data Input";
        text.innerHTML = "Silakan masukkan angka pengunjung/impresi dan konversi di form sebelah kiri untuk memulai analisa.";
        return;
    }

    const crA = cA / nA;
    const crB = cB / nB;

    // Tampilkan Conversion Rate
    document.getElementById('cvrA').innerText = (crA * 100).toFixed(2) + '%';
    document.getElementById('cvrB').innerText = (crB * 100).toFixed(2) + '%';
    document.getElementById('rawA').innerText = `${cA.toLocaleString('id-ID')} konversi dari ${nA.toLocaleString('id-ID')} N`;
    document.getElementById('rawB').innerText = `${cB.toLocaleString('id-ID')} konversi dari ${nB.toLocaleString('id-ID')} N`;

    // 1. Peningkatan Relatif
    let relativeImp = 0;
    if (crA > 0) {
        relativeImp = ((crB - crA) / crA) * 100;
    }
    const sign = relativeImp >= 0 ? '+' : '';
    document.getElementById('relativeImprovement').innerText = `${sign}${relativeImp.toFixed(2)}%`;
    document.getElementById('relativeImprovement').className = relativeImp >= 0 ? 'fw-bold text-success' : 'fw-bold text-danger';

    // 2. Hitung Z-Score & p-value
    const pPooled = (cA + cB) / (nA + nB);
    let z = 0;
    let pVal = 0.5;
    let confidence = 0;

    if (pPooled > 0 && pPooled < 1) {
        const se = Math.sqrt(pPooled * (1 - pPooled) * (1 / nA + 1 / nB));
        z = (crB - crA) / se;
        pVal = 2 * (1 - normalCDF(Math.abs(z)));
        confidence = (2 * normalCDF(Math.abs(z)) - 1) * 100;
    }

    document.getElementById('zScore').innerText = Math.abs(z).toFixed(4);
    document.getElementById('pValue').innerText = pVal.toFixed(5);
    document.getElementById('confidenceLevel').innerText = confidence.toFixed(2) + '%';

    // 3. Tentukan pemenang & signifikansi
    if (confidence >= 95) {
        // Signifikan
        if (crB > crA) {
            // Variant B Pemenang
            banner.className = "alert alert-success d-flex align-items-start gap-3 rounded-4 p-3 mb-4 border-start border-4 border-success";
            icon.innerHTML = '<i class="bi bi-check-circle-fill text-success fs-3"></i>';
            title.innerHTML = "Hasil Signifikan! Variant B adalah Pemenang";
            title.className = "fw-bold mb-1 text-success-emphasis";
            text.innerHTML = `Variant B (Challenger) terbukti menghasilkan Conversion Rate yang lebih tinggi (${(crB*100).toFixed(2)}%) dibanding Variant A (${(crA*100).toFixed(2)}%) dengan tingkat kepercayaan <strong>${confidence.toFixed(2)}%</strong> (p = ${pVal.toFixed(5)}). Anda direkomendasikan mengalihkan budget iklan ke Variant B.`;
        } else {
            // Variant A Pemenang
            banner.className = "alert alert-danger d-flex align-items-start gap-3 rounded-4 p-3 mb-4 border-start border-4 border-danger";
            icon.innerHTML = '<i class="bi bi-x-circle-fill text-danger fs-3"></i>';
            title.innerHTML = "Hasil Signifikan! Variant A (Control) Lebih Baik";
            title.className = "fw-bold mb-1 text-danger-emphasis";
            text.innerHTML = `Variant A (Control) terbukti menghasilkan Conversion Rate yang lebih tinggi (${(crA*100).toFixed(2)}%) dibanding Variant B (${(crB*100).toFixed(2)}%) dengan tingkat kepercayaan <strong>${confidence.toFixed(2)}%</strong> (p = ${pVal.toFixed(5)}). Variant B gagal memberikan hasil lebih baik.`;
        }
    } else {
        // Tidak Signifikan
        banner.className = "alert alert-warning d-flex align-items-start gap-3 rounded-4 p-3 mb-4 border-start border-4 border-warning";
        icon.innerHTML = '<i class="bi bi-exclamation-triangle-fill text-warning fs-3"></i>';
        title.innerHTML = "Hasil Tidak Signifikan";
        title.className = "fw-bold mb-1 text-warning-emphasis";
        text.innerHTML = `Perbedaan Conversion Rate antara kedua Variant belum memenuhi standar signifikansi statistik (Tingkat kepercayaan baru mencapai <strong>${confidence.toFixed(2)}%</strong>, di bawah batas minimum 95.00%). Hasil pengujian ini mungkin dikarenakan faktor kebetulan. Kami sarankan untuk melanjutkan pengumpulan data impresi/klik lebih lanjut.`;
    }
}
</script>
@endsection
