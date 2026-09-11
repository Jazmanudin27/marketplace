@extends('layouts.app')

@section('title', 'Edit Transaksi — ' . $offlineSale->sale_number)
@section('page-title', 'Edit Transaksi')

@section('content')
<div class="container-fluid px-0">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="fas fa-edit text-warning me-2"></i>Edit Transaksi Penjualan Offline
            </h4>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary fs-6">{{ $offlineSale->sale_number }}</span>
                <span class="text-muted small">Dibuat pada {{ $offlineSale->sold_at?->format('d/m/Y H:i') }}</span>
                <span class="badge bg-secondary">{{ $offlineSale->status_label }}</span>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('offline_sales.show', $offlineSale->id) }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Kembali ke Detail
            </a>
            <a href="{{ route('offline_sales.index') }}" class="btn btn-outline-dark btn-sm">
                <i class="fas fa-list me-1"></i> Daftar Transaksi
            </a>
        </div>
    </div>

    @if ($offlineSale->is_po && $offlineSale->spks()->exists())
        <div class="alert alert-info border d-flex align-items-center gap-2 mb-3 py-2 px-3 shadow-sm">
            <i class="fas fa-info-circle fa-lg text-info"></i>
            <div class="small">
                <strong>Catatan SPK Produksi:</strong> Transaksi PO ini telah memiliki <strong>{{ $offlineSale->spks()->count() }} SPK</strong> aktif. Jika Anda mengubah daftar item atau kuantitas, pastikan untuk menyesuaikan SPK terkait di modul SPK Produksi.
            </div>
        </div>
    @endif

    <form id="offline-form" action="{{ route('offline_sales.update', $offlineSale->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        {{-- ========================================================================= --}}
        {{-- BARIS 1: DATA PELANGGAN & RINGKASAN TOTAL                                 --}}
        {{-- ========================================================================= --}}
        <div class="row g-3 mb-3">
            {{-- DATA PELANGGAN (COL-8) --}}
            <div class="col-lg-8">
                <div class="card border shadow-sm h-100">
                    <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-dark">
                            <i class="fas fa-user text-primary me-2"></i>Data Pelanggan &amp; Pembeli
                        </span>
                        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCreateCustomer">
                            <i class="fas fa-plus me-1"></i>Pelanggan Baru
                        </button>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-3">
                            {{-- Pilih Master Pelanggan --}}
                            <div class="col-md-6" id="customer-select-wrapper">
                                <label class="form-label small fw-semibold text-secondary mb-1">
                                    Pilih Master Pelanggan <span class="text-danger">*</span>
                                </label>
                                <select name="customer_id" id="customer-select" class="form-select form-select-sm select2" style="width: 100%;">
                                    <option value="">-- Tanpa Master Pelanggan (Manual) --</option>
                                    @foreach ($customers as $cust)
                                        <option value="{{ $cust->id }}" data-name="{{ $cust->name }}"
                                            data-phone="{{ $cust->phone }}" data-address="{{ $cust->address }}"
                                            data-category="{{ $cust->category }}" data-category-label="{{ $cust->category_label }}"
                                            data-tags="{{ $cust->tags }}" data-balance="{{ $cust->balance ?? 0 }}"
                                            {{ $offlineSale->customer_id == $cust->id ? 'selected' : '' }}>
                                            [{{ $cust->category_label }}] {{ $cust->name }} {{ $cust->phone ? '(' . $cust->phone . ')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Tanggal Transaksi --}}
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary mb-1">
                                    <i class="far fa-calendar-alt me-1"></i>Waktu Transaksi
                                </label>
                                <input type="datetime-local" name="sold_at" class="form-control form-control-sm"
                                    value="{{ $offlineSale->sold_at ? $offlineSale->sold_at->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i') }}">
                            </div>

                            {{-- Nama Pembeli --}}
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary mb-1" id="buyer-name-label">
                                    Nama Pembeli <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="buyer_name" id="buyer-name-input" class="form-control form-control-sm"
                                    placeholder="Nama pembeli..." value="{{ $offlineSale->buyer_name }}" required>
                            </div>

                            {{-- No HP --}}
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary mb-1" id="buyer-phone-label">No. HP / WhatsApp</label>
                                <input type="text" name="buyer_phone" id="buyer-phone-input" class="form-control form-control-sm"
                                    placeholder="0812xxxx" value="{{ $offlineSale->buyer_phone }}">
                            </div>

                            {{-- Alamat --}}
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary mb-1">Alamat Pelanggan</label>
                                <textarea name="buyer_address" id="buyer-address-input" class="form-control form-control-sm"
                                    rows="1" placeholder="Alamat pelanggan...">{{ $offlineSale->buyer_address }}</textarea>
                            </div>

                            {{-- Instansi / Saluran / Channel (Opsional) --}}
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary mb-1">
                                    Instansi / Saluran / Channel <span class="badge bg-secondary opacity-75">Opsional</span>
                                </label>
                                <input type="text" name="institution_name" id="institution-name-input" class="form-control form-control-sm"
                                    placeholder="Contoh: Dinas Pendidikan, Sekolah ABC, PT Sinar Harapan..." value="{{ $offlineSale->institution_name }}">
                            </div>
                        </div>

                        <!-- Hidden fields for dropship -->
                        <input type="hidden" name="is_dropship" id="is-dropship-toggle" value="{{ $offlineSale->is_dropship ? '1' : '0' }}">
                        <input type="hidden" name="dropshipper_name" id="dropshipper-name-input" value="{{ $offlineSale->dropshipper_name }}">
                        <input type="hidden" name="dropshipper_phone" id="dropshipper-phone-input" value="{{ $offlineSale->dropshipper_phone }}">

                        {{-- Section Dropship & Upload Resi --}}
                        <div id="dropship-detail-section" class="alert alert-warning border mt-3 mb-0" style="{{ $offlineSale->is_dropship ? '' : 'display: none;' }}">
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge bg-warning text-dark me-2 fw-bold"><i class="fas fa-shipping-fast me-1"></i>MODE DROPSHIP</span>
                                <span class="small fw-semibold text-dark">Informasi Resi &amp; Label Pengiriman</span>
                            </div>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold text-secondary mb-1">Jenis Jasa Kirim / Ekspedisi / No. Resi</label>
                                    <input type="text" name="resi_number" id="resi-number-input" class="form-control form-control-sm"
                                        placeholder="Contoh: J&amp;T, JNE, SiCepat, Resi..." value="{{ $offlineSale->resi_number }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold text-secondary mb-1">Ganti Label / Dokumen Resi</label>
                                    <input type="file" name="resi_file" id="resi-file-input" class="form-control form-control-sm" accept="image/*,.pdf">
                                    @if ($offlineSale->resi_file)
                                        <div class="form-text small">
                                            Resi saat ini: <a href="{{ asset('storage/' . $offlineSale->resi_file) }}" target="_blank" class="text-primary fw-bold">Lihat Dokumen</a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TIPE PESANAN & TOTAL (COL-4) --}}
            <div class="col-lg-4">
                <div class="card border shadow-sm h-100">
                    <div class="card-header bg-white py-2 px-3 border-bottom">
                        <span class="fw-bold text-dark">
                            <i class="fas fa-receipt text-success me-2"></i>Total &amp; Tipe Pesanan
                        </span>
                    </div>
                    <div class="card-body p-3 d-flex flex-column justify-content-between">
                        {{-- Switch PO Produksi --}}
                        <div class="card bg-light border p-3 mb-3">
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" name="is_po" id="is-po-switch" value="1" {{ $offlineSale->is_po ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold text-dark small" for="is-po-switch">
                                    <i class="fas fa-hammer text-primary me-1"></i> Pre-Order / PO Produksi (SPK)
                                </label>
                            </div>
                            <div id="po-deadline-container" class="mt-2 pt-2 border-top" style="{{ $offlineSale->is_po ? '' : 'display: none;' }}">
                                <div class="mb-1">
                                    <label class="form-label small fw-semibold text-danger mb-1">
                                        <i class="fas fa-bell text-danger me-1"></i>Tanggal Follow Up DP
                                    </label>
                                    <input type="date" name="follow_up_date" id="po-follow-up-input" class="form-control form-control-sm border-danger border-opacity-50"
                                        value="{{ $offlineSale->follow_up_date ? $offlineSale->follow_up_date->format('Y-m-d') : now()->addDays(3)->format('Y-m-d') }}">
                                    <div class="form-text text-muted small" style="font-size: 0.72rem;">
                                        Jika DP belum masuk hingga tanggal ini, sistem akan memunculkan alert perlu follow up.
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Display Total Banner --}}
                        <div class="card bg-light border text-center p-3 my-auto">
                            <div class="text-muted small text-uppercase fw-semibold mb-1">Total Pembayaran</div>
                            <div class="fs-3 fw-bold text-success font-monospace" id="display-grand-total">
                                Rp {{ number_format($offlineSale->grand_total, 0, ',', '.') }}
                            </div>
                            <div class="small text-muted mt-1">
                                Sudah Dibayar: <strong class="text-primary font-monospace">Rp {{ number_format($offlineSale->paid_amount, 0, ',', '.') }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- BARIS 2: PILIH PRODUK & KERANJANG & RINGKASAN PEMBAYARAN                  --}}
        {{-- ========================================================================= --}}
        <div class="row g-3">
            {{-- PILIH PRODUK & KERANJANG BELANJA (COL-8) --}}
            <div class="col-lg-8">
                <div class="card border shadow-sm mb-3" id="product-section-card">
                    <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-dark">
                            <i class="fas fa-boxes text-primary me-2"></i>Pilih Produk &amp; Keranjang Belanja
                        </span>
                        <span class="badge bg-info bg-opacity-10 text-info fw-semibold">
                            Klik produk untuk menambah ke keranjang
                        </span>
                    </div>
                    <div class="card-body p-3">
                        {{-- Pencarian Produk --}}
                        <div class="mb-3">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" id="product-search" class="form-control form-control-sm" placeholder="Cari nama produk atau SKU...">
                            </div>
                        </div>

                        {{-- List Produk --}}
                        <div id="product-list" class="list-group overflow-auto mb-3" style="max-height: 220px;">
                            @foreach ($products as $product)
                                @php
                                    $resellerPrice = $product->reseller_price && $product->reseller_price > 0 ? $product->reseller_price : $product->price;
                                @endphp
                                <a href="javascript:void(0)" class="list-group-item list-group-item-action product-row d-flex justify-content-between align-items-center py-2 px-3 text-decoration-none"
                                    data-id="{{ $product->id }}"
                                    data-name="{{ $product->name }}" data-sku="{{ $product->sku }}"
                                    data-price="{{ $product->price }}" data-reseller-price="{{ $resellerPrice }}"
                                    data-stock="{{ $product->stock }}" data-is-po="{{ $product->is_preorder ? 1 : 0 }}">
                                    <div>
                                        <div class="fw-semibold text-dark small">{{ $product->name }}</div>
                                        <div class="text-muted small font-monospace">
                                            {{ $product->sku }} &bull; Stok: <span class="fw-bold">{{ $product->stock }}</span> {{ $product->unit ?? 'Pcs' }}
                                        </div>
                                    </div>
                                    <div class="text-end text-nowrap">
                                        <div class="price-normal-display fw-bold text-success font-monospace small" style="{{ $offlineSale->is_dropship ? 'display:none;' : '' }}">
                                            Rp {{ number_format($product->price, 0, ',', '.') }}
                                        </div>
                                        <div class="price-dropship-display fw-bold text-warning font-monospace small" style="{{ $offlineSale->is_dropship ? '' : 'display:none;' }}">
                                            <span class="badge bg-warning text-dark me-1">Dropship</span>
                                            Rp {{ number_format($resellerPrice, 0, ',', '.') }}
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>

                        {{-- Keranjang Belanja --}}
                        <div class="fw-bold text-dark mb-2 mt-4 d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-shopping-cart text-primary me-2"></i>Daftar Item Transaksi</span>
                            <span class="badge bg-secondary font-monospace" id="cart-count-badge">0 item</span>
                        </div>

                        <div id="cart-empty" class="text-center py-4 text-muted border border-dashed rounded bg-light" style="display: none;">
                            <i class="fas fa-shopping-cart fa-2x mb-2 opacity-50"></i>
                            <div class="small">Belum ada produk yang dipilih</div>
                        </div>

                        <div class="table-responsive border rounded" id="cart-table">
                            <table class="table table-bordered table-hover align-middle mb-0">
                                <thead class="table-light text-secondary small">
                                    <tr>
                                        <th class="ps-3">PRODUK</th>
                                        <th class="text-center" style="width: 70px;">PROMO</th>
                                        <th class="text-center" style="width: 100px;">QTY</th>
                                        <th class="text-end" style="width: 115px;">HARGA</th>
                                        <th class="text-center" style="width: 125px;">DISKON</th>
                                        <th class="text-end" style="width: 125px;">SUBTOTAL</th>
                                        <th class="text-center" style="width: 50px;">AKSI</th>
                                    </tr>
                                </thead>
                                <tbody id="cart-body" class="small"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- RINGKASAN PESANAN (COL-4) --}}
            <div class="col-lg-4">
                <div class="card border shadow-sm mb-3">
                    <div class="card-header bg-white py-2 px-3 border-bottom">
                        <span class="fw-bold text-dark">
                            <i class="fas fa-file-invoice-dollar text-primary me-2"></i>Ringkasan Tagihan &amp; Simpan
                        </span>
                    </div>
                    <div class="card-body p-3">
                        {{-- Subtotal --}}
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">Subtotal</span>
                            <span class="fw-bold text-dark font-monospace" id="display-subtotal">Rp 0</span>
                        </div>

                        {{-- Diskon Nota --}}
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary mb-1">Diskon Nota / Transaksi</label>
                            <div class="input-group input-group-sm">
                                <button type="button" class="btn {{ $offlineSale->discount_type === 'percentage' ? 'btn-primary' : 'btn-outline-primary' }} fw-bold" id="btn-global-disc-toggle" style="width: 48px;">
                                    {{ $offlineSale->discount_type === 'percentage' ? '%' : 'Rp' }}
                                </button>
                                <input type="text" id="discount-input" class="form-control form-control-sm font-monospace"
                                    value="{{ $offlineSale->discount_type === 'percentage' ? (float)$offlineSale->discount_value : number_format($offlineSale->discount_value ?: 0, 0, ',', '.') }}">
                                <input type="hidden" name="discount_type" id="global-discount-type" value="{{ $offlineSale->discount_type ?: 'fixed' }}">
                                <input type="hidden" name="discount_value" id="global-discount-value" value="{{ (float)$offlineSale->discount_value }}">
                                <input type="hidden" name="discount_amount" id="global-discount-amount" value="{{ (float)$offlineSale->discount_amount }}">
                            </div>
                            <span id="reseller-info-badge" class="badge bg-success text-white mt-1 w-100 py-1" style="{{ $offlineSale->is_dropship ? '' : 'display: none;' }}">
                                <i class="fas fa-percent me-1"></i> Mode Dropship Aktif — Menggunakan Harga Dropship
                            </span>
                        </div>

                        <hr class="my-3">

                        {{-- Pilihan Jenis Pembayaran: Tunai / Kredit --}}
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary mb-1">
                                <i class="fas fa-wallet me-1"></i>Metode / Jenis Pembayaran <span class="text-danger">*</span>
                            </label>
                            <div class="btn-group w-100 mb-2" role="group" id="payment-type-group">
                                <input type="radio" class="btn-check" name="payment_type" id="pay_type_tunai" value="tunai" autocomplete="off"
                                    {{ $offlineSale->payment_method !== 'piutang' ? 'checked' : '' }}>
                                <label class="btn btn-outline-success btn-sm fw-bold py-2" for="pay_type_tunai">
                                    <i class="fas fa-money-bill-wave me-1"></i> Tunai (Lunas)
                                </label>

                                <input type="radio" class="btn-check" name="payment_type" id="pay_type_kredit" value="kredit" autocomplete="off"
                                    {{ $offlineSale->payment_method === 'piutang' ? 'checked' : '' }}>
                                <label class="btn btn-outline-danger btn-sm fw-bold py-2" for="pay_type_kredit">
                                    <i class="fas fa-file-invoice-dollar me-1"></i> Kredit (Tempo)
                                </label>
                            </div>
                            <input type="hidden" name="payment_method" id="payment-method-input" value="{{ $offlineSale->payment_method ?: 'piutang' }}">
                        </div>

                        {{-- Total Tagihan --}}
                        <div class="card bg-light border p-3 mb-3 text-center">
                            <div class="text-muted small fw-semibold mb-1">Total Tagihan Baru</div>
                            <div class="fs-4 fw-bold text-dark font-monospace" id="display-order-grand-total">
                                Rp {{ number_format($offlineSale->grand_total, 0, ',', '.') }}
                            </div>
                            <div class="text-muted small mt-1" id="payment-hint-text">
                                @if ($offlineSale->paid_amount > 0)
                                    <span class="text-success fw-semibold"><i class="fas fa-check-circle me-1"></i> Sudah dibayar: Rp {{ number_format($offlineSale->paid_amount, 0, ',', '.') }}</span>
                                @else
                                    <i class="fas fa-info-circle text-primary me-1"></i> Pembayaran dapat disesuaikan / dicicil.
                                @endif
                            </div>
                        </div>

                        @if ($offlineSale->payments()->count() == 0)
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary mb-1">Nominal Dibayar (DP / Pelunasan Langsung)</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" id="paid-display-input" class="form-control form-control-sm font-monospace"
                                        value="{{ number_format($offlineSale->paid_amount, 0, ',', '.') }}">
                                </div>
                                <input type="hidden" name="paid_amount" id="paid-input" value="{{ (float)$offlineSale->paid_amount }}">
                            </div>
                        @else
                            <input type="hidden" name="paid_amount" id="paid-input" value="{{ (float)$offlineSale->paid_amount }}">
                            <div class="alert alert-info py-2 px-3 small mb-3">
                                <i class="fas fa-history me-1"></i> Terdapat <strong>{{ $offlineSale->payments()->count() }} riwayat pembayaran</strong> (Total: Rp {{ number_format($offlineSale->paid_amount, 0, ',', '.') }}). Kelola cicilan lanjutan via modal pembayaran.
                            </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary mb-1">Catatan Pesanan</label>
                            <textarea name="notes" class="form-control form-control-sm" rows="3" placeholder="Tulis catatan transaksi jika ada...">{{ $offlineSale->notes }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-warning text-dark w-100 py-2 fw-bold" id="btn-submit">
                            <i class="fas fa-save me-1"></i> Simpan Perubahan Transaksi
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- MODAL TAMBAH PELANGGAN BARU (MASTER DATA) -->
<div class="modal fade" id="modalCreateCustomer" tabindex="-1" aria-labelledby="modalCreateCustomerLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header py-2 bg-light">
                <h6 class="modal-title fw-bold text-dark" id="modalCreateCustomerLabel">
                    <i class="fas fa-user-plus me-2 text-primary"></i>Tambah Master Pelanggan Baru
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="form-quick-customer">
                @csrf
                <div class="modal-body p-3">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary mb-1">Nama Pelanggan <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm" placeholder="Nama pelanggan..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary mb-1">No. Handphone / WhatsApp</label>
                        <input type="text" name="phone" class="form-control form-control-sm" placeholder="08123456789">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary mb-1">Kategori Pelanggan <span class="text-danger">*</span></label>
                        <select name="category" class="form-select form-select-sm" required>
                            <option value="umum">Pelanggan Umum</option>
                            <option value="biasa">Pelanggan Biasa</option>
                            <option value="dropship">Pelanggan Dropship</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary mb-1">Tag Tambahan</label>
                        <input type="text" name="tags" class="form-control form-control-sm" placeholder="Contoh: VIP, Member">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary mb-1">Alamat Lengkap</label>
                        <textarea name="address" class="form-control form-control-sm" rows="2" placeholder="Alamat lengkap..."></textarea>
                    </div>
                </div>
                <div class="modal-footer py-2 bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="btn-save-customer">Simpan &amp; Pilih Pelanggan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        let grandTotal = {{ (float) $offlineSale->grand_total }};
        let isDropshipCustomer = {{ $offlineSale->is_dropship ? 'true' : 'false' }};

        // Initialize cart items from existing database records
        let cartItems = {};
        @foreach ($offlineSale->items as $item)
            @php
                $prod = $item->masterProduct;
                $normalPrice = $prod ? (float) $prod->price : (float) $item->unit_price;
                $dropshipPrice = ($prod && $prod->reseller_price && $prod->reseller_price > 0) ? (float) $prod->reseller_price : $normalPrice;
                $effectivePrice = (float) $item->unit_price;
                $stock = $prod ? ((int) $prod->stock + (int) $item->quantity) : 9999;
            @endphp
            cartItems["{{ $item->master_product_id }}"] = {
                id: {{ $item->master_product_id }},
                name: @json($item->product_name),
                sku: @json($item->sku ?? ($prod->sku ?? '')),
                normal_price: {{ $normalPrice }},
                dropship_price: {{ $dropshipPrice }},
                price: {{ $effectivePrice }},
                qty: {{ (int) $item->quantity }},
                max_stock: {{ $stock }},
                is_po: {{ ($prod && $prod->is_preorder) || $offlineSale->is_po ? 1 : 0 }},
                discount_type: @json($item->discount_type ?: 'fixed'),
                discount_value: {{ (float) ($item->discount_value ?: 0) }},
                subtotal: {{ (float) $item->subtotal }},
                is_promo: false
            };
        @endforeach

        function setDropshipMode(active) {
            isDropshipCustomer = active;
            $('#is-dropship-toggle').val(active ? '1' : '0');

            if (active) {
                $('.price-normal-display').hide();
                $('.price-dropship-display').show();
            } else {
                $('.price-dropship-display').hide();
                $('.price-normal-display').show();
            }

            Object.keys(cartItems).forEach(id => {
                cartItems[id].price = active ? cartItems[id].dropship_price : cartItems[id].normal_price;
            });

            renderCart();
        }

        function triggerCustomerSelectChange() {
            const selectedOption = $('#customer-select').find('option:selected');
            const customerId = $('#customer-select').val();

            if (customerId) {
                const name = selectedOption.data('name');
                const phone = selectedOption.data('phone');
                const address = selectedOption.data('address');
                const category = String(selectedOption.data('category') || '');
                const tags = String(selectedOption.data('tags') || '');

                $('#buyer-name-input').val(name);
                $('#buyer-phone-input').val(phone || '');
                $('#buyer-address-input').val(address || '');

                if (category === 'dropship' || tags.toLowerCase().includes('reseller') || tags.toLowerCase().includes('dropship')) {
                    setDropshipMode(true);
                    $('#dropshipper-name-input').val(name);
                    $('#dropshipper-phone-input').val(phone || '');
                    $('#dropship-detail-section').slideDown(150);
                    $('#reseller-info-badge').html('<i class="fas fa-percent me-1"></i> Mode Dropship Aktif — Menggunakan Harga Dropship').show();
                } else {
                    setDropshipMode(false);
                    $('#dropshipper-name-input').val('');
                    $('#dropshipper-phone-input').val('');
                    $('#dropship-detail-section').slideUp(150);
                    $('#reseller-info-badge').hide();
                }
            }
        }

        $('#customer-select').on('change', triggerCustomerSelectChange);

        // Pre-Order switch
        $('#is-po-switch').on('change', function() {
            if ($(this).is(':checked')) {
                $('#po-deadline-container').slideDown(150);
            } else {
                $('#po-deadline-container').slideUp(150);
            }
            renderCart();
        });

        // Search product
        $('#product-search').on('input', function() {
            const query = $(this).val().toLowerCase().trim();
            $('.product-row').each(function() {
                const name = ($(this).data('name') || '').toString().toLowerCase();
                const sku = ($(this).data('sku') || '').toString().toLowerCase();
                if (name.includes(query) || sku.includes(query)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });

        // Click product row
        $(document).on('click', '.product-row', function() {
            const id = $(this).data('id');
            const name = $(this).data('name');
            const sku = $(this).data('sku');
            const normalPrice = parseFloat($(this).data('price'));
            const resellerPrice = parseFloat($(this).data('reseller-price'));
            const stock = parseInt($(this).data('stock'));
            const isPoMode = $('#is-po-switch').is(':checked');
            const isPreorder = parseInt($(this).data('is-po')) === 1;

            const activePrice = isDropshipCustomer ? resellerPrice : normalPrice;

            if (cartItems[id]) {
                cartItems[id].qty++;
            } else {
                cartItems[id] = {
                    id,
                    name,
                    sku,
                    price: activePrice,
                    normal_price: normalPrice,
                    dropship_price: resellerPrice,
                    stock: stock,
                    is_po: isPreorder,
                    qty: 1,
                    discount_type: 'fixed',
                    discount_value: 0,
                    is_promo: false
                };
            }
            renderCart();
        });

        // Checkbox Promo
        $(document).on('change', '.promo-checkbox', function() {
            const id = $(this).data('id');
            if (cartItems[id]) {
                cartItems[id].is_promo = $(this).is(':checked');
                renderCart();
            }
        });

        // Plus, Minus, Remove, Qty Input
        $(document).on('click', '.btn-minus', function(e) {
            e.stopPropagation();
            const id = $(this).data('id');
            changeQty(id, -1);
        });

        $(document).on('click', '.btn-plus', function(e) {
            e.stopPropagation();
            const id = $(this).data('id');
            changeQty(id, 1);
        });

        $(document).on('change', '.qty-input', function() {
            const id = $(this).data('id');
            setQty(id, $(this).val());
        });

        $(document).on('click', '.btn-remove', function(e) {
            e.stopPropagation();
            const id = $(this).data('id');
            delete cartItems[id];
            renderCart();
        });

        function changeQty(id, delta) {
            if (!cartItems[id]) return;
            let newQty = cartItems[id].qty + delta;
            if (newQty <= 0) {
                delete cartItems[id];
            } else {
                cartItems[id].qty = newQty;
            }
            renderCart();
        }

        function setQty(id, val) {
            if (!cartItems[id]) return;
            let qty = parseInt(val) || 1;
            if (qty <= 0) qty = 1;
            cartItems[id].qty = qty;
            renderCart();
        }

        // Toggle item discount type
        $(document).on('click', '.btn-item-disc-toggle', function(e) {
            e.stopPropagation();
            const id = $(this).data('id');
            if (cartItems[id]) {
                cartItems[id].discount_type = cartItems[id].discount_type === 'percentage' ? 'fixed' : 'percentage';
                renderCart();
            }
        });

        // Input item discount
        $(document).on('input', '.item-disc-input', function(e) {
            const id = $(this).data('id');
            if (!cartItems[id]) return;

            let valStr = $(this).val();
            if (cartItems[id].discount_type === 'percentage') {
                let num = parseFloat(valStr) || 0;
                if (num > 100) num = 100;
                if (num < 0) num = 0;
                cartItems[id].discount_value = num;
            } else {
                let num = unformatNumber(valStr);
                cartItems[id].discount_value = num;
                $(this).val(num > 0 ? num.toLocaleString('id-ID') : '0');
            }
            recalculate();
        });

        // Toggle global discount type
        $('#btn-global-disc-toggle').on('click', function() {
            const curType = $('#global-discount-type').val();
            const newType = curType === 'percentage' ? 'fixed' : 'percentage';
            $('#global-discount-type').val(newType);
            $(this).text(newType === 'percentage' ? '%' : 'Rp');
            if (newType === 'percentage') {
                $(this).removeClass('btn-outline-primary').addClass('btn-primary');
            } else {
                $(this).removeClass('btn-primary').addClass('btn-outline-primary');
            }
            $('#discount-input').val('0');
            recalculate();
        });

        // Global discount input
        $('#discount-input').on('input', function() {
            const discType = $('#global-discount-type').val();
            if (discType === 'percentage') {
                let num = parseFloat($(this).val()) || 0;
                if (num > 100) num = 100;
                if (num < 0) num = 0;
                $(this).val(num);
                $('#global-discount-value').val(num);
            } else {
                let num = unformatNumber($(this).val());
                $(this).val(num > 0 ? num.toLocaleString('id-ID') : '0');
                $('#global-discount-value').val(num);
            }
            recalculate();
        });

        // Payment type switch (Tunai / Kredit)
        $('input[name="payment_type"]').on('change', function() {
            if ($(this).val() === 'tunai') {
                $('#payment-method-input').val('tunai');
                $('#payment-hint-text').html('<span class="text-success fw-bold"><i class="fas fa-check-circle me-1"></i> Pembayaran Lunas saat transaksi.</span>');
            } else {
                $('#payment-method-input').val('piutang');
                $('#payment-hint-text').html('<i class="fas fa-info-circle text-primary me-1"></i> Pembayaran tempo / dapat dicicil.');
            }
        });

        // Paid display input
        $('#paid-display-input').on('input', function() {
            let num = unformatNumber($(this).val());
            $(this).val(num > 0 ? num.toLocaleString('id-ID') : '0');
            $('#paid-input').val(num);
        });

        function formatRupiah(num) {
            return 'Rp ' + Math.round(num).toLocaleString('id-ID');
        }

        function unformatNumber(str) {
            if (!str) return 0;
            let clean = str.toString().replace(/[^0-9]/g, '');
            return parseInt(clean) || 0;
        }

        function renderCart() {
            const ids = Object.keys(cartItems);
            $('#cart-count-badge').text(ids.length + ' item');

            if (ids.length === 0) {
                $('#cart-empty').show();
                $('#cart-table').hide();
                $('#btn-submit').prop('disabled', true);
            } else {
                $('#cart-empty').hide();
                $('#cart-table').show();
                $('#btn-submit').prop('disabled', false);
            }

            let html = '';
            ids.forEach((id, index) => {
                const item = cartItems[id];
                const unitPrice = item.is_promo ? 0 : item.price;
                const discType = item.discount_type || 'fixed';
                const discVal = item.discount_value || 0;

                let discPerUnit = 0;
                if (!item.is_promo) {
                    if (discType === 'percentage') {
                        discPerUnit = (unitPrice * Math.min(100, Math.max(0, discVal))) / 100;
                    } else {
                        discPerUnit = Math.min(unitPrice, Math.max(0, discVal));
                    }
                }

                const effectiveUnit = Math.max(0, unitPrice - discPerUnit);
                const subtotal = item.qty * effectiveUnit;
                item.subtotal = subtotal;

                const discDisplayVal = discType === 'percentage' ? discVal : (discVal > 0 ? discVal.toLocaleString('id-ID') : '0');

                html += `
                <tr>
                    <td class="ps-3">
                        <div class="fw-semibold text-dark">${item.name}</div>
                        <div class="text-muted small font-monospace">${item.sku || '-'}</div>
                        <input type="hidden" name="items[${index}][master_product_id]" value="${item.id}">
                    </td>
                    <td class="text-center">
                        <input class="form-check-input promo-checkbox" type="checkbox" data-id="${item.id}" ${item.is_promo ? 'checked' : ''} title="Set sebagai barang promo (Rp 0)">
                    </td>
                    <td class="text-center">
                        <div class="input-group input-group-sm justify-content-center" style="max-width: 95px; margin: 0 auto;">
                            <button class="btn btn-outline-secondary btn-minus" type="button" data-id="${item.id}">-</button>
                            <input type="number" name="items[${index}][quantity]" class="form-control text-center px-1 qty-input font-monospace" value="${item.qty}" min="1" data-id="${item.id}">
                            <button class="btn btn-outline-secondary btn-plus" type="button" data-id="${item.id}">+</button>
                        </div>
                    </td>
                    <td class="text-end font-monospace">
                        <input type="hidden" name="items[${index}][unit_price]" value="${unitPrice}">
                        ${item.is_promo ? '<span class="badge bg-danger">PROMO Rp 0</span>' : formatRupiah(unitPrice)}
                    </td>
                    <td class="text-center">
                        <div class="input-group input-group-sm">
                            <button class="btn ${discType === 'percentage' ? 'btn-primary' : 'btn-outline-primary'} btn-item-disc-toggle fw-bold" type="button" data-id="${item.id}" style="width: 38px;">
                                ${discType === 'percentage' ? '%' : 'Rp'}
                            </button>
                            <input type="text" class="form-control text-end item-disc-input font-monospace px-1" value="${discDisplayVal}" data-id="${item.id}">
                            <input type="hidden" name="items[${index}][discount_type]" value="${discType}">
                            <input type="hidden" name="items[${index}][discount_value]" value="${discVal}">
                        </div>
                    </td>
                    <td class="text-end fw-bold text-success font-monospace">
                        ${formatRupiah(subtotal)}
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 btn-remove" data-id="${item.id}" title="Hapus Item">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                </tr>`;
            });

            $('#cart-body').html(html);
            recalculate();
        }

        function recalculate() {
            let totalSubtotal = 0;
            Object.keys(cartItems).forEach(id => {
                const item = cartItems[id];
                const unitPrice = item.is_promo ? 0 : item.price;
                const discType = item.discount_type || 'fixed';
                const discVal = item.discount_value || 0;

                let discPerUnit = 0;
                if (!item.is_promo) {
                    if (discType === 'percentage') {
                        discPerUnit = (unitPrice * Math.min(100, Math.max(0, discVal))) / 100;
                    } else {
                        discPerUnit = Math.min(unitPrice, Math.max(0, discVal));
                    }
                }

                const effectiveUnit = Math.max(0, unitPrice - discPerUnit);
                totalSubtotal += (item.qty * effectiveUnit);
            });

            $('#display-subtotal').text(formatRupiah(totalSubtotal));

            // Global discount
            const gType = $('#global-discount-type').val();
            const gVal = parseFloat($('#global-discount-value').val()) || 0;
            let globalDiscAmt = 0;

            if (gType === 'percentage') {
                globalDiscAmt = (totalSubtotal * Math.min(100, Math.max(0, gVal))) / 100;
            } else {
                globalDiscAmt = Math.min(totalSubtotal, Math.max(0, gVal));
            }

            $('#global-discount-amount').val(globalDiscAmt);

            grandTotal = Math.max(0, totalSubtotal - globalDiscAmt);
            $('#display-order-grand-total').text(formatRupiah(grandTotal));
            $('#display-grand-total').text(formatRupiah(grandTotal));
        }

        // Quick customer creation modal submit
        $('#form-quick-customer').on('submit', function(e) {
            e.preventDefault();
            const submitBtn = $('#btn-save-customer');
            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...');

            $.ajax({
                url: "{{ route('customers.store') }}",
                method: "POST",
                data: $(this).serialize(),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                success: function(res) {
                    submitBtn.prop('disabled', false).text('Simpan & Pilih Pelanggan');
                    if (res.success && res.customer) {
                        const c = res.customer;
                        const labelText = `[${c.category_label || 'Pelanggan Umum'}] ${c.name} ${c.phone ? '(' + c.phone + ')' : ''}`;
                        const newOption = new Option(labelText, c.id, true, true);
                        $(newOption).data('name', c.name);
                        $(newOption).data('phone', c.phone);
                        $(newOption).data('address', c.address);
                        $(newOption).data('category', c.category);
                        $(newOption).data('category-label', c.category_label);
                        $(newOption).data('tags', c.tags);
                        $(newOption).data('balance', c.balance || 0);

                        $('#customer-select').append(newOption).trigger('change');
                        $('#modalCreateCustomer').modal('hide');
                        $('#form-quick-customer')[0].reset();

                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: 'Pelanggan berhasil ditambahkan & dipilih.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    }
                },
                error: function(xhr) {
                    submitBtn.prop('disabled', false).text('Simpan & Pilih Pelanggan');
                    let errMsg = 'Terjadi kesalahan saat menyimpan pelanggan.';
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        errMsg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Menyimpan!',
                        html: errMsg,
                        confirmButtonColor: '#dc3545'
                    });
                }
            });
        });

        // Initialize display on load
        renderCart();
    });
</script>
@endpush
