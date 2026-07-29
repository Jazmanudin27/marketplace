<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Panel Produksi - {{ $spk->no_produksi ?: $spk->no_spk }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-blue: #2563eb;
            --header-blue: #1d4ed8;
            --green-success: #10b981;
            --purple-sampling: #8b5cf6;
            --cyan-print: #0284c7;
            --emerald-lkpk: #059669;
            --bg-canvas: #f8fafc;
        }

        body {
            background-color: var(--bg-canvas);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #1e293b;
            padding-bottom: 110px;
            margin: 0;
        }

        /* ── Top Header Banner (Match Screenshots) ── */
        .panel-header-container {
            background: linear-gradient(135deg, #1e40af 0%, #2563eb 60%, #3b82f6 100%);
            color: #ffffff;
            padding: 20px 24px;
            box-shadow: 0 4px 18px rgba(37, 99, 235, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .panel-subtitle {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            opacity: 0.85;
            margin-bottom: 2px;
        }

        .panel-title-code {
            font-size: 28px;
            font-weight: 900;
            letter-spacing: 1px;
            margin: 0;
            line-height: 1.1;
        }

        .btn-lock-icon {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.35);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            cursor: pointer;
        }

        /* ── Banner Status "Akses Produksi Dibuka!" ── */
        .alert-akses-dibuka {
            background-color: #ecfdf5;
            border: 1.5px solid #a7f3d0;
            color: #047857;
            font-size: 16px;
            font-weight: 800;
            text-align: center;
            padding: 14px 20px;
            border-radius: 16px;
            margin-bottom: 16px;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.08);
        }

        /* ── Card Containers ── */
        .app-card {
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
            border: 1px solid #e2e8f0;
            margin-bottom: 16px;
            overflow: hidden;
        }

        .card-body-padding {
            padding: 16px;
        }

        /* ── Product & Fabric Card ── */
        .product-thumb-box {
            width: 68px;
            height: 68px;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            object-fit: cover;
            background: #f1f5f9;
        }

        .product-title-name {
            font-size: 16px;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .fabric-badge {
            display: inline-flex;
            align-items: center;
            background: #fff7ed;
            color: #ea580c;
            font-size: 11px;
            font-weight: 800;
            padding: 4px 10px;
            border-radius: 8px;
            text-transform: uppercase;
            gap: 6px;
        }

        /* ── Tahapan Saat Ini Select ── */
        .select-label-title {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1px;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .select-tahapan-custom {
            border: 2.5px solid #2563eb !important;
            border-radius: 14px !important;
            font-size: 16px !important;
            font-weight: 800 !important;
            color: #1e3a8a !important;
            padding: 14px 16px !important;
            background-color: #ffffff !important;
            box-shadow: 0 3px 10px rgba(37, 99, 235, 0.08) !important;
            cursor: pointer;
        }

        /* ── TAHAP LKPK UI ── */
        .panel-bom-banner {
            background: linear-gradient(135deg, #059669 0%, #0d9488 100%);
            border-radius: 18px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(5, 150, 105, 0.25);
            margin-bottom: 16px;
        }

        .lkpk-avatar-badge {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: #d1fae5;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-tambah-lkpk {
            background: #ecfdf5;
            color: #059669;
            border: 1.5px solid #a7f3d0;
            font-size: 14px;
            font-weight: 800;
            padding: 12px;
            border-radius: 14px;
            box-shadow: 0 2px 6px rgba(5, 150, 105, 0.06);
        }

        .btn-tambah-lkpk:hover {
            background: #d1fae5;
            color: #047857;
        }

        /* ── TAHAP JAHIT UI ── */
        .tailor-avatar-badge {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: #f3e8ff;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-tambah-penjahit {
            background: #faf5ff;
            color: #9333ea;
            border: 1.5px solid #e9d5ff;
            font-size: 14px;
            font-weight: 800;
            padding: 12px;
            border-radius: 14px;
            box-shadow: 0 2px 6px rgba(147, 51, 234, 0.06);
        }

        .btn-tambah-penjahit:hover {
            background: #f3e8ff;
            color: #7e22ce;
        }

        /* ── TAHAP PEMOTONGAN UI ── */
        .potong-dropzone-box {
            border: 2px dashed #93c5fd;
            border-radius: 16px;
            padding: 30px 20px;
            background: #eff6ff;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .potong-dropzone-box:hover {
            background: #dbeafe;
            border-color: #2563eb;
        }

        .cutter-avatar-badge {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: #dbeafe;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* ── TAHAP PRINT KAIN UI ── */
        .panel-print-banner {
            background: linear-gradient(135deg, #0284c7 0%, #06b6d4 100%);
            border-radius: 18px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(2, 132, 199, 0.25);
            margin-bottom: 16px;
        }

        .print-dropzone-box {
            border: 2px dashed #38bdf8;
            border-radius: 16px;
            padding: 30px 20px;
            background: #f0f9ff;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .print-dropzone-box:hover {
            background: #e0f2fe;
            border-color: #0284c7;
        }

        .vendor-avatar-badge {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: #cff4fc;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* ── TAHAP SAMPLING UI ── */
        .panel-sampling-banner {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border-radius: 18px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(139, 92, 246, 0.25);
            margin-bottom: 16px;
        }

        .sample-dropzone-box {
            border: 2px dashed #c084fc;
            border-radius: 16px;
            padding: 30px 20px;
            background: #faf5ff;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .sample-dropzone-box:hover {
            background: #f3e8ff;
            border-color: #a855f7;
        }

        .user-avatar-badge {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: #e0e7ff;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Status ACC Radio Group Buttons */
        .btn-check:checked + .btn-outline-warning {
            background-color: #fffbeb !important;
            color: #d97706 !important;
            border: 2px solid #f59e0b !important;
            box-shadow: 0 2px 6px rgba(245, 158, 11, 0.2) !important;
        }

        .btn-check:checked + .btn-outline-secondary {
            background-color: #f8fafc !important;
            color: #475569 !important;
            border: 2px solid #64748b !important;
        }

        .btn-check:checked + .btn-outline-success {
            background-color: #ecfdf5 !important;
            color: #059669 !important;
            border: 2px solid #10b981 !important;
            box-shadow: 0 2px 6px rgba(16, 185, 129, 0.2) !important;
        }

        /* ── Sub-Section: Antrian ── */
        .sub-card-title {
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 1px;
            color: #475569;
            text-transform: uppercase;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .select-antrian-cream {
            background-color: #fffbeb !important;
            border: 1.5px solid #fde68a !important;
            color: #92400e !important;
            font-size: 14px !important;
            font-weight: 800 !important;
            border-radius: 12px !important;
            padding: 12px 14px !important;
            text-align: center;
        }

        .textarea-catatan-proses {
            border: 1.5px solid #cbd5e1 !important;
            border-radius: 12px !important;
            font-size: 14px !important;
            color: #334155 !important;
            padding: 12px !important;
            min-height: 80px;
        }

        /* ── Matriks Lolos QC Card (Green Theme) ── */
        .matriks-header-bar {
            background: #059669;
            color: #ffffff;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 1px;
            text-transform: uppercase;
            padding: 12px 16px;
        }

        .matriks-body-area {
            background: #ecfdf5;
            padding: 16px;
        }

        .sku-header-title {
            font-size: 14px;
            font-weight: 900;
            color: #064e3b;
            font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
            margin-bottom: 10px;
            letter-spacing: 0.5px;
        }

        .size-boxes-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 16px;
        }

        .size-box-item {
            width: 74px;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            background: #ffffff;
            overflow: hidden;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .size-box-label {
            background: #f1f5f9;
            color: #475569;
            font-size: 11px;
            font-weight: 800;
            padding: 4px 0;
            border-bottom: 1px solid #e2e8f0;
            text-transform: uppercase;
        }

        .size-box-input {
            width: 100%;
            border: none;
            text-align: center;
            font-size: 16px;
            font-weight: 900;
            color: #0f172a;
            padding: 8px 0;
            background: transparent;
            font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
        }

        /* ── Reject / Cacat Card (Red Theme) ── */
        .reject-header-bar {
            background: #ef4444;
            color: #ffffff;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 1px;
            text-transform: uppercase;
            padding: 12px 16px;
        }

        .reject-body-area {
            background: #fef2f2;
            padding: 16px;
            text-align: center;
        }

        .btn-tambah-reject {
            background: #ffffff;
            color: #ef4444;
            border: 1.5px solid #fca5a5;
            font-size: 13px;
            font-weight: 800;
            padding: 8px 20px;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(239, 68, 68, 0.08);
        }

        /* ── Sticky Bottom Bar (Match Screenshots) ── */
        .sticky-footer-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #ffffff;
            padding: 12px 16px;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.08);
            z-index: 1040;
            border-top: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .footer-update-text {
            font-size: 11px;
            color: #94a3b8;
            line-height: 1.3;
        }

        .btn-simpan-cloud {
            background: #2563eb;
            color: #ffffff;
            font-weight: 900;
            font-size: 15px;
            padding: 12px 24px;
            border-radius: 14px;
            border: none;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        /* ── PIN Overlay Lock Screen ── */
        .pin-overlay-screen {
            position: fixed;
            inset: 0;
            background: #0f172a;
            color: #ffffff;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .pin-dot-item {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            border: 2px solid #38bdf8;
            background: transparent;
            transition: all 0.2s;
        }

        .pin-dot-item.active {
            background: #38bdf8;
            box-shadow: 0 0 12px #38bdf8;
        }

        .keypad-grid-num {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            width: 100%;
            max-width: 280px;
            margin-top: 20px;
        }

        .keypad-btn-num {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #ffffff;
            font-size: 22px;
            font-weight: 800;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            user-select: none;
        }

        .keypad-btn-num:active {
            background: rgba(56, 189, 248, 0.3);
        }
    </style>
</head>
<body>

    <!-- ── PIN LOCK OVERLAY SCREEN ── -->
    <div id="pinLockScreen" class="pin-overlay-screen" style="{{ session('spk_mobile_unlocked_' . $spk->id) ? 'display:none;' : '' }}">
        <div class="text-center mb-3">
            <div class="fs-1 mb-2">🔒</div>
            <h4 class="fw-bold mb-1">PANEL PRODUKSI #{{ $spk->no_produksi ?: $spk->no_spk }}</h4>
            <p class="small text-muted" style="color: #94a3b8 !important;">Masukkan 4-Digit Kode PIN untuk mengakses tracking</p>
        </div>

        <div class="d-flex gap-3 my-3" id="pinDotsWrapper">
            <div class="pin-dot-item"></div>
            <div class="pin-dot-item"></div>
            <div class="pin-dot-item"></div>
            <div class="pin-dot-item"></div>
        </div>

        <div id="pinErrAlert" class="text-danger small fw-bold mb-3" style="display:none;">
            ⚠️ Kode PIN Salah! Silakan coba lagi.
        </div>

        <div class="keypad-grid-num">
            <div class="keypad-btn-num" onclick="pressDigit('1')">1</div>
            <div class="keypad-btn-num" onclick="pressDigit('2')">2</div>
            <div class="keypad-btn-num" onclick="pressDigit('3')">3</div>
            <div class="keypad-btn-num" onclick="pressDigit('4')">4</div>
            <div class="keypad-btn-num" onclick="pressDigit('5')">5</div>
            <div class="keypad-btn-num" onclick="pressDigit('6')">6</div>
            <div class="keypad-btn-num" onclick="pressDigit('7')">7</div>
            <div class="keypad-btn-num" onclick="pressDigit('8')">8</div>
            <div class="keypad-btn-num" onclick="pressDigit('9')">9</div>
            <div class="keypad-btn-num text-danger fs-6 fw-bold" onclick="clearPinCode()">C</div>
            <div class="keypad-btn-num" onclick="pressDigit('0')">0</div>
            <div class="keypad-btn-num text-warning fs-5" onclick="backspacePinCode()"><i class="fas fa-backspace"></i></div>
        </div>

        <div class="mt-4 text-center">
            <span class="text-muted small" style="font-size: 11px;">PIN Default: <strong>1234</strong></span>
        </div>
    </div>

    <!-- ── TOP HEADER BANNER (Match Screenshot) ── -->
    <div class="panel-header-container">
        <div>
            <div class="panel-subtitle">PANEL PRODUKSI</div>
            <h1 class="panel-title-code">{{ $spk->no_produksi ?: $spk->no_spk }}</h1>
        </div>
        <div class="btn-lock-icon" onclick="lockPinScreen()" title="Kunci PIN">
            <i class="fas fa-lock-open"></i>
        </div>
    </div>

    <div class="container-fluid px-3 pt-3">

        <!-- ── BANNER STATUS "Akses Produksi Dibuka!" ── -->
        <div class="alert-akses-dibuka">
            Akses Produksi Dibuka!
        </div>

        <form id="simpleTrackingForm" action="{{ route('spks.mobile_update_tracking', $spk->id) }}" method="POST" enctype="multipart/form-data">
            @csrf

            <!-- ── CARD 1: PRODUCT & FABRIC ── -->
            <div class="app-card">
                <div class="card-body-padding d-flex align-items-center gap-3">
                    @php
                        $mockupImg = $spk->mockup_url ?: ($spk->image_url ?: $spk->referensi_klien_url);
                    @endphp
                    @if($mockupImg)
                        <img src="{{ $mockupImg }}" class="product-thumb-box" alt="Gambar Desain" loading="lazy">
                    @else
                        <div class="product-thumb-box d-flex align-items-center justify-content-center text-muted">
                            <i class="fas fa-tshirt fs-4"></i>
                        </div>
                    @endif
                    <div>
                        <div class="product-title-name">{{ strtoupper($spk->pemesan ?: 'OFFICE') }}</div>
                        <div class="fabric-badge">
                            <i class="fas fa-folder text-warning"></i> {{ strtoupper($fabricName) }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── CARD 2: TAHAPAN SAAT INI (DROPDOWN EXACT OPTIONS) ── -->
            <div class="app-card">
                <div class="card-body-padding">
                    <div class="select-label-title">TAHAPAN SAAT INI:</div>
                    <select name="spk_status" id="tahapanSelect" class="form-select select-tahapan-custom" onchange="toggleTahapanSubCards()">
                        <option value="Perencanaan" {{ $spk->status === 'Perencanaan' || $spk->status === 'DRAFT' ? 'selected' : '' }}>Perencanaan</option>
                        <option value="Antrian & Sampling" {{ $spk->status === 'Antrian & Sampling' ? 'selected' : '' }}>Antrian &amp; Sampling</option>
                        <option value="Tahap Sampling" {{ $spk->status === 'Tahap Sampling' ? 'selected' : '' }}>Tahap Sampling</option>
                        <option value="Tahap Print Kain" {{ $spk->status === 'Tahap Print Kain' ? 'selected' : '' }}>Tahap Print Kain</option>
                        <option value="Tahap Pemotongan" {{ $spk->status === 'Tahap Pemotongan' || $spk->status === 'DIPROSES' ? 'selected' : '' }}>Tahap Pemotongan</option>
                        <option value="Tahap Jahit" {{ $spk->status === 'Tahap Jahit' ? 'selected' : '' }}>Tahap Jahit</option>
                        <option value="Tahap LKPK" {{ $spk->status === 'Tahap LKPK' ? 'selected' : '' }}>Tahap LKPK</option>
                        <option value="Quality Control" {{ $spk->status === 'Quality Control' || $spk->status === 'QC' ? 'selected' : '' }}>Quality Control</option>
                        <option value="Packing / Finishing" {{ $spk->status === 'Packing / Finishing' || $spk->status === 'FINISHING' ? 'selected' : '' }}>Packing / Finishing</option>
                        <option value="Selesai (Finished Good)" {{ $spk->status === 'Selesai (Finished Good)' || $spk->status === 'SELESAI' ? 'selected' : '' }}>Selesai (Finished Good)</option>
                    </select>
                </div>
            </div>

            <!-- ── CARD DYNAMIC SUB-CONTENT: TAHAP LKPK (MATCH SCREENSHOT 100%) ── -->
            <div id="cardLkpkArea" style="display: none;">
                <!-- 1. Kalkulator Aksesoris (BOM) Banner Card -->
                <div class="panel-bom-banner mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="fs-2 text-white"><i class="fas fa-calculator"></i></div>
                        <div>
                            <h5 class="fw-bold text-white mb-1">Kalkulator Aksesoris (BOM)</h5>
                            <div class="small text-white-50" style="font-size: 11px; line-height: 1.4;">
                                Input jumlah baju yang dikerjakan, sistem akan otomatis menghitung total pemakaian Kancing &amp; Jasa Lubang berdasarkan resep HPP.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Nama Vendor LKPK & Matriks Kalkulator BOM Card -->
                <div class="app-card mb-3">
                    <div class="card-body-padding" style="background: #f0fdf4;">
                        <!-- Nama Vendor LKPK Header Row -->
                        <div class="d-flex align-items-center gap-3 mb-3 p-3 bg-white rounded-3 border">
                            <div class="lkpk-avatar-badge">
                                <i class="fas fa-user-cog fs-5" style="color: #059669;"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="small fw-bold font-monospace mb-1" style="font-size: 11px; color: #059669;">
                                    NAMA VENDOR LKPK
                                </div>
                                <input type="text" name="vendor_lkpk" class="form-control form-control-sm border-0 border-bottom bg-transparent font-monospace fw-bold text-dark p-0" placeholder="Ketik nama vendor..." value="YUDI">
                            </div>
                        </div>

                        <!-- List per-Item SKU + Size with BOM Recipe -->
                        <div class="bg-white p-3 rounded-3 border mb-2">
                            @foreach($spk->items as $item)
                                @php
                                    $size = $item->ukuran ?: 'ALL';
                                    $resepKancing = str_contains(strtolower($size), 'xl') ? 11 : (str_contains(strtolower($size), 'lpk') ? 7 : 9);
                                    $resepLubang = $resepKancing;
                                @endphp
                                <div class="lkpk-item-row mb-3 pb-3 border-bottom">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <span class="fw-bold font-monospace text-dark" style="font-size: 13px;">{{ $item->sku ?: $item->nama_produk }}</span>
                                            <span class="badge bg-secondary font-monospace ms-1">{{ $size }}</span>
                                        </div>
                                        <span class="badge font-monospace" style="background: #fef3c7; color: #92400e; font-size: 10px;">
                                            Resep: {{ $resepKancing }} Kancing | {{ $resepLubang }} Lubang
                                        </span>
                                    </div>

                                    <div class="row g-2">
                                        <div class="col-4 text-center">
                                            <label class="form-label form-label-sm mb-1 fw-bold" style="font-size: 10px; color: #059669;">Qty Baju</label>
                                            <input type="number" 
                                                   id="qty_baju_{{ $item->id }}"
                                                   name="items[{{ $item->id }}][qty_baju]" 
                                                   class="form-control form-control-sm text-center fw-bold font-monospace py-2" 
                                                   value="{{ $item->quantity }}" 
                                                   oninput="hitungBomLkpk({{ $item->id }}, {{ $resepKancing }}, {{ $resepLubang }})"
                                                   style="background: #ecfdf5; border: 1.5px solid #a7f3d0 !important;">
                                        </div>
                                        <div class="col-4 text-center">
                                            <label class="form-label form-label-sm mb-1 fw-bold text-secondary" style="font-size: 10px;">Tot Kancing</label>
                                            <input type="number" 
                                                   id="tot_kancing_{{ $item->id }}"
                                                   name="items[{{ $item->id }}][tot_kancing]" 
                                                   class="form-control form-control-sm text-center fw-bold font-monospace bg-light py-2" 
                                                   value="0" 
                                                   readonly>
                                        </div>
                                        <div class="col-4 text-center">
                                            <label class="form-label form-label-sm mb-1 fw-bold text-secondary" style="font-size: 10px;">Tot Lubang</label>
                                            <input type="number" 
                                                   id="tot_lubang_{{ $item->id }}"
                                                   name="items[{{ $item->id }}][tot_lubang]" 
                                                   class="form-control form-control-sm text-center fw-bold font-monospace bg-light py-2" 
                                                   value="0" 
                                                   readonly>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- 3. Tambah Vendor LKPK Button -->
                <button type="button" class="btn btn-tambah-lkpk w-100 mb-3" onclick="tambahRowLkpk()">
                    <i class="fas fa-user-plus me-1"></i> Tambah Vendor LKPK
                </button>
            </div>

            <!-- ── CARD DYNAMIC SUB-CONTENT: TAHAP JAHIT (MATCH SCREENSHOT 100%) ── -->
            <div id="cardJahitArea" style="display: none;">
                <div class="app-card mb-3">
                    <div class="card-body-padding" style="background: #fdf4ff;">
                        <div class="d-flex align-items-center gap-3 mb-3 p-3 bg-white rounded-3 border">
                            <div class="tailor-avatar-badge">
                                <i class="fas fa-user-tag fs-5" style="color: #c084fc;"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="small fw-bold font-monospace mb-1" style="font-size: 11px; color: #a855f7;">
                                    NAMA PENJAHIT / VENDOR JAHIT
                                </div>
                                @php $currentPenjahit = $spk->items->first()?->penjahit; @endphp
                                <select name="penjahit" class="form-select form-select-sm border-0 border-bottom bg-transparent font-monospace fw-bold text-dark p-0">
                                    <option value="">-- Pilih Penjahit --</option>
                                    @foreach($penjahitList ?? [] as $vName)
                                        <option value="{{ $vName }}" {{ $currentPenjahit === $vName ? 'selected' : '' }}>{{ $vName }}</option>
                                    @endforeach
                                    @if(!empty($currentPenjahit) && !($penjahitList ?? collect())->contains($currentPenjahit))
                                        <option value="{{ $currentPenjahit }}" selected>{{ $currentPenjahit }}</option>
                                    @endif
                                </select>
                            </div>
                        </div>

                        <div id="jahitMatriksContainer" class="bg-white p-3 rounded-3 border mb-3">
                            @foreach($variantRows as $modelName => $row)
                                <div class="sku-header-title text-dark">{{ $modelName }}</div>
                                <div class="size-boxes-grid">
                                    @foreach($row['sizes'] as $szItem)
                                        @php
                                            $item = $szItem['item'];
                                            $pg = $item->progres->first();
                                            $valQty = $pg ? $pg->qty_done : $item->quantity;
                                        @endphp
                                        <div class="size-box-item">
                                            <div class="size-box-label">{{ $szItem['size'] }}</div>
                                            <input type="number" 
                                                   name="{{ $pg ? 'progres['.$pg->id.']' : 'items['.$item->id.'][quantity_done]' }}" 
                                                   class="size-box-input" 
                                                   value="{{ $valQty }}" 
                                                   min="0">
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach

                            <div class="form-check mt-3 pt-2 border-top">
                                <input class="form-check-input" type="checkbox" name="serahkan_ke_qc" id="chkSerahkanQC" value="1">
                                <label class="form-check-label fw-bold text-dark small" for="chkSerahkanQC">
                                    Serahkan ke QC
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="button" class="btn btn-tambah-penjahit w-100 mb-3" onclick="tambahRowPenjahit()">
                    <i class="fas fa-user-plus me-1"></i> Tambah Penjahit
                </button>

                <div class="app-card mb-3">
                    <div class="card-body-padding">
                        <textarea name="catatan_jahit" class="form-control textarea-catatan-proses" placeholder="Catatan jahit...">{{ $savedCatatanJahit ?? '' }}</textarea>
                    </div>
                </div>
            </div>

            <!-- ── CARD DYNAMIC SUB-CONTENT: TAHAP PEMOTONGAN (MATCH SCREENSHOT 100%) ── -->
            <div id="cardPotongArea" style="display: none;">
                <div class="app-card mb-3">
                    <div class="card-body-padding">
                        <div class="sub-card-title mb-3">
                            <i class="fas fa-camera text-primary"></i> BUKTI POTONG (GLOBAL)
                        </div>
                        <div class="potong-dropzone-box" onclick="document.getElementById('potongCameraInput').click()">
                            <input type="file" id="potongCameraInput" name="potong_photo" accept="image/*" capture="environment" style="display:none;" onchange="previewPotongPhoto(this)">
                            @if(!empty($spk->image_url))
                                <div id="potongPreviewPlaceholder" class="text-center" style="display:none;">
                                    <i class="fas fa-camera fs-1 text-primary mb-2"></i>
                                    <div class="fw-bold text-secondary">Jepret Kamera</div>
                                </div>
                                <img id="potongPreviewImg" src="{{ $spk->image_url }}" loading="lazy" style="display:block; max-height: 180px; width:100%; object-fit:contain; border-radius:10px;">
                            @else
                                <div id="potongPreviewPlaceholder" class="text-center">
                                    <i class="fas fa-camera fs-1 text-primary mb-2"></i>
                                    <div class="fw-bold text-secondary">Jepret Kamera</div>
                                </div>
                                <img id="potongPreviewImg" src="" style="display:none; max-height: 180px; width:100%; object-fit:contain; border-radius:10px;">
                            @endif
                        </div>
                    </div>
                </div>

                <div class="app-card mb-3">
                    <div class="card-body-padding" style="background: #f8fafc;">
                        <div class="d-flex align-items-center gap-3 mb-3 p-3 bg-white rounded-3 border">
                            <div class="cutter-avatar-badge">
                                <i class="fas fa-cut text-primary fs-5"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="small fw-bold text-primary font-monospace" style="font-size: 11px;">
                                    PEMOTONG KAIN INI
                                </div>
                                @php $currentPemotong = $spk->items->first()?->pemotong; @endphp
                                <select name="pemotong" class="form-select form-select-sm border-0 border-bottom bg-transparent font-monospace fw-bold text-dark p-0">
                                    <option value="">-- Pilih Pemotong --</option>
                                    @foreach($pemotongList ?? [] as $vName)
                                        <option value="{{ $vName }}" {{ $currentPemotong === $vName ? 'selected' : '' }}>{{ $vName }}</option>
                                    @endforeach
                                    @if(!empty($currentPemotong) && !($pemotongList ?? collect())->contains($currentPemotong))
                                        <option value="{{ $currentPemotong }}" selected>{{ $currentPemotong }}</option>
                                    @endif
                                </select>
                            </div>
                        </div>

                        <div class="bg-white p-3 rounded-3 border mb-3">
                            <label class="form-label form-label-sm mb-1 fw-bold text-secondary" style="font-size: 11px;">Acuan Potong (Material)</label>
                            <input type="text" class="form-control form-control-sm font-monospace fw-bold bg-light mb-3" value="{{ strtoupper($fabricName) }}" readonly>

                            <div class="row g-2">
                                <div class="col-3 text-center">
                                    <label class="form-label form-label-sm mb-1 fw-bold text-secondary" style="font-size: 10px;">Est(m)</label>
                                    <input type="number" name="est_kain_potong" class="form-control form-control-sm text-center fw-bold font-monospace bg-light py-2" value="84" readonly>
                                </div>
                                <div class="col-3 text-center">
                                    <label class="form-label form-label-sm mb-1 fw-bold text-secondary" style="font-size: 10px;">Rdy(m)</label>
                                    <input type="number" name="rdy_kain_potong" class="form-control form-control-sm text-center fw-bold font-monospace py-2" placeholder="" step="0.01">
                                </div>
                                <div class="col-3 text-center">
                                    <label class="form-label form-label-sm mb-1 fw-bold text-primary" style="font-size: 10px;">Pki(m)</label>
                                    <input type="number" name="pki_kain_potong" class="form-control form-control-sm text-center fw-bold font-monospace border-primary py-2" placeholder="" step="0.01">
                                </div>
                                <div class="col-3 text-center">
                                    <label class="form-label form-label-sm mb-1 fw-bold text-warning" style="font-size: 10px;">Sisa(m)</label>
                                    <input type="number" name="sisa_kain_potong" class="form-control form-control-sm text-center fw-bold font-monospace border-warning py-2" placeholder="" step="0.01" style="border-color: #f97316 !important;">
                                </div>
                            </div>
                        </div>

                        <div class="bg-white p-3 rounded-3 border mb-3">
                            <div class="sub-card-title mb-3">
                                <i class="fas fa-scissors text-primary me-1"></i> HASIL POTONG (KAIN INI)
                            </div>
                            @foreach($variantRows as $modelName => $row)
                                <div class="sku-header-title text-primary">SKU: {{ $modelName }}</div>
                                <div class="size-boxes-grid">
                                    @foreach($row['sizes'] as $szItem)
                                        @php
                                            $item = $szItem['item'];
                                            $pg = $item->progres->first();
                                            $valQty = $pg ? $pg->qty_done : $item->quantity;
                                        @endphp
                                        <div class="size-box-item">
                                            <div class="size-box-label">{{ $szItem['size'] }}</div>
                                            <input type="number" 
                                                   name="{{ $pg ? 'progres['.$pg->id.']' : 'items['.$item->id.'][quantity_done]' }}" 
                                                   class="size-box-input" 
                                                   value="{{ $valQty }}" 
                                                   min="0">
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>

                        <div>
                            <textarea name="catatan_pemotongan" class="form-control textarea-catatan-proses" placeholder="Catatan pemotongan...">{{ $savedCatatanPotong ?? '' }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── CARD DYNAMIC SUB-CONTENT: TAHAP PRINT KAIN ── -->
            <div id="cardPrintKainArea" style="display: none;">
                <div class="panel-print-banner mb-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <div class="fs-2 text-white"><i class="fas fa-print"></i></div>
                            <div>
                                <h5 class="fw-bold text-white mb-1">Panel Print Motif</h5>
                                <div class="small text-white-50">Input vendor &amp; konversi SKU per-bahan.</div>
                            </div>
                        </div>
                        <div class="fs-1 text-white opacity-25"><i class="fas fa-tint"></i></div>
                    </div>
                </div>

                <div class="app-card mb-3">
                    <div class="card-body-padding">
                        <div class="sub-card-title mb-3 text-cyan">
                            <i class="fas fa-camera text-info"></i> BUKTI PRINT / ROLL KAIN (GLOBAL)
                        </div>
                        <div class="print-dropzone-box" onclick="document.getElementById('printCameraInput').click()">
                            <input type="file" id="printCameraInput" name="print_photo" accept="image/*" capture="environment" style="display:none;" onchange="previewPrintPhoto(this)">
                            @if(!empty($spk->image_url))
                                <div id="printPreviewPlaceholder" class="text-center" style="display:none;">
                                    <i class="fas fa-camera fs-1 text-info mb-2"></i>
                                    <div class="fw-bold text-secondary">Jepret Hasil Print</div>
                                </div>
                                <img id="printPreviewImg" src="{{ $spk->image_url }}" loading="lazy" style="display:block; max-height: 180px; width:100%; object-fit:contain; border-radius:10px;">
                            @else
                                <div id="printPreviewPlaceholder" class="text-center">
                                    <i class="fas fa-camera fs-1 text-info mb-2"></i>
                                    <div class="fw-bold text-secondary">Jepret Hasil Print</div>
                                </div>
                                <img id="printPreviewImg" src="" style="display:none; max-height: 180px; width:100%; object-fit:contain; border-radius:10px;">
                            @endif
                        </div>
                    </div>
                </div>

                <div class="app-card mb-3">
                    <div class="card-body-padding" style="background: #ecfeff;">
                        <div class="d-flex align-items-center gap-3 mb-3 p-3 bg-white rounded-3 border">
                            <div class="vendor-avatar-badge">
                                <i class="fas fa-industry text-info fs-5"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="small fw-bold text-info font-monospace" style="font-size: 11px;">
                                    VENDOR PRINT ({{ strtoupper($fabricName) }})
                                </div>
                                <select name="vendor_print" class="form-select form-select-sm border-0 border-bottom bg-transparent font-monospace fw-bold text-dark p-0">
                                    <option value="">-- Pilih Vendor Print --</option>
                                    @foreach($vendorPrintList ?? [] as $vName)
                                        <option value="{{ $vName }}" {{ ($savedVendorPrint ?? '') === $vName ? 'selected' : '' }}>{{ $vName }}</option>
                                    @endforeach
                                    @if(!empty($savedVendorPrint) && !($vendorPrintList ?? collect())->contains($savedVendorPrint))
                                        <option value="{{ $savedVendorPrint }}" selected>{{ $savedVendorPrint }}</option>
                                    @endif
                                </select>
                            </div>
                        </div>



                            <div class="row g-2">
                                <div class="col-6 text-center">
                                    <label class="form-label form-label-sm mb-1 fw-bold text-secondary" style="font-size: 10px;">ESTIMASI HPP (M)</label>
                                    <input type="number" name="est_hpp_print" class="form-control form-control-sm text-center fw-bold font-monospace py-2" value="84" step="any">
                                </div>
                                <div class="col-6 text-center">
                                    <label class="form-label form-label-sm mb-1 fw-bold text-secondary" style="font-size: 10px;">KAIN TERPAKAI (M)</label>
                                    <input type="number" name="kain_terpakai_print" class="form-control form-control-sm text-center fw-bold font-monospace py-2" value="0.00" step="0.01">
                                </div>
                            </div>
                        </div>

                        <div class="small text-muted fst-italic px-1" style="font-size: 10.5px; color: #0284c7 !important;">
                            <i class="fas fa-info-circle me-1"></i> Jika Anda merubah SKU di sini, nama SKU kain di Tahap Potong akan ikut berubah secara otomatis.
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── CARD DYNAMIC SUB-CONTENT: TAHAP SAMPLING ── -->
            <div id="cardSamplingArea" style="display: none;">
                <div class="panel-sampling-banner">
                    <div class="d-flex align-items-center gap-3">
                        <div class="fs-2 text-white"><i class="fas fa-flask"></i></div>
                        <div>
                            <h5 class="fw-bold text-white mb-1">Panel Sampling</h5>
                            <div class="small text-white-50">Isi data sample &amp; status per-kain.</div>
                        </div>
                    </div>
                </div>

                <div class="app-card">
                    <div class="card-body-padding">
                        <div class="sub-card-title mb-3">
                            <i class="fas fa-camera text-primary"></i> DOKUMENTASI SAMPLE FISIK (GLOBAL)
                        </div>
                        <div class="sample-dropzone-box" onclick="document.getElementById('sampleCameraInput').click()">
                            <input type="file" id="sampleCameraInput" name="sample_photo" accept="image/*" capture="environment" style="display:none;" onchange="previewSamplePhoto(this)">
                            @if(!empty($spk->image_url))
                                <div id="samplePreviewPlaceholder" class="text-center" style="display:none;">
                                    <i class="fas fa-camera fs-1 text-primary mb-2"></i>
                                    <div class="fw-bold text-secondary">Jepret Baju Sample</div>
                                </div>
                                <img id="samplePreviewImg" src="{{ $spk->image_url }}" loading="lazy" style="display:block; max-height: 180px; width:100%; object-fit:contain; border-radius:10px;">
                            @else
                                <div id="samplePreviewPlaceholder" class="text-center">
                                    <i class="fas fa-camera fs-1 text-primary mb-2"></i>
                                    <div class="fw-bold text-secondary">Jepret Baju Sample</div>
                                </div>
                                <img id="samplePreviewImg" src="" style="display:none; max-height: 180px; width:100%; object-fit:contain; border-radius:10px;">
                            @endif
                        </div>
                    </div>
                </div>

                <div class="app-card">
                    <div class="card-body-padding" style="background: #f8fafc;">
                        <div class="d-flex align-items-center gap-3 mb-3 p-3 bg-white rounded-3 border">
                            <div class="user-avatar-badge">
                                <i class="fas fa-user-tag text-primary fs-5"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="small fw-bold text-primary font-monospace" style="font-size: 11px;">
                                    PEMBUAT SAMPLE ({{ strtoupper($fabricName) }})
                                </div>
                                <select name="pembuat_sample" class="form-select form-select-sm border-0 border-bottom bg-transparent font-monospace fw-bold text-dark p-0">
                                    <option value="">-- Pilih Pembuat Sample --</option>
                                    @foreach($pembuatSampleList ?? [] as $vName)
                                        <option value="{{ $vName }}" {{ ($savedPembuatSample ?? '') === $vName ? 'selected' : '' }}>{{ $vName }}</option>
                                    @endforeach
                                    @if(!empty($savedPembuatSample) && !($pembuatSampleList ?? collect())->contains($savedPembuatSample))
                                        <option value="{{ $savedPembuatSample }}" selected>{{ $savedPembuatSample }}</option>
                                    @endif
                                </select>
                            </div>
                        </div>



                        <div class="bg-white p-3 rounded-3 border mb-3">
                            <label class="form-label form-label-sm mb-1 fw-bold text-secondary" style="font-size: 11px;">KAIN TERPAKAI (CM)</label>
                            <input type="number" name="terpakai_kain" class="form-control form-control-sm text-center fw-bold font-monospace py-2" value="{{ $spk->items->first()?->kain_pakai ?: '0.00' }}" placeholder="0.00" step="any">
                        </div>

                        <div class="mb-3">
                            <div class="sub-card-title mb-2">STATUS ACC</div>
                            <div class="btn-group w-100 p-1 bg-white border rounded-3" role="group">
                                <input type="radio" class="btn-check" name="status_acc" id="acc_menunggu" value="MENUNGGU" {{ ($savedStatusAcc ?? 'MENUNGGU') === 'MENUNGGU' ? 'checked' : '' }}>
                                <label class="btn btn-sm btn-outline-warning fw-bold border-0 rounded-2 py-2" for="acc_menunggu">MENUNGGU</label>

                                <input type="radio" class="btn-check" name="status_acc" id="acc_revisi" value="REVISI" {{ ($savedStatusAcc ?? '') === 'REVISI' ? 'checked' : '' }}>
                                <label class="btn btn-sm btn-outline-secondary fw-bold border-0 rounded-2 py-2" for="acc_revisi">REVISI</label>

                                <input type="radio" class="btn-check" name="status_acc" id="acc_ok" value="ACC" {{ ($savedStatusAcc ?? '') === 'ACC' ? 'checked' : '' }}>
                                <label class="btn btn-sm btn-outline-success fw-bold border-0 rounded-2 py-2" for="acc_ok">ACC</label>
                            </div>
                        </div>

                        <div>
                            <textarea name="catatan_revisi_kain" class="form-control textarea-catatan-proses" placeholder="Catatan revisi bahan ini...">{{ $savedCatatanRevisi ?? '' }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── CARD DYNAMIC SUB-CONTENT: RINCIAN ANTRIAN & SAMPLING ── -->
            <div id="cardAntrianArea" class="app-card" style="display: none;">
                <div class="card-body-padding">
                    <div class="sub-card-title mb-2">
                        <i class="fas fa-hourglass-half text-warning"></i> RINCIAN STATUS ANTRIAN
                    </div>
                    <select name="rincian_antrian" class="form-select select-antrian-cream mb-3">
                        <option value="">-- Pilih Rincian Antrian --</option>
                        <option value="Kain Belum Datang" {{ ($savedRincianAntrian ?? '') === 'Kain Belum Datang' ? 'selected' : '' }}>Kain Belum Datang</option>
                        <option value="Menunggu Antrian Potong" {{ ($savedRincianAntrian ?? '') === 'Menunggu Antrian Potong' ? 'selected' : '' }}>Menunggu Antrian Potong</option>
                        <option value="Proses Approval Sample" {{ ($savedRincianAntrian ?? '') === 'Proses Approval Sample' ? 'selected' : '' }}>Proses Approval Sample</option>
                        <option value="Revisi Sample" {{ ($savedRincianAntrian ?? '') === 'Revisi Sample' ? 'selected' : '' }}>Revisi Sample</option>
                    </select>

                    <div class="sub-card-title mb-2">
                        <i class="fas fa-edit text-primary"></i> CATATAN PROSES ANTRIAN
                    </div>
                    <textarea name="catatan_antrian" class="form-control textarea-catatan-proses" placeholder="Tuliskan kendala bahan / detail sample di sini...">{{ $savedCatatanAntrian ?? '' }}</textarea>
                </div>
            </div>

            <!-- ── CARD DYNAMIC SUB-CONTENT: MATRIKS LOLOS QC / PRODUKSI ── -->
            <div id="cardMatriksArea" class="app-card">
                <div class="matriks-header-bar">
                    <i class="fas fa-check-circle me-1"></i> MATRIKS LOLOS QC
                </div>
                <div class="matriks-body-area">
                    @foreach($variantRows as $modelName => $row)
                        <div class="sku-header-title">{{ $modelName }}</div>
                        <div class="size-boxes-grid">
                            @foreach($row['sizes'] as $szItem)
                                @php
                                    $item = $szItem['item'];
                                    $pg = $item->progres->first();
                                    $valQty = $pg ? $pg->qty_done : $item->quantity;
                                @endphp
                                <div class="size-box-item">
                                    <div class="size-box-label">{{ $szItem['size'] }}</div>
                                    <input type="number" 
                                           name="{{ $pg ? 'progres['.$pg->id.']' : 'items['.$item->id.'][quantity_done]' }}" 
                                           class="size-box-input" 
                                           value="{{ $valQty }}" 
                                           min="0">
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- ── CARD DYNAMIC SUB-CONTENT: DATA REJECT / CACAT ── -->
            <div id="cardRejectArea" class="app-card">
                <div class="reject-header-bar">
                    <i class="fas fa-exclamation-triangle me-1"></i> DATA REJECT / CACAT
                </div>
                <div class="reject-body-area">
                    <div id="rejectListArea" class="mb-2">
                        @foreach($spk->items as $item)
                            @if(($item->qc_reject ?? 0) > 0)
                                <div class="d-flex justify-content-between align-items-center bg-white p-2 rounded-3 border mb-2">
                                    <span class="small fw-bold font-monospace text-dark">{{ $item->sku ?: $item->nama_produk }} ({{ $item->ukuran }})</span>
                                    <span class="badge bg-danger fs-6">{{ $item->qc_reject }} Pcs Cacat</span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                    <button type="button" class="btn btn-tambah-reject" onclick="showTambahRejectPrompt()">
                        + Tambah Reject
                    </button>
                </div>
            </div>

            <!-- ── STICKY FOOTER ACTION BAR (Match Screenshots) ── -->
            <div class="sticky-footer-bar">
                <div class="footer-update-text">
                    Last Update:<br>
                    <strong class="text-dark">{{ $spk->updated_at ? $spk->updated_at->format('d M, H:i') : date('d M, H:i') }}</strong>
                </div>
                <button type="submit" class="btn btn-simpan-cloud">
                    <i class="fas fa-cloud-upload-alt fs-5"></i> SIMPAN DATA
                </button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        let enterPin = '';
        const correctPin = '{{ $correctPin }}';
        const verifyPinUrl = '{{ route("spks.mobile_verify_pin", $spk->id) }}';

        function toggleTahapanSubCards() {
            const val = document.getElementById('tahapanSelect').value;
            const lkpkArea = document.getElementById('cardLkpkArea');
            const jahitArea = document.getElementById('cardJahitArea');
            const potongArea = document.getElementById('cardPotongArea');
            const printKainArea = document.getElementById('cardPrintKainArea');
            const samplingArea = document.getElementById('cardSamplingArea');
            const antrianArea = document.getElementById('cardAntrianArea');
            const matriksArea = document.getElementById('cardMatriksArea');
            const rejectArea = document.getElementById('cardRejectArea');

            // Hide all sub-cards first
            lkpkArea.style.display = 'none';
            jahitArea.style.display = 'none';
            potongArea.style.display = 'none';
            printKainArea.style.display = 'none';
            samplingArea.style.display = 'none';
            antrianArea.style.display = 'none';
            matriksArea.style.display = 'none';
            rejectArea.style.display = 'none';

            if (val === 'Tahap LKPK') {
                lkpkArea.style.display = 'block';
            } else if (val === 'Tahap Jahit') {
                jahitArea.style.display = 'block';
            } else if (val === 'Tahap Pemotongan') {
                potongArea.style.display = 'block';
            } else if (val === 'Tahap Print Kain') {
                printKainArea.style.display = 'block';
            } else if (val === 'Tahap Sampling') {
                samplingArea.style.display = 'block';
            } else if (val === 'Antrian & Sampling') {
                antrianArea.style.display = 'block';
            }
            // Perencanaan, Quality Control, Packing / Finishing, Selesai (Finished Good) -> Clean view!
        }

        function hitungBomLkpk(itemId, kancingPerPcs, lubangPerPcs) {
            const qtyInput = document.getElementById('qty_baju_' + itemId);
            const totKancing = document.getElementById('tot_kancing_' + itemId);
            const totLubang = document.getElementById('tot_lubang_' + itemId);
            const qty = parseInt(qtyInput.value) || 0;

            if (totKancing) totKancing.value = qty * kancingPerPcs;
            if (totLubang) totLubang.value = qty * lubangPerPcs;
        }

        function resizeAndCompressImage(file, maxDimension, quality, callback) {
            if (!file || !file.type.startsWith('image/')) {
                return callback(null, file);
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                const img = new Image();
                img.onload = function() {
                    let width = img.width;
                    let height = img.height;

                    if (width > maxDimension || height > maxDimension) {
                        if (width >= height) {
                            height = Math.round((height / width) * maxDimension);
                            width = maxDimension;
                        } else {
                            width = Math.round((width / height) * maxDimension);
                            height = maxDimension;
                        }
                    }

                    const canvas = document.createElement('canvas');
                    canvas.width = width;
                    canvas.height = height;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, width, height);

                    const mimeType = file.type === 'image/png' ? 'image/png' : 'image/jpeg';
                    const dataUrl = canvas.toDataURL(mimeType, quality);

                    canvas.toBlob(function(blob) {
                        if (blob) {
                            const resizedFile = new File([blob], file.name, {
                                type: mimeType,
                                lastModified: Date.now()
                            });
                            callback(dataUrl, resizedFile);
                        } else {
                            callback(e.target.result, file);
                        }
                    }, mimeType, quality);
                };
                img.onerror = () => callback(e.target.result, file);
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }

        function previewPotongPhoto(input) {
            if (input.files && input.files[0]) {
                resizeAndCompressImage(input.files[0], 1200, 0.8, function(dataUrl, resizedFile) {
                    if (resizedFile && typeof DataTransfer !== 'undefined') {
                        const dt = new DataTransfer();
                        dt.items.add(resizedFile);
                        input.files = dt.files;
                    }
                    document.getElementById('potongPreviewPlaceholder').style.display = 'none';
                    const img = document.getElementById('potongPreviewImg');
                    img.src = dataUrl;
                    img.style.display = 'block';
                });
            }
        }

        function previewPrintPhoto(input) {
            if (input.files && input.files[0]) {
                resizeAndCompressImage(input.files[0], 1200, 0.8, function(dataUrl, resizedFile) {
                    if (resizedFile && typeof DataTransfer !== 'undefined') {
                        const dt = new DataTransfer();
                        dt.items.add(resizedFile);
                        input.files = dt.files;
                    }
                    document.getElementById('printPreviewPlaceholder').style.display = 'none';
                    const img = document.getElementById('printPreviewImg');
                    img.src = dataUrl;
                    img.style.display = 'block';
                });
            }
        }

        function previewSamplePhoto(input) {
            if (input.files && input.files[0]) {
                resizeAndCompressImage(input.files[0], 1200, 0.8, function(dataUrl, resizedFile) {
                    if (resizedFile && typeof DataTransfer !== 'undefined') {
                        const dt = new DataTransfer();
                        dt.items.add(resizedFile);
                        input.files = dt.files;
                    }
                    document.getElementById('samplePreviewPlaceholder').style.display = 'none';
                    const img = document.getElementById('samplePreviewImg');
                    img.src = dataUrl;
                    img.style.display = 'block';
                });
            }
        }

        function tambahRowPenjahit() {
            Swal.fire({
                title: 'Tambah Penjahit Baru',
                html: `<input type="text" id="namaPenjahitBaru" class="form-control" placeholder="Nama Penjahit / Vendor Jahit">`,
                showCancelButton: true,
                confirmButtonText: 'Tambah',
                preConfirm: () => {
                    return document.getElementById('namaPenjahitBaru').value;
                }
            }).then((res) => {
                if (res.isConfirmed && res.value) {
                    Swal.fire('Ditambahkan', 'Penjahit ' + res.value + ' berhasil ditambahkan.', 'success');
                }
            });
        }

        function tambahRowLkpk() {
            Swal.fire({
                title: 'Tambah Vendor LKPK Baru',
                html: `<input type="text" id="namaLkpkBaru" class="form-control" placeholder="Nama Vendor LKPK">`,
                showCancelButton: true,
                confirmButtonText: 'Tambah',
                preConfirm: () => {
                    return document.getElementById('namaLkpkBaru').value;
                }
            }).then((res) => {
                if (res.isConfirmed && res.value) {
                    Swal.fire('Ditambahkan', 'Vendor LKPK ' + res.value + ' berhasil ditambahkan.', 'success');
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            toggleTahapanSubCards();
        });

        function pressDigit(val) {
            if (enterPin.length < 4) {
                enterPin += val;
                updateDots();
                if (enterPin.length === 4) {
                    verifyPinCode();
                }
            }
        }

        function backspacePinCode() {
            if (enterPin.length > 0) {
                enterPin = enterPin.slice(0, -1);
                updateDots();
            }
        }

        function clearPinCode() {
            enterPin = '';
            updateDots();
        }

        function updateDots() {
            const dots = document.querySelectorAll('.pin-dot-item');
            dots.forEach((dot, idx) => {
                if (idx < enterPin.length) {
                    dot.classList.add('active');
                } else {
                    dot.classList.remove('active');
                }
            });
        }

        function verifyPinCode() {
            fetch(verifyPinUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ pin: enterPin })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('pinLockScreen').style.display = 'none';
                    Swal.fire({
                        icon: 'success',
                        title: 'Akses Diterima',
                        text: 'Panel Produksi Siap Digunakan',
                        timer: 1200,
                        showConfirmButton: false
                    });
                } else {
                    document.getElementById('pinErrAlert').style.display = 'block';
                    clearPinCode();
                }
            })
            .catch(() => {
                if (enterPin === correctPin) {
                    document.getElementById('pinLockScreen').style.display = 'none';
                } else {
                    document.getElementById('pinErrAlert').style.display = 'block';
                    clearPinCode();
                }
            });
        }

        function lockPinScreen() {
            document.getElementById('pinLockScreen').style.display = 'flex';
            clearPinCode();
        }

        function showTambahRejectPrompt() {
            Swal.fire({
                title: 'Tambah Data Cacat / Reject',
                html: `
                    <div class="text-start">
                        <label class="form-label small fw-bold">Jumlah Barang Cacat (Pcs):</label>
                        <input type="number" id="rejectQtyInput" class="form-control mb-2" min="1" value="1">
                        <label class="form-label small fw-bold">Keterangan Cacat / Alasan:</label>
                        <input type="text" id="rejectReasonInput" class="form-control" placeholder="Contoh: Jahitan lepas / Kain robek">
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Simpan Reject',
                confirmButtonColor: '#dc2626',
                preConfirm: () => {
                    const qty = document.getElementById('rejectQtyInput').value;
                    const reason = document.getElementById('rejectReasonInput').value;
                    if (!qty || qty <= 0) {
                        Swal.showValidationMessage('Jumlah cacat harus lebih besar dari 0');
                    }
                    return { qty, reason };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const area = document.getElementById('rejectListArea');
                    const div = document.createElement('div');
                    div.className = 'd-flex justify-content-between align-items-center bg-white p-2 rounded-3 border mb-2';
                    div.innerHTML = `
                        <span class="small fw-bold text-dark">${result.value.reason || 'Barang Cacat'}</span>
                        <span class="badge bg-danger fs-6">${result.value.qty} Pcs Cacat</span>
                    `;
                    area.appendChild(div);
                    Swal.fire('Tersimpan', 'Data reject berhasil ditambahkan.', 'success');
                }
            });
        }

        // Form Submit Handler
        document.getElementById('simpleTrackingForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            Swal.fire({
                title: 'Menyimpan Data...',
                text: 'Memperbarui Panel Produksi',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Data Panel Produksi Berhasil Disimpan',
                        timer: 1800,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire('Error', 'Gagal menyimpan data.', 'error');
                }
            })
            .catch(() => {
                this.submit();
            });
        });
    </script>
</body>
</html>
