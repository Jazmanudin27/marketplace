@extends('layouts.app')

@section('title', 'Penjualan Offline')
@section('page-title', 'Penjualan Offline')

@section('content')

    {{-- SUMMARY CARDS --}}
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-3">
                    <div class="fs-4 fw-bold text-success">{{ number_format($summary->total_count ?? 0) }}</div>
                    <div class="text-muted small">Total Transaksi Selesai</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-3">
                    <div class="fs-5 fw-bold text-primary font-monospace">Rp
                        {{ number_format($summary->total_revenue ?? 0, 0, ',', '.') }}</div>
                    <div class="text-muted small">Total Pendapatan Offline</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-3">
                    <div class="fs-5 fw-bold text-warning font-monospace">
                        Rp
                        {{ $summary->total_count > 0 ? number_format(($summary->total_revenue ?? 0) / $summary->total_count, 0, ',', '.') : '0' }}
                    </div>
                    <div class="text-muted small">Rata-rata per Transaksi</div>
                </div>
            </div>
        </div>
    </div>

    {{-- FILTER --}}

    {{-- TABLE CARD --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-info bg-opacity-10 d-flex justify-content-between align-items-center py-2 px-3">
            <div>
                <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-store-alt me-2 text-info"></i>Daftar Penjualan Offline
                </h6>
                <small class="text-muted d-block">Kelola data penjualan manual yang terjadi secara langsung</small>
            </div>
            <a href="{{ route('offline_sales.create') }}" class="btn btn-primary btn-sm px-3">
                <i class="fas fa-plus me-1"></i> Transaksi Baru
            </a>
        </div>
        @if (!empty($overdueFollowUpCount) && $overdueFollowUpCount > 0)
            <div class="alert alert-danger d-flex align-items-center justify-content-between mx-3 mt-3 mb-0 py-2 px-3 border-danger shadow-sm">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-exclamation-triangle fa-lg text-danger"></i>
                    <div>
                        <strong>Peringatan Follow Up:</strong> Terdapat <strong>{{ $overdueFollowUpCount }}</strong> pesanan PO yang telah melewati batas tanggal follow up dan belum membayar DP!
                    </div>
                </div>
                <a href="{{ route('offline_sales.index', ['status' => 'perlu_follow_up']) }}" class="btn btn-danger btn-sm text-nowrap">
                    <i class="fas fa-filter me-1"></i> Lihat Pesanan Overdue
                </a>
            </div>
        @endif
        <div class="card-body p-0">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body py-2 px-3">
                    <form method="GET" class="row g-2 align-items-end">
                        <div class="col-12 col-sm-6 col-md-3">
                            <label class="form-label form-label-sm fw-semibold mb-1">
                                <i class="fas fa-search me-1"></i>Cari
                            </label>
                            <input type="text" name="search" class="form-control form-control-sm"
                                placeholder="No. transaksi / nama..." value="{{ request('search') }}">
                        </div>
                        <div class="col-12 col-sm-6 col-md-2">
                            <label class="form-label form-label-sm fw-semibold mb-1">
                                <i class="fas fa-info-circle me-1"></i>Status
                            </label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="">Semua Status</option>
                                <option value="menunggu_dp" {{ request('status') === 'menunggu_dp' ? 'selected' : '' }}>Menunggu DP Masuk</option>
                                <option value="perlu_follow_up" {{ request('status') === 'perlu_follow_up' ? 'selected' : '' }}>⚠️ Perlu Follow Up (Overdue DP)</option>
                                <option value="belum_spk" {{ request('status') === 'belum_spk' ? 'selected' : '' }}>Belum dibuat SPK</option>
                                <option value="spk_diproses" {{ request('status') === 'spk_diproses' ? 'selected' : '' }}>SPK Sedang Diproses</option>
                                <option value="pending_approval" {{ request('status') === 'pending_approval' ? 'selected' : '' }}>Menunggu Approval</option>
                                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Selesai</option>
                                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-md-2">
                            <label class="form-label form-label-sm fw-semibold mb-1">
                                <i class="fas fa-tags me-1"></i>Jenis Transaksi
                            </label>
                            <select name="payment_method" class="form-select form-select-sm">
                                <option value="">Semua Jenis</option>
                                <option value="tunai" {{ request('payment_method') === 'tunai' ? 'selected' : '' }}>Tunai
                                </option>
                                <option value="piutang"
                                    {{ in_array(request('payment_method'), ['piutang', 'kredit']) ? 'selected' : '' }}>
                                    Kredit</option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-md-2">
                            <label class="form-label form-label-sm fw-semibold mb-1">
                                <i class="fas fa-money-bill-wave me-1"></i>Pembayaran
                            </label>
                            <select name="payment_status" class="form-select form-select-sm">
                                <option value="">Semua Pembayaran</option>
                                <option value="lunas" {{ request('payment_status') === 'lunas' ? 'selected' : '' }}>Lunas
                                </option>
                                <option value="belum_lunas"
                                    {{ request('payment_status') === 'belum_lunas' ? 'selected' : '' }}>Belum Lunas / Cicil
                                </option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-md-2">
                            <label class="form-label form-label-sm fw-semibold mb-1">
                                <i class="fas fa-calendar-alt me-1"></i>Dari Tanggal
                            </label>
                            <input type="date" name="date_from" class="form-control form-control-sm"
                                value="{{ request('date_from') }}">
                        </div>
                        <div class="col-12 col-sm-6 col-md-2">
                            <label class="form-label form-label-sm fw-semibold mb-1">
                                <i class="fas fa-calendar-alt me-1"></i>Sampai
                            </label>
                            <input type="date" name="date_to" class="form-control form-control-sm"
                                value="{{ request('date_to') }}">
                        </div>
                        <div class="col-12 col-sm-6 col-md-auto d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm px-3">
                                <i class="fas fa-search me-1"></i> Cari
                            </button>
                            @if (request()->hasAny(['search', 'status', 'payment_method', 'payment_status', 'date_from', 'date_to']))
                                <a href="{{ route('offline_sales.index') }}" class="btn btn-outline-secondary btn-sm px-2">
                                    <i class="fas fa-times"></i>
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            <style>
                .table-offline th {
                    padding: 0.85rem 0.75rem !important;
                    font-size: 0.78rem;
                    font-weight: 700;
                    letter-spacing: 0.03em;
                    text-transform: uppercase;
                    vertical-align: middle;
                    white-space: nowrap;
                }

                .table-offline td {
                    padding: 0.55rem 0.45rem !important;
                    vertical-align: middle;
                }
            </style>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0 table-offline">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">NO. TRANSAKSI</th>
                            <th>TANGGAL</th>
                            <th>PEMBELI</th>
                            <th>DIINPUT</th>
                            <th class="text-center">TRANSAKSI</th>
                            <th class="text-end">TOTAL</th>
                            <th class="text-center">SISA BAYAR</th>
                            <th class="text-center">STATUS</th>
                            <th class="text-center">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sales as $sale)
                            <tr>
                                <td class="ps-3">
                                    <a href="{{ route('offline_sales.show', $sale->id) }}"
                                        class="fw-bold text-decoration-none text-primary font-monospace small">
                                        {{ $sale->sale_number }}
                                    </a>
                                    @if ($sale->is_dropship)
                                        <span class="badge bg-warning text-dark font-monospace ms-1"
                                            style="font-size: 0.6rem; padding: 0.15em 0.3em;">Dropship</span>
                                    @endif
                                    @if ($sale->is_po)
                                        <span class="badge text-white font-monospace ms-1"
                                            style="background-color: #8b5cf6; font-size: 0.6rem; padding: 0.15em 0.3em;">PO
                                            Produksi</span>
                                    @endif
                                    @if ($sale->needs_follow_up)
                                        <span class="badge bg-danger text-white ms-1"
                                            style="font-size: 0.6rem; padding: 0.15em 0.3em;" title="Perlu Follow Up DP">
                                            <i class="fas fa-exclamation-circle"></i>
                                        </span>
                                    @endif
                                </td>
                                <td class="small text-muted text-nowrap">
                                    {{ $sale->sold_at ? $sale->sold_at->format('d M Y, H:i') : '-' }}
                                </td>
                                <td class="small">
                                    <div class="fw-semibold">{{ $sale->buyer_name ?: '(Umum)' }}</div>
                                    <div class="text-muted">{{ $sale->buyer_phone ?? '' }}</div>
                                </td>
                                <td class="small text-muted">{{ $sale->user->name ?? '-' }}</td>
                                <td class="text-center align-middle">
                                    @if ($sale->payment_method === 'piutang')
                                        <span class="badge bg-danger small py-1 px-2">
                                            <i class="fas fa-file-invoice-dollar me-1"></i>Kredit
                                        </span>
                                    @else
                                        <span class="badge bg-success small py-1 px-2">
                                            <i class="fas fa-money-bill-wave me-1"></i>Tunai
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end fw-bold text-success font-monospace small text-nowrap">
                                    Rp {{ number_format($sale->grand_total, 0, ',', '.') }}
                                </td>
                                <td class="text-center align-middle">
                                    @if ($sale->status === \App\Models\OfflineSale::STATUS_CANCELLED)
                                        <span class="badge bg-secondary small">Dibatalkan</span>
                                    @elseif ($sale->is_paid || (float) $sale->remaining_amount <= 0)
                                        <span class="badge bg-success small py-1 px-2">
                                            <i class="fas fa-check-circle me-1"></i>Lunas
                                        </span>
                                    @else
                                        <span class="fw-bold text-danger font-monospace small text-nowrap"
                                            @if ((float) $sale->paid_amount > 0) title="Sudah dicicil: Rp {{ number_format($sale->paid_amount, 0, ',', '.') }}" @endif>
                                            Rp {{ number_format($sale->remaining_amount, 0, ',', '.') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center align-middle">
                                    @php
                                        $badgeClass = match ($sale->status_badge) {
                                            'success' => 'bg-success',
                                            'danger' => 'bg-danger',
                                            'warning' => 'bg-warning text-dark',
                                            'info' => 'bg-info text-dark',
                                            'primary' => 'bg-primary text-white',
                                            default => 'bg-secondary',
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }} small">{{ $sale->status_label }}</span>
                                    @if ($sale->needs_follow_up)
                                        <div class="mt-1">
                                            <span class="badge bg-danger text-white small py-1 px-2 shadow-sm" title="Batas Follow Up: {{ $sale->follow_up_date?->format('d/m/Y') }}">
                                                <i class="fas fa-bell me-1"></i>Perlu Follow Up
                                            </span>
                                        </div>
                                        <div class="small text-danger fw-semibold mt-1" style="font-size: 0.7rem;">
                                            <i class="fas fa-calendar-times me-1"></i>F/U: {{ $sale->follow_up_date->format('d/m/Y') }}
                                        </div>
                                    @elseif ($sale->follow_up_date && $sale->status === \App\Models\OfflineSale::STATUS_WAITING_DP)
                                        <div class="small text-muted mt-1" style="font-size: 0.7rem;">
                                            <i class="far fa-clock me-1"></i>F/U: {{ $sale->follow_up_date->format('d/m/Y') }}
                                        </div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <a href="{{ route('offline_sales.show', $sale->id) }}"
                                            class="btn btn-sm btn-outline-primary py-1 px-2" title="Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('offline_sales.print', $sale->id) }}" target="_blank"
                                            class="btn btn-sm btn-outline-secondary py-1 px-2" title="Cetak Struk">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        @if (
                                            in_array($sale->status, [\App\Models\OfflineSale::STATUS_PENDING_APPROVAL, \App\Models\OfflineSale::STATUS_SPK_PROCESSING]) &&
                                                (auth()->user()->canDo('offline-sales.approve') ||
                                                    auth()->user()->isAdmin() ||
                                                    auth()->user()->isOwner() ||
                                                    in_array(auth()->user()->role, ['admin', 'owner', 'warehouse', 'gudang'])))
                                            <button type="button" class="btn btn-sm btn-success py-1 px-2"
                                                title="Approve Transaksi" data-bs-toggle="modal"
                                                data-bs-target="#modalApprove" data-id="{{ $sale->id }}"
                                                data-sale-number="{{ $sale->sale_number }}">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        @endif
                                        @if ($sale->status === \App\Models\OfflineSale::STATUS_PENDING_SPK)
                                            <button type="button" class="btn btn-sm btn-warning text-dark py-1 px-2 fw-bold"
                                                title="Buat SPK Produksi" data-bs-toggle="modal"
                                                data-bs-target="#modalCreateSpk" data-id="{{ $sale->id }}"
                                                data-sale-number="{{ $sale->sale_number }}"
                                                data-default-no-produksi="{{ \App\Models\Spk::generateNoProduksi() }}"
                                                data-items="{{ json_encode($sale->items->map(fn($i) => ['name' => $i->product_name, 'qty' => $i->quantity, 'sku' => $i->sku])) }}">
                                                <i class="fas fa-hammer"></i>
                                            </button>
                                        @endif
                                        @if ($sale->status === \App\Models\OfflineSale::STATUS_SPK_PROCESSING && $sale->spks->isNotEmpty())
                                            @php $firstSpk = $sale->spks->first(); @endphp
                                            <a href="{{ route('spks.show', $firstSpk->id) }}" target="_blank"
                                                class="btn btn-sm btn-outline-primary py-1 px-2"
                                                title="Lihat SPK Produksi ({{ $firstSpk->no_produksi ?: $firstSpk->no_spk }})">
                                                <i class="fas fa-industry"></i>
                                            </a>
                                        @endif
                                        @if ($sale->status !== \App\Models\OfflineSale::STATUS_CANCELLED && !$sale->is_paid)
                                            <button type="button" class="btn btn-sm btn-outline-success py-1 px-2"
                                                title="Catat Pembayaran / Cicilan" data-bs-toggle="modal"
                                                data-bs-target="#modalMarkPaid" data-id="{{ $sale->id }}"
                                                data-sale-number="{{ $sale->sale_number }}"
                                                data-status="{{ $sale->status }}"
                                                data-grand-total="Rp {{ number_format($sale->grand_total, 0, ',', '.') }}"
                                                data-paid-amount="Rp {{ number_format($sale->paid_amount, 0, ',', '.') }}"
                                                data-unpaid-amount="Rp {{ number_format(max(0, $sale->grand_total - $sale->paid_amount), 0, ',', '.') }}"
                                                data-unpaid-raw="{{ max(0, (float) $sale->grand_total - (float) $sale->paid_amount) }}">
                                                <i class="fas fa-money-bill-wave"></i>
                                            </button>
                                        @endif
                                        @php
                                            $canCancel = $sale->status !== \App\Models\OfflineSale::STATUS_CANCELLED
                                                && (!$sale->is_po || (float) $sale->paid_amount <= 0);
                                        @endphp
                                        @if ($canCancel)
                                            <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2"
                                                title="Batalkan Transaksi" data-bs-toggle="modal"
                                                data-bs-target="#modalCancel" data-id="{{ $sale->id }}"
                                                data-sale-number="{{ $sale->sale_number }}"
                                                data-status="{{ $sale->status }}">
                                                <i class="fas fa-times-circle"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="fas fa-store-slash fa-3x mb-3 d-block opacity-25"></i>
                                    Belum ada transaksi penjualan offline.
                                    <div class="mt-2">
                                        <a href="{{ route('offline_sales.create') }}"
                                            class="btn btn-success btn-sm mt-2">
                                            <i class="fas fa-plus me-1"></i>Buat Transaksi Pertama
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($sales->hasPages())
                <div class="d-flex justify-content-between align-items-center px-3 py-2">
                    <span class="text-muted small">
                        Halaman {{ $sales->currentPage() }} dari {{ $sales->lastPage() }}
                        &mdash; {{ $sales->total() }} total transaksi
                    </span>
                    {{ $sales->links() }}
                </div>
            @endif
        </div>
    </div>

@endsection

{{-- Modal Konfirmasi Approve --}}
@push('modals')
    @if (auth()->user()->canDo('offline-sales.approve') ||
            auth()->user()->isAdmin() ||
            auth()->user()->isOwner() ||
            in_array(auth()->user()->role, ['admin', 'owner', 'warehouse', 'gudang']))
        <div class="modal fade" id="modalApprove" tabindex="-1" aria-labelledby="modalApproveLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-success bg-opacity-10 border-bottom">
                        <h6 class="modal-title fw-bold text-success" id="modalApproveLabel">
                            <i class="fas fa-check-circle me-2"></i>Konfirmasi Persetujuan Transaksi
                        </h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="form-approve" method="POST" class="m-0">
                        @csrf
                        <div class="modal-body">
                            <p class="mb-1 text-dark">Yakin ingin menyetujui (approve) transaksi:</p>
                            <p class="fw-bold font-monospace text-success mb-3" id="modal-approve-sale-number"></p>

                            <div class="mb-3">
                                <label for="approve_payment_destination"
                                    class="form-label fw-semibold small text-dark mb-1">
                                    <i class="fas fa-university me-1 text-primary"></i> Kas / Bank Tujuan Pemasukan <span
                                        class="text-danger">*</span>
                                </label>
                                <select name="payment_destination" id="approve_payment_destination"
                                    class="form-select form-select-sm" required>
                                    @if (isset($bankAccounts) && $bankAccounts->isNotEmpty())
                                        @foreach ($bankAccounts as $bank)
                                            <option value="{{ $bank->bank_name }}">
                                                {{ $bank->bank_name }}
                                                {{ $bank->account_number ? '(' . $bank->account_number . ')' : '' }} —
                                                Saldo:
                                                Rp {{ number_format($bank->current_balance, 0, ',', '.') }}
                                            </option>
                                        @endforeach
                                    @else
                                        <option value="kas_besar">Kas Besar (Utama)</option>
                                        <option value="kas_kecil">Kas Kecil (Operasional)</option>
                                    @endif
                                </select>
                                <div class="form-text text-muted">Uang pembayaran akan masuk ke akun kas/bank yang dipilih.
                                </div>
                            </div>

                            <div class="alert alert-success py-2 mb-0 small">
                                <i class="fas fa-boxes me-1"></i> Stok produk akan <strong>dikurangi</strong> & pemasukan
                                dicatat ke Kas/Bank.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm"
                                data-bs-dismiss="modal">Tutup</button>
                            <button type="submit" class="btn btn-success btn-sm px-4">
                                <i class="fas fa-check me-1"></i> Ya, Setujui & Catat Pemasukan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal Konfirmasi Pembatalan --}}
    <div class="modal fade" id="modalCancel" tabindex="-1" aria-labelledby="modalCancelLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger bg-opacity-10 border-bottom">
                    <h6 class="modal-title fw-bold text-danger" id="modalCancelLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>Konfirmasi Pembatalan Transaksi
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="form-cancel" method="POST" class="m-0">
                    @csrf
                    <div class="modal-body">
                        <p class="mb-1 text-dark">Yakin ingin membatalkan transaksi:</p>
                        <p class="fw-bold font-monospace text-danger mb-3" id="modal-sale-number"></p>
                        <div class="alert alert-warning py-2 mb-3 small" id="modal-cancel-note"></div>
                        <div class="mb-0">
                            <label for="cancellation_reason" class="form-label fw-semibold small text-dark mb-1">
                                Alasan Pembatalan <span class="text-danger">*</span>
                            </label>
                            <textarea name="cancellation_reason" id="cancellation_reason" rows="3" class="form-control form-control-sm"
                                placeholder="Contoh: Pelanggan membatalkan pesanan, stok habis, dll..." required minlength="5" maxlength="500"></textarea>
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

    {{-- Modal Konfirmasi Pelunasan --}}
    {{-- Modal Catat Pembayaran / Cicilan --}}
    <div class="modal fade" id="modalMarkPaid" tabindex="-1" aria-labelledby="modalMarkPaidLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-success bg-opacity-10 border-bottom">
                    <h6 class="modal-title fw-bold text-success" id="modalMarkPaidLabel">
                        <i class="fas fa-money-bill-wave me-2"></i>Catat Pembayaran / Cicilan
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="form-mark-paid" method="POST" class="m-0">
                    @csrf
                    <div class="modal-body">
                        <p class="mb-1 text-dark">Catat pembayaran untuk transaksi:</p>
                        <p class="fw-bold font-monospace text-primary mb-3" id="modal-paid-sale-number"></p>

                        <div class="p-3 bg-light rounded border mb-3">
                            <div class="d-flex justify-content-between mb-1 small">
                                <span class="text-muted">Total Transaksi:</span>
                                <strong class="font-monospace text-dark" id="modal-paid-grand-total">Rp 0</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-1 small">
                                <span class="text-muted">Sudah Dibayar:</span>
                                <span class="font-monospace text-secondary" id="modal-paid-amount">Rp 0</span>
                            </div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between small">
                                <span class="fw-bold text-danger">Sisa Kekurangan:</span>
                                <strong class="font-monospace text-danger fs-6" id="modal-paid-unpaid-amount">Rp
                                    0</strong>
                            </div>
                        </div>

                        {{-- Nominal Pembayaran / Cicilan --}}
                        <div class="mb-3">
                            <label for="modal-index-paid-amount-display"
                                class="form-label fw-semibold small text-dark mb-1">
                                Nominal Pembayaran / Cicilan <span class="text-danger">*</span>
                            </label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text fw-bold">Rp</span>
                                <input type="text" id="modal-index-paid-amount-display"
                                    class="form-control form-control-sm font-monospace fw-bold fs-6" required>
                                <input type="hidden" name="amount" id="modal-index-paid-amount-raw">
                            </div>
                            <div class="form-text text-muted" style="font-size:0.72rem;">
                                Bisa bayar lunas langsung atau bayar sebagian (dicicil).
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label for="paid_payment_method" class="form-label fw-semibold small text-dark mb-1">
                                    Metode Pembayaran <span class="text-danger">*</span>
                                </label>
                                <select name="payment_method" id="paid_payment_method" class="form-select form-select-sm"
                                    required>
                                    <option value="transfer" selected>Transfer Bank</option>
                                    <option value="tunai">Tunai (Cash)</option>
                                    <option value="qris">QRIS</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="paid_payment_date" class="form-label fw-semibold small text-dark mb-1">
                                    Tanggal Bayar <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="payment_date" id="paid_payment_date"
                                    class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="paid_payment_destination" class="form-label fw-semibold small text-dark mb-1">
                                <i class="fas fa-university me-1 text-primary"></i> Kas / Bank Tujuan Pembayaran <span
                                    class="text-danger">*</span>
                            </label>
                            <select name="payment_destination" id="paid_payment_destination"
                                class="form-select form-select-sm" required>
                                @if (isset($bankAccounts) && $bankAccounts->isNotEmpty())
                                    @foreach ($bankAccounts as $bank)
                                        <option value="{{ $bank->bank_name }}">
                                            {{ $bank->bank_name }}
                                            {{ $bank->account_number ? '(' . $bank->account_number . ')' : '' }} — Saldo:
                                            Rp
                                            {{ number_format($bank->current_balance, 0, ',', '.') }}
                                        </option>
                                    @endforeach
                                @else
                                    <option value="kas_besar">Kas Besar (Utama)</option>
                                    <option value="kas_kecil">Kas Kecil (Operasional)</option>
                                @endif
                            </select>
                        </div>

                        <div class="mb-2">
                            <label for="paid_notes" class="form-label fw-semibold small text-dark mb-1">Catatan
                                (Opsional)</label>
                            <input type="text" name="notes" id="paid_notes" class="form-control form-control-sm"
                                placeholder="Contoh: Cicilan 1, Pelunasan...">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-success btn-sm px-4">
                            <i class="fas fa-check-circle me-1"></i> Simpan Pembayaran
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Terbitkan SPK Produksi --}}
    <div class="modal fade" id="modalCreateSpk" tabindex="-1" aria-labelledby="modalCreateSpkLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-warning bg-opacity-10 border-bottom">
                    <h6 class="modal-title fw-bold text-dark" id="modalCreateSpkLabel">
                        <i class="fas fa-hammer me-2 text-warning"></i>Terbitkan SPK Produksi
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="form-create-spk" method="POST" class="m-0">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3 p-3 bg-light rounded border">
                            <div>
                                <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.7rem;">Transaksi PO</small>
                                <span class="fw-bold font-monospace text-primary fs-6" id="modal-spk-sale-number"></span>
                            </div>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle py-1.5 px-2.5">
                                <i class="fas fa-layer-group me-1"></i> 1 No. Produksi = Multi SPK Produk
                            </span>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="spk_no_produksi" class="form-label fw-semibold small text-dark mb-1">
                                    <i class="fas fa-hashtag text-primary me-1"></i>Nomor / Kode Produksi <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="no_produksi" id="spk_no_produksi" class="form-control form-control-sm font-monospace fw-bold" required>
                                <div class="form-text text-muted" style="font-size: 0.75rem;">
                                    Satu kode produksi ini akan menaungi seluruh SPK pesanan ini.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="spk_tahap_saat_ini" class="form-label fw-semibold small text-dark mb-1">
                                    <i class="fas fa-tasks text-primary me-1"></i>Tahap Awal Produksi <span class="text-danger">*</span>
                                </label>
                                <select name="tahap_saat_ini" id="spk_tahap_saat_ini" class="form-select form-select-sm fw-semibold" required>
                                    <option value="Antrian &amp; Sampling" selected>⏳ Antrian &amp; Sampling (Langsung Antrian)</option>
                                    <option value="Perencanaan">📋 Perencanaan / Pesanan Baru</option>
                                    <option value="Tahap Pemotongan">✂️ Tahap Pemotongan</option>
                                    <option value="Tahap Jahit">🪡 Tahap Jahit</option>
                                </select>
                                <div class="form-text text-success" style="font-size: 0.75rem;">
                                    <i class="fas fa-check-circle me-1"></i>SPK langsung masuk antrian aktif (bukan Draft).
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="spk_deadline" class="form-label fw-semibold small text-dark mb-1">
                                <i class="fas fa-calendar-alt text-primary me-1"></i>Target Deadline SPK Produksi <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="deadline" id="spk_deadline" class="form-control form-control-sm" value="{{ now()->addDays(7)->format('Y-m-d') }}" required>
                            <div class="form-text text-muted" style="font-size: 0.75rem;">Target tanggal selesai pengerjaan oleh tim produksi.</div>
                        </div>

                        {{-- Rincian SPK yang akan dibuat --}}
                        <div class="border rounded p-3 bg-light">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label fw-bold small text-dark mb-0">
                                    <i class="fas fa-boxes me-1 text-primary"></i>Rincian SPK yang Akan Diterbitkan:
                                </label>
                                <span class="badge bg-secondary small" id="modal-spk-item-count">0 Produk</span>
                            </div>
                            <div class="small text-muted mb-2" style="font-size: 0.75rem;">
                                Setiap jenis produk di bawah ini akan otomatis dibuatkan <strong>1 SPK tersendiri</strong> di bawah Kode Produksi yang sama:
                            </div>
                            <div class="table-responsive bg-white rounded border">
                                <table class="table table-sm table-hover mb-0" style="font-size: 0.8rem;">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>PRODUK / SPK</th>
                                            <th class="text-center">QTY</th>
                                            <th>KODE PRODUKSI</th>
                                            <th>STATUS AWAL</th>
                                        </tr>
                                    </thead>
                                    <tbody id="modal-spk-items-table">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold">
                            <i class="fas fa-hammer me-1"></i> Terbitkan SPK Sekarang
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Modal Approve
            const modalApproveEl = document.getElementById('modalApprove');
            if (modalApproveEl) {
                modalApproveEl.addEventListener('show.bs.modal', function(event) {
                    const btn = event.relatedTarget;
                    document.getElementById('modal-approve-sale-number').textContent = btn.getAttribute(
                        'data-sale-number');
                    document.getElementById('form-approve').action = '/offline-sales/' + btn.getAttribute(
                        'data-id') + '/approve';
                });
            }

            // Modal Batal
            const modalCancel = document.getElementById('modalCancel');
            modalCancel.addEventListener('show.bs.modal', function(event) {
                const btn = event.relatedTarget;
                const id = btn.getAttribute('data-id');
                const saleNumber = btn.getAttribute('data-sale-number');
                const status = btn.getAttribute('data-status');
                document.getElementById('modal-sale-number').textContent = saleNumber;
                document.getElementById('form-cancel').action = '/offline-sales/' + id + '/cancel';
                // Pesan berbeda tergantung status
                const noteEl = document.getElementById('modal-cancel-note');
                if (status === 'pending_approval') {
                    noteEl.innerHTML =
                        '<i class="fas fa-info-circle me-1"></i> Transaksi ini belum diapprove, stok <strong>tidak akan</strong> berubah.';
                    noteEl.className = 'alert alert-info py-2 mb-0 small';
                } else {
                    noteEl.innerHTML =
                        '<i class="fas fa-undo me-1"></i> Stok semua produk dalam transaksi ini akan <strong>dikembalikan</strong> secara otomatis.';
                    noteEl.className = 'alert alert-warning py-2 mb-0 small';
                }
            });

            // Modal Catat Pembayaran / Cicilan
            const modalMarkPaid = document.getElementById('modalMarkPaid');
            if (modalMarkPaid) {
                let currentMaxRemaining = 0;
                modalMarkPaid.addEventListener('show.bs.modal', function(event) {
                    const btn = event.relatedTarget;
                    const id = btn.getAttribute('data-id');
                    const unpaidRaw = parseFloat(btn.getAttribute('data-unpaid-raw')) || 0;
                    currentMaxRemaining = unpaidRaw;

                    document.getElementById('modal-paid-sale-number').textContent = btn.getAttribute(
                        'data-sale-number');
                    document.getElementById('modal-paid-grand-total').textContent = btn.getAttribute(
                        'data-grand-total');
                    document.getElementById('modal-paid-amount').textContent = btn.getAttribute(
                        'data-paid-amount');
                    document.getElementById('modal-paid-unpaid-amount').textContent = btn.getAttribute(
                        'data-unpaid-amount');

                    const displayInput = document.getElementById('modal-index-paid-amount-display');
                    const rawInput = document.getElementById('modal-index-paid-amount-raw');
                    displayInput.value = Math.round(unpaidRaw).toLocaleString('id-ID');
                    rawInput.value = unpaidRaw;

                    document.getElementById('form-mark-paid').action = '/offline-sales/' + id + '/payments';

                    const status = btn.getAttribute('data-status');
                    const notesInput = document.getElementById('paid_notes');
                    if (notesInput) {
                        notesInput.value = (status === 'menunggu_dp') ? 'Pembayaran DP' : '';
                    }
                });

                const displayInput = document.getElementById('modal-index-paid-amount-display');
                const rawInput = document.getElementById('modal-index-paid-amount-raw');
                if (displayInput && rawInput) {
                    displayInput.addEventListener('input', function() {
                        let val = this.value.replace(/[^0-9]/g, '');
                        let num = parseInt(val, 10) || 0;
                        if (currentMaxRemaining > 0 && num > currentMaxRemaining) {
                            num = currentMaxRemaining;
                        }
                        this.value = num > 0 ? num.toLocaleString('id-ID') : '';
                        rawInput.value = num;
                    });
                }
            }

            // Modal Buat SPK
            const modalCreateSpk = document.getElementById('modalCreateSpk');
            if (modalCreateSpk) {
                modalCreateSpk.addEventListener('show.bs.modal', function(event) {
                    const btn = event.relatedTarget;
                    const saleNumber = btn.getAttribute('data-sale-number');
                    const noProduksi = btn.getAttribute('data-default-no-produksi');
                    const itemsJson = btn.getAttribute('data-items');

                    document.getElementById('modal-spk-sale-number').textContent = saleNumber;
                    document.getElementById('spk_no_produksi').value = noProduksi || '';
                    document.getElementById('form-create-spk').action = '/offline-sales/' + btn.getAttribute('data-id') + '/create-spk';

                    const tbody = document.getElementById('modal-spk-items-table');
                    const countBadge = document.getElementById('modal-spk-item-count');
                    if (tbody) {
                        tbody.innerHTML = '';
                        let items = [];
                        try { items = JSON.parse(itemsJson || '[]'); } catch(e) {}
                        countBadge.textContent = items.length + ' SPK Produk';
                        if (items.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-2">Semua produk dalam pesanan ini akan dibuatkan SPK</td></tr>';
                        } else {
                            items.forEach((it, idx) => {
                                const tr = document.createElement('tr');
                                tr.innerHTML = `
                                    <td class="text-muted">${idx + 1}</td>
                                    <td class="fw-semibold text-dark"><i class="fas fa-clipboard-check text-primary me-1"></i> SPK: ${it.name}</td>
                                    <td class="text-center fw-bold font-monospace">${it.qty} Pcs</td>
                                    <td><span class="badge bg-light text-dark border font-monospace spk-preview-prod-code">${noProduksi || '-'}</span></td>
                                    <td><span class="badge bg-primary bg-opacity-10 text-primary">Antrian & Sampling</span></td>
                                `;
                                tbody.appendChild(tr);
                            });
                        }
                    }
                });

                const noProdInput = document.getElementById('spk_no_produksi');
                if (noProdInput) {
                    noProdInput.addEventListener('input', function() {
                        const val = this.value || '-';
                        document.querySelectorAll('.spk-preview-prod-code').forEach(el => el.textContent = val);
                    });
                }
            }

            // Loading state saat submit
            ['form-approve', 'form-cancel', 'form-mark-paid', 'form-create-spk'].forEach(function(formId) {
                const form = document.getElementById(formId);
                if (form) {
                    form.addEventListener('submit', function() {
                        const btn = form.querySelector('button[type="submit"]');
                        if (btn) {
                            btn.disabled = true;
                            btn.innerHTML =
                                '<i class="fas fa-spinner fa-spin me-1"></i> Memproses...';
                        }
                    });
                }
            });
        });
    </script>
@endpush
