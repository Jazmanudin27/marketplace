@extends('v2.layouts.app')

@section('title', 'Laporan Penjualan Marketplace & POS')

@push('styles')
<style>
.smk-kpi-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 14px 16px;
    position: relative;
    overflow: hidden;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.smk-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.05);
}
.smk-kpi-title {
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #64748b;
    margin-bottom: 4px;
}
.smk-kpi-value {
    font-size: 1.25rem;
    font-weight: 800;
    line-height: 1.2;
    margin-bottom: 2px;
}
.smk-kpi-sub {
    font-size: 0.72rem;
    color: #64748b;
    margin-top: 2px;
}
.smk-kpi-icon {
    position: absolute;
    right: 14px;
    bottom: 12px;
    font-size: 2rem;
    opacity: 0.12;
    pointer-events: none;
}
.statement-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 14px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 0.8rem;
}
.statement-row:last-child {
    border-bottom: none;
}
.statement-row.header {
    background: #f8fafc;
    font-weight: 700;
    color: #334155;
    text-transform: uppercase;
    font-size: 0.72rem;
    letter-spacing: 0.04em;
}
.statement-row.total {
    background: #f0fdf4;
    font-weight: 800;
    font-size: 0.92rem;
    border-top: 2px solid #86efac;
}
</style>
@endpush

@section('content')

{{-- ── Page Header ── --}}
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2 mb-0.5 fs-5">
            <i class="bi bi-file-earmark-bar-graph text-primary"></i> Laporan Penjualan (Dana Cair / Escrow Released)
        </h1>
        <div class="text-muted small">Rekapitulasi penjualan marketplace & POS offline, rincian potongan escrow, serta dana bersih yang dilepas</div>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('reports.released_sales.export', request()->all()) }}" class="btn btn-sm btn-outline-success shadow-sm fw-semibold" title="Export data laporan ke format CSV">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export CSV
        </a>
        <a href="{{ route('reports.released_sales.print', request()->all()) }}" target="_blank" class="btn btn-sm btn-success shadow-sm fw-semibold" title="Cetak laporan penjualan lengkap">
            <i class="bi bi-printer me-1"></i> Cetak Rekap
        </a>
    </div>
</div>

{{-- ── KPI Metric Cards ── --}}
<div class="row g-2.5 mb-3">
    <!-- Total Dana Dilepas (Net) -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="smk-kpi-card border-start border-success border-4">
            <div class="smk-kpi-title text-success">Total Dana Cair (Net)</div>
            <div class="smk-kpi-value text-success">
                Rp {{ number_format($summary['net_released'], 0, ',', '.') }}
            </div>
            <div class="smk-kpi-sub">Bersih masuk dompet / rekening</div>
            <i class="bi bi-cash-stack smk-kpi-icon text-success"></i>
        </div>
    </div>
    <!-- Total Omset Kotor (Gross) -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="smk-kpi-card border-start border-primary border-4">
            <div class="smk-kpi-title text-primary">Total Omset Kotor (Gross)</div>
            <div class="smk-kpi-value text-primary">
                Rp {{ number_format($summary['gross_revenue'], 0, ',', '.') }}
            </div>
            <div class="smk-kpi-sub">Total nilai belanja pembeli</div>
            <i class="bi bi-graph-up-arrow smk-kpi-icon text-primary"></i>
        </div>
    </div>
    <!-- Total Potongan Marketplace -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="smk-kpi-card border-start border-warning border-4">
            <div class="smk-kpi-title text-warning">Potongan Marketplace</div>
            <div class="smk-kpi-value text-warning-emphasis">
                Rp {{ number_format($summary['marketplace_fee'], 0, ',', '.') }}
            </div>
            <div class="smk-kpi-sub">Biaya platform, layanan, promo, ongkir</div>
            <i class="bi bi-percent smk-kpi-icon text-warning"></i>
        </div>
    </div>
    <!-- Total Transaksi Selesai -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="smk-kpi-card border-start border-info border-4">
            <div class="smk-kpi-title text-info">Transaksi Selesai</div>
            <div class="smk-kpi-value text-info">
                {{ number_format($summary['total_orders'], 0, ',', '.') }} <span class="fs-6 text-muted fw-normal">Order</span>
            </div>
            <div class="smk-kpi-sub">Order completed / dana dilepas</div>
            <i class="bi bi-check-circle-fill smk-kpi-icon text-info"></i>
        </div>
    </div>
</div>

