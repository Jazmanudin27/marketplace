@extends('layouts.app')
@section('title', 'Laporan HPP Produksi')
@section('page-title', 'Laporan HPP Produksi')

@section('content')
    <div class="row justify-content-start">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-info bg-opacity-10 py-2 px-3">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="fas fa-file-invoice text-info me-2"></i>Filter Laporan HPP Produksi
                    </h6>
                    <small class="text-muted">Tampilkan laporan hitungan HPP dari pesanan produksi (SPK) yang sudah selesai</small>
                </div>
                <div class="card-body">
                    <form action="{{ route('reports.production_hpp.print') }}" method="GET" target="_blank">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label form-label-sm fw-semibold">Tanggal Mulai</label>
                                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ date('Y-m-01') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label form-label-sm fw-semibold">Tanggal Selesai</label>
                                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ date('Y-m-t') }}">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label form-label-sm fw-semibold">Produk Jadi (Master)</label>
                            <select name="product_id" class="form-select form-select-sm">
                                <option value="">Semua Produk</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}">
                                        {{ $product->sku ? '[' . $product->sku . '] ' : '' }}{{ $product->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fas fa-print me-1"></i> Cetak Laporan HPP
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
