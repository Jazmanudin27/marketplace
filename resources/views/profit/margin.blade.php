@extends('layouts.app')
@section('title', 'Laporan Margin Produk Aktual')
@section('page-title', 'Margin Produk Aktual (HPP Real-time)')

@section('content')
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2 px-3">
        <form method="GET" action="{{ route('profit.margin') }}">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-3">
                    <label class="form-label form-label-sm fw-semibold mb-1">Dari Tanggal</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label form-label-sm fw-semibold mb-1">Sampai Tanggal</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control form-control-sm">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label form-label-sm fw-semibold mb-1">Toko / Marketplace</label>
                    <select name="store_id" class="form-select form-select-sm">
                        <option value="">Semua Toko (Online &amp; Offline)</option>
                        @foreach ($stores as $store)
                            <option value="{{ $store->id }}" {{ $storeId == $store->id ? 'selected' : '' }}>
                                {{ $store->store_name }} ({{ $store->channel->name }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill">
                        <i class="fas fa-filter me-1"></i>Filter
                    </button>
                    @if (request()->anyFilled(['date_from', 'date_to', 'store_id']))
                        <a href="{{ route('profit.margin') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-undo"></i>
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center border-bottom">
        <div>
            <h6 class="fw-bold mb-0 text-dark">
                <i class="fas fa-chart-line me-2 text-success"></i>Tabel Analisis Margin Keuntungan Produk
            </h6>
            <small class="text-muted d-block mt-1">HPP dihitung real-time menggunakan riwayat produksi SPK dan pemotongan bahan baku.</small>
        </div>
        <div class="d-flex gap-2">
            <input type="text" id="search-product" class="form-control form-control-sm" placeholder="Cari nama produk / SKU..." style="width: 250px;">
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle border mb-0" id="table-margin-analysis">
                <thead class="table-light text-uppercase text-muted" style="font-size: 10px;">
                    <tr>
                        <th class="ps-4 text-center" style="width: 5%">No</th>
                        <th style="width: 30%">Nama Produk / SKU</th>
                        <th class="text-center" style="width: 10%">Qty Terjual</th>
                        <th class="text-end" style="width: 12%">Gross Sales</th>
                        <th class="text-end" style="width: 10%">Biaya Admin</th>
                        <th class="text-end" style="width: 12%">Net Payout</th>
                        <th class="text-end" style="width: 12%">HPP Aktual</th>
                        <th class="text-end" style="width: 12%">Laba Bersih</th>
                        <th class="text-center" style="width: 10%">Margin (%)</th>
                        <th class="text-end pe-4" style="width: 10%">HPP / Pcs</th>
                    </tr>
                </thead>
                <tbody>
                    @php $no = 1; @endphp
                    @forelse ($productMetrics as $prodId => $m)
                        @php
                            $marginClass = 'bg-success-subtle text-success';
                            if ($m['margin_pct'] < 10) {
                                $marginClass = 'bg-danger-subtle text-danger';
                            } elseif ($m['margin_pct'] < 30) {
                                $marginClass = 'bg-warning-subtle text-warning';
                            }
                        @endphp
                        <tr class="product-row" data-name="{{ strtolower($m['name']) }}" data-sku="{{ strtolower($m['sku']) }}">
                            <td class="ps-4 text-center text-muted small">{{ $no++ }}</td>
                            <td>
                                <span class="fw-semibold text-dark small d-block">{{ $m['name'] }}</span>
                                <code class="font-monospace text-muted" style="font-size: 11px;">SKU: {{ $m['sku'] }}</code>
                            </td>
                            <td class="text-center fw-bold text-dark small">{{ number_format($m['qty_sold']) }}</td>
                            <td class="text-end small">Rp {{ number_format($m['gross_sales'], 0, ',', '.') }}</td>
                            <td class="text-end text-danger small">Rp {{ number_format($m['allocated_fees'], 0, ',', '.') }}</td>
                            <td class="text-end fw-semibold text-dark small">Rp {{ number_format($m['net_payout'], 0, ',', '.') }}</td>
                            <td class="text-end text-muted small">Rp {{ number_format($m['actual_hpp'], 0, ',', '.') }}</td>
                            <td class="text-end fw-bold text-success small">
                                @if($m['net_profit'] < 0)
                                    <span class="text-danger">-Rp {{ number_format(abs($m['net_profit']), 0, ',', '.') }}</span>
                                @else
                                    <span>Rp {{ number_format($m['net_profit'], 0, ',', '.') }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $marginClass }} fw-bold px-2 py-1" style="font-size: 11px;">
                                    {{ $m['margin_pct'] }}%
                                </span>
                            </td>
                            <td class="text-end fw-semibold text-primary font-monospace pe-4 small">
                                Rp {{ number_format($m['current_hpp'], 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="fas fa-chart-bar fa-3x mb-3 opacity-25 d-block"></i>
                                Tidak ada data penjualan produk untuk filter yang dipilih.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#search-product').on('input', function() {
        const query = $(this).val().toLowerCase();
        $('.product-row').each(function() {
            const name = $(this).data('name') || '';
            const sku = $(this).data('sku') || '';
            if (name.includes(query) || sku.includes(query)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
});
</script>
@endpush
