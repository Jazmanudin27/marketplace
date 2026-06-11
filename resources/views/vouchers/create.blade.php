@extends('layouts.app')
@section('title', 'Buat Voucher')
@section('page-title', 'Buat Voucher Baru')

@section('content')
<div class="form-page-wrapper">
    <a href="{{ route('vouchers.index') }}" class="btn-back">
        <i class="fas fa-arrow-left"></i> Kembali ke Daftar Voucher
    </a>

    <div class="dashboard-card" style="margin-top:1rem; max-width:760px;">
        <div class="card-header-line">
            <h3><i class="fas fa-ticket-alt"></i> Form Voucher Baru</h3>
        </div>

        <form action="{{ route('vouchers.store') }}" method="POST">
            @csrf

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">

                <div class="form-group">
                    <label class="form-label">Nama Voucher <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="name" class="form-input" required
                           value="{{ old('name') }}" placeholder="Contoh: Diskon Lebaran 10%" id="voucher-name">
                </div>

                <div class="form-group">
                    <label class="form-label">Kode Voucher <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="code" class="form-input" required
                           value="{{ old('code') }}" placeholder="LEBARAN10" id="voucher-code"
                           style="text-transform:uppercase; letter-spacing:.05em;">
                    <small class="text-muted">Akan otomatis diubah menjadi huruf kapital.</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Jenis Diskon <span style="color:#ef4444;">*</span></label>
                    <select name="type" class="form-input" id="discount-type" onchange="toggleDiscountFields()">
                        <option value="percentage" {{ old('type') === 'percentage' ? 'selected' : '' }}>Persentase (%)</option>
                        <option value="fixed" {{ old('type') === 'fixed' ? 'selected' : '' }}>Nominal Tetap (Rp)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" id="value-label">Nilai Diskon <span style="color:#ef4444;">*</span></label>
                    <input type="number" name="value" class="form-input" required
                           value="{{ old('value') }}" min="1" step="0.01" placeholder="10" id="voucher-value">
                </div>

                <div class="form-group" id="max-discount-group">
                    <label class="form-label">Maks. Potongan (Rp)</label>
                    <input type="number" name="max_discount" class="form-input"
                           value="{{ old('max_discount') }}" min="0" placeholder="50000" id="max-discount">
                    <small class="text-muted">Opsional. Batas maksimal potongan untuk diskon %.</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Min. Pembelian (Rp)</label>
                    <input type="number" name="min_purchase" class="form-input"
                           value="{{ old('min_purchase', 0) }}" min="0" placeholder="0">
                </div>

                <div class="form-group">
                    <label class="form-label">Tanggal Mulai <span style="color:#ef4444;">*</span></label>
                    <input type="datetime-local" name="start_date" class="form-input" required
                           value="{{ old('start_date') }}" id="start-date">
                </div>

                <div class="form-group">
                    <label class="form-label">Tanggal Berakhir <span style="color:#ef4444;">*</span></label>
                    <input type="datetime-local" name="end_date" class="form-input" required
                           value="{{ old('end_date') }}" id="end-date">
                </div>

                <div class="form-group">
                    <label class="form-label">Batas Penggunaan</label>
                    <input type="number" name="usage_limit" class="form-input"
                           value="{{ old('usage_limit') }}" min="1" placeholder="Kosongkan = tidak terbatas">
                </div>

                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label">Pilih Toko / Channel (opsional)</label>
                    <select name="store_id" class="form-input" id="voucher-store" onchange="toggleStoreNotice()">
                        <option value="" data-channel="">-- Semua Toko / Tanpa Sync --</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" data-channel="{{ $store->channel->code }}" {{ old('store_id') == $store->id ? 'selected' : '' }}>
                                {{ $store->store_name }} ({{ $store->channel->name }})
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted" id="store-help">Pilih toko jika ingin mengaitkan voucher dengan channel tertentu.</small>
                </div>

                <div id="tiktok-notice" style="grid-column: span 2; display:none; background-color:rgba(245, 158, 11, 0.1); border:1px solid rgba(245, 158, 11, 0.3); color:#d97706; padding:12px 15px; border-radius:6px; margin-bottom:0.5rem; font-size:0.85rem;">
                    <i class="fas fa-info-circle"></i>
                    <span><strong>Info Toko TikTok:</strong> TikTok Shop API saat ini tidak mendukung pembuatan voucher secara otomatis melalui pihak ketiga. Setelah menyimpan voucher ini di ERP, silakan buat voucher dengan kode yang sama secara manual di TikTok Seller Center.</span>
                </div>

            </div>

            <div style="margin-top:1.5rem; display:flex; gap:.75rem;">
                <button type="submit" class="btn-primary-sm" style="padding:8px 24px;" id="btn-save-voucher">
                    <i class="fas fa-save"></i> Simpan Voucher
                </button>
                <a href="{{ route('vouchers.index') }}" class="btn-primary-sm" style="background:var(--bg-sidebar); border-color:var(--border);">
                    Batal
                </a>
            </div>
        </form>
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
        label.innerHTML = 'Nilai Diskon (%) <span style="color:#ef4444;">*</span>';
        valueInput.max = 100;
        valueInput.placeholder = '10';
        maxGroup.style.display = 'block';
    } else {
        label.innerHTML = 'Nilai Diskon (Rp) <span style="color:#ef4444;">*</span>';
        valueInput.removeAttribute('max');
        valueInput.placeholder = '15000';
        maxGroup.style.display = 'none';
    }
}

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
        help.innerHTML = '<span style="color:#d97706;"><i class="fas fa-exclamation-triangle"></i> Memerlukan pembuatan manual di TikTok Seller Center.</span>';
    } else if (channel === 'shopee') {
        notice.style.display = 'none';
        help.innerHTML = '<span style="color:#10b981;"><i class="fas fa-check-circle"></i> Dapat disinkronkan otomatis ke Shopee API.</span>';
    } else {
        notice.style.display = 'none';
        help.textContent = 'Pilih toko jika ingin mengaitkan voucher dengan channel tertentu.';
    }
}

// Auto uppercase code
document.getElementById('voucher-code').addEventListener('input', function() {
    this.value = this.value.toUpperCase().replace(/\s/g, '');
});

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
