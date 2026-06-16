@extends('layouts.app')
@section('title', 'Kartu Stok: ' . $product->name)
@section('page-title', 'Kartu Stok')

@section('content')
    <div class="container-fluid p-0">
        <!-- Back Button -->
        <div class="mb-3">
            <a href="{{ route('inventory.index') }}" class="btn btn-secondary btn-sm shadow-sm">
                <i class="fas fa-arrow-left me-1"></i> Kembali ke Inventory
            </a>
        </div>

        <div class="row g-3">

            <!-- Left Column: Stock Movements History -->
            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-transparent py-3 border-0">
                        <h5 class="card-title mb-0 fw-bold text-primary">
                            <i class="fas fa-history me-2"></i>Riwayat Pergerakan Stok
                        </h5>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th class="ps-3 border-0">Waktu</th>
                                        <th class="border-0">Tipe</th>
                                        <th class="text-end border-0">Qty</th>
                                        <th class="text-end border-0">Sisa Stok</th>
                                        <th class="border-0">Referensi / Keterangan</th>
                                        <th class="pe-3 border-0">User</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($movements as $mov)
                                        <tr>
                                            <td class="mono small ps-3">{{ $mov->created_at->format('d M Y H:i') }}</td>
                                            <td>
                                                @if ($mov->type == 'in')
                                                    <span class="badge bg-success">IN</span>
                                                @elseif($mov->type == 'out')
                                                    <span class="badge bg-danger">OUT</span>
                                                @else
                                                    <span class="badge bg-warning text-dark">ADJ</span>
                                                @endif
                                            </td>
                                            <td
                                                class="mono fw-bold text-end text-{{ $mov->quantity > 0 ? 'success' : 'danger' }}">
                                                {{ $mov->quantity > 0 ? '+' : '' }}{{ number_format($mov->quantity) }}
                                            </td>
                                            <td class="mono fw-bold text-end">{{ number_format($mov->balance_after) }}</td>
                                            <td>{{ $mov->reference }}</td>
                                            <td class="pe-3 fw-semibold">{{ $mov->user->name ?? 'System' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">Belum ada riwayat stok
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if ($movements->hasPages())
                        <div class="card-footer bg-transparent border-0 pt-0">
                            <div class="mt-3">
                                {{ $movements->links() }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Right Column: Product Info & Adjustment Form -->
            <div class="col-lg-4">

                <!-- Product Info Card -->
                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-header bg-transparent py-3 border-0">
                        <h6 class="card-title mb-0 fw-bold">
                            <i class="fas fa-info-circle me-2"></i>Info Produk
                        </h6>
                    </div>
                    <div class="card-body pt-0">
                        <div class="d-flex gap-3 align-items-center mb-3">
                            @if ($product->image_url)
                                <img src="{{ Storage::url($product->image_url) }}" alt="{{ $product->name }}"
                                    class="rounded shadow-sm" style="width: 60px; height: 60px; object-fit: cover;">
                            @else
                                <div class="rounded bg-body-secondary d-flex align-items-center justify-content-center shadow-sm"
                                    style="width: 60px; height: 60px;">
                                    <i class="fas fa-image text-muted fa-lg"></i>
                                </div>
                            @endif
                            <div>
                                <div class="fw-bold text-light">{{ $product->name }}</div>
                                <div class="mono small text-muted">{{ $product->sku }}</div>
                            </div>
                        </div>

                        <div class="p-3 rounded bg-body-tertiary text-center border">
                            <small class="text-muted d-block text-uppercase fw-semibold mb-1"
                                style="font-size: 0.72rem; letter-spacing: 0.5px;">Total Stok Saat Ini</small>
                            <span
                                class="mono fw-bold text-{{ $product->stock <= $product->min_stock ? 'danger' : 'success' }} fs-2">
                                {{ number_format($product->stock) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Adjustment Form Card -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-transparent py-3 border-0">
                        <h6 class="card-title mb-0 fw-bold">
                            <i class="fas fa-sliders-h me-2"></i>Penyesuaian Stok (Adjustment)
                        </h6>
                    </div>
                    <div class="card-body pt-0">
                        <form action="{{ route('inventory.adjust', $product->id) }}" method="POST">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label small fw-bold">Jumlah Penyesuaian (Qty)</label>
                                <input type="number" name="quantity" required
                                    placeholder="Gunakan minus (-) untuk mengurangi" class="form-control form-control-sm">
                                <div class="form-text small text-muted">Contoh: <b>5</b> untuk menambah, <b>-3</b> untuk
                                    mengurangi.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold">Keterangan / Alasan</label>
                                <textarea name="reference" required rows="3" placeholder="Misal: Stok Opname, Barang Rusak, Penambahan Manual..."
                                    class="form-control form-control-sm" style="resize: vertical;"></textarea>
                            </div>

                            <button type="submit" class="btn btn-warning btn-sm w-100 text-dark fw-bold py-2 shadow-sm">
                                <i class="fas fa-save me-1"></i> Simpan Penyesuaian
                            </button>
                        </form>
                    </div>
                </div>

            </div>

        </div>
    </div>
@endsection
