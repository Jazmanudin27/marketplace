@extends('v2.layouts.app')

@section('title', 'Tambah Toko Marketplace V2')

@section('content')
<!-- Page Header Compact -->
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2">
            <i class="bi bi-shop text-primary fs-5"></i> Tambah Toko Marketplace
        </h1>
        <p class="v2-page-subtitle mb-0">Hubungkan toko baru Anda ke ERP via OAuth resmi marketplace atau daftarkan toko instan secara manual.</p>
    </div>
    <div>
        <a href="{{ url('/v2/toko') }}" class="btn btn-sm btn-v2-secondary py-1.5 px-3 shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Toko
        </a>
    </div>
</div>

<!-- Main Container -->
<div class="row justify-content-start">
    <div class="col-12 col-lg-9">
        
        <div class="v2-card shadow-sm border mb-3">
            <div class="v2-card-header bg-light py-2.5">
                <h6 class="v2-card-title d-flex align-items-center gap-2 m-0">
                    <i class="bi bi-plug-fill text-primary"></i> 1. Otorisasi Resmi Platform Marketplace (OAuth 2.0)
                </h6>
            </div>
            <div class="v2-card-body p-3">
                <p class="text-muted mb-3" style="font-size: 0.78rem;">Pilih platform marketplace yang ingin Anda hubungkan. Anda akan diarahkan ke portal otorisasi resmi platform secara aman.</p>

                <!-- Platform List Cards -->
                <div class="d-flex flex-column gap-3">
                    
                    {{-- SHOPEE --}}
                    <div class="p-3 border rounded-3 bg-white hover-shadow transition-all">
                        <div class="row align-items-center g-3">
                            <div class="col-12 col-md-8 d-flex align-items-start gap-3">
                                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 fs-4 p-2 bg-danger text-white shadow-sm" style="width: 48px; height: 48px;">
                                    <i class="bi bi-bag-fill"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-1" style="font-size: 0.9rem;">Shopee Marketplace</h6>
                                    <p class="text-muted mb-2" style="font-size: 0.75rem;">Hubungkan toko Shopee Anda secara otomatis via OAuth resmi Shopee Open Platform.</p>
                                    <div class="d-flex gap-1.5 flex-wrap">
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5" style="font-size: 0.65rem;">
                                            <i class="bi bi-lightning-fill me-1"></i>Otomatis
                                        </span>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-0.5" style="font-size: 0.65rem;">
                                            <i class="bi bi-shield-check me-1"></i>OAuth 2.0
                                        </span>
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-0.5" style="font-size: 0.65rem;">
                                            <i class="bi bi-box-seam me-1"></i>Sync Stok Auto
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-4 text-md-end">
                                <a href="{{ route('shopee.authorize') }}" class="btn btn-sm btn-danger w-100 w-md-auto py-1.5 px-3 fw-bold shadow-sm">
                                    <i class="bi bi-plug-fill me-1"></i> Hubungkan Shopee
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- TOKOPEDIA --}}
                    <div class="p-3 border rounded-3 bg-white hover-shadow transition-all">
                        <div class="row align-items-center g-3">
                            <div class="col-12 col-md-8 d-flex align-items-start gap-3">
                                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 fs-4 p-2 bg-success text-white shadow-sm" style="width: 48px; height: 48px;">
                                    <i class="bi bi-shop"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-1" style="font-size: 0.9rem;">Tokopedia Marketplace</h6>
                                    <p class="text-muted mb-2" style="font-size: 0.75rem;">Hubungkan toko Tokopedia Anda. Terintegrasi terpusat via TikTok Shop OAuth Portal.</p>
                                    <div class="d-flex gap-1.5 flex-wrap">
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5" style="font-size: 0.65rem;">
                                            <i class="bi bi-lightning-fill me-1"></i>Otomatis
                                        </span>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-0.5" style="font-size: 0.65rem;">
                                            <i class="bi bi-shield-check me-1"></i>OAuth 2.0
                                        </span>
                                        <span class="badge bg-secondary-subtle text-secondary border px-2 py-0.5" style="font-size: 0.65rem;">
                                            <i class="bi bi-tiktok me-1"></i>Via TikTok Portal
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-4 text-md-end">
                                <a href="{{ route('tiktok.auth', ['channel' => 'tokopedia']) }}" class="btn btn-sm btn-success w-100 w-md-auto py-1.5 px-3 fw-bold shadow-sm">
                                    <i class="bi bi-plug-fill me-1"></i> Hubungkan Tokopedia
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- TIKTOK SHOP --}}
                    <div class="p-3 border rounded-3 bg-white hover-shadow transition-all">
                        <div class="row align-items-center g-3">
                            <div class="col-12 col-md-8 d-flex align-items-start gap-3">
                                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 fs-4 p-2 bg-dark text-white shadow-sm" style="width: 48px; height: 48px;">
                                    <i class="bi bi-tiktok"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-1" style="font-size: 0.9rem;">TikTok Shop</h6>
                                    <p class="text-muted mb-2" style="font-size: 0.75rem;">Hubungkan toko TikTok Shop Anda secara otomatis via OAuth resmi TikTok Open API.</p>
                                    <div class="d-flex gap-1.5 flex-wrap">
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5" style="font-size: 0.65rem;">
                                            <i class="bi bi-lightning-fill me-1"></i>Otomatis
                                        </span>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-0.5" style="font-size: 0.65rem;">
                                            <i class="bi bi-shield-check me-1"></i>OAuth 2.0
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-4 text-md-end">
                                <a href="{{ route('tiktok.auth') }}" class="btn btn-sm btn-dark w-100 w-md-auto py-1.5 px-3 fw-bold shadow-sm">
                                    <i class="bi bi-plug-fill me-1"></i> Hubungkan TikTok
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- LAZADA --}}
                    <div class="p-3 border rounded-3 bg-white hover-shadow transition-all">
                        <div class="row align-items-center g-3">
                            <div class="col-12 col-md-8 d-flex align-items-start gap-3">
                                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 fs-4 p-2 text-white shadow-sm" style="width: 48px; height: 48px; background-color: #0f146d;">
                                    <i class="bi bi-bag"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-1" style="font-size: 0.9rem;">Lazada Marketplace</h6>
                                    <p class="text-muted mb-2" style="font-size: 0.75rem;">Hubungkan toko Lazada Anda secara otomatis via OAuth resmi Lazada Open Platform.</p>
                                    <div class="d-flex gap-1.5 flex-wrap">
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5" style="font-size: 0.65rem;">
                                            <i class="bi bi-lightning-fill me-1"></i>Otomatis
                                        </span>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-0.5" style="font-size: 0.65rem;">
                                            <i class="bi bi-shield-check me-1"></i>OAuth 2.0
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-4 text-md-end">
                                <a href="{{ route('lazada.authorize') }}" class="btn btn-sm btn-primary w-100 w-md-auto py-1.5 px-3 fw-bold shadow-sm" style="background-color: #0f146d !important; border-color: #0f146d !important;">
                                    <i class="bi bi-plug-fill me-1"></i> Hubungkan Lazada
                                </a>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Section 2: Form Tambah Toko Manual / Instan -->
        <div class="v2-card shadow-sm border mb-3">
            <div class="v2-card-header bg-light py-2.5 d-flex align-items-center justify-content-between">
                <h6 class="v2-card-title d-flex align-items-center gap-2 m-0">
                    <i class="bi bi-pencil-square text-primary"></i> 2. Form Tambah Toko Manual / Instan
                </h6>
                <button type="button" class="btn btn-sm btn-outline-primary py-0.5 px-2" data-bs-toggle="collapse" data-bs-target="#manualStoreForm" style="font-size: 0.7rem;">
                    <i class="bi bi-chevron-down me-1"></i> Toggle Form
                </button>
            </div>
            <div class="v2-card-body p-3">
                <p class="text-muted mb-3" style="font-size: 0.78rem;">Masukkan Nama Toko & Shop ID / Username Toko secara langsung untuk mendaftarkan toko ke database ERP.</p>

                <div class="collapse show" id="manualStoreForm">
                    <form action="{{ url('/v2/toko') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="channel_id" class="form-label form-label-sm fw-bold text-dark">Channel / Marketplace <span class="text-danger">*</span></label>
                                <select name="channel_id" id="channel_id" class="form-select form-select-sm" required>
                                    <option value="">-- Pilih Channel Marketplace --</option>
                                    @foreach($channels as $c)
                                        <option value="{{ $c->id }}" {{ strtolower($c->code) === 'shopee' ? 'selected' : '' }}>{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="store_name" class="form-label form-label-sm fw-bold text-dark">Nama Toko <span class="text-danger">*</span></label>
                                <input type="text" name="store_name" id="store_name" class="form-control form-control-sm" placeholder="Contoh: Nusantara Seragam Sekolah Official" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="marketplace_store_id" class="form-label form-label-sm fw-bold text-dark">Shop ID / Username Toko <span class="text-danger">*</span></label>
                                <input type="text" name="marketplace_store_id" id="marketplace_store_id" class="form-control form-control-sm font-monospace" placeholder="Contoh: 2036279 atau shop_shopee_01" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="logo" class="form-label form-label-sm fw-bold text-dark">Logo Toko (Opsional)</label>
                                <input type="file" name="logo" id="logo" class="form-control form-control-sm" accept="image/*">
                            </div>
                        </div>
                        <div class="mt-3 text-end">
                            <button type="submit" class="btn btn-sm btn-v2-primary py-1.5 px-4 shadow-sm">
                                <i class="bi bi-save me-1"></i> Simpan Toko Ke ERP
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Info Box Guide -->
        <div class="p-3 bg-light rounded-3 border d-flex gap-3 align-items-start mb-3">
            <i class="bi bi-info-circle-fill fs-5 text-primary mt-0.5 flex-shrink-0"></i>
            <div style="font-size: 0.78rem;">
                <h6 class="fw-bold text-dark mb-1" style="font-size: 0.82rem;">Bagaimana cara kerja koneksi toko ke ERP?</h6>
                <ol class="text-muted mb-0 ps-3" style="line-height: 1.5;">
                    <li>Pilih platform marketplace di atas lalu klik <strong>Hubungkan</strong> untuk otorisasi otomatis via OAuth, atau gunakan <strong>Form Manual</strong> jika ingin memasukkan ID toko secara langsung.</li>
                    <li>Setelah terhubung, Anda akan dialihkan kembali ke daftar toko dengan status terhubung (Connected). Produk dan pesanan toko dapat langsung disinkronkan secara terpusat.</li>
                </ol>
            </div>
        </div>

    </div>
</div>
@endsection
