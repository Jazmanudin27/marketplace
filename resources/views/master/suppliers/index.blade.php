@extends('layouts.app')
@section('title', 'Data Supplier')
@section('page-title', 'Data Supplier')

@section('content')
    <div class="row">
        <div class="col-md-12">
            {{-- ── Filter Card ───────────────────────────────────────────── --}}
            <div class="dashboard-card mb-3 py-3">
                <form method="GET" action="{{ route('suppliers.index') }}">
                    <div class="row g-2 align-items-end">
                        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                            <label class="form-label form-label-sm">
                                <i class="fas fa-store me-1"></i>Nama Supplier
                            </label>
                            <input type="text" name="name" class="form-control form-control-sm"
                                placeholder="Cari nama supplier..." value="{{ request('name') }}">
                        </div>
                        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                            <label class="form-label form-label-sm">
                                <i class="fas fa-user me-1"></i>Kontak Person
                            </label>
                            <input type="text" name="contact_person" class="form-control form-control-sm"
                                placeholder="Cari kontak person..." value="{{ request('contact_person') }}">
                        </div>
                        <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                            <label class="form-label form-label-sm">
                                <i class="fas fa-toggle-on me-1"></i>Status
                            </label>
                            <select name="status" class="form-select form-select-sm select2">
                                <option value="">-- Semua --</option>
                                <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Aktif</option>
                                <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Nonaktif</option>
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

            {{-- ── Table Card ────────────────────────────────────────────── --}}
            <div class="dashboard-card">
                {{-- ── Header ──────────────────────────────────────────── --}}
                <div class="card-header-line d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0"><i class="fas fa-truck me-2 text-primary"></i>Daftar Supplier</h5>
                        <p class="text-muted mb-0 mt-1" style="font-size:0.75rem;">
                            Kelola data supplier &amp; vendor perusahaan
                        </p>
                    </div>
                    <a href="{{ route('suppliers.create') }}" class="btn btn-primary btn-sm px-3">
                        <i class="fas fa-plus me-1"></i> Tambah Supplier
                    </a>
                </div>

                {{-- ── Alert ───────────────────────────────────────────── --}}
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                {{-- ── Tabel ────────────────────────────────────────────── --}}
                <div class="table-responsive rounded border border-secondary border-opacity-10 mt-3">
                    <table class="table table-sm table-bordered table-premium-dark align-middle mb-0">
                        <thead>
                            <tr>
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
                                        <span
                                            class="badge rounded-circle bg-secondary bg-opacity-25 text-secondary d-inline-flex align-items-center justify-content-center"
                                            style="width:20px;height:20px;">
                                            {{ $suppliers->firstItem() + $index }}
                                        </span>
                                    </td>

                                    @if ($isSuperAdmin)
                                        <td>
                                            <span
                                                class="badge rounded-pill bg-primary bg-opacity-10 text-primary border border-primary border-opacity-10 small fw-medium">
                                                <i class="fas fa-building me-1"></i>
                                                {{ $supplier->tenant->name ?? '-' }}
                                            </span>
                                        </td>
                                    @endif

                                    <td>
                                        <strong class="text-white small">{{ $supplier->name }}</strong>
                                    </td>

                                    <td>
                                        <div class="lh-sm">
                                            <div class="text-white-50 small">
                                                <i class="fas fa-user-tie me-1"></i>
                                                {{ $supplier->contact_person ?? '—' }}
                                            </div>
                                            @if ($supplier->phone)
                                                <div class="mt-1 small">
                                                    <a href="tel:{{ $supplier->phone }}"
                                                        class="text-decoration-none text-light text-opacity-75">
                                                        <i class="fas fa-phone me-1 text-secondary"></i>
                                                        {{ $supplier->phone }}
                                                    </a>
                                                </div>
                                            @endif
                                        </div>
                                    </td>

                                    <td>
                                        @if ($supplier->address)
                                            <div class="text-white-50 text-wrap lh-sm small"
                                                title="{{ $supplier->address }}">
                                                <i class="fas fa-map-marker-alt me-1 text-secondary"></i>
                                                {{ $supplier->address }}
                                            </div>
                                        @else
                                            <span class="text-muted opacity-50">—</span>
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
                                                class="btn btn-warning btn-action-sm" title="Edit"
                                                data-bs-toggle="tooltip">
                                                <i class="fas fa-pen"></i>
                                            </a>
                                            <form action="{{ route('suppliers.destroy', $supplier) }}" method="POST"
                                                onsubmit="return confirm('Apakah Anda yakin ingin menghapus supplier ini?');"
                                                class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-action-sm" title="Hapus"
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
                                        <p class="text-muted mb-0">Tidak ada data supplier ditemukan.</p>
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
@endsection
