@extends('layouts.app')
@section('title', 'Data Supplier')
@section('page-title', 'Data Supplier')

@section('content')
    <div class="row">
        <div class="col-md-12">


            {{-- ── Table Card ────────────────────────────────────────────── --}}
            <div class="card border shadow-sm overflow-hidden">
                {{-- ── Header ──────────────────────────────────────────── --}}
                <div
                    class="card-header bg-info bg-opacity-10 d-flex justify-content-between align-items-center border-bottom py-2 px-3">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-truck me-2 text-info"></i>Daftar Supplier</h6>
                        <small class="text-muted d-block">
                            Kelola data supplier &amp; vendor perusahaan
                        </small>
                    </div>
                    <a href="{{ route('suppliers.create') }}" class="btn btn-primary btn-sm px-3 rounded-3">
                        <i class="fas fa-plus me-1"></i> Tambah Supplier
                    </a>
                </div>
                <div class="card-body p-3">

                    {{-- ── Filter Card ───────────────────────────────────────────── --}}
                    <div class="card border shadow-sm p-3 mb-3">
                        <form method="GET" action="{{ route('suppliers.index') }}">
                            <div class="row g-2 align-items-end">
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                    <label class="form-label small">
                                        <i class="fas fa-store me-1"></i>Nama Supplier
                                    </label>
                                    <input type="text" name="name" class="form-control form-control-sm"
                                        placeholder="Cari nama supplier..." value="{{ request('name') }}">
                                </div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                    <label class="form-label small">
                                        <i class="fas fa-user me-1"></i>Kontak Person
                                    </label>
                                    <input type="text" name="contact_person" class="form-control form-control-sm"
                                        placeholder="Cari kontak person..." value="{{ request('contact_person') }}">
                                </div>
                                <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                                    <label class="form-label small">
                                        <i class="fas fa-toggle-on me-1"></i>Status
                                    </label>
                                    <select name="status" class="form-select form-select-sm select2">
                                        <option value="">-- Semua --</option>
                                        <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Aktif
                                        </option>
                                        <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Nonaktif
                                        </option>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-6 col-md-auto d-flex gap-2">
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="fas fa-search me-1"></i> Cari
                                    </button>
                                    @if (request()->hasAny(['name', 'contact_person', 'status']))
                                        <a href="{{ route('suppliers.index') }}" class="btn btn-secondary btn-sm">
                                            <i class="fas fa-times me-1"></i> Reset
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </form>
                    </div>

                    {{-- ── Tabel ────────────────────────────────────────────── --}}
                    <div class="table-responsive rounded border mt-3">
                        <table class="table table-sm table-striped table-bordered align-middle mb-0">
                            <thead>
                                <tr class="small">
                                    <th class="text-center">#</th>
                                    @if ($isSuperAdmin)
                                        <th>PERUSAHAAN</th>
                                    @endif
                                    <th>SUPPLIER</th>
                                    <th>PIC / KONTAK</th>
                                    <th>ALAMAT</th>
                                    <th class="text-center">STATUS</th>
                                    <th class="text-center">AKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($suppliers as $index => $supplier)
                                    <tr>
                                        <td class="text-center">
                                            <span class="badge bg-light text-secondary border small">
                                                {{ $suppliers->firstItem() + $index }}
                                            </span>
                                        </td>

                                        @if ($isSuperAdmin)
                                            <td>
                                                <span class="badge bg-primary-subtle text-primary border small">
                                                    <i class="fas fa-building me-1"></i>
                                                    {{ $supplier->tenant->name ?? '-' }}
                                                </span>
                                            </td>
                                        @endif

                                        <td>
                                            <strong class="text-dark small">{{ $supplier->name }}</strong>
                                        </td>

                                        <td>
                                            <div class="lh-sm small">
                                                <div class="text-secondary">
                                                    <i class="fas fa-user-tie me-1"></i>
                                                    {{ $supplier->contact_person ?? '—' }}
                                                </div>
                                                @if ($supplier->phone)
                                                    <div class="mt-1">
                                                        <a href="tel:{{ $supplier->phone }}"
                                                            class="text-decoration-none text-secondary">
                                                            <i class="fas fa-phone me-1"></i>
                                                            {{ $supplier->phone }}
                                                        </a>
                                                    </div>
                                                @endif
                                            </div>
                                        </td>

                                        <td>
                                            @if ($supplier->address)
                                                <div class="text-secondary text-wrap lh-sm small"
                                                    title="{{ $supplier->address }}">
                                                    <i class="fas fa-map-marker-alt me-1"></i>
                                                    {{ $supplier->address }}
                                                </div>
                                            @else
                                                <span class="text-muted opacity-50 small">—</span>
                                            @endif
                                        </td>

                                        <td class="text-center">
                                            @if ($supplier->is_active)
                                                <span
                                                    class="badge bg-success-subtle text-success border border-success-subtle small">
                                                    Aktif
                                                </span>
                                            @else
                                                <span
                                                    class="badge bg-danger-subtle text-danger border border-danger-subtle small">
                                                    Nonaktif
                                                </span>
                                            @endif
                                        </td>

                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <a href="{{ route('suppliers.edit', $supplier) }}"
                                                    class="btn btn-warning btn-sm" title="Edit" data-bs-toggle="tooltip">
                                                    <i class="fas fa-pen"></i>
                                                </a>
                                                <form action="{{ route('suppliers.destroy', $supplier) }}" method="POST"
                                                    onsubmit="return confirm('Apakah Anda yakin ingin menghapus supplier ini?');"
                                                    class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" title="Hapus"
                                                        data-bs-toggle="tooltip">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $isSuperAdmin ? 7 : 6 }}" class="text-center py-5">
                                            <i class="fas fa-truck fa-2x mb-3 d-block opacity-25"></i>
                                            <p class="text-muted mb-0 small">Tidak ada data supplier ditemukan.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- ── Pagination ───────────────────────────────────────── --}}
                    @if ($suppliers->hasPages())
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="text-muted small">
                                Halaman {{ $suppliers->currentPage() }} dari {{ $suppliers->lastPage() }}
                                &mdash; {{ $suppliers->total() }} total supplier
                            </span>
                            {{ $suppliers->links() }}
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
@endsection
