@extends('layouts.app')
@section('title', 'Edit Voucher')
@section('page-title', 'Edit Voucher')

@section('content')
<div class="form-page-wrapper">
    <a href="{{ route('vouchers.index') }}" class="btn-back">
        <i class="fas fa-arrow-left"></i> Kembali ke Daftar Voucher
    </a>

    <div class="dashboard-card" style="margin-top:1rem; max-width:760px;">
        <div class="card-header-line">
            <h3><i class="fas fa-ticket-alt"></i> Edit Voucher: {{ $voucher->code }}</h3>
        </div>

        @if($voucher->marketplace_voucher_id)
            <div style="background-color:rgba(239, 68, 68, 0.1); border:1px solid rgba(239, 68, 68, 0.3); color:#ef4444; padding:12px 15px; border-radius:6px; margin-bottom:1.5rem; font-size:0.85rem; display:flex; align-items:center; gap:8px;">
                <i class="fas fa-exclamation-triangle"></i>
                <span>Voucher ini sudah di-sync ke Shopee (ID: {{ $voucher->marketplace_voucher_id }}). Perubahan di sini tidak akan otomatis memperbarui voucher di Shopee.</span>
            </div>
        @endif

        <form action="{{ route('vouchers.update', $voucher) }}" method="POST">
            @csrf
            @method('PUT')

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">

                <div class="form-group">
                    <label class="form-label">Nama Voucher <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="name" class="form-input" required
                           value="{{ old('name', $voucher->name) }}" placeholder="Contoh: Diskon Lebaran 10%" id="voucher-name">
                </div>

                <div class="form-group">
                    <label class="form-label">Kode Voucher <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="code" class="form-input" required
                           value="{{ old('code', $voucher->code) }}" placeholder="LEBARAN10" id="voucher-code"
                           style="text-transform:uppercase; letter-spacing:.05em;"
                           {{ $voucher->marketplace_voucher_id ? 'readonly' : '' }}>
                    @if($voucher->marketplace_voucher_id)
                        <small class="text-muted"><i class="fas fa-lock"></i> Kode tidak dapat diubah karena sudah ter-sync.</small>
                    @else
                        <small class="text-muted">Akan otomatis diubah menjadi huruf kapital.</small>
                    @endif
                </div>

                <div class="form-group">
                    <label class="form-label">Jenis Diskon <span style="color:#ef4444;">*</span></label>
                    <select name="type" class="form-input" id="discount-type" onchange="toggleDiscountFields()"
                            {{ $voucher->marketplace_voucher_id ? 'disabled' : '' }}>
                        <option value="percentage" {{ old('type', $voucher->type) === 'percentage' ? 'selected' : '' }}>Persentase (%)</option>
                        <option value="fixed" {{ old('type', $voucher->type) === 'fixed' ? 'selected' : '' }}>Nominal Tetap (Rp)</option>
                    </select>
                    @if($voucher->marketplace_voucher_id)
                        <input type="hidden" name="type" value="{{ $voucher->type }}">
                    @endif
                </div>

                <div class="form-group">
                    <label class="form-label" id="value-label">Nilai Diskon <span style="color:#ef4444;">*</span></label>
                    <input type="number" name="value" class="form-input" required
                           value="{{ old('value', $voucher->value) }}" min="1" step="0.01" placeholder="10" id="voucher-value"
                           {{ $voucher->marketplace_voucher_id ? 'readonly' : '' }}>
                </div>

                <div class="form-group" id="max-discount-group">
                    <label class="form-label">Maks. Potongan (Rp)</label>
                    <input type="number" name="max_discount" class="form-input"
                           value="{{ old('max_discount', $voucher->max_discount) }}" min="0" placeholder="50000" id="max-discount"
                           {{ $voucher->marketplace_voucher_id ? 'readonly' : '' }}>
                    <small class="text-muted">Opsional. Batas maksimal potongan untuk diskon %.</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Min. Pembelian (Rp)</label>
                    <input type="number" name="min_purchase" class="form-input"
                           value="{{ old('min_purchase', $voucher->min_purchase) }}" min="0" placeholder="0"
                           {{ $voucher->marketplace_voucher_id ? 'readonly' : '' }}>
                </div>

                <div class="form-group">
                    <label class="form-label">Tanggal Mulai <span style="color:#ef4444;">*</span></label>
                    <input type="datetime-local" name="start_date" class="form-input" required
                           value="{{ old('start_date', $voucher->start_date ? $voucher->start_date->format('Y-m-d\TH:i') : '') }}" id="start-date"
                           {{ $voucher->marketplace_voucher_id ? 'readonly' : '' }}>
                </div>

                <div class="form-group">
                    <label class="form-label">Tanggal Berakhir <span style="color:#ef4444;">*</span></label>
                    <input type="datetime-local" name="end_date" class="form-input" required
                           value="{{ old('end_date', $voucher->end_date ? $voucher->end_date->format('Y-m-d\TH:i') : '') }}" id="end-date">
                </div>

                <div class="form-group">
                    <label class="form-label">Batas Penggunaan</label>
                    <input type="number" name="usage_limit" class="form-input"
                           value="{{ old('usage_limit', $voucher->usage_limit) }}" min="1" placeholder="Kosongkan = tidak terbatas"
                           {{ $voucher->marketplace_voucher_id ? 'readonly' : '' }}>
                </div>

                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label">Pilih Toko / Channel (opsional)</label>
                    <select name="store_id" class="form-input" id="voucher-store" onchange="toggleStoreNotice()" {{ $voucher->marketplace_voucher_id ? 'disabled' : '' }}>
                        <option value="" data-channel="">-- Semua Toko / Tanpa Sync --</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" data-channel="{{ $store->channel->code }}" {{ old('store_id', $voucher->store_id) == $store->id ? 'selected' : '' }}>
                                {{ $store->store_name }} ({{ $store->channel->name }})
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted" id="store-help">Pilih toko jika ingin mengaitkan voucher dengan channel tertentu.</small>
                    @if($voucher->marketplace_voucher_id)
                        <input type="hidden" name="store_id" value="{{ $voucher->store_id }}">
                    @endif
                </div>

                <div id="tiktok-notice" style="grid-column: span 2; display:none; background-color:rgba(245, 158, 11, 0.1); border:1px solid rgba(245, 158, 11, 0.3); color:#d97706; padding:12px 15px; border-radius:6px; margin-bottom:0.5rem; font-size:0.85rem;">
                    <i class="fas fa-info-circle"></i>
                    <span><strong>Info Toko TikTok:</strong> TikTok Shop API saat ini tidak mendukung pembuatan voucher secara otomatis melalui pihak ketiga. Silakan kelola voucher ini secara manual di TikTok Seller Center.</span>
                </div>

                <div class="form-group">
                    <label class="form-label">Status Voucher</label>
                    <select name="is_active" class="form-input">
                        <option value="1" {{ old('is_active', $voucher->is_active) ? 'selected' : '' }}>Aktif</option>
                        <option value="0" {{ !old('is_active', $voucher->is_active) ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>

            </div>

            <div style="margin-top:1.5rem; display:flex; gap:.75rem;">
                <button type="submit" class="btn-primary-sm" style="padding:8px 24px;" id="btn-save-voucher">
                    <i class="fas fa-save"></i> Perbarui Voucher
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

// Auto uppercase code if not readonly
const codeInput = document.getElementById('voucher-code');
if (codeInput && !codeInput.readOnly) {
    codeInput.addEventListener('input', function() {
        this.value = this.value.toUpperCase().replace(/\s/g, '');
    });
}

window.addEventListener('DOMContentLoaded', function() {
    toggleDiscountFields();
    toggleStoreNotice();
});
</script>
@endpush
@endsection
