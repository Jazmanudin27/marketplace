@extends('layouts.app')
@section('title', 'Laporan Rekap Penjualan')
@section('page-title', 'Laporan Rekap Penjualan')

@section('content')
<div class="row justify-content-start">
    <div class="col-md-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-info bg-opacity-10 py-2 px-3">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="fas fa-th-list text-info me-2"></i>Filter Rekap Penjualan
                </h6>
            </div>
            <div class="card-body">
                <form id="salesReportForm" action="{{ route('reports.sales.print') }}" method="GET" target="_blank">
                    {{-- PILIHAN FORMAT LAPORAN (Sesuai Permintaan User) --}}
                    <div class="mb-3">
                        <label class="form-label form-label-sm fw-bold text-primary">Format Laporan Penjualan</label>
                        <select name="report_format" class="form-select form-select-sm border-primary fw-bold text-primary bg-primary bg-opacity-10">
                            <option value="per_produk" {{ $reportFormat === 'per_produk' ? 'selected' : '' }}>📦 Laporan Per Produk</option>
                            <option value="per_channel" {{ $reportFormat === 'per_channel' ? 'selected' : '' }}>🏪 Laporan Per Channel / Saluran</option>
                            <option value="detail" {{ $reportFormat === 'detail' ? 'selected' : '' }}>📑 Laporan Detail Transaksi</option>
                            <option value="per_tanggal" {{ $reportFormat === 'per_tanggal' ? 'selected' : '' }}>📅 Laporan Per Tanggal</option>
                            <option value="per_kategori_pelanggan" {{ $reportFormat === 'per_kategori_pelanggan' ? 'selected' : '' }}>👥 Laporan Per Kategori Pelanggan</option>
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
                        <label class="form-label form-label-sm fw-semibold">Tipe Pre-Order (PO)</label>
                        <select name="po_status" class="form-select form-select-sm">
                            <option value="">Semua Tipe (PO &amp; Reguler)</option>
                            <option value="1" {{ $isPo === '1' ? 'selected' : '' }}>⏳ Pre-Order (PO)</option>
                            <option value="0" {{ $isPo === '0' ? 'selected' : '' }}>📦 Reguler (Bukan PO)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label form-label-sm fw-semibold">Saluran Penjualan / Channel</label>
                        <select name="channel_code" class="form-select form-select-sm">
                            <option value="all" {{ $channelCode === 'all' ? 'selected' : '' }}>🌐 Semua Saluran (Offline POS &amp; Online)</option>
                            <option value="offline" {{ $channelCode === 'offline' ? 'selected' : '' }}>🏪 Penjualan Offline (POS Toko Fisik)</option>
                            <option value="online" {{ $channelCode === 'online' ? 'selected' : '' }}>🛒 Penjualan Online (Semua Marketplace)</option>
                            <option value="shopee" {{ $channelCode === 'shopee' ? 'selected' : '' }}>🟠 Shopee</option>
                            <option value="tiktok" {{ $channelCode === 'tiktok' ? 'selected' : '' }}>🎵 TikTok Shop</option>
                            <option value="lazada" {{ $channelCode === 'lazada' ? 'selected' : '' }}>🔵 Lazada</option>
                            <option value="tokopedia" {{ $channelCode === 'tokopedia' ? 'selected' : '' }}>🟢 Tokopedia</option>
                        </select>
                    </div>

                    {{-- KATEGORI PELANGGAN (Sesuai Master Data Customer) --}}
                    <div class="mb-3">
                        <label class="form-label form-label-sm fw-semibold">Kategori Pelanggan (Master Data)</label>
                        <select name="customer_category" class="form-select form-select-sm">
                            <option value="all" {{ $customerCat === 'all' ? 'selected' : '' }}>Semua Kategori Pelanggan</option>
                            @foreach ($customerCategories as $catVal)
                                @php
                                    $label = $customerCategoryLabels[$catVal] ?? ucfirst($catVal);
                                @endphp
                                <option value="{{ $catVal }}" {{ $customerCat === $catVal ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label form-label-sm fw-semibold">Dari Tanggal</label>
                            <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label form-label-sm fw-semibold">Sampai Tanggal</label>
                            <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo }}">
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="hide_zero_sales" value="1" id="hideZeroSales" {{ !empty($hideZeroSales) ? 'checked' : '' }}>
                            <label class="form-check-label small fw-semibold text-dark" for="hideZeroSales">
                                Sembunyikan / Hilangkan Produk 0 Penjualan
                            </label>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-sm btn-primary px-4 fw-bold">
                            <i class="fas fa-print me-1"></i> Cetak Rekap Penjualan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
