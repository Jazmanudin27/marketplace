@extends('layouts.app')
@section('title', 'Hutang Supplier')
@section('page-title', 'Hutang Supplier')

@section('content')
    <div class="row">
        <div class="col-md-12">

            {{-- KPI Cards --}}
            <div class="row g-2.5 mb-3">
                <div class="col-6 col-lg-3">
                    <div class="card border shadow-sm">
                        <div class="card-body p-3">
                            <span class="text-muted d-block small mb-1">Total Hutang</span>
                            <h5 class="fw-bold mb-0 text-dark">Rp {{ number_format($totalHutang, 0, ',', '.') }}</h5>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card border shadow-sm">
                        <div class="card-body p-3">
                            <span class="text-muted d-block small mb-1">Sisa Belum Dibayar</span>
                            <h5 class="fw-bold mb-0 text-danger">Rp {{ number_format($totalBelumBayar, 0, ',', '.') }}</h5>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card border shadow-sm">
                        <div class="card-body p-3">
                            <span class="text-muted d-block small mb-1">Total Dibayar</span>
                            <h5 class="fw-bold mb-0 text-success">Rp {{ number_format($totalLunas, 0, ',', '.') }}</h5>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card border shadow-sm">
                        <div class="card-body p-3">
                            <span class="text-muted d-block small mb-1">Supplier/Mitra</span>
                            <h5 class="fw-bold mb-0 text-dark">{{ $totalSupplier }}</h5>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Filter Bar ──────────────────────────────────────── --}}
            <div class="card border shadow-sm mb-3">
                <div class="card-body py-3 px-3">
                    <form method="GET" action="{{ route('supplier_payables.index') }}" id="filterForm">
                        <div class="row g-2 align-items-end">
                            {{-- Supplier --}}
                            <div class="col-md-3">
                                <label class="form-label form-label-sm fw-semibold mb-1 text-dark">
                                    <i class="fas fa-truck text-muted me-1"></i>Supplier / Mitra
                                </label>
                                <select name="supplier_id" id="filterSupplier" class="form-select form-select-sm select2" data-placeholder="-- Semua Supplier --">
                                    <option value="">-- Semua Supplier --</option>
                                    @foreach($suppliers as $s)
                                        <option value="{{ $s->id }}" @selected(request('supplier_id') == $s->id)>{{ $s->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Status --}}
                            <div class="col-md-2">
                                <label class="form-label form-label-sm fw-semibold mb-1 text-dark">
                                    <i class="fas fa-info-circle text-muted me-1"></i>Status
                                </label>
                                <select name="status" id="filterStatus" class="form-select form-select-sm">
                                    <option value="">-- Semua Status --</option>
                                    <option value="unpaid" @selected(request('status') === 'unpaid')>Belum Dibayar</option>
                                    <option value="partial" @selected(request('status') === 'partial')>Dibayar Sebagian</option>
                                    <option value="paid" @selected(request('status') === 'paid')>Lunas</option>
                                </select>
                            </div>

                            {{-- Dari Tanggal --}}
                            <div class="col-md-2">
                                <label class="form-label form-label-sm fw-semibold mb-1 text-dark">
                                    <i class="fas fa-calendar text-muted me-1"></i>Dari Tanggal
                                </label>
                                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
                            </div>

                            {{-- Sampai Tanggal --}}
                            <div class="col-md-2">
                                <label class="form-label form-label-sm fw-semibold mb-1 text-dark">
                                    <i class="fas fa-calendar-check text-muted me-1"></i>Sampai Tanggal
                                </label>
                                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
                            </div>

                            {{-- Tombol Filter --}}
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary btn-sm px-3">
                                    <i class="fas fa-search me-1"></i>Terapkan
                                </button>
                                <a href="{{ route('supplier_payables.index') }}" class="btn btn-secondary btn-sm px-3 ms-1">
                                    <i class="fas fa-times me-1"></i>Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- ── Tabel Utama ─────────────────────────────────────── --}}
            <div class="card border shadow-sm">
                {{-- Header --}}
                <div class="card-header bg-light d-flex justify-content-between align-items-center py-2.5 px-3 border-bottom">
                    <div>
                        <h6 class="m-0 fw-bold text-primary"><i class="fas fa-credit-card me-2"></i>Daftar Hutang Supplier</h6>
                        <p class="text-muted mb-0 small mt-1">
                            Kelola dan pantau status pembayaran hutang ke supplier / mitra usaha
                        </p>
                    </div>
                </div>

                <div class="card-body p-3">
                    {{-- Alert --}}
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    {{-- Pending Approval Alert --}}
                    @if($pendingApprovalCount > 0)
                        <div class="alert alert-warning alert-dismissible fade show mb-3 d-flex align-items-center gap-3 border border-warning-subtle bg-warning bg-opacity-10" role="alert">
                            <i class="fas fa-hourglass-half text-warning flex-shrink-0"></i>
                            <div class="flex-grow-1 text-dark small">
                                <strong>{{ $pendingApprovalCount }} pengajuan pembayaran menunggu persetujuan.</strong>
                                Silakan buka detail masing-masing hutang untuk menyetujui atau menolak.
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    {{-- Tabel --}}
                    <div class="table-responsive rounded border mt-2">
                        <table class="table table-sm table-bordered table-striped table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>NO. REFERENSI</th>
                                    <th>SUPPLIER / MITRA</th>
                                    <th>PENERIMAAN BARANG</th>
                                    <th>TANGGAL</th>
                                    <th class="text-end">TOTAL HUTANG</th>
                                    <th class="text-end">SUDAH DIBAYAR</th>
                                    <th class="text-end">SISA</th>
                                    <th class="text-center">STATUS</th>
                                    <th class="text-center">AKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($payables as $i => $p)
                                    <tr>
                                        <td class="text-center text-muted small">{{ $i + 1 }}</td>
                                        <td class="font-monospace fw-semibold small text-dark">{{ $p->reference_number }}</td>
                                        <td class="fw-semibold text-dark small">{{ $p->supplier->name ?? '-' }}</td>
                                        <td>
                                            @if($p->goodsReceipt)
                                                <a href="{{ route('goods_receipts.show', $p->goodsReceipt) }}" class="text-primary fw-semibold small text-decoration-none">
                                                    {{ $p->goodsReceipt->receipt_number }}
                                                </a>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                        <td class="small text-secondary">{{ $p->payable_date->format('d/m/Y') }}</td>
                                        <td class="text-end font-monospace small fw-semibold">
                                            Rp {{ number_format($p->total_amount, 0, ',', '.') }}
                                        </td>
                                        <td class="text-end font-monospace small text-success">
                                            Rp {{ number_format($p->paid_amount, 0, ',', '.') }}
                                        </td>
                                        <td class="text-end font-monospace small fw-bold {{ $p->remaining_amount > 0 ? 'text-danger' : 'text-success' }}">
                                            Rp {{ number_format($p->remaining_amount, 0, ',', '.') }}
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $badgeClass = match ($p->status_badge) {
                                                    'danger' => 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25',
                                                    'warning' => 'bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25',
                                                    'success' => 'bg-success bg-opacity-10 text-success border border-success border-opacity-25',
                                                    default => 'bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25',
                                                };
                                            @endphp
                                            <span class="badge {{ $badgeClass }}">{{ $p->status_label }}</span>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('supplier_payables.show', $p) }}" class="btn btn-primary btn-sm px-3" title="Detail">
                                                <i class="fas fa-eye"></i> Detail
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center text-muted py-4 small">
                                            <i class="fas fa-inbox me-2 opacity-50"></i>
                                            Belum ada data hutang supplier yang cocok dengan filter.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($payables->hasPages())
                <div class="card-footer bg-white border-top py-3 px-4">
                    {{ $payables->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                // Init Select2 di filter bar
                $('#filterSupplier').select2({
                    theme: 'bootstrap-5',
                    width: '100%'
                });
            });
        </script>
    @endpush
@endsection
