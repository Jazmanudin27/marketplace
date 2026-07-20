@extends('layouts.app')

@section('title', 'Detail Hutang — ' . $supplierPayable->reference_number)

@section('content')
<div class="container-fluid p-4">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('supplier_payables.index') }}" class="btn btn-outline-secondary rounded-2">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <h4 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-credit-card-2-back text-danger"></i>
                    Hutang <span class="font-monospace">{{ $supplierPayable->reference_number }}</span>
                </h4>
                <p class="text-muted mb-0 small">Dicatat pada {{ $supplierPayable->created_at->format('d M Y H:i') }}</p>
            </div>
        </div>
        @if($supplierPayable->status !== 'paid')
        <button class="btn btn-success px-4 rounded-2" data-bs-toggle="modal" data-bs-target="#bayarModal">
            <i class="bi bi-cash-coin me-2"></i>Ajukan Pembayaran
        </button>
        @endif
    </div>

    {{-- Alerts --}}
    @foreach(['success','error','info'] as $type)
        @if(session($type))
        <div class="alert alert-{{ $type === 'error' ? 'danger' : ($type === 'info' ? 'info' : 'success') }} alert-dismissible fade show mb-4 rounded-3 d-flex align-items-center" role="alert">
            <i class="bi bi-{{ $type === 'error' ? 'exclamation-triangle-fill' : ($type === 'info' ? 'info-circle-fill' : 'check-circle-fill') }} me-2 fs-5"></i>
            <div>{!! session($type) !!}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
    @endforeach

    {{-- Validation errors (for approval modals reopened) --}}
    @if($errors->any() && session('approval_payment_id'))
    <div class="alert alert-danger alert-dismissible fade show mb-4 rounded-3">
        <ul class="mb-0 ps-3 small">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- Pending Approval Banner --}}
    @php $pendingPayments = $supplierPayable->payments->where('approval_status', 'pending'); @endphp
    @if($pendingPayments->count() > 0)
    <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center gap-3 mb-4 rounded-3" role="alert">
        <i class="bi bi-hourglass-split fs-4 text-warning flex-shrink-0"></i>
        <div class="text-dark small">
            <strong>{{ $pendingPayments->count() }} pembayaran menunggu persetujuan.</strong>
            @if($isAdmin) Silakan setujui atau tolak pada riwayat pembayaran di bawah.
            @else Silakan tunggu persetujuan dari Admin/Finance. @endif
        </div>
    </div>
    @endif

    <div class="row g-4">

        {{-- KOLOM KIRI: Info & Progress --}}
        <div class="col-lg-5">

            <div class="card border-0 shadow-sm mb-4 rounded-3">
                <div class="card-header bg-transparent border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-info-circle text-primary fs-5"></i>
                        <span class="fw-bold text-dark">Informasi Hutang</span>
                    </div>
                    <span class="badge bg-{{ $supplierPayable->status_badge }} px-3 py-2 rounded-pill small">
                        {{ $supplierPayable->status_label }}
                    </span>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-6">
                            <span class="text-muted d-block small mb-1">Supplier</span>
                            <span class="fw-semibold text-dark small">{{ $supplierPayable->supplier->name ?? '—' }}</span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block small mb-1">Sumber Dokumen</span>
                            @if($supplierPayable->goodsReceipt)
                                <a href="{{ route('goods_receipts.show', $supplierPayable->goodsReceipt) }}"
                                   class="text-primary text-decoration-none fw-semibold d-block mb-1 small">
                                    {{ $supplierPayable->goodsReceipt->receipt_number }}
                                </a>
                                <span class="text-muted d-block small">Penerimaan Barang</span>
                            @else
                                <span class="text-dark d-block small">Input Manual</span>
                            @endif
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block small mb-1">Tanggal Hutang</span>
                            <span class="text-dark small">{{ $supplierPayable->payable_date->format('d M Y') }}</span>
                        </div>
                        @if($supplierPayable->notes)
                        <div class="col-12">
                            <span class="text-muted d-block small mb-1">Keterangan / Catatan</span>
                            <span class="text-secondary small">{{ $supplierPayable->notes }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-transparent border-bottom py-3 px-4 d-flex align-items-center gap-2">
                    <i class="bi bi-bar-chart-steps text-primary fs-5"></i>
                    <span class="fw-bold text-dark">Progres Pembayaran</span>
                </div>
                <div class="card-body p-4">
                    @php
                        $pct = $supplierPayable->total_amount > 0
                            ? min(100, round(($supplierPayable->paid_amount / $supplierPayable->total_amount) * 100)) : 0;
                        $barColor = $pct >= 100 ? 'bg-success' : ($pct > 0 ? 'bg-warning' : 'bg-danger');
                    @endphp
                    <div class="d-flex justify-content-between small text-muted mb-2">
                        <span>{{ $pct }}% dibayar</span>
                        <span>{{ 100 - $pct }}% sisa</span>
                    </div>
                    <div class="progress mb-4 rounded-pill">
                        <div class="progress-bar {{ $barColor }}" role="progressbar" style="width:{{ $pct }}%;" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="row g-2 text-center">
                        <div class="col-4">
                            <div class="bg-light rounded-3 p-2">
                                <span class="text-muted d-block small mb-1">TOTAL HUTANG</span>
                                <h6 class="fw-bold mb-0 text-dark small">Rp {{ number_format($supplierPayable->total_amount, 0, ',', '.') }}</h6>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="bg-success-subtle rounded-3 p-2">
                                <span class="text-success d-block small mb-1">DIBAYAR</span>
                                <h6 class="fw-bold mb-0 text-success small">Rp {{ number_format($supplierPayable->paid_amount, 0, ',', '.') }}</h6>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="bg-{{ $supplierPayable->remaining_amount > 0 ? 'danger-subtle' : 'success-subtle' }} rounded-3 p-2">
                                <span class="text-{{ $supplierPayable->remaining_amount > 0 ? 'danger' : 'success' }} d-block small mb-1">SISA</span>
                                <h6 class="fw-bold mb-0 text-{{ $supplierPayable->remaining_amount > 0 ? 'danger' : 'success' }} small">
                                    Rp {{ number_format($supplierPayable->remaining_amount, 0, ',', '.') }}
                                </h6>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        @if($supplierPayable->status !== 'paid')
                            <button class="btn btn-success w-100 rounded-2 py-2" data-bs-toggle="modal" data-bs-target="#bayarModal">
                                <i class="bi bi-cash-coin me-2"></i>Ajukan Pembayaran
                            </button>
                        @else
                            <div class="alert alert-success border-0 bg-success-subtle text-success py-2 rounded-3 text-center mb-0">
                                <i class="bi bi-check-circle-fill me-1"></i> Hutang Lunas
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>

        {{-- KOLOM KANAN: History Pembayaran + Approval --}}
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-transparent border-bottom py-3 px-4 d-flex align-items-center gap-2">
                    <i class="bi bi-clock-history text-primary fs-5"></i>
                    <span class="fw-bold text-dark">History Pembayaran</span>
                    <span class="badge bg-secondary rounded-pill ms-2">{{ $supplierPayable->payments->count() }}</span>
                    @if($pendingPayments->count() > 0)
                        <span class="badge bg-warning text-dark rounded-pill ms-1">{{ $pendingPayments->count() }} pending</span>
                    @endif
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($supplierPayable->payments->sortByDesc('created_at') as $payment)
                        <div class="list-group-item p-4 border-0 border-bottom
                            {{ $payment->approval_status === 'pending' ? 'bg-warning bg-opacity-10' : '' }}
                            {{ $payment->approval_status === 'rejected' ? 'bg-danger bg-opacity-10' : '' }}">
                            
                            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <i class="bi bi-{{ $payment->approval_status === 'approved' ? 'check-circle-fill text-success' : ($payment->approval_status === 'pending' ? 'hourglass-split text-warning' : 'x-circle-fill text-danger') }} fs-5"></i>
                                    <span class="fw-bold text-dark fs-6">Rp {{ number_format($payment->amount, 0, ',', '.') }}</span>
                                    <span class="badge bg-secondary rounded-pill small">{{ $payment->payment_method_label }}</span>
                                    <span class="badge bg-{{ $payment->approval_status_badge }} rounded-pill small">{{ $payment->approval_status_label }}</span>
                                </div>
                                <span class="text-muted small">{{ $payment->created_at->format('d M Y H:i') }}</span>
                            </div>

                            <div class="bg-light bg-opacity-50 border rounded-3 p-3 mb-3 small text-dark">
                                <div class="row g-2">
                                    <div class="col-sm-6">
                                        <span class="text-muted">Tanggal Bayar:</span>
                                        <strong class="ms-1">{{ $payment->payment_date->format('d/m/Y') }}</strong>
                                    </div>
                                    @if($payment->reference_number)
                                    <div class="col-sm-6">
                                        <span class="text-muted">No. Referensi:</span>
                                        <span class="font-monospace ms-1">{{ $payment->reference_number }}</span>
                                    </div>
                                    @endif
                                    
                                    {{-- Detail bank/kas --}}
                                    @if($payment->approval_status === 'approved')
                                        @if($payment->payment_method === 'cash' && $payment->payment_source)
                                        <div class="col-sm-6">
                                            <span class="text-muted">Sumber Kas:</span>
                                            <span class="badge bg-{{ $payment->payment_source === 'kas_kecil' ? 'info' : 'primary' }} ms-1">
                                                {{ $payment->payment_source === 'kas_kecil' ? 'Kas Kecil' : 'Kas Besar' }}
                                            </span>
                                        </div>
                                        @elseif($payment->bank_name)
                                        <div class="col-sm-6">
                                            <span class="text-muted">Bank:</span>
                                            <strong class="ms-1">{{ $payment->bank_name }}</strong>
                                        </div>
                                        @if($payment->account_number)
                                        <div class="col-sm-6">
                                            <span class="text-muted">No. Rekening:</span>
                                            <span class="font-monospace ms-1">{{ $payment->account_number }}</span>
                                        </div>
                                        @endif
                                        @if($payment->account_name)
                                        <div class="col-sm-6">
                                            <span class="text-muted">Atas Nama:</span>
                                            <strong class="ms-1">{{ $payment->account_name }}</strong>
                                        </div>
                                        @endif
                                        @endif
                                    @endif
                                </div>
                                @if($payment->notes)
                                <div class="text-muted mt-2 pt-2 border-top fst-italic">
                                    Catatan: {{ $payment->notes }}
                                </div>
                                @endif
                            </div>

                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 small">
                                <div>
                                    @if($payment->approval_status === 'approved')
                                        <span class="text-success">
                                            <i class="bi bi-check-lg me-1"></i> Disetujui oleh <strong>{{ $payment->approvedBy->name ?? '?' }}</strong> pada {{ $payment->approved_at?->format('d/m/Y H:i') }}
                                        </span>
                                    @elseif($payment->approval_status === 'rejected')
                                        <span class="text-danger">
                                            <i class="bi bi-x-lg me-1"></i> Ditolak oleh <strong>{{ $payment->rejectedBy->name ?? '?' }}</strong> pada {{ $payment->rejected_at?->format('d/m/Y H:i') }}
                                            <span class="d-block mt-1 text-muted">Alasan: {{ $payment->rejection_reason }}</span>
                                        </span>
                                    @else
                                        <span class="text-muted">
                                            <i class="bi bi-person me-1"></i> Diajukan oleh <strong>{{ $payment->createdBy->name ?? 'System' }}</strong>
                                        </span>
                                    @endif
                                </div>

                                {{-- Buttons --}}
                                @if($isAdmin && $payment->approval_status === 'pending')
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-success btn-sm px-3 rounded-2"
                                            data-bs-toggle="modal"
                                            data-bs-target="#approveModal{{ $payment->id }}">
                                        <i class="bi bi-check-lg me-1"></i> Setujui
                                    </button>
                                    <button type="button" class="btn btn-outline-danger btn-sm px-3 rounded-2"
                                            data-bs-toggle="modal"
                                            data-bs-target="#rejectModal{{ $payment->id }}">
                                        <i class="bi bi-x-lg me-1"></i> Tolak
                                    </button>
                                </div>
                                @endif
                            </div>
                        </div>

                        {{-- MODAL APPROVE --}}
                        @if($isAdmin && $payment->approval_status === 'pending')
                        <div class="modal fade" id="approveModal{{ $payment->id }}" tabindex="-1" aria-hidden="true"
                             data-method="{{ $payment->payment_method }}">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content rounded-3">
                                    <form action="{{ route('supplier_payables.approve', [$supplierPayable, $payment]) }}" method="POST">
                                        @csrf
                                        <div class="modal-header py-3 px-4">
                                            <h6 class="modal-title fw-bold d-flex align-items-center gap-2">
                                                <i class="bi bi-check-circle text-success fs-5"></i>
                                                Setujui Pembayaran
                                            </h6>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body p-4">
                                            {{-- Ringkasan --}}
                                            <div class="alert alert-success border-0 bg-success-subtle rounded-3 p-3 mb-4">
                                                <div class="row g-2 small text-dark">
                                                    <div class="col-6">
                                                        <span class="text-muted">Supplier:</span>
                                                        <strong class="d-block mt-0.5">{{ $supplierPayable->supplier->name ?? '—' }}</strong>
                                                    </div>
                                                    <div class="col-6">
                                                        <span class="text-muted">Nominal:</span>
                                                        <strong class="d-block mt-0.5 text-success">Rp {{ number_format($payment->amount, 0, ',', '.') }}</strong>
                                                    </div>
                                                    <div class="col-6">
                                                        <span class="text-muted">Metode:</span>
                                                        <span class="badge bg-secondary d-block mt-1 w-fit-content rounded-pill">{{ $payment->payment_method_label }}</span>
                                                    </div>
                                                    <div class="col-6">
                                                        <span class="text-muted">Tgl Bayar:</span>
                                                        <strong class="d-block mt-0.5">{{ $payment->payment_date->format('d/m/Y') }}</strong>
                                                    </div>
                                                </div>
                                            </div>

                                            @if($errors->any() && session('approval_payment_id') == $payment->id)
                                            <div class="alert alert-danger rounded-3 py-2 px-3 mb-3 small">
                                                <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                                            </div>
                                            @endif

                                            {{-- TUNAI --}}
                                            @if($payment->payment_method === 'cash')
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Sumber Kas <span class="text-danger">*</span></label>
                                                <select name="payment_source" class="form-select rounded-2" required>
                                                    <option value="">-- Pilih --</option>
                                                    <option value="kas_besar" @selected(old('payment_source') === 'kas_besar')>Kas Besar (Main Cash)</option>
                                                    <option value="kas_kecil" @selected(old('payment_source') === 'kas_kecil')>Kas Kecil (Petty Cash)</option>
                                                </select>
                                                <div class="form-text text-warning small mt-1">
                                                    <i class="bi bi-info-circle me-1"></i>
                                                    Saldo kas akan otomatis terpotong setelah disetujui.
                                                </div>
                                            </div>

                                            {{-- TRANSFER / GIRO --}}
                                            @else
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">
                                                    Bank {{ $payment->payment_method === 'giro' ? '/ Penerbit Giro' : 'Tujuan' }}
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <select name="bank_name" class="form-select rounded-2" required
                                                        onchange="toggleOtherBank(this, 'otherBank{{ $payment->id }}')">
                                                    <option value="">-- Pilih Bank --</option>
                                                    @foreach(['BCA','BNI','BRI','Mandiri','BSI','CIMB Niaga','Danamon','Permata','BTN','Mega','Panin','Maybank'] as $bank)
                                                    <option value="{{ $bank }}" @selected(old('bank_name') === $bank)>{{ $bank }}</option>
                                                    @endforeach
                                                    <option value="__other__" @selected(old('bank_name') && !in_array(old('bank_name'), ['BCA','BNI','BRI','Mandiri','BSI','CIMB Niaga','Danamon','Permata','BTN','Mega','Panin','Maybank']))>Lainnya...</option>
                                                </select>
                                            </div>
                                            <div class="mb-3 d-none" id="otherBank{{ $payment->id }}">
                                                <label class="form-label small fw-semibold">Nama Bank Lainnya</label>
                                                <input type="text" name="bank_name_other" class="form-control rounded-2"
                                                       value="{{ old('bank_name_other') }}" placeholder="Ketik nama bank...">
                                            </div>
                                            <div class="row g-3 mb-3">
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">No. Rekening</label>
                                                    <input type="text" name="account_number" class="form-control font-monospace rounded-2"
                                                           value="{{ old('account_number') }}" placeholder="Nomor rekening">
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">Atas Nama</label>
                                                    <input type="text" name="account_name" class="form-control rounded-2"
                                                           value="{{ old('account_name') }}" placeholder="Nama pemilik rek.">
                                                </div>
                                            </div>
                                            @endif

                                            {{-- Catatan --}}
                                            <div class="mb-0">
                                                <label class="form-label small fw-semibold">Catatan Approval</label>
                                                <textarea name="approval_notes" class="form-control rounded-2" rows="2"
                                                          placeholder="Catatan untuk pembayaran ini (opsional)">{{ old('approval_notes') }}</textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer py-2 px-4 border-top-0">
                                            <button type="button" class="btn btn-outline-secondary rounded-2" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-success px-4 rounded-2">
                                                <i class="bi bi-check-lg me-1"></i> Konfirmasi Setujui
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        {{-- MODAL REJECT --}}
                        <div class="modal fade" id="rejectModal{{ $payment->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content rounded-3">
                                    <form action="{{ route('supplier_payables.reject', [$supplierPayable, $payment]) }}" method="POST">
                                        @csrf
                                        <div class="modal-header py-3 px-4">
                                            <h6 class="modal-title fw-bold text-danger d-flex align-items-center gap-2">
                                                <i class="bi bi-x-circle fs-5"></i> Tolak Pembayaran
                                            </h6>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body p-4">
                                            <p class="small text-muted mb-3">
                                                Apakah Anda yakin ingin menolak pembayaran sebesar <strong>Rp {{ number_format($payment->amount, 0, ',', '.') }}</strong>
                                                ({{ $payment->payment_method_label }}) dari {{ $supplierPayable->supplier->name ?? '—' }}?
                                            </p>
                                            <div class="mb-0">
                                                <label class="form-label small fw-semibold text-danger">Alasan Penolakan <span class="text-danger">*</span></label>
                                                <textarea name="rejection_reason" class="form-control rounded-2" rows="3"
                                                          placeholder="Tulis alasan penolakan..." required></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer py-2 px-4 border-top-0">
                                            <button type="button" class="btn btn-outline-secondary rounded-2" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-danger px-4 rounded-2">Konfirmasi Tolak</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endif

                        @empty
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                            <p class="small mb-0">Belum ada pengajuan pembayaran.</p>
                            @if($supplierPayable->status !== 'paid')
                            <button class="btn btn-success btn-sm mt-3 px-3 rounded-2" data-bs-toggle="modal" data-bs-target="#bayarModal">
                                <i class="bi bi-cash-coin me-2"></i>Ajukan Sekarang
                            </button>
                            @endif
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- MODAL AJUKAN PEMBAYARAN --}}
@if($supplierPayable->status !== 'paid')
<div class="modal fade" id="bayarModal" tabindex="-1" aria-labelledby="bayarModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3">
            <form action="{{ route('supplier_payables.pay', $supplierPayable) }}" method="POST">
                @csrf
                <div class="modal-header py-3 px-4">
                    <h6 class="modal-title fw-bold d-flex align-items-center gap-2" id="bayarModalLabel">
                        <i class="bi bi-cash-coin text-success fs-5"></i>
                        Ajukan Pembayaran ke {{ $supplierPayable->supplier->name ?? '—' }}
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    @if($errors->any() && !session('approval_payment_id'))
                    <div class="alert alert-danger rounded-3 py-2 px-3 mb-3 small">
                        <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </div>
                    @endif

                    <div class="alert alert-info border-0 bg-info-subtle rounded-3 p-3 mb-4">
                        <span class="text-muted d-block small mb-1">Sisa Hutang:</span>
                        <h5 class="fw-bold text-danger mb-2">Rp {{ number_format($supplierPayable->remaining_amount, 0, ',', '.') }}</h5>
                        <div class="text-muted small mt-1">
                            <i class="bi bi-info-circle me-1"></i>
                            Setelah dikirim, pembayaran menunggu persetujuan Admin/Finance. Detail bank/kas akan diisi saat approval.
                        </div>
                    </div>

                    {{-- Tanggal --}}
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Tanggal Bayar <span class="text-danger">*</span></label>
                        <input type="date" name="payment_date" class="form-control rounded-2"
                               value="{{ old('payment_date', now()->format('Y-m-d')) }}" required>
                    </div>

                    {{-- Nominal --}}
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nominal Bayar <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light rounded-start-2 border-end-0">Rp</span>
                            <input type="number" name="amount" class="form-control rounded-end-2"
                                   min="1" max="{{ $supplierPayable->remaining_amount }}"
                                   value="{{ old('amount', $supplierPayable->remaining_amount) }}" required>
                        </div>
                        <div class="form-text text-muted">Maks: Rp {{ number_format($supplierPayable->remaining_amount, 0, ',', '.') }}</div>
                    </div>

                    {{-- Metode Pembayaran --}}
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Metode Pembayaran <span class="text-danger">*</span></label>
                        <div class="d-flex gap-2 flex-wrap">
                            <div class="form-check border rounded-3 px-3 py-2 flex-grow-1 m-0">
                                <input class="form-check-input" type="radio" name="payment_method" id="methodTransfer"
                                       value="transfer" @checked(old('payment_method', 'transfer') === 'transfer')>
                                <label class="form-check-label small fw-semibold" for="methodTransfer">
                                    <i class="bi bi-bank me-1 text-primary"></i>Transfer
                                </label>
                            </div>
                            <div class="form-check border rounded-3 px-3 py-2 flex-grow-1 m-0">
                                <input class="form-check-input" type="radio" name="payment_method" id="methodCash"
                                       value="cash" @checked(old('payment_method') === 'cash')>
                                <label class="form-check-label small fw-semibold" for="methodCash">
                                    <i class="bi bi-cash me-1 text-success"></i>Tunai
                                </label>
                            </div>
                            <div class="form-check border rounded-3 px-3 py-2 flex-grow-1 m-0">
                                <input class="form-check-input" type="radio" name="payment_method" id="methodGiro"
                                       value="giro" @checked(old('payment_method') === 'giro')>
                                <label class="form-check-label small fw-semibold" for="methodGiro">
                                    <i class="bi bi-file-earmark-text me-1 text-secondary"></i>Giro / Cek
                                </label>
                            </div>
                        </div>
                        <div class="form-text text-muted mt-2">
                            <i class="bi bi-info-circle me-1"></i>Detail bank / kas akan diisi oleh Finance saat approval.
                        </div>
                    </div>

                    {{-- No. Referensi --}}
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">No. Referensi <small class="text-muted fw-normal">(opsional)</small></label>
                        <input type="text" name="reference_number" class="form-control rounded-2"
                               value="{{ old('reference_number') }}" placeholder="No. invoice supplier, dll.">
                    </div>

                    {{-- Catatan --}}
                    <div class="mb-0">
                        <label class="form-label small fw-semibold">Catatan <small class="text-muted fw-normal">(opsional)</small></label>
                        <textarea name="notes" class="form-control rounded-2" rows="2"
                                  placeholder="Keterangan tambahan...">{{ old('notes') }}</textarea>
                    </div>
                </div>
                <div class="modal-footer py-2 px-4 border-top-0">
                    <button type="button" class="btn btn-outline-secondary rounded-2" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success px-4 rounded-2">
                        <i class="bi bi-send me-1"></i> Kirim Pengajuan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if($errors->any() && !session('approval_payment_id'))
<script>
    document.addEventListener('DOMContentLoaded', function () {
        new bootstrap.Modal(document.getElementById('bayarModal')).show();
    });
</script>
@endif
@endif

<script>
function toggleOtherBank(select, otherBankId) {
    const otherGroup = document.getElementById(otherBankId);
    if (!otherGroup) return;
    const isOther = select.value === '__other__';
    otherGroup.classList.toggle('d-none', !isOther);
    const otherInput = otherGroup.querySelector('input');
    if (otherInput) otherInput.required = isOther;
}

// Re-open approve modal if validation failed for that specific payment
@if($errors->any() && session('approval_payment_id'))
document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('approveModal{{ session("approval_payment_id") }}');
    if (modalEl) new bootstrap.Modal(modalEl).show();
});
@endif
</script>

@endsection
