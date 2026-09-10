@extends('layouts.app')
@section('title', 'Pembayaran Produksi SPK')
@section('page-title', 'Pembayaran ke Produksi (SPK)')

@section('content')
<div class="container-fluid px-3 py-2">

    {{-- ALERT MESSAGES --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3 d-flex align-items-center gap-2" role="alert">
            <i class="fas fa-check-circle fs-5 text-success"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3 d-flex align-items-center gap-2" role="alert">
            <i class="fas fa-exclamation-circle fs-5 text-danger"></i>
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3" role="alert">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- TOP ACTION & TITLE BAR --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                <i class="fas fa-money-bill-wave text-warning"></i> Rekap Pembayaran Produksi SPK
            </h5>
            <small class="text-muted">Pantau kelunasan dan cicilan ongkos jasa produksi vendor/penjahit per SPK</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('spks.index') }}" class="btn btn-sm btn-outline-secondary px-3">
                <i class="fas fa-arrow-left me-1"></i>Daftar SPK
            </a>
        </div>
    </div>

    {{-- 4 KPI CARDS --}}
    <div class="row g-2.5 mb-3">
        {{-- Total Biaya Produksi --}}
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100">
                <div class="card-body p-3">
                    <span class="text-muted d-block small text-uppercase fw-bold mb-1" style="font-size: 10px; letter-spacing: 0.5px;">
                        Total Biaya Produksi
                    </span>
                    <h5 class="fw-extrabold mb-0 text-dark">
                        Rp {{ number_format($totalBiayaProduksiAll, 0, ',', '.') }}
                    </h5>
                    <small class="text-muted" style="font-size: 11px;">Dari {{ $totalSpkCount }} pesanan SPK</small>
                </div>
            </div>
        </div>

        {{-- Sudah Dibayar --}}
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100 border-start border-4 border-success">
                <div class="card-body p-3">
                    <span class="text-success d-block small text-uppercase fw-bold mb-1" style="font-size: 10px; letter-spacing: 0.5px;">
                        Total Sudah Dibayar
                    </span>
                    <h5 class="fw-extrabold mb-0 text-success">
                        Rp {{ number_format($totalSudahDibayarAll, 0, ',', '.') }}
                    </h5>
                    <small class="text-muted" style="font-size: 11px;">Cicilan masuk ke vendor</small>
                </div>
            </div>
        </div>

        {{-- Sisa Belum Dibayar --}}
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100 border-start border-4 border-danger">
                <div class="card-body p-3">
                    <span class="text-danger d-block small text-uppercase fw-bold mb-1" style="font-size: 10px; letter-spacing: 0.5px;">
                        Sisa Belum Dibayar
                    </span>
                    <h5 class="fw-extrabold mb-0 text-danger">
                        Rp {{ number_format($totalSisaBelumDibayarAll, 0, ',', '.') }}
                    </h5>
                    <small class="text-muted" style="font-size: 11px;">Kewajiban hutang produksi</small>
                </div>
            </div>
        </div>

        {{-- Status SPK --}}
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100 border-start border-4 border-primary">
                <div class="card-body p-3">
                    <span class="text-primary d-block small text-uppercase fw-bold mb-1" style="font-size: 10px; letter-spacing: 0.5px;">
                        Status Kelunasan
                    </span>
                    <div class="d-flex align-items-center gap-1.5 flex-wrap pt-0.5">
                        <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold" style="font-size: 10.5px;">
                            {{ $countLunas }} Lunas
                        </span>
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fw-bold" style="font-size: 10.5px;">
                            {{ $countDicicil }} Dicicil
                        </span>
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold" style="font-size: 10.5px;">
                            {{ $countBelumBayar }} Belum Bayar
                        </span>
                    </div>
                    <small class="text-muted d-block mt-1" style="font-size: 11px;">Progress kelunasan per SPK</small>
                </div>
            </div>
        </div>
    </div>

    {{-- FILTER BAR --}}
    <div class="card border-0 shadow-sm rounded-3 mb-3 bg-white">
        <div class="card-body py-2.5 px-3">
            <form method="GET" action="{{ route('spks.payments.index') }}" id="filterForm">
                <div class="row g-2 align-items-end">
                    {{-- Search --}}
                    <div class="col-md-3">
                        <label class="form-label form-label-sm fw-semibold mb-1 text-dark" style="font-size: 11px;">
                            <i class="fas fa-search text-muted me-1"></i>Cari SPK / Pemesan
                        </label>
                        <input type="text" name="search" value="{{ request('search') }}"
                               class="form-control form-control-sm"
                               placeholder="No SPK, No Produksi, Pemesan...">
                    </div>

                    {{-- Status Pembayaran --}}
                    <div class="col-md-2">
                        <label class="form-label form-label-sm fw-semibold mb-1 text-dark" style="font-size: 11px;">
                            <i class="fas fa-filter text-muted me-1"></i>Status Pembayaran
                        </label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">-- Semua Status --</option>
                            <option value="unpaid" @selected(request('status') === 'unpaid')>Belum Bayar</option>
                            <option value="partial" @selected(request('status') === 'partial')>Dicicil</option>
                            <option value="paid" @selected(request('status') === 'paid')>Lunas</option>
                        </select>
                    </div>

                    {{-- Dari Tanggal --}}
                    <div class="col-md-2">
                        <label class="form-label form-label-sm fw-semibold mb-1 text-dark" style="font-size: 11px;">
                            <i class="far fa-calendar-alt text-muted me-1"></i>Dari Tanggal
                        </label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
                    </div>

                    {{-- Sampai Tanggal --}}
                    <div class="col-md-2">
                        <label class="form-label form-label-sm fw-semibold mb-1 text-dark" style="font-size: 11px;">
                            <i class="far fa-calendar-check text-muted me-1"></i>Sampai Tanggal
                        </label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
                    </div>

                    {{-- Tombol Action Filter --}}
                    <div class="col-md-3 d-flex gap-1">
                        <button type="submit" class="btn btn-primary btn-sm px-3 flex-fill">
                            <i class="fas fa-search me-1"></i>Filter
                        </button>
                        <a href="{{ route('spks.payments.index') }}" class="btn btn-outline-secondary btn-sm px-2.5" title="Reset Filter">
                            <i class="fas fa-redo"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- DATA TABLE CARD --}}
    <div class="card border-0 shadow-sm rounded-3 bg-white overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 12px;">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;" class="text-center">#</th>
                        <th>SPK &amp; Pemesan</th>
                        <th style="width: 100px;" class="text-center">Volume</th>
                        <th style="width: 140px;" class="text-end">Biaya Produksi</th>
                        <th style="width: 180px;">Sudah Dibayar</th>
                        <th style="width: 140px;" class="text-end">Sisa Tagihan</th>
                        <th style="width: 110px;" class="text-center">Status</th>
                        <th style="width: 130px;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($spks as $idx => $spk)
                        @php
                            $targetCost = $spk->total_biaya_produksi;
                            $paidCost = $spk->total_paid_production;
                            $remaining = $spk->remaining_production_cost;
                            $pct = $spk->production_payment_percentage;
                            $status = $spk->production_payment_status;
                            $isLunas = ($status === 'paid');
                        @endphp
                        <tr>
                            <td class="text-center text-muted fw-semibold">
                                {{ $spks->firstItem() + $idx }}
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-1.5 mb-0.5">
                                    <span class="font-monospace fw-bold text-dark" style="font-size: 12px;">
                                        {{ $spk->no_produksi ?: $spk->no_spk }}
                                    </span>
                                    @if ($spk->no_produksi && $spk->no_spk !== $spk->no_produksi)
                                        <small class="text-muted font-monospace">({{ $spk->no_spk }})</small>
                                    @endif
                                </div>
                                <div class="d-flex align-items-center gap-1">
                                    <span class="fw-semibold text-primary" style="font-size: 11.5px;">
                                        {{ $spk->pemesan ?: 'Pelanggan Umum' }}
                                    </span>
                                    @if ($spk->instansi)
                                        <span class="text-muted" style="font-size: 10.5px;">&bull; {{ $spk->instansi }}</span>
                                    @endif
                                </div>
                                <small class="text-muted d-block" style="font-size: 10.5px;">
                                    <i class="far fa-calendar-alt me-1"></i>{{ $spk->tanggal ? $spk->tanggal->format('d/m/Y') : '-' }}
                                </small>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary-subtle text-secondary fw-bold" style="font-size: 11px;">
                                    {{ number_format($spk->total_pcs) }} Pcs
                                </span>
                            </td>
                            <td class="text-end fw-bold text-dark font-monospace" style="font-size: 12.5px;">
                                Rp {{ number_format($targetCost, 0, ',', '.') }}
                            </td>
                            <td>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-bold text-success font-monospace" style="font-size: 11.5px;">
                                        Rp {{ number_format($paidCost, 0, ',', '.') }}
                                    </span>
                                    <span class="text-muted" style="font-size: 10px;">{{ $pct }}%</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar {{ $isLunas ? 'bg-success' : 'bg-primary' }}"
                                         role="progressbar"
                                         style="width: {{ $pct }}%;"
                                         aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                @if ($spk->payments->count() > 0)
                                    <small class="text-muted d-block mt-0.5" style="font-size: 10px;">
                                        {{ $spk->payments->count() }}x cicilan dicatat
                                    </small>
                                @endif
                            </td>
                            <td class="text-end fw-extrabold {{ $remaining > 0 ? 'text-danger' : 'text-muted' }} font-monospace" style="font-size: 12.5px;">
                                Rp {{ number_format($remaining, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                {!! $spk->production_payment_status_badge !!}
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center align-items-center gap-1">
                                    @if (!$isLunas && $targetCost > 0)
                                        <button type="button"
                                                class="btn btn-warning btn-sm py-1 px-2 text-dark fw-bold btn-record-pay"
                                                data-id="{{ $spk->id }}"
                                                data-code="{{ $spk->no_produksi ?: $spk->no_spk }}"
                                                data-pemesan="{{ $spk->pemesan ?: 'Pelanggan Umum' }}"
                                                data-target="{{ $targetCost }}"
                                                data-paid="{{ $paidCost }}"
                                                data-remaining="{{ $remaining }}"
                                                title="Catat Cicilan Pembayaran">
                                            <i class="fas fa-wallet me-1"></i>Cicil
                                        </button>
                                    @else
                                        <span class="badge bg-light text-muted border py-1.5 px-2" style="font-size: 10px;">
                                            <i class="fas fa-check text-success me-1"></i>Selesai
                                        </span>
                                    @endif

                                    <a href="{{ route('spks.show', $spk) }}"
                                       class="btn btn-outline-secondary btn-sm py-1 px-2"
                                       title="Buka Detail SPK">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fa-3x mb-2 d-block opacity-25"></i>
                                Tidak ditemukan data SPK yang sesuai dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($spks->hasPages())
            <div class="card-footer bg-white border-top py-2 px-3">
                {{ $spks->links() }}
            </div>
        @endif
    </div>

</div>

{{-- MODAL RECORD SPK INSTALLMENT PAYMENT --}}
<div class="modal fade" id="modalRecordSpkPayment" tabindex="-1" aria-labelledby="modalRecordSpkPaymentLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form id="formRecordSpkPayment" method="POST" action="">
                @csrf
                <div class="modal-header bg-warning text-dark py-3 px-4">
                    <div>
                        <h6 class="modal-title fw-bold mb-0" id="modalRecordSpkPaymentLabel">
                            <i class="fas fa-wallet me-1"></i> Catat Cicilan Pembayaran Produksi
                        </h6>
                        <small class="text-dark opacity-75" id="modalSpkSubtitle">SPK #</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4" style="background: #f8fafc;">
                    {{-- Summary Box --}}
                    <div class="bg-white rounded-3 p-3 border mb-3 shadow-2xs">
                        <div class="row g-2 text-center">
                            <div class="col-4 border-end">
                                <span class="d-block text-muted text-uppercase fw-bold" style="font-size: 9.5px;">Target Ongkos</span>
                                <span class="fw-bold text-dark font-monospace" id="modalTargetCost">Rp 0</span>
                            </div>
                            <div class="col-4 border-end">
                                <span class="d-block text-muted text-uppercase fw-bold" style="font-size: 9.5px;">Sudah Dibayar</span>
                                <span class="fw-bold text-success font-monospace" id="modalPaidCost">Rp 0</span>
                            </div>
                            <div class="col-4">
                                <span class="d-block text-muted text-uppercase fw-bold" style="font-size: 9.5px;">Sisa Tagihan</span>
                                <span class="fw-extrabold text-danger font-monospace" id="modalRemainingCost">Rp 0</span>
                            </div>
                        </div>
                    </div>

                    {{-- Form Fields --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark small mb-1">
                            Nominal Cicilan Pembayaran (Rp) <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light fw-bold">Rp</span>
                            <input type="number" name="amount" id="modalInputAmount"
                                   class="form-control fw-extrabold fs-6 text-end font-monospace"
                                   min="1" step="1" required placeholder="0">
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2" id="btnQuick50" style="font-size: 11px;">
                                    50%
                                </button>
                                <button type="button" class="btn btn-xs btn-outline-success py-0 px-2 fw-bold" id="btnQuickLunas" style="font-size: 11px;">
                                    Pelunasan (100%)
                                </button>
                            </div>
                            <small class="text-muted" style="font-size: 11px;">Maks: <span id="modalMaxAmountText">Rp 0</span></small>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark small mb-1">
                                Tanggal Pembayaran <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="payment_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark small mb-1">
                                Sumber Kas / Bank <span class="text-danger">*</span>
                            </label>
                            <select name="payment_source" class="form-select form-select-sm" required>
                                <option value="kas_besar" selected>Kas Besar (Main Cash)</option>
                                <option value="kas_kecil">Kas Kecil (Petty Cash)</option>
                                @if (isset($bankAccounts) && count($bankAccounts) > 0)
                                    <optgroup label="Rekening Bank">
                                        @foreach ($bankAccounts as $bank)
                                            <option value="{{ $bank->id }}">{{ $bank->bank_name }} - {{ $bank->account_number }}</option>
                                        @endforeach
                                    </optgroup>
                                @endif
                            </select>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark small mb-1">Penerima / Vendor / Penjahit</label>
                            <input type="text" name="recipient_name" id="modalRecipientInput"
                                   class="form-control form-control-sm"
                                   placeholder="Nama Vendor / Penjahit"
                                   list="tailorList">
                            <datalist id="tailorList">
                                @if (isset($tailors))
                                    @foreach ($tailors as $t)
                                        <option value="{{ $t->name }}">{{ $t->name }} ({{ $t->specialization ?? 'Penjahit' }})</option>
                                    @endforeach
                                @endif
                            </datalist>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark small mb-1">Jenis Ongkos</label>
                            <select name="payment_type" class="form-select form-select-sm">
                                <option value="biaya_produksi" selected>Ongkos Jasa Produksi</option>
                                <option value="jasa_jahit">Jasa Jahit</option>
                                <option value="jasa_potong">Jasa Potong</option>
                                <option value="jasa_sablon">Jasa Sablon / Print</option>
                                <option value="jasa_finishing">Finishing / Packing</option>
                                <option value="tambahan">Biaya Tambahan</option>
                                <option value="umum">Lainnya / Umum</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-semibold text-dark small mb-1">Catatan / Keterangan</label>
                        <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Catatan cicilan (opsional, misal: DP Jahit Tahap 1)..."></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light py-2 px-3 border-top">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-success fw-bold px-3">
                        <i class="fas fa-save me-1"></i>Simpan Cicilan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentRemaining = 0;

    // Trigger modal bayar cicilan
    document.querySelectorAll('.btn-record-pay').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const spkId = this.dataset.id;
            const spkCode = this.dataset.code;
            const pemesan = this.dataset.pemesan;
            const targetCost = parseFloat(this.dataset.target) || 0;
            const paidCost = parseFloat(this.dataset.paid) || 0;
            currentRemaining = parseFloat(this.dataset.remaining) || 0;

            const form = document.getElementById('formRecordSpkPayment');
            form.action = `/spks/${spkId}/payments`;

            document.getElementById('modalSpkSubtitle').textContent = `SPK #${spkCode} • ${pemesan}`;
            document.getElementById('modalTargetCost').textContent = 'Rp ' + targetCost.toLocaleString('id-ID');
            document.getElementById('modalPaidCost').textContent = 'Rp ' + paidCost.toLocaleString('id-ID');
            document.getElementById('modalRemainingCost').textContent = 'Rp ' + currentRemaining.toLocaleString('id-ID');
            document.getElementById('modalMaxAmountText').textContent = 'Rp ' + currentRemaining.toLocaleString('id-ID');

            const amountInput = document.getElementById('modalInputAmount');
            amountInput.max = currentRemaining;
            amountInput.value = currentRemaining; // Default to full remaining

            const modal = new bootstrap.Modal(document.getElementById('modalRecordSpkPayment'));
            modal.show();
        });
    });

    // Quick buttons
    document.getElementById('btnQuick50')?.addEventListener('click', function() {
        if (currentRemaining > 0) {
            document.getElementById('modalInputAmount').value = Math.round(currentRemaining * 0.5);
        }
    });

    document.getElementById('btnQuickLunas')?.addEventListener('click', function() {
        if (currentRemaining > 0) {
            document.getElementById('modalInputAmount').value = currentRemaining;
        }
    });
});
</script>
@endpush
@endsection
