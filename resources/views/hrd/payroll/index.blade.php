@extends('layouts.app')
@section('title', 'Manajemen Penggajian (Payroll)')
@section('page-title', 'Manajemen Penggajian (Payroll)')

@section('content')
    <!-- Filter Periode & Generate Gaji -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="dashboard-card p-0 overflow-hidden h-100">
                <div class="card-header-line d-flex align-items-center p-3 mb-0 bg-primary bg-opacity-10">
                    <div class="bg-primary text-white rounded p-2 me-3 d-flex align-items-center justify-content-center"
                        style="width: 32px; height: 32px;">
                        <i class="far fa-calendar-alt text-white"></i>
                    </div>
                    <h6 class="mb-0 fw-bold text-white">Pilih Periode Penggajian</h6>
                </div>
                <div class="p-4">
                    <form method="GET" action="{{ route('hr.payroll.index') }}" id="periodFilterForm" class="w-100">
                        <div class="input-group input-group-sm">
                            <input type="month" name="period" class="form-control" value="{{ $period }}">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i> Tampilkan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="dashboard-card p-0 overflow-hidden h-100">
                <div class="card-header-line d-flex align-items-center p-3 mb-0 bg-success bg-opacity-10">
                    <div class="bg-success text-white rounded p-2 me-3 d-flex align-items-center justify-content-center"
                        style="width: 32px; height: 32px;">
                        <i class="fas fa-sync-alt text-white"></i>
                    </div>
                    <h6 class="mb-0 fw-bold text-white">Sinkronisasi Gaji Periode Ini</h6>
                </div>
                <div class="p-4">
                    <form method="POST" action="{{ route('hr.payroll.generate') }}" class="w-100">
                        @csrf
                        <input type="hidden" name="period" value="{{ $period }}">
                        <button type="submit" class="btn btn-success w-100 btn-sm py-2 fw-semibold">
                            <i class="fas fa-sync-alt me-1"></i> Hitung Ulang & Sinkronkan Gaji (Periode:
                            {{ date('F Y', strtotime($period . '-01')) }})
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistik Gaji Bulan Terpilih -->
    @php
        $totalBasic = $payrolls->sum('basic_salary');
        $totalAllowance = $payrolls->sum('allowance');
        $totalOvertime = $payrolls->sum('overtime_pay');
        $totalDeductions = $payrolls->sum('cash_advance_deduction');
        $totalNet = $payrolls->sum('net_salary');

        $paidCount = $payrolls->where('status', 'paid')->count();
        $draftCount = $payrolls->where('status', 'draft')->count();
    @endphp
    <div class="row g-3 mb-4">
        <!-- Stat Card 1 -->
        <div class="col-xl-3 col-md-6">
            <div class="dashboard-card h-100 d-flex align-items-center gap-3 py-3">
                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                    style="width: 48px; height: 48px; font-size: 1.25rem;">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="min-width-0">
                    <div class="fw-bold fs-5 text-white">Rp {{ number_format($totalNet, 0, ',', '.') }}</div>
                    <div class="text-muted small">Total Gaji Bersih</div>
                    <small class="text-muted d-block mt-1">{{ $payrolls->count() }} Karyawan</small>
                </div>
            </div>
        </div>

        <!-- Stat Card 2 -->
        <div class="col-xl-3 col-md-6">
            <div class="dashboard-card h-100 d-flex align-items-center gap-3 py-3">
                <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                    style="width: 48px; height: 48px; font-size: 1.25rem;">
                    <i class="fas fa-user-clock"></i>
                </div>
                <div class="min-width-0">
                    <div class="fw-bold fs-5 text-white">Rp {{ number_format($totalOvertime, 0, ',', '.') }}</div>
                    <div class="text-muted small">Total Lembur</div>
                    <small class="text-muted d-block mt-1">Akumulasi Lembur</small>
                </div>
            </div>
        </div>

        <!-- Stat Card 3 -->
        <div class="col-xl-3 col-md-6">
            <div class="dashboard-card h-100 d-flex align-items-center gap-3 py-3">
                <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                    style="width: 48px; height: 48px; font-size: 1.25rem;">
                    <i class="fas fa-minus-circle"></i>
                </div>
                <div class="min-width-0">
                    <div class="fw-bold fs-5 text-white">Rp {{ number_format($totalDeductions, 0, ',', '.') }}</div>
                    <div class="text-muted small">Total Potongan</div>
                    <small class="text-muted d-block mt-1">Potongan Kasbon</small>
                </div>
            </div>
        </div>

        <!-- Stat Card 4 -->
        <div class="col-xl-3 col-md-6">
            <div class="dashboard-card h-100 d-flex align-items-center gap-3 py-3">
                <div class="bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                    style="width: 48px; height: 48px; font-size: 1.25rem;">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="min-width-0">
                    <div class="fw-bold fs-5 text-white">{{ $paidCount }} / {{ $draftCount }}</div>
                    <div class="text-muted small">Status Slip</div>
                    <small class="text-muted d-block mt-1">Dibayar / Draft</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Daftar Gaji -->
    <div class="dashboard-card p-0 overflow-hidden">
        <div class="card-header-line d-flex align-items-center p-3 mb-0 bg-primary bg-opacity-10">
            <div class="bg-primary text-white rounded p-2 me-3 d-flex align-items-center justify-content-center"
                style="width: 36px; height: 36px;">
                <i class="fas fa-file-invoice-dollar text-white"></i>
            </div>
            <h5 class="mb-0 fw-bold text-white">Daftar Slip Gaji Periode: {{ date('F Y', strtotime($period . '-01')) }}</h5>
        </div>
        <div class="table-responsive p-3 pt-0">
            <table class="table table-bordered table-premium-dark align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 25%;">Karyawan</th>
                        <th style="width: 12%;">Gaji Pokok</th>
                        <th style="width: 12%;">Tunjangan</th>
                        <th style="width: 12%;">Lembur (+)</th>
                        <th style="width: 12%;">Kasbon (-)</th>
                        <th style="width: 15%;">Gaji Bersih (Net)</th>
                        <th style="width: 8%;" class="text-center">Status</th>
                        <th style="width: 14%;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payrolls as $pr)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    @php
                                        $words = explode(' ', $pr->employee->name);
                                        $initials = '';
                                        if (count($words) >= 2) {
                                            $initials = strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
                                        } else {
                                            $initials = strtoupper(substr($pr->employee->name, 0, 2));
                                        }
                                        $gradients = [
                                            'linear-gradient(135deg, #6C63FF, #8B5CF6)',
                                            'linear-gradient(135deg, #10B981, #059669)',
                                            'linear-gradient(135deg, #F59E0B, #D97706)',
                                            'linear-gradient(135deg, #EF4444, #DC2626)',
                                            'linear-gradient(135deg, #06B6D4, #0891B2)',
                                            'linear-gradient(135deg, #EC4899, #BE185D)'
                                        ];
                                        $grad = $gradients[$pr->employee->id % count($gradients)];
                                    @endphp
                                    <div class="avatar-circle me-3" style="background: {{ $grad }}; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 0.8rem; color: #fff;">
                                        {{ $initials }}
                                    </div>
                                    <div>
                                        <strong class="text-white d-block" style="font-size: 0.82rem;">{{ $pr->employee->name }}</strong>
                                        <span class="text-muted small">{{ $pr->employee->position ?? '-' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="text-muted">Rp {{ number_format($pr->basic_salary, 0, ',', '.') }}</td>
                            <td class="text-muted">Rp {{ number_format($pr->allowance, 0, ',', '.') }}</td>
                            <td class="text-success">+Rp {{ number_format($pr->overtime_pay, 0, ',', '.') }}</td>
                            <td class="text-danger">-Rp {{ number_format($pr->cash_advance_deduction, 0, ',', '.') }}</td>
                            <td>
                                <strong class="text-white">Rp {{ number_format($pr->net_salary, 0, ',', '.') }}</strong>
                                @if ($pr->salary_adjustment_addition > 0)
                                    <small class="text-success d-block" style="font-size: 0.72rem;">+Rp {{ number_format($pr->salary_adjustment_addition, 0, ',', '.') }} Adj</small>
                                @endif
                                @if ($pr->salary_adjustment_deduction > 0)
                                    <small class="text-danger d-block" style="font-size: 0.72rem;">-Rp {{ number_format($pr->salary_adjustment_deduction, 0, ',', '.') }} Adj</small>
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($pr->status === 'paid')
                                    <span class="badge badge-success"><i class="fas fa-check-double me-1"></i> Dibayar</span>
                                @else
                                    <span class="badge badge-warning"><i class="fas fa-edit me-1"></i> Draft</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="d-inline-flex gap-1 align-items-center">
                                    <a href="{{ route('hr.payroll.show', $pr->id) }}"
                                        class="btn btn-info text-white btn-sm px-2 py-1" title="Lihat & Cetak Slip Gaji">
                                        <i class="fas fa-file-invoice me-1"></i> Slip
                                    </a>
                                    @if ($pr->status === 'draft')
                                        <form action="{{ route('hr.payroll.pay', $pr->id) }}" method="POST"
                                            style="display:inline;"
                                            onsubmit="return confirm('Bayar gaji ini dan potong kasbon terkait?');">
                                            @csrf
                                            <button type="submit" class="btn btn-success btn-sm px-2 py-1" title="Bayar"><i
                                                    class="fas fa-coins me-1"></i> Bayar</button>
                                        </form>
                                        <form action="{{ route('hr.payroll.destroy', $pr->id) }}" method="POST"
                                            style="display:inline;"
                                            onsubmit="return confirm('Hapus slip gaji draft ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-action-sm" title="Hapus"><i
                                                    class="fas fa-trash"></i></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="far fa-folder-open fa-2x mb-3 d-block text-secondary opacity-25"></i>
                                Belum ada slip gaji yang digenerate untuk periode ini. Klik tombol di kanan atas untuk generate.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
