@extends('layouts.app')
@section('title', 'Transfer Dana Internal')
@section('page-title', 'Mutasi & Transfer Dana Internal')

@section('content')
    {{-- KPI Cards --}}
    <div class="row g-3 mb-4">
        <!-- Total Transaksi -->
        <div class="col-12 col-md-6">
            <div class="card h-100 border rounded shadow-sm bg-white">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                        <i class="fas fa-exchange-alt fs-5"></i>
                    </div>
                    <div class="min-width-0">
                        <div class="fw-bold fs-4 text-dark">{{ number_format($transfers->total()) }}</div>
                        <div class="text-muted small">Total Transaksi Transfer</div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Total Nominal -->
        <div class="col-12 col-md-6">
            <div class="card h-100 border rounded shadow-sm bg-white">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                        <i class="fas fa-money-bill-wave fs-5"></i>
                    </div>
                    <div class="min-width-0">
                        <div class="fw-bold fs-4 text-dark">Rp {{ number_format($transfers->sum('amount'), 0, ',', '.') }}</div>
                        <div class="text-muted small">Total Nominal Dimutasikan (Halaman Ini)</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="card border rounded shadow-sm bg-white mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('finance.transfers.index') }}">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-5">
                        <label class="form-label form-label-sm fw-semibold mb-1 text-muted">Dari Tanggal</label>
                        <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-12 col-md-5">
                        <label class="form-label form-label-sm fw-semibold mb-1 text-muted">Sampai Tanggal</label>
                        <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-12 col-md-2 d-flex gap-2 justify-content-end">
                        <button type="submit" class="btn btn-sm btn-primary flex-fill"><i class="fas fa-filter me-1"></i> Filter</button>
                        <a href="{{ route('finance.transfers.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-undo"></i></a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Main Table --}}
    <div class="card border rounded shadow-sm bg-white">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-exchange-alt text-primary me-2"></i>Daftar Transfer Dana Internal</h5>
                <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addTransferModal">
                    <i class="fas fa-plus me-1"></i> Catat Transfer Dana
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover border align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>Kas Asal</th>
                            <th class="text-center" style="width: 50px;"></th>
                            <th>Kas Tujuan</th>
                            <th>Keterangan / Memo</th>
                            <th class="text-end">Nominal</th>
                            <th class="text-center" style="width:120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transfers as $tf)
                            <tr>
                                <td class="font-monospace">{{ $tf->transfer_date->format('d/m/Y') }}</td>
                                <td>
                                    <span class="badge bg-danger px-2 py-1">
                                        {{ $tf->source_label }}
                                    </span>
                                </td>
                                <td class="text-center"><i class="fas fa-arrow-right text-muted"></i></td>
                                <td>
                                    <span class="badge bg-success px-2 py-1">
                                        {{ $tf->destination_label }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $tf->description ?: '(Tanpa Memo)' }}</div>
                                </td>
                                <td class="font-monospace fw-bold text-info text-end">
                                    Rp {{ number_format($tf->amount, 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary edit-btn" data-bs-toggle="modal"
                                        data-bs-target="#editTransferModal" data-id="{{ $tf->id }}"
                                        data-source="{{ $tf->source }}" data-destination="{{ $tf->destination }}"
                                        data-amount="{{ $tf->amount }}"
                                        data-transfer_date="{{ $tf->transfer_date->format('Y-m-d') }}"
                                        data-description="{{ $tf->description }}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form action="{{ route('finance.transfers.destroy', $tf) }}" method="POST"
                                        style="display:inline;"
                                        onsubmit="return confirm('Apakah Anda yakin ingin menghapus catatan transfer ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">Belum ada catatan transfer dana.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $transfers->links() }}
            </div>
        </div>
    </div>

    {{-- MODAL TAMBAH TRANSFER --}}
    <div class="modal fade" id="addTransferModal" tabindex="-1" aria-labelledby="addTransferModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('finance.transfers.store') }}" method="POST" class="modal-content overflow-hidden border-0 shadow-lg">
                @csrf
                <div class="modal-header d-flex align-items-center gap-3 p-3 bg-success bg-opacity-10 border-0">
                    <div class="bg-success text-white rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 fs-5"
                        style="width: 38px; height: 38px;">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="modal-title fw-bold fs-6 mb-0 text-dark" id="addTransferModalLabel">Catat Transfer Dana</h5>
                        <p class="mb-0 text-muted small">Pindahkan dana antar kas internal</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Kas Sumber (Asal)</label>
                            <select name="source" class="form-select form-select-sm" required>
                                <option value="kas_besar">Kas Besar (Utama)</option>
                                <option value="kas_kecil">Kas Kecil (Operasional)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Kas Tujuan</label>
                            <select name="destination" class="form-select form-select-sm" required>
                                <option value="kas_kecil">Kas Kecil (Operasional)</option>
                                <option value="kas_besar">Kas Besar (Utama)</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Nominal Transfer (Rp)</label>
                            <input type="number" name="amount" min="0.01" step="any"
                                class="form-control form-control-sm" required placeholder="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Tanggal Transfer</label>
                            <input type="date" name="transfer_date" value="{{ date('Y-m-d') }}"
                                class="form-control form-control-sm" required>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold small">Deskripsi / Memo Transfer</label>
                        <textarea name="description" rows="3" class="form-control form-control-sm"
                            placeholder="Contoh: Pengisian petty cash mingguan untuk gudang..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 p-3 bg-light">
                    <button type="button" class="btn btn-sm btn-outline-secondary"
                        data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-success">Simpan Transfer</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL EDIT TRANSFER --}}
    <div class="modal fade" id="editTransferModal" tabindex="-1" aria-labelledby="editTransferModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="editTransferForm" method="POST" class="modal-content overflow-hidden border-0 shadow-lg">
                @csrf
                @method('PUT')
                <div class="modal-header d-flex align-items-center gap-3 p-3 bg-primary bg-opacity-10 border-0">
                    <div class="bg-primary text-white rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 fs-5"
                        style="width: 38px; height: 38px;">
                        <i class="fas fa-edit"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="modal-title fw-bold fs-6 mb-0 text-dark" id="editTransferModalLabel">Edit Transfer Dana</h5>
                        <p class="mb-0 text-muted small">Ubah detail transaksi mutasi internal</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Kas Sumber (Asal)</label>
                            <select name="source" id="edit_source" class="form-select form-select-sm" required>
                                <option value="kas_besar">Kas Besar (Utama)</option>
                                <option value="kas_kecil">Kas Kecil (Operasional)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Kas Tujuan</label>
                            <select name="destination" id="edit_destination" class="form-select form-select-sm" required>
                                <option value="kas_kecil">Kas Kecil (Operasional)</option>
                                <option value="kas_besar">Kas Besar (Utama)</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Nominal Transfer (Rp)</label>
                            <input type="number" name="amount" id="edit_amount" min="0.01" step="any"
                                class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Tanggal Transfer</label>
                            <input type="date" name="transfer_date" id="edit_transfer_date"
                                class="form-control form-control-sm" required>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold small">Deskripsi / Memo Transfer</label>
                        <textarea name="description" id="edit_description" rows="3"
                            class="form-control form-control-sm"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 p-3 bg-light">
                    <button type="button" class="btn btn-sm btn-outline-secondary"
                        data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                $('.edit-btn').on('click', function() {
                    const id = $(this).data('id');
                    const source = $(this).data('source');
                    const destination = $(this).data('destination');
                    const amount = $(this).data('amount');
                    const transferDate = $(this).data('transfer_date');
                    const description = $(this).data('description');

                    $('#editTransferForm').attr('action', `/finance/transfers/${id}`);
                    $('#edit_source').val(source).trigger('change');
                    $('#edit_destination').val(destination).trigger('change');
                    $('#edit_amount').val(amount);
                    $('#edit_transfer_date').val(transferDate);
                    $('#edit_description').val(description);
                });
            });
        </script>
    @endpush
@endsection
