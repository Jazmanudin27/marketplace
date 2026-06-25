@extends('layouts.app')
@section('title', 'Kartu Stok: ' . $product->name)
@section('page-title', 'Kartu Stok')

@section('content')

    {{-- Back Button --}}
    <div class="mb-3">
        <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Kembali ke Inventory
        </a>
    </div>

    <div class="row g-3">

        {{-- ── LEFT: Riwayat Pergerakan Stok ── --}}
        <div class="col-lg-8">
            <div class="dashboard-card">
                <div class="card-header-line d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 fw-bold d-flex align-items-center gap-2">
                            <i class="fas fa-history text-primary"></i> Riwayat Pergerakan Stok
                        </h5>
                        <p class="text-muted mb-0 mt-1 small">
                            Menampilkan {{ $movements->firstItem() ?? 0 }}–{{ $movements->lastItem() ?? 0 }}
                            dari {{ $movements->total() }} mutasi
                        </p>
                    </div>
                </div>

                <div class="table-responsive rounded border border-secondary border-opacity-10 mt-3">
                    <table class="table table-sm table-bordered table-premium-dark align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">Waktu</th>
                                <th style="width:10%">Tipe</th>
                                <th class="text-end" style="width:13%">Qty</th>
                                <th class="text-end" style="width:13%">Sisa Stok</th>
                                <th>Referensi / Alasan</th>
                                <th class="pe-3" style="width:17%">User</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($movements as $mov)
                                <tr>
                                    <td class="ps-3 small text-muted font-monospace">
                                        {{ $mov->created_at->format('d M Y H:i') }}
                                    </td>
                                    <td>
                                        @if($mov->type === 'in')
                                            <span class="badge badge-success px-2 py-1">
                                                <i class="fas fa-arrow-down me-1"></i>MASUK
                                            </span>
                                        @elseif($mov->type === 'out')
                                            <span class="badge badge-danger px-2 py-1">
                                                <i class="fas fa-arrow-up me-1"></i>KELUAR
                                            </span>
                                        @else
                                            <span class="badge badge-warning px-2 py-1">
                                                <i class="fas fa-sliders-h me-1"></i>ADJUST
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end font-monospace fw-bold {{ $mov->quantity > 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $mov->quantity > 0 ? '+' : '' }}{{ number_format($mov->quantity) }}
                                    </td>
                                    <td class="text-end font-monospace fw-bold text-white">
                                        {{ number_format($mov->balance_after) }}
                                    </td>
                                    <td class="text-muted small">{{ $mov->reference }}</td>
                                    <td class="pe-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="user-avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width:22px;height:22px;font-size:.6rem;flex-shrink:0">
                                                {{ strtoupper(substr($mov->user->name ?? 'S', 0, 1)) }}
                                            </div>
                                            <span class="small text-light">{{ $mov->user->name ?? 'System' }}</span>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-secondary py-5">
                                        <i class="fas fa-history fa-2x mb-3 d-block opacity-25"></i>
                                        <div class="fw-semibold text-light mb-1">Belum Ada Riwayat Stok</div>
                                        <div class="small text-muted">Mutasi stok (masuk, keluar, opname) akan tercatat di sini.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($movements->hasPages())
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <span class="text-muted" style="font-size:.75rem">
                            Menampilkan {{ $movements->firstItem() ?? 0 }}–{{ $movements->lastItem() ?? 0 }}
                            dari {{ $movements->total() }} mutasi
                        </span>
                        {{ $movements->withQueryString()->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>

        {{-- ── RIGHT: Info Produk + Adjustment Form ── --}}
        <div class="col-lg-4">

            {{-- Info Produk --}}
            <div class="dashboard-card mb-3">
                <div class="card-header-line">
                    <h6 class="mb-0 fw-bold d-flex align-items-center gap-2">
                        <i class="fas fa-info-circle text-primary"></i> Info Produk
                    </h6>
                </div>

                <div class="d-flex gap-3 align-items-center mt-3 mb-3">
                    @if($product->image_url)
                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                             class="rounded border border-secondary border-opacity-25"
                             style="width:60px;height:60px;object-fit:cover;flex-shrink:0">
                    @else
                        <div class="rounded border border-secondary border-opacity-25 d-flex align-items-center justify-content-center text-muted"
                             style="width:60px;height:60px;flex-shrink:0;background:rgba(255,255,255,0.03)">
                            <i class="fas fa-image fa-lg"></i>
                        </div>
                    @endif
                    <div>
                        <code class="small text-muted"
                              style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.08);padding:2px 6px;border-radius:4px">
                            {{ $product->sku }}
                        </code>
                        <div class="fw-semibold text-light mt-1">{{ $product->name }}</div>
                    </div>
                </div>

                @php
                    $isLow = $product->stock <= $product->min_stock;
                @endphp
                <div class="text-center p-3 rounded border {{ $isLow ? 'border-danger border-opacity-25' : 'border-success border-opacity-25' }}"
                     style="background:{{ $isLow ? 'rgba(239,68,68,0.07)' : 'rgba(16,185,129,0.07)' }}">
                    <div class="text-muted small fw-semibold text-uppercase mb-1" style="font-size:.7rem;letter-spacing:.5px">
                        Stok Saat Ini
                    </div>
                    <div class="font-monospace fw-bold {{ $isLow ? 'text-danger' : 'text-success' }}"
                         style="font-size:2.5rem;line-height:1">
                        {{ number_format($product->stock) }}
                    </div>
                    <div class="small text-muted mt-1">
                        @if($isLow)
                            <i class="fas fa-exclamation-triangle text-warning me-1"></i>Menipis
                        @else
                            <i class="fas fa-check-circle text-success me-1"></i>Aman
                        @endif
                        &nbsp;·&nbsp; Min: {{ number_format($product->min_stock) }}
                    </div>
                </div>
            </div>

            {{-- Penyesuaian Stok --}}
            <div class="dashboard-card">
                <div class="card-header-line">
                    <h6 class="mb-0 fw-bold d-flex align-items-center gap-2">
                        <i class="fas fa-sliders-h text-warning"></i> Penyesuaian Stok
                    </h6>
                </div>

                <form action="{{ route('inventory.adjust', $product->id) }}" method="POST" class="mt-3">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label form-label-sm fw-semibold mb-1 text-muted">
                            Jumlah Penyesuaian (Qty)
                        </label>
                        <input type="number" name="quantity" required
                               class="form-control form-control-sm"
                               placeholder="Gunakan minus (-) untuk mengurangi">
                        <div class="form-text small mt-1">
                            <span class="text-success fw-bold">+5</span> untuk menambah,
                            <span class="text-danger fw-bold">-3</span> untuk mengurangi.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label form-label-sm fw-semibold mb-1 text-muted">
                            Keterangan / Alasan
                        </label>
                        <textarea name="reference" required rows="3"
                                  class="form-control form-control-sm"
                                  placeholder="Misal: Stock Opname, Barang Rusak…"
                                  style="resize:vertical"></textarea>
                    </div>

                    <button type="submit" class="btn btn-warning btn-sm w-100 fw-bold text-dark">
                        <i class="fas fa-save me-1"></i> Simpan Penyesuaian
                    </button>
                </form>
            </div>

        </div>
    </div>

@endsection
