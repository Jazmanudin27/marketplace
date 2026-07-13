@extends('layouts.app')
@section('title', 'Formula Produk (BOM)')
@section('page-title', 'Formula Produk (BOM)')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card border shadow-sm overflow-hidden">
                <div
                    class="card-header bg-primary bg-opacity-10 d-flex justify-content-between align-items-center border-bottom py-2 px-3">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="fas fa-flask me-2 text-primary"></i>Daftar Formula Produk (Bill of Materials)
                        </h6>
                        <small class="text-muted d-block">Kelola resep bahan baku dan biaya jasa produksi produk jadi</small>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('product_recipes.export', request()->query()) }}"
                            class="btn btn-outline-success btn-sm px-3 rounded-3">
                            <i class="fas fa-file-excel me-1"></i> Export Excel
                        </a>
                        <a href="{{ route('product_recipes.print_report', request()->query()) }}" target="_blank"
                            class="btn btn-outline-danger btn-sm px-3 rounded-3">
                            <i class="fas fa-print me-1"></i> Cetak Laporan
                        </a>
                        <a href="{{ route('product_recipes.bulk', request()->query()) }}"
                            class="btn btn-warning btn-sm px-3 rounded-3 fw-semibold">
                            <i class="fas fa-edit me-1"></i> Input Massal
                        </a>
                        <a href="{{ route('product_recipes.create', request()->query()) }}"
                            class="btn btn-primary btn-sm px-3 rounded-3">
                            <i class="fas fa-plus me-1"></i> Tambah Formula
                        </a>
                    </div>
                </div>

                <div class="card-body p-3">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    {{-- Filter --}}
                    <div class="card border shadow-sm p-3 mb-3">
                        <form method="GET" action="{{ route('product_recipes.index') }}">
                            <div class="row g-2 align-items-end">
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                    <label class="form-label small fw-bold">Cari Produk / SKU</label>
                                    <input type="text" name="search" class="form-control form-control-sm"
                                        placeholder="Ketik nama atau SKU..." value="{{ request('search') }}">
                                </div>
                                <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                                    <label class="form-label small fw-bold">Status Formula</label>
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="">Semua Status</option>
                                        <option value="has_recipe"
                                            {{ request('status') === 'has_recipe' ? 'selected' : '' }}>Sudah Ada Formula
                                        </option>
                                        <option value="no_recipe" {{ request('status') === 'no_recipe' ? 'selected' : '' }}>
                                            Belum Ada Formula</option>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-6 col-md-auto d-flex gap-2">
                                    <button type="submit" class="btn btn-primary btn-sm px-3">
                                        <i class="fas fa-search me-1"></i> Filter
                                    </button>
                                    @if (request()->anyFilled(['search', 'status']))
                                        <a href="{{ route('product_recipes.index') }}"
                                            class="btn btn-secondary btn-sm px-3">
                                            <i class="fas fa-times me-1"></i> Reset
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </form>
                    </div>

                    {{-- Tabel --}}
                    <div class="table-responsive rounded border mt-3">
                        <table class="table table-sm table-striped table-bordered align-middle mb-0">
                            <thead>
                                <tr class="small text-uppercase text-muted" style="background: #f8f9fa;">
                                    <th class="text-center" style="width: 50px;">#</th>
                                    <th>Produk &amp; SKU</th>
                                    <th class="text-center">Tipe Produk</th>
                                    <th>Nama Formula Terpasang</th>
                                    <th class="text-center">Bahan Baku</th>
                                    <th class="text-center">Jasa Ahli / QC</th>
                                    <th class="text-end">Total HPP Formula</th>
                                    <th class="text-center" style="width: 180px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($products as $i => $p)
                                    @php
                                        $recipe = $p->activeRecipe;
                                        $materialsCount = $recipe ? $recipe->items->count() : 0;
                                        $laborsCount = $recipe ? $recipe->labors->count() : 0;

                                        // Calculate total materials cost
                                        $materialsCost = 0;
                                        if ($recipe) {
                                            foreach ($recipe->items as $item) {
                                                $materialsCost +=
                                                    $item->quantity * ($item->inventoryItem->cost_price ?? 0);
                                            }
                                        }

                                        // Calculate total labor cost
                                        $laborCost = $recipe ? $recipe->labors->sum('default_cost') : 0;

                                        // Total cost (HPP)
                                        $totalCost = ($materialsCost + $laborCost) / ($recipe->batch_qty ?? 1);
                                    @endphp
                                    <tr>
                                        <td class="text-center">
                                            <span class="badge bg-light text-secondary border small">
                                                {{ $products->firstItem() + $i }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark small">{{ $p->name }}</div>
                                            <div class="text-muted font-monospace" style="font-size: 11px;">
                                                {{ $p->sku ?? '—' }}</div>
                                        </td>
                                        <td class="text-center">
                                            @if ($p->is_bundle)
                                                <span
                                                    class="badge bg-purple bg-opacity-10 text-purple border border-purple-subtle small px-2">Set
                                                    / Bundle</span>
                                            @else
                                                <span
                                                    class="badge bg-info bg-opacity-10 text-info border border-info-subtle small px-2">Single</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($recipe)
                                                <span class="fw-semibold text-secondary small"><i
                                                        class="far fa-file-alt me-1"></i>{{ $recipe->name }}</span>
                                            @else
                                                <span class="text-muted small"><em>Belum dikonfigurasi</em></span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if ($recipe && $materialsCount > 0)
                                                <span
                                                    class="badge bg-primary-subtle text-primary border border-primary-subtle small">
                                                    {{ $materialsCount }} item
                                                </span>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if ($recipe && $laborsCount > 0)
                                                <span
                                                    class="badge bg-warning-subtle text-warning border border-warning-subtle small">
                                                    {{ $laborsCount }} operasional
                                                </span>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                        <td class="text-end fw-bold font-monospace small">
                                            @if ($recipe)
                                                <span class="text-success">Rp
                                                    {{ number_format($totalCost, 0, ',', '.') }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if ($recipe)
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button"
                                                        class="btn btn-xs btn-outline-info view-recipe-btn"
                                                        data-product-id="{{ $p->id }}"
                                                        data-product-name="{{ $p->name }}"
                                                        data-product-sku="{{ $p->sku }}"
                                                        data-recipe-name="{{ $recipe->name }}"
                                                        data-batch-qty="{{ $recipe->batch_qty }}"
                                                        data-items="{{ json_encode($recipe->items->map(fn($item) => ['name' => $item->inventoryItem->name, 'sku' => $item->inventoryItem->sku, 'qty' => $item->quantity, 'unit' => $item->inventoryItem->unit, 'price' => (float) $item->inventoryItem->cost_price])) }}"
                                                        data-labors="{{ json_encode($recipe->labors->map(fn($labor) => ['name' => $labor->service_name, 'cost' => (float) $labor->default_cost])) }}"
                                                        data-bs-toggle="modal" data-bs-target="#viewRecipeModal">
                                                        <i class="fas fa-eye me-1"></i>Detail
                                                    </button>
                                                    <a href="{{ route('product_recipes.edit', array_merge([$p->id], request()->query())) }}"
                                                        class="btn btn-xs btn-outline-warning">
                                                        <i class="fas fa-pencil-alt me-1"></i>Edit
                                                    </a>
                                                    <form
                                                        action="{{ route('product_recipes.destroy', array_merge([$p->id], request()->query())) }}"
                                                        method="POST" class="confirm-delete d-inline"
                                                        data-message="Formula resep produk ini akan dinonaktifkan!">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-xs btn-outline-danger">
                                                            <i class="fas fa-trash-alt me-1"></i>Hapus
                                                        </button>
                                                    </form>
                                                </div>
                                            @else
                                                <a href="{{ route('product_recipes.edit', array_merge([$p->id], request()->query())) }}"
                                                    class="btn btn-xs btn-outline-primary px-3">
                                                    <i class="fas fa-plus me-1"></i>Buat Formula
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-3 small">
                                            Tidak ditemukan produk yang cocok.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $products->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Detail Formula --}}
    <div class="modal fade" id="viewRecipeModal" tabindex="-1" aria-labelledby="viewRecipeModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="d-flex align-items-center gap-3 p-3 border-bottom bg-info bg-opacity-10">
                    <div class="bg-info text-white rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 fs-5 p-2"
                        style="width: 38px; height: 38px;">
                        <i class="fas fa-flask"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="modal-title fw-bold fs-6 mb-0 text-dark" id="viewRecipeModalLabel">Detail Formula &amp;
                            BOM</h5>
                        <p class="mb-0 text-muted small" id="detail-product-info"></p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-4">
                        {{-- Kiri: Bahan Baku --}}
                        <div class="col-md-7 border-end">
                            <h6 class="fw-bold text-dark mb-2"><i class="fas fa-boxes me-2 text-primary"></i>1. Bahan Baku
                                (BOM)</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered align-middle mb-0 small"
                                    id="detail-materials-table">
                                    <thead>
                                        <tr class="table-light">
                                            <th>Bahan Baku</th>
                                            <th class="text-center" style="width: 100px;">Qty Takaran</th>
                                            <th class="text-end" style="width: 120px;">Harga Modal</th>
                                            <th class="text-end" style="width: 120px;">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                    <tfoot>
                                        <tr class="fw-bold">
                                            <td colspan="3" class="text-end">Total Bahan Baku:</td>
                                            <td class="text-end text-primary" id="detail-materials-total"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        {{-- Kanan: Jasa & QC --}}
                        <div class="col-md-5">
                            <h6 class="fw-bold text-dark mb-2"><i class="fas fa-user-cog me-2 text-warning"></i>2. Jasa
                                &amp; QC</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered align-middle mb-0 small"
                                    id="detail-labors-table">
                                    <thead>
                                        <tr class="table-light">
                                            <th>Nama Jasa / QC</th>
                                            <th class="text-end" style="width: 130px;">Tarif / Biaya</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                    <tfoot>
                                        <tr class="fw-bold">
                                            <td class="text-end">Total Jasa:</td>
                                            <td class="text-end text-warning" id="detail-labors-total"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- HPP Summary Box --}}
                    <div
                        class="card bg-success bg-opacity-10 border border-success border-opacity-20 p-3 mt-4 text-center">
                        <div class="text-muted small text-uppercase fw-bold mb-1">Estimasi Total HPP Produksi (Per 1 Pcs)
                        </div>
                        <h3 class="fw-bold text-success font-monospace mb-0" id="detail-hpp-per-unit"></h3>
                        <small class="text-muted mt-1" id="detail-batch-info"></small>
                    </div>
                </div>
                <div class="modal-footer bg-light px-4 py-3 border-top">
                    <a href="#" id="print-recipe-link" target="_blank" class="btn btn-outline-primary btn-sm px-4 rounded-3 me-auto">
                        <i class="fas fa-print me-1"></i> Cetak Detail BOM
                    </a>
                    <button type="button" class="btn btn-secondary btn-sm px-4 rounded-3"
                        data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                const formatRupiah = (num) => 'Rp ' + Math.round(num).toLocaleString('id-ID');

                $('.view-recipe-btn').on('click', function() {
                    const productId = $(this).data('product-id');
                    const productName = $(this).data('product-name');
                    const productSku = $(this).data('product-sku');
                    const recipeName = $(this).data('recipe-name');
                    const batchQty = parseInt($(this).data('batch-qty')) || 1;
                    const items = $(this).data('items') || [];
                    const labors = $(this).data('labors') || [];

                    $('#detail-product-info').text(`${productName} (${productSku}) — ${recipeName}`);
                    $('#detail-batch-info').text(
                        `Dihitung berdasarkan pembagian Output Batch: ${batchQty} Pcs`);
                    $('#print-recipe-link').attr('href', `/product_recipes/${productId}/print`);

                    // 1. Render Materials
                    let materialsHtml = '';
                    let materialsTotal = 0;
                    if (items.length > 0) {
                        items.forEach(function(item) {
                            const subtotal = item.qty * item.price;
                            materialsTotal += subtotal;
                            materialsHtml += `
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark">${item.name}</div>
                                    </td>
                                    <td class="text-center">${parseFloat(item.qty).toLocaleString('id-ID')} ${item.unit}</td>
                                    <td class="text-end">${formatRupiah(item.price)}</td>
                                    <td class="text-end font-monospace fw-semibold">${formatRupiah(subtotal)}</td>
                                </tr>
                            `;
                        });
                    } else {
                        materialsHtml =
                            `<tr><td colspan="4" class="text-center text-muted py-2">Tidak ada bahan baku</td></tr>`;
                    }
                    $('#detail-materials-table tbody').html(materialsHtml);
                    $('#detail-materials-total').text(formatRupiah(materialsTotal));

                    // 2. Render Labors
                    let laborsHtml = '';
                    let laborsTotal = 0;
                    if (labors.length > 0) {
                        labors.forEach(function(labor) {
                            laborsTotal += labor.cost;
                            laborsHtml += `
                                <tr>
                                    <td>${labor.name}</td>
                                    <td class="text-end font-monospace fw-semibold">${formatRupiah(labor.cost)}</td>
                                </tr>
                            `;
                        });
                    } else {
                        laborsHtml =
                            `<tr><td colspan="2" class="text-center text-muted py-2">Tidak ada jasa/QC</td></tr>`;
                    }
                    $('#detail-labors-table tbody').html(laborsHtml);
                    $('#detail-labors-total').text(formatRupiah(laborsTotal));

                    // 3. Render HPP summary
                    const totalHpp = (materialsTotal + laborsTotal) / batchQty;
                    $('#detail-hpp-per-unit').text(formatRupiah(totalHpp));
                });
            });
        </script>
    @endpush
@endsection
