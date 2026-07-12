@extends('layouts.app')
@section('title', 'Master Jasa Produksi')
@section('page-title', 'Master Jasa Produksi')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card border shadow-sm overflow-hidden">
                <div class="card-header bg-info bg-opacity-10 d-flex justify-content-between align-items-center border-bottom py-2 px-3">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="fas fa-hand-holding-usd me-2 text-info"></i>Daftar Jasa Produksi (Labor & QC)
                        </h6>
                        <small class="text-muted d-block">Kelola tarif standar jasa jahit, potong, QC, dll.</small>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm px-3 rounded-3" data-bs-toggle="modal"
                        data-bs-target="#createServiceModal">
                        <i class="fas fa-plus me-1"></i> Tambah Jasa
                    </button>
                </div>

                <div class="card-body p-3">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    {{-- Validation Errors --}}
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong><i class="fas fa-exclamation-triangle me-2"></i>Periksa kembali inputan Anda:</strong>
                            <ul class="mb-0 mt-1 ps-3 small">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    {{-- Filter --}}
                    <div class="card border shadow-sm p-3 mb-3">
                        <form method="GET" action="{{ route('labor_services.index') }}">
                            <div class="row g-2 align-items-end">
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                    <label class="form-label small">
                                        <i class="fas fa-hand-holding-usd me-1"></i>Nama Jasa / QC
                                    </label>
                                    <input type="text" name="name" class="form-control form-control-sm"
                                        placeholder="Cari nama jasa..." value="{{ request('name') }}">
                                </div>
                                <div class="col-12 col-sm-6 col-md-auto d-flex gap-2">
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="fas fa-search me-1"></i> Cari
                                    </button>
                                    @if (request()->filled('name'))
                                        <a href="{{ route('labor_services.index') }}" class="btn btn-secondary btn-sm">
                                            <i class="fas fa-times me-1"></i> Reset
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </form>
                    </div>

                    {{-- Tabel --}}
                    <div class="table-responsive rounded border mt-3">
                        <table class="table table-sm table-striped table-bordered align-middle mb-0">
                            <thead>
                                <tr class="small">
                                    <th class="text-center" style="width: 70px;">#</th>
                                    <th>Nama Jasa / Kategori QC</th>
                                    <th class="text-end" style="width: 250px;">Tarif / Biaya Default</th>
                                    <th class="text-center" style="width: 150px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($services as $i => $service)
                                    <tr>
                                        <td class="text-center">
                                            <span class="badge bg-light text-secondary border small">
                                                {{ $services->firstItem() + $i }}
                                            </span>
                                        </td>
                                        <td>
                                            <i class="fas fa-tools me-2 text-secondary"></i>
                                            <span class="fw-semibold text-dark small">{{ $service->name }}</span>
                                        </td>
                                        <td class="text-end fw-semibold text-success font-monospace small">
                                            Rp {{ number_format($service->default_cost, 0, ',', '.') }}
                                        </td>
                                        <td class="text-center">
                                            <button type="button"
                                                class="btn btn-xs btn-outline-warning edit-service-btn"
                                                data-bs-toggle="modal" data-bs-target="#editServiceModal"
                                                data-name="{{ $service->name }}"
                                                data-cost="{{ number_format($service->default_cost, 0, '', '') }}"
                                                data-action="{{ route('labor_services.update', $service) }}"
                                                data-delete-action="{{ route('labor_services.destroy', $service) }}">
                                                <i class="fas fa-pencil-alt me-1"></i>Edit
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-3 small">
                                            Belum ada data jasa produksi.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $services->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Tambah Jasa --}}
    <div class="modal fade" id="createServiceModal" tabindex="-1" aria-labelledby="createServiceModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content overflow-hidden">
                <div class="d-flex align-items-center gap-3 p-3 border-bottom bg-primary bg-opacity-10">
                    <div class="bg-primary text-white rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 fs-5 p-2"
                        style="width: 38px; height: 38px;">
                        <i class="fas fa-plus"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="modal-title fw-bold fs-6 mb-0 text-dark" id="createServiceModalLabel">Tambah Jasa Produksi</h5>
                        <p class="mb-0 text-muted small">Tambahkan master jasa baru untuk resep / SPK</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('labor_services.store') }}" method="POST">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label for="create-name" class="form-label fw-bold small text-dark">
                                    Nama Jasa / Kategori QC <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="create-name" name="name"
                                    class="form-control form-control-sm @error('name') is-invalid @enderror"
                                    placeholder="Contoh: Jasa Potong, Jasa Jahit, QC, Operator..." value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="invalid-feedback small">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-12">
                                <label for="create-cost" class="form-label fw-bold small text-dark">Harga / Tarif Default</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light">Rp</span>
                                    <input type="text" id="create-cost" name="default_cost"
                                        class="form-control form-control-sm rupiah-mask" placeholder="0" value="{{ old('default_cost') }}">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-primary bg-opacity-10 px-4 py-3 border-top d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4 rounded-3">
                            <i class="fas fa-save"></i> Tambah Jasa
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Edit Jasa --}}
    <div class="modal fade" id="editServiceModal" tabindex="-1" aria-labelledby="editServiceModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content overflow-hidden">
                <div class="d-flex align-items-center gap-3 p-3 border-bottom bg-warning bg-opacity-10">
                    <div class="bg-warning text-dark rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 fs-5 p-2"
                        style="width: 38px; height: 38px;">
                        <i class="fas fa-pencil-alt text-warning"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="modal-title fw-bold fs-6 mb-0 text-dark" id="editServiceModalLabel">Edit Jasa Produksi</h5>
                        <p class="mb-0 text-muted small">Perbarui tarif standar jasa produksi</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="edit-form" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label for="edit-name" class="form-label fw-bold small text-dark">
                                    Nama Jasa / Kategori QC <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="edit-name" name="name"
                                    class="form-control form-control-sm @error('name') is-invalid @enderror" required>
                                @error('name')
                                    <div class="invalid-feedback small">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-12">
                                <label for="edit-cost" class="form-label fw-bold small text-dark">Harga / Tarif Default</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light">Rp</span>
                                    <input type="text" id="edit-cost" name="default_cost"
                                        class="form-control form-control-sm rupiah-mask" placeholder="0">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-warning bg-opacity-10 px-4 py-3 border-top d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4 rounded-3">
                            <i class="fas fa-save"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>

                {{-- Danger Zone (Hapus) --}}
                <div class="border-top px-4 py-3 bg-danger bg-opacity-5">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="fw-semibold text-danger small">
                                <i class="fas fa-exclamation-triangle me-1"></i> Hapus Jasa
                            </div>
                            <div class="text-muted small" style="font-size: 0.75rem;">
                                Menghapus master tidak menghapus riwayat yang sudah berjalan
                            </div>
                        </div>
                        <form id="delete-form" class="confirm-delete"
                            data-message="Jasa ini akan dihapus dari master secara permanen!" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger px-3 ms-3 rounded-3">
                                <i class="fas fa-trash me-1"></i> Hapus
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                const formatNumber = (num) => parseFloat(num).toLocaleString('id-ID');
                const handleRupiahInput = function(e) {
                    let cursorPosition = e.target.selectionStart;
                    let originalLength = e.target.value.length;
                    let cleanValue = e.target.value.replace(/[^0-9]/g, '');
                    if (cleanValue === '') {
                        $(e.target).val('');
                        return;
                    }
                    let formatted = formatNumber(cleanValue);
                    $(e.target).val(formatted);

                    let newLength = formatted.length;
                    cursorPosition = cursorPosition + (newLength - originalLength);
                    e.target.setSelectionRange(cursorPosition, cursorPosition);
                };

                $('.rupiah-mask').on('input', handleRupiahInput);

                $('.edit-service-btn').on('click', function() {
                    const name = $(this).data('name');
                    const cost = $(this).data('cost') || 0;
                    const action = $(this).data('action');
                    const deleteAction = $(this).data('delete-action');

                    $('#edit-name').val(name);
                    $('#edit-cost').val(cost > 0 ? formatNumber(cost) : '');
                    $('#edit-form').attr('action', action);
                    $('#delete-form').attr('action', deleteAction);
                });
            });
        </script>
    @endpush
@endsection
