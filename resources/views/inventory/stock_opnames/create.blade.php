@extends('layouts.app')
@section('title', 'Stock Opname Massal')
@section('page-title', 'Stock Opname Massal')

@section('content')

    {{-- ── Filter Products ─────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('stock_opnames.create') }}" method="GET">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-4">
                        <label class="form-label small fw-semibold text-secondary mb-1">
                            <i class="fas fa-search me-1"></i>Pencarian
                        </label>
                        <input type="text" name="search" class="form-control form-control-sm"
                            placeholder="Cari SKU / Nama Produk…" value="{{ request('search') }}">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-semibold text-secondary mb-1">
                            <i class="fas fa-folder me-1"></i>Kategori
                        </label>
                        <select name="category_id" class="form-select form-select-sm">
                            <option value="">Semua Kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}"
                                    {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-semibold text-secondary mb-1">
                            <i class="fas fa-tag me-1"></i>Merk
                        </label>
                        <select name="brand_id" class="form-select form-select-sm">
                            <option value="">Semua Merk</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->id }}" {{ request('brand_id') == $brand->id ? 'selected' : '' }}>
                                    {{ $brand->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm flex-fill">
                            <i class="fas fa-filter me-1"></i>Filter
                        </button>
                        @if (request()->anyFilled(['search', 'category_id', 'brand_id']))
                            <a href="{{ route('stock_opnames.create') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Opname Form ──────────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-3 border-bottom">
            <div>
                <h5 class="mb-0 fw-bold d-flex align-items-center gap-2">
                    <i class="fas fa-clipboard-check text-primary"></i> Form Stock Opname Massal
                </h5>
                <small class="text-secondary">
                    Isi kolom <strong class="text-primary">Stok Fisik (Opname)</strong> sesuai hasil hitung fisik di gudang. Kosongkan jika barang tidak dihitung.
                </small>
            </div>
            <a href="{{ route('stock_opnames.index') }}"
                class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                <i class="fas fa-arrow-left"></i> Riwayat Opname
            </a>
        </div>

        <div class="card-body">
            <form action="{{ route('stock_opnames.store') }}" method="POST" id="opnameForm">
                @csrf

                {{-- Meta Opname --}}
                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-3">
                        <label class="form-label small fw-semibold text-secondary mb-1">
                            <i class="far fa-calendar-alt me-1"></i>Tanggal Opname
                        </label>
                        <input type="date" name="opname_date" class="form-control form-control-sm"
                            value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label small fw-semibold text-secondary mb-1">
                            <i class="fas fa-user me-1"></i>Petugas (Penanggung Jawab)
                        </label>
                        <input type="text" name="pic" class="form-control form-control-sm"
                            value="{{ Auth::user()->name }}" placeholder="Nama Petugas" required>
                    </div>
                </div>

                {{-- Products Table --}}
                <div class="table-responsive mb-3">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3" style="width: 15%;">SKU</th>
                                <th>Nama Produk</th>
                                <th style="width: 15%;">Kategori</th>
                                <th style="width: 15%;">Merk</th>
                                <th class="text-center" style="width: 15%;">Stok Sistem</th>
                                <th class="text-center bg-light" style="width: 18%;">Stok Fisik (Opname)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($products as $product)
                                <tr>
                                    <td class="ps-3 font-monospace text-nowrap small text-secondary">{{ $product->sku }}</td>
                                    <td class="fw-semibold">{{ $product->name }}</td>
                                    <td>
                                        @if ($product->category)
                                            <span class="badge bg-light text-dark border">
                                                {{ $product->category->name }}
                                            </span>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($product->brand)
                                            <span class="badge bg-light text-dark border">
                                                {{ $product->brand->name }}
                                            </span>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center font-monospace fw-semibold fs-6">
                                        {{ number_format($product->stock) }}
                                    </td>
                                    <td class="text-center bg-light">
                                        <input type="number" name="actual_stocks[{{ $product->id }}]"
                                            class="form-control form-control-sm text-center fw-bold mx-auto" min="0"
                                            placeholder="—" style="max-width: 130px;">
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-secondary py-5">
                                        <i class="fas fa-box-open fa-2x mb-3 d-block opacity-50"></i>
                                        <div class="fw-semibold mb-1">Tidak Ada Produk Ditemukan</div>
                                        <div class="small text-muted">Coba ubah kata kunci atau filter pencarian Anda.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination + Submit --}}
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 pt-2">
                    <div>
                        @if ($products->hasPages())
                            {{ $products->withQueryString()->links('pagination::bootstrap-5') }}
                        @endif
                    </div>
                    @if ($products->count() > 0)
                        <button type="submit" class="btn btn-success fw-semibold px-4">
                            <i class="fas fa-save me-1"></i> Simpan Hasil Opname
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        $(function() {
            // Highlight row when physical stock input is filled
            $(document).on('input', 'input[name^="actual_stocks"]', function() {
                const row = $(this).closest('tr');
                if ($(this).val() !== '') {
                    row.addClass('table-primary');
                } else {
                    row.removeClass('table-primary');
                }
            });

            // Confirm before submit
            $('#opnameForm').on('submit', function(e) {
                const filled = $('input[name^="actual_stocks"]').filter(function() {
                    return $(this).val() !== '';
                }).length;

                if (filled === 0) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Belum Ada Data',
                        text: 'Isi minimal satu kolom Stok Fisik sebelum menyimpan.'
                    });
                }
            });
        });
    </script>
@endpush
