@extends('layouts.app')

@section('title', 'Detail Hutang — ' . $supplierPayable->reference_number)

@section('content')
<div class="container-fluid px-3 py-3">

    {{-- Header --}}
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
            <i class="bi bi-cash-coin me-1"></i> Ajukan Pembayaran
        </button>
        @endif
    </div>

    {{-- Alerts --}}
    @foreach(['success','error','info'] as $type)
        @if(session($type))
        <div class="alert alert-{{ $type === 'error' ? 'danger' : ($type === 'info' ? 'info' : 'success') }} alert-dismissible fade show py-2 small" role="alert">
            <i class="bi bi-{{ $type === 'error' ? 'exclamation-triangle-fill' : ($type === 'info' ? 'info-circle-fill' : 'check-circle-fill') }} me-1"></i>
            {!! session($type) !!}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
    @endforeach

    {{-- Validation errors (for approval modals reopened) --}}
    @if($errors->any() && session('approval_payment_id'))
    <div class="alert alert-danger py-2 small alert-dismissible fade show">
        <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- Pending Approval Banner --}}
    @php $pendingPayments = $supplierPayable->payments->where('approval_status', 'pending'); @endphp
    @if($pendingPayments->count() > 0)
    <div class="alert alert-warning d-flex align-items-center gap-3 mb-3 py-2">
        <i class="bi bi-hourglass-split fs-4 text-warning flex-shrink-0"></i>
        <div>
            <strong>{{ $pendingPayments->count() }} pembayaran menunggu persetujuan.</strong>
            @if($isAdmin) Scroll ke bawah untuk menyetujui atau menolak.
            @else Silakan tunggu persetujuan dari Admin/Finance. @endif
        </div>
    </div>
    @endif

    <div class="row g-3">

        {{-- =========================================================== --}}
        {{-- KOLOM KIRI: Info & Progress                                  --}}
        {{-- =========================================================== --}}
        <div class="col-lg-5">

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
                            @else <span class="text-muted">Input Manual</span> @endif
                        </dd>
                        <dt class="col-5 text-secondary fw-normal">Tanggal Hutang</dt>
                        <dd class="col-7 mb-0">{{ $supplierPayable->payable_date->format('d M Y') }}</dd>
                        @if($supplierPayable->notes)
                        <dt class="col-5 text-secondary fw-normal mt-2">Keterangan</dt>
                        <dd class="col-7 mb-0 text-secondary mt-2" style="font-size:11px;">{{ $supplierPayable->notes }}</dd>
                        @endif
                    </dl>
                </div>
            </div>

            <div class="card border shadow-sm">
                <div class="card-header py-2 px-3 d-flex align-items-center gap-2">
                    <i class="bi bi-bar-chart-steps text-primary"></i>
                    <span class="fw-bold text-dark small">Progres Pembayaran</span>
                </div>
                <div class="card-body p-3">
                    @php
                        $pct = $supplierPayable->total_amount > 0
                            ? min(100, round(($supplierPayable->paid_amount / $supplierPayable->total_amount) * 100)) : 0;
                        $barColor = $pct >= 100 ? 'bg-success' : ($pct > 0 ? 'bg-warning' : 'bg-danger');
                    @endphp
                    <div class="d-flex justify-content-between small text-secondary mb-1">
                        <span>{{ $pct }}% dibayar</span><span>{{ 100 - $pct }}% sisa</span>
                    </div>
                    <div class="progress mb-3" style="height:12px;">
                        <div class="progress-bar {{ $barColor }}" style="width:{{ $pct }}%;">
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
                                <div class="text-secondary" style="font-size:10px;">DIBAYAR</div>
                                <div class="fw-bold text-success small">Rp {{ number_format($supplierPayable->paid_amount, 0, ',', '.') }}</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded p-2 {{ $supplierPayable->remaining_amount > 0 ? 'bg-danger-subtle' : 'bg-success-subtle' }}">
                                <div class="text-secondary" style="font-size:10px;">SISA</div>
                                <div class="fw-bold {{ $supplierPayable->remaining_amount > 0 ? 'text-danger' : 'text-success' }} small">
                                    Rp {{ number_format($supplierPayable->remaining_amount, 0, ',', '.') }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 text-center">
                        @if($supplierPayable->status !== 'paid')
                            <button class="btn btn-success btn-sm px-4" data-bs-toggle="modal" data-bs-target="#bayarModal">
                                <i class="bi bi-cash-coin me-1"></i> Ajukan Pembayaran
                            </button>
                        @else
                            <span class="badge bg-success px-3 py-2 fs-6">✅ Hutang Lunas</span>
                        @endif
                    </div>
                </div>
            </div>

        </div>

        {{-- =========================================================== --}}
        {{-- KOLOM KANAN: History Pembayaran + Approval                   --}}
        {{-- =========================================================== --}}
        <div class="col-lg-7">
            <div class="card border shadow-sm h-100">
                <div class="card-header py-2 px-3 d-flex align-items-center gap-2">
                    <i class="bi bi-clock-history text-primary"></i>
                    <span class="fw-bold text-dark small">History Pembayaran</span>
                    <span class="badge bg-secondary rounded-pill ms-1">{{ $supplierPayable->payments->count() }}</span>
                    @if($pendingPayments->count() > 0)
                        <span class="badge bg-warning text-dark rounded-pill ms-1">{{ $pendingPayments->count() }} pending</span>
                    @endif
                </div>
                <div class="card-body p-0">
                    @forelse($supplierPayable->payments->sortByDesc('created_at') as $payment)
                    <div class="p-3 border-bottom
                        {{ $payment->approval_status === 'pending' ? 'bg-warning bg-opacity-10' : '' }}
                        {{ $payment->approval_status === 'rejected' ? 'bg-danger bg-opacity-10' : '' }}">

                        <div class="d-flex align-items-start gap-3">
                            {{-- Icon --}}
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0
                                {{ $payment->approval_status === 'approved' ? 'bg-success-subtle text-success' : ($payment->approval_status === 'pending' ? 'bg-warning-subtle text-warning' : 'bg-danger-subtle text-danger') }}"
                                 style="width:40px;height:40px;">
                                <i class="bi bi-{{ $payment->approval_status === 'approved' ? 'check-circle' : ($payment->approval_status === 'pending' ? 'hourglass-split' : 'x-circle') }} fs-5"></i>
                            </div>

                            <div class="flex-grow-1">
                                {{-- Baris atas: nominal + badges + tanggal --}}
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-1">
                                    <div>
                                        <span class="fw-bold text-dark">Rp {{ number_format($payment->amount, 0, ',', '.') }}</span>
                                        <span class="badge bg-secondary ms-1 px-2 small">{{ $payment->payment_method_label }}</span>
                                        <span class="badge bg-{{ $payment->approval_status_badge }} ms-1 px-2 small">
                                            {{ $payment->approval_status_label }}
                                        </span>
                                    </div>
                                    <small class="text-secondary">{{ $payment->created_at->format('d M Y H:i') }}</small>
                                </div>

                                {{-- Detail pengajuan --}}
                                <div class="mt-2 rounded p-2 bg-light small">
                                    <div class="row g-1">
                                        <div class="col-sm-6">
                                            <span class="text-secondary">Tgl Bayar:</span>
                                            <strong class="ms-1">{{ $payment->payment_date->format('d/m/Y') }}</strong>
                                        </div>
                                        @if($payment->reference_number)
                                        <div class="col-sm-6">
                                            <span class="text-secondary">No. Ref:</span>
                                            <span class="font-monospace ms-1">{{ $payment->reference_number }}</span>
                                        </div>
                                        @endif
                                        {{-- Detail bank/kas (terisi setelah approve) --}}
                                        @if($payment->approval_status === 'approved')
                                            @if($payment->payment_method === 'cash' && $payment->payment_source)
                                            <div class="col-sm-6 mt-1">
                                                <span class="text-secondary">Sumber Kas:</span>
                                                <span class="badge bg-{{ $payment->payment_source === 'kas_kecil' ? 'info' : 'primary' }} ms-1">
                                                    {{ $payment->payment_source === 'kas_kecil' ? 'Kas Kecil' : 'Kas Besar' }}
                                                </span>
                                            </div>
                                            @elseif($payment->bank_name)
                                            <div class="col-sm-6 mt-1">
                                                <span class="text-secondary">Bank:</span>
                                                <strong class="ms-1">{{ $payment->bank_name }}</strong>
                                            </div>
                                            @if($payment->account_number)
                                            <div class="col-sm-6 mt-1">
                                                <span class="text-secondary">No. Rek:</span>
                                                <span class="font-monospace ms-1">{{ $payment->account_number }}</span>
                                            </div>
                                            @endif
                                            @if($payment->account_name)
                                            <div class="col-sm-6 mt-1">
                                                <span class="text-secondary">Atas Nama:</span>
                                                <strong class="ms-1">{{ $payment->account_name }}</strong>
                                            </div>
                                            @endif
                                            @endif
                                        @endif
                                    </div>
                                    @if($payment->notes)
                                        <div class="text-secondary mt-1 fst-italic border-top pt-1">{{ $payment->notes }}</div>
                                    @endif
                                </div>

                                {{-- Info diajukan / disetujui / ditolak --}}
                                <div class="small mt-1">
                                    @if($payment->approval_status === 'approved')
                                    <span class="text-success">
                                        <i class="bi bi-check-circle me-1"></i>
                                        Disetujui oleh <strong>{{ $payment->approvedBy->name ?? '?' }}</strong>
                                        · {{ $payment->approved_at?->format('d/m/Y H:i') }}
                                        @if($payment->expense_id)
                                        <span class="text-secondary ms-1"><i class="bi bi-journal-check"></i> Jurnal kas tercatat</span>
                                        @endif
                                    </span>
                                    @elseif($payment->approval_status === 'rejected')
                                    <div class="text-danger">
                                        <i class="bi bi-x-circle me-1"></i>
                                        Ditolak oleh <strong>{{ $payment->rejectedBy->name ?? '?' }}</strong>
                                        · {{ $payment->rejected_at?->format('d/m/Y H:i') }}
                                        <div class="fst-italic mt-1 text-danger-emphasis">Alasan: {{ $payment->rejection_reason }}</div>
                                    </div>
                                    @else
                                    <span class="text-secondary">
                                        <i class="bi bi-person me-1"></i>
                                        Diajukan oleh: {{ $payment->createdBy->name ?? 'System' }}
                                    </span>
                                    @endif
                                </div>

                                {{-- Tombol Approve / Reject untuk Admin --}}
                                @if($isAdmin && $payment->approval_status === 'pending')
                                <div class="d-flex gap-2 mt-2 flex-wrap">
                                    <button type="button" class="btn btn-success btn-sm px-3"
                                            data-bs-toggle="modal"
                                            data-bs-target="#approveModal{{ $payment->id }}">
                                        <i class="bi bi-check-lg me-1"></i> Setujui
                                    </button>
                                    <button type="button" class="btn btn-outline-danger btn-sm px-3"
                                            data-bs-toggle="modal"
                                            data-bs-target="#rejectModal{{ $payment->id }}">
                                        <i class="bi bi-x-lg me-1"></i> Tolak
                                    </button>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- ================================================ --}}
                    {{-- MODAL APPROVE (detail bank/kas diisi saat approve) --}}
                    {{-- ================================================ --}}
                    @if($isAdmin && $payment->approval_status === 'pending')
                    <div class="modal fade" id="approveModal{{ $payment->id }}" tabindex="-1" aria-hidden="true"
                         data-method="{{ $payment->payment_method }}">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <form action="{{ route('supplier_payables.approve', [$supplierPayable, $payment]) }}" method="POST">
                                    @csrf
                                    <div class="modal-header py-2">
                                        <h6 class="modal-title fw-bold d-flex align-items-center gap-2">
                                            <i class="bi bi-check-circle text-success"></i>
                                            Setujui Pembayaran
                                        </h6>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        {{-- Ringkasan --}}
                                        <div class="alert alert-success-subtle border border-success-subtle rounded p-2 small mb-3">
                                            <div class="row g-1">
                                                <div class="col-6">
                                                    <span class="text-secondary">Supplier:</span>
                                                    <strong class="ms-1">{{ $supplierPayable->supplier->name ?? '—' }}</strong>
                                                </div>
                                                <div class="col-6">
                                                    <span class="text-secondary">Nominal:</span>
                                                    <strong class="ms-1 text-success">Rp {{ number_format($payment->amount, 0, ',', '.') }}</strong>
                                                </div>
                                                <div class="col-6">
                                                    <span class="text-secondary">Metode:</span>
                                                    <span class="badge bg-secondary ms-1">{{ $payment->payment_method_label }}</span>
                                                </div>
                                                <div class="col-6">
                                                    <span class="text-secondary">Tgl Bayar:</span>
                                                    <strong class="ms-1">{{ $payment->payment_date->format('d/m/Y') }}</strong>
                                                </div>
                                            </div>
                                        </div>

                                        @if($errors->any() && session('approval_payment_id') == $payment->id)
                                        <div class="alert alert-danger py-2 small">
                                            <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                                        </div>
                                        @endif

                                        {{-- ================================ --}}
                                        {{-- TUNAI: pilih sumber kas          --}}
                                        {{-- ================================ --}}
                                        @if($payment->payment_method === 'cash')
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Sumber Kas <span class="text-danger">*</span></label>
                                            <select name="payment_source" class="form-select form-select-sm" required>
                                                <option value="">-- Pilih --</option>
                                                <option value="kas_besar" @selected(old('payment_source') === 'kas_besar')>💰 Kas Besar (Main Cash)</option>
                                                <option value="kas_kecil" @selected(old('payment_source') === 'kas_kecil')>💵 Kas Kecil (Petty Cash)</option>
                                            </select>
                                            <div class="form-text text-warning small">
                                                <i class="bi bi-info-circle me-1"></i>
                                                Saldo kas akan otomatis terpotong setelah disetujui.
                                            </div>
                                        </div>

                                        {{-- ================================ --}}
                                        {{-- TRANSFER / GIRO: detail bank     --}}
                                        {{-- ================================ --}}
                                        @else
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">
                                                Bank {{ $payment->payment_method === 'giro' ? '/ Penerbit Giro' : 'Tujuan' }}
                                                <span class="text-danger">*</span>
                                            </label>
                                            <select name="bank_name" class="form-select form-select-sm" required
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
                                            <input type="text" name="bank_name_other" class="form-control form-control-sm"
                                                   value="{{ old('bank_name_other') }}" placeholder="Ketik nama bank...">
                                        </div>
                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold">No. Rekening</label>
                                                <input type="text" name="account_number" class="form-control form-control-sm font-monospace"
                                                       value="{{ old('account_number') }}" placeholder="Nomor rekening">
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold">Atas Nama</label>
                                                <input type="text" name="account_name" class="form-control form-control-sm"
                                                       value="{{ old('account_name') }}" placeholder="Nama pemilik rek.">
                                            </div>
                                        </div>
                                        @endif

                                        {{-- Catatan approval --}}
                                        <div class="mb-0">
                                            <label class="form-label small fw-semibold">Catatan Approval</label>
                                            <textarea name="approval_notes" class="form-control form-control-sm" rows="2"
                                                      placeholder="Catatan untuk pembayaran ini (opsional)">{{ old('approval_notes') }}</textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer py-2">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-success btn-sm px-4">
                                            <i class="bi bi-check-lg me-1"></i> Konfirmasi Setujui
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    {{-- MODAL REJECT --}}
                    <div class="modal fade" id="rejectModal{{ $payment->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-sm">
                            <div class="modal-content">
                                <form action="{{ route('supplier_payables.reject', [$supplierPayable, $payment]) }}" method="POST">
                                    @csrf
                                    <div class="modal-header py-2">
                                        <h6 class="modal-title fw-bold text-danger">
                                            <i class="bi bi-x-circle me-1"></i> Tolak Pembayaran
                                        </h6>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p class="small text-secondary mb-2">
                                            Tolak pembayaran <strong>Rp {{ number_format($payment->amount, 0, ',', '.') }}</strong>
                                            ({{ $payment->payment_method_label }}) dari {{ $supplierPayable->supplier->name ?? '—' }}?
                                        </p>
                                        <label class="form-label small fw-semibold">Alasan Penolakan <span class="text-danger">*</span></label>
                                        <textarea name="rejection_reason" class="form-control form-control-sm" rows="3"
                                                  placeholder="Tulis alasan penolakan..." required></textarea>
                                    </div>
                                    <div class="modal-footer py-2">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-danger btn-sm px-3">Konfirmasi Tolak</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endif

                    @empty
                    <div class="text-center py-5 text-secondary">
                        <i class="bi bi-inbox fs-3 d-block mb-2 opacity-50"></i>
                        <p class="small mb-0">Belum ada pengajuan pembayaran.</p>
                        @if($supplierPayable->status !== 'paid')
                        <button class="btn btn-success btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#bayarModal">
                            <i class="bi bi-cash-coin me-1"></i> Ajukan Sekarang
                        </button>
                        @endif
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>{{-- /row --}}
</div>

