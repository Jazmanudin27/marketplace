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
                    @if ($offlineSale->status !== \App\Models\OfflineSale::STATUS_CANCELLED && !$offlineSale->is_paid)
                        <button type="button" class="btn btn-outline-success btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modalMarkPaidShow">
                            <i class="fas fa-money-bill-wave me-1"></i> Catat Pembayaran / Cicilan
                        </button>
                    @endif
                    @php
                        $showCreateSpkShowBtn = $offlineSale->status === \App\Models\OfflineSale::STATUS_PENDING_SPK
                            || ($offlineSale->is_po && $offlineSale->spks->isEmpty() && (float) $offlineSale->paid_amount > 0 && $offlineSale->status !== \App\Models\OfflineSale::STATUS_CANCELLED);
                    @endphp
                    @if ($showCreateSpkShowBtn)
                        <button type="button" class="btn btn-warning btn-sm px-3 text-dark fw-bold" data-bs-toggle="modal" data-bs-target="#modalCreateSpkShow">
                            <i class="fas fa-hammer me-1"></i> Buat SPK Produksi
                        </button>
                    @endif
                    @if ($offlineSale->status === \App\Models\OfflineSale::STATUS_COMPLETED)
                        <button type="button" class="btn btn-warning btn-sm px-3 text-dark fw-bold" data-bs-toggle="modal" data-bs-target="#modalReturnShow">
                            <i class="fas fa-undo me-1"></i> Retur Barang
                        </button>
                    @endif
                    @php
                        $canCancelShow = $offlineSale->status !== \App\Models\OfflineSale::STATUS_CANCELLED
                            && (!$offlineSale->is_po || (float) $offlineSale->paid_amount <= 0);
                    @endphp
                    @if ($canCancelShow)
                        <button type="button" class="btn btn-danger btn-sm px-3"
                            data-bs-toggle="modal" data-bs-target="#modalCancelShow"
                            data-status="{{ $offlineSale->status }}">
                            <i class="fas fa-times-circle me-1"></i> Batalkan
                        </button>
                    @endif
                </div>
            </div>

            {{-- Alert Perlu Follow Up --}}
            @if ($offlineSale->needs_follow_up)
                <div class="alert alert-danger d-flex align-items-center gap-3 mb-3 py-3 border-danger shadow-sm">
                    <i class="fas fa-exclamation-triangle fa-2x text-danger"></i>
                    <div>
                        <strong class="fs-6">⚠️ PERINGATAN: PERLU SEGERA DI-FOLLOW UP!</strong><br>
                        <span>Batas tanggal follow up transaksi ini adalah <strong>{{ $offlineSale->follow_up_date?->format('d M Y') }}</strong> (sudah lewat), dan belum ada DP yang diterima. Silakan segera hubungi pembeli di nomor <strong>{{ $offlineSale->buyer_phone ?: '-' }}</strong>.</span>
                    </div>
                </div>
            @endif

            {{-- Banner Menunggu DP Masuk --}}
            @if ($offlineSale->status === \App\Models\OfflineSale::STATUS_WAITING_DP)
                <div class="alert alert-info d-flex align-items-center justify-content-between gap-3 mb-3 py-3" style="background-color: #e0f2fe; border-color: #7dd3fc; color: #0369a1;">
                    <div class="d-flex align-items-center gap-3">
                        <i class="fas fa-hourglass-half fa-2x text-info"></i>
                        <div>
                            <strong>Menunggu Pembayaran DP</strong><br>
                            <small>Pesanan PO ini sedang menunggu pembayaran Down Payment (DP) dari pembeli. Setelah DP dicatat, status akan berganti menjadi <strong>Belum dibuat SPK</strong>.</small>
                        </div>
                    </div>
                    @if (!$offlineSale->is_paid)
                        <button type="button" class="btn btn-primary btn-sm px-3 text-nowrap" data-bs-toggle="modal" data-bs-target="#modalMarkPaidShow">
                            <i class="fas fa-money-bill-wave me-1"></i> Catat DP Sekarang
                        </button>
                    @endif
                </div>
            @endif

            {{-- Banner Belum Dibuat SPK --}}
            @if ($offlineSale->status === \App\Models\OfflineSale::STATUS_PENDING_SPK || ($offlineSale->is_po && $offlineSale->spks->isEmpty() && (float) $offlineSale->paid_amount > 0 && $offlineSale->status !== \App\Models\OfflineSale::STATUS_CANCELLED))
                <div class="alert alert-warning d-flex align-items-center justify-content-between gap-3 mb-3 py-3" style="background-color: #fef3c7; border-color: #fcd34d; color: #92400e;">
                    <div class="d-flex align-items-center gap-3">
                        <i class="fas fa-hammer fa-2x text-warning"></i>
                        <div>
                            <strong class="fs-6">Pembayaran DP Diterima — Belum Dibuat SPK</strong><br>
                            <small>DP pesanan ini telah masuk. Silakan terbitkan SPK untuk Tim Produksi agar proses pengerjaan dapat dimulai.</small>
                        </div>
                    </div>
                    <button type="button" class="btn btn-warning btn-sm px-3 text-dark fw-bold text-nowrap shadow-sm" data-bs-toggle="modal" data-bs-target="#modalCreateSpkShow">
                        <i class="fas fa-hammer me-1"></i> Buat SPK Sekarang
                    </button>
                </div>
            @endif

            {{-- Banner Pesanan PO Produksi (SPK Sedang Diproses) --}}
            @if ($offlineSale->is_po && in_array($offlineSale->status, [\App\Models\OfflineSale::STATUS_PENDING_APPROVAL, \App\Models\OfflineSale::STATUS_SPK_PROCESSING]) && $offlineSale->spks->isNotEmpty())
                @php
                    $firstSpk = $offlineSale->spks->first();
                @endphp
                <div class="alert alert-primary d-flex align-items-center justify-content-between gap-3 mb-3 py-3" style="background-color: #eff6ff; border-color: #93c5fd; color: #1e40af;">
                    <div class="d-flex align-items-center gap-3">
                        <i class="fas fa-industry fa-2x text-primary"></i>
                        <div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <strong class="fs-6">SPK Sedang Diproses Produksi</strong>
                                @if($firstSpk && $firstSpk->no_produksi)
                                    <span class="badge bg-white text-primary border border-primary-subtle font-monospace">
                                        <i class="fas fa-hashtag me-0.5"></i>Kode Produksi: {{ $firstSpk->no_produksi }}
                                    </span>
                                @endif
                                <span class="badge bg-primary bg-opacity-10 text-primary">
                                    {{ $offlineSale->spks->count() }} SPK Diterbitkan
                                </span>
                            </div>
                            <small class="d-block mt-1">Pesanan PO ini memiliki <strong>{{ $offlineSale->spks->count() }} SPK Produksi</strong> di bawah Kode Produksi <strong>{{ $firstSpk?->no_produksi ?: '-' }}</strong> dan sedang dalam proses pengerjaan oleh Tim Produksi.</small>
                        </div>
                    </div>
                    @if($offlineSale->spks && $offlineSale->spks->count() > 0)
                        <div class="d-flex gap-1 flex-wrap justify-content-end">
                            @foreach($offlineSale->spks as $spkItem)
                                <a href="{{ route('spks.show', $spkItem->id) }}" target="_blank" class="btn btn-sm btn-primary text-white text-nowrap">
                                    <i class="fas fa-external-link-alt me-1"></i> SPK #{{ $spkItem->no_spk }} ({{ $spkItem->kategori ?: ($spkItem->items->first()?->nama_produk ?: 'Item') }})
                                </a>
                            @endforeach
                        </div>
                    @endif
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
                                @if ($offlineSale->institution_name)
                                    <div class="col-md-12">
                                        <div class="p-3 border border-info border-opacity-25 rounded bg-info bg-opacity-10">
                                            <small class="text-info d-block text-uppercase fw-bold mb-1"
                                                style="font-size: 0.65rem;"><i class="fas fa-building me-1"></i>Instansi / Saluran / Channel</small>
                                            <span class="fw-bold text-dark fs-6">{{ $offlineSale->institution_name }}</span>
                                        </div>
                                    </div>
                                @endif
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
                                            style="font-size: 0.65rem;">Jenis Transaksi</small>
                                        @if ($offlineSale->payment_method === 'piutang')
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 small fw-semibold mt-1">
                                                <i class="fas fa-file-invoice-dollar me-1"></i>Kredit (Tempo)
                                            </span>
                                        @else
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 small fw-semibold mt-1">
                                                <i class="fas fa-money-bill-wave me-1"></i>Tunai (Lunas)
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-3 border rounded h-100 bg-light">
                                        <small class="text-muted d-block text-uppercase fw-semibold mb-1 small"
                                            style="font-size: 0.65rem;">Status Pembayaran</small>
                                        <div>
                                            <span
                                                class="badge bg-{{ $offlineSale->payment_status_badge }} bg-opacity-10 text-{{ $offlineSale->payment_status_badge }} border border-{{ $offlineSale->payment_status_badge }} border-opacity-10 small fw-semibold mt-1">
                                                <i class="fas fa-{{ $offlineSale->is_paid ? 'check-circle' : 'exclamation-circle' }} me-1"></i>
                                                {{ $offlineSale->payment_status_label }}
                                            </span>
                                            @if (!$offlineSale->is_paid && $offlineSale->status !== \App\Models\OfflineSale::STATUS_CANCELLED)
                                                <div class="small font-monospace text-danger fw-semibold mt-1" style="font-size: 0.75rem;">
                                                    Sisa: Rp {{ number_format($offlineSale->remaining_amount, 0, ',', '.') }}
                                                </div>
                                            @endif
                                        </div>
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
                                @if ($offlineSale->follow_up_date)
                                    <div class="col-md-4">
                                        <div class="p-3 border rounded h-100 {{ $offlineSale->needs_follow_up ? 'bg-danger bg-opacity-10 border-danger' : 'bg-light' }}">
                                            <small class="text-muted d-block text-uppercase fw-semibold mb-1 small"
                                                style="font-size: 0.65rem;">Tanggal Follow Up DP</small>
                                            <div class="fw-bold {{ $offlineSale->needs_follow_up ? 'text-danger' : 'text-dark' }} small">
                                                <i class="far fa-calendar-alt me-1"></i>{{ $offlineSale->follow_up_date->format('d M Y') }}
                                                @if ($offlineSale->needs_follow_up)
                                                    <span class="badge bg-danger ms-1">Overdue</span>
                                                @endif
                                            </div>
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
                                                 <i class="fas fa-shipping-fast me-1"></i> Informasi Dropshipper &amp; Resi Pengiriman
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
                                                 @if ($offlineSale->resi_number)
                                                     <div class="col-md-6 text-dark small mt-2">
                                                         <span class="text-muted">Jasa Kirim / Ekspedisi:</span>
                                                         <strong class="fw-bold text-primary bg-white px-2 py-0.5 rounded border border-primary border-opacity-25">{{ $offlineSale->resi_number }}</strong>
                                                     </div>
                                                 @endif
                                                 @if ($offlineSale->resi_file)
                                                     <div class="col-md-6 text-dark small mt-2">
                                                         <span class="text-muted">Dokumen Resi / Label:</span>
                                                         <a href="{{ Storage::url($offlineSale->resi_file) }}" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2.5 fw-bold ms-1" style="font-size:0.75rem;">
                                                             <i class="fas fa-file-download me-1"></i>Buka / Download Label Resi
                                                         </a>
                                                     </div>
                                                 @endif
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

                {{-- RIGHT: Ringkasan Pembayaran & Cicilan --}}
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-light py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-wallet me-2 text-success"></i>Ringkasan Pembayaran</h6>
                            <span class="badge bg-{{ $offlineSale->payment_status_badge }} bg-opacity-10 text-{{ $offlineSale->payment_status_badge }} border border-{{ $offlineSale->payment_status_badge }} border-opacity-10 small fw-bold">
                                {{ $offlineSale->payment_status_label }}
                            </span>
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

                            {{-- Payment Progress & Status --}}
                            <div class="p-3 border rounded bg-light mb-3">
                                <div class="d-flex justify-content-between mb-1 small">
                                    <span class="text-muted">Sudah Dibayar</span>
                                    <span class="font-monospace text-success fw-bold">Rp {{ number_format($offlineSale->paid_amount, 0, ',', '.') }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2 small">
                                    <span class="text-muted">Sisa Kekurangan</span>
                                    <span class="font-monospace text-danger fw-bold fs-6">Rp {{ number_format($offlineSale->remaining_amount, 0, ',', '.') }}</span>
                                </div>
                                
                                <div class="progress mb-2" style="height: 10px;">
                                    <div class="progress-bar {{ $offlineSale->is_paid ? 'bg-success' : 'bg-warning' }}" 
                                         role="progressbar" 
                                         style="width: {{ $offlineSale->payment_percentage }}%;" 
                                         aria-valuenow="{{ $offlineSale->payment_percentage }}" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between text-muted" style="font-size: 0.7rem;">
                                    <span>Terbayar {{ $offlineSale->payment_percentage }}%</span>
                                    <span>{{ $offlineSale->is_paid ? 'Lunas 100%' : 'Belum Lunas' }}</span>
                                </div>
                            </div>

                            @if ($offlineSale->status !== \App\Models\OfflineSale::STATUS_CANCELLED && !$offlineSale->is_paid)
                                <div class="d-grid">
                                    <button type="button" class="btn btn-success btn-sm py-2 fw-bold" data-bs-toggle="modal" data-bs-target="#modalMarkPaidShow">
                                        <i class="fas fa-plus-circle me-1"></i> Catat Pembayaran / Cicilan
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Riwayat Pembayaran Cicilan --}}
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header bg-success bg-opacity-10 py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-success">
                        <i class="fas fa-history me-2"></i>Riwayat Pembayaran Cicilan
                    </h6>
                    <span class="badge bg-success font-monospace">{{ $offlineSale->payments->count() }}x Pembayaran Masuk</span>
                </div>
                <div class="card-body p-3">
                    @if($offlineSale->payments->isEmpty())
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-receipt fa-2x mb-2 text-muted opacity-50"></i>
                            <p class="mb-0 small">Belum ada catatan cicilan / pembayaran yang masuk.</p>
                        </div>
                    @else
                        <div class="table-responsive rounded border">
                            <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.8rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">NO. PEMBAYARAN</th>
                                        <th>TANGGAL</th>
                                        <th class="text-end">NOMINAL</th>
                                        <th>METODE</th>
                                        <th>KAS / BANK TUJUAN</th>
                                        <th>CATATAN</th>
                                        <th>PETUGAS</th>
                                        @if(auth()->user()->isAdmin() || auth()->user()->isOwner() || in_array(auth()->user()->role, ['admin', 'owner']))
                                            <th class="text-center" style="width: 50px;">AKSI</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($offlineSale->payments as $pmt)
                                        <tr>
                                            <td class="ps-3 font-monospace fw-bold text-dark">{{ $pmt->payment_number }}</td>
                                            <td>{{ $pmt->payment_date ? $pmt->payment_date->format('d/m/Y') : '-' }}</td>
                                            <td class="text-end font-monospace fw-bold text-success">Rp {{ number_format($pmt->amount, 0, ',', '.') }}</td>
                                            <td>
                                                <span class="badge bg-light text-dark border">{{ $pmt->payment_method_label }}</span>
                                            </td>
                                            <td>{{ $pmt->payment_destination ?: '-' }}</td>
                                            <td><small class="text-muted">{{ $pmt->notes ?: '-' }}</small></td>
                                            <td><small class="text-dark">{{ $pmt->user->name ?? '-' }}</small></td>
                                            @if(auth()->user()->isAdmin() || auth()->user()->isOwner() || in_array(auth()->user()->role, ['admin', 'owner']))
                                                <td class="text-center">
                                                    <form action="{{ route('offline_sales.payments.destroy', [$offlineSale->id, $pmt->id]) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus riwayat pembayaran ini? Saldo bank & pemasukan keuangan akan disesuaikan kembali.');" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-2" title="Hapus Riwayat Pembayaran">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
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



