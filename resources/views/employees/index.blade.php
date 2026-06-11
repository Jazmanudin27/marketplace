@extends('layouts.app')
@section('title', 'Data Karyawan')
@section('page-title', 'Data Karyawan')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Daftar Karyawan</h5>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#employeeModal">
            <i class="fas fa-plus"></i> Tambah Karyawan
        </button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nama</th>
                        <th>Posisi</th>
                        <th>No. HP</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($employees as $emp)
                    <tr>
                        <td>
                            <strong>{{ $emp->name }}</strong><br>
                            <small class="text-muted">{{ $emp->email ?? '-' }}</small>
                        </td>
                        <td>{{ $emp->position ?? '-' }}</td>
                        <td>{{ $emp->phone ?? '-' }}</td>
                        <td>
                            @if($emp->is_active)
                                <span class="badge bg-success">Aktif</span>
                            @else
                                <span class="badge bg-danger">Non-Aktif</span>
                            @endif
                        </td>
                        <td>
                            <button class="btn btn-sm btn-info text-white" onclick="editEmployee({{ $emp->id }}, '{{ addslashes($emp->name) }}', '{{ addslashes($emp->email) }}', '{{ addslashes($emp->phone) }}', '{{ addslashes($emp->position) }}', '{{ addslashes($emp->address) }}', '{{ $emp->join_date ? $emp->join_date->format('Y-m-d') : '' }}', {{ $emp->is_active ? 'true' : 'false' }})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form action="{{ route('employees.destroy', $emp->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Hapus data karyawan ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Form -->
<div class="modal fade" id="employeeModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="employeeForm" method="POST" action="{{ route('employees.store') }}">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">
            <div class="modal-content" style="background:var(--bg-card); color:var(--text-primary); border-color:var(--border);">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title" id="modalTitle">Tambah Data Karyawan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="name" id="name" class="form-control" required style="background:var(--bg-app); color:var(--text-primary); border-color:var(--border);">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="email" class="form-control" style="background:var(--bg-app); color:var(--text-primary); border-color:var(--border);">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">No. Handphone</label>
                            <input type="text" name="phone" id="phone" class="form-control" style="background:var(--bg-app); color:var(--text-primary); border-color:var(--border);">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Posisi / Jabatan</label>
                            <input type="text" name="position" id="position" class="form-control" style="background:var(--bg-app); color:var(--text-primary); border-color:var(--border);">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tanggal Bergabung</label>
                            <input type="date" name="join_date" id="join_date" class="form-control" style="background:var(--bg-app); color:var(--text-primary); border-color:var(--border);">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alamat</label>
                        <textarea name="address" id="address" class="form-control" rows="2" style="background:var(--bg-app); color:var(--text-primary); border-color:var(--border);"></textarea>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1" checked>
                        <label class="form-check-label" for="is_active">Karyawan Aktif</label>
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function editEmployee(id, name, email, phone, position, address, join_date, is_active) {
        document.getElementById('modalTitle').innerText = 'Edit Data Karyawan';
        document.getElementById('formMethod').value = 'PUT';
        document.getElementById('employeeForm').action = '/employees/' + id;
        
        document.getElementById('name').value = name;
        document.getElementById('email').value = email;
        document.getElementById('phone').value = phone;
        document.getElementById('position').value = position;
        document.getElementById('address').value = address;
        document.getElementById('join_date').value = join_date;
        document.getElementById('is_active').checked = is_active;
        
        var modal = new bootstrap.Modal(document.getElementById('employeeModal'));
        modal.show();
    }
    
    document.getElementById('employeeModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('modalTitle').innerText = 'Tambah Data Karyawan';
        document.getElementById('formMethod').value = 'POST';
        document.getElementById('employeeForm').action = '{{ route('employees.store') }}';
        
        document.getElementById('name').value = '';
        document.getElementById('email').value = '';
        document.getElementById('phone').value = '';
        document.getElementById('position').value = '';
        document.getElementById('address').value = '';
        document.getElementById('join_date').value = '';
        document.getElementById('is_active').checked = true;
    });
</script>
@endpush
@endsection
