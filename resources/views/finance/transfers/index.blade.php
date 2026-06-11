@extends('layouts.app')
@section('title', 'Transfer Dana Internal')
@section('page-title', 'Mutasi & Transfer Dana Internal')

@section('content')
<div class="stats-grid mb-4" style="display:grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
    <div class="stat-card stat-primary">
        <div class="stat-icon"><i class="fas fa-exchange-alt"></i></div>
        <div class="stat-body">
            <div class="stat-value">{{ number_format($transfers->total()) }}</div>
            <div class="stat-label">Total Transaksi Transfer</div>
        </div>
        <div class="stat-glow"></div>
    </div>
    <div class="stat-card stat-success">
        <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
        <div class="stat-body">
            <div class="stat-value">Rp {{ number_format($transfers->sum('amount'), 0, ',', '.') }}</div>
            <div class="stat-label">Total Nominal Dimutasikan (Halaman Ini)</div>
        </div>
        <div class="stat-glow"></div>
    </div>
</div>

<div class="dashboard-card mb-4">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
        <form method="GET" action="{{ route('finance.transfers.index') }}" style="display:flex; flex-wrap:wrap; gap:0.75rem; align-items:flex-end; flex:1;">
            <div style="width:150px;">
                <label class="form-label small text-muted display-block mb-1">Dari Tanggal</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control form-control-sm form-control-dark">
            </div>
            <div style="width:150px;">
                <label class="form-label small text-muted display-block mb-1">Sampai Tanggal</label>
                <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control form-control-sm form-control-dark">
            </div>
            <div>
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i> Filter</button>
                <a href="{{ route('finance.transfers.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-undo"></i></a>
            </div>
        </form>
        <div>
            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addTransferModal">
                <i class="fas fa-plus me-1"></i> Catat Transfer Dana
            </button>
        </div>
    </div>
</div>

<div class="dashboard-card">
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Kas Asal</th>
                    <th style="width: 50px; text-align:center;"></th>
                    <th>Kas Tujuan</th>
                    <th>Keterangan / Memo</th>
                    <th style="text-align:right;">Nominal</th>
                    <th style="text-align:center; width:120px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transfers as $tf)
                <tr>
                    <td class="mono">{{ $tf->transfer_date->format('d/m/Y') }}</td>
                    <td>
                        <span class="badge bg-danger">
                            {{ $tf->source_label }}
                        </span>
                    </td>
                    <td style="text-align:center;"><i class="fas fa-arrow-right text-muted"></i></td>
                    <td>
                        <span class="badge bg-success">
                            {{ $tf->destination_label }}
                        </span>
                    </td>
                    <td>
                        <div style="font-weight:500; color:var(--text-primary);">{{ $tf->description ?: '(Tanpa Memo)' }}</div>
                    </td>
                    <td class="mono fw-bold text-info" style="text-align:right;">
                        Rp {{ number_format($tf->amount, 0, ',', '.') }}
                    </td>
                    <td style="text-align:center;">
                        <button class="btn btn-sm btn-outline-primary edit-btn" 
                                data-bs-toggle="modal" 
                                data-bs-target="#editTransferModal"
                                data-id="{{ $tf->id }}"
                                data-source="{{ $tf->source }}"
                                data-destination="{{ $tf->destination }}"
                                data-amount="{{ $tf->amount }}"
                                data-transfer_date="{{ $tf->transfer_date->format('Y-m-d') }}"
                                data-description="{{ $tf->description }}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <form action="{{ route('finance.transfers.destroy', $tf) }}" method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus catatan transfer ini?')">
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
                    <td colspan="7" class="text-center text-muted" style="padding:3rem;">Belum ada catatan transfer dana.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:1rem;">
        {{ $transfers->links() }}
    </div>
</div>

