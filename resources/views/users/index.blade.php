@extends('layouts.app')
@section('title', 'Pengguna Sistem')
@section('page-title', 'Pengguna Sistem')

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Daftar Pengguna Sistem</h5>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-request="add"
                data-bs-target="#userModal">
                <i class="fas fa-plus"></i> Tambah Pengguna
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Role / Posisi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $u)
                            <tr>
                                <td>{{ $u->name }}</td>
                                <td>{{ $u->email }}</td>
                                <td>
                                    @if ($u->role === 'admin')
                                        <span class="badge bg-primary">Admin (Owner)</span>
                                    @elseif($u->role === 'warehouse')
                                        <span class="badge bg-warning text-dark">Staf Gudang</span>
                                    @elseif($u->role === 'finance')
                                        <span class="badge bg-success">Staf Keuangan</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $u->role }}</span>
                                    @endif
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info text-white"
                                        onclick="editUser({{ $u->id }}, '{{ addslashes($u->name) }}', '{{ addslashes($u->email) }}', '{{ $u->role }}')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    @if ($u->id !== Auth::id())
                                        <form action="{{ route('users.destroy', $u->id) }}" method="POST"
                                            style="display:inline;" onsubmit="return confirm('Hapus pengguna ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger"><i
                                                    class="fas fa-trash"></i></button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Form -->
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog">
            <form id="userForm" method="POST" action="{{ route('users.store') }}">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">
                <div class="modal-content"
                    style="background:var(--bg-card); color:var(--text-primary); border-color:var(--border);">
                    <div class="modal-header border-bottom-0">
                        <h5 class="modal-title" id="modalTitle">Tambah Pengguna</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama</label>
                            <input type="text" name="name" id="name" class="form-control" required
                                style="background:var(--bg-app); color:var(--text-primary); border-color:var(--border);">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="email" class="form-control" required
                                style="background:var(--bg-app); color:var(--text-primary); border-color:var(--border);">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password <small id="pwdHint" class="text-muted"></small></label>
                            <input type="password" name="password" id="password" class="form-control" required
                                style="background:var(--bg-app); color:var(--text-primary); border-color:var(--border);">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role / Posisi</label>
                            <select name="role" id="role" class="form-select form-control" required
                                style="background:var(--bg-app); color:var(--text-primary); border-color:var(--border);">
                                <option value="warehouse">Staf Gudang (Hanya Inventory & Pesanan)</option>
                                <option value="finance">Staf Keuangan (Hanya Laporan Keuangan)</option>
                                <option value="admin">Admin (Akses Penuh)</option>
                            </select>
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
            function editUser(id, name, email, role) {
                document.getElementById('modalTitle').innerText = 'Edit Pengguna';
                document.getElementById('formMethod').value = 'PUT';
                document.getElementById('userForm').action = '/users/' + id;

                document.getElementById('name').value = name;
                document.getElementById('email').value = email;
                document.getElementById('role').value = role;

                document.getElementById('password').required = false;
                document.getElementById('pwdHint').innerText = '(Kosongkan jika tidak ingin mengubah password)';

                var modal = new bootstrap.Modal(document.getElementById('userModal'));
                modal.show();
            }

            document.getElementById('userModal').addEventListener('hidden.bs.modal', function() {
                document.getElementById('modalTitle').innerText = 'Tambah Pengguna';
                document.getElementById('formMethod').value = 'POST';
                document.getElementById('userForm').action = '{{ route('users.store') }}';

                document.getElementById('name').value = '';
                document.getElementById('email').value = '';
                document.getElementById('role').value = 'warehouse';

                document.getElementById('password').required = true;
                document.getElementById('pwdHint').innerText = '';
            });
        </script>
    @endpush
@endsection
