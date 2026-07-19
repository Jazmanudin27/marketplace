@extends('layouts.app')

@section('title', 'Detail Hutang — ' . $supplierPayable->reference_number)

@section('content')
<div class="container-fluid px-3 py-3">

    {{-- Back button + Header --}}
    <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
        <a href="{{ route('supplier_payables.index') }}" class="btn btn-outline-secondary btn-sm py-1">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div class="flex-grow-1">
            <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                <i class="bi bi-credit-card-2-back text-danger"></i>
                Hutang Supplier — <span class="font-monospace">{{ $supplierPayable->reference_number }}</span>
            </h5>
            <small class="text-secondary">Dicatat pada {{ $supplierPayable->created_at->format('d M Y H:i') }}</small>
        </div>
        @if($supplierPayable->status !== 'paid')
        <button class="btn btn-success btn-sm px-3" data-bs-toggle="modal" data-bs-target="#bayarModal">
            <i class="bi bi-cash-coin me-1"></i> Bayar Hutang
        </button>
        @endif
    </div>

    {{-- Alert --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2 small" role="alert">
            <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show py-2 small" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-3">

        {{-- Left Column: Info Hutang --}}
        <div class="col-lg-5">

            {{-- Summary Card --}}
            <div class="card border shadow-sm mb-3">
                <div class="card-header py-2 px-3 d-flex align-items-center gap-2">
                    <i class="bi bi-info-circle text-primary"></i>
                    <span class="fw-bold text-dark small">Informasi Hutang</span>
                    <span class="badge bg-{{ $supplierPayable->status_badge }} ms-auto px-2 py-1">
                        {{ $supplierPayable->status_label }}
                    </span>
                </div>
                <div class="card-body p-3">
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-secondary fw-normal">Supplier</dt>
                        <dd class="col-7 fw-semibold mb-2">{{ $supplierPayable->supplier->name ?? '—' }}</dd>

                        <dt class="col-5 text-secondary fw-normal">Sumber</dt>
                        <dd class="col-7 mb-2">
                            @if($supplierPayable->goodsReceipt)
                                <a href="{{ route('goods_receipts.show', $supplierPayable->goodsReceipt) }}"
                                   class="text-primary text-decoration-none fw-semibold">
                                    {{ $supplierPayable->goodsReceipt->receipt_number }}
                                </a>
                                <span class="text-muted d-block" style="font-size:11px;">Penerimaan Barang</span>
                            @else
                                <span class="text-muted">Input Manual</span>
                            @endif
                        </dd>

                        <dt class="col-5 text-secondary fw-normal">Tanggal Hutang</dt>
                        <dd class="col-7 mb-2">{{ $supplierPayable->payable_date->format('d M Y') }}</dd>

                        @if($supplierPayable->notes)
                        <dt class="col-5 text-secondary fw-normal">Keterangan</dt>
                        <dd class="col-7 mb-0 text-secondary" style="font-size:11px;">{{ $supplierPayable->notes }}</dd>
                        @endif
                    </dl>
                </div>
            </div>

            {{-- Progress Card --}}
            <div class="card border shadow-sm">
                <div class="card-header py-2 px-3 d-flex align-items-center gap-2">
                    <i class="bi bi-bar-chart-steps text-primary"></i>
                    <span class="fw-bold text-dark small">Progres Pembayaran</span>
                </div>
                <div class="card-body p-3">
                    @php
                        $pct = $supplierPayable->total_amount > 0
                            ? min(100, round(($supplierPayable->paid_amount / $supplierPayable->total_amount) * 100))
                            : 0;
                        $barColor = $pct >= 100 ? 'bg-success' : ($pct > 0 ? 'bg-warning' : 'bg-danger');
                    @endphp
                    <div class="d-flex justify-content-between small text-secondary mb-1">
                        <span>{{ $pct }}% dibayar</span>
                        <span>{{ 100 - $pct }}% sisa</span>
                    </div>
                    <div class="progress mb-3" style="height: 12px;">
                        <div class="progress-bar {{ $barColor }} fw-bold" style="width: {{ $pct }}%;">
                            @if($pct > 15) {{ $pct }}% @endif
                        </div>
                    </div>

                    <div class="row g-2 text-center">
                        <div class="col-4">
                            <div class="border rounded p-2 bg-light">
                                <div class="text-secondary" style="font-size:10px;">TOTAL HUTANG</div>
                                <div class="fw-bold text-dark small">Rp {{ number_format($supplierPayable->total_amount, 0, ',', '.') }}</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded p-2 bg-success-subtle">
                                <div class="text-secondary" style="font-size:10px;">SUDAH DIBAYAR</div>
                                <div class="fw-bold text-success small">Rp {{ number_format($supplierPayable->paid_amount, 0, ',', '.') }}</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded p-2 {{ $supplierPayable->remaining_amount > 0 ? 'bg-danger-subtle' : 'bg-success-subtle' }}">
                                <div class="text-secondary" style="font-size:10px;">SISA HUTANG</div>
                                <div class="fw-bold {{ $supplierPayable->remaining_amount > 0 ? 'text-danger' : 'text-success' }} small">
                                    Rp {{ number_format($supplierPayable->remaining_amount, 0, ',', '.') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($supplierPayable->status !== 'paid')
                    <div class="mt-3 text-center">
                        <button class="btn btn-success btn-sm px-4" data-bs-toggle="modal" data-bs-target="#bayarModal">
                            <i class="bi bi-cash-coin me-1"></i> Bayar Hutang
                        </button>
                    </div>
                    @else
                    <div class="mt-3 text-center">
                        <span class="badge bg-success px-3 py-2 fs-6">✅ Hutang Lunas</span>
                    </div>
                    @endif
                </div>
            </div>

        </div>

        {{-- Right Column: History Pembayaran --}}
        <div class="col-lg-7">
            <div class="card border shadow-sm h-100">
                <div class="card-header py-2 px-3 d-flex align-items-center gap-2">
                    <i class="bi bi-clock-history text-primary"></i>
                    <span class="fw-bold text-dark small">History Pembayaran</span>
                    <span class="badge bg-secondary rounded-pill ms-1">{{ $supplierPayable->payments->count() }}</span>
                </div>
                <div class="card-body p-0">
                    @forelse($supplierPayable->payments->sortByDesc('payment_date') as $payment)
                    <div class="d-flex align-items-start gap-3 p-3 border-bottom">
                        <div class="bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                             style="width:40px;height:40px;">
                            <i class="bi bi-cash-coin fs-5"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="fw-bold text-dark">Rp {{ number_format($payment->amount, 0, ',', '.') }}</span>
                                    <span class="badge bg-secondary ms-2 px-2 py-1 small">
                                        {{ $payment->payment_method_label }}
                                    </span>
                                </div>
                                <small class="text-secondary">{{ $payment->payment_date->format('d M Y') }}</small>
                            </div>
                            @if($payment->reference_number)
                                <div class="text-secondary small mt-1">
                                    <i class="bi bi-tag me-1"></i>No. Ref: <span class="font-monospace">{{ $payment->reference_number }}</span>
                                </div>
                            @endif
                            @if($payment->notes)
                                <div class="text-secondary small mt-1 fst-italic">{{ $payment->notes }}</div>
                            @endif
                            <div class="text-secondary mt-1" style="font-size:11px;">
                                Dicatat oleh: {{ $payment->createdBy->name ?? 'System' }}
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-5 text-secondary">
                        <i class="bi bi-inbox fs-3 d-block mb-2 opacity-50"></i>
                        <p class="small mb-0">Belum ada pembayaran tercatat.</p>
                        @if($supplierPayable->status !== 'paid')
                        <button class="btn btn-success btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#bayarModal">
                            <i class="bi bi-cash-coin me-1"></i> Bayar Sekarang
                        </button>
                        @endif
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>{{-- /row --}}
</div>

{{-- ======================================================== --}}
{{-- MODAL BAYAR HUTANG                                       --}}
{{-- ======================================================== --}}
@if($supplierPayable->status !== 'paid')
<div class="modal fade" id="bayarModal" tabindex="-1" aria-labelledby="bayarModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('supplier_payables.pay', $supplierPayable) }}" method="POST">
                @csrf
                <div class="modal-header py-2">
                    <h6 class="modal-title fw-bold d-flex align-items-center gap-2" id="bayarModalLabel">
                        <i class="bi bi-cash-coin text-success"></i>
                        Bayar Hutang ke {{ $supplierPayable->supplier->name ?? '—' }}
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if($errors->any())
                        <div class="alert alert-danger py-2 small">
                            <ul class="mb-0 ps-3">
                                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="alert alert-info py-2 small border-0 bg-info-subtle mb-3">
                        <strong>Sisa Hutang:</strong>
                        <span class="fw-bold text-danger ms-1">Rp {{ number_format($supplierPayable->remaining_amount, 0, ',', '.') }}</span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Tanggal Bayar <span class="text-danger">*</span></label>
                        <input type="date" name="payment_date" class="form-control form-control-sm"
                               value="{{ old('payment_date', now()->format('Y-m-d')) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">
                            Nominal Bayar <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">Rp</span>
                            <input type="number" name="amount" id="payAmount" class="form-control"
                                   min="1" max="{{ $supplierPayable->remaining_amount }}"
                                   value="{{ old('amount', $supplierPayable->remaining_amount) }}"
                                   placeholder="Nominal pembayaran" required>
                        </div>
                        <div class="form-text">Maks: Rp {{ number_format($supplierPayable->remaining_amount, 0, ',', '.') }}</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Metode Pembayaran <span class="text-danger">*</span></label>
                        <select name="payment_method" class="form-select form-select-sm" required>
                            <option value="transfer" @selected(old('payment_method') === 'transfer')>Transfer Bank</option>
                            <option value="cash" @selected(old('payment_method') === 'cash')>Tunai</option>
                            <option value="giro" @selected(old('payment_method') === 'giro')>Giro / Cek</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">No. Referensi / No. Transfer</label>
                        <input type="text" name="reference_number" class="form-control form-control-sm"
                               value="{{ old('reference_number') }}" placeholder="Opsional — nomor bukti bayar">
                    </div>

                    <div class="mb-0">
                        <label class="form-label small fw-semibold">Keterangan</label>
                        <textarea name="notes" class="form-control form-control-sm" rows="2"
                                  placeholder="Keterangan tambahan (opsional)">{{ old('notes') }}</textarea>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success btn-sm px-4">
                        <i class="bi bi-check-lg me-1"></i> Konfirmasi Pembayaran
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if($errors->any())
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var bayarModal = new bootstrap.Modal(document.getElementById('bayarModal'));
        bayarModal.show();
    });
</script>
@endif
@endif

@endsection
