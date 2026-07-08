@extends('layouts.app')
@section('title', 'Penerimaan Barang (Approval)')
@section('page-title', 'Penerimaan Barang')

@section('content')
<div class="card border-0 shadow-sm rounded-3 bg-white">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle d-flex align-items-center justify-content-center"
                    style="width:42px;height:42px;background:linear-gradient(135deg,#f59e0b,#d97706)">
                    <i class="fas fa-tasks text-white"></i>
                </div>
                <div>
                    <h5 class="fw-bold text-dark mb-0">Menunggu Approval Penerimaan</h5>
                    <div class="text-muted small">Daftar pengiriman barang dari Gudang yang menunggu persetujuan bagian Produksi</div>
                </div>
            </div>
        </div>

        {{-- Filter --}}
        <form method="GET" class="row g-2 mb-4 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label small fw-semibold text-muted">Cari No. Mutasi</label>
                <input type="text" name="search" class="form-control form-control-sm"
                    value="{{ request('search') }}" placeholder="WMO-2026...">
            </div>
            <div class="col-12 col-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm px-3">
                    <i class="fas fa-search me-1"></i> Filter
                </button>
                @if(request()->anyFilled(['search']))
                    <a href="{{ route('produksi_mutations.pending_approvals') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                @endif
            </div>
        </form>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-1"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- Table --}}
        <div class="table-responsive">
            <table class="table table-hover border align-middle mb-0 rounded-2 overflow-hidden">
                <thead style="background:#fff7ed">
                    <tr class="small text-uppercase text-muted">
                        <th class="py-2 px-3">No. Mutasi Asal</th>
                        <th>Gudang Asal</th>
                        <th>Tanggal Kirim</th>
                        <th>Catatan Pengiriman</th>
                        <th class="text-center">Total Item</th>
                        <th class="text-center" style="width:200px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mutations as $m)
                        <tr>
                            <td class="font-monospace fw-bold text-dark px-3 py-3" style="font-size:13px">
                                {{ $m->mutation_number }}
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $m->fromDepartment ? $m->fromDepartment->name : 'Gudang Bahan/Logistik' }}</span>
                            </td>
                            <td class="small text-muted">{{ $m->mutation_date->format('d M Y') }}</td>
                            <td class="small text-muted" style="max-width:250px">
                                <div class="text-truncate" title="{{ $m->notes }}">{{ $m->notes ?: '—' }}</div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary rounded-pill small">
                                    {{ $m->items->count() }} item
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="{{ route('produksi_mutations.show', $m) }}"
                                        class="btn btn-info btn-sm text-white px-2 py-1" title="Detail & Review">
                                        <i class="fas fa-eye me-1"></i> Detail
                                    </a>
                                    <form action="{{ route('produksi_mutations.approve', $m) }}" method="POST"
                                        onsubmit="return confirm('Setujui penerimaan barang ini? Stok akan dimasukkan ke Produksi.')">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm px-2 py-1" title="Approve">
                                            <i class="fas fa-check me-1"></i> Terima
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fas fa-clipboard-list fa-2x mb-3 opacity-25 d-block"></i>
                                Belum ada pengiriman barang ke Produksi yang memerlukan persetujuan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $mutations->links() }}
        </div>
    </div>
</div>
@endsection
