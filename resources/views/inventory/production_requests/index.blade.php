@extends('layouts.app')
@section('title', 'Permintaan Produksi')
@section('page-title', 'Permintaan Produksi')

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-1"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-1"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
    <ul class="nav nav-tabs border-0 m-0" id="requestTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active small fw-semibold border-0" id="pending-tab" data-bs-toggle="tab" data-bs-target="#panel-pending" type="button" role="tab">
                <i class="fas fa-hourglass-half me-1 text-warning"></i> Antrean Permintaan (Pending)
                <span class="badge bg-warning text-dark ms-1">{{ count($pendingRequests) }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link small fw-semibold border-0" id="approved-tab" data-bs-toggle="tab" data-bs-target="#panel-approved" type="button" role="tab">
                <i class="fas fa-check-circle me-1 text-success"></i> Disetujui (Approved)
                <span class="badge bg-success text-white ms-1">{{ count($approvedRequests) }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link small fw-semibold border-0" id="riwayat-tab" data-bs-toggle="tab" data-bs-target="#panel-riwayat" type="button" role="tab">
                <i class="fas fa-history me-1 text-secondary"></i> Riwayat
            </button>
        </li>
    </ul>
    <a href="{{ route('production_requests.create') }}" class="btn btn-sm btn-primary px-3 rounded-2 fw-semibold">
        <i class="fas fa-plus me-1"></i> Buat Permintaan
    </a>
</div>

<div class="tab-content" id="requestTabContent">
    {{-- Tab 1: Antrean Pending --}}
    <div class="tab-pane fade show active" id="panel-pending" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-3 bg-white">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover border align-middle mb-0 rounded-2 overflow-hidden">
                        <thead class="table-light">
                            <tr class="small text-uppercase text-muted">
                                <th class="py-2 px-3">No. Permintaan</th>
                                <th>Departemen Pengaju</th>
                                <th>Tipe Permintaan</th>
                                <th>Estimasi Nilai</th>
                                <th>Tanggal Pengajuan</th>
                                <th class="text-center" style="width:160px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pendingRequests as $req)
                                <tr>
                                    <td class="fw-bold px-3 py-3 small">
                                        <a href="{{ route('production_requests.show', $req) }}" class="text-decoration-none text-primary">
                                            {{ $req->request_number }}
                                        </a>
                                    </td>
                                    <td class="small fw-semibold text-dark">{{ $req->department ? $req->department->name : '-' }}</td>
                                    <td>
                                        <span class="badge {{ $req->request_type === 'po' ? 'bg-primary' : 'bg-secondary' }} small">
                                            {{ $req->request_type === 'po' ? 'PO Pelanggan' : 'Stok Gudang Jadi' }}
                                        </span>
                                    </td>
                                    <td class="fw-semibold text-dark small">Rp {{ number_format($req->total_amount, 0, ',', '.') }}</td>
                                    <td class="small text-muted">{{ $req->created_at->format('d M Y, H:i') }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('production_requests.show', $req) }}" class="btn btn-outline-primary btn-sm px-3 fw-bold">
                                            <i class="fas fa-eye me-1"></i> Detail
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fas fa-inbox fa-2x mb-3 opacity-25 d-block"></i>
                                        Tidak ada antrean permintaan produksi saat ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Tab 2: Disetujui --}}
    <div class="tab-pane fade" id="panel-approved" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-3 bg-white">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover border align-middle mb-0 rounded-2 overflow-hidden">
                        <thead class="table-light">
                            <tr class="small text-uppercase text-muted">
                                <th class="py-2 px-3">No. Permintaan</th>
                                <th>Departemen Pengaju</th>
                                <th>Tipe Permintaan</th>
                                <th>Estimasi Nilai</th>
                                <th>Disetujui Oleh</th>
                                <th>Tanggal Persetujuan</th>
                                <th class="text-center" style="width:160px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($approvedRequests as $req)
                                <tr>
                                    <td class="fw-bold px-3 py-3 small">
                                        <a href="{{ route('production_requests.show', $req) }}" class="text-decoration-none text-primary">
                                            {{ $req->request_number }}
                                        </a>
                                    </td>
                                    <td class="small fw-semibold text-dark">{{ $req->department ? $req->department->name : '-' }}</td>
                                    <td>
                                        <span class="badge {{ $req->request_type === 'po' ? 'bg-primary' : 'bg-secondary' }} small">
                                            {{ $req->request_type === 'po' ? 'PO Pelanggan' : 'Stok Gudang Jadi' }}
                                        </span>
                                    </td>
                                    <td class="fw-semibold text-dark small">Rp {{ number_format($req->total_amount, 0, ',', '.') }}</td>
                                    <td class="small text-muted">{{ $req->approvedBy ? $req->approvedBy->name : 'Sistem' }}</td>
                                    <td class="small text-muted">{{ $req->approved_at ? $req->approved_at->format('d M Y, H:i') : '-' }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('production_requests.show', $req) }}" class="btn btn-outline-primary btn-sm px-3 fw-bold">
                                            <i class="fas fa-eye me-1"></i> Detail
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fas fa-inbox fa-2x mb-3 opacity-25 d-block"></i>
                                        Belum ada permintaan produksi yang disetujui.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Tab 3: Riwayat --}}
    <div class="tab-pane fade" id="panel-riwayat" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-3 bg-white">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover border align-middle mb-0 rounded-2 overflow-hidden">
                        <thead class="table-light">
                            <tr class="small text-uppercase text-muted">
                                <th class="py-2 px-3">No. Permintaan</th>
                                <th>Departemen Pengaju</th>
                                <th>Tipe Permintaan</th>
                                <th>Estimasi Nilai</th>
                                <th>Status</th>
                                <th>Tanggal Update</th>
                                <th class="text-center" style="width:160px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($otherRequests as $req)
                                <tr>
                                    <td class="fw-bold px-3 py-3 small">
                                        <a href="{{ route('production_requests.show', $req) }}" class="text-decoration-none text-primary">
                                            {{ $req->request_number }}
                                        </a>
                                    </td>
                                    <td class="small fw-semibold text-dark">{{ $req->department ? $req->department->name : '-' }}</td>
                                    <td>
                                        <span class="badge {{ $req->request_type === 'po' ? 'bg-primary' : 'bg-secondary' }} small">
                                            {{ $req->request_type === 'po' ? 'PO Pelanggan' : 'Stok Gudang Jadi' }}
                                        </span>
                                    </td>
                                    <td class="fw-semibold text-dark small">Rp {{ number_format($req->total_amount, 0, ',', '.') }}</td>
                                    <td>
                                        <span class="badge bg-{{ $req->status === 'completed' ? 'success' : 'danger' }} small">
                                            {{ strtoupper($req->status) }}
                                        </span>
                                    </td>
                                    <td class="small text-muted">{{ $req->updated_at->format('d M Y, H:i') }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('production_requests.show', $req) }}" class="btn btn-outline-primary btn-sm px-3 fw-bold">
                                            <i class="fas fa-eye me-1"></i> Detail
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fas fa-history fa-2x mb-3 opacity-25 d-block"></i>
                                        Tidak ada riwayat pengajuan produksi.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $otherRequests->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
