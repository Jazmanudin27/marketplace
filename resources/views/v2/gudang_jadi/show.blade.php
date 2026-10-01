@extends('v2.layouts.app')

@section('title', 'Detail Mutasi Stok Gudang Jadi V2')

@section('content')

{{-- ── Page Header ── --}}
<div class="v2-page-header align-items-center mb-4">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2 mb-1">
            Detail Mutasi Stok Gudang Jadi #{{ $mutation->id }}
        </h1>
        <p class="text-muted small mb-0">Rincian pergerakan stok barang produk master gudang jadi</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        @if($mutation->type === 'in')
            <a href="{{ route('v2.gudang_jadi.masuk') }}" class="btn btn-sm btn-outline-secondary px-3 fw-semibold">
                Kembali ke Mutasi Masuk
            </a>
        @elseif($mutation->type === 'out')
            <a href="{{ route('v2.gudang_jadi.keluar') }}" class="btn btn-sm btn-outline-secondary px-3 fw-semibold">
                Kembali ke Mutasi Keluar
            </a>
        @else
            <a href="{{ route('v2.gudang_jadi.index') }}" class="btn btn-sm btn-outline-secondary px-3 fw-semibold">
                Kembali ke Daftar Mutasi
            </a>
        @endif
    </div>
</div>

<div class="row g-3">
    <!-- Main Content Column -->
    <div class="col-12 col-lg-8">
        <!-- Card 1: Informasi Transaksi -->
        <div class="v2-card p-4 shadow-sm mb-3">
            <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-3">
                <div>
                    <span class="text-muted small d-block mb-1 text-uppercase fw-semibold" style="letter-spacing:0.04em;">STATUS TRANSAKSI</span>
                    @if($mutation->type === 'in')
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1.5 fw-bold" style="font-size: 0.8rem;">
                            Disetujui / Barang Masuk (+)
                        </span>
                    @elseif($mutation->type === 'out')
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-1.5 fw-bold" style="font-size: 0.8rem;">
                            Dikeluarkan / Barang Keluar (-)
                        </span>
                    @else
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-3 py-1.5 fw-bold" style="font-size: 0.8rem;">
                            Penyesuaian Stok
                        </span>
                    @endif
                </div>
                <div class="text-end">
                    <span class="text-muted small d-block mb-1 text-uppercase fw-semibold" style="letter-spacing:0.04em;">WAKTU DIBATAT</span>
                    <span class="fw-bold text-dark fs-6">
                        {{ $mutation->created_at ? $mutation->created_at->format('d F Y, H:i') : '-' }} WIB
                    </span>
                </div>
            </div>

            <!-- Detail Produk Master -->
            <div class="bg-light border rounded-3 p-3.5 mb-4">
                <span class="text-muted small d-block mb-1 text-uppercase fw-semibold" style="letter-spacing:0.04em; font-size: 0.72rem;">PRODUK MASTER</span>
                <h5 class="fw-bold text-dark mb-1">{{ $mutation->masterProduct->name ?? 'Produk Tidak Ditemukan' }}</h5>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge bg-white text-dark border font-monospace px-2 py-1" style="font-size: 0.78rem;">
                        SKU: {{ $mutation->masterProduct->sku ?? '-' }}
                    </span>
                    <span class="text-muted small">
                        Stok Produk Saat Ini: <b>{{ number_format($mutation->masterProduct->stock ?? 0, 0, ',', '.') }}</b> {{ $mutation->masterProduct->unit ?? 'PCS' }}
                    </span>
                </div>
            </div>

            <!-- Card Metric Grid -->
            <div class="row g-3 mb-4">
                <div class="col-6">
                    <div class="p-3 border rounded-3 text-center {{ $mutation->type === 'in' ? 'bg-success-subtle border-success-subtle' : ($mutation->type === 'out' ? 'bg-danger-subtle border-danger-subtle' : 'bg-light') }}">
                        <span class="text-muted d-block small mb-1 fw-semibold text-uppercase" style="letter-spacing:0.03em;">JUMLAH MUTASI</span>
                        <span class="fw-bold fs-3 {{ $mutation->type === 'in' ? 'text-success' : ($mutation->type === 'out' ? 'text-danger' : 'text-dark') }}">
                            {{ $mutation->type === 'in' ? '+' : ($mutation->type === 'out' ? '-' : '') }}{{ number_format(abs($mutation->quantity), 0, ',', '.') }} {{ $mutation->masterProduct->unit ?? 'PCS' }}
                        </span>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-3 border rounded-3 text-center bg-light">
                        <span class="text-muted d-block small mb-1 fw-semibold text-uppercase" style="letter-spacing:0.03em;">STOK SETELAH MUTASI</span>
                        <span class="fw-bold text-dark fs-3">
                            {{ number_format($mutation->balance_after ?? 0, 0, ',', '.') }} {{ $mutation->masterProduct->unit ?? 'PCS' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Keterangan / Referensi -->
            <div>
                <span class="text-muted small d-block mb-1 text-uppercase fw-semibold" style="letter-spacing:0.04em; font-size: 0.72rem;">CATATAN REFERENSI & KETERANGAN</span>
                <div class="p-3 border rounded-3 bg-white text-dark fw-medium" style="line-height: 1.6; min-height: 60px;">
                    {{ $mutation->reference ?: 'Tidak ada catatan referensi khusus yang dilampirkan.' }}
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar Column -->
    <div class="col-12 col-lg-4">
        <!-- Audit Log Info Card -->
        <div class="v2-card p-4 shadow-sm mb-3">
            <h6 class="fw-bold text-dark border-bottom pb-2.5 mb-3">Audit Operational Log</h6>
            
            <div class="mb-3">
                <span class="text-muted small d-block mb-1">ID Transaksi</span>
                <span class="fw-bold font-monospace text-dark">#{{ $mutation->id }}</span>
            </div>

            <div class="mb-3">
                <span class="text-muted small d-block mb-1">Petugas Operator</span>
                <div class="fw-bold text-dark fs-6">{{ $mutation->user->name ?? 'Sistem / Admin' }}</div>
                <span class="text-muted small">{{ $mutation->user->email ?? 'admin@erp.com' }}</span>
            </div>

            <div class="mb-3">
                <span class="text-muted small d-block mb-1">Tenant Organisasi</span>
                <span class="fw-semibold text-dark">{{ Auth::user()->tenant->name ?? 'Ruang Seragam' }}</span>
            </div>

            <div>
                <span class="text-muted small d-block mb-1">Waktu Sistem Recorded</span>
                <span class="text-dark small">{{ $mutation->created_at ? $mutation->created_at->format('d/m/Y H:i:s') : '-' }}</span>
            </div>
        </div>
    </div>
</div>

@endsection
