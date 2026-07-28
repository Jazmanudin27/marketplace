@extends('layouts.app')
@section('title', 'Laporan Stok Barang')
@section('page-title', 'Laporan Stok Barang')

@section('content')
    <div class="row justify-content-start">
        <div class="col-md-6 col-lg-5">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-info bg-opacity-10 py-2.5 px-3 border-0">
                    <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                        <i class="fas fa-file-invoice text-info"></i>Filter Laporan Stok Barang
                    </h6>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('reports.stock.print') }}" method="GET" target="_blank">
                        <div class="mb-3">
                            <label class="form-label form-label-sm fw-semibold">Kategori Produk</label>
                            <select name="category_id" id="category_id" class="form-select form-select-sm select2">
                                <option value="">Semua Kategori</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label form-label-sm fw-semibold">Merk Produk</label>
                            <select name="brand_id" id="brand_id" class="form-select form-select-sm select2">
                                <option value="">Semua Merk</option>
                                @foreach ($brands as $brand)
                                    <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label form-label-sm fw-semibold">Tipe Order / Pre-Order (PO)</label>
                            <select name="is_preorder" id="is_preorder" class="form-select form-select-sm select2">
                                <option value="">Semua Tipe (PO &amp; Ready Stock)</option>
                                <option value="1">📦 Pre-Order (PO)</option>
                                <option value="0">⚡ Ready Stock (Bukan PO)</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label form-label-sm fw-semibold">Produk Spesifik</label>
                            <select name="product_id" id="product_id" class="form-select form-select-sm select2">
                                <option value="">Semua Produk Spesifik</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}">
                                        {{ $product->sku ? '[' . $product->sku . '] ' : '' }}{{ $product->name }} {{ $product->is_preorder ? '(PO)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-sm btn-primary px-3 fw-bold">
                                <i class="fas fa-print me-1"></i> Cetak Laporan Stok
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%',
            allowClear: true,
            placeholder: '-- Pilih / Ketik untuk Cari --'
        });
    });
</script>
@endpush
