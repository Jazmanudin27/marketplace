@extends('layouts.app')
@section('title', 'Kartu Stok: ' . $product->name)
@section('page-title', 'Kartu Stok')

@push('styles')
<style>
    .table-hover tbody tr:hover {
        background-color: rgba(255, 255, 255, 0.03) !important;
    }
    .badge {
        font-weight: 600;
        letter-spacing: 0.02em;
    }
    .mono {
        font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
    }
    .form-control-custom {
        background: rgba(255, 255, 255, 0.03) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        color: var(--text-primary) !important;
    }
    .form-control-custom:focus {
        background: rgba(255, 255, 255, 0.05) !important;
        box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2) !important;
        border-color: var(--warning) !important;
    }
    .stock-display-card {
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.03) 0%, rgba(255, 255, 255, 0.01) 100%);
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
    }
</style>
@endpush

@section('content')
    <div class="container-fluid p-0">
        <!-- Back Button -->
        <div class="mb-4">
            <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary btn-sm px-3 fw-semibold">
                <i class="fas fa-arrow-left me-1.5"></i> Kembali ke Inventory
            </a>
        </div>

        <div class="row g-4">
            <!-- Left Column: Stock Movements History -->
            <div class="col-lg-8">
                <div class="card shadow-sm border-0" style="background: var(--bg-card); border-radius: var(--radius); border: 1px solid var(--border);">
                    <div class="card-header bg-transparent py-3 border-bottom d-flex align-items-center justify-content-between" style="border-color: var(--border) !important;">
                        <h5 class="card-title mb-0 fw-bold text-primary">
                            <i class="fas fa-history me-2"></i>Riwayat Pergerakan Stok
                        </h5>
                        <span class="text-muted small fw-medium">Menampilkan {{ $movements->firstItem() ?? 0 }}-{{ $movements->lastItem() ?? 0 }} dari {{ $movements->total() }} mutasi</span>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" style="background: transparent;">
                                <thead style="background: rgba(255, 255, 255, 0.02); border-bottom: 2px solid var(--border);">
                                    <tr>
                                        <th class="ps-4 border-0 text-muted" style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em;">Waktu</th>
                                        <th class="border-0 text-muted" style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; width: 12%;">Tipe</th>
                                        <th class="text-end border-0 text-muted" style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; width: 15%;">Jumlah (Qty)</th>
                                        <th class="text-end border-0 text-muted" style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; width: 15%;">Sisa Stok</th>
                                        <th class="border-0 text-muted" style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em;">Referensi / Alasan</th>
                                        <th class="pe-4 border-0 text-muted" style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; width: 18%;">User</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($movements as $mov)
                                        <tr style="border-bottom: 1px solid var(--border); transition: background-color 0.2s;">
                                            <td class="mono small ps-4 py-3 text-secondary">{{ $mov->created_at->format('d M Y H:i') }}</td>
                                            <td>
                                                @if ($mov->type == 'in')
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 rounded">
                                                        <i class="fas fa-arrow-down me-1"></i>MASUK
                                                    </span>
                                                @elseif($mov->type == 'out')
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1.5 rounded">
                                                        <i class="fas fa-arrow-up me-1"></i>KELUAR
                                                    </span>
                                                @else
                                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2.5 py-1.5 rounded">
                                                        <i class="fas fa-sliders-h me-1"></i>ADJUST
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="mono fw-bold text-end text-{{ $mov->quantity > 0 ? 'success' : 'danger' }} py-3 fs-6">
                                                {{ $mov->quantity > 0 ? '+' : '' }}{{ number_format($mov->quantity) }}
                                            </td>
                                            <td class="mono fw-bold text-end text-white py-3 fs-6">{{ number_format($mov->balance_after) }}</td>
                                            <td class="text-secondary py-3">{{ $mov->reference }}</td>
                                            <td class="pe-4 py-3 text-white">
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="user-avatar" style="width: 24px; height: 24px; font-size: 0.65rem;">
                                                        {{ strtoupper(substr($mov->user->name ?? 'S', 0, 1)) }}
                                                    </div>
                                                    <span class="fw-medium">{{ $mov->user->name ?? 'System' }}</span>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-5">
                                                <div class="d-flex flex-column align-items-center justify-content-center">
                                                    <i class="fas fa-history text-muted opacity-25 fa-3x mb-3"></i>
                                                    <h6 class="fw-semibold">Belum Ada Riwayat Stok</h6>
                                                    <p class="text-muted small mb-0">Semua mutasi stok (masuk, keluar, opname) akan tercatat di sini.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if ($movements->hasPages())
                        <div class="card-footer bg-transparent border-top py-3 d-flex justify-content-center" style="border-color: var(--border) !important;">
                            {{ $movements->links('pagination::bootstrap-5') }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- Right Column: Product Info & Adjustment Form -->
            <div class="col-lg-4">

                <!-- Product Info Card -->
                <div class="card shadow-sm border-0 mb-4" style="background: var(--bg-card); border-radius: var(--radius); border: 1px solid var(--border);">
                    <div class="card-header bg-transparent py-3 border-bottom" style="border-color: var(--border) !important;">
                        <h6 class="card-title mb-0 fw-bold text-white">
                            <i class="fas fa-info-circle me-2 text-primary"></i>Info Produk
                        </h6>
                    </div>
                    <div class="card-body py-3">
                        <div class="d-flex gap-3 align-items-center mb-3">
                            @if ($product->image_url)
                                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="rounded shadow-sm border border-secondary"
                                    style="width: 64px; height: 64px; object-fit: cover; background-color: rgba(255, 255, 255, 0.05);">
                            @else
                                <div class="rounded border border-secondary d-flex align-items-center justify-content-center bg-dark text-muted shadow-sm"
                                    style="width: 64px; height: 64px; flex-shrink: 0; background-color: rgba(255, 255, 255, 0.02);">
                                    <i class="fas fa-image fa-lg"></i>
                                </div>
                            @endif
                            <div>
                                <span class="mono text-muted small fw-semibold bg-dark px-2 py-0.5 rounded border" style="border-color: var(--border) !important;">{{ $product->sku }}</span>
                                <div class="fw-bold text-light mt-1.5 fs-6">{{ $product->name }}</div>
                            </div>
                        </div>

                        @php
                            $isLow = $product->stock <= $product->min_stock;
                            $boxBg = $isLow ? 'rgba(239, 68, 68, 0.08)' : 'rgba(16, 185, 129, 0.08)';
                            $boxBorder = $isLow ? 'rgba(239, 68, 68, 0.25)' : 'rgba(16, 185, 129, 0.25)';
                            $textClass = $isLow ? 'text-danger' : 'text-success';
                        @endphp
                        <div class="p-3 rounded text-center border" style="background: {{ $boxBg }}; border-color: {{ $boxBorder }} !important;">
                            <small class="text-muted d-block text-uppercase fw-bold mb-1"
                                style="font-size: 0.72rem; letter-spacing: 0.5px;">Stok Saat Ini</small>
                            <span class="mono fw-extrabold {{ $textClass }} fs-1">
                                {{ number_format($product->stock) }}
                            </span>
                            <div class="text-muted small mt-1.5">
                                @if($isLow)
                                    <i class="fas fa-exclamation-triangle text-danger me-1"></i> Stok menipis (Min: {{ $product->min_stock }})
                                @else
                                    <i class="fas fa-check-circle text-success me-1"></i> Stok aman (Min: {{ $product->min_stock }})
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Adjustment Form Card -->
                <div class="card shadow-sm border-0" style="background: var(--bg-card); border-radius: var(--radius); border: 1px solid var(--border);">
                    <div class="card-header bg-transparent py-3 border-bottom" style="border-color: var(--border) !important;">
                        <h6 class="card-title mb-0 fw-bold text-white">
                            <i class="fas fa-sliders-h me-2 text-warning"></i>Penyesuaian Stok (Adjustment)
                        </h6>
                    </div>
                    <div class="card-body py-3">
                        <form action="{{ route('inventory.adjust', $product->id) }}" method="POST">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-secondary">Jumlah Penyesuaian (Qty)</label>
                                <input type="number" name="quantity" required
                                    placeholder="Gunakan minus (-) untuk mengurangi" class="form-control form-control-custom form-control-sm">
                                <div class="form-text small text-muted mt-1.5">
                                    Contoh: <span class="text-success fw-bold">+5</span> untuk menambah, <span class="text-danger fw-bold">-3</span> untuk mengurangi.
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label small fw-bold text-secondary">Keterangan / Alasan</label>
                                <textarea name="reference" required rows="3" placeholder="Misal: Stok Opname, Barang Rusak, Penambahan Manual..."
                                    class="form-control form-control-custom form-control-sm" style="resize: vertical;"></textarea>
                            </div>

                            <button type="submit" class="btn btn-warning btn-sm w-100 text-dark fw-bold py-2.5 shadow-sm">
                                <i class="fas fa-save me-1.5"></i> Simpan Penyesuaian
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
