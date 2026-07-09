@extends('layouts.app')
@section('title', 'Perintah Kerja (SPK) - Produksi')
@section('page-title', 'Perintah Kerja (SPK)')

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-1"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-1"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
    <ul class="nav nav-tabs border-0 m-0" id="spkTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active small fw-semibold border-0" id="antrean-tab" data-bs-toggle="tab" data-bs-target="#panel-antrean" type="button" role="tab">
                <i class="fas fa-hourglass-half me-1"></i> Antrean Permintaan (Pending)
                <span class="badge bg-warning text-dark ms-1">{{ count($pendingOrders) }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link small fw-semibold text-primary border-0" id="proses-tab" data-bs-toggle="tab" data-bs-target="#panel-proses" type="button" role="tab">
                <i class="fas fa-cog fa-spin me-1"></i> Sedang Diproses (In Progress)
                <span class="badge bg-primary ms-1">{{ count($producingOrders) }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link small fw-semibold border-0" id="riwayat-tab" data-bs-toggle="tab" data-bs-target="#panel-riwayat" type="button" role="tab">
                <i class="fas fa-history me-1"></i> Riwayat Produksi
            </button>
        </li>
    </ul>
    <button type="button" class="btn btn-sm btn-primary px-3 rounded-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#createManualSpkModal">
        <i class="fas fa-plus me-1"></i> Buat SPK Manual
    </button>
</div>

<div class="tab-content" id="spkTabContent">
    {{-- Tab 1: Antrean Pending --}}
    <div class="tab-pane fade show active" id="panel-antrean" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-3 bg-white">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover border align-middle mb-0 rounded-2 overflow-hidden">
                        <thead class="table-light">
                            <tr class="small text-uppercase text-muted">
                                <th class="py-2 px-3">Produk Master</th>
                                <th>SKU</th>
                                <th>Kuantitas Diminta</th>
                                <th>Diajukan Oleh</th>
                                <th>Tanggal Pengajuan</th>
                                <th class="text-center" style="width:200px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pendingOrders as $order)
                                <tr>
                                    <td class="fw-semibold text-dark px-3 py-3 small">
                                        {{ $order->masterProduct->name }}
                                    </td>
                                    <td class="font-monospace text-muted small">{{ $order->masterProduct->sku }}</td>
                                    <td class="fw-bold text-dark small">{{ number_format($order->quantity) }} {{ $order->masterProduct->unit }}</td>
                                    <td class="small text-muted">{{ $order->requestedBy ? $order->requestedBy->name : 'Sistem' }}</td>
                                    <td class="small text-muted">{{ $order->created_at->format('d M Y, H:i') }}</td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-2">
                                            <form action="{{ route('production_orders.start', $order) }}" method="POST" class="m-0">
                                                @csrf
                                                <button type="submit" class="btn btn-primary btn-sm px-3 fw-bold">
                                                    <i class="fas fa-play me-1"></i> Mulai Produksi
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fas fa-inbox fa-2x mb-3 opacity-25 d-block"></i>
                                        Tidak ada antrean permintaan produksi saat ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Tab 2: Sedang Diproses --}}
    <div class="tab-pane fade" id="panel-proses" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-3 bg-white">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover border align-middle mb-0 rounded-2 overflow-hidden">
                        <thead class="table-light">
                            <tr class="small text-uppercase text-muted">
                                <th class="py-2 px-3">Produk Master</th>
                                <th>SKU</th>
                                <th>Target Qty</th>
                                <th>Resep BOM</th>
                                <th>Tanggal Mulai</th>
                                <th class="text-center" style="width:250px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($producingOrders as $order)
                                @php
                                    $activeRecipe = \App\Models\ProductRecipe::with(['labors', 'items.inventoryItem'])
                                        ->where('master_product_id', $order->master_product_id)
                                        ->where('is_active', true)
                                        ->first();
                                    $laborsJson = $activeRecipe ? json_encode($activeRecipe->labors) : '[]';
                                    
                                    $bomItems = [];
                                    if ($activeRecipe) {
                                        foreach ($activeRecipe->items as $item) {
                                            $bomItems[] = [
                                                'inventory_item_id' => $item->inventory_item_id,
                                                'name' => $item->inventoryItem->name,
                                                'sku' => $item->inventoryItem->sku,
                                                'recipe_qty' => (float)$item->quantity,
                                                'unit' => $item->inventoryItem->unit,
                                                'stock' => (float)$item->inventoryItem->stock,
                                            ];
                                        }
                                    }
                                    $bomJson = json_encode($bomItems);
                                @endphp
                                <tr>
                                    <td class="fw-semibold text-dark px-3 py-3 small">
                                        {{ $order->masterProduct->name }}
                                    </td>
                                    <td class="font-monospace text-muted small">{{ $order->masterProduct->sku }}</td>
                                    <td class="fw-bold text-dark small">{{ number_format($order->quantity) }} {{ $order->masterProduct->unit }}</td>
                                    <td>
                                        @if($activeRecipe)
                                            <span class="badge bg-light text-dark border"><i class="fas fa-check-circle text-success me-1"></i>BOM Aktif</span>
                                        @else
                                            <span class="badge bg-light text-danger border"><i class="fas fa-times-circle text-danger me-1"></i>Belum Ada BOM</span>
                                        @endif
                                    </td>
                                    <td class="small text-muted">{{ $order->updated_at->format('d M Y, H:i') }}</td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-2">
                                            <button type="button" class="btn btn-success btn-sm px-3 fw-bold btn-complete-spk"
                                                data-id="{{ $order->id }}"
                                                data-qty="{{ $order->quantity }}"
                                                data-unit="{{ $order->masterProduct->unit ?: 'pcs' }}"
                                                data-labors="{{ $laborsJson }}"
                                                data-bom-items="{{ $bomJson }}"
                                                data-batch-qty="{{ $activeRecipe ? $activeRecipe->batch_qty : 1 }}">
                                                <i class="fas fa-check me-1"></i> Selesaikan
                                            </button>
                                            <form action="{{ route('production_orders.cancel', $order) }}" method="POST" class="m-0"
                                                onsubmit="return confirm('Batalkan proses produksi ini?')">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                                    <i class="fas fa-times"></i> Batal
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fas fa-tasks fa-2x mb-3 opacity-25 d-block"></i>
                                        Tidak ada barang yang sedang diproduksi.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Tab 3: Riwayat --}}
    <div class="tab-pane fade" id="panel-riwayat" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-3 bg-white">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover border align-middle mb-0 rounded-2 overflow-hidden">
                        <thead class="table-light">
                            <tr class="small text-uppercase text-muted">
                                <th class="py-2 px-3">Produk Master</th>
                                <th>SKU</th>
                                <th>Qty Selesai</th>
                                <th>Status</th>
                                <th>Tanggal Selesai</th>
                                <th class="text-center" style="width:100px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($completedOrders as $order)
                                <tr>
                                    <td class="fw-semibold text-dark px-3 py-3 small">
                                        {{ $order->masterProduct->name }}
                                    </td>
                                    <td class="font-monospace text-muted small">{{ $order->masterProduct->sku }}</td>
                                    <td class="fw-bold text-dark small">{{ number_format($order->quantity) }} {{ $order->masterProduct->unit }}</td>
                                    <td>
                                        @if($order->status === 'completed')
                                            <span class="badge bg-success">Selesai</span>
                                        @else
                                            <span class="badge bg-danger">Dibatalkan</span>
                                        @endif
                                    </td>
                                    <td class="small text-muted">{{ $order->updated_at->format('d M Y, H:i') }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('production_orders.show', $order) }}" class="btn btn-info btn-sm text-white px-3">
                                            <i class="fas fa-eye me-1"></i> Detail
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fas fa-history fa-2x mb-3 opacity-25 d-block"></i>
                                        Belum ada riwayat produksi.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $completedOrders->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL COMPLETE SPK --}}
