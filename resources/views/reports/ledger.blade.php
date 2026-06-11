@extends('layouts.app')
@section('title', 'Laporan Kartu Stok')
@section('page-title', 'Laporan Kartu Stok')

@section('content')
    <div class="row justify-content-start">
        <div class="col-md-6">
            <div class="dashboard-card">
                <div class="card-header-line">
                    <h3><i class="fas fa-history"></i> Filter Kartu Stok</h3>
                </div>

                <form action="{{ route('reports.ledger.print') }}" method="GET" target="_blank">
                    <div class="mb-3">
                        <label class="form-label text-muted" style="font-size:0.85rem;">Pilih Produk <span
                                class="text-danger">*</span></label>
                        <select name="product_id" class="form-select form-select-sm form-select-dark" required>
                            <option value="">-- Pilih Produk --</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}">[{{ $product->sku }}] {{ $product->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted" style="font-size:0.75rem;">Kartu stok hanya bisa dicetak per satu
                            produk.</small>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label text-muted" style="font-size:0.85rem;">Dari Tanggal</label>
                            <input type="date" name="start_date" class="form-control form-control-sm form-control-dark">
                        </div>

                        <div class="col-md-6 mb-4">
                            <label class="form-label text-muted" style="font-size:0.85rem;">Sampai Tanggal</label>
                            <input type="date" name="end_date" class="form-control form-control-sm form-control-dark">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="fas fa-print me-1"></i> Cetak Kartu Stok
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
