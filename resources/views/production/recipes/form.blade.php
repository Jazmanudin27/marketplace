@extends('layouts.app')
@section('title', isset($product) ? 'Edit Formula Produk' : 'Buat Formula Produk')
@section('page-title', isset($product) ? 'Edit Formula Produk' : 'Buat Formula Produk')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-11">
            <div class="card border shadow-sm overflow-hidden">
                <div class="card-header bg-primary bg-opacity-10 d-flex align-items-center border-bottom py-2 px-3">
                    <a href="{{ route('product_recipes.index') }}" class="btn btn-sm btn-link text-secondary me-2 p-0"><i class="fas fa-arrow-left"></i></a>
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="fas fa-flask me-2 text-primary"></i>{{ isset($product) ? 'Edit Formula: ' . $product->name : 'Buat Formula Produk Baru' }}
                        </h6>
                        <small class="text-muted d-block">Konfigurasi bahan baku (BOM) &amp; operasional jasa produksi</small>
                    </div>
                </div>

                <div class="card-body p-4">
                    <form action="{{ isset($product) ? route('product_recipes.update', $product->id) : route('product_recipes.store') }}" method="POST" id="form-recipe">
                        @csrf
                        @if(isset($product))
                            @method('PUT')
                        @endif

                        <div class="row g-3 mb-4 border-bottom pb-4">
                            <div class="col-md-8">
                                <label class="form-label fw-bold small text-dark">Produk Jadi / Target Hasil Produksi <span class="text-danger">*</span></label>
                                @if(isset($product))
                                    <input type="hidden" name="master_product_id" value="{{ $product->id }}">
                                    <div class="form-control form-control-sm bg-light fw-bold text-dark py-2">
                                        <i class="fas fa-box me-2 text-secondary"></i>{{ $product->name }} (SKU: {{ $product->sku ?? '—' }})
                                    </div>
                                @else
                                    <select name="master_product_id" class="form-select select-product-target" required style="width: 100%;">
                                        <option value=""></option>
                                        @foreach($products as $p)
                                            <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->sku ?? '—' }})</option>
                                        @endforeach
                                    </select>
                                @endif
                                <small class="text-muted mt-1 d-block">Pilih produk jadi yang akan diproduksi menggunakan formula resep ini</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-dark">Output Batch Standar (Pcs) <span class="text-danger">*</span></label>
                                <input type="number" name="batch_qty" id="batch_qty" class="form-control form-control-sm text-center" 
                                    min="1" value="{{ $recipe->batch_qty ?? 1 }}" required>
                                <small class="text-muted mt-1 d-block">Pembagi takaran formula untuk menghasilkan N pcs produk jadi</small>
                            </div>
                        </div>

                        <div class="row g-4">
                            <!-- Kiri: Formulasi Bahan Baku -->
                            <div class="col-lg-7 border-end pe-lg-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="fw-bold text-dark mb-0"><i class="fas fa-boxes me-2 text-primary"></i>1. Formulasi Bahan Baku (BOM)</h6>
                                    <button type="button" class="btn btn-sm btn-outline-primary fw-semibold" id="btn-add-bom-row">
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
                                            @if(isset($recipe) && $recipe->items->count() > 0)
                                                @foreach($recipe->items as $idx => $rItem)
                                                    <tr class="bom-row">
                                                        <td>
                                                            <select name="items[{{ $idx }}][inventory_item_id]" class="form-select form-select-sm select-bom-item" required style="width: 100%;">
                                                                <option value=""></option>
                                                                @foreach($inventoryItems as $item)
                                                                    <option value="{{ $item->id }}" data-unit="{{ $item->unit }}" data-price="{{ (float)$item->cost_price }}"
                                                                        {{ $rItem->inventory_item_id === $item->id ? 'selected' : '' }}>
                                                                        {{ $item->name }} ({{ $item->sku ?? '—' }})
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <div class="input-group input-group-sm">
                                                                <input type="number" step="0.0001" name="items[{{ $idx }}][quantity]" class="form-control qty-field" min="0.0001" value="{{ (float)$rItem->quantity }}" required>
                                                                <span class="input-group-text span-bom-unit" style="font-size:10px">{{ $rItem->inventoryItem->unit ?? 'PCS' }}</span>
                                                            </div>
                                                        </td>
                                                        <td class="text-center">
                                                            <button type="button" class="btn btn-sm btn-link text-danger btn-remove-bom-row"><i class="fas fa-trash-alt"></i></button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Kanan: Template Jasa Ahli -->
                            <div class="col-lg-5 ps-lg-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="fw-bold text-dark mb-0"><i class="fas fa-user-cog me-2 text-warning"></i>2. Jasa Ahli &amp; QC</h6>
                                    <button type="button" class="btn btn-sm btn-outline-warning fw-semibold" id="btn-add-labor-row">
                                        <i class="fas fa-plus me-1"></i> Tambah Jasa
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle table-sm" id="table-labor">
                                        <thead>
                                            <tr class="small text-uppercase text-muted" style="font-size:11px">
                                                <th style="width: 55%">Nama Jasa / QC</th>
                                                <th style="width: 35%">Biaya (Rp)</th>
                                                <th style="width: 10%" class="text-center"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @if(isset($recipe) && $recipe->labors->count() > 0)
                                                @foreach($recipe->labors as $idx => $rLabor)
                                                    <tr class="labor-row">
                                                        <td>
                                                            <select name="labors[{{ $idx }}][service_name]" class="form-select form-select-sm select-labor-item" required style="width: 100%;">
                                                                <option value=""></option>
                                                                @php $found = false; @endphp
                                                                @foreach($laborServices as $ls)
                                                                    @if($rLabor->service_name === $ls->name)
                                                                        @php $found = true; @endphp
                                                                    @endif
                                                                    <option value="{{ $ls->name }}" data-cost="{{ (int)$ls->default_cost }}" {{ $rLabor->service_name === $ls->name ? 'selected' : '' }}>
                                                                        {{ $ls->name }}
                                                                    </option>
                                                                @endforeach
                                                                @if(!$found && !empty($rLabor->service_name))
                                                                    <option value="{{ $rLabor->service_name }}" data-cost="{{ (int)$rLabor->default_cost }}" selected>
                                                                        {{ $rLabor->service_name }} (Kustom)
                                                                    </option>
                                                                @endif
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <input type="number" name="labors[{{ $idx }}][default_cost]" class="form-control form-control-sm cost-field" value="{{ (int)$rLabor->default_cost }}" min="0" required>
                                                        </td>
                                                        <td class="text-center">
                                                            <button type="button" class="btn btn-sm btn-link text-danger btn-remove-labor-row"><i class="fas fa-trash-alt"></i></button>
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
                                <div class="card bg-success bg-opacity-10 border border-success border-opacity-20 p-3 text-center">
                                    <div class="text-muted small text-uppercase fw-bold mb-1">Estimasi HPP Formula Terhitung (Per 1 Pcs)</div>
                                    <h3 class="fw-bold text-success font-monospace mb-0" id="live-hpp-preview">Rp 0</h3>
                                    <small class="text-muted mt-1" id="live-batch-label">Berdasarkan batch pembagi 1 pcs</small>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                            <a href="{{ route('product_recipes.index') }}" class="btn btn-secondary fw-semibold px-4 me-2">Batal</a>
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
                let laborRowIndex = {{ isset($recipe) && $recipe->labors->count() > 0 ? $recipe->labors->count() : 0 }};

                // Initialize select2 components
                $('.select-product-target').select2({
                    theme: 'bootstrap-5',
                    placeholder: '— Pilih Produk Jadi —',
                    allowClear: true
                });

                $('.select-bom-item').select2({
                    theme: 'bootstrap-5',
                    placeholder: '— Pilih Bahan Baku —',
                    allowClear: true
                });

                $('.select-labor-item').select2({
                    theme: 'bootstrap-5',
                    placeholder: '— Pilih Jasa / QC —',
                    allowClear: true
                });

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
                        const cost = parseFloat($(this).find('.cost-field').val()) || 0;
                        laborsTotal += cost;
                    });

                    const grandTotalHpp = (materialsTotal + laborsTotal) / batchQty;
                    $('#live-hpp-preview').text('Rp ' + Math.round(grandTotalHpp).toLocaleString('id-ID'));
                }

                // Bind events to trigger live recalculation
                $(document).on('input change', '#batch_qty, .qty-field, .cost-field, .select-bom-item', calculateLiveHpp);

                // --- Materials Row Actions ---
                $('#btn-add-bom-row').on('click', function() {
                    let optionsHtml = '<option value=""></option>';
                    inventoryItemsData.forEach(function(item) {
                        optionsHtml += `<option value="${item.id}" data-unit="${item.unit}" data-price="${parseFloat(item.cost_price) || 0}">${item.name} (${item.sku ?? '—'})</option>`;
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
                        allowClear: true
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
                        optionsHtml += `<option value="${item.name}" data-cost="${parseInt(item.default_cost) || 0}">${item.name}</option>`;
                    });

                    const rowHtml = `
                        <tr class="labor-row">
                            <td>
                                <select name="labors[${laborRowIndex}][service_name]" class="form-select form-select-sm select-labor-item" required style="width: 100%;">
                                    ${optionsHtml}
                                </select>
                            </td>
                            <td>
                                <input type="number" name="labors[${laborRowIndex}][default_cost]" class="form-control form-control-sm cost-field" min="0" value="0" required>
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
                        allowClear: true
                    });

                    laborRowIndex++;
                    calculateLiveHpp();
                });

                $(document).on('change', '.select-labor-item', function() {
                    const row = $(this).closest('.labor-row');
                    const selected = $(this).find('option:selected');
                    if (selected.val()) {
                        row.find('.cost-field').val(selected.data('cost'));
                    } else {
                        row.find('.cost-field').val(0);
                    }
                    calculateLiveHpp();
                });

                $(document).on('click', '.btn-remove-labor-row', function() {
                    $(this).closest('.labor-row').remove();
                    calculateLiveHpp();
                });

                // Initial calculation on page load
                calculateLiveHpp();
            });
        </script>
    @endpush
@endsection
