@extends('layouts.app')
@section('title', 'Laporan Riwayat Opname')
@section('page-title', 'Laporan Riwayat Opname')

@section('content')
    <div class="row justify-content-start">
        <div class="col-md-6">
            <div class="dashboard-card">
                <div class="card-header-line">
                    <h3><i class="fas fa-clipboard-check"></i> Filter Riwayat Opname</h3>
                </div>

                <form action="{{ route('reports.opname.print') }}" method="GET" target="_blank">
                    <div class="mb-3">
                        <label class="form-label text-muted" style="font-size:0.85rem;">Kategori Barang</label>
                        <select name="category_id" class="form-select form-select-sm form-select-dark">
                            <option value="">Semua Kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
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
                            <i class="fas fa-print me-1"></i> Cetak Laporan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
