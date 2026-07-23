@extends('layouts.app')
@section('title', 'Kartu Stok: ' . $product->name)
@section('page-title', 'Kartu Stok')

@section('content')

    {{-- Back Button --}}
    <div class="mb-4">
        <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Kembali ke Inventory
        </a>
    </div>

    <div class="row g-4">

        {{-- ── LEFT: Riwayat Pergerakan Stok ── --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-3 border-bottom">
                    <div>
                        <h5 class="mb-0 fw-bold d-flex align-items-center gap-2">
                            <i class="fas fa-history text-primary"></i> Riwayat Pergerakan Stok (Kartu Stok)
                        </h5>
                        <small class="text-secondary">
                            Menampilkan {{ $movements->firstItem() ?? 0 }}–{{ $movements->lastItem() ?? 0 }} dari {{ $movements->total() }} mutasi stok
                        </small>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3" style="width: 20%;">Waktu</th>
                                    <th style="width: 12%;">Tipe</th>
                                    <th class="text-end" style="width: 15%;">Qty Mutasi</th>
                                    <th class="text-end" style="width: 15%;">Sisa Stok</th>
                                    <th>Referensi / Alasan</th>
                                    <th class="pe-3" style="width: 18%;">User</th>
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
                                                <span class="badge bg-success">
                                                    <i class="fas fa-arrow-down me-1"></i>MASUK
                                                </span>
                                            @elseif($mov->type === 'out')
                                                <span class="badge bg-danger">
                                                    <i class="fas fa-arrow-up me-1"></i>KELUAR
                                                </span>
                                            @else
                                                <span class="badge bg-warning text-dark">
                                                    <i class="fas fa-sliders-h me-1"></i>ADJUST
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-end font-monospace fw-bold {{ $mov->quantity > 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $mov->quantity > 0 ? '+' : '' }}{{ number_format($mov->quantity) }}
                                        </td>
                                        <td class="text-end font-monospace fw-bold">
                                            {{ number_format($mov->balance_after) }}
                                        </td>
                                        <td class="text-secondary small">{{ $mov->reference }}</td>
                                        <td class="pe-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width:24px;height:24px;font-size:.65rem;flex-shrink:0">
                                                    {{ strtoupper(substr($mov->user->name ?? 'S', 0, 1)) }}
                                                </div>
                                                <span class="small text-dark">{{ $mov->user->name ?? 'System' }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-secondary py-5">
                                            <i class="fas fa-history fa-2x mb-3 d-block opacity-25"></i>
                                            <div class="fw-semibold mb-1">Belum Ada Riwayat Pergerakan Stok</div>
                                            <div class="small text-muted">Mutasi stok (masuk, keluar, opname) akan tercatat di sini.</div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if($movements->hasPages())
                    <div class="card-footer bg-transparent d-flex justify-content-between align-items-center py-3 border-top">
                        <span class="text-secondary small">
                            Menampilkan {{ $movements->firstItem() ?? 0 }}–{{ $movements->lastItem() ?? 0 }} dari {{ $movements->total() }} mutasi
                        </span>
                        {{ $movements->withQueryString()->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>

        {{-- ── RIGHT: Info Produk + Adjustment Form ── --}}
        <div class="col-lg-4">

            {{-- Info Produk --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent py-3 border-bottom">
                    <h6 class="mb-0 fw-bold d-flex align-items-center gap-2">
                        <i class="fas fa-info-circle text-primary"></i> Info Produk
                    </h6>
                </div>

                <div class="card-body">
                    <div class="d-flex gap-3 align-items-center mb-3">
                        @if($product->image_url)
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                                 class="rounded border"
                                 style="width:55px;height:55px;object-fit:cover;flex-shrink:0">
                        @else
                            <div class="rounded border d-flex align-items-center justify-content-center text-secondary bg-light"
                                 style="width:55px;height:55px;flex-shrink:0">
                                <i class="fas fa-box fa-lg"></i>
                            </div>
                        @endif
                        <div>
                            <span class="badge bg-light text-dark border font-monospace mb-1">
                                {{ $product->sku }}
                            </span>
                            <div class="fw-bold text-dark">{{ $product->name }}</div>
                        </div>
                    </div>

                    @php
                        $isLow = $product->stock <= $product->min_stock;
                    @endphp
                    <div class="text-center p-3 rounded border {{ $isLow ? 'bg-danger bg-opacity-10 border-danger border-opacity-25' : 'bg-success bg-opacity-10 border-success border-opacity-25' }}">
                        <div class="text-secondary small fw-semibold text-uppercase mb-1" style="font-size:.7rem;letter-spacing:.5px">
                            Stok Saat Ini
                        </div>
                        <div class="font-monospace fw-bold {{ $isLow ? 'text-danger' : 'text-success' }}" style="font-size:2.25rem;line-height:1">
                            {{ number_format($product->stock) }}
                        </div>
                        <div class="small text-secondary mt-1">
                            @if($isLow)
                                <i class="fas fa-exclamation-triangle text-danger me-1"></i>Stok Menipis
                            @else
                                <i class="fas fa-check-circle text-success me-1"></i>Stok Aman
                            @endif
                            &nbsp;·&nbsp; Min: {{ number_format($product->min_stock) }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Penyesuaian Stok --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent py-3 border-bottom">
                    <h6 class="mb-0 fw-bold d-flex align-items-center gap-2">
                        <i class="fas fa-sliders-h text-warning"></i> Penyesuaian Stok
                    </h6>
                </div>

                <div class="card-body">
                    <form action="{{ route('inventory.adjust', $product->id) }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary mb-1">
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
                            <label class="form-label small fw-semibold text-secondary mb-1">
                                Keterangan / Alasan
                            </label>
                            <textarea name="reference" required rows="3"
                                      class="form-control form-control-sm"
                                      placeholder="Misal: Stock Opname, Barang Rusak…"
                                      style="resize:vertical"></textarea>
                        </div>

                        <button type="submit" class="btn btn-warning btn-sm w-100 fw-bold">
                            <i class="fas fa-save me-1"></i> Simpan Penyesuaian
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>

@endsection
