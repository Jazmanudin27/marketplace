@extends('layouts.app')
@section('title', 'Stock Opname Massal')
@section('page-title', 'Stock Opname Massal')
@section('content')
    <div class="row">
        <div class="col-12">


            <div class="card border-0 shadow-sm" style="background-color: var(--bg-card);">
                <div class="card-header border-bottom d-flex justify-content-between align-items-center py-3"
                    style="background-color: transparent;">
                    <h5 class="mb-0 fw-bold text-white"><i class="fas fa-clipboard-check text-primary"></i> Form Stock Opname
                    </h5>
                    <div>
                        <a href="{{ route('stock_opnames.index') }}" class="btn btn-outline-secondary btn-sm text-white">
                            <i class="fas fa-arrow-left"></i> Batal & Kembali
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="mb-4 pb-3 border-bottom border-secondary">
                        <form action="{{ route('stock_opnames.create') }}" method="GET" class="row g-3 align-items-end">
                            <div class="col-md-4 col-sm-12">
                                <label class="form-label fw-semibold small mb-1 text-white">Pencarian</label>
                                <input type="text" name="search" placeholder="Cari SKU / Nama Produk..."
                                    value="{{ request('search') }}"
                                    class="form-control form-control-sm bg-dark text-white border-secondary">
                            </div>

                            <div class="col-md-3 col-sm-6">
                                <label class="form-label fw-semibold small mb-1 text-white">Kategori</label>
                                <select name="category_id"
                                    class="form-select form-select-sm bg-dark text-white border-secondary">
                                    <option value="">Semua Kategori</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}"
                                            {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3 col-sm-6">
                                <label class="form-label fw-semibold small mb-1 text-white">Merk</label>
                                <select name="brand_id"
                                    class="form-select form-select-sm bg-dark text-white border-secondary">
                                    <option value="">Semua Merk</option>
                                    @foreach ($brands as $brand)
                                        <option value="{{ $brand->id }}"
                                            {{ request('brand_id') == $brand->id ? 'selected' : '' }}>{{ $brand->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2 col-sm-12 d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fas fa-filter"></i>
                                    Filter</button>
                                @if (request()->anyFilled(['search', 'category_id', 'brand_id']))
                                    <a href="{{ route('stock_opnames.create') }}" class="btn btn-outline-danger btn-sm"
                                        title="Reset">
                                        <i class="fas fa-times"></i>
                                    </a>
                                @endif
                            </div>
                        </form>
                    </div>

                    <form action="{{ route('stock_opnames.store') }}" method="POST" id="opnameForm">
                        @csrf

                        <div class="row mb-4 mt-2">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small mb-1 text-white">Tanggal Opname</label>
                                <input type="date" name="opname_date"
                                    class="form-control form-control-sm bg-dark text-white border-secondary"
                                    value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small mb-1 text-white">Petugas (Penanggung
                                    Jawab)</label>
                                <input type="text" name="pic"
                                    class="form-control form-control-sm bg-dark text-white border-secondary"
                                    value="{{ Auth::user()->name }}" placeholder="Nama Petugas" required>
                            </div>
                        </div>

                        <div class="table-responsive mb-3">
                            <table class="table table-hover table-bordered table-sm table-dark align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col" class="text-white">SKU</th>
                                        <th scope="col" class="text-white">Nama Produk</th>
                                        <th scope="col" class="text-white">Kategori</th>
                                        <th scope="col" class="text-white">Merk</th>
                                        <th scope="col" class="text-center text-white" style="width: 150px;">Stok Sistem
                                        </th>
                                        <th scope="col" class="text-center text-white"
                                            style="width: 200px; background-color: rgba(108,99,255,0.2);">Stok Fisik
                                            (Opname)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($products as $product)
                                        <tr>
                                            <td class="text-nowrap font-monospace">{{ $product->sku }}</td>
                                            <td class="fw-bold text-white">{{ $product->name }}</td>
                                            <td><span
                                                    class="badge bg-secondary">{{ $product->category->name ?? '-' }}</span>
                                            </td>
                                            <td><span
                                                    class="badge border border-secondary">{{ $product->brand->name ?? '-' }}</span>
                                            </td>
                                            <td class="text-center font-monospace fw-bold fs-5 text-white">
                                                {{ number_format($product->stock) }}
                                            </td>
                                            <td class="text-center" style="background-color: rgba(108,99,255,0.05);">
                                                <input type="number" name="actual_stocks[{{ $product->id }}]"
                                                    class="form-control form-control-sm bg-dark text-white border-secondary text-center fw-bold mx-auto"
                                                    style="max-width: 120px;" min="0" placeholder="-"
                                                    style="padding: 0.5rem;">
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-5">
                                                <i class="fas fa-box-open fs-2 mb-3 d-block  opacity-50"></i>
                                                Belum ada produk yang ditemukan.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div>{{ $products->links() }}</div>

                            @if ($products->count() > 0)
                                <button type="submit" class="btn btn-success px-4 py-2 fw-bold shadow-sm">
                                    <i class="fas fa-save me-1"></i> Simpan Hasil Opname
                                </button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
