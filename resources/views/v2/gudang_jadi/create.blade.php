@extends('v2.layouts.app')

@section('title', 'Catat Mutasi Gudang Jadi V2')

@section('content')

{{-- ── Page Header ── --}}
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2 mb-0">
            <i class="bi bi-box-arrow-in-down text-primary fs-5"></i> Catat Mutasi Gudang Jadi
        </h1>
        <p class="text-muted small mb-0">Input mutasi stok masuk, keluar, atau penyesuaian barang ke gudang jadi</p>
    </div>
    <div>
        <a href="{{ route('v2.gudang_jadi.index') }}" class="btn btn-sm btn-outline-secondary px-3 fw-semibold">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
        </a>
    </div>
</div>

{{-- ── Alert Notifications ── --}}
@foreach(['error','info'] as $type)
    @if(session($type))
        <div class="alert alert-{{ $type === 'error' ? 'danger' : 'info' }} alert-dismissible fade show mb-3 border-0 shadow-sm" role="alert" style="border-radius:10px;">
            <i class="bi bi-exclamation-triangle me-2"></i>
            {!! session($type) !!}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
@endforeach

{{-- ── Form Card ── --}}
<div class="v2-card p-4 shadow-sm mb-4">
    <form action="{{ route('v2.gudang_jadi.store') }}" method="POST" id="mutation-form">
        @csrf

        <div class="row g-3 mb-4">
            <!-- Jenis Mutasi -->
            <div class="col-12 col-md-4">
                <label class="form-label small fw-semibold text-dark">Jenis Mutasi <span class="text-danger">*</span></label>
                <select name="type" class="form-select @error('type') is-invalid @enderror" required id="mutation-type">
                    <option value="in" {{ old('type', $selectedType) === 'in' ? 'selected' : '' }}> Barang Masuk (+ Add Stock)</option>
                    <option value="out" {{ old('type', $selectedType) === 'out' ? 'selected' : '' }}> Barang Keluar (- Reduce Stock)</option>
                </select>
                @error('type')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Kategori / Alasan Mutasi -->
            <div class="col-12 col-md-4">
                <label class="form-label small fw-semibold text-dark">Kategori / Alasan Mutasi <span class="text-danger">*</span></label>
                <input type="text" name="category_reason" value="{{ old('category_reason', 'Hasil Produksi / Penyesuaian') }}" class="form-control @error('category_reason') is-invalid @enderror" placeholder="Contoh: Hasil Produksi SPK, Sample Toko, Pengembalian..." required>
                @error('category_reason')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Tanggal Mutasi -->
            <div class="col-12 col-md-4">
                <label class="form-label small fw-semibold text-dark">Tanggal Mutasi</label>
                <input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" class="form-control @error('date') is-invalid @enderror">
                @error('date')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Catatan Keterangan -->
            <div class="col-12">
                <label class="form-label small fw-semibold text-dark">Catatan Tambahan (Opsional)</label>
                <input type="text" name="notes" value="{{ old('notes') }}" class="form-control" placeholder="Tuliskan nomor SPK, nama penerima, atau catatan pendukung lainnya...">
            </div>
        </div>

        {{-- ── Table Input Items ── --}}
        <div class="border rounded-3 p-3 bg-light mb-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-box-seam me-1"></i> Daftar Produk Mutasi</h6>
                <button type="button" class="btn btn-sm btn-success px-3 fw-semibold" id="btn-add-item">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Baris Produk
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered align-middle bg-white mb-0" id="items-table">
                    <thead class="bg-light small text-muted">
                        <tr>
                            <th style="min-width: 250px;">PRODUK MASTER <span class="text-danger">*</span></th>
                            <th style="width: 150px;" class="text-center">STOK SAAT INI</th>
                            <th style="width: 160px;" class="text-center">QTY MUTASI <span class="text-danger">*</span></th>
                            <th style="width: 60px;" class="text-center">AKSI</th>
                        </tr>
                    </thead>
                    <tbody id="items-container">
                        <tr class="item-row">
                            <td>
                                <select name="items[0][product_id]" class="form-select form-select-sm product-select" required>
                                    <option value="">-- Pilih Produk Master --</option>
                                    @foreach($products as $p)
                                        <option value="{{ $p->id }}" data-stock="{{ $p->stock }}" data-unit="{{ $p->unit ?: 'PCS' }}" {{ $selectedProductId == $p->id ? 'selected' : '' }}>
                                            [{{ $p->sku }}] {{ $p->name }} (Stok: {{ number_format($p->stock) }})
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="text-center font-monospace fw-semibold current-stock-cell">
                                -
                            </td>
                            <td>
                                <div class="input-group input-group-sm">
                                    <input type="number" name="items[0][quantity]" class="form-control text-center fw-bold qty-input" value="1" min="1" required>
                                    <span class="input-group-text unit-label bg-light">PCS</span>
                                </div>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row" title="Hapus Baris">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="d-flex align-items-center justify-content-end gap-2">
            <a href="{{ route('v2.gudang_jadi.index') }}" class="btn btn-outline-secondary px-4 fw-semibold">Batal</a>
            <button type="submit" class="btn btn-primary px-4 fw-semibold">
                <i class="bi bi-check-circle me-1"></i> Simpan Mutasi Gudang
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let rowIndex = 1;
    const container = document.getElementById('items-container');

    function updateRowInfo(row) {
        const select = row.querySelector('.product-select');
        const stockCell = row.querySelector('.current-stock-cell');
        const unitLabel = row.querySelector('.unit-label');

        const option = select.options[select.selectedIndex];
        if (option && option.value) {
            const stock = option.getAttribute('data-stock');
            const unit = option.getAttribute('data-unit') || 'PCS';
            stockCell.textContent = Number(stock).toLocaleString() + ' ' + unit;
            unitLabel.textContent = unit;
        } else {
            stockCell.textContent = '-';
            unitLabel.textContent = 'PCS';
        }
    }

    container.addEventListener('change', function(e) {
        if (e.target.classList.contains('product-select')) {
            updateRowInfo(e.target.closest('tr'));
        }
    });

    document.querySelectorAll('.item-row').forEach(row => updateRowInfo(row));

    document.getElementById('btn-add-item').addEventListener('click', function() {
        const firstRow = container.querySelector('.item-row');
        const newRow = firstRow.cloneNode(true);

        newRow.querySelectorAll('select, input').forEach(el => {
            if (el.name) {
                el.name = el.name.replace(/\[\d+\]/, '[' + rowIndex + ']');
            }
            if (el.tagName === 'SELECT') {
                el.selectedIndex = 0;
            } else if (el.type === 'number') {
                el.value = 1;
            }
        });

        container.appendChild(newRow);
        updateRowInfo(newRow);
        rowIndex++;
    });

    container.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-remove-row');
        if (btn) {
            const rows = container.querySelectorAll('.item-row');
            if (rows.length > 1) {
                btn.closest('tr').remove();
            } else {
                alert('Minimal 1 baris produk harus tersedia.');
            }
        }
    });
});
</script>
@endpush

@endsection
