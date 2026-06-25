@extends('layouts.app')
@section('title', 'Kasbon Karyawan')
@section('page-title', 'Kasbon Karyawan')

@section('content')
    <div class="dashboard-card p-0 overflow-hidden">
        <div class="card-header-line d-flex justify-content-between align-items-center p-3 mb-0">
            <div class="d-flex align-items-center">
                <div class="bg-success bg-opacity-10 text-success rounded p-2 me-3 d-flex align-items-center justify-content-center"
                    style="width: 36px; height: 36px;">
                    <i class="fas fa-hand-holding-usd text-success"></i>
                </div>
                <h5 class="mb-0 fw-bold text-white">Daftar Kasbon Karyawan</h5>
            </div>
            <button type="button" class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#cashAdvanceModal">
                <i class="fas fa-plus me-1"></i> Tambah Kasbon
            </button>
        </div>
        <div class="table-responsive p-3 pt-0">
            <table class="table table-bordered table-premium-dark align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 25%;">Karyawan</th>
                        <th style="width: 15%;">Tanggal</th>
                        <th style="width: 15%;">Jumlah Kasbon</th>
                        <th style="width: 25%;">Catatan / Keterangan</th>
                        <th style="width: 10%;" class="text-center">Status</th>
                        <th style="width: 10%;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cashAdvances as $ca)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    @php
                                        $words = explode(' ', $ca->employee->name);
                                        $initials = '';
                                        if (count($words) >= 2) {
                                            $initials = strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
                                        } else {
                                            $initials = strtoupper(substr($ca->employee->name, 0, 2));
                                        }
                                        $gradients = [
                                            'linear-gradient(135deg, #6C63FF, #8B5CF6)',
                                            'linear-gradient(135deg, #10B981, #059669)',
                                            'linear-gradient(135deg, #F59E0B, #D97706)',
                                            'linear-gradient(135deg, #EF4444, #DC2626)',
                                            'linear-gradient(135deg, #06B6D4, #0891B2)',
                                            'linear-gradient(135deg, #EC4899, #BE185D)'
                                        ];
                                        $grad = $gradients[$ca->employee->id % count($gradients)];
                                    @endphp
                                    <div class="avatar-circle me-3" style="background: {{ $grad }}; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 0.8rem; color: #fff;">
                                        {{ $initials }}
                                    </div>
                                    <div>
                                        <strong class="text-white d-block" style="font-size: 0.82rem;">{{ $ca->employee->name }}</strong>
                                        <span class="text-muted small">{{ $ca->employee->position ?? '-' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="text-muted">{{ $ca->date->format('d-m-Y') }}</td>
                            <td>
                                <strong class="text-danger">Rp {{ number_format($ca->amount, 0, ',', '.') }}</strong>
                            </td>
                            <td class="text-muted">{{ $ca->notes ?? '-' }}</td>
                            <td class="text-center">
                                @if ($ca->status === 'approved')
                                    <span class="badge badge-success"><i class="fas fa-check-double me-1"></i> Disetujui</span>
                                @elseif($ca->status === 'settled')
                                    <span class="badge badge-success"><i class="fas fa-coins me-1"></i> Lunas</span>
                                @elseif($ca->status === 'rejected')
                                    <span class="badge badge-danger"><i class="fas fa-times me-1"></i> Ditolak</span>
                                @else
                                    <span class="badge badge-warning"><i class="fas fa-hourglass-half me-1"></i> Pending</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($ca->status === 'pending')
                                    <div class="d-inline-flex gap-1 align-items-center">
                                        <form action="{{ route('hr.cash-advances.approve', $ca->id) }}" method="POST"
                                            style="display:inline;">
                                            @csrf
                                            <button type="submit" class="btn btn-success btn-action-sm" title="Setujui"><i
                                                    class="fas fa-check"></i></button>
                                        </form>
                                        <form action="{{ route('hr.cash-advances.reject', $ca->id) }}" method="POST"
                                            style="display:inline;">
                                            @csrf
                                            <button type="submit" class="btn btn-danger btn-action-sm" title="Tolak"><i
                                                    class="fas fa-times"></i></button>
                                        </form>
                                        <button class="btn btn-warning btn-action-sm" title="Edit"
                                            onclick="editCashAdvance({{ $ca->id }}, {{ $ca->employee_id }}, '{{ $ca->date->format('Y-m-d') }}', {{ $ca->amount }}, '{{ addslashes($ca->notes) }}')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form action="{{ route('hr.cash-advances.destroy', $ca->id) }}" method="POST"
                                            style="display:inline;" onsubmit="return confirm('Hapus kasbon ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-action-sm" title="Hapus"><i
                                                    class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="far fa-folder-open fa-2x mb-3 d-block text-secondary opacity-25"></i>
                                Belum ada data kasbon karyawan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Form -->
    <div class="modal fade" id="cashAdvanceModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form id="cashAdvanceForm" method="POST" action="{{ route('hr.cash-advances.store') }}">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">
                <div class="modal-content">
                    <div class="modal-header border-bottom py-3">
                        <h5 class="modal-title fw-bold text-white fs-6" id="modalTitle">
                            <i class="fas fa-hand-holding-usd me-2 text-primary"></i>Form Pengajuan Kasbon
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-user-tie text-primary me-1"></i> Karyawan</label>
                            <select name="employee_id" id="employee_id" class="form-select" required>
                                <option value="">Pilih Karyawan...</option>
                                @foreach ($employees as $emp)
                                    <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->position ?? '-' }}
                                        - Gaji: Rp {{ number_format($emp->basic_salary, 0, ',', '.') }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-calendar-alt text-primary me-1"></i> Tanggal Kasbon</label>
                                <input type="date" name="date" id="date" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-coins text-primary me-1"></i> Jumlah Nominal (Rp)</label>
                                <input type="number" name="amount" id="amount" class="form-control" min="1000"
                                    step="1000" required placeholder="Contoh: 500000">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-pen-fancy text-primary me-1"></i> Keterangan / Catatan</label>
                            <textarea name="notes" id="notes" class="form-control" rows="3"
                                placeholder="Kasbon untuk keperluan mendesak keluarga"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top py-3">
                        <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold">
                            <i class="fas fa-save me-1"></i> Simpan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            function editCashAdvance(id, employee_id, date, amount, notes) {
                document.getElementById('modalTitle').innerText = 'Edit Data Kasbon';
                document.getElementById('formMethod').value = 'PUT';
                document.getElementById('cashAdvanceForm').action = '/hr/cash-advances/' + id;

                document.getElementById('employee_id').value = employee_id;
                document.getElementById('date').value = date;
                document.getElementById('amount').value = amount;
                document.getElementById('notes').value = notes;

                var modal = new bootstrap.Modal(document.getElementById('cashAdvanceModal'));
                modal.show();
            }

            document.getElementById('cashAdvanceModal').addEventListener('hidden.bs.modal', function() {
                document.getElementById('modalTitle').innerText = 'Form Pengajuan Kasbon';
                document.getElementById('formMethod').value = 'POST';
                document.getElementById('cashAdvanceForm').action = '{{ route('hr.cash-advances.store') }}';

                document.getElementById('employee_id').value = '';
                document.getElementById('date').value = '';
                document.getElementById('amount').value = '';
                document.getElementById('notes').value = '';
            });
        </script>
    @endpush
@endsection
