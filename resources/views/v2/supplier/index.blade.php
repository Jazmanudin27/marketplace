@extends('v2.layouts.app')

@section('title', 'Data Master Supplier V2')

@push('styles')
<style>
/* ─── Supplier V2 Custom Styles ─── */
.sup-kpi-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 14px 16px;
    position: relative;
    overflow: hidden;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.sup-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}
.sup-kpi-title {
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: #6b7280;
    margin-bottom: 4px;
}
.sup-kpi-value {
    font-size: 1.25rem;
    font-weight: 700;
    line-height: 1.2;
}
.sup-kpi-icon {
    position: absolute;
    right: 12px;
    bottom: 12px;
    font-size: 2.2rem;
    opacity: 0.12;
    pointer-events: none;
}
</style>
@endpush

@section('content')

{{-- ── Page Header ── --}}
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2 mb-0">
            <i class="bi bi-truck text-primary fs-5"></i> Data Master Supplier
        </h1>
        <p class="text-muted small mb-0">Kelola daftar pemasok bahan baku, produk konsinyasi, dan partner pengadaan</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('v2.supplier.index') }}" class="btn btn-sm py-1.5 px-3 shadow-sm fw-semibold" style="background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; border-radius:8px;" title="Refresh Data">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </a>
        <button type="button" class="btn btn-sm py-1.5 px-3 shadow-sm fw-semibold text-white" style="background:#16a34a; border:none; border-radius:8px;" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
            <i class="bi bi-plus-circle me-1"></i> Tambah Supplier Baru
        </button>
    </div>
</div>

{{-- ── Alert Messages ── --}}
@foreach(['success','error','info'] as $type)
    @if(session($type))
        <div class="alert alert-{{ $type === 'error' ? 'danger' : ($type === 'info' ? 'info' : 'success') }} alert-dismissible fade show mb-3 border-0 shadow-sm" role="alert" style="border-radius:10px;">
            <i class="bi bi-{{ $type === 'error' ? 'exclamation-triangle' : ($type === 'info' ? 'info-circle' : 'check-circle') }} me-2"></i>
            {!! session($type) !!}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
@endforeach

{{-- ── KPI Summary Cards ── --}}
<div class="row g-3 mb-4">
    <!-- Total Supplier -->
    <div class="col-md-6 col-12">
        <div class="sup-kpi-card border-start border-primary border-3">
            <div class="sup-kpi-title text-primary">Total Supplier Terdaftar</div>
            <div class="sup-kpi-value text-primary">{{ number_format($totalSuppliers, 0, ',', '.') }} Vendor</div>
            <i class="bi bi-building sup-kpi-icon text-primary"></i>
        </div>
    </div>
    <!-- Active Supplier -->
    <div class="col-md-6 col-12">
        <div class="sup-kpi-card border-start border-success border-3">
            <div class="sup-kpi-title text-success">Supplier Aktif</div>
            <div class="sup-kpi-value text-success">{{ number_format($activeSuppliers, 0, ',', '.') }} Vendor Aktif</div>
            <i class="bi bi-check-circle sup-kpi-icon text-success"></i>
        </div>
    </div>
</div>

{{-- ── Filter Bar ── --}}
<div class="v2-card p-3 mb-3 shadow-sm">
    <form method="GET" action="{{ route('v2.supplier.index') }}" class="row g-2 align-items-end">
        <div class="col-12 col-md-6">
            <label class="form-label small fw-semibold text-muted mb-1" style="font-size: 0.72rem;">Cari Supplier / Kontak / Alamat</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama supplier, CP, no HP..." class="form-control form-control-sm">
        </div>
        <div class="col-6 col-md-4">
            <label class="form-label small fw-semibold text-muted mb-1" style="font-size: 0.72rem;">Status Supplier</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">Semua Status</option>
                <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Aktif</option>
                <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Non-Aktif</option>
            </select>
        </div>
        <div class="col-6 col-md-2 d-flex gap-1 ms-auto">
            <button type="submit" class="btn btn-sm text-white fw-semibold flex-grow-1" style="background:#1e293b; border:none;">
                <i class="bi bi-funnel me-1"></i> Filter
            </button>
            <a href="{{ route('v2.supplier.index') }}" class="btn btn-sm text-white fw-semibold" style="background:#64748b; border:none;">
                <i class="bi bi-arrow-counterclockwise"></i>
            </a>
        </div>
    </form>
</div>

