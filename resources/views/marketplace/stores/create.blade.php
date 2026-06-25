@extends('layouts.app')
@section('title', 'Tambah Toko')
@section('page-title', 'Tambah Toko Marketplace')

@section('content')
    <div class="row justify-content-start">
        <div class="col-12 col-lg-7">
            <div class="dashboard-card mb-4">
                <div class="card-header-line d-flex justify-content-between align-items-center mb-4">
                    <h3 class="mb-0"><i class="fas fa-plug text-primary me-2"></i> Pilih Platform Marketplace</h3>
                    <a href="{{ route('stores.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                </div>
                <p class="text-muted small mb-4">Pilih platform marketplace yang ingin Anda hubungkan. Anda akan diarahkan ke
                    portal otorisasi resmi platform tersebut secara aman.</p>

                {{-- SHOPEE — OAuth Otomatis --}}
                <div class="dashboard-card mb-3 p-3 position-relative overflow-hidden connect-card-shopee"
                    style="background: linear-gradient(135deg, rgba(255,255,255,0.015), rgba(255,255,255,0.005)); border: 1px solid rgba(255, 255, 255, 0.08); transition: border-color 0.2s, transform 0.2s;">
                    <div class="row align-items-center g-3">
                        <div class="col-12 col-md-8 d-flex align-items-start gap-3">
                            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 fs-5 p-2"
                                style="width: 48px; height: 48px; background-color: rgba(238, 77, 45, 0.15) !important; color: #ee4d2d !important;">
                                <i class="fas fa-shopping-bag"></i>
                            </div>
                            <div class="min-width-0">
                                <h5 class="fw-bold text-white mb-1" style="font-size: 0.95rem;">Shopee</h5>
                                <p class="text-muted small mb-2" style="font-size: 0.78rem;">Hubungkan toko Shopee Anda
                                    secara otomatis via OAuth resmi Shopee.</p>
                                <div class="d-flex gap-1.5 flex-wrap">
                                    <span class="badge badge-success px-2 py-1" style="font-size: 0.65rem;"><i
                                            class="fas fa-bolt"></i> Otomatis</span>
                                    <span class="badge badge-primary px-2 py-1" style="font-size: 0.65rem;"><i
                                            class="fas fa-shield-alt"></i> OAuth 2.0</span>
                                    <span class="badge badge-warning px-2 py-1 text-dark" style="font-size: 0.65rem;"><i
                                            class="fas fa-flask"></i> Test Environment</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4 text-md-end">
                            <a href="{{ route('shopee.authorize') }}"
                                class="btn btn-sm text-white w-100 w-md-auto py-2 px-3 fw-bold btn-connect-shopee"
                                style="background: linear-gradient(135deg, #EE4D2D, #D63B1F); border: none; box-shadow: 0 4px 16px rgba(238, 77, 45, 0.25);">
                                <i class="fas fa-plug me-1"></i> Hubungkan Shopee
                            </a>
                        </div>
                    </div>
                </div>

                {{-- TOKOPEDIA --}}
                <div class="dashboard-card mb-3 p-3 position-relative overflow-hidden connect-card-tokopedia"
                    style="background: linear-gradient(135deg, rgba(255,255,255,0.015), rgba(255,255,255,0.005)); border: 1px solid rgba(255, 255, 255, 0.08); transition: border-color 0.2s, transform 0.2s;">
                    <div class="row align-items-center g-3">
                        <div class="col-12 col-md-8 d-flex align-items-start gap-3">
                            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 fs-5 p-2"
                                style="width: 48px; height: 48px; background-color: rgba(3, 172, 14, 0.12) !important; color: #03ac0e !important;">
                                <i class="fas fa-store"></i>
                            </div>
                            <div class="min-width-0">
                                <h5 class="fw-bold text-white mb-1" style="font-size: 0.95rem;">Tokopedia</h5>
                                <p class="text-muted small mb-2" style="font-size: 0.78rem;">Hubungkan toko Tokopedia Anda.
                                    Kini terintegrasi via portal TikTok Shop OAuth.</p>
                                <div class="d-flex gap-1.5 flex-wrap">
                                    <span class="badge badge-success px-2 py-1" style="font-size: 0.65rem;"><i
                                            class="fas fa-bolt"></i> Otomatis</span>
                                    <span class="badge badge-primary px-2 py-1" style="font-size: 0.65rem;"><i
                                            class="fas fa-shield-alt"></i> OAuth 2.0</span>
                                    <span class="badge badge-secondary px-2 py-1" style="font-size: 0.65rem;"><i
                                            class="fab fa-tiktok"></i> Via TikTok OAuth</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4 text-md-end">
                            <a href="{{ route('tiktok.auth', ['channel' => 'tokopedia']) }}"
                                class="btn btn-sm text-white w-100 w-md-auto py-2 px-3 fw-bold btn-connect-tokopedia"
                                style="background: linear-gradient(135deg, #03AC0E, #028A0B); border: none; box-shadow: 0 4px 16px rgba(3, 172, 14, 0.25);">
                                <i class="fas fa-plug me-1"></i> Hubungkan Tokopedia
                            </a>
                        </div>
                    </div>
                </div>

                {{-- TIKTOK SHOP --}}
                <div class="dashboard-card mb-3 p-3 position-relative overflow-hidden connect-card-tiktok"
                    style="background: linear-gradient(135deg, rgba(255,255,255,0.015), rgba(255,255,255,0.005)); border: 1px solid rgba(255, 255, 255, 0.08); transition: border-color 0.2s, transform 0.2s;">
                    <div class="row align-items-center g-3">
                        <div class="col-12 col-md-8 d-flex align-items-start gap-3">
                            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 fs-5 p-2"
                                style="width: 48px; height: 48px; background-color: rgba(255, 255, 255, 0.08) !important; color: #ffffff !important;">
                                <i class="fab fa-tiktok"></i>
                            </div>
                            <div class="min-width-0">
                                <h5 class="fw-bold text-white mb-1" style="font-size: 0.95rem;">TikTok Shop</h5>
                                <p class="text-muted small mb-2" style="font-size: 0.78rem;">Hubungkan toko TikTok Shop Anda
                                    secara otomatis via OAuth resmi.</p>
                                <div class="d-flex gap-1.5 flex-wrap">
                                    <span class="badge badge-success px-2 py-1" style="font-size: 0.65rem;"><i
                                            class="fas fa-bolt"></i> Otomatis</span>
                                    <span class="badge badge-primary px-2 py-1" style="font-size: 0.65rem;"><i
                                            class="fas fa-shield-alt"></i> OAuth 2.0</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4 text-md-end">
                            <a href="{{ route('tiktok.auth') }}"
                                class="btn btn-sm text-white w-100 w-md-auto py-2 px-3 fw-bold btn-connect-tiktok"
                                style="background: linear-gradient(135deg, #000000, #333333); border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);">
                                <i class="fas fa-plug me-1"></i> Hubungkan TikTok
                            </a>
                        </div>
                    </div>
                </div>

                {{-- LAZADA — Coming Soon --}}
                <div class="dashboard-card mb-3 p-3 position-relative overflow-hidden connect-card-disabled"
                    style="background: linear-gradient(135deg, rgba(255,255,255,0.015), rgba(255,255,255,0.005)); border: 1px solid rgba(255, 255, 255, 0.08); opacity: 0.55; cursor: not-allowed;">
                    <div class="row align-items-center g-3">
                        <div class="col-12 col-md-8 d-flex align-items-start gap-3">
                            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 fs-5 p-2"
                                style="width: 48px; height: 48px; background-color: rgba(92, 107, 255, 0.12) !important; color: #5c6bff !important;">
                                <i class="fas fa-tag"></i>
                            </div>
                            <div class="min-width-0">
                                <h5 class="fw-bold text-white mb-1" style="font-size: 0.95rem;">Lazada</h5>
                                <p class="text-muted small mb-2" style="font-size: 0.78rem;">Integrasi Lazada Open Platform
                                    API segera tersedia.</p>
                                <div class="d-flex gap-1.5 flex-wrap">
                                    <span class="badge badge-secondary px-2 py-1" style="font-size: 0.65rem;"><i
                                            class="fas fa-clock"></i> Coming Soon</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4 text-md-end">
                            <button class="btn btn-sm btn-outline-secondary w-100 w-md-auto py-2 px-3 fw-bold" disabled>
                                <i class="fas fa-lock me-1"></i> Segera Hadir
                            </button>
                        </div>
                    </div>
                </div>

                {{-- INFO OAUTH FLOW --}}
                <div class="mt-4 p-3 rounded-3 d-flex gap-3"
                    style="background: rgba(108, 99, 255, 0.08); border: 1px solid rgba(108, 99, 255, 0.2);">
                    <i class="fas fa-info-circle fs-5 mt-0.5 text-primary flex-shrink-0"></i>
                    <div>
                        <h6 class="fw-bold text-white mb-1" style="font-size: 0.88rem;">Bagaimana cara kerja koneksi
                            otomatis?</h6>
                        <ol class="text-muted mb-0 ps-3" style="font-size: 0.8rem; line-height: 1.6;">
                            <li>Pilih salah satu platform marketplace di atas lalu klik **Hubungkan**.</li>
                            <li>Sistem akan mengarahkan Anda ke portal otorisasi resmi penjual marketplace.</li>
                            <li>Masuk ke akun toko Anda dan setujui izin sinkronisasi data.</li>
                            <li>Setelah disetujui, Anda akan otomatis kembali ke ERP ini dengan koneksi aktif.</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .connect-card-shopee:hover {
            border-color: rgba(238, 77, 45, 0.4) !important;
            transform: translateY(-1px);
        }

        .connect-card-tokopedia:hover {
            border-color: rgba(3, 172, 14, 0.4) !important;
            transform: translateY(-1px);
        }

        .connect-card-tiktok:hover {
            border-color: rgba(255, 255, 255, 0.2) !important;
            transform: translateY(-1px);
        }

        .btn-connect-shopee:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(238, 77, 45, 0.45) !important;
        }

        .btn-connect-tokopedia:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(3, 172, 14, 0.45) !important;
        }

        .btn-connect-tiktok:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.45) !important;
        }
    </style>
@endpush
