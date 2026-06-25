@extends('layouts.app')
@section('title', 'Laporan Rekap Persediaan')
@section('page-title', 'Laporan Rekap Persediaan')

@section('content')
    <div class="row justify-content-start">
        <div class="col-md-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-info bg-opacity-10 py-2 px-3">
                    <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-th-list text-info me-2"></i>Filter Rekap Persediaan</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('reports.summary.print') }}" method="GET" target="_blank">
                        <div class="mb-3">
                            <label class="form-label form-label-sm fw-semibold">Kategori</label>
                            <select name="category_id" class="form-select form-select-sm">
                                <option value="">Semua Kategori</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label form-label-sm fw-semibold">Merk</label>
                            <select name="brand_id" class="form-select form-select-sm">
                                <option value="">Semua Merk</option>
                                @foreach ($brands as $brand)
                                    <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label form-label-sm fw-semibold">Dari Tanggal</label>
                                <input type="date" name="start_date" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label form-label-sm fw-semibold">Sampai Tanggal</label>
                                <input type="date" name="end_date" class="form-control form-control-sm">
                            </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fas fa-print me-1"></i> Cetak Rekap Persediaan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
