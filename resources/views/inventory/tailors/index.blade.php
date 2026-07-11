@extends('layouts.app')
@section('title', 'Daftar Tukang Jahit')
@section('page-title', 'Daftar Tukang Jahit')

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-1"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h5 class="fw-bold mb-1"><i class="fas fa-user-tie text-primary me-2"></i>Tukang Jahit / Penjahit</h5>
                <p class="text-muted small mb-0">Kelola master data penjahit untuk pembagian tugas pada Surat Perintah Kerja (SPK).</p>
            </div>
            <div>
                <a href="{{ route('tailors.create') }}" class="btn btn-primary btn-sm px-3 fw-bold rounded-2">
                    <i class="fas fa-plus me-1"></i> Tambah Tukang Jahit
                </a>
            </div>
        </div>

        <hr class="my-4 opacity-10">

        <form action="{{ route('tailors.index') }}" method="GET" class="m-0">
            <div class="row g-2">
                <div class="col-md-9">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" 
                            placeholder="Cari nama atau no. telepon..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-sm btn-primary w-100 fw-bold">Cari</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-3 bg-white">
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover border align-middle mb-0 rounded-2 overflow-hidden">
                <thead class="table-light">
                    <tr class="small text-uppercase text-muted">
                        <th class="py-3 px-3">Nama</th>
                        <th>No. HP</th>
                        <th>Alamat</th>
                        <th>Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tailors as $row)
                        <tr>
                            <td class="fw-bold py-3 px-3 text-dark">{{ $row->name }}</td>
                            <td class="font-monospace text-muted">{{ $row->phone ?: '—' }}</td>
                            <td class="small text-muted">{{ $row->address ?: '—' }}</td>
                            <td>
                                @if($row->is_active)
                                    <span class="badge bg-success-subtle text-success border border-success border-opacity-25 rounded-pill px-3">Aktif</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger border-opacity-25 rounded-pill px-3">Non-Aktif</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="{{ route('tailors.edit', $row) }}" class="btn btn-sm btn-outline-primary" title="Ubah">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('tailors.destroy', $row) }}" method="POST" class="m-0"
                                        onsubmit="return confirm('Apakah Anda yakin ingin menghapus penjahit ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted small">
                                <i class="fas fa-info-circle fa-2x mb-2 d-block opacity-25"></i>
                                Tidak ditemukan data Tukang Jahit.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $tailors->links() }}
        </div>
    </div>
</div>
@endsection