<div class="modal fade" id="completeModal" tabindex="-1" aria-labelledby="completeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success bg-opacity-10 border-bottom">
                <h5 class="modal-title text-success fw-bold" id="completeModalLabel">
                    <i class="fas fa-check-circle me-2"></i>Selesaikan Produksi &amp; Input Biaya Aktual
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="form-complete" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="input-produced-qty" class="form-label fw-semibold small text-muted">Kuantitas Hasil Produksi Riil</label>
                            <div class="input-group">
                                <input type="number" name="produced_qty" id="input-produced-qty" class="form-control form-control-lg" min="1" required>
                                <span class="input-group-text span-complete-unit">pcs</span>
                            </div>
                            <small class="form-text text-muted">Masukkan jumlah barang jadi yang benar-benar selesai diproduksi.</small>
                        </div>
                    </div>

                    <!-- 2. Komposisi Bahan Baku Aktual -->
                    <div class="border-top pt-3 mb-4">
                        <h6 class="fw-bold text-dark mb-2"><i class="fas fa-boxes me-2 text-primary"></i>Komposisi Bahan Baku &amp; Kemasan Aktual</h6>
                        <small class="text-muted small d-block mb-3">Tentukan kuantitas bahan baku yang sebenarnya terpakai (untuk mencatat kebocoran/wastage). Warning merah akan muncul jika stok di Produksi kurang.</small>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle border mb-0" id="table-actual-bom">
                                <thead class="table-light">
                                    <tr class="small text-uppercase text-muted" style="font-size:10px">
                                        <th style="width:40%" class="ps-3">Nama Bahan / SKU</th>
                                        <th style="width:20%">Stok Tersedia</th>
                                        <th style="width:20%">Kebutuhan Resep</th>
                                        <th style="width:20%" class="pe-3">Konsumsi Aktual</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- Baris diisi via JS --}}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 3. Biaya Jasa Ahli Aktual -->
                    <div class="border-top pt-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="fw-bold text-dark mb-0"><i class="fas fa-user-cog me-2 text-warning"></i>Biaya Jasa Ahli &amp; QC Aktual</h6>
                                <small class="text-muted small">Input nominal rupiah yang dibayarkan. Anda dapat menambah/menghapus baris secara custom.</small>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-warning fw-semibold" id="btn-modal-add-labor">
                                <i class="fas fa-plus me-1"></i> Tambah Jasa
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle border mb-0" id="table-actual-labor">
                                <thead class="table-light">
                                    <tr class="small text-uppercase text-muted" style="font-size:10px">
                                        <th class="ps-3" style="width:60%">Nama Jasa / QC</th>
                                        <th style="width:30%">Biaya Aktual (Rp)</th>
                                        <th style="width:10%" class="text-center pe-3"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- Baris akan dimuat via JS --}}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top p-3">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success fw-bold px-4">
                        <i class="fas fa-save me-1"></i> Selesaikan &amp; Hitung HPP
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let modalLaborIndex = 0;
    let currentBomItems = [];
    let currentBatchQty = 1;

    $('.btn-complete-spk').on('click', function() {
        const orderId = $(this).data('id');
        const qty = $(this).data('qty');
        const unit = $(this).data('unit');
        const labors = $(this).data('labors');
        const bomItems = $(this).data('bom-items');
        const batchQty = $(this).data('batch-qty') || 1;
        const actionUrl = `/production-orders/${orderId}/complete`;

        $('#form-complete').attr('action', actionUrl);
        $('#input-produced-qty').val(qty);
        $('.span-complete-unit').text(unit);

        currentBomItems = bomItems || [];
        currentBatchQty = batchQty;

        // Render BOM items with warning indicators
        renderBomItems(qty);

        // Populate labors
        const tbodyLabor = $('#table-actual-labor tbody');
        tbodyLabor.empty();
        modalLaborIndex = 0;

        if (labors && labors.length > 0) {
            labors.forEach((labor) => {
                tbodyLabor.append(`
                    <tr class="actual-labor-row">
                        <td class="ps-3">
                            <input type="text" name="labor_items[${modalLaborIndex}][service_name]" class="form-control form-control-sm" value="${labor.service_name}" required>
                        </td>
                        <td>
                            <input type="number" name="labor_items[${modalLaborIndex}][actual_cost]" class="form-control form-control-sm" value="${parseInt(labor.default_cost)}" min="0" required>
                        </td>
                        <td class="text-center pe-3">
                            <button type="button" class="btn btn-sm btn-link text-danger btn-remove-actual-labor"><i class="fas fa-trash-alt"></i></button>
                        </td>
                    </tr>
                `);
                modalLaborIndex++;
            });
        }

        $('#completeModal').modal('show');
    });

    function renderBomItems(producedQty) {
        const tbodyBom = $('#table-actual-bom tbody');
        tbodyBom.empty();

        if (currentBomItems.length === 0) {
            tbodyBom.append('<tr><td colspan="4" class="text-center text-muted small py-3">Tidak ada bahan baku dalam resep.</td></tr>');
            return;
        }

        currentBomItems.forEach((item, idx) => {
            const standardQty = ((item.recipe_qty / currentBatchQty) * producedQty).toFixed(4);
            const isWarning = parseFloat(standardQty) > parseFloat(item.stock);
            const warningBadge = isWarning ? '<span class="badge bg-danger stock-warning-badge" style="font-size:9px">Stok Kurang</span>' : '';

            tbodyBom.append(`
                <tr class="bom-actual-row">
                    <td class="ps-3">
                        <span class="fw-semibold text-dark small d-block">${item.name}</span>
                        <code class="text-muted font-monospace" style="font-size:10px">SKU: ${item.sku}</code>
                        <input type="hidden" name="items[${idx}][inventory_item_id]" value="${item.inventory_item_id}">
                    </td>
                    <td>
                        <span class="small text-muted font-monospace">${item.stock} ${item.unit}</span>
                    </td>
                    <td>
                        <span class="small text-muted font-monospace item-recipe-qty" data-recipe-qty="${item.recipe_qty}">${standardQty} ${item.unit}</span>
                    </td>
                    <td class="pe-3">
                        <div class="input-group input-group-sm">
                            <input type="number" step="0.0001" name="items[${idx}][actual_qty]" class="form-control form-control-sm input-actual-qty" value="${standardQty}" min="0" required data-stock="${item.stock}">
                            <span class="input-group-text font-monospace" style="font-size:10px">${item.unit}</span>
                        </div>
                        <div class="warning-container mt-1">${warningBadge}</div>
                    </td>
                </tr>
            `);
        });
    }

    // Recalculate BOM requirements when produced qty changes
    $('#input-produced-qty').on('input change', function() {
        const qty = parseFloat($(this).val()) || 1;
        renderBomItems(qty);
    });

    // Stock check when actual quantity changes
    $(document).on('input change', '.input-actual-qty', function() {
        const row = $(this).closest('.bom-actual-row');
        const actualQty = parseFloat($(this).val()) || 0;
        const stock = parseFloat($(this).attr('data-stock')) || 0;
        const warnContainer = row.find('.warning-container');

        if (actualQty > stock) {
            if (warnContainer.find('.stock-warning-badge').length === 0) {
                warnContainer.html('<span class="badge bg-danger stock-warning-badge" style="font-size:9px">Stok Kurang</span>');
            }
        } else {
            warnContainer.empty();
        }
    });

    $('#btn-modal-add-labor').on('click', function() {
        const tbody = $('#table-actual-labor tbody');
        tbody.append(`
            <tr class="actual-labor-row">
                <td class="ps-3">
                    <input type="text" name="labor_items[${modalLaborIndex}][service_name]" class="form-control form-control-sm" placeholder="Misal: Jasa Packing, QC Extra" required>
                </td>
                <td>
                    <input type="number" name="labor_items[${modalLaborIndex}][actual_cost]" class="form-control form-control-sm" value="0" min="0" required>
                </td>
                <td class="text-center pe-3">
                    <button type="button" class="btn btn-sm btn-link text-danger btn-remove-actual-labor"><i class="fas fa-trash-alt"></i></button>
                </td>
            </tr>
        `);
        modalLaborIndex++;
    });

    $(document).on('click', '.btn-remove-actual-labor', function() {
        $(this).closest('tr').remove();
    });
});
</script>

