@extends('v2.layouts.app')

@section('title', 'Form Setoran Hasil Penjualan ke Supplier V2')

@section('content')

{{-- ── Page Header ── --}}
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2 mb-0">
            <i class="bi bi-cash-stack text-success fs-5"></i> Form Setoran Hasil Penjualan ke Supplier
        </h1>
        <p class="text-muted small mb-0" style="font-size: 0.78rem;">Catat pembayaran setoran barang titipan/konsinyasi yang telah terjual kepada supplier.</p>
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
                    <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Contoh: Setoran hasil penjualan 80 pcs Celana SMA L...">{{ old('notes') }}</textarea>
                </div>

                <!-- Total Summary Box -->
                <div class="p-2.5 bg-light rounded-3 border mt-auto" style="padding: 10px 12px;">
                    <div class="d-flex justify-content-between align-items-center text-muted small mb-1">
                        <span>Total Qty Disetorkan:</span>
                        <strong id="total-qty-settle" class="text-dark font-monospace">0 PCS</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-dark small">Total Nilai Setoran:</span>
                        <strong id="total-amount-settle" class="text-success fs-6 fw-bold font-monospace">Rp 0</strong>
                    </div>
                </div>
            </div>
        </div>

        {{-- Kanan: Form Item Barang Terjual --}}
        <div class="col-12 col-lg-9">
            <div class="v2-card p-3 shadow-sm h-100 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-card-checklist text-success me-1.5"></i> Pilih Barang Terjual yang Disetorkan
                    </h6>
                    <span class="badge bg-light text-dark border fw-normal" style="font-size: 0.75rem;">
                        Total Available: {{ count($availableItems) }} Item
                    </span>
                </div>

                <div class="table-responsive flex-grow-1 mb-2" style="overflow-x: hidden; overflow-y: visible;">
                    <table class="table table-bordered align-middle m-0" id="itemsTable" style="font-size: 0.8rem; table-layout: fixed; width: 100%;">
                        <thead class="bg-light text-muted">
                            <tr>
                                <th style="width: 34%;">PENERIMAAN & PRODUK</th>
                                <th style="width: 9%;" class="text-center">QTY DITERIMA</th>
                                <th style="width: 9%;" class="text-center">SUDAH DISETOR</th>
                                <th style="width: 9%;" class="text-center">BELUM DISETOR</th>
                                <th style="width: 12%;" class="text-center">QTY DISETORKAN</th>
                                <th style="width: 13%;" class="text-end">HARGA TITIP (HPP)</th>
                                <th style="width: 14%;" class="text-end pe-2">SUBTOTAL SETORAN</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($availableItems as $index => $item)
                                <tr>
                                    <td class="ps-2 py-2">
                                        <input type="hidden" name="items[{{ $index }}][consignment_item_id]" value="{{ $item['consignment_item_id'] }}">
                                        <input type="hidden" name="items[{{ $index }}][master_product_id]" value="{{ $item['master_product_id'] }}">
                                        <input type="hidden" name="items[{{ $index }}][unit_cost_price]" value="{{ $item['unit_cost_price'] }}">

                                        <div class="fw-bold text-dark text-wrap" style="line-height: 1.25;">{{ $item['name'] }}</div>
                                        <div class="text-muted text-wrap" style="font-size: 0.72rem;">
                                            SKU: {{ $item['sku'] }} | Ref: {{ $item['ref_number'] }} ({{ $item['consignment_date'] }})
                                        </div>
                                    </td>
                                    <td class="text-center font-monospace py-2">
                                        {{ number_format($item['qty_received']) }}
                                    </td>
                                    <td class="text-center font-monospace text-success py-2">
                                        {{ number_format($item['qty_settled']) }}
                                    </td>
                                    <td class="text-center font-monospace text-danger fw-bold py-2">
                                        {{ number_format($item['qty_unsettled']) }}
                                    </td>
                                    <td class="py-2 px-1">
                                        <input type="number" name="items[{{ $index }}][qty_settled]"
                                            class="form-control form-control-sm text-center qty-settle-input px-1 font-monospace"
                                            value="{{ $item['qty_unsettled'] }}" min="0"
                                            max="{{ $item['qty_unsettled'] }}"
                                            data-cost="{{ $item['unit_cost_price'] }}"
                                            oninput="calculateTotalSetoran()">
                                    </td>
                                    <td class="text-end font-monospace py-2">
                                        Rp {{ number_format($item['unit_cost_price'], 0, ',', '.') }}
                                    </td>
                                    <td class="text-end pe-2 fw-bold text-success font-monospace py-2 row-subtotal">
                                        Rp 0
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="bi bi-info-circle fs-2 d-block mb-2 text-secondary"></i>
                                        Tidak ada tagihan barang penerimaan yang belum disetorkan untuk supplier ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center pt-2 border-top mt-auto">
                    <div class="d-flex align-items-center gap-2">
                        <span class="small text-muted fw-semibold">Total Setoran:</span>
                        <span id="footer-total-summary" class="fw-bold text-success font-monospace small">0 PCS | Rp 0</span>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('supplier_consignments.stock_card', ['supplier_id' => $selectedSupplierId]) }}" class="btn btn-sm text-white fw-semibold" style="background:#64748b; border:none;">
                            Batal
                        </a>
                        @if (count($availableItems) > 0)
                            <button type="submit" class="btn btn-sm text-white fw-semibold py-1.5 px-4" style="background:#16a34a; border:none;">
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

    function calculateTotalSetoran() {
        let totalQty = 0;
        let totalAmount = 0;

        document.querySelectorAll('.qty-settle-input').forEach(input => {
            const qty = parseFloat(input.value || 0);
            const cost = parseFloat(input.dataset.cost || 0);
            const subtotal = qty * cost;

            const row = input.closest('tr');
            const subtotalElem = row.querySelector('.row-subtotal');
            if (subtotalElem) {
                subtotalElem.innerText = 'Rp ' + Math.round(subtotal).toLocaleString('id-ID');
            }

            totalQty += qty;
            totalAmount += subtotal;
        });

        const formattedQty = totalQty.toLocaleString('id-ID') + ' PCS';
        const formattedAmount = 'Rp ' + Math.round(totalAmount).toLocaleString('id-ID');

        const qtyElem = document.getElementById('total-qty-settle');
        if (qtyElem) qtyElem.innerText = formattedQty;

        const amountElem = document.getElementById('total-amount-settle');
        if (amountElem) amountElem.innerText = formattedAmount;

        const footerSummary = document.getElementById('footer-total-summary');
        if (footerSummary) footerSummary.innerText = formattedQty + ' | ' + formattedAmount;
    }

    document.addEventListener('DOMContentLoaded', function() {
        const methodSelect = document.getElementById('payment_method');
        if (methodSelect) {
            toggleBank(methodSelect.value);
        }
        calculateTotalSetoran();
    });
</script>
@endpush
