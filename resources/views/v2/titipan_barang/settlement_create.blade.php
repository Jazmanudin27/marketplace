@extends('v2.layouts.app')

@section('title', 'Form Setoran Hasil Penjualan ke Supplier V2')

@section('content')

{{-- ── Page Header ── --}}
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2 mb-0">
            <i class="bi bi-cash-stack text-success fs-5"></i> Form Setoran Hasil Penjualan ke Supplier
        </h1>
        <p class="text-muted small mb-0" style="font-size: 0.78rem;">Catat pembayaran setoran barang titipan/konsinyasi berbasis nominal per invoice atau dokumen titipan.</p>
    </div>
    <div>
        <a href="{{ route('supplier_consignments.stock_card', ['supplier_id' => $selectedSupplierId]) }}" class="btn btn-sm text-white py-1.5 px-3 fw-semibold" style="background:#64748b; border:none;">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Kartu Stok
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

<form action="{{ route('supplier_consignments.settlement.store') }}" method="POST" id="settlement-form">
    @csrf
    <div class="row g-3">
        {{-- Kiri: Form Informasi Setoran & Pembayaran --}}
        <div class="col-12 col-lg-3">
            <div class="v2-card p-3 shadow-sm h-100 d-flex flex-column">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-2">
                    <i class="bi bi-info-circle text-primary me-1.5"></i> Informasi Setoran & Pembayaran
                </h6>

                <!-- No. Setoran -->
                <div class="mb-2">
                    <label class="form-label small fw-semibold text-dark mb-1">No. Setoran</label>
                    <input type="text" class="form-control form-control-sm bg-light font-monospace" value="{{ $settlementNumber }}" readonly>
                </div>

                <!-- Supplier -->
                <div class="mb-2">
                    <label class="form-label small fw-semibold text-dark mb-1">Supplier <span class="text-danger">*</span></label>
                    <select name="supplier_id"
                        class="form-select form-select-sm @error('supplier_id') is-invalid @enderror"
                        onchange="window.location.href='{{ route('supplier_consignments.settlement.create') }}?supplier_id='+this.value"
                        required>
                        <option value="">-- Pilih Supplier --</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ $selectedSupplierId == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Tanggal Setoran -->
                <div class="mb-2">
                    <label class="form-label small fw-semibold text-dark mb-1">Tanggal Setoran <span class="text-danger">*</span></label>
                    <input type="date" name="settlement_date"
                        class="form-control form-control-sm @error('settlement_date') is-invalid @enderror"
                        value="{{ old('settlement_date', date('Y-m-d')) }}" required>
                </div>

                <!-- Metode Pembayaran -->
                <div class="mb-2">
                    <label class="form-label small fw-semibold text-dark mb-1">Metode Pembayaran <span class="text-danger">*</span></label>
                    <select name="payment_method" id="payment_method" class="form-select form-select-sm" onchange="toggleBank(this.value)" required>
                        <option value="transfer" {{ old('payment_method', 'transfer') === 'transfer' ? 'selected' : '' }}>Transfer Bank</option>
                        <option value="cash" {{ old('payment_method') === 'cash' ? 'selected' : '' }}>Tunai / Kas</option>
                    </select>
                </div>

                <!-- Sumber Rekening Bank -->
                <div class="mb-2" id="bank-container">
                    <label class="form-label small fw-semibold text-dark mb-1">Sumber Rekening Bank <span class="text-danger">*</span></label>
                    <select name="bank_account_id" class="form-select form-select-sm">
                        <option value="">-- Pilih Rekening --</option>
                        @foreach ($bankAccounts as $bank)
                            <option value="{{ $bank->id }}" {{ old('bank_account_id') == $bank->id ? 'selected' : '' }}>
                                {{ $bank->bank_name }} - {{ $bank->account_number }} (a.n {{ $bank->account_name }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- No. Ref Transfer -->
                <div class="mb-2">
                    <label class="form-label small fw-semibold text-dark mb-1">No. Ref Transfer / Giro</label>
                    <input type="text" name="reference_number" class="form-control form-control-sm" placeholder="Contoh: TRF-8823901" value="{{ old('reference_number') }}">
                </div>

                <!-- Catatan -->
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark mb-1">Catatan / Keterangan</label>
                    <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Contoh: Pembayaran setoran nota titipan KNS-...">{{ old('notes') }}</textarea>
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
                            <i class="bi bi-receipt text-success me-1.5"></i> Pilih Invoice / Dokumen Penerimaan Titipan
                        </h6>
                        <small class="text-muted" style="font-size: 0.72rem;">Masukkan nominal yang disetorkan pada masing-masing dokumen titipan.</small>
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
                                <th style="width: 13%;" class="text-end">SUDAH DISETOR</th>
                                <th style="width: 14%;" class="text-end">SISA TAGIHAN</th>
                                <th style="width: 20%;" class="text-end pe-2">NOMINAL DISETORKAN (RP)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($availableInvoices as $index => $inv)
                                <tr class="{{ $inv['is_paid_off'] ? 'bg-light opacity-75' : '' }}">
                                    <td class="ps-2 py-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="fw-bold text-primary font-monospace" style="font-size: 0.85rem;">
                                                {{ $inv['reference_number'] }}
                                            </span>
                                            @if($inv['is_paid_off'])
                                                <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.62rem;">Lunas</span>
                                            @endif
                                        </div>
                                        <div class="text-muted d-flex align-items-center gap-2 mt-0.5" style="font-size: 0.72rem;">
                                            <span><i class="bi bi-calendar3 me-1"></i>{{ $inv['consignment_date'] }}</span>
                                            <span>•</span>
                                            <span>{{ $inv['items_count'] }} Jenis Produk</span>
                                        </div>
                                        @if($inv['notes'] !== '-')
                                            <div class="text-muted small fst-italic text-truncate" style="max-width: 280px; font-size: 0.7rem;">
                                                "{{ $inv['notes'] }}"
                                            </div>
                                        @endif
                                        {{-- Collapsible Preview Barang --}}
                                        <div class="mt-1">
                                            <a class="text-decoration-none fw-semibold text-secondary" style="font-size: 0.68rem;" data-bs-toggle="collapse" href="#itemsCollapse{{ $inv['consignment_id'] }}" role="button" aria-expanded="false">
                                                <i class="bi bi-box-seam me-1"></i> Rincian Produk ({{ $inv['items_count'] }}) <i class="bi bi-chevron-down ms-0.5" style="font-size: 0.6rem;"></i>
                                            </a>
                                            <div class="collapse mt-1" id="itemsCollapse{{ $inv['consignment_id'] }}">
                                                <div class="p-2 bg-light border rounded" style="font-size: 0.72rem; max-height: 180px; overflow-y: auto;">
                                                    <table class="table table-sm table-borderless m-0" style="font-size: 0.7rem;">
                                                        <thead>
                                                            <tr class="text-muted border-bottom">
                                                                <th>Produk</th>
                                                                <th class="text-center">Qty</th>
                                                                <th class="text-end">HPP</th>
                                                                <th class="text-end">Subtotal</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($inv['items'] as $itemPreview)
                                                                <tr>
                                                                    <td class="py-0.5 text-truncate" style="max-width: 140px;">
                                                                        <span class="fw-semibold text-dark">{{ $itemPreview['name'] }}</span>
                                                                        <span class="text-muted d-block font-monospace" style="font-size: 0.65rem;">{{ $itemPreview['sku'] }}</span>
                                                                    </td>
                                                                    <td class="py-0.5 text-center font-monospace">{{ number_format($itemPreview['qty']) }}</td>
                                                                    <td class="py-0.5 text-end font-monospace">Rp {{ number_format($itemPreview['unit_cost_price'], 0, ',', '.') }}</td>
                                                                    <td class="py-0.5 text-end font-monospace fw-semibold">Rp {{ number_format($itemPreview['subtotal'], 0, ',', '.') }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center font-monospace py-2">
                                        {{ number_format($inv['total_qty']) }} PCS
                                    </td>
                                    <td class="text-end font-monospace py-2">
                                        Rp {{ number_format($inv['total_amount'], 0, ',', '.') }}
                                    </td>
                                    <td class="text-end font-monospace text-success py-2">
                                        Rp {{ number_format($inv['settled_amount'], 0, ',', '.') }}
                                    </td>
                                    <td class="text-end font-monospace {{ $inv['remaining_amount'] > 0 ? 'text-danger fw-bold' : 'text-muted' }} py-2">
                                        Rp {{ number_format($inv['remaining_amount'], 0, ',', '.') }}
                                    </td>
                                    <td class="text-end pe-2 py-2">
                                        @if(!$inv['is_paid_off'])
                                            <div class="d-flex align-items-center gap-1 justify-content-end">
                                                <input type="text"
                                                    name="invoices[{{ $inv['consignment_id'] }}][amount]"
                                                    id="amount_input_{{ $inv['consignment_id'] }}"
                                                    class="form-control form-control-sm text-end font-monospace invoice-pay-input fw-bold text-success number-separator"
                                                    placeholder="0"
                                                    value="{{ old("invoices.{$inv['consignment_id']}.amount") }}"
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
                                        @else
                                            <span class="text-muted small fst-italic">Sudah Lunas</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="bi bi-receipt-cutoff fs-2 d-block mb-2 text-secondary"></i>
                                        Tidak ada dokumen penerimaan titipan untuk supplier yang dipilih.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center pt-2 border-top mt-auto">
                    <div class="d-flex align-items-center gap-2">
                        <span class="small text-muted fw-semibold">Total Setoran:</span>
                        <span id="footer-total-summary" class="fw-bold text-success font-monospace small">0 Dokumen | Rp 0</span>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('supplier_consignments.stock_card', ['supplier_id' => $selectedSupplierId]) }}" class="btn btn-sm text-white fw-semibold" style="background:#64748b; border:none;">
                            Batal
                        </a>
                        @if (count($availableInvoices) > 0)
                            <button type="submit" class="btn btn-sm text-white fw-semibold py-1.5 px-4" style="background:#16a34a; border:none;" id="btn-submit-settle">
                                <i class="bi bi-check-circle me-1"></i> Simpan & Lakukan Setoran Ke Supplier
                            </button>
                        @endif
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
    });
</script>
@endpush
