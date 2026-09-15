@extends('layouts.app')
@section('title', 'Laporan Stok Barang (Gudang & Marketplace)')
@section('page-title', 'Laporan Stok Barang (Gudang & Marketplace)')

@section('content')
    <div class="row justify-content-start">
        <div class="col-md-6 col-lg-5">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-info bg-opacity-10 py-2 px-3">
                    <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-th-list text-info me-2"></i>Filter Stok Barang</h6>
                </div>
                <div class="card-body p-3">
                    <form action="{{ route('reports.stock.print') }}" method="GET" target="_blank" id="stockFilterForm">
                        <div class="mb-3">
                            <label class="form-label form-label-sm fw-semibold text-muted">Toko / Marketplace</label>
                            <select name="store_id" class="form-select form-select-sm rounded-2">
                                <option value="">Semua Toko Marketplace</option>
                                @foreach ($stores as $st)
                                    <option value="{{ $st->id }}" {{ request('store_id') == $st->id ? 'selected' : '' }}>
                                        {{ $st->store_name }} ({{ ucfirst($st->channel->name ?? $st->channel->code ?? 'MP') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label form-label-sm fw-semibold text-muted">Jenis Produk</label>
                            <select name="is_bundle" class="form-select form-select-sm rounded-2">
                                <option value="">Semua Jenis (Single &amp; BUNDLE)</option>
                                <option value="0" {{ request('is_bundle') === '0' ? 'selected' : '' }}>📦 Single (Produk Standar)</option>
                                <option value="1" {{ request('is_bundle') === '1' ? 'selected' : '' }}>🎁 BUNDLE / Paket Set</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label form-label-sm fw-semibold text-muted">Tipe Pre-Order (PO)</label>
                            <select name="is_preorder" class="form-select form-select-sm rounded-2">
                                <option value="">Semua Tipe (PO &amp; Reguler)</option>
                                <option value="1" {{ request('is_preorder') === '1' ? 'selected' : '' }}>⏳ Pre-Order (PO)</option>
                                <option value="0" {{ request('is_preorder') === '0' ? 'selected' : '' }}>📦 Reguler (Bukan PO)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label form-label-sm fw-semibold text-muted">Cari Nama / SKU</label>
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm rounded-2" placeholder="Ketik nama produk atau SKU...">
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="hide_zero_stock" value="1" id="hideZeroStock" {{ request()->boolean('hide_zero_stock') ? 'checked' : '' }}>
                                <label class="form-check-label small fw-semibold text-dark" for="hideZeroStock">
                                    Sembunyikan / Hilangkan Produk Stok 0 (Tanpa Mutasi)
                                </label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="only_different" value="1" id="onlyDifferent" {{ request()->boolean('only_different') ? 'checked' : '' }}>
                                <label class="form-check-label small fw-bold text-danger" for="onlyDifferent">
                                    ⚠️ Hanya Stok Berbeda (Beda Gudang vs Toko)
                                </label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                            <a href="{{ route('reports.stock') }}" class="btn btn-sm btn-outline-secondary px-3">
                                Reset
                            </a>
                            <button type="submit" class="btn btn-sm btn-primary px-3">
                                <i class="fas fa-print me-1"></i> Cetak Stok Barang
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

