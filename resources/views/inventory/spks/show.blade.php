@extends('layouts.app')
@section('title', 'Detail & Edit SPK #' . $spk->no_spk)
@section('page-title', 'Marketing & Pengiriman')

@push('styles')
    <style>
        /* ── SPK Form Styles ── */
        .spk-create-wrap {
            font-family: 'Inter', system-ui, sans-serif;
            color: #1a1a2e;
        }

        /* Info Banner Row */
        .spk-info-banner {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px 24px;
            margin-bottom: 14px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, .05);
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
            min-height: 250px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all .2s;
            position: relative;
            overflow: hidden;
            background: #fafafa;
            padding: 10px;
        }

        .desain-drop-area:hover {
            border-color: #3b82f6;
            background: #eff6ff;
        }

        .desain-drop-area.has-image {
            border-style: solid;
            border-color: #3b82f6;
            background: #fff;
        }

        .desain-drop-area input[type=file] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            z-index: 2;
        }

        .desain-drop-area .desain-label {
            font-size: 2.5rem;
            font-weight: 900;
            letter-spacing: 6px;
            color: #d1d5db;
            user-select: none;
            line-height: 1;
        }

        .desain-drop-area .desain-hint {
            font-size: 12px;
            color: #9ca3af;
            margin-top: 6px;
        }

        .desain-drop-area #desain-preview-img {
            max-height: 240px;
            width: 100%;
            border-radius: 10px;
            object-fit: contain;
        }

        /* Type Tabs */
        .spk-type-tabs .btn-check:checked+.btn-outline-primary,
        .spk-type-tabs .btn-check:checked+.btn-outline-secondary {
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
        .spk-priority-toggle .btn-check:checked+.btn-urgent {
            background: #ef4444;
            color: #fff;
            border-color: #ef4444;
        }

        .spk-priority-toggle .btn-check:checked+.btn-normal {
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

        .btn-urgent {
            border-color: #ef4444;
            color: #ef4444;
        }

        .btn-normal {
            border-color: #475569;
            color: #475569;
        }

        /* Customer Info Card */
        .customer-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px 24px;
            margin-bottom: 14px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, .05);
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
            box-shadow: 0 1px 5px rgba(0, 0, 0, .05);
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

        .rincian-card .rincian-body {
            padding: 20px;
        }

        /* Upload Areas */
        .upload-zone {
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            min-height: 220px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all .2s;
            position: relative;
            background: #fafafa;
            text-align: center;
            padding: 12px;
        }

        .upload-zone:hover {
            border-color: #3b82f6;
            background: #eff6ff;
        }

        .upload-zone.has-file {
            border-style: solid;
            border-color: #10b981;
            background: #fff;
        }

        .upload-zone input[type=file] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
        }

        .upload-zone img {
            max-height: 200px;
            width: 100%;
            border-radius: 8px;
            object-fit: contain;
        }

        .upload-zone .uz-icon {
            font-size: 28px;
            opacity: .4;
        }

        .upload-zone .uz-label {
            font-size: 11px;
            font-weight: 700;
            color: #6b7280;
            margin-top: 4px;
        }

        /* Tables in Rincian Card */
        .product-table-custom,
        .bahan-modal-table {
            border-collapse: separate;
            border-spacing: 0;
            font-size: 12px;
        }

        .product-table-custom thead tr th,
        .bahan-modal-table thead tr th {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            font-size: 11px;
            font-weight: 700;
            text-align: center;
            letter-spacing: .4px;
            padding: 8px 6px;
        }

        .product-table-custom tbody td,
        .bahan-modal-table tbody td {
            border: 1px solid #e2e8f0;
            padding: 6px 8px;
            vertical-align: middle;
        }

        .product-table-custom .form-control,
        .bahan-modal-table .form-control {
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
            flex-wrap: wrap;
            gap: 10px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, .05);
        }

        /* Colon separator style */
        .colon-label {
            display: grid;
            grid-template-columns: auto 12px 1fr;
            align-items: center;
            gap: 0;
        }

        .colon-label label {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .7px;
            text-transform: uppercase;
            color: #6b7280;
            white-space: nowrap;
        }

        .colon-label .colon {
            font-weight: 700;
            color: #9ca3af;
            text-align: center;
        }

        .btn-bahan-trigger,
        .btn-tahap-trigger {
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

        {{-- Datalist Autocomplete SKU Master --}}
        <datalist id="master_skus_datalist">
            @foreach ($products->take(10) as $p)
                <option value="{{ $p->sku }}">{{ $p->name }} @if ($p->ukuran)
                        ({{ $p->ukuran }})
                    @endif
                </option>
                @if ($p->sku_induk && $p->sku_induk !== $p->sku)
                    <option value="{{ $p->sku_induk }}">{{ $p->name }} (Induk)</option>
                @endif
            @endforeach
        </datalist>

        {{-- Datalist Autocomplete Nama Produk Master --}}
        <datalist id="master_product_names_datalist">
            @foreach ($products->take(10) as $p)
                <option value="{{ $p->name }}">{{ $p->sku ? $p->sku . ' — ' : '' }}@if ($p->ukuran)
                        (Ukuran: {{ $p->ukuran }})
                    @endif
                </option>
            @endforeach
        </datalist>

        {{-- Datalist Autocomplete Inventory Items --}}
        <datalist id="inventory_items_datalist">
            @foreach ($inventoryItems->take(10) as $invItemName)
                <option value="{{ $invItemName }}"></option>
            @endforeach
        </datalist>

        {{-- Datalist Autocomplete Vendors per Role --}}
        <datalist id="pemotong_datalist">
            @foreach ($pemotongList as $vName)
                <option value="{{ $vName }}"></option>
            @endforeach
        </datalist>

        <datalist id="penjahit_datalist">
            @foreach ($penjahitList as $vName)
                <option value="{{ $vName }}"></option>
            @endforeach
        </datalist>

        <datalist id="vendor_kancing_datalist">
            @foreach ($vendorKancingList as $vName)
                <option value="{{ $vName }}"></option>
            @endforeach
        </datalist>

        <datalist id="petugas_qc_datalist">
            @foreach ($petugasQcList as $vName)
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

        {{-- ── PAGE HEADER (PURE BOOTSTRAP 5) ── --}}
        <div class="card border shadow-sm mb-3">
            <div class="card-body py-3 px-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                            <a href="{{ route('spks.index') }}" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Kembali
                            </a>
                            <span
                                class="badge {{ $spk->tipe_spk === 'stok_gudang' ? 'bg-info bg-opacity-10 text-info border border-info border-opacity-25' : 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25' }}">
                                <i
                                    class="fas {{ $spk->tipe_spk === 'stok_gudang' ? 'fa-warehouse' : 'fa-shopping-cart' }} me-1"></i>
                                {{ $spk->tipe_spk === 'stok_gudang' ? 'Produksi Stok Gudang' : 'Pesanan Klien' }}
                            </span>
                            @if ($spk->is_urgent)
                                <span
                                    class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 fw-bold">
                                    <i class="fas fa-bolt me-1"></i> URGENT
                                </span>
                            @endif
                        </div>
                        <h5 class="fw-bold text-dark mb-0">
                            <i class="fas fa-file-invoice text-primary me-1"></i> Detail &amp; Edit SPK
                            #{{ $spk->no_spk }}
                        </h5>
                        <p class="text-muted small mb-0 mt-1">Kelola data rincian produksi, bahan, dan status SPK.</p>
                    </div>

                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        {{-- Tombol Scan Penerimaan (Aksi Kunci) --}}
                        <a href="{{ route('spks.scan_pickup', $spk->id) }}"
                            class="btn btn-sm btn-primary fw-bold px-3 shadow-sm d-inline-flex align-items-center gap-1">
                            <i class="fas fa-qrcode"></i> Scan Penerimaan
                        </a>

                        {{-- Dropdown Cetak & Label --}}
                        <div class="dropdown">
                            <button
                                class="btn btn-sm btn-outline-secondary dropdown-toggle px-3 d-inline-flex align-items-center gap-1"
                                type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-print text-primary"></i> Cetak
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                <li>
                                    <a class="dropdown-item py-2 small fw-semibold" href="{{ route('spks.print', $spk) }}"
                                        target="_blank">
                                        <i class="fas fa-file-invoice text-primary me-2"></i> Cetak Lembar SPK
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item py-2 small fw-semibold"
                                        href="{{ route('spks.print_labels', $spk->id) }}" target="_blank">
                                        <i class="fas fa-tags text-dark me-2"></i> Cetak Label Stiker Kemasan
                                    </a>
                                </li>
                            </ul>
                        </div>

                        {{-- Dropdown Bagikan / Customer Link --}}
                        <div class="dropdown">
                            <button
                                class="btn btn-sm btn-outline-secondary dropdown-toggle px-3 d-inline-flex align-items-center gap-1"
                                type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-share-nodes text-info"></i> Bagikan
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                <li>
                                    <a class="dropdown-item py-2 small fw-semibold"
                                        href="{{ route('spks.customer_track', $spk->no_produksi ?: $spk->id) }}"
                                        target="_blank">
                                        <i class="fas fa-mobile-screen text-info me-2"></i> Buka Link Tracking Customer
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item py-2 small fw-semibold text-success"
                                        href="https://wa.me/?text={{ rawurlencode('Halo ' . ($spk->pemesan ?: 'Customer') . ', berikut link tracking status pengerjaan pesanan Anda: ' . route('spks.customer_track', $spk->no_produksi ?: $spk->id)) }}"
                                        target="_blank">
                                        <i class="fab fa-whatsapp me-2"></i> Kirim Link via WhatsApp
                                    </a>
                                </li>
                            </ul>
                        </div>

                        {{-- Dropdown Opsi Lainnya (Tambah SPK & Hapus) --}}
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle px-2" type="button"
                                data-bs-toggle="dropdown" aria-expanded="false" title="Menu Lainnya">
                                <i class="fas fa-ellipsis-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                <li>
                                    <a class="dropdown-item py-2 small fw-semibold text-success"
                                        href="{{ route('spks.create', [
                                            'no_produksi' => $spk->no_produksi ?: $spk->no_spk,
                                            'no_pesanan' => $spk->no_pesanan,
                                            'pemesan' => $spk->pemesan,
                                            'no_hp_pemesan' => $spk->no_hp_pemesan,
                                            'instansi' => $spk->instansi,
                                            'order_id' => $spk->order_id,
                                        ]) }}">
                                        <i class="fas fa-plus-circle me-2"></i> Tambah SPK Baru
                                    </a>
                                </li>
                                <li>
                                    <hr class="dropdown-divider my-1">
                                </li>
                                <li>
                                    <form action="{{ route('spks.destroy', $spk) }}" method="POST" class="m-0"
                                        onsubmit="return confirm('Apakah Anda yakin ingin menghapus data SPK ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="dropdown-item py-2 small fw-semibold text-danger">
                                            <i class="fas fa-trash-alt me-2"></i> Hapus SPK
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>

                        {{-- Tombol Simpan Perubahan (Aksi Utama Form) --}}
                        <button type="submit" form="spkForm"
                            class="btn btn-sm btn-success fw-bold px-3 shadow-sm d-inline-flex align-items-center gap-1">
                            <i class="fas fa-save"></i> Simpan Perubahan
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── SPK SIBLING TABS (semua SPK dalam 1 Nomor Produksi) ── --}}
        <div class="mb-3">
            <div class="d-flex align-items-center gap-2 flex-wrap p-3 rounded-3 border bg-white shadow-sm"
                style="border-color:#e5e7eb!important">
                <span class="text-muted fw-semibold" style="font-size:12px; white-space:nowrap">
                    📦 Produksi <strong>{{ $spk->no_produksi ?: $spk->no_spk }}</strong> — {{ $siblingSpks->count() }}
                    SPK:
                </span>
                @foreach ($siblingSpks as $idx => $sib)
                    @php
                        $tabTitle = !empty($sib->kategori) ? $sib->kategori : 'SPK #' . ($idx + 1);
                    @endphp
                    <a href="{{ route('spks.show', $sib->id) }}"
                        class="btn btn-sm fw-bold px-3 rounded-pill {{ $sib->id === $spk->id ? 'btn-primary' : 'btn-outline-secondary' }}"
                        style="font-size:12px;" title="{{ $sib->no_spk }}">
                        🏷️ {{ $tabTitle }}
                    </a>
                @endforeach
                <a href="{{ route('spks.create', [
                    'no_produksi' => $spk->no_produksi ?: $spk->no_spk,
                    'no_pesanan' => $spk->no_pesanan,
                    'pemesan' => $spk->pemesan,
                    'no_hp_pemesan' => $spk->no_hp_pemesan,
                    'instansi' => $spk->instansi,
                    'order_id' => $spk->order_id,
                ]) }}"
                    class="btn btn-sm btn-success fw-bold px-3 rounded-pill" style="font-size:12px;">
                    ✚ Tambah SPK Baru
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show rounded-3 mb-3" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

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

        <form action="{{ route('spks.update', $spk) }}" method="POST" enctype="multipart/form-data" id="spkForm">
            @csrf
            @method('PUT')

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
                                    list="existing_no_produksi_list" autocomplete="off"
                                    placeholder="Kosongkan = DRAFT, atau pilih / ketik kode"
                                    value="{{ old('no_produksi', $spk->no_produksi) }}" style="font-size:13px;">
                                <datalist id="existing_no_produksi_list">
                                    @foreach ($existingNoProduksi as $existCode)
                                        <option value="{{ $existCode }}">{{ $existCode }}</option>
                                    @endforeach
                                </datalist>
                                <div class="mt-1" id="produksi-status-display">
                                    @if ($spk->no_produksi)
                                        <span class="draft-badge is-filled" id="produksi-badge">✅
                                            {{ $spk->no_produksi }}</span>
                                    @else
                                        <span class="draft-badge" id="produksi-badge">🕐 KOSONG = DRAFT</span>
                                    @endif
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
                                class="form-control form-control-sm" placeholder="Nomor / referensi pesanan klien"
                                value="{{ old('no_pesanan', $spk->no_pesanan ?: $spk->no_spk) }}"
                                style="font-size:13px;">
                        </div>
                    </div>

                    {{-- Center: DESAIN area --}}
                    @php
                        $displayDesainUrl = $spk->image_url ?: ($spk->mockup_url ?: $spk->referensi_klien_url);
                    @endphp
                    <div class="col-lg-6 col-md-4">
                        <div class="desain-drop-area {{ $displayDesainUrl ? 'has-image' : '' }}" id="desain-drop-area">
                            <input type="file" name="image" id="input-spk-image" accept="image/*">
                            <div id="desain-placeholder-content" class="{{ $displayDesainUrl ? 'd-none' : '' }}">
                                <div class="desain-label">DESAIN</div>
                                <div class="desain-hint">Klik atau seret foto desain/mockup ke sini</div>
                                <div class="mt-2">
                                    <small class="badge bg-light text-secondary border" style="font-size:10px;">JPEG / PNG
                                        / JPG · maks 4MB</small>
                                </div>
                            </div>
                            <img id="desain-preview-img" src="{{ $displayDesainUrl ?: '' }}" alt="Preview Desain"
                                class="{{ $displayDesainUrl ? '' : 'd-none' }}"
                                style="max-height:240px; width:100%; border-radius:10px; object-fit:contain;">
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
                            <input type="date" name="tanggal" class="form-control form-control-sm" required
                                value="{{ old('tanggal', $spk->tanggal ? $spk->tanggal->format('Y-m-d') : date('Y-m-d')) }}"
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
                                value="{{ old('deadline', $spk->deadline ? $spk->deadline->format('Y-m-d') : '') }}"
                                style="font-size:13px; color:#dc2626;">
                        </div>
                    </div>
                </div>

                {{-- ── TYPE TABS + PRIORITY TOGGLE ── --}}
                <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 pt-3"
                    style="border-top: 1px solid #e5e7eb;">
                    {{-- Tipe SPK Tabs --}}
                    <div class="spk-type-tabs btn-group" role="group">
                        <input type="radio" name="tipe_spk" class="btn-check" id="tipe_pesanan"
                            value="pesanan_pelanggan"
                            {{ old('tipe_spk', $spk->tipe_spk ?: 'pesanan_pelanggan') === 'pesanan_pelanggan' ? 'checked' : '' }}>
                        <label class="btn btn-outline-primary" for="tipe_pesanan">
                            🛒 PESANAN / CUSTOM
                        </label>

                        <input type="radio" name="tipe_spk" class="btn-check" id="tipe_stok" value="stok_gudang"
                            {{ old('tipe_spk', $spk->tipe_spk) === 'stok_gudang' ? 'checked' : '' }}>
                        <label class="btn btn-outline-secondary" for="tipe_stok">
                            🏬 PRODUKSI STOK
                        </label>
                    </div>

                    {{-- Priority Toggle --}}
                    <div class="spk-priority-toggle btn-group mt-2 mt-md-0" role="group">
                        <input type="radio" name="is_urgent" class="btn-check" id="priority_normal" value="0"
                            {{ old('is_urgent', $spk->is_urgent ? '1' : '0') == '0' ? 'checked' : '' }}>
                        <label class="btn btn-normal" for="priority_normal">
                            ✓ NORMAL
                        </label>

                        <input type="radio" name="is_urgent" class="btn-check" id="priority_urgent" value="1"
                            {{ old('is_urgent', $spk->is_urgent ? '1' : '0') == '1' ? 'checked' : '' }}>
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
                            value="{{ old('pemesan', $spk->pemesan) }}">
                    </div>
                    <div class="col-md-6">
                        <div class="section-label">NAMA TOKO / INSTANSI</div>
                        <select name="instansi" class="form-select form-select-sm">
                            <option value="">— Pilih atau ketik toko —</option>
                            @php $selectedStore = old('instansi', $spk->instansi); @endphp
                            @foreach ($stores as $st)
                                @php $sName = $st->store_name . ($st->channel ? ' (' . $st->channel->name . ')' : ''); @endphp
                                <option value="{{ $st->store_name }}"
                                    {{ $selectedStore == $st->store_name ? 'selected' : '' }}>{{ $sName }}</option>
                            @endforeach
                            <option value="POS / Penjualan Offline"
                                {{ $selectedStore == 'POS / Penjualan Offline' ? 'selected' : '' }}>POS / Penjualan Offline
                            </option>
                            <option value="Pesanan Direct / Whatsapp"
                                {{ $selectedStore == 'Pesanan Direct / Whatsapp' ? 'selected' : '' }}>Pesanan Direct /
                                Whatsapp</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <div class="section-label">NO WHATSAPP KLIEN</div>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">📱</span>
                            <input type="text" name="no_hp_pemesan" class="form-control" placeholder="0852-xxxx-xxxx"
                                value="{{ old('no_hp_pemesan', $spk->no_hp_pemesan) }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="section-label">NAMA PIC / ADMIN</div>
                        <input type="text" name="nama_pic" class="form-control form-control-sm"
                            placeholder="Nama admin yang menginput"
                            value="{{ old('nama_pic', $spk->nama_pic ?: $spk->penginput->name ?? '') }}">
                    </div>
                </div>

                {{-- Catatan Tambahan --}}
                <div class="mt-3">
                    <div class="section-label">CATATAN TAMBAHAN / KETERANGAN</div>
                    <textarea name="tambahan" class="form-control form-control-sm" rows="3"
                        placeholder="Tulis instruksi desain, keterangan khusus, atau pesan untuk tim produksi di sini...">{{ old('tambahan', $spk->tambahan) }}</textarea>
                </div>

                {{-- Tahapan Saat Ini --}}
                <div class="mt-3">
                    <div class="section-label mb-1">TAHAPAN SAAT INI</div>
                    <div class="tahap-select-wrap">
                        <select name="tahap_saat_ini" id="tahap_saat_ini_select" class="form-select tahap-select">
                            @php
                                $tahapanList = \App\Models\Spk::TAHAPAN;
                                $selectedTahap = old(
                                    'tahap_saat_ini',
                                    $spk->status ?: ($spk->tahap_saat_ini ?: 'Perencanaan'),
                                );
                            @endphp
                            @foreach ($tahapanList as $key => $info)
                                <option value="{{ $key }}" {{ $selectedTahap === $key ? 'selected' : '' }}>
                                    {{ $info['emoji'] }} {{ $info['label'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <small class="text-muted d-block mt-1" style="font-size:11px;">
                        💡 Ubah tahapan produksi untuk memperbarui progress SPK
                    </small>
                </div>
            </div>

            {{-- ══════════════════════════════════════════════════════════════════
             SECTION 3: DETAIL RINCIAN PRODUK (SPK)
        ══════════════════════════════════════════════════════════════════ --}}
            <div id="rincianContainer">
                @php $rIdx = 0; @endphp
                <div class="rincian-card" id="rincian-block-{{ $rIdx }}">
                    <div class="rincian-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span>📋 DETAIL RINCIAN PRODUK (SPK #{{ $spk->no_spk }})</span>
                        @php
                            $sumEstKainHeader = (float) $spk->items->sum('est_kain');
                            if ($sumEstKainHeader <= 0 && $spk->items->first()) {
                                $sumEstKainHeader = (float) ($spk->items->first()->est_kain ?? 0);
                            }
                        @endphp
                        @if ($sumEstKainHeader > 0)
                            <span class="badge bg-white text-primary border px-2.5 py-1.5 rounded-pill shadow-2xs" style="font-size: 11px; font-weight: 700;">
                                <i class="fas fa-ruler-combined me-1"></i> TOTAL ESTIMASI KAIN: {{ number_format($sumEstKainHeader, 2, ',', '.') }} M / KG
                            </span>
                        @endif
                    </div>
                    <div class="rincian-body">

                        {{-- Kategori Produk & Link File Mentah --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <div class="section-label mb-1"
                                    style="font-size:10px; font-weight:700; letter-spacing:.7px; text-transform:uppercase; color:#4f46e5;">
                                    🏷️ KATEGORI PRODUK
                                </div>
                                <input type="text" name="kategori" class="form-control form-control-sm"
                                    list="kategori_datalist" style="font-size:12px;"
                                    value="{{ old('kategori', $spk->kategori) }}"
                                    placeholder="Contoh: Baju Olah Raga, Jaket, Seragam...">
                            </div>
                            <div class="col-md-6">
                                <div class="section-label mb-1"
                                    style="font-size:10px; font-weight:700; letter-spacing:.7px; text-transform:uppercase; color:#16a34a;">
                                    🔗 LINK FILE MENTAH (G-DRIVE / DROPBOX)
                                </div>
                                <input type="url" name="link_file_mentah" class="form-control form-control-sm"
                                    style="font-size:12px;" value="{{ old('link_file_mentah', $spk->link_file_mentah) }}"
                                    placeholder="Paste link G-Drive / Dropbox...">
                            </div>
                        </div>

                        {{-- Upload Foto Referensi & Mockup Final --}}
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="section-label">REFERENSI KLIEN (FOTO / SKETSA)</div>
                                <div class="upload-zone {{ $spk->referensi_klien_url ? 'has-file' : '' }}"
                                    id="ref-drop-zone">
                                    <input type="file" name="referensi_klien" id="input-referensi-klien"
                                        class="input-referensi" accept="image/*">
                                    <img id="ref-preview-img" src="{{ $spk->referensi_klien_url ?: '' }}"
                                        alt="Referensi Klien" class="{{ $spk->referensi_klien_url ? '' : 'd-none' }}"
                                        style="max-height:200px; width:100%; border-radius:8px; object-fit:contain;">
                                    <div id="ref-placeholder-content"
                                        class="{{ $spk->referensi_klien_url ? 'd-none' : '' }}">
                                        <div class="uz-icon">🖼️</div>
                                        <div class="uz-label">Upload foto referensi / sketsa pakaian</div>
                                        <small class="text-muted" style="font-size:10px;">Format JPG/PNG maks 8MB</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="section-label">MOCKUP FINAL (ACC KLIEN)</div>
                                <div class="upload-zone {{ $spk->mockup_url ? 'has-file' : '' }}" id="mockup-drop-zone">
                                    <input type="file" name="mockup_final" id="input-mockup-final"
                                        class="input-mockup" accept="image/*">
                                    <img id="mockup-preview-img" src="{{ $spk->mockup_url ?: '' }}" alt="Mockup Final"
                                        class="{{ $spk->mockup_url ? '' : 'd-none' }}"
                                        style="max-height:200px; width:100%; border-radius:8px; object-fit:contain;">
                                    <div id="mockup-placeholder-content" class="{{ $spk->mockup_url ? 'd-none' : '' }}">
                                        <div class="uz-icon">✨</div>
                                        <div class="uz-label">Upload gambar mockup hasil desain final</div>
                                        <small class="text-muted" style="font-size:10px;">Format JPG/PNG maks 8MB</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @php
                            $initGrandQty = (int) $spk->items->sum('quantity');
                            $initTotalBahan = array_sum(array_column($spkBahanData, 'subtotal'));
                            $initBiayaProduksi = (float) ($existingBiayaProduksi ?? 0);
                            $initBiayaTambahan = (float) ($existingBiayaTambahan ?? 0);
                            $initGrandHpp = $initTotalBahan + $initBiayaProduksi + $initBiayaTambahan;
                            $initAvgHpp = $initGrandQty > 0 ? round($initGrandHpp / $initGrandQty) : 0;
                        @endphp

                        {{-- Tabel Produk & Variasi Ukuran --}}
                        <div class="table-responsive rounded-3 border bg-white mb-3">
                            <table class="table table-sm product-table-custom align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 32%;">SKU PRODUK / VARIAN</th>
                                        <th style="width: 38%;">NAMA PRODUK</th>
                                        <th style="width: 13%;" class="text-center">UKURAN</th>
                                        <th style="width: 13%;" class="text-center">QTY</th>
                                        <th style="width: 4%;" class="text-center"></th>
                                    </tr>
                                </thead>
                                <tbody id="product-tbody-{{ $rIdx }}">
                                    @foreach ($spk->items as $pIdx => $item)
                                        <tr id="product-row-{{ $rIdx }}-{{ $pIdx }}"
                                            data-r-idx="{{ $rIdx }}" data-p-idx="{{ $pIdx }}">
                                            <td>
                                                <input type="text"
                                                    name="rincian[{{ $rIdx }}][produk][{{ $pIdx }}][sku_produk]"
                                                    class="form-control font-monospace fw-bold row-sku-produk"
                                                    list="master_skus_datalist" autocomplete="off"
                                                    placeholder="Pilih SKU..."
                                                    value="{{ $item->sku ?: $item->sku_induk }}">
                                            </td>
                                            <td>
                                                <input type="text"
                                                    name="rincian[{{ $rIdx }}][produk][{{ $pIdx }}][nama_produk]"
                                                    class="form-control row-nama-produk"
                                                    list="master_product_names_datalist" autocomplete="off"
                                                    placeholder="Nama produk..." value="{{ $item->nama_produk }}">
                                            </td>
                                            <td>
                                                <input type="text"
                                                    name="rincian[{{ $rIdx }}][produk][{{ $pIdx }}][ukuran]"
                                                    class="form-control text-center row-ukuran"
                                                    list="ukuran_datalist"
                                                    placeholder="S, M, L..."
                                                    value="{{ $item->ukuran }}">
                                            </td>
                                            <td>
                                                <input type="number"
                                                    name="rincian[{{ $rIdx }}][produk][{{ $pIdx }}][qty_produksi]"
                                                    class="form-control text-center fw-bold row-qty-produksi"
                                                    min="1" value="{{ $item->quantity }}">
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-outline-danger border-0 btn-remove-product-row" title="Hapus Varian">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div class="p-2 bg-light border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <button type="button"
                                    class="btn btn-sm btn-success fw-bold px-3 py-1 text-uppercase rounded-3"
                                    onclick="addNewProductRow({{ $rIdx }})">
                                    <i class="fas fa-plus-circle me-1"></i> + Tambah Produk / Varian Baru
                                </button>

                                @php
                                    $totEstKain = (float) $spk->items->sum('est_kain');
                                    if ($totEstKain <= 0 && $spk->items->first()) {
                                        $totEstKain = (float) ($spk->items->first()->est_kain ?? 0);
                                    }
                                @endphp
                                <div class="d-flex align-items-center gap-2 px-2 flex-wrap">
                                    <span class="badge bg-white text-dark border px-3 py-2 rounded-3 shadow-2xs" style="font-size: 11.5px;">
                                        <i class="fas fa-box text-primary me-1"></i> Total Qty: <strong>{{ number_format($spk->items->sum('quantity')) }} Pcs</strong>
                                    </span>
                                    @if ($totEstKain > 0)
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2 rounded-3" style="font-size: 11.5px;">
                                            <i class="fas fa-ruler-combined me-1"></i> TOTAL ESTIMASI KAIN: <strong>{{ number_format($totEstKain, 2, ',', '.') }} M / Kg</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- ══════════════════════════════════════════════════════════════════
                             KARTU BIAYA & ESTIMASI HPP LEVEL SPK
                        ══════════════════════════════════════════════════════════════════ --}}
                        <div class="card shadow-sm rounded-3 mb-3 border-0 overflow-hidden" style="background: #ffffff; border: 1px solid #e2e8f0 !important;">
                            <div class="card-header py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2" style="background: linear-gradient(135deg, #1e293b, #0f172a); color: #ffffff;">
                                <div>
                                    <h6 class="m-0 fw-bold d-flex align-items-center gap-2 text-white" style="font-size: 14px; letter-spacing: 0.3px;">
                                        <i class="fas fa-calculator text-warning"></i> BIAYA PRODUKSI &amp; ESTIMASI HPP (LEVEL SPK)
                                    </h6>
                                    <small class="text-white-50" style="font-size: 11px;">
                                        Input bahan baku (BB-TH), biaya pengerjaan, dan biaya tambahan dihitung terpusat untuk SPK ini.
                                    </small>
                                </div>
                                <span class="badge bg-warning text-dark fw-bold px-2.5 py-1.5 rounded-pill" style="font-size: 11px;">
                                    SPK Level Costing
                                </span>
                            </div>
                            <div class="card-body p-4" style="background: #f8fafc;">
                                
                                {{-- BAGIAN 1: PAKAI BAHAN SPK (BB-TH / BAHAN BAKU) --}}
                                <div class="bg-white p-3 rounded-3 border mb-3 shadow-2xs">
                                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                                        <div>
                                            <span class="fw-bold text-dark text-uppercase d-flex align-items-center gap-1.5" style="font-size: 12px;">
                                                <i class="fas fa-layer-group text-primary"></i> 1. Pakai Bahan SPK (BB-TH / Bahan Baku)
                                            </span>
                                            <small class="text-muted d-block" style="font-size: 10.5px;">Bahan yang dialokasikan khusus untuk pengerjaan SPK ini</small>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-primary fw-bold px-2.5 py-1" onclick="addSpkBahanRow()">
                                            <i class="fas fa-plus me-1"></i> Tambah Bahan
                                        </button>
                                    </div>
                                    
                                    <div class="table-responsive rounded-2 border">
                                        <table class="table table-sm align-middle mb-0" style="font-size: 11.5px;">
                                            <thead class="table-light text-uppercase fw-semibold" style="font-size: 10.5px; color: #475569;">
                                                <tr>
                                                    <th style="width: 34%;">Nama Bahan / Kain</th>
                                                    <th style="width: 14%;" class="text-center">Qty Bahan</th>
                                                    <th style="width: 14%;" class="text-center">Satuan</th>
                                                    <th style="width: 18%;" class="text-end">Harga Satuan (Rp)</th>
                                                    <th style="width: 16%;" class="text-end">Subtotal (Rp)</th>
                                                    <th style="width: 4%;" class="text-center"></th>
                                                </tr>
                                            </thead>
                                            <tbody id="spkBahanTableBody">
                                                @forelse($spkBahanData as $bIdx => $bItem)
                                                    <tr class="spk-bahan-row" data-b-idx="{{ $bIdx }}">
                                                        <td>
                                                            <input type="text" name="spk_bahan[{{ $bIdx }}][nama_bahan]" 
                                                                   class="form-control form-control-sm fw-bold spk-bahan-nama" 
                                                                   list="inventory_items_datalist" autocomplete="off"
                                                                   placeholder="Contoh: BB-TH / Cotton Combed 30s..."
                                                                   value="{{ $bItem['nama_bahan'] ?? 'BB-TH' }}">
                                                        </td>
                                                        <td>
                                                            <input type="text" name="spk_bahan[{{ $bIdx }}][qty_bahan]" 
                                                                   class="form-control form-control-sm text-center spk-bahan-qty" 
                                                                   placeholder="1"
                                                                   value="{{ $bItem['qty_bahan'] ?? 1 }}">
                                                        </td>
                                                        <td>
                                                            <input type="text" name="spk_bahan[{{ $bIdx }}][satuan]" 
                                                                   class="form-control form-control-sm text-center spk-bahan-satuan" 
                                                                   placeholder="Roll / Kg / Mtr"
                                                                   value="{{ $bItem['satuan'] ?? 'Roll' }}">
                                                        </td>
                                                        <td>
                                                            <div class="input-group input-group-sm">
                                                                <span class="input-group-text px-1 bg-light text-muted" style="font-size: 10px;">Rp</span>
                                                                <input type="text" name="spk_bahan[{{ $bIdx }}][harga]" 
                                                                       class="form-control form-control-sm text-end fw-bold spk-bahan-harga numeric-dot-format" 
                                                                       placeholder="0"
                                                                       value="{{ number_format($bItem['harga'] ?? 0, 0, ',', '.') }}">
                                                            </div>
                                                        </td>
                                                        <td class="text-end">
                                                            <input type="hidden" name="spk_bahan[{{ $bIdx }}][subtotal]" 
                                                                   class="spk-bahan-subtotal-val" 
                                                                   value="{{ $bItem['subtotal'] ?? 0 }}">
                                                            <span class="fw-bold text-primary spk-bahan-subtotal-text">
                                                                Rp {{ number_format($bItem['subtotal'] ?? 0, 0, ',', '.') }}
                                                            </span>
                                                        </td>
                                                        <td class="text-center">
                                                            <button type="button" class="btn btn-sm btn-link text-danger p-0 btn-remove-spk-bahan" title="Hapus Bahan">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr class="spk-bahan-row" data-b-idx="0">
                                                        <td>
                                                            <input type="text" name="spk_bahan[0][nama_bahan]" 
                                                                   class="form-control form-control-sm fw-bold spk-bahan-nama" 
                                                                   list="inventory_items_datalist" autocomplete="off"
                                                                   placeholder="Contoh: BB-TH..."
                                                                   value="BB-TH">
                                                        </td>
                                                        <td>
                                                            <input type="text" name="spk_bahan[0][qty_bahan]" 
                                                                   class="form-control form-control-sm text-center spk-bahan-qty" 
                                                                   placeholder="1" value="1">
                                                        </td>
                                                        <td>
                                                            <input type="text" name="spk_bahan[0][satuan]" 
                                                                   class="form-control form-control-sm text-center spk-bahan-satuan" 
                                                                   placeholder="Roll / Kg" value="Roll">
                                                        </td>
                                                        <td>
                                                            <div class="input-group input-group-sm">
                                                                <span class="input-group-text px-1 bg-light text-muted" style="font-size: 10px;">Rp</span>
                                                                <input type="text" name="spk_bahan[0][harga]" 
                                                                       class="form-control form-control-sm text-end fw-bold spk-bahan-harga numeric-dot-format" 
                                                                       placeholder="0" value="0">
                                                            </div>
                                                        </td>
                                                        <td class="text-end">
                                                            <input type="hidden" name="spk_bahan[0][subtotal]" 
                                                                   class="spk-bahan-subtotal-val" value="0">
                                                            <span class="fw-bold text-primary spk-bahan-subtotal-text">
                                                                Rp 0
                                                            </span>
                                                        </td>
                                                        <td class="text-center">
                                                            <button type="button" class="btn btn-sm btn-link text-danger p-0 btn-remove-spk-bahan" title="Hapus Bahan">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                            <tfoot class="table-light">
                                                <tr>
                                                    <td colspan="4" class="text-end fw-bold text-muted py-2" style="font-size: 11px;">
                                                        TOTAL BIAYA BAHAN SPK:
                                                    </td>
                                                    <td class="text-end fw-extrabold text-primary py-2" id="spkTotalBahanFooter" style="font-size: 12px;">
                                                        Rp {{ number_format($initTotalBahan, 0, ',', '.') }}
                                                    </td>
                                                    <td></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>

                                {{-- BAGIAN 2 & 3: BIAYA PRODUKSI & BIAYA TAMBAHAN SPK (SIDE BY SIDE) --}}
                                <div class="row g-3 mb-3">
                                    {{-- BIAYA PRODUKSI --}}
                                    <div class="col-md-6">
                                        <div class="bg-white p-3 rounded-3 border h-100 shadow-2xs">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <span class="fw-bold text-dark text-uppercase d-flex align-items-center gap-1.5" style="font-size: 12px;">
                                                    <i class="fas fa-cut text-warning"></i> 2. Biaya Produksi SPK
                                                </span>
                                                <span class="badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning-subtle" style="font-size: 10px;">
                                                    Jasa / Ongkos
                                                </span>
                                            </div>
                                            <label class="form-label text-muted small mb-1" style="font-size: 11px;">
                                                Total Ongkos Jasa Produksi (Potong, Jahit, Sablon, Bordir, Finishing, dll.):
                                            </label>
                                            <div class="input-group input-group-sm mb-2">
                                                <span class="input-group-text bg-light text-muted fw-bold">Rp</span>
                                                <input type="text" name="spk_biaya_produksi" id="spk_biaya_produksi"
                                                       class="form-control form-control-sm text-end fw-bold fs-6 numeric-dot-format input-spk-biaya-produksi"
                                                       placeholder="0"
                                                       value="{{ number_format($existingBiayaProduksi ?? 0, 0, ',', '.') }}">
                                            </div>
                                            <div class="mt-2 p-2 bg-light rounded border">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="small fw-semibold text-muted" style="font-size: 11px;">Status Pembayaran:</span>
                                                    {!! $spk->production_payment_status_badge !!}
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center mb-1" style="font-size: 11px;">
                                                    <span class="text-muted">Sudah Dibayar: <strong class="text-success font-monospace">Rp {{ number_format($spk->total_paid_production, 0, ',', '.') }}</strong></span>
                                                    <span class="text-muted">Sisa: <strong class="text-danger font-monospace">Rp {{ number_format($spk->remaining_production_cost, 0, ',', '.') }}</strong></span>
                                                </div>
                                                <div class="progress mb-2" style="height: 5px;">
                                                    <div class="progress-bar {{ $spk->production_payment_status === 'paid' ? 'bg-success' : 'bg-warning' }}"
                                                         style="width: {{ $spk->production_payment_percentage }}%;"></div>
                                                </div>
                                                <button type="button" class="btn btn-xs btn-warning text-dark fw-bold w-100 py-1"
                                                        data-bs-toggle="modal" data-bs-target="#modalPayLabor">
                                                    <i class="fas fa-wallet me-1"></i>Bayar / Catat Cicilan Produksi
                                                </button>
                                            </div>
                                            <div class="small text-muted mt-1" style="font-size: 10px;">
                                                💡 Biaya ini dicatat ke ongkos pengerjaan vendor dan dapat dibayarkan / dicicil.
                                            </div>
                                        </div>
                                    </div>

                                    {{-- BIAYA TAMBAHAN --}}
                                    <div class="col-md-6">
                                        <div class="bg-white p-3 rounded-3 border h-100 shadow-2xs">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <span class="fw-bold text-dark text-uppercase d-flex align-items-center gap-1.5" style="font-size: 12px;">
                                                    <i class="fas fa-box-open text-info"></i> 3. Biaya Tambahan SPK
                                                </span>
                                                <span class="badge bg-info bg-opacity-10 text-info border border-info-subtle" style="font-size: 10px;">
                                                    Aksesoris / Packing
                                                </span>
                                            </div>
                                            <div class="row g-2 mb-2">
                                                <div class="col-sm-5">
                                                    <label class="form-label text-muted small mb-1" style="font-size: 11px;">Nominal (Rp):</label>
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text bg-light text-muted fw-bold">Rp</span>
                                                        <input type="text" name="spk_biaya_tambahan" id="spk_biaya_tambahan"
                                                               class="form-control form-control-sm text-end fw-bold fs-6 numeric-dot-format input-spk-biaya-tambahan"
                                                               placeholder="0"
                                                               value="{{ number_format($existingBiayaTambahan ?? 0, 0, ',', '.') }}">
                                                    </div>
                                                </div>
                                                <div class="col-sm-7">
                                                    <label class="form-label text-muted small mb-1" style="font-size: 11px;">Keterangan Biaya Tambahan:</label>
                                                    <input type="text" name="spk_ket_tambahan" id="spk_ket_tambahan"
                                                           class="form-control form-control-sm"
                                                           placeholder="Contoh: Hangtag, Plastik OPP, Kancing..."
                                                           value="{{ $existingKetTambahan ?? '' }}">
                                                </div>
                                            </div>
                                            <div class="small text-muted" style="font-size: 10.5px;">
                                                💡 Biaya ekstra selain bahan utama &amp; jasa jahit yang ikut dibebankan ke HPP produk.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- BAGIAN 4: RINGKASAN AKUMULASI BIAYA & ESTIMASI HPP SPK --}}
                                <div class="p-3 rounded-3 border" style="background: linear-gradient(135deg, #ffffff, #f1f5f9);">
                                    <div class="text-muted text-uppercase fw-bold mb-2 d-flex align-items-center gap-1.5" style="font-size: 10.5px; letter-spacing: 0.5px;">
                                        <i class="fas fa-chart-pie text-success"></i> Ringkasan Akumulasi Biaya &amp; Estimasi HPP SPK
                                    </div>
                                    <div class="row g-2 text-center align-items-center">
                                        <div class="col-md-2 col-6 border-end">
                                            <span class="d-block text-muted text-uppercase fw-bold" style="font-size: 9.5px; letter-spacing:.5px;">TOTAL QTY SPK</span>
                                            <span class="fw-extrabold fs-6 text-dark" id="spkSummaryTotalQty">{{ number_format($initGrandQty, 0, ',', '.') }} pcs</span>
                                        </div>
                                        <div class="col-md-2 col-6 border-end">
                                            <span class="d-block text-muted text-uppercase fw-bold" style="font-size: 9.5px; letter-spacing:.5px;">TOTAL BIAYA BAHAN</span>
                                            <span class="fw-bold fs-6 text-primary" id="spkSummaryTotalBahan">Rp {{ number_format($initTotalBahan, 0, ',', '.') }}</span>
                                        </div>
                                        <div class="col-md-2 col-6 border-end">
                                            <span class="d-block text-muted text-uppercase fw-bold" style="font-size: 9.5px; letter-spacing:.5px;">BIAYA PRODUKSI</span>
                                            <span class="fw-bold fs-6 text-warning-emphasis" id="spkSummaryTotalBiayaProduksi">Rp {{ number_format($initBiayaProduksi, 0, ',', '.') }}</span>
                                        </div>
                                        <div class="col-md-2 col-6 border-end">
                                            <span class="d-block text-muted text-uppercase fw-bold" style="font-size: 9.5px; letter-spacing:.5px;">BIAYA TAMBAHAN</span>
                                            <span class="fw-bold fs-6 text-info text-dark" id="spkSummaryTotalBiayaTambahan">Rp {{ number_format($initBiayaTambahan, 0, ',', '.') }}</span>
                                        </div>
                                        <div class="col-md-2 col-6 border-end">
                                            <span class="d-block text-muted text-uppercase fw-bold" style="font-size: 9.5px; letter-spacing:.5px;">GRAND TOTAL HPP</span>
                                            <span class="fw-extrabold fs-6 text-success" id="spkSummaryGrandTotalHpp">Rp {{ number_format($initGrandHpp, 0, ',', '.') }}</span>
                                        </div>
                                        <div class="col-md-2 col-12">
                                            <span class="d-block text-muted text-uppercase fw-bold" style="font-size: 9.5px; letter-spacing:.5px;">ESTIMASI HPP / PCS</span>
                                            <span class="fw-extrabold fs-6 text-success" id="spkSummaryAvgHpp">Rp {{ number_format($initAvgHpp, 0, ',', '.') }} / pcs</span>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- ══════════════════════════════════════════════════════════════════
             SECTION 4: STATUS PENERIMAAN & PENGAMBILAN BARANG (QR / BARCODE)
        ══════════════════════════════════════════════════════════════════ --}}
            @php
                $deskTotalTarget = (int) $spk->items->sum('quantity');
                $deskTotalDiambil = (int) $spk->items->sum('qty_diambil');
                $deskTotalSisa = (int) $spk->items->sum('sisa_qty');
                $deskPct = $deskTotalTarget > 0 ? min(100, round(($deskTotalDiambil / $deskTotalTarget) * 100)) : 0;
                $deskPickups = \App\Models\SpkItemPickup::whereIn('spk_item_id', $spk->items->pluck('id'))
                    ->with(['item', 'pemberi'])
                    ->orderByDesc('created_at')
                    ->take(15)
                    ->get();
            @endphp
            <div class="rincian-card mb-4" style="border-top: 4px solid #10b981;">
                <div class="rincian-header d-flex justify-content-between align-items-center flex-wrap gap-2"
                    style="background:#f0fdf4;">
                    <span class="text-success fw-bold d-flex align-items-center gap-2">
                        <i class="fas fa-boxes-packing fs-5"></i> STATUS PENERIMAAN &amp; PENGAMBILAN BARANG (HASIL
                        PRODUKSI)
                    </span>
                    <div class="d-flex gap-2">
                        <a href="{{ route('spks.print_labels', $spk->id) }}" target="_blank"
                            class="btn btn-sm btn-dark fw-bold px-3">
                            <i class="fas fa-tags me-1"></i> Cetak Label Stiker Kemasan
                        </a>
                        <a href="{{ route('spks.scan_pickup', $spk->id) }}"
                            class="btn btn-sm btn-success fw-bold px-3 shadow-sm d-inline-flex align-items-center gap-1.5"
                            style="background: linear-gradient(135deg, #059669, #10b981);">
                            <i class="fas fa-qrcode fs-6"></i> Buka Layar Scanner Penerimaan (QR / Barcode)
                        </a>
                    </div>
                </div>
                <div class="rincian-body">
                    <div class="row g-3 align-items-center mb-3">
                        <div class="col-md-3 col-6">
                            <div class="p-3 bg-light rounded-3 border text-center">
                                <span class="text-muted d-block small fw-bold text-uppercase"
                                    style="font-size: 10px;">Total Pesanan</span>
                                <span class="fs-5 fw-black text-dark">{{ $deskTotalTarget }} pcs</span>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="p-3 bg-success bg-opacity-10 rounded-3 border border-success-subtle text-center">
                                <span class="text-success d-block small fw-bold text-uppercase"
                                    style="font-size: 10px;">Sudah Diterima / Diambil</span>
                                <span class="fs-5 fw-black text-success">{{ $deskTotalDiambil }} pcs</span>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="p-3 bg-danger bg-opacity-10 rounded-3 border border-danger-subtle text-center">
                                <span class="text-danger d-block small fw-bold text-uppercase"
                                    style="font-size: 10px;">Sisa Belum Diterima</span>
                                <span class="fs-5 fw-black text-danger">{{ $deskTotalSisa }} pcs</span>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="p-3 bg-primary bg-opacity-10 rounded-3 border border-primary-subtle text-center">
                                <span class="text-primary d-block small fw-bold text-uppercase"
                                    style="font-size: 10px;">Progress Penyelesaian</span>
                                <span class="fs-5 fw-black text-primary">{{ $deskPct }}%</span>
                            </div>
                        </div>
                    </div>

                    {{-- Tabel Rincian per Ukuran --}}
                    <div class="table-responsive rounded-3 border mb-3">
                        <table class="table table-sm table-hover align-middle mb-0" style="font-size: 12px;">
                            <thead class="table-light text-uppercase">
                                <tr>
                                    <th>Item Produk</th>
                                    <th>SKU / Barcode</th>
                                    <th class="text-center" style="width: 12%;">Ukuran</th>
                                    <th class="text-center" style="width: 12%;">Target SPK</th>
                                    <th class="text-center" style="width: 14%;">Sudah Diambil</th>
                                    <th class="text-center" style="width: 14%;">Sisa SPK</th>
                                    <th class="text-center" style="width: 14%;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($spk->items as $item)
                                    @php
                                        $itTarget = (int) $item->quantity;
                                        $itDiambil = (int) $item->qty_diambil;
                                        $itSisa = (int) $item->sisa_qty;
                                        $itDone = $itSisa == 0;
                                    @endphp
                                    <tr class="{{ $itDone ? 'table-success bg-opacity-25' : '' }}">
                                        <td>
                                            <strong class="text-dark">{{ $item->nama_produk }}</strong>
                                        </td>
                                        <td>
                                            <code
                                                class="text-primary font-monospace">{{ $item->sku ?: $item->masterProduct->sku ?? '—' }}</code>
                                            @if ($item->masterProduct && $item->masterProduct->barcode)
                                                <span
                                                    class="text-muted ms-1">({{ $item->masterProduct->barcode }})</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $itDone ? 'bg-success' : 'bg-dark' }} px-2 py-1 fs-7">
                                                {{ $item->ukuran ?: 'ALL' }}
                                            </span>
                                        </td>
                                        <td class="text-center fw-bold">{{ $itTarget }} pcs</td>
                                        <td class="text-center fw-bold text-success">{{ $itDiambil }} pcs</td>
                                        <td class="text-center fw-bold {{ $itSisa > 0 ? 'text-danger' : 'text-muted' }}">
                                            {{ $itSisa }} pcs</td>
                                        <td class="text-center">
                                            @if ($itDone)
                                                <span class="badge bg-success px-2 py-1">✅ Lengkap</span>
                                            @else
                                                <span class="badge bg-warning text-dark px-2 py-1">⏳ Proses
                                                    ({{ $itDiambil }}/{{ $itTarget }})
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Riwayat Pengambilan Terkini --}}
                    @if ($deskPickups->isNotEmpty())
                        <div class="mt-3">
                            <span class="text-muted fw-bold d-block mb-1 text-uppercase" style="font-size: 11px;">
                                📜 Riwayat Pengambilan Terakhir:
                            </span>
                            <div class="table-responsive rounded border">
                                <table class="table table-sm table-striped align-middle mb-0" style="font-size: 11px;">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Waktu Ambil</th>
                                            <th>Item &amp; Ukuran</th>
                                            <th class="text-center">Jumlah</th>
                                            <th>Nama Pengambil / Penerima</th>
                                            <th>Catatan</th>
                                            <th class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($deskPickups as $dp)
                                            <tr>
                                                <td class="text-muted">
                                                    {{ $dp->tanggal_ambil ? $dp->tanggal_ambil->format('d/m/Y H:i') : '-' }}
                                                </td>
                                                <td><strong>{{ $dp->item->nama_produk ?? '' }}</strong> (Size:
                                                    {{ $dp->item->ukuran ?? '—' }})</td>
                                                <td class="text-center fw-bold text-success">+{{ $dp->qty_diambil }} pcs
                                                </td>
                                                <td class="text-dark">{{ $dp->nama_pengambil }}</td>
                                                <td class="text-muted small">{{ $dp->catatan ?: '-' }}</td>
                                                <td class="text-center">
                                                    <form action="{{ route('spks.pickups.destroy', $dp->id) }}"
                                                        method="POST" class="d-inline"
                                                        onsubmit="return confirm('Apakah Anda yakin ingin menghapus/membatalkan catatan pengambilan ini?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                            class="btn btn-outline-danger btn-sm py-0 px-2"
                                                            title="Hapus">
                                                            <i class="fas fa-trash-alt" style="font-size: 9px;"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ── SUBMIT BAR ── --}}
            <div class="spk-submit-bar">
                <a href="{{ route('spks.index') }}" class="btn btn-sm btn-outline-secondary px-4">Batal</a>
                <button type="button" class="btn btn-sm btn-warning text-dark fw-bold px-3 me-1" data-bs-toggle="modal"
                    data-bs-target="#modalPayLabor">
                    💳 Pembayaran Produksi / Cicilan
                </button>
                <a href="{{ route('spks.scan_pickup', $spk->id) }}"
                    class="btn btn-sm btn-primary fw-bold px-3 me-1 d-inline-flex align-items-center gap-1.5"
                    style="background: linear-gradient(135deg, #2563eb, #1d4ed8);">
                    <i class="fas fa-qrcode"></i> Scan Penerimaan
                </a>
                <a href="{{ route('spks.create', [
                    'no_produksi' => $spk->no_produksi ?: $spk->no_spk,
                    'no_pesanan' => $spk->no_pesanan,
                    'pemesan' => $spk->pemesan,
                    'no_hp_pemesan' => $spk->no_hp_pemesan,
                    'instansi' => $spk->instansi,
                    'order_id' => $spk->order_id,
                ]) }}"
                    class="btn btn-sm btn-success fw-bold px-3 me-1">
                    ✚ Tambah SPK Baru
                </a>
                <a href="{{ route('spks.print', $spk) }}" target="_blank"
                    class="btn btn-sm btn-outline-primary fw-bold px-4 me-1">
                    🖨️ Cetak Lembar SPK
                </a>
                <button type="submit" class="btn btn-sm btn-success fw-bold px-4">
                    💾 Simpan Perubahan
                </button>
            </div>
        </form>
    </div>

    {{-- POPUP MODAL 1: RINCIAN BAHAN & BARANG KOMPONEN --}}
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
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" style="background:#f8fafc;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-secondary fw-bold"
                            style="font-size:11px; text-transform:uppercase; letter-spacing:.5px;">
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
                            <tbody id="modalBahanTableBody"></tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <button type="button" class="btn btn-outline-primary btn-sm fw-bold px-3"
                            id="btnModalAddBahanRow">
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

    {{-- POPUP MODAL 2: TAHAP OPERASIONAL & TIM PRODUKSI --}}
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
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" style="background:#f8fafc;">

                    {{-- 1. TAHAP PEMOTONGAN (POTONG) --}}
                    <div class="card border-0 shadow-sm rounded-3 mb-3">
                        <div class="card-header bg-danger-subtle text-danger-emphasis fw-bold py-2 px-3 d-flex justify-content-between align-items-center"
                            style="font-size:12px;">
                            <span>✂️ TAHAP PEMOTONGAN</span>
                            <span class="text-muted subtotal-potong-display">Subtotal: Rp 0</span>
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-3">
                                <div class="col-md-5">
                                    <label class="form-label mb-1 fw-semibold text-secondary"
                                        style="font-size:11px;">PEMOTONG / TUKANG POTONG</label>
                                    <input type="text" id="modal_pemotong"
                                        class="form-control form-control-sm modal-op-field" list="pemotong_datalist"
                                        placeholder="Pilih / Ketik Nama Pemotong">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label mb-1 fw-semibold text-secondary" style="font-size:11px;">QTY
                                        POTONG (PCS)</label>
                                    <input type="number" id="modal_qty_potong"
                                        class="form-control form-control-sm text-center modal-op-field" min="0"
                                        placeholder="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label mb-1 fw-semibold text-secondary"
                                        style="font-size:11px;">TARIF ONGKOS / PCS (RP)</label>
                                    <input type="text" id="modal_tarif_potong"
                                        class="form-control form-control-sm text-end modal-op-field numeric-dot-format"
                                        placeholder="0">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 2. TAHAP JAHIT --}}
                    <div class="card border-0 shadow-sm rounded-3 mb-3">
                        <div class="card-header bg-warning-subtle text-warning-emphasis fw-bold py-2 px-3 d-flex justify-content-between align-items-center"
                            style="font-size:12px;">
                            <span>🧵 TAHAP JAHIT</span>
                            <span class="text-muted subtotal-jahit-display">Subtotal: Rp 0</span>
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-3">
                                <div class="col-md-5">
                                    <label class="form-label mb-1 fw-semibold text-secondary"
                                        style="font-size:11px;">PENJAHIT</label>
                                    <input type="text" id="modal_penjahit"
                                        class="form-control form-control-sm modal-op-field" list="penjahit_datalist"
                                        placeholder="Pilih / Ketik Penjahit">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label mb-1 fw-semibold text-secondary" style="font-size:11px;">QTY
                                        JAHIT (PCS)</label>
                                    <input type="number" id="modal_qty_jahit"
                                        class="form-control form-control-sm text-center modal-op-field" min="0"
                                        placeholder="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label mb-1 fw-semibold text-secondary"
                                        style="font-size:11px;">TARIF ONGKOS / PCS (RP)</label>
                                    <input type="text" id="modal_tarif_jahit"
                                        class="form-control form-control-sm text-end modal-op-field numeric-dot-format"
                                        placeholder="0">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 3. TAHAP LKPK (KANCING) --}}
                    <div class="card border-0 shadow-sm rounded-3 mb-3">
                        <div class="card-header bg-info-subtle text-info-emphasis fw-bold py-2 px-3 d-flex justify-content-between align-items-center"
                            style="font-size:12px;">
                            <span>🔘 TAHAP LKPK (KANCING)</span>
                            <span class="text-muted subtotal-kancing-display">Subtotal: Rp 0</span>
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-3">
                                <div class="col-md-5">
                                    <label class="form-label mb-1 fw-semibold text-secondary"
                                        style="font-size:11px;">VENDOR KANCING</label>
                                    <input type="text" id="modal_vendor_kancing"
                                        class="form-control form-control-sm modal-op-field" list="vendor_kancing_datalist"
                                        placeholder="Pilih / Ketik Vendor Kancing">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label mb-1 fw-semibold text-secondary" style="font-size:11px;">QTY
                                        KANCING (PCS)</label>
                                    <input type="number" id="modal_qty_kancing"
                                        class="form-control form-control-sm text-center modal-op-field" min="0"
                                        placeholder="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label mb-1 fw-semibold text-secondary"
                                        style="font-size:11px;">TARIF ONGKOS / PCS (RP)</label>
                                    <input type="text" id="modal_tarif_kancing"
                                        class="form-control form-control-sm text-end modal-op-field numeric-dot-format"
                                        placeholder="0">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 4. TAHAP QC (QUALITY CONTROL) --}}
                    <div class="card border-0 shadow-sm rounded-3 mb-3">
                        <div class="card-header bg-secondary-subtle text-secondary-emphasis fw-bold py-2 px-3 d-flex justify-content-between align-items-center"
                            style="font-size:12px;">
                            <span>🔍 TAHAP QC (QUALITY CONTROL)</span>
                            <span class="text-muted subtotal-qc-display">Subtotal: Rp 0</span>
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label mb-1 fw-semibold text-secondary"
                                        style="font-size:11px;">PETUGAS QC</label>
                                    <input type="text" id="modal_petugas_qc"
                                        class="form-control form-control-sm modal-op-field" list="petugas_qc_datalist"
                                        placeholder="Pilih / Ketik Petugas QC">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label mb-1 fw-semibold text-success" style="font-size:11px;">LOLOS
                                        (PCS)</label>
                                    <input type="number" id="modal_qc_lolos"
                                        class="form-control form-control-sm text-center border-success modal-op-field"
                                        min="0" placeholder="0">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label mb-1 fw-semibold text-danger" style="font-size:11px;">REJECT
                                        (PCS)</label>
                                    <input type="number" id="modal_qc_reject"
                                        class="form-control form-control-sm text-center border-danger modal-op-field"
                                        min="0" placeholder="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label mb-1 fw-semibold text-secondary"
                                        style="font-size:11px;">TARIF QC / PCS (RP)</label>
                                    <input type="text" id="modal_tarif_qc"
                                        class="form-control form-control-sm text-end modal-op-field numeric-dot-format"
                                        placeholder="0">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 5. TAHAP FINISHING & F.GOOD --}}
                    <div class="card border-0 shadow-sm rounded-3 mb-0">
                        <div class="card-header bg-success-subtle text-success-emphasis fw-bold py-2 px-3 d-flex justify-content-between align-items-center"
                            style="font-size:12px;">
                            <span>✨ FINISHING &amp; F.GOOD (FINISHED GOOD)</span>
                            <span class="text-muted subtotal-finishing-display">Subtotal: Rp 0</span>
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label mb-1 fw-semibold text-secondary"
                                        style="font-size:11px;">PETUGAS FINISHING</label>
                                    <input type="text" id="modal_petugas_finishing"
                                        class="form-control form-control-sm modal-op-field" list="finishing_datalist"
                                        placeholder="Pilih / Ketik Petugas Finishing">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label mb-1 fw-semibold text-secondary"
                                        style="font-size:11px;">FINISHING (PCS)</label>
                                    <input type="number" id="modal_qty_finishing"
                                        class="form-control form-control-sm text-center modal-op-field" min="0"
                                        placeholder="0">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label mb-1 fw-semibold text-success" style="font-size:11px;">F.GOOD
                                        (PCS)</label>
                                    <input type="number" id="modal_qty_fgood"
                                        class="form-control form-control-sm text-center border-success fw-bold modal-op-field"
                                        min="0" placeholder="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label mb-1 fw-semibold text-secondary"
                                        style="font-size:11px;">TARIF FINISHING / PCS (RP)</label>
                                    <input type="text" id="modal_tarif_finishing"
                                        class="form-control form-control-sm text-end modal-op-field numeric-dot-format"
                                        placeholder="0">
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-white border-top py-2 px-4 d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-primary fs-6" id="modalTotalLaborDisplay">Total Ongkos Jasa: Rp 0</span>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-secondary px-3 me-2"
                            data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-sm btn-primary fw-bold px-4" id="btnSaveModalTahap">
                            ✅ Simpan Tahapan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- POPUP MODAL 3: PEMBAYARAN PRODUKSI & CICILAN SPK --}}
    <div class="modal fade" id="modalPayLabor" tabindex="-1" aria-labelledby="modalPayLaborLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-warning text-dark py-3 px-4">
                    <div>
                        <h6 class="modal-title fw-bold d-flex align-items-center gap-1.5 mb-0" id="modalPayLaborLabel">
                            💳 Pembayaran Produksi &amp; Cicilan SPK (#{{ $spk->no_produksi ?: $spk->no_spk }})
                        </h6>
                        <small class="text-dark opacity-75">Pencatatan cicilan biaya produksi ke vendor / konveksi / penjahit</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" style="background:#f8fafc;">

                    {{-- TOP SUMMARY CARDS FOR PRODUCTION COSTS & UNPAID BALANCE --}}
                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <div class="bg-white p-2.5 rounded-3 border shadow-2xs text-center">
                                <span class="text-muted d-block text-uppercase fw-bold" style="font-size: 9px; letter-spacing: 0.5px;">Target Biaya Produksi</span>
                                <span class="fw-extrabold text-dark fs-6 font-monospace">Rp {{ number_format($spk->total_biaya_produksi, 0, ',', '.') }}</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="bg-white p-2.5 rounded-3 border border-success-subtle shadow-2xs text-center" style="background-color: #f0fdf4 !important;">
                                <span class="text-success d-block text-uppercase fw-bold" style="font-size: 9px; letter-spacing: 0.5px;">Sudah Dibayar</span>
                                <span class="fw-extrabold text-success fs-6 font-monospace">Rp {{ number_format($spk->total_paid_production, 0, ',', '.') }}</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="bg-white p-2.5 rounded-3 border border-danger-subtle shadow-2xs text-center" style="background-color: #fffbeb !important;">
                                <span class="text-danger d-block text-uppercase fw-bold" style="font-size: 9px; letter-spacing: 0.5px;">Sisa Tagihan</span>
                                <span class="fw-extrabold text-danger fs-6 font-monospace">Rp {{ number_format($spk->remaining_production_cost, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- NAV TABS: CICILAN UMUM VS PER RINCIAN JASA --}}
                    <ul class="nav nav-pills nav-fill mb-3 bg-white p-1 rounded-3 border" id="payModalTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold py-1.5 px-3" id="tab-installment" data-bs-toggle="pill" data-bs-target="#content-installment" type="button" role="tab">
                                💰 Catat Cicilan Pembayaran
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold py-1.5 px-3" id="tab-breakdown" data-bs-toggle="pill" data-bs-target="#content-breakdown" type="button" role="tab">
                                📋 Bayar Per Rincian Jasa (Vendor)
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="payModalTabContent">
                        {{-- TAB 1: CICILAN PEMBAYARAN PRODUKSI --}}
                        <div class="tab-pane fade show active" id="content-installment" role="tabpanel">
                            <form action="{{ route('spks.payments.store', $spk) }}" method="POST">
                                @csrf
                                <div class="bg-white p-3 rounded-3 border mb-3">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold text-dark small mb-1">
                                            Nominal Cicilan Dibayar (Rp) <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light fw-bold">Rp</span>
                                            <input type="number" name="amount" id="showModalInputAmount"
                                                   class="form-control fw-extrabold fs-6 text-end font-monospace"
                                                   min="1" max="{{ (int) $spk->remaining_production_cost }}"
                                                   value="{{ (int) $spk->remaining_production_cost }}"
                                                   step="1" required placeholder="0"
                                                   {{ $spk->remaining_production_cost <= 0 ? 'disabled' : '' }}>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-1">
                                            <div class="d-flex gap-1">
                                                <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2" id="btnShowQuick50" style="font-size: 11px;" {{ $spk->remaining_production_cost <= 0 ? 'disabled' : '' }}>
                                                    50%
                                                </button>
                                                <button type="button" class="btn btn-xs btn-outline-success py-0 px-2 fw-bold" id="btnShowQuickLunas" style="font-size: 11px;" {{ $spk->remaining_production_cost <= 0 ? 'disabled' : '' }}>
                                                    Pelunasan (100%)
                                                </button>
                                            </div>
                                            <small class="text-muted" style="font-size: 11px;">Maksimal: Rp {{ number_format($spk->remaining_production_cost, 0, ',', '.') }}</small>
                                        </div>
                                    </div>

                                    <div class="row g-2 mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold text-dark small mb-1">
                                                Tanggal Pembayaran <span class="text-danger">*</span>
                                            </label>
                                            <input type="date" name="payment_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold text-dark small mb-1">
                                                Sumber Kas / Bank <span class="text-danger">*</span>
                                            </label>
                                            <select name="payment_source" class="form-select form-select-sm" required>
                                                <option value="kas_besar" selected>Kas Besar (Main Cash)</option>
                                                <option value="kas_kecil">Kas Kecil (Petty Cash)</option>
                                                @if (isset($bankAccounts) && count($bankAccounts) > 0)
                                                    <optgroup label="Rekening Bank">
                                                        @foreach ($bankAccounts as $bank)
                                                            <option value="{{ $bank->id }}">{{ $bank->bank_name }} - {{ $bank->account_number }} (a.n {{ $bank->account_name }})</option>
                                                        @endforeach
                                                    </optgroup>
                                                @endif
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row g-2 mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold text-dark small mb-1">Penerima / Vendor / Penjahit</label>
                                            <input type="text" name="recipient_name" class="form-control form-control-sm"
                                                   placeholder="Nama Vendor / Penjahit" list="tailorListShow">
                                            <datalist id="tailorListShow">
                                                @if (isset($vendorsData) && count($vendorsData) > 0)
                                                    @foreach ($vendorsData as $v)
                                                        @php
                                                            $vName = is_object($v) ? ($v->name ?? '') : (is_array($v) ? ($v['name'] ?? '') : (string)$v);
                                                            $vCat  = is_object($v) ? ($v->category ?? '') : (is_array($v) ? ($v['category'] ?? '') : '');
                                                        @endphp
                                                        <option value="{{ $vName }}">{{ $vName }}{{ !empty($vCat) ? ' (' . $vCat . ')' : '' }}</option>
                                                    @endforeach
                                                @elseif (isset($tailors))
                                                    @foreach ($tailors as $t)
                                                        @php
                                                            $tName = is_object($t) ? ($t->name ?? '') : (is_array($t) ? ($t['name'] ?? '') : (string)$t);
                                                            $tCat  = is_object($t) ? ($t->category ?? '') : (is_array($t) ? ($t['category'] ?? '') : '');
                                                        @endphp
                                                        <option value="{{ $tName }}">{{ $tName }}{{ !empty($tCat) ? ' (' . $tCat . ')' : '' }}</option>
                                                    @endforeach
                                                @endif
                                            </datalist>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold text-dark small mb-1">Jenis Ongkos</label>
                                            <select name="payment_type" class="form-select form-select-sm">
                                                <option value="biaya_produksi" selected>Ongkos Jasa Produksi</option>
                                                <option value="jasa_jahit">Jasa Jahit</option>
                                                <option value="jasa_potong">Jasa Potong</option>
                                                <option value="jasa_sablon">Jasa Sablon / Print</option>
                                                <option value="jasa_finishing">Finishing / Packing</option>
                                                <option value="tambahan">Biaya Tambahan</option>
                                                <option value="umum">Lainnya / Umum</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold text-dark small mb-1">Catatan / Keterangan</label>
                                        <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Catatan cicilan (opsional, misal: DP Konveksi 50%)..."></textarea>
                                    </div>

                                    <div class="text-end">
                                        <button type="submit" class="btn btn-sm btn-success fw-bold px-4"
                                                {{ $spk->remaining_production_cost <= 0 ? 'disabled' : '' }}>
                                            <i class="fas fa-save me-1"></i>Simpan Cicilan Pembayaran
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        {{-- TAB 2: BAYAR PER RINCIAN JASA VENDOR --}}
                        <div class="tab-pane fade" id="content-breakdown" role="tabpanel">
                            <form action="{{ route('spks.pay_labor', $spk) }}" method="POST">
                                @csrf
                                <div class="alert alert-info py-2 px-3 mb-3 border-0 shadow-sm" style="font-size:12px;">
                                    Pilih item ongkos jasa vendor/pekerja yang ingin dibayar. Sistem akan membuat <strong>pencatatan
                                        pengeluaran kas terpisah (per vendor)</strong> dan menyimpan histori transaksi pembayaran.
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold text-secondary" style="font-size:12px;">SUMBER KAS / REKENING PEMBAYARAN</label>
                                        <select name="payment_source" class="form-select form-select-sm" required>
                                            <option value="kas_kecil">Kas Kecil (Petty Cash)</option>
                                            <option value="kas_besar" selected>Kas Besar (Main Cash)</option>
                                            @if (isset($bankAccounts) && count($bankAccounts) > 0)
                                                <optgroup label="Rekening Bank">
                                                    @foreach ($bankAccounts as $bank)
                                                        <option value="{{ $bank->id }}">{{ $bank->bank_name }} - {{ $bank->account_number }} (a.n {{ $bank->account_name }})</option>
                                                    @endforeach
                                                </optgroup>
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold text-secondary" style="font-size:12px;">TANGGAL PEMBAYARAN</label>
                                        <input type="date" name="expense_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                                    </div>
                                </div>

                                <label class="form-label fw-semibold text-secondary mb-2" style="font-size:12px;">RINCIAN ONGKOS JASA VENDOR / TIM OPERASIONAL</label>
                                <div class="table-responsive bg-white rounded border shadow-sm mb-3">
                                    <table class="table table-sm table-hover align-middle mb-0" style="font-size:11.5px;">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 35px;" class="text-center">
                                                    <input type="checkbox" id="checkAllPayItems" class="form-check-input">
                                                </th>
                                                <th>Rincian Jasa &amp; Vendor</th>
                                                <th style="width: 18%;">Produk SPK</th>
                                                <th style="width: 16%;" class="text-end">Total Tarif</th>
                                                <th style="width: 16%;" class="text-end">Sisa Tagihan</th>
                                                <th style="width: 24%;" class="text-end">Nominal Dibayar (Rp)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($laborBreakdown as $lIdx => $lItem)
                                                @php
                                                    $sisaVal = (float) $lItem['sisa_bayar'];
                                                    $isLunas = $lItem['is_lunas'];
                                                @endphp
                                                <tr class="{{ $isLunas ? 'bg-light bg-opacity-50 text-muted' : '' }}">
                                                    <td class="text-center">
                                                        <input type="checkbox" name="payments[{{ $lIdx }}][checked_val]"
                                                            value="1" class="form-check-input pay-item-checkbox"
                                                            {{ !$isLunas ? 'checked' : '' }} {{ $isLunas ? 'disabled' : '' }}>
                                                    </td>
                                                    <td>
                                                        <input type="hidden" name="payments[{{ $lIdx }}][title]"
                                                            value="{{ $lItem['keterangan'] }}">
                                                        <span
                                                            class="fw-bold {{ $isLunas ? 'text-muted' : 'text-dark' }}">{{ $lItem['keterangan'] }}</span>
                                                    </td>
                                                    <td>
                                                        <span
                                                            class="badge bg-secondary-subtle text-secondary">{{ $lItem['produk'] }}</span>
                                                    </td>
                                                    <td class="text-end fw-semibold">
                                                        Rp {{ number_format($lItem['nominal'], 0, ',', '.') }}
                                                    </td>
                                                    <td class="text-end">
                                                        @if ($isLunas)
                                                            <span
                                                                class="badge bg-success-subtle text-success border border-success-subtle fw-bold">
                                                                ✅ LUNAS
                                                            </span>
                                                        @elseif($lItem['sudah_dibayar'] > 0)
                                                            <span
                                                                class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fw-bold d-block text-end">
                                                                Sisa: Rp {{ number_format($sisaVal, 0, ',', '.') }}
                                                            </span>
                                                        @else
                                                            <span class="fw-extrabold text-danger">
                                                                Rp {{ number_format($sisaVal, 0, ',', '.') }}
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td class="text-end">
                                                        <input type="number" name="payments[{{ $lIdx }}][amount]"
                                                            class="form-control form-control-sm text-end fw-bold pay-item-amount"
                                                            value="{{ (int) $sisaVal }}" min="1"
                                                            max="{{ (int) $sisaVal }}" step="1"
                                                            {{ $isLunas ? 'disabled' : '' }}>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="text-center text-muted py-3">Belum ada data ongkos
                                                        jasa yang disetting pada SPK ini.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <div class="d-flex justify-content-between align-items-center bg-warning-subtle text-warning-emphasis p-2.5 rounded-3 border border-warning-subtle mb-3">
                                    <span class="fw-bold small">Total Pembayaran Terpilih:</span>
                                    <span class="fw-extrabold fs-6" id="displayTotalSelectedPay">Rp 0</span>
                                </div>

                                <div class="text-end">
                                    <button type="submit" class="btn btn-sm btn-warning fw-bold px-4 text-dark"
                                        {{ count($laborBreakdown) == 0 || $totalSpkLaborUnpaid <= 0 ? 'disabled' : '' }}>
                                        💳 Proses Bayar Per Vendor Terpilih
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- HISTORI CICILAN PEMBAYARAN PRODUKSI (SPK_PAYMENTS) --}}
                    <div class="bg-white rounded border p-3 shadow-2xs mt-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="fw-bold text-dark m-0 d-flex align-items-center gap-1.5" style="font-size: 13px;">
                                📜 Riwayat Cicilan Pembayaran Produksi
                            </h6>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle fw-bold" style="font-size: 10px;">
                                {{ count($spkPayments) }} Transaksi Cicilan
                            </span>
                        </div>
                        <div class="table-responsive rounded border">
                            <table class="table table-sm table-striped table-hover align-middle mb-0" style="font-size: 11px;">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 14%;">Tanggal &amp; No.</th>
                                        <th>Penerima / Vendor</th>
                                        <th style="width: 22%;">Sumber Kas / Bank</th>
                                        <th>Catatan</th>
                                        <th style="width: 18%;" class="text-end">Nominal (Rp)</th>
                                        <th style="width: 40px;" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($spkPayments as $pPay)
                                        <tr>
                                            <td>
                                                <div class="fw-bold text-dark">{{ $pPay->payment_date ? $pPay->payment_date->format('d/m/Y') : '-' }}</div>
                                                <small class="text-muted font-monospace" style="font-size: 9.5px;">{{ $pPay->payment_number }}</small>
                                            </td>
                                            <td>
                                                <span class="fw-bold text-dark">{{ $pPay->recipient_name ?: 'Tim Produksi' }}</span>
                                                <small class="text-muted d-block" style="font-size: 9.5px;">{{ ucwords(str_replace('_', ' ', $pPay->payment_type)) }}</small>
                                            </td>
                                            <td>
                                                @if ($pPay->bankAccount)
                                                    <span class="badge bg-light text-dark border fw-bold" style="font-size: 10px;">
                                                        🏦 {{ $pPay->bankAccount->bank_name }} ({{ $pPay->bankAccount->account_number }})
                                                    </span>
                                                @else
                                                    <span class="badge bg-light text-dark border fw-bold" style="font-size: 10px;">
                                                        💵 {{ strtoupper(str_replace('_', ' ', $pPay->payment_source)) }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="text-muted">{{ $pPay->notes ?: '-' }}</span>
                                                @if ($pPay->user)
                                                    <small class="text-secondary d-block" style="font-size: 9px;">Oleh: {{ $pPay->user->name }}</small>
                                                @endif
                                            </td>
                                            <td class="text-end fw-extrabold text-success font-monospace">
                                                Rp {{ number_format($pPay->amount, 0, ',', '.') }}
                                            </td>
                                            <td class="text-center">
                                                @if (auth()->user()->isAdmin() || auth()->user()->isOwner() || in_array(auth()->user()->role, ['admin', 'owner']))
                                                    <form action="{{ route('spks.payments.destroy', [$spk, $pPay]) }}" method="POST"
                                                          onsubmit="return confirm('Hapus cicilan pembayaran Rp {{ number_format($pPay->amount, 0, ',', '.') }} ini? Saldo kas/bank akan dikembalikan.')" class="m-0">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-xs btn-outline-danger p-1" title="Hapus Pembayaran">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-3">
                                                Belum ada riwayat cicilan pembayaran produksi yang dicatat untuk SPK ini.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-white border-top py-2 px-4">
                    <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    {{-- DATALISTS FOR SKU AND PRODUCT NAME AUTOCOMPLETE --}}
    <datalist id="master_skus_datalist">
        @foreach ($products as $p)
            @if (!empty($p->sku))
                <option value="{{ $p->sku }}">{{ $p->name }}
                    {{ $p->ukuran ? '(' . $p->ukuran . ')' : '' }}
                </option>
            @endif
            @if (!empty($p->sku_induk) && $p->sku_induk !== $p->sku)
                <option value="{{ $p->sku_induk }}">{{ $p->name }}
                    {{ $p->ukuran ? '(' . $p->ukuran . ')' : '' }}
                </option>
            @endif
        @endforeach
    </datalist>

    <datalist id="master_product_names_datalist">
        @foreach ($products as $p)
            @if (!empty($p->name))
                <option value="{{ $p->name }}">{{ $p->sku ? $p->sku . ' — ' : '' }}{{ $p->name }}
                    {{ $p->ukuran ? '(' . $p->ukuran . ')' : '' }}</option>
            @endif
        @endforeach
    </datalist>

    <datalist id="ukuran_datalist">
        <option value="S">
        <option value="M">
        <option value="L">
        <option value="XL">
        <option value="XXL">
        <option value="3XL">
        <option value="ALL SIZE">
    </datalist>
@endsection

@push('scripts')
    <script>
        const tailorsList = @json($tailors);
        const existingNoProduksiList = @json($existingNoProduksi);
        const recipesMap = @json($recipesMap ?? []);
        const allMasterProductsList = @json($allMasterProductsList ?? []);
        const allInventoryItemsList = @json($inventoryItems);
        const inventoryItemsMap = @json($inventoryItemsMap ?? []);

        const masterProductsMap = {};
        @foreach ($products as $p)
            @php
                $prodInfo = [
                    'name' => $p->name,
                    'sku' => $p->sku,
                    'ukuran' => $p->ukuran ?? '',
                ];
            @endphp
            @if (!empty($p->sku))
                masterProductsMap[@json(strtoupper(trim($p->sku)))] = @json($prodInfo);
            @endif
            @if (!empty($p->sku_induk))
                masterProductsMap[@json(strtoupper(trim($p->sku_induk)))] = @json($prodInfo);
            @endif
        @endforeach

        function addNewProductRow(rIdx) {
            const tbody = document.getElementById(`product-tbody-${rIdx}`);
            if (!tbody) return;

            const pIdx = tbody.querySelectorAll('tr').length;
            const tr = document.createElement('tr');
            tr.id = `product-row-${rIdx}-${pIdx}`;
            tr.dataset.rIdx = rIdx;
            tr.dataset.pIdx = pIdx;

            tr.innerHTML = `
            <td>
                <input type="text" name="rincian[${rIdx}][produk][${pIdx}][sku_produk]" 
                       class="form-control font-monospace fw-bold row-sku-produk input-sku-produk" 
                       list="master_skus_datalist" autocomplete="off"
                       placeholder="Pilih SKU...">
            </td>
            <td>
                <input type="text" name="rincian[${rIdx}][produk][${pIdx}][nama_produk]" 
                       class="form-control row-nama-produk input-nama-produk" 
                       placeholder="Nama produk..." 
                       list="master_product_names_datalist" autocomplete="off">
            </td>
            <td>
                <input type="text" name="rincian[${rIdx}][produk][${pIdx}][ukuran]" 
                       class="form-control text-center row-ukuran input-ukuran-produk" 
                       placeholder="S, M, L..." 
                       list="ukuran_datalist">
            </td>
            <td>
                <input type="number" name="rincian[${rIdx}][produk][${pIdx}][qty_produksi]" 
                       class="form-control text-center fw-bold row-qty-produksi input-qty-produksi" 
                       value="1" min="1">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger border-0 btn-remove-product-row" onclick="removeProductRow('${rIdx}-${pIdx}')" title="Hapus Varian">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
        `;

            tbody.appendChild(tr);
            if (window.recalculateSpkCosts) {
                window.recalculateSpkCosts();
            }
        }

        function removeProductRow(rowId) {
            const row = document.getElementById(`product-row-${rowId}`) || document.getElementById(`prod-row-${rowId}`);
            if (row) {
                const tbody = row.closest('tbody');
                if (tbody && tbody.querySelectorAll('tr').length <= 1) {
                    alert('Minimal 1 varian produk harus ada dalam SPK.');
                    return;
                }
                row.remove();
                if (window.recalculateSpkCosts) {
                    window.recalculateSpkCosts();
                }
            }
        }

        let spkBahanCounter = {{ max(count($spkBahanData ?? []), 1) }};

        function addSpkBahanRow() {
            const tbody = document.getElementById('spkBahanTableBody');
            if (!tbody) return;
            const bIdx = spkBahanCounter++;
            const tr = document.createElement('tr');
            tr.className = 'spk-bahan-row';
            tr.dataset.bIdx = bIdx;
            tr.innerHTML = `
                <td>
                    <input type="text" name="spk_bahan[${bIdx}][nama_bahan]" 
                           class="form-control form-control-sm fw-bold spk-bahan-nama" 
                           list="inventory_items_datalist" autocomplete="off"
                           placeholder="Contoh: BB-TH / Cotton Combed 30s..."
                           value="BB-TH">
                </td>
                <td>
                    <input type="text" name="spk_bahan[${bIdx}][qty_bahan]" 
                           class="form-control form-control-sm text-center spk-bahan-qty" 
                           placeholder="1" value="1">
                </td>
                <td>
                    <input type="text" name="spk_bahan[${bIdx}][satuan]" 
                           class="form-control form-control-sm text-center spk-bahan-satuan" 
                           placeholder="Roll / Kg / Mtr" value="Roll">
                </td>
                <td>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text px-1 bg-light text-muted" style="font-size: 10px;">Rp</span>
                        <input type="text" name="spk_bahan[${bIdx}][harga]" 
                               class="form-control form-control-sm text-end fw-bold spk-bahan-harga numeric-dot-format" 
                               placeholder="0" value="0">
                    </div>
                </td>
                <td class="text-end">
                    <input type="hidden" name="spk_bahan[${bIdx}][subtotal]" 
                           class="spk-bahan-subtotal-val" value="0">
                    <span class="fw-bold text-primary spk-bahan-subtotal-text">
                        Rp 0
                    </span>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-link text-danger p-0 btn-remove-spk-bahan" title="Hapus Bahan">
                        <i class="fas fa-times"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
            if (window.recalculateSpkCosts) {
                window.recalculateSpkCosts();
            }
        }

        function updateMasterSkuDatalist(queryStr = '') {
            const datalist = document.getElementById('master_skus_datalist');
            if (!datalist) return;
            const cleanQ = queryStr.trim().toLowerCase();
            datalist.innerHTML = '';
            let count = 0;
            for (const prod of allMasterProductsList) {
                const skuStr = (prod.sku || '').toLowerCase();
                const skuIndukStr = (prod.sku_induk || '').toLowerCase();
                const nameStr = (prod.name || '').toLowerCase();
                if (cleanQ === '' || skuStr.includes(cleanQ) || skuIndukStr.includes(cleanQ) || nameStr.includes(cleanQ)) {
                    if (prod.sku) {
                        const opt = document.createElement('option');
                        opt.value = prod.sku;
                        opt.textContent = prod.name + (prod.ukuran ? ' (' + prod.ukuran + ')' : '');
                        datalist.appendChild(opt);
                        count++;
                    }
                    if (count >= 15) break;
                }
            }
        }

        function handleSkuSelection(tr) {
            if (!tr) return;
            const skuInput = tr.querySelector('.row-sku-produk, .input-sku-produk');
            const nameInput = tr.querySelector('.row-nama-produk, .input-nama-produk');
            const ukInput = tr.querySelector('.row-ukuran, .input-ukuran-produk');

            const cleanSku = skuInput ? skuInput.value.trim().toUpperCase() : '';
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
                if (nameInput && (!nameInput.value || nameInput.value === 'PRODUK BARU')) nameInput.value = masterProd.name;
                if (skuInput && !skuInput.value && masterProd.sku) skuInput.value = masterProd.sku;
                if (ukInput && masterProd.ukuran) ukInput.value = masterProd.ukuran;
            }

            applyRecipeToProductRow(tr);
        }

        function applyRecipeToProductRow(tr) {
            if (!tr) return;
            const rIdx = tr.dataset.rIdx ?? '0';
            const pIdx = tr.dataset.pIdx ?? '0';
            const skuInput = tr.querySelector('.row-sku-produk, .input-sku-produk');
            const nameInput = tr.querySelector('.row-nama-produk, .input-nama-produk');
            const skuVal = skuInput?.value?.trim()?.toUpperCase();
            const nameVal = nameInput?.value?.trim()?.toUpperCase();
            const qtyProd = parseInt(tr.querySelector('.row-qty-produksi, .input-qty-produksi')?.value || 1) || 1;

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

            if (recipe && recipe.items && recipe.items.length > 0) {
                // If SPK bahan table currently only has 1 empty/default row (e.g. price = 0 or name = BB-TH), populate with recipe
                const currentBahanRows = document.querySelectorAll('#spkBahanTableBody tr');
                const isOnlyDefaultBahan = currentBahanRows.length === 1 && (
                    cleanNumberFromDots(currentBahanRows[0].querySelector('.spk-bahan-harga')?.value || '0') === 0
                );
                if (isOnlyDefaultBahan) {
                    const tbody = document.getElementById('spkBahanTableBody');
                    if (tbody) {
                        tbody.innerHTML = '';
                        recipe.items.forEach((item, bIdx) => {
                            const trB = document.createElement('tr');
                            trB.className = 'spk-bahan-row';
                            trB.dataset.bIdx = bIdx;
                            const calcQty = Number((item.qty_unit * qtyProd).toFixed(2));
                            const subtotal = Math.round(calcQty * item.harga);
                            trB.innerHTML = `
                                <td>
                                    <input type="text" name="spk_bahan[${bIdx}][nama_bahan]" 
                                           class="form-control form-control-sm fw-bold spk-bahan-nama" 
                                           list="inventory_items_datalist" autocomplete="off"
                                           value="${escHtml(item.nama_bahan)}">
                                </td>
                                <td>
                                    <input type="text" name="spk_bahan[${bIdx}][qty_bahan]" 
                                           class="form-control form-control-sm text-center spk-bahan-qty" 
                                           value="${calcQty}">
                                </td>
                                <td>
                                    <input type="text" name="spk_bahan[${bIdx}][satuan]" 
                                           class="form-control form-control-sm text-center spk-bahan-satuan" 
                                           value="${escHtml(item.unit || 'Mtr')}">
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text px-1 bg-light text-muted" style="font-size: 10px;">Rp</span>
                                        <input type="text" name="spk_bahan[${bIdx}][harga]" 
                                               class="form-control form-control-sm text-end fw-bold spk-bahan-harga numeric-dot-format" 
                                               value="${formatNumberWithDots(item.harga)}">
                                    </div>
                                </td>
                                <td class="text-end">
                                    <input type="hidden" name="spk_bahan[${bIdx}][subtotal]" 
                                           class="spk-bahan-subtotal-val" value="${subtotal}">
                                    <span class="fw-bold text-primary spk-bahan-subtotal-text">
                                        ${formatRupiah(subtotal)}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-link text-danger p-0 btn-remove-spk-bahan" title="Hapus Bahan">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </td>
                            `;
                            tbody.appendChild(trB);
                        });
                        spkBahanCounter = recipe.items.length;
                        if (window.recalculateSpkCosts) {
                            window.recalculateSpkCosts();
                        }
                    }
                }
            }
        }

        function createHiddenBahanRow(rIdx, pIdx, bIdx, data = {}) {
            const div = document.createElement('div');
            div.className = `hidden-bahan-row hidden-bahan-${bIdx}`;
            div.innerHTML = `
            <input type="hidden" name="rincian[${rIdx}][produk][${pIdx}][bahan][${bIdx}][nama_bahan]" value="${data.nama_bahan || ''}">
            <input type="hidden" name="rincian[${rIdx}][produk][${pIdx}][bahan][${bIdx}][qty_bahan]" value="${data.qty_bahan || '1'}">
            <input type="hidden" name="rincian[${rIdx}][produk][${pIdx}][bahan][${bIdx}][harga]" value="${data.harga || '0'}">
            <input type="hidden" name="rincian[${rIdx}][produk][${pIdx}][bahan][${bIdx}][subtotal]" value="${data.subtotal || '0'}">
        `;
            return div;
        }

        function updateProductRowBahanButton(tr, count, totalCost, isRecipe = false) {
            const btn = tr.querySelector('.btn-bahan-trigger');
            if (!btn) return;

            if (count > 0) {
                btn.className =
                    'btn btn-sm btn-success-subtle text-success border border-success-subtle btn-bahan-trigger btn-open-bahan-modal';
                btn.innerHTML =
                    `${isRecipe ? '✨' : '📦'} ${count} Bahan (Rp ${Math.round(totalCost).toLocaleString('id-ID')})`;
            } else {
                btn.className = 'btn btn-sm btn-outline-secondary btn-bahan-trigger btn-open-bahan-modal';
                btn.innerHTML = `📦 Atur Bahan <span class="badge bg-secondary rounded-pill ms-1">0</span>`;
            }
        }

        function onSkuInputChanged(inputEl) {
            updateMasterSkuDatalist(inputEl.value);
            const tr = inputEl.closest('tr');
            if (tr) handleSkuSelection(tr);
        }

        function onSkuInputBlur(inputEl) {
            onSkuInputChanged(inputEl);
        }

        function onProductNameInputChanged(inputEl) {
            updateMasterProductNameDatalist(inputEl.value);
            const tr = inputEl.closest('tr');
            if (tr) handleSkuSelection(tr);
        }

        // Event delegation for dynamically added and static row inputs
        document.addEventListener('input', function(e) {
            if (e.target.classList.contains('row-sku-produk') || e.target.classList.contains('input-sku-produk')) {
                onSkuInputChanged(e.target);
            } else if (e.target.classList.contains('row-nama-produk') || e.target.classList.contains(
                    'input-nama-produk')) {
                onProductNameInputChanged(e.target);
            }
        });

        document.addEventListener('focusin', function(e) {
            if (e.target.classList.contains('row-sku-produk') || e.target.classList.contains('input-sku-produk')) {
                updateMasterSkuDatalist(e.target.value);
            } else if (e.target.classList.contains('row-nama-produk') || e.target.classList.contains(
                    'input-nama-produk')) {
                updateMasterProductNameDatalist(e.target.value);
            }
        });

        function updateInventoryItemsDatalist(queryStr = '') {
            const datalist = document.getElementById('inventory_items_datalist');
            if (!datalist) return;
            const cleanQ = queryStr.trim().toLowerCase();
            datalist.innerHTML = '';
            let count = 0;
            for (const item of allInventoryItemsList) {
                if (cleanQ === '' || item.toLowerCase().includes(cleanQ)) {
                    const opt = document.createElement('option');
                    opt.value = item;
                    datalist.appendChild(opt);
                    count++;
                    if (count >= 10) break;
                }
            }
        }

        function updateMasterProductNameDatalist(queryStr = '') {
            const datalist = document.getElementById('master_product_names_datalist');
            if (!datalist) return;
            const cleanQ = queryStr.trim().toLowerCase();
            datalist.innerHTML = '';
            let count = 0;
            for (const prod of allMasterProductsList) {
                const nameStr = (prod.name || '').toLowerCase();
                const skuStr = (prod.sku || '').toLowerCase();
                if (cleanQ === '' || nameStr.includes(cleanQ) || skuStr.includes(cleanQ)) {
                    const opt = document.createElement('option');
                    opt.value = prod.name;
                    const labelSku = prod.sku ? prod.sku + ' — ' : '';
                    const labelUk = prod.ukuran ? ' (Ukuran: ' + prod.ukuran + ')' : '';
                    opt.textContent = labelSku + labelUk;
                    datalist.appendChild(opt);
                    count++;
                    if (count >= 10) break;
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // 1. DESAIN Drop Area
            const dropArea = document.getElementById('desain-drop-area');
            const fileInput = document.getElementById('input-spk-image');
            const placeholder = document.getElementById('desain-placeholder-content');
            const previewImg = document.getElementById('desain-preview-img');

            if (dropArea && fileInput) {
                fileInput.addEventListener('change', function() {
                    const file = this.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            previewImg.src = e.target.result;
                            previewImg.classList.remove('d-none');
                            if (placeholder) placeholder.classList.add('d-none');
                            dropArea.classList.add('has-image');
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }

            // 1b. Referensi Klien Preview
            const refInput = document.getElementById('input-referensi-klien');
            const refPreview = document.getElementById('ref-preview-img');
            const refPlaceholder = document.getElementById('ref-placeholder-content');
            const refDropZone = document.getElementById('ref-drop-zone');

            if (refInput && refPreview) {
                refInput.addEventListener('change', function() {
                    const file = this.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            refPreview.src = e.target.result;
                            refPreview.classList.remove('d-none');
                            if (refPlaceholder) refPlaceholder.classList.add('d-none');
                            if (refDropZone) refDropZone.classList.add('has-file');
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }

            // 1c. Mockup Final Preview
            const mockupInput = document.getElementById('input-mockup-final');
            const mockupPreview = document.getElementById('mockup-preview-img');
            const mockupPlaceholder = document.getElementById('mockup-placeholder-content');
            const mockupDropZone = document.getElementById('mockup-drop-zone');

            if (mockupInput && mockupPreview) {
                mockupInput.addEventListener('change', function() {
                    const file = this.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            mockupPreview.src = e.target.result;
                            mockupPreview.classList.remove('d-none');
                            if (mockupPlaceholder) mockupPlaceholder.classList.add('d-none');
                            if (mockupDropZone) mockupDropZone.classList.add('has-file');

                            // Also sync to top DESAIN box preview if no explicit main image file selected
                            if (fileInput && !fileInput.files.length) {
                                if (previewImg) {
                                    previewImg.src = e.target.result;
                                    previewImg.classList.remove('d-none');
                                }
                                if (placeholder) placeholder.classList.add('d-none');
                                if (dropArea) dropArea.classList.add('has-image');
                            }
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }

            // 2. No Produksi Badge
            const noProduksiInput = document.getElementById('no_produksi_input');
            const produksiBadge = document.getElementById('produksi-badge');
            const tahapSelect = document.getElementById('tahap_saat_ini_select');

            function updateProduksiBadge() {
                if (!noProduksiInput) return;
                const val = noProduksiInput.value.trim();
                if (val === '') {
                    produksiBadge.textContent = '🕐 KOSONG = DRAFT';
                    produksiBadge.classList.remove('is-filled');
                } else {
                    produksiBadge.textContent = '✅ ' + val;
                    produksiBadge.classList.add('is-filled');
                }
            }
            if (noProduksiInput) {
                noProduksiInput.addEventListener('input', updateProduksiBadge);
                updateProduksiBadge();
            }

            // 3. Tipe SPK Switch
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

            // 4. Modal Handlers
            let activeModalRIdx = null;
            let activeModalPIdx = null;
            let modalBahanCounter = 0;
            const bahanModal = new bootstrap.Modal(document.getElementById('modalBahanProduk'));
            const tahapModal = new bootstrap.Modal(document.getElementById('modalTahapOperasional'));

            function escHtml(str) {
                if (!str) return '';
                return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g,
                    '&quot;').replace(/'/g, '&#039;');
            }

            function formatRupiah(val) {
                return 'Rp ' + (parseFloat(val) || 0).toLocaleString('id-ID');
            }

            function formatNumberWithDots(val) {
                if (val === null || val === undefined) return '';
                let clean = val.toString().replace(/\D/g, '');
                return clean.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            }

            function cleanNumberFromDots(val) {
                if (!val) return 0;
                return parseFloat(val.toString().replace(/\./g, '')) || 0;
            }

            // Live SPK Cost and HPP calculation (Level SPK)
            window.recalculateSpkCosts = function() {
                // 1. Total Qty Produk
                let grandQty = 0;
                document.querySelectorAll('.row-qty-produksi').forEach(input => {
                    grandQty += Math.max(0, parseInt(input.value) || 0);
                });

                // 2. Total Biaya Bahan
                let totalBahan = 0;
                document.querySelectorAll('#spkBahanTableBody tr').forEach(tr => {
                    const qtyStr = tr.querySelector('.spk-bahan-qty')?.value || '1';
                    const cleanQty = parseFloat(qtyStr.toString().replace(/\./g, '').replace(',', '.')) || 1;
                    const harga = cleanNumberFromDots(tr.querySelector('.spk-bahan-harga')?.value || '0');
                    const subtotal = Math.round(cleanQty * harga);
                    
                    const subVal = tr.querySelector('.spk-bahan-subtotal-val');
                    if (subVal) subVal.value = subtotal;
                    
                    const subText = tr.querySelector('.spk-bahan-subtotal-text');
                    if (subText) subText.textContent = formatRupiah(subtotal);
                    
                    totalBahan += subtotal;
                });

                const footerBahan = document.getElementById('spkTotalBahanFooter');
                if (footerBahan) footerBahan.textContent = formatRupiah(totalBahan);

                // 3. Biaya Produksi
                const bpInput = document.getElementById('spk_biaya_produksi');
                const totalProduksi = bpInput ? cleanNumberFromDots(bpInput.value) : 0;

                // 4. Biaya Tambahan
                const btInput = document.getElementById('spk_biaya_tambahan');
                const totalTambahan = btInput ? cleanNumberFromDots(btInput.value) : 0;

                // 5. Grand Total & Estimasi HPP
                const grandTotalHpp = totalBahan + totalProduksi + totalTambahan;
                const avgHpp = grandQty > 0 ? Math.round(grandTotalHpp / grandQty) : 0;

                // Update summary elements
                const elQty = document.getElementById('spkSummaryTotalQty');
                if (elQty) elQty.textContent = grandQty.toLocaleString('id-ID') + ' pcs';

                const elMat = document.getElementById('spkSummaryTotalBahan');
                if (elMat) elMat.textContent = formatRupiah(totalBahan);

                const elLabor = document.getElementById('spkSummaryTotalBiayaProduksi');
                if (elLabor) elLabor.textContent = formatRupiah(totalProduksi);

                const elTambahan = document.getElementById('spkSummaryTotalBiayaTambahan');
                if (elTambahan) elTambahan.textContent = formatRupiah(totalTambahan);

                const elHpp = document.getElementById('spkSummaryGrandTotalHpp');
                if (elHpp) elHpp.textContent = formatRupiah(grandTotalHpp);

                const elAvg = document.getElementById('spkSummaryAvgHpp');
                if (elAvg) elAvg.textContent = formatRupiah(avgHpp) + ' / pcs';
            };

            // Aliases for backwards compatibility
            window.recalculateRowHpp = window.recalculateSpkCosts;
            window.recalculateSpkGrandSummary = window.recalculateSpkCosts;

            // Auto-format numeric inputs with dots on typing
            document.addEventListener('input', function(e) {
                if (e.target.classList.contains('numeric-dot-format')) {
                    let cursorPosition = e.target.selectionStart;
                    let oldLength = e.target.value.length;

                    let formatted = formatNumberWithDots(e.target.value);
                    e.target.value = formatted;

                    let newLength = formatted.length;
                    let newPosition = cursorPosition + (newLength - oldLength);
                    if (newPosition < 0) newPosition = 0;
                    e.target.setSelectionRange(newPosition, newPosition);
                }
            });

            window.openBahanModalForProduct = function(rIdx, pIdx) {
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
                        addModalBahanRow({
                            nama_bahan: nBahan,
                            qty_bahan: qBahan,
                            harga: hBahan
                        });
                    });
                } else {
                    addModalBahanRow();
                }

                calculateModalTotalBahan();
                bahanModal.show();
            };

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
                    <input type="text" class="form-control text-end modal-row-harga-bahan numeric-dot-format" placeholder="0" value="${formatNumberWithDots(hargaVal)}">
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
                    const harga = cleanNumberFromDots(tr.querySelector('.modal-row-harga-bahan')?.value ||
                        '0');
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

                const container = productTr.querySelector(
                    `.hidden-bahan-container-${activeModalRIdx}-${activeModalPIdx}`);
                if (!container) return;

                container.innerHTML = '';
                const modalTbody = document.getElementById('modalBahanTableBody');
                let count = 0;

                modalTbody.querySelectorAll('tr').forEach((tr, bIdx) => {
                    const nBahan = tr.querySelector('.modal-row-nama-bahan')?.value?.trim();
                    const qBahan = tr.querySelector('.modal-row-qty-bahan')?.value || '1';
                    const hBahanVal = cleanNumberFromDots(tr.querySelector('.modal-row-harga-bahan')
                        ?.value || '0');
                    const subtotal = (parseFloat(qBahan) || 1) * hBahanVal;

                    if (nBahan) {
                        const hiddenDiv = document.createElement('div');
                        hiddenDiv.className = `hidden-bahan-row hidden-bahan-${bIdx}`;
                        hiddenDiv.innerHTML = `
                        <input type="hidden" name="rincian[${activeModalRIdx}][produk][${activeModalPIdx}][bahan][${bIdx}][nama_bahan]" value="${escHtml(nBahan)}">
                        <input type="hidden" name="rincian[${activeModalRIdx}][produk][${activeModalPIdx}][bahan][${bIdx}][qty_bahan]" value="${escHtml(qBahan)}">
                        <input type="hidden" name="rincian[${activeModalRIdx}][produk][${activeModalPIdx}][bahan][${bIdx}][harga]" value="${hBahanVal}">
                        <input type="hidden" name="rincian[${activeModalRIdx}][produk][${activeModalPIdx}][bahan][${bIdx}][subtotal]" value="${subtotal}">
                    `;
                        container.appendChild(hiddenDiv);
                        count++;
                    }
                });

                const btn = productTr.querySelector('.btn-bahan-trigger');
                if (btn) {
                    if (count > 0) {
                        btn.className =
                            'btn btn-sm btn-success-subtle text-success border border-success-subtle btn-bahan-trigger btn-open-bahan-modal';
                        btn.innerHTML = `📦 ${count} Bahan (${formatRupiah(totalCost)})`;
                    } else {
                        btn.className = 'btn btn-sm btn-outline-secondary btn-bahan-trigger btn-open-bahan-modal';
                        btn.innerHTML = `📦 Atur Bahan <span class="badge bg-secondary rounded-pill ms-1">0</span>`;
                    }
                }

                if (window.recalculateRowHpp) {
                    window.recalculateRowHpp(productTr);
                }
            }

            document.getElementById('btnModalAddBahanRow').addEventListener('click', function() {
                addModalBahanRow();
            });

            document.getElementById('modalBahanTableBody').addEventListener('input', function(e) {
                if (e.target.classList.contains('modal-row-nama-bahan')) {
                    updateInventoryItemsDatalist(e.target.value);
                    const cleanName = e.target.value.trim().toUpperCase();
                    if (cleanName && inventoryItemsMap[cleanName]) {
                        const tr = e.target.closest('tr');
                        if (tr) {
                            const hInput = tr.querySelector('.modal-row-harga-bahan');
                            if (hInput) {
                                hInput.value = formatNumberWithDots(inventoryItemsMap[cleanName]
                                    .cost_price);
                            }
                        }
                    }
                }
                if (e.target.classList.contains('modal-row-qty-bahan') || e.target.classList.contains(
                        'modal-row-harga-bahan') || e.target.classList.contains('modal-row-nama-bahan')) {
                    calculateModalTotalBahan();
                }
            });

            document.getElementById('modalBahanTableBody').addEventListener('click', function(e) {
                const btnRemove = e.target.closest('.btn-remove-modal-bahan-row');
                if (btnRemove) {
                    btnRemove.closest('tr').remove();
                    calculateModalTotalBahan();
                }
            });

            let activeTahapRIdx = null;
            let activeTahapPIdx = null;

            window.openTahapModalForProduct = function(rIdx, pIdx) {
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
                    document.getElementById('modal_pemotong').value = container.querySelector('.h-pemotong')
                        ?.value || '';
                    document.getElementById('modal_qty_potong').value = container.querySelector('.h-qty-potong')
                        ?.value || qtyVal;
                    document.getElementById('modal_tarif_potong').value = formatNumberWithDots(container
                        .querySelector('.h-tarif-potong')?.value || '');

                    document.getElementById('modal_penjahit').value = container.querySelector('.h-penjahit')
                        ?.value || '';
                    document.getElementById('modal_qty_jahit').value = container.querySelector('.h-qty-jahit')
                        ?.value || qtyVal;
                    document.getElementById('modal_tarif_jahit').value = formatNumberWithDots(container
                        .querySelector('.h-tarif-jahit')?.value || '');

                    document.getElementById('modal_vendor_kancing').value = container.querySelector(
                        '.h-vendor-kancing')?.value || '';
                    document.getElementById('modal_qty_kancing').value = container.querySelector(
                        '.h-qty-kancing')?.value || '';
                    document.getElementById('modal_tarif_kancing').value = formatNumberWithDots(container
                        .querySelector('.h-tarif-kancing')?.value || '');

                    document.getElementById('modal_petugas_qc').value = container.querySelector('.h-petugas-qc')
                        ?.value || '';
                    document.getElementById('modal_qc_lolos').value = container.querySelector('.h-qc-lolos')
                        ?.value || '';
                    document.getElementById('modal_qc_reject').value = container.querySelector('.h-qc-reject')
                        ?.value || '';
                    document.getElementById('modal_tarif_qc').value = formatNumberWithDots(container
                        .querySelector('.h-tarif-qc')?.value || '');

                    document.getElementById('modal_petugas_finishing').value = container.querySelector(
                        '.h-petugas-finishing')?.value || '';
                    document.getElementById('modal_qty_finishing').value = container.querySelector(
                        '.h-qty-finishing')?.value || '';
                    document.getElementById('modal_qty_fgood').value = container.querySelector('.h-qty-fgood')
                        ?.value || '';
                    document.getElementById('modal_tarif_finishing').value = formatNumberWithDots(container
                        .querySelector('.h-tarif-finishing')?.value || '');
                }

                calculateModalTahapLaborTotal();
                tahapModal.show();
            };

            function calculateModalTahapLaborTotal() {
                const qPotong = parseFloat(document.getElementById('modal_qty_potong').value) || 0;
                const tPotong = cleanNumberFromDots(document.getElementById('modal_tarif_potong').value);
                const subPotong = qPotong * tPotong;
                document.querySelector('.subtotal-potong-display').textContent = 'Subtotal: ' + formatRupiah(
                    subPotong);

                const qJahit = parseFloat(document.getElementById('modal_qty_jahit').value) || 0;
                const tJahit = cleanNumberFromDots(document.getElementById('modal_tarif_jahit').value);
                const subJahit = qJahit * tJahit;
                document.querySelector('.subtotal-jahit-display').textContent = 'Subtotal: ' + formatRupiah(
                    subJahit);

                const qKancing = parseFloat(document.getElementById('modal_qty_kancing').value) || 0;
                const tKancing = cleanNumberFromDots(document.getElementById('modal_tarif_kancing').value);
                const subKancing = qKancing * tKancing;
                document.querySelector('.subtotal-kancing-display').textContent = 'Subtotal: ' + formatRupiah(
                    subKancing);

                const qQc = parseFloat(document.getElementById('modal_qc_lolos').value) || 0;
                const tQc = cleanNumberFromDots(document.getElementById('modal_tarif_qc').value);
                const subQc = qQc * tQc;
                document.querySelector('.subtotal-qc-display').textContent = 'Subtotal: ' + formatRupiah(subQc);

                const qFinishing = parseFloat(document.getElementById('modal_qty_finishing').value) || 0;
                const tFinishing = cleanNumberFromDots(document.getElementById('modal_tarif_finishing').value);
                const subFinishing = qFinishing * tFinishing;
                const subFinishingDisplay = document.querySelector('.subtotal-finishing-display');
                if (subFinishingDisplay) subFinishingDisplay.textContent = 'Subtotal: ' + formatRupiah(
                    subFinishing);

                const totalLabor = subPotong + subJahit + subKancing + subQc + subFinishing;
                document.getElementById('modalTotalLaborDisplay').textContent = 'Total Ongkos Jasa: ' +
                    formatRupiah(totalLabor);
                return totalLabor;
            }

            document.querySelectorAll('.modal-op-field').forEach(field => {
                field.addEventListener('input', calculateModalTahapLaborTotal);
            });

            document.getElementById('btnSaveModalTahap').addEventListener('click', function() {
                if (activeTahapRIdx === null || activeTahapPIdx === null) return;
                const tr = document.getElementById(`product-row-${activeTahapRIdx}-${activeTahapPIdx}`);
                if (!tr) return;

                const container = tr.querySelector(
                    `.hidden-tahap-container-${activeTahapRIdx}-${activeTahapPIdx}`);
                if (!container) return;

                const pemotong = document.getElementById('modal_pemotong').value.trim();
                const qtyPotong = document.getElementById('modal_qty_potong').value || '0';
                const tarifPotong = cleanNumberFromDots(document.getElementById('modal_tarif_potong')
                    .value);

                const penjahit = document.getElementById('modal_penjahit').value.trim();
                const qtyJahit = document.getElementById('modal_qty_jahit').value || '0';
                const tarifJahit = cleanNumberFromDots(document.getElementById('modal_tarif_jahit').value);

                const vendorKancing = document.getElementById('modal_vendor_kancing').value.trim();
                const qtyKancing = document.getElementById('modal_qty_kancing').value || '0';
                const tarifKancing = cleanNumberFromDots(document.getElementById('modal_tarif_kancing')
                    .value);

                const petugasQc = document.getElementById('modal_petugas_qc').value.trim();
                const qcLolos = document.getElementById('modal_qc_lolos').value || '0';
                const qcReject = document.getElementById('modal_qc_reject').value || '0';
                const tarifQc = cleanNumberFromDots(document.getElementById('modal_tarif_qc').value);

                const petugasFinishing = document.getElementById('modal_petugas_finishing').value.trim();
                const qtyFinishing = document.getElementById('modal_qty_finishing').value || '0';
                const qtyFgood = document.getElementById('modal_qty_fgood').value || '0';
                const tarifFinishing = cleanNumberFromDots(document.getElementById('modal_tarif_finishing')
                    .value);

                if (container.querySelector('.h-pemotong')) container.querySelector('.h-pemotong').value =
                    pemotong;
                if (container.querySelector('.h-qty-potong')) container.querySelector('.h-qty-potong')
                    .value = qtyPotong;
                if (container.querySelector('.h-tarif-potong')) container.querySelector('.h-tarif-potong')
                    .value = tarifPotong;

                if (container.querySelector('.h-penjahit')) container.querySelector('.h-penjahit').value =
                    penjahit;
                if (container.querySelector('.h-qty-jahit')) container.querySelector('.h-qty-jahit').value =
                    qtyJahit;
                if (container.querySelector('.h-tarif-jahit')) container.querySelector('.h-tarif-jahit')
                    .value = tarifJahit;

                if (container.querySelector('.h-vendor-kancing')) container.querySelector(
                    '.h-vendor-kancing').value = vendorKancing;
                if (container.querySelector('.h-qty-kancing')) container.querySelector('.h-qty-kancing')
                    .value = qtyKancing;
                if (container.querySelector('.h-tarif-kancing')) container.querySelector('.h-tarif-kancing')
                    .value = tarifKancing;

                if (container.querySelector('.h-petugas-qc')) container.querySelector('.h-petugas-qc')
                    .value = petugasQc;
                if (container.querySelector('.h-qc-lolos')) container.querySelector('.h-qc-lolos').value =
                    qcLolos;
                if (container.querySelector('.h-qc-reject')) container.querySelector('.h-qc-reject').value =
                    qcReject;
                if (container.querySelector('.h-tarif-qc')) container.querySelector('.h-tarif-qc').value =
                    tarifQc;

                if (container.querySelector('.h-qty-finishing')) container.querySelector('.h-qty-finishing')
                    .value = qtyFinishing;
                if (container.querySelector('.h-qty-fgood')) container.querySelector('.h-qty-fgood').value =
                    qtyFgood;

                const totalLaborCost = calculateModalTahapLaborTotal();

                const btnTahap = tr.querySelector('.btn-tahap-trigger');
                if (btnTahap) {
                    if (pemotong || penjahit || vendorKancing || petugasQc || petugasFinishing ||
                        totalLaborCost > 0) {
                        btnTahap.className =
                            'btn btn-sm btn-primary-subtle text-primary border border-primary-subtle btn-tahap-trigger btn-open-tahap-modal';
                        let labelText = '';
                        if (penjahit) labelText = `${penjahit}`;
                        else if (pemotong) labelText = `Potong: ${pemotong}`;
                        else labelText = `Jasa SPK`;
                        btnTahap.innerHTML = `✂️ ${escHtml(labelText)} (${formatRupiah(totalLaborCost)})`;
                    } else {
                        btnTahap.className =
                            'btn btn-sm btn-outline-primary btn-tahap-trigger btn-open-tahap-modal';
                        btnTahap.innerHTML = `✂️ Atur Tahap`;
                    }
                }

                tahapModal.hide();
            });

            // Delegate event triggers for buttons inside rincianContainer
            document.getElementById('rincianContainer').addEventListener('click', function(e) {
                const btnOpenBahan = e.target.closest('.btn-open-bahan-modal');
                if (btnOpenBahan) {
                    const rIdx = btnOpenBahan.dataset.rIdx || '0';
                    const pIdx = btnOpenBahan.dataset.pIdx || '0';
                    openBahanModalForProduct(rIdx, pIdx);
                }

                const btnOpenTahap = e.target.closest('.btn-open-tahap-modal');
                if (btnOpenTahap) {
                    const rIdx = btnOpenTahap.dataset.rIdx || '0';
                    const pIdx = btnOpenTahap.dataset.pIdx || '0';
                    openTahapModalForProduct(rIdx, pIdx);
                }
            });
            // Modal Pembayaran Ongkos Jasa Per Vendor calculation
            function calculateTotalPaySelected() {
                let grandTotal = 0;
                document.querySelectorAll('#modalPayLabor tbody tr').forEach(tr => {
                    const cb = tr.querySelector('.pay-item-checkbox');
                    const amtInput = tr.querySelector('.pay-item-amount');
                    if (cb && cb.checked && amtInput) {
                        grandTotal += parseFloat(amtInput.value) || 0;
                    }
                });
                const disp = document.getElementById('displayTotalSelectedPay');
                if (disp) disp.textContent = formatRupiah(grandTotal);
            }

            const checkAllCb = document.getElementById('checkAllPayItems');
            if (checkAllCb) {
                checkAllCb.addEventListener('change', function() {
                    document.querySelectorAll('.pay-item-checkbox').forEach(cb => {
                        cb.checked = checkAllCb.checked;
                    });
                    calculateTotalPaySelected();
                });
            }

            document.querySelectorAll('.pay-item-checkbox, .pay-item-amount').forEach(el => {
                el.addEventListener('input', calculateTotalPaySelected);
                el.addEventListener('change', calculateTotalPaySelected);
            });

            // Event delegation for removing product or bahan row
            document.addEventListener('click', function(e) {
                const btnRemoveBahan = e.target.closest('.btn-remove-spk-bahan');
                if (btnRemoveBahan) {
                    const tr = btnRemoveBahan.closest('tr');
                    if (tr) {
                        const tbody = tr.closest('tbody');
                        if (tbody && tbody.querySelectorAll('tr').length > 1) {
                            tr.remove();
                        } else if (tr) {
                            // Reset to default
                            const nInp = tr.querySelector('.spk-bahan-nama');
                            if (nInp) nInp.value = 'BB-TH';
                            const qInp = tr.querySelector('.spk-bahan-qty');
                            if (qInp) qInp.value = '1';
                            const sInp = tr.querySelector('.spk-bahan-satuan');
                            if (sInp) sInp.value = 'Roll';
                            const hInp = tr.querySelector('.spk-bahan-harga');
                            if (hInp) hInp.value = '0';
                            const subVal = tr.querySelector('.spk-bahan-subtotal-val');
                            if (subVal) subVal.value = '0';
                            const subTxt = tr.querySelector('.spk-bahan-subtotal-text');
                            if (subTxt) subTxt.textContent = 'Rp 0';
                        }
                        if (window.recalculateSpkCosts) {
                            window.recalculateSpkCosts();
                        }
                    }
                }

                const btnRemoveProd = e.target.closest('.btn-remove-product-row');
                if (btnRemoveProd) {
                    const tr = btnRemoveProd.closest('tr');
                    if (tr) {
                        const tbody = tr.closest('tbody');
                        if (tbody && tbody.querySelectorAll('tr').length > 1) {
                            tr.remove();
                            if (window.recalculateSpkCosts) {
                                window.recalculateSpkCosts();
                            }
                        } else {
                            alert('Minimal 1 varian produk harus ada dalam SPK.');
                        }
                    }
                }
            });

            // Auto-fill unit & cost price when choosing/typing raw material from inventory list
            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('spk-bahan-nama')) {
                    const tr = e.target.closest('tr');
                    if (!tr) return;
                    const valUpper = e.target.value.trim().toUpperCase();
                    if (inventoryItemsMap && inventoryItemsMap[valUpper]) {
                        const inv = inventoryItemsMap[valUpper];
                        const sInp = tr.querySelector('.spk-bahan-satuan');
                        if (sInp && (!sInp.value || sInp.value === 'Roll')) {
                            sInp.value = inv.unit || 'Roll';
                        }
                        const hInp = tr.querySelector('.spk-bahan-harga');
                        if (hInp && (cleanNumberFromDots(hInp.value) === 0)) {
                            hInp.value = formatNumberWithDots(inv.cost_price);
                        }
                        if (window.recalculateSpkCosts) {
                            window.recalculateSpkCosts();
                        }
                    }
                }
            });

            // Live calculation on any SPK cost / qty input
            document.addEventListener('input', function(e) {
                if (
                    e.target.classList.contains('row-qty-produksi') ||
                    e.target.classList.contains('spk-bahan-qty') ||
                    e.target.classList.contains('spk-bahan-harga') ||
                    e.target.id === 'spk_biaya_produksi' ||
                    e.target.id === 'spk_biaya_tambahan'
                ) {
                    if (window.recalculateSpkCosts) {
                        window.recalculateSpkCosts();
                    }
                }
            });

            // Initial calculation on page load
            if (window.recalculateSpkCosts) {
                window.recalculateSpkCosts();
            }

            const modalPayLaborEl = document.getElementById('modalPayLabor');
            if (modalPayLaborEl) {
                modalPayLaborEl.addEventListener('shown.bs.modal', calculateTotalPaySelected);
            }

            document.getElementById('btnShowQuick50')?.addEventListener('click', function() {
                const max = {{ (int) $spk->remaining_production_cost }};
                if (max > 0) document.getElementById('showModalInputAmount').value = Math.round(max * 0.5);
            });
            document.getElementById('btnShowQuickLunas')?.addEventListener('click', function() {
                const max = {{ (int) $spk->remaining_production_cost }};
                if (max > 0) document.getElementById('showModalInputAmount').value = max;
            });
        });
    </script>
@endpush
