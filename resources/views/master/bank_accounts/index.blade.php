@extends('layouts.app')
@section('title', 'Master Rekening Bank & Kas')
@section('page-title', 'Master Rekening Bank & Kas')

@section('content')
<div class="container-fluid p-0">

    {{-- Stats Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 bg-white">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary-subtle text-primary p-3 rounded-3 fs-4">
                            <i class="fas fa-university"></i>
                        </div>
                        <div>
                            <div class="text-muted small fw-semibold">Total Rekening Bank</div>
                            <div class="fs-4 fw-bold text-dark">{{ $totalAccounts }} <span class="fs-7 fw-normal text-muted">Akun</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 bg-white">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-success-subtle text-success p-3 rounded-3 fs-4">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div>
                            <div class="text-muted small fw-semibold">Rekening Aktif</div>
                            <div class="fs-4 fw-bold text-dark">{{ $activeAccounts }} <span class="fs-7 fw-normal text-muted">Akun</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 bg-white">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-info-subtle text-info p-3 rounded-3 fs-4">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div>
                            <div class="text-muted small fw-semibold">Total Saldo Terdaftar</div>
                            <div class="fs-4 fw-bold text-dark">Rp {{ number_format($totalBalance, 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Table & Actions Card --}}
    <div class="card border-0 shadow-sm rounded-3 bg-white">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-money-check-alt text-primary me-2"></i>Daftar Master Rekening Bank & Kas</h5>
            <button type="button" class="btn btn-primary btn-sm px-3 fw-bold rounded-3" data-bs-toggle="modal" data-bs-target="#modalAddBank">
                <i class="fas fa-plus me-1"></i> Tambah Rekening Bank
            </button>
        </div>
        <div class="card-body p-3">
            
            {{-- Filter Form --}}
            <form method="GET" action="{{ route('bank-accounts.index') }}" class="mb-3">
                <div class="row g-2">
                    <div class="col-md-6 col-lg-5">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-muted"><i class="fas fa-search"></i></span>
                            <input type="text" name="search" class="form-control" placeholder="Cari Nama Bank, No. Rekening, Atas Nama..." value="{{ $search }}">
                        </div>
                    </div>
                    <div class="col-md-3 col-lg-3">
                        <select name="status" class="form-select form-select-sm">
                            <option value="">Semua Status</option>
                            <option value="1" {{ $status === '1' ? 'selected' : '' }}>Aktif</option>
                            <option value="0" {{ $status === '0' ? 'selected' : '' }}>Non-Aktif</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-lg-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm px-3"><i class="fas fa-filter me-1"></i> Filter</button>
                        @if($search || $status !== null)
                            <a href="{{ route('bank-accounts.index') }}" class="btn btn-secondary btn-sm px-3"><i class="fas fa-undo me-1"></i> Reset</a>
                        @endif
                    </div>
                </div>
            </form>

            {{-- Alert --}}
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show small py-2 px-3 mb-3" role="alert">
                    <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            {{-- Table --}}
            <div class="table-responsive">
                <table class="table table-hover align-middle border mb-0">
                    <thead class="table-light">
                        <tr class="small text-muted text-uppercase">
                            <th style="width: 50px;" class="text-center">No</th>
                            <th>Nama Bank / Kas</th>
                            <th>No. Rekening</th>
                            <th>Atas Nama</th>
                            <th>Cabang</th>
                            <th class="text-end">Saldo Awal</th>
                            <th class="text-end">Saldo Saat Ini</th>
                            <th class="text-center">Status</th>
                            <th class="text-center" style="width: 130px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bankAccounts as $index => $bank)
                            <tr>
                                <td class="text-center small text-muted">{{ $bankAccounts->firstItem() + $index }}</td>
                                <td>
                                    <div class="fw-bold text-dark fs-6">{{ $bank->bank_name }}</div>
                                    @if($bank->notes)
                                        <small class="text-muted d-block" style="font-size: 11px;">{{ $bank->notes }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="font-monospace fw-bold text-primary">{{ $bank->account_number ?: '-' }}</span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $bank->account_name ?: '-' }}</div>
                                </td>
                                <td class="small text-muted">{{ $bank->branch_name ?: '-' }}</td>
                                <td class="text-end small">Rp {{ number_format($bank->initial_balance, 0, ',', '.') }}</td>
                                <td class="text-end fw-bold text-dark">Rp {{ number_format($bank->current_balance, 0, ',', '.') }}</td>
                                <td class="text-center">
                                    @if($bank->is_active)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                            <i class="fas fa-check-circle me-1"></i> Aktif
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">
                                            <i class="fas fa-times-circle me-1"></i> Non-Aktif
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditBank-{{ $bank->id }}" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form action="{{ route('bank-accounts.toggle', $bank->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn {{ $bank->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}" title="{{ $bank->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                                <i class="fas {{ $bank->is_active ? 'fa-ban' : 'fa-check' }}"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('bank-accounts.destroy', $bank->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus rekening bank ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>

                            {{-- Modal Edit Bank --}}
                            <div class="modal fade" id="modalEditBank-{{ $bank->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form action="{{ route('bank-accounts.update', $bank->id) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-header bg-light">
                                                <h5 class="modal-title fw-bold text-dark"><i class="fas fa-edit text-primary me-2"></i>Edit Rekening Bank</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold small text-dark">Nama Bank / Jenis Rekening <span class="text-danger">*</span></label>
                                                    <input type="text" name="bank_name" class="form-control" value="{{ $bank->bank_name }}" required placeholder="Contoh: BCA, MANDIRI, BRI, BNI, KAS TUNAI">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold small text-dark">Nomor Rekening</label>
                                                    <input type="text" name="account_number" class="form-control font-monospace" value="{{ $bank->account_number }}" placeholder="Contoh: 1234567890">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold small text-dark">Atas Nama Rekening</label>
                                                    <input type="text" name="account_name" class="form-control" value="{{ $bank->account_name }}" placeholder="Contoh: PT ASPARTECH ERP">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold small text-dark">Cabang Bank</label>
                                                    <input type="text" name="branch_name" class="form-control" value="{{ $bank->branch_name }}" placeholder="Contoh: KCP Sudirman Jakarta">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold small text-dark">Catatan Tambahan</label>
                                                    <textarea name="notes" class="form-control" rows="2" placeholder="Catatan internal">{{ $bank->notes }}</textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer bg-light">
                                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-primary btn-sm fw-bold"><i class="fas fa-save me-1"></i> Simpan Perubahan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted p-5">
                                    <i class="fas fa-university fs-1 opacity-25 mb-3 d-block"></i>
                                    Belum ada data Master Rekening Bank yang terdaftar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $bankAccounts->appends(request()->query())->links('pagination::bootstrap-5') }}
            </div>

        </div>
    </div>

</div>

{{-- Modal Add Bank --}}
<div class="modal fade" id="modalAddBank" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('bank-accounts.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark"><i class="fas fa-plus-circle text-primary me-2"></i>Tambah Rekening Bank / Kas Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark">Nama Bank / Jenis Kas <span class="text-danger">*</span></label>
                        <input type="text" name="bank_name" class="form-control" required placeholder="Contoh: BCA, MANDIRI, BRI, BNI, BSI, KAS TUNAI, OVO">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark">Nomor Rekening</label>
                        <input type="text" name="account_number" class="form-control font-monospace" placeholder="Contoh: 1234567890 (kosongkan jika Kas Tunai)">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark">Atas Nama Rekening</label>
                        <input type="text" name="account_name" class="form-control" placeholder="Contoh: PT ASPARTECH ERP">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark">Cabang Bank</label>
                        <input type="text" name="branch_name" class="form-control" placeholder="Contoh: KCP Sudirman Jakarta">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark">Saldo Awal (Rp)</label>
                        <input type="number" name="initial_balance" class="form-control" value="0" min="0" placeholder="Saldo awal saat pertama kali didaftarkan">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark">Catatan Tambahan</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Catatan internal peruntukan rekening ini"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-bold"><i class="fas fa-save me-1"></i> Simpan Rekening</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
