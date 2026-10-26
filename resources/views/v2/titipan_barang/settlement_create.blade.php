@extends('v2.layouts.app')

@section('title', 'Form Pembayaran Setoran ke Supplier V2')

@section('content')

{{-- ── Page Header ── --}}
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2 mb-0">
            <i class="bi bi-cash-stack text-success fs-5"></i> Form Pembayaran Setoran ke Supplier
        </h1>
        <p class="text-muted small mb-0" style="font-size: 0.78rem;">Catat pembayaran setoran barang titipan kepada supplier.</p>
    </div>
    <div>
        <a href="{{ route('v2.titipan_barang.index') }}" class="btn btn-sm text-white py-1.5 px-3 fw-semibold" style="background:#64748b; border:none;">
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

<div class="row justify-content-center">
    <div class="col-12 col-md-9 col-lg-7 col-xl-6">
        <div class="v2-card p-4 shadow-sm">
            <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">
                <i class="bi bi-credit-card text-success me-2"></i>Input Pembayaran Setoran
            </h5>

            <form action="{{ route('supplier_consignments.settlement.store') }}" method="POST" id="settlement-form">
                @csrf

                <!-- Supplier -->
                <div class="mb-3">
                    <label class="form-label small fw-bold text-dark mb-1">Supplier <span class="text-danger">*</span></label>
                    <select name="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror" required>
                        <option value="">-- Pilih Supplier --</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ $selectedSupplierId == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Tanggal Setoran -->
                <div class="mb-3">
                    <label class="form-label small fw-bold text-dark mb-1">Tanggal Bayar <span class="text-danger">*</span></label>
                    <input type="date" name="settlement_date"
                        class="form-control @error('settlement_date') is-invalid @enderror"
                        value="{{ old('settlement_date', date('Y-m-d')) }}" required>
                </div>

                <!-- Bayar Pakai Apa (Metode Pembayaran) -->
                <div class="mb-3">
                    <label class="form-label small fw-bold text-dark mb-1">Bayar Pakai Apa <span class="text-danger">*</span></label>
                    <select name="payment_method" id="payment_method" class="form-select @error('payment_method') is-invalid @enderror" onchange="toggleBank(this.value)" required>
                        <option value="transfer" {{ old('payment_method', 'transfer') === 'transfer' ? 'selected' : '' }}>Transfer Bank</option>
                        <option value="cash" {{ old('payment_method') === 'cash' ? 'selected' : '' }}>Tunai / Kas</option>
                    </select>
                </div>

                <!-- Sumber Rekening Bank (Ditampilkan jika Transfer Bank) -->
                <div class="mb-3" id="bank-container">
                    <label class="form-label small fw-bold text-dark mb-1">Ditransfer Melalui Rekening Bank <span class="text-danger">*</span></label>
                    <select name="bank_account_id" id="bank_account_id" class="form-select @error('bank_account_id') is-invalid @enderror">
                        <option value="">-- Pilih Rekening Bank --</option>
                        @foreach ($bankAccounts as $bank)
                            <option value="{{ $bank->id }}" {{ old('bank_account_id') == $bank->id ? 'selected' : '' }}>
                                {{ $bank->bank_name }} - {{ $bank->account_number }} (a.n {{ $bank->account_name }})
                            </option>
                        @endforeach
                    </select>
                    @error('bank_account_id')
                        <div class="invalid-feedback small">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Jumlah Bayar -->
                <div class="mb-3">
                    <label class="form-label small fw-bold text-success mb-1">Jumlah Bayar (Rp) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light fw-bold text-success font-monospace">Rp</span>
                        <input type="text"
                            name="total_amount"
                            id="total_amount"
                            class="form-control form-control-lg fw-bold text-success font-monospace @error('total_amount') is-invalid @enderror"
                            placeholder="0"
                            value="{{ old('total_amount') }}"
                            autocomplete="off"
                            required
                            style="font-size: 1.25rem;"
                            oninput="formatRupiahInput(this)">
                    </div>
                    @error('total_amount')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Catatan / Keterangan (Opsional) -->
                <div class="mb-4">
                    <label class="form-label small fw-semibold text-muted mb-1">Catatan / Keterangan (Opsional)</label>
                    <input type="text" name="notes" class="form-control" placeholder="Contoh: Setoran hasil penjualan titipan..." value="{{ old('notes') }}">
                </div>

                <!-- Tombol Aksi -->
                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                    <a href="{{ route('v2.titipan_barang.index') }}" class="btn btn-secondary px-3 py-2 fw-semibold">
                        Batal
                    </a>
                    <button type="submit" class="btn btn-success px-4 py-2 fw-semibold shadow-sm" style="background:#16a34a; border:none;">
                        <i class="bi bi-check-circle me-1.5"></i> Simpan Pembayaran
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function toggleBank(method) {
        const bankContainer = document.getElementById('bank-container');
        if (bankContainer) {
            bankContainer.style.display = (method === 'transfer') ? 'block' : 'none';
        }
    }

    function formatRupiahInput(input) {
        let val = input.value.replace(/[^0-9]/g, '');
        if (val) {
            input.value = parseInt(val, 10).toLocaleString('id-ID');
        } else {
            input.value = '';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const methodSelect = document.getElementById('payment_method');
        if (methodSelect) {
            toggleBank(methodSelect.value);
        }

        const inputAmount = document.getElementById('total_amount');
        if (inputAmount && inputAmount.value) {
            formatRupiahInput(inputAmount);
        }

        const form = document.getElementById('settlement-form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const method = document.getElementById('payment_method')?.value;
                const bankSelect = document.getElementById('bank_account_id');
                if (method === 'transfer' && bankSelect && !bankSelect.value) {
                    e.preventDefault();
                    alert('Silakan pilih Rekening Bank terlebih dahulu.');
                    bankSelect.focus();
                    return false;
                }

                const amountVal = inputAmount?.value.replace(/[^0-9]/g, '');
                if (!amountVal || parseInt(amountVal, 10) <= 0) {
                    e.preventDefault();
                    alert('Silakan masukkan Jumlah Bayar terlebih dahulu.');
                    inputAmount?.focus();
                    return false;
                }
            });
        }
    });
</script>
@endpush
