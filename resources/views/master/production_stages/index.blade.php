@extends('layouts.app')
@section('title', 'Master Tahapan Produksi')
@section('page-title', 'Master Tahapan Produksi')

@section('content')
<div class="container-fluid py-3">
    <div class="row g-3">
        {{-- Form Tambah Tahapan --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-success text-white py-3 border-0">
                    <h6 class="fw-bold mb-0"><i class="fas fa-plus-circle me-2"></i>Tambah Tahapan Baru</h6>
                </div>
                <div class="card-body p-3">
                    <form action="{{ route('production-stages.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Nama Tahapan</label>
                            <input type="text" name="name" class="form-control form-control-sm" required placeholder="Contoh: Potong / Jahit / QC">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Urutan Alur</label>
                            <input type="number" name="sort_order" class="form-control form-control-sm text-center" required min="1" value="{{ $stages->max('sort_order') + 1 }}">
                            <small class="text-muted" style="font-size:10px;">Angka lebih kecil akan muncul di urutan pertama</small>
                        </div>
                        <div class="mb-3 form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active_add" value="1" checked>
                            <label class="form-check-input-label small fw-semibold" for="is_active_add">Status Aktif</label>
                        </div>
                        <button type="submit" class="btn btn-sm btn-success w-100 fw-semibold">
                            <i class="fas fa-save me-1"></i> Simpan Tahapan
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Daftar Tahapan --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0"><i class="fas fa-tasks me-2 text-success"></i>Master Alur Tahapan Produksi</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0" style="font-size: 13px;">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3 text-center" style="width: 80px;">Urutan</th>
                                    <th>Nama Tahapan Produksi</th>
                                    <th class="text-center" style="width: 120px;">Status</th>
                                    <th class="text-center" style="width: 150px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($stages as $stage)
                                    <tr>
                                        <td class="ps-3 fw-bold text-muted text-center">#{{ $stage->sort_order }}</td>
                                        <td class="fw-bold text-dark">
                                            <i class="fas fa-circle-notch text-success me-2" style="font-size: 10px;"></i>{{ $stage->name }}
                                        </td>
                                        <td class="text-center">
                                            @if($stage->is_active)
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Aktif</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">Non-Aktif</span>
                                            @endif
                                        </td>
                                        <td class="text-center pe-3">
                                            <div class="d-flex justify-content-center gap-1">
                                                {{-- Edit Button --}}
                                                <button class="btn btn-xs btn-outline-primary py-1 px-2" data-bs-toggle="modal" data-bs-target="#editStageModal-{{ $stage->id }}">
                                                    <i class="fas fa-edit"></i>
                                                </button>

                                                {{-- Delete Button --}}
                                                <form action="{{ route('production-stages.destroy', $stage->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus tahapan produksi ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-xs btn-outline-danger py-1 px-2">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>

                                    {{-- Edit Modal --}}
                                    <div class="modal fade" id="editStageModal-{{ $stage->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered modal-sm">
                                            <div class="modal-content">
                                                <div class="modal-header py-2 bg-light">
                                                    <h6 class="modal-title fw-bold">Edit Tahapan Produksi</h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form action="{{ route('production-stages.update', $stage->id) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-body py-3">
                                                        <div class="mb-2">
                                                            <label class="form-label small fw-semibold mb-1">Nama Tahapan</label>
                                                            <input type="text" name="name" class="form-control form-control-sm" required value="{{ $stage->name }}">
                                                        </div>
                                                        <div class="mb-2">
                                                            <label class="form-label small fw-semibold mb-1">Urutan Alur</label>
                                                            <input type="number" name="sort_order" class="form-control form-control-sm text-center" required min="1" value="{{ $stage->sort_order }}">
                                                        </div>
                                                        <div class="mb-0 form-check form-switch mt-2">
                                                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active_edit_{{ $stage->id }}" value="1" {{ $stage->is_active ? 'checked' : '' }}>
                                                            <label class="form-check-label small fw-semibold" for="is_active_edit_{{ $stage->id }}">Aktif</label>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer py-2 bg-light">
                                                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn btn-sm btn-success fw-bold">Simpan Perubahan</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-3 text-muted">Belum ada tahapan produksi.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
