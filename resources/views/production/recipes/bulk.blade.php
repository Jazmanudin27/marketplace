@extends('layouts.app')
@section('title', 'Input Formula Massal')
@section('page-title', 'Input Formula Massal')

@push('styles')
    <style>
        .product-recipe-card {
            transition: border-color 0.2s, box-shadow 0.2s;
            border-color: #e2e8f0;
        }
        .product-recipe-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.02);
        }
        .table-mini th {
            font-size: 11px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 600;
            padding-bottom: 6px;
        }
        .save-status-badge {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        .hpp-box {
            background-color: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            padding: 12px;
            min-width: 180px;
        }
        .form-select-sm, .form-control-sm {
            font-size: 0.8rem;
            padding-top: 0.25rem;
            padding-bottom: 0.25rem;
        }
        .input-group-text-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
    </style>
@endpush

@section('content')
    <div class="row">
        <div class="col-md-12">
            <!-- Filter Panel -->
            <div class="card border shadow-sm p-3 mb-4 rounded-3 bg-white">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h5 class="fw-bold mb-0 text-dark">
                            <i class="fas fa-flask me-2 text-primary"></i>Input &amp; Konfigurasi Formula Massal
                        </h5>
                        <small class="text-muted d-block">Edit dan simpan formula (BOM &amp; Jasa) produk secara real-time. Perubahan tersimpan otomatis.</small>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('product_recipes.index') }}" class="btn btn-outline-secondary btn-sm px-3 rounded-3">
                            <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar
                        </a>
                    </div>
                </div>

                <hr class="my-3 text-muted">

                <form method="GET" action="{{ route('product_recipes.bulk') }}" class="row g-2 align-items-end">
                    <div class="col-12 col-md-5 col-lg-4">
                        <label class="form-label small fw-bold text-dark">Cari Produk / SKU</label>
                        <input type="text" name="search" class="form-control form-control-sm"
                            placeholder="Ketik nama atau SKU..." value="{{ request('search') }}">
                    </div>
                    <div class="col-12 col-md-3 col-lg-3">
                        <label class="form-label small fw-bold text-dark">Status Formula</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">Semua Status</option>
                            <option value="has_recipe" {{ request('status') === 'has_recipe' ? 'selected' : '' }}>Sudah Ada Formula</option>
                            <option value="no_recipe" {{ request('status') === 'no_recipe' ? 'selected' : '' }}>Belum Ada Formula</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-4 col-lg-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm px-4">
                            <i class="fas fa-search me-1"></i> Filter
                        </button>
                        @if (request()->anyFilled(['search', 'status']))
                            <a href="{{ route('product_recipes.bulk') }}" class="btn btn-secondary btn-sm px-3">
                                <i class="fas fa-times me-1"></i> Reset
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Product List -->
            @forelse($products as $p)
                @php
                    $recipe = $p->activeRecipe;
                @endphp
                <div class="card border shadow-sm mb-4 product-recipe-card bg-white" data-product-id="{{ $p->id }}">
                    <!-- Card Header -->
                    <div class="card-header bg-light bg-opacity-50 d-flex justify-content-between align-items-center py-2 px-3 border-bottom">
                        <div>
                            <h6 class="fw-bold mb-0 text-dark d-flex align-items-center flex-wrap gap-2">
                                <i class="fas fa-box text-secondary"></i>
                                {{ $p->name }}
                                <span class="badge bg-secondary font-monospace" style="font-size: 10px;">SKU: {{ $p->sku ?? '—' }}</span>
                            </h6>
                        </div>
                        <div>
                            <span class="badge save-status-badge {{ $recipe ? 'bg-success text-white' : 'bg-secondary text-white' }} px-3 py-1.5 rounded-pill">
                                @if ($recipe)
                                    <i class="fas fa-check-circle me-1"></i> Terpasang
                                @else
                                    <i class="fas fa-info-circle me-1"></i> Belum ada formula
                                @endif
                            </span>
                        </div>
                    </div>
                    
                    <!-- Card Body -->
                    <div class="card-body p-3">
                        <div class="row g-3">
                            <!-- Batch Qty and HPP Info -->
                            <div class="col-12 border-bottom pb-2 d-flex justify-content-between align-items-center flex-wrap gap-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div style="max-width: 180px;">
                                        <label class="form-label fw-bold small text-dark mb-1">Output Batch Standar</label>
                                        <div class="input-group input-group-sm">
                                            <input type="number" class="form-control text-center batch-qty-input select-field" 
                                                min="1" value="{{ $recipe->batch_qty ?? 1 }}" required>
                                            <span class="input-group-text">Pcs</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="hpp-box d-flex flex-column align-items-end">
                                    <span class="text-muted small text-uppercase fw-bold mb-1" style="font-size: 10px;">Estimasi HPP Per Pcs</span>
                                    <h5 class="fw-bold text-primary mb-0 font-monospace hpp-display">Rp 0</h5>
                                </div>
                            </div>

                            <!-- Left Column: BOM Materials -->
                            <div class="col-lg-6 border-end pe-lg-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold text-dark small"><i class="fas fa-boxes me-1.5 text-primary"></i>1. Bahan Baku &amp; Kemasan</span>
                                    <button type="button" class="btn btn-xs btn-outline-primary btn-sm px-2 py-0.5 fw-semibold btn-add-bom" style="font-size: 11px;">
                                        <i class="fas fa-plus me-1"></i> Tambah
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover table-borderless table-mini align-middle mb-0" id="table-bom-{{ $p->id }}">
                                        <thead>
                                            <tr>
                                                <th style="width: 55%">Bahan Baku</th>
                                                <th style="width: 35%">Qty Takaran</th>
                                                <th style="width: 10%" class="text-center"></th>
                                            </tr>
                                        </thead>
                                        <tbody class="bom-tbody">
                                            @if ($recipe && $recipe->items->count() > 0)
                                                @foreach ($recipe->items as $idx => $rItem)
                                                    <tr class="bom-row" data-index="{{ $idx }}">
                                                        <td>
                                                            <select name="items[{{ $idx }}][inventory_item_id]" class="form-select form-select-sm bom-item-select select-field" required>
                                                                <option value="">— Pilih Bahan Baku —</option>
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
                                                                <input type="number" step="0.0001" name="items[{{ $idx }}][quantity]" class="form-control qty-field select-field" min="0.0001" value="{{ (float) $rItem->quantity }}" required placeholder="0.0">
                                                                <span class="input-group-text bom-unit-badge bg-light" style="font-size: 10px;">{{ $rItem->inventoryItem->unit ?? '—' }}</span>
                                                            </div>
                                                        </td>
                                                        <td class="text-center">
                                                            <button type="button" class="btn btn-sm btn-link text-danger p-0 btn-remove-row"><i class="fas fa-trash-alt"></i></button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Right Column: Labor & QC -->
                            <div class="col-lg-6 ps-lg-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold text-dark small"><i class="fas fa-user-cog me-1.5 text-warning"></i>2. Jasa Operasional / QC</span>
                                    <button type="button" class="btn btn-xs btn-outline-warning btn-sm px-2 py-0.5 fw-semibold btn-add-labor" style="font-size: 11px;">
                                        <i class="fas fa-plus me-1"></i> Tambah
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover table-borderless table-mini align-middle mb-0" id="table-labor-{{ $p->id }}">
                                        <thead>
                                            <tr>
                                                <th style="width: 40%">Nama Jasa</th>
                                                <th style="width: 20%">Qty</th>
                                                <th style="width: 30%">Biaya Satuan</th>
                                                <th style="width: 10%" class="text-center"></th>
                                            </tr>
                                        </thead>
                                        <tbody class="labor-tbody">
                                            @if ($recipe && $recipe->labors->count() > 0)
                                                @foreach ($recipe->labors as $idx => $rLabor)
                                                    <tr class="labor-row" data-index="{{ $idx }}">
                                                        <td>
                                                            <input type="text" name="labors[{{ $idx }}][service_name]" class="form-control form-control-sm srv-name-field select-field" list="srv-list-{{ $p->id }}" value="{{ $rLabor->service_name }}" required placeholder="Nama Jasa...">
                                                        </td>
                                                        <td>
                                                            <input type="number" name="labors[{{ $idx }}][qty]" class="form-control form-control-sm labor-qty-field select-field" min="1" value="{{ $rLabor->qty }}" required>
                                                        </td>
                                                        <td>
                                                            <input type="number" name="labors[{{ $idx }}][unit_cost]" class="form-control form-control-sm labor-cost-field select-field" min="0" value="{{ (float) $rLabor->unit_cost }}" required>
                                                        </td>
                                                        <td class="text-center">
                                                            <button type="button" class="btn btn-sm btn-link text-danger p-0 btn-remove-row"><i class="fas fa-trash-alt"></i></button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @endif
                                        </tbody>
                                    </table>
                                    
                                    {{-- Data list of services for easy selection --}}
                                    <datalist id="srv-list-{{ $p->id }}">
                                        @foreach ($laborServices as $s)
                                            <option value="{{ $s->name }}" data-cost="{{ (float) $s->default_cost }}"></option>
                                        @endforeach
                                    </datalist>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="card border p-5 text-center bg-white">
                    <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                    <h6 class="fw-bold text-dark">Tidak Ada Produk Ditemukan</h6>
                    <p class="text-muted small">Coba cari dengan kata kunci lain atau periksa filter Anda.</p>
                </div>
            @endforelse

            <!-- Pagination -->
            <div class="mt-3">
                {{ $products->links() }}
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const inventoryItems = @json($inventoryItems);
        const laborServices = @json($laborServices);
        let saveTimeouts = {};

        $(document).ready(function() {
            // Initial calculations
            $('.product-recipe-card').each(function() {
                const productId = $(this).data('product-id');
                recalculateHpp(productId);
            });

            // Dynamic unit badge update on change of select
            $(document).on('change', '.bom-item-select', function() {
                const select = $(this);
                const selectedOption = select.find('option:selected');
                const unit = selectedOption.data('unit') || '—';
                const row = select.closest('.bom-row');
                row.find('.bom-unit-badge').text(unit);
                
                const productId = select.closest('.product-recipe-card').data('product-id');
                recalculateHpp(productId);
                triggerAutoSave(productId);
            });

            // Auto-fill cost price when typing name in srv-name-field and matches option in datalist
            $(document).on('input', '.srv-name-field', function() {
                const input = $(this);
                const val = input.val();
                const productId = input.closest('.product-recipe-card').data('product-id');
                const datalist = $(`#srv-list-${productId}`);
                const option = datalist.find(`option[value="${val}"]`);
                
                if (option.length > 0) {
                    const cost = option.data('cost') || 0;
                    const row = input.closest('.labor-row');
                    row.find('.labor-cost-field').val(cost);
                    recalculateHpp(productId);
                    triggerAutoSave(productId);
                }
            });

            // Trigger auto-save and recalculation on any input field change
            $(document).on('change input', '.select-field', function() {
                const productId = $(this).closest('.product-recipe-card').data('product-id');
                recalculateHpp(productId);
                triggerAutoSave(productId);
            });

            // Remove Row Handler
            $(document).on('click', '.btn-remove-row', function() {
                const button = $(this);
                const row = button.closest('tr');
                const productId = button.closest('.product-recipe-card').data('product-id');
                
                row.fadeOut(200, function() {
                    row.remove();
                    recalculateHpp(productId);
                    triggerAutoSave(productId);
                });
            });

            // Add BOM Row Button
            $('.btn-add-bom').click(function() {
                const button = $(this);
                const card = button.closest('.product-recipe-card');
                const productId = card.data('product-id');
                const tbody = card.find('.bom-tbody');
                
                const rowHtml = generateBomRow(productId);
                tbody.append(rowHtml);
            });

            // Add Labor Row Button
            $('.btn-add-labor').click(function() {
                const button = $(this);
                const card = button.closest('.product-recipe-card');
                const productId = card.data('product-id');
                const tbody = card.find('.labor-tbody');
                
                const rowHtml = generateLaborRow(productId);
                tbody.append(rowHtml);
            });
        });

        // Generate BOM row html dynamically
        function generateBomRow(productId) {
            const idx = Date.now() + Math.random().toString(36).substr(2, 5);
            let optionsHtml = '<option value="">— Pilih Bahan Baku —</option>';
            inventoryItems.forEach(item => {
                optionsHtml += `<option value="${item.id}" data-unit="${item.unit}" data-price="${parseFloat(item.cost_price || 0)}">${item.name} (${item.unit})</option>`;
            });

            return `
                <tr class="bom-row" data-index="${idx}" style="display:none;">
                    <td>
                        <select name="items[${idx}][inventory_item_id]" class="form-select form-select-sm bom-item-select select-field" required>
                            ${optionsHtml}
                        </select>
                    </td>
                    <td>
                        <div class="input-group input-group-sm">
                            <input type="number" step="0.0001" name="items[${idx}][quantity]" class="form-control qty-field select-field" min="0.0001" required placeholder="0.0">
                            <span class="input-group-text bom-unit-badge bg-light" style="font-size: 10px;">—</span>
                        </div>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-link text-danger p-0 btn-remove-row"><i class="fas fa-trash-alt"></i></button>
                    </td>
                </tr>
            `;
        }

        // Generate Labor row html dynamically
        function generateLaborRow(productId) {
            const idx = Date.now() + Math.random().toString(36).substr(2, 5);

            return `
                <tr class="labor-row" data-index="${idx}" style="display:none;">
                    <td>
                        <input type="text" name="labors[${idx}][service_name]" class="form-control form-control-sm srv-name-field select-field" list="srv-list-${productId}" required placeholder="Nama Jasa...">
                    </td>
                    <td>
                        <input type="number" name="labors[${idx}][qty]" class="form-control form-control-sm labor-qty-field select-field" min="1" value="1" required>
                    </td>
                    <td>
                        <input type="number" name="labors[${idx}][unit_cost]" class="form-control form-control-sm labor-cost-field select-field" min="0" value="0" required>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-link text-danger p-0 btn-remove-row"><i class="fas fa-trash-alt"></i></button>
                    </td>
                </tr>
            `;
        }

        // Recalculate HPP dynamically
        function recalculateHpp(productId) {
            const card = $(`.product-recipe-card[data-product-id="${productId}"]`);
            const batchQty = parseFloat(card.find('.batch-qty-input').val()) || 1;
            
            let totalMaterialsCost = 0;
            card.find('.bom-row').each(function() {
                const row = $(this);
                const select = row.find('.bom-item-select');
                const selectedOption = select.find('option:selected');
                const price = parseFloat(selectedOption.data('price')) || 0;
                const qty = parseFloat(row.find('.qty-field').val()) || 0;
                totalMaterialsCost += price * qty;
            });

            let totalLaborCost = 0;
            card.find('.labor-row').each(function() {
                const row = $(this);
                const qty = parseFloat(row.find('.labor-qty-field').val()) || 0;
                const cost = parseFloat(row.find('.labor-cost-field').val()) || 0;
                totalLaborCost += qty * cost;
            });

            const totalRecipeCost = totalMaterialsCost + totalLaborCost;
            const hppPerPcs = totalRecipeCost / batchQty;

            // Render HPP display
            card.find('.hpp-display').text('Rp ' + numberFormat(Math.round(hppPerPcs)));
        }

        // Format numbers like PHP's number_format
        function numberFormat(val) {
            return val.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        // Trigger Auto Save
        function triggerAutoSave(productId) {
            const card = $(`.product-recipe-card[data-product-id="${productId}"]`);
            const badge = card.find('.save-status-badge');
            
            badge.html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Menyimpan...')
                 .removeClass('bg-success bg-danger bg-secondary')
                 .addClass('bg-warning text-dark');

            if (saveTimeouts[productId]) {
                clearTimeout(saveTimeouts[productId]);
            }

            saveTimeouts[productId] = setTimeout(() => {
                saveRecipeAjax(productId);
            }, 800); // 800ms debounce
        }

        // Actual AJAX save
        function saveRecipeAjax(productId) {
            const card = $(`.product-recipe-card[data-product-id="${productId}"]`);
            const badge = card.find('.save-status-badge');
            const batchQty = card.find('.batch-qty-input').val();
            
            const items = [];
            card.find('.bom-row').each(function() {
                const row = $(this);
                const itemId = row.find('.bom-item-select').val();
                const qty = row.find('.qty-field').val();
                if (itemId && qty) {
                    items.push({
                        inventory_item_id: itemId,
                        quantity: qty
                    });
                }
            });

            const labors = [];
            card.find('.labor-row').each(function() {
                const row = $(this);
                const name = row.find('.srv-name-field').val();
                const qty = row.find('.labor-qty-field').val();
                const cost = row.find('.labor-cost-field').val();
                if (name && qty && cost !== '') {
                    labors.push({
                        service_name: name,
                        qty: qty,
                        unit_cost: cost
                    });
                }
            });

            $.ajax({
                url: "{{ route('product_recipes.bulk_save') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    master_product_id: productId,
                    batch_qty: batchQty,
                    items: items,
                    labors: labors
                },
                dataType: 'json',
                success: function(response) {
                    badge.removeClass('bg-warning text-dark bg-danger bg-secondary')
                         .addClass('bg-success text-white');
                    
                    if (response.status === 'empty') {
                        badge.html('<i class="fas fa-info-circle me-1"></i> Kosong (Nonaktif)')
                             .removeClass('bg-success text-white')
                             .addClass('bg-secondary text-white');
                    } else {
                        badge.html('<i class="fas fa-check-circle me-1"></i> Tersimpan otomatis');
                    }
                },
                error: function(xhr) {
                    console.error(xhr);
                    let errMsg = 'Gagal menyimpan';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errMsg += ': ' + xhr.responseJSON.message;
                    }
                    badge.html('<i class="fas fa-exclamation-triangle me-1"></i> ' + errMsg)
                         .removeClass('bg-warning text-dark bg-success bg-secondary')
                         .addClass('bg-danger text-white');
                }
            });
        }
    </script>
    <script>
        // Custom animation after row insertion to ensure smooth display
        $(document).on('click', '.btn-add-bom, .btn-add-labor', function() {
            const card = $(this).closest('.product-recipe-card');
            card.find('tr[style="display:none;"]').fadeIn(300).removeAttr('style');
        });
    </script>
@endpush