{{-- ── Suppliers Table ── --}}
<div class="card border-0 shadow-sm rounded-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 0.83rem;">
            <thead class="table-light">
                <tr>
                    <th class="ps-3" style="width: 50px;">No</th>
                    <th>Nama Supplier</th>
                    <th>Contact Person (CP)</th>
                    <th>No. Telepon / WA</th>
                    <th>Alamat</th>
                    <th class="text-center">Status</th>
                    <th class="text-center" style="width: 120px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($suppliers as $index => $sup)
                    <tr>
                        <td class="ps-3 text-muted">{{ $suppliers->firstItem() + $index }}</td>
                        <td>
                            <div class="fw-bold text-dark fs-6">{{ $sup->name }}</div>
                        </td>
                        <td>
                            <span class="fw-semibold text-secondary">{{ $sup->contact_person ?: '-' }}</span>
                        </td>
                        <td>
                            @if($sup->phone)
                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $sup->phone) }}" target="_blank" class="text-success text-decoration-none fw-semibold">
                                    <i class="bi bi-whatsapp me-1"></i>{{ $sup->phone }}
                                </a>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <span class="text-muted small">{{ \Illuminate\Support\Str::limit($sup->address, 50) ?: '-' }}</span>
                        </td>
                        <td class="text-center">
                            @if($sup->is_active)
                                <span class="badge bg-success text-white px-2 py-1" style="font-size: 0.68rem;">Aktif</span>
                            @else
                                <span class="badge bg-secondary text-white px-2 py-1" style="font-size: 0.68rem;">Non-Aktif</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="d-flex align-items-center justify-content-center gap-1">
                                <button type="button" class="btn btn-sm btn-outline-primary py-0.5 px-2 btn-edit-supplier"
                                        style="font-size: 0.75rem;"
                                        data-id="{{ $sup->id }}"
                                        data-name="{{ $sup->name }}"
                                        data-cp="{{ $sup->contact_person }}"
                                        data-phone="{{ $sup->phone }}"
                                        data-address="{{ $sup->address }}"
                                        data-active="{{ $sup->is_active ? '1' : '0' }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form action="{{ route('v2.supplier.destroy', $sup->id) }}" method="POST" class="d-inline m-0 p-0" onsubmit="return confirm('Apakah Anda yakin ingin menghapus supplier ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger py-0.5 px-2" style="font-size: 0.75rem;">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-truck fs-2 d-block mb-2 text-secondary opacity-50"></i>
                            Belum ada data supplier yang ditemukan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($suppliers->hasPages())
        <div class="card-footer bg-white py-2 border-top">
            {{ $suppliers->links() }}
        </div>
    @endif
</div>

{{-- ── Modal Tambah Supplier ── --}}
<div class="modal fade" id="addSupplierModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header text-white px-4 py-3" style="background: linear-gradient(135deg, #16a34a, #15803d);">
                <h5 class="modal-title fw-bold fs-6 d-flex align-items-center gap-2 mb-0">
                    <i class="bi bi-plus-circle fs-5"></i> Tambah Supplier Baru
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('v2.supplier.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4 bg-light">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark mb-1">Nama Supplier / Perusahaan <span class="text-danger">*</span></label>
                        <input type="text" name="name" required class="form-control form-control-sm" placeholder="Contoh: PT. Kain Nusantara">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark mb-1">Contact Person (CP)</label>
                        <input type="text" name="contact_person" class="form-control form-control-sm" placeholder="Contoh: Bpk. Heru">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark mb-1">No. Telepon / WhatsApp</label>
                        <input type="text" name="phone" class="form-control form-control-sm" placeholder="08xxxxxxxxxx">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark mb-1">Alamat Kantor / Gudang</label>
                        <textarea name="address" rows="3" class="form-control form-control-sm" placeholder="Alamat lengkap supplier..."></textarea>
                    </div>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" name="is_active" id="add_is_active" value="1" checked>
                        <label class="form-check-label small fw-semibold text-dark" for="add_is_active">Status Supplier Aktif</label>
                    </div>
                </div>
                <div class="modal-footer bg-white px-4 py-3">
                    <button type="button" class="btn btn-light border px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success fw-bold px-4 text-white" style="background:#16a34a; border:none;">
                        <i class="bi bi-check-lg me-1"></i> Simpan Supplier
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── Modal Edit Supplier ── --}}
<div class="modal fade" id="editSupplierModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header text-white px-4 py-3" style="background: linear-gradient(135deg, #2563eb, #1d4ed8);">
                <h5 class="modal-title fw-bold fs-6 d-flex align-items-center gap-2 mb-0">
                    <i class="bi bi-pencil-square fs-5"></i> Edit Data Supplier
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editSupplierForm" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-body p-4 bg-light">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark mb-1">Nama Supplier / Perusahaan <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edit_name" required class="form-control form-control-sm">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark mb-1">Contact Person (CP)</label>
                        <input type="text" name="contact_person" id="edit_cp" class="form-control form-control-sm">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark mb-1">No. Telepon / WhatsApp</label>
                        <input type="text" name="phone" id="edit_phone" class="form-control form-control-sm">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark mb-1">Alamat Kantor / Gudang</label>
                        <textarea name="address" id="edit_address" rows="3" class="form-control form-control-sm"></textarea>
                    </div>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active" value="1">
                        <label class="form-check-label small fw-semibold text-dark" for="edit_is_active">Status Supplier Aktif</label>
                    </div>
                </div>
                <div class="modal-footer bg-white px-4 py-3">
                    <button type="button" class="btn btn-light border px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4 text-white" style="background:#2563eb; border:none;">
                        <i class="bi bi-check-lg me-1"></i> Perbarui Supplier
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const editModal = new bootstrap.Modal(document.getElementById('editSupplierModal'));
    const editForm = document.getElementById('editSupplierForm');

    document.querySelectorAll('.btn-edit-supplier').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            const cp = this.dataset.cp;
            const phone = this.dataset.phone;
            const address = this.dataset.address;
            const active = this.dataset.active === '1';

            editForm.action = `/v2/supplier/${id}`;
            document.getElementById('edit_name').value = name || '';
            document.getElementById('edit_cp').value = cp || '';
            document.getElementById('edit_phone').value = phone || '';
            document.getElementById('edit_address').value = address || '';
            document.getElementById('edit_is_active').checked = active;

            editModal.show();
        });
    });
});
</script>
@endpush

@endsection
