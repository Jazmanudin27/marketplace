@extends('layouts.app')
@section('title', 'Manajemen Penggajian (Payroll)')
@section('page-title', 'Manajemen Penggajian (Payroll)')

@section('content')
    <!-- Filter Periode & Generate Gaji -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card h-100" style="border-radius: 14px; background: var(--bg-card); border: 1px solid var(--border);">
                <div class="card-header py-3 border-bottom d-flex align-items-center" style="border-color: var(--border) !important;">
                    <div class="brand-icon bg-primary-subtle text-primary me-3 p-2 rounded d-flex align-items-center justify-content-center"
                        style="width: 32px; height: 32px; background: rgba(108, 99, 255, 0.1) !important;">
                        <i class="far fa-calendar-alt text-primary"></i>
                    </div>
                    <h6 class="mb-0 fw-bold text-white">Pilih Periode Penggajian</h6>
                </div>
                <div class="card-body d-flex align-items-center p-4">
                    <form method="GET" action="{{ route('hr.payroll.index') }}" id="periodFilterForm" class="w-100">
                        <div class="input-group">
                            <input type="month" name="period" class="form-control form-control-custom" value="{{ $period }}"
                                onchange="document.getElementById('periodFilterForm').submit()" style="border-radius: 10px 0 0 10px !important;">
                            <button type="submit" class="btn btn-primary btn-sm px-3" style="border-radius: 0 10px 10px 0 !important;"><i class="fas fa-search"></i>
                                Tampilkan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100" style="border-radius: 14px; background: var(--bg-card); border: 1px solid var(--border);">
                <div class="card-header py-3 border-bottom d-flex align-items-center" style="border-color: var(--border) !important;">
                    <div class="brand-icon bg-success-subtle text-success me-3 p-2 rounded d-flex align-items-center justify-content-center"
                        style="width: 32px; height: 32px; background: rgba(16, 185, 129, 0.1) !important;">
                        <i class="fas fa-sync-alt text-success"></i>
                    </div>
                    <h6 class="mb-0 fw-bold text-white">Sinkronisasi Gaji Periode Ini</h6>
                </div>
                <div class="card-body d-flex align-items-center p-4">
                    <form method="POST" action="{{ route('hr.payroll.generate') }}" class="w-100">
                        @csrf
                        <input type="hidden" name="period" value="{{ $period }}">
                        <button type="submit" class="btn btn-success w-100 btn-sm py-2 fw-semibold" style="border-radius: 10px;">
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
    <div class="row mb-4">
        <!-- Stat Card 1 -->
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stat-card-custom h-100" style="border-left: 4px solid var(--primary);">
                <div>
                    <span class="text-white-50 small fw-semibold d-block mb-1">Total Gaji Bersih</span>
                    <h4 class="fw-bold mb-0 text-white">Rp {{ number_format($totalNet, 0, ',', '.') }}</h4>
                    <small class="text-muted d-block mt-1">{{ $payrolls->count() }} Karyawan</small>
                </div>
                <div class="stat-icon-wrapper" style="background: rgba(108, 99, 255, 0.1); color: var(--primary);">
                    <i class="fas fa-wallet"></i>
                </div>
            </div>
        </div>

        <!-- Stat Card 2 -->
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stat-card-custom h-100" style="border-left: 4px solid var(--success);">
                <div>
                    <span class="text-white-50 small fw-semibold d-block mb-1">Total Lembur</span>
                    <h4 class="fw-bold mb-0 text-white">Rp {{ number_format($totalOvertime, 0, ',', '.') }}</h4>
                    <small class="text-muted d-block mt-1">Akumulasi Lembur</small>
                </div>
                <div class="stat-icon-wrapper" style="background: rgba(16, 185, 129, 0.1); color: var(--success);">
                    <i class="fas fa-user-clock"></i>
                </div>
            </div>
        </div>

        <!-- Stat Card 3 -->
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stat-card-custom h-100" style="border-left: 4px solid var(--warning);">
                <div>
                    <span class="text-white-50 small fw-semibold d-block mb-1">Total Potongan</span>
                    <h4 class="fw-bold mb-0 text-white">Rp {{ number_format($totalDeductions, 0, ',', '.') }}</h4>
                    <small class="text-muted d-block mt-1">Potongan Kasbon</small>
                </div>
                <div class="stat-icon-wrapper" style="background: rgba(245, 158, 11, 0.1); color: var(--warning);">
                    <i class="fas fa-minus-circle"></i>
                </div>
            </div>
        </div>

        <!-- Stat Card 4 -->
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stat-card-custom h-100" style="border-left: 4px solid var(--purple);">
                <div>
                    <span class="text-white-50 small fw-semibold d-block mb-1">Status slip</span>
                    <h4 class="fw-bold mb-0 text-white">{{ $paidCount }} / {{ $draftCount }}</h4>
                    <small class="text-muted d-block mt-1">Dibayar / Draft</small>
                </div>
                <div class="stat-icon-wrapper" style="background: rgba(139, 92, 246, 0.1); color: var(--purple);">
                    <i class="fas fa-info-circle"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Daftar Gaji -->
    <div class="card" style="border-radius: 14px; background: var(--bg-card); border: 1px solid var(--border);">
        <div class="card-header py-3 border-bottom d-flex align-items-center" style="border-color: var(--border) !important;">
            <div class="brand-icon bg-primary-subtle text-primary me-3 p-2 rounded d-flex align-items-center justify-content-center"
                style="width: 36px; height: 36px; background: rgba(108, 99, 255, 0.1) !important;">
                <i class="fas fa-file-invoice-dollar text-primary"></i>
            </div>
            <h5 class="mb-0 fw-bold text-white">Daftar Slip Gaji Periode: {{ date('F Y', strtotime($period . '-01')) }}</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark" style="background: var(--bg-card2);">
                        <tr>
                            <th style="padding: 1rem 1.2rem;">Karyawan</th>
                            <th style="padding: 1rem 1.2rem;">Gaji Pokok</th>
                            <th style="padding: 1rem 1.2rem;">Tunjangan</th>
                            <th style="padding: 1rem 1.2rem;">Lembur (+)</th>
                            <th style="padding: 1rem 1.2rem;">Kasbon (-)</th>
                            <th style="padding: 1rem 1.2rem;">Gaji Bersih (Net)</th>
                            <th style="padding: 1rem 1.2rem; text-align: center;">Status</th>
                            <th style="padding: 1rem 1.2rem; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payrolls as $pr)
                            <tr style="border-bottom-color: var(--border);">
                                <td style="padding: 1rem 1.2rem;">
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
                                        <div class="avatar-circle me-3" style="background: {{ $grad }};">
                                            {{ $initials }}
                                        </div>
                                        <div>
                                            <strong class="text-white d-block" style="font-size: 0.95rem;">{{ $pr->employee->name }}</strong>
                                            <span class="text-muted small">{{ $pr->employee->position ?? '-' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td style="padding: 1rem 1.2rem; color: var(--text-secondary);">Rp {{ number_format($pr->basic_salary, 0, ',', '.') }}</td>
                                <td style="padding: 1rem 1.2rem; color: var(--text-secondary);">Rp {{ number_format($pr->allowance, 0, ',', '.') }}</td>
                                <td class="text-success" style="padding: 1rem 1.2rem;">+Rp {{ number_format($pr->overtime_pay, 0, ',', '.') }}</td>
                                <td class="text-danger" style="padding: 1rem 1.2rem;">-Rp {{ number_format($pr->cash_advance_deduction, 0, ',', '.') }}</td>
                                <td style="padding: 1rem 1.2rem;">
                                    <strong class="text-white">Rp {{ number_format($pr->net_salary, 0, ',', '.') }}</strong>
                                    @if ($pr->salary_adjustment_addition > 0)
                                        <small class="text-success d-block" style="font-size: 0.72rem;">+Rp {{ number_format($pr->salary_adjustment_addition, 0, ',', '.') }} Adj</small>
                                    @endif
                                    @if ($pr->salary_adjustment_deduction > 0)
                                        <small class="text-danger d-block" style="font-size: 0.72rem;">-Rp {{ number_format($pr->salary_adjustment_deduction, 0, ',', '.') }} Adj</small>
                                    @endif
                                </td>
                                <td style="padding: 1rem 1.2rem; text-align: center;">
                                    @if ($pr->status === 'paid')
                                        <span class="badge-premium badge-premium-approved"><i class="fas fa-check-double me-1"></i> Dibayar</span>
                                    @else
                                        <span class="badge-premium badge-premium-pending"><i class="fas fa-edit me-1"></i> Draft</span>
                                    @endif
                                </td>
                                <td style="padding: 1rem 1.2rem; text-align: center;">
                                    <div class="d-inline-flex gap-1 align-items-center">
                                        <a href="{{ route('hr.payroll.show', $pr->id) }}"
                                            class="btn btn-action-custom btn-action-edit" style="width: auto !important; padding: 0 0.65rem !important;" title="Lihat & Cetak Slip Gaji">
                                            <i class="fas fa-file-invoice me-1"></i> Slip
                                        </a>
                                        @if ($pr->status === 'draft')
                                            <form action="{{ route('hr.payroll.pay', $pr->id) }}" method="POST"
                                                style="display:inline;"
                                                onsubmit="return confirm('Bayar gaji ini dan potong kasbon terkait?');">
                                                @csrf
                                                <button type="submit" class="btn btn-action-custom btn-action-approve" style="width: auto !important; padding: 0 0.65rem !important;" title="Bayar"><i
                                                        class="fas fa-coins me-1"></i> Bayar</button>
                                            </form>
                                            <form action="{{ route('hr.payroll.destroy', $pr->id) }}" method="POST"
                                                style="display:inline;"
                                                onsubmit="return confirm('Hapus slip gaji draft ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-action-custom btn-action-delete" title="Hapus"><i
                                                        class="fas fa-trash"></i></button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <div class="fs-4 mb-2"><i class="far fa-folder-open"></i></div>
                                    Belum ada slip gaji yang digenerate untuk periode ini. Klik tombol di kanan atas untuk generate.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
