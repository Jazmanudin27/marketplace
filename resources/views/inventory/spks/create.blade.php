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
    .spk-field-group label {
        display: block;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .8px;
        text-transform: uppercase;
        color: #6b7280;
        margin-bottom: 4px;
    }
    .spk-field-group .field-value {
        font-size: 14px;
        font-weight: 700;
        color: #111;
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
    .tahap-select-wrap {
        position: relative;
    }
    .tahap-select {
        background: #f1f5f9;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        padding: 10px 14px;
        color: #1e293b;
        cursor: pointer;
        transition: border-color .2s;
    }
    .tahap-select:focus {
        border-color: #3b82f6;
        outline: none;
        background: #fff;
    }

    /* Rincian Produk Section */
    .rincian-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        margin-bottom: 14px;
        box-shadow: 0 1px 4px rgba(0,0,0,.05);
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
        min-height: 100px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all .2s;
        position: relative;
        background: #fafafa;
        text-align: center;
        padding: 14px;
    }
    .upload-zone:hover { border-color: #3b82f6; background: #eff6ff; }
    .upload-zone.has-file { border-style: solid; border-color: #10b981; background: #ecfdf5; }
    .upload-zone input[type=file] {
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
    }
    .upload-zone .uz-icon { font-size: 24px; opacity: .35; }
    .upload-zone .uz-label { font-size: 11px; font-weight: 700; color: #6b7280; letter-spacing: .5px; margin-top: 4px; }
    .upload-zone .uz-hint { font-size: 10px; color: #9ca3af; }

    /* SKU Table */
    .sku-table-custom {
        border-collapse: separate;
        border-spacing: 0;
        font-size: 12px;
    }
    .sku-table-custom thead tr th {
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        font-size: 11px;
        font-weight: 700;
        text-align: center;
        letter-spacing: .4px;
        padding: 8px 6px;
    }
    .sku-table-custom tbody td {
        border: 1px solid #e2e8f0;
        padding: 6px;
        vertical-align: middle;
    }
    .sku-table-custom .form-control, .sku-table-custom .form-select {
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
</style>
@endpush

@section('content')
<div class="container-fluid py-2 px-3 spk-create-wrap">

    {{-- Datalist Autocomplete SKU Master --}}
    <datalist id="master_skus_datalist">
        @foreach($products as $p)
            <option value="{{ $p->sku }}">{{ $p->name }} @if($p->sku_induk)({{ $p->sku_induk }})@endif</option>
            @if($p->sku_induk && $p->sku_induk !== $p->sku)
                <option value="{{ $p->sku_induk }}">{{ $p->name }} (Induk)</option>
            @endif
        @endforeach
    </datalist>

    {{-- Datalist Autocomplete Inventory Items (Tabel Barang) --}}
    <datalist id="inventory_items_datalist">
        @foreach($inventoryItems as $invItemName)
            <option value="{{ $invItemName }}"></option>
        @endforeach
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

{{-- Data untuk JS --}}
<script>
    const tailorsList = @json($tailors);
    const existingNoProduksiList = @json($existingNoProduksi);
    const orderItemsList = @json(isset($order) ? $order->items : []);
    const recipesMap = @json($recipesMap ?? []);
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

    // ── 3. DYNAMIC RINCIAN PRODUK (SPK) BLOCKS ──
    let rincianBlockCount = 0;

    function escHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
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

                {{-- PRODUK UMUM + SKU KAIN --}}
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 p-2 rounded" style="background:#f8fafc; border:1px solid #e2e8f0;">
                    <div class="d-flex align-items-center gap-2 flex-grow-1" style="max-width:400px;">
                        <span class="fw-bold text-dark" style="font-size:12px; letter-spacing:.3px; white-space:nowrap;">
                            🧵 PRODUK / SPK:
                        </span>
                        <input type="text" name="rincian[${rIdx}][nama_produk]" class="form-control form-control-sm rincian-nama-produk"
                            placeholder="Contoh: Kemeja Batik SD, Celana PDL..." style="font-size:12px; font-weight:600;">
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary fw-bold" style="font-size:11px; padding:6px 10px;">📦 SKU KAIN / BAHAN :</span>
                        <input type="text" name="rincian[${rIdx}][sku_kain]" class="form-control form-control-sm rincian-sku-kain"
                            list="inventory_items_datalist" autocomplete="off"
                            style="width:220px; font-size:12px;" placeholder="Pilih / ketik bahan..." data-rincian-idx="${rIdx}">
                    </div>
                </div>

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
                                    <div class="uz-hint">Foto contoh / referensi dari klien</div>
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
                        <div>
                            <div class="section-label mb-1" style="font-size:10px; font-weight:700; letter-spacing:.7px; text-transform:uppercase; color:#16a34a;">
                                🔗 LINK FILE MENTAH
                            </div>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background:#f0fdf4; border-color:#bbf7d0; color:#16a34a; font-size:14px;">G</span>
                                <input type="url" name="rincian[${rIdx}][link_file_mentah]" class="form-control"
                                    style="font-size:12px;" placeholder="Paste link G-Drive / Dropbox...">
                            </div>
                        </div>
                    </div>

                    {{-- Right: SKU Table --}}
                    <div class="col-xl-9 col-lg-8">
                        <div class="table-responsive" style="border-radius:10px; border:1px solid #e2e8f0; overflow:hidden;">
                            <table class="table table-sm sku-table-custom mb-0 align-middle">
                                <thead>
                                    <tr class="text-uppercase text-center">
                                        <th style="width:50%;">SKU PRODUK</th>
                                        <th style="width:35%;">SKU KAIN / BAHAN</th>
                                        <th style="width:15%;">QTY</th>
                                        <th style="width:36px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="skuTableBody_${rIdx}">
                                    {{-- SKU Rows --}}
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-2 d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-outline-primary btn-sm fw-bold px-3 btn-add-sku-row" data-rincian-idx="${rIdx}">
                                    ✚ Baris SKU
                                </button>
                                <span class="text-muted small sku-row-count-${rIdx}">0 baris SKU</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.getElementById('rincianContainer').appendChild(card);

        // Bind image previews for this block
        bindRincianUploads(rIdx);

        // Add 1 default SKU row
        addSkuRowToBlock(rIdx);
    };

    let itemCounters = {};

    window.addSkuRowToBlock = function(rIdx, defaultData = null) {
        if (!itemCounters[rIdx]) itemCounters[rIdx] = 0;
        const itemIdx = itemCounters[rIdx]++;

        const card = document.getElementById(`rincian-card-${rIdx}`);
        const skuKainInput = card ? card.querySelector('.rincian-sku-kain') : null;
        const currentSkuKain = skuKainInput ? skuKainInput.value : '';

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <input type="text" name="rincian[${rIdx}][items][${itemIdx}][sku_produk]" class="form-control row-sku-produk"
                    list="master_skus_datalist" autocomplete="off"
                    placeholder="SKU Produk" style="font-size:12px;" value="${defaultData ? escHtml(defaultData.sku_produk) : ''}">
            </td>
            <td>
                <input type="text" name="rincian[${rIdx}][items][${itemIdx}][sku_kain]" class="form-control row-sku-kain"
                    list="inventory_items_datalist" autocomplete="off"
                    placeholder="Pilih / SKU Kain" value="${defaultData ? escHtml(defaultData.sku_kain) : escHtml(currentSkuKain)}"
                    style="font-size:12px;">
            </td>
            <td>
                <input type="number" name="rincian[${rIdx}][items][${itemIdx}][qty]" class="form-control text-center row-qty"
                    placeholder="0" min="1" value="${defaultData ? defaultData.qty : 1}" style="font-size:12px; padding:4px;">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-sku-row py-0 px-1" title="Hapus baris">
                    <i class="fas fa-times"></i>
                </button>
            </td>
        `;

        const tbody = document.getElementById(`skuTableBody_${rIdx}`);
        if (tbody) {
            tbody.appendChild(tr);
            updateRowCount(rIdx);
            if (defaultData && defaultData.sku_produk) {
                applyRecipeToRow(tr, defaultData.sku_produk);
            }
        }
    };

    function applyRecipeToRow(tr, skuVal) {
        if (!skuVal) return;
        const cleanSku = skuVal.trim().toUpperCase();
        const recipe = recipesMap[cleanSku];

        if (recipe && recipe.items && recipe.items.length > 0) {
            const qtyInput = tr.querySelector('.row-qty');
            const qty = parseInt(qtyInput ? qtyInput.value : 1) || 1;
            const tbody = tr.closest('tbody');
            const rIdx = tbody ? tbody.id.replace('skuTableBody_', '') : 0;

            const item0 = recipe.items[0];
            const kainInput = tr.querySelector('.row-sku-kain');
            if (kainInput && item0.sku_kain) {
                kainInput.value = item0.sku_kain;
            }

            if (recipe.items.length > 1 && tbody && !tr.dataset.recipeExpanded) {
                tr.dataset.recipeExpanded = 'true';
                for (let i = 1; i < recipe.items.length; i++) {
                    const itemData = recipe.items[i];
                    addSkuRowToBlock(rIdx, {
                        sku_produk: skuVal,
                        sku_kain: itemData.sku_kain,
                        qty: qty
                    });
                    const newTr = tbody.lastElementChild;
                    if (newTr) {
                        newTr.dataset.recipeExpanded = 'true';
                    }
                }
            }
        }
    }

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

    function updateRowCount(rIdx) {
        const tbody = document.getElementById(`skuTableBody_${rIdx}`);
        if (tbody) {
            const count = tbody.querySelectorAll('tr').length;
            const span = document.querySelector(`.sku-row-count-${rIdx}`);
            if (span) span.textContent = count + ' baris SKU';
        }
    }

    // Add Block Button Handler
    document.getElementById('btnAddRincianBlock').addEventListener('click', function() {
        addRincianBlock();
    });

    // Delegate Add SKU Row & Remove Block events
    document.getElementById('rincianContainer').addEventListener('click', function(e) {
        const btnAddSku = e.target.closest('.btn-add-sku-row');
        if (btnAddSku) {
            const rIdx = btnAddSku.dataset.rincianIdx;
            addSkuRowToBlock(rIdx);
            return;
        }

        const btnRemoveRow = e.target.closest('.btn-remove-sku-row');
        if (btnRemoveRow) {
            const tr = btnRemoveRow.closest('tr');
            const tbody = tr.closest('tbody');
            const rIdx = tbody.id.replace('skuTableBody_', '');
            tr.remove();
            updateRowCount(rIdx);
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

    // Delegate Auto-Fill from Formula when SKU or QTY changes
    document.getElementById('rincianContainer').addEventListener('input', function(e) {
        if (e.target.classList.contains('row-sku-produk')) {
            const tr = e.target.closest('tr');
            if (tr) {
                const skuVal = tr.querySelector('.row-sku-produk')?.value || '';
                applyRecipeToRow(tr, skuVal);
            }
        }

        // Global SKU Kain auto-fill per block
        if (e.target.classList.contains('rincian-sku-kain')) {
            const rIdx = e.target.dataset.rincianIdx;
            const val = e.target.value;
            const tbody = document.getElementById(`skuTableBody_${rIdx}`);
            if (tbody) {
                tbody.querySelectorAll('.row-sku-kain').forEach(inp => {
                    if (inp.value === '' || inp.dataset.autoFilled === 'true') {
                        inp.value = val;
                        inp.dataset.autoFilled = 'true';
                    }
                });
            }
        }
    });

    // Initialize with 1 default Rincian Block
    addRincianBlock();

}); // end DOMContentLoaded
</script>
@endsection
