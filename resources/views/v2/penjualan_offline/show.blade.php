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
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('v2.penjualan_offline.index') }}" class="btn btn-sm btn-outline-secondary px-3">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
        @if($sale->status !== 'cancelled')
            <a href="{{ route('v2.penjualan_offline.edit', $sale->id) }}" class="btn btn-sm btn-warning text-dark px-3 fw-semibold">
                <i class="bi bi-pencil-square me-1"></i> Edit Transaksi
            </a>
            <button type="button" class="btn btn-sm btn-danger px-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalCancelSaleDetail">
                <i class="bi bi-x-circle me-1"></i> Batalkan Transaksi
            </button>
        @endif
        <a href="{{ route('offline_sales.print', $sale->id) }}?format=invoice" target="_blank" class="btn btn-sm btn-dark px-3">
            <i class="bi bi-printer me-1"></i> Cetak Invoice
        </a>
        @if($sale->status !== 'cancelled')
            @if($sale->spks->isNotEmpty())
                @php $firstSpk = $sale->spks->first(); @endphp
                <a href="{{ route('spks.show', $firstSpk->id) }}" target="_blank" class="btn btn-sm btn-info text-white px-3 fw-semibold">
                    <i class="bi bi-gear-wide-connected me-1"></i> Lihat SPK ({{ $firstSpk->no_produksi ?: $firstSpk->no_spk }})
                </a>
            @else
                <button type="button" class="btn btn-sm btn-primary px-3 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalCreateSpk">
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
                        @foreach($sale->items as $item)
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-bold text-dark">{{ $item->product_name ?? ($item->masterProduct->name ?? 'Produk POS') }}</div>
                                    <small class="text-muted font-monospace">SKU: {{ $item->sku ?? ($item->masterProduct->sku ?? '-') }}</small>
                                </td>
                                <td class="text-center fw-bold">{{ number_format($item->quantity, 0, ',', '.') }}</td>
                                <td class="text-end font-monospace">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                <td class="text-end font-monospace text-muted">Rp {{ number_format($item->masterProduct->purchase_price ?? 0, 0, ',', '.') }}</td>
                                <td class="text-end pe-3 font-monospace fw-bold text-dark">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="4" class="text-end fw-bold">Subtotal Produk:</td>
                            <td class="text-end pe-3 font-monospace fw-bold">Rp {{ number_format($sale->total_amount, 0, ',', '.') }}</td>
                        </tr>
                        @if((float)$sale->discount_amount > 0)
                            <tr>
                                <td colspan="4" class="text-end text-danger fw-semibold">Diskon Penjualan:</td>
                                <td class="text-end pe-3 font-monospace text-danger fw-bold">- Rp {{ number_format($sale->discount_amount, 0, ',', '.') }}</td>
                            </tr>
                        @endif
                        @if((float)$sale->shipping_fee > 0)
                            <tr>
                                <td colspan="4" class="text-end text-muted fw-semibold">Biaya Pengiriman:</td>
                                <td class="text-end pe-3 font-monospace fw-bold">+ Rp {{ number_format($sale->shipping_fee, 0, ',', '.') }}</td>
                            </tr>
                        @endif
                        <tr class="table-active">
                            <td colspan="4" class="text-end fw-bold fs-6">GRAND TOTAL:</td>
                            <td class="text-end pe-3 font-monospace fw-bold fs-6 text-primary">Rp {{ number_format($sale->grand_total, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Payments History -->
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="card-title fw-bold mb-0">Riwayat Pembayaran Kasir</h6>
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
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sale->payments as $pmt)
                            <tr>
                                <td class="ps-3">{{ \Carbon\Carbon::parse($pmt->payment_date ?: $pmt->created_at)->format('d/m/Y H:i') }}</td>
                                <td><span class="badge bg-light text-dark border">{{ strtoupper($pmt->payment_method) }}</span></td>
                                <td>{{ ucwords(str_replace('_', ' ', $pmt->payment_destination ?: 'kas_besar')) }}</td>
                                <td class="text-muted">{{ $pmt->notes ?: '-' }}</td>
                                <td class="text-end pe-3 font-monospace fw-bold text-success">Rp {{ number_format($pmt->amount, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-3 text-muted fst-italic">Belum ada riwayat pembayaran tercatat.</td>
                            </tr>
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
                    <small class="text-muted d-block">Nama Pembeli:</small>
                    <span class="fw-bold text-dark fs-6">{{ $sale->buyer_name ?: 'Pelanggan Walk-in (Umum)' }}</span>
                </div>
                @if($sale->institution_name)
                    <div class="mb-2">
                        <small class="text-muted d-block">Instansi / Toko Pembeli:</small>
                        <span class="fw-bold text-primary"><i class="bi bi-building me-1"></i>{{ $sale->institution_name }}</span>
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

        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="card-title fw-bold mb-0">Status Pembayaran</h6>
            </div>
            <div class="card-body p-3" style="font-size: 0.85rem;">
                @php
                    $sisa = max(0, (float)$sale->grand_total - (float)$sale->paid_amount);
                    $isLunas = (float)$sale->paid_amount >= (float)$sale->grand_total;
                @endphp
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Total Tagihan:</span>
                    <span class="fw-bold font-monospace">Rp {{ number_format($sale->grand_total, 0, ',', '.') }}</span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Total Terbayar:</span>
                    <span class="fw-bold font-monospace text-success">Rp {{ number_format($sale->paid_amount, 0, ',', '.') }}</span>
                </div>
                <div class="d-flex justify-content-between py-2 mb-3">
                    <span class="text-muted">Sisa Piutang:</span>
                    <span class="fw-bold font-monospace {{ $sisa > 0 ? 'text-danger fs-6' : 'text-muted' }}">
                        {{ $sisa > 0 ? 'Rp '.number_format($sisa, 0, ',', '.') : 'LUNAS (Rp 0)' }}
                    </span>
                </div>
                <div class="text-center p-2 rounded-3 {{ $isLunas ? 'bg-success bg-opacity-10 text-success border border-success' : 'bg-danger bg-opacity-10 text-danger border border-danger' }}">
                    <i class="bi bi-{{ $isLunas ? 'check-circle-fill' : 'exclamation-triangle-fill' }} me-1"></i>
                    <strong>{{ $isLunas ? 'PEMBAYARAN LUNAS' : 'MASIH ADA SISA PIUTANG' }}</strong>
                </div>
            </div>
        </div>
    </div>
</div>

@if($sale->status !== 'cancelled')
{{-- Modal Batalkan Transaksi --}}
<div class="modal fade" id="modalCancelSaleDetail" tabindex="-1" aria-labelledby="modalCancelSaleDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white py-2.5">
                <h6 class="modal-title fw-bold" id="modalCancelSaleDetailLabel">
                    <i class="bi bi-exclamation-triangle-fill me-1.5"></i> Batalkan Transaksi Penjualan #{{ $sale->sale_number }}
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('v2.penjualan_offline.cancel', $sale->id) }}">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-warning py-2 px-3 small d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-info-circle-fill fs-5 text-warning"></i>
                        <div>
                            Pembatalan transaksi akan mengubah status nota menjadi <strong>Batal</strong> dan otomatis mengembalikan stok barang yang terpotong ke inventory.
                        </div>
                    </div>

                    <div class="mb-2">
                        <label for="cancellation_reason_detail" class="form-label small fw-bold text-dark">
                            Alasan Pembatalan <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" id="cancellation_reason_detail" name="cancellation_reason" rows="3" 
                                  placeholder="Tuliskan alasan pembatalan transaksi ini..." required minlength="5"></textarea>
                        <div class="form-text small text-muted">Minimal 5 karakter. Wajib diisi sebagai catatan audit.</div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-3">
                    <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-sm btn-danger px-3 fw-bold shadow-sm">
                        <i class="bi bi-check-circle me-1"></i> Ya, Batalkan Transaksi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Terbitkan SPK Produksi --}}
@php
    $spkItems = $sale->items->map(fn($i) => [
        'id'   => $i->id,
        'name' => $i->product_name ?? ($i->masterProduct->name ?? 'Produk POS'),
        'qty'  => (int) $i->quantity,
        'sku'  => $i->sku ?? ($i->masterProduct->sku ?? ''),
    ]);
@endphp
<div class="modal fade" id="modalCreateSpk" tabindex="-1" aria-labelledby="modalCreateSpkLabel" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
        <form method="POST" action="{{ route('offline_sales.create_spk', $sale->id) }}" class="modal-content border-0" style="display: flex; flex-direction: column; height: 100%; max-height: 100vh; overflow: hidden;">
            @csrf
            <div class="modal-header bg-warning bg-opacity-10 border-bottom px-4 py-3" style="flex-shrink: 0;">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-warning bg-opacity-25 p-2 text-warning d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-hammer fs-6 text-dark"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="modalCreateSpkLabel">
                            Terbitkan SPK Produksi
                        </h5>
                        <small class="text-muted" style="font-size: 0.75rem;">Konfigurasi Nomor Produksi, Tahap Awal, dan Pembagian SPK Pesanan Offline</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body p-4" style="flex: 1 1 auto; overflow-y: auto !important; min-height: 0;">
                <div class="container-fluid px-lg-3 py-1">
                    {{-- Info Banner --}}
                    <div class="d-flex justify-content-between align-items-center mb-3 p-3 bg-light rounded border flex-wrap gap-2">
                        <div>
                            <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.7rem;">Transaksi Penjualan</small>
                            <span class="fw-bold font-monospace text-primary fs-5">#{{ $sale->sale_number }}</span>
                            <span class="text-muted ms-2">&bull; Pembeli: <strong>{{ $sale->buyer_name ?: 'Umum (Pelanggan Walk-in)' }}</strong></span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle py-2 px-3 fs-7">
                                <i class="bi bi-layers me-1"></i> 1 No. Produksi = Multi SPK Produk
                            </span>
                        </div>
                    </div>

                    {{-- Parameter Utama SPK --}}
                    <div class="row g-3 mb-4">
                        <div class="col-lg-4 col-md-6">
                            <label for="spk_no_produksi" class="form-label fw-semibold small text-dark mb-1">
                                <i class="bi bi-hash text-primary me-1"></i>Nomor / Kode Produksi <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="no_produksi" id="spk_no_produksi" class="form-control form-control-sm font-monospace fw-bold" value="{{ \App\Models\Spk::generateNoProduksi() }}" required>
                            <div class="form-text text-muted" style="font-size: 0.75rem;">
                                Satu kode produksi ini akan menaungi seluruh SPK pesanan ini.
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <label for="spk_tahap_saat_ini" class="form-label fw-semibold small text-dark mb-1">
                                <i class="bi bi-list-task text-primary me-1"></i>Tahap Awal Produksi <span class="text-danger">*</span>
                            </label>
                            <select name="tahap_saat_ini" id="spk_tahap_saat_ini" class="form-select form-select-sm fw-semibold" required>
                                <option value="Antrian &amp; Sampling" selected>⏳ Antrian &amp; Sampling (Langsung Antrian)</option>
                                <option value="Perencanaan">📋 Perencanaan / Pesanan Baru</option>
                                <option value="Tahap Pemotongan">✂️ Tahap Pemotongan</option>
                                <option value="Tahap Jahit">🪡 Tahap Jahit</option>
                            </select>
                            <div class="form-text text-success" style="font-size: 0.75rem;">
                                <i class="bi bi-check-circle me-1"></i>SPK langsung masuk antrian aktif (bukan Draft).
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-12">
                            <label for="spk_deadline" class="form-label fw-semibold small text-dark mb-1">
                                <i class="bi bi-calendar-event text-primary me-1"></i>Target Deadline SPK Produksi <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="deadline" id="spk_deadline" class="form-control form-control-sm" value="{{ now()->addDays(7)->format('Y-m-d') }}" required>
                            <div class="form-text text-muted" style="font-size: 0.75rem;">Target tanggal selesai pengerjaan oleh tim produksi.</div>
                        </div>
                    </div>

                    {{-- Layout 2 Kolom --}}
                    <div class="row g-4">
                        {{-- Kolom Kiri: Tabel Pembagian SPK --}}
                        <div class="col-lg-7">
                            <div class="border rounded p-3 bg-light shadow-sm">
                                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                    <div>
                                        <label class="form-label fw-bold text-dark mb-0 fs-6">
                                            <i class="bi bi-diagram-3 me-1 text-primary"></i>Tentukan Pembagian SPK (SPK 1 - SPK 5)
                                        </label>
                                        <div class="text-muted small" style="font-size: 0.75rem;">
                                            Pilih SPK tujuan untuk masing-masing item transaksi.
                                        </div>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary btn-sm py-1 px-2.5 fw-semibold" id="btn-quick-combine" title="Semua item dijadikan 1 SPK">
                                            <i class="bi bi-link-45deg me-1"></i>Gabung Semua (1 SPK)
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2.5 fw-semibold" id="btn-quick-split" title="Pisahkan tiap item menjadi SPK sendiri-sendiri">
                                            <i class="bi bi-scissors me-1"></i>Pisah Masing-masing
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
                                        <tbody id="modal-spk-items-table">
                                            @foreach($spkItems as $idx => $it)
                                                <tr>
                                                    <td class="text-center text-muted">{{ $idx + 1 }}</td>
                                                    <td>
                                                        <div class="fw-semibold text-dark">{{ $it['name'] }}</div>
                                                        @if(!empty($it['sku']))
                                                            <small class="text-muted font-monospace">{{ $it['sku'] }}</small>
                                                        @endif
                                                    </td>
                                                    <td class="text-center fw-bold font-monospace">{{ $it['qty'] }} Pcs</td>
                                                    <td>
                                                        <select name="spk_group[{{ $it['id'] }}]" class="form-select form-select-sm fw-bold spk-group-select" data-item-id="{{ $it['id'] }}">
                                                            <option value="1" selected>Masuk ke SPK 1</option>
                                                            <option value="2">Masuk ke SPK 2</option>
                                                            <option value="3">Masuk ke SPK 3</option>
                                                            <option value="4">Masuk ke SPK 4</option>
                                                            <option value="5">Masuk ke SPK 5</option>
                                                            <option value="0">Lewati (Jangan Buat SPK)</option>
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
                                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                    <div>
                                        <h6 class="fw-bold text-dark mb-0">
                                            <i class="bi bi-clipboard-check me-1 text-primary"></i>Ringkasan SPK yang Terbit
                                        </h6>
                                        <small class="text-muted" style="font-size: 0.72rem;">Pratinjau otomatis SPK yang akan dibentuk</small>
                                    </div>
                                    <span class="badge bg-primary px-2.5 py-1.5 fs-7" id="modal-spk-summary-badge">0 SPK</span>
                                </div>
                                <div id="modal-spk-summary-list" class="d-flex flex-column gap-2" style="font-size: 0.82rem;">
                                </div>
                                <div class="mt-3 pt-2 border-top text-muted small" style="font-size: 0.75rem;">
                                    <i class="bi bi-info-circle text-primary me-1"></i>
                                    Setiap SPK akan memiliki nomor unik di modul SPK di bawah No. Produksi yang sama.
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
            const item = rawItems.find(i => String(i.id) === String(itemId)) || { name: 'Produk', qty: 1 };

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
            summaryList.innerHTML = '<div class="alert alert-danger py-2 mb-0 small"><i class="bi bi-exclamation-triangle me-1"></i>Pilih minimal 1 SPK tujuan untuk item pesanan.</div>';
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
            const itemsDetail = grp.items.map(it => `&bull; ${it.name} <strong>(${it.qty} pcs)</strong>`).join('<br>');

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
@endif

@endsection
