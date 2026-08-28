@extends('layouts.app')

@section('title', 'Hutang Supplier')

@section('content')
<div class="container-fluid p-4">

    {{-- Header --}}
    <div class="row align-items-center mb-4">
        <div class="col-12 col-md-6">
            <h4 class="fw-bold mb-1 text-dark">Hutang Supplier</h4>
            <p class="text-muted mb-0 small">Kelola dan pantau status pembayaran hutang ke supplier</p>
        </div>
    </div>

    {{-- Alert --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4 rounded-3 d-flex align-items-center" role="alert">
            <i class="bi bi-check-circle-fill me-2 fs-5"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Pending Approval Alert --}}
    @if($pendingApprovalCount > 0)
        <div class="alert alert-warning border border-warning-subtle d-flex align-items-center gap-3 mb-4 rounded-3 bg-warning bg-opacity-10" role="alert">
            <i class="bi bi-hourglass-split fs-4 text-warning flex-shrink-0"></i>
            <div class="flex-grow-1 text-dark small">
                <strong>{{ $pendingApprovalCount }} pengajuan pembayaran menunggu persetujuan.</strong>
                Silakan buka detail masing-masing hutang untuk menyetujui atau menolak.
            </div>
        </div>
    @endif

    {{-- KPI Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card border border-light-subtle rounded-3 h-100">
                <div class="card-body p-3">
                    <span class="text-muted d-block small mb-1">Total Hutang</span>
                    <h5 class="fw-bold mb-0 text-dark">Rp {{ number_format($totalHutang, 0, ',', '.') }}</h5>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border border-light-subtle rounded-3 h-100">
                <div class="card-body p-3">
                    <span class="text-muted d-block small mb-1">Sisa Belum Dibayar</span>
                    <h5 class="fw-bold mb-0 text-danger">Rp {{ number_format($totalBelumBayar, 0, ',', '.') }}</h5>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border border-light-subtle rounded-3 h-100">
                <div class="card-body p-3">
                    <span class="text-muted d-block small mb-1">Total Dibayar</span>
                    <h5 class="fw-bold mb-0 text-success">Rp {{ number_format($totalLunas, 0, ',', '.') }}</h5>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border border-light-subtle rounded-3 h-100">
                <div class="card-body p-3">
                    <span class="text-muted d-block small mb-1">Supplier Aktif</span>
                    <h5 class="fw-bold mb-0 text-dark">{{ $totalSupplier }}</h5>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter --}}
    <div class="card border border-light-subtle rounded-3 mb-4">
        <div class="card-body p-3">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-medium text-secondary">Supplier</label>
                    <select name="supplier_id" class="form-select form-select-sm">
                        <option value="">Semua Supplier</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}" @selected(request('supplier_id') == $s->id)>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-medium text-secondary">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Semua Status</option>
                        <option value="unpaid" @selected(request('status') === 'unpaid')>Belum Dibayar</option>
                        <option value="partial" @selected(request('status') === 'partial')>Dibayar Sebagian</option>
                        <option value="paid" @selected(request('status') === 'paid')>Lunas</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-medium text-secondary">Dari Tanggal</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-medium text-secondary">Sampai Tanggal</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
                </div>
                <div class="col-12 col-md-3 d-flex gap-2">
                    <button class="btn btn-primary btn-sm flex-grow-1"><i class="bi bi-search me-1"></i>Filter</button>
                    <a href="{{ route('supplier_payables.index') }}" class="btn btn-outline-secondary btn-sm flex-grow-1">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card border border-light-subtle rounded-3 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-secondary small">
                    <tr>
                        <th class="py-3 px-4">No. Referensi</th>
                        <th class="py-3">Supplier</th>
                        <th class="py-3">Penerimaan Barang</th>
                        <th class="py-3">Tanggal</th>
                        <th class="py-3 text-end">Total Hutang</th>
                        <th class="py-3 text-end">Sudah Dibayar</th>
                        <th class="py-3 text-end">Sisa</th>
                        <th class="py-3 text-center">Status</th>
                        <th class="py-3 text-center px-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payables as $p)
                    <tr>
                        <td class="py-3 px-4">
                            <span class="font-monospace fw-semibold text-dark">{{ $p->reference_number }}</span>
                        </td>
                        <td class="py-3">
                            <span class="fw-semibold text-dark">{{ $p->supplier->name ?? '-' }}</span>
                        </td>
                        <td class="py-3">
                            @if($p->goodsReceipt)
                                <a href="{{ route('goods_receipts.show', $p->goodsReceipt) }}" class="text-primary fw-semibold text-decoration-none">
                                    {{ $p->goodsReceipt->receipt_number }}
                                </a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="py-3">
                            <span class="text-muted">{{ $p->payable_date->format('d/m/Y') }}</span>
                        </td>
                        <td class="py-3 text-end font-monospace fw-semibold">
                            Rp {{ number_format($p->total_amount, 0, ',', '.') }}
                        </td>
                        <td class="py-3 text-end font-monospace text-success">
                            Rp {{ number_format($p->paid_amount, 0, ',', '.') }}
                        </td>
                        <td class="py-3 text-end font-monospace fw-bold {{ $p->remaining_amount > 0 ? 'text-danger' : 'text-success' }}">
                            Rp {{ number_format($p->remaining_amount, 0, ',', '.') }}
                        </td>
                        <td class="py-3 text-center">
                            @php
                                $badgeClass = match ($p->status_badge) {
                                    'danger' => 'bg-danger-subtle text-danger border border-danger-subtle',
                                    'warning' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
                                    'success' => 'bg-success-subtle text-success border border-success-subtle',
                                    default => 'bg-light text-dark border',
                                };
                            @endphp
                            <span class="badge rounded-pill {{ $badgeClass }} px-3 py-1.5">{{ $p->status_label }}</span>
                        </td>
                        <td class="py-3 text-center px-4">
                            <a href="{{ route('supplier_payables.show', $p) }}" class="btn btn-outline-primary btn-sm px-3 rounded-pill">
                                <i class="bi bi-eye me-1"></i>Detail
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                            <div>Belum ada data hutang supplier.</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($payables->hasPages())
        <div class="card-footer bg-white border-top py-3 px-4">
            {{ $payables->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