<!-- Modal Buat SPK Manual -->
<div class="modal fade" id="createManualSpkModal" tabindex="-1" aria-labelledby="createManualSpkModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form action="{{ route('production_orders.create_from_order') }}" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white py-2 px-3">
                    <h6 class="modal-title fw-bold" id="createManualSpkModalLabel">Buat Perintah Kerja (SPK) Baru</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="mb-3">
                        <label class="form-label form-label-sm fw-semibold">Produk Master Jadi</label>
                        <select name="master_product_id" class="form-select form-select-sm" required style="width: 100%;">
                            <option value="">-- Pilih Produk Master --</option>
                            @php
                                $allProducts = \App\Models\MasterProduct::where('tenant_id', auth()->user()->tenant_id)->where('is_active', true)->orderBy('name')->get();
                            @endphp
                            @foreach($allProducts as $p)
                                <option value="{{ $p->id }}">{{ $p->sku ? '['.$p->sku.'] ' : '' }}{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-1">
                        <label class="form-label form-label-sm fw-semibold">Kuantitas Produksi (Qty)</label>
                        <input type="number" name="quantity" class="form-control form-control-sm" min="1" required placeholder="Contoh: 100">
                    </div>
                </div>
                <div class="modal-footer py-2 px-3">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary fw-bold">
                        <i class="fas fa-check me-1"></i> Simpan SPK
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endpush
