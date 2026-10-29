@extends('v2.layouts.app')

@section('title', 'Detail Penjualan Offline #' . $sale->sale_number)

@section('content')

    <div class="v2-page-header align-items-center mb-3">
        <div>
            <h1 class="v2-page-title d-flex align-items-center gap-2 mb-0">
                <i class="bi bi-receipt text-primary fs-5"></i> Detail Nota Penjualan #{{ $sale->sale_number }}
            </h1>
            <p class="text-muted small mb-0">Rincian produk, transaksi kasir, dan pembayaran nota penjualan offline</p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="{{ route('v2.penjualan_offline.index') }}" class="btn btn-sm btn-outline-secondary px-3">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
            @if ($sale->status !== 'cancelled')
                @php
                    $isAllPacked =
                        $sale->packing_status === 'verified' ||
                        ($sale->items->sum('quantity') > 0 &&
                            $sale->items->sum('packed_quantity') >= $sale->items->sum('quantity'));
                @endphp
                <button type="button"
                    class="btn btn-sm {{ $isAllPacked ? 'btn-success text-white' : 'btn-primary' }} px-3 fw-semibold shadow-sm"
                    data-bs-toggle="modal" data-bs-target="#modalScanKemasOffline" id="btnHeaderScanKemas">
                    <i class="bi bi-{{ $isAllPacked ? 'check-circle-fill' : 'upc-scan' }} me-1"></i>
                    <span
                        id="txtBtnHeaderKemas">{{ $isAllPacked ? 'Sudah Dikemas (Lihat Scan)' : 'Scan & Kemas Barang' }}</span>
                </button>
                <a href="{{ route('v2.scanner_gudang.index', ['scan' => $sale->sale_number]) }}" target="_blank"
                    class="btn btn-sm btn-outline-dark px-3" title="Buka di Layar Scanner Gudang Fullscreen">
                    <i class="bi bi-fullscreen me-1"></i> Layar Gudang
                </a>
                <a href="{{ route('v2.penjualan_offline.edit', $sale->id) }}"
                    class="btn btn-sm btn-warning text-dark px-3 fw-semibold">
                    <i class="bi bi-pencil-square me-1"></i> Edit Transaksi
                </a>
                <button type="button" class="btn btn-sm btn-danger px-3 fw-semibold" data-bs-toggle="modal"
                    data-bs-target="#modalCancelSaleDetail">
                    <i class="bi bi-x-circle me-1"></i> Batalkan Transaksi
                </button>
            @endif
            <a href="{{ route('offline_sales.print', $sale->id) }}?format=invoice" target="_blank"
                class="btn btn-sm btn-dark px-3">
                <i class="bi bi-printer me-1"></i> Cetak Invoice
            </a>
            @if ($sale->status !== 'cancelled')
                @php
                    $activeSpks = $sale->spks->where('is_batal', false);
                @endphp
                @if ($activeSpks->isNotEmpty())
                    @php $firstSpk = $activeSpks->first(); @endphp
                    <a href="{{ route('spks.show', $firstSpk->id) }}" target="_blank"
                        class="btn btn-sm btn-info text-white px-3 fw-semibold">
                        <i class="bi bi-gear-wide-connected me-1"></i> Lihat SPK
                        ({{ $firstSpk->no_produksi ?: $firstSpk->no_spk }})
                    </a>
                @else
                    <button type="button" class="btn btn-sm btn-outline-primary px-3 fw-semibold shadow-sm"
                        data-bs-toggle="modal" data-bs-target="#modalCreateSpk">
                        <i class="bi bi-hammer me-1"></i> Buat SPK
                    </button>
                @endif
            @endif
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Left Column: Details & Items -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="card-title fw-bold mb-0">Rincian Item Produk</h6>
                    <span class="badge bg-light text-dark border">
                        {{ \Carbon\Carbon::parse($sale->sold_at)->format('d M Y H:i') }}
                    </span>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0" style="font-size: 0.83rem;">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Produk / SKU</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Harga Satuan</th>
                                <th class="text-end">HPP Modal</th>
                                <th class="text-end pe-3">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sale->items as $item)
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-bold text-dark">
                                            {{ $item->product_name ?? ($item->masterProduct->name ?? 'Produk POS') }}</div>
                                        <div class="d-flex align-items-center gap-2 flex-wrap mt-0.5">
                                            <small class="text-muted font-monospace">SKU:
                                                {{ $item->sku ?? ($item->masterProduct->sku ?? '-') }}</small>
                                            @if ($item->masterProduct && $item->masterProduct->barcode)
                                                <span class="badge bg-light text-dark border font-monospace"
                                                    style="font-size: 0.65rem;">
                                                    <i class="bi bi-barcode me-0.5"></i>{{ $item->masterProduct->barcode }}
                                                </span>
                                            @endif
                                            @if ($item->packed_quantity >= $item->quantity)
                                                <span
                                                    class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25"
                                                    style="font-size:0.68rem;" id="badgeItemPacked_{{ $item->id }}">
                                                    <i class="bi bi-check2-circle me-0.5"></i>Lengkap
                                                    ({{ $item->packed_quantity }}/{{ $item->quantity }})
                                                </span>
                                            @elseif($item->packed_quantity > 0)
                                                <span
                                                    class="badge bg-warning bg-opacity-15 text-dark border border-warning border-opacity-25"
                                                    style="font-size:0.68rem;" id="badgeItemPacked_{{ $item->id }}">
                                                    <i class="bi bi-hourglass-split me-0.5"></i>Sebagian
                                                    ({{ $item->packed_quantity }}/{{ $item->quantity }})
                                                </span>
                                            @else
                                                <span class="badge bg-light text-secondary border"
                                                    style="font-size:0.68rem;" id="badgeItemPacked_{{ $item->id }}">
                                                    <i class="bi bi-dash-circle me-0.5"></i>Belum Dikemas
                                                    (0/{{ $item->quantity }})
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-center fw-bold">
                                        <span class="fs-6">{{ number_format($item->quantity, 0, ',', '.') }}</span>
                                        <small class="d-block text-muted" style="font-size:0.7rem;">Kemas: <strong
                                                class="{{ $item->packed_quantity >= $item->quantity ? 'text-success' : ($item->packed_quantity > 0 ? 'text-warning' : 'text-secondary') }}"
                                                id="txtTablePackedQty_{{ $item->id }}">{{ $item->packed_quantity }}</strong></small>
                                    </td>
                                    <td class="text-end font-monospace">Rp
                                        {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                    <td class="text-end font-monospace text-muted">Rp
                                        {{ number_format($item->masterProduct->purchase_price ?? 0, 0, ',', '.') }}</td>
                                    <td class="text-end pe-3 font-monospace fw-bold text-dark">Rp
                                        {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="4" class="text-end fw-bold">Subtotal Produk:</td>
                                <td class="text-end pe-3 font-monospace fw-bold">Rp
                                    {{ number_format($sale->total_amount, 0, ',', '.') }}</td>
                            </tr>
                            @if ((float) $sale->discount_amount > 0)
                                <tr>
                                    <td colspan="4" class="text-end text-danger fw-semibold">Diskon Penjualan:</td>
                                    <td class="text-end pe-3 font-monospace text-danger fw-bold">- Rp
                                        {{ number_format($sale->discount_amount, 0, ',', '.') }}</td>
                                </tr>
                            @endif
                            @if ((float) $sale->shipping_fee > 0)
                                <tr>
                                    <td colspan="4" class="text-end text-muted fw-semibold">Biaya Pengiriman:</td>
                                    <td class="text-end pe-3 font-monospace fw-bold">+ Rp
                                        {{ number_format($sale->shipping_fee, 0, ',', '.') }}</td>
                                </tr>
                            @endif
                            <tr class="table-active">
                                <td colspan="4" class="text-end fw-bold fs-6">GRAND TOTAL:</td>
                                <td class="text-end pe-3 font-monospace fw-bold fs-6 text-primary">Rp
                                    {{ number_format($sale->grand_total, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Payments History -->
            <div class="card border-0 shadow-sm rounded-3">
                <div
                    class="card-header bg-white py-2.5 px-3 border-bottom d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-cash-stack text-success fs-5"></i>
                        <h6 class="card-title fw-bold mb-0">Riwayat Pembayaran Kasir</h6>
                    </div>
                    @if ($sale->status !== 'cancelled' && $sale->remaining_amount > 0)
                        <button type="button" class="btn btn-sm btn-success fw-semibold px-2.5 py-1 shadow-sm"
                            data-bs-toggle="modal" data-bs-target="#modalRecordPaymentShow">
                            <i class="bi bi-plus-circle me-1"></i> Catat Pembayaran / Cicilan
                        </button>
                    @endif
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0" style="font-size: 0.83rem;">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Tanggal Bayar</th>
                                <th>Metode</th>
                                <th>Tujuan Kas / Bank</th>
                                <th>Keterangan</th>
                                <th class="text-end pe-3">Jumlah Dibayar</th>
                                @if (auth()->user()->isAdmin() || auth()->user()->isOwner() || in_array(auth()->user()->role, ['admin', 'owner']))
                                    <th class="text-center" style="width: 60px;">Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sale->payments as $pmt)
                                <tr>
                                    <td class="ps-3">
                                        {{ \Carbon\Carbon::parse($pmt->payment_date ?: $pmt->created_at)->format('d/m/Y H:i') }}
                                    </td>
                                    <td><span
                                            class="badge bg-light text-dark border">{{ strtoupper($pmt->payment_method) }}</span>
                                    </td>
                                    <td>{{ ucwords(str_replace('_', ' ', $pmt->payment_destination ?: 'kas_besar')) }}</td>
                                    <td class="text-muted">{{ $pmt->notes ?: '-' }}</td>
                                    <td class="text-end pe-3 font-monospace fw-bold text-success">Rp
                                        {{ number_format($pmt->amount, 0, ',', '.') }}</td>
                                    @if (auth()->user()->isAdmin() || auth()->user()->isOwner() || in_array(auth()->user()->role, ['admin', 'owner']))
                                        <td class="text-center">
                                            <form method="POST"
                                                action="{{ route('offline_sales.payments.destroy', [$sale->id, $pmt->id]) }}"
                                                class="d-inline"
                                                onsubmit="return confirm('Hapus cicilan pembayaran Rp {{ number_format($pmt->amount, 0, ',', '.') }} ini? Saldo kas/bank dan jurnal pemasukan keuangan akan di-rollback.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-xs py-0 px-1.5"
                                                    title="Hapus Pembayaran">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                @if ((float) $sale->paid_amount > 0)
                                    <tr>
                                        <td class="ps-3">
                                            {{ \Carbon\Carbon::parse($sale->sold_at ?: $sale->created_at)->format('d/m/Y H:i') }}
                                        </td>
                                        <td><span
                                                class="badge bg-light text-dark border">{{ strtoupper($sale->payment_method ?: 'TUNAI') }}</span>
                                        </td>
                                        <td>{{ ucwords(str_replace('_', ' ', $sale->payment_destination ?: 'kas_besar')) }}
                                        </td>
                                        <td class="text-muted">Pembayaran Kasir Saat Transaksi Dibuat</td>
                                        <td class="text-end pe-3 font-monospace fw-bold text-success">Rp
                                            {{ number_format(min($sale->paid_amount, $sale->grand_total), 0, ',', '.') }}
                                        </td>
                                        @if (auth()->user()->isAdmin() || auth()->user()->isOwner() || in_array(auth()->user()->role, ['admin', 'owner']))
                                            <td class="text-center text-muted small"><span
                                                    class="badge bg-light text-secondary border">Awal</span></td>
                                        @endif
                                    </tr>
                                @else
                                    <tr>
                                        <td colspan="{{ auth()->user()->isAdmin() || auth()->user()->isOwner() || in_array(auth()->user()->role, ['admin', 'owner']) ? 6 : 5 }}"
                                            class="text-center py-3 text-muted fst-italic">Belum ada riwayat pembayaran
                                            tercatat.</td>
                                    </tr>
                                @endif
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right Column: Buyer Info & Summary -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="card-title fw-bold mb-0">Informasi Pelanggan</h6>
                </div>
                <div class="card-body p-3" style="font-size: 0.85rem;">
                    <div class="mb-2">
                        <small class="text-muted d-block">Toko / Cabang Asal:</small>
                        <span class="badge bg-light text-dark border px-2 py-1">
                            <i class="bi bi-shop text-primary me-1"></i>{{ $sale->store->store_name ?? 'Toko Utama' }}
                        </span>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted d-block">Nama Pembeli:</small>
                        <span class="fw-bold text-dark fs-6">{{ $sale->buyer_name ?: 'Pelanggan Walk-in (Umum)' }}</span>
                    </div>
                    @if ($sale->institution_name)
                        <div class="mb-2">
                            <small class="text-muted d-block">Instansi / Toko Pembeli:</small>
                            <span class="fw-bold text-primary"><i
                                    class="bi bi-building me-1"></i>{{ $sale->institution_name }}</span>
                        </div>
                    @endif
                    <div class="mb-2">
                        <small class="text-muted d-block">No. Telepon / WA:</small>
                        <span>{{ $sale->buyer_phone ?: '-' }}</span>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted d-block">Kasir / Operator:</small>
                        <span>{{ $sale->user->name ?? 'Admin POS' }}</span>
                    </div>
                    <div class="mb-0">
                        <small class="text-muted d-block">Catatan Nota:</small>
                        <span class="fst-italic text-secondary">{{ $sale->notes ?: '-' }}</span>
                    </div>
                </div>
            </div>

            <!-- Packaging & Scan Status Card -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-box-seam text-primary fs-5"></i>
                        <h6 class="card-title fw-bold mb-0">Status Pengemasan</h6>
                    </div>
                    <span class="badge {{ $sale->packing_badge_class }}" id="cardPackingBadge">
                        {{ $sale->packing_status_label }}
                    </span>
                </div>
                <div class="card-body p-3" style="font-size: 0.85rem;">
                    @php
                        $tQty = (int) $sale->items->sum('quantity');
                        $pQty = (int) $sale->items->sum('packed_quantity');
                        $pct = $tQty > 0 ? min(100, round(($pQty / $tQty) * 100)) : 0;
                    @endphp
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted fw-semibold">Progres Kemas:</span>
                            <strong class="font-monospace text-dark" id="txtCardPackingProgress">{{ $pQty }} /
                                {{ $tQty }} Pcs ({{ $pct }}%)</strong>
                        </div>
                        <div class="progress" style="height: 8px; border-radius: 999px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $pct }}%;"
                                id="barCardPackingProgress" aria-valuenow="{{ $pct }}" aria-valuemin="0"
                                aria-valuemax="100"></div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between py-1.5 border-bottom">
                        <span class="text-muted">Petugas Kemas:</span>
                        <strong class="text-dark"
                            id="txtCardPackerName">{{ $sale->packer->name ?? ($sale->packed_by ? 'User #' . $sale->packed_by : '-') }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-1.5 mb-3 border-bottom">
                        <span class="text-muted">Waktu Selesai:</span>
                        <strong class="text-dark"
                            id="txtCardPackedAt">{{ $sale->packed_at ? \Carbon\Carbon::parse($sale->packed_at)->format('d/m/Y H:i') : '-' }}</strong>
                    </div>

                    @if ($sale->status !== 'cancelled')
                        <div class="d-grid gap-2">
                            <button type="button"
                                class="btn btn-primary btn-sm fw-semibold shadow-sm d-flex align-items-center justify-content-center gap-1.5 py-2"
                                data-bs-toggle="modal" data-bs-target="#modalScanKemasOffline">
                                <i class="bi bi-upc-scan fs-6"></i> Buka Layar Scan Kemas
                            </button>
                            <a href="{{ route('v2.scanner_gudang.index', ['scan' => $sale->sale_number]) }}"
                                target="_blank"
                                class="btn btn-outline-dark btn-sm fw-semibold d-flex align-items-center justify-content-center gap-1.5">
                                <i class="bi bi-fullscreen"></i> Terminal Scanner Gudang
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="card-title fw-bold mb-0">Status Pembayaran</h6>
                </div>
                <div class="card-body p-3" style="font-size: 0.85rem;">
                    @php
                        $sisa = max(0, (float) $sale->grand_total - (float) $sale->paid_amount);
                        $isLunas = (float) $sale->paid_amount >= (float) $sale->grand_total;
                    @endphp
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Total Tagihan:</span>
                        <span class="fw-bold font-monospace">Rp
                            {{ number_format($sale->grand_total, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Total Terbayar:</span>
                        <span class="fw-bold font-monospace text-success">Rp
                            {{ number_format($sale->paid_amount, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-2 mb-3">
                        <span class="text-muted">Sisa Piutang:</span>
                        <span class="fw-bold font-monospace {{ $sisa > 0 ? 'text-danger fs-6' : 'text-muted' }}">
                            {{ $sisa > 0 ? 'Rp ' . number_format($sisa, 0, ',', '.') : 'LUNAS (Rp 0)' }}
                        </span>
                    </div>
                    <div
                        class="text-center p-2 rounded-3 {{ $isLunas ? 'bg-success bg-opacity-10 text-success border border-success' : 'bg-danger bg-opacity-10 text-danger border border-danger' }}">
                        <i class="bi bi-{{ $isLunas ? 'check-circle-fill' : 'exclamation-triangle-fill' }} me-1"></i>
                        <strong>{{ $isLunas ? 'PEMBAYARAN LUNAS' : 'MASIH ADA SISA PIUTANG' }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($sale->status !== 'cancelled')
        {{-- Modal Batalkan Transaksi --}}
        <div class="modal fade" id="modalCancelSaleDetail" tabindex="-1" aria-labelledby="modalCancelSaleDetailLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-danger text-white py-2.5">
                        <h6 class="modal-title fw-bold" id="modalCancelSaleDetailLabel">
                            <i class="bi bi-exclamation-triangle-fill me-1.5"></i> Batalkan Transaksi Penjualan
                            #{{ $sale->sale_number }}
                        </h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <form method="POST" action="{{ route('v2.penjualan_offline.cancel', $sale->id) }}">
                        @csrf
                        <div class="modal-body p-4">
                            <div class="alert alert-warning py-2 px-3 small d-flex align-items-center gap-2 mb-3">
                                <i class="bi bi-info-circle-fill fs-5 text-warning"></i>
                                <div>
                                    Pembatalan transaksi akan mengubah status nota menjadi <strong>Batal</strong> dan
                                    otomatis mengembalikan stok barang yang terpotong ke inventory.
                                </div>
                            </div>

                            <div class="mb-2">
                                <label for="cancellation_reason_detail" class="form-label small fw-bold text-dark">
                                    Alasan Pembatalan <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control" id="cancellation_reason_detail" name="cancellation_reason" rows="3"
                                    placeholder="Tuliskan alasan pembatalan transaksi ini..." required minlength="5"></textarea>
                                <div class="form-text small text-muted">Minimal 5 karakter. Wajib diisi sebagai catatan
                                    audit.</div>
                            </div>
                        </div>
                        <div class="modal-footer bg-light py-2 px-3">
                            <button type="button" class="btn btn-sm btn-outline-secondary px-3"
                                data-bs-dismiss="modal">Tutup</button>
                            <button type="submit" class="btn btn-sm btn-danger px-3 fw-bold shadow-sm">
                                <i class="bi bi-check-circle me-1"></i> Ya, Batalkan Transaksi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modal Catat Pembayaran / Cicilan --}}
        @if ($sale->status !== 'cancelled' && $sale->remaining_amount > 0)
            <div class="modal fade" id="modalRecordPaymentShow" tabindex="-1"
                aria-labelledby="modalRecordPaymentShowLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header bg-success text-white py-2.5">
                            <h6 class="modal-title fw-bold" id="modalRecordPaymentShowLabel">
                                <i class="bi bi-cash-coin me-1.5"></i> Catat Pembayaran / Cicilan
                                #{{ $sale->sale_number }}
                            </h6>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <form method="POST" action="{{ route('offline_sales.payments.store', $sale->id) }}"
                            id="formRecordPaymentShow">
                            @csrf
                            <div class="modal-body p-3">
                                <div class="p-2.5 bg-light rounded-3 border mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="small text-muted">Grand Total Tagihan:</span>
                                        <span class="font-monospace fw-bold">Rp
                                            {{ number_format($sale->grand_total, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="small text-muted">Sudah Dibayar:</span>
                                        <span class="font-monospace fw-semibold text-success">Rp
                                            {{ number_format($sale->paid_amount, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center border-top pt-1">
                                        <span class="small fw-bold text-dark">Sisa Tagihan / Piutang:</span>
                                        <span class="font-monospace fw-bold text-danger fs-6">Rp
                                            {{ number_format($sale->remaining_amount, 0, ',', '.') }}</span>
                                    </div>
                                </div>

                                <div class="row g-2 mb-2.5">
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold text-dark mb-1">Tanggal Bayar <span
                                                class="text-danger">*</span></label>
                                        <input type="date" name="payment_date" class="form-control form-control-sm"
                                            value="{{ date('Y-m-d') }}" required>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold text-dark mb-1">Metode Bayar <span
                                                class="text-danger">*</span></label>
                                        <select name="payment_method" class="form-select form-select-sm fw-semibold"
                                            required>
                                            <option value="tunai" selected>Tunai / Cash</option>
                                            <option value="transfer">Transfer Bank</option>
                                            @if ($sale->customer_id)
                                                <option value="reseller_balance">Saldo Reseller</option>
                                            @endif
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-2.5">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label small fw-semibold text-dark mb-0">Nominal Pembayaran (Rp)
                                            <span class="text-danger">*</span></label>
                                        <button type="button"
                                            class="btn btn-link btn-xs text-primary p-0 text-decoration-none small fw-semibold"
                                            id="btn_modal_pay_all">Lunasi Semua</button>
                                    </div>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text py-0 px-2 text-success fw-bold">Rp</span>
                                        <input type="text" name="amount" id="modal_payment_amount"
                                            class="form-control form-control-sm text-end font-monospace fw-bold text-success fs-6"
                                            value="{{ number_format($sale->remaining_amount, 0, ',', '.') }}"
                                            inputmode="numeric" required>
                                    </div>
                                </div>

                                <div class="mb-2.5">
                                    <label class="form-label small fw-semibold text-dark mb-1">
                                        <i class="bi bi-bank text-primary me-1"></i>Kas / Bank Tujuan Masuk <span
                                            class="text-danger">*</span>
                                    </label>
                                    <select name="payment_destination" class="form-select form-select-sm fw-semibold"
                                        required>
                                        @if (isset($bankAccounts) && $bankAccounts->isNotEmpty())
                                            @foreach ($bankAccounts as $bank)
                                                <option value="{{ $bank->bank_name }}"
                                                    {{ $sale->payment_destination == $bank->bank_name || $loop->first ? 'selected' : '' }}>
                                                    {{ $bank->bank_name }}
                                                    {{ $bank->account_number ? '(' . $bank->account_number . ')' : '' }} —
                                                    Saldo: Rp {{ number_format($bank->current_balance, 0, ',', '.') }}
                                                </option>
                                            @endforeach
                                        @else
                                            <option value="kas_besar">Kas Toko (Utama)</option>
                                            <option value="kas_kecil">Kas Kecil (Operasional)</option>
                                        @endif
                                    </select>
                                </div>

                                <div class="mb-2.5">
                                    <label class="form-label small fw-semibold text-dark mb-1">No. Referensi / Bukti
                                        Transfer (Opsional)</label>
                                    <input type="text" name="reference_number" class="form-control form-control-sm"
                                        placeholder="Contoh: REF-12345678">
                                </div>

                                <div class="mb-2">
                                    <label class="form-label small fw-semibold text-dark mb-1">Catatan / Keterangan
                                        (Opsional)</label>
                                    <input type="text" name="notes" class="form-control form-control-sm"
                                        value="{{ $sale->status === \App\Models\OfflineSale::STATUS_WAITING_DP ? 'Pembayaran DP' : 'Cicilan Tagihan POS' }}"
                                        placeholder="Contoh: Pelunasan piutang, DP tambahan...">
                                </div>
                            </div>
                            <div class="modal-footer bg-light py-2 px-3">
                                <button type="button" class="btn btn-sm btn-outline-secondary px-3"
                                    data-bs-dismiss="modal">Tutup</button>
                                <button type="submit" class="btn btn-sm btn-success px-4 fw-bold shadow-sm">
                                    <i class="bi bi-check-circle me-1"></i> Simpan Pembayaran
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            @push('scripts')
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const payInput = document.getElementById('modal_payment_amount');
                        const payForm = document.getElementById('formRecordPaymentShow');
                        const btnPayAll = document.getElementById('btn_modal_pay_all');

                        function formatRupiah(num) {
                            return new Intl.NumberFormat('id-ID').format(Math.round(num || 0));
                        }

                        function parseRupiah(str) {
                            if (!str) return 0;
                            return parseFloat(str.toString().replace(/[^0-9]/g, '')) || 0;
                        }

                        if (payInput) {
                            payInput.addEventListener('input', function() {
                                this.value = formatRupiah(parseRupiah(this.value));
                            });
                        }

                        if (btnPayAll && payInput) {
                            btnPayAll.addEventListener('click', function(e) {
                                e.preventDefault();
                                payInput.value = formatRupiah({{ (int) $sale->remaining_amount }});
                            });
                        }

                        if (payForm && payInput) {
                            payForm.addEventListener('submit', function() {
                                payInput.value = parseRupiah(payInput.value);
                            });
                        }
                    });
                </script>
            @endpush
        @endif

        {{-- Modal Terbitkan SPK Produksi --}}
        @php
            $spkItems = $sale->items->map(
                fn($i) => [
                    'id' => $i->id,
                    'name' => $i->product_name ?? ($i->masterProduct->name ?? 'Produk POS'),
                    'qty' => (int) $i->quantity,
                    'sku' => $i->sku ?? ($i->masterProduct->sku ?? ''),
                ],
            );
        @endphp
        <div class="modal fade" id="modalCreateSpk" tabindex="-1" aria-labelledby="modalCreateSpkLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
                <form method="POST" action="{{ route('offline_sales.create_spk', $sale->id) }}"
                    class="modal-content border-0"
                    style="display: flex; flex-direction: column; height: 100%; max-height: 100vh; overflow: hidden;">
                    @csrf
                    <div class="modal-header bg-warning bg-opacity-10 border-bottom px-4 py-3" style="flex-shrink: 0;">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle bg-warning bg-opacity-25 p-2 text-warning d-flex align-items-center justify-content-center"
                                style="width: 38px; height: 38px;">
                                <i class="bi bi-hammer fs-6 text-dark"></i>
                            </div>
                            <div>
                                <h5 class="modal-title fw-bold text-dark mb-0" id="modalCreateSpkLabel">
                                    Terbitkan SPK Produksi
                                </h5>
                                <small class="text-muted" style="font-size: 0.75rem;">Konfigurasi Nomor Produksi, Tahap
                                    Awal, dan Pembagian SPK Pesanan Offline</small>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body p-4" style="flex: 1 1 auto; overflow-y: auto !important; min-height: 0;">
                        <div class="container-fluid px-lg-3 py-1">
                            {{-- Info Banner --}}
                            <div
                                class="d-flex justify-content-between align-items-center mb-3 p-3 bg-light rounded border flex-wrap gap-2">
                                <div>
                                    <small class="text-muted d-block text-uppercase fw-semibold"
                                        style="font-size: 0.7rem;">Transaksi Penjualan</small>
                                    <span
                                        class="fw-bold font-monospace text-primary fs-5">#{{ $sale->sale_number }}</span>
                                    <span class="text-muted ms-2">&bull; Pembeli:
                                        <strong>{{ $sale->buyer_name ?: 'Umum (Pelanggan Walk-in)' }}</strong></span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span
                                        class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle py-2 px-3 fs-7">
                                        <i class="bi bi-layers me-1"></i> 1 No. Produksi = Multi SPK Produk
                                    </span>
                                </div>
                            </div>

                            {{-- Parameter Utama SPK --}}
                            <div class="row g-3 mb-4">
                                <div class="col-lg-4 col-md-6">
                                    <label for="spk_no_produksi" class="form-label fw-semibold small text-dark mb-1">
                                        <i class="bi bi-hash text-primary me-1"></i>Nomor / Kode Produksi <span
                                            class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="no_produksi" id="spk_no_produksi"
                                        class="form-control form-control-sm font-monospace fw-bold"
                                        value="{{ \App\Models\Spk::generateNoProduksi() }}" required>
                                    <div class="form-text text-muted" style="font-size: 0.75rem;">
                                        Satu kode produksi ini akan menaungi seluruh SPK pesanan ini.
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6">
                                    <label for="spk_tahap_saat_ini" class="form-label fw-semibold small text-dark mb-1">
                                        <i class="bi bi-list-task text-primary me-1"></i>Tahap Awal Produksi <span
                                            class="text-danger">*</span>
                                    </label>
                                    <select name="tahap_saat_ini" id="spk_tahap_saat_ini"
                                        class="form-select form-select-sm fw-semibold" required>
                                        <option value="Antrian &amp; Sampling" selected>⏳ Antrian &amp; Sampling (Langsung
                                            Antrian)</option>
                                        <option value="Perencanaan">📋 Perencanaan / Pesanan Baru</option>
                                        <option value="Tahap Pemotongan">✂️ Tahap Pemotongan</option>
                                        <option value="Tahap Jahit">🪡 Tahap Jahit</option>
                                    </select>
                                    <div class="form-text text-success" style="font-size: 0.75rem;">
                                        <i class="bi bi-check-circle me-1"></i>SPK langsung masuk antrian aktif (bukan
                                        Draft).
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-12">
                                    <label for="spk_deadline" class="form-label fw-semibold small text-dark mb-1">
                                        <i class="bi bi-calendar-event text-primary me-1"></i>Target Deadline SPK Produksi
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" name="deadline" id="spk_deadline"
                                        class="form-control form-control-sm"
                                        value="{{ now()->addDays(7)->format('Y-m-d') }}" required>
                                    <div class="form-text text-muted" style="font-size: 0.75rem;">Target tanggal selesai
                                        pengerjaan oleh tim produksi.</div>
                                </div>
                            </div>

                            {{-- Layout 2 Kolom --}}
                            <div class="row g-4">
                                {{-- Kolom Kiri: Tabel Pembagian SPK --}}
                                <div class="col-lg-7">
                                    <div class="border rounded p-3 bg-light shadow-sm">
                                        <div
                                            class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                            <div>
                                                <label class="form-label fw-bold text-dark mb-0 fs-6">
                                                    <i class="bi bi-diagram-3 me-1 text-primary"></i>Tentukan Pembagian SPK
                                                    (SPK 1 - SPK 5)
                                                </label>
                                                <div class="text-muted small" style="font-size: 0.75rem;">
                                                    Pilih SPK tujuan untuk masing-masing item transaksi.
                                                </div>
                                            </div>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button"
                                                    class="btn btn-outline-primary btn-sm py-1 px-2.5 fw-semibold"
                                                    id="btn-quick-combine" title="Semua item dijadikan 1 SPK">
                                                    <i class="bi bi-link-45deg me-1"></i>Gabung Semua (1 SPK)
                                                </button>
                                                <button type="button"
                                                    class="btn btn-outline-secondary btn-sm py-1 px-2.5 fw-semibold"
                                                    id="btn-quick-split"
                                                    title="Pisahkan tiap item menjadi SPK sendiri-sendiri">
                                                    <i class="bi bi-scissors me-1"></i>Pisah Masing-masing
                                                </button>
                                            </div>
                                        </div>

                                        <div class="table-responsive bg-white rounded border mb-0">
                                            <table class="table table-sm table-hover align-middle mb-0"
                                                style="font-size: 0.85rem;">
                                                <thead class="table-light sticky-top">
                                                    <tr>
                                                        <th style="width: 40px;" class="text-center">#</th>
                                                        <th>PRODUK / ITEM PESANAN</th>
                                                        <th class="text-center" style="width: 80px;">QTY</th>
                                                        <th style="width: 210px;">PILIH SPK TUJUAN</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="modal-spk-items-table">
                                                    @foreach ($spkItems as $idx => $it)
                                                        <tr>
                                                            <td class="text-center text-muted">{{ $idx + 1 }}</td>
                                                            <td>
                                                                <div class="fw-semibold text-dark">{{ $it['name'] }}
                                                                </div>
                                                                @if (!empty($it['sku']))
                                                                    <small
                                                                        class="text-muted font-monospace">{{ $it['sku'] }}</small>
                                                                @endif
                                                            </td>
                                                            <td class="text-center fw-bold font-monospace">
                                                                {{ $it['qty'] }} Pcs</td>
                                                            <td>
                                                                <select name="spk_group[{{ $it['id'] }}]"
                                                                    class="form-select form-select-sm fw-bold spk-group-select"
                                                                    data-item-id="{{ $it['id'] }}">
                                                                    <option value="1" selected>Masuk ke SPK 1</option>
                                                                    <option value="2">Masuk ke SPK 2</option>
                                                                    <option value="3">Masuk ke SPK 3</option>
                                                                    <option value="4">Masuk ke SPK 4</option>
                                                                    <option value="5">Masuk ke SPK 5</option>
                                                                    <option value="0">Lewati (Jangan Buat SPK)
                                                                    </option>
                                                                </select>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                {{-- Kolom Kanan: Pratinjau Real-Time --}}
                                <div class="col-lg-5">
                                    <div class="p-3 bg-white rounded border shadow-sm d-flex flex-column">
                                        <div
                                            class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                            <div>
                                                <h6 class="fw-bold text-dark mb-0">
                                                    <i class="bi bi-clipboard-check me-1 text-primary"></i>Ringkasan SPK
                                                    yang Terbit
                                                </h6>
                                                <small class="text-muted" style="font-size: 0.72rem;">Pratinjau otomatis
                                                    SPK yang akan dibentuk</small>
                                            </div>
                                            <span class="badge bg-primary px-2.5 py-1.5 fs-7"
                                                id="modal-spk-summary-badge">0 SPK</span>
                                        </div>
                                        <div id="modal-spk-summary-list" class="d-flex flex-column gap-2"
                                            style="font-size: 0.82rem;">
                                        </div>
                                        <div class="mt-3 pt-2 border-top text-muted small" style="font-size: 0.75rem;">
                                            <i class="bi bi-info-circle text-primary me-1"></i>
                                            Setiap SPK akan memiliki nomor unik di modul SPK di bawah No. Produksi yang
                                            sama.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer px-4 py-3 bg-light border-top" style="flex-shrink: 0;">
                        <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">
                            <i class="bi bi-x me-1"></i> Tutup
                        </button>
                        <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm">
                            <i class="bi bi-hammer me-1"></i> Terbitkan SPK Sekarang
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Datalist Kategori SPK --}}
        <datalist id="kategori_datalist">
            <option value="Batik"></option>
            <option value="Baju Olah Raga"></option>
            <option value="Seragam Sekolah"></option>
            <option value="Jaket & Outer"></option>
            <option value="Kaos / T-Shirt"></option>
            <option value="Kemeja & PDH"></option>
            <option value="Almamater & Jas"></option>
            <option value="Gamis & Busana Muslim"></option>
            <option value="Jersey Printing"></option>
            <option value="Topi & Aksesoris"></option>
            <option value="Celana & Rok"></option>
            <option value="Rompi & Wearpack"></option>
            <option value="Tas & Merchandise"></option>
        </datalist>

        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const rawItems = @json($spkItems);
                    const modalCreateSpk = document.getElementById('modalCreateSpk');
                    if (!modalCreateSpk) return;

                    let userCategories = {};

                    function detectCategory(items) {
                        if (!items || items.length === 0) return 'Produk SPK';
                        const allNames = items.map(it => it.name).join(' ');

                        if (/batik/i.test(allNames)) return 'Batik';
                        if (/olah\s*raga|olahraga|training/i.test(allNames)) return 'Baju Olah Raga';
                        if (/topi/i.test(allNames)) return 'Topi & Aksesoris';
                        if (/jaket|hoodie|sweater/i.test(allNames)) return 'Jaket & Outer';
                        if (/kaos|t-shirt|tshirt/i.test(allNames)) return 'Kaos / T-Shirt';
                        if (/kemeja|pdh|pdl/i.test(allNames)) return 'Kemeja & PDH';
                        if (/jas|almamater|blazer/i.test(allNames)) return 'Almamater & Jas';
                        if (/jersey/i.test(allNames)) return 'Jersey Printing';
                        if (/gamis|busana muslim|koko/i.test(allNames)) return 'Gamis & Busana Muslim';
                        if (/seragam/i.test(allNames)) return 'Seragam Sekolah';
                        if (/celana|rok/i.test(allNames)) return 'Celana & Rok';
                        if (/rompi|wearpack/i.test(allNames)) return 'Rompi & Wearpack';

                        let clean = (items[0]?.name || '').replace(/\s*-\s*[A-Z0-9\s()]+$/i, '').trim();
                        if (clean.length > 30) clean = clean.substring(0, 30) + '...';
                        return clean || 'Produk SPK';
                    }

                    function renderSpkSummary() {
                        const tbody = document.getElementById('modal-spk-items-table');
                        const summaryList = document.getElementById('modal-spk-summary-list');
                        const summaryBadge = document.getElementById('modal-spk-summary-badge');
                        const noProdInput = document.getElementById('spk_no_produksi');
                        const currentNoProduksi = noProdInput ? noProdInput.value : '';

                        if (!summaryList || !tbody) return;

                        const selects = tbody.querySelectorAll('.spk-group-select');
                        const groups = {};

                        selects.forEach(sel => {
                            const grpVal = sel.value;
                            if (grpVal === '0') return;
                            const itemId = sel.getAttribute('data-item-id');
                            const item = rawItems.find(i => String(i.id) === String(itemId)) || {
                                name: 'Produk',
                                qty: 1
                            };

                            if (!groups[grpVal]) {
                                groups[grpVal] = {
                                    groupNumber: grpVal,
                                    items: [],
                                    totalQty: 0
                                };
                            }
                            groups[grpVal].items.push(item);
                            groups[grpVal].totalQty += parseInt(item.qty, 10) || 0;
                        });

                        const groupKeys = Object.keys(groups).sort((a, b) => parseInt(a) - parseInt(b));
                        summaryBadge.textContent = groupKeys.length + ' SPK Akan Diterbitkan';

                        const submitBtn = modalCreateSpk.querySelector('button[type="submit"]');
                        if (groupKeys.length === 0) {
                            summaryList.innerHTML =
                                '<div class="alert alert-danger py-2 mb-0 small"><i class="bi bi-exclamation-triangle me-1"></i>Pilih minimal 1 SPK tujuan untuk item pesanan.</div>';
                            if (submitBtn) submitBtn.disabled = true;
                            return;
                        }
                        if (submitBtn) submitBtn.disabled = false;

                        const badgeColors = ['primary', 'success', 'warning text-dark', 'info', 'danger', 'secondary'];
                        let html = '';

                        groupKeys.forEach((key, kIdx) => {
                            const grp = groups[key];
                            const colorClass = badgeColors[kIdx % badgeColors.length];

                            if (!userCategories[key] || userCategories[key].trim() === '') {
                                userCategories[key] = detectCategory(grp.items);
                            }
                            const currentCat = userCategories[key];
                            const itemsDetail = grp.items.map(it =>
                                `&bull; ${it.name} <strong>(${it.qty} pcs)</strong>`).join('<br>');

                            html += `
                <div class="p-3 rounded bg-light border shadow-sm mb-2">
                    <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom">
                        <div class="d-flex align-items-center gap-1.5">
                            <span class="badge bg-${colorClass} px-2 py-1 fw-bold fs-7">
                                <i class="bi bi-file-earmark-text me-1"></i>SPK ${grp.groupNumber}
                            </span>
                            <span class="badge bg-white text-secondary border font-monospace" style="font-size: 0.72rem;">
                                ${grp.totalQty} Pcs total
                            </span>
                        </div>
                        <span class="badge bg-white text-primary border font-monospace small"><i class="bi bi-hash me-0.5"></i>${currentNoProduksi || '-'}</span>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-bold mb-1 d-flex justify-content-between align-items-center" style="font-size: 0.75rem; color: #4f46e5;">
                            <span>🏷️ KATEGORI PRODUK SPK ${grp.groupNumber} <span class="text-danger">*</span></span>
                            <span class="text-muted fw-normal" style="font-size: 0.68rem;">(Tampil di Tab SPK)</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white text-primary"><i class="bi bi-tags"></i></span>
                            <input type="text"
                                   name="spk_kategori[${grp.groupNumber}]"
                                   class="form-control form-control-sm fw-bold text-dark spk-kategori-input"
                                   list="kategori_datalist"
                                   value="${currentCat.replace(/"/g, '&quot;')}"
                                   placeholder="Contoh: Batik, Baju Olah Raga, Jaket..."
                                   data-group="${grp.groupNumber}"
                                   autocomplete="off"
                                   required>
                        </div>
                    </div>

                    <div class="bg-white rounded p-2 border">
                        <div class="text-muted small mb-1 fw-semibold" style="font-size: 0.7rem;">Item Pesanan (${grp.items.length}):</div>
                        <div class="text-secondary ps-1" style="font-size: 0.74rem;">
                            ${itemsDetail}
                        </div>
                    </div>
                </div>
            `;
                        });

                        summaryList.innerHTML = html;

                        summaryList.querySelectorAll('.spk-kategori-input').forEach(inp => {
                            inp.addEventListener('input', function() {
                                userCategories[this.getAttribute('data-group')] = this.value;
                            });
                        });
                    }

                    const tbody = document.getElementById('modal-spk-items-table');
                    if (tbody) {
                        tbody.querySelectorAll('.spk-group-select').forEach(sel => {
                            sel.addEventListener('change', renderSpkSummary);
                        });
                    }

                    const btnCombine = document.getElementById('btn-quick-combine');
                    if (btnCombine) {
                        btnCombine.addEventListener('click', function() {
                            modalCreateSpk.querySelectorAll('.spk-group-select').forEach(sel => {
                                sel.value = '1';
                            });
                            renderSpkSummary();
                        });
                    }

                    const btnSplit = document.getElementById('btn-quick-split');
                    if (btnSplit) {
                        btnSplit.addEventListener('click', function() {
                            modalCreateSpk.querySelectorAll('.spk-group-select').forEach((sel, idx) => {
                                sel.value = String(Math.min(idx + 1, 5));
                            });
                            renderSpkSummary();
                        });
                    }

                    const noProdInput = document.getElementById('spk_no_produksi');
                    if (noProdInput) {
                        noProdInput.addEventListener('input', renderSpkSummary);
                    }

                    modalCreateSpk.addEventListener('shown.bs.modal', renderSpkSummary);
                    renderSpkSummary();
                });
            </script>
        @endpush
        @if ($sale->status !== 'cancelled')
            {{-- Modal Scan & Kemas Offline --}}
            <div class="modal fade" id="modalScanKemasOffline" tabindex="-1"
                aria-labelledby="modalScanKemasOfflineLabel" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content border-0 shadow-lg rounded-3">
                        <div class="modal-header bg-dark text-white py-3 px-4"
                            style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;">
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-primary bg-opacity-25 p-2 rounded-circle text-primary d-flex align-items-center justify-content-center"
                                    style="width: 36px; height: 36px;">
                                    <i class="bi bi-upc-scan fs-5 text-white"></i>
                                </div>
                                <div>
                                    <h6 class="modal-title fw-bold mb-0 text-white" id="modalScanKemasOfflineLabel">
                                        Scan & Kemas Nota #{{ $sale->sale_number }}
                                    </h6>
                                    <small class="text-white-50 fs-12">
                                        {{ $sale->buyer_name ?: 'Pelanggan Walk-in (Umum)' }} &bull;
                                        {{ $sale->store->store_name ?? 'Toko POS' }}
                                    </small>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge {{ $sale->packing_badge_class }} px-2.5 py-1.5"
                                    id="modalHeaderPackingBadge">
                                    {{ $sale->packing_status_label }}
                                </span>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                        </div>

                        <div class="modal-body p-4 bg-white">
                            {{-- Audio & Quick Tools Bar --}}
                            <div
                                class="d-flex justify-content-between align-items-center pb-3 mb-3 border-bottom flex-wrap gap-2">
                                <div class="form-check form-switch d-flex align-items-center gap-2 mb-0">
                                    <input class="form-check-input" type="checkbox" id="modalSoundToggle" checked
                                        style="width: 2.3em; height: 1.15em; cursor: pointer;">
                                    <label class="form-check-label text-muted fs-12 mb-0 fw-semibold"
                                        for="modalSoundToggle" style="cursor: pointer;">
                                        <i class="bi bi-volume-up-fill text-info me-1"></i> Suara Scanner
                                    </label>
                                </div>
                                <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                    <button type="button" class="btn btn-xs btn-outline-success fw-semibold px-2.5 py-1"
                                        id="btnModalMarkAllDone" style="font-size: 0.75rem;">
                                        <i class="bi bi-check-all me-1"></i> Tandai Semua Selesai
                                    </button>
                                    <button type="button" class="btn btn-xs btn-outline-danger fw-semibold px-2.5 py-1"
                                        id="btnModalResetProgress" style="font-size: 0.75rem;">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Progres
                                    </button>
                                    <button type="button" class="btn btn-sm btn-success fw-bold px-3 py-1 shadow-sm btn-trigger-save-kemas ms-1"
                                        style="font-size: 0.8rem;">
                                        <i class="bi bi-check-circle-fill me-1"></i> Selesai & Simpan Kemasan
                                    </button>
                                </div>
                            </div>

                            {{-- Barcode / SKU Scan Input Field --}}
                            <div class="mb-3">
                                <label
                                    class="form-label fw-bold text-dark fs-12 mb-1.5 d-flex justify-content-between align-items-center">
                                    <span><i class="bi bi-upc-scan text-primary me-1"></i> Tembak Barcode / SKU
                                        Produk:</span>
                                    <span class="badge bg-light text-secondary border font-monospace fs-11">Tekan Enter
                                        otomatis dihitung</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-primary border-end-0"><i
                                            class="bi bi-barcode fs-5"></i></span>
                                    <input type="text"
                                        class="form-control form-control-lg font-monospace text-uppercase border-start-0 border-end-0"
                                        id="modalBarcodeScanInput"
                                        placeholder="Tembak barcode produk atau ketik SKU disini..." autocomplete="off"
                                        style="font-size: 1rem; font-weight: 600; letter-spacing: 0.5px;">
                                    <button class="btn btn-outline-secondary" type="button" id="btnModalClearScanInput"
                                        title="Bersihkan input">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                                <div class="form-text text-muted fs-11 mt-1">
                                    Scanner gun akan membaca barcode atau SKU yang tertera pada kemasan produk secara
                                    otomatis.
                                </div>
                            </div>

                            {{-- Alert Live Feedback --}}
                            <div id="modalScanFeedback"
                                class="alert d-none py-2 px-3 mb-3 border fs-12 d-flex align-items-center justify-content-between"
                                role="alert">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi fs-5" id="modalScanFeedbackIcon"></i>
                                    <span id="modalScanFeedbackText">-</span>
                                </div>
                                <button type="button" class="btn-close fs-11"
                                    onclick="$('#modalScanFeedback').addClass('d-none')"></button>
                            </div>

                            {{-- Progress Bar Container --}}
                            @php
                                $initTotal = (int) $sale->items->sum('quantity');
                                $initPacked = (int) $sale->items->sum('packed_quantity');
                                $initPct = $initTotal > 0 ? min(100, round(($initPacked / $initTotal) * 100)) : 0;
                            @endphp
                            <div class="bg-light p-3 rounded-3 border mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1.5">
                                    <span class="fw-semibold text-secondary fs-12">Total Kemasan Terverifikasi:</span>
                                    <strong class="font-monospace text-dark fs-13" id="modalProgressText">
                                        <span id="modalPackedTotalCount">{{ $initPacked }}</span> / <span
                                            id="modalTargetTotalCount">{{ $initTotal }}</span> Pcs (<span
                                            id="modalPackedPct">{{ $initPct }}</span>%)
                                    </strong>
                                </div>
                                <div class="progress" style="height: 10px; border-radius: 999px;">
                                    <div class="progress-bar {{ $initPct >= 100 ? 'bg-success' : 'bg-primary' }} progress-bar-striped progress-bar-animated"
                                        role="progressbar" style="width: {{ $initPct }}%;" id="modalProgressBar"
                                        aria-valuenow="{{ $initPct }}" aria-valuemin="0" aria-valuemax="100">
                                    </div>
                                </div>
                            </div>

                            {{-- Items Checklist Table --}}
                            <div class="border rounded-3 overflow-hidden">
                                <div class="table-responsive" style="max-height: 280px;">
                                    <table class="table table-hover align-middle mb-0" style="font-size: 0.82rem;">
                                        <thead class="table-light text-uppercase fw-semibold"
                                            style="font-size: 0.72rem; position: sticky; top: 0; z-index: 1;">
                                            <tr>
                                                <th class="ps-3 py-2">Item Produk</th>
                                                <th class="text-center py-2" style="width: 100px;">Target</th>
                                                <th class="text-center py-2" style="width: 140px;">Hasil Scan</th>
                                                <th class="text-center pe-3 py-2" style="width: 110px;">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="modalItemsTbody">
                                            @foreach ($sale->items as $item)
                                                @php
                                                    $itQty = (int) $item->quantity;
                                                    $itPacked = (int) $item->packed_quantity;
                                                    $itDone = $itPacked >= $itQty;
                                                    $skuClean = trim($item->sku ?? ($item->masterProduct->sku ?? ''));
                                                    $barcodeClean = trim($item->masterProduct->barcode ?? '');
                                                @endphp
                                                <tr id="modalItemRow_{{ $item->id }}"
                                                    class="{{ $itDone ? 'table-success bg-opacity-25' : '' }}"
                                                    data-id="{{ $item->id }}"
                                                    data-sku="{{ strtolower($skuClean) }}"
                                                    data-barcode="{{ strtolower($barcodeClean) }}"
                                                    data-target="{{ $itQty }}"
                                                    data-name="{{ $item->product_name ?? ($item->masterProduct->name ?? 'Produk POS') }}">
                                                    <td class="ps-3 py-2.5">
                                                        <div class="fw-bold text-dark fs-12">
                                                            {{ $item->product_name ?? ($item->masterProduct->name ?? 'Produk POS') }}
                                                        </div>
                                                        <div class="d-flex align-items-center gap-1.5 flex-wrap mt-0.5">
                                                            <span class="badge bg-dark font-monospace"
                                                                style="font-size: 0.65rem;">SKU:
                                                                {{ $skuClean ?: '-' }}</span>
                                                            @if ($barcodeClean)
                                                                <span
                                                                    class="badge bg-light text-dark border font-monospace"
                                                                    style="font-size: 0.65rem;">
                                                                    <i
                                                                        class="bi bi-barcode me-0.5"></i>{{ $barcodeClean }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td
                                                        class="text-center py-2.5 font-monospace fw-bold fs-13 text-secondary">
                                                        {{ $itQty }} Pcs
                                                    </td>
                                                    <td class="text-center py-2.5">
                                                        <div class="input-group input-group-sm justify-content-center"
                                                            style="width: 120px; margin: 0 auto;">
                                                            <button class="btn btn-outline-secondary btn-item-adjust px-2"
                                                                type="button" data-id="{{ $item->id }}"
                                                                data-delta="-1" title="Kurangi 1">
                                                                <i class="bi bi-dash"></i>
                                                            </button>
                                                            <input type="text"
                                                                class="form-control text-center font-monospace fw-bold item-packed-qty-input {{ $itDone ? 'bg-success text-white' : 'bg-white' }}"
                                                                id="itemInputPacked_{{ $item->id }}"
                                                                value="{{ $itPacked }}" readonly
                                                                style="max-width: 50px;">
                                                            <button class="btn btn-outline-primary btn-item-adjust px-2"
                                                                type="button" data-id="{{ $item->id }}"
                                                                data-delta="1" title="Tambah 1">
                                                                <i class="bi bi-plus"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                    <td class="text-center pe-3 py-2.5">
                                                        <span
                                                            class="badge {{ $itDone ? 'bg-success text-white' : ($itPacked > 0 ? 'bg-warning text-dark' : 'bg-secondary text-white') }} rounded-pill px-2 py-1 fs-11"
                                                            id="itemStatusBadge_{{ $item->id }}">
                                                            <i
                                                                class="bi bi-{{ $itDone ? 'check-circle-fill' : ($itPacked > 0 ? 'hourglass-split' : 'dash-circle') }} me-0.5"></i>
                                                            {{ $itDone ? 'Lengkap' : ($itPacked > 0 ? 'Kurang ' . ($itQty - $itPacked) : 'Belum') }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer bg-light py-2.5 px-4 d-flex justify-content-between align-items-center">
                            <div>
                                <a href="{{ route('v2.scanner_gudang.index', ['scan' => $sale->sale_number]) }}"
                                    target="_blank" class="btn btn-sm btn-outline-dark fw-semibold"
                                    title="Buka terminal scanner gudang lengkap">
                                    <i class="bi bi-fullscreen me-1"></i> Buka Terminal Gudang
                                </a>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-sm btn-secondary px-3" data-bs-dismiss="modal">
                                    Tutup
                                </button>
                                <button type="button" class="btn btn-sm btn-success px-4 fw-bold shadow-sm"
                                    id="btnModalSaveKemas">
                                    <i class="bi bi-check-circle-fill me-1"></i> Selesai & Simpan Kemasan
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @push('scripts')
                <script>
                    $(document).ready(function() {
                        // -------------------------------------------------------------
                        // State Variabel Modal Scan Kemas Offline
                        // -------------------------------------------------------------
                        const offlineSaleId = {{ $sale->id }};
                        const offlineSaleNo = @json($sale->sale_number);
                        let itemsState = {};

                        // Inisialisasi state item dari server
                        @foreach ($sale->items as $it)
                            itemsState[{{ $it->id }}] = {
                                id: {{ $it->id }},
                                name: @json($it->product_name ?? ($it->masterProduct->name ?? 'Produk POS')),
                                sku: @json(strtolower(trim($it->sku ?? ($it->masterProduct->sku ?? '')))),
                                barcode: @json(strtolower(trim($it->masterProduct->barcode ?? ''))),
                                target: {{ (int) $it->quantity }},
                                packed: {{ (int) $it->packed_quantity }},
                            };
                        @endforeach

                        // -------------------------------------------------------------
                        // Web Audio Synthesizer (Buzzer & Beep)
                        // -------------------------------------------------------------
                        let offlineAudioCtx = null;

                        function getOfflineAudioCtx() {
                            if (!offlineAudioCtx) {
                                offlineAudioCtx = new(window.AudioContext || window.webkitAudioContext)();
                            }
                            return offlineAudioCtx;
                        }

                        function playOfflineTone(freq = 880, type = 'sine', duration = 0.12) {
                            if (!$('#modalSoundToggle').is(':checked')) return;
                            try {
                                const ctx = getOfflineAudioCtx();
                                if (ctx.state === 'suspended') ctx.resume();
                                const osc = ctx.createOscillator();
                                const gain = ctx.createGain();
                                osc.type = type;
                                osc.frequency.setValueAtTime(freq, ctx.currentTime);
                                gain.gain.setValueAtTime(0.15, ctx.currentTime);
                                gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + duration);
                                osc.connect(gain);
                                gain.connect(ctx.destination);
                                osc.start();
                                osc.stop(ctx.currentTime + duration);
                            } catch (e) {
                                console.error('Audio error:', e);
                            }
                        }

                        function playOfflineSuccess() {
                            playOfflineTone(880, 'sine', 0.08);
                            setTimeout(() => playOfflineTone(1320, 'sine', 0.12), 80);
                        }

                        function playOfflineCompleteFanfare() {
                            playOfflineTone(523, 'sine', 0.1);
                            setTimeout(() => playOfflineTone(659, 'sine', 0.1), 100);
                            setTimeout(() => playOfflineTone(784, 'sine', 0.1), 200);
                            setTimeout(() => playOfflineTone(1046, 'sine', 0.25), 300);
                        }

                        function playOfflineError() {
                            playOfflineTone(220, 'sawtooth', 0.25);
                        }

                        // -------------------------------------------------------------
                        // Render Progress & Update Item Row
                        // -------------------------------------------------------------
                        function updateProgressUi() {
                            let totalTarget = 0;
                            let totalPacked = 0;
                            let allDone = true;

                            Object.values(itemsState).forEach(it => {
                                totalTarget += it.target;
                                totalPacked += it.packed;
                                if (it.packed < it.target) allDone = false;

                                // Update row item
                                const row = $(`#modalItemRow_${it.id}`);
                                const input = $(`#itemInputPacked_${it.id}`);
                                const badge = $(`#itemStatusBadge_${it.id}`);
                                const isDone = it.packed >= it.target;

                                input.val(it.packed);

                                if (isDone) {
                                    row.addClass('table-success bg-opacity-25');
                                    input.removeClass('bg-white').addClass('bg-success text-white');
                                    badge.removeClass('bg-warning bg-secondary text-dark').addClass(
                                            'bg-success text-white')
                                        .html('<i class="bi bi-check-circle-fill me-0.5"></i>Lengkap');
                                } else if (it.packed > 0) {
                                    row.removeClass('table-success bg-opacity-25');
                                    input.removeClass('bg-success text-white').addClass('bg-white');
                                    badge.removeClass('bg-success bg-secondary text-white').addClass(
                                            'bg-warning text-dark')
                                        .html(
                                            `<i class="bi bi-hourglass-split me-0.5"></i>Kurang ${it.target - it.packed}`
                                        );
                                } else {
                                    row.removeClass('table-success bg-opacity-25');
                                    input.removeClass('bg-success text-white').addClass('bg-white');
                                    badge.removeClass('bg-success bg-warning text-dark').addClass(
                                            'bg-secondary text-white')
                                        .html('<i class="bi bi-dash-circle me-0.5"></i>Belum');
                                }
                            });

                            const pct = totalTarget > 0 ? Math.min(100, Math.round((totalPacked / totalTarget) * 100)) : 0;
                            $('#modalPackedTotalCount').text(totalPacked);
                            $('#modalTargetTotalCount').text(totalTarget);
                            $('#modalPackedPct').text(pct);
                            $('#modalProgressBar').css('width', pct + '%').attr('aria-valuenow', pct);

                            if (pct >= 100) {
                                $('#modalProgressBar').removeClass('bg-primary').addClass('bg-success');
                            } else {
                                $('#modalProgressBar').removeClass('bg-success').addClass('bg-primary');
                            }
                        }

                        function showFeedback(message, isSuccess = true) {
                            const alertBox = $('#modalScanFeedback');
                            const icon = $('#modalScanFeedbackIcon');
                            const text = $('#modalScanFeedbackText');

                            alertBox.removeClass('d-none alert-success alert-danger alert-warning');
                            if (isSuccess) {
                                alertBox.addClass('alert-success');
                                icon.attr('class', 'bi bi-check-circle-fill text-success fs-5');
                            } else {
                                alertBox.addClass('alert-danger');
                                icon.attr('class', 'bi bi-exclamation-triangle-fill text-danger fs-5');
                            }
                            text.text(message);
                        }

                        // -------------------------------------------------------------
                        // Scan Barcode / SKU Handler
                        // -------------------------------------------------------------
                        $('#modalBarcodeScanInput').on('keydown', function(e) {
                            if (e.key === 'Enter' || e.which === 13) {
                                e.preventDefault();
                                const scanned = $(this).val().trim();
                                $(this).val('');
                                if (scanned) {
                                    processBarcodeScan(scanned);
                                }
                            }
                        });

                        $('#btnModalClearScanInput').on('click', function() {
                            $('#modalBarcodeScanInput').val('').focus();
                        });

                        function processBarcodeScan(code) {
                            const clean = code.toLowerCase().trim();
                            let matched = null;

                            // 1. Cocok persis SKU atau Barcode
                            for (let id in itemsState) {
                                const it = itemsState[id];
                                if ((it.sku && it.sku === clean) || (it.barcode && it.barcode === clean)) {
                                    matched = it;
                                    break;
                                }
                            }

                            // 2. Cocok barcode format SPK (SPK-xxx|ITEM-xxx|SKU)
                            if (!matched && clean.includes('|')) {
                                const parts = clean.split('|');
                                for (let part of parts) {
                                    const sub = part.trim();
                                    for (let id in itemsState) {
                                        const it = itemsState[id];
                                        if ((it.sku && it.sku === sub) || (it.barcode && it.barcode === sub)) {
                                            matched = it;
                                            break;
                                        }
                                    }
                                    if (matched) break;
                                }
                            }

                            // 3. Fallback partial SKU match
                            if (!matched) {
                                for (let id in itemsState) {
                                    const it = itemsState[id];
                                    if ((it.sku && (it.sku.includes(clean) || clean.includes(it.sku))) ||
                                        (it.barcode && (it.barcode.includes(clean) || clean.includes(it.barcode)))) {
                                        matched = it;
                                        break;
                                    }
                                }
                            }

                            if (matched) {
                                if (matched.packed >= matched.target) {
                                    playOfflineError();
                                    showFeedback(
                                        `Produk '${matched.name}' sudah mencapai target kemas (${matched.target} pcs). Tidak dapat menambah lagi.`,
                                        false);
                                    return;
                                }

                                matched.packed++;
                                playOfflineSuccess();
                                showFeedback(
                                    `✅ Scan Berhasil: '${matched.name}' (+1 pcs) &bull; ${matched.packed} / ${matched.target} Pcs`,
                                    true);
                                updateProgressUi();

                                // Cek apakah semua selesai
                                let allCompleted = Object.values(itemsState).every(it => it.packed >= it.target);
                                if (allCompleted) {
                                    playOfflineCompleteFanfare();
                                    showFeedback(`🎉 SEMUA ITEM LENGKAP! Silakan klik 'Selesai & Simpan Kemasan'.`, true);
                                }
                            } else {
                                playOfflineError();
                                showFeedback(`❌ Barcode / SKU '${code}' tidak cocok dengan item manapun pada nota ini.`, false);
                            }
                        }

                        // -------------------------------------------------------------
                        // Manual Plus / Minus Button
                        // -------------------------------------------------------------
                        $(document).on('click', '.btn-item-adjust', function() {
                            const id = $(this).data('id');
                            const delta = parseInt($(this).data('delta'));
                            const it = itemsState[id];

                            if (!it) return;

                            if (delta > 0) {
                                if (it.packed < it.target) {
                                    it.packed++;
                                    playOfflineSuccess();
                                    updateProgressUi();
                                } else {
                                    playOfflineError();
                                    showFeedback(`Item '${it.name}' sudah penuh (${it.target} Pcs).`, false);
                                }
                            } else if (delta < 0) {
                                if (it.packed > 0) {
                                    it.packed--;
                                    playOfflineTone(440, 'sine', 0.08);
                                    updateProgressUi();
                                }
                            }
                        });

                        // -------------------------------------------------------------
                        // Tandai Semua Selesai
                        // -------------------------------------------------------------
                        $('#btnModalMarkAllDone').on('click', function() {
                            Object.values(itemsState).forEach(it => {
                                it.packed = it.target;
                            });
                            playOfflineCompleteFanfare();
                            updateProgressUi();
                            showFeedback('Semua item telah ditandai lengkap selesai.', true);
                        });

                        // -------------------------------------------------------------
                        // Reset Progres
                        // -------------------------------------------------------------
                        $('#btnModalResetProgress').on('click', function() {
                            Swal.fire({
                                title: 'Reset Progres Scan?',
                                text: 'Semua jumlah terkemas pada nota ini akan dikembalikan ke 0.',
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#ef4444',
                                cancelButtonColor: '#64748b',
                                confirmButtonText: 'Ya, Reset ke 0',
                                cancelButtonText: 'Batal'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    Object.values(itemsState).forEach(it => {
                                        it.packed = 0;
                                    });
                                    updateProgressUi();
                                    showFeedback('Progres scan kemas berhasil di-reset.', false);

                                    // Kirim reset ke backend
                                    $.ajax({
                                        url: `{{ route('v2.penjualan_offline.scan_kemas', $sale->id) }}`,
                                        type: 'POST',
                                        data: {
                                            _token: '{{ csrf_token() }}',
                                            reset: 1
                                        },
                                        dataType: 'json',
                                        success: function(res) {
                                            applyBackendUpdate(res);
                                        }
                                    });
                                }
                            });
                        });

                        // -------------------------------------------------------------
                        // Simpan Kemasan ke Server
                        // -------------------------------------------------------------
                        $(document).on('click', '#btnModalSaveKemas, .btn-trigger-save-kemas', function() {
                            const packedItemsPayload = {};
                            Object.values(itemsState).forEach(it => {
                                packedItemsPayload[it.id] = it.packed;
                            });

                            Swal.fire({
                                title: 'Menyimpan Kemasan...',
                                text: 'Memproses status pengemasan transaksi offline.',
                                allowOutsideClick: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });

                            $.ajax({
                                url: `{{ route('v2.penjualan_offline.scan_kemas', $sale->id) }}`,
                                type: 'POST',
                                data: {
                                    _token: '{{ csrf_token() }}',
                                    packed_items: packedItemsPayload
                                },
                                dataType: 'json',
                                success: function(res) {
                                    Swal.close();
                                    if (res.success) {
                                        playOfflineCompleteFanfare();
                                        applyBackendUpdate(res);
                                        $('#modalScanKemasOffline').modal('hide');
                                        Swal.fire({
                                            icon: 'success',
                                            title: res.is_all_done ? 'Berhasil Dikemas Lengkap!' :
                                                'Progres Tersimpan',
                                            text: res.message,
                                            timer: 2000,
                                            showConfirmButton: false
                                        });
                                    } else {
                                        playOfflineError();
                                        Swal.fire('Gagal', res.message || 'Gagal menyimpan kemasan.',
                                            'error');
                                    }
                                },
                                error: function(xhr) {
                                    Swal.close();
                                    playOfflineError();
                                    let msg = 'Terjadi kesalahan sistem.';
                                    if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON
                                        .message;
                                    Swal.fire('Error', msg, 'error');
                                }
                            });
                        });

                        function applyBackendUpdate(res) {
                            // Update header button
                            const isAll = res.is_all_done || res.packing_status === 'verified';
                            if (isAll) {
                                $('#btnHeaderScanKemas').removeClass('btn-primary').addClass('btn-success text-white');
                                $('#btnHeaderScanKemas i').attr('class', 'bi bi-check-circle-fill me-1');
                                $('#txtBtnHeaderKemas').text('Sudah Dikemas (Lihat Scan)');
                                $('#cardPackingBadge').attr('class', 'badge bg-success text-white').text('Sudah Dikemas');
                                $('#modalHeaderPackingBadge').attr('class', 'badge bg-success text-white px-2.5 py-1.5').text(
                                    'Sudah Dikemas');
                            } else if (res.packing_status === 'packing') {
                                $('#btnHeaderScanKemas').removeClass('btn-success text-white').addClass('btn-primary');
                                $('#btnHeaderScanKemas i').attr('class', 'bi bi-upc-scan me-1');
                                $('#txtBtnHeaderKemas').text('Lanjutkan Scan Kemas');
                                $('#cardPackingBadge').attr('class', 'badge bg-info text-dark').text('Sedang Dikemas');
                                $('#modalHeaderPackingBadge').attr('class', 'badge bg-info text-dark px-2.5 py-1.5').text(
                                    'Sedang Dikemas');
                            } else {
                                $('#btnHeaderScanKemas').removeClass('btn-success text-white').addClass('btn-primary');
                                $('#btnHeaderScanKemas i').attr('class', 'bi bi-upc-scan me-1');
                                $('#txtBtnHeaderKemas').text('Scan & Kemas Barang');
                                $('#cardPackingBadge').attr('class', 'badge bg-secondary text-white').text('Belum Dikemas');
                                $('#modalHeaderPackingBadge').attr('class', 'badge bg-secondary text-white px-2.5 py-1.5').text(
                                    'Belum Dikemas');
                            }

                            if (res.packer_name) $('#txtCardPackerName').text(res.packer_name);
                            if (res.packed_at) $('#txtCardPackedAt').text(res.packed_at);

                            if (res.items && Array.isArray(res.items)) {
                                res.items.forEach(it => {
                                    $(`#txtTablePackedQty_${it.id}`).text(it.packed_quantity);
                                    const badge = $(`#badgeItemPacked_${it.id}`);
                                    if (it.is_completed) {
                                        badge.attr('class',
                                                'badge bg-success bg-opacity-10 text-success border border-success border-opacity-25'
                                            )
                                            .html(
                                                `<i class="bi bi-check2-circle me-0.5"></i>Lengkap (${it.packed_quantity}/${it.quantity})`
                                            );
                                    } else if (it.packed_quantity > 0) {
                                        badge.attr('class',
                                                'badge bg-warning bg-opacity-15 text-dark border border-warning border-opacity-25'
                                            )
                                            .html(
                                                `<i class="bi bi-hourglass-split me-0.5"></i>Sebagian (${it.packed_quantity}/${it.quantity})`
                                            );
                                    } else {
                                        badge.attr('class', 'badge bg-light text-secondary border')
                                            .html(
                                                `<i class="bi bi-dash-circle me-0.5"></i>Belum Dikemas (0/${it.quantity})`
                                            );
                                    }
                                });
                            }

                            if (res.total_target !== undefined && res.total_packed !== undefined) {
                                const pPct = res.total_target > 0 ? Math.min(100, Math.round((res.total_packed / res
                                    .total_target) * 100)) : 0;
                                $('#txtCardPackingProgress').text(`${res.total_packed} / ${res.total_target} Pcs (${pPct}%)`);
                                $('#barCardPackingProgress').css('width', `${pPct}%`);
                            }
                        }

                        // Auto-focus barcode input saat modal dibuka
                        const modalEl = document.getElementById('modalScanKemasOffline');
                        if (modalEl) {
                            modalEl.addEventListener('shown.bs.modal', function() {
                                $('#modalBarcodeScanInput').focus();
                            });
                        }
                    });
                </script>
            @endpush
        @endif
    @endif

@endsection
