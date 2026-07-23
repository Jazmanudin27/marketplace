@extends('layouts.app')

@section('title', 'Detail Transaksi — ' . $offlineSale->sale_number)
@section('page-title', 'Detail Penjualan Offline')

@section('content')
    <div class="row">
        <div class="col-md-12">

            {{-- HEADER --}}
            <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-success bg-opacity-10 text-success rounded border border-success border-opacity-10 d-flex align-items-center justify-content-center"
                        style="width:48px;height:48px;font-size:1.25rem;">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div>
                        <h4 class="mb-0 text-dark fw-bold">Detail Transaksi: {{ $offlineSale->sale_number }}</h4>
                        <p class="text-muted mb-0 small">Detail penjualan offline & status pembayaran</p>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('offline_sales.index') }}" class="btn btn-secondary btn-sm px-3">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                    <a href="{{ route('offline_sales.print', $offlineSale->id) }}" target="_blank"
                        class="btn btn-primary btn-sm px-3 text-white">
                        <i class="fas fa-print me-1"></i> Cetak Struk
                    </a>
                    @if ($offlineSale->status === \App\Models\OfflineSale::STATUS_PENDING_APPROVAL && (auth()->user()->canDo('offline-sales.approve') || auth()->user()->isAdmin() || auth()->user()->isOwner() || in_array(auth()->user()->role, ['admin', 'owner', 'warehouse', 'gudang'])))
                        <button type="button" class="btn btn-success btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modalApproveShow">
                            <i class="fas fa-check-circle me-1"></i> Setujui (Approve)
                        </button>
                    @endif
                    @if ($offlineSale->status !== \App\Models\OfflineSale::STATUS_CANCELLED && !$offlineSale->is_paid)
                        <button type="button" class="btn btn-outline-success btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modalMarkPaidShow">
                            <i class="fas fa-money-bill-wave me-1"></i> Tandai Lunas
                        </button>
                    @endif
                    @if ($offlineSale->status === \App\Models\OfflineSale::STATUS_COMPLETED)
                        <button type="button" class="btn btn-warning btn-sm px-3 text-dark fw-bold" data-bs-toggle="modal" data-bs-target="#modalReturnShow">
                            <i class="fas fa-undo me-1"></i> Retur Barang
                        </button>
                    @endif
                    @if (in_array($offlineSale->status, [\App\Models\OfflineSale::STATUS_COMPLETED, \App\Models\OfflineSale::STATUS_PENDING_APPROVAL]))
                        <button type="button" class="btn btn-danger btn-sm px-3"
                            data-bs-toggle="modal" data-bs-target="#modalCancelShow"
                            data-status="{{ $offlineSale->status }}">
                            <i class="fas fa-times-circle me-1"></i> Batalkan
                        </button>
                    @endif
                </div>
            </div>

            {{-- Banner Menunggu Approval --}}
            @if ($offlineSale->status === \App\Models\OfflineSale::STATUS_PENDING_APPROVAL)
                <div class="alert alert-warning d-flex align-items-center gap-3 mb-4 py-3">
                    <i class="fas fa-hourglass-half fa-lg text-warning"></i>
                    <div>
                        <strong>Menunggu Persetujuan Gudang</strong><br>
                        <small class="text-muted">Transaksi ini belum disetujui. Stok belum dikurangi. Hubungi bagian Gudang untuk melakukan approval.</small>
                    </div>
                </div>
            @endif
            <div class="row g-3">
                {{-- LEFT: Item detail --}}
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm mb-3">
                        <div
                            class="card-header bg-light py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0 text-dark">
                                <i class="fas fa-info-circle me-2 text-primary"></i>Informasi Transaksi
                            </h6>
                            <span
                                class="badge bg-{{ $offlineSale->status_badge }} bg-opacity-10 text-{{ $offlineSale->status_badge }} border border-{{ $offlineSale->status_badge }} border-opacity-10 small text-uppercase">
                                {{ $offlineSale->status_label }}
                            </span>
                        </div>
                        <div class="card-body p-3">
                            {{-- info row --}}
                            <div class="row g-2 mb-4">
                                <div class="col-md-6">
                                    <div class="p-3 border rounded h-100 bg-light">
                                        <small class="text-muted d-block text-uppercase fw-semibold mb-1 small"
                                            style="font-size: 0.65rem;">Pembeli</small>
                                        <span class="fw-bold text-dark small">
                                            @if ($offlineSale->customer_id)
                                                <a href="{{ route('customers.show', $offlineSale->customer_id) }}"
                                                    class="text-decoration-none text-primary fw-bold">
                                                    {{ $offlineSale->buyer_name ?: '(Umum)' }} <i
                                                        class="fas fa-external-link-alt ms-1 small"></i>
                                                </a>
                                            @else
                                                {{ $offlineSale->buyer_name ?: '(Umum)' }}
                                            @endif
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-3 border rounded h-100 bg-light">
                                        <small class="text-muted d-block text-uppercase fw-semibold mb-1 small"
                                            style="font-size: 0.65rem;">No. HP Pembeli</small>
                                        <span
                                            class="font-monospace fw-semibold text-dark small">{{ $offlineSale->buyer_phone ?? '-' }}</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-3 border rounded h-100 bg-light">
                                        <small class="text-muted d-block text-uppercase fw-semibold mb-1 small"
                                            style="font-size: 0.65rem;">Kasir</small>
                                        <span class="fw-bold text-dark small">{{ $offlineSale->user->name ?? '-' }}</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-3 border rounded h-100 bg-light">
                                        <small class="text-muted d-block text-uppercase fw-semibold mb-1 small"
                                            style="font-size: 0.65rem;">Metode Pembayaran</small>
                                        <span
                                            class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-10 small fw-medium mt-1">
                                            {{ $offlineSale->payment_method_label }}
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-3 border rounded h-100 bg-light">
                                        <small class="text-muted d-block text-uppercase fw-semibold mb-1 small"
                                            style="font-size: 0.65rem;">Status Pembayaran</small>
                                        <span
                                            class="badge bg-{{ $offlineSale->payment_status_badge }} bg-opacity-10 text-{{ $offlineSale->payment_status_badge }} border border-{{ $offlineSale->payment_status_badge }} border-opacity-10 small fw-semibold mt-1">
                                            <i class="fas fa-{{ $offlineSale->is_paid ? 'check-circle' : 'exclamation-circle' }} me-1"></i>
                                            {{ $offlineSale->payment_status_label }}
                                        </span>
                                    </div>
                                </div>
                                @if ($offlineSale->payment_destination)
                                    <div class="col-md-4">
                                        <div class="p-3 border rounded h-100 bg-light">
                                            <small class="text-muted d-block text-uppercase fw-semibold mb-1 small"
                                                style="font-size: 0.65rem;">Kas / Bank Tujuan</small>
                                            <span
                                                class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-10 small fw-medium mt-1">
                                                {{ $offlineSale->payment_destination === 'kas_kecil' ? 'Kas Kecil (Operasional)' : ($offlineSale->payment_destination === 'kas_besar' ? 'Kas Besar (Utama)' : $offlineSale->payment_destination) }}
                                            </span>
                                        </div>
                                    </div>
                                @endif
                                <div class="col-md-4">
                                    <div class="p-3 border rounded h-100 bg-light">
                                        <small class="text-muted d-block text-uppercase fw-semibold mb-1 small"
                                            style="font-size: 0.65rem;">Waktu Transaksi</small>
                                        <span
                                            class="fw-semibold text-dark small">{{ $offlineSale->sold_at?->format('d M Y, H:i') ?? '-' }}</span>
                                    </div>
                                </div>
                                @if ($offlineSale->customer && $offlineSale->customer->address)
                                    <div class="col-md-12">
                                        <div class="p-3 border rounded h-100 bg-light">
                                            <small class="text-muted d-block text-uppercase fw-semibold mb-1 small"
                                                style="font-size: 0.65rem;">Alamat Pembeli</small>
                                            <span
                                                class="text-secondary text-wrap small">{{ $offlineSale->customer->address }}</span>
                                        </div>
                                    </div>
                                @endif
                                @if ($offlineSale->is_dropship)
                                    <div class="col-md-12">
                                        <div class="p-3 border border-warning rounded h-100 bg-warning bg-opacity-10">
                                            <small class="text-warning-emphasis d-block text-uppercase fw-bold mb-2"
                                                style="font-size: 0.65rem;">
                                                <i class="fas fa-shipping-fast me-1"></i> Informasi Dropshipper
                                            </small>
                                            <div class="row g-2">
                                                <div class="col-md-6 text-dark small">
                                                    <span class="text-muted">Nama Pengirim:</span>
                                                    <strong>{{ $offlineSale->dropshipper_name ?? '-' }}</strong>
                                                </div>
                                                <div class="col-md-6 text-dark small">
                                                    <span class="text-muted">No. Telepon:</span> <strong
                                                        class="font-monospace text-dark">{{ $offlineSale->dropshipper_phone ?? '-' }}</strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                @if ($offlineSale->status === \App\Models\OfflineSale::STATUS_CANCELLED && $offlineSale->cancellation_reason)
                                    <div class="col-md-12">
                                        <div class="p-3 border border-danger rounded h-100 bg-danger bg-opacity-10">
                                            <small class="text-danger d-block text-uppercase fw-bold mb-1"
                                                style="font-size: 0.65rem;">
                                                <i class="fas fa-times-circle me-1"></i> Alasan Pembatalan
                                            </small>
                                            <span class="text-dark small">{{ $offlineSale->cancellation_reason }}</span>
                                        </div>
                                    </div>
                                @endif
                                @if ($offlineSale->notes)
                                    <div class="col-md-12">
                                        <div class="p-3 border rounded h-100 bg-light">
                                            <small class="text-muted d-block text-uppercase fw-semibold mb-1 small"
                                                style="font-size: 0.65rem;">Catatan</small>
                                            <span class="text-secondary text-wrap small">{{ $offlineSale->notes }}</span>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            {{-- Table Item --}}
                            <div class="d-flex align-items-center mb-3">
                                <h6 class="fw-bold mb-0 text-dark" style="font-size:0.9rem;"><i
                                        class="fas fa-box me-2 text-primary"></i>Item Yang Dijual</h6>
                            </div>
                            <div class="table-responsive rounded border">
                                <table class="table table-sm table-bordered table-striped align-middle mb-0 text-dark">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3">PRODUK</th>
                                            <th>SKU</th>
                                            <th class="text-center">QTY</th>
                                            <th class="text-end">HARGA SATUAN</th>
                                            <th class="text-end">DISKON ITEM</th>
                                            <th class="text-end">SUBTOTAL</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($offlineSale->items as $item)
                                            <tr>
                                                <td class="ps-3">
                                                    <strong class="text-dark small">{{ $item->product_name }}</strong>
                                                    @if($item->returned_quantity > 0)
                                                        <span class="badge bg-warning text-dark ms-1" style="font-size:0.65rem;">Diretur {{ $item->returned_quantity }}x</span>
                                                    @endif
                                                </td>
                                                <td><code
                                                        class="text-primary font-monospace small">{{ $item->sku ?? '-' }}</code>
                                                </td>
                                                <td class="text-center small">{{ $item->quantity }}</td>
                                                <td class="text-end font-monospace small">Rp
                                                    {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                                <td class="text-end font-monospace text-danger small">
                                                    @if($item->discount_amount > 0)
                                                        - Rp {{ number_format($item->discount_amount, 0, ',', '.') }}
                                                        <span class="text-muted d-block" style="font-size:0.65rem;">
                                                            ({{ $item->discount_type === 'percentage' ? number_format($item->discount_value, 0).'% / unit' : 'Rp '.number_format($item->discount_value, 0, ',', '.').' / unit' }})
                                                        </span>
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td class="text-end font-monospace text-success fw-bold small">Rp
                                                    {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- RIGHT: Ringkasan --}}
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-light py-2 px-3 border-bottom mb-3">
                            <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-wallet me-2 text-success"></i>Ringkasan
                                Pembayaran</h6>
                        </div>
                        <div class="card-body p-3">
                            <div class="p-3 border rounded bg-light mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted small">Subtotal</span>
                                    <span class="font-monospace text-dark small">Rp
                                        {{ number_format($offlineSale->total_amount, 0, ',', '.') }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted small">Diskon Transaksi</span>
                                    <span class="font-monospace text-danger small">- Rp
                                        {{ number_format($offlineSale->discount_amount, 0, ',', '.') }}
                                        @if($offlineSale->discount_type === 'percentage' && $offlineSale->discount_value > 0)
                                            ({{ number_format($offlineSale->discount_value, 0) }}%)
                                        @endif
                                    </span>
                                </div>
                                <hr class="my-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-dark fw-bold small">Grand Total</span>
                                    <span class="font-monospace text-success fw-bold fs-5">Rp
                                        {{ number_format($offlineSale->grand_total, 0, ',', '.') }}</span>
                                </div>
                            </div>

                            <div class="p-3 border rounded bg-light">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted small">Dibayar</span>
                                    <span class="font-monospace text-dark small">Rp
                                        {{ number_format($offlineSale->paid_amount, 0, ',', '.') }}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted small">Kembalian</span>
                                    <span class="font-monospace text-primary fw-bold small">Rp
                                        {{ number_format($offlineSale->change_amount, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Riwayat Retur Penjualan --}}
            @if($offlineSale->returns->isNotEmpty())
                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-header bg-warning bg-opacity-10 py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0 text-warning-emphasis">
                            <i class="fas fa-undo me-2"></i>Riwayat Retur Penjualan
                        </h6>
                        <span class="badge bg-warning text-dark">{{ $offlineSale->returns->count() }}x Retur</span>
                    </div>
                    <div class="card-body p-3">
                        @foreach($offlineSale->returns as $ret)
                            <div class="p-3 border rounded mb-3 bg-light">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <strong class="font-monospace text-dark">{{ $ret->return_number }}</strong>
                                        <small class="text-muted ms-2">{{ $ret->returned_at ? $ret->returned_at->format('d M Y, H:i') : '' }}</small>
                                    </div>
                                    <span class="badge bg-warning text-dark font-monospace">Total Refund: Rp {{ number_format($ret->total_return_amount, 0, ',', '.') }}</span>
                                </div>
                                <div class="small text-muted mb-2">
                                    Metode Refund: <strong>{{ ucfirst($ret->refund_method) }}</strong> &bull; Alasan: <em>"{{ $ret->reason }}"</em> &bull; Petugas: {{ $ret->user->name ?? '-' }}
                                </div>
                                <div class="table-responsive rounded border">
                                    <table class="table table-sm table-bordered bg-white mb-0" style="font-size:0.78rem;">
                                        <thead class="table-light">
                                            <tr>
                                                <th>PRODUK</th>
                                                <th class="text-center">QTY RETUR</th>
                                                <th class="text-end">HARGA SATUAN</th>
                                                <th class="text-end">SUBTOTAL REFUND</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($ret->items as $rItem)
                                                <tr>
                                                    <td>{{ $rItem->offlineSaleItem->product_name ?? 'Produk' }}</td>
                                                    <td class="text-center fw-bold text-danger">{{ $rItem->quantity }}x</td>
                                                    <td class="text-end font-monospace">Rp {{ number_format($rItem->unit_price, 0, ',', '.') }}</td>
                                                    <td class="text-end font-monospace text-danger fw-bold">Rp {{ number_format($rItem->subtotal, 0, ',', '.') }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
@endsection

{{-- Modal Konfirmasi Approve --}}
@push('modals')
@if($offlineSale->status === \App\Models\OfflineSale::STATUS_PENDING_APPROVAL && (auth()->user()->canDo('offline-sales.approve') || auth()->user()->isAdmin() || auth()->user()->isOwner() || in_array(auth()->user()->role, ['admin', 'owner', 'warehouse', 'gudang'])))
<div class="modal fade" id="modalApproveShow" tabindex="-1" aria-labelledby="modalApproveShowLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success bg-opacity-10 border-bottom">
                <h6 class="modal-title fw-bold text-success" id="modalApproveShowLabel">
                    <i class="fas fa-check-circle me-2"></i>Konfirmasi Persetujuan Transaksi
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('offline_sales.approve', $offlineSale->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <p class="mb-1 text-dark">Yakin ingin menyetujui (approve) transaksi:</p>
                    <p class="fw-bold font-monospace text-success mb-3">{{ $offlineSale->sale_number }}</p>

                    <div class="mb-3">
                        <label for="approve_payment_destination_show" class="form-label fw-semibold small text-dark mb-1">
                            <i class="fas fa-university me-1 text-primary"></i> Kas / Bank Tujuan Pemasukan <span class="text-danger">*</span>
                        </label>
                        <select name="payment_destination" id="approve_payment_destination_show" class="form-select form-select-sm" required>
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
                        <div class="form-text text-muted">Uang pembayaran akan masuk ke akun kas/bank yang dipilih.</div>
                    </div>

                    <div class="alert alert-success py-2 mb-0 small">
                        <i class="fas fa-boxes me-1"></i> Stok produk akan <strong>dikurangi</strong> & pemasukan dicatat ke Kas/Bank.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-success btn-sm px-4">
                        <i class="fas fa-check me-1"></i> Ya, Setujui & Catat Pemasukan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- Modal Konfirmasi Pelunasan --}}
@push('modals')
@if($offlineSale->status !== \App\Models\OfflineSale::STATUS_CANCELLED && !$offlineSale->is_paid)
<div class="modal fade" id="modalMarkPaidShow" tabindex="-1" aria-labelledby="modalMarkPaidShowLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success bg-opacity-10 border-bottom">
                <h6 class="modal-title fw-bold text-success" id="modalMarkPaidShowLabel">
                    <i class="fas fa-money-bill-wave me-2"></i>Pelunasan Pembayaran
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('offline_sales.mark_paid', $offlineSale->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <p class="mb-1 text-dark">Tandai lunas untuk transaksi:</p>
                    <p class="fw-bold font-monospace text-primary mb-3">{{ $offlineSale->sale_number }}</p>

                    <div class="p-3 bg-light rounded border mb-3">
                        <div class="d-flex justify-content-between mb-1 small">
                            <span class="text-muted">Total Transaksi:</span>
                            <strong class="font-monospace text-dark">Rp {{ number_format($offlineSale->grand_total, 0, ',', '.') }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1 small">
                            <span class="text-muted">Sudah Dibayar:</span>
                            <span class="font-monospace text-secondary">Rp {{ number_format($offlineSale->paid_amount, 0, ',', '.') }}</span>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between small">
                            <span class="fw-bold text-danger">Sisa Kekurangan:</span>
                            <strong class="font-monospace text-danger fs-6">Rp {{ number_format(max(0, $offlineSale->grand_total - $offlineSale->paid_amount), 0, ',', '.') }}</strong>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="paid_payment_destination_show" class="form-label fw-semibold small text-dark mb-1">
                            <i class="fas fa-university me-1 text-primary"></i> Kas / Bank Tujuan Pelunasan <span class="text-danger">*</span>
                        </label>
                        <select name="payment_destination" id="paid_payment_destination_show" class="form-select form-select-sm" required>
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

                    <div class="alert alert-info py-2 mb-0 small">
                        <i class="fas fa-check-circle me-1"></i> Sisa kekurangan akan dicatat sebagai <strong>Lunas</strong> dan dimasukkan ke Kas/Bank pilihan Anda.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-success btn-sm px-4">
                        <i class="fas fa-check-circle me-1"></i> Konfirmasi Pelunasan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- Modal Konfirmasi Pembatalan --}}
@push('modals')
<div class="modal fade" id="modalCancelShow" tabindex="-1" aria-labelledby="modalCancelShowLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger bg-opacity-10 border-bottom">
                <h6 class="modal-title fw-bold text-danger" id="modalCancelShowLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Konfirmasi Pembatalan Transaksi
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('offline_sales.cancel', $offlineSale->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <p class="mb-1 text-dark">Yakin ingin membatalkan transaksi:</p>
                    <p class="fw-bold font-monospace text-danger mb-3">{{ $offlineSale->sale_number }}</p>
                    <div class="alert py-2 mb-3 small" id="show-cancel-note"></div>
                    <div>
                        <label for="cancellation_reason_show" class="form-label fw-semibold small text-dark mb-1">
                            Alasan Pembatalan <span class="text-danger">*</span>
                        </label>
                        <textarea name="cancellation_reason" id="cancellation_reason_show" rows="3"
                            class="form-control form-control-sm"
                            placeholder="Contoh: Pelanggan membatalkan pesanan, stok habis, dll..."
                            required minlength="5" maxlength="500"></textarea>
                        <div class="form-text text-muted">Wajib diisi, minimal 5 karakter.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-danger btn-sm px-4">
                        <i class="fas fa-times-circle me-1"></i> Ya, Batalkan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endpush

{{-- Modal Retur Barang --}}
@push('modals')
@if($offlineSale->status === \App\Models\OfflineSale::STATUS_COMPLETED)
<div class="modal fade" id="modalReturnShow" tabindex="-1" aria-labelledby="modalReturnShowLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning bg-opacity-10 border-bottom">
                <h6 class="modal-title fw-bold text-dark" id="modalReturnShowLabel">
                    <i class="fas fa-undo me-2 text-warning"></i>Form Retur Sebagian / Seluruh Barang POS
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('offline_sales.return', $offlineSale->id) }}" method="POST" class="m-0">
                @csrf
                <div class="modal-body p-3">
                    <div class="alert alert-warning py-2 mb-3 small">
                        <i class="fas fa-info-circle me-1"></i> Pilih jumlah produk yang akan diretur. Produk yang diretur akan <strong>otomatis dikembalikan ke stok gudang</strong>.
                    </div>

                    <div class="table-responsive rounded border mb-3">
                        <table class="table table-sm table-bordered align-middle mb-0" style="font-size:0.8rem;">
                            <thead class="table-light">
                                <tr>
                                    <th>PRODUK</th>
                                    <th class="text-center">HARGA EFEKTIF</th>
                                    <th class="text-center">QTY DIBELI</th>
                                    <th class="text-center">SISA BISA DIRETUR</th>
                                    <th class="text-center" style="width:130px;">QTY RETUR</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($offlineSale->items as $sItem)
                                    @php
                                        $remQty = $sItem->remaining_quantity;
                                        $effectivePrice = $sItem->quantity > 0 ? ($sItem->subtotal / $sItem->quantity) : 0;
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-dark">{{ $sItem->product_name }}</div>
                                            <div class="text-muted" style="font-size:0.7rem;">{{ $sItem->sku }}</div>
                                        </td>
                                        <td class="text-center font-monospace">Rp {{ number_format($effectivePrice, 0, ',', '.') }}</td>
                                        <td class="text-center font-monospace">{{ $sItem->quantity }}x</td>
                                        <td class="text-center font-monospace fw-semibold {{ $remQty > 0 ? 'text-success' : 'text-danger' }}">{{ $remQty }}x</td>
                                        <td class="text-center">
                                            @if($remQty > 0)
                                                <input type="number" name="returns[{{ $sItem->id }}]" class="form-control form-control-sm text-center font-monospace input-ret-qty" min="0" max="{{ $remQty }}" value="0">
                                            @else
                                                <span class="badge bg-secondary">Sudah Habis Diretur</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="refund_method" class="form-label fw-semibold small text-dark mb-1">
                                Metode Pengembalian Dana (Refund) <span class="text-danger">*</span>
                            </label>
                            <select name="refund_method" id="refund_method" class="form-select form-select-sm" required>
                                <option value="cash">Tunai (Kas Tunai)</option>
                                <option value="bank">Transfer Bank</option>
                                @if($offlineSale->customer_id)
                                    <option value="customer_balance">Tambah Deposit Saldo Pelanggan ({{ $offlineSale->buyer_name }})</option>
                                @endif
                                <option value="no_refund">Tanpa Refund Uang (Hanya Retur Fisik Barang)</option>
                            </select>
                        </div>
                        <div class="col-md-6" id="refund-bank-wrapper">
                            <label for="refund_payment_destination" class="form-label fw-semibold small text-dark mb-1">
                                Kas / Bank Pengeluaran Refund <span class="text-danger">*</span>
                            </label>
                            <select name="payment_destination" id="refund_payment_destination" class="form-select form-select-sm">
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
                        <div class="col-md-12">
                            <label for="return_reason" class="form-label fw-semibold small text-dark mb-1">
                                Alasan Retur Barang <span class="text-danger">*</span>
                            </label>
                            <textarea name="reason" id="return_reason" rows="2" class="form-control form-control-sm" placeholder="Contoh: Barang cacat/rusak, tukar ukuran, dll..." required minlength="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-warning btn-sm px-4 text-dark fw-bold">
                        <i class="fas fa-undo me-1"></i> Proses Retur Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalCancelShow = document.getElementById('modalCancelShow');
    if (modalCancelShow) {
        modalCancelShow.addEventListener('show.bs.modal', function (event) {
            const btn = event.relatedTarget;
            const status = btn ? btn.getAttribute('data-status') : '';
            const noteEl = document.getElementById('show-cancel-note');
            if (status === 'pending_approval') {
                noteEl.innerHTML = '<i class="fas fa-info-circle me-1"></i> Transaksi belum diapprove. Stok <strong>tidak akan</strong> berubah.';
                noteEl.className = 'alert alert-info py-2 mb-3 small';
            } else {
                noteEl.innerHTML = '<i class="fas fa-undo me-1"></i> Stok semua produk akan <strong>dikembalikan</strong> secara otomatis.';
                noteEl.className = 'alert alert-warning py-2 mb-3 small';
            }
        });
    }

    // Loading state saat submit
    document.querySelectorAll('#modalApproveShow form, #modalCancelShow form, #modalMarkPaidShow form, #modalReturnShow form').forEach(function (form) {
        form.addEventListener('submit', function () {
            const btn = form.querySelector('button[type="submit"]');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Memproses...';
            }
        });
    });

    const refundMethodSelect = document.getElementById('refund_method');
    const refundBankWrapper = document.getElementById('refund-bank-wrapper');
    if (refundMethodSelect && refundBankWrapper) {
        refundMethodSelect.addEventListener('change', function () {
            if (this.value === 'cash' || this.value === 'bank') {
                refundBankWrapper.style.display = 'block';
            } else {
                refundBankWrapper.style.display = 'none';
            }
        });
    }
});
</script>
@endpush
