@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1"><i class="bi bi-cash-stack text-success me-2"></i>Form Setoran Hasil Penjualan ke Supplier</h3>
            <p class="text-muted small mb-0">Catat pembayaran setoran barang titipan/konsinyasi yang telah terjual kepada supplier.</p>
        </div>
        <a href="{{ route('supplier_consignments.stock_card', ['supplier_id' => $selectedSupplierId]) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Kartu Stok
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <h6 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Terjadi Kesalahan:</h6>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('supplier_consignments.settlement.store') }}" method="POST" id="settlement-form">
        @csrf
        <div class="row g-4">
            <!-- Header Info -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white py-3">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-info-circle me-2 text-primary"></i>Informasi Setoran & Pembayaran</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-uppercase">No. Setoran</label>
                            <input type="text" class="form-control bg-light" value="{{ $settlementNumber }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-uppercase">Supplier <span class="text-danger">*</span></label>
                            <select name="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror" onchange="window.location.href='{{ route('supplier_consignments.settlement.create') }}?supplier_id='+this.value" required>
                                <option value="">-- Pilih Supplier --</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" {{ $selectedSupplierId == $supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-uppercase">Tanggal Setoran <span class="text-danger">*</span></label>
                            <input type="date" name="settlement_date" class="form-control @error('settlement_date') is-invalid @enderror" value="{{ old('settlement_date', date('Y-m-d')) }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-uppercase">Metode Pembayaran <span class="text-danger">*</span></label>
                            <select name="payment_method" id="payment_method" class="form-select" onchange="toggleBank(this.value)" required>
                                <option value="transfer" {{ old('payment_method') === 'transfer' ? 'selected' : '' }}>Transfer Bank</option>
                                <option value="cash" {{ old('payment_method') === 'cash' ? 'selected' : '' }}>Tunai / Kas</option>
                            </select>
                        </div>
                        <div class="mb-3" id="bank-container">
                            <label class="form-label fw-semibold small text-uppercase">Sumber Rekening Bank <span class="text-danger">*</span></label>
                            <select name="bank_account_id" class="form-select">
                                <option value="">-- Pilih Rekening --</option>
                                @foreach($bankAccounts as $bank)
                                    <option value="{{ $bank->id }}" {{ old('bank_account_id') == $bank->id ? 'selected' : '' }}>
                                        {{ $bank->bank_name }} - {{ $bank->account_number }} (a.n {{ $bank->account_name }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-uppercase">No. Ref Transfer / Giro</label>
                            <input type="text" name="reference_number" class="form-control" placeholder="Contoh: TRF-8823901" value="{{ old('reference_number') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-uppercase">Catatan / Keterangan</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Contoh: Setoran hasil penjualan 80 pcs Celana SMA L periode tgl 01...">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white py-3">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-card-checklist me-2 text-primary"></i>Pilih Barang Terjual yang Disetorkan</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="bg-light text-muted small text-uppercase fw-semibold">
                                    <tr>
                                        <th class="ps-3">Penerimaan & Produk</th>
                                        <th class="text-center">Terjual</th>
                                        <th class="text-center">Sudah Disetor</th>
                                        <th class="text-center">Belum Disetor</th>
                                        <th style="width: 15%;">Qty Disetorkan</th>
                                        <th class="text-end">Harga Titip (HPP)</th>
                                        <th class="text-end pe-3">Subtotal Setoran</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($availableItems as $index => $item)
                                        <tr>
                                            <td class="ps-3">
                                                <input type="hidden" name="items[{{ $index }}][consignment_item_id]" value="{{ $item['consignment_item_id'] }}">
                                                <input type="hidden" name="items[{{ $index }}][master_product_id]" value="{{ $item['master_product_id'] }}">
                                                <input type="hidden" name="items[{{ $index }}][unit_cost_price]" value="{{ $item['unit_cost_price'] }}">
                                                
                                                <div class="fw-bold text-dark">{{ $item['name'] }}</div>
                                                <div class="small text-muted">SKU: {{ $item['sku'] }} | Ref: {{ $item['ref_number'] }} ({{ $item['consignment_date'] }})</div>
                                            </td>
                                            <td class="text-center font-monospace">{{ number_format($item['qty_sold']) }}</td>
                                            <td class="text-center font-monospace text-success">{{ number_format($item['qty_settled']) }}</td>
                                            <td class="text-center font-monospace text-danger fw-bold">{{ number_format($item['qty_unsettled']) }}</td>
                                            <td>
                                                <input type="number" 
                                                       name="items[{{ $index }}][qty_settled]" 
                                                       class="form-control form-control-sm qty-settle-input" 
                                                       value="{{ $item['qty_unsettled'] }}" 
                                                       min="0" 
                                                       max="{{ $item['qty_unsettled'] > 0 ? $item['qty_unsettled'] : $item['qty_sold'] }}"
                                                       data-cost="{{ $item['unit_cost_price'] }}"
                                                       oninput="calculateTotalSetoran()">
                                            </td>
                                            <td class="text-end">Rp {{ number_format($item['unit_cost_price'], 0, ',', '.') }}</td>
                                            <td class="text-end pe-3 fw-bold text-success row-subtotal">Rp 0</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-5 text-muted">
                                                <i class="bi bi-info-circle fs-2 d-block mb-2"></i>
                                                Tidak ada tagihan barang terjual yang belum disetorkan untuk supplier ini.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="bg-light fw-bold">
                                    <tr>
                                        <td class="ps-3" colspan="4">TOTAL KESELURUHAN SETORAN SUPPLIER</td>
                                        <td id="total-qty-settle" class="text-center fs-6 text-primary">0 PCS</td>
                                        <td></td>
                                        <td id="total-amount-settle" class="text-end pe-3 fs-5 text-success">Rp 0</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    @if(count($availableItems) > 0)
                        <div class="card-footer bg-white py-3 text-end">
                            <button type="submit" class="btn btn-success px-4 shadow-sm">
                                <i class="bi bi-check-circle me-1"></i> Simpan & Lakukan Setoran Ke Supplier
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function toggleBank(method) {
    const bankContainer = document.getElementById('bank-container');
    if (method === 'transfer') {
        bankContainer.style.display = 'block';
    } else {
        bankContainer.style.display = 'none';
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
            subtotalElem.innerText = 'Rp ' + subtotal.toLocaleString('id-ID');
        }

        totalQty += qty;
        totalAmount += subtotal;
    });

    document.getElementById('total-qty-settle').innerText = totalQty.toLocaleString() + ' PCS';
    document.getElementById('total-amount-settle').innerText = 'Rp ' + totalAmount.toLocaleString('id-ID');
}

document.addEventListener('DOMContentLoaded', function() {
    calculateTotalSetoran();
});
</script>
@endsection
