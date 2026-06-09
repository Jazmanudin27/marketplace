@extends('layouts.app')
@section('title', 'Tambah Toko')
@section('page-title', 'Tambah Toko Marketplace')

@section('content')
<div class="form-page-wrapper">
    <a href="{{ route('stores.index') }}" class="btn-back">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>

    <div style="max-width: 680px; margin-top: 1rem;">

        {{-- JUDUL --}}
        <div style="margin-bottom: 1.5rem;">
            <h2 style="font-size: 1.1rem; font-weight: 800; color: var(--text-primary); margin-bottom: 0.3rem;">
                Pilih Platform Marketplace
            </h2>
            <p style="font-size: 0.85rem; color: var(--text-secondary);">
                Pilih platform yang ingin Anda hubungkan. Anda akan diarahkan ke halaman otorisasi platform tersebut.
            </p>
        </div>

        {{-- SHOPEE — OAuth Otomatis --}}
        <div class="connect-card connect-card-shopee">
            <div class="connect-card-left">
                <div class="connect-platform-icon connect-shopee">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <div class="connect-info">
                    <div class="connect-name">Shopee</div>
                    <div class="connect-desc">Hubungkan toko Shopee Anda secara otomatis via OAuth resmi Shopee.</div>
                    <div class="connect-badges">
                        <span class="connect-badge badge-auto"><i class="fas fa-bolt"></i> Otomatis</span>
                        <span class="connect-badge badge-secure"><i class="fas fa-shield-alt"></i> OAuth 2.0</span>
                        <span class="connect-badge badge-env"><i class="fas fa-flask"></i> Test Environment</span>
                    </div>
                </div>
            </div>
            <a href="{{ route('shopee.authorize') }}" class="btn-connect btn-connect-shopee" id="btn-shopee-connect">
                <i class="fas fa-plug"></i>
                Hubungkan Shopee
            </a>
        </div>

        {{-- TOKOPEDIA — Coming Soon --}}
        <div class="connect-card connect-card-disabled">
            <div class="connect-card-left">
                <div class="connect-platform-icon connect-tokopedia">
                    <i class="fas fa-store"></i>
                </div>
                <div class="connect-info">
                    <div class="connect-name">Tokopedia</div>
                    <div class="connect-desc">Integrasi Tokopedia API sedang dalam pengembangan.</div>
                    <div class="connect-badges">
                        <span class="connect-badge badge-coming"><i class="fas fa-clock"></i> Coming Soon</span>
                    </div>
                </div>
            </div>
            <button class="btn-connect btn-connect-disabled" disabled>
                <i class="fas fa-lock"></i> Segera Hadir
            </button>
        </div>

        {{-- TIKTOK SHOP — Coming Soon --}}
        <div class="connect-card connect-card-disabled">
            <div class="connect-card-left">
                <div class="connect-platform-icon connect-tiktok">
                    <i class="fab fa-tiktok"></i>
                </div>
                <div class="connect-info">
                    <div class="connect-name">TikTok Shop</div>
                    <div class="connect-desc">Integrasi TikTok Shop API sedang dalam pengembangan.</div>
                    <div class="connect-badges">
                        <span class="connect-badge badge-coming"><i class="fas fa-clock"></i> Coming Soon</span>
                    </div>
                </div>
            </div>
            <button class="btn-connect btn-connect-disabled" disabled>
                <i class="fas fa-lock"></i> Segera Hadir
            </button>
        </div>

        {{-- LAZADA — Coming Soon --}}
        <div class="connect-card connect-card-disabled">
            <div class="connect-card-left">
                <div class="connect-platform-icon connect-lazada">
                    <i class="fas fa-tag"></i>
                </div>
                <div class="connect-info">
                    <div class="connect-name">Lazada</div>
                    <div class="connect-desc">Integrasi Lazada Open Platform API segera tersedia.</div>
                    <div class="connect-badges">
                        <span class="connect-badge badge-coming"><i class="fas fa-clock"></i> Coming Soon</span>
                    </div>
                </div>
            </div>
            <button class="btn-connect btn-connect-disabled" disabled>
                <i class="fas fa-lock"></i> Segera Hadir
            </button>
        </div>

        {{-- INFO OAUTH FLOW --}}
        <div class="oauth-info-box">
            <i class="fas fa-info-circle" style="color: var(--primary); flex-shrink:0;"></i>
            <div>
                <strong style="color: var(--text-primary);">Bagaimana cara kerja koneksi otomatis?</strong>
                <ol style="margin-top: 0.5rem; padding-left: 1rem; color: var(--text-secondary); font-size: 0.82rem; line-height: 1.7;">
                    <li>Klik tombol "Hubungkan" di atas.</li>
                    <li>Anda akan diarahkan ke halaman Shopee Seller Center untuk meminta izin.</li>
                    <li>Setelah menyetujui, Shopee akan mengarahkan kembali ke ERP ini.</li>
                    <li>Token akses tersimpan otomatis — toko langsung aktif!</li>
                </ol>
            </div>
        </div>

    </div>
