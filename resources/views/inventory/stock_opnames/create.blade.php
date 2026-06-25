@extends('layouts.app')
@section('title', 'Stock Opname Massal')
@section('page-title', 'Stock Opname Massal')

@push('styles')
    <style>
        .table-premium-dark tr.table-active td {
            background-color: rgba(16, 185, 129, 0.06) !important;
            color: #ffffff !important;
            border-color: rgba(16, 185, 129, 0.15) !important;
        }
    </style>
@endpush

@section('content')

    {{-- ── Filter Products ─────────────────────────────────────────── --}}
    <div class="dashboard-card mb-3 py-3">
        <form action="{{ route('stock_opnames.create') }}" method="GET">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label form-label-sm fw-semibold mb-1 text-muted">
                        <i class="fas fa-search me-1"></i>Pencarian
                    </label>
                    <input type="text" name="search" class="form-control form-control-sm"
                        placeholder="Cari SKU / Nama Produk…" value="{{ request('search') }}">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label form-label-sm fw-semibold mb-1 text-muted">
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
                    <label class="form-label form-label-sm fw-semibold mb-1 text-muted">
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

    {{-- ── Opname Form ──────────────────────────────────────────────── --}}
    <div class="dashboard-card">
        <div class="card-header-line d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold d-flex align-items-center gap-2">
                    <i class="fas fa-clipboard-check text-primary"></i> Form Stock Opname
                </h5>
                <p class="text-muted mb-0 mt-1 small">
                    Isi kolom <strong class="text-primary">Stok Fisik</strong> sesuai jumlah barang hasil hitung fisik.
                    Kosongkan jika tidak dihitung.
                </p>
            </div>
            <a href="{{ route('stock_opnames.index') }}"
                class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                <i class="fas fa-arrow-left"></i> Batal &amp; Kembali
            </a>
        </div>

        <form action="{{ route('stock_opnames.store') }}" method="POST" id="opnameForm">
            @csrf

            {{-- Meta Opname --}}
            <div class="row g-3 mt-2 mb-3">
                <div class="col-6 col-md-3">
                    <label class="form-label form-label-sm fw-semibold mb-1 text-muted">
                        <i class="far fa-calendar-alt me-1"></i>Tanggal Opname
                    </label>
                    <input type="date" name="opname_date" class="form-control form-control-sm"
                        value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="col-6 col-md-4">
                    <label class="form-label form-label-sm fw-semibold mb-1 text-muted">
                        <i class="fas fa-user me-1"></i>Petugas (Penanggung Jawab)
                    </label>
                    <input type="text" name="pic" class="form-control form-control-sm"
                        value="{{ Auth::user()->name }}" placeholder="Nama Petugas" required>
                </div>
            </div>

            {{-- Products Table --}}
            <div class="table-responsive rounded border border-secondary border-opacity-10 mb-3">
                <table class="table table-sm table-bordered table-premium-dark align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3">SKU</th>
                            <th>Nama Produk</th>
                            <th>Kategori</th>
                            <th>Merk</th>
                            <th class="text-center" style="width:130px">Stok Sistem</th>
                            <th class="text-center pe-3" style="width:160px;background:rgba(99,102,241,0.08)">
                                Stok Fisik (Opname)
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                            <tr>
                                <td class="ps-3 font-monospace text-nowrap small">{{ $product->sku }}</td>
                                <td class="fw-semibold text-white">{{ $product->name }}</td>
                                <td>
                                    @if ($product->category)
                                        <span class="badge border" style="font-size:.68rem;background:rgba(255,255,255,0.03);color:#cbd5e1;border-color:rgba(255,255,255,0.08) !important">
                                            {{ $product->category->name }}
                                        </span>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($product->brand)
                                        <span class="badge border" style="font-size:.68rem;background:rgba(255,255,255,0.03);color:#cbd5e1;border-color:rgba(255,255,255,0.08) !important">
                                            {{ $product->brand->name }}
                                        </span>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td class="text-center text-white">
                                    {{ number_format($product->stock) }}
                                </td>
                                <td class="text-center" style="background:rgba(99,102,241,0.04)">
                                    <input type="number" name="actual_stocks[{{ $product->id }}]"
                                        class="form-control form-control-sm text-center fw-bold mx-auto" min="0"
                                        placeholder="—">
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-secondary py-5">
                                    <i class="fas fa-box-open fa-2x mb-3 d-block opacity-25"></i>
                                    <div class="fw-semibold text-light mb-1">Tidak Ada Produk</div>
                                    <div class="small text-muted">Sesuaikan filter pencarian.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination + Submit --}}
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
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

@endsection

@push('scripts')
    <script>
        $(function() {
            // Highlight row when physical stock input is filled
            $(document).on('input', 'input[name^="actual_stocks"]', function() {
                const row = $(this).closest('tr');
                if ($(this).val() !== '') {
                    row.addClass('table-active');
                } else {
                    row.removeClass('table-active');
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
                        text: 'Isi minimal satu kolom Stok Fisik sebelum menyimpan.',
                        background: '#151f2c',
                        color: '#f8fafc',
                        customClass: {
                            popup: 'border border-secondary border-opacity-10'
                        }
                    });
                }
            });
        });
    </script>
@endpush