{{-- Modal Catat Pembayaran / Cicilan --}}
@push('modals')
@if($offlineSale->status !== \App\Models\OfflineSale::STATUS_CANCELLED && !$offlineSale->is_paid)
<div class="modal fade" id="modalMarkPaidShow" tabindex="-1" aria-labelledby="modalMarkPaidShowLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success bg-opacity-10 border-bottom">
                <h6 class="modal-title fw-bold text-success" id="modalMarkPaidShowLabel">
                    <i class="fas fa-money-bill-wave me-2"></i>Catat Pembayaran / Cicilan
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('offline_sales.payments.store', $offlineSale->id) }}" method="POST" id="form-record-payment-show">
                @csrf
                <div class="modal-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">Transaksi: <strong class="font-monospace text-primary">{{ $offlineSale->sale_number }}</strong></span>
                        <span class="small text-muted">Pembeli: <strong>{{ $offlineSale->buyer_name ?: '(Umum)' }}</strong></span>
                    </div>

                    <div class="p-3 bg-light rounded border mb-3">
                        <div class="d-flex justify-content-between mb-1 small">
                            <span class="text-muted">Total Tagihan:</span>
                            <strong class="font-monospace text-dark">Rp {{ number_format($offlineSale->grand_total, 0, ',', '.') }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1 small">
                            <span class="text-muted">Sudah Dibayar:</span>
                            <span class="font-monospace text-success fw-semibold">Rp {{ number_format($offlineSale->paid_amount, 0, ',', '.') }}</span>
                        </div>
                        <hr class="my-1">
                        <div class="d-flex justify-content-between small">
                            <span class="fw-bold text-danger">Sisa Kekurangan:</span>
                            <strong class="font-monospace text-danger fs-6" id="modal-show-remaining-display">Rp {{ number_format($offlineSale->remaining_amount, 0, ',', '.') }}</strong>
                        </div>
                    </div>

                    {{-- Nominal Pembayaran / Cicilan --}}
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="modal-payment-amount-display" class="form-label fw-semibold small text-dark mb-0">
                                Nominal Pembayaran / Cicilan <span class="text-danger">*</span>
                            </label>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-secondary py-0 px-2" id="btn-fill-half" style="font-size:0.7rem;">50%</button>
                                <button type="button" class="btn btn-outline-success py-0 px-2" id="btn-fill-full" style="font-size:0.7rem;">Lunas (Semua)</button>
                            </div>
                        </div>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text fw-bold">Rp</span>
                            <input type="text" id="modal-payment-amount-display" class="form-control form-control-sm font-monospace fw-bold fs-6" value="{{ number_format($offlineSale->remaining_amount, 0, ',', '.') }}" required>
                            <input type="hidden" name="amount" id="modal-payment-amount-raw" value="{{ (float) $offlineSale->remaining_amount }}">
                        </div>
                        <div class="form-text text-muted" style="font-size:0.72rem;">
                            Bisa dicicil nominal berapapun atau langsung dilunasi penuh.
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label for="show_payment_method" class="form-label fw-semibold small text-dark mb-1">
                                Metode Pembayaran <span class="text-danger">*</span>
                            </label>
                            <select name="payment_method" id="show_payment_method" class="form-select form-select-sm" required>
                                <option value="transfer" selected>Transfer Bank</option>
                                <option value="tunai">Tunai (Cash)</option>
                                <option value="qris">QRIS</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="show_payment_date" class="form-label fw-semibold small text-dark mb-1">
                                Tanggal Bayar <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="payment_date" id="show_payment_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="paid_payment_destination_show" class="form-label fw-semibold small text-dark mb-1">
                            <i class="fas fa-university me-1 text-primary"></i> Kas / Bank Tujuan Pembayaran <span class="text-danger">*</span>
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

                    <div class="mb-2">
                        <label for="show_notes" class="form-label fw-semibold small text-dark mb-1">Catatan / Keterangan (Opsional)</label>
                        <input type="text" name="notes" id="show_notes" class="form-control form-control-sm"
                            value="{{ $offlineSale->status === \App\Models\OfflineSale::STATUS_WAITING_DP ? 'Pembayaran DP' : '' }}"
                            placeholder="Contoh: Cicilan ke-1, DP tambahan, pelunasan transfer...">
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
@endif

{{-- Modal Terbitkan SPK Produksi (Full Screen) --}}
@if($offlineSale->status === \App\Models\OfflineSale::STATUS_PENDING_SPK || ($offlineSale->is_po && $offlineSale->spks->isEmpty() && (float) $offlineSale->paid_amount > 0 && $offlineSale->status !== \App\Models\OfflineSale::STATUS_CANCELLED))
<div class="modal fade" id="modalCreateSpkShow" tabindex="-1" aria-labelledby="modalCreateSpkShowLabel" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
        <div class="modal-content border-0">
            <div class="modal-header bg-warning bg-opacity-10 border-bottom px-4 py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-warning bg-opacity-25 p-2 text-warning d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="fas fa-hammer fs-6 text-dark"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="modalCreateSpkShowLabel">
                            Terbitkan SPK Produksi
                        </h5>
                        <small class="text-muted" style="font-size: 0.75rem;">Konfigurasi Nomor Produksi, Tahap Awal, dan Pembagian SPK Pesanan Offline PO</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form action="{{ route('offline_sales.create_spk', $offlineSale->id) }}" method="POST" class="m-0 d-flex flex-column flex-grow-1">
                @csrf
                <div class="modal-body p-4 flex-grow-1">
                    <div class="container-fluid px-lg-3 py-1">
                        {{-- Info Banner PO --}}
                        <div class="d-flex justify-content-between align-items-center mb-3 p-3 bg-light rounded border flex-wrap gap-2">
                            <div>
                                <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.7rem;">Transaksi PO</small>
                                <span class="fw-bold font-monospace text-primary fs-5">{{ $offlineSale->sale_number }}</span>
                                <span class="text-muted ms-2">&bull; Pembeli: <strong>{{ $offlineSale->buyer_name ?: 'Pelanggan' }}</strong></span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle py-2 px-3 fs-7">
                                    <i class="fas fa-layer-group me-1"></i> 1 No. Produksi = Multi SPK Produk
                                </span>
                            </div>
                        </div>

                        {{-- Parameter Utama SPK: 3 Kolom Sejajar --}}
                        <div class="row g-3 mb-4">
                            <div class="col-lg-4 col-md-6">
                                <label for="show_spk_no_produksi" class="form-label fw-semibold small text-dark mb-1">
                                    <i class="fas fa-hashtag text-primary me-1"></i>Nomor / Kode Produksi <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="no_produksi" id="show_spk_no_produksi" class="form-control form-control-sm font-monospace fw-bold"
                                    value="{{ \App\Models\Spk::generateNoProduksi() }}" required>
                                <div class="form-text text-muted" style="font-size: 0.75rem;">
                                    Satu kode produksi ini akan menaungi seluruh SPK pesanan ini.
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6">
                                <label for="show_spk_tahap_saat_ini" class="form-label fw-semibold small text-dark mb-1">
                                    <i class="fas fa-tasks text-primary me-1"></i>Tahap Awal Produksi <span class="text-danger">*</span>
                                </label>
                                <select name="tahap_saat_ini" id="show_spk_tahap_saat_ini" class="form-select form-select-sm fw-semibold" required>
                                    <option value="Antrian &amp; Sampling" selected>⏳ Antrian &amp; Sampling (Langsung Antrian)</option>
                                    <option value="Perencanaan">📋 Perencanaan / Pesanan Baru</option>
                                    <option value="Tahap Pemotongan">✂️ Tahap Pemotongan</option>
                                    <option value="Tahap Jahit">🪡 Tahap Jahit</option>
                                </select>
                                <div class="form-text text-success" style="font-size: 0.75rem;">
                                    <i class="fas fa-check-circle me-1"></i>SPK langsung masuk antrian aktif (bukan Draft).
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-12">
                                <label for="show_spk_deadline" class="form-label fw-semibold small text-dark mb-1">
                                    <i class="fas fa-calendar-alt text-primary me-1"></i>Target Deadline SPK Produksi <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="deadline" id="show_spk_deadline" class="form-control form-control-sm" value="{{ now()->addDays(7)->format('Y-m-d') }}" required>
                                <div class="form-text text-muted" style="font-size: 0.75rem;">Target tanggal selesai pengerjaan oleh tim produksi.</div>
                            </div>
                        </div>

                        {{-- Layout 2 Kolom: Kiri (Tabel Pembagian Item) | Kanan (Ringkasan Real-Time) --}}
                        <div class="row g-4">
                            {{-- Kolom Kiri: Tabel Pembagian SPK --}}
                            <div class="col-lg-7">
                                <div class="border rounded p-3 bg-light shadow-sm h-100">
                                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                        <div>
                                            <label class="form-label fw-bold text-dark mb-0 fs-6">
                                                <i class="fas fa-layer-group me-1 text-primary"></i>Tentukan Pembagian SPK (SPK 1 - SPK 5)
                                            </label>
                                            <div class="text-muted small" style="font-size: 0.75rem;">
                                                Pilih SPK tujuan untuk masing-masing item (contoh: Item A &amp; B masuk SPK 1, Item C masuk SPK 2).
                                            </div>
                                        </div>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary btn-sm py-1 px-2.5 fw-semibold" id="btn-quick-combine-show" title="Semua item dijadikan 1 SPK">
                                                <i class="fas fa-link me-1"></i>Gabung Semua (1 SPK)
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2.5 fw-semibold" id="btn-quick-split-show" title="Pisahkan tiap item menjadi SPK sendiri-sendiri">
                                                <i class="fas fa-cut me-1"></i>Pisah Masing-masing
                                            </button>
                                        </div>
                                    </div>

                                    <div class="table-responsive bg-white rounded border mb-0" style="max-height: 480px; overflow-y: auto;">
                                        <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.85rem;">
                                            <thead class="table-light sticky-top">
                                                <tr>
                                                    <th style="width: 40px;" class="text-center">#</th>
                                                    <th>PRODUK / ITEM PESANAN</th>
                                                    <th class="text-center" style="width: 80px;">QTY</th>
                                                    <th style="width: 210px;">PILIH SPK TUJUAN</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($offlineSale->items as $idx => $item)
                                                    <tr>
                                                        <td class="text-center text-muted">{{ $idx + 1 }}</td>
                                                        <td>
                                                            <div class="fw-semibold text-dark">{{ $item->product_name }}</div>
                                                            @if($item->sku)
                                                                <small class="text-muted font-monospace">{{ $item->sku }}</small>
                                                            @endif
                                                        </td>
                                                        <td class="text-center fw-bold font-monospace">{{ $item->quantity }} Pcs</td>
                                                        <td>
                                                            <select name="spk_group[{{ $item->id }}]" class="form-select form-select-sm fw-bold spk-group-select-show"
                                                                data-item-id="{{ $item->id }}" data-item-name="{{ $item->product_name }}" data-item-qty="{{ $item->quantity }}">
                                                                <option value="1" selected>📦 Masuk ke SPK 1</option>
                                                                <option value="2">📦 Masuk ke SPK 2</option>
                                                                <option value="3">📦 Masuk ke SPK 3</option>
                                                                <option value="4">📦 Masuk ke SPK 4</option>
                                                                <option value="5">📦 Masuk ke SPK 5</option>
                                                                <option value="0">❌ Lewati (Jangan Buat SPK)</option>
                                                            </select>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            {{-- Kolom Kanan: Pratinjau Real-Time SPK yang Akan Dibuat --}}
                            <div class="col-lg-5">
                                <div class="p-3 bg-white rounded border shadow-sm h-100 d-flex flex-column" style="position: sticky; top: 1rem;">
                                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                        <div>
                                            <h6 class="fw-bold text-dark mb-0">
                                                <i class="fas fa-clipboard-list me-1 text-primary"></i>Ringkasan SPK yang Terbit
                                            </h6>
                                            <small class="text-muted" style="font-size: 0.72rem;">Pratinjau otomatis SPK yang akan dibentuk</small>
                                        </div>
                                        <span class="badge bg-primary px-2.5 py-1.5 fs-7" id="modal-spk-summary-badge-show">0 SPK</span>
                                    </div>
                                    <div id="modal-spk-summary-list-show" class="d-flex flex-column gap-2 flex-grow-1" style="font-size: 0.82rem; max-height: 440px; overflow-y: auto;">
                                    </div>
                                    <div class="mt-3 pt-2 border-top text-muted small" style="font-size: 0.75rem;">
                                        <i class="fas fa-info-circle text-primary me-1"></i>
                                        Setiap SPK akan memiliki nomor unik di modul SPK (misal <code>SPK-...-0001</code>, <code>SPK-...-0002</code>) di bawah No. Produksi yang sama.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer px-4 py-3 bg-light border-top">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Tutup
                    </button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm">
                        <i class="fas fa-hammer me-1"></i> Terbitkan SPK Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
    {{-- Datalist Kategori SPK (Sama seperti di Modul SPK) --}}
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
@endif
@endpush

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
            if (['pending_approval', 'spk_diproses', 'menunggu_dp', 'belum_spk'].includes(status)) {
                noteEl.innerHTML = '<i class="fas fa-info-circle me-1"></i> Transaksi belum diapprove. Stok <strong>tidak akan</strong> berubah.';
                noteEl.className = 'alert alert-info py-2 mb-3 small';
            } else {
                noteEl.innerHTML = '<i class="fas fa-undo me-1"></i> Stok semua produk akan <strong>dikembalikan</strong> secara otomatis.';
                noteEl.className = 'alert alert-warning py-2 mb-3 small';
            }
        });
    }

    // Loading state saat submit
    document.querySelectorAll('#modalCancelShow form, #modalMarkPaidShow form, #modalReturnShow form').forEach(function (form) {
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

    // Input nominal cicilan & quick buttons di modal
    const pmtDisplay = document.getElementById('modal-payment-amount-display');
    const pmtRaw = document.getElementById('modal-payment-amount-raw');
    const maxRemaining = {{ (float) $offlineSale->remaining_amount }};

    if (pmtDisplay && pmtRaw) {
        pmtDisplay.addEventListener('input', function () {
            let val = this.value.replace(/[^0-9]/g, '');
            let num = parseInt(val, 10) || 0;
            if (num > maxRemaining) {
                num = maxRemaining;
            }
            this.value = num > 0 ? num.toLocaleString('id-ID') : '';
            pmtRaw.value = num;
        });

        const btnFillFull = document.getElementById('btn-fill-full');
        if (btnFillFull) {
            btnFillFull.addEventListener('click', function () {
                pmtDisplay.value = Math.round(maxRemaining).toLocaleString('id-ID');
                pmtRaw.value = maxRemaining;
            });
        }

        const btnFillHalf = document.getElementById('btn-fill-half');
        if (btnFillHalf) {
            btnFillHalf.addEventListener('click', function () {
                let half = Math.round(maxRemaining / 2);
                pmtDisplay.value = half.toLocaleString('id-ID');
                pmtRaw.value = half;
            });
        }
    }

    // SPK Grouping logic in show view
    const modalCreateSpkShow = document.getElementById('modalCreateSpkShow');
    if (modalCreateSpkShow) {
        let userCategoriesShow = {};

        function detectCategoryShow(items) {
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
            if (clean.length > 30) {
                clean = clean.substring(0, 30) + '...';
            }
            return clean || 'Produk SPK';
        }

        function renderSpkSummaryShow() {
            const summaryList = document.getElementById('modal-spk-summary-list-show');
            const summaryBadge = document.getElementById('modal-spk-summary-badge-show');
            const noProdInput = document.getElementById('show_spk_no_produksi');
            const currentNoProduksi = noProdInput ? (noProdInput.value || '-') : '-';
            if (!summaryList) return;

            const selects = modalCreateSpkShow.querySelectorAll('.spk-group-select-show');
            const groups = {};

            selects.forEach(sel => {
                const grpVal = sel.value;
                if (grpVal === '0') return; // lewati
                const itemName = sel.getAttribute('data-item-name');
                const itemQty = parseInt(sel.getAttribute('data-item-qty'), 10) || 0;

                if (!groups[grpVal]) {
                    groups[grpVal] = {
                        groupNumber: grpVal,
                        items: [],
                        totalQty: 0
                    };
                }
                groups[grpVal].items.push({ name: itemName, qty: itemQty });
                groups[grpVal].totalQty += itemQty;
            });

            const groupKeys = Object.keys(groups).sort((a, b) => parseInt(a) - parseInt(b));
            if (summaryBadge) summaryBadge.textContent = groupKeys.length + ' SPK Akan Diterbitkan';

            if (groupKeys.length === 0) {
                summaryList.innerHTML = '<div class="text-danger py-1"><i class="fas fa-exclamation-triangle me-1"></i>Pilih minimal 1 SPK tujuan untuk item pesanan.</div>';
                return;
            }

            const badgeColors = ['primary', 'success', 'warning text-dark', 'info', 'danger', 'secondary'];
            let html = '';
            groupKeys.forEach((key, kIdx) => {
                const grp = groups[key];
                const colorClass = badgeColors[kIdx % badgeColors.length];

                // Gunakan kategori yang pernah diedit manual, atau auto-detect jika baru
                if (!userCategoriesShow[key] || userCategoriesShow[key].trim() === '') {
                    userCategoriesShow[key] = detectCategoryShow(grp.items);
                }
                const currentCat = userCategoriesShow[key];

                const itemsDetail = grp.items.map(it => `&bull; ${it.name} <strong>(${it.qty} pcs)</strong>`).join('<br>');

                html += `
                    <div class="p-3 rounded bg-light border shadow-sm mb-2">
                        <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom">
                            <div class="d-flex align-items-center gap-1.5">
                                <span class="badge bg-${colorClass} px-2 py-1 fw-bold fs-7">
                                    <i class="fas fa-file-invoice me-1"></i>SPK ${grp.groupNumber}
                                </span>
                                <span class="badge bg-white text-secondary border font-monospace" style="font-size: 0.72rem;">
                                    ${grp.totalQty} Pcs total
                                </span>
                            </div>
                            <span class="badge bg-white text-primary border font-monospace small"><i class="fas fa-hashtag me-0.5"></i>${currentNoProduksi}</span>
                        </div>

                        {{-- Input Kategori Produk Sesuai Show SPK --}}
                        <div class="mb-2">
                            <label class="form-label fw-bold mb-1 d-flex justify-content-between align-items-center" style="font-size: 0.75rem; color: #4f46e5;">
                                <span>🏷️ KATEGORI PRODUK SPK ${grp.groupNumber} <span class="text-danger">*</span></span>
                                <span class="text-muted fw-normal" style="font-size: 0.68rem;">(Tampil di Tab SPK)</span>
                            </label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white text-primary"><i class="fas fa-tags"></i></span>
                                <input type="text"
                                       name="spk_kategori[${grp.groupNumber}]"
                                       class="form-control form-control-sm fw-bold text-dark spk-kategori-input-show"
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

            // Simpan perubahan kategori yang diinput oleh user
            summaryList.querySelectorAll('.spk-kategori-input-show').forEach(inp => {
                inp.addEventListener('input', function() {
                    userCategoriesShow[this.getAttribute('data-group')] = this.value;
                });
                inp.addEventListener('change', function() {
                    userCategoriesShow[this.getAttribute('data-group')] = this.value;
                });
            });
        }

        modalCreateSpkShow.querySelectorAll('.spk-group-select-show').forEach(sel => {
            sel.addEventListener('change', renderSpkSummaryShow);
        });

        const btnCombineShow = document.getElementById('btn-quick-combine-show');
        if (btnCombineShow) {
            btnCombineShow.addEventListener('click', function() {
                modalCreateSpkShow.querySelectorAll('.spk-group-select-show').forEach(sel => {
                    sel.value = '1';
                });
                renderSpkSummaryShow();
            });
        }

        const btnSplitShow = document.getElementById('btn-quick-split-show');
        if (btnSplitShow) {
            btnSplitShow.addEventListener('click', function() {
                modalCreateSpkShow.querySelectorAll('.spk-group-select-show').forEach((sel, idx) => {
                    sel.value = String(Math.min(idx + 1, 5));
                });
                renderSpkSummaryShow();
            });
        }

        const noProdInputShow = document.getElementById('show_spk_no_produksi');
        if (noProdInputShow) {
            noProdInputShow.addEventListener('input', renderSpkSummaryShow);
        }

        modalCreateSpkShow.addEventListener('shown.bs.modal', function() {
            userCategoriesShow = {}; // Reset state input kategori saat modal dibuka kembali
            renderSpkSummaryShow();
        });
        renderSpkSummaryShow();
    }
});
</script>
@endpush
