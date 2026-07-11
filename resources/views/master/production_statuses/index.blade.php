@extends('layouts.app')
@section('title', 'Master Status Produksi')
@section('page-title', 'Master Status Produksi')

@section('content')
<div class="container-fluid py-3">
    <div class="row g-3">
        {{-- Form Tambah Status --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-primary text-white py-3 border-0">
                    <h6 class="fw-bold mb-0"><i class="fas fa-plus-circle me-2"></i>Tambah Status Baru</h6>
                </div>
                <div class="card-body p-3">
                    <form action="{{ route('production-statuses.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Nama Status</label>
                            <input type="text" name="name" class="form-control form-control-sm" required placeholder="Contoh: Proses Steam">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Warna Label</label>
                            <select name="color" class="form-select form-select-sm" required>
                                <option value="secondary">Abu-abu (secondary)</option>
                                <option value="dark">Hitam (dark)</option>
                                <option value="warning">Kuning (warning)</option>
                                <option value="info">Biru Muda (info)</option>
                                <option value="primary">Biru (primary)</option>
                                <option value="success">Hijau (success)</option>
                                <option value="danger">Merah (danger)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Urutan Alur</label>
                            <input type="number" name="sort_order" class="form-control form-control-sm text-center" required min="1" value="{{ $statuses->max('sort_order') + 1 }}">
                            <small class="text-muted" style="font-size:10px;">Angka lebih kecil akan muncul di urutan pertama</small>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary w-100 fw-semibold">
                            <i class="fas fa-save me-1"></i> Simpan Status
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Daftar Status --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0"><i class="fas fa-list me-2 text-primary"></i>Alur Proses Produksi</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0" style="font-size: 13px;">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3" style="width: 80px;">Urutan</th>
                                    <th>Nama Status</th>
                                    <th>Warna Preview</th>
                                    <th class="text-center" style="width: 150px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($statuses as $status)
                                    <tr>
                                        <td class="ps-3 fw-bold text-muted text-center">{{ $status->sort_order }}</td>
                                        <td class="fw-bold">{{ $status->name }}</td>
                                        <td>
                                            @php
                                                $badgeColors = [
                                                    'secondary' => 'bg-secondary text-white',
                                                    'dark' => 'bg-dark text-white',
                                                    'warning' => 'bg-warning text-dark',
                                                    'info' => 'bg-info text-dark',
                                                    'primary' => 'bg-primary text-white',
                                                    'success' => 'bg-success text-white',
                                                    'danger' => 'bg-danger text-white'
                                                ];
                                                $cls = $badgeColors[$status->color] ?? 'bg-secondary text-white';
                                            @endphp
                                            <span class="badge {{ $cls }} px-3 py-1-5">{{ $status->name }}</span>
                                        </td>
                                        <td class="text-center pe-3">
                                            <div class="d-flex justify-content-center gap-1">
                                                {{-- Edit Button (Triggers Modal) --}}
                                                <button class="btn btn-xs btn-outline-primary py-1 px-2" data-bs-toggle="modal" data-bs-target="#editModal-{{ $status->id }}">
                                                    <i class="fas fa-edit"></i>
                                                </button>

                                                {{-- Delete Button --}}
                                                <form action="{{ route('production-statuses.destroy', $status->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus status ini?')">
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
                                    <div class="modal fade" id="editModal-{{ $status->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered modal-sm">
                                            <div class="modal-content">
                                                <div class="modal-header py-2">
                                                    <h6 class="modal-title fw-bold">Edit Status</h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form action="{{ route('production-statuses.update', $status->id) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-body py-3">
                                                        <div class="mb-2">
                                                            <label class="form-label small fw-semibold mb-1">Nama Status</label>
                                                            <input type="text" name="name" class="form-control form-control-sm" required value="{{ $status->name }}">
                                                        </div>
                                                        <div class="mb-2">
                                                            <label class="form-label small fw-semibold mb-1">Warna Label</label>
                                                            <select name="color" class="form-select form-select-sm" required>
                                                                <option value="secondary" {{ $status->color == 'secondary' ? 'selected' : '' }}>Abu-abu (secondary)</option>
                                                                <option value="dark" {{ $status->color == 'dark' ? 'selected' : '' }}>Hitam (dark)</option>
                                                                <option value="warning" {{ $status->color == 'warning' ? 'selected' : '' }}>Kuning (warning)</option>
                                                                <option value="info" {{ $status->color == 'info' ? 'selected' : '' }}>Biru Muda (info)</option>
                                                                <option value="primary" {{ $status->color == 'primary' ? 'selected' : '' }}>Biru (primary)</option>
                                                                <option value="success" {{ $status->color == 'success' ? 'selected' : '' }}>Hijau (success)</option>
                                                                <option value="danger" {{ $status->color == 'danger' ? 'selected' : '' }}>Merah (danger)</option>
                                                            </select>
                                                        </div>
                                                        <div class="mb-0">
                                                            <label class="form-label small fw-semibold mb-1">Urutan Alur</label>
                                                            <input type="number" name="sort_order" class="form-control form-control-sm text-center" required min="1" value="{{ $status->sort_order }}">
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer py-2">
                                                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn btn-sm btn-primary">Simpan Perubahan</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
