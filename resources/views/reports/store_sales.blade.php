@extends('layouts.app')
@section('title', 'Rekonsiliasi Omset & Marketplace')
@section('page-title', 'Rekonsiliasi Omset & Marketplace Per Toko')

@section('content')
    {{-- Filter Bar --}}
    <div class="card border shadow-sm mb-4 bg-white">
        <div class="card-body py-2 px-3">
            <form method="GET" action="{{ route('reports.store_sales') }}">
                <div class="row g-2 align-items-end">
                    <div class="col-6 col-md-3">
                        <label class="form-label form-label-sm fw-semibold mb-1 text-muted">Filter Berdasarkan Tanggal</label>
                        <select name="date_type" class="form-select form-select-sm fw-semibold text-primary">
                            <option value="order_date" {{ ($dateType ?? 'order_date') === 'order_date' ? 'selected' : '' }}>
                                📅 Tanggal Transaksi (Order Masuk)
                            </option>
                            <option value="completed_at" {{ ($dateType ?? '') === 'completed_at' ? 'selected' : '' }}>
                                💵 Tanggal Dana Dilepas / Cair (Escrow Selesai)
                            </option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label form-label-sm fw-semibold mb-1 text-muted">Dari Tanggal</label>
                        <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label form-label-sm fw-semibold mb-1 text-muted">Sampai Tanggal</label>
                        <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-12 col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm flex-fill fw-semibold">
                            <i class="fas fa-filter me-1"></i> Filter Data
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @php
        $totErpOrders = 0;
        $totErpGross  = 0;
        $totErpAdmin  = 0;
        $totErpNet    = 0;

        foreach($storeStats as $s) {
            $totErpOrders += $s['orders'];
            $totErpGross  += ($s['gross_sales'] ?? 0);
            $totErpAdmin  += ($s['admin_fee'] ?? 0);
            $totErpNet    += $s['sales'];
        }
    @endphp

    {{-- Ringkasan Card --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-primary bg-gradient text-white p-3 rounded-3">
                <div class="small text-white-50 fw-semibold text-uppercase">Total Jumlah Order</div>
                <div class="fs-4 fw-bold mt-1">{{ number_format($totErpOrders) }} Order</div>
                <div class="small mt-1 text-white-75">Klik nama toko untuk rincian order</div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-dark bg-gradient text-white p-3 rounded-3">
                <div class="small text-white-50 fw-semibold text-uppercase">Total Omset Kotor</div>
                <div class="fs-4 fw-bold mt-1">Rp {{ number_format($totErpGross, 0, ',', '.') }}</div>
                <div class="small mt-1 text-white-75">Total Penjualan Sebelum Potongan</div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-danger bg-gradient text-white p-3 rounded-3">
                <div class="small text-white-50 fw-semibold text-uppercase">Total Biaya Admin</div>
                <div class="fs-4 fw-bold mt-1">Rp {{ number_format($totErpAdmin, 0, ',', '.') }}</div>
                <div class="small mt-1 text-white-75">Total Potongan Marketplace</div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-success bg-gradient text-white p-3 rounded-3">
                <div class="small text-white-50 fw-semibold text-uppercase">Total Omset Bersih (Net)</div>
                <div class="fs-4 fw-bold mt-1">Rp {{ number_format($totErpNet, 0, ',', '.') }}</div>
                <div class="small mt-1 text-white-75">Total Dana Cair Ke Rekening</div>
            </div>
        </div>
    </div>

    {{-- Tabel Utama Per Toko dengan Fitur Detail Transaksi --}}
    <div class="card border shadow-sm bg-white mb-4">
        <div class="card-header bg-light py-3 px-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-dark">
                <i class="fas fa-store text-primary me-2"></i>Rekonsiliasi Penjualan Per Toko (Klik Nama Toko Untuk Rincian Pesanan)
            </h6>
            <span class="badge bg-primary px-3 py-2">
                <i class="fas fa-calendar-alt me-1"></i> {{ date('d M Y', strtotime($dateFrom)) }} – {{ date('d M Y', strtotime($dateTo)) }}
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0" style="font-size: 0.85rem;">
                    <thead class="table-dark text-center align-middle">
                        <tr>
                            <th class="ps-3 text-start" style="min-width: 220px;">Nama Toko / Cabang</th>
                            <th style="min-width: 120px;">Channel</th>
                            <th style="min-width: 120px;">Jumlah Order</th>
                            <th class="text-end" style="min-width: 150px;">Omset Kotor</th>
                            <th class="text-end text-warning" style="min-width: 140px;">Biaya Admin</th>
                            <th class="text-end text-success" style="min-width: 150px;">Omset Bersih (Net)</th>
                            <th style="min-width: 140px;">Aksi / Rincian</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($storeStats as $index => $stat)
                            @php
                                $storeKey = 'store_' . ($stat['id'] ?? $index);
                                $details = $stat['orders_detail'] ?? [];
                            @endphp
                            {{-- Baris Toko Utama --}}
                            <tr class="table-light-hover" role="button" data-bs-toggle="collapse" data-bs-target="#collapseOrder_{{ $storeKey }}">
                                <td class="ps-3 fw-bold text-primary">
                                    <i class="fas fa-chevron-right me-2 text-muted small transition-icon" id="icon_{{ $storeKey }}"></i>
                                    {{ $stat['name'] }}
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary-subtle text-secondary py-1 px-2 border border-secondary border-opacity-10 small fw-semibold">
                                        {{ $stat['channel'] }}
                                    </span>
                                </td>
                                <td class="text-center fw-bold text-dark">{{ number_format($stat['orders']) }} Order</td>
                                <td class="text-end font-monospace fw-semibold text-dark">
                                    Rp {{ number_format($stat['gross_sales'] ?? $stat['sales'], 0, ',', '.') }}
                                </td>
                                <td class="text-end font-monospace fw-semibold text-danger">
                                    Rp {{ number_format($stat['admin_fee'] ?? 0, 0, ',', '.') }}
                                </td>
                                <td class="text-end font-monospace fw-bold text-success fs-6">
                                    Rp {{ number_format($stat['sales'], 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-xs btn-outline-primary py-1 px-2 fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOrder_{{ $storeKey }}">
                                        <i class="fas fa-list me-1"></i> Rincian ({{ count($details) }})
                                    </button>
                                </td>
                            </tr>

                            {{-- Collapsible Baris Rincian Transaksi Order --}}
                            <tr>
                                <td colspan="7" class="p-0 border-0">
                                    <div class="collapse bg-light p-3 border-top border-bottom" id="collapseOrder_{{ $storeKey }}">
                                        <div class="d-flex justify-content-between align-items-center mb-2 px-2">
                                            <h6 class="fw-bold text-dark mb-0">
                                                <i class="fas fa-file-invoice-dollar text-primary me-2"></i>Rincian Transaksi: <span class="text-primary">{{ $stat['name'] }}</span>
                                            </h6>
                                            <span class="badge bg-dark px-2 py-1">{{ count($details) }} Transaksi Ditemukan</span>
                                        </div>

                                        @if(!empty($details))
                                            <div class="table-responsive bg-white rounded border shadow-sm">
                                                <table class="table table-sm table-striped table-hover mb-0" style="font-size: 0.8rem;">
                                                    <thead class="table-secondary text-uppercase small">
                                                        <tr>
                                                            <th class="ps-3">#</th>
                                                            <th>No. Order Marketplace</th>
                                                            <th>Tanggal</th>
                                                            <th>Nama Pembeli</th>
                                                            <th class="text-center">Status</th>
                                                            <th class="text-end">Omset Kotor</th>
                                                            <th class="text-end text-danger">Biaya Admin</th>
                                                            <th class="text-end text-success pe-3">Omset Bersih</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($details as $i => $od)
                                                            <tr>
                                                                <td class="ps-3 text-muted">{{ $i + 1 }}</td>
                                                                <td class="fw-bold text-primary font-monospace">{{ $od['order_sn'] }}</td>
                                                                <td class="text-muted">{{ date('d/m/Y H:i', strtotime($od['order_date'])) }}</td>
                                                                <td class="fw-semibold text-dark">{{ $od['buyer_name'] }}</td>
                                                                <td class="text-center">
                                                                    <span class="badge bg-info-subtle text-info border border-info border-opacity-25 px-2 py-1 small">
                                                                        {{ $od['order_status'] }}
                                                                    </span>
                                                                </td>
                                                                <td class="text-end font-monospace text-dark">Rp {{ number_format($od['total_amount'], 0, ',', '.') }}</td>
                                                                <td class="text-end font-monospace text-danger">Rp {{ number_format($od['marketplace_fee'], 0, ',', '.') }}</td>
                                                                <td class="text-end font-monospace fw-bold text-success pe-3">Rp {{ number_format($od['net_amount'], 0, ',', '.') }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <div class="alert alert-light border text-center py-3 mb-0 text-muted">
                                                Tidak ada rincian pesanan untuk toko ini dalam rentang tanggal terpilih.
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="fas fa-store-slash fa-2x mb-3 text-secondary opacity-25"></i>
                                    <p class="mb-0">Tidak ada transaksi dalam rentang tanggal ini.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(count($storeStats) > 0)
                        <tfoot class="table-dark fw-bold align-middle">
                            <tr>
                                <td colspan="2" class="ps-3 text-uppercase">GRAND TOTAL SELURUH TOKO</td>
                                <td class="text-center">{{ number_format($totErpOrders) }} Order</td>
                                <td class="text-end font-monospace">Rp {{ number_format($totErpGross, 0, ',', '.') }}</td>
                                <td class="text-end font-monospace text-danger">Rp {{ number_format($totErpAdmin, 0, ',', '.') }}</td>
                                <td class="text-end font-monospace text-success fs-6">Rp {{ number_format($totErpNet, 0, ',', '.') }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
@endsection
