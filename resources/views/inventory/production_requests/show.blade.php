@extends('layouts.app')

@section('title', 'Detail Permintaan Produksi')
@section('page-title', 'Detail Permintaan Produksi')

@section('content')
<div class="container-fluid p-0">
    <!-- Back Button -->
    <div class="mb-3">
        <a href="{{ route('production_requests.index') }}" class="btn btn-secondary btn-sm rounded-3">
            <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar
        </a>
    </div>

    <!-- Main Grid Layout -->
    <div class="row g-3">

        <!-- Left Side: Request Details & Items -->
        <div class="col-lg-8">

            <!-- Request Detail Card -->
            <div class="card border shadow-sm mb-3">
                <div class="card-header bg-info bg-opacity-10 d-flex justify-content-between align-items-center p-3 border-bottom">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="fas fa-file-invoice me-2 text-info"></i>{{ $productionRequest->request_number }}
                    </h6>
                    <div class="d-flex gap-2 align-items-center">
                        @php
                            $badgeColor = match ($productionRequest->status) {
                                'pending' => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                default => 'secondary',
                            };
                        @endphp
                        <span class="badge bg-{{ $badgeColor }} bg-opacity-10 text-{{ $badgeColor }} border border-{{ $badgeColor }} border-opacity-25 small text-uppercase px-3 py-1">
                            {{ $productionRequest->status }}
                        </span>
                    </div>
                </div>

                <div class="card-body p-3">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <div class="p-3 border rounded h-100 bg-light">
                                <small class="text-muted d-block text-uppercase fw-semibold mb-1" style="font-size: 0.65rem;">Departemen Pengaju</small>
                                <span class="fw-bold text-dark small">{{ $productionRequest->department->name ?? '-' }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 border rounded h-100 bg-light">
                                <small class="text-muted d-block text-uppercase fw-semibold mb-1" style="font-size: 0.65rem;">Tipe Permintaan</small>
                                <span class="fw-bold text-primary small">
                                    {{ $productionRequest->request_type === 'po' ? 'PO Pelanggan' : 'Stok Gudang Jadi' }}
                                </span>
                            </div>
                        </div>

                        @if($productionRequest->request_type === 'po')
                            <div class="col-md-6">
                                <div class="p-3 border rounded h-100 bg-light">
                                    <small class="text-muted d-block text-uppercase fw-semibold mb-1" style="font-size: 0.65rem;">Nama Pelanggan PO</small>
                                    <span class="fw-bold text-dark small">{{ $productionRequest->customer_name ?? '-' }}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 border rounded h-100 bg-light">
                                    <small class="text-muted d-block text-uppercase fw-semibold mb-1" style="font-size: 0.65rem;">No. HP Pelanggan</small>
                                    <span class="font-monospace fw-semibold text-dark small">{{ $productionRequest->customer_phone ?? '-' }}</span>
                                </div>
                            </div>
                        @endif

                        <div class="col-md-12">
                            <div class="p-3 border rounded h-100 bg-light">
                                <small class="text-muted d-block text-uppercase fw-semibold mb-1" style="font-size: 0.65rem;">Tujuan / Alamat Pengiriman</small>
                                <span class="fw-semibold text-dark text-wrap small" style="white-space: pre-line;">{{ $productionRequest->shipping_address ?? '-' }}</span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="p-3 border rounded h-100 bg-light">
                                <small class="text-muted d-block text-uppercase fw-semibold mb-1" style="font-size: 0.65rem;">Toko Tujuan</small>
                                <span class="fw-semibold text-dark small">{{ $productionRequest->store->store_name ?? '-' }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 border rounded h-100 bg-light">
                                <small class="text-muted d-block text-uppercase fw-semibold mb-1" style="font-size: 0.65rem;">Tanggal Pengajuan</small>
                                <span class="fw-semibold text-dark small">{{ $productionRequest->created_at->format('d M Y, H:i') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items Card -->
            <div class="card border shadow-sm overflow-hidden mb-3">
                <div class="card-header bg-primary bg-opacity-10 d-flex align-items-center p-3 border-bottom">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-box me-2 text-primary"></i>Produk yang Diminta</h6>
                </div>
                <div class="card-body p-3">
                    <div class="table-responsive rounded border">
                        <table class="table table-sm table-striped table-bordered align-middle mb-0">
                            <thead>
                                <tr class="small text-muted">
                                    <th>PRODUK</th>
                                    <th>SKU</th>
                                    <th class="text-end">HARGA ESTIMASI</th>
                                    <th class="text-center">QTY</th>
                                    <th class="text-end">SUBTOTAL</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($productionRequest->items as $item)
                                    <tr>
                                        <td>
                                            <strong class="text-dark small">{{ $item->masterProduct->name ?? '-' }}</strong>
                                        </td>
                                        <td><code class="text-info font-monospace small">{{ $item->masterProduct->sku ?? '-' }}</code></td>
                                        <td class="text-end font-monospace text-dark small">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                                        <td class="text-center text-dark small">{{ $item->quantity }}</td>
                                        <td class="text-end font-monospace text-primary small">Rp {{ number_format($item->price * $item->quantity, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

        <!-- Right Side: Approvals & Status details -->
        <div class="col-lg-4">

            <!-- Request Summary & Cost Card -->
            <div class="card border shadow-sm mb-3">
                <div class="card-header bg-primary bg-opacity-10 p-3 border-bottom">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-wallet me-2 text-primary"></i>Ringkasan Nilai</h6>
                </div>
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">Total Estimasi</span>
                        <span class="font-monospace fw-bold text-dark fs-5">Rp {{ number_format($productionRequest->total_amount, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <!-- Approval Action Panel -->
            <div class="card border shadow-sm">
                <div class="card-header bg-warning bg-opacity-10 p-3 border-bottom">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-tasks me-2 text-warning"></i>Status & Aksi Persetujuan</h6>
                </div>
                <div class="card-body p-3">
                    @if($productionRequest->status === 'pending')
                        <p class="small text-muted mb-3">Permintaan ini menunggu persetujuan dari Bagian Produksi.</p>
                        
                        @if(Auth::user()->hasAnyRole(['admin-produksi', 'admin', 'owner', 'super-admin']))
                            <div class="d-flex flex-column gap-2">
                                <form action="{{ route('production_requests.approve', $productionRequest) }}" method="POST" class="m-0"
                                    onsubmit="return confirm('Apakah Anda yakin menyetujui permintaan produksi ini? SPK produksi akan otomatis dibuat.')">
                                    @csrf
                                    <button type="submit" class="btn btn-success w-100 fw-bold py-2">
                                        <i class="fas fa-check-circle me-1"></i> Setujui & Buat SPK
                                    </button>
                                </form>
                                <button type="button" class="btn btn-danger w-100 fw-bold py-2" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                    <i class="fas fa-times-circle me-1"></i> Tolak Permintaan
                                </button>
                            </div>
                        @else
                            <button class="btn btn-secondary w-100 fw-semibold" disabled>
                                <i class="fas fa-lock me-1"></i> Khusus Admin Produksi / Owner
                            </button>
                        @endif
                    @elseif($productionRequest->status === 'approved')
                        <div class="alert alert-success border-success bg-success bg-opacity-10 p-3 mb-0">
                            <h6 class="alert-heading fw-bold mb-2 small"><i class="fas fa-check-circle me-1"></i>Telah Disetujui</h6>
                            <div class="small text-dark">
                                Disetujui oleh: <strong>{{ $productionRequest->approvedBy->name ?? 'System' }}</strong>
                                <br>Pada: {{ $productionRequest->approved_at ? $productionRequest->approved_at->format('d M Y, H:i') : '-' }}
                            </div>
                        </div>
                    @elseif($productionRequest->status === 'rejected')
                        <div class="alert alert-danger border-danger bg-danger bg-opacity-10 p-3 mb-0">
                            <h6 class="alert-heading fw-bold mb-2 small"><i class="fas fa-times-circle me-1"></i>Telah Ditolak</h6>
                            <div class="small text-dark">
                                Ditolak oleh: <strong>{{ $productionRequest->rejectedBy->name ?? 'System' }}</strong>
                                <br>Pada: {{ $productionRequest->rejected_at ? $productionRequest->rejected_at->format('d M Y, H:i') : '-' }}
                                <hr class="my-2 opacity-25">
                                <strong>Alasan Penolakan:</strong>
                                <div class="text-danger-emphasis mt-1 fst-italic">{{ $productionRequest->rejection_reason ?? '-' }}</div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

        </div>

    </div>
</div>

<!-- Reject Reason Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold" id="rejectModalLabel"><i class="fas fa-times-circle me-2"></i>Tolak Permintaan Produksi</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('production_requests.reject', $productionRequest) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label fw-semibold small">Alasan Penolakan <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="3" required placeholder="Masukkan alasan mengapa permintaan produksi ini ditolak..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary fw-semibold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-danger fw-bold"><i class="fas fa-paper-plane me-1"></i>Kirim Penolakan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
