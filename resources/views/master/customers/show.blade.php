@extends('layouts.app')
@section('title', 'Detail Pelanggan')
@section('page-title', 'Profil Pelanggan')

@section('content')
    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item">
                <a href="{{ route('customers.index') }}" class="text-decoration-none">
                    <i class="fas fa-users me-1"></i>Pelanggan
                </a>
            </li>
            <li class="breadcrumb-item active">Profil Pelanggan</li>
        </ol>
    </nav>

    <div class="row g-3">
        {{-- Profil Kiri --}}
        <div class="col-md-5 col-lg-4">
            <div class="card border shadow-sm p-3 text-center mb-3">
                <div class="rounded-circle bg-primary bg-opacity-10 text-primary mx-auto mb-3 d-flex align-items-center justify-content-center fw-bold" 
                    style="width:80px; height:80px; font-size:2.5rem; border: 1px solid rgba(59, 130, 246, 0.2);">
                    {{ strtoupper(substr($customer->name, 0, 1)) }}
                </div>
                <h5 class="mb-1 fw-bold text-dark">{{ $customer->name }}</h5>
                <p class="text-muted mb-3 font-monospace small">{{ $customer->marketplace_username ?? 'No Username' }}</p>
                
                @if($customer->orders->count() >= 3)
                    <div class="badge bg-warning-subtle text-warning border border-warning-subtle mb-3 w-100 py-2" style="font-size:0.8rem;">
                        <i class="fas fa-crown me-1"></i> Loyal Customer
                    </div>
                @endif

                {{-- Alert --}}
                @if (session('success'))
                    <div class="alert alert-success py-2 px-3 mb-3 small text-start" role="alert">
                        <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
                    </div>
                @endif

                <form action="{{ route('customers.update', $customer->id) }}" method="POST" class="text-start">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-2">
                        <label class="form-label small fw-bold text-dark">Nama / Alias</label>
                        <input type="text" name="name" class="form-control form-control-sm" value="{{ $customer->name }}" required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-bold text-dark">Nomor Telepon</label>
                        <input type="text" name="phone" class="form-control form-control-sm" value="{{ $customer->phone }}">
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-bold text-dark">Alamat Utama</label>
                        <textarea name="address" class="form-control form-control-sm" rows="3">{{ $customer->address }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Tag / Label Tambahan</label>
                        <input type="text" name="tags" class="form-control form-control-sm" value="{{ $customer->tags }}" placeholder="VIP, Reseller, Blacklist">
                        <small class="text-muted d-block mt-1" style="font-size:0.68rem;">Pisahkan dengan koma jika lebih dari satu.</small>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm w-100 mt-2">
                        <i class="fas fa-save me-1"></i> Simpan Profil
                    </button>
                </form>
            </div>

            {{-- Card Saldo Reseller --}}
            <div class="card border shadow-sm p-3 mb-3 border-start border-4 border-success bg-success bg-opacity-5">
                <div class="d-flex justify-content-between align-items-center mb-2 text-start">
                    <span class="text-secondary small fw-bold"><i class="fas fa-wallet me-1 text-success"></i> Saldo Deposit Reseller</span>
                    <button type="button" class="btn btn-xs btn-outline-success py-0 px-2" style="font-size: 0.72rem;" data-bs-toggle="modal" data-bs-target="#topupModal">
                        <i class="fas fa-plus-circle me-1"></i> Top-up / Tarik
                    </button>
                </div>
                <div class="fw-extrabold fs-4 text-success font-monospace mb-1 text-start">Rp {{ number_format($customer->balance, 0, ',', '.') }}</div>
                <small class="text-muted d-block text-start" style="font-size:0.68rem;">Digunakan untuk transaksi checkout POS Offline potong saldo.</small>
            </div>

            {{-- Card Sisa Piutang Pelanggan --}}
            <div class="card border shadow-sm p-3 mb-3 border-start border-4 border-danger bg-danger bg-opacity-5">
                <div class="d-flex justify-content-between align-items-center mb-2 text-start">
                    <span class="text-secondary small fw-bold"><i class="fas fa-file-invoice-dollar me-1 text-danger"></i> Sisa Piutang Belum Lunas</span>
                    @if($totalReceivable > 0)
                        <button type="button" class="btn btn-xs btn-outline-danger py-0 px-2" style="font-size: 0.72rem;" data-bs-toggle="modal" data-bs-target="#payReceivableModal">
                            <i class="fas fa-money-bill-wave me-1"></i> Pelunasan
                        </button>
                    @endif
                </div>
                <div class="fw-extrabold fs-4 text-danger font-monospace mb-1 text-start">Rp {{ number_format($totalReceivable, 0, ',', '.') }}</div>
                <small class="text-muted d-block text-start" style="font-size:0.68rem;">Tunggakan dari transaksi kasir POS yang belum dilunasi.</small>
            </div>

            <div class="card border shadow-sm p-3">
                <h6 class="fw-bold mb-3 text-dark"><i class="fas fa-chart-pie me-2 text-info"></i>Ringkasan Nilai</h6>
                
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-secondary small">Total Transaksi</span>
                    <span class="font-monospace fw-bold small text-dark">{{ $totalOrdersCount }}x</span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-secondary small">Total Belanja (LTV)</span>
                    <span class="font-monospace text-success fw-bold small">Rp {{ number_format($totalSpent, 0, ',', '.') }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2">
                    <span class="text-secondary small">Rata-rata Order</span>
                    <span class="font-monospace fw-semibold small text-dark">Rp {{ number_format($averageOrderValue, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        {{-- Riwayat Kanan (Tabbed) --}}
        <div class="col-md-7 col-lg-8">
            <ul class="nav nav-tabs mb-0 border-bottom-0" id="customerDetailTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active small fw-semibold py-2.5 px-3" id="orders-tab" data-bs-toggle="tab"
                        data-bs-target="#orders-pane" type="button" role="tab" style="border-top-left-radius: 8px; border-top-right-radius: 8px;">
                        <i class="fas fa-globe me-1.5 text-info"></i>Pesanan Online
                        <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill ms-1.5 px-2">{{ $customer->orders->count() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link small fw-semibold py-2.5 px-3" id="offline-tab" data-bs-toggle="tab"
                        data-bs-target="#offline-pane" type="button" role="tab" style="border-top-left-radius: 8px; border-top-right-radius: 8px;">
                        <i class="fas fa-store me-1.5 text-primary"></i>Penjualan POS Offline
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill ms-1.5 px-2">{{ $offlineSales->count() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link small fw-semibold py-2.5 px-3" id="receivable-tab" data-bs-toggle="tab"
                        data-bs-target="#receivable-pane" type="button" role="tab" style="border-top-left-radius: 8px; border-top-right-radius: 8px;">
                        <i class="fas fa-file-invoice-dollar me-1.5 text-danger"></i>Tagihan Piutang
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill ms-1.5 px-2">{{ $receivableSales->count() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link small fw-semibold py-2.5 px-3" id="balance-tab" data-bs-toggle="tab"
                        data-bs-target="#balance-pane" type="button" role="tab" style="border-top-left-radius: 8px; border-top-right-radius: 8px;">
                        <i class="fas fa-wallet me-1.5 text-success"></i>Mutasi Deposit
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill ms-1.5 px-2">{{ $customer->balanceTransactions->count() }}</span>
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="customerDetailTabsContent">
                {{-- TAB 1: RIWAYAT PESANAN ONLINE --}}
                <div class="tab-pane fade show active" id="orders-pane" role="tabpanel">
                    <div class="card border border-top-0 rounded-top-0 shadow-sm">
                        <div class="card-body p-3">
                            <div class="table-responsive rounded border">
                                <table class="table table-sm table-hover align-middle mb-0" style="font-size:0.8rem;">
                                    <thead class="table-light">
                                        <tr class="small text-uppercase text-muted">
                                            <th class="ps-3 py-2">TGL PESANAN</th>
                                            <th class="py-2">NO INVOICE / ID</th>
                                            <th class="py-2">STATUS</th>
                                            <th class="py-2 text-end pe-3">NILAI BERSIH (LTV)</th>
                                            <th class="py-2 text-center" style="width: 80px;">AKSI</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($customer->orders as $order)
                                            <tr>
                                                <td class="text-secondary ps-3" style="font-size:0.75rem;">{{ $order->order_date ? $order->order_date->format('d M Y, H:i') : '-' }}</td>
                                                <td>
                                                    <div class="fw-semibold text-dark">{{ $order->invoice_number ?? $order->order_marketplace_id }}</div>
                                                    <span class="text-muted small" style="font-size:0.68rem;">
                                                        {{ $order->items->count() }} item produk
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-{{ $order->status_badge }}-subtle text-{{ $order->status_badge }} border border-{{ $order->status_badge }}-subtle text-uppercase" style="font-size:0.68rem; padding:0.25em 0.5em;">
                                                        {{ str_replace('_', ' ', $order->order_status) }}
                                                    </span>
                                                </td>
                                                <td class="font-monospace fw-bold text-end pe-3 text-success">
                                                    Rp {{ number_format($order->net_amount, 0, ',', '.') }}
                                                </td>
                                                <td class="text-center">
                                                    <a href="{{ route('orders.show', $order->id) }}" class="btn btn-outline-primary btn-xs px-2 py-0.5" title="Detail Pesanan" data-bs-toggle="tooltip">
                                                        <i class="fas fa-eye small"></i> View
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center py-5">
                                                    <i class="fas fa-globe fa-2x mb-3 d-block text-secondary opacity-25"></i>
                                                    <p class="text-muted mb-0 small">Belum ada riwayat pesanan online.</p>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- TAB 2: RIWAYAT PENJUALAN OFFLINE (POS) --}}
                <div class="tab-pane fade" id="offline-pane" role="tabpanel">
                    <div class="card border border-top-0 rounded-top-0 shadow-sm">
                        <div class="card-body p-3">
                            <div class="table-responsive rounded border">
                                <table class="table table-sm table-hover align-middle mb-0" style="font-size:0.8rem;">
                                    <thead class="table-light">
                                        <tr class="small text-uppercase text-muted">
                                            <th class="ps-3 py-2">TGL TRANSAKSI</th>
                                            <th class="py-2">NO TRANSAKSI</th>
                                            <th class="py-2">METODE</th>
                                            <th class="py-2">STATUS BAYAR</th>
                                            <th class="py-2">STATUS ORDER</th>
                                            <th class="py-2 text-end pe-3">GRAND TOTAL</th>
                                            <th class="py-2 text-center" style="width: 80px;">AKSI</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($offlineSales as $sale)
                                            <tr>
                                                <td class="text-secondary ps-3" style="font-size:0.75rem;">{{ $sale->sold_at ? $sale->sold_at->format('d M Y, H:i') : '-' }}</td>
                                                <td>
                                                    <div class="fw-semibold text-dark">{{ $sale->sale_number }}</div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-secondary border px-2 py-0.5" style="font-size:0.68rem;">
                                                        {{ $sale->payment_method_label }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-{{ $sale->payment_status_badge }}-subtle text-{{ $sale->payment_status_badge }} border border-{{ $sale->payment_status_badge }}-subtle" style="font-size:0.68rem; padding:0.25em 0.5em;">
                                                        {{ $sale->payment_status_label }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-{{ $sale->status_badge }}-subtle text-{{ $sale->status_badge }} border border-{{ $sale->status_badge }}-subtle text-uppercase" style="font-size:0.68rem; padding:0.25em 0.5em;">
                                                        {{ $sale->status_label }}
                                                    </span>
                                                </td>
                                                <td class="font-monospace fw-bold text-end pe-3">
                                                    Rp {{ number_format($sale->grand_total, 0, ',', '.') }}
                                                </td>
                                                <td class="text-center">
                                                    <a href="{{ route('offline_sales.show', $sale->id) }}" class="btn btn-outline-primary btn-xs px-2 py-0.5" title="Detail Penjualan" data-bs-toggle="tooltip">
                                                        <i class="fas fa-eye small"></i> View
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center py-5">
                                                    <i class="fas fa-store fa-2x mb-3 d-block text-secondary opacity-25"></i>
                                                    <p class="text-muted mb-0 small">Belum ada riwayat penjualan offline (POS).</p>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- TAB 3: TAGIHAN PIUTANG --}}
                <div class="tab-pane fade" id="receivable-pane" role="tabpanel">
                    <div class="card border border-top-0 rounded-top-0 shadow-sm">
                        <div class="card-body p-3">
                            <div class="table-responsive rounded border">
                                <table class="table table-sm table-hover align-middle mb-0" style="font-size:0.8rem;">
                                    <thead class="table-light">
                                        <tr class="small text-uppercase text-muted">
                                            <th class="ps-3 py-2">TANGGAL POS</th>
                                            <th class="py-2">NO. TRANSAKSI</th>
                                            <th class="py-2 text-end">GRAND TOTAL</th>
                                            <th class="py-2 text-end text-success">SUDAH DIBAYAR</th>
                                            <th class="py-2 text-end text-danger fw-bold">SISA PIUTANG</th>
                                            <th class="py-2 text-center" style="width: 110px;">AKSI</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($receivableSales as $sale)
                                            @php $saleUnpaid = max(0, (float)$sale->grand_total - (float)$sale->paid_amount); @endphp
                                            <tr>
                                                <td class="text-secondary ps-3" style="font-size:0.75rem;">{{ $sale->sold_at ? $sale->sold_at->format('d M Y, H:i') : '-' }}</td>
                                                <td>
                                                    <a href="{{ route('offline_sales.show', $sale->id) }}" class="fw-semibold text-primary text-decoration-none">
                                                        {{ $sale->sale_number }}
                                                    </a>
                                                </td>
                                                <td class="font-monospace text-end text-dark">Rp {{ number_format($sale->grand_total, 0, ',', '.') }}</td>
                                                <td class="font-monospace text-end text-success">Rp {{ number_format($sale->paid_amount, 0, ',', '.') }}</td>
                                                <td class="font-monospace text-end text-danger fw-bold">Rp {{ number_format($saleUnpaid, 0, ',', '.') }}</td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-outline-success btn-xs px-2 py-0.5 btn-pay-single-sale" style="font-size:0.7rem; font-weight: 500;"
                                                        data-bs-toggle="modal" data-bs-target="#payReceivableModal"
                                                        data-sale-id="{{ $sale->id }}"
                                                        data-sale-number="{{ $sale->sale_number }}"
                                                        data-unpaid="{{ $saleUnpaid }}">
                                                        <i class="fas fa-money-bill-wave me-1"></i>Bayar
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-5">
                                                    <i class="fas fa-check-circle fa-2x mb-3 d-block text-success opacity-50"></i>
                                                    <p class="text-muted mb-0 small">Pelanggan ini tidak memiliki tunggakan piutang.</p>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- TAB 4: RIWAYAT DEPOSIT / MUTASI --}}
                <div class="tab-pane fade" id="balance-pane" role="tabpanel">
                    <div class="card border border-top-0 rounded-top-0 shadow-sm">
                        <div class="card-body p-3">
                            <div class="table-responsive rounded border">
                                <table class="table table-sm table-hover align-middle mb-0" style="font-size:0.8rem;">
                                    <thead class="table-light">
                                        <tr class="small text-uppercase text-muted">
                                            <th class="ps-3 py-2">TANGGAL</th>
                                            <th class="py-2">TIPE</th>
                                            <th class="py-2 text-end">NOMINAL</th>
                                            <th class="py-2">DESKRIPSI</th>
                                            <th class="py-2 text-center">PETUGAS</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($customer->balanceTransactions()->orderByDesc('created_at')->get() as $tx)
                                            <tr>
                                                <td class="text-secondary ps-3" style="font-size:0.75rem;">{{ $tx->created_at->format('d M Y, H:i') }}</td>
                                                <td>
                                                    <span class="badge bg-{{ $tx->type_badge }}-subtle text-{{ $tx->type_badge }} border border-{{ $tx->type_badge }}-subtle" style="font-size:0.68rem; padding:0.25em 0.5em;">
                                                        {{ $tx->type_label }}
                                                    </span>
                                                </td>
                                                <td class="font-monospace fw-bold text-end {{ $tx->type === 'in' ? 'text-success' : 'text-danger' }}">
                                                    {{ $tx->type === 'in' ? '+' : '-' }} Rp {{ number_format($tx->amount, 0, ',', '.') }}
                                                </td>
                                                <td class="text-dark" style="font-size:0.75rem;">{{ $tx->description }}</td>
                                                <td class="text-secondary text-center" style="font-size:0.75rem;">{{ $tx->user->name ?? '-' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center py-5">
                                                    <i class="fas fa-wallet fa-2x mb-3 d-block text-secondary opacity-25 text-success"></i>
                                                    <p class="text-muted mb-0 small">Belum ada riwayat mutasi saldo deposit.</p>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- Topup Modal --}}
    <div class="modal fade" id="topupModal" tabindex="-1" aria-labelledby="topupModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('customers.topup', $customer->id) }}" method="POST">
                @csrf
                <div class="modal-content text-start">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold text-dark" id="topupModalLabel"><i class="fas fa-wallet text-success me-2"></i>Sesuaikan Saldo Reseller</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-3">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Tipe Penyesuaian</label>
                            <select name="type" class="form-select">
                                <option value="in">Kredit / Top-up Tambah Saldo (+)</option>
                                <option value="out">Debit / Tarik Kurangi Saldo (-)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Nominal Penyesuaian (Rp)</label>
                            <input type="number" name="amount" step="0.01" min="0.01" class="form-control" placeholder="Contoh: 100000" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Keterangan / Deskripsi</label>
                            <input type="text" name="description" class="form-control" placeholder="Contoh: Deposit reseller via transfer BCA" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-success btn-sm px-3">Proses Transaksi</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    {{-- Modal Pelunasan Piutang Pelanggan --}}
    <div class="modal fade" id="payReceivableModal" tabindex="-1" aria-labelledby="payReceivableModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('customers.pay_receivable', $customer->id) }}" method="POST">
                @csrf
                <input type="hidden" name="offline_sale_id" id="modal_pay_offline_sale_id" value="">
                <div class="modal-content text-start border-0 shadow">
                    <div class="modal-header bg-success bg-opacity-10 border-bottom">
                        <h5 class="modal-title fw-bold text-success" id="payReceivableModalLabel">
                            <i class="fas fa-money-bill-wave me-2"></i>Pelunasan Piutang Pelanggan
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-3">
                        <div class="p-3 bg-light rounded border mb-3">
                            <div class="small text-muted mb-1">Pelanggan: <strong class="text-dark">{{ $customer->name }}</strong></div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="small fw-semibold text-danger">Total Tunggakan Piutang:</span>
                                <strong class="fs-5 font-monospace text-danger" id="modal_pay_total_unpaid">Rp {{ number_format($totalReceivable, 0, ',', '.') }}</strong>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Nominal Pembayaran Pelunasan (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="amount" id="modal_pay_amount" step="any" min="1" max="{{ $totalReceivable }}" class="form-control form-control-sm" value="{{ $totalReceivable }}" required>
                            <div class="form-text text-muted">Nominal yang diterima dari pelanggan untuk melunasi piutang.</div>
                        </div>

                        <div class="mb-3">
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
                </div>
            </form>
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
    document.querySelectorAll('#payReceivableModal form, #topupModal form').forEach(function (form) {
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
