@extends('layouts.app')
@section('title', isset($product) ? 'Edit Formula Produk' : 'Buat Formula Produk')
@section('page-title', isset($product) ? 'Edit Formula Produk' : 'Buat Formula Produk')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-11">
            <div class="card border shadow-sm overflow-hidden">
                <div class="card-header bg-primary bg-opacity-10 d-flex align-items-center border-bottom py-2 px-3">
                    <a href="{{ route('product_recipes.index') }}" class="btn btn-sm btn-link text-secondary me-2 p-0"><i
                            class="fas fa-arrow-left"></i></a>
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">
                            <i
                                class="fas fa-flask me-2 text-primary"></i>{{ isset($product) ? 'Edit Formula: ' . $product->name : 'Buat Formula Produk Baru' }}
                        </h6>
                        <small class="text-muted d-block">Konfigurasi bahan baku (BOM) &amp; operasional jasa
                            produksi</small>
                    </div>
                </div>

                <div class="card-body p-4">
                    <form
                        action="{{ isset($product) ? route('product_recipes.update', $product->id) : route('product_recipes.store') }}"
                        method="POST" id="form-recipe">
                        @csrf
                        @if (isset($product))
                            @method('PUT')
                        @endif

                        <!-- Salin Formula Panel -->
                        <div class="card border bg-light shadow-sm p-3 mb-4 rounded-3">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-7">
                                    <label class="form-label fw-bold small text-dark mb-1"><i
                                            class="fas fa-copy me-1 text-primary"></i>Salin Formula Dari Produk Lain
                                        (Opsional)</label>
                                    <select id="select-copy-source" class="form-select select-copy-source"
                                        style="width: 100%;">
                                        @if($productsWithRecipe->isEmpty())
                                            <option value="" disabled>— Belum ada formula produk lain yang tersimpan —</option>
                                        @else
                                            <option value=""></option>
                                            @foreach ($productsWithRecipe as $p)
                                                <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->sku ?? '—' }})</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <button type="button" class="btn btn-outline-primary btn-sm px-3 w-100"
                                        id="btn-copy-recipe" style="height: 38px;">
                                        <i class="fas fa-clone me-1"></i>Salin Komponen &amp; Jasa
                                    </button>
                                </div>
                            </div>
                            <small class="text-muted mt-2 d-block"><i class="fas fa-info-circle me-1"></i>Fitur ini akan
                                menyalin seluruh bahan baku &amp; jasa dari resep produk lain untuk mempermudah input
                                massal.</small>
                        </div>

                        <div class="row g-3 mb-4 border-bottom pb-4">
                            <div class="col-md-8">
                                <label class="form-label fw-bold small text-dark">Produk Jadi / Target Hasil Produksi <span
                                        class="text-danger">*</span></label>
                                @if (isset($product))
                                    <input type="hidden" name="master_product_id" value="{{ $product->id }}">
                                    <div class="form-control form-control-sm bg-light fw-bold text-dark py-2">
                                        <i class="fas fa-box me-2 text-secondary"></i>{{ $product->name }} (SKU:
                                        {{ $product->sku ?? '—' }})
                                    </div>
                                @else
                                    <select name="master_product_id" class="form-select select-product-target" required
                                        style="width: 100%;">
                                        <option value=""></option>
                                        @foreach ($products as $p)
                                            <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->sku ?? '—' }})
                                            </option>
                                        @endforeach
                                    </select>
                                @endif
                                <small class="text-muted mt-1 d-block">Pilih produk jadi yang akan diproduksi menggunakan
                                    formula resep ini</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-dark">Output Batch Standar (Pcs) <span
                                        class="text-danger">*</span></label>
                                <input type="number" name="batch_qty" id="batch_qty"
                                    class="form-control form-control-sm text-center" min="1"
                                    value="{{ $recipe->batch_qty ?? 1 }}" required>
                                <small class="text-muted mt-1 d-block">Pembagi takaran formula untuk menghasilkan N pcs
                                    produk jadi</small>
                            </div>
                        </div>

                        <div class="row g-4">
                            <!-- Kiri: Formulasi Bahan Baku -->
                            <div class="col-lg-5 border-end pe-lg-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="fw-bold text-dark mb-0"><i class="fas fa-boxes me-2 text-primary"></i>1.
                                        Formulasi Bahan Baku (BOM)</h6>
                                    <button type="button" class="btn btn-sm btn-outline-primary fw-semibold"
                                        id="btn-add-bom-row">
                                        <i class="fas fa-plus me-1"></i> Tambah Bahan
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle table-sm" id="table-bom">
                                        <thead>
                                            <tr class="small text-uppercase text-muted" style="font-size:11px">
                                                <th style="width: 60%">Bahan Baku</th>
                                                <th style="width: 30%">Qty Takaran</th>
                                                <th style="width: 10%" class="text-center"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @if (isset($recipe) && $recipe->items->count() > 0)
                                                @foreach ($recipe->items as $idx => $rItem)
                                                    <tr class="bom-row">
                                                        <td>
                                                            <select name="items[{{ $idx }}][inventory_item_id]"
                                                                class="form-select form-select-sm select-bom-item" required
                                                                style="width: 100%;">
                                                                <option value=""></option>
                                                                @foreach ($inventoryItems as $item)
                                                                    <option value="{{ $item->id }}"
                                                                        data-unit="{{ $item->unit }}"
                                                                        data-price="{{ (float) $item->cost_price }}"
                                                                        {{ $rItem->inventory_item_id === $item->id ? 'selected' : '' }}>
                                                                        {{ $item->name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <div class="input-group input-group-sm">
                                                                <input type="number" step="0.0001"
                                                                    name="items[{{ $idx }}][quantity]"
                                                                    class="form-control qty-field" min="0.0001"
                                                                    value="{{ (float) $rItem->quantity }}" required>
                                                                <span class="input-group-text span-bom-unit"
                                                                    style="font-size:10px">{{ $rItem->inventoryItem->unit ?? 'PCS' }}</span>
                                                            </div>
                                                        </td>
                                                        <td class="text-center">
                                                            <button type="button"
                                                                class="btn btn-sm btn-link text-danger btn-remove-bom-row"><i
                                                                    class="fas fa-trash-alt"></i></button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Kanan: Template Jasa Ahli -->
                            <div class="col-lg-7 ps-lg-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="fw-bold text-dark mb-0"><i class="fas fa-user-cog me-2 text-warning"></i>2.
                                        Jasa Ahli &amp; QC</h6>
                                    <button type="button" class="btn btn-sm btn-outline-warning fw-semibold"
                                        id="btn-add-labor-row">
                                        <i class="fas fa-plus me-1"></i> Tambah Jasa
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle table-sm" id="table-labor">
                                        <thead>
                                            <tr class="small text-uppercase text-muted" style="font-size:11px">
                                                <th style="width: 40%">Nama Jasa / QC</th>
                                                <th style="width: 15%" class="text-center">Qty</th>
                                                <th style="width: 20%">Tarif (Rp)</th>
                                                <th style="width: 20%">Total (Rp)</th>
                                                <th style="width: 5%" class="text-center"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @if (isset($recipe) && $recipe->labors->count() > 0)
                                                @foreach ($recipe->labors as $idx => $rLabor)
                                                    <tr class="labor-row">
                                                        <td>
                                                            <select name="labors[{{ $idx }}][service_name]"
                                                                class="form-select form-select-sm select-labor-item"
                                                                required style="width: 100%;">
                                                                <option value=""></option>
                                                                @php $found = false; @endphp
                                                                @foreach ($laborServices as $ls)
                                                                    @if ($rLabor->service_name === $ls->name)
                                                                        @php $found = true; @endphp
                                                                    @endif
                                                                    <option value="{{ $ls->name }}"
                                                                        data-cost="{{ (int) $ls->default_cost }}"
                                                                        {{ $rLabor->service_name === $ls->name ? 'selected' : '' }}>
                                                                        {{ $ls->name }}
                                                                    </option>
                                                                @endforeach
                                                                @if (!$found && !empty($rLabor->service_name))
                                                                    <option value="{{ $rLabor->service_name }}"
                                                                        data-cost="{{ (int) ($rLabor->unit_cost ?? $rLabor->default_cost) }}"
                                                                        selected>
                                                                        {{ $rLabor->service_name }} (Kustom)
                                                                    </option>
                                                                @endif
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <input type="number" name="labors[{{ $idx }}][qty]"
                                                                class="form-control form-control-sm qty-labor-field text-center"
                                                                value="{{ $rLabor->qty ?? 1 }}" min="1" required>
                                                        </td>
                                                        <td>
                                                            <input type="text"
                                                                name="labors[{{ $idx }}][unit_cost]"
                                                                class="form-control form-control-sm unit-cost-field rupiah-mask"
                                                                value="{{ number_format($rLabor->unit_cost ?? $rLabor->default_cost, 0, ',', '.') }}"
                                                                required>
                                                        </td>
                                                        <td>
                                                            <input type="text"
                                                                class="form-control form-control-sm total-cost-field bg-light text-muted font-monospace"
                                                                value="{{ number_format($rLabor->default_cost, 0, ',', '.') }}"
                                                                readonly>
                                                        </td>
                                                        <td class="text-center">
                                                            <button type="button"
                                                                class="btn btn-sm btn-link text-danger btn-remove-labor-row"><i
                                                                    class="fas fa-trash-alt"></i></button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Live Cost Summary -->
                        <div class="row mt-4 pt-3 border-top justify-content-center">
                            <div class="col-md-6">
                                <div
                                    class="card bg-success bg-opacity-10 border border-success border-opacity-20 p-3 text-center">
                                    <div class="text-muted small text-uppercase fw-bold mb-1">Estimasi HPP Formula
                                        Terhitung (Per 1 Pcs)</div>
                                    <h3 class="fw-bold text-success font-monospace mb-0" id="live-hpp-preview">Rp 0</h3>
                                    <small class="text-muted mt-1" id="live-batch-label">Berdasarkan batch pembagi 1
                                        pcs</small>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                            <a href="{{ route('product_recipes.index') }}"
                                class="btn btn-secondary fw-semibold px-4 me-2">Batal</a>
                            <button type="submit" class="btn btn-success fw-bold px-4">
                                <i class="fas fa-save me-1"></i> Simpan Formula Produk
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                // Pass arrays safely to Javascript
                const inventoryItemsData = @json($inventoryItems ?? []);
                const laborServicesData = @json($laborServices ?? []);

                let bomRowIndex = {{ isset($recipe) && $recipe->items->count() > 0 ? $recipe->items->count() : 0 }};
                let laborRowIndex =
                    {{ isset($recipe) && $recipe->labors->count() > 0 ? $recipe->labors->count() : 0 }};

                // Initialize select2 components
                $('.select-product-target').select2({
                    theme: 'bootstrap-5',
                    placeholder: '— Pilih Produk Jadi —',
                    allowClear: true,
                    width: '100%'
                });

                $('.select-copy-source').select2({
                    theme: 'bootstrap-5',
                    placeholder: '— Cari & Pilih Produk Sumber Formula —',
                    allowClear: true,
                    width: '100%'
                });

                $('.select-bom-item').select2({
                    theme: 'bootstrap-5',
                    placeholder: '— Pilih Bahan Baku —',
                    allowClear: true,
                    width: '100%'
                });

                $('.select-labor-item').select2({
                    theme: 'bootstrap-5',
                    placeholder: '— Pilih Jasa / QC —',
                    allowClear: true,
                    width: '100%'
                });

                // Helpers for formatting and cleaning numbers
                const cleanNumber = (val) => parseFloat(String(val).replace(/[^0-9]/g, '')) || 0;
                const formatNumber = (num) => parseFloat(num).toLocaleString('id-ID');

                // --- Live HPP Calculator ---
                function calculateLiveHpp() {
                    const batchQty = parseInt($('#batch_qty').val()) || 1;
                    $('#live-batch-label').text(`Berdasarkan pembagian Output Batch: ${batchQty} Pcs`);

                    let materialsTotal = 0;
                    $('#table-bom tbody tr').each(function() {
                        const selectedOption = $(this).find('.select-bom-item option:selected');
                        const qty = parseFloat($(this).find('.qty-field').val()) || 0;
                        if (selectedOption.val()) {
                            const price = parseFloat(selectedOption.data('price')) || 0;
                            materialsTotal += (qty * price);
                        }
                    });

                    let laborsTotal = 0;
                    $('#table-labor tbody tr').each(function() {
                        const qty = parseInt($(this).find('.qty-labor-field').val()) || 1;
                        const unitCost = cleanNumber($(this).find('.unit-cost-field').val());
                        const totalCost = qty * unitCost;

                        // Update row total cost display
                        $(this).find('.total-cost-field').val(formatNumber(totalCost));

                        laborsTotal += totalCost;
                    });

                    const grandTotalHpp = (materialsTotal + laborsTotal) / batchQty;
                    $('#live-hpp-preview').text('Rp ' + Math.round(grandTotalHpp).toLocaleString('id-ID'));
                }

                // Bind events to trigger live recalculation
                $(document).on('input change',
                    '#batch_qty, .qty-field, .qty-labor-field, .unit-cost-field, .select-bom-item', calculateLiveHpp
                );

                // --- Materials Row Actions ---
                $('#btn-add-bom-row').on('click', function() {
                    let optionsHtml = '<option value=""></option>';
                    inventoryItemsData.forEach(function(item) {
                        optionsHtml +=
                            `<option value="${item.id}" data-unit="${item.unit}" data-price="${parseFloat(item.cost_price) || 0}">${item.name}</option>`;
                    });

                    const rowHtml = `
                        <tr class="bom-row">
                            <td>
                                <select name="items[${bomRowIndex}][inventory_item_id]" class="form-select form-select-sm select-bom-item" required style="width: 100%;">
                                    ${optionsHtml}
                                </select>
                            </td>
                            <td>
                                <div class="input-group input-group-sm">
                                    <input type="number" step="0.0001" name="items[${bomRowIndex}][quantity]" class="form-control qty-field" min="0.0001" value="1" required>
                                    <span class="input-group-text span-bom-unit" style="font-size:10px">—</span>
                                </div>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-link text-danger btn-remove-bom-row"><i class="fas fa-trash-alt"></i></button>
                            </td>
                        </tr>
                    `;

                    $('#table-bom tbody').append(rowHtml);

                    const $addedRow = $('#table-bom tbody tr:last-child');
                    $addedRow.find('.select-bom-item').select2({
                        theme: 'bootstrap-5',
                        placeholder: '— Pilih Bahan Baku —',
                        allowClear: true,
                        width: '100%'
                    });

                    bomRowIndex++;
                    calculateLiveHpp();
                });

                $(document).on('change', '.select-bom-item', function() {
                    const row = $(this).closest('.bom-row');
                    const selected = $(this).find('option:selected');
                    if (selected.val()) {
                        row.find('.span-bom-unit').text(selected.data('unit'));
                    } else {
                        row.find('.span-bom-unit').text('—');
                    }
                    calculateLiveHpp();
                });

                $(document).on('click', '.btn-remove-bom-row', function() {
                    $(this).closest('.bom-row').remove();
                    calculateLiveHpp();
                });

                // --- Labor Row Actions ---
                $('#btn-add-labor-row').on('click', function() {
                    let optionsHtml = '<option value=""></option>';
                    laborServicesData.forEach(function(item) {
                        optionsHtml +=
                            `<option value="${item.name}" data-cost="${parseInt(item.default_cost) || 0}">${item.name}</option>`;
                    });

                    const rowHtml = `
                        <tr class="labor-row">
                            <td>
                                <select name="labors[${laborRowIndex}][service_name]" class="form-select form-select-sm select-labor-item" required style="width: 100%;">
                                    ${optionsHtml}
                                </select>
                            </td>
                            <td>
                                <input type="number" name="labors[${laborRowIndex}][qty]" class="form-control form-control-sm qty-labor-field text-center" min="1" value="1" required>
                            </td>
                            <td>
                                <input type="text" name="labors[${laborRowIndex}][unit_cost]" class="form-control form-control-sm unit-cost-field rupiah-mask" value="0" required>
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm total-cost-field bg-light text-muted font-monospace" value="0" readonly>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-link text-danger btn-remove-labor-row"><i class="fas fa-trash-alt"></i></button>
                            </td>
                        </tr>
                    `;

                    $('#table-labor tbody').append(rowHtml);

                    const $addedRow = $('#table-labor tbody tr:last-child');
                    $addedRow.find('.select-labor-item').select2({
                        theme: 'bootstrap-5',
                        placeholder: '— Pilih Jasa / QC —',
                        allowClear: true,
                        width: '100%'
                    });

                    laborRowIndex++;
                    calculateLiveHpp();
                });

                $(document).on('change', '.select-labor-item', function() {
                    const row = $(this).closest('.labor-row');
                    const selected = $(this).find('option:selected');
                    if (selected.val()) {
                        row.find('.unit-cost-field').val(formatNumber(selected.data('cost')));
                    } else {
                        row.find('.unit-cost-field').val(0);
                    }
                    calculateLiveHpp();
                });

                $(document).on('click', '.btn-remove-labor-row', function() {
                    $(this).closest('.labor-row').remove();
                    calculateLiveHpp();
                });

                // Masking inputs
                const handleRupiahInput = function(e) {
                    let cursorPosition = e.target.selectionStart;
                    let originalLength = e.target.value.length;
                    let cleanValue = e.target.value.replace(/[^0-9]/g, '');
                    if (cleanValue === '') {
                        $(e.target).val('');
                        return;
                    }
                    let formatted = formatNumber(cleanValue);
                    $(e.target).val(formatted);

                    let newLength = formatted.length;
                    cursorPosition = cursorPosition + (newLength - originalLength);
                    e.target.setSelectionRange(cursorPosition, cursorPosition);
                };

                $(document).on('input', '.rupiah-mask', handleRupiahInput);

                // Strip thousand separators before form submit
                $('#form-recipe').on('submit', function() {
                    $('.rupiah-mask').each(function() {
                        const clean = $(this).val().replace(/[^0-9]/g, '');
                        $(this).val(clean);
                    });
                });

                // --- Copy Formula AJAX Handler ---
                $('#btn-copy-recipe').on('click', function() {
                    const sourceProductId = $('#select-copy-source').val();
                    if (!sourceProductId) {
                        alert('Silakan pilih produk sumber terlebih dahulu.');
                        return;
                    }

                    if (confirm(
                            'Menyalin formula akan menghapus komponen bahan baku dan jasa yang sudah Anda masukkan saat ini di form. Apakah Anda yakin ingin melanjutkan?'
                        )) {
                        // Show loading state
                        const $btn = $(this);
                        $btn.prop('disabled', true).html(
                            '<i class="fas fa-spinner fa-spin me-1"></i>Menyalin...');

                        $.get('/api/product-recipes/' + sourceProductId + '/json')
                            .done(function(data) {
                                // 1. Clear current tables
                                $('#table-bom tbody').empty();
                                $('#table-labor tbody').empty();
                                bomRowIndex = 0;
                                laborRowIndex = 0;

                                // 2. Update Batch Qty
                                $('#batch_qty').val(data.batch_qty);

                                // 3. Populate Materials (BOM)
                                if (data.items && data.items.length > 0) {
                                    data.items.forEach(function(item) {
                                        let optionsHtml = '<option value=""></option>';
                                        inventoryItemsData.forEach(function(inv) {
                                            optionsHtml +=
                                                `<option value="${inv.id}" data-unit="${inv.unit}" data-price="${parseFloat(inv.cost_price) || 0}" ${inv.id == item.inventory_item_id ? 'selected' : ''}>${inv.name}</option>`;
                                        });

                                        const rowHtml = `
                                            <tr class="bom-row">
                                                <td>
                                                    <select name="items[${bomRowIndex}][inventory_item_id]" class="form-select form-select-sm select-bom-item" required style="width: 100%;">
                                                        ${optionsHtml}
                                                    </select>
                                                </td>
                                                <td>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" step="0.0001" name="items[${bomRowIndex}][quantity]" class="form-control qty-field" min="0.0001" value="${item.quantity}" required>
                                                        <span class="input-group-text span-bom-unit" style="font-size:10px">—</span>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-sm btn-link text-danger btn-remove-bom-row"><i class="fas fa-trash-alt"></i></button>
                                                </td>
                                            </tr>
                                        `;
                                        $('#table-bom tbody').append(rowHtml);

                                        const $addedRow = $('#table-bom tbody tr:last-child');
                                         $addedRow.find('.select-bom-item').select2({
                                             theme: 'bootstrap-5',
                                             placeholder: '— Pilih Bahan Baku —',
                                             allowClear: true,
                                             width: '100%'
                                         });

                                        // Set unit span
                                        const selectedOpt = $addedRow.find(
                                            '.select-bom-item option:selected');
                                        if (selectedOpt.val()) {
                                            $addedRow.find('.span-bom-unit').text(selectedOpt.data(
                                                'unit'));
                                        }

                                        bomRowIndex++;
                                    });
                                }

                                // 4. Populate Labors (Jasa)
                                if (data.labors && data.labors.length > 0) {
                                    data.labors.forEach(function(labor) {
                                        let optionsHtml = '<option value=""></option>';
                                        let found = false;
                                        laborServicesData.forEach(function(ls) {
                                            if (ls.name === labor.service_name) found =
                                                true;
                                            optionsHtml +=
                                                `<option value="${ls.name}" data-cost="${parseInt(ls.default_cost) || 0}" ${ls.name === labor.service_name ? 'selected' : ''}>${ls.name}</option>`;
                                        });
                                        if (!found && labor.service_name) {
                                            optionsHtml +=
                                                `<option value="${labor.service_name}" data-cost="${labor.unit_cost}" selected>${labor.service_name} (Kustom)</option>`;
                                        }

                                        const rowHtml = `
                                            <tr class="labor-row">
                                                <td>
                                                    <select name="labors[${laborRowIndex}][service_name]" class="form-select form-select-sm select-labor-item" required style="width: 100%;">
                                                        ${optionsHtml}
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="number" name="labors[${laborRowIndex}][qty]" class="form-control form-control-sm qty-labor-field text-center" min="1" value="${labor.qty}" required>
                                                </td>
                                                <td>
                                                    <input type="text" name="labors[${laborRowIndex}][unit_cost]" class="form-control form-control-sm unit-cost-field rupiah-mask" value="${formatNumber(labor.unit_cost)}" required>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control form-control-sm total-cost-field bg-light text-muted font-monospace" value="${formatNumber(labor.default_cost)}" readonly>
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-sm btn-link text-danger btn-remove-labor-row"><i class="fas fa-trash-alt"></i></button>
                                                </td>
                                            </tr>
                                        `;
                                        $('#table-labor tbody').append(rowHtml);

                                        const $addedRow = $('#table-labor tbody tr:last-child');
                                        $addedRow.find('.select-labor-item').select2({
                                            theme: 'bootstrap-5',
                                            placeholder: '— Pilih Jasa / QC —',
                                            allowClear: true,
                                            width: '100%'
                                        });

                                        laborRowIndex++;
                                    });
                                }

                                calculateLiveHpp();
                            })
                            .fail(function(xhr) {
                                alert('Gagal menyalin resep: ' + (xhr.responseJSON?.error ||
                                    'Kesalahan sistem.'));
                            })
                            .always(function() {
                                $btn.prop('disabled', false).html(
                                    '<i class="fas fa-clone me-1"></i>Salin Komponen &amp; Jasa');
                            });
                    }
                });

                // Initial calculation on page load
                calculateLiveHpp();
            });
        </script>
    @endpush
@endsection