{{-- ================================================================ --}}
{{-- MODAL AJUKAN PEMBAYARAN (simpel: tgl + nominal + metode + catatan) --}}
{{-- ================================================================ --}}
@if($supplierPayable->status !== 'paid')
<div class="modal fade" id="bayarModal" tabindex="-1" aria-labelledby="bayarModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('supplier_payables.pay', $supplierPayable) }}" method="POST">
                @csrf
                <div class="modal-header py-2">
                    <h6 class="modal-title fw-bold d-flex align-items-center gap-2" id="bayarModalLabel">
                        <i class="bi bi-cash-coin text-success"></i>
                        Ajukan Pembayaran ke {{ $supplierPayable->supplier->name ?? '—' }}
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if($errors->any() && !session('approval_payment_id'))
                    <div class="alert alert-danger py-2 small">
                        <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </div>
                    @endif

                    <div class="alert alert-info py-2 small border-0 bg-info-subtle mb-3">
                        <strong>Sisa Hutang:</strong>
                        <span class="fw-bold text-danger ms-1">Rp {{ number_format($supplierPayable->remaining_amount, 0, ',', '.') }}</span>
                        <div class="text-secondary mt-1" style="font-size:11px;">
                            <i class="bi bi-info-circle me-1"></i>
                            Setelah dikirim, pembayaran menunggu persetujuan Admin/Finance. Detail bank/kas akan diisi saat approval.
                        </div>
                    </div>

                    {{-- Tanggal --}}
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Tanggal Bayar <span class="text-danger">*</span></label>
                        <input type="date" name="payment_date" class="form-control form-control-sm"
                               value="{{ old('payment_date', now()->format('Y-m-d')) }}" required>
                    </div>

                    {{-- Nominal --}}
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nominal Bayar <span class="text-danger">*</span></label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">Rp</span>
                            <input type="number" name="amount" class="form-control"
                                   min="1" max="{{ $supplierPayable->remaining_amount }}"
                                   value="{{ old('amount', $supplierPayable->remaining_amount) }}" required>
                        </div>
                        <div class="form-text">Maks: Rp {{ number_format($supplierPayable->remaining_amount, 0, ',', '.') }}</div>
                    </div>

                    {{-- Metode Pembayaran --}}
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Metode Pembayaran <span class="text-danger">*</span></label>
                        <div class="d-flex gap-2 flex-wrap">
                            <div class="form-check form-check-inline border rounded px-3 py-2 flex-grow-1 m-0">
                                <input class="form-check-input" type="radio" name="payment_method" id="methodTransfer"
                                       value="transfer" @checked(old('payment_method', 'transfer') === 'transfer')>
                                <label class="form-check-label small fw-semibold" for="methodTransfer">
                                    <i class="bi bi-bank me-1 text-primary"></i>Transfer Bank
                                </label>
                            </div>
                            <div class="form-check form-check-inline border rounded px-3 py-2 flex-grow-1 m-0">
                                <input class="form-check-input" type="radio" name="payment_method" id="methodCash"
                                       value="cash" @checked(old('payment_method') === 'cash')>
                                <label class="form-check-label small fw-semibold" for="methodCash">
                                    <i class="bi bi-cash me-1 text-success"></i>Tunai
                                </label>
                            </div>
                            <div class="form-check form-check-inline border rounded px-3 py-2 flex-grow-1 m-0">
                                <input class="form-check-input" type="radio" name="payment_method" id="methodGiro"
                                       value="giro" @checked(old('payment_method') === 'giro')>
                                <label class="form-check-label small fw-semibold" for="methodGiro">
                                    <i class="bi bi-file-earmark-text me-1 text-secondary"></i>Giro / Cek
                                </label>
                            </div>
                        </div>
                        <div class="form-text text-muted">
                            <i class="bi bi-info-circle me-1"></i>Detail bank / kas akan diisi oleh Finance saat approval.
                        </div>
                    </div>

                    {{-- No. Referensi --}}
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">No. Referensi <small class="text-muted fw-normal">(opsional)</small></label>
                        <input type="text" name="reference_number" class="form-control form-control-sm"
                               value="{{ old('reference_number') }}" placeholder="No. invoice supplier, dll.">
                    </div>

                    {{-- Catatan --}}
                    <div class="mb-0">
                        <label class="form-label small fw-semibold">Catatan <small class="text-muted fw-normal">(opsional)</small></label>
                        <textarea name="notes" class="form-control form-control-sm" rows="2"
                                  placeholder="Keterangan tambahan...">{{ old('notes') }}</textarea>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success btn-sm px-4">
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
    // If not "other", sync bank_name from the select to prevent empty submit
    // (handled server-side: if bank_name == __other__ → use bank_name_other)
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
