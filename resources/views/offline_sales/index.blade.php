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
                                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Selesai
                                </option>
                                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>
                                    Dibatalkan</option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-md-2">
                            <label class="form-label form-label-sm fw-semibold mb-1">
                                <i class="fas fa-wallet me-1"></i>Pembayaran
                            </label>
                            <select name="payment_method" class="form-select form-select-sm">
                                <option value="">Semua Metode</option>
                                @foreach (\App\Models\OfflineSale::PAYMENT_METHODS as $key => $label)
                                    <option value="{{ $key }}"
                                        {{ request('payment_method') === $key ? 'selected' : '' }}>{{ $label }}
                                    </option>
                                @endforeach
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
                            @if (request()->hasAny(['search', 'status', 'payment_method', 'date_from', 'date_to']))
                                <a href="{{ route('offline_sales.index') }}" class="btn btn-outline-secondary btn-sm px-2">
                                    <i class="fas fa-times"></i>
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">NO. TRANSAKSI</th>
                            <th>PEMBELI</th>
                            <th>KASIR</th>
                            <th>PEMBAYARAN</th>
                            <th class="text-end">GRAND TOTAL</th>
                            <th class="text-center">STATUS</th>
                            <th>WAKTU</th>
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
                                        <span class="badge bg-warning text-dark font-monospace ms-1" style="font-size: 0.6rem; padding: 0.15em 0.3em;">Dropship</span>
                                    @endif
                                </td>
                                <td class="small">
                                    <div class="fw-semibold">{{ $sale->buyer_name ?: '(Umum)' }}</div>
                                    <div class="text-muted">{{ $sale->buyer_phone ?? '' }}</div>
                                </td>
                                <td class="small text-muted">{{ $sale->user->name ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-secondary small">{{ $sale->payment_method_label }}</span>
                                </td>
                                <td class="text-end fw-bold text-success font-monospace small">
                                    Rp {{ number_format($sale->grand_total, 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    @php
                                        $badgeClass = match ($sale->status_badge) {
                                            'success' => 'bg-success',
                                            'danger'  => 'bg-danger',
                                            'warning' => 'bg-warning text-dark',
                                            default   => 'bg-secondary',
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }} small">{{ $sale->status_label }}</span>
                                </td>
                                <td class="small text-muted">
                                    {{ $sale->sold_at ? $sale->sold_at->format('d M Y, H:i') : '-' }}
                                </td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <a href="{{ route('offline_sales.show', $sale->id) }}"
                                            class="btn btn-sm btn-outline-primary py-0 px-2" title="Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('offline_sales.print', $sale->id) }}" target="_blank"
                                            class="btn btn-sm btn-outline-secondary py-0 px-2" title="Cetak Struk">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        @if ($sale->status === \App\Models\OfflineSale::STATUS_PENDING_APPROVAL && auth()->user()->canDo('offline-sales.approve'))
                                            <button type="button"
                                                class="btn btn-sm btn-success py-0 px-2"
                                                title="Approve Transaksi"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalApprove"
                                                data-id="{{ $sale->id }}"
                                                data-sale-number="{{ $sale->sale_number }}">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        @endif
                                        @if ($sale->status !== \App\Models\OfflineSale::STATUS_CANCELLED)
                                            <button type="button"
                                                class="btn btn-sm btn-outline-danger py-0 px-2"
                                                title="Batalkan Transaksi"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalCancel"
                                                data-id="{{ $sale->id }}"
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
                                <td colspan="8" class="text-center py-5 text-muted">
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
@if(auth()->user()->canDo('offline-sales.approve'))
<div class="modal fade" id="modalApprove" tabindex="-1" aria-labelledby="modalApproveLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success bg-opacity-10 border-bottom">
                <h6 class="modal-title fw-bold text-success" id="modalApproveLabel">
                    <i class="fas fa-check-circle me-2"></i>Konfirmasi Persetujuan Transaksi
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1 text-dark">Yakin ingin menyetujui (approve) transaksi:</p>
                <p class="fw-bold font-monospace text-success mb-3" id="modal-approve-sale-number"></p>
                <div class="alert alert-success py-2 mb-0 small">
                    <i class="fas fa-boxes me-1"></i> Stok semua produk dalam transaksi ini akan <strong>dikurangi</strong> secara otomatis setelah disetujui.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                <form id="form-approve" method="POST" class="m-0">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm px-4">
                        <i class="fas fa-check me-1"></i> Ya, Setujui
                    </button>
                </form>
            </div>
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
            <div class="modal-body">
                <p class="mb-1 text-dark">Yakin ingin membatalkan transaksi:</p>
                <p class="fw-bold font-monospace text-danger mb-3" id="modal-sale-number"></p>
                <div class="alert alert-warning py-2 mb-0 small" id="modal-cancel-note"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                <form id="form-cancel" method="POST" class="m-0">
                    @csrf
                    <button type="submit" class="btn btn-danger btn-sm px-4">
                        <i class="fas fa-times-circle me-1"></i> Ya, Batalkan
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Modal Approve
        const modalApproveEl = document.getElementById('modalApprove');
        if (modalApproveEl) {
            modalApproveEl.addEventListener('show.bs.modal', function (event) {
                const btn = event.relatedTarget;
                document.getElementById('modal-approve-sale-number').textContent = btn.getAttribute('data-sale-number');
                document.getElementById('form-approve').action = '/offline-sales/' + btn.getAttribute('data-id') + '/approve';
            });
        }

        // Modal Batal
        const modalCancel = document.getElementById('modalCancel');
        modalCancel.addEventListener('show.bs.modal', function (event) {
            const btn = event.relatedTarget;
            const id = btn.getAttribute('data-id');
            const saleNumber = btn.getAttribute('data-sale-number');
            const status = btn.getAttribute('data-status');
            document.getElementById('modal-sale-number').textContent = saleNumber;
            document.getElementById('form-cancel').action = '/offline-sales/' + id + '/cancel';
            // Pesan berbeda tergantung status
            const noteEl = document.getElementById('modal-cancel-note');
            if (status === 'pending_approval') {
                noteEl.innerHTML = '<i class="fas fa-info-circle me-1"></i> Transaksi ini belum diapprove, stok <strong>tidak akan</strong> berubah.';
                noteEl.className = 'alert alert-info py-2 mb-0 small';
            } else {
                noteEl.innerHTML = '<i class="fas fa-undo me-1"></i> Stok semua produk dalam transaksi ini akan <strong>dikembalikan</strong> secara otomatis.';
                noteEl.className = 'alert alert-warning py-2 mb-0 small';
            }
        });
    });
</script>
@endpush
