@extends('layouts.app')
@section('title', 'Hubungkan Tokopedia')
@section('page-title', 'Hubungkan Tokopedia')

@section('content')
    <div class="form-page-wrapper">
        <a href="{{ route('stores.create') }}" class="btn-back">
            <i class="fas fa-arrow-left"></i> Kembali ke Pilihan Platform
        </a>

        <div style="max-width: 580px; margin-top: 1.5rem;">
            
            <div class="connect-header-card">
                <div class="tokopedia-logo-large">
                    <i class="fas fa-store"></i>
                </div>
                <div>
                    <h3 class="connect-title">Integrasi Tokopedia</h3>
                    <p class="connect-subtitle">Masukkan detail toko Anda di bawah untuk mengaktifkan koneksi marketplace.</p>
                </div>
            </div>

            @if(session('error'))
                <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center gap-3 p-3 mb-4" role="alert" style="background-color: rgba(239, 68, 68, 0.12); border-left: 4px solid #ef4444 !important; border-radius: var(--radius-sm);">
                    <i class="fas fa-exclamation-circle fs-5 text-danger"></i>
                    <div class="text-danger small fw-medium">{{ session('error') }}</div>
                </div>
            @endif

            <div class="card shadow-sm border-0" style="background: var(--bg-card); border: 1px solid var(--border) !important; border-radius: var(--radius);">
                <div class="card-body p-4">
                    <form action="{{ route('tokopedia.connect.post') }}" method="POST">
                        @csrf

                        <div class="mb-4">
                            <label for="store_name" class="form-label fw-bold small text-muted" style="text-transform: uppercase; letter-spacing: 0.5px;">Nama Toko ERP</label>
                            <input type="text" class="form-control @error('store_name') is-invalid @enderror" id="store_name" name="store_name" value="{{ old('store_name') }}" placeholder="Contoh: Tokopedia Toko B-1" required style="background: rgba(255,255,255,0.03); border: 1px solid var(--border); color: var(--text-primary); padding: 0.75rem; font-size: 0.9rem; border-radius: var(--radius-sm);">
                            @error('store_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text small text-muted mt-1">Nama representatif toko ini dalam sistem ERP Anda.</div>
                        </div>

                        <div class="mb-4">
                            <label for="marketplace_store_id" class="form-label fw-bold small text-muted" style="text-transform: uppercase; letter-spacing: 0.5px;">Tokopedia Shop ID</label>
                            <input type="text" class="form-control @error('marketplace_store_id') is-invalid @enderror" id="marketplace_store_id" name="marketplace_store_id" value="{{ old('marketplace_store_id') }}" placeholder="Contoh: 123456789 atau TOKPED_B_1_DEMO" required style="background: rgba(255,255,255,0.03); border: 1px solid var(--border); color: var(--text-primary); padding: 0.75rem; font-size: 0.9rem; border-radius: var(--radius-sm);">
                            @error('marketplace_store_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text small text-muted mt-1">ID Toko Tokopedia Anda. Gunakan akhiran <code>_DEMO</code> jika ingin mencoba dengan mode simulasi.</div>
                        </div>

                        <div class="d-flex align-items-center justify-content-end gap-3 mt-5">
                            <a href="{{ route('stores.create') }}" class="btn btn-secondary btn-sm px-4 py-2 border" style="background: transparent; color: var(--text-secondary); border-color: var(--border) !important; font-size: 0.85rem; font-weight: 700; border-radius: var(--radius-sm);">
                                Batal
                            </a>
                            <button type="submit" class="btn btn-tokopedia px-4 py-2 text-white fw-bold" style="background: linear-gradient(135deg, #03AC0E, #028A0B); border: none; font-size: 0.85rem; border-radius: var(--radius-sm); box-shadow: 0 4px 16px rgba(3, 172, 14, 0.3);">
                                <i class="fas fa-plug me-2"></i> Hubungkan Toko
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="oauth-info-box mt-4" style="display: flex; gap: 0.75rem; background: rgba(3, 172, 14, 0.08); border: 1px solid rgba(3, 172, 14, 0.2); border-radius: var(--radius); padding: 1rem 1.25rem;">
                <i class="fas fa-info-circle" style="color: #03AC0E; font-size: 1.1rem; margin-top: 2px; flex-shrink: 0;"></i>
                <div>
                    <strong style="color: var(--text-primary); font-size: 0.9rem;">Bagaimana cara menghubungkan Tokopedia?</strong>
                    <ol style="margin-top: 0.5rem; padding-left: 1.2rem; color: var(--text-secondary); font-size: 0.82rem; line-height: 1.7;">
                        <li>Pastikan toko Anda memiliki status level minimum <strong>Power Merchant</strong> atau <strong>Official Store</strong> di Tokopedia.</li>
                        <li>Aktifkan permohonan aplikasi pihak ketiga di portal **[Tokopedia & Shop Partner Center](https://partner.tiktokshop.com/)** Anda.</li>
                        <li>Masukkan **Shop ID** Tokopedia Anda di formulir atas dan klik **Hubungkan Toko**.</li>
                        <li>Untuk tujuan pengujian lokal/sandbox, Anda dapat mengisi Shop ID dengan akhiran <code>_DEMO</code> (misalnya: <code>TOKPED_B_1_DEMO</code>) untuk bypass verifikasi resmi.</li>
                    </ol>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('styles')
    <style>
        .connect-header-card {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            background: rgba(3, 172, 14, 0.05);
            border: 1px dashed rgba(3, 172, 14, 0.3);
            border-radius: var(--radius);
            padding: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .tokopedia-logo-large {
            width: 54px;
            height: 54px;
            border-radius: 14px;
            background: rgba(3, 172, 14, 0.15);
            color: #03AC0E;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .connect-title {
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--text-primary);
            margin: 0 0 0.2rem 0;
        }

        .connect-subtitle {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin: 0;
        }

        .btn-tokopedia:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(3, 172, 14, 0.45) !important;
        }
        
        .btn-tokopedia:active {
            transform: translateY(0);
        }

        .form-control:focus {
            border-color: #03AC0E !important;
            box-shadow: 0 0 0 0.2rem rgba(3, 172, 14, 0.15) !important;
        }
    </style>
@endpush
