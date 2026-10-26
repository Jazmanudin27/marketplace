@extends('v2.layouts.app')

@section('title', 'Edit Bukti Setoran Supplier V2')

@section('content')

{{-- ── Page Header ── --}}
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2 mb-0">
            <i class="bi bi-pencil-square text-warning fs-5"></i> Edit Bukti Setoran Supplier: {{ $settlement->settlement_number }}
        </h1>
        <p class="text-muted small mb-0" style="font-size: 0.78rem;">Ubah rincian pembayaran setoran hasil penjualan barang titipan/konsinyasi supplier.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('supplier_consignments.index') }}" class="btn btn-sm btn-outline-secondary py-1.5 px-3 fw-semibold">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Titipan Barang
        </a>
    </div>
</div>

{{-- ── Error Notification ── --}}
@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mb-3 border-0 shadow-sm" role="alert" style="border-radius:10px;">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>Terjadi Kesalahan:</strong>
        <ul class="mb-0 mt-1 ps-3 small">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<form action="{{ route('supplier_consignments.settlement.update', $settlement) }}" method="POST" id="settlement-form">
    @csrf
    @method('PUT')
    <div class="row g-3">
        {{-- Kiri: Form Informasi Setoran & Pembayaran --}}
        <div class="col-12 col-lg-3">
            <div class="v2-card p-3 shadow-sm h-100 d-flex flex-column">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-2">
                    <i class="bi bi-info-circle text-primary me-1.5"></i> Informasi Setoran &amp; Pembayaran
                </h6>

                <!-- No. Setoran -->
                <div class="mb-2">
                    <label class="form-label small fw-semibold text-dark mb-1">No. Setoran</label>
                    <input type="text" class="form-control form-control-sm bg-light font-monospace" value="{{ $settlement->settlement_number }}" readonly>
                </div>

                <!-- Supplier -->
                <div class="mb-2">
                    <label class="form-label small fw-semibold text-dark mb-1">Supplier</label>
                    <input type="text" class="form-control form-control-sm bg-light fw-bold" value="{{ $settlement->supplier ? $settlement->supplier->name : 'Supplier' }}" readonly>
                    <input type="hidden" name="supplier_id" value="{{ $settlement->supplier_id }}">
                </div>

                <!-- Tanggal Setoran -->
                <div class="mb-2">
                    <label class="form-label small fw-semibold text-dark mb-1">Tanggal Setoran <span class="text-danger">*</span></label>
                    <input type="date" name="settlement_date"
                        class="form-control form-control-sm @error('settlement_date') is-invalid @enderror"
                        value="{{ old('settlement_date', $settlement->settlement_date ? $settlement->settlement_date->format('Y-m-d') : date('Y-m-d')) }}" required>
                </div>

                <!-- Metode Pembayaran -->
                <div class="mb-2">
                    <label class="form-label small fw-semibold text-dark mb-1">Metode Pembayaran <span class="text-danger">*</span></label>
                    <select name="payment_method" id="payment_method" class="form-select form-select-sm" onchange="toggleBank(this.value)" required>
                        <option value="transfer" {{ old('payment_method', $settlement->payment_method) === 'transfer' ? 'selected' : '' }}>Transfer Bank</option>
                        <option value="cash" {{ old('payment_method', $settlement->payment_method) === 'cash' ? 'selected' : '' }}>Tunai / Kas</option>
                    </select>
                </div>

                <!-- Sumber Rekening Bank -->
                <div class="mb-2" id="bank-container">
                    <label class="form-label small fw-semibold text-dark mb-1">Sumber Rekening Bank <span class="text-danger">*</span></label>
                    <select name="bank_account_id" id="bank_account_id" class="form-select form-select-sm @error('bank_account_id') is-invalid @enderror">
                        <option value="">-- Pilih Rekening --</option>
                        @foreach ($bankAccounts as $bank)
                            <option value="{{ $bank->id }}" {{ old('bank_account_id', $settlement->bank_account_id) == $bank->id ? 'selected' : '' }}>
                                {{ $bank->bank_name }} - {{ $bank->account_number }} (a.n {{ $bank->account_name }})
                            </option>
                        @endforeach
                    </select>
                    @error('bank_account_id')
                        <div class="invalid-feedback small">{{ $message }}</div>
                    @enderror
                </div>

                <!-- No. Ref Transfer -->
                <div class="mb-2">
                    <label class="form-label small fw-semibold text-dark mb-1">No. Ref Transfer / Giro</label>
                    <input type="text" name="reference_number" class="form-control form-control-sm" placeholder="Contoh: TRF-8823901" value="{{ old('reference_number', $settlement->reference_number) }}">
                </div>

                <!-- Catatan -->
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark mb-1">Catatan / Keterangan</label>
                    <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Catatan bukti setoran">{{ old('notes', $settlement->notes) }}</textarea>
                </div>

                <!-- Total Summary Box -->
                <div class="p-2.5 bg-light rounded-3 border mt-auto" style="padding: 10px 12px;">
                    <div class="d-flex justify-content-between align-items-center text-muted small mb-1">
                        <span>Invoice Dibayar:</span>
                        <strong id="total-invoices-settle" class="text-dark font-monospace">0 Dokumen</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-dark small">Total Nominal Setoran:</span>
                        <strong id="total-amount-settle" class="text-success fs-6 fw-bold font-monospace">Rp 0</strong>
                    </div>
                </div>
            </div>
        </div>

        {{-- Kanan: Form Daftar Invoice Titipan / Dokumen Penerimaan --}}
        <div class="col-12 col-lg-9">
            <div class="v2-card p-3 shadow-sm h-100 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom pb-2 mb-2">
                    <div>
                        <h6 class="fw-bold text-dark mb-0">
                            <i class="bi bi-receipt text-success me-1.5"></i> Alokasi Nominal Per Invoice / Dokumen Penerimaan
                        </h6>
                        <small class="text-muted" style="font-size: 0.72rem;">Ubah nominal yang disetorkan pada masing-masing dokumen titipan.</small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-light text-dark border fw-normal" style="font-size: 0.75rem;">
                            Total: {{ count($availableInvoices) }} Dokumen
                        </span>
                        <button type="button" class="btn btn-xs btn-outline-success py-1 px-2.5 fw-semibold" style="font-size: 0.72rem;" onclick="bayarSemuaPenuh()">
                            <i class="bi bi-check2-all me-1"></i> Setor Semua Lunas
                        </button>
                    </div>
                </div>

                <div class="table-responsive flex-grow-1 mb-2">
                    <table class="table table-bordered table-hover align-middle m-0" id="invoicesTable" style="font-size: 0.8rem; width: 100%;">
                        <thead class="bg-light text-muted">
                            <tr>
                                <th style="width: 28%;">NO. INVOICE / INPUTAN TITIPAN</th>
                                <th style="width: 10%;" class="text-center">QTY TOTAL</th>
                                <th style="width: 15%;" class="text-end">NILAI TITIPAN (HPP)</th>
                                <th style="width: 13%;" class="text-end">SUDAH DISETOR LAIN</th>
                                <th style="width: 14%;" class="text-end">MAX TAGIHAN</th>
                                <th style="width: 20%;" class="text-end pe-2">NOMINAL DISETORKAN (RP)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($availableInvoices as $index => $inv)
                                @php
                                    $prevVal = old("invoices.{$inv['consignment_id']}.amount", $inv['current_paid'] > 0 ? number_format($inv['current_paid'], 0, ',', '.') : '');
                                @endphp
                                <tr>
                                    <td class="ps-2 py-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="fw-bold text-primary font-monospace" style="font-size: 0.85rem;">
                                                {{ $inv['reference_number'] }}
                                            </span>
                                        </div>
                                        <div class="text-muted d-flex align-items-center gap-2 mt-0.5" style="font-size: 0.72rem;">
                                            <span><i class="bi bi-calendar3 me-1"></i>{{ $inv['consignment_date'] }}</span>
                                            <span>•</span>
                                            <span>{{ $inv['items_count'] }} Jenis Produk</span>
                                        </div>
                                    </td>
                                    <td class="text-center font-monospace py-2">
                                        {{ number_format($inv['total_qty']) }} PCS
                                    </td>
                                    <td class="text-end font-monospace py-2">
                                        Rp {{ number_format($inv['total_amount'], 0, ',', '.') }}
                                    </td>
                                    <td class="text-end font-monospace text-muted py-2">
                                        Rp {{ number_format($inv['settled_amount'], 0, ',', '.') }}
                                    </td>
                                    <td class="text-end font-monospace text-danger fw-bold py-2">
                                        Rp {{ number_format($inv['remaining_amount'], 0, ',', '.') }}
                                    </td>
                                    <td class="text-end pe-2 py-2">
                                        <div class="d-flex align-items-center gap-1 justify-content-end">
                                            <input type="text"
                                                name="invoices[{{ $inv['consignment_id'] }}][amount]"
                                                id="amount_input_{{ $inv['consignment_id'] }}"
                                                class="form-control form-control-sm text-end font-monospace invoice-pay-input fw-bold text-success number-separator"
                                                placeholder="0"
                                                value="{{ $prevVal }}"
                                                data-remaining="{{ $inv['remaining_amount'] }}"
                                                data-id="{{ $inv['consignment_id'] }}"
                                                oninput="calculateTotalNominal()"
                                                style="max-width: 130px; font-size: 0.82rem;">
                                            <button type="button"
                                                class="btn btn-xs btn-outline-primary py-1 px-1.5 fw-semibold"
                                                title="Setor Lunas Invoice Ini"
                                                style="font-size: 0.68rem; white-space: nowrap;"
                                                onclick="setPayFull({{ $inv['consignment_id'] }}, {{ $inv['remaining_amount'] }})">
                                                Penuh
                                            </button>
                                            <button type="button"
                                                class="btn btn-xs btn-outline-secondary py-1 px-1"
                                                title="Kosongkan"
                                                style="font-size: 0.68rem;"
                                                onclick="clearPay({{ $inv['consignment_id'] }})">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="bi bi-receipt-cutoff fs-2 d-block mb-2 text-secondary"></i>
                                        Tidak ada dokumen penerimaan titipan untuk supplier ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Footer Tombol Simpan --}}
                <div class="d-flex justify-content-between align-items-center pt-3 border-top mt-auto">
                    <div>
                        <span class="small text-muted">Total Alokasi Setoran:</span>
                        <strong class="text-success font-monospace ms-1 fs-6" id="footer-total-summary">0 Dokumen | Rp 0</strong>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('supplier_consignments.index') }}" class="btn btn-sm btn-outline-secondary px-3 fw-semibold">
                            Batal
                        </a>
                        <button type="submit" class="btn btn-sm btn-warning text-dark px-4 fw-bold shadow-sm">
                            <i class="bi bi-check2-circle me-1"></i> Simpan Perubahan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection

