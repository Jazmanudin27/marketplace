@extends('layouts.app')
@section('title', 'Master Kategori')
@section('page-title', 'Master Kategori')

@section('content')
    <div class="dashboard-card">
        <div class="card-header-line">
            <h3><i class="fas fa-folder"></i> Daftar Kategori</h3>
            <a href="{{ route('categories.create') }}" class="btn-primary-sm"><i class="fas fa-plus"></i> Tambah Kategori</a>
        </div>

        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Kategori</th>
                        <th>Jumlah Produk</th>
                        <th width="150">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                        <tr>
                            <td class="mono">{{ $category->id }}</td>
                            <td class="fw-bold">{{ $category->name }}</td>
                            <td>{{ $category->products()->count() }}</td>
                            <td>
                                <a href="{{ route('categories.edit', $category->id) }}" class="btn-primary-sm"
                                    style="background:var(--warning); border-color:var(--warning); color:#fff; font-size:0.7rem; padding:0.3rem 0.6rem;">
                                    <i class="fas fa-pencil-alt"></i> Edit
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted" style="padding:2rem;">Belum ada kategori</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
