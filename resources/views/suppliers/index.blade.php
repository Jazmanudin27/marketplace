@extends('layouts.app')
@section('title', 'Data Supplier')
@section('page-title', 'Data Supplier')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="dashboard-card">
            <div class="card-header-line" style="display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0;"><i class="fas fa-truck"></i> Daftar Supplier</h3>
                <a href="{{ route('suppliers.create') }}" class="btn-primary-sm" style="background: var(--primary);">
                    <i class="fas fa-plus"></i> Tambah Supplier
                </a>
            </div>

            @if (session('success'))
                <div class="alert alert-success" style="background-color: rgba(34, 197, 94, 0.1); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.2); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <i class="fas fa-check-circle" style="margin-right: 8px;"></i> {{ session('success') }}
                </div>
            @endif

            <form method="GET" action="{{ route('suppliers.index') }}" class="mb-4 d-flex" style="gap: 10px;">
                <input type="text" name="search" class="form-input" placeholder="Cari nama supplier atau kontak person..." 
                       value="{{ request('search') }}" style="background-color: var(--bg-card); color: var(--text-primary); border-color: var(--border);">
                <button type="submit" class="btn-primary-sm" style="background: var(--primary);">Cari</button>
                @if(request()->has('search'))
                    <a href="{{ route('suppliers.index') }}" class="btn-secondary-sm" style="background: var(--bg-body); color: var(--text-primary); border: 1px solid var(--border);">Reset</a>
                @endif
            </form>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="5%" class="text-center">No</th>
                            <th width="25%">Nama Supplier</th>
                            <th width="20%">Kontak Person</th>
                            <th width="15%">No. HP / Telepon</th>
                            <th width="25%">Alamat</th>
                            <th width="5%" class="text-center">Status</th>
                            <th width="5%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($suppliers as $index => $supplier)
                            <tr>
                                <td class="text-center">{{ $suppliers->firstItem() + $index }}</td>
                                <td><strong>{{ $supplier->name }}</strong></td>
                                <td>{{ $supplier->contact_person ?? '-' }}</td>
                                <td>{{ $supplier->phone ?? '-' }}</td>
                                <td>{{ $supplier->address ?? '-' }}</td>
                                <td class="text-center">
                                    @if($supplier->is_active)
                                        <span style="background: rgba(34, 197, 94, 0.1); color: #22c55e; padding: 3px 8px; border-radius: 4px; font-size: 0.8rem;">Aktif</span>
                                    @else
                                        <span style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 3px 8px; border-radius: 4px; font-size: 0.8rem;">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div style="display: flex; gap: 5px; justify-content: center;">
                                        <a href="{{ route('suppliers.edit', $supplier) }}" class="btn-primary-sm" style="background: #eab308; padding: 0.3rem 0.6rem;" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('suppliers.destroy', $supplier) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus supplier ini?');" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-danger-sm" style="background: var(--danger); padding: 0.3rem 0.6rem;" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center" style="padding: 20px;">Tidak ada data supplier.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $suppliers->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>
@endsection
