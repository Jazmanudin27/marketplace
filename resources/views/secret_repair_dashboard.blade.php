<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>🛠️ Admin Maintenance & Data Repair Dashboard</title>

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts Inter & JetBrains Mono -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        body {
            background-color: #f8fafc;
            color: #0f172a;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            padding-bottom: 4rem;
        }

        .navbar-white {
            background-color: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .card-stat {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.85rem;
            padding: 1.25rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            transition: all 0.2s ease-in-out;
        }

        .card-stat:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(0,0,0,0.08);
            border-color: #cbd5e1;
        }

        .card-action {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.85rem;
            padding: 1.25rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.2s ease-in-out;
        }

        .card-action:hover {
            box-shadow: 0 8px 20px -4px rgba(0,0,0,0.08);
            border-color: #cbd5e1;
        }

        .terminal-container {
            background: #0f172a;
            border-radius: 0.85rem;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.15);
            border: 1px solid #334155;
        }

        .terminal-header {
            background: #1e293b;
            padding: 0.75rem 1.25rem;
            border-bottom: 1px solid #334155;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .terminal-body {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.82rem;
            color: #38bdf8;
            padding: 1.25rem;
            min-height: 280px;
            max-height: 480px;
            overflow-y: auto;
            white-space: pre-wrap;
            line-height: 1.6;
        }

        .log-timestamp { color: #94a3b8; }
        .log-success { color: #4ade80; }
        .log-warning { color: #facc15; }
        .log-error { color: #f87171; }
        .log-info { color: #38bdf8; }

        .btn-custom {
            font-weight: 600;
            padding: 0.65rem 1.2rem;
            border-radius: 0.6rem;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
            width: 100%;
        }

        .btn-warning-custom {
            background-color: #f59e0b;
            color: #000000 !important;
            font-weight: 700;
            border: none;
        }

        .btn-warning-custom:hover {
            background-color: #d97706;
            color: #000000 !important;
        }

        .btn-dark-custom {
            background-color: #0f172a;
            color: #ffffff !important;
            border: none;
        }

        .btn-dark-custom:hover {
            background-color: #1e293b;
            color: #ffffff !important;
        }
    </style>
</head>
<body>

    <!-- Header Navbar -->
    <nav class="navbar navbar-expand-lg navbar-white sticky-top py-2 mb-4">
        <div class="container-fluid px-4">
            <a class="navbar-brand d-flex align-items-center gap-2 fw-bold text-dark fs-5" href="#">
                <span class="badge bg-primary text-white rounded-pill px-2.5 py-1" style="font-size: 0.75rem;">ADMIN TOOLS</span>
                <i class="fas fa-wrench text-primary me-1"></i> Data Maintenance & Repair Panel
            </a>
            <div class="d-flex align-items-center gap-3">
                <span class="text-dark small">
                    <i class="fas fa-user-circle text-primary me-1"></i> User: <strong>{{ auth()->user()->name ?? 'Admin' }}</strong>
                </span>
                <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary btn-sm px-3 rounded-2 fw-semibold">
                    <i class="fas fa-arrow-left me-1"></i> Kembali ke Order ERP
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4">

        <!-- 📊 SECTION 1: Status Pesanan Breakdown Cards -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold text-dark mb-0"><i class="fas fa-chart-pie me-2 text-primary"></i>Ringkasan Perbandingan Status Pesanan ERP</h5>
            <span class="badge bg-white text-dark border px-3 py-2 rounded-2 fw-semibold">Total {{ number_format($ordersCount, 0, ',', '.') }} Orders</span>
        </div>

        <div class="row g-3 mb-4">
            <!-- Completed -->
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card-stat border-start border-4 border-success">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-uppercase text-secondary fw-bold" style="font-size: 0.68rem;">Selesai (Completed)</span>
                        <i class="fas fa-check-circle text-success"></i>
                    </div>
                    <h4 class="fw-bold text-success mb-1">{{ number_format($completedCount, 0, ',', '.') }}</h4>
                    <div class="progress mt-1" style="height: 5px;">
                        <div class="progress-bar bg-success" style="width: {{ $ordersCount > 0 ? round(($completedCount/$ordersCount)*100) : 0 }}%"></div>
                    </div>
                    <small class="text-secondary mt-1 d-block" style="font-size: 0.7rem;">{{ $ordersCount > 0 ? round(($completedCount/$ordersCount)*100, 1) : 0 }}% dari total pesanan</small>
                </div>
            </div>

            <!-- Ready To Ship -->
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card-stat border-start border-4 border-info">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-uppercase text-secondary fw-bold" style="font-size: 0.68rem;">Proses (Ready Ship)</span>
                        <i class="fas fa-box-open text-info"></i>
                    </div>
                    <h4 class="fw-bold text-info mb-1">{{ number_format($readyToShipCount, 0, ',', '.') }}</h4>
                    <div class="progress mt-1" style="height: 5px;">
                        <div class="progress-bar bg-info" style="width: {{ $ordersCount > 0 ? round(($readyToShipCount/$ordersCount)*100) : 0 }}%"></div>
                    </div>
                    <small class="text-secondary mt-1 d-block" style="font-size: 0.7rem;">{{ $ordersCount > 0 ? round(($readyToShipCount/$ordersCount)*100, 1) : 0 }}% perlu dikirim</small>
                </div>
            </div>

            <!-- Shipped -->
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card-stat border-start border-4 border-primary">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-uppercase text-secondary fw-bold" style="font-size: 0.68rem;">Dikirim (Shipped)</span>
                        <i class="fas fa-truck text-primary"></i>
                    </div>
                    <h4 class="fw-bold text-primary mb-1">{{ number_format($shippedCount, 0, ',', '.') }}</h4>
                    <div class="progress mt-1" style="height: 5px;">
                        <div class="progress-bar bg-primary" style="width: {{ $ordersCount > 0 ? round(($shippedCount/$ordersCount)*100) : 0 }}%"></div>
                    </div>
                    <small class="text-secondary mt-1 d-block" style="font-size: 0.7rem;">{{ $ordersCount > 0 ? round(($shippedCount/$ordersCount)*100, 1) : 0 }}% sedang jalan</small>
                </div>
            </div>

            <!-- Cancelled -->
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card-stat border-start border-4 border-danger">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-uppercase text-secondary fw-bold" style="font-size: 0.68rem;">Dibatalkan (Cancel)</span>
                        <i class="fas fa-times-circle text-danger"></i>
                    </div>
                    <h4 class="fw-bold text-danger mb-1">{{ number_format($cancelledCount, 0, ',', '.') }}</h4>
                    <div class="progress mt-1" style="height: 5px;">
                        <div class="progress-bar bg-danger" style="width: {{ $ordersCount > 0 ? round(($cancelledCount/$ordersCount)*100) : 0 }}%"></div>
                    </div>
                    <small class="text-secondary mt-1 d-block" style="font-size: 0.7rem;">{{ $ordersCount > 0 ? round(($cancelledCount/$ordersCount)*100, 1) : 0 }}% batal/cancel</small>
                </div>
            </div>

            <!-- Returned -->
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card-stat border-start border-4 border-warning">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-uppercase text-secondary fw-bold" style="font-size: 0.68rem;">Retur (Refunded)</span>
                        <i class="fas fa-undo-alt text-warning"></i>
                    </div>
                    <h4 class="fw-bold text-warning mb-1">{{ number_format($returnedCount, 0, ',', '.') }}</h4>
                    <div class="progress mt-1" style="height: 5px;">
                        <div class="progress-bar bg-warning" style="width: {{ $ordersCount > 0 ? round(($returnedCount/$ordersCount)*100) : 0 }}%"></div>
                    </div>
                    <small class="text-secondary mt-1 d-block" style="font-size: 0.7rem;">{{ $ordersCount > 0 ? round(($returnedCount/$ordersCount)*100, 1) : 0 }}% pengembalian</small>
                </div>
            </div>

            <!-- Missing Items Alert -->
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card-stat border-start border-4 border-dark bg-warning-subtle">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-uppercase text-dark fw-bold" style="font-size: 0.68rem;">Item Kosong</span>
                        <i class="fas fa-exclamation-triangle text-dark"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-1" id="statMissingItems">{{ number_format($missingItemsCount, 0, ',', '.') }}</h4>
                    <small class="text-dark mt-1 d-block fw-bold" style="font-size: 0.7rem;">
                        {{ $missingItemsCount > 0 ? '⚠️ Butuh Perbaikan' : '✅ 100% Lengkap' }}
                    </small>
                </div>
            </div>
        </div>

        <!-- 🌐 SECTION 2: Tabel Perbandingan Data ERP per Channel + Filter Tanggal -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 px-4 border-bottom">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <h6 class="fw-bold text-dark mb-0"><i class="fas fa-exchange-alt me-2 text-primary"></i>Perbandingan Data ERP per Channel Marketplace</h6>
                        <small class="text-secondary">Jml Order, Omset, Biaya Admin, Dana Cair — eksklusif order batal</small>
                    </div>
                    <!-- Filter Tanggal -->
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <div class="d-flex align-items-center gap-1">
                            <label class="text-secondary fw-semibold" style="font-size:0.78rem; white-space:nowrap;">Dari:</label>
                            <input type="date" id="filterDateFrom" class="form-control form-control-sm rounded-2" style="font-size:0.82rem; width:145px;" value="{{ date('Y-m-01') }}">
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <label class="text-secondary fw-semibold" style="font-size:0.78rem; white-space:nowrap;">Sampai:</label>
                            <input type="date" id="filterDateTo" class="form-control form-control-sm rounded-2" style="font-size:0.82rem; width:145px;" value="{{ date('Y-m-d') }}">
                        </div>
                        <button id="btnLoadCompare" class="btn btn-primary btn-sm px-3 fw-semibold rounded-2" style="font-size:0.82rem;" onclick="loadCompareStats()">
                            <i class="fas fa-search me-1"></i> Tampilkan
                        </button>
                        <button class="btn btn-outline-secondary btn-sm px-2 rounded-2" style="font-size:0.82rem;" onclick="resetCompareFilter()" title="Reset ke semua waktu">
                            <i class="fas fa-undo"></i>
                        </button>
                    </div>
                </div>
                <div id="compareFilterLabel" class="mt-2">
                    <span class="badge bg-primary-subtle text-primary-emphasis border border-primary-subtle px-3 py-1" style="font-size:0.75rem;">
                        <i class="fas fa-calendar me-1"></i> <span id="compareDateRangeText">Bulan ini</span>
                    </span>
                </div>
            </div>

            <!-- Tabel Ringkasan Channel -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.82rem;" id="compareTable">
                    <thead style="font-size: 0.68rem;">
                        <!-- Group Header -->
                        <tr class="text-uppercase fw-bold" style="background:#f1f5f9; border-bottom:1px solid #e2e8f0;">
                            <th class="ps-4 py-2" rowspan="2" style="vertical-align:middle; min-width:170px;">CHANNEL</th>
                            <th class="py-2 text-end" rowspan="2" style="vertical-align:middle; min-width:100px;">JML ORDER ERP</th>
                            <th class="py-2 text-center border-start" colspan="2" style="background:#eff6ff; color:#1d4ed8;">OMSET</th>
                            <th class="py-2 text-center border-start" colspan="2" style="background:#fff7ed; color:#c2410c;">BIAYA ADMIN</th>
                            <th class="py-2 text-center border-start" colspan="2" style="background:#f0fdf4; color:#15803d;">DANA CAIR</th>
                            <th class="py-2 text-center border-start" colspan="3" style="background:#fdf4ff; color:#7e22ce;">SELISIH ERP-API</th>
                        </tr>
                        <tr class="text-uppercase fw-bold" style="background:#f8fafc; font-size:0.66rem;">
                            <th class="py-2 text-end border-start" style="color:#1d4ed8;">ERP</th>
                            <th class="py-2 text-end" style="color:#1d4ed8;">API</th>
                            <th class="py-2 text-end border-start" style="color:#c2410c;">ERP</th>
                            <th class="py-2 text-end" style="color:#c2410c;">API</th>
                            <th class="py-2 text-end border-start" style="color:#15803d;">ERP</th>
                            <th class="py-2 text-end" style="color:#15803d;">API</th>
                            <th class="py-2 text-end border-start" style="color:#7e22ce;">OMSET</th>
                            <th class="py-2 text-end" style="color:#7e22ce;">BIAYA ADMIN</th>
                            <th class="py-2 text-end pe-4" style="color:#7e22ce;">DANA CAIR</th>
                        </tr>
                    </thead>
                    <tbody id="compareTableBody">
                        <tr>
                            <td colspan="11" class="text-center py-5 text-secondary">
                                <i class="fas fa-spinner fa-spin me-2"></i> Memuat data...
                            </td>
                        </tr>
                    </tbody>
                    <tfoot id="compareTableFoot" class="fw-bold" style="background:#f8fafc;"></tfoot>
                </table>
            </div>

            <!-- Per-Store Detail (collapsible) -->
            <div class="px-4 py-2 border-top bg-white">
                <button class="btn btn-sm btn-outline-secondary rounded-2 fw-semibold" style="font-size:0.78rem;" type="button" data-bs-toggle="collapse" data-bs-target="#storeDetailCollapse">
                    <i class="fas fa-store me-1"></i> Lihat Detail per Toko
                </button>
            </div>
            <div class="collapse" id="storeDetailCollapse">
                <div class="table-responsive border-top">
                    <table class="table table-sm table-hover align-middle mb-0" style="font-size:0.81rem;">
                        <thead class="text-uppercase fw-bold" style="font-size:0.66rem; background:#f8fafc;">
                            <tr>
                                <th class="ps-4 py-2">NAMA TOKO</th>
                                <th class="py-2">CH</th>
                                <th class="py-2 text-end">JML ORDER</th>
                                <th class="py-2 text-end text-danger">BATAL</th>
                                <th class="py-2 text-end" style="color:#1d4ed8;">OMSET ERP</th>
                                <th class="py-2 text-end" style="color:#1d4ed8;">OMSET API</th>
                                <th class="py-2 text-end" style="color:#c2410c;">ADMIN ERP</th>
                                <th class="py-2 text-end" style="color:#c2410c;">ADMIN API</th>
                                <th class="py-2 text-end" style="color:#15803d;">CAIR ERP</th>
                                <th class="py-2 text-end" style="color:#15803d;">CAIR API</th>
                                <th class="py-2 text-end" style="color:#7e22ce;">SELISIH ADMIN</th>
                                <th class="py-2 text-end pe-4" style="color:#7e22ce;">SELISIH CAIR</th>
                            </tr>
                        </thead>
                        <tbody id="storeDetailBody">
                            <tr><td colspan="12" class="text-center py-3 text-secondary"><i class="fas fa-spinner fa-spin me-1"></i> Memuat...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>


        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 px-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-dark mb-0"><i class="fas fa-exchange-alt me-2 text-primary"></i>Tabel Perbandingan Integrasi ERP vs Marketplace API</h6>
                <span class="badge bg-secondary-subtle text-secondary-emphasis border px-2.5 py-1">TikTok Shop & Shopee Channels</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                    <thead class="table-light text-uppercase fw-bold text-dark" style="font-size: 0.72rem;">
                        <tr>
                            <th class="ps-3 py-2.5">MARKETPLACE CHANNEL</th>
                            <th class="py-2.5 text-center">TOTAL ERP ORDERS</th>
                            <th class="py-2.5 text-center">SELESAI (COMPLETED)</th>
                            <th class="py-2.5 text-center">BATAL (CANCELLED)</th>
                            <th class="py-2.5 text-center">PERLU SYNC ESCROW</th>
                            <th class="py-2.5 text-center">ITEM KOSONG</th>
                            <th class="pe-3 py-2.5 text-end">AKSI PERBAIKAN</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- TikTok -->
                        <tr>
                            <td class="ps-3 fw-bold text-dark">
                                <i class="fab fa-tiktok me-2 text-dark fs-6"></i>TikTok Shop & Tokopedia
                            </td>
                            <td class="text-center font-monospace fw-bold text-dark">{{ number_format($tiktokTotalOrders, 0, ',', '.') }}</td>
                            <td class="text-center font-monospace text-success fw-bold">{{ number_format($tiktokCompleted, 0, ',', '.') }}</td>
                            <td class="text-center font-monospace text-danger fw-bold">{{ number_format($tiktokCancelled, 0, ',', '.') }}</td>
                            <td class="text-center">
                                @if ($tiktokMissingFees > 0)
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle font-monospace px-2.5 py-1">{{ number_format($tiktokMissingFees, 0, ',', '.') }} orders</span>
                                @else
                                    <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle px-2 py-1"><i class="fas fa-check me-1"></i>Matched</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($tiktokMissingItems > 0)
                                    <span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle font-monospace px-2.5 py-1">{{ number_format($tiktokMissingItems, 0, ',', '.') }} orders</span>
                                @else
                                    <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle px-2 py-1"><i class="fas fa-check me-1"></i>Lengkap</span>
                                @endif
                            </td>
                            <td class="pe-3 text-end">
                                <button type="button" class="btn btn-primary btn-sm px-2.5 py-1 rounded-2 fw-semibold" onclick="triggerRepair('sync_tiktok_escrow', this)" style="font-size: 0.78rem;">
                                    <i class="fas fa-sync me-1"></i> Sync Escrow TikTok
                                </button>
                            </td>
                        </tr>

                        <!-- Shopee -->
                        <tr>
                            <td class="ps-3 fw-bold text-dark">
                                <i class="fas fa-shopping-bag me-2 text-danger fs-6"></i>Shopee Seller Center
                            </td>
                            <td class="text-center font-monospace fw-bold text-dark">{{ number_format($shopeeTotalOrders, 0, ',', '.') }}</td>
                            <td class="text-center font-monospace text-success fw-bold">{{ number_format($shopeeCompleted, 0, ',', '.') }}</td>
                            <td class="text-center font-monospace text-danger fw-bold">{{ number_format($shopeeCancelled, 0, ',', '.') }}</td>
                            <td class="text-center">
                                @if ($shopeeMissingFees > 0)
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle font-monospace px-2.5 py-1">{{ number_format($shopeeMissingFees, 0, ',', '.') }} orders</span>
                                @else
                                    <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle px-2 py-1"><i class="fas fa-check me-1"></i>Matched</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($shopeeMissingItems > 0)
                                    <span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle font-monospace px-2.5 py-1">{{ number_format($shopeeMissingItems, 0, ',', '.') }} orders</span>
                                @else
                                    <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle px-2 py-1"><i class="fas fa-check me-1"></i>Lengkap</span>
                                @endif
                            </td>
                            <td class="pe-3 text-end">
                                <button type="button" class="btn btn-danger btn-sm px-2.5 py-1 rounded-2 fw-semibold" onclick="triggerRepair('sync_shopee_escrow', this)" style="font-size: 0.78rem;">
                                    <i class="fas fa-sync me-1"></i> Sync Escrow Shopee
                                </button>
                            </td>
                        </tr>

                        <!-- Manual & Offline -->
                        <tr>
                            <td class="ps-3 fw-bold text-dark">
                                <i class="fas fa-store me-2 text-secondary fs-6"></i>Manual & Penjualan Kasir
                            </td>
                            <td class="text-center font-monospace fw-bold text-dark">{{ number_format($manualTotalOrders, 0, ',', '.') }}</td>
                            <td class="text-center font-monospace text-success fw-bold">-</td>
                            <td class="text-center font-monospace text-secondary">-</td>
                            <td class="text-center"><span class="badge bg-secondary-subtle text-secondary-emphasis border px-2 py-1">Manual ERP</span></td>
                            <td class="text-center"><span class="badge bg-success-subtle text-success-emphasis border border-success-subtle px-2 py-1"><i class="fas fa-check me-1"></i>Lengkap</span></td>
                            <td class="pe-3 text-end">
                                <button type="button" class="btn btn-outline-secondary btn-sm px-2.5 py-1 rounded-2 fw-semibold" onclick="triggerRepair('recalculate_reconciliation', this)" style="font-size: 0.78rem;">
                                    <i class="fas fa-calculator me-1"></i> Recalculate
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 🛠️ SECTION 3: Tombol Eksekusi Perbaikan Data -->
        <h5 class="fw-bold text-dark mb-3"><i class="fas fa-bolt text-warning me-2"></i>Tombol Perbaikan Data & Command Repair Panel</h5>

        <div class="row g-3 mb-4">

            <!-- Action 0: Clean Duplicate Orders -->
            <div class="col-lg-4 col-md-6">
                <div class="card-action border-top border-4 border-danger">
                    <div>
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-danger text-white fw-bold px-2 py-1" style="font-size: 0.72rem;"><i class="fas fa-copy me-1"></i>DUPLICATE CLEANER</span>
                                <h6 class="fw-bold text-dark mb-0">Hapus Pesanan Double</h6>
                            </div>
                            @if(isset($duplicateOrdersCount) && $duplicateOrdersCount > 0)
                                <span class="badge bg-danger rounded-pill px-2 py-1" title="{{ $duplicateOrdersCount }} grup pesanan duplikat">{{ $duplicateOrdersCount }} Ganda</span>
                            @else
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size:0.68rem;"><i class="fas fa-check me-1"></i>Bersih</span>
                            @endif
                        </div>
                        <p class="text-secondary small mb-3" style="font-size: 0.8rem; line-height: 1.45;">
                            Mendeteksi &amp; menghapus pesanan ganda (duplikat) di ERP berdasarkan No. Pesanan Marketplace.
                        </p>
                    </div>
                    <button type="button" class="btn-custom btn-danger fw-semibold" onclick="triggerRepair('clean_duplicate_orders', this)">
                        <i class="fas fa-trash-alt me-1"></i> Hapus Pesanan Double
                    </button>
                </div>
            </div>

            <!-- Action 0.5: Sync Product Stock -->
            <div class="col-lg-4 col-md-6">
                <div class="card-action border-top border-4 border-info">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle fw-bold px-2 py-1" style="font-size: 0.72rem;"><i class="fas fa-boxes me-1"></i>STOCK SYNC</span>
                            <h6 class="fw-bold text-dark mb-0">Sinkronisasi Stok Produk</h6>
                        </div>
                        <p class="text-secondary small mb-3" style="font-size: 0.8rem; line-height: 1.45;">
                            Mendorong (<code class="text-primary fw-bold">Push</code>) stok Master Product ERP secara instan ke seluruh toko Marketplace (Shopee, TikTok, Lazada).
                        </p>
                    </div>
                    <button type="button" class="btn-custom btn-info text-white fw-semibold" onclick="triggerRepair('sync_product_stock', this)">
                        <i class="fas fa-sync-alt me-1"></i> Sync Stok Produk Massal
                    </button>
                </div>
            </div>

            <!-- Action 0.6: Recalculate Bundle Stock -->
            <div class="col-lg-4 col-md-6">
                <div class="card-action border-top border-4 border-success">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-success text-white fw-bold px-2 py-1" style="font-size: 0.72rem;"><i class="fas fa-cubes me-1"></i>BUNDLE STOCKS</span>
                            <h6 class="fw-bold text-dark mb-0">Sync Stok Produk Set / Bundle</h6>
                        </div>
                        <p class="text-secondary small mb-3" style="font-size: 0.8rem; line-height: 1.45;">
                            Menghitung ulang &amp; memperbarui stok seluruh produk Paket/Setelan berdasarkan ketersediaan stok komponen single-nya di DB.
                        </p>
                    </div>
                    <button type="button" class="btn-custom btn-success fw-semibold" onclick="triggerRepair('recalculate_bundle_stocks', this)">
                        <i class="fas fa-sync me-1"></i> Hitung Ulang Stok Bundle
                    </button>
                </div>
            </div>

            <!-- Action 1: Fix Missing Items -->
            <div class="col-lg-4 col-md-6">
                <div class="card-action border-top border-4 border-warning">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fw-bold px-2 py-1" style="font-size: 0.72rem;"><i class="fas fa-wrench me-1"></i>REPAIR ITEM</span>
                            <h6 class="fw-bold text-dark mb-0">Perbaiki Item Order Kosong</h6>
                        </div>
                        <p class="text-secondary small mb-3" style="font-size: 0.8rem; line-height: 1.45;">
                            Eksekusi <code class="text-primary fw-bold">php fix_all_missing_items.php</code> untuk mengisi produk yang belum masuk dari API TikTok/Shopee.
                        </p>
                    </div>
                    <button type="button" class="btn-custom btn-warning-custom" onclick="triggerRepair('fix_missing_items', this)">
                        <i class="fas fa-tools me-1"></i> Jalankan Perbaikan Item Kosong
                    </button>
                </div>
            </div>

            <!-- Action 2: Sync TikTok Escrow -->
            <div class="col-lg-4 col-md-6">
                <div class="card-action border-top border-4 border-dark">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-dark text-white px-2 py-1" style="font-size: 0.72rem;"><i class="fab fa-tiktok me-1"></i>TIKTOK ESCROW</span>
                            <h6 class="fw-bold text-dark mb-0">Sync Escrow & Potongan TikTok</h6>
                        </div>
                        <p class="text-secondary small mb-3" style="font-size: 0.8rem; line-height: 1.45;">
                            Eksekusi <code class="text-primary fw-bold">php artisan tiktok:sync-escrow</code> untuk menarik rincian 5 potongan biaya & dana cair resmi.
                        </p>
                    </div>
                    <button type="button" class="btn-custom btn-dark-custom" onclick="triggerRepair('sync_tiktok_escrow', this)">
                        <i class="fas fa-sync me-1"></i> Sync Escrow TikTok
                    </button>
                </div>
            </div>

            <!-- Action 3: Sync Shopee Escrow -->
            <div class="col-lg-4 col-md-6">
                <div class="card-action border-top border-4 border-danger">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle px-2 py-1" style="font-size: 0.72rem;"><i class="fas fa-shopping-bag me-1"></i>SHOPEE ESCROW</span>
                            <h6 class="fw-bold text-dark mb-0">Sync Escrow & Income Shopee</h6>
                        </div>
                        <p class="text-secondary small mb-3" style="font-size: 0.8rem; line-height: 1.45;">
                            Eksekusi <code class="text-primary fw-bold">php artisan shopee:sync-escrow</code> untuk melengkapi saldo cair Shopee Seller Center.
                        </p>
                    </div>
                    <button type="button" class="btn-custom btn-danger" onclick="triggerRepair('sync_shopee_escrow', this)">
                        <i class="fas fa-sync me-1"></i> Sync Escrow Shopee
                    </button>
                </div>
            </div>

            <!-- Action 3.5: Resync Shopee Status -->
            <div class="col-lg-4 col-md-6">
                <div class="card-action border-top border-4 border-danger">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-danger text-white px-2 py-1" style="font-size: 0.72rem;"><i class="fas fa-sync me-1"></i>FIX SHOPEE STATUS</span>
                            <h6 class="fw-bold text-dark mb-0">Koreksi Status Pesanan Shopee</h6>
                        </div>
                        <p class="text-secondary small mb-3" style="font-size: 0.8rem; line-height: 1.45;">
                            Menarik status asli dari API Shopee untuk mengembalikan pesanan yang tertimpa status ke status sebenarnya (misal: Perlu Dikirim).
                        </p>
                    </div>
                    <button type="button" class="btn-custom btn-outline-danger fw-semibold" onclick="triggerRepair('resync_shopee_status', this)">
                        <i class="fas fa-sync me-1"></i> Koreksi Status Shopee API
                    </button>
                </div>
            </div>

            <!-- Action 4: Pull TikTok Orders -->
            <div class="col-lg-3 col-md-6">
                <div class="card-action border-top border-4 border-primary">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-primary-subtle text-primary-emphasis border border-primary-subtle px-2 py-1" style="font-size: 0.72rem;"><i class="fas fa-cloud-download-alt me-1"></i>PULL TIKTOK</span>
                            <h6 class="fw-bold text-dark mb-0">Tarik Pesanan TikTok (7 Hari)</h6>
                        </div>
                        <p class="text-secondary small mb-3" style="font-size: 0.8rem;">
                            Menarik dan memperbarui status pesanan terbaru dari TikTok Shop 7 hari terakhir.
                        </p>
                    </div>
                    <button type="button" class="btn-custom btn-primary" onclick="triggerRepair('pull_tiktok_orders', this)">
                        <i class="fas fa-download me-1"></i> Tarik Pesanan TikTok
                    </button>
                </div>
            </div>

            <!-- Action 5: Pull Shopee Orders -->
            <div class="col-lg-3 col-md-6">
                <div class="card-action border-top border-4 border-danger">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle px-2 py-1" style="font-size: 0.72rem;"><i class="fas fa-cloud-download-alt me-1"></i>PULL SHOPEE</span>
                            <h6 class="fw-bold text-dark mb-0">Tarik Pesanan Shopee (7 Hari)</h6>
                        </div>
                        <p class="text-secondary small mb-3" style="font-size: 0.8rem;">
                            Menarik dan memperbarui status pesanan terbaru dari Shopee Seller Center 7 hari terakhir.
                        </p>
                    </div>
                    <button type="button" class="btn-custom btn-outline-danger fw-semibold" onclick="triggerRepair('pull_shopee_orders', this)">
                        <i class="fas fa-download me-1"></i> Tarik Pesanan Shopee
                    </button>
                </div>
            </div>

            <!-- Action 6: Recalculate Reconciliation -->
            <div class="col-lg-3 col-md-6">
                <div class="card-action border-top border-4 border-success">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle px-2 py-1" style="font-size: 0.72rem;"><i class="fas fa-calculator me-1"></i>RECONCILIATION</span>
                            <h6 class="fw-bold text-dark mb-0">Hitung Ulang Status Rekonsiliasi</h6>
                        </div>
                        <p class="text-secondary small mb-3" style="font-size: 0.8rem;">
                            Mengkalkulasi ulang status rekonsiliasi MATCHED vs UNRECONCILED.
                        </p>
                    </div>
                    <button type="button" class="btn-custom btn-success" onclick="triggerRepair('recalculate_reconciliation', this)">
                        <i class="fas fa-calculator me-1"></i> Recalculate Status
                    </button>
                </div>
            </div>

            <!-- Action 7: Clear Cache -->
            <div class="col-lg-3 col-md-6">
                <div class="card-action border-top border-4 border-secondary">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-secondary-subtle text-secondary-emphasis border px-2 py-1" style="font-size: 0.72rem;"><i class="fas fa-broom me-1"></i>CLEAR CACHE</span>
                            <h6 class="fw-bold text-dark mb-0">Bersihkan Cache & Memory</h6>
                        </div>
                        <p class="text-secondary small mb-3" style="font-size: 0.8rem;">
                            Membersihkan Web Cache, View Cache, dan Memory Rekonsiliasi agar perubahan langsung tercermin.
                        </p>
                    </div>
                    <button type="button" class="btn-custom btn-outline-secondary fw-semibold" onclick="triggerRepair('clear_system_cache', this)">
                        <i class="fas fa-trash-alt me-1"></i> Clear Web Cache
                    </button>
                </div>
            </div>

        </div>

        <!-- 🚀 SECTION 4: Server Deployment Tools -->
        <h5 class="fw-bold text-dark mb-3"><i class="fas fa-server text-info me-2"></i>Server Deployment Tools</h5>

        <div class="row g-3 mb-4">

            <!-- Git Pull -->
            <div class="col-lg-4 col-md-6">
                <div class="card-action border-top border-4" style="border-color: #0ea5e9 !important;">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge text-white px-2 py-1" style="font-size: 0.72rem; background:#0ea5e9;"><i class="fab fa-git-alt me-1"></i>GIT PULL</span>
                            <h6 class="fw-bold text-dark mb-0">Pull Update dari Git Repository</h6>
                        </div>
                        <p class="text-secondary small mb-3" style="font-size: 0.8rem; line-height: 1.45;">
                            Eksekusi <code class="text-primary fw-bold">git pull</code> untuk mengambil kode terbaru dari repository ke server production.
                        </p>
                    </div>
                    <button type="button" class="btn-custom text-white fw-bold border-0" style="background:#0ea5e9;" onmouseover="this.style.background='#0284c7'" onmouseout="this.style.background='#0ea5e9'" onclick="triggerRepair('git_pull', this)">
                        <i class="fab fa-git-alt me-1"></i> Git Pull Latest Code
                    </button>
                </div>
            </div>

            <!-- Artisan Optimize -->
            <div class="col-lg-4 col-md-6">
                <div class="card-action border-top border-4" style="border-color: #8b5cf6 !important;">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge text-white px-2 py-1" style="font-size: 0.72rem; background:#8b5cf6;"><i class="fas fa-bolt me-1"></i>OPTIMIZE</span>
                            <h6 class="fw-bold text-dark mb-0">Artisan Optimize (Rebuild Cache)</h6>
                        </div>
                        <p class="text-secondary small mb-3" style="font-size: 0.8rem; line-height: 1.45;">
                            Eksekusi <code class="text-primary fw-bold">php artisan optimize</code> — rebuild config, route, dan view cache agar performa server optimal.
                        </p>
                    </div>
                    <button type="button" class="btn-custom text-white fw-bold border-0" style="background:#8b5cf6;" onmouseover="this.style.background='#7c3aed'" onmouseout="this.style.background='#8b5cf6'" onclick="triggerRepair('artisan_optimize', this)">
                        <i class="fas fa-bolt me-1"></i> Jalankan Artisan Optimize
                    </button>
                </div>
            </div>

            <!-- Artisan Migrate -->
            <div class="col-lg-4 col-md-6">
                <div class="card-action border-top border-4" style="border-color: #10b981 !important;">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge text-white px-2 py-1" style="font-size: 0.72rem; background:#10b981;"><i class="fas fa-database me-1"></i>MIGRATE</span>
                            <h6 class="fw-bold text-dark mb-0">Artisan Migrate (Update DB Schema)</h6>
                        </div>
                        <p class="text-secondary small mb-3" style="font-size: 0.8rem; line-height: 1.45;">
                            Eksekusi <code class="text-primary fw-bold">php artisan migrate --force</code> untuk menerapkan migration database terbaru ke production.
                        </p>
                    </div>
                    <button type="button" class="btn-custom text-white fw-bold border-0" style="background:#10b981;" onmouseover="this.style.background='#059669'" onmouseout="this.style.background='#10b981'" onclick="confirmMigrate(this)">
                        <i class="fas fa-database me-1"></i> Jalankan Artisan Migrate
                    </button>
                </div>
            </div>

        </div>

        <!-- 🖥️ SECTION 5: Terminal Log Output Window -->
        <div class="terminal-container mb-4">
            <div class="terminal-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex gap-1.5">
                        <div style="width:11px; height:11px; border-radius:50%; background:#ef4444;"></div>
                        <div style="width:11px; height:11px; border-radius:50%; background:#f59e0b;"></div>
                        <div style="width:11px; height:11px; border-radius:50%; background:#10b981;"></div>
                    </div>
                    <span class="text-white font-monospace small"><i class="fas fa-terminal me-1.5 text-info"></i>Console Output Output Logs</span>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-dark text-white border-secondary py-1 px-2.5" style="font-size: 0.75rem;" onclick="copyConsoleLogs()">
                        <i class="fas fa-copy me-1"></i> Salin Log
                    </button>
                    <button type="button" class="btn btn-sm btn-dark text-danger border-secondary py-1 px-2.5" style="font-size: 0.75rem;" onclick="clearConsoleLogs()">
                        <i class="fas fa-trash me-1"></i> Bersihkan Log
                    </button>
                </div>
            </div>
            <div class="terminal-body" id="consoleOutput">
<span class="log-timestamp">[{{ date('H:i:s') }}]</span> <span class="log-info">Ready! Silakan klik salah satu tombol di atas untuk menjalankan perbaikan data / sync API.</span>
            </div>
        </div>

    </div>

    <!-- Script AJAX Handler -->
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function appendLog(text, type = 'info') {
            const consoleOutput = document.getElementById('consoleOutput');
            const now = new Date().toLocaleTimeString('id-ID');
            let colorClass = 'log-info';
            if (type === 'success') colorClass = 'log-success';
            if (type === 'error') colorClass = 'log-error';
            if (type === 'warning') colorClass = 'log-warning';

            const logLine = document.createElement('div');
            logLine.innerHTML = `<span class="log-timestamp">[${now}]</span> <span class="${colorClass}">${escapeHtml(text)}</span>`;
            consoleOutput.appendChild(logLine);
            consoleOutput.scrollTop = consoleOutput.scrollHeight;
        }

        function escapeHtml(text) {
            if (text === null || text === undefined) return '';
            if (typeof text !== 'string') text = String(text);
            return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
        }

        function clearConsoleLogs() {
            document.getElementById('consoleOutput').innerHTML = '<span class="log-timestamp">[' + new Date().toLocaleTimeString('id-ID') + ']</span> <span class="log-info">Log dibersihkan. Sedia menerima instruksi baru.</span>';
        }

        function copyConsoleLogs() {
            const logsText = document.getElementById('consoleOutput').innerText;
            navigator.clipboard.writeText(logsText).then(() => {
                alert('Log berhasil disalin ke clipboard!');
            });
        }

        function triggerRepair(actionName, btnElement) {
            const originalText = btnElement.innerHTML;
            btnElement.disabled = true;
            btnElement.innerHTML = `<i class="fas fa-spinner fa-spin me-1"></i> Memproses...`;

            appendLog(`▶ Menjalankan eksekusi: [${actionName}]...`, 'info');

            fetch('{{ route("secret_repair.run") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ action: actionName })
            })
            .then(async response => {
                const isJson = response.headers.get('content-type')?.includes('application/json');
                const data = isJson ? await response.json() : null;

                if (!response.ok) {
                    const text = !isJson ? await response.text() : '';
                    const errorMsg = data?.message || data?.error || (text ? 'Server HTTP ' + response.status + ' Error' : 'Response error');
                    throw new Error(errorMsg);
                }
                return data;
            })
            .then(data => {
                btnElement.disabled = false;
                btnElement.innerHTML = originalText;

                if (data && data.success) {
                    appendLog(`✅ EKSEKUSI [${actionName}] SELESAI dalam ${data.duration}:`, 'success');
                    appendLog(data.output || 'Selesai tanpa output.', 'success');

                    if (data.stats && data.stats.missing_items !== undefined && document.getElementById('statMissingItems')) {
                        document.getElementById('statMissingItems').innerText = data.stats.missing_items.toLocaleString('id-ID');
                    }
                } else {
                    appendLog(`❌ EKSEKUSI [${actionName}] GAGAL:`, 'error');
                    appendLog(data?.output || data?.error || data?.message || 'Terjadi kesalahan pada server.', 'error');
                }
            })
            .catch(err => {
                btnElement.disabled = false;
                btnElement.innerHTML = originalText;
                appendLog(`❌ Network / Timeout Error: ${err.message}`, 'error');
            });
        }
        function confirmMigrate(btnElement) {
            if (!confirm('⚠️ Yakin ingin menjalankan php artisan migrate --force?\n\nIni akan menerapkan migration database terbaru ke production. Pastikan tidak ada perubahan schema yang berbahaya.')) {
                return;
            }
            triggerRepair('artisan_migrate', btnElement);
        }

        // ── COMPARE STATS (Tabel Perbandingan ERP vs API) ──────────────────────
        const compareUrl = '{{ route("secret_repair.compare_stats") }}';

        function formatRp(num) {
            if (!num && num !== 0) return 'Rp 0';
            return 'Rp ' + Math.round(num).toLocaleString('id-ID');
        }

        function diffBadge(diff) {
            if (Math.abs(diff) < 100) return `<span class="badge bg-success-subtle text-success-emphasis border border-success-subtle px-2" style="font-size:0.65rem;">✓ Match</span>`;
            const cls = diff > 0 ? 'bg-warning-subtle text-warning-emphasis border-warning-subtle' : 'bg-danger-subtle text-danger-emphasis border-danger-subtle';
            const sign = diff > 0 ? '+' : '';
            return `<span class="badge ${cls} border px-2 font-monospace" style="font-size:0.65rem;">${sign}${formatRp(diff)}</span>`;
        }

        function getChannelBadge(channel) {
            if (channel.includes('tiktok')) return '<span class="badge bg-dark text-white" style="font-size:0.65rem;"><i class="fab fa-tiktok me-1"></i>TikTok</span>';
            if (channel.includes('shopee')) return '<span class="badge bg-danger text-white" style="font-size:0.65rem;"><i class="fas fa-shopping-bag me-1"></i>Shopee</span>';
            return '<span class="badge bg-secondary text-white" style="font-size:0.65rem;">' + channel + '</span>';
        }

        const detailBaseUrl = '{{ route("secret_repair.compare_detail") }}';

        function renderChannelRow(icon, label, d, channelKey) {
            const dateFrom = document.getElementById('filterDateFrom').value;
            const dateTo   = document.getElementById('filterDateTo').value;
            const url = `${detailBaseUrl}?channel=${channelKey}&date_from=${dateFrom}&date_to=${dateTo}`;
            return `
                <tr style="cursor:pointer" onclick="window.open('${url}', '_blank')" title="Klik untuk lihat detail order ${label}">
                    <td class="ps-4 fw-bold">
                        ${icon} ${label}
                        <span class="badge bg-secondary-subtle text-secondary-emphasis border ms-2" style="font-size:0.62rem;">
                            <i class="fas fa-external-link-alt me-1"></i>Lihat Detail
                        </span>
                    </td>
                    <td class="text-end font-monospace fw-semibold">${d.erp_count.toLocaleString('id-ID')}
                        <br><small class="text-secondary" style="font-size:0.65rem;">API: ${d.api_count.toLocaleString('id-ID')}</small></td>
                    <td class="text-end font-monospace border-start" style="color:#1d4ed8;">${formatRp(d.erp_omset)}</td>
                    <td class="text-end font-monospace" style="color:#1d4ed8; background:#eff6ff;">${formatRp(d.api_omset)}</td>
                    <td class="text-end font-monospace border-start" style="color:#c2410c;">${formatRp(d.erp_fee)}</td>
                    <td class="text-end font-monospace" style="color:#c2410c; background:#fff7ed;">${formatRp(d.api_fee)}</td>
                    <td class="text-end font-monospace border-start" style="color:#15803d;">${formatRp(d.erp_net)}</td>
                    <td class="text-end font-monospace" style="color:#15803d; background:#f0fdf4;">${formatRp(d.api_net)}</td>
                    <td class="text-end border-start">${diffBadge(d.diff_omset)}</td>
                    <td class="text-end">${diffBadge(d.diff_fee)}</td>
                    <td class="text-end pe-4">${diffBadge(d.diff_net)}</td>
                </tr>`;
        }

        function loadCompareStats() {
            const dateFrom = document.getElementById('filterDateFrom').value;
            const dateTo   = document.getElementById('filterDateTo').value;
            const btn      = document.getElementById('btnLoadCompare');

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Memuat...';

            document.getElementById('compareTableBody').innerHTML = '<tr><td colspan="11" class="text-center py-4 text-secondary"><i class="fas fa-spinner fa-spin me-2"></i> Mengambil data ERP + API...</td></tr>';
            document.getElementById('compareTableFoot').innerHTML = '';
            document.getElementById('storeDetailBody').innerHTML = '<tr><td colspan="12" class="text-center py-3 text-secondary"><i class="fas fa-spinner fa-spin me-1"></i> Memuat...</td></tr>';

            const params = new URLSearchParams({ date_from: dateFrom, date_to: dateTo });

            fetch(compareUrl + '?' + params.toString(), {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
            })
            .then(r => r.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-search me-1"></i> Tampilkan';

                const label = (data.date_from === 'Semua waktu') ? 'Semua waktu' : (data.date_from + ' s/d ' + data.date_to);
                document.getElementById('compareDateRangeText').textContent = label;

                // Render channel rows
                document.getElementById('compareTableBody').innerHTML =
                    renderChannelRow('<i class="fab fa-tiktok text-dark"></i>', 'TikTok Shop & Tokopedia', data.tiktok, 'tiktok') +
                    renderChannelRow('<i class="fas fa-shopping-bag text-danger"></i>', 'Shopee Seller Center', data.shopee, 'shopee');

                // Footer Total
                const t = data.total;
                document.getElementById('compareTableFoot').innerHTML = `
                    <tr style="border-top:2px solid #334155; background:#0f172a; color:#fff;">
                        <td class="ps-4 fw-bold text-white" style="font-size:0.72rem; letter-spacing:0.04em;">TOTAL SEMUA CHANNEL</td>
                        <td class="text-end font-monospace fw-bold text-white">${t.erp_count.toLocaleString('id-ID')} order</td>
                        <td class="text-end font-monospace fw-bold border-start" style="color:#93c5fd;">${formatRp(t.erp_omset)}</td>
                        <td class="text-end font-monospace fw-bold" style="color:#93c5fd;">${formatRp(t.api_omset)}</td>
                        <td class="text-end font-monospace fw-bold border-start" style="color:#fca5a5;">${formatRp(t.erp_fee)}</td>
                        <td class="text-end font-monospace fw-bold" style="color:#fca5a5;">${formatRp(t.api_fee)}</td>
                        <td class="text-end font-monospace fw-bold border-start" style="color:#86efac;">${formatRp(t.erp_net)}</td>
                        <td class="text-end font-monospace fw-bold" style="color:#86efac;">${formatRp(t.api_net)}</td>
                        <td class="text-end border-start">${diffBadge(t.diff_omset)}</td>
                        <td class="text-end">${diffBadge(t.diff_fee)}</td>
                        <td class="text-end pe-4">${diffBadge(t.diff_net)}</td>
                    </tr>`;

                // Per-store detail
                let storeHtml = '';
                if (data.stores && data.stores.length > 0) {
                    data.stores.forEach(s => {
                        storeHtml += `
                            <tr>
                                <td class="ps-4 fw-semibold">${escapeHtml(s.store_name)}</td>
                                <td>${getChannelBadge(s.channel)}</td>
                                <td class="text-end font-monospace">${s.erp_count.toLocaleString('id-ID')}</td>
                                <td class="text-end font-monospace text-danger">${s.erp_cancelled.toLocaleString('id-ID')}</td>
                                <td class="text-end font-monospace" style="color:#1d4ed8;">${formatRp(s.erp_omset)}</td>
                                <td class="text-end font-monospace" style="color:#1d4ed8; background:#eff6ff;">${formatRp(s.api_omset)}</td>
                                <td class="text-end font-monospace" style="color:#c2410c;">${formatRp(s.erp_fee)}</td>
                                <td class="text-end font-monospace" style="color:#c2410c; background:#fff7ed;">${formatRp(s.api_fee)}</td>
                                <td class="text-end font-monospace" style="color:#15803d;">${formatRp(s.erp_net)}</td>
                                <td class="text-end font-monospace" style="color:#15803d; background:#f0fdf4;">${formatRp(s.api_net)}</td>
                                <td class="text-end">${diffBadge(s.diff_fee)}</td>
                                <td class="text-end pe-4">${diffBadge(s.diff_net)}</td>
                            </tr>`;
                    });
                } else {
                    storeHtml = '<tr><td colspan="12" class="text-center py-3 text-secondary">Tidak ada data toko.</td></tr>';
                }

                document.getElementById('storeDetailBody').innerHTML = storeHtml;
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-search me-1"></i> Tampilkan';
                document.getElementById('compareTableBody').innerHTML = `<tr><td colspan="11" class="text-center py-4 text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Gagal memuat: ${err.message}</td></tr>`;
            });
        }

        function resetCompareFilter() {
            document.getElementById('filterDateFrom').value = '';
            document.getElementById('filterDateTo').value = '';
            document.getElementById('compareDateRangeText').textContent = 'Semua waktu';
            loadCompareStats();
        }

        // Auto-load saat halaman dibuka
        document.addEventListener('DOMContentLoaded', () => loadCompareStats());
    </script>
</body>
</html>
