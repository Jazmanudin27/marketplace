@extends('layouts.app')
@section('title', 'Tambah Toko')
@section('page-title', 'Tambah Toko Marketplace')

@section('content')
    <div class="row justify-content-start">
        <div class="col-12 col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <a href="{{ route('stores.index') }}" class="btn btn-sm btn-outline-secondary rounded-3">
                    <i class="fas fa-arrow-left me-1"></i> Kembali
                </a>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="fas fa-plug fs-5"></i>
                        </div>
                        <h5 class="fw-bold mb-0 text-dark">Pilih Platform Marketplace</h5>
                    </div>
                    <p class="text-muted small mb-4">Pilih platform marketplace yang ingin Anda hubungkan. Anda akan diarahkan ke portal otorisasi resmi platform tersebut secara aman.</p>

                    <!-- Platform List -->
                    <div class="d-flex flex-column gap-3">
                        
                        {{-- SHOPEE --}}
                        <div class="card border rounded-3 p-3">
                            <div class="row align-items-center g-3">
                                <div class="col-12 col-md-8 d-flex align-items-start gap-3">
                                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 fs-5 p-2 bg-danger bg-opacity-10 text-danger"
                                        style="width: 48px; height: 48px;">
                                        <i class="fas fa-shopping-bag"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold text-dark mb-1">Shopee</h6>
                                        <p class="text-muted small mb-2">Hubungkan toko Shopee Anda secara otomatis via OAuth resmi Shopee.</p>
                                        <div class="d-flex gap-2 flex-wrap">
                                            <span class="badge bg-success-subtle text-success border border-success border-opacity-10 rounded-pill px-2" style="font-size: 0.65rem;">
                                                <i class="fas fa-bolt me-1"></i>Otomatis
                                            </span>
                                            <span class="badge bg-primary-subtle text-primary border border-primary border-opacity-10 rounded-pill px-2" style="font-size: 0.65rem;">
                                                <i class="fas fa-shield-alt me-1"></i>OAuth 2.0
                                            </span>
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning border-opacity-10 rounded-pill px-2" style="font-size: 0.65rem;">
                                                <i class="fas fa-flask me-1"></i>Test Env
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-4 text-md-end">
                                    <a href="{{ route('shopee.authorize') }}" class="btn btn-danger btn-sm w-100 w-md-auto py-2 px-3 fw-semibold rounded-3 shadow-sm">
                                        <i class="fas fa-plug me-1"></i> Hubungkan Shopee
                                    </a>
                                </div>
                            </div>
                        </div>

                        {{-- TOKOPEDIA --}}
                        <div class="card border rounded-3 p-3">
                            <div class="row align-items-center g-3">
                                <div class="col-12 col-md-8 d-flex align-items-start gap-3">
                                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 fs-5 p-2 bg-success bg-opacity-10 text-success"
                                        style="width: 48px; height: 48px;">
                                        <i class="fas fa-store"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold text-dark mb-1">Tokopedia</h6>
                                        <p class="text-muted small mb-2">Hubungkan toko Tokopedia Anda. Terintegrasi via portal TikTok Shop OAuth.</p>
                                        <div class="d-flex gap-2 flex-wrap">
                                            <span class="badge bg-success-subtle text-success border border-success border-opacity-10 rounded-pill px-2" style="font-size: 0.65rem;">
                                                <i class="fas fa-bolt me-1"></i>Otomatis
                                            </span>
                                            <span class="badge bg-primary-subtle text-primary border border-primary border-opacity-10 rounded-pill px-2" style="font-size: 0.65rem;">
                                                <i class="fas fa-shield-alt me-1"></i>OAuth 2.0
                                            </span>
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary border-opacity-10 rounded-pill px-2" style="font-size: 0.65rem;">
                                                <i class="fab fa-tiktok me-1"></i>Via TikTok OAuth
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-4 text-md-end">
                                    <a href="{{ route('tiktok.auth', ['channel' => 'tokopedia']) }}" class="btn btn-success btn-sm w-100 w-md-auto py-2 px-3 fw-semibold rounded-3 shadow-sm">
                                        <i class="fas fa-plug me-1"></i> Hubungkan Tokopedia
                                    </a>
                                </div>
                            </div>
                        </div>

                        {{-- TIKTOK SHOP --}}
                        <div class="card border rounded-3 p-3">
                            <div class="row align-items-center g-3">
                                <div class="col-12 col-md-8 d-flex align-items-start gap-3">
                                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 fs-5 p-2 bg-dark bg-opacity-10 text-dark"
                                        style="width: 48px; height: 48px;">
                                        <i class="fab fa-tiktok"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold text-dark mb-1">TikTok Shop</h6>
                                        <p class="text-muted small mb-2">Hubungkan toko TikTok Shop Anda secara otomatis via OAuth resmi.</p>
                                        <div class="d-flex gap-2 flex-wrap">
                                            <span class="badge bg-success-subtle text-success border border-success border-opacity-10 rounded-pill px-2" style="font-size: 0.65rem;">
                                                <i class="fas fa-bolt me-1"></i>Otomatis
                                            </span>
                                            <span class="badge bg-primary-subtle text-primary border border-primary border-opacity-10 rounded-pill px-2" style="font-size: 0.65rem;">
                                                <i class="fas fa-shield-alt me-1"></i>OAuth 2.0
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-4 text-md-end">
                                    <a href="{{ route('tiktok.auth') }}" class="btn btn-dark btn-sm w-100 w-md-auto py-2 px-3 fw-semibold rounded-3 shadow-sm">
                                        <i class="fas fa-plug me-1"></i> Hubungkan TikTok
                                    </a>
                                </div>
                            </div>
                        </div>

                        {{-- LAZADA --}}
                        <div class="card border rounded-3 p-3 bg-light bg-opacity-50" style="opacity: 0.7;">
                            <div class="row align-items-center g-3">
                                <div class="col-12 col-md-8 d-flex align-items-start gap-3">
                                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 fs-5 p-2 bg-secondary bg-opacity-10 text-secondary"
                                        style="width: 48px; height: 48px;">
                                        <i class="fas fa-tag"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold text-muted mb-1">Lazada</h6>
                                        <p class="text-muted small mb-2">Integrasi Lazada Open Platform API segera tersedia.</p>
                                        <div class="d-flex gap-2 flex-wrap">
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary border-opacity-10 rounded-pill px-2" style="font-size: 0.65rem;">
                                                <i class="fas fa-clock me-1"></i>Coming Soon
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-4 text-md-end">
                                    <button class="btn btn-sm btn-outline-secondary w-100 w-md-auto py-2 px-3 fw-semibold rounded-3" disabled>
                                        <i class="fas fa-lock me-1"></i> Segera Hadir
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Info Box -->
                    <div class="alert alert-info border-0 shadow-sm d-flex gap-3 p-3 mt-4 rounded-3" role="alert" style="background-color: rgba(13, 110, 253, 0.05);">
                        <i class="fas fa-info-circle fs-5 text-primary mt-0.5 flex-shrink-0"></i>
                        <div>
                            <h6 class="fw-bold text-dark mb-1" style="font-size: 0.88rem;">Bagaimana cara kerja koneksi otomatis?</h6>
                            <ol class="text-muted mb-0 ps-3 small" style="line-height: 1.6;">
                               <li>Pilih salah satu platform marketplace di atas lalu klik <strong>Hubungkan</strong>.</li>
                               <li>Sistem akan mengarahkan Anda ke portal otorisasi resmi penjual marketplace secara aman.</li>
                               <li>Masuk ke akun toko Anda dan setujui izin sinkronisasi data.</li>
                               <li>Setelah disetujui, Anda akan otomatis dialihkan kembali ke ERP ini dengan koneksi aktif.</li>
                            </ol>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
