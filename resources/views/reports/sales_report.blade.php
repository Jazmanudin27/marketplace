@extends('layouts.app')
@section('title', 'Laporan Rekap Penjualan Produk')
@section('page-title', 'Laporan Rekap Penjualan Produk')

@section('content')
<div class="container-fluid px-0">
    {{-- STATS CARDS SUMMARY --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-primary border-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.7rem;">Total Omset Penjualan</div>
                        <h4 class="fw-bold text-primary mb-0 font-monospace">Rp {{ number_format($grandTotalOmset, 0, ',', '.') }}</h4>
                    </div>
                    <div class="bg-primary bg-opacity-10 p-2.5 rounded-circle text-primary">
                        <i class="fas fa-chart-line fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-success border-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.7rem;">Total Qty Terjual</div>
                        <h4 class="fw-bold text-success mb-0 font-monospace">{{ number_format($grandTotalQty) }} <span class="fs-6 text-muted fw-normal">Pcs</span></h4>
                    </div>
                    <div class="bg-success bg-opacity-10 p-2.5 rounded-circle text-success">
                        <i class="fas fa-boxes fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-warning border-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.7rem;">Total HPP Modal Terjual</div>
                        <h4 class="fw-bold text-dark mb-0 font-monospace">Rp {{ number_format($grandTotalHpp, 0, ',', '.') }}</h4>
                    </div>
                    <div class="bg-warning bg-opacity-10 p-2.5 rounded-circle text-warning">
                        <i class="fas fa-wallet fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-info border-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.7rem;">Estimasi Laba Kotor (Gross)</div>
                        <h4 class="fw-bold text-info mb-0 font-monospace">Rp {{ number_format($grandTotalProfit, 0, ',', '.') }}</h4>
                        <div class="small text-muted" style="font-size: 0.72rem;">Margin Avg: <strong class="text-success">{{ number_format($overallMargin, 1) }}%</strong></div>
                    </div>
                    <div class="bg-info bg-opacity-10 p-2.5 rounded-circle text-info">
                        <i class="fas fa-coins fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- FILTER BOX (DESAIN PERSIS REKAP PERSEDIAAN STOK) --}}
    <div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
        <div class="card-body p-4">
            <h5 class="fw-bold text-dark mb-4">
                <i class="fas fa-filter me-2 text-primary"></i>Filter Rekap Penjualan Produk
            </h5>

            <form method="GET" action="{{ route('reports.sales') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted">Dari Tanggal</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted">Sampai Tanggal</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted">Kategori Produk</label>
                    <select name="category_id" class="form-select form-select-sm">
                        <option value="">Semua Kategori</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted">Brand / Merek</label>
                    <select name="brand_id" class="form-select form-select-sm">
                        <option value="">Semua Brand</option>
                        @foreach($brands as $b)
                            <option value="{{ $b->id }}" {{ $brandId == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted">Saluran Penjualan / Channel</label>
                    <select name="channel_code" class="form-select form-select-sm">
                        <option value="all" {{ $channelCode === 'all' ? 'selected' : '' }}>Semua Saluran (Offline &amp; Online)</option>
                        <option value="offline" {{ $channelCode === 'offline' ? 'selected' : '' }}>POS Offline (Toko Fisik)</option>
                        <option value="shopee" {{ $channelCode === 'shopee' ? 'selected' : '' }}>Shopee</option>
                        <option value="tiktok" {{ $channelCode === 'tiktok' ? 'selected' : '' }}>TikTok Shop</option>
                        <option value="lazada" {{ $channelCode === 'lazada' ? 'selected' : '' }}>Lazada</option>
                        <option value="tokopedia" {{ $channelCode === 'tokopedia' ? 'selected' : '' }}>Tokopedia</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted">Kategori Pelanggan</label>
                    <select name="customer_category" class="form-select form-select-sm">
                        <option value="all" {{ $customerCat === 'all' ? 'selected' : '' }}>Semua Kategori Pelanggan</option>
                        <option value="dropship" {{ $customerCat === 'dropship' ? 'selected' : '' }}>Dropshipper / Reseller</option>
                        <option value="umum" {{ $customerCat === 'umum' ? 'selected' : '' }}>Pelanggan Umum / Eceran</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted">Cari SKU / Nama Produk</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Ketik kata kunci SKU / Nama..." value="{{ $search }}">
                </div>

                <div class="col-md-3 d-flex align-items-center">
                    <div class="form-check form-switch pt-3">
                        <input class="form-check-input" type="checkbox" name="hide_zero_sales" value="1" id="hideZeroSales" {{ !empty($hideZeroSales) ? 'checked' : '' }}>
                        <label class="form-check-label small fw-semibold text-dark" for="hideZeroSales">
                            Sembunyikan Produk 0 Penjualan
                        </label>
                    </div>
                </div>

                <div class="col-md-12 d-flex justify-content-end gap-2 mt-3 pt-2 border-top">
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold">
                        <i class="fas fa-filter me-1"></i> Filter Laporan
                    </button>
                    <a href="{{ route('reports.sales.export', request()->all()) }}" class="btn btn-outline-success btn-sm px-3 fw-semibold">
                        <i class="fas fa-file-excel me-1"></i> Export Excel/CSV
                    </a>
                    <a href="{{ route('reports.sales.print', request()->all()) }}" target="_blank" class="btn btn-success btn-sm px-4 fw-semibold">
                        <i class="fas fa-print me-1"></i> Cetak Laporan
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- TABLE REKAP PENJUALAN --}}
    <div class="card border-0 shadow-sm rounded-3 bg-white">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="fas fa-list me-1 text-primary"></i> Rincian Penjualan per Produk ({{ count($items) }} Produk)
                </h6>
                <span class="badge bg-light text-muted border font-monospace">
                    Periode: {{ date('d M Y', strtotime($dateFrom)) }} — {{ date('d M Y', strtotime($dateTo)) }}
                </span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle border mb-0 rounded-2 overflow-hidden">
                    <thead style="background:#f8fafc">
                        <tr class="small text-uppercase text-muted" style="font-size: 0.72rem;">
                            <th class="py-2.5 px-3">SKU</th>
                            <th>Nama Produk</th>
                            <th>Kategori</th>
                            <th class="text-center">Stok Fisik</th>
                            <th class="text-center text-primary" style="background:#eff6ff">Qty Offline</th>
                            <th class="text-center text-success" style="background:#f0fdf4">Qty Online</th>
                            <th class="text-center fw-bold">Total Terjual</th>
                            <th class="text-end">HPP Modal</th>
                            <th class="text-end fw-bold text-primary">Total Omset</th>
                            <th class="text-end text-muted">Total HPP</th>
                            <th class="text-end fw-bold text-success">Laba Kotor</th>
                            <th class="text-center">Margin</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        @forelse($items as $row)
                            <tr>
                                <td class="px-3 font-monospace text-primary fw-bold" style="font-size:0.8rem;">
                                    {{ $row['sku'] ?: '—' }}
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $row['name'] }}</div>
                                    <small class="text-muted" style="font-size: 0.7rem;">Brand: {{ $row['brand_name'] }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $row['category_name'] }}</span>
                                </td>
                                <td class="text-center font-monospace">
                                    <span class="badge {{ $row['stock'] > 0 ? 'bg-secondary' : 'bg-danger' }}">
                                        {{ number_format($row['stock']) }}
                                    </span>
                                </td>
                                <td class="text-center font-monospace fw-semibold text-primary" style="background:#f8fafc">
                                    {{ number_format($row['qty_offline']) }}
                                </td>
                                <td class="text-center font-monospace fw-semibold text-success" style="background:#f8fafc">
                                    {{ number_format($row['qty_online']) }}
                                </td>
                                <td class="text-center font-monospace fw-bold text-dark fs-6">
                                    {{ number_format($row['qty_total']) }}
                                </td>
                                <td class="text-end font-monospace text-muted">
                                    Rp {{ number_format($row['cost_price'], 0, ',', '.') }}
                                </td>
                                <td class="text-end font-monospace fw-bold text-primary">
                                    Rp {{ number_format($row['total_omset'], 0, ',', '.') }}
                                </td>
                                <td class="text-end font-monospace text-muted">
                                    Rp {{ number_format($row['total_hpp'], 0, ',', '.') }}
                                </td>
                                <td class="text-end font-monospace fw-bold text-success">
                                    Rp {{ number_format($row['gross_profit'], 0, ',', '.') }}
                                </td>
                                <td class="text-center font-monospace">
                                    <span class="badge {{ $row['profit_margin'] >= 20 ? 'bg-success' : ($row['profit_margin'] > 0 ? 'bg-warning text-dark' : 'bg-secondary') }}">
                                        {{ number_format($row['profit_margin'], 1) }}%
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block text-secondary"></i>
                                    Tidak ada data penjualan produk ditemukan dengan filter ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(count($items) > 0)
                        <tfoot class="bg-light fw-bold small">
                            <tr>
                                <td colspan="4" class="text-uppercase px-3 text-dark">TOTAL REKAPITULASI</td>
                                <td class="text-center font-monospace text-primary"></td>
                                <td class="text-center font-monospace text-success"></td>
                                <td class="text-center font-monospace text-dark fs-6">{{ number_format($grandTotalQty) }}</td>
                                <td></td>
                                <td class="text-end font-monospace text-primary fs-6">Rp {{ number_format($grandTotalOmset, 0, ',', '.') }}</td>
                                <td class="text-end font-monospace text-muted">Rp {{ number_format($grandTotalHpp, 0, ',', '.') }}</td>
                                <td class="text-end font-monospace text-success fs-6">Rp {{ number_format($grandTotalProfit, 0, ',', '.') }}</td>
                                <td class="text-center font-monospace text-success fs-6">{{ number_format($overallMargin, 1) }}%</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
