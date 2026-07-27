@extends('layouts.app')
@section('title', 'Buat SPK Baru')
@section('page-title', 'Marketing & Pengiriman')

@push('styles')
<style>
    /* ── SPK Create Form Styles ── */
    .spk-create-wrap {
        font-family: 'Inter', system-ui, sans-serif;
        color: #1a1a2e;
    }
    .spk-page-header {
        background: #fff;
        border-bottom: 1px solid #e5e7eb;
        padding: 14px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        border-radius: 12px;
        box-shadow: 0 1px 6px rgba(0,0,0,.07);
    }
    .spk-page-header h4 {
        font-size: 1.1rem;
        font-weight: 800;
        margin: 0;
        letter-spacing: -.3px;
    }
    .spk-page-header p { font-size: 12px; color: #888; margin: 2px 0 0; }

    /* Info Banner Row */
    .spk-info-banner {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 20px 24px;
        margin-bottom: 14px;
        box-shadow: 0 1px 4px rgba(0,0,0,.05);
    }
    .draft-badge {
        display: inline-block;
        background: #fef9c3;
        color: #92400e;
        font-size: 11px;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: 20px;
        border: 1px solid #fde68a;
        letter-spacing: .5px;
    }
    .draft-badge.is-filled {
        background: #d1fae5;
        color: #065f46;
        border-color: #6ee7b7;
    }

    /* DESAIN Drop Area */
    .desain-drop-area {
        border: 2.5px dashed #d1d5db;
        border-radius: 14px;
        min-height: 140px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all .2s;
        position: relative;
        overflow: hidden;
        background: #fafafa;
    }
    .desain-drop-area:hover {
        border-color: #3b82f6;
        background: #eff6ff;
    }
    .desain-drop-area.has-image { border-style: solid; border-color: #3b82f6; }
    .desain-drop-area input[type=file] {
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
        z-index: 2;
    }
    .desain-drop-area .desain-label {
        font-size: 2.2rem;
        font-weight: 900;
        letter-spacing: 6px;
        color: #d1d5db;
        user-select: none;
        line-height: 1;
    }
    .desain-drop-area .desain-hint {
        font-size: 11px;
        color: #9ca3af;
        margin-top: 6px;
    }
    .desain-drop-area #desain-preview-img {
        max-height: 130px;
        border-radius: 8px;
        object-fit: contain;
    }

    /* Type Tabs */
    .spk-type-tabs .btn-check:checked + .btn-outline-primary,
    .spk-type-tabs .btn-check:checked + .btn-outline-secondary {
        background: #2563eb;
        color: #fff;
        border-color: #2563eb;
    }
    .spk-type-tabs .btn {
        font-size: 12px;
        font-weight: 700;
        padding: 7px 18px;
        letter-spacing: .5px;
    }

    /* Priority Toggle */
    .spk-priority-toggle .btn-check:checked + .btn-urgent {
        background: #ef4444;
        color: #fff;
        border-color: #ef4444;
    }
    .spk-priority-toggle .btn-check:checked + .btn-normal {
        background: #475569;
        color: #fff;
        border-color: #475569;
    }
    .spk-priority-toggle .btn {
        font-size: 12px;
        font-weight: 700;
        padding: 7px 18px;
        letter-spacing: .5px;
    }
    .btn-urgent { border-color: #ef4444; color: #ef4444; }
    .btn-normal { border-color: #475569; color: #475569; }

    /* Customer Info Card */
    .customer-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 20px 24px;
        margin-bottom: 14px;
        box-shadow: 0 1px 4px rgba(0,0,0,.05);
    }
    .customer-card .section-label {
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .8px;
        text-transform: uppercase;
        color: #6b7280;
        margin-bottom: 4px;
    }

    /* Tahapan Dropdown */
    .tahap-select {
        background: #f1f5f9;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        padding: 10px 14px;
        color: #1e293b;
        cursor: pointer;
    }

    /* Rincian Produk Section Card */
    .rincian-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        margin-bottom: 16px;
        box-shadow: 0 1px 5px rgba(0,0,0,.05);
        overflow: hidden;
    }
    .rincian-card .rincian-header {
        background: #f8fafc;
        border-bottom: 1px solid #e5e7eb;
        padding: 12px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 800;
        font-size: 13px;
        letter-spacing: .3px;
        color: #1e293b;
    }
    .rincian-card .rincian-body { padding: 20px; }

    /* Upload Areas */
    .upload-zone {
        border: 2px dashed #d1d5db;
        border-radius: 10px;
        min-height: 90px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all .2s;
        position: relative;
        background: #fafafa;
        text-align: center;
        padding: 10px;
    }
    .upload-zone:hover { border-color: #3b82f6; background: #eff6ff; }
    .upload-zone.has-file { border-style: solid; border-color: #10b981; background: #ecfdf5; }
    .upload-zone input[type=file] {
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
    }
    .upload-zone .uz-icon { font-size: 20px; opacity: .4; }
    .upload-zone .uz-label { font-size: 10px; font-weight: 700; color: #6b7280; margin-top: 2px; }

    /* Tables in Rincian Card */
    .product-table-custom, .bahan-modal-table {
        border-collapse: separate;
        border-spacing: 0;
        font-size: 12px;
    }
    .product-table-custom thead tr th, .bahan-modal-table thead tr th {
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        font-size: 11px;
        font-weight: 700;
        text-align: center;
        letter-spacing: .4px;
        padding: 8px 6px;
    }
    .product-table-custom tbody td, .bahan-modal-table tbody td {
        border: 1px solid #e2e8f0;
        padding: 6px 8px;
        vertical-align: middle;
    }
    .product-table-custom .form-control, .bahan-modal-table .form-control {
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 12px;
        padding: 4px 8px;
        height: 32px;
    }

    /* Bottom bar */
    .spk-submit-bar {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 16px 24px;
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 10px;
        box-shadow: 0 1px 4px rgba(0,0,0,.05);
    }

    /* Colon separator style */
    .colon-label {
        display: grid;
        grid-template-columns: auto 12px 1fr;
        align-items: center;
        gap: 0;
    }
    .colon-label label { font-size: 10px; font-weight: 700; letter-spacing: .7px; text-transform: uppercase; color: #6b7280; white-space: nowrap; }
    .colon-label .colon { font-weight: 700; color: #9ca3af; text-align: center; }

    .btn-bahan-trigger, .btn-tahap-trigger {
        font-size: 11px;
        font-weight: 700;
        padding: 4px 10px;
        border-radius: 20px;
        white-space: nowrap;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-2 px-3 spk-create-wrap">

    {{-- Datalist Autocomplete SKU Master (Limit 10 untuk cegah lag) --}}
    <datalist id="master_skus_datalist">
        @foreach($products->take(10) as $p)
            <option value="{{ $p->sku }}">{{ $p->name }} @if($p->ukuran)({{ $p->ukuran }})@endif</option>
            @if($p->sku_induk && $p->sku_induk !== $p->sku)
                <option value="{{ $p->sku_induk }}">{{ $p->name }} (Induk)</option>
            @endif
        @endforeach
    </datalist>

    {{-- Datalist Autocomplete Nama Produk Master (Limit 10 untuk cegah lag) --}}
    <datalist id="master_product_names_datalist">
        @foreach($products->take(10) as $p)
            <option value="{{ $p->name }}">{{ $p->sku ? $p->sku . ' — ' : '' }}@if($p->ukuran)(Ukuran: {{ $p->ukuran }})@endif</option>
        @endforeach
    </datalist>

    {{-- Datalist Autocomplete Inventory Items (Limit 10 untuk cegah lag) --}}
    <datalist id="inventory_items_datalist">
        @foreach($inventoryItems->take(10) as $invItemName)
            <option value="{{ $invItemName }}"></option>
        @endforeach
    </datalist>

    {{-- Datalist Autocomplete Vendors per Role --}}
    <datalist id="pemotong_datalist">
        @foreach($pemotongList as $vName)
            <option value="{{ $vName }}"></option>
        @endforeach
    </datalist>

    <datalist id="penjahit_datalist">
        @foreach($penjahitList as $vName)
            <option value="{{ $vName }}"></option>
        @endforeach
    </datalist>

    <datalist id="vendor_kancing_datalist">
        @foreach($vendorKancingList as $vName)
            <option value="{{ $vName }}"></option>
        @endforeach
    </datalist>

    <datalist id="petugas_qc_datalist">
        @foreach($petugasQcList as $vName)
            <option value="{{ $vName }}"></option>
        @endforeach
    </datalist>

    <datalist id="kategori_datalist">
        <option value="Baju Olah Raga"></option>
        <option value="Seragam Sekolah"></option>
        <option value="Jaket & Outer"></option>
        <option value="Kaos / T-Shirt"></option>
        <option value="Kemeja & PDH"></option>
        <option value="Almamater & Jas"></option>
        <option value="Gamis & Busana Muslim"></option>
        <option value="Jersey Printing"></option>
        <option value="Topi & Aksesoris"></option>
    </datalist>

    {{-- ── PAGE HEADER ── --}}
    <div class="spk-page-header">
        <div>
            <h4>📋 Marketing &amp; Pengiriman</h4>
            <p>Buat SPK baru · Pantau pesanan, bagikan link pelacak ke pelanggan.</p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <a href="{{ route('spks.index') }}" class="btn btn-sm btn-outline-secondary fw-semibold">
                ← Kembali
            </a>
            <button type="submit" form="spkForm" class="btn btn-sm btn-primary fw-bold px-3">
                ✚ Tambah Order
            </button>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible rounded-3 mb-3">
            <strong>⚠️ Ada kesalahan:</strong>
            <ul class="mb-0 mt-1 small">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('spks.store') }}" method="POST" enctype="multipart/form-data" id="spkForm">
        @csrf
        @if(isset($order))
            <input type="hidden" name="order_id" value="{{ $order->id }}">
        @endif

        {{-- ══════════════════════════════════════════════════════════════════
             SECTION 1: NO PRODUKSI | DESAIN | DATES
        ══════════════════════════════════════════════════════════════════ --}}
        <div class="spk-info-banner">
            <div class="row g-3 align-items-center">

                {{-- Left: No Produksi + No Pesanan --}}
                <div class="col-lg-3 col-md-4">
                    {{-- No Produksi --}}
                    <div class="mb-3">
                        <div class="colon-label mb-1">
                            <label>NO PRODUKSI</label>
                            <span class="colon">:</span>
                            <span></span>
                        </div>
                        <div>
                            <input type="text" name="no_produksi" id="no_produksi_input"
                                class="form-control form-control-sm font-monospace fw-bold"
                                list="existing_no_produksi_list"
                                autocomplete="off"
                                placeholder="Kosongkan = DRAFT, atau pilih / ketik kode"
                                value="{{ old('no_produksi', request('no_produksi', '')) }}"
                                style="font-size:13px;">
                            <datalist id="existing_no_produksi_list">
                                @foreach($existingNoProduksi as $existCode)
                                    <option value="{{ $existCode }}">{{ $existCode }}</option>
                                @endforeach
                            </datalist>
                            <div class="mt-1" id="produksi-status-display">
                                <span class="draft-badge" id="produksi-badge">🕐 KOSONG = DRAFT</span>
                            </div>
                        </div>
                    </div>

                    {{-- No Pesanan --}}
                    <div>
                        <div class="colon-label mb-1">
                            <label>NO PESANAN</label>
                            <span class="colon">:</span>
                            <span></span>
                        </div>
                        <input type="text" name="no_pesanan" id="no_pesanan_input"
                            class="form-control form-control-sm"
                            placeholder="Nomor / referensi pesanan klien"
                            value="{{ old('no_pesanan', $order->invoice_number ?? $order->order_marketplace_id ?? '') }}"
                            style="font-size:13px;">
                    </div>
                </div>

                {{-- Center: DESAIN area --}}
                <div class="col-lg-6 col-md-4">
                    <div class="desain-drop-area" id="desain-drop-area">
                        <input type="file" name="image" id="input-spk-image" accept="image/*">
                        <div id="desain-placeholder-content">
                            <div class="desain-label">DESAIN</div>
                            <div class="desain-hint">Klik atau seret foto desain/mockup ke sini</div>
                            <div class="mt-2">
                                <small class="badge bg-light text-secondary border" style="font-size:10px;">JPEG / PNG / JPG · maks 4MB</small>
                            </div>
                        </div>
                        <img id="desain-preview-img" src="#" alt="Preview Desain" class="d-none" style="max-height:130px; border-radius:8px; object-fit:contain;">
                    </div>
                </div>

                {{-- Right: Dates --}}
                <div class="col-lg-3 col-md-4">
                    {{-- Order Date --}}
                    <div class="mb-3">
                        <div class="colon-label mb-1">
                            <label>ORDER DATE</label>
                            <span class="colon">:</span>
                            <span></span>
                        </div>
                        <input type="date" name="tanggal" class="form-control form-control-sm"
                            required value="{{ old('tanggal', date('Y-m-d')) }}"
                            style="font-size:13px;">
                    </div>

                    {{-- Deadline --}}
                    <div>
                        <div class="colon-label mb-1">
                            <label class="text-danger fw-bold">DEADLINE</label>
                            <span class="colon text-danger">:</span>
                            <span></span>
                        </div>
                        <input type="date" name="deadline" class="form-control form-control-sm border-danger"
                            value="{{ old('deadline', date('Y-m-d', strtotime('+14 days'))) }}"
                            style="font-size:13px; color:#dc2626;">
                    </div>
                </div>
            </div>

            {{-- ── TYPE TABS + PRIORITY TOGGLE ── --}}
            <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 pt-3" style="border-top: 1px solid #e5e7eb;">
                {{-- Tipe SPK Tabs --}}
                <div class="spk-type-tabs btn-group" role="group">
                    <input type="radio" name="tipe_spk" class="btn-check" id="tipe_pesanan" value="pesanan_pelanggan"
                        {{ old('tipe_spk', 'pesanan_pelanggan') === 'pesanan_pelanggan' ? 'checked' : '' }}>
                    <label class="btn btn-outline-primary" for="tipe_pesanan">
                        🛒 PESANAN / CUSTOM
                    </label>

                    <input type="radio" name="tipe_spk" class="btn-check" id="tipe_stok" value="stok_gudang"
                        {{ old('tipe_spk') === 'stok_gudang' ? 'checked' : '' }}>
                    <label class="btn btn-outline-secondary" for="tipe_stok">
                        🏬 PRODUKSI STOK
                    </label>
                </div>

                {{-- Priority Toggle --}}
                <div class="spk-priority-toggle btn-group mt-2 mt-md-0" role="group">
                    <input type="radio" name="is_urgent" class="btn-check" id="priority_normal" value="0"
                        {{ old('is_urgent', '0') == '0' ? 'checked' : '' }}>
                    <label class="btn btn-normal" for="priority_normal">
                        ✓ NORMAL
                    </label>

                    <input type="radio" name="is_urgent" class="btn-check" id="priority_urgent" value="1"
                        {{ old('is_urgent') == '1' ? 'checked' : '' }}>
                    <label class="btn btn-urgent" for="priority_urgent">
                        ⚡ URGENT
                    </label>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════════════
             SECTION 2: DATA PELANGGAN + CATATAN + TAHAPAN
        ══════════════════════════════════════════════════════════════════ --}}
        <div class="customer-card">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="section-label">NAMA KLIEN / PEMESAN</div>
                    <input type="text" name="pemesan" class="form-control form-control-sm"
                        placeholder="Contoh: Ibu Yanti, PT. Maju Jaya..."
                        value="{{ old('pemesan', $order->buyer_name ?? '') }}">
                </div>
                <div class="col-md-6">
                    <div class="section-label">NAMA TOKO / INSTANSI</div>
                    <select name="instansi" class="form-select form-select-sm">
                        <option value="">— Pilih atau ketik toko —</option>
                        @php $selectedStore = old('instansi', isset($order) && $order->store ? $order->store->store_name : ''); @endphp
                        @foreach($stores as $st)
                            @php $sName = $st->store_name . ($st->channel ? ' (' . $st->channel->name . ')' : ''); @endphp
                            <option value="{{ $st->store_name }}" {{ $selectedStore == $st->store_name ? 'selected' : '' }}>{{ $sName }}</option>
                        @endforeach
                        <option value="POS / Penjualan Offline" {{ $selectedStore == 'POS / Penjualan Offline' ? 'selected' : '' }}>POS / Penjualan Offline</option>
                        <option value="Pesanan Direct / Whatsapp" {{ $selectedStore == 'Pesanan Direct / Whatsapp' ? 'selected' : '' }}>Pesanan Direct / Whatsapp</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <div class="section-label">NO WHATSAPP KLIEN</div>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">📱</span>
                        <input type="text" name="no_hp_pemesan" class="form-control"
                            placeholder="0852-xxxx-xxxx"
                            value="{{ old('no_hp_pemesan', $order->buyer_phone ?? '') }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="section-label">NAMA PIC / ADMIN</div>
                    <input type="text" name="nama_pic" class="form-control form-control-sm"
                        placeholder="Nama admin yang menginput"
                        value="{{ old('nama_pic', Auth::user()->name ?? '') }}">
                </div>
            </div>

            {{-- Catatan Tambahan --}}
            <div class="mt-3">
                <div class="section-label">CATATAN TAMBAHAN / KETERANGAN</div>
                <textarea name="tambahan" class="form-control form-control-sm" rows="3"
                    placeholder="Tulis instruksi desain, keterangan khusus, atau pesan untuk tim produksi di sini..."
                    >{{ old('tambahan', isset($order) ? 'Diproduksi untuk Pesanan #' . ($order->invoice_number ?? $order->order_marketplace_id) : '') }}</textarea>
            </div>

            {{-- Tahapan Saat Ini --}}
            <div class="mt-3">
                <div class="section-label mb-1">TAHAPAN SAAT INI</div>
                <div class="tahap-select-wrap">
                    <select name="tahap_saat_ini" id="tahap_saat_ini_select" class="form-select tahap-select">
                        @php
                            $tahapanList = \App\Models\Spk::TAHAPAN;
                            $selectedTahap = old('tahap_saat_ini', 'DRAFT');
                        @endphp
                        @foreach($tahapanList as $key => $info)
                            <option value="{{ $key }}" {{ $selectedTahap === $key ? 'selected' : '' }}>
                                {{ $info['emoji'] }} {{ $info['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <small class="text-muted d-block mt-1" style="font-size:11px;">
                    💡 Status DRAFT otomatis jika No. Produksi kosong (menunggu DP dari pelanggan)
                </small>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════════════
             SECTION 3: DETAIL RINCIAN PRODUK (SPK) - DYNAMIC CONTAINER
        ══════════════════════════════════════════════════════════════════ --}}
        <div id="rincianContainer">
            {{-- Dynamic Rincian Cards render here --}}
        </div>

        {{-- Button to Add More Rincian Block --}}
        <div class="mb-4 text-center">
            <button type="button" class="btn btn-outline-primary btn-md fw-bold px-4 py-2 shadow-sm" id="btnAddRincianBlock"
                    style="border-width:2px; border-style:dashed; border-radius:10px;">
                ✚ TAMBAH DETAIL RINCIAN PRODUK (SPK BARU)
            </button>
            <div class="text-muted mt-2" style="font-size:11px;">
                💡 Klik tombol di atas jika dalam 1 Kode Produksi yang sama terdapat beberapa jenis SPK/produk yang berbeda
            </div>
        </div>

        {{-- ── SUBMIT BAR ── --}}
        <div class="spk-submit-bar">
            <a href="{{ route('spks.index') }}" class="btn btn-sm btn-outline-secondary px-4">Batal</a>
            <button type="submit" form="spkForm" class="btn btn-sm btn-success fw-bold px-4">
                💾 Simpan sebagai DRAFT
            </button>
            <button type="submit" form="spkForm" name="tahap_saat_ini" value="Tahap Desain &amp; Mockup" class="btn btn-sm btn-primary fw-bold px-4">
                🚀 Simpan &amp; Mulai Desain
            </button>
        </div>
    </form>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     POPUP MODAL 1: RINCIAN BAHAN & BARANG KOMPONEN
══════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="modalBahanProduk" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg border-0" style="border-radius:16px; overflow:hidden;">
            <div class="modal-header bg-dark text-white py-3 px-4">
                <div>
                    <h5 class="modal-title fw-bold fs-6 mb-0 d-flex align-items-center gap-2">
                        📦 RINCIAN BAHAN &amp; BARANG KOMPONEN
                    </h5>
                    <small class="text-light opacity-75" style="font-size:11px;" id="modalProductSubtitle">
                        Produk SPK
                    </small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" style="background:#f8fafc;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-secondary fw-bold" style="font-size:11px; text-transform:uppercase; letter-spacing:.5px;">
                        Daftar Bahan / Barang Yang Digunakan:
                    </span>
                    <div id="modalRecipeBadge"></div>
                </div>

                <div class="table-responsive bg-white rounded-3 shadow-sm border mb-3">
                    <table class="table table-sm bahan-modal-table mb-0 align-middle">
                        <thead>
                            <tr class="text-uppercase text-center">
                                <th style="width:40%;">NAMA BAHAN / BARANG</th>
                                <th style="width:20%;">QTY BAHAN</th>
                                <th style="width:20%;">HARGA (Rp)</th>
                                <th style="width:20%;">SUBTOTAL (Rp)</th>
                                <th style="width:36px;"></th>
                            </tr>
                        </thead>
                        <tbody id="modalBahanTableBody">
                            {{-- Dynamic Rows rendered inside JS Modal --}}
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-outline-primary btn-sm fw-bold px-3" id="btnModalAddBahanRow">
                        ✚ Tambah Baris Bahan
                    </button>
                    <span class="fw-bold text-success fs-6" id="modalTotalBahanDisplay">Total: Rp 0</span>
                </div>
            </div>
            <div class="modal-footer bg-white border-top py-2 px-4">
                <button type="button" class="btn btn-sm btn-primary fw-bold px-4" data-bs-dismiss="modal">
                    ✅ Selesai
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     POPUP MODAL 2: TAHAP OPERASIONAL & TIM PRODUKSI (Potong, Jahit, LKPK, QC, Finishing, F.Good)
══════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="modalTahapOperasional" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg border-0" style="border-radius:16px; overflow:hidden;">
            <div class="modal-header bg-primary text-white py-3 px-4">
                <div>
                    <h5 class="modal-title fw-bold fs-6 mb-0 d-flex align-items-center gap-2">
                        ✂️ TAHAP OPERASIONAL, TIM &amp; ONGKOS JASA
                    </h5>
                    <small class="text-white opacity-75" style="font-size:11px;" id="modalTahapProductSubtitle">
                        Produk SPK
                    </small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" style="background:#f8fafc;">
                
                {{-- 1. TAHAP PEMOTONGAN (POTONG) --}}
                <div class="card border-0 shadow-sm rounded-3 mb-3">
                    <div class="card-header bg-danger-subtle text-danger-emphasis fw-bold py-2 px-3 d-flex justify-content-between align-items-center" style="font-size:12px;">
                        <span>✂️ TAHAP PEMOTONGAN</span>
                        <span class="text-muted subtotal-potong-display">Subtotal: Rp 0</span>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label mb-1 fw-semibold text-secondary" style="font-size:11px;">PEMOTONG / TUKANG POTONG</label>
                                <input type="text" id="modal_pemotong" class="form-control form-control-sm modal-op-field" list="pemotong_datalist" placeholder="Pilih / Ketik Nama Pemotong">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label mb-1 fw-semibold text-secondary" style="font-size:11px;">QTY POTONG (PCS)</label>
                                <input type="number" id="modal_qty_potong" class="form-control form-control-sm text-center modal-op-field" min="0" placeholder="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label mb-1 fw-semibold text-secondary" style="font-size:11px;">TARIF ONGKOS / PCS (RP)</label>
                                <input type="number" id="modal_tarif_potong" class="form-control form-control-sm text-end modal-op-field" min="0" placeholder="0">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 2. TAHAP JAHIT --}}
                <div class="card border-0 shadow-sm rounded-3 mb-3">
                    <div class="card-header bg-warning-subtle text-warning-emphasis fw-bold py-2 px-3 d-flex justify-content-between align-items-center" style="font-size:12px;">
                        <span>🧵 TAHAP JAHIT</span>
                        <span class="text-muted subtotal-jahit-display">Subtotal: Rp 0</span>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label mb-1 fw-semibold text-secondary" style="font-size:11px;">PENJAHIT</label>
                                <input type="text" id="modal_penjahit" class="form-control form-control-sm modal-op-field" list="penjahit_datalist" placeholder="Pilih / Ketik Penjahit">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label mb-1 fw-semibold text-secondary" style="font-size:11px;">QTY JAHIT (PCS)</label>
                                <input type="number" id="modal_qty_jahit" class="form-control form-control-sm text-center modal-op-field" min="0" placeholder="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label mb-1 fw-semibold text-secondary" style="font-size:11px;">TARIF ONGKOS / PCS (RP)</label>
                                <input type="number" id="modal_tarif_jahit" class="form-control form-control-sm text-end modal-op-field" min="0" placeholder="0">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 3. TAHAP LKPK (KANCING) --}}
                <div class="card border-0 shadow-sm rounded-3 mb-3">
                    <div class="card-header bg-info-subtle text-info-emphasis fw-bold py-2 px-3 d-flex justify-content-between align-items-center" style="font-size:12px;">
                        <span>🔘 TAHAP LKPK (KANCING)</span>
                        <span class="text-muted subtotal-kancing-display">Subtotal: Rp 0</span>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label mb-1 fw-semibold text-secondary" style="font-size:11px;">VENDOR KANCING</label>
                                <input type="text" id="modal_vendor_kancing" class="form-control form-control-sm modal-op-field" list="vendor_kancing_datalist" placeholder="Pilih / Ketik Vendor Kancing">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label mb-1 fw-semibold text-secondary" style="font-size:11px;">QTY KANCING (PCS)</label>
                                <input type="number" id="modal_qty_kancing" class="form-control form-control-sm text-center modal-op-field" min="0" placeholder="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label mb-1 fw-semibold text-secondary" style="font-size:11px;">TARIF ONGKOS / PCS (RP)</label>
                                <input type="number" id="modal_tarif_kancing" class="form-control form-control-sm text-end modal-op-field" min="0" placeholder="0">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 4. TAHAP QC (QUALITY CONTROL) --}}
                <div class="card border-0 shadow-sm rounded-3 mb-3">
                    <div class="card-header bg-secondary-subtle text-secondary-emphasis fw-bold py-2 px-3 d-flex justify-content-between align-items-center" style="font-size:12px;">
                        <span>🔍 TAHAP QC (QUALITY CONTROL)</span>
                        <span class="text-muted subtotal-qc-display">Subtotal: Rp 0</span>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label mb-1 fw-semibold text-secondary" style="font-size:11px;">PETUGAS QC</label>
                                <input type="text" id="modal_petugas_qc" class="form-control form-control-sm modal-op-field" list="petugas_qc_datalist" placeholder="Pilih / Ketik Petugas QC">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label mb-1 fw-semibold text-success" style="font-size:11px;">LOLOS (PCS)</label>
                                <input type="number" id="modal_qc_lolos" class="form-control form-control-sm text-center border-success modal-op-field" min="0" placeholder="0">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label mb-1 fw-semibold text-danger" style="font-size:11px;">REJECT (PCS)</label>
                                <input type="number" id="modal_qc_reject" class="form-control form-control-sm text-center border-danger modal-op-field" min="0" placeholder="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label mb-1 fw-semibold text-secondary" style="font-size:11px;">TARIF QC / PCS (RP)</label>
                                <input type="number" id="modal_tarif_qc" class="form-control form-control-sm text-end modal-op-field" min="0" placeholder="0">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 5. TAHAP FINISHING & F.GOOD --}}
                <div class="card border-0 shadow-sm rounded-3 mb-0">
                    <div class="card-header bg-success-subtle text-success-emphasis fw-bold py-2 px-3 d-flex justify-content-between align-items-center" style="font-size:12px;">
                        <span>✨ FINISHING &amp; F.GOOD (FINISHED GOOD)</span>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label mb-1 fw-semibold text-secondary" style="font-size:11px;">FINISHING (PCS)</label>
                                <input type="number" id="modal_qty_finishing" class="form-control form-control-sm text-center modal-op-field" min="0" placeholder="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label mb-1 fw-semibold text-success" style="font-size:11px;">F.GOOD / FINISHED GOOD (PCS)</label>
                                <input type="number" id="modal_qty_fgood" class="form-control form-control-sm text-center border-success fw-bold modal-op-field" min="0" placeholder="0">
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer bg-white border-top py-2 px-4 d-flex justify-content-between align-items-center">
                <span class="fw-bold text-primary fs-6" id="modalTotalLaborDisplay">Total Ongkos Jasa: Rp 0</span>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-secondary px-3 me-2" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-sm btn-primary fw-bold px-4" id="btnSaveModalTahap">
                        ✅ Simpan Tahapan
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Data untuk JS --}}
<script>
    const tailorsList = @json($tailors);
    const existingNoProduksiList = @json($existingNoProduksi);
    const orderItemsList = @json(isset($order) ? $order->items : []);
    const recipesMap = @json($recipesMap ?? []);
    const allMasterProductsList = @json($allMasterProductsList ?? []);
    const allInventoryItemsList = @json($inventoryItems);
    const inventoryItemsMap = @json($inventoryItemsMap ?? []);

    // Master Products map keyed by SKU and SKU Induk for instantaneous auto-filling
    const masterProductsMap = {};
    @foreach($products as $p)
        @php
            $prodInfo = [
                'name' => $p->name,
                'sku' => $p->sku,
                'ukuran' => $p->ukuran ?? '',
            ];
        @endphp
        @if(!empty($p->sku))
            masterProductsMap[@json(strtoupper(trim($p->sku)))] = @json($prodInfo);
        @endif
        @if(!empty($p->sku_induk))
            masterProductsMap[@json(strtoupper(trim($p->sku_induk)))] = @json($prodInfo);
        @endif
    @endforeach
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {

    // ── 1. DESAIN Image Preview ──
    const desainInput   = document.getElementById('input-spk-image');
    const desainArea    = document.getElementById('desain-drop-area');
    const desainPreview = document.getElementById('desain-preview-img');
    const desainPlaceholder = document.getElementById('desain-placeholder-content');

    if (desainInput) {
        desainInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = e => {
                    desainPreview.src = e.target.result;
                    desainPreview.classList.remove('d-none');
                    desainPlaceholder.classList.add('d-none');
                    desainArea.classList.add('has-image');
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // ── 2. No Produksi → Badge update ──
    const noProduksiInput = document.getElementById('no_produksi_input');
    const produksiBadge   = document.getElementById('produksi-badge');
    const tahapSelect     = document.getElementById('tahap_saat_ini_select');

    function updateProduksiBadge() {
        if (!noProduksiInput) return;
        const val = noProduksiInput.value.trim();
        if (val === '') {
            produksiBadge.textContent = '🕐 KOSONG = DRAFT';
            produksiBadge.classList.remove('is-filled');
            if (tahapSelect) tahapSelect.value = 'DRAFT';
        } else {
            produksiBadge.textContent = '✅ ' + val;
            produksiBadge.classList.add('is-filled');
            if (tahapSelect && tahapSelect.value === 'DRAFT') {
                tahapSelect.value = 'Tahap Desain & Mockup';
            }
        }
    }
    if (noProduksiInput) {
        noProduksiInput.addEventListener('input', updateProduksiBadge);
        updateProduksiBadge();
    }

    // ── 2b. Tipe SPK Switch (Pesanan / Custom vs Produksi Stok) ──
    function handleTipeSpkChange() {
        const tipeStokRadio = document.getElementById('tipe_stok');
        if (!tipeStokRadio) return;

        const isStok = tipeStokRadio.checked;
        const pemesanInput = document.querySelector('input[name="pemesan"]');
        const instansiSelect = document.querySelector('select[name="instansi"]');
        const noHpInput = document.querySelector('input[name="no_hp_pemesan"]');

        if (isStok) {
            if (pemesanInput) {
                if (!pemesanInput.dataset.prevVal) pemesanInput.dataset.prevVal = pemesanInput.value;
                pemesanInput.value = 'STOK GUDANG';
                pemesanInput.readOnly = true;
                pemesanInput.classList.add('bg-light', 'fw-bold', 'text-primary');
            }
            if (instansiSelect) {
                if (!instansiSelect.dataset.prevVal) instansiSelect.dataset.prevVal = instansiSelect.value;
                instansiSelect.value = 'POS / Penjualan Offline';
                instansiSelect.classList.add('bg-light');
            }
            if (noHpInput) {
                if (!noHpInput.dataset.prevVal) noHpInput.dataset.prevVal = noHpInput.value;
                noHpInput.value = '-';
                noHpInput.readOnly = true;
                noHpInput.classList.add('bg-light');
            }
        } else {
            if (pemesanInput) {
                pemesanInput.readOnly = false;
                pemesanInput.classList.remove('bg-light', 'fw-bold', 'text-primary');
                if (pemesanInput.value === 'STOK GUDANG') {
                    pemesanInput.value = pemesanInput.dataset.prevVal || '';
                }
            }
            if (instansiSelect) {
                instansiSelect.classList.remove('bg-light');
                if (instansiSelect.value === 'POS / Penjualan Offline' && instansiSelect.dataset.prevVal) {
                    instansiSelect.value = instansiSelect.dataset.prevVal;
                }
            }
            if (noHpInput) {
                noHpInput.readOnly = false;
                noHpInput.classList.remove('bg-light');
                if (noHpInput.value === '-') {
                    noHpInput.value = noHpInput.dataset.prevVal || '';
                }
            }
        }
    }

    document.querySelectorAll('input[name="tipe_spk"]').forEach(radio => {
        radio.addEventListener('change', handleTipeSpkChange);
    });
    handleTipeSpkChange();

    // ── 3. DYNAMIC RINCIAN PRODUK (SPK) BLOCKS ──
    let rincianBlockCount = 0;
    let productRowCounters = {};

    function escHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function formatRupiah(val) {
        return 'Rp ' + (parseFloat(val) || 0).toLocaleString('id-ID');
    }

    window.addRincianBlock = function() {
        const rIdx = rincianBlockCount++;
        const blockNum = rIdx + 1;

        const card = document.createElement('div');
        card.className = 'rincian-card';
        card.id = `rincian-card-${rIdx}`;
        card.dataset.rincianIdx = rIdx;

        card.innerHTML = `
            <div class="rincian-header">
                <div>
                    <span>≡</span>
                    <span class="ms-1">DETAIL RINCIAN PRODUK (SPK #${blockNum})</span>
                </div>
                ${rIdx > 0 ? `
                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 btn-remove-rincian" style="font-size:11px;">
                        <i class="fas fa-trash-alt me-1"></i> Hapus Rincian Ini
                    </button>
                ` : ''}
            </div>
            <div class="rincian-body">

                <div class="row g-3">
                    {{-- Left: Upload Areas + G-Drive Link --}}
                    <div class="col-xl-3 col-lg-4">

                        {{-- 1. Referensi Klien --}}
                        <div class="mb-3">
                            <div class="section-label mb-1" style="font-size:10px; font-weight:700; letter-spacing:.7px; text-transform:uppercase; color:#6b7280;">
                                1. REFERENSI KLIEN (MARKETING)
                            </div>
                            <div class="upload-zone ref-zone-${rIdx}">
                                <input type="file" name="rincian[${rIdx}][referensi_klien]" class="input-ref-${rIdx}" accept="image/*">
                                <div class="ref-placeholder-${rIdx}">
                                    <div class="uz-icon">📸</div>
                                    <div class="uz-label">UPLOAD REFERENSI</div>
                                    <div class="uz-hint">Foto contoh dari klien</div>
                                </div>
                                <img class="ref-preview-${rIdx} d-none img-fluid rounded" style="max-height:80px;" alt="Referensi">
                            </div>
                        </div>

                        {{-- 2. Mockup Final --}}
                        <div class="mb-3">
                            <div class="section-label mb-1" style="font-size:10px; font-weight:700; letter-spacing:.7px; text-transform:uppercase; color:#6b7280;">
                                2. MOCKUP FINAL (DESAIN)
                            </div>
                            <div class="upload-zone mockup-zone-${rIdx}" style="opacity:.8;">
                                <input type="file" name="rincian[${rIdx}][mockup_final]" class="input-mockup-${rIdx}" accept="image/*">
                                <div class="mockup-placeholder-${rIdx}">
                                    <div class="uz-icon">🎨</div>
                                    <div class="uz-label" style="color:#9ca3af;">MENUNGGU DESAINER</div>
                                    <div class="uz-hint">Upload setelah desain selesai</div>
                                </div>
                                <img class="mockup-preview-${rIdx} d-none img-fluid rounded" style="max-height:80px;" alt="Mockup">
                            </div>
                        </div>

                        {{-- 3. Link File Mentah --}}
                        <div class="mb-3">
                            <div class="section-label mb-1" style="font-size:10px; font-weight:700; letter-spacing:.7px; text-transform:uppercase; color:#16a34a;">
                                🔗 LINK FILE MENTAH
                            </div>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background:#f0fdf4; border-color:#bbf7d0; color:#16a34a; font-size:14px;">G</span>
                                <input type="url" name="rincian[${rIdx}][link_file_mentah]" class="form-control"
                                    style="font-size:12px;" placeholder="Paste link G-Drive / Dropbox...">
                            </div>
                        </div>

                        {{-- 4. Kategori Produk --}}
                        <div>
                            <div class="section-label mb-1" style="font-size:10px; font-weight:700; letter-spacing:.7px; text-transform:uppercase; color:#4f46e5;">
                                🏷️ KATEGORI PRODUK
                            </div>
                            <input type="text" name="rincian[${rIdx}][kategori]" class="form-control form-control-sm"
                                list="kategori_datalist" style="font-size:12px;" placeholder="Contoh: Baju Olah Raga, Jaket...">
                        </div>
                    </div>

                    {{-- Right: Tabel Rincian Produk --}}
                    <div class="col-xl-9 col-lg-8">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-dark" style="font-size:12px; letter-spacing:.3px;">
                                👕 RINCIAN PRODUK &amp; VARIASI UKURAN:
                            </span>
                            <span class="fw-bold text-primary total-qty-produksi-${rIdx}" style="font-size:12px;">Total Produksi: 0 pcs</span>
                        </div>
                        <div class="table-responsive" style="border-radius:10px; border:1px solid #e2e8f0; overflow:hidden;">
                            <table class="table table-sm product-table-custom mb-0 align-middle">
                                <thead>
                                    <tr class="text-uppercase text-center">
                                        <th style="width:20%;">SKU PRODUK</th>
                                        <th style="width:25%;">NAMA PRODUK</th>
                                        <th style="width:12%;">UKURAN</th>
                                        <th style="width:10%;">QTY</th>
                                        <th style="width:16%;">RINCIAN BAHAN</th>
                                        <th style="width:17%;">TAHAP OPERASIONAL</th>
                                        <th style="width:36px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="productTableBody_${rIdx}">
                                    {{-- Dynamic Product Rows --}}
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-2 d-flex align-items-center justify-content-between">
                            <button type="button" class="btn btn-outline-primary btn-sm fw-bold px-3 btn-add-product-row" data-rincian-idx="${rIdx}">
                                ✚ Tambah Produk / Ukuran
                            </button>
                            <span class="text-muted small product-row-count-${rIdx}">0 baris produk</span>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.getElementById('rincianContainer').appendChild(card);

        // Bind image previews for this block
        bindRincianUploads(rIdx);

        // Add 1 default Product row
        addProductRowToBlock(rIdx);
    };

    window.addProductRowToBlock = function(rIdx, defaultData = null) {
        if (!productRowCounters[rIdx]) productRowCounters[rIdx] = 0;
        const pIdx = productRowCounters[rIdx]++;

        const tr = document.createElement('tr');
        tr.id = `product-row-${rIdx}-${pIdx}`;
        tr.dataset.rIdx = rIdx;
        tr.dataset.pIdx = pIdx;

        tr.innerHTML = `
            <td>
                <input type="text" name="rincian[${rIdx}][produk][${pIdx}][sku_produk]" class="form-control row-sku-produk font-monospace fw-bold text-primary"
                    list="master_skus_datalist" autocomplete="off"
                    placeholder="Pilih SKU" value="${defaultData ? escHtml(defaultData.sku_produk) : ''}">
            </td>
            <td>
                <input type="text" name="rincian[${rIdx}][produk][${pIdx}][nama_produk]" class="form-control row-nama-produk"
                    list="master_product_names_datalist" autocomplete="off"
                    placeholder="Nama produk" value="${defaultData ? escHtml(defaultData.nama_produk) : ''}">
            </td>
            <td>
                <input type="text" name="rincian[${rIdx}][produk][${pIdx}][ukuran]" class="form-control row-ukuran text-center"
                    placeholder="S, M, L" value="${defaultData ? escHtml(defaultData.ukuran) : ''}">
            </td>
            <td>
                <input type="number" name="rincian[${rIdx}][produk][${pIdx}][qty_produksi]" class="form-control text-center row-qty-produksi"
                    placeholder="1" min="1" value="${defaultData ? defaultData.qty_produksi : '1'}">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-secondary btn-bahan-trigger btn-open-bahan-modal" data-r-idx="${rIdx}" data-p-idx="${pIdx}">
                    📦 Atur Bahan <span class="badge bg-secondary rounded-pill ms-1 bahan-count-badge">0</span>
                </button>
                <div class="hidden-bahan-container-${rIdx}-${pIdx}"></div>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-primary btn-tahap-trigger btn-open-tahap-modal" data-r-idx="${rIdx}" data-p-idx="${pIdx}">
                    ✂️ Atur Tahap
                </button>
                <div class="hidden-tahap-container-${rIdx}-${pIdx}">
                    <input type="hidden" name="rincian[${rIdx}][produk][${pIdx}][pemotong]" class="h-pemotong" value="">
                    <input type="hidden" name="rincian[${rIdx}][produk][${pIdx}][qty_potong]" class="h-qty-potong" value="0">
                    <input type="hidden" name="rincian[${rIdx}][produk][${pIdx}][tarif_potong]" class="h-tarif-potong" value="0">

                    <input type="hidden" name="rincian[${rIdx}][produk][${pIdx}][penjahit]" class="h-penjahit" value="">
                    <input type="hidden" name="rincian[${rIdx}][produk][${pIdx}][qty_jahit]" class="h-qty-jahit" value="0">
                    <input type="hidden" name="rincian[${rIdx}][produk][${pIdx}][tarif_jahit]" class="h-tarif-jahit" value="0">

                    <input type="hidden" name="rincian[${rIdx}][produk][${pIdx}][vendor_kancing]" class="h-vendor-kancing" value="">
                    <input type="hidden" name="rincian[${rIdx}][produk][${pIdx}][qty_kancing]" class="h-qty-kancing" value="0">
                    <input type="hidden" name="rincian[${rIdx}][produk][${pIdx}][tarif_kancing]" class="h-tarif-kancing" value="0">

                    <input type="hidden" name="rincian[${rIdx}][produk][${pIdx}][petugas_qc]" class="h-petugas-qc" value="">
                    <input type="hidden" name="rincian[${rIdx}][produk][${pIdx}][qc_lolos]" class="h-qc-lolos" value="0">
                    <input type="hidden" name="rincian[${rIdx}][produk][${pIdx}][qc_reject]" class="h-qc-reject" value="0">
                    <input type="hidden" name="rincian[${rIdx}][produk][${pIdx}][tarif_qc]" class="h-tarif-qc" value="0">

                    <input type="hidden" name="rincian[${rIdx}][produk][${pIdx}][petugas_finishing]" class="h-petugas-finishing" value="">
                    <input type="hidden" name="rincian[${rIdx}][produk][${pIdx}][qty_finishing]" class="h-qty-finishing" value="0">
                    <input type="hidden" name="rincian[${rIdx}][produk][${pIdx}][qty_fgood]" class="h-qty-fgood" value="0">
                    <input type="hidden" name="rincian[${rIdx}][produk][${pIdx}][tarif_finishing]" class="h-tarif-finishing" value="0">
                </div>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-product-row py-0 px-1" title="Hapus produk">
                    <i class="fas fa-times"></i>
                </button>
            </td>
        `;

        const tbody = document.getElementById(`productTableBody_${rIdx}`);
        if (tbody) {
            tbody.appendChild(tr);
            updateProductRowCount(rIdx);
            calculateBlockTotalProducts(rIdx);
        }
    };

    function bindRincianUploads(rIdx) {
        // Referensi Klien
        const refInput = document.querySelector(`.input-ref-${rIdx}`);
        const refZone  = document.querySelector(`.ref-zone-${rIdx}`);
        const refPrev  = document.querySelector(`.ref-preview-${rIdx}`);
        const refHolder= document.querySelector(`.ref-placeholder-${rIdx}`);
        if (refInput) {
            refInput.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = e => {
                        refPrev.src = e.target.result;
                        refPrev.classList.remove('d-none');
                        refHolder.classList.add('d-none');
                        refZone.classList.add('has-file');
                    };
                    reader.readAsDataURL(file);
                }
            });
        }

        // Mockup Final
        const mockInput = document.querySelector(`.input-mockup-${rIdx}`);
        const mockZone  = document.querySelector(`.mockup-zone-${rIdx}`);
        const mockPrev  = document.querySelector(`.mockup-preview-${rIdx}`);
        const mockHolder= document.querySelector(`.mockup-placeholder-${rIdx}`);
        if (mockInput) {
            mockInput.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = e => {
                        mockPrev.src = e.target.result;
                        mockPrev.classList.remove('d-none');
                        mockHolder.classList.add('d-none');
                        mockZone.classList.add('has-file');
                        mockZone.style.opacity = '1';
                    };
                    reader.readAsDataURL(file);
                }
            });
        }
    }

    function updateProductRowCount(rIdx) {
        const tbody = document.getElementById(`productTableBody_${rIdx}`);
        if (tbody) {
            const count = tbody.querySelectorAll('tr').length;
            const span = document.querySelector(`.product-row-count-${rIdx}`);
            if (span) span.textContent = count + ' baris produk';
        }
    }

    function calculateBlockTotalProducts(rIdx) {
        const tbody = document.getElementById(`productTableBody_${rIdx}`);
        if (!tbody) return 0;
        let totalQty = 0;
        tbody.querySelectorAll('tr').forEach(tr => {
            const qty = parseInt(tr.querySelector('.row-qty-produksi')?.value) || 0;
            totalQty += qty;
        });
        const totalSpan = document.querySelector(`.total-qty-produksi-${rIdx}`);
        if (totalSpan) totalSpan.textContent = 'Total Produksi: ' + totalQty + ' pcs';
        return totalQty;
    }

    function handleSkuSelection(tr) {
        const skuInput  = tr.querySelector('.row-sku-produk');
        const nameInput = tr.querySelector('.row-nama-produk');
        const ukInput   = tr.querySelector('.row-ukuran');

        const cleanSku  = skuInput ? skuInput.value.trim().toUpperCase() : '';
        const cleanName = nameInput ? nameInput.value.trim().toUpperCase() : '';

        let masterProd = null;
        if (cleanSku && masterProductsMap[cleanSku]) {
            masterProd = masterProductsMap[cleanSku];
        } else if (cleanName && masterProductsMap[cleanName]) {
            masterProd = masterProductsMap[cleanName];
        } else if (cleanName) {
            const found = allMasterProductsList.find(p => p.name && p.name.trim().toUpperCase() === cleanName);
            if (found && found.sku) {
                masterProd = masterProductsMap[found.sku.toUpperCase()];
            }
        }

        if (masterProd) {
            if (nameInput && !nameInput.value) nameInput.value = masterProd.name;
            if (skuInput && !skuInput.value && masterProd.sku) skuInput.value = masterProd.sku;
            if (ukInput && masterProd.ukuran) ukInput.value = masterProd.ukuran;
        }

        applyRecipeToProductRow(tr);
    }

    function applyRecipeToProductRow(tr) {
        const rIdx = tr.dataset.rIdx;
        const pIdx = tr.dataset.pIdx;
        const skuInput  = tr.querySelector('.row-sku-produk');
        const nameInput = tr.querySelector('.row-nama-produk');
        const skuVal    = skuInput?.value?.trim()?.toUpperCase();
        const nameVal   = nameInput?.value?.trim()?.toUpperCase();
        const qtyProd   = parseInt(tr.querySelector('.row-qty-produksi')?.value || 1) || 1;

        let recipe = null;
        if (skuVal && recipesMap[skuVal]) {
            recipe = recipesMap[skuVal];
        } else if (nameVal && recipesMap[nameVal]) {
            recipe = recipesMap[nameVal];
        } else if (skuVal || nameVal) {
            const masterProd = (skuVal && masterProductsMap[skuVal]) || (nameVal && masterProductsMap[nameVal]);
            if (masterProd) {
                if (masterProd.sku && recipesMap[masterProd.sku.toUpperCase()]) {
                    recipe = recipesMap[masterProd.sku.toUpperCase()];
                } else if (masterProd.sku_induk && recipesMap[masterProd.sku_induk.toUpperCase()]) {
                    recipe = recipesMap[masterProd.sku_induk.toUpperCase()];
                } else if (masterProd.name && recipesMap[masterProd.name.toUpperCase()]) {
                    recipe = recipesMap[masterProd.name.toUpperCase()];
                }
            }
        }

        const container = tr.querySelector(`.hidden-bahan-container-${rIdx}-${pIdx}`);
        if (!container) return;

        if (recipe && recipe.items && recipe.items.length > 0) {
            container.innerHTML = '';
            let totalCost = 0;
            recipe.items.forEach((item, bIdx) => {
                const rawQty = item.qty_unit * qtyProd;
                const calcQty = (rawQty % 1 === 0) ? Math.round(rawQty).toString() : Number(rawQty.toFixed(4)).toString();
                const subtotal = rawQty * item.harga;
                totalCost += subtotal;

                container.appendChild(createHiddenBahanRow(rIdx, pIdx, bIdx, {
                    nama_bahan: item.nama_bahan + (item.unit ? ' (' + item.unit + ')' : ''),
                    qty_bahan: calcQty,
                    harga: item.harga,
                    subtotal: subtotal
                }));
            });

            updateProductRowBahanButton(tr, recipe.items.length, totalCost, true);
        }
    }

    function createHiddenBahanRow(rIdx, pIdx, bIdx, data = {}) {
        const div = document.createElement('div');
        div.className = `hidden-bahan-row hidden-bahan-${bIdx}`;
        div.innerHTML = `
            <input type="hidden" name="rincian[${rIdx}][produk][${pIdx}][bahan][${bIdx}][nama_bahan]" value="${escHtml(data.nama_bahan || '')}">
            <input type="hidden" name="rincian[${rIdx}][produk][${pIdx}][bahan][${bIdx}][qty_bahan]" value="${escHtml(data.qty_bahan || '1')}">
            <input type="hidden" name="rincian[${rIdx}][produk][${pIdx}][bahan][${bIdx}][harga]" value="${escHtml(data.harga || '0')}">
            <input type="hidden" name="rincian[${rIdx}][produk][${pIdx}][bahan][${bIdx}][subtotal]" value="${escHtml(data.subtotal || '0')}">
        `;
        return div;
    }

    function updateProductRowBahanButton(tr, count, totalCost, isRecipe = false) {
        const btn = tr.querySelector('.btn-bahan-trigger');
        if (!btn) return;

        if (count > 0) {
            btn.className = 'btn btn-sm btn-success-subtle text-success border border-success-subtle btn-bahan-trigger btn-open-bahan-modal';
            btn.innerHTML = `${isRecipe ? '✨' : '📦'} ${count} Bahan (${formatRupiah(totalCost)})`;
        } else {
            btn.className = 'btn btn-sm btn-outline-secondary btn-bahan-trigger btn-open-bahan-modal';
            btn.innerHTML = `📦 Atur Bahan <span class="badge bg-secondary rounded-pill ms-1">0</span>`;
        }
    }

    // ── 4. POPUP MODAL BAHAN LOGIC ──
    let activeModalRIdx = null;
    let activeModalPIdx = null;
    let modalBahanCounter = 0;
    const bahanModal = new bootstrap.Modal(document.getElementById('modalBahanProduk'));

    function openBahanModalForProduct(rIdx, pIdx) {
        activeModalRIdx = rIdx;
        activeModalPIdx = pIdx;

        const tr = document.getElementById(`product-row-${rIdx}-${pIdx}`);
        if (!tr) return;

        const skuVal = tr.querySelector('.row-sku-produk')?.value || '-';
        const nameVal = tr.querySelector('.row-nama-produk')?.value || 'Produk';
        const ukVal = tr.querySelector('.row-ukuran')?.value || '-';
        const qtyVal = tr.querySelector('.row-qty-produksi')?.value || '1';

        document.getElementById('modalProductSubtitle').innerHTML = `
            <strong>SKU:</strong> ${escHtml(skuVal)} · <strong>${escHtml(nameVal)}</strong> (Ukuran: ${escHtml(ukVal)}) · <strong>Qty:</strong> ${escHtml(qtyVal)} pcs
        `;

        const container = tr.querySelector(`.hidden-bahan-container-${rIdx}-${pIdx}`);
        const tbody = document.getElementById('modalBahanTableBody');
        tbody.innerHTML = '';
        modalBahanCounter = 0;

        const hiddenRows = container ? container.querySelectorAll('.hidden-bahan-row') : [];
        if (hiddenRows.length > 0) {
            hiddenRows.forEach(hRow => {
                const nBahan = hRow.querySelector('input[name*="[nama_bahan]"]')?.value || '';
                const qBahan = hRow.querySelector('input[name*="[qty_bahan]"]')?.value || '1';
                const hBahan = hRow.querySelector('input[name*="[harga]"]')?.value || '0';
                addModalBahanRow({ nama_bahan: nBahan, qty_bahan: qBahan, harga: hBahan });
            });
        } else {
            addModalBahanRow();
        }

        calculateModalTotalBahan();
        bahanModal.show();
    }

    function addModalBahanRow(data = null) {
        const bIdx = modalBahanCounter++;
        const tbody = document.getElementById('modalBahanTableBody');

        const tr = document.createElement('tr');
        const qtyVal = data ? data.qty_bahan : '1';
        let hargaVal = data ? data.harga : '0';

        if (data && data.nama_bahan) {
            const cleanName = data.nama_bahan.trim().toUpperCase();
            if ((!data.harga || parseFloat(data.harga) === 0) && inventoryItemsMap[cleanName]) {
                hargaVal = inventoryItemsMap[cleanName].cost_price;
            }
        }

        const subtotalVal = (parseFloat(qtyVal) || 0) * (parseFloat(hargaVal) || 0);

        tr.innerHTML = `
            <td>
                <input type="text" class="form-control modal-row-nama-bahan" list="inventory_items_datalist" autocomplete="off"
                    placeholder="Ketik / Pilih nama bahan..." value="${data ? escHtml(data.nama_bahan) : ''}">
            </td>
            <td>
                <input type="text" class="form-control text-center modal-row-qty-bahan" placeholder="1.5 / 10 pcs" value="${data ? escHtml(data.qty_bahan) : '1'}">
            </td>
            <td>
                <input type="number" class="form-control text-end modal-row-harga-bahan" placeholder="0" min="0" value="${hargaVal}">
            </td>
            <td>
                <input type="text" class="form-control text-end bg-light modal-row-subtotal-bahan" readonly tabindex="-1" value="${formatRupiah(subtotalVal)}">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-modal-bahan-row py-0 px-1" title="Hapus bahan">
                    <i class="fas fa-times"></i>
                </button>
            </td>
        `;

        tbody.appendChild(tr);
        calculateModalTotalBahan();
    }

    function calculateModalTotalBahan() {
        const tbody = document.getElementById('modalBahanTableBody');
        if (!tbody) return;
        let total = 0;
        tbody.querySelectorAll('tr').forEach(tr => {
            const qtyStr = tr.querySelector('.modal-row-qty-bahan')?.value || '0';
            const qty = parseFloat(qtyStr) || 1;
            const harga = parseFloat(tr.querySelector('.modal-row-harga-bahan')?.value) || 0;
            const subtotal = qty * harga;
            const subInp = tr.querySelector('.modal-row-subtotal-bahan');
            if (subInp) subInp.value = formatRupiah(subtotal);
            total += subtotal;
        });

        document.getElementById('modalTotalBahanDisplay').textContent = 'Total: ' + formatRupiah(total);
        saveModalDataToHiddenContainer(total);
    }

    function saveModalDataToHiddenContainer(totalCost) {
        if (activeModalRIdx === null || activeModalPIdx === null) return;
        const productTr = document.getElementById(`product-row-${activeModalRIdx}-${activeModalPIdx}`);
        if (!productTr) return;

        const container = productTr.querySelector(`.hidden-bahan-container-${activeModalRIdx}-${activeModalPIdx}`);
        if (!container) return;

        container.innerHTML = '';
        const modalTbody = document.getElementById('modalBahanTableBody');
        let count = 0;

        modalTbody.querySelectorAll('tr').forEach((tr, bIdx) => {
            const nBahan = tr.querySelector('.modal-row-nama-bahan')?.value?.trim();
            const qBahan = tr.querySelector('.modal-row-qty-bahan')?.value || '1';
            const hBahan = tr.querySelector('.modal-row-harga-bahan')?.value || '0';
            const subtotal = (parseFloat(qBahan) || 1) * (parseFloat(hBahan) || 0);

            if (nBahan) {
                container.appendChild(createHiddenBahanRow(activeModalRIdx, activeModalPIdx, bIdx, {
                    nama_bahan: nBahan,
                    qty_bahan: qBahan,
                    harga: hBahan,
                    subtotal: subtotal
                }));
                count++;
            }
        });

        updateProductRowBahanButton(productTr, count, totalCost);
    }

    // Modal Add Row Button
    document.getElementById('btnModalAddBahanRow').addEventListener('click', function() {
        addModalBahanRow();
    });

    // Modal Table Input listener
    document.getElementById('modalBahanTableBody').addEventListener('input', function(e) {
        if (e.target.classList.contains('modal-row-nama-bahan')) {
            updateInventoryItemsDatalist(e.target.value);
            const cleanName = e.target.value.trim().toUpperCase();
            if (cleanName && inventoryItemsMap[cleanName]) {
                const tr = e.target.closest('tr');
                if (tr) {
                    const hInput = tr.querySelector('.modal-row-harga-bahan');
                    if (hInput) {
                        hInput.value = inventoryItemsMap[cleanName].cost_price;
                    }
                }
            }
        }
        if (e.target.classList.contains('modal-row-qty-bahan') || e.target.classList.contains('modal-row-harga-bahan') || e.target.classList.contains('modal-row-nama-bahan')) {
            calculateModalTotalBahan();
        }
    });

    // Modal Table Remove listener
    document.getElementById('modalBahanTableBody').addEventListener('click', function(e) {
        const btnRemove = e.target.closest('.btn-remove-modal-bahan-row');
        if (btnRemove) {
            btnRemove.closest('tr').remove();
            calculateModalTotalBahan();
        }
    });

    // ── 5. POPUP MODAL TAHAP OPERASIONAL LOGIC ──
    let activeTahapRIdx = null;
    let activeTahapPIdx = null;
    const tahapModal = new bootstrap.Modal(document.getElementById('modalTahapOperasional'));

    function openTahapModalForProduct(rIdx, pIdx) {
        activeTahapRIdx = rIdx;
        activeTahapPIdx = pIdx;

        const tr = document.getElementById(`product-row-${rIdx}-${pIdx}`);
        if (!tr) return;

        const skuVal = tr.querySelector('.row-sku-produk')?.value || '-';
        const nameVal = tr.querySelector('.row-nama-produk')?.value || 'Produk';
        const ukVal = tr.querySelector('.row-ukuran')?.value || '-';
        const qtyVal = tr.querySelector('.row-qty-produksi')?.value || '1';

        document.getElementById('modalTahapProductSubtitle').innerHTML = `
            <strong>SKU:</strong> ${escHtml(skuVal)} · <strong>${escHtml(nameVal)}</strong> (Ukuran: ${escHtml(ukVal)}) · <strong>Qty Produksi:</strong> ${escHtml(qtyVal)} pcs
        `;

        const container = tr.querySelector(`.hidden-tahap-container-${rIdx}-${pIdx}`);
        if (container) {
            document.getElementById('modal_pemotong').value = container.querySelector('.h-pemotong')?.value || '';
            document.getElementById('modal_qty_potong').value = container.querySelector('.h-qty-potong')?.value || qtyVal;
            document.getElementById('modal_tarif_potong').value = container.querySelector('.h-tarif-potong')?.value || '';

            document.getElementById('modal_penjahit').value = container.querySelector('.h-penjahit')?.value || '';
            document.getElementById('modal_qty_jahit').value = container.querySelector('.h-qty-jahit')?.value || qtyVal;
            document.getElementById('modal_tarif_jahit').value = container.querySelector('.h-tarif-jahit')?.value || '';

            document.getElementById('modal_vendor_kancing').value = container.querySelector('.h-vendor-kancing')?.value || '';
            document.getElementById('modal_qty_kancing').value = container.querySelector('.h-qty-kancing')?.value || '';
            document.getElementById('modal_tarif_kancing').value = container.querySelector('.h-tarif-kancing')?.value || '';

            document.getElementById('modal_petugas_qc').value = container.querySelector('.h-petugas-qc')?.value || '';
            document.getElementById('modal_qc_lolos').value = container.querySelector('.h-qc-lolos')?.value || '';
            document.getElementById('modal_qc_reject').value = container.querySelector('.h-qc-reject')?.value || '';
            document.getElementById('modal_tarif_qc').value = container.querySelector('.h-tarif-qc')?.value || '';

            document.getElementById('modal_qty_finishing').value = container.querySelector('.h-qty-finishing')?.value || '';
            document.getElementById('modal_qty_fgood').value = container.querySelector('.h-qty-fgood')?.value || '';
        }

        calculateModalTahapLaborTotal();
        tahapModal.show();
    }

    function calculateModalTahapLaborTotal() {
        const qPotong = parseFloat(document.getElementById('modal_qty_potong').value) || 0;
        const tPotong = parseFloat(document.getElementById('modal_tarif_potong').value) || 0;
        const subPotong = qPotong * tPotong;
        document.querySelector('.subtotal-potong-display').textContent = 'Subtotal: ' + formatRupiah(subPotong);

        const qJahit = parseFloat(document.getElementById('modal_qty_jahit').value) || 0;
        const tJahit = parseFloat(document.getElementById('modal_tarif_jahit').value) || 0;
        const subJahit = qJahit * tJahit;
        document.querySelector('.subtotal-jahit-display').textContent = 'Subtotal: ' + formatRupiah(subJahit);

        const qKancing = parseFloat(document.getElementById('modal_qty_kancing').value) || 0;
        const tKancing = parseFloat(document.getElementById('modal_tarif_kancing').value) || 0;
        const subKancing = qKancing * tKancing;
        document.querySelector('.subtotal-kancing-display').textContent = 'Subtotal: ' + formatRupiah(subKancing);

        const qQc = parseFloat(document.getElementById('modal_qc_lolos').value) || 0;
        const tQc = parseFloat(document.getElementById('modal_tarif_qc').value) || 0;
        const subQc = qQc * tQc;
        document.querySelector('.subtotal-qc-display').textContent = 'Subtotal: ' + formatRupiah(subQc);

        const totalLabor = subPotong + subJahit + subKancing + subQc;
        document.getElementById('modalTotalLaborDisplay').textContent = 'Total Ongkos Jasa: ' + formatRupiah(totalLabor);
        return totalLabor;
    }

    document.querySelectorAll('.modal-op-field').forEach(field => {
        field.addEventListener('input', calculateModalTahapLaborTotal);
    });

    document.getElementById('btnSaveModalTahap').addEventListener('click', function() {
        if (activeTahapRIdx === null || activeTahapPIdx === null) return;
        const tr = document.getElementById(`product-row-${activeTahapRIdx}-${activeTahapPIdx}`);
        if (!tr) return;

        const container = tr.querySelector(`.hidden-tahap-container-${activeTahapRIdx}-${activeTahapPIdx}`);
        if (!container) return;

        const pemotong = document.getElementById('modal_pemotong').value.trim();
        const qtyPotong = document.getElementById('modal_qty_potong').value || '0';
        const tarifPotong = document.getElementById('modal_tarif_potong').value || '0';

        const penjahit = document.getElementById('modal_penjahit').value.trim();
        const qtyJahit = document.getElementById('modal_qty_jahit').value || '0';
        const tarifJahit = document.getElementById('modal_tarif_jahit').value || '0';

        const vendorKancing = document.getElementById('modal_vendor_kancing').value.trim();
        const qtyKancing = document.getElementById('modal_qty_kancing').value || '0';
        const tarifKancing = document.getElementById('modal_tarif_kancing').value || '0';

        const petugasQc = document.getElementById('modal_petugas_qc').value.trim();
        const qcLolos = document.getElementById('modal_qc_lolos').value || '0';
        const qcReject = document.getElementById('modal_qc_reject').value || '0';
        const tarifQc = document.getElementById('modal_tarif_qc').value || '0';

        const qtyFinishing = document.getElementById('modal_qty_finishing').value || '0';
        const qtyFgood = document.getElementById('modal_qty_fgood').value || '0';

        // Hidden input sets
        if (container.querySelector('.h-pemotong')) container.querySelector('.h-pemotong').value = pemotong;
        if (container.querySelector('.h-qty-potong')) container.querySelector('.h-qty-potong').value = qtyPotong;
        if (container.querySelector('.h-tarif-potong')) container.querySelector('.h-tarif-potong').value = tarifPotong;

        if (container.querySelector('.h-penjahit')) container.querySelector('.h-penjahit').value = penjahit;
        if (container.querySelector('.h-qty-jahit')) container.querySelector('.h-qty-jahit').value = qtyJahit;
        if (container.querySelector('.h-tarif-jahit')) container.querySelector('.h-tarif-jahit').value = tarifJahit;

        if (container.querySelector('.h-vendor-kancing')) container.querySelector('.h-vendor-kancing').value = vendorKancing;
        if (container.querySelector('.h-qty-kancing')) container.querySelector('.h-qty-kancing').value = qtyKancing;
        if (container.querySelector('.h-tarif-kancing')) container.querySelector('.h-tarif-kancing').value = tarifKancing;

        if (container.querySelector('.h-petugas-qc')) container.querySelector('.h-petugas-qc').value = petugasQc;
        if (container.querySelector('.h-qc-lolos')) container.querySelector('.h-qc-lolos').value = qcLolos;
        if (container.querySelector('.h-qc-reject')) container.querySelector('.h-qc-reject').value = qcReject;
        if (container.querySelector('.h-tarif-qc')) container.querySelector('.h-tarif-qc').value = tarifQc;

        if (container.querySelector('.h-qty-finishing')) container.querySelector('.h-qty-finishing').value = qtyFinishing;
        if (container.querySelector('.h-qty-fgood')) container.querySelector('.h-qty-fgood').value = qtyFgood;

        const totalLaborCost = calculateModalTahapLaborTotal();

        // Update button text badge on main product row table
        const btnTahap = tr.querySelector('.btn-tahap-trigger');
        if (btnTahap) {
            if (pemotong || penjahit || vendorKancing || petugasQc || totalLaborCost > 0) {
                btnTahap.className = 'btn btn-sm btn-primary-subtle text-primary border border-primary-subtle btn-tahap-trigger btn-open-tahap-modal';
                let labelText = '';
                if (penjahit) labelText = `${penjahit}`;
                else if (pemotong) labelText = `Potong: ${pemotong}`;
                else labelText = `Jasa SPK`;
                btnTahap.innerHTML = `✂️ ${escHtml(labelText)} (${formatRupiah(totalLaborCost)})`;
            } else {
                btnTahap.className = 'btn btn-sm btn-outline-primary btn-tahap-trigger btn-open-tahap-modal';
                btnTahap.innerHTML = `✂️ Atur Tahap`;
            }
        }

        tahapModal.hide();
    });

    // Add Block Button Handler
    document.getElementById('btnAddRincianBlock').addEventListener('click', function() {
        addRincianBlock();
    });

    // Delegate events inside rincian cards
    document.getElementById('rincianContainer').addEventListener('click', function(e) {
        const btnOpenBahan = e.target.closest('.btn-open-bahan-modal');
        if (btnOpenBahan) {
            const rIdx = btnOpenBahan.dataset.rIdx;
            const pIdx = btnOpenBahan.dataset.pIdx;
            openBahanModalForProduct(rIdx, pIdx);
            return;
        }

        const btnOpenTahap = e.target.closest('.btn-open-tahap-modal');
        if (btnOpenTahap) {
            const rIdx = btnOpenTahap.dataset.rIdx;
            const pIdx = btnOpenTahap.dataset.pIdx;
            openTahapModalForProduct(rIdx, pIdx);
            return;
        }

        const btnAddProduct = e.target.closest('.btn-add-product-row');
        if (btnAddProduct) {
            const rIdx = btnAddProduct.dataset.rincianIdx;
            addProductRowToBlock(rIdx);
            return;
        }

        const btnRemoveProduct = e.target.closest('.btn-remove-product-row');
        if (btnRemoveProduct) {
            const tr = btnRemoveProduct.closest('tr');
            const tbody = tr.closest('tbody');
            const rIdx = tbody.id.replace('productTableBody_', '');
            tr.remove();
            updateProductRowCount(rIdx);
            calculateBlockTotalProducts(rIdx);
            return;
        }

        const btnRemoveRincian = e.target.closest('.btn-remove-rincian');
        if (btnRemoveRincian) {
            if (confirm('Hapus seluruh rincian produk ini?')) {
                btnRemoveRincian.closest('.rincian-card').remove();
            }
            return;
        }
    });

    function updateMasterSkuDatalist(query) {
        const datalist = document.getElementById('master_skus_datalist');
        if (!datalist) return;
        const cleanQ = (query || '').trim().toLowerCase();

        let matches = [];
        if (!cleanQ) {
            matches = allMasterProductsList.slice(0, 10);
        } else {
            matches = allMasterProductsList.filter(p => {
                return (p.sku && p.sku.toLowerCase().includes(cleanQ)) ||
                       (p.sku_induk && p.sku_induk.toLowerCase().includes(cleanQ)) ||
                       (p.name && p.name.toLowerCase().includes(cleanQ));
            }).slice(0, 10);
        }

        let html = '';
        matches.forEach(p => {
            if (p.sku) {
                html += `<option value="${escHtml(p.sku)}">${escHtml(p.name)} ${p.ukuran ? '(' + escHtml(p.ukuran) + ')' : ''}</option>`;
            }
            if (p.sku_induk && p.sku_induk !== p.sku) {
                html += `<option value="${escHtml(p.sku_induk)}">${escHtml(p.name)} (Induk)</option>`;
            }
        });
        datalist.innerHTML = html;
    }

    function updateInventoryItemsDatalist(query) {
        const datalist = document.getElementById('inventory_items_datalist');
        if (!datalist) return;
        const cleanQ = (query || '').trim().toLowerCase();

        let matches = [];
        if (!cleanQ) {
            matches = allInventoryItemsList.slice(0, 10);
        } else {
            matches = allInventoryItemsList.filter(name => {
                return name && name.toLowerCase().includes(cleanQ);
            }).slice(0, 10);
        }

        let html = '';
        matches.forEach(name => {
            html += `<option value="${escHtml(name)}"></option>`;
        });
        datalist.innerHTML = html;
    }

    function updateMasterProductNameDatalist(query) {
        const datalist = document.getElementById('master_product_names_datalist');
        if (!datalist) return;
        const cleanQ = (query || '').trim().toLowerCase();

        let matches = [];
        if (!cleanQ) {
            matches = allMasterProductsList.slice(0, 10);
        } else {
            matches = allMasterProductsList.filter(p => {
                return (p.sku && p.sku.toLowerCase().includes(cleanQ)) ||
                       (p.sku_induk && p.sku_induk.toLowerCase().includes(cleanQ)) ||
                       (p.name && p.name.toLowerCase().includes(cleanQ));
            }).slice(0, 10);
        }

        let html = '';
        matches.forEach(p => {
            if (p.name) {
                html += `<option value="${escHtml(p.name)}">${p.sku ? escHtml(p.sku) + ' — ' : ''}${p.ukuran ? 'Ukuran: ' + escHtml(p.ukuran) : ''}</option>`;
            }
        });
        datalist.innerHTML = html;
    }

    // Delegate input & change changes for SKU lookup, calculation & recipe lookup
    const handleProductRowInputChange = function(e) {
        if (e.target.classList.contains('row-sku-produk')) {
            updateMasterSkuDatalist(e.target.value);
            const tr = e.target.closest('tr');
            if (tr) handleSkuSelection(tr);
        }

        if (e.target.classList.contains('row-nama-produk')) {
            updateMasterProductNameDatalist(e.target.value);
            const tr = e.target.closest('tr');
            if (tr) handleSkuSelection(tr);
        }

        if (e.target.classList.contains('row-sku-produk') || e.target.classList.contains('row-nama-produk') || e.target.classList.contains('row-qty-produksi')) {
            const tr = e.target.closest('tr');
            const tbody = tr ? tr.closest('tbody') : null;
            if (tbody) {
                const rIdx = tbody.id.replace('productTableBody_', '');
                calculateBlockTotalProducts(rIdx);
                applyRecipeToProductRow(tr);
            }
        }
    };

    document.getElementById('rincianContainer').addEventListener('input', handleProductRowInputChange);
    document.getElementById('rincianContainer').addEventListener('change', handleProductRowInputChange);

    // Initialize with 1 default Rincian Block
    addRincianBlock();

}); // end DOMContentLoaded
</script>
@endsection
