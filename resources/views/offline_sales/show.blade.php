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
                        <i class="fas fa-print me-1"></i> Cetak Invoice
                    </a>
                    @if ($offlineSale->status !== \App\Models\OfflineSale::STATUS_CANCELLED)
                        <a href="{{ route('offline_sales.edit', $offlineSale->id) }}"
                            class="btn btn-warning btn-sm px-3 text-dark fw-bold">
                            <i class="fas fa-edit me-1"></i> Edit Transaksi
                        </a>
                    @endif
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
                {{-- LEFT COLUMN: Item Pesanan, Riwayat Pembayaran, Riwayat Retur --}}
                <div class="col-lg-8">
                    {{-- Card: Item Pesanan --}}
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-white py-3 px-3 border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0 text-dark">
                                <i class="fas fa-boxes-stacked me-2 text-primary"></i>Item Pesanan
                            </h6>
                            <span class="badge bg-light text-secondary border small px-2.5 py-1 rounded-pill">
                                {{ $offlineSale->items->count() }} Produk &bull; {{ $offlineSale->items->sum('quantity') }} Pcs
                            </span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light text-secondary" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                    <tr>
                                        <th class="ps-3 py-2.5">PRODUK</th>
                                        <th class="py-2.5">SKU</th>
                                        <th class="text-center py-2.5">QTY</th>
                                        <th class="text-end py-2.5">HARGA SATUAN</th>
                                        <th class="text-end py-2.5">DISKON</th>
                                        <th class="text-end pe-3 py-2.5">SUBTOTAL</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($offlineSale->items as $item)
                                        <tr>
                                            <td class="ps-3 py-2.5">
                                                <span class="fw-bold text-dark d-block" style="font-size: 0.85rem;">{{ $item->product_name }}</span>
                                                @if($item->returned_quantity > 0)
                                                    <span class="badge bg-warning text-dark mt-1" style="font-size:0.65rem;">
                                                        <i class="fas fa-undo me-0.5"></i> Diretur {{ $item->returned_quantity }}x
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="py-2.5">
                                                <code class="text-primary font-monospace small bg-primary bg-opacity-10 px-1.5 py-0.5 rounded">{{ $item->sku ?? '-' }}</code>
                                            </td>
                                            <td class="text-center py-2.5">
                                                <span class="badge bg-light text-dark border px-2 py-1 font-monospace fw-semibold">{{ $item->quantity }}</span>
                                            </td>
                                            <td class="text-end font-monospace small py-2.5">
                                                Rp {{ number_format($item->unit_price, 0, ',', '.') }}
                                            </td>
                                            <td class="text-end font-monospace text-danger small py-2.5">
                                                @if($item->discount_amount > 0)
                                                    - Rp {{ number_format($item->discount_amount, 0, ',', '.') }}
                                                    <div class="text-muted" style="font-size:0.68rem;">
                                                        ({{ $item->discount_type === 'percentage' ? number_format($item->discount_value, 0).'% / unit' : 'Rp '.number_format($item->discount_value, 0, ',', '.').' / unit' }})
                                                    </div>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="text-end font-monospace text-success fw-bold small pe-3 py-2.5">
                                                Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light fw-bold" style="font-size: 0.82rem;">
                                    <tr>
                                        <td colspan="2" class="ps-3 py-2.5 text-muted text-uppercase" style="font-size: 0.72rem;">Total Kuantitas</td>
                                        <td class="text-center font-monospace py-2.5">{{ $offlineSale->items->sum('quantity') }} pcs</td>
                                        <td colspan="2" class="text-end py-2.5 text-muted text-uppercase" style="font-size: 0.72rem;">Subtotal Item:</td>
                                        <td class="text-end font-monospace text-dark pe-3 py-2.5">Rp {{ number_format($offlineSale->total_amount, 0, ',', '.') }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    {{-- Card: Riwayat Pembayaran Cicilan --}}
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-white py-3 px-3 border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0 text-success">
                                <i class="fas fa-history me-2"></i>Riwayat Pembayaran &amp; Cicilan
                            </h6>
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2.5 py-1 rounded-pill small font-monospace">
                                {{ $offlineSale->payments->count() }}x Pembayaran
                            </span>
                        </div>
                        <div class="card-body p-0">
                            @if($offlineSale->payments->isEmpty())
                                <div class="text-center py-4 text-muted">
                                    <i class="fas fa-receipt fa-2x mb-2 text-muted opacity-50"></i>
                                    <p class="mb-0 small">Belum ada catatan pembayaran / cicilan yang masuk.</p>
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.8rem;">
                                        <thead class="table-light text-secondary" style="font-size: 0.75rem;">
                                            <tr>
                                                <th class="ps-3 py-2">NO. PEMBAYARAN</th>
                                                <th class="py-2">TANGGAL</th>
                                                <th class="text-end py-2">NOMINAL</th>
                                                <th class="py-2">METODE</th>
                                                <th class="py-2">KAS / BANK</th>
                                                <th class="py-2">CATATAN</th>
                                                <th class="py-2">PETUGAS</th>
                                                @if(auth()->user()->isAdmin() || auth()->user()->isOwner() || in_array(auth()->user()->role, ['admin', 'owner']))
                                                    <th class="text-center py-2 pe-3" style="width: 50px;">AKSI</th>
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
                                                    <td>
                                                        @if($pmt->payment_destination)
                                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 fw-normal">
                                                                {{ $pmt->payment_destination === 'kas_kecil' ? 'Kas Kecil' : ($pmt->payment_destination === 'kas_besar' ? 'Kas Besar' : $pmt->payment_destination) }}
                                                            </span>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                    <td><small class="text-muted">{{ $pmt->notes ?: '-' }}</small></td>
                                                    <td><small class="text-dark">{{ $pmt->user->name ?? '-' }}</small></td>
                                                    @if(auth()->user()->isAdmin() || auth()->user()->isOwner() || in_array(auth()->user()->role, ['admin', 'owner']))
                                                        <td class="text-center pe-3">
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

                    {{-- Card: Riwayat Retur Penjualan --}}
                    @if($offlineSale->returns->isNotEmpty())
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-header bg-white py-3 px-3 border-bottom d-flex justify-content-between align-items-center">
                                <h6 class="fw-bold mb-0 text-warning-emphasis">
                                    <i class="fas fa-undo me-2 text-warning"></i>Riwayat Retur Penjualan
                                </h6>
                                <span class="badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25 px-2.5 py-1 rounded-pill small">
                                    {{ $offlineSale->returns->count() }}x Retur
                                </span>
                            </div>
                            <div class="card-body p-3">
                                @foreach($offlineSale->returns as $ret)
                                    <div class="p-3 border rounded bg-light mb-2">
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
                                        <div class="table-responsive rounded border bg-white">
                                            <table class="table table-sm table-borderless align-middle mb-0" style="font-size:0.78rem;">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th class="ps-2">PRODUK</th>
                                                        <th class="text-center">QTY RETUR</th>
                                                        <th class="text-end">HARGA SATUAN</th>
                                                        <th class="text-end pe-2">SUBTOTAL REFUND</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($ret->items as $rItem)
                                                        <tr>
                                                            <td class="ps-2">{{ $rItem->offlineSaleItem->product_name ?? 'Produk' }}</td>
                                                            <td class="text-center fw-bold text-danger">{{ $rItem->quantity }}x</td>
                                                            <td class="text-end font-monospace">Rp {{ number_format($rItem->unit_price, 0, ',', '.') }}</td>
                                                            <td class="text-end font-monospace text-danger fw-bold pe-2">Rp {{ number_format($rItem->subtotal, 0, ',', '.') }}</td>
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
                </div>

                {{-- RIGHT COLUMN: Informasi Transaksi (di atas) & Informasi Pembayaran (di bawah) --}}
                <div class="col-lg-4">
                    {{-- 1. INFORMASI TRANSAKSI --}}
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-white py-3 px-3 border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0 text-dark">
                                <i class="fas fa-file-invoice me-2 text-primary"></i>Informasi Transaksi
                            </h6>
                            <span class="badge bg-{{ $offlineSale->status_badge }} bg-opacity-10 text-{{ $offlineSale->status_badge }} border border-{{ $offlineSale->status_badge }} border-opacity-25 px-2.5 py-1 rounded-pill small">
                                {{ $offlineSale->status_label }}
                            </span>
                        </div>
                        <div class="card-body p-3">
                            {{-- Key-Value Meta --}}
                            <table class="table table-sm table-borderless mb-0 align-middle" style="font-size: 0.82rem;">
                                <tbody>
                                    <tr>
                                        <td class="text-muted ps-0 py-1.5" style="width: 120px;">No. Transaksi</td>
                                        <td class="text-end pe-0 py-1.5 font-monospace fw-bold text-dark">{{ $offlineSale->sale_number }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted ps-0 py-1.5">Waktu</td>
                                        <td class="text-end pe-0 py-1.5 text-dark">{{ $offlineSale->sold_at?->format('d M Y, H:i') ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted ps-0 py-1.5">Kasir</td>
                                        <td class="text-end pe-0 py-1.5 text-dark fw-semibold">{{ $offlineSale->user->name ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted ps-0 py-1.5">Jenis Transaksi</td>
                                        <td class="text-end pe-0 py-1.5">
                                            @if ($offlineSale->payment_method === 'piutang')
                                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">
                                                    <i class="fas fa-file-invoice-dollar me-1"></i>Kredit (Tempo)
                                                </span>
                                            @else
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                                    <i class="fas fa-money-bill-wave me-1"></i>Tunai (Lunas)
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                    @if ($offlineSale->follow_up_date)
                                        <tr>
                                            <td class="text-muted ps-0 py-1.5">Follow Up DP</td>
                                            <td class="text-end pe-0 py-1.5">
                                                <span class="fw-semibold {{ $offlineSale->needs_follow_up ? 'text-danger' : 'text-dark' }}">
                                                    {{ $offlineSale->follow_up_date->format('d M Y') }}
                                                    @if ($offlineSale->needs_follow_up)
                                                        <span class="badge bg-danger ms-1">Overdue</span>
                                                    @endif
                                                </span>
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>

                            {{-- Divider --}}
                            <hr class="my-2.5 text-muted opacity-25">

                            {{-- Pelanggan Section --}}
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-uppercase text-muted fw-bold" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                                    <i class="fas fa-user-circle me-1 text-primary"></i>Pelanggan
                                </span>
                                @if ($offlineSale->customer_id)
                                    <a href="{{ route('customers.show', $offlineSale->customer_id) }}" class="text-decoration-none small" style="font-size: 0.72rem;">
                                        Detail Profil <i class="fas fa-external-link-alt ms-0.5"></i>
                                    </a>
                                @endif
                            </div>
                            <div class="p-2.5 rounded bg-light border border-light-subtle">
                                <div class="d-flex align-items-center justify-content-between">
                                    <span class="fw-bold text-dark" style="font-size: 0.88rem;">
                                        {{ $offlineSale->buyer_name ?: '(Pelanggan Umum)' }}
                                    </span>
                                </div>
                                @if ($offlineSale->buyer_phone)
                                    <div class="mt-1 small font-monospace text-secondary d-flex align-items-center">
                                        <i class="fas fa-phone-alt me-1.5 text-muted" style="font-size: 0.75rem;"></i>
                                        <span>{{ $offlineSale->buyer_phone }}</span>
                                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $offlineSale->buyer_phone) }}" target="_blank" class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 ms-2 text-decoration-none px-1.5 py-0.5" title="Chat via WhatsApp">
                                            <i class="fab fa-whatsapp me-0.5"></i> WA
                                        </a>
                                    </div>
                                @endif
                                @if ($offlineSale->institution_name)
                                    <div class="mt-1.5 pt-1.5 border-top border-light-subtle small text-dark">
                                        <i class="fas fa-building me-1 text-info"></i>
                                        <span class="text-muted">Instansi:</span> <strong>{{ $offlineSale->institution_name }}</strong>
                                    </div>
                                @endif
                                @if ($offlineSale->customer && $offlineSale->customer->address)
                                    <div class="mt-1.5 pt-1.5 border-top border-light-subtle small text-muted" style="line-height: 1.4;">
                                        <i class="fas fa-map-marker-alt me-1 text-danger"></i>{{ $offlineSale->customer->address }}
                                    </div>
                                @endif
                            </div>

                            {{-- Dropship Info (if applicable) --}}
                            @if ($offlineSale->is_dropship)
                                <div class="p-2.5 rounded bg-warning bg-opacity-10 border border-warning border-opacity-25 mt-2.5 small">
                                    <div class="text-warning-emphasis fw-bold mb-1 d-flex align-items-center" style="font-size: 0.72rem; text-transform: uppercase;">
                                        <i class="fas fa-shipping-fast me-1"></i> Dropshipper &amp; Resi
                                    </div>
                                    <div class="text-dark">
                                        <span class="text-muted">Pengirim:</span> <strong>{{ $offlineSale->dropshipper_name ?? '-' }}</strong>
                                        @if($offlineSale->dropshipper_phone)
                                            <span class="text-muted">({{ $offlineSale->dropshipper_phone }})</span>
                                        @endif
                                    </div>
                                    @if ($offlineSale->resi_number)
                                        <div class="mt-1 text-dark">
                                            <span class="text-muted">No. Resi / Jasa:</span>
                                            <span class="font-monospace fw-bold text-primary">{{ $offlineSale->resi_number }}</span>
                                        </div>
                                    @endif
                                    @if ($offlineSale->resi_file)
                                        <div class="mt-1.5">
                                            <a href="{{ Storage::url($offlineSale->resi_file) }}" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2 fw-bold" style="font-size:0.72rem;">
                                                <i class="fas fa-file-download me-1"></i> Unduh Label Resi
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            {{-- Catatan (if any) --}}
                            @if ($offlineSale->notes)
                                <div class="p-2.5 rounded bg-light border mt-2.5 small text-secondary">
                                    <div class="text-muted fw-bold mb-0.5" style="font-size: 0.7rem; text-transform: uppercase;">
                                        <i class="far fa-comment-dots me-1 text-primary"></i>Catatan Transaksi
                                    </div>
                                    <div class="text-dark">{{ $offlineSale->notes }}</div>
                                </div>
                            @endif

                            {{-- Alasan Pembatalan (if cancelled) --}}
                            @if ($offlineSale->status === \App\Models\OfflineSale::STATUS_CANCELLED && $offlineSale->cancellation_reason)
                                <div class="p-2.5 rounded bg-danger bg-opacity-10 border border-danger border-opacity-25 mt-2.5 small text-danger">
                                    <strong class="d-block mb-0.5"><i class="fas fa-times-circle me-1"></i>Alasan Pembatalan:</strong>
                                    <span>{{ $offlineSale->cancellation_reason }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- 2. INFORMASI PEMBAYARAN --}}
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-white py-3 px-3 border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0 text-dark">
                                <i class="fas fa-wallet me-2 text-success"></i>Informasi Pembayaran
                            </h6>
                            <span class="badge bg-{{ $offlineSale->payment_status_badge }} bg-opacity-10 text-{{ $offlineSale->payment_status_badge }} border border-{{ $offlineSale->payment_status_badge }} border-opacity-25 px-2.5 py-1 rounded-pill small">
                                {{ $offlineSale->payment_status_label }}
                            </span>
                        </div>
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">Subtotal Item</span>
                                <span class="font-monospace text-dark fw-semibold small">Rp {{ number_format($offlineSale->total_amount, 0, ',', '.') }}</span>
                            </div>
                            @if ($offlineSale->discount_amount > 0)
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted small">Diskon Transaksi</span>
                                    <span class="font-monospace text-danger fw-semibold small">- Rp {{ number_format($offlineSale->discount_amount, 0, ',', '.') }}
                                        @if($offlineSale->discount_type === 'percentage' && $offlineSale->discount_value > 0)
                                            ({{ number_format($offlineSale->discount_value, 0) }}%)
                                        @endif
                                    </span>
                                </div>
                            @endif
                            @if ($offlineSale->payment_destination)
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted small">Kas / Bank Tujuan</span>
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 small fw-normal">
                                        {{ $offlineSale->payment_destination === 'kas_kecil' ? 'Kas Kecil (Operasional)' : ($offlineSale->payment_destination === 'kas_besar' ? 'Kas Besar (Utama)' : $offlineSale->payment_destination) }}
                                    </span>
                                </div>
                            @endif

                            <hr class="my-2.5 text-muted opacity-25">

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-dark fw-bold">Grand Total</span>
                                <span class="font-monospace text-success fw-bold fs-5">Rp {{ number_format($offlineSale->grand_total, 0, ',', '.') }}</span>
                            </div>

                            {{-- Payment Progress Box --}}
                            <div class="p-3 rounded bg-light border border-light-subtle mb-3">
                                <div class="d-flex justify-content-between mb-1.5 small">
                                    <span class="text-muted">Sudah Dibayar</span>
                                    <span class="font-monospace text-success fw-bold">Rp {{ number_format($offlineSale->paid_amount, 0, ',', '.') }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2 small">
                                    <span class="text-muted">Sisa Kekurangan</span>
                                    <span class="font-monospace {{ $offlineSale->remaining_amount > 0 ? 'text-danger fw-bold fs-6' : 'text-muted' }}">
                                        Rp {{ number_format($offlineSale->remaining_amount, 0, ',', '.') }}
                                    </span>
                                </div>
                                
                                <div class="progress rounded-pill mb-1.5" style="height: 8px;">
                                    <div class="progress-bar {{ $offlineSale->is_paid ? 'bg-success' : 'bg-warning' }}" 
                                         role="progressbar" 
                                         style="width: {{ $offlineSale->payment_percentage }}%;" 
                                         aria-valuenow="{{ $offlineSale->payment_percentage }}" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between text-muted" style="font-size: 0.72rem;">
                                    <span>Terbayar {{ $offlineSale->payment_percentage }}%</span>
                                    <span class="fw-semibold {{ $offlineSale->is_paid ? 'text-success' : 'text-warning-emphasis' }}">{{ $offlineSale->is_paid ? 'Lunas 100%' : 'Belum Lunas' }}</span>
                                </div>
                            </div>

                            @if ($offlineSale->status !== \App\Models\OfflineSale::STATUS_CANCELLED && !$offlineSale->is_paid)
                                <div class="d-grid">
                                    <button type="button" class="btn btn-success btn-sm py-2 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalMarkPaidShow">
                                        <i class="fas fa-plus-circle me-1"></i> Catat Pembayaran / Cicilan
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
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
        <form action="{{ route('offline_sales.create_spk', $offlineSale->id) }}" method="POST" class="modal-content border-0" style="display: flex; flex-direction: column; height: 100%; max-height: 100vh; overflow: hidden;">
            @csrf
            <div class="modal-header bg-warning bg-opacity-10 border-bottom px-4 py-3" style="flex-shrink: 0;">
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
            <div class="modal-body p-4" style="flex: 1 1 auto; overflow-y: auto !important; min-height: 0;">
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
                            <div class="border rounded p-3 bg-light shadow-sm">
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

                                <div class="table-responsive bg-white rounded border mb-0">
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
                            <div class="p-3 bg-white rounded border shadow-sm d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                    <div>
                                        <h6 class="fw-bold text-dark mb-0">
                                            <i class="fas fa-clipboard-list me-1 text-primary"></i>Ringkasan SPK yang Terbit
                                        </h6>
                                        <small class="text-muted" style="font-size: 0.72rem;">Pratinjau otomatis SPK yang akan dibentuk</small>
                                    </div>
                                    <span class="badge bg-primary px-2.5 py-1.5 fs-7" id="modal-spk-summary-badge-show">0 SPK</span>
                                </div>
                                <div id="modal-spk-summary-list-show" class="d-flex flex-column gap-2" style="font-size: 0.82rem;">
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
            <div class="modal-footer px-4 py-3 bg-light border-top" style="flex-shrink: 0;">
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
    document.querySelectorAll('#modalCancelShow form, #modalMarkPaidShow form, #modalReturnShow form, #modalCreateSpkShow form').forEach(function (form) {
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

            const submitBtnShow = modalCreateSpkShow.querySelector('button[type="submit"]');
            if (groupKeys.length === 0) {
                summaryList.innerHTML = '<div class="alert alert-danger py-2 mb-0 small"><i class="fas fa-exclamation-triangle me-1"></i>Pilih minimal 1 SPK tujuan untuk item pesanan.</div>';
                if (submitBtnShow) submitBtnShow.disabled = true;
                return;
            }
            if (submitBtnShow) submitBtnShow.disabled = false;

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
