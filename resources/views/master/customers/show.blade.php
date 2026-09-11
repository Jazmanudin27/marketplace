@extends('layouts.app')
@section('title', 'Detail Pelanggan — ' . $customer->name)
@section('page-title', 'Profil Pelanggan')

@section('content')
<style>
    /* High-contrast, elegant badge styles to fix all bg & text clashing */
    .badge-soft-primary { background-color: #eff6ff !important; color: #1e40af !important; border: 1px solid #bfdbfe !important; }
    .badge-soft-success { background-color: #ecfdf5 !important; color: #065f46 !important; border: 1px solid #a7f3d0 !important; }
    .badge-soft-danger  { background-color: #fef2f2 !important; color: #991b1b !important; border: 1px solid #fecaca !important; }
    .badge-soft-warning { background-color: #fffbeb !important; color: #92400e !important; border: 1px solid #fde68a !important; }
    .badge-soft-info    { background-color: #f0f9ff !important; color: #0369a1 !important; border: 1px solid #bae6fd !important; }
    .badge-soft-secondary { background-color: #f8fafc !important; color: #334155 !important; border: 1px solid #cbd5e1 !important; }
    .badge-soft-dark    { background-color: #f1f5f9 !important; color: #0f172a !important; border: 1px solid #cbd5e1 !important; }

    /* Modern Pill Tabs */
    .customer-tabs {
        background-color: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        padding: 0.35rem;
        gap: 0.25rem;
    }
    .customer-tabs .nav-link {
        color: #475569;
        border: none;
        border-radius: 0.375rem;
        padding: 0.5rem 0.85rem;
        font-size: 0.82rem;
        font-weight: 500;
        transition: all 0.15s ease-in-out;
    }
    .customer-tabs .nav-link:hover {
        background-color: #f1f5f9;
        color: #0f172a;
    }
    .customer-tabs .nav-link.active {
        background-color: #2563eb;
        color: #ffffff !important;
        font-weight: 600;
        box-shadow: 0 1px 2px rgba(37, 99, 235, 0.2);
    }
    .customer-tabs .nav-link.active i {
        color: #ffffff !important;
    }
    .customer-tabs .nav-link .tab-badge {
        font-size: 0.7rem;
        padding: 0.2em 0.55em;
        background-color: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
        transition: all 0.15s ease;
    }
    .customer-tabs .nav-link.active .tab-badge {
        background-color: rgba(255, 255, 255, 0.25) !important;
        color: #ffffff !important;
        border-color: rgba(255, 255, 255, 0.3) !important;
    }
</style>

    {{-- HEADER PAGE --}}
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center fw-bold"
                style="width: 48px; height: 48px; font-size: 1.25rem;">
                <i class="fas fa-user"></i>
            </div>
            <div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h4 class="mb-0 text-dark fw-bold">{{ $customer->name }}</h4>
                    @if($customer->marketplace_username)
                        <span class="badge badge-soft-secondary font-monospace">{{ $customer->marketplace_username }}</span>
                    @endif
                    @if($customer->orders->count() >= 3)
                        <span class="badge badge-soft-warning"><i class="fas fa-crown me-1 text-warning"></i>Loyal Customer</span>
                    @endif
                </div>
                <p class="text-muted mb-0 small">Profil lengkap pelanggan, saldo deposit, piutang &amp; riwayat transaksi</p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('customers.index') }}" class="btn btn-secondary btn-sm px-3">
                <i class="fas fa-arrow-left me-1"></i> Kembali
            </a>
            <button type="button" class="btn btn-primary btn-sm px-3 text-white fw-medium" data-bs-toggle="modal" data-bs-target="#editCustomerModal">
                <i class="fas fa-edit me-1"></i> Edit Profil
            </button>
            <a href="{{ route('offline_sales.create', ['customer_id' => $customer->id]) }}" class="btn btn-success btn-sm px-3 fw-medium">
                <i class="fas fa-cash-register me-1"></i> Buat Penjualan POS
            </a>
        </div>
    </div>

    {{-- Alert Notification --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center mb-3 py-2.5 px-3 border-0 shadow-sm" role="alert" style="background-color: #ecfdf5; color: #065f46; border-left: 4px solid #10b981 !important;">
            <i class="fas fa-check-circle me-2"></i>
            <div class="small fw-medium">{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-3">
        {{-- KOLOM KIRI: Informasi Profil & Status Keuangan --}}
        <div class="col-md-5 col-lg-4">
            {{-- Card 1: Data Profil Pelanggan --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white py-3 px-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="fas fa-id-card me-2 text-primary"></i>Informasi Pelanggan
                    </h6>
                    <button type="button" class="btn btn-sm btn-outline-primary py-0.5 px-2 rounded-2" data-bs-toggle="modal" data-bs-target="#editCustomerModal" style="font-size:0.75rem;">
                        <i class="fas fa-edit me-1"></i>Edit
                    </button>
                </div>
                <div class="card-body p-3">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm flex-shrink-0" 
                            style="width: 54px; height: 54px; font-size: 1.5rem; background-color: #eff6ff; color: #1d4ed8; border: 2px solid #bfdbfe;">
                            {{ strtoupper(substr($customer->name, 0, 1)) }}
                        </div>
                        <div class="overflow-hidden">
                            <h6 class="mb-0 text-dark fw-bold text-truncate">{{ $customer->name }}</h6>
                            <span class="badge badge-soft-info text-capitalize mt-1">{{ $customer->category ?? 'Umum' }}</span>
                            @if($customer->tags)
                                @foreach(explode(',', $customer->tags) as $t)
                                    @if(trim($t))
                                        <span class="badge badge-soft-secondary mt-1">{{ trim($t) }}</span>
                                    @endif
                                @endforeach
                            @endif
                        </div>
                    </div>

                    <table class="table table-sm table-borderless mb-0 align-middle" style="font-size: 0.82rem;">
                        <tbody>
                            <tr>
                                <td class="text-muted ps-0 py-1.5" style="width: 100px;">Telepon / WA</td>
                                <td class="text-end pe-0 py-1.5">
                                    @if ($customer->phone)
                                        <span class="font-monospace text-dark fw-semibold">{{ $customer->phone }}</span>
                                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $customer->phone) }}" target="_blank" class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 ms-1.5 text-decoration-none px-1.5 py-0.5" title="Chat via WhatsApp">
                                            <i class="fab fa-whatsapp me-0.5"></i> WA
                                        </a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted ps-0 py-1.5">Username</td>
                                <td class="text-end pe-0 py-1.5 font-monospace text-dark">{{ $customer->marketplace_username ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted ps-0 py-1.5">Terdaftar</td>
                                <td class="text-end pe-0 py-1.5 text-dark">{{ $customer->created_at ? $customer->created_at->format('d M Y') : '-' }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="mt-2.5 pt-2.5 border-top border-light-subtle">
                        <small class="text-muted d-block text-uppercase fw-bold mb-1" style="font-size: 0.68rem; letter-spacing: 0.5px;">
                            <i class="fas fa-map-marker-alt me-1 text-danger"></i>Alamat
                        </small>
                        <div class="small text-secondary text-break" style="line-height: 1.45;">
                            {{ $customer->address ?: 'Belum ada alamat terdaftar.' }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card 2: Saldo Deposit & Tagihan Piutang --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white py-3 px-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="fas fa-wallet me-2 text-success"></i>Keuangan Pelanggan
                    </h6>
                </div>
                <div class="card-body p-3">
                    {{-- Saldo Reseller --}}
                    <div class="p-3 rounded-3 bg-light border border-light-subtle mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small fw-semibold">
                                <i class="fas fa-coins me-1 text-success"></i>Saldo Deposit Reseller
                            </span>
                            <button type="button" class="btn btn-outline-success btn-xs py-0.5 px-2 fw-semibold" style="font-size: 0.72rem;" data-bs-toggle="modal" data-bs-target="#topupModal">
                                <i class="fas fa-plus-circle me-1"></i>Top-up / Tarik
                            </button>
                        </div>
                        <div class="fw-bold fs-4 text-success font-monospace">Rp {{ number_format($customer->balance, 0, ',', '.') }}</div>
                        <small class="text-muted d-block mt-0.5" style="font-size: 0.7rem;">Saldo aktif yang dapat dipotong untuk order POS Offline.</small>
                    </div>

                    {{-- Piutang Belum Lunas --}}
                    <div class="p-3 rounded-3 bg-light border border-light-subtle">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small fw-semibold">
                                <i class="fas fa-file-invoice-dollar me-1 text-danger"></i>Sisa Piutang Belum Lunas
                            </span>
                            @if($totalReceivable > 0)
                                <button type="button" class="btn btn-outline-danger btn-xs py-0.5 px-2 fw-semibold" style="font-size: 0.72rem;" data-bs-toggle="modal" data-bs-target="#payReceivableModal">
                                    <i class="fas fa-money-bill-wave me-1"></i>Pelunasan
                                </button>
                            @endif
                        </div>
                        <div class="fw-bold fs-4 {{ $totalReceivable > 0 ? 'text-danger' : 'text-dark' }} font-monospace">
                            Rp {{ number_format($totalReceivable, 0, ',', '.') }}
                        </div>
                        <small class="text-muted d-block mt-0.5" style="font-size: 0.7rem;">
                            {{ $totalReceivable > 0 ? 'Tunggakan dari transaksi tempo yang belum lunas.' : 'Tidak ada tunggakan piutang aktif.' }}
                        </small>
                    </div>
                </div>
            </div>

            {{-- Card 3: Ringkasan Nilai Belanja --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white py-3 px-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="fas fa-chart-pie me-2 text-info"></i>Ringkasan Belanja (LTV)
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-light-subtle">
                        <span class="text-muted small">Total Transaksi</span>
                        <span class="font-monospace fw-bold small text-dark">{{ $totalOrdersCount }}x Transaksi</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-light-subtle">
                        <span class="text-muted small">Total Belanja (LTV)</span>
                        <span class="font-monospace text-success fw-bold small">Rp {{ number_format($totalSpent, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-2">
                        <span class="text-muted small">Rata-rata Order</span>
                        <span class="font-monospace fw-semibold small text-dark">Rp {{ number_format($averageOrderValue, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- KOLOM KANAN: Tabbed Riwayat Transaksi --}}
        <div class="col-md-7 col-lg-8">
            <ul class="nav nav-pills customer-tabs mb-3 shadow-sm" id="customerDetailTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders-pane" type="button" role="tab">
                        <i class="fas fa-globe me-1.5 text-primary"></i>Pesanan Online
                        <span class="badge rounded-pill tab-badge ms-1.5">{{ $customer->orders->count() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="offline-tab" data-bs-toggle="tab" data-bs-target="#offline-pane" type="button" role="tab">
                        <i class="fas fa-store me-1.5 text-primary"></i>Penjualan POS Offline
                        <span class="badge rounded-pill tab-badge ms-1.5">{{ $offlineSales->count() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="receivable-tab" data-bs-toggle="tab" data-bs-target="#receivable-pane" type="button" role="tab">
                        <i class="fas fa-file-invoice-dollar me-1.5 text-primary"></i>Tagihan Piutang
                        <span class="badge rounded-pill tab-badge ms-1.5">{{ $receivableSales->count() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="balance-tab" data-bs-toggle="tab" data-bs-target="#balance-pane" type="button" role="tab">
                        <i class="fas fa-wallet me-1.5 text-primary"></i>Mutasi Deposit
                        <span class="badge rounded-pill tab-badge ms-1.5">{{ $customer->balanceTransactions->count() }}</span>
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="customerDetailTabsContent">
                {{-- TAB 1: RIWAYAT PESANAN ONLINE --}}
                <div class="tab-pane fade show active" id="orders-pane" role="tabpanel">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-0">
                            @if($customer->orders->isEmpty())
                                <div class="text-center py-5 text-muted">
                                    <i class="fas fa-globe fa-2x mb-3 d-block text-secondary opacity-50"></i>
                                    <p class="mb-0 small">Belum ada riwayat pesanan online dari marketplace.</p>
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.8rem;">
                                        <thead class="table-light text-secondary" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                            <tr>
                                                <th class="ps-3 py-2.5">TGL PESANAN</th>
                                                <th class="py-2.5">NO. INVOICE / ID</th>
                                                <th class="py-2.5">STATUS</th>
                                                <th class="py-2.5 text-end">NILAI BERSIH (LTV)</th>
                                                <th class="py-2.5 text-center pe-3" style="width: 80px;">AKSI</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($customer->orders as $order)
                                                <tr>
                                                    <td class="text-muted ps-3" style="font-size:0.75rem;">
                                                        {{ $order->order_date ? $order->order_date->format('d M Y, H:i') : '-' }}
                                                    </td>
                                                    <td>
                                                        <div class="fw-semibold text-dark">{{ $order->invoice_number ?? $order->order_marketplace_id }}</div>
                                                        <span class="text-muted" style="font-size:0.7rem;">
                                                            {{ $order->items->count() }} item produk
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @php
                                                            $badgeStyle = match($order->status_badge) {
                                                                'success' => 'badge-soft-success',
                                                                'warning' => 'badge-soft-warning',
                                                                'danger'  => 'badge-soft-danger',
                                                                'info'    => 'badge-soft-info',
                                                                default   => 'badge-soft-secondary',
                                                            };
                                                        @endphp
                                                        <span class="badge {{ $badgeStyle }} text-uppercase px-2 py-1">
                                                            {{ str_replace('_', ' ', $order->order_status) }}
                                                        </span>
                                                    </td>
                                                    <td class="font-monospace fw-bold text-end text-success">
                                                        Rp {{ number_format($order->net_amount, 0, ',', '.') }}
                                                    </td>
                                                    <td class="text-center pe-3">
                                                        <a href="{{ route('orders.show', $order->id) }}" class="btn btn-outline-primary btn-xs px-2 py-0.5" title="Detail Pesanan">
                                                            <i class="fas fa-eye small"></i> View
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- TAB 2: RIWAYAT PENJUALAN OFFLINE (POS) --}}
                <div class="tab-pane fade" id="offline-pane" role="tabpanel">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-0">
                            @if($offlineSales->isEmpty())
                                <div class="text-center py-5 text-muted">
                                    <i class="fas fa-store fa-2x mb-3 d-block text-secondary opacity-50"></i>
                                    <p class="mb-0 small">Belum ada riwayat penjualan offline (POS).</p>
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.8rem;">
                                        <thead class="table-light text-secondary" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                            <tr>
                                                <th class="ps-3 py-2.5">TGL TRANSAKSI</th>
                                                <th class="py-2.5">NO. TRANSAKSI</th>
                                                <th class="py-2.5">METODE</th>
                                                <th class="py-2.5">STATUS BAYAR</th>
                                                <th class="py-2.5">STATUS ORDER</th>
                                                <th class="py-2.5 text-end">GRAND TOTAL</th>
                                                <th class="py-2.5 text-center pe-3" style="width: 80px;">AKSI</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($offlineSales as $sale)
                                                <tr>
                                                    <td class="text-muted ps-3" style="font-size:0.75rem;">
                                                        {{ $sale->sold_at ? $sale->sold_at->format('d M Y, H:i') : '-' }}
                                                    </td>
                                                    <td>
                                                        <div class="fw-semibold text-dark font-monospace">{{ $sale->sale_number }}</div>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-soft-secondary">
                                                            {{ $sale->payment_method_label }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @php
                                                            $payBadge = match($sale->payment_status_badge) {
                                                                'success' => 'badge-soft-success',
                                                                'danger'  => 'badge-soft-danger',
                                                                'warning' => 'badge-soft-warning',
                                                                default   => 'badge-soft-secondary',
                                                            };
                                                        @endphp
                                                        <span class="badge {{ $payBadge }}">
                                                            {{ $sale->payment_status_label }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @php
                                                            $ordBadge = match($sale->status_badge) {
                                                                'success' => 'badge-soft-success',
                                                                'danger'  => 'badge-soft-danger',
                                                                'warning' => 'badge-soft-warning',
                                                                'info'    => 'badge-soft-info',
                                                                default   => 'badge-soft-secondary',
                                                            };
                                                        @endphp
                                                        <span class="badge {{ $ordBadge }} text-uppercase">
                                                            {{ $sale->status_label }}
                                                        </span>
                                                    </td>
                                                    <td class="font-monospace fw-bold text-end text-dark">
                                                        Rp {{ number_format($sale->grand_total, 0, ',', '.') }}
                                                    </td>
                                                    <td class="text-center pe-3">
                                                        <a href="{{ route('offline_sales.show', $sale->id) }}" class="btn btn-outline-primary btn-xs px-2 py-0.5" title="Detail Penjualan">
                                                            <i class="fas fa-eye small"></i> View
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- TAB 3: TAGIHAN PIUTANG --}}
                <div class="tab-pane fade" id="receivable-pane" role="tabpanel">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-0">
                            @if($receivableSales->isEmpty())
                                <div class="text-center py-5 text-muted">
                                    <i class="fas fa-check-circle fa-2x mb-3 d-block text-success opacity-50"></i>
                                    <p class="mb-0 small">Pelanggan ini tidak memiliki tunggakan piutang.</p>
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.8rem;">
                                        <thead class="table-light text-secondary" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                            <tr>
                                                <th class="ps-3 py-2.5">TANGGAL POS</th>
                                                <th class="py-2.5">NO. TRANSAKSI</th>
                                                <th class="py-2.5 text-end">GRAND TOTAL</th>
                                                <th class="py-2.5 text-end">SUDAH DIBAYAR</th>
                                                <th class="py-2.5 text-end">SISA PIUTANG</th>
                                                <th class="py-2.5 text-center pe-3" style="width: 100px;">AKSI</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($receivableSales as $sale)
                                                @php $saleUnpaid = max(0, (float)$sale->grand_total - (float)$sale->paid_amount); @endphp
                                                <tr>
                                                    <td class="text-muted ps-3" style="font-size:0.75rem;">
                                                        {{ $sale->sold_at ? $sale->sold_at->format('d M Y, H:i') : '-' }}
                                                    </td>
                                                    <td>
                                                        <a href="{{ route('offline_sales.show', $sale->id) }}" class="fw-semibold text-primary text-decoration-none font-monospace">
                                                            {{ $sale->sale_number }}
                                                        </a>
                                                    </td>
                                                    <td class="font-monospace text-end text-dark">Rp {{ number_format($sale->grand_total, 0, ',', '.') }}</td>
                                                    <td class="font-monospace text-end text-success">Rp {{ number_format($sale->paid_amount, 0, ',', '.') }}</td>
                                                    <td class="font-monospace text-end text-danger fw-bold">Rp {{ number_format($saleUnpaid, 0, ',', '.') }}</td>
                                                    <td class="text-center pe-3">
                                                        <button type="button" class="btn btn-outline-success btn-xs px-2 py-0.5 btn-pay-single-sale fw-medium"
                                                            data-bs-toggle="modal" data-bs-target="#payReceivableModal"
                                                            data-sale-id="{{ $sale->id }}"
                                                            data-sale-number="{{ $sale->sale_number }}"
                                                            data-unpaid="{{ $saleUnpaid }}">
                                                            <i class="fas fa-money-bill-wave me-1"></i>Bayar
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- TAB 4: RIWAYAT DEPOSIT / MUTASI --}}
                <div class="tab-pane fade" id="balance-pane" role="tabpanel">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-0">
                            @php $balanceTxList = $customer->balanceTransactions()->orderByDesc('created_at')->get(); @endphp
                            @if($balanceTxList->isEmpty())
                                <div class="text-center py-5 text-muted">
                                    <i class="fas fa-wallet fa-2x mb-3 d-block text-secondary opacity-50"></i>
                                    <p class="mb-0 small">Belum ada riwayat mutasi saldo deposit reseller.</p>
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.8rem;">
                                        <thead class="table-light text-secondary" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                            <tr>
                                                <th class="ps-3 py-2.5">TANGGAL</th>
                                                <th class="py-2.5">TIPE</th>
                                                <th class="py-2.5 text-end">NOMINAL</th>
                                                <th class="py-2.5">DESKRIPSI</th>
                                                <th class="py-2.5 text-center pe-3">PETUGAS</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($balanceTxList as $tx)
                                                <tr>
                                                    <td class="text-muted ps-3" style="font-size:0.75rem;">
                                                        {{ $tx->created_at->format('d M Y, H:i') }}
                                                    </td>
                                                    <td>
                                                        <span class="badge {{ $tx->type === 'in' ? 'badge-soft-success' : 'badge-soft-danger' }}">
                                                            {{ $tx->type_label }}
                                                        </span>
                                                    </td>
                                                    <td class="font-monospace fw-bold text-end {{ $tx->type === 'in' ? 'text-success' : 'text-danger' }}">
                                                        {{ $tx->type === 'in' ? '+' : '-' }} Rp {{ number_format($tx->amount, 0, ',', '.') }}
                                                    </td>
                                                    <td class="text-dark">{{ $tx->description }}</td>
                                                    <td class="text-secondary text-center pe-3">{{ $tx->user->name ?? '-' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL EDIT DATA PELANGGAN --}}
    <div class="modal fade" id="editCustomerModal" tabindex="-1" aria-labelledby="editCustomerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary bg-opacity-10 border-bottom">
                    <h6 class="modal-title fw-bold text-primary" id="editCustomerModalLabel">
                        <i class="fas fa-user-edit me-2"></i>Edit Data Pelanggan
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('customers.update', $customer->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body p-3">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Nama Pelanggan / Alias <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control form-control-sm" value="{{ $customer->name }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Kategori Pelanggan</label>
                            <select name="category" class="form-select form-select-sm">
                                <option value="umum" {{ $customer->category === 'umum' ? 'selected' : '' }}>Umum</option>
                                <option value="biasa" {{ $customer->category === 'biasa' ? 'selected' : '' }}>Biasa</option>
                                <option value="dropship" {{ $customer->category === 'dropship' ? 'selected' : '' }}>Dropship</option>
                                <option value="marketplace" {{ $customer->category === 'marketplace' ? 'selected' : '' }}>Marketplace</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Nomor Telepon / WhatsApp</label>
                            <input type="text" name="phone" class="form-control form-control-sm" value="{{ $customer->phone }}" placeholder="Contoh: 08123456789">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Alamat Utama</label>
                            <textarea name="address" class="form-control form-control-sm" rows="3" placeholder="Alamat lengkap jalan, kota, kode pos...">{{ $customer->address }}</textarea>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold text-dark">Tag / Label Tambahan</label>
                            <input type="text" name="tags" class="form-control form-control-sm" value="{{ $customer->tags }}" placeholder="VIP, Reseller, Grosir">
                            <div class="form-text text-muted" style="font-size:0.72rem;">Pisahkan dengan koma jika lebih dari satu (misal: VIP, Reseller).</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4">
                            <i class="fas fa-save me-1"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL TOPUP / PENYESUAIAN SALDO RESELLER --}}
    <div class="modal fade" id="topupModal" tabindex="-1" aria-labelledby="topupModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form action="{{ route('customers.topup', $customer->id) }}" method="POST">
                    @csrf
                    <div class="modal-header bg-success bg-opacity-10 border-bottom">
                        <h6 class="modal-title fw-bold text-success" id="topupModalLabel">
                            <i class="fas fa-coins me-2"></i>Sesuaikan Saldo Deposit Reseller
                        </h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-3">
                        <div class="p-2.5 rounded bg-light border mb-3">
                            <div class="small text-muted mb-0.5">Saldo Saat Ini:</div>
                            <strong class="font-monospace fs-5 text-success">Rp {{ number_format($customer->balance, 0, ',', '.') }}</strong>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Tipe Penyesuaian <span class="text-danger">*</span></label>
                            <select name="type" class="form-select form-select-sm" required>
                                <option value="in">Kredit / Top-up Tambah Saldo (+)</option>
                                <option value="out">Debit / Tarik Kurangi Saldo (-)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Nominal Penyesuaian (Rp) <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text fw-bold">Rp</span>
                                <input type="number" name="amount" step="0.01" min="0.01" class="form-control font-monospace fw-bold" placeholder="Contoh: 100000" required>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold text-dark">Keterangan / Deskripsi <span class="text-danger">*</span></label>
                            <input type="text" name="description" class="form-control form-control-sm" placeholder="Contoh: Deposit reseller via transfer BCA" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-success btn-sm px-3">
                            <i class="fas fa-check-circle me-1"></i> Proses Transaksi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL PELUNASAN PIUTANG PELANGGAN --}}
    <div class="modal fade" id="payReceivableModal" tabindex="-1" aria-labelledby="payReceivableModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form action="{{ route('customers.pay_receivable', $customer->id) }}" method="POST">
                    @csrf
                    <input type="hidden" name="offline_sale_id" id="modal_pay_offline_sale_id" value="">
                    <div class="modal-header bg-success bg-opacity-10 border-bottom">
                        <h6 class="modal-title fw-bold text-success" id="payReceivableModalLabel">
                            <i class="fas fa-money-bill-wave me-2"></i>Pelunasan Piutang Pelanggan
                        </h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-3">
                        <div class="p-3 bg-light rounded border border-light-subtle mb-3">
                            <div class="small text-muted mb-1">Pelanggan: <strong class="text-dark">{{ $customer->name }}</strong></div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="small fw-semibold text-danger">Total Tunggakan:</span>
                                <strong class="fs-5 font-monospace text-danger" id="modal_pay_total_unpaid">Rp {{ number_format($totalReceivable, 0, ',', '.') }}</strong>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Nominal Pembayaran Pelunasan (Rp) <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text fw-bold">Rp</span>
                                <input type="number" name="amount" id="modal_pay_amount" step="any" min="1" max="{{ $totalReceivable }}" class="form-control font-monospace fw-bold" value="{{ $totalReceivable }}" required>
                            </div>
                            <div class="form-text text-muted" style="font-size:0.72rem;">Nominal yang diterima dari pelanggan untuk melunasi piutang.</div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label small fw-bold text-dark">Kas / Bank Tujuan Pemasukan <span class="text-danger">*</span></label>
                            <select name="payment_destination" class="form-select form-select-sm" required>
                                @if(isset($bankAccounts) && $bankAccounts->isNotEmpty())
                                    @foreach($bankAccounts as $bank)
                                        <option value="{{ $bank->bank_name }}">
                                            {{ $bank->bank_name }} {{ $bank->account_number ? '('.$bank->account_number.')' : '' }} — Saldo: Rp {{ number_format($bank->current_balance, 0, ',', '.') }}
                                        </option>
                                    @endforeach
                                @else
                                    <option value="kas_besar">Kas Besar (Utama)</option>
                                    <option value="kas_kecil">Kas Kecil (Operasional)</option>
                                @endif
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-success btn-sm px-4">
                            <i class="fas fa-check-circle me-1"></i> Simpan Pelunasan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Auto-activate tab based on URL hash (e.g. #receivable-pane)
    const hash = window.location.hash;
    if (hash) {
        const tabButton = document.querySelector(`button[data-bs-target="${hash}"]`);
        if (tabButton) {
            const tab = new bootstrap.Tab(tabButton);
            tab.show();
        }
    }

    // 2. Update URL hash when a tab is switched by the user
    const tabButtons = document.querySelectorAll('button[data-bs-toggle="tab"]');
    tabButtons.forEach(button => {
        button.addEventListener('shown.bs.tab', function (e) {
            const target = e.target.getAttribute('data-bs-target');
            if (target) {
                history.replaceState(null, null, target);
            }
        });
    });

    // 3. Modal show event binding
    const payModal = document.getElementById('payReceivableModal');
    if (payModal) {
        payModal.addEventListener('show.bs.modal', function (event) {
            const btn = event.relatedTarget;
            const saleId = btn ? btn.getAttribute('data-sale-id') : null;
            const unpaid = btn ? btn.getAttribute('data-unpaid') : null;
            
            const saleIdInput = document.getElementById('modal_pay_offline_sale_id');
            const amountInput = document.getElementById('modal_pay_amount');
            const totalUnpaidEl = document.getElementById('modal_pay_total_unpaid');

            if (saleId && unpaid) {
                saleIdInput.value = saleId;
                amountInput.value = unpaid;
                totalUnpaidEl.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(unpaid);
            } else {
                saleIdInput.value = '';
                amountInput.value = '{{ $totalReceivable }}';
                totalUnpaidEl.textContent = 'Rp {{ number_format($totalReceivable, 0, ",", ".") }}';
            }
        });
    }

    // 4. Submit loading state
    document.querySelectorAll('#payReceivableModal form, #topupModal form, #editCustomerModal form').forEach(function (form) {
        form.addEventListener('submit', function () {
            const btn = form.querySelector('button[type="submit"]');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Memproses...';
            }
        });
    });
});
</script>
@endpush