{{-- ── Filter Card ── --}}
<div class="v2-card p-3 mb-3 shadow-sm">
    <form action="{{ route('v2.laporan.index') }}" method="GET" id="laporanFilterForm">
        <div class="row g-2 align-items-end">
            <!-- Format Laporan -->
            <div class="col-12 col-md-3">
                <label class="form-label mb-1 fw-semibold text-muted small" style="font-size:0.75rem;">Format Laporan</label>
                <select name="report_format" class="form-select form-select-sm v2-input fw-semibold text-primary" onchange="document.getElementById('laporanFilterForm').submit()">
                    <option value="ringkasan_penghasilan" {{ $reportFormat === 'ringkasan_penghasilan' ? 'selected' : '' }}>📄 Ringkasan Penghasilan & Biaya Escrow</option>
                    <option value="per_produk" {{ $reportFormat === 'per_produk' ? 'selected' : '' }}>📦 Rekap Per Produk</option>
                    <option value="per_channel" {{ $reportFormat === 'per_channel' ? 'selected' : '' }}>🏪 Rekap Per Toko / Channel MP</option>
                    <option value="detail" {{ $reportFormat === 'detail' ? 'selected' : '' }}>📑 Detail Transaksi Penjualan</option>
                    <option value="per_tanggal" {{ $reportFormat === 'per_tanggal' ? 'selected' : '' }}>📅 Rekap Per Tanggal</option>
                </select>
            </div>

            <!-- Toko Marketplace -->
            <div class="col-12 col-sm-6 col-md-2">
                <label class="form-label mb-1 fw-semibold text-muted small" style="font-size:0.75rem;">Toko MP</label>
                <select name="store_id" class="form-select form-select-sm v2-input">
                    <option value="">Semua Toko MP</option>
                    @foreach($stores as $st)
                        <option value="{{ $st->id }}" {{ $storeId == $st->id ? 'selected' : '' }}>
                            {{ $st->store_name }} ({{ strtoupper($st->channel->code ?? 'MP') }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Channel / Saluran -->
            <div class="col-12 col-sm-6 col-md-2">
                <label class="form-label mb-1 fw-semibold text-muted small" style="font-size:0.75rem;">Saluran Penjualan</label>
                <select name="channel_code" class="form-select form-select-sm v2-input">
                    <option value="all" {{ $channelCode === 'all' ? 'selected' : '' }}>Semua Saluran</option>
                    <option value="shopee" {{ $channelCode === 'shopee' ? 'selected' : '' }}>Shopee</option>
                    <option value="tiktok" {{ $channelCode === 'tiktok' ? 'selected' : '' }}>TikTok Shop</option>
                    <option value="offline" {{ $channelCode === 'offline' ? 'selected' : '' }}>POS Offline</option>
                </select>
            </div>

            <!-- Dari Tanggal -->
            <div class="col-12 col-sm-6 col-md-1.5" style="width: 14%;">
                <label class="form-label mb-1 fw-semibold text-muted small" style="font-size:0.75rem;">Dari Tanggal</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control form-control-sm v2-input">
            </div>

            <!-- Sampai Tanggal -->
            <div class="col-12 col-sm-6 col-md-1.5" style="width: 14%;">
                <label class="form-label mb-1 fw-semibold text-muted small" style="font-size:0.75rem;">Sampai Tanggal</label>
                <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control form-control-sm v2-input">
            </div>

            <!-- Search & Actions -->
            <div class="col-12 col-md d-flex gap-1.5 align-items-center">
                <button type="submit" class="btn btn-sm btn-v2-primary py-1 px-3 shadow-sm fw-semibold flex-fill" style="border-radius:6px;">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                <a href="{{ route('v2.laporan.index') }}" class="btn btn-sm btn-light border py-1 px-2.5 fw-semibold" style="border-radius:6px;" title="Reset Filter">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </div>
    </form>
</div>

{{-- ── Preview Content Card ── --}}
<div class="v2-card p-0 shadow-sm overflow-hidden mb-3">
    <div class="py-2.5 px-3 bg-white border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-table text-primary"></i>
            <span class="fw-bold text-dark" style="font-size:0.85rem;">
                @if($reportFormat === 'ringkasan_penghasilan')
                    Ringkasan Penghasilan & Biaya Escrow Marketplace
                @elseif($reportFormat === 'per_produk')
                    Laporan Penjualan Per Produk
                @elseif($reportFormat === 'per_channel')
                    Laporan Penjualan Per Toko / Channel Marketplace
                @elseif($reportFormat === 'detail')
                    Laporan Detail Transaksi Penjualan Selesai
                @elseif($reportFormat === 'per_tanggal')
                    Laporan Penjualan Per Tanggal
                @endif
            </span>
            <span class="badge bg-light text-dark border px-2 py-0.5 small" style="font-size:0.68rem;">
                Periode: {{ Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} - {{ Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}
            </span>
        </div>
    </div>

    {{-- 1. Format: Ringkasan Penghasilan (Shopee / Escrow Style) --}}
    @if($reportFormat === 'ringkasan_penghasilan')
        <div class="p-3">
            <div class="row g-3">
                <div class="col-12 col-lg-7">
                    <div class="card border border-light-subtle rounded-3 overflow-hidden shadow-none">
                        <div class="statement-row header">
                            <span>Komponen Penghasilan</span>
                            <span class="text-end">Jumlah (Rp)</span>
                        </div>
                        <div class="statement-row">
                            <span><i class="bi bi-bag-check text-primary me-2"></i>Penjualan Kotor (Gross Sales)</span>
                            <strong class="font-monospace text-dark">Rp {{ number_format($summary['gross_revenue'], 0, ',', '.') }}</strong>
                        </div>
                        <div class="statement-row">
                            <span><i class="bi bi-arrow-counterclockwise text-danger me-2"></i>Pengembalian Dana / Retur</span>
                            <span class="font-monospace text-danger">- Rp {{ number_format($summary['total_refunds'], 0, ',', '.') }}</span>
                        </div>
                        <div class="statement-row header bg-light">
                            <span>Komponen Potongan Marketplace (Fees)</span>
                            <span class="text-end">Jumlah (Rp)</span>
                        </div>
                        <div class="statement-row">
                            <span class="ps-3 text-muted"><i class="bi bi-dash-circle me-1.5"></i>Biaya Administrasi Platform</span>
                            <span class="font-monospace text-muted">- Rp {{ number_format($summary['fee_platform'], 0, ',', '.') }}</span>
                        </div>
                        <div class="statement-row">
                            <span class="ps-3 text-muted"><i class="bi bi-truck me-1.5"></i>Biaya Program Gratis Ongkir Ekstra</span>
                            <span class="font-monospace text-muted">- Rp {{ number_format($summary['fee_free_shipping'], 0, ',', '.') }}</span>
                        </div>
                        <div class="statement-row">
                            <span class="ps-3 text-muted"><i class="bi bi-gear me-1.5"></i>Biaya Layanan & Pembayaran</span>
                            <span class="font-monospace text-muted">- Rp {{ number_format($summary['fee_service'], 0, ',', '.') }}</span>
                        </div>
                        <div class="statement-row">
                            <span class="ps-3 text-muted"><i class="bi bi-tag me-1.5"></i>Biaya Voucher / Promosi Marketplace</span>
                            <span class="font-monospace text-muted">- Rp {{ number_format($summary['fee_promo'], 0, ',', '.') }}</span>
                        </div>
                        @if($summary['fee_other'] > 0)
                            <div class="statement-row">
                                <span class="ps-3 text-muted"><i class="bi bi-three-dots me-1.5"></i>Biaya Penyesuaian Lainnya</span>
                                <span class="font-monospace text-muted">- Rp {{ number_format($summary['fee_other'], 0, ',', '.') }}</span>
                            </div>
                        @endif
                        <div class="statement-row bg-warning-subtle text-warning-emphasis fw-bold">
                            <span><i class="bi bi-percent me-2"></i>Total Potongan Marketplace</span>
                            <span class="font-monospace">- Rp {{ number_format($summary['marketplace_fee'], 0, ',', '.') }}</span>
                        </div>
                        <div class="statement-row total text-success">
                            <span><i class="bi bi-check2-circle me-2"></i>TOTAL DANA BERSIH DILEPAS (NET RELEASED)</span>
                            <span class="font-monospace fs-6">Rp {{ number_format($summary['net_released'], 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-5">
                    <div class="card border border-light-subtle rounded-3 p-3 h-100 bg-light-subtle">
                        <h6 class="fw-bold text-dark mb-2" style="font-size:0.85rem;">
                            <i class="bi bi-info-circle text-primary me-1.5"></i>Informasi Rekapitulasi Escrow
                        </h6>
                        <p class="text-muted small mb-3" style="font-size:0.75rem; line-height:1.4;">
                            Data pada format ini mengkalkulasi seluruh pesanan yang telah berstatus <strong>SELESAI / COMPLETED</strong> pada rentang tanggal terpilih. Dana bersih yang ditampilkan adalah jumlah riil yang dilepas ke Saldo Dompet Penjual.
                        </p>
                        <div class="border rounded-2 p-2.5 bg-white mb-2">
                            <div class="d-flex justify-content-between small text-muted mb-1">
                                <span>Persentase Potongan Rata-rata:</span>
                                <strong class="text-dark">
                                    {{ $summary['gross_revenue'] > 0 ? number_format(($summary['marketplace_fee'] / $summary['gross_revenue']) * 100, 1) : 0 }}%
                                </strong>
                            </div>
                            <div class="progress" style="height: 6px;">
                                @php
                                    $feePct = $summary['gross_revenue'] > 0 ? min(100, ($summary['marketplace_fee'] / $summary['gross_revenue']) * 100) : 0;
                                @endphp
                                <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $feePct }}%"></div>
                            </div>
                        </div>
                        <div class="mt-auto d-flex gap-2">
                            <a href="{{ route('reports.released_sales.print', array_merge(request()->all(), ['report_format' => 'ringkasan_penghasilan'])) }}" target="_blank" class="btn btn-sm btn-success w-100 fw-semibold">
                                <i class="bi bi-printer me-1"></i> Cetak Format Ini
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    {{-- 2. Format: Per Produk --}}
    @elseif($reportFormat === 'per_produk')
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0" style="font-size:0.78rem;">
                <thead class="table-light">
                    <tr>
                        <th class="text-center py-2" style="width:40px; font-size:0.7rem;">No</th>
                        <th class="py-2" style="width:130px; font-size:0.7rem;">SKU</th>
                        <th class="py-2" style="font-size:0.7rem;">Nama Produk</th>
                        <th class="text-center py-2" style="width:90px; font-size:0.7rem;">Qty Online</th>
                        <th class="text-center py-2" style="width:90px; font-size:0.7rem;">Qty POS</th>
                        <th class="text-center py-2" style="width:90px; font-size:0.7rem;">Total Qty</th>
                        <th class="text-end py-2" style="width:130px; font-size:0.7rem;">Omset Kotor</th>
                        <th class="text-end py-2" style="width:120px; font-size:0.7rem;">Potongan MP</th>
                        <th class="text-end py-2" style="width:140px; font-size:0.7rem;">Dana Bersih Cair</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reportData['products'] ?? [] as $i => $item)
                        <tr>
                            <td class="text-center text-muted" style="font-size:0.72rem;">{{ $i + 1 }}</td>
                            <td><span class="badge bg-light text-dark border font-monospace px-1.5 py-0.5" style="font-size:0.7rem;">{{ $item['sku'] }}</span></td>
                            <td class="fw-medium text-dark text-truncate" style="max-width:280px;" title="{{ $item['name'] }}">{{ $item['name'] }}</td>
                            <td class="text-center">{{ number_format($item['qty_online']) }}</td>
                            <td class="text-center">{{ number_format($item['qty_offline']) }}</td>
                            <td class="text-center fw-bold text-dark">{{ number_format($item['total_qty']) }}</td>
                            <td class="text-end font-monospace">Rp {{ number_format($item['omset'], 0, ',', '.') }}</td>
                            <td class="text-end font-monospace text-warning-emphasis">- Rp {{ number_format($item['fee'], 0, ',', '.') }}</td>
                            <td class="text-end font-monospace fw-bold text-success">Rp {{ number_format($item['net'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">Tidak ada data produk terjual pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    {{-- 3. Format: Per Channel / Toko --}}
    @elseif($reportFormat === 'per_channel')
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0" style="font-size:0.78rem;">
                <thead class="table-light">
                    <tr>
                        <th class="text-center py-2" style="width:40px; font-size:0.7rem;">No</th>
                        <th class="py-2" style="font-size:0.7rem;">Toko / Saluran Penjualan</th>
                        <th class="py-2" style="width:130px; font-size:0.7rem;">Tipe</th>
                        <th class="text-center py-2" style="width:100px; font-size:0.7rem;">Total Order</th>
                        <th class="text-center py-2" style="width:100px; font-size:0.7rem;">Item Terjual</th>
                        <th class="text-end py-2" style="width:140px; font-size:0.7rem;">Omset Kotor</th>
                        <th class="text-end py-2" style="width:130px; font-size:0.7rem;">Potongan MP</th>
                        <th class="text-end py-2" style="width:150px; font-size:0.7rem;">Dana Bersih Cair</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reportData['channels'] ?? [] as $i => $item)
                        <tr>
                            <td class="text-center text-muted" style="font-size:0.72rem;">{{ $i + 1 }}</td>
                            <td class="fw-bold text-dark">{{ $item['name'] }}</td>
                            <td><span class="badge bg-secondary-subtle text-secondary-emphasis border rounded-pill px-2 py-0.5" style="font-size:0.65rem;">{{ $item['type'] }}</span></td>
                            <td class="text-center">{{ number_format($item['orders']) }}</td>
                            <td class="text-center">{{ number_format($item['qty']) }}</td>
                            <td class="text-end font-monospace">Rp {{ number_format($item['omset'], 0, ',', '.') }}</td>
                            <td class="text-end font-monospace text-warning-emphasis">- Rp {{ number_format($item['fee'], 0, ',', '.') }}</td>
                            <td class="text-end font-monospace fw-bold text-success">Rp {{ number_format($item['net'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">Tidak ada data transaksi toko pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    {{-- 4. Format: Detail Transaksi --}}
    @elseif($reportFormat === 'detail')
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0" style="font-size:0.78rem;">
                <thead class="table-light">
                    <tr>
                        <th class="text-center py-2" style="width:40px; font-size:0.7rem;">No</th>
                        <th class="py-2" style="width:120px; font-size:0.7rem;">Waktu Order</th>
                        <th class="py-2" style="width:100px; font-size:0.7rem;">Tgl Cair</th>
                        <th class="py-2" style="width:160px; font-size:0.7rem;">No. Pesanan</th>
                        <th class="py-2" style="font-size:0.7rem;">Toko / Saluran</th>
                        <th class="py-2" style="font-size:0.7rem;">Pelanggan</th>
                        <th class="text-center py-2" style="width:60px; font-size:0.7rem;">Qty</th>
                        <th class="text-end py-2" style="width:120px; font-size:0.7rem;">Omset</th>
                        <th class="text-end py-2" style="width:110px; font-size:0.7rem;">Fee MP</th>
                        <th class="text-end py-2" style="width:130px; font-size:0.7rem;">Net Cair</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reportData['transactions'] ?? [] as $i => $item)
                        <tr>
                            <td class="text-center text-muted" style="font-size:0.72rem;">{{ $i + 1 }}</td>
                            <td class="text-muted" style="font-size:0.72rem;">{{ $item['date'] }}</td>
                            <td class="text-muted" style="font-size:0.72rem;">{{ $item['completed_date'] }}</td>
                            <td class="fw-semibold text-primary font-monospace">{{ $item['ref'] }}</td>
                            <td class="text-dark">{{ $item['channel'] }}</td>
                            <td class="text-truncate" style="max-width:140px;" title="{{ $item['customer'] }}">{{ $item['customer'] }}</td>
                            <td class="text-center">{{ $item['qty'] }}</td>
                            <td class="text-end font-monospace">Rp {{ number_format($item['omset'], 0, ',', '.') }}</td>
                            <td class="text-end font-monospace text-warning-emphasis">- Rp {{ number_format($item['fee'], 0, ',', '.') }}</td>
                            <td class="text-end font-monospace fw-bold text-success">Rp {{ number_format($item['net'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">Tidak ada transaksi ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    {{-- 5. Format: Per Tanggal --}}
    @elseif($reportFormat === 'per_tanggal')
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0" style="font-size:0.78rem;">
                <thead class="table-light">
                    <tr>
                        <th class="text-center py-2" style="width:40px; font-size:0.7rem;">No</th>
                        <th class="py-2" style="width:140px; font-size:0.7rem;">Tanggal</th>
                        <th class="text-center py-2" style="width:100px; font-size:0.7rem;">Total Order</th>
                        <th class="text-center py-2" style="width:100px; font-size:0.7rem;">Item Terjual</th>
                        <th class="text-end py-2" style="width:140px; font-size:0.7rem;">Omset Kotor</th>
                        <th class="text-end py-2" style="width:130px; font-size:0.7rem;">Potongan MP</th>
                        <th class="text-end py-2" style="width:150px; font-size:0.7rem;">Dana Bersih Cair</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reportData['dates'] ?? [] as $i => $item)
                        <tr>
                            <td class="text-center text-muted" style="font-size:0.72rem;">{{ $i + 1 }}</td>
                            <td class="fw-bold text-dark">{{ Carbon\Carbon::parse($item['date'])->format('d/m/Y') }}</td>
                            <td class="text-center">{{ number_format($item['orders']) }}</td>
                            <td class="text-center">{{ number_format($item['qty']) }}</td>
                            <td class="text-end font-monospace">Rp {{ number_format($item['omset'], 0, ',', '.') }}</td>
                            <td class="text-end font-monospace text-warning-emphasis">- Rp {{ number_format($item['fee'], 0, ',', '.') }}</td>
                            <td class="text-end font-monospace fw-bold text-success">Rp {{ number_format($item['net'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">Tidak ada data tanggal pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>

@endsection
