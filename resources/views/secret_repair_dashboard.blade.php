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

        <!-- 🌐 SECTION 2: Perbandingan Data ERP vs Marketplace API per Channel -->
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
            .then(response => response.json())
            .then(data => {
                btnElement.disabled = false;
                btnElement.innerHTML = originalText;

                if (data.success) {
                    appendLog(`✅ EKSEKUSI [${actionName}] SELESAI dalam ${data.duration}:`, 'success');
                    appendLog(data.output, 'success');

                    if (data.stats) {
                        if (data.stats.missing_items !== undefined) {
                            document.getElementById('statMissingItems').innerText = data.stats.missing_items.toLocaleString('id-ID');
                        }
                    }
                } else {
                    appendLog(`❌ EKSEKUSI [${actionName}] GAGAL:`, 'error');
                    appendLog(data.output || data.error, 'error');
                }
            })
            .catch(err => {
                btnElement.disabled = false;
                btnElement.innerHTML = originalText;
                appendLog(`❌ Network Error: ${err.message}`, 'error');
            });
        }
        function confirmMigrate(btnElement) {
            if (!confirm('⚠️ Yakin ingin menjalankan php artisan migrate --force?\n\nIni akan menerapkan migration database terbaru ke production. Pastikan tidak ada perubahan schema yang berbahaya.')) {
                return;
            }
            triggerRepair('artisan_migrate', btnElement);
        }
    </script>
</body>
</html>
