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
                        <button type="submit" name="refresh" value="1" class="btn btn-outline-secondary btn-sm fw-semibold" title="Tarik Ulang Perbandingan Live API">
                            <i class="fas fa-sync-alt me-1"></i> Refresh API
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

        $totApiOrders = 0;
        $totApiGross  = 0;
        $totApiAdmin  = 0;
        $totApiNet    = 0;

        foreach($storeStats as $s) {
            $totErpOrders += $s['orders'];
            $totErpGross  += ($s['gross_sales'] ?? 0);
            $totErpAdmin  += ($s['admin_fee'] ?? 0);
            $totErpNet    += $s['sales'];

            $totApiOrders += ($s['api_orders'] ?? 0);
            $totApiGross  += ($s['api_gross'] ?? 0);
            $totApiAdmin  += ($s['api_admin'] ?? 0);
            $totApiNet    += ($s['api_net'] ?? 0);
        }
    @endphp

    {{-- Ringkasan Card --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-primary bg-gradient text-white p-3 rounded-3">
                <div class="small text-white-50 fw-semibold text-uppercase">Total Jumlah Order</div>
                <div class="fs-4 fw-bold mt-1">{{ number_format($totErpOrders) }} Order</div>
                <div class="small mt-2 text-white-50">
                    API Marketplace: <span class="fw-bold text-white">{{ number_format($totApiOrders) }} Order</span>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-dark bg-gradient text-white p-3 rounded-3">
                <div class="small text-white-50 fw-semibold text-uppercase">Total Omset Kotor</div>
                <div class="fs-4 fw-bold mt-1">Rp {{ number_format($totErpGross, 0, ',', '.') }}</div>
                <div class="small mt-2 text-white-50">
                    API Marketplace: <span class="fw-bold text-white">Rp {{ number_format($totApiGross, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-danger bg-gradient text-white p-3 rounded-3">
                <div class="small text-white-50 fw-semibold text-uppercase">Total Biaya Admin</div>
                <div class="fs-4 fw-bold mt-1">Rp {{ number_format($totErpAdmin, 0, ',', '.') }}</div>
                <div class="small mt-2 text-white-50">
                    API Marketplace: <span class="fw-bold text-white">Rp {{ number_format($totApiAdmin, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-success bg-gradient text-white p-3 rounded-3">
                <div class="small text-white-50 fw-semibold text-uppercase">Total Omset Bersih (Net)</div>
                <div class="fs-4 fw-bold mt-1">Rp {{ number_format($totErpNet, 0, ',', '.') }}</div>
                <div class="small mt-2 text-white-50">
                    API Marketplace: <span class="fw-bold text-white">Rp {{ number_format($totApiNet, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabel Perbandingan Utama --}}
    <div class="card border shadow-sm bg-white mb-4">
        <div class="card-header bg-light py-3 px-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-dark">
                <i class="fas fa-balance-scale text-primary me-2"></i>Tabel Perbandingan Penjualan: ERP Local vs Live Marketplace API
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
                            <th rowspan="2" class="ps-3 text-start" style="min-width: 180px;">Nama Toko</th>
                            <th rowspan="2" style="min-width: 120px;">Channel</th>
                            <th rowspan="2" style="min-width: 130px;">Status Match</th>
                            <th colspan="3" class="bg-primary text-white">JUMLAH ORDER</th>
                            <th colspan="3" class="bg-secondary text-white">OMSET KOTOR</th>
                            <th colspan="3" class="bg-danger text-white">BIAYA ADMIN</th>
                            <th colspan="3" class="bg-success text-white">OMSET BERSIH (NET)</th>
                        </tr>
                        <tr class="small text-nowrap">
                            <th class="bg-primary bg-opacity-75 text-white">ERP</th>
                            <th class="bg-primary bg-opacity-75 text-white">API</th>
                            <th class="bg-primary bg-opacity-50 text-white">Selisih</th>

                            <th class="bg-dark text-white">ERP</th>
                            <th class="bg-dark text-white">API</th>
                            <th class="bg-dark text-white opacity-75">Selisih</th>

                            <th class="bg-danger bg-opacity-75 text-white">ERP</th>
                            <th class="bg-danger bg-opacity-75 text-white">API</th>
                            <th class="bg-danger bg-opacity-50 text-white">Selisih</th>

                            <th class="bg-success bg-opacity-75 text-white">ERP</th>
                            <th class="bg-success bg-opacity-75 text-white">API</th>
                            <th class="bg-success bg-opacity-50 text-white">Selisih</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($storeStats as $stat)
                            @php
                                $dOrders = $stat['diff_orders'] ?? 0;
                                $dGross  = $stat['diff_gross'] ?? 0;
                                $dAdmin  = $stat['diff_admin'] ?? 0;
                                $dNet    = $stat['diff_net'] ?? 0;
                                $isMatch = $stat['is_match'] ?? true;
                            @endphp
                            <tr>
                                <td class="ps-3 fw-bold text-dark">{{ $stat['name'] }}</td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary py-1 px-2 border border-secondary border-opacity-10 small fw-semibold">
                                        {{ $stat['channel'] }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if($isMatch)
                                        <span class="badge bg-success-subtle text-success border border-success border-opacity-25 px-2 py-1"><i class="fas fa-check-circle me-1"></i>100% MATCH</span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning border border-warning border-opacity-25 px-2 py-1"><i class="fas fa-exclamation-triangle me-1"></i>ADA SELISIH</span>
                                    @endif
                                </td>

                                {{-- Order Count --}}
                                <td class="text-center fw-semibold text-dark">{{ number_format($stat['orders']) }}</td>
                                <td class="text-center text-muted">{{ number_format($stat['api_orders'] ?? $stat['orders']) }}</td>
                                <td class="text-center font-monospace {{ $dOrders != 0 ? 'fw-bold text-danger' : 'text-muted' }}">
                                    {{ $dOrders >= 0 ? '+' : '' }}{{ number_format($dOrders) }}
                                </td>

                                {{-- Gross Omset --}}
                                <td class="text-end font-monospace fw-semibold text-dark">Rp {{ number_format($stat['gross_sales'] ?? $stat['sales'], 0, ',', '.') }}</td>
                                <td class="text-end font-monospace text-muted">Rp {{ number_format($stat['api_gross'] ?? $stat['gross_sales'], 0, ',', '.') }}</td>
                                <td class="text-end font-monospace {{ abs($dGross) > 100 ? 'fw-bold text-danger' : 'text-muted' }}">
                                    Rp {{ number_format($dGross, 0, ',', '.') }}
                                </td>

                                {{-- Biaya Admin --}}
                                <td class="text-end font-monospace fw-semibold text-danger">Rp {{ number_format($stat['admin_fee'] ?? 0, 0, ',', '.') }}</td>
                                <td class="text-end font-monospace text-muted">Rp {{ number_format($stat['api_admin'] ?? $stat['admin_fee'], 0, ',', '.') }}</td>
                                <td class="text-end font-monospace {{ abs($dAdmin) > 100 ? 'fw-bold text-danger' : 'text-muted' }}">
                                    Rp {{ number_format($dAdmin, 0, ',', '.') }}
                                </td>

                                {{-- Net Omset --}}
                                <td class="text-end font-monospace fw-bold text-success">Rp {{ number_format($stat['sales'], 0, ',', '.') }}</td>
                                <td class="text-end font-monospace text-muted">Rp {{ number_format($stat['api_net'] ?? $stat['sales'], 0, ',', '.') }}</td>
                                <td class="text-end font-monospace {{ abs($dNet) > 100 ? 'fw-bold text-danger' : 'text-muted' }}">
                                    Rp {{ number_format($dNet, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="15" class="text-center text-muted py-5">
                                    <i class="fas fa-store-slash fa-2x mb-3 text-secondary opacity-25"></i>
                                    <p class="mb-0">Tidak ada transaksi dalam rentang tanggal ini.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(count($storeStats) > 0)
                        <tfoot class="table-dark fw-bold align-middle">
                            <tr>
                                <td colspan="3" class="ps-3 text-uppercase">GRAND TOTAL SELURUH TOKO</td>
                                
                                {{-- Orders --}}
                                <td class="text-center">{{ number_format($totErpOrders) }}</td>
                                <td class="text-center">{{ number_format($totApiOrders) }}</td>
                                <td class="text-center">{{ number_format($totErpOrders - $totApiOrders) }}</td>

                                {{-- Gross --}}
                                <td class="text-end font-monospace">Rp {{ number_format($totErpGross, 0, ',', '.') }}</td>
                                <td class="text-end font-monospace">Rp {{ number_format($totApiGross, 0, ',', '.') }}</td>
                                <td class="text-end font-monospace">Rp {{ number_format($totErpGross - $totApiGross, 0, ',', '.') }}</td>

                                {{-- Admin --}}
                                <td class="text-end font-monospace text-danger">Rp {{ number_format($totErpAdmin, 0, ',', '.') }}</td>
                                <td class="text-end font-monospace text-danger">Rp {{ number_format($totApiAdmin, 0, ',', '.') }}</td>
                                <td class="text-end font-monospace text-danger">Rp {{ number_format($totErpAdmin - $totApiAdmin, 0, ',', '.') }}</td>

                                {{-- Net --}}
                                <td class="text-end font-monospace text-success fs-6">Rp {{ number_format($totErpNet, 0, ',', '.') }}</td>
                                <td class="text-end font-monospace text-success fs-6">Rp {{ number_format($totApiNet, 0, ',', '.') }}</td>
                                <td class="text-end font-monospace text-success fs-6">Rp {{ number_format($totErpNet - $totApiNet, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
@endsection
