@extends('layouts.app')
@section('title', 'Lembur Karyawan')
@section('page-title', 'Lembur Karyawan')

@section('content')
    <div class="dashboard-card p-0 overflow-hidden">
        <div class="card-header-line d-flex justify-content-between align-items-center p-3 mb-0">
            <div class="d-flex align-items-center">
                <div class="bg-primary bg-opacity-10 text-primary rounded p-2 me-3 d-flex align-items-center justify-content-center"
                    style="width: 36px; height: 36px;">
                    <i class="fas fa-user-clock text-primary"></i>
                </div>
                <h5 class="mb-0 fw-bold text-white">Daftar Lembur Karyawan</h5>
            </div>
            <button type="button" class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#overtimeModal">
                <i class="fas fa-plus me-1"></i> Ajukan Lembur
            </button>
        </div>
        <div class="table-responsive p-3 pt-0">
            <table class="table table-bordered table-premium-dark align-middle mb-0">
                <thead>
                    <tr>
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
                                        $gradients = [
                                            'linear-gradient(135deg, #6C63FF, #8B5CF6)',
                                            'linear-gradient(135deg, #10B981, #059669)',
                                            'linear-gradient(135deg, #F59E0B, #D97706)',
                                            'linear-gradient(135deg, #EF4444, #DC2626)',
                                            'linear-gradient(135deg, #06B6D4, #0891B2)',
                                            'linear-gradient(135deg, #EC4899, #BE185D)'
                                        ];
                                        $grad = $gradients[$ot->employee->id % count($gradients)];
                                    @endphp
                                    <div class="avatar-circle me-3" style="background: {{ $grad }}; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 0.8rem; color: #fff;">
                                        {{ $initials }}
                                    </div>
                                    <div>
                                        <strong class="text-white d-block" style="font-size: 0.82rem;">{{ $ot->employee->name }}</strong>
                                        <span class="text-muted small">{{ $ot->employee->position ?? '-' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="text-muted">{{ $ot->date->format('d-m-Y') }}</td>
                            <td class="text-muted">{{ floatval($ot->hours) }} Jam</td>
                            <td class="text-muted">Rp {{ number_format($ot->employee->overtime_rate, 0, ',', '.') }}/jam</td>
                            <td>
                                <strong class="text-success">Rp {{ number_format($ot->hours * $ot->employee->overtime_rate, 0, ',', '.') }}</strong>
                            </td>
                            <td class="text-muted">{{ $ot->description ?? '-' }}</td>
                            <td class="text-center">
                                @if ($ot->status === 'approved')
                                    <span class="badge badge-success"><i class="fas fa-check-double me-1"></i> Disetujui</span>
                                @elseif($ot->status === 'rejected')
                                    <span class="badge badge-danger"><i class="fas fa-times me-1"></i> Ditolak</span>
                                @else
                                    <span class="badge badge-warning"><i class="fas fa-hourglass-half me-1"></i> Pending</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($ot->status === 'pending')
                                    <div class="d-inline-flex gap-1 align-items-center">
                                        <form action="{{ route('hr.overtime.approve', $ot->id) }}" method="POST"
                                            style="display:inline;">
                                            @csrf
                                            <button type="submit" class="btn btn-success btn-action-sm" title="Setujui"><i
                                                    class="fas fa-check"></i></button>
                                        </form>
                                        <form action="{{ route('hr.overtime.reject', $ot->id) }}" method="POST"
                                            style="display:inline;">
                                            @csrf
                                            <button type="submit" class="btn btn-danger btn-action-sm" title="Tolak"><i
                                                    class="fas fa-times"></i></button>
                                        </form>
                                        <button class="btn btn-warning btn-action-sm" title="Edit"
                                            onclick="editOvertime({{ $ot->id }}, {{ $ot->employee_id }}, '{{ $ot->date->format('Y-m-d') }}', {{ $ot->hours }}, '{{ addslashes($ot->description) }}')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form action="{{ route('hr.overtime.destroy', $ot->id) }}" method="POST"
                                            style="display:inline;"
                                            onsubmit="return confirm('Hapus pengajuan lembur ini?');">
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
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="far fa-clock fa-2x mb-3 d-block text-secondary opacity-25"></i>
                                Belum ada data lembur karyawan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Form -->
    <div class="modal fade" id="overtimeModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form id="overtimeForm" method="POST" action="{{ route('hr.overtime.store') }}">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">
                <div class="modal-content">
                    <div class="modal-header border-bottom py-3">
                        <h5 class="modal-title fw-bold text-white fs-6" id="modalTitle">
                            <i class="fas fa-business-time me-2 text-primary"></i>Form Pengajuan Lembur
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
                                        - Rp {{ number_format($emp->overtime_rate, 0, ',', '.') }}/jam)</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-calendar-alt text-primary me-1"></i> Tanggal Lembur</label>
                                <input type="date" name="date" id="date" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-hourglass-half text-primary me-1"></i> Jumlah Jam</label>
                                <input type="number" name="hours" id="hours" class="form-control" step="0.5"
                                    min="0.5" max="24" required placeholder="Contoh: 2.5">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-pen-fancy text-primary me-1"></i> Keterangan / Deskripsi Pekerjaan</label>
                            <textarea name="description" id="description" class="form-control" rows="3"
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
