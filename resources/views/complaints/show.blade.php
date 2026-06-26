@extends('layouts.app')
@section('title', 'Detail Pengaduan')
@section('page-title', 'Detail Pengaduan Barang Rusak')

@section('content')
<div class="container-fluid p-0">
    <div class="row">
        <div class="col-lg-8">
            <div class="card border shadow-sm mb-4">
                <div class="card-header bg-primary bg-opacity-10 d-flex justify-content-between align-items-center p-3 border-bottom">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-info-circle me-2 text-primary"></i>Informasi Pengaduan</h6>
                    <div>
                        @php
                            $badgeClass = match($complaint->status) {
                                'Pending' => 'bg-warning text-dark',
                                'Diproses' => 'bg-primary text-white',
                                'Selesai' => 'bg-success text-white',
                                'Dibatalkan' => 'bg-danger text-white',
                                default => 'bg-secondary text-white'
                            };
                        @endphp
                        <span class="badge {{ $badgeClass }} rounded-pill px-3 py-2 fw-semibold" style="font-size: 0.8rem;">
                            Status: {{ $complaint->status }}
                        </span>
                    </div>
                </div>

                <div class="card-body p-4">
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Informasi Pelanggan</h6>
                            <table class="table table-sm table-borderless mb-0 small">
                                <tr>
                                    <td class="fw-semibold text-secondary" style="width: 30%;">Nama</td>
                                    <td class="text-dark">: {{ $complaint->name }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold text-secondary">Nomor HP</td>
                                    <td class="text-dark">: {{ $complaint->phone }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold text-secondary">Alamat</td>
                                    <td class="text-dark">: {{ $complaint->address }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3 font-semibold">Detail Laporan</h6>
                            <table class="table table-sm table-borderless mb-0 small">
                                <tr>
                                    <td class="fw-semibold text-secondary" style="width: 40%;">ID Pengaduan</td>
                                    <td class="text-dark">: #{{ $complaint->id }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold text-secondary">Tanggal Lapor</td>
                                    <td class="text-dark">: {{ $complaint->created_at->format('d M Y, H:i') }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold text-secondary">Pembaruan Terakhir</td>
                                    <td class="text-dark">: {{ $complaint->updated_at->format('d M Y, H:i') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Deskripsi Masalah / Kerusakan</h6>
                        <div class="p-3 bg-light rounded-3 text-dark small" style="white-space: pre-wrap;">{{ $complaint->description }}</div>
                    </div>

                    <div>
                        <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Foto Barang Bukti (Maksimal 3)</h6>
                        <div class="row g-3">
                            @php $hasPhotos = false; @endphp
                            @for ($i = 1; $i <= 3; $i++)
                                @php $field = 'photo_' . $i; @endphp
                                @if ($complaint->$field)
                                    @php $hasPhotos = true; @endphp
                                    <div class="col-md-4 col-6">
                                        <div class="card border rounded-3 overflow-hidden shadow-sm h-100">
                                            <a href="#" data-bs-toggle="modal" data-bs-target="#imageModal" data-bs-img="{{ asset('storage/' . $complaint->$field) }}">
                                                <img src="{{ asset('storage/' . $complaint->$field) }}" alt="Foto Barang Bukti {{ $i }}" class="img-fluid w-100" style="height: 180px; object-fit: cover;">
                                            </a>
                                            <div class="card-footer bg-white border-0 text-center py-1.5">
                                                <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.7rem;">Foto {{ $i }}</small>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endfor

                            @if (!$hasPhotos)
                                <div class="col-12">
                                    <div class="p-3 text-center border rounded-3 bg-light text-secondary small">
                                        <i class="fas fa-image me-1"></i> Tidak ada foto bukti kerusakan yang dilampirkan.
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-light p-3 d-flex justify-content-between">
                    <div>
                        <a href="{{ route('complaints.index') }}" class="btn btn-outline-secondary btn-sm px-3 rounded-3 fw-semibold">
                            <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar
                        </a>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('complaints.edit', $complaint->id) }}" class="btn btn-primary btn-sm px-3 rounded-3 fw-semibold shadow-sm">
                            <i class="fas fa-edit me-1"></i> Ubah / Update Status
                        </a>
                        <form action="{{ route('complaints.destroy', $complaint->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengaduan ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm px-3 rounded-3 fw-semibold shadow-sm">
                                <i class="fas fa-trash-alt me-1"></i> Hapus
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Image Modal -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="modal-header bg-dark text-white border-0 py-2.5">
                <h6 class="modal-title font-semibold small mb-0" id="imageModalLabel">Preview Foto Barang Rusak</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-dark p-0 text-center">
                <img id="modal-preview-image" src="" alt="Bukti Foto" class="img-fluid" style="max-height: 80vh;">
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var imageModal = document.getElementById('imageModal');
        imageModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            var imageSrc = button.getAttribute('data-bs-img');
            var modalImage = document.getElementById('modal-preview-image');
            modalImage.src = imageSrc;
        });
    });
</script>
@endsection
