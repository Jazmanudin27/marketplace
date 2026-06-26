@extends('layouts.app')
@section('title', 'Pengaduan Barang')
@section('page-title', 'Daftar Pengaduan Barang Rusak')

@section('content')
<div class="container-fluid p-0">
    <div class="card border shadow-sm overflow-hidden">
        <div class="card-header bg-primary bg-opacity-10 d-flex justify-content-between align-items-center p-3 border-bottom">
            <div>
                <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-exclamation-triangle me-2 text-warning"></i>Pengaduan Barang Rusak</h6>
                <small class="text-muted d-block mt-1">Kelola dan tindak lanjuti laporan kerusakan barang dari pelanggan</small>
            </div>
            <div>
                <a href="{{ route('complaints.create') }}" class="btn btn-primary btn-sm px-3 rounded-3 fw-semibold shadow-sm">
                    <i class="fas fa-plus me-1"></i> Tambah Pengaduan
                </a>
            </div>
        </div>

        <div class="card-body p-3">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if ($complaints->isEmpty())
                <div class="text-center py-5">
                    <img src="https://illustrations.popsy.co/gray/active-search.svg" alt="No data" class="mb-3" style="max-height: 150px;">
                    <h5 class="fw-bold text-muted mb-1">Belum Ada Pengaduan</h5>
                    <p class="text-secondary small mb-3">Semua data pengaduan barang rusak akan muncul di sini.</p>
                    <a href="{{ route('complaints.create') }}" class="btn btn-primary btn-sm rounded-3 px-4">Buat Pengaduan Pertama</a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-secondary fw-semibold small">
                            <tr>
                                <th class="border-0 px-3 py-2.5" style="width: 5%;">No</th>
                                <th class="border-0 px-3 py-2.5">Nama Pengadu</th>
                                <th class="border-0 px-3 py-2.5">Nomor HP</th>
                                <th class="border-0 px-3 py-2.5">Deskripsi</th>
                                <th class="border-0 px-3 py-2.5">Tanggal Lapor</th>
                                <th class="border-0 px-3 py-2.5 text-center">Status</th>
                                <th class="border-0 px-3 py-2.5 text-center" style="width: 15%;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            @foreach ($complaints as $index => $complaint)
                                <tr class="border-bottom">
                                    <td class="px-3 py-3">{{ $complaints->firstItem() + $index }}</td>
                                    <td class="px-3 py-3 fw-bold text-dark">{{ $complaint->name }}</td>
                                    <td class="px-3 py-3">{{ $complaint->phone }}</td>
                                    <td class="px-3 py-3 text-secondary text-truncate" style="max-width: 250px;">
                                        {{ Str::limit($complaint->description, 60) }}
                                    </td>
                                    <td class="px-3 py-3">{{ $complaint->created_at->format('d M Y H:i') }}</td>
                                    <td class="px-3 py-3 text-center">
                                        @php
                                            $badgeClass = match($complaint->status) {
                                                'Pending' => 'bg-warning text-dark',
                                                'Diproses' => 'bg-primary text-white',
                                                'Selesai' => 'bg-success text-white',
                                                'Dibatalkan' => 'bg-danger text-white',
                                                default => 'bg-secondary text-white'
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }} rounded-pill px-2.5 py-1.5 fw-semibold" style="font-size: 0.75rem;">
                                            {{ $complaint->status }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            <a href="{{ route('complaints.show', $complaint->id) }}" class="btn btn-xs btn-outline-info rounded-3 px-2.5 py-1" title="Detail">
                                                <i class="fas fa-eye"></i> Detail
                                            </a>
                                            <a href="{{ route('complaints.edit', $complaint->id) }}" class="btn btn-xs btn-outline-primary rounded-3 px-2.5 py-1" title="Edit">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <form action="{{ route('complaints.destroy', $complaint->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengaduan ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-xs btn-outline-danger rounded-3 px-2.5 py-1" title="Hapus">
                                                    <i class="fas fa-trash-alt"></i> Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3 px-3">
                    <span class="small text-muted">
                        Menampilkan {{ $complaints->firstItem() }} - {{ $complaints->lastItem() }} dari {{ $complaints->total() }} pengaduan
                    </span>
                    <div>
                        {{ $complaints->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