{{-- MODAL TAMBAH TRANSFER --}}
<div class="modal fade" id="addTransferModal" tabindex="-1" aria-labelledby="addTransferModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('finance.transfers.store') }}" method="POST" class="modal-content" style="background-color: var(--bg-card); color: var(--text-primary); border: 1px solid var(--border);">
            @csrf
            <div class="modal-header" style="border-bottom: 1px solid var(--border);">
                <h5 class="modal-title" id="addTransferModalLabel"><i class="fas fa-exchange-alt text-success me-2"></i>Catat Transfer Dana</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="display:flex; flex-direction:column; gap:1rem;">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                    <div>
                        <label class="form-label small text-muted">Kas Sumber (Asal)</label>
                        <select name="source" class="form-select form-select-sm form-select-dark" required>
                            <option value="kas_besar">Kas Besar (Utama)</option>
                            <option value="kas_kecil">Kas Kecil (Operasional)</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label small text-muted">Kas Tujuan</label>
                        <select name="destination" class="form-select form-select-sm form-select-dark" required>
                            <option value="kas_kecil">Kas Kecil (Operasional)</option>
                            <option value="kas_besar">Kas Besar (Utama)</option>
                        </select>
                    </div>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                    <div>
                        <label class="form-label small text-muted">Nominal Transfer (Rp)</label>
                        <input type="number" name="amount" min="0.01" step="any" class="form-control form-control-sm form-control-dark" required placeholder="0">
                    </div>
                    <div>
                        <label class="form-label small text-muted">Tanggal Transfer</label>
                        <input type="date" name="transfer_date" value="{{ date('Y-m-d') }}" class="form-control form-control-sm form-control-dark" required>
                    </div>
                </div>
                <div>
                    <label class="form-label small text-muted">Deskripsi / Memo Transfer</label>
                    <textarea name="description" rows="3" class="form-control form-control-sm form-control-dark" placeholder="Contoh: Pengisian petty cash mingguan untuk gudang..."></textarea>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid var(--border);">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-sm btn-success">Simpan Transfer</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL EDIT TRANSFER --}}
<div class="modal fade" id="editTransferModal" tabindex="-1" aria-labelledby="editTransferModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="editTransferForm" method="POST" class="modal-content" style="background-color: var(--bg-card); color: var(--text-primary); border: 1px solid var(--border);">
            @csrf
            @method('PUT')
            <div class="modal-header" style="border-bottom: 1px solid var(--border);">
                <h5 class="modal-title" id="editTransferModalLabel"><i class="fas fa-edit text-primary me-2"></i>Edit Transfer Dana</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="display:flex; flex-direction:column; gap:1rem;">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                    <div>
                        <label class="form-label small text-muted">Kas Sumber (Asal)</label>
                        <select name="source" id="edit_source" class="form-select form-select-sm form-select-dark" required>
                            <option value="kas_besar">Kas Besar (Utama)</option>
                            <option value="kas_kecil">Kas Kecil (Operasional)</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label small text-muted">Kas Tujuan</label>
                        <select name="destination" id="edit_destination" class="form-select form-select-sm form-select-dark" required>
                            <option value="kas_kecil">Kas Kecil (Operasional)</option>
                            <option value="kas_besar">Kas Besar (Utama)</option>
                        </select>
                    </div>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                    <div>
                        <label class="form-label small text-muted">Nominal Transfer (Rp)</label>
                        <input type="number" name="amount" id="edit_amount" min="0.01" step="any" class="form-control form-control-sm form-control-dark" required>
                    </div>
                    <div>
                        <label class="form-label small text-muted">Tanggal Transfer</label>
                        <input type="date" name="transfer_date" id="edit_transfer_date" class="form-control form-control-sm form-control-dark" required>
                    </div>
                </div>
                <div>
                    <label class="form-label small text-muted">Deskripsi / Memo Transfer</label>
                    <textarea name="description" id="edit_description" rows="3" class="form-control form-control-sm form-control-dark"></textarea>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid var(--border);">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
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
