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

            <div class="card border shadow-sm p-3">
                <h6 class="fw-bold mb-3 text-dark"><i class="fas fa-chart-pie me-2 text-info"></i>Ringkasan Nilai</h6>
                
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-secondary small">Total Transaksi</span>
                    <span class="font-monospace fw-bold small text-dark">{{ $customer->orders->count() }}x</span>
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
            <ul class="nav nav-tabs mb-0" id="customerDetailTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active small fw-semibold" id="orders-tab" data-bs-toggle="tab"
                        data-bs-target="#orders-pane" type="button" role="tab">
                        <i class="fas fa-history me-1 text-info"></i>Riwayat Pesanan
                        <span class="badge bg-info ms-1">{{ $customer->orders->count() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link small fw-semibold" id="balance-tab" data-bs-toggle="tab"
                        data-bs-target="#balance-pane" type="button" role="tab">
                        <i class="fas fa-wallet me-1 text-success"></i>Mutasi Saldo Deposit
                        <span class="badge bg-success ms-1">{{ $customer->balanceTransactions->count() }}</span>
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="customerDetailTabsContent">
                {{-- TAB 1: RIWAYAT PESANAN --}}
                <div class="tab-pane fade show active" id="orders-pane" role="tabpanel">
                    <div class="card border border-top-0 rounded-top-0 shadow-sm">
                        <div class="card-body p-3">
                            <div class="table-responsive rounded border">
                                <table class="table table-sm table-striped table-bordered align-middle mb-0">
                                    <thead>
                                        <tr class="small text-uppercase">
                                            <th>TGL PESANAN</th>
                                            <th>NO INVOICE / ID</th>
                                            <th>STATUS</th>
                                            <th class="text-end">NILAI BERSIH (LTV)</th>
                                            <th class="text-center" style="width: 100px;">AKSI</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($customer->orders as $order)
                                            <tr>
                                                <td style="font-size:0.78rem;" class="text-secondary">{{ $order->order_date->format('d M Y, H:i') }}</td>
                                                <td>
                                                    <div class="fw-semibold text-dark" style="font-size:0.82rem;">{{ $order->invoice_number ?? $order->order_marketplace_id }}</div>
                                                    <span class="text-secondary small" style="font-size:0.7rem;">
                                                        {{ $order->items->count() }} item produk
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-{{ $order->status_badge }} bg-opacity-10 text-{{ $order->status_badge }} border border-{{ $order->status_badge }} border-opacity-10 small text-uppercase">
                                                        {{ str_replace('_', ' ', $order->order_status) }}
                                                    </span>
                                                </td>
                                                <td class="font-monospace fw-semibold text-success text-end" style="font-size:0.78rem;">
                                                    Rp {{ number_format($order->net_amount, 0, ',', '.') }}
                                                </td>
                                                <td class="text-center">
                                                    <a href="{{ route('orders.show', $order->id) }}" class="btn btn-info btn-sm text-white" title="Detail Pesanan" data-bs-toggle="tooltip">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center py-5">
                                                    <i class="fas fa-history fa-2x mb-3 d-block text-secondary opacity-25"></i>
                                                    <p class="text-muted mb-0 small">Belum ada riwayat pesanan yang valid.</p>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- TAB 2: RIWAYAT DEPOSIT / MUTASI --}}
                <div class="tab-pane fade" id="balance-pane" role="tabpanel">
                    <div class="card border border-top-0 rounded-top-0 shadow-sm">
                        <div class="card-body p-3">
                            <div class="table-responsive rounded border">
                                <table class="table table-sm table-striped table-bordered align-middle mb-0" style="font-size:0.8rem;">
                                    <thead class="table-light">
                                        <tr class="small text-uppercase">
                                            <th>TANGGAL</th>
                                            <th>TIPE</th>
                                            <th class="text-end">NOMINAL</th>
                                            <th>DESKRIPSI</th>
                                            <th>PETUGAS</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($customer->balanceTransactions()->orderByDesc('created_at')->get() as $tx)
                                            <tr>
                                                <td class="text-secondary small">{{ $tx->created_at->format('d M Y, H:i') }}</td>
                                                <td>
                                                    <span class="badge bg-{{ $tx->type_badge }} bg-opacity-10 text-{{ $tx->type_badge }} border border-{{ $tx->type_badge }} border-opacity-10 small">
                                                        {{ $tx->type_label }}
                                                    </span>
                                                </td>
                                                <td class="font-monospace fw-bold text-end {{ $tx->type === 'in' ? 'text-success' : 'text-danger' }}">
                                                    {{ $tx->type === 'in' ? '+' : '-' }} Rp {{ number_format($tx->amount, 0, ',', '.') }}
                                                </td>
                                                <td class="text-dark small" style="font-size:0.75rem;">{{ $tx->description }}</td>
                                                <td class="text-secondary small">{{ $tx->user->name ?? '-' }}</td>
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
@endsection
