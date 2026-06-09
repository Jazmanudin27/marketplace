@extends('layouts.app')
@section('title', 'Master Merk')
@section('page-title', 'Master Merk')

@section('content')
<div class="dashboard-card">
    <div class="card-header-line">
        <h3><i class="fas fa-certificate"></i> Daftar Merk</h3>
        <a href="{{ route('brands.create') }}" class="btn-primary-sm"><i class="fas fa-plus"></i> Tambah Merk</a>
    </div>

    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama Merk</th>
                    <th>Jumlah Produk</th>
                    <th width="150">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($brands as $brand)
                <tr>
                    <td class="mono">{{ $brand->id }}</td>
                    <td class="fw-bold">{{ $brand->name }}</td>
                    <td>{{ $brand->products()->count() }}</td>
                    <td>
                        <a href="{{ route('brands.edit', $brand->id) }}" class="btn-primary-sm" style="background:var(--warning); border-color:var(--warning); color:#fff; font-size:0.7rem; padding:0.3rem 0.6rem;">
                            <i class="fas fa-pencil-alt"></i> Edit
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center text-muted" style="padding:2rem;">Belum ada merk</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
