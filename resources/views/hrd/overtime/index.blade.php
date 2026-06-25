@extends('layouts.app')
@section('title', 'Lembur Karyawan')
@section('page-title', 'Lembur Karyawan')

@section('content')
    <div class="card border shadow-sm overflow-hidden">
        <div class="card-header bg-info bg-opacity-10 d-flex justify-content-between align-items-center p-3 border-bottom">
            <div class="d-flex align-items-center">
                <div class="bg-info text-white rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 fs-5 p-2 me-3"
                    style="width: 36px; height: 36px;">
                    <i class="fas fa-user-clock text-white" style="font-size: 1rem;"></i>
                </div>
                <h6 class="mb-0 fw-bold text-dark">Daftar Lembur Karyawan</h6>
            </div>
            <button type="button" class="btn btn-primary btn-sm px-3 rounded-3" data-bs-toggle="modal" data-bs-target="#overtimeModal">
                <i class="fas fa-plus me-1"></i> Ajukan Lembur
            </button>
        </div>
        
        <div class="card-body p-3">
            <div class="table-responsive rounded border">
                <table class="table table-sm table-striped table-bordered align-middle mb-0">
                    <thead>
                        <tr class="small">
                            <th style="width: 25%;">Karyawan</th>
                            <th style="width: 12%;">Tanggal</th>
                            <th style="width: 10%;">Durasi</th>
                            <th style="width: 15%;">Tarif Lembur</th>
                            <th style="width: 15%;">Total Lembur</th>
                            <th style="width: 13%;">Keterangan</th>
                            <th style="width: 10%;" class="text-center">Status</th>
                            <th style="width: 10%;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($overtimes as $ot)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @php
                                            $words = explode(' ', $ot->employee->name);
                                            $initials = '';
                                            if (count($words) >= 2) {
                                                $initials = strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
                                            } else {
                                                $initials = strtoupper(substr($ot->employee->name, 0, 2));
                                            }
                                            $colors = [
                                                '#6C63FF',
                                                '#10B981',
                                                '#F59E0B',
                                                '#EF4444',
                                                '#06B6D4',
                                                '#EC4899'
                                            ];
                                            $bg = $colors[$ot->employee->id % count($colors)];
                                        @endphp
                                        <div class="text-white rounded-circle d-flex align-items-center justify-content-center fw-bold me-3" 
                                            style="background: {{ $bg }}; width: 32px; height: 32px; font-size: 0.8rem;">
                                            {{ $initials }}
                                        </div>
                                        <div>
                                            <strong class="text-dark d-block small">{{ $ot->employee->name }}</strong>
                                            <span class="text-muted" style="font-size:0.7rem;">{{ $ot->employee->position ?? '-' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="text-dark small">{{ $ot->date->format('d-m-Y') }}</span></td>
                                <td><span class="text-dark small">{{ floatval($ot->hours) }} Jam</span></td>
                                <td><span class="text-muted small">Rp {{ number_format($ot->employee->overtime_rate, 0, ',', '.') }}/jam</span></td>
                                <td>
                                    <strong class="text-success small">Rp {{ number_format($ot->hours * $ot->employee->overtime_rate, 0, ',', '.') }}</strong>
                                </td>
                                <td><span class="text-muted small">{{ $ot->description ?? '-' }}</span></td>
                                <td class="text-center">
                                    @if ($ot->status === 'approved')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:0.7rem; padding: 0.25em 0.5em;"><i class="fas fa-check-double me-1"></i> Disetujui</span>
                                    @elseif($ot->status === 'rejected')
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size:0.7rem; padding: 0.25em 0.5em;"><i class="fas fa-times me-1"></i> Ditolak</span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size:0.7rem; padding: 0.25em 0.5em;"><i class="fas fa-hourglass-half me-1"></i> Pending</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if ($ot->status === 'pending')
                                        <div class="d-inline-flex gap-1 align-items-center">
                                            <form action="{{ route('hr.overtime.approve', $ot->id) }}" method="POST"
                                                style="display:inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-success btn-sm" title="Setujui"><i
                                                        class="fas fa-check"></i></button>
                                            </form>
                                            <form action="{{ route('hr.overtime.reject', $ot->id) }}" method="POST"
                                                style="display:inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-danger btn-sm" title="Tolak"><i
                                                        class="fas fa-times"></i></button>
                                            </form>
                                            <button class="btn btn-warning btn-sm" title="Edit"
                                                onclick="editOvertime({{ $ot->id }}, {{ $ot->employee_id }}, '{{ $ot->date->format('Y-m-d') }}', {{ $ot->hours }}, '{{ addslashes($ot->description) }}')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form action="{{ route('hr.overtime.destroy', $ot->id) }}" method="POST"
                                                style="display:inline;"
                                                onsubmit="return confirm('Hapus pengajuan lembur ini?');">
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
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="far fa-clock fa-2x mb-3 d-block text-secondary opacity-25"></i>
                                    <p class="mb-0 small">Belum ada data lembur karyawan.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Form -->
    <div class="modal fade" id="overtimeModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form id="overtimeForm" method="POST" action="{{ route('hr.overtime.store') }}">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">
                <div class="modal-content">
                    <div class="modal-header bg-primary bg-opacity-10 border-bottom py-3">
                        <h5 class="modal-title fw-bold text-dark fs-6" id="modalTitle">
                            <i class="fas fa-business-time me-2 text-primary"></i>Form Pengajuan Lembur
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
                                        - Rp {{ number_format($emp->overtime_rate, 0, ',', '.') }}/jam)</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small text-dark"><i class="fas fa-calendar-alt text-primary me-1"></i> Tanggal Lembur</label>
                                <input type="date" name="date" id="date" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small text-dark"><i class="fas fa-hourglass-half text-primary me-1"></i> Jumlah Jam</label>
                                <input type="number" name="hours" id="hours" class="form-control form-control-sm" step="0.5"
                                    min="0.5" max="24" required placeholder="Contoh: 2.5">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-dark"><i class="fas fa-pen-fancy text-primary me-1"></i> Keterangan / Deskripsi Pekerjaan</label>
                            <textarea name="description" id="description" class="form-control form-control-sm" rows="3"
                                placeholder="Pekerjaan lembur untuk packing shopee / stock-take"></textarea>
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
            function editOvertime(id, employee_id, date, hours, description) {
                document.getElementById('modalTitle').innerText = 'Edit Data Lembur';
                document.getElementById('formMethod').value = 'PUT';
                document.getElementById('overtimeForm').action = '/hr/overtime/' + id;

                document.getElementById('employee_id').value = employee_id;
                document.getElementById('date').value = date;
                document.getElementById('hours').value = hours;
                document.getElementById('description').value = description;

                var modal = new bootstrap.Modal(document.getElementById('overtimeModal'));
                modal.show();
            }

            document.getElementById('overtimeModal').addEventListener('hidden.bs.modal', function() {
                document.getElementById('modalTitle').innerText = 'Form Pengajuan Lembur';
                document.getElementById('formMethod').value = 'POST';
                document.getElementById('overtimeForm').action = '{{ route('hr.overtime.store') }}';

                document.getElementById('employee_id').value = '';
                document.getElementById('date').value = '';
                document.getElementById('hours').value = '';
                document.getElementById('description').value = '';
            });
        </script>
    @endpush
@endsection
