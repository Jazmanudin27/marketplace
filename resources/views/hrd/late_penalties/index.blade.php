@extends('layouts.app')
@section('title', 'Denda Keterlambatan')
@section('page-title', 'Pengaturan Denda Keterlambatan')

@section('content')
    <div class="row">
        <!-- Form Aturan Denda Keterlambatan (Kiri) -->
        <div class="col-md-4 mb-4">
            <div class="dashboard-card p-0 overflow-hidden">
                <div class="card-header-line d-flex align-items-center p-3 mb-0 bg-primary bg-opacity-10">
                    <div class="bg-primary text-white rounded p-2 me-3 d-flex align-items-center justify-content-center"
                        style="width: 36px; height: 36px;">
                        <i class="fas fa-history text-white"></i>
                    </div>
                    <h5 class="mb-0 fw-bold text-white" id="formTitle">Tambah Aturan Denda</h5>
                </div>
                <div class="p-4">
                    @if ($errors->any())
                        <div class="alert alert-danger py-2">
                            <ul class="mb-0 ps-3 small">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form id="latePenaltyForm" method="POST" action="{{ route('hr.late-penalties.store') }}">
                        @csrf
                        <input type="hidden" name="_method" id="formMethod" value="POST">

                        <div class="mb-4">
                            <label class="form-label"><i class="fas fa-hourglass-half text-primary me-1"></i> Minimal Menit Keterlambatan</label>
                            <div class="input-group input-group-sm">
                                <input type="number" name="min_minutes" id="min_minutes" class="form-control"
                                    placeholder="Contoh: 5" min="1" required>
                                <span class="input-group-text">menit</span>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label"><i class="fas fa-coins text-primary me-1"></i> Nominal Denda</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="penalty_amount" id="penalty_amount" class="form-control"
                                    placeholder="Contoh: 5000" min="0" required>
                            </div>
                        </div>

                        <div class="d-flex gap-2 pt-2">
                            <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold">Simpan</button>
                            <button type="button" class="btn btn-secondary btn-sm px-3" id="btnCancel" style="display:none;"
                                onclick="resetForm()">Batal</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Tabel Daftar Aturan Denda (Kanan) -->
        <div class="col-md-8">
            <div class="dashboard-card p-0 overflow-hidden">
                <div class="card-header-line d-flex align-items-center p-3 mb-0 bg-success bg-opacity-10">
                    <div class="bg-success text-white rounded p-2 me-3 d-flex align-items-center justify-content-center"
                        style="width: 36px; height: 36px;">
                        <i class="fas fa-list text-white"></i>
                    </div>
                    <h5 class="mb-0 fw-bold text-white">Daftar Tingkatan Denda (Rules)</h5>
                </div>
                <div class="table-responsive p-3 pt-0">
                    <table class="table table-bordered table-premium-dark align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 40%;">Batas Keterlambatan</th>
                                <th style="width: 40%;">Nominal Denda</th>
                                <th style="width: 20%;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rules as $rule)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-danger bg-opacity-10 text-danger rounded p-1 px-2 me-3">
                                                <i class="fas fa-clock text-danger"></i>
                                            </div>
                                            <span class="text-white fw-bold">&ge; {{ $rule->min_minutes }} menit</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-success fw-bold">Rp {{ number_format($rule->penalty_amount, 0, ',', '.') }}</span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-inline-flex gap-1 align-items-center">
                                            <button class="btn btn-warning btn-action-sm" title="Edit"
                                                onclick="editLatePenalty({{ $rule->id }}, {{ $rule->min_minutes }}, {{ $rule->penalty_amount }})">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form action="{{ route('hr.late-penalties.destroy', $rule->id) }}" method="POST"
                                                style="display:inline;" onsubmit="return confirm('Hapus aturan denda keterlambatan ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-action-sm" title="Hapus"><i
                                                        class="fas fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center py-5 text-muted">
                                        <i class="far fa-folder-open fa-2x mb-3 d-block text-secondary opacity-25"></i>
                                        Belum ada aturan denda keterlambatan yang terdaftar.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function editLatePenalty(id, minMinutes, penaltyAmount) {
                document.getElementById('formTitle').innerText = 'Edit Aturan Denda';
                document.getElementById('formMethod').value = 'PUT';
                document.getElementById('latePenaltyForm').action = '/hr/late-penalties/' + id;

                document.getElementById('min_minutes').value = minMinutes;
                document.getElementById('penalty_amount').value = penaltyAmount;
                document.getElementById('btnCancel').style.display = 'inline-block';
            }

            function resetForm() {
                document.getElementById('formTitle').innerText = 'Tambah Aturan Denda';
                document.getElementById('formMethod').value = 'POST';
                document.getElementById('latePenaltyForm').action = '{{ route('hr.late-penalties.store') }}';

                document.getElementById('min_minutes').value = '';
                document.getElementById('penalty_amount').value = '';
                document.getElementById('btnCancel').style.display = 'none';
            }
        </script>
    @endpush
@endsection
