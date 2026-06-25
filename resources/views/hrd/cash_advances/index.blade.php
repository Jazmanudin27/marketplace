@extends('layouts.app')
@section('title', 'Kasbon Karyawan')
@section('page-title', 'Kasbon Karyawan')

@section('content')
    <div class="card border shadow-sm overflow-hidden">
        <div class="card-header bg-info bg-opacity-10 d-flex justify-content-between align-items-center p-3 border-bottom">
            <div class="d-flex align-items-center">
                <div class="bg-info text-white rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 fs-5 p-2 me-3"
                    style="width: 36px; height: 36px;">
                    <i class="fas fa-hand-holding-usd text-white" style="font-size: 1rem;"></i>
                </div>
                <h6 class="mb-0 fw-bold text-dark">Daftar Kasbon Karyawan</h6>
            </div>
            <button type="button" class="btn btn-primary btn-sm px-3 rounded-3" data-bs-toggle="modal" data-bs-target="#cashAdvanceModal">
                <i class="fas fa-plus me-1"></i> Tambah Kasbon
            </button>
        </div>
        
        <div class="card-body p-3">
            <div class="table-responsive rounded border">
                <table class="table table-sm table-striped table-bordered align-middle mb-0">
                    <thead>
                        <tr class="small">
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
                                            $colors = [
                                                '#6C63FF',
                                                '#10B981',
                                                '#F59E0B',
                                                '#EF4444',
                                                '#06B6D4',
                                                '#EC4899'
                                            ];
                                            $bg = $colors[$ca->employee->id % count($colors)];
                                        @endphp
                                        <div class="text-white rounded-circle d-flex align-items-center justify-content-center fw-bold me-3" 
                                            style="background: {{ $bg }}; width: 32px; height: 32px; font-size: 0.8rem;">
                                            {{ $initials }}
                                        </div>
                                        <div>
                                            <strong class="text-dark d-block small">{{ $ca->employee->name }}</strong>
                                            <span class="text-muted" style="font-size:0.7rem;">{{ $ca->employee->position ?? '-' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="text-dark small">{{ $ca->date->format('d-m-Y') }}</span></td>
                                <td>
                                    <strong class="text-danger small">Rp {{ number_format($ca->amount, 0, ',', '.') }}</strong>
                                </td>
                                <td><span class="text-muted small">{{ $ca->notes ?? '-' }}</span></td>
                                <td class="text-center">
                                    @if ($ca->status === 'approved')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:0.7rem; padding: 0.25em 0.5em;"><i class="fas fa-check-double me-1"></i> Disetujui</span>
                                    @elseif($ca->status === 'settled')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:0.7rem; padding: 0.25em 0.5em;"><i class="fas fa-coins me-1"></i> Lunas</span>
                                    @elseif($ca->status === 'rejected')
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size:0.7rem; padding: 0.25em 0.5em;"><i class="fas fa-times me-1"></i> Ditolak</span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size:0.7rem; padding: 0.25em 0.5em;"><i class="fas fa-hourglass-half me-1"></i> Pending</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if ($ca->status === 'pending')
                                        <div class="d-inline-flex gap-1 align-items-center">
                                            <form action="{{ route('hr.cash-advances.approve', $ca->id) }}" method="POST"
                                                style="display:inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-success btn-sm" title="Setujui"><i
                                                        class="fas fa-check"></i></button>
                                            </form>
                                            <form action="{{ route('hr.cash-advances.reject', $ca->id) }}" method="POST"
                                                style="display:inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-danger btn-sm" title="Tolak"><i
                                                        class="fas fa-times"></i></button>
                                            </form>
                                            <button class="btn btn-warning btn-sm" title="Edit"
                                                onclick="editCashAdvance({{ $ca->id }}, {{ $ca->employee_id }}, '{{ $ca->date->format('Y-m-d') }}', {{ $ca->amount }}, '{{ addslashes($ca->notes) }}')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form action="{{ route('hr.cash-advances.destroy', $ca->id) }}" method="POST"
                                                style="display:inline;" onsubmit="return confirm('Hapus kasbon ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm" title="Hapus"><i
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
                                    <p class="mb-0 small">Belum ada data kasbon karyawan.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Form -->
    <div class="modal fade" id="cashAdvanceModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form id="cashAdvanceForm" method="POST" action="{{ route('hr.cash-advances.store') }}">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">
                <div class="modal-content">
                    <div class="modal-header bg-primary bg-opacity-10 border-bottom py-3">
                        <h5 class="modal-title fw-bold text-dark fs-6" id="modalTitle">
                            <i class="fas fa-hand-holding-usd me-2 text-primary"></i>Form Pengajuan Kasbon
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-dark"><i class="fas fa-user-tie text-primary me-1"></i> Karyawan</label>
                            <select name="employee_id" id="employee_id" class="form-select form-select-sm" required>
                                <option value="">Pilih Karyawan...</option>
                                @foreach ($employees as $emp)
                                    <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->position ?? '-' }}
                                        - Gaji: Rp {{ number_format($emp->basic_salary, 0, ',', '.') }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small text-dark"><i class="fas fa-calendar-alt text-primary me-1"></i> Tanggal Kasbon</label>
                                <input type="date" name="date" id="date" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small text-dark"><i class="fas fa-coins text-primary me-1"></i> Jumlah Nominal (Rp)</label>
                                <input type="number" name="amount" id="amount" class="form-control form-control-sm" min="1000"
                                    step="1000" required placeholder="Contoh: 500000">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-dark"><i class="fas fa-pen-fancy text-primary me-1"></i> Keterangan / Catatan</label>
                            <textarea name="notes" id="notes" class="form-control form-control-sm" rows="3"
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
