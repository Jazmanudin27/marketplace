@extends('layouts.app')
@section('title', 'Manajemen Penggajian (Payroll)')
@section('page-title', 'Manajemen Penggajian (Payroll)')

@section('content')
    <!-- Filter Periode & Generate Gaji -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card border shadow-sm overflow-hidden">
                <div class="card-header bg-primary bg-opacity-10 d-flex align-items-center gap-3 p-3 border-bottom">
                    <div class="bg-primary text-white rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                        style="width: 32px; height: 32px;">
                        <i class="far fa-calendar-alt" style="font-size: 0.9rem;"></i>
                    </div>
                    <h6 class="mb-0 fw-bold text-dark">Pilih Periode Penggajian</h6>
                </div>
                <div class="p-3">
                    <form method="GET" action="{{ route('hr.payroll.index') }}" id="periodFilterForm" class="w-100">
                        <div class="input-group input-group-sm">
                            <input type="month" name="period" class="form-control form-control-sm" value="{{ $period }}">
                            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search me-1"></i> Tampilkan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border shadow-sm overflow-hidden">
                <div class="card-header bg-success bg-opacity-10 d-flex align-items-center gap-3 p-3 border-bottom">
                    <div class="bg-success text-white rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                        style="width: 32px; height: 32px;">
                        <i class="fas fa-sync-alt" style="font-size: 0.9rem;"></i>
                    </div>
                    <h6 class="mb-0 fw-bold text-dark">Sinkronisasi Gaji Periode Ini</h6>
                </div>
                <div class="p-3">
                    <form method="POST" action="{{ route('hr.payroll.generate') }}" class="w-100">
                        @csrf
                        <input type="hidden" name="period" value="{{ $period }}">
                        <button type="submit" class="btn btn-success w-100 btn-sm rounded-3">
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
            <div class="card border shadow-sm h-100 d-flex flex-row align-items-center gap-3 py-2 px-3">
                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                    style="width: 40px; height: 40px; font-size: 1.1rem;">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="min-width-0">
                    <div class="fw-bold fs-5 text-dark">Rp {{ number_format($totalNet, 0, ',', '.') }}</div>
                    <div class="text-muted small">Total Gaji Bersih</div>
                    <small class="text-muted d-block mt-1" style="font-size:0.7rem;">{{ $payrolls->count() }} Karyawan</small>
                </div>
            </div>
        </div>

        <!-- Stat Card 2 -->
        <div class="col-xl-3 col-md-6">
            <div class="card border shadow-sm h-100 d-flex flex-row align-items-center gap-3 py-2 px-3">
                <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                    style="width: 40px; height: 40px; font-size: 1.1rem;">
                    <i class="fas fa-user-clock"></i>
                </div>
                <div class="min-width-0">
                    <div class="fw-bold fs-5 text-dark">Rp {{ number_format($totalOvertime, 0, ',', '.') }}</div>
                    <div class="text-muted small">Total Lembur</div>
                    <small class="text-muted d-block mt-1" style="font-size:0.7rem;">Akumulasi Lembur</small>
                </div>
            </div>
        </div>

        <!-- Stat Card 3 -->
        <div class="col-xl-3 col-md-6">
            <div class="card border shadow-sm h-100 d-flex flex-row align-items-center gap-3 py-2 px-3">
                <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                    style="width: 40px; height: 40px; font-size: 1.1rem;">
                    <i class="fas fa-minus-circle"></i>
                </div>
                <div class="min-width-0">
                    <div class="fw-bold fs-5 text-dark">Rp {{ number_format($totalDeductions, 0, ',', '.') }}</div>
                    <div class="text-muted small">Total Potongan</div>
                    <small class="text-muted d-block mt-1" style="font-size:0.7rem;">Potongan Kasbon</small>
                </div>
            </div>
        </div>

        <!-- Stat Card 4 -->
        <div class="col-xl-3 col-md-6">
            <div class="card border shadow-sm h-100 d-flex flex-row align-items-center gap-3 py-2 px-3">
                <div class="bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                    style="width: 40px; height: 40px; font-size: 1.1rem;">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="min-width-0">
                    <div class="fw-bold fs-5 text-dark">{{ $paidCount }} / {{ $draftCount }}</div>
                    <div class="text-muted small">Status Slip</div>
                    <small class="text-muted d-block mt-1" style="font-size:0.7rem;">Dibayar / Draft</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Daftar Gaji -->
    <div class="card border shadow-sm overflow-hidden">
        <div class="card-header bg-info bg-opacity-10 d-flex align-items-center gap-3 p-3 border-bottom">
            <div class="bg-info text-white rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 fs-5 p-2"
                style="width: 36px; height: 36px;">
                <i class="fas fa-file-invoice-dollar text-white" style="font-size: 1rem;"></i>
            </div>
            <h6 class="mb-0 fw-bold text-dark">Daftar Slip Gaji Periode: {{ date('F Y', strtotime($period . '-01')) }}</h6>
        </div>
        
        <div class="card-body p-3">
            <div class="table-responsive rounded border">
                <table class="table table-sm table-striped table-bordered align-middle mb-0">
                    <thead>
                        <tr class="small">
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
                                            $colors = [
                                                '#6C63FF',
                                                '#10B981',
                                                '#F59E0B',
                                                '#EF4444',
                                                '#06B6D4',
                                                '#EC4899'
                                            ];
                                            $bg = $colors[$pr->employee->id % count($colors)];
                                        @endphp
                                        <div class="text-white rounded-circle d-flex align-items-center justify-content-center fw-bold me-3" 
                                            style="background: {{ $bg }}; width: 32px; height: 32px; font-size: 0.8rem;">
                                            {{ $initials }}
                                        </div>
                                        <div>
                                            <strong class="text-dark d-block small">{{ $pr->employee->name }}</strong>
                                            <span class="text-muted" style="font-size:0.7rem;">{{ $pr->employee->position ?? '-' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="text-dark small">Rp {{ number_format($pr->basic_salary, 0, ',', '.') }}</span></td>
                                <td><span class="text-dark small">Rp {{ number_format($pr->allowance, 0, ',', '.') }}</span></td>
                                <td><span class="text-success small">+Rp {{ number_format($pr->overtime_pay, 0, ',', '.') }}</span></td>
                                <td><span class="text-danger small">-Rp {{ number_format($pr->cash_advance_deduction, 0, ',', '.') }}</span></td>
                                <td>
                                    <strong class="text-dark small">Rp {{ number_format($pr->net_salary, 0, ',', '.') }}</strong>
                                    @if ($pr->salary_adjustment_addition > 0)
                                        <small class="text-success d-block" style="font-size: 0.68rem;">+Rp {{ number_format($pr->salary_adjustment_addition, 0, ',', '.') }} Adj</small>
                                    @endif
                                    @if ($pr->salary_adjustment_deduction > 0)
                                        <small class="text-danger d-block" style="font-size: 0.68rem;">-Rp {{ number_format($pr->salary_adjustment_deduction, 0, ',', '.') }} Adj</small>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if ($pr->status === 'paid')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.7rem; padding: 0.25em 0.5em;"><i class="fas fa-check-double me-1"></i> Dibayar</span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size: 0.7rem; padding: 0.25em 0.5em;"><i class="fas fa-edit me-1"></i> Draft</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-inline-flex gap-1 align-items-center">
                                        <a href="{{ route('hr.payroll.show', $pr->id) }}"
                                            class="btn btn-info text-white btn-sm px-2" title="Lihat & Cetak Slip Gaji">
                                            <i class="fas fa-file-invoice me-1"></i> Slip
                                        </a>
                                        @if ($pr->status === 'draft')
                                            <form action="{{ route('hr.payroll.pay', $pr->id) }}" method="POST"
                                                style="display:inline;"
                                                onsubmit="return confirm('Bayar gaji ini dan potong kasbon terkait?');">
                                                @csrf
                                                <button type="submit" class="btn btn-success btn-sm px-2" title="Bayar"><i
                                                        class="fas fa-coins me-1"></i> Bayar</button>
                                            </form>
                                            <form action="{{ route('hr.payroll.destroy', $pr->id) }}" method="POST"
                                                style="display:inline;"
                                                onsubmit="return confirm('Hapus slip gaji draft ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm" title="Hapus"><i
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
                                    <p class="mb-0 small">Belum ada slip gaji yang digenerate untuk periode ini. Klik tombol di kanan atas untuk generate.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
