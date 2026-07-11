@extends('layouts.app')
@section('title', 'Daftar Surat Perintah Kerja (SPK)')
@section('page-title', 'Daftar SPK Produksi')

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
                <h5 class="fw-bold mb-1"><i class="fas fa-file-signature text-primary me-2"></i>Surat Perintah Kerja (SPK)</h5>
                <p class="text-muted small mb-0">Kelola dokumen perintah kerja produksi, pembagian tugas penjahit, dan visual desain baju.</p>
            </div>
            <div>
                <a href="{{ route('spks.create') }}" class="btn btn-primary btn-sm px-3 fw-bold rounded-2">
                    <i class="fas fa-plus me-1"></i> Buat SPK Baru
                </a>
            </div>
        </div>

        <hr class="my-4 opacity-10">

        {{-- Filter & Search Form --}}
        <form action="{{ route('spks.index') }}" method="GET" class="m-0">
            <div class="row g-2">
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" 
                            placeholder="Cari No. SPK, No. Produksi, Pemesan..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <input type="date" name="date_from" class="form-control form-control-sm" 
                        value="{{ request('date_from') }}" placeholder="Tanggal Awal">
                </div>
                <div class="col-md-3">
                    <input type="date" name="date_to" class="form-control form-control-sm" 
                        value="{{ request('date_to') }}" placeholder="Tanggal Akhir">
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary w-100 fw-bold">Filter</button>
                    @if(request()->anyFilled(['search', 'date_from', 'date_to']))
                        <a href="{{ route('spks.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                    @endif
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
                        <th class="py-3 px-3">No. SPK</th>
                        <th>No. Produksi</th>
                        <th>Tanggal</th>
                        <th>Jatuh Tempo</th>
                        <th>Pemesan</th>
                        <th>Instansi</th>
                        <th>Total Pcs</th>
                        <th>Penginput</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($spks as $row)
                        <tr>
                            <td class="font-monospace fw-bold py-3 px-3">
                                <a href="{{ route('spks.show', $row) }}" class="text-primary">{{ $row->no_spk }}</a>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border font-monospace">{{ $row->no_produksi ?: '—' }}</span>
                            </td>
                            <td class="small text-muted">{{ $row->tanggal ? $row->tanggal->format('d M Y') : '—' }}</td>
                            <td class="small text-danger fw-bold">{{ $row->deadline ? $row->deadline->format('d M Y') : '—' }}</td>
                            <td class="small text-dark fw-semibold">{{ $row->pemesan ?: '—' }}</td>
                            <td class="small text-muted">{{ $row->instansi ?: '—' }}</td>
                            <td>
                                <span class="badge bg-primary rounded-pill">{{ number_format($row->items->sum('quantity')) }} pcs</span>
                            </td>
                            <td class="small text-muted">{{ $row->penginput->name ?? 'SYSTEM' }}</td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="{{ route('spks.show', $row) }}" class="btn btn-sm btn-outline-primary" title="Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('spks.print', $row) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="Cetak SPK">
                                        <i class="fas fa-print"></i>
                                    </a>
                                    <form action="{{ route('spks.destroy', $row) }}" method="POST" class="m-0"
                                        onsubmit="return confirm('Apakah Anda yakin ingin menghapus data SPK ini? Tindakan ini tidak bisa dibatalkan.')">
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
                            <td colspan="9" class="text-center py-4 text-muted small">
                                <i class="fas fa-info-circle fa-2x mb-2 d-block opacity-25"></i>
                                Tidak ditemukan data SPK.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $spks->links() }}
        </div>
    </div>
</div>
@endsection