</div>
@endsection

@push('styles')
<style>
.connect-card {
    display: flex; align-items: center; justify-content: space-between;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 1.25rem 1.5rem;
    margin-bottom: 0.75rem;
    gap: 1rem;
    transition: border-color 0.2s, transform 0.2s;
}
.connect-card:hover:not(.connect-card-disabled) { border-color: var(--border-light); transform: translateY(-1px); }
.connect-card-shopee:hover { border-color: rgba(238,77,45,0.4) !important; }
.connect-card-disabled { opacity: 0.55; cursor: not-allowed; }

.connect-card-left { display: flex; align-items: flex-start; gap: 1rem; flex: 1; }

.connect-platform-icon {
    width: 48px; height: 48px; flex-shrink: 0;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.25rem;
}
.connect-shopee    { background: rgba(238,77,45,0.15); color: var(--shopee); }
.connect-tokopedia { background: rgba(3,172,14,0.12); color: var(--tokopedia); }
.connect-tiktok    { background: rgba(255,255,255,0.08); color: var(--text-primary); }
.connect-lazada    { background: rgba(92,107,255,0.12); color: #5C6BFF; }

.connect-info { flex: 1; }
.connect-name { font-size: 0.95rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.2rem; }
.connect-desc { font-size: 0.78rem; color: var(--text-muted); margin-bottom: 0.5rem; }

.connect-badges { display: flex; gap: 0.4rem; flex-wrap: wrap; }
.connect-badge {
    font-size: 0.68rem; font-weight: 700;
    padding: 0.18rem 0.5rem; border-radius: 999px;
    display: inline-flex; align-items: center; gap: 0.25rem;
}
.badge-auto   { background: rgba(16,185,129,0.12); color: var(--success); border: 1px solid rgba(16,185,129,0.25); }
.badge-secure { background: rgba(108,99,255,0.12); color: var(--primary); border: 1px solid rgba(108,99,255,0.25); }
.badge-env    { background: rgba(245,158,11,0.12); color: var(--warning); border: 1px solid rgba(245,158,11,0.25); }
.badge-coming { background: rgba(148,163,184,0.1); color: var(--text-muted); border: 1px solid rgba(148,163,184,0.15); }

.btn-connect {
    display: inline-flex; align-items: center; gap: 0.5rem;
    font-size: 0.85rem; font-weight: 700;
    padding: 0.65rem 1.25rem; border-radius: var(--radius-sm);
    white-space: nowrap; text-decoration: none; border: none;
    cursor: pointer; font-family: 'Inter', sans-serif;
    transition: all 0.2s;
    flex-shrink: 0;
}
.btn-connect-shopee {
    background: linear-gradient(135deg, #EE4D2D, #D63B1F);
    color: white;
    box-shadow: 0 4px 16px rgba(238,77,45,0.3);
}
.btn-connect-shopee:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(238,77,45,0.45); }
.btn-connect-shopee:active { transform: translateY(0); }
.btn-connect-disabled {
    background: rgba(255,255,255,0.05);
    color: var(--text-muted);
    border: 1px solid var(--border);
    cursor: not-allowed;
}

.oauth-info-box {
    display: flex; gap: 0.75rem;
    background: rgba(108,99,255,0.08);
    border: 1px solid rgba(108,99,255,0.2);
    border-radius: var(--radius);
    padding: 1rem 1.25rem;
    margin-top: 1rem;
}
</style>
@endpush
