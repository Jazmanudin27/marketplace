@extends('layouts.app')
@section('title', 'Laporan Penjualan Dilepas (Dana Cair)')
@section('page-title', 'Laporan Penjualan Dilepas (Dana Cair)')

@section('content')
<div class="row justify-content-start g-3">
    <!-- METRIC CARDS -->
    <div class="col-12">
        <div class="row g-3">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm bg-success bg-opacity-10 border-start border-4 border-success h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <small class="text-uppercase fw-bold text-success" style="font-size: 0.75rem;">Total Dana Dilepas (Net)</small>
                                <h4 class="fw-bold mb-0 text-success mt-1">Rp {{ number_format($summary['net_released'], 0, ',', '.') }}</h4>
                            </div>
                            <div class="bg-success text-white rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="bi bi-cash-stack fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm bg-primary bg-opacity-10 border-start border-4 border-primary h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <small class="text-uppercase fw-bold text-primary" style="font-size: 0.75rem;">Total Omset Kotor (Gross)</small>
                                <h4 class="fw-bold mb-0 text-primary mt-1">Rp {{ number_format($summary['gross_revenue'], 0, ',', '.') }}</h4>
                            </div>
                            <div class="bg-primary text-white rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="bi bi-graph-up-arrow fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm bg-warning bg-opacity-10 border-start border-4 border-warning h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <small class="text-uppercase fw-bold text-dark" style="font-size: 0.75rem;">Potongan Marketplace</small>
                                <h4 class="fw-bold mb-0 text-dark mt-1">Rp {{ number_format($summary['marketplace_fee'], 0, ',', '.') }}</h4>
                            </div>
                            <div class="bg-warning text-dark rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="bi bi-percent fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm bg-info bg-opacity-10 border-start border-4 border-info h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <small class="text-uppercase fw-bold text-info" style="font-size: 0.75rem;">Total Transaksi Selesai</small>
                                <h4 class="fw-bold mb-0 text-info mt-1">{{ number_format($summary['total_orders'], 0, ',', '.') }} Order</h4>
                            </div>
                            <div class="bg-info text-white rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="bi bi-check-circle-fill fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FILTER FORM CARD -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-success bg-opacity-10 py-2 px-3">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-funnel-fill text-success me-2"></i>Filter Penjualan Dilepas (Escrow Released)
                </h6>
            </div>
            <div class="card-body">
                <form id="releasedSalesReportForm" action="{{ route('reports.released_sales.print') }}" method="GET" target="_blank">
                    {{-- PILIHAN FORMAT LAPORAN --}}
                    <div class="mb-3">
                        <label class="form-label form-label-sm fw-bold text-success">Format Laporan Penjualan Dilepas</label>
                        <select name="report_format" class="form-select form-select-sm border-success fw-bold text-success bg-success bg-opacity-10">
                            <option value="per_produk" {{ $reportFormat === 'per_produk' ? 'selected' : '' }}>📦 Laporan Per Produk (Dilepas)</option>
                            <option value="per_channel" {{ $reportFormat === 'per_channel' ? 'selected' : '' }}>🏪 Laporan Per Channel Marketplace (Dilepas)</option>
                            <option value="detail" {{ $reportFormat === 'detail' ? 'selected' : '' }}>📑 Laporan Detail Transaksi (Dilepas)</option>
                            <option value="per_tanggal" {{ $reportFormat === 'per_tanggal' ? 'selected' : '' }}>📅 Laporan Per Tanggal (Dilepas)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label form-label-sm fw-semibold">Kategori Produk</label>
                        <select name="category_id" class="form-select form-select-sm">
                            <option value="">Semua Kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" {{ $categoryId == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label form-label-sm fw-semibold">Merk</label>
                        <select name="brand_id" class="form-select form-select-sm">
                            <option value="">Semua Merk</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->id }}" {{ $brandId == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label form-label-sm fw-semibold">Jenis Produk</label>
                        <select name="is_bundle" class="form-select form-select-sm">
                            <option value="">Semua Jenis (Single &amp; BUNDLE)</option>
                            <option value="0" {{ $isBundle === '0' ? 'selected' : '' }}>📦 Single (Produk Standar)</option>
                            <option value="1" {{ $isBundle === '1' ? 'selected' : '' }}>🎁 BUNDLE / Paket Set</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label form-label-sm fw-semibold">Toko Marketplace</label>
                        <select name="store_id" class="form-select form-select-sm">
                            <option value="" {{ empty($storeId) ? 'selected' : '' }}>🛒 Semua Toko Marketplace</option>
                            @foreach ($stores as $store)
                                <option value="{{ $store->id }}" {{ (isset($storeId) && $storeId == $store->id) ? 'selected' : '' }}>
                                    {{ $store->store_name }} ({{ $store->channel->name ?? 'Marketplace' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label form-label-sm fw-semibold">Status Transaksi</label>
                        <input type="text" class="form-control form-control-sm bg-light text-success fw-bold" value="✅ Selesai / Completed (Khusus Penjualan Dilepas)" readonly>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label form-label-sm fw-semibold text-success"><i class="bi bi-calendar-check me-1"></i>Dari Tanggal (Dilepas / Cair)</label>
                            <input type="date" name="date_from" class="form-control form-control-sm border-success" value="{{ $dateFrom }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label form-label-sm fw-semibold text-success"><i class="bi bi-calendar-check me-1"></i>Sampai Tanggal (Dilepas / Cair)</label>
                            <input type="date" name="date_to" class="form-control form-control-sm border-success" value="{{ $dateTo }}">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ route('reports.released_sales.export', request()->all()) }}" class="btn btn-sm btn-outline-success px-3 fw-bold">
                            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export CSV
                        </a>
                        <button type="submit" class="btn btn-sm btn-success px-4 fw-bold">
                            <i class="bi bi-printer-fill me-1"></i> Cetak Rekap Penjualan Dilepas
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
