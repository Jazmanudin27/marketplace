@extends('layouts.app')
@section('title', 'Buat Purchase Order')
@section('page-title', 'Purchase Order Baru')

@push('styles')
<style>
/* Select2 custom styling */
.select2-container--bootstrap-5 .select2-selection {
    border-radius: 8px !important;
    border-color: #e2e8f0 !important;
    min-height: 38px !important;
}
.select2-container--bootstrap-5 .select2-selection--single {
    padding: 5px 10px !important;
}
.select2-container--bootstrap-5.select2-container--focus .select2-selection,
.select2-container--bootstrap-5.select2-container--open .select2-selection {
    border-color: #3b82f6 !important;
    box-shadow: 0 0 0 3px rgba(59,130,246,.15) !important;
}
.select2-results__option {
    padding: 8px 12px !important;
    border-radius: 6px;
    margin: 2px 4px;
}
.select2-results__option--highlighted {
    background: #eff6ff !important;
    color: #1d4ed8 !important;
}
.select2-results__group {
    font-size: 11px !important;
    font-weight: 700 !important;
    color: #94a3b8 !important;
    text-transform: uppercase;
    padding: 8px 12px 4px !important;
    letter-spacing: .05em;
}
.select2-dropdown {
    border-radius: 10px !important;
    border-color: #e2e8f0 !important;
    box-shadow: 0 10px 30px rgba(0,0,0,.12) !important;
    overflow: hidden;
}
.select2-search--dropdown .select2-search__field {
    border-radius: 8px !important;
    border-color: #e2e8f0 !important;
    padding: 6px 10px !important;
    font-size: 13px !important;
}
.item-option-name { font-size: 13px; font-weight: 600; color: #1e293b; }
.item-option-meta { font-size: 11px; color: #64748b; margin-top: 1px; }
.item-option-price { font-size: 12px; font-weight: 700; color: #059669; }

/* Table rows */
.item-row td { vertical-align: middle; }
.item-row:hover { background: #f8fafc; }
</style>
@endpush

@section('content')
<form action="{{ route('purchase_orders.store') }}" method="POST" id="po-form">
@csrf

<div class="row g-3">
    {{-- Kiri: Info PO --}}
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 bg-white mb-3 sticky-top" style="top:80px">
            <div class="card-header border-0 py-3 px-4" style="background:linear-gradient(135deg,#3b82f6,#1d4ed8)">
                <h6 class="fw-bold text-white mb-0">
                    <i class="fas fa-file-invoice me-2"></i>Informasi Purchase Order
                </h6>
            </div>
            <div class="card-body p-4">
                @if($errors->any())
                    <div class="alert alert-danger py-2 small mb-3">
                        @foreach($errors->all() as $e)
                            <div><i class="fas fa-exclamation-circle me-1"></i>{{ $e }}</div>
                        @endforeach
                    </div>
                @endif

                {{-- Supplier --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-muted">
                        <i class="fas fa-building me-1 text-primary"></i>Supplier <span class="text-danger">*</span>
                    </label>
                    <select name="supplier_id" id="sel-supplier" class="form-select" required
                        data-placeholder="🔍 Cari & pilih supplier...">
                        <option value=""></option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}"
                                data-phone="{{ $s->phone ?? '' }}"
                                data-address="{{ $s->address ?? '' }}"
                                {{ old('supplier_id') == $s->id ? 'selected' : '' }}>
                                {{ $s->name }}
                            </option>
                        @endforeach
                    </select>
                    {{-- Info supplier mini --}}
                    <div id="supplier-info" class="mt-2 small text-muted d-none">
                        <i class="fas fa-phone me-1"></i><span id="supplier-phone"></span>
                    </div>
                </div>

                {{-- Departemen --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-muted">
                        <i class="fas fa-building-user me-1 text-primary"></i>Departemen Pemesan <span class="text-danger">*</span>
                    </label>
                    <select name="department_id" id="sel-dept" class="form-select" required
                        data-placeholder="— Pilih departemen —">
                        <option value=""></option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Tanggal --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-muted">
                        <i class="fas fa-calendar me-1 text-primary"></i>Tanggal PO <span class="text-danger">*</span>
                    </label>
                    <input type="date" name="po_date" class="form-control"
                        value="{{ old('po_date', date('Y-m-d')) }}" required>
                </div>

                {{-- Catatan --}}
                <div class="mb-4">
                    <label class="form-label fw-semibold small text-muted">
                        <i class="fas fa-sticky-note me-1 text-primary"></i>Catatan Tambahan
                    </label>
                    <textarea name="notes" class="form-control form-control-sm" rows="2"
                        placeholder="Instruksi pengiriman, term of payment...">{{ old('notes') }}</textarea>
                </div>

                {{-- Summary total --}}
                <div class="border rounded-2 p-3 mb-4" style="background:#f0f9ff">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small">Jumlah Item</span>
                        <span class="fw-bold text-dark" id="summary-item-count">0 item</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Grand Total</span>
                        <span class="fw-bold text-primary font-monospace" id="grand-total-display" style="font-size:15px">Rp 0</span>
                    </div>
                </div>

                <div class="d-flex flex-column gap-2">
                    <button type="submit" class="btn btn-primary fw-bold py-2" id="btn-submit">
                        <i class="fas fa-save me-2"></i>Simpan Purchase Order
                    </button>
                    <a href="{{ route('purchase_orders.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Kanan: Daftar Item --}}
    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-3 bg-white">
            <div class="card-header border-0 py-3 px-4 d-flex justify-content-between align-items-center"
                style="background:linear-gradient(135deg,#3b82f6,#1d4ed8)">
                <h6 class="fw-bold text-white mb-0">
                    <i class="fas fa-cubes me-2"></i>Daftar Item Pembelian
                </h6>
                <span class="badge bg-white text-primary" id="badge-count" style="font-size:12px">0 item</span>
            </div>
            <div class="card-body p-4">

                {{-- Picker Barang --}}
                <div class="border rounded-2 p-3 mb-4" style="background:#f8fafc">
                    <div class="row g-2 align-items-end">
                        <div class="col">
                            <label class="form-label fw-semibold small text-muted mb-1">
                                <i class="fas fa-search me-1 text-primary"></i>Cari & Pilih Barang
                            </label>
                            <select id="product-picker" class="form-select"
                                data-placeholder="🔍 Ketik nama atau SKU barang...">
                                <option value=""></option>
                                <optgroup label="Bahan Baku &amp; Kemasan">
                                    @foreach($materials as $m)
                                        <option value="{{ $m->id }}"
                                            data-type="material"
                                            data-sku="{{ $m->sku ?? '-' }}"
                                            data-name="{{ $m->name }}"
                                            data-unit="{{ $m->unit ?? '' }}"
                                            data-stock="{{ $m->stock ?? 0 }}"
                                            data-price="{{ (int)($m->cost_price ?? 0) }}"
                                            data-category="bahan">
                                            {{ $m->name }} ({{ $m->sku ?? '-' }})
                                        </option>
                                    @endforeach
                                </optgroup>
                                <optgroup label="Inventory / ATK / Inventaris">
                                    @foreach($inventoryItems as $inv)
                                        <option value="{{ $inv->id }}"
                                            data-type="inventory"
                                            data-sku="{{ $inv->sku ?? '-' }}"
                                            data-name="{{ $inv->name }}"
                                            data-unit="{{ $inv->unit ?? '' }}"
                                            data-stock="{{ $inv->stock ?? 0 }}"
                                            data-price="{{ (int)($inv->cost_price ?? 0) }}"
                                            data-category="{{ $inv->type }}">
                                            {{ $inv->name }} ({{ $inv->sku ?? '-' }})
                                        </option>
                                    @endforeach
                                </optgroup>
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="button" id="btn-add-product"
                                class="btn btn-primary fw-semibold px-4"
                                style="height:38px">
                                <i class="fas fa-plus me-1"></i> Tambah
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Tabel Item --}}
                <div class="table-responsive rounded-2 border">
                    <table class="table table-hover align-middle mb-0" id="items-table">
                        <thead style="background:#f1f5f9">
                            <tr class="small text-uppercase text-muted">
                                <th class="py-2 px-3">Barang</th>
                                <th class="text-center py-2" style="width:120px">Qty</th>
                                <th class="text-end py-2" style="width:160px">Harga Satuan</th>
                                <th class="text-end py-2" style="width:140px">Subtotal</th>
                                <th class="text-center py-2" style="width:60px"></th>
                            </tr>
                        </thead>
                        <tbody id="items-body">
                            <tr id="empty-row">
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="fas fa-cubes fa-2x mb-2 opacity-25 d-block"></i>
                                    <div class="small">Belum ada barang. Pilih dari dropdown di atas lalu klik <strong>Tambah</strong>.</div>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot style="background:#eff6ff">
                            <tr>
                                <td colspan="3" class="text-end fw-bold text-dark py-3 px-3">GRAND TOTAL</td>
                                <td class="text-end font-monospace fw-bold text-primary py-3" id="grand-total-foot" style="font-size:15px">Rp 0</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>
</form>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    let itemIndex = 0;

    // ── Select2: Supplier (dengan template custom) ──────────────────────
    function formatSupplier(opt) {
        if (!opt.id) return $('<span class="text-muted">🔍 Cari & pilih supplier...</span>');
        return $('<span><i class="fas fa-building me-2 text-primary" style="font-size:11px"></i><strong>' + opt.text + '</strong></span>');
    }

    $('#sel-supplier').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: '🔍 Cari & pilih supplier...',
        allowClear: true,
        templateResult: formatSupplier,
        templateSelection: formatSupplier,
    }).on('select2:select', function (e) {
        const data = e.params.data.element;
        const phone   = data.getAttribute('data-phone');
        if (phone) {
            $('#supplier-phone').text(phone);
            $('#supplier-info').removeClass('d-none');
        } else {
            $('#supplier-info').addClass('d-none');
        }
    }).on('select2:clear', function () {
        $('#supplier-info').addClass('d-none');
    });

    // ── Select2: Departemen ──────────────────────────────────────────────
    $('#sel-dept').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: '— Pilih departemen —',
        allowClear: true,
    });

    // ── Select2: Product Picker (template custom dgn harga & stok) ──────
    function formatItem(opt) {
        if (!opt.id) return $('<span class="text-muted">🔍 Ketik nama atau SKU barang...</span>');
        const el    = opt.element;
        const sku   = el.getAttribute('data-sku') || '-';
        const unit  = el.getAttribute('data-unit') || '';
        const stock = el.getAttribute('data-stock') || '0';
        const price = parseInt(el.getAttribute('data-price') || 0);
        const cat   = el.getAttribute('data-category') || '';

        const catColors = {
            bahan:     'background:#fef3c7;color:#92400e',
            kemasan:   'background:#dbeafe;color:#1e40af',
            atk:       'background:#d1fae5;color:#065f46',
            inventaris:'background:#f3e8ff;color:#6d28d9',
            material:  'background:#fef3c7;color:#92400e',
            inventory: 'background:#e0f2fe;color:#0369a1',
        };
        const catStyle = catColors[cat] || 'background:#f1f5f9;color:#475569';
        const stockBadge = parseInt(stock) <= 0
            ? '<span class="badge bg-danger ms-1" style="font-size:10px">Habis</span>'
            : '<span class="badge bg-success ms-1" style="font-size:10px">Stok: ' + parseInt(stock).toLocaleString('id-ID') + ' ' + unit + '</span>';

        return $(`
            <div class="d-flex justify-content-between align-items-start py-1">
                <div>
                    <div class="item-option-name">${opt.text.split(' (')[0]}</div>
                    <div class="item-option-meta">
                        <span class="font-monospace">${sku}</span>
                        ${stockBadge}
                        <span class="badge ms-1" style="font-size:10px;${catStyle}">${cat}</span>
                    </div>
                </div>
                <div class="item-option-price text-end ms-3">Rp ${price.toLocaleString('id-ID')}</div>
            </div>
        `);
    }

    function formatItemSelected(opt) {
        if (!opt.id) return $('<span class="text-muted">🔍 Ketik nama atau SKU barang...</span>');
        return $('<span><i class="fas fa-box me-2 text-primary" style="font-size:11px"></i><strong>' + opt.text.split(' (')[0] + '</strong></span>');
    }

    $('#product-picker').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: '🔍 Ketik nama atau SKU barang...',
        allowClear: true,
        templateResult: formatItem,
        templateSelection: formatItemSelected,
    });

    // ── Tambah item ke tabel ─────────────────────────────────────────────
    $('#btn-add-product').on('click', function () {
        const picker  = $('#product-picker');
        const val     = picker.val();
        if (!val) {
            picker.next('.select2-container').find('.select2-selection').css('border-color','#ef4444');
            setTimeout(() => picker.next('.select2-container').find('.select2-selection').css('border-color',''), 1200);
            return;
        }

        const el    = picker.find('option:selected')[0];
        const id    = val;
        const type  = el.getAttribute('data-type');
        const sku   = el.getAttribute('data-sku') || '-';
        const name  = el.getAttribute('data-name');
        const unit  = el.getAttribute('data-unit') || '';
        const price = parseInt(el.getAttribute('data-price') || 0);
        const cat   = el.getAttribute('data-category') || '';

        // Cek duplikat
        if (document.querySelector(`#row-item-${type}-${id}`)) {
            Swal.fire({ icon:'warning', title:'Duplikat', text:'Barang ini sudah ada di daftar.', timer:2000, showConfirmButton:false });
            return;
        }

        // Hapus empty row
        const emptyRow = document.getElementById('empty-row');
        if (emptyRow) emptyRow.remove();

        const catColors = {
            bahan:'background:#fef3c7;color:#92400e',
            kemasan:'background:#dbeafe;color:#1e40af',
            atk:'background:#d1fae5;color:#065f46',
            inventaris:'background:#f3e8ff;color:#6d28d9',
            material:'background:#fef3c7;color:#92400e',
            inventory:'background:#e0f2fe;color:#0369a1',
        };
        const catStyle = catColors[cat] || 'background:#f1f5f9;color:#475569';

        const tbody = document.getElementById('items-body');
        const tr = document.createElement('tr');
        tr.id = `row-item-${type}-${id}`;
        tr.className = 'item-row';
        tr.innerHTML = `
            <td class="px-3 py-3">
                <div class="fw-semibold text-dark small">${name}</div>
                <div class="d-flex align-items-center gap-1 mt-1">
                    <span class="font-monospace text-muted" style="font-size:11px">${sku}</span>
                    <span class="badge" style="font-size:10px;${catStyle}">${cat}</span>
                    ${unit ? `<span class="badge bg-secondary" style="font-size:10px">${unit}</span>` : ''}
                </div>
                <input type="hidden" name="items[${itemIndex}][item_id]" value="${id}" class="item-id-input" data-type="${type}">
                <input type="hidden" name="items[${itemIndex}][item_type]" value="${type}">
            </td>
            <td class="text-center py-2">
                <input type="number" name="items[${itemIndex}][quantity]"
                    class="form-control form-control-sm text-center qty-input"
                    style="width:80px;margin:auto"
                    value="1" min="1" required>
            </td>
            <td class="py-2">
                <div class="input-group input-group-sm">
                    <span class="input-group-text" style="font-size:11px">Rp</span>
                    <input type="number" name="items[${itemIndex}][unit_price]"
                        class="form-control form-control-sm text-end price-input"
                        value="${price}" min="0" step="100" required>
                </div>
            </td>
            <td class="text-end font-monospace fw-bold text-dark subtotal-display" style="font-size:13px">
                Rp ${price.toLocaleString('id-ID')}
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-item rounded-2">
                    <i class="fas fa-times"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
        itemIndex++;

        // Reset picker
        picker.val('').trigger('change');

        // Events
        tr.querySelector('.qty-input').addEventListener('input', recalc);
        tr.querySelector('.price-input').addEventListener('input', recalc);
        tr.querySelector('.btn-remove-item').addEventListener('click', function () {
            tr.remove();
            checkEmpty();
            updateSummary();
        });

        updateSummary();
    });

    // ── Kalkulasi ────────────────────────────────────────────────────────
    function recalc() {
        const tr    = this.closest('tr');
        const qty   = parseInt(tr.querySelector('.qty-input').value || 0);
        const price = parseFloat(tr.querySelector('.price-input').value || 0);
        tr.querySelector('.subtotal-display').textContent = 'Rp ' + (qty * price).toLocaleString('id-ID');
        updateSummary();
    }

    function updateSummary() {
        let total = 0, count = 0;
        document.querySelectorAll('.item-row').forEach(row => {
            const qty   = parseInt(row.querySelector('.qty-input').value || 0);
            const price = parseFloat(row.querySelector('.price-input').value || 0);
            total += qty * price;
            count++;
        });
        const fmt = 'Rp ' + total.toLocaleString('id-ID');
        document.getElementById('grand-total-display').textContent = fmt;
        document.getElementById('grand-total-foot').textContent = fmt;
        document.getElementById('summary-item-count').textContent = count + ' item';
        document.getElementById('badge-count').textContent = count + ' item';
    }

    function checkEmpty() {
        if (!document.querySelectorAll('.item-row').length) {
            document.getElementById('items-body').innerHTML = `
                <tr id="empty-row">
                    <td colspan="5" class="text-center py-5 text-muted">
                        <i class="fas fa-cubes fa-2x mb-2 opacity-25 d-block"></i>
                        <div class="small">Belum ada barang. Pilih dari dropdown di atas lalu klik <strong>Tambah</strong>.</div>
                    </td>
                </tr>`;
        }
    }

    // Submit guard
    document.getElementById('po-form').addEventListener('submit', function () {
        if (!document.querySelectorAll('.item-row').length) {
            Swal.fire({ icon:'warning', title:'Item kosong', text:'Tambahkan minimal 1 barang ke daftar.', confirmButtonText:'OK' });
            event.preventDefault();
            return;
        }
        const btn = document.getElementById('btn-submit');
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';
        btn.disabled = true;
    });
});
</script>
@endpush
