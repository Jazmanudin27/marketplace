@extends('layouts.app')
@section('title', 'Rekonsiliasi Omset & Marketplace')
@section('page-title', 'Rekonsiliasi Omset & Marketplace Per Toko')

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Filter & Action Bar --}}
    <div class="card border shadow-sm mb-4 bg-white">
        <div class="card-body py-2 px-3">
            <div class="d-flex flex-wrap align-items-end justify-content-between gap-3">
                <form method="GET" action="{{ route('reports.store_sales') }}" class="row g-2 align-items-end flex-fill">
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
                    <div class="col-6 col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm flex-fill fw-semibold">
                            <i class="fas fa-filter me-1"></i> Filter Data
                        </button>
                    </div>
                </form>

                {{-- Tombol Sync Biaya Admin --}}
                <div class="d-flex align-items-end">
                    <form action="{{ route('reports.released_sales.sync_fees') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menyinkronkan ulang seluruh Biaya Admin dari API Marketplace untuk pesanan ERP?');">
                        @csrf
                        <button type="submit" class="btn btn-warning btn-sm fw-bold text-dark px-3">
                            <i class="fas fa-sync-alt me-1"></i> ⚡ Sync Biaya Admin
                        </button>
                    </form>
                </div>
            </div>
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
                <div class="small mt-1 text-white-75">API Live: <span class="fw-bold">{{ number_format($totApiOrders) }} Order</span></div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-dark bg-gradient text-white p-3 rounded-3">
                <div class="small text-white-50 fw-semibold text-uppercase">Total Omset Kotor</div>
                <div class="fs-4 fw-bold mt-1">Rp {{ number_format($totErpGross, 0, ',', '.') }}</div>
                <div class="small mt-1 text-white-75">API Live: <span class="fw-bold">Rp {{ number_format($totApiGross, 0, ',', '.') }}</span></div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-danger bg-gradient text-white p-3 rounded-3">
                <div class="small text-white-50 fw-semibold text-uppercase">Total Biaya Admin</div>
                <div class="fs-4 fw-bold mt-1">Rp {{ number_format($totErpAdmin, 0, ',', '.') }}</div>
                <div class="small mt-1 text-white-75">API Live: <span class="fw-bold">Rp {{ number_format($totApiAdmin, 0, ',', '.') }}</span></div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-success bg-gradient text-white p-3 rounded-3">
                <div class="small text-white-50 fw-semibold text-uppercase">Total Omset Bersih (Net)</div>
                <div class="fs-4 fw-bold mt-1">Rp {{ number_format($totErpNet, 0, ',', '.') }}</div>
                <div class="small mt-1 text-white-75">API Live: <span class="fw-bold">Rp {{ number_format($totApiNet, 0, ',', '.') }}</span></div>
            </div>
        </div>
    </div>

    {{-- Tabel Perbandingan Sisi-demi-Sisi + Detail Transaksi per Toko --}}
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
                            <th rowspan="2" class="ps-3 text-start" style="min-width: 200px;">Nama Toko</th>
                            <th rowspan="2" style="min-width: 110px;">Channel</th>
                            <th rowspan="2" style="min-width: 120px;">Status Match</th>
                            <th colspan="3" class="bg-primary text-white">JUMLAH ORDER</th>
                            <th colspan="3" class="bg-secondary text-white">OMSET KOTOR</th>
                            <th colspan="3" class="bg-danger text-white">BIAYA ADMIN</th>
                            <th colspan="3" class="bg-success text-white">OMSET BERSIH (NET)</th>
                            <th rowspan="2" style="min-width: 120px;">Rincian</th>
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
                        @forelse($storeStats as $index => $stat)
                            @php
                                $dOrders = $stat['diff_orders'] ?? 0;
                                $dGross  = $stat['diff_gross'] ?? 0;
                                $dAdmin  = $stat['diff_admin'] ?? 0;
                                $dNet    = $stat['diff_net'] ?? 0;
                                $isMatch = $stat['is_match'] ?? true;
                                $storeKey = 'store_' . ($stat['id'] ?? $index);
                                $details = $stat['orders_detail'] ?? [];
                            @endphp
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
                                <td class="text-center">
                                    @if($isMatch)
                                        <span class="badge bg-success-subtle text-success border border-success border-opacity-25 px-2 py-1"><i class="fas fa-check-circle me-1"></i>100% MATCH</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger border-opacity-25 px-2 py-1"><i class="fas fa-exclamation-triangle me-1"></i>ADA SELISIH</span>
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

                                <td class="text-center">
                                    <button class="btn btn-xs btn-outline-primary py-1 px-2 fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOrder_{{ $storeKey }}">
                                        <i class="fas fa-list me-1"></i> Detail ({{ count($details) }})
                                    </button>
                                </td>
                            </tr>

                            {{-- Collapsible Rincian Order + Deteksi Per Transaksi --}}
                            <tr>
                                <td colspan="16" class="p-0 border-0">
                                    <div class="collapse bg-light p-3 border-top border-bottom" id="collapseOrder_{{ $storeKey }}">
                                        <div class="d-flex justify-content-between align-items-center mb-2 px-2">
                                            <h6 class="fw-bold text-dark mb-0">
                                                <i class="fas fa-search-dollar text-primary me-2"></i>Rincian Deteksi Per Transaksi: <span class="text-primary">{{ $stat['name'] }}</span>
                                            </h6>
                                            <span class="badge bg-dark px-2 py-1">{{ count($details) }} Transaksi Ditemukan</span>
                                        </div>

                                        @if(!empty($details))
                                            <div class="table-responsive bg-white rounded border shadow-sm">
                                                <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.8rem;">
                                                    <thead class="table-secondary text-uppercase small align-middle">
                                                        <tr>
                                                            <th class="ps-3">#</th>
                                                            <th>No. Order Marketplace</th>
                                                            <th>Tanggal</th>
                                                            <th>Nama Pembeli</th>
                                                            <th class="text-center">Status</th>
                                                            <th class="text-end">Omset Kotor (ERP / API)</th>
                                                            <th class="text-end text-danger">Biaya Admin (ERP / API)</th>
                                                            <th class="text-end text-success">Omset Bersih (ERP / API)</th>
                                                            <th class="text-center pe-3">Deteksi Selisih</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($details as $i => $od)
                                                            @php
                                                                $hasDiff = $od['has_diff'] ?? false;
                                                                $dNetOrd = $od['diff_net'] ?? 0;
                                                                $dAdmOrd = $od['diff_admin'] ?? 0;
                                                            @endphp
                                                            <tr class="{{ $hasDiff ? 'table-danger' : '' }}">
                                                                <td class="ps-3 text-muted">{{ $i + 1 }}</td>
                                                                <td class="fw-bold text-primary font-monospace">{{ $od['order_sn'] }}</td>
                                                                <td class="text-muted">{{ date('d/m/Y H:i', strtotime($od['order_date'])) }}</td>
                                                                <td class="fw-semibold text-dark">{{ $od['buyer_name'] }}</td>
                                                                <td class="text-center">
                                                                    <span class="badge bg-info-subtle text-info border border-info border-opacity-25 px-2 py-1 small">
                                                                        {{ $od['order_status'] }}
                                                                    </span>
                                                                </td>

                                                                {{-- Gross --}}
                                                                <td class="text-end font-monospace text-dark">
                                                                    Rp {{ number_format($od['total_amount'], 0, ',', '.') }}
                                                                    @if(isset($od['api_gross']) && abs($od['total_amount'] - $od['api_gross']) > 100)
                                                                        <br><small class="text-muted">API: Rp {{ number_format($od['api_gross'], 0, ',', '.') }}</small>
                                                                    @endif
                                                                </td>

                                                                {{-- Admin Fee --}}
                                                                <td class="text-end font-monospace text-danger">
                                                                    Rp {{ number_format($od['marketplace_fee'], 0, ',', '.') }}
                                                                    @if(isset($od['api_admin']) && abs($od['marketplace_fee'] - $od['api_admin']) > 100)
                                                                        <br><small class="text-danger fw-bold">API: Rp {{ number_format($od['api_admin'], 0, ',', '.') }}</small>
                                                                    @endif
                                                                </td>

                                                                {{-- Net --}}
                                                                <td class="text-end font-monospace fw-bold text-success">
                                                                    Rp {{ number_format($od['net_amount'], 0, ',', '.') }}
                                                                    @if(isset($od['api_net']) && abs($od['net_amount'] - $od['api_net']) > 100)
                                                                        <br><small class="text-muted">API: Rp {{ number_format($od['api_net'], 0, ',', '.') }}</small>
                                                                    @endif
                                                                </td>

                                                                {{-- Match status per order --}}
                                                                <td class="text-center pe-3">
                                                                    @if($hasDiff)
                                                                        <span class="badge bg-danger text-white px-2 py-1 fw-bold" title="Biaya admin atau net amount berbeda">
                                                                            ⚠️ SELISIH Rp {{ number_format(abs($dNetOrd ?: $dAdmOrd), 0, ',', '.') }}
                                                                        </span>
                                                                    @else
                                                                        <span class="badge bg-success-subtle text-success border border-success border-opacity-25 px-2 py-1">
                                                                            <i class="fas fa-check me-1"></i>MATCH
                                                                        </span>
                                                                    @endif
                                                                </td>
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
                                <td colspan="16" class="text-center text-muted py-5">
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
                                <td></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
@endsection
