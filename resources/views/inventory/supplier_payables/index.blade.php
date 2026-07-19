@extends('layouts.app')

@section('title', 'Hutang Supplier')

@section('content')
<div class="container-fluid px-3 py-3">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div>
            <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                <i class="bi bi-credit-card-2-back text-danger"></i> Hutang Supplier
            </h5>
            <small class="text-secondary">Kelola dan pantau pembayaran hutang ke supplier</small>
        </div>
    </div>

    {{-- Alert --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2 small" role="alert">
            <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Pending Approval Alert --}}
    @if($pendingApprovalCount > 0)
    <div class="alert alert-warning border-warning d-flex align-items-center gap-3 mb-3 py-2" role="alert">
        <i class="bi bi-hourglass-split fs-5 text-warning flex-shrink-0"></i>
        <div class="flex-grow-1">
            <strong>{{ $pendingApprovalCount }} pengajuan pembayaran menunggu persetujuan.</strong>
            Buka detail hutang untuk menyetujui atau menolak.
        </div>
    </div>
    @endif

    {{-- KPI Cards --}}
    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-lg-3">
            <div class="card border shadow-sm h-100">
                <div class="card-body p-3 py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-secondary text-uppercase fw-bold" style="font-size:0.72rem;">Total Hutang</small>
                            <h5 class="fw-bold mb-0 mt-1 text-dark">Rp {{ number_format($totalHutang, 0, ',', '.') }}</h5>
                        </div>
                        <div class="fs-2 text-danger opacity-75"><i class="bi bi-credit-card-2-back"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border shadow-sm h-100 border-danger border-opacity-50">
                <div class="card-body p-3 py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-secondary text-uppercase fw-bold" style="font-size:0.72rem;">Sisa Belum Dibayar</small>
                            <h5 class="fw-bold mb-0 mt-1 text-danger">Rp {{ number_format($totalBelumBayar, 0, ',', '.') }}</h5>
                        </div>
                        <div class="fs-2 text-danger opacity-75"><i class="bi bi-exclamation-circle"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border shadow-sm h-100 border-success border-opacity-50">
                <div class="card-body p-3 py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-secondary text-uppercase fw-bold" style="font-size:0.72rem;">Total Dibayar</small>
                            <h5 class="fw-bold mb-0 mt-1 text-success">Rp {{ number_format($totalLunas, 0, ',', '.') }}</h5>
                        </div>
                        <div class="fs-2 text-success opacity-75"><i class="bi bi-check-circle"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border shadow-sm h-100">
                <div class="card-body p-3 py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-secondary text-uppercase fw-bold" style="font-size:0.72rem;">Supplier Aktif Hutang</small>
                            <h5 class="fw-bold mb-0 mt-1 text-dark">{{ $totalSupplier }}</h5>
                        </div>
                        <div class="fs-2 text-primary opacity-75"><i class="bi bi-people"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter --}}
    <div class="card border shadow-sm mb-3">
        <div class="card-body p-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-sm-6 col-md-3">
                    <label class="form-label small fw-semibold mb-1">Supplier</label>
                    <select name="supplier_id" class="form-select form-select-sm">
                        <option value="">Semua Supplier</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}" @selected(request('supplier_id') == $s->id)>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Semua Status</option>
                        <option value="unpaid" @selected(request('status') === 'unpaid')>Belum Dibayar</option>
                        <option value="partial" @selected(request('status') === 'partial')>Dibayar Sebagian</option>
                        <option value="paid" @selected(request('status') === 'paid')>Lunas</option>
                    </select>
                </div>
                <div class="col-sm-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Dari Tanggal</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
                </div>
                <div class="col-sm-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Sampai Tanggal</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary btn-sm px-3"><i class="bi bi-search me-1"></i>Filter</button>
                    <a href="{{ route('supplier_payables.index') }}" class="btn btn-outline-secondary btn-sm ms-1">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card border shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="px-3">No. Referensi</th>
                            <th>Supplier</th>
                            <th>Penerimaan Barang</th>
                            <th>Tanggal</th>
                            <th class="text-end">Total Hutang</th>
                            <th class="text-end">Sudah Dibayar</th>
                            <th class="text-end">Sisa</th>
                            <th class="text-center">Status</th>
                            <th class="text-center px-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payables as $p)
                        <tr>
                            <td class="px-3">
                                <span class="font-monospace fw-semibold small text-dark">{{ $p->reference_number }}</span>
                            </td>
                            <td>
                                <span class="fw-semibold small text-dark">{{ $p->supplier->name ?? '-' }}</span>
                            </td>
                            <td>
                                @if($p->goodsReceipt)
                                    <a href="{{ route('goods_receipts.show', $p->goodsReceipt) }}" class="text-primary small text-decoration-none">
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
                                <span class="badge bg-{{ $p->status_badge }} px-2 py-1 small">{{ $p->status_label }}</span>
                            </td>
                            <td class="text-center px-3">
                                <a href="{{ route('supplier_payables.show', $p) }}" class="btn btn-outline-primary btn-sm py-0 px-2">
                                    <i class="bi bi-eye me-1"></i>Detail
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-secondary">
                                <i class="bi bi-inbox fs-3 d-block mb-2 opacity-50"></i>
                                Belum ada data hutang supplier.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($payables->hasPages())
        <div class="card-footer py-2 px-3">
            {{ $payables->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
