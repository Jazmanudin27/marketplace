@extends('layouts.app')
@section('title', 'Laporan Margin Katalog Produk')
@section('page-title', 'Margin Katalog Produk (BOM & Jual)')

@section('content')
    @php
        $totalProducts = $products->count();
        $avgCost = $products->avg('cost_price') ?: 0;
        $avgPrice = $products->avg('price') ?: 0;
        
        $totalRegMarginNominal = 0;
        $totalDsMarginNominal = 0;
        $regMarginProductsCount = 0;
        $dsMarginProductsCount = 0;

        foreach($products as $p) {
            $cost = (float)$p->cost_price;
            $price = (float)$p->price;
            $rslPrice = (float)$p->reseller_price;

            if ($price > 0) {
                $totalRegMarginNominal += ($price - $cost);
                $regMarginProductsCount++;
            }
            if ($rslPrice > 0) {
                $totalDsMarginNominal += ($rslPrice - $cost);
                $dsMarginProductsCount++;
            }
        }

        $avgRegMarginPct = $avgPrice > 0 ? (($avgPrice - $avgCost) / $avgPrice) * 100 : 0;
        $avgDsMarginPct = $products->avg('reseller_price') > 0 ? (($products->avg('reseller_price') - $avgCost) / $products->avg('reseller_price')) * 100 : 0;
    @endphp

    {{-- KPI Cards --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-4 col-lg">
            <div class="card h-100 border border-secondary border-opacity-10 shadow-sm">
                <div class="card-body py-3 px-3">
                    <div class="text-muted small fw-semibold text-uppercase mb-1">
                        <i class="fas fa-boxes me-1 text-primary"></i> Total Produk
                    </div>
                    <div class="fw-bold fs-5 text-dark">{{ number_format($totalProducts) }}</div>
                    <div class="small text-muted mt-1">Produk Tunggal & Set</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="card h-100 border border-secondary border-opacity-10 shadow-sm">
                <div class="card-body py-3 px-3">
                    <div class="text-muted small fw-semibold text-uppercase mb-1">
                        <i class="fas fa-wallet me-1 text-danger"></i> Rata-rata HPP
                    </div>
                    <div class="fw-bold fs-5 text-danger">Rp {{ number_format($avgCost, 0, ',', '.') }}</div>
                    <div class="small text-muted mt-1">Berdasarkan BOM aktif</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="card h-100 border border-secondary border-opacity-10 shadow-sm">
                <div class="card-body py-3 px-3">
                    <div class="text-muted small fw-semibold text-uppercase mb-1">
                        <i class="fas fa-money-bill me-1 text-success"></i> Rata-rata Harga Jual
                    </div>
                    <div class="fw-bold fs-5 text-success">Rp {{ number_format($avgPrice, 0, ',', '.') }}</div>
                    <div class="small text-muted mt-1">Harga jual reguler</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-6 col-lg">
            <div class="card h-100 border border-secondary border-opacity-10 shadow-sm">
                <div class="card-body py-3 px-3">
                    <div class="text-muted small fw-semibold text-uppercase mb-1">
                        <i class="fas fa-chart-pie me-1 text-primary"></i> Avg Margin Reguler
                    </div>
                    <div class="fw-bold fs-5 text-primary">{{ round($avgRegMarginPct, 2) }}%</div>
                    <div class="small text-muted mt-1">Dari harga jual reguler</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-6 col-lg">
            <div class="card h-100 border border-secondary border-opacity-10 shadow-sm bg-dark text-white">
                <div class="card-body py-3 px-3">
                    <div class="text-white-50 small fw-semibold text-uppercase mb-1">
                        <i class="fas fa-hand-holding-usd me-1 text-warning"></i> Avg Margin Reseller
                    </div>
                    <div class="fw-bold fs-5 text-warning">{{ round($avgDsMarginPct, 2) }}%</div>
                    <div class="small text-white-50 mt-1">Dari harga khusus reseller</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="card border border-secondary border-opacity-10 shadow-sm mb-3">
        <div class="card-body py-2 px-3">
            <form method="GET" action="{{ route('reports.product_margins') }}">
                <div class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label form-label-sm fw-semibold mb-1">Cari Produk</label>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Ketik nama produk atau SKU...">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label form-label-sm fw-semibold mb-1">Kategori</label>
                        <select name="category_id" class="form-select form-select-sm">
                            <option value="">Semua Kategori</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm flex-fill fw-semibold">
                            <i class="fas fa-search me-1"></i> Cari
                        </button>
                        @if (request()->anyFilled(['search', 'category_id']))
                            <a href="{{ route('reports.product_margins') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-undo"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card border border-secondary border-opacity-10 shadow-sm">
        <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center border-bottom">
            <div>
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="fas fa-percent me-2 text-primary"></i>Analisis Margin Keuntungan Katalog
                </h6>
                <small class="text-muted d-block mt-1">Perbandingan margin keuntungan produk antara harga jual reguler dan reseller berdasarkan HPP/modal aktif.</small>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase text-muted" style="font-size: 10px;">
                        <tr>
                            <th class="ps-4 text-center" style="width: 5%">No</th>
                            <th style="width: 25%">Produk / SKU</th>
                            <th class="text-end" style="width: 12%">HPP (Modal)</th>
                            <th class="text-end" style="width: 12%">Jual Reguler</th>
                            <th class="text-center" style="width: 12%">Margin Reguler</th>
                            <th class="text-end" style="width: 12%">Jual Reseller</th>
                            <th class="text-center pe-4" style="width: 12%">Margin Reseller</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $no = 1; @endphp
                        @forelse ($products as $p)
                            @php
                                $cost = (float)$p->cost_price;
                                $price = (float)$p->price;
                                $rslPrice = (float)$p->reseller_price;

                                // Reg margin
                                $regMarginNom = $price - $cost;
                                $regMarginPct = $price > 0 ? ($regMarginNom / $price) * 100 : 0;

                                $regBadge = 'bg-success-subtle text-success';
                                if ($regMarginPct < 15) {
                                    $regBadge = 'bg-danger-subtle text-danger';
                                } elseif ($regMarginPct < 30) {
                                    $regBadge = 'bg-warning-subtle text-warning';
                                }

                                // Reseller margin
                                $rslMarginNom = $rslPrice > 0 ? ($rslPrice - $cost) : 0;
                                $rslMarginPct = $rslPrice > 0 ? ($rslMarginNom / $rslPrice) * 100 : 0;

                                $rslBadge = 'bg-success-subtle text-success';
                                if ($rslMarginPct < 15) {
                                    $rslBadge = 'bg-danger-subtle text-danger';
                                } elseif ($rslMarginPct < 30) {
                                    $rslBadge = 'bg-warning-subtle text-warning';
                                }
                            @endphp
                            <tr>
                                <td class="ps-4 text-center text-muted small">{{ $no++ }}</td>
                                <td>
                                    <span class="fw-semibold text-dark small d-block">{{ $p->name }}</span>
                                    <code class="font-monospace text-muted" style="font-size: 11px;">SKU: {{ $p->sku }}</code>
                                </td>
                                <td class="text-end font-monospace text-dark small fw-semibold">
                                    Rp {{ number_format($cost, 0, ',', '.') }}
                                </td>
                                <td class="text-end font-monospace text-primary small">
                                    Rp {{ number_format($price, 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    <div class="lh-sm">
                                        <span class="badge {{ $regBadge }} fw-bold px-2 py-1" style="font-size: 11px;">
                                            {{ round($regMarginPct, 2) }}%
                                        </span>
                                        <div class="text-muted small mt-1 font-monospace" style="font-size: 10px;">
                                            +Rp {{ number_format($regMarginNom, 0, ',', '.') }}
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end font-monospace text-success small">
                                    @if ($rslPrice > 0)
                                        Rp {{ number_format($rslPrice, 0, ',', '.') }}
                                    @else
                                        <span class="text-muted opacity-50">—</span>
                                    @endif
                                </td>
                                <td class="text-center pe-4">
                                    @if ($rslPrice > 0)
                                        <div class="lh-sm">
                                            <span class="badge {{ $rslBadge }} fw-bold px-2 py-1" style="font-size: 11px;">
                                                {{ round($rslMarginPct, 2) }}%
                                            </span>
                                            <div class="text-muted small mt-1 font-monospace" style="font-size: 10px;">
                                                +Rp {{ number_format($rslMarginNom, 0, ',', '.') }}
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-muted opacity-50">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fas fa-boxes fa-3x mb-3 opacity-25 d-block"></i>
                                    Tidak ada data produk yang ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
