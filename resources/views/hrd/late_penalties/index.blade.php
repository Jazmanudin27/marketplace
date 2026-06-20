@extends('layouts.app')
@section('title', 'Denda Keterlambatan')
@section('page-title', 'Pengaturan Denda Keterlambatan')

@section('content')
    <div class="row">
        <!-- Form Aturan Denda Keterlambatan (Kiri) -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm" style="border-radius: 14px; background: var(--bg-card); border: 1px solid var(--border);">
                <div class="card-header border-bottom d-flex align-items-center py-3" style="border-color: var(--border) !important;">
                    <div class="brand-icon bg-primary-subtle text-primary me-3 p-2 rounded d-flex align-items-center justify-content-center"
                        style="width: 36px; height: 36px; background: rgba(108, 99, 255, 0.1) !important;">
                        <i class="fas fa-history text-primary"></i>
                    </div>
                    <h5 class="mb-0 fw-bold text-white" id="formTitle">Tambah Aturan Denda</h5>
                </div>
                <div class="card-body p-4">
                    @if ($errors->any())
                        <div class="alert alert-danger py-2" style="border-radius: 8px;">
                            <ul class="mb-0 ps-3">
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
                            <label class="form-label-custom"><i class="fas fa-hourglass-half text-primary"></i> Minimal Menit Keterlambatan</label>
                            <div class="input-group">
                                <input type="number" name="min_minutes" id="min_minutes" class="form-control form-control-custom"
                                    placeholder="Contoh: 5" min="1" required style="border-radius: 10px 0 0 10px !important;">
                                <span class="input-group-text" style="background:var(--bg-card); color:var(--text-secondary); border-color:var(--border); border-radius: 0 10px 10px 0 !important;">menit</span>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label-custom"><i class="fas fa-coins text-primary"></i> Nominal Denda</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background:var(--bg-card); color:var(--text-secondary); border-color:var(--border); border-radius: 10px 0 0 10px !important;">Rp</span>
                                <input type="number" name="penalty_amount" id="penalty_amount" class="form-control form-control-custom"
                                    placeholder="Contoh: 5000" min="0" required style="border-radius: 0 10px 10px 0 !important;">
                            </div>
                        </div>

                        <div class="d-flex gap-2 pt-2">
                            <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold" style="border-radius: 8px;">Simpan</button>
                            <button type="button" class="btn btn-secondary btn-sm px-3" id="btnCancel" style="display:none; border-radius: 8px;"
                                onclick="resetForm()">Batal</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Tabel Daftar Aturan Denda (Kanan) -->
        <div class="col-md-8">
            <div class="card shadow-sm" style="border-radius: 14px; background: var(--bg-card); border: 1px solid var(--border);">
                <div class="card-header border-bottom d-flex align-items-center py-3" style="border-color: var(--border) !important;">
                    <div class="brand-icon bg-success-subtle text-success me-3 p-2 rounded d-flex align-items-center justify-content-center"
                        style="width: 36px; height: 36px; background: rgba(16, 185, 129, 0.1) !important;">
                        <i class="fas fa-list text-success"></i>
                    </div>
                    <h5 class="mb-0 fw-bold text-white">Daftar Tingkatan Denda (Rules)</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-dark" style="background: var(--bg-card2);">
                                <tr>
                                    <th style="width: 40%; padding: 1rem 1.2rem;">Batas Keterlambatan</th>
                                    <th style="width: 40%; padding: 1rem 1.2rem;">Nominal Denda</th>
                                    <th style="width: 20%; padding: 1rem 1.2rem; text-align: center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rules as $rule)
                                    <tr style="border-bottom-color: var(--border);">
                                        <td style="padding: 1rem 1.2rem;">
                                            <div class="d-flex align-items-center">
                                                <div class="brand-icon bg-danger-subtle text-danger me-3 p-1 px-2 rounded" style="background: rgba(239, 68, 68, 0.1) !important;">
                                                    <i class="fas fa-clock text-danger"></i>
                                                </div>
                                                <span class="text-white fw-bold">&ge; {{ $rule->min_minutes }} menit</span>
                                            </div>
                                        </td>
                                        <td style="padding: 1rem 1.2rem;">
                                            <span class="text-success fw-bold">Rp {{ number_format($rule->penalty_amount, 0, ',', '.') }}</span>
                                        </td>
                                        <td style="padding: 1rem 1.2rem; text-align: center;">
                                            <div class="d-inline-flex gap-1 align-items-center">
                                                <button class="btn btn-action-custom btn-action-edit" title="Edit"
                                                    onclick="editLatePenalty({{ $rule->id }}, {{ $rule->min_minutes }}, {{ $rule->penalty_amount }})">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form action="{{ route('hr.late-penalties.destroy', $rule->id) }}" method="POST"
                                                    style="display:inline;" onsubmit="return confirm('Hapus aturan denda keterlambatan ini?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-action-custom btn-action-delete" title="Hapus"><i
                                                            class="fas fa-trash"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-5 text-muted">
                                            <div class="fs-4 mb-2"><i class="far fa-folder-open"></i></div>
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
