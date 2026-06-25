@extends('layouts.app')
@section('title', 'Buat Voucher')
@section('page-title', 'Buat Voucher Baru')

@section('content')
<div class="container-fluid px-0">
    <div class="mb-3">
        <a href="{{ route('vouchers.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar Voucher
        </a>
    </div>

    <div class="card border shadow-sm" style="max-width:760px;">
        <div class="card-header bg-light border-bottom py-2.5 px-3">
            <h6 class="m-0 fw-bold text-primary">
                <i class="fas fa-ticket-alt me-2"></i> Form Voucher Baru
            </h6>
        </div>

        <div class="card-body p-3">
            <form action="{{ route('vouchers.store') }}" method="POST">
                @csrf

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-dark small fw-semibold">Nama Voucher <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm" required
                               value="{{ old('name') }}" placeholder="Contoh: Diskon Lebaran 10%" id="voucher-name">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-dark small fw-semibold">Kode Voucher <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control form-control-sm text-uppercase" required
                               value="{{ old('code') }}" placeholder="LEBARAN10" id="voucher-code" style="letter-spacing:.05em;">
                        <small class="text-muted">Akan otomatis diubah menjadi huruf kapital.</small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-dark small fw-semibold">Jenis Diskon <span class="text-danger">*</span></label>
                        <select name="type" class="form-select form-select-sm" id="discount-type" onchange="toggleDiscountFields()">
                            <option value="percentage" {{ old('type') === 'percentage' ? 'selected' : '' }}>Persentase (%)</option>
                            <option value="fixed" {{ old('type') === 'fixed' ? 'selected' : '' }}>Nominal Tetap (Rp)</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-dark small fw-semibold" id="value-label">Nilai Diskon <span class="text-danger">*</span></label>
                        <input type="number" name="value" class="form-control form-control-sm" required
                               value="{{ old('value') }}" min="1" step="0.01" placeholder="10" id="voucher-value">
                    </div>

                    <div class="col-md-6" id="max-discount-group">
                        <label class="form-label text-dark small fw-semibold">Maks. Potongan (Rp)</label>
                        <input type="number" name="max_discount" class="form-control form-control-sm"
                               value="{{ old('max_discount') }}" min="0" placeholder="50000" id="max-discount">
                        <small class="text-muted">Opsional. Batas maksimal potongan untuk diskon %.</small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-dark small fw-semibold">Min. Pembelian (Rp)</label>
                        <input type="number" name="min_purchase" class="form-control form-control-sm"
                               value="{{ old('min_purchase', 0) }}" min="0" placeholder="0">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-dark small fw-semibold">Tanggal Mulai <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="start_date" class="form-control form-control-sm" required
                               value="{{ old('start_date') }}" id="start-date">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-dark small fw-semibold">Tanggal Berakhir <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="end_date" class="form-control form-control-sm" required
                               value="{{ old('end_date') }}" id="end-date">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-dark small fw-semibold">Batas Penggunaan</label>
                        <input type="number" name="usage_limit" class="form-control form-control-sm"
                               value="{{ old('usage_limit') }}" min="1" placeholder="Kosongkan = tidak terbatas">
                    </div>

                    <div class="col-12">
                        <label class="form-label text-dark small fw-semibold">Pilih Toko / Channel (opsional)</label>
                        <select name="store_id" class="form-select form-select-sm" id="voucher-store" onchange="toggleStoreNotice()">
                            <option value="" data-channel="">-- Semua Toko / Tanpa Sync --</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" data-channel="{{ $store->channel->code }}" {{ old('store_id') == $store->id ? 'selected' : '' }}>
                                    {{ $store->store_name }} ({{ $store->channel->name }})
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted d-block mt-1" id="store-help">Pilih toko jika ingin mengaitkan voucher dengan channel tertentu.</small>
                    </div>

                    <div class="col-12">
                        <div id="tiktok-notice" class="alert alert-warning mb-0" style="display:none;">
                            <i class="fas fa-info-circle me-1"></i>
                            <strong>Info Toko TikTok:</strong> TikTok Shop API saat ini tidak mendukung pembuatan voucher secara otomatis melalui pihak ketiga. Setelah menyimpan voucher ini di ERP, silakan buat voucher dengan kode yang sama secara manual di TikTok Seller Center.
                        </div>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm px-4" id="btn-save-voucher">
                        <i class="fas fa-save me-1"></i> Simpan Voucher
                    </button>
                    <a href="{{ route('vouchers.index') }}" class="btn btn-outline-secondary btn-sm px-4">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function toggleDiscountFields() {
    const type = document.getElementById('discount-type').value;
    const label = document.getElementById('value-label');
    const maxGroup = document.getElementById('max-discount-group');
    const valueInput = document.getElementById('voucher-value');

    if (type === 'percentage') {
        label.innerHTML = 'Nilai Diskon (%) <span class="text-danger">*</span>';
        valueInput.max = 100;
        valueInput.placeholder = '10';
        maxGroup.style.display = 'block';
    } else {
        label.innerHTML = 'Nilai Diskon (Rp) <span class="text-danger">*</span>';
        valueInput.removeAttribute('max');
        valueInput.placeholder = '15000';
        maxGroup.style.display = 'none';
    }
}

// Auto uppercase code
document.getElementById('voucher-code').addEventListener('input', function() {
    this.value = this.value.toUpperCase().replace(/\s/g, '');
});

function toggleStoreNotice() {
    const select = document.getElementById('voucher-store');
    if (!select) return;
    const selectedOption = select.options[select.selectedIndex];
    const channel = selectedOption ? selectedOption.getAttribute('data-channel') : '';
    const notice = document.getElementById('tiktok-notice');
    const help = document.getElementById('store-help');

    if (!notice || !help) return;

    if (channel === 'tiktok') {
        notice.style.display = 'block';
        help.innerHTML = '<span class="text-warning"><i class="fas fa-exclamation-triangle"></i> Memerlukan pembuatan manual di TikTok Seller Center.</span>';
    } else if (channel === 'shopee') {
        notice.style.display = 'none';
        help.innerHTML = '<span class="text-success"><i class="fas fa-check-circle"></i> Dapat disinkronkan otomatis ke Shopee API.</span>';
    } else {
        notice.style.display = 'none';
        help.textContent = 'Pilih toko jika ingin mengaitkan voucher dengan channel tertentu.';
    }
}

// Set default start date to now
window.addEventListener('DOMContentLoaded', function() {
    toggleDiscountFields();
    toggleStoreNotice();
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    const nowStr = now.toISOString().slice(0,16);
    if (!document.getElementById('start-date').value) {
        document.getElementById('start-date').value = nowStr;
    }
    const endDefault = new Date(now.getTime() + 7 * 24 * 60 * 60 * 1000);
    endDefault.setMinutes(endDefault.getMinutes() - endDefault.getTimezoneOffset());
    if (!document.getElementById('end-date').value) {
        document.getElementById('end-date').value = endDefault.toISOString().slice(0,16);
    }
});
</script>
@endpush
@endsection
