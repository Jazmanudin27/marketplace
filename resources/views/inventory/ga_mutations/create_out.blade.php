@extends('layouts.app')
@section('title', 'Catat Barang Keluar - General Affair')
@section('page-title', 'Barang Keluar GA')

@push('styles')
<style>
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
    border-color: #f59e0b !important;
    box-shadow: 0 0 0 3px rgba(245,158,11,.15) !important;
}
.item-option-name { font-size: 13px; font-weight: 600; color: #1e293b; }
.item-option-meta { font-size: 11px; color: #64748b; margin-top: 1px; }
.item-row td { vertical-align: middle; }
.item-row:hover { background: #f8fafc; }
</style>
@endpush

@section('content')
<form action="{{ route('ga_mutations.store_out') }}" method="POST" id="wmo-form">
@csrf

<div class="row g-3">
    {{-- Kiri --}}
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 bg-white mb-3 sticky-top" style="top:80px">
            <div class="card-header border-0 py-3 px-4" style="background:linear-gradient(135deg,#f59e0b,#d97706)">
                <h6 class="fw-bold text-white mb-0">
                    <i class="fas fa-sign-out-alt me-2"></i>Detail Pengeluaran GA
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

                {{-- Asal Gudang GA --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-muted">Asal Gudang</label>
                    <select name="from_department_id" id="sel-from-dept" class="form-select">
                        <option value="">— Gudang General Affair (Utama) —</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('from_department_id') == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Departemen Tujuan --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-muted">
                        Departemen Tujuan <span class="text-danger">*</span>
                    </label>
                    <select name="to_department_id" id="sel-to-dept" class="form-select" required
                        data-placeholder="— Pilih penerima —">
                        <option value=""></option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('to_department_id') == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text" style="font-size:11px">Pilih departemen yang menerima barang.</div>
                </div>

                {{-- Tanggal --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-muted">Tanggal Keluar <span class="text-danger">*</span></label>
                    <input type="date" name="mutation_date" class="form-control"
                        value="{{ old('mutation_date', date('Y-m-d')) }}" required>
                </div>

                {{-- Catatan --}}
                <div class="mb-4">
                    <label class="form-label fw-semibold small text-muted">Keterangan / Catatan</label>
                    <textarea name="notes" class="form-control form-control-sm" rows="3"
                        placeholder="Misal: Permintaan ATK bulanan HRD, kebutuhan inventaris...">{{ old('notes') }}</textarea>
                </div>

                <div class="d-flex flex-column gap-2">
                    <button type="submit" class="btn text-white fw-bold py-2" id="btn-submit"
                        style="background:linear-gradient(135deg,#f59e0b,#d97706)">
                        <i class="fas fa-check-circle me-2"></i>Simpan Barang Keluar GA
                    </button>
                    <a href="{{ route('ga_mutations.index_out') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Kanan: Pilih Barang --}}
    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-3 bg-white">
            <div class="card-header border-0 py-3 px-4 d-flex justify-content-between align-items-center"
                style="background:linear-gradient(135deg,#f59e0b,#d97706)">
                <h6 class="fw-bold text-white mb-0">
                    <i class="fas fa-boxes me-2"></i>Pilih Barang ATK / Inventaris
                </h6>
                <span class="badge bg-white text-warning" id="badge-count" style="font-size:12px;color:#d97706">0 item</span>
            </div>
            <div class="card-body p-4">

                <div class="border rounded-2 p-3 mb-4" style="background:#f8fafc">
                    <div class="row g-2 align-items-end">
                        <div class="col">
                            <label class="form-label fw-semibold small text-muted mb-1">Cari &amp; Pilih Barang</label>
                            <select id="product-picker" class="form-select"
                                data-placeholder="🔍 Ketik nama atau SKU barang ATK / Inventaris...">
                                <option value=""></option>
                                @foreach($inventoryItems as $item)
                                    <option value="{{ $item->id }}"
                                        data-sku="{{ $item->sku ?? '-' }}"
                                        data-name="{{ $item->name }}"
                                        data-unit="{{ $item->unit ?? '' }}"
                                        data-stock="{{ $item->stock ?? 0 }}"
                                        data-category="{{ $item->type }}">
                                        {{ $item->name }} ({{ $item->sku ?? '-' }}) — Stok: {{ $item->stock }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="button" id="btn-add-product"
                                class="btn text-white fw-semibold px-4"
                                style="background:#f59e0b; height:38px">
                                <i class="fas fa-plus me-1"></i> Tambah
                            </button>
                        </div>
                    </div>
                </div>

                <div class="table-responsive rounded-2 border">
                    <table class="table table-hover align-middle mb-0" id="items-table">
                        <thead style="background:#fef9ec">
                            <tr class="small text-uppercase text-muted">
                                <th class="py-2 px-3">Barang</th>
                                <th class="text-center py-2" style="width:80px">Stok</th>
                                <th class="text-center py-2" style="width:130px">Qty Keluar</th>
                                <th class="text-center py-2" style="width:60px"></th>
                            </tr>
                        </thead>
                        <tbody id="items-body">
                            <tr id="empty-row">
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="fas fa-cubes fa-2x mb-2 opacity-25 d-block"></i>
                                    <div class="small">Belum ada barang dipilih.</div>
                                </td>
                            </tr>
                        </tbody>
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

    $('#sel-from-dept, #sel-to-dept').select2({
        theme: 'bootstrap-5',
        width: '100%',
        allowClear: true
    });

    function formatItem(opt) {
        if (!opt.id) return $('<span class="text-muted">🔍 Cari barang...</span>');
        const el    = opt.element;
        const sku   = el.getAttribute('data-sku') || '-';
        const unit  = el.getAttribute('data-unit') || '';
        const stock = el.getAttribute('data-stock') || '0';
        const cat   = el.getAttribute('data-category') || '';

        const catColors = {
            atk:        'background:#ede9fe;color:#5b21b6',
            inventaris: 'background:#dbeafe;color:#1e40af',
        };
        const catStyle = catColors[cat] || 'background:#f1f5f9;color:#475569';

        return $(`
            <div class="d-flex justify-content-between align-items-center py-1">
                <div>
                    <div class="item-option-name">${opt.text.split(' (')[0]}</div>
                    <div class="item-option-meta">
                        <span class="font-monospace">${sku}</span> · Stok: <strong>${stock}</strong> ${unit}
                        <span class="badge ms-1" style="font-size:10px;${catStyle}">${cat}</span>
                    </div>
                </div>
            </div>
        `);
    }

    $('#product-picker').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: '🔍 Ketik nama atau SKU barang ATK / Inventaris...',
        allowClear: true,
        templateResult: formatItem
    });

    $('#btn-add-product').on('click', function () {
        const picker = $('#product-picker');
        const val    = picker.val();
        if (!val) return;

        const el    = picker.find('option:selected')[0];
        const id    = val;
        const sku   = el.getAttribute('data-sku') || '-';
        const name  = el.getAttribute('data-name');
        const unit  = el.getAttribute('data-unit') || '';
        const stock = parseInt(el.getAttribute('data-stock') || 0);
        const cat   = el.getAttribute('data-category') || '';

        if (document.querySelector(`#row-item-${id}`)) {
            Swal.fire({ icon:'warning', title:'Duplikat', text:'Barang ini sudah ada di list.', timer:2000, showConfirmButton:false });
            return;
        }

        if (stock <= 0) {
            Swal.fire({ icon:'error', title:'Stok Habis', text:'Barang ini stoknya 0, tidak bisa dikeluarkan.' });
            return;
        }

        const emptyRow = document.getElementById('empty-row');
        if (emptyRow) emptyRow.remove();

        const catColors = { atk:'background:#ede9fe;color:#5b21b6', inventaris:'background:#dbeafe;color:#1e40af' };
        const catStyle = catColors[cat] || 'background:#f1f5f9;color:#475569';

        const tbody = document.getElementById('items-body');
        const tr = document.createElement('tr');
        tr.id = `row-item-${id}`;
        tr.className = 'item-row';
        tr.innerHTML = `
            <td class="px-3 py-3">
                <div class="fw-semibold text-dark small">${name}</div>
                <div class="d-flex align-items-center gap-1 mt-1">
                    <span class="font-monospace text-muted" style="font-size:11px">${sku}</span>
                    <span class="badge" style="font-size:10px;${catStyle}">${cat}</span>
                    ${unit ? `<span class="badge bg-secondary" style="font-size:10px">${unit}</span>` : ''}
                </div>
                <input type="hidden" name="items[${itemIndex}][item_id]" value="${id}">
            </td>
            <td class="text-center">
                <span class="badge ${stock < 10 ? 'bg-warning text-dark' : 'bg-success'} font-monospace">${stock}</span>
            </td>
            <td class="text-center py-2">
                <input type="number" name="items[${itemIndex}][quantity]"
                    class="form-control form-control-sm text-center qty-input"
                    style="width:85px;margin:auto"
                    value="1" min="1" max="${stock}" required>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-item rounded-2">
                    <i class="fas fa-times"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
        itemIndex++;

        picker.val('').trigger('change');
        updateCount();

        tr.querySelector('.btn-remove-item').addEventListener('click', function () {
            tr.remove();
            checkEmpty();
            updateCount();
        });
    });

    function updateCount() {
        const count = document.querySelectorAll('.item-row').length;
        document.getElementById('badge-count').textContent = count + ' item';
    }

    function checkEmpty() {
        if (!document.querySelectorAll('.item-row').length) {
            document.getElementById('items-body').innerHTML = `
                <tr id="empty-row">
                    <td colspan="4" class="text-center py-5 text-muted">
                        <i class="fas fa-cubes fa-2x mb-2 opacity-25 d-block"></i>
                        <div class="small">Belum ada barang dipilih.</div>
                    </td>
                </tr>`;
        }
    }

    document.getElementById('wmo-form').addEventListener('submit', function (event) {
        if (!document.querySelectorAll('.item-row').length) {
            Swal.fire({ icon:'warning', title:'Kosong', text:'Pilih minimal 1 barang terlebih dahulu.' });
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
