@extends('layouts.mobile')

@section('title', 'Input Setoran Titipan')
@section('header-title', 'Bayar Titipan')

@section('content')
<div class="mb-3">
    <a href="{{ route('mobile.owner.titipan_settlement') }}" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1 mb-1">
        <i class="fas fa-arrow-left"></i> Kembali ke Daftar
    </a>
    <h5 class="fw-bold text-dark mb-0">Input Pembayaran Titipan</h5>
    <div class="text-muted small">Setor hasil penjualan titipan supplier & otomatis tercatat ke mutasi kas.</div>
</div>

<form action="{{ route('mobile.owner.titipan_settlement.store') }}" method="POST" id="mobile-settlement-form">
    @csrf

    <!-- Info Transaksi Card -->
    <div class="glass-card p-3 mb-3" style="background: #ffffff; border-radius: 14px; border: 1px solid rgba(0,0,0,0.06);">
        <div class="mb-2">
            <label class="form-label small fw-bold text-muted text-uppercase" style="font-size: 0.68rem;">Pilih Supplier</label>
            <select name="supplier_id" id="supplier_select" class="form-select form-select-sm" required onchange="window.location.href='{{ route('mobile.owner.titipan_settlement.create') }}?supplier_id='+this.value">
                <option value="">-- Pilih Supplier --</option>
                @foreach($suppliers as $sup)
                    <option value="{{ $sup->id }}" {{ $selectedSupplierId == $sup->id ? 'selected' : '' }}>
                        {{ $sup->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="row g-2 mb-2">
            <div class="col-6">
                <label class="form-label small fw-bold text-muted text-uppercase" style="font-size: 0.68rem;">No. Setoran</label>
                <input type="text" class="form-control form-control-sm bg-light font-monospace" value="{{ $settlementNumber }}" readonly>
            </div>
            <div class="col-6">
                <label class="form-label small fw-bold text-muted text-uppercase" style="font-size: 0.68rem;">Tanggal Setor</label>
                <input type="date" name="settlement_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
            </div>
        </div>

        <div class="row g-2 mb-2">
            <div class="col-6">
                <label class="form-label small fw-bold text-muted text-uppercase" style="font-size: 0.68rem;">Metode Bayar</label>
                <select name="payment_method" id="payment_method" class="form-select form-select-sm" required onchange="toggleBankSelect()">
                    <option value="transfer">Transfer Bank</option>
                    <option value="cash">Tunai / Kas</option>
                </select>
            </div>
            <div class="col-6" id="bank_account_wrapper">
                <label class="form-label small fw-bold text-muted text-uppercase" style="font-size: 0.68rem;">Rekening Sumber</label>
                <select name="bank_account_id" id="bank_account_id" class="form-select form-select-sm">
                    <option value="">Pilih Bank</option>
                    @foreach($bankAccounts as $bank)
                        <option value="{{ $bank->id }}">{{ $bank->bank_name }} - {{ $bank->account_number }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mb-2">
            <label class="form-label small fw-bold text-muted text-uppercase" style="font-size: 0.68rem;">No. Referensi / Bukti Transfer (Opsional)</label>
            <input type="text" name="reference_number" class="form-control form-control-sm" placeholder="Contoh: TRF-20261020-001">
        </div>

        <div>
            <label class="form-label small fw-bold text-muted text-uppercase" style="font-size: 0.68rem;">Catatan (Opsional)</label>
            <input type="text" name="notes" class="form-control form-control-sm" placeholder="Catatan pembayaran...">
        </div>
    </div>

    <!-- Daftar Item Titipan yang Harus Disetor -->
    <div class="mb-2 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold text-dark mb-0" style="font-size: 0.85rem;">Item Titipan Terjual (Belum Disetor)</h6>
        <span class="badge bg-secondary bg-opacity-10 text-secondary border" style="font-size: 0.65rem;">
            {{ count($availableItems) }} Item Tersedia
        </span>
    </div>

    @if(empty($availableItems))
        <div class="glass-card p-4 text-center mb-4" style="background: #ffffff; border-radius: 14px; border: 1px dashed #cbd5e1;">
            <i class="fas fa-check-circle text-success fs-1 opacity-50 mb-2 d-block"></i>
            <div class="fw-bold text-dark">Tidak ada tagihan titipan pending</div>
            <div class="text-muted small mt-1">Semua barang titipan supplier ini sudah disetorkan atau belum ada penjualan baru.</div>
        </div>
    @else
        <div class="d-flex flex-column gap-2 mb-4">
            @foreach($availableItems as $idx => $item)
                <div class="glass-card p-3 item-settle-card" style="background: #ffffff; border-radius: 14px; border: 1px solid rgba(0,0,0,0.06);">
                    <input type="hidden" name="items[{{ $idx }}][consignment_item_id]" value="{{ $item['consignment_item_id'] }}">
                    <input type="hidden" name="items[{{ $idx }}][master_product_id]" value="{{ $item['master_product_id'] }}">
                    <input type="hidden" name="items[{{ $idx }}][unit_cost_price]" value="{{ $item['unit_cost_price'] }}" class="unit-cost-input">

                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div class="fw-bold text-dark" style="font-size: 0.88rem;">{{ $item['name'] }}</div>
                            <small class="text-muted font-monospace" style="font-size: 0.72rem;">SKU: {{ $item['sku'] }} • Ref: {{ $item['ref_number'] }}</small>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-light text-dark border" style="font-size: 0.68rem;">HPP Rp {{ number_format($item['unit_cost_price'], 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                        <div class="small text-muted" style="font-size: 0.75rem;">
                            Belum disetor: <strong class="text-danger">{{ $item['qty_unsettled'] }}</strong> pcs
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <label class="small text-muted fw-bold mb-0" style="font-size: 0.72rem;">Setor:</label>
                            <input type="number" name="items[{{ $idx }}][qty_settled]"
                                   class="form-control form-control-sm text-center fw-bold qty-settle-input"
                                   style="width: 75px; border-radius: 8px;"
                                   min="0" max="{{ $item['qty_unsettled'] }}"
                                   value="{{ $item['qty_unsettled'] }}"
                                   data-cost="{{ $item['unit_cost_price'] }}"
                                   oninput="calculateTotal()">
                        </div>
                    </div>
                    <div class="text-end mt-2 pt-1 border-top border-light" style="font-size: 0.75rem;">
                        Subtotal: <strong class="text-primary item-subtotal">Rp {{ number_format($item['qty_unsettled'] * $item['unit_cost_price'], 0, ',', '.') }}</strong>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Sticky Bottom Summary Bar -->
        <div class="fixed-bottom p-3 bg-white border-top shadow-lg" style="z-index: 1040; border-top-left-radius: 18px; border-top-right-radius: 18px;">
            <div class="container d-flex justify-content-between align-items-center mb-2">
                <div>
                    <div class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Total Setor Titipan</div>
                    <div class="fw-bold fs-5 text-success" id="grand_total_text">Rp 0</div>
                </div>
                <div class="text-end text-muted small" style="font-size: 0.75rem;">
                    Total: <strong id="total_qty_text">0</strong> pcs
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2 rounded-pill fw-bold shadow d-flex justify-content-center align-items-center gap-2" style="background: linear-gradient(135deg, #4f46e5, #4338ca); border:none;">
                <i class="fas fa-check-circle"></i> Konfirmasi Bayar & Mutasi Kas
            </button>
        </div>
        <div style="height: 90px;"></div>
    @endif
</form>

<script>
function toggleBankSelect() {
    const method = document.getElementById('payment_method').value;
    const bankWrapper = document.getElementById('bank_account_wrapper');
    const bankInput = document.getElementById('bank_account_id');
    if (method === 'cash') {
        bankWrapper.style.display = 'none';
        bankInput.removeAttribute('required');
        bankInput.value = '';
    } else {
        bankWrapper.style.display = 'block';
        bankInput.setAttribute('required', 'required');
    }
}

function calculateTotal() {
    let grandTotal = 0;
    let totalQty = 0;

    const qtyInputs = document.querySelectorAll('.qty-settle-input');
    qtyInputs.forEach(input => {
        const qty = parseInt(input.value) || 0;
        const cost = parseFloat(input.getAttribute('data-cost')) || 0;
        const subtotal = qty * cost;

        const card = input.closest('.item-settle-card');
        if (card) {
            const subtotalEl = card.querySelector('.item-subtotal');
            if (subtotalEl) {
                subtotalEl.innerText = 'Rp ' + subtotal.toLocaleString('id-ID');
            }
        }

        grandTotal += subtotal;
        totalQty += qty;
    });

    const grandTotalEl = document.getElementById('grand_total_text');
    const totalQtyEl = document.getElementById('total_qty_text');
    if (grandTotalEl) grandTotalEl.innerText = 'Rp ' + grandTotal.toLocaleString('id-ID');
    if (totalQtyEl) totalQtyEl.innerText = totalQty;
}

document.addEventListener('DOMContentLoaded', function() {
    toggleBankSelect();
    calculateTotal();
});
</script>
@endsection
