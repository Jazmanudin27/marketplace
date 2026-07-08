@extends('layouts.app')
@section('title', 'Catat Penerimaan Barang Langsung')
@section('page-title', 'Penerimaan Barang Langsung')

@section('content')
<div class="row g-3">
    {{-- Kiri: Konfigurasi --}}
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 bg-white mb-3">
            <div class="card-body p-3">
                <div class="d-flex align-items-center gap-2 mb-4">
                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                        style="width:38px;height:38px;background:linear-gradient(135deg,#10b981,#059669)">
                        <i class="fas fa-truck text-white small"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark small">Penerimaan Langsung</div>
                        <div class="text-muted" style="font-size:11px">Tanpa Purchase Order</div>
                    </div>
                </div>

                {{-- Jenis --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-muted">Jenis Penerimaan <span class="text-danger">*</span></label>
                    <select name="source" form="formGR" class="form-select form-select-sm @error('source') is-invalid @enderror" required>
                        <option value="direct"    {{ old('source','direct') === 'direct'    ? 'selected' : '' }}>🛒 Pembelian Langsung (Cash)</option>
                        <option value="walk_in"   {{ old('source') === 'walk_in'   ? 'selected' : '' }}>🏪 Walk-in / Beli di Toko</option>
                        <option value="emergency" {{ old('source') === 'emergency' ? 'selected' : '' }}>🚨 Pembelian Darurat</option>
                    </select>
                </div>

                {{-- Tanggal --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-muted">Tanggal Terima <span class="text-danger">*</span></label>
                    <input type="date" name="receipt_date" form="formGR"
                        class="form-control form-control-sm @error('receipt_date') is-invalid @enderror"
                        value="{{ old('receipt_date', date('Y-m-d')) }}" required>
                </div>

                {{-- Supplier (opsional) --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-muted">Supplier / Toko <span class="text-muted fw-normal">(opsional)</span></label>
                    <select name="supplier_id" form="formGR" class="form-select form-select-sm">
                        <option value="">— Tanpa Supplier / Toko Umum —</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}" {{ old('supplier_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                    <div class="form-text" style="font-size:11px">Kosongkan jika beli di toko tanpa nota supplier resmi.</div>
                </div>

                {{-- Departemen --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-muted">Departemen Penerima</label>
                    <select name="department_id" form="formGR" class="form-select form-select-sm">
                        <option value="">— Umum / Tidak Spesifik —</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Catatan --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-muted">Catatan / Keterangan</label>
                    <textarea name="notes" form="formGR" class="form-control form-control-sm" rows="2"
                        placeholder="No. struk, keterangan pembelian...">{{ old('notes') }}</textarea>
                </div>

                <div class="alert py-2 px-3 small mb-0"
                    style="background:#ecfdf5;border:1px solid #6ee7b7;color:#065f46;border-radius:8px">
                    <i class="fas fa-check-circle me-1"></i>
                    Stok akan <strong>langsung bertambah</strong> setelah simpan.
                </div>
            </div>
        </div>
        <a href="{{ route('goods_receipts.index') }}" class="btn btn-outline-secondary btn-sm w-100">
            <i class="fas fa-arrow-left me-1"></i> Kembali
        </a>
    </div>

    {{-- Kanan: Pilih Barang --}}
    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-3 bg-white">
            <div class="card-header border-0 py-3 px-4"
                style="background:linear-gradient(135deg,#10b981,#059669)">
                <h6 class="fw-bold text-white mb-0">
                    <i class="fas fa-boxes me-2"></i>Pilih Barang yang Diterima
                </h6>
            </div>
            <div class="card-body p-4">
                @if($errors->any())
                    <div class="alert alert-danger py-2 small">
                        @foreach($errors->all() as $error)
                            <div><i class="fas fa-exclamation-circle me-1"></i>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <form action="{{ route('goods_receipts.store') }}" method="POST" id="formGR">
                    @csrf

                    {{-- Tabel barang per tipe --}}
                    @php $idx = 0; @endphp
                    @foreach($inventoryItems as $type => $items)
                        @php
                            $typeInfo = match($type) {
                                'bahan'     => ['icon' => '🌾', 'label' => 'Bahan Baku',   'bg' => '#fef3c7', 'text' => '#92400e'],
                                'kemasan'   => ['icon' => '📦', 'label' => 'Kemasan',       'bg' => '#dbeafe', 'text' => '#1e40af'],
                                'atk'       => ['icon' => '✏️',  'label' => 'ATK',           'bg' => '#d1fae5', 'text' => '#065f46'],
                                'inventaris'=> ['icon' => '🖥️',  'label' => 'Inventaris',    'bg' => '#f3e8ff', 'text' => '#6d28d9'],
                                default     => ['icon' => '📋', 'label' => ucfirst($type), 'bg' => '#f1f5f9', 'text' => '#475569'],
                            };
                        @endphp
                        <div class="mb-4">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge rounded-pill px-3 py-1"
                                    style="background:{{ $typeInfo['bg'] }};color:{{ $typeInfo['text'] }};font-size:12px">
                                    {{ $typeInfo['icon'] }} {{ $typeInfo['label'] }}
                                </span>
                                <span class="text-muted small">{{ $items->count() }} item terdaftar</span>
                            </div>
                            <div class="border rounded-2 overflow-hidden">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead style="background:#f8fafc">
                                        <tr class="small text-muted text-uppercase">
                                            <th class="py-1 px-3" style="width:32px">
                                                <input type="checkbox" class="form-check-input check-all-type"
                                                    data-type="{{ $type }}">
                                            </th>
                                            <th>Nama Barang</th>
                                            <th class="text-center">Stok Saat Ini</th>
                                            <th class="text-center" style="min-width:110px">Qty Terima</th>
                                            <th class="text-end" style="min-width:120px">Harga Satuan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($items as $item)
                                            <tr>
                                                <td class="px-3">
                                                    <input type="checkbox" class="form-check-input item-check"
                                                        data-type="{{ $type }}"
                                                        data-index="{{ $idx }}"
                                                        id="chk_{{ $idx }}">
                                                    <input type="hidden" name="items[{{ $idx }}][item_type]" value="inventory" disabled>
                                                    <input type="hidden" name="items[{{ $idx }}][item_id]" value="{{ $item->id }}" disabled>
                                                </td>
                                                <td class="py-2">
                                                    <label for="chk_{{ $idx }}" class="d-block mb-0" style="cursor:pointer">
                                                        <div class="small fw-semibold text-dark">{{ $item->name }}</div>
                                                        <div class="font-monospace text-muted" style="font-size:11px">{{ $item->sku ?: '—' }}</div>
                                                    </label>
                                                    <span class="badge" style="font-size:10px;background:{{ $typeInfo['bg'] }};color:{{ $typeInfo['text'] }}">{{ $item->unit }}</span>
                                                </td>
                                                <td class="text-center">
                                                    @if($item->stock <= ($item->min_stock ?? 0) && $item->stock > 0)
                                                        <span class="badge bg-warning text-dark">{{ $item->stock }}</span>
                                                    @elseif($item->stock <= 0)
                                                        <span class="badge bg-danger">Habis</span>
                                                    @else
                                                        <span class="badge bg-success">{{ $item->stock }}</span>
                                                    @endif
                                                </td>
                                                <td class="text-center py-1 px-2">
                                                    <input type="number"
                                                        name="items[{{ $idx }}][quantity]"
                                                        class="form-control form-control-sm text-center item-qty"
                                                        data-index="{{ $idx }}"
                                                        style="width:90px;margin:auto"
                                                        min="1" value="1" disabled>
                                                </td>
                                                <td class="text-end py-1 px-2">
                                                    <div class="input-group input-group-sm" style="width:130px;margin-left:auto">
                                                        <span class="input-group-text" style="font-size:11px">Rp</span>
                                                        <input type="number"
                                                            name="items[{{ $idx }}][unit_price]"
                                                            class="form-control form-control-sm item-price"
                                                            data-index="{{ $idx }}"
                                                            min="0" step="100"
                                                            value="{{ old("items.{$idx}.unit_price", (int)$item->cost_price) }}"
                                                            disabled>
                                                    </div>
                                                </td>
                                            </tr>
                                            @php $idx++; @endphp
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach

                    @if($inventoryItems->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-box-open fa-2x mb-2 opacity-25 d-block"></i>
                            <div class="small">Belum ada barang terdaftar di master inventory.</div>
                            <a href="{{ route('inventory_items.create') }}" class="btn btn-sm btn-outline-primary mt-2">
                                + Tambah Barang
                            </a>
                        </div>
                    @endif

                    {{-- Summary --}}
                    <div class="border rounded-2 p-3 mt-2" style="background:#f8fafc">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="small text-muted" id="summaryText">
                                <i class="fas fa-info-circle me-1"></i>
                                <span id="selectedCount">0 barang dipilih</span>
                                · Est. Total: <strong id="estTotal">Rp 0</strong>
                            </div>
                            <button type="submit" class="btn fw-semibold px-4 text-white"
                                id="btnSubmit"
                                style="background:linear-gradient(135deg,#10b981,#059669)"
                                disabled>
                                <i class="fas fa-check-circle me-2"></i>Simpan & Tambah Stok
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function updateSummary() {
    let count = 0;
    let total = 0;

    document.querySelectorAll('.item-check:checked').forEach(function(chk) {
        const idx   = chk.dataset.index;
        const qty   = parseFloat(document.querySelector(`input[name="items[${idx}][quantity]"]`)?.value || 0);
        const price = parseFloat(document.querySelector(`input[name="items[${idx}][unit_price]"]`)?.value || 0);
        total += qty * price;
        count++;
    });

    document.getElementById('selectedCount').textContent = count + ' barang dipilih';
    document.getElementById('estTotal').textContent = 'Rp ' + total.toLocaleString('id-ID');
    document.getElementById('btnSubmit').disabled = (count === 0);
}

// Toggle per item
document.querySelectorAll('.item-check').forEach(function(chk) {
    chk.addEventListener('change', function() {
        const idx = this.dataset.index;
        const typeInput  = document.querySelector(`input[name="items[${idx}][item_type]"]`);
        const idInput    = document.querySelector(`input[name="items[${idx}][item_id]"]`);
        const qtyInput   = document.querySelector(`input[name="items[${idx}][quantity]"]`);
        const priceInput = document.querySelector(`input[name="items[${idx}][unit_price]"]`);

        const enable = this.checked;
        [typeInput, idInput, qtyInput, priceInput].forEach(el => {
            if (el) el.disabled = !enable;
        });
        updateSummary();
    });
});

// Check-all per tipe
document.querySelectorAll('.check-all-type').forEach(function(chkAll) {
    chkAll.addEventListener('change', function() {
        const type = this.dataset.type;
        document.querySelectorAll(`.item-check[data-type="${type}"]`).forEach(function(chk) {
            if (chk.checked !== chkAll.checked) {
                chk.checked = chkAll.checked;
                chk.dispatchEvent(new Event('change'));
            }
        });
    });
});

// Update total on qty/price change
document.addEventListener('input', function(e) {
    if (e.target.classList.contains('item-qty') || e.target.classList.contains('item-price')) {
        updateSummary();
    }
});

document.getElementById('formGR')?.addEventListener('submit', function() {
    const btn = document.getElementById('btnSubmit');
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';
    btn.disabled = true;
});
</script>
@endpush
