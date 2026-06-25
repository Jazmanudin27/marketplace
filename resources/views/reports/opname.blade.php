@extends('layouts.app')
@section('title', 'Laporan Riwayat Opname')
@section('page-title', 'Laporan Riwayat Opname')

@section('content')
    <div class="row justify-content-start">
        <div class="col-md-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-info bg-opacity-10 py-2 px-3">
                    <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-clipboard-check text-info me-2"></i>Filter Riwayat Opname</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('reports.opname.print') }}" method="GET" target="_blank">
                        <div class="mb-3">
                            <label class="form-label form-label-sm fw-semibold">Kategori Barang</label>
                            <select name="category_id" class="form-select form-select-sm">
                                <option value="">Semua Kategori</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
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
                                <i class="fas fa-print me-1"></i> Cetak Laporan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
