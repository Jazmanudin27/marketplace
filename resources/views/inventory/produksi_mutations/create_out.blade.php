@extends('layouts.app')
@section('title', 'Catat Barang Keluar - Produksi')
@section('page-title', 'Barang Keluar / Pemakaian')

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
<form action="{{ route('produksi_mutations.store_out') }}" method="POST" id="wmo-form">
@csrf

<div class="row g-3">
    {{-- Kiri: Detail Pengeluaran --}}
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 bg-white mb-3 sticky-top" style="top:80px">
            <div class="card-header border-0 py-3 px-4" style="background:linear-gradient(135deg,#f59e0b,#d97706)">
                <h6 class="fw-bold text-white mb-0">
                    <i class="fas fa-sign-out-alt me-2"></i>Detail Pengeluaran / Pemakaian
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

                {{-- Asal --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-muted">Asal (Gudang)</label>
                    <input type="text" class="form-control" value="Gudang Produksi" readonly disabled>
                </div>

                {{-- Departemen Tujuan --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-muted">
                        Tujuan / Divisi Pengguna <span class="text-danger">*</span>
                    </label>
                    <select name="to_department_id" id="sel-to-dept" class="form-select"
                        data-placeholder="— Pilih tujuan (opsional) —">
                        <option value="">Eksternal / Pemakaian Langsung</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('to_department_id') == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Tanggal --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-muted">
                        Tanggal Pemakaian <span class="text-danger">*</span>
                    </label>
                    <input type="date" name="mutation_date" class="form-control" 
                        value="{{ old('mutation_date', date('Y-m-d')) }}" required>
                </div>

                {{-- Catatan --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-muted">Tujuan Pemakaian / Catatan</label>
                    <textarea name="notes" class="form-control" rows="3" 
                        placeholder="Misal: Pemakaian bahan baku untuk pesanan X..." required>{{ old('notes') }}</textarea>
                </div>

                <hr class="text-muted opacity-25">

                <button type="submit" class="btn btn-warning w-100 fw-bold py-2 shadow-sm text-white" style="background:#d97706">
                    <i class="fas fa-save me-1"></i> Simpan Transaksi
                </button>
                <a href="{{ route('produksi_mutations.index_out') }}" class="btn btn-outline-secondary w-100 fw-semibold mt-2">
                    Batal
                </a>
            </div>
        </div>
    </div>

    {{-- Kanan: Daftar Barang --}}
    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-3 bg-white mb-3">
            <div class="card-header border-0 py-3 px-4 bg-light d-flex align-items-center justify-content-between">
                <h6 class="fw-bold text-dark mb-0">Daftar Barang Keluar / Dipakai</h6>
                <button type="button" class="btn btn-sm btn-outline-warning fw-semibold" id="btn-add-item">
                    <i class="fas fa-plus me-1"></i> Tambah Baris
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="table-items">
                        <thead class="table-light">
                            <tr class="small text-uppercase text-muted">
                                <th class="ps-4" style="width:50%">Pilih Barang</th>
                                <th style="width:20%">Jumlah (Qty)</th>
                                <th style="width:25%">Keterangan Item</th>
                                <th class="text-center pe-4" style="width:5%"></th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Baris dinamis akan dirender di sini --}}
                        </tbody>
                    </table>
                </div>

                <div id="empty-state" class="text-center py-5 text-muted">
                    <i class="fas fa-box-open fa-2x mb-2 opacity-50"></i>
                    <p class="mb-0 small">Belum ada barang yang ditambahkan. Silakan klik tombol <strong>Tambah Baris</strong>.</p>
                </div>
            </div>
        </div>
    </div>
</div>
</form>

{{-- Template Baris Barang --}}
<template id="row-template">
    <tr class="item-row">
        <td class="ps-4 py-3">
            <select name="items[{index}][item_id]" class="form-select select-item" required>
                <option value=""></option>
                @foreach($inventoryItems as $item)
                    <option value="{{ $item->id }}" 
                        data-sku="{{ $item->sku ?: '—' }}" 
                        data-unit="{{ $item->unit }}" 
                        data-type="{{ strtoupper($item->type) }}"
                        data-stock="{{ $item->stock }}">
                        {{ $item->name }}
                    </option>
                @endforeach
            </select>
        </td>
        <td>
            <div class="input-group">
                <input type="number" name="items[{index}][quantity]" class="form-control input-qty" min="1" value="1" required>
                <span class="input-group-text small text-muted span-unit" style="font-size:11px">—</span>
            </div>
            <div class="text-muted small mt-1 info-stok" style="font-size:10px">Stok tersedia: 0</div>
        </td>
        <td>
            <input type="text" name="items[{index}][notes]" class="form-control form-control-sm" placeholder="Keterangan opsional">
        </td>
        <td class="text-center pe-4">
            <button type="button" class="btn btn-sm btn-link text-danger btn-remove-row" style="text-decoration:none">
                <i class="fas fa-trash-alt"></i>
            </button>
        </td>
    </tr>
</template>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let rowIndex = 0;
    const tableBody = $('#table-items tbody');
    const rowTemplate = $('#row-template').html();
    const emptyState = $('#empty-state');

    function checkEmptyState() {
        if (tableBody.children().length === 0) {
            emptyState.show();
        } else {
            emptyState.hide();
        }
    }

    function initSelect2(row) {
        row.find('.select-item').select2({
            theme: 'bootstrap-5',
            placeholder: '— Cari nama / SKU barang —',
            allowClear: true,
            dropdownParent: tableBody,
            templateResult: formatItemOption,
            templateSelection: formatItemSelection
        });
    }

    function formatItemOption(opt) {
        if (!opt.id) return opt.text;
        const el = $(opt.element);
        const sku = el.data('sku');
        const type = el.data('type');
        const stock = el.data('stock');
        const unit = el.data('unit');

        return $(`
            <div class="d-flex flex-column">
                <span class="item-option-name">${opt.text}</span>
                <span class="item-option-meta">SKU: ${sku} | Tipe: ${type} | Stok: ${stock} ${unit}</span>
            </div>
        `);
    }

    function formatItemSelection(opt) {
        if (!opt.id) return opt.text;
        const el = $(opt.element);
        const sku = el.data('sku');
        return opt.text + ' (' + sku + ')';
    }

    $('#btn-add-item').on('click', function() {
        const html = rowTemplate.replace(/{index}/g, rowIndex);
        const newRow = $(html);
        tableBody.append(newRow);
        initSelect2(newRow);
        rowIndex++;
        checkEmptyState();
    });

    tableBody.on('change', '.select-item', function() {
        const row = $(this).closest('.item-row');
        const selected = $(this).find('option:selected');
        if (selected.val()) {
            row.find('.span-unit').text(selected.data('unit'));
            row.find('.info-stok').text('Stok tersedia: ' + selected.data('stock') + ' ' + selected.data('unit'));
            row.find('.input-qty').attr('max', selected.data('stock'));
        } else {
            row.find('.span-unit').text('—');
            row.find('.info-stok').text('Stok tersedia: 0');
            row.find('.input-qty').removeAttr('max');
        }
    });

    tableBody.on('click', '.btn-remove-row', function() {
        const row = $(this).closest('.item-row');
        row.find('.select-item').select2('destroy');
        row.remove();
        checkEmptyState();
    });

    // Add first row automatically
    $('#btn-add-item').trigger('click');
});
</script>
@endpush