@push('scripts')
<script>
    function toggleBank(method) {
        const bankContainer = document.getElementById('bank-container');
        if (bankContainer) {
            if (method === 'transfer') {
                bankContainer.style.display = 'block';
            } else {
                bankContainer.style.display = 'none';
            }
        }
    }

    function parseRupiahNumber(val) {
        if (!val) return 0;
        const clean = val.toString().replace(/[^0-9]/g, '');
        return clean ? parseFloat(clean) : 0;
    }

    function formatRupiahNumberText(num) {
        return 'Rp ' + Math.round(num).toLocaleString('id-ID');
    }

    function calculateTotalNominal() {
        let totalNominal = 0;
        let invoiceCount = 0;

        document.querySelectorAll('.invoice-pay-input').forEach(input => {
            const nominal = parseRupiahNumber(input.value);
            if (nominal > 0) {
                totalNominal += nominal;
                invoiceCount++;
            }
        });

        const formattedCount = invoiceCount.toLocaleString('id-ID') + ' Dokumen';
        const formattedAmount = formatRupiahNumberText(totalNominal);

        const countElem = document.getElementById('total-invoices-settle');
        if (countElem) countElem.innerText = formattedCount;

        const amountElem = document.getElementById('total-amount-settle');
        if (amountElem) amountElem.innerText = formattedAmount;

        const footerSummary = document.getElementById('footer-total-summary');
        if (footerSummary) footerSummary.innerText = formattedCount + ' | ' + formattedAmount;
    }

    function setPayFull(consignmentId, maxAmount) {
        const input = document.getElementById('amount_input_' + consignmentId);
        if (input) {
            input.value = new Intl.NumberFormat('id-ID').format(Math.round(maxAmount));
            calculateTotalNominal();
        }
    }

    function clearPay(consignmentId) {
        const input = document.getElementById('amount_input_' + consignmentId);
        if (input) {
            input.value = '';
            calculateTotalNominal();
        }
    }

    function bayarSemuaPenuh() {
        document.querySelectorAll('.invoice-pay-input').forEach(input => {
            const remaining = parseFloat(input.dataset.remaining || 0);
            if (remaining > 0) {
                input.value = new Intl.NumberFormat('id-ID').format(Math.round(remaining));
            }
        });
        calculateTotalNominal();
    }

    document.addEventListener('DOMContentLoaded', function() {
        const methodSelect = document.getElementById('payment_method');
        if (methodSelect) {
            toggleBank(methodSelect.value);
        }
        calculateTotalNominal();

        const form = document.getElementById('settlement-form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const method = document.getElementById('payment_method')?.value;
                const bankSelect = document.getElementById('bank_account_id');
                if (method === 'transfer' && bankSelect && !bankSelect.value) {
                    e.preventDefault();
                    alert('Silakan pilih Sumber Rekening Bank terlebih dahulu untuk metode Transfer Bank.');
                    bankSelect.focus();
                    return false;
                }

                let totalNominal = 0;
                document.querySelectorAll('.invoice-pay-input').forEach(input => {
                    totalNominal += parseRupiahNumber(input.value);
                });

                if (totalNominal <= 0) {
                    e.preventDefault();
                    alert('Nominal pembayaran setoran masih Rp 0. Masukkan nominal yang ingin disetorkan pada minimal satu dokumen titipan.');
                    return false;
                }
            });
        }
    });
</script>
@endpush
