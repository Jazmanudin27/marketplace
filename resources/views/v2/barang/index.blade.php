@extends('v2.layouts.app')

@section('title', 'Data Barang V2')

@section('content')
<!-- Page Header Compact -->
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2">
            <i class="bi bi-boxes text-primary fs-5"></i> Data Barang
        </h1>
        <p class="v2-page-subtitle mb-0">Kelola kamus master data barang operasional, bahan baku, kemasan, ATK & inventaris</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ url('/v2/barang') }}" class="btn btn-sm btn-v2-secondary py-1.5 px-2.5" title="Refresh Page">
            <i class="bi bi-arrow-clockwise"></i>
        </a>
        <button type="button" class="btn btn-sm btn-v2-primary py-1.5 px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#createBarangModal">
            <i class="bi bi-plus-lg me-1"></i> Tambah Barang
        </button>
    </div>
</div>

<!-- Alerts -->
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show py-2 px-3 mb-3 border-0 shadow-sm" style="font-size: 0.78rem;" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close py-2.5" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show py-2 px-3 mb-3 border-0 shadow-sm" style="font-size: 0.78rem;" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close py-2.5" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show py-2 px-3 mb-3 border-0 shadow-sm" style="font-size: 0.78rem;" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> Terjadi kesalahan input. Periksa kembali form anda.
        <button type="button" class="btn-close py-2.5" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Summary Widgets (KPI Cards) -->
<div class="row g-2 mb-3">
    <div class="col-12 col-sm-6 col-md-3">
        <div class="v2-stat-widget widget-blue">
            <div class="v2-stat-icon-wrapper blue">
                <i class="bi bi-boxes"></i>
            </div>
            <div class="v2-stat-info">
                <span class="v2-stat-num">{{ number_format($counts['total_items']) }}</span>
                <span class="v2-stat-lbl">Total Item Barang</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="v2-stat-widget widget-green">
            <div class="v2-stat-icon-wrapper green">
                <i class="bi bi-scissors"></i>
            </div>
            <div class="v2-stat-info">
                <span class="v2-stat-num">{{ number_format($counts['total_bahan']) }}</span>
                <span class="v2-stat-lbl">Item Bahan Baku</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="v2-stat-widget widget-purple">
            <div class="v2-stat-icon-wrapper purple">
                <i class="bi bi-box-seam"></i>
            </div>
            <div class="v2-stat-info">
                <span class="v2-stat-num">{{ number_format($counts['total_kemasan']) }}</span>
                <span class="v2-stat-lbl">Item Kemasan</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="v2-stat-widget widget-amber">
            <div class="v2-stat-icon-wrapper amber">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <div class="v2-stat-info">
                <span class="v2-stat-num">{{ number_format($counts['low_stock']) }}</span>
                <span class="v2-stat-lbl">Stok Menipis / Habis</span>
            </div>
        </div>
    </div>
</div>

<!-- Filter Card -->
<div class="v2-card mb-3">
    <div class="v2-card-body p-2.5">
        <form method="GET" action="{{ url('/v2/barang') }}" class="row g-2 align-items-center">
            <div class="col-12 col-sm-6 col-md-4">
                <input type="text" name="name" class="form-control form-control-sm" placeholder="Cari nama barang..." value="{{ request('name') }}">
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <input type="text" name="sku" class="form-control form-control-sm" placeholder="Cari SKU / Kode..." value="{{ request('sku') }}">
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <select name="type" class="form-select form-select-sm">
                    <option value="">-- Semua Jenis Barang --</option>
                    <option value="bahan" {{ request('type') == 'bahan' ? 'selected' : '' }}>Bahan Baku (Kain, dll)</option>
                    <option value="kemasan" {{ request('type') == 'kemasan' ? 'selected' : '' }}>Kemasan (Kardus, Plastik)</option>
                    <option value="atk" {{ request('type') == 'atk' ? 'selected' : '' }}>ATK / Peralatan</option>
                    <option value="inventaris" {{ request('type') == 'inventaris' ? 'selected' : '' }}>Inventaris / Aset</option>
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex gap-1.5">
                <button type="submit" class="btn btn-sm btn-v2-primary w-100 justify-content-center py-1">
                    <i class="bi bi-search me-1"></i> Cari
                </button>
                @if(request()->anyFilled(['name', 'sku', 'type']))
                    <a href="{{ url('/v2/barang') }}" class="btn btn-sm btn-v2-secondary py-1 px-2" title="Reset Filter">
                        <i class="bi bi-x-lg"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Table Card -->
<div class="v2-card">
    <div class="v2-card-header bg-light py-2 d-flex align-items-center justify-content-between">
        <h6 class="v2-card-title d-flex align-items-center gap-2 m-0">
            <i class="bi bi-list-columns-reverse text-primary"></i> Daftar Master Data Barang
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-0.5 rounded-pill" style="font-size: 0.65rem;">
                {{ $items->total() }} Item
            </span>
        </h6>
    </div>
    <div class="v2-card-body p-0">
        <div class="v2-table-responsive">
            <table class="v2-table align-middle">
                <thead>
                    <tr>
                        <th style="width: 35px;" class="text-center">#</th>
                        <th>SKU / KODE</th>
                        <th>NAMA BARANG</th>
                        <th class="text-center">JENIS BARANG</th>
                        <th class="text-center">SATUAN</th>
                        <th class="text-end">STOK GUDANG</th>
                        <th class="text-end">HARGA MODAL (HPP)</th>
                        <th class="text-center" style="width: 130px;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $index => $item)
                        <tr>
                            <td class="text-center text-muted" style="font-size: 0.72rem;">
                                {{ $items->firstItem() + $index }}
                            </td>
                            <td>
                                <span class="sku-badge">{{ $item->sku }}</span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark" style="font-size: 0.8rem;">
                                    {{ $item->name }}
                                </div>
                            </td>
                            <td class="text-center">
                                @if($item->type === 'bahan')
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-0.5" style="font-size: 0.68rem;">
                                        <i class="bi bi-scissors me-1"></i> Bahan Baku
                                    </span>
                                @elseif($item->type === 'kemasan')
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-0.5" style="font-size: 0.68rem;">
                                        <i class="bi bi-box-seam me-1"></i> Kemasan
                                    </span>
                                @elseif($item->type === 'atk')
                                    <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-0.5" style="font-size: 0.68rem;">
                                        <i class="bi bi-pencil me-1"></i> ATK / Peralatan
                                    </span>
                                @elseif($item->type === 'inventaris')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5" style="font-size: 0.68rem;">
                                        <i class="bi bi-building me-1"></i> Inventaris / Aset
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-0.5" style="font-size: 0.68rem;">
                                        {{ ucfirst($item->type) }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-secondary border px-2 py-0.5" style="font-size: 0.68rem;">{{ $item->unit }}</span>
                            </td>
                            <td class="text-end fw-bold font-monospace" style="font-size: 0.78rem;">
                                @if($item->stock <= 0)
                                    <span class="badge bg-danger text-white px-2 py-0.5">Habis (0)</span>
                                @elseif($item->stock <= $item->min_stock)
                                    <span class="badge bg-warning text-dark px-2 py-0.5" title="Minimal Stok: {{ $item->min_stock }}">
                                        Menipis ({{ number_format($item->stock, 2, ',', '.') }})
                                    </span>
                                @else
                                    <span class="text-dark">{{ number_format($item->stock, 2, ',', '.') }}</span>
                                @endif
                            </td>
                            <td class="text-end font-monospace text-dark" style="font-size: 0.78rem;">
                                Rp {{ number_format($item->cost_price, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                <div class="d-flex align-items-center justify-content-center gap-1">
                                    <button type="button" 
                                            class="btn-action-icon btn-action-view btn-adjust-item" 
                                            title="Opname / Sesuaikan Stok"
                                            data-id="{{ $item->id }}"
                                            data-name="{{ $item->name }}"
                                            data-unit="{{ $item->unit }}"
                                            data-stock="{{ $item->stock }}">
                                        <i class="bi bi-clipboard-check text-white"></i>
                                    </button>
                                    <button type="button" 
                                            class="btn-action-icon btn-action-edit btn-edit-item" 
                                            title="Edit Barang"
                                            data-id="{{ $item->id }}"
                                            data-sku="{{ $item->sku }}"
                                            data-name="{{ $item->name }}"
                                            data-type="{{ $item->type }}"
                                            data-unit="{{ $item->unit }}"
                                            data-min-stock="{{ $item->min_stock }}"
                                            data-cost-price="{{ number_format($item->cost_price, 0, '', '') }}">
                                        <i class="bi bi-pencil text-white"></i>
                                    </button>
                                    <form action="{{ url('/v2/barang/' . $item->id) }}" method="POST" class="d-inline form-delete-barang m-0 p-0">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-action-icon btn-action-delete" title="Hapus Barang" onclick="return confirm('Apakah Anda yakin ingin menghapus barang ini?')">
                                            <i class="bi bi-trash text-white"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                <span style="font-size: 0.82rem;">Belum ada data barang ditemukan.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($items->hasPages())
        <div class="v2-card-footer bg-light py-2 px-3">
            <div class="d-flex align-items-center justify-content-between">
                <div class="text-muted" style="font-size: 0.72rem;">
                    Menampilkan {{ $items->firstItem() }} - {{ $items->lastItem() }} dari {{ $items->total() }} barang
                </div>
                <div>
                    {{ $items->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Modal Create Barang -->
<div class="modal fade" id="createBarangModal" tabindex="-1" aria-labelledby="createBarangModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light py-2.5 border-bottom">
                <h6 class="modal-title fw-bold d-flex align-items-center gap-2 m-0" id="createBarangModalLabel">
                    <i class="bi bi-plus-circle text-primary"></i> Tambah Barang Baru
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ url('/v2/barang') }}" method="POST">
                @csrf
                <div class="modal-body p-3.5">
                    <div class="mb-2.5">
                        <label class="form-label form-label-sm fw-semibold">Nama Barang <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm" placeholder="Contoh: Kain Batik Semi Sutra / Plastik Zipper 25x35" required>
                    </div>
                    <div class="row g-2 mb-2.5">
                        <div class="col-6">
                            <label class="form-label form-label-sm fw-semibold">SKU / Kode Barang</label>
                            <input type="text" name="sku" class="form-control form-control-sm" placeholder="Otomatis jika kosong">
                        </div>
                        <div class="col-6">
                            <label class="form-label form-label-sm fw-semibold">Jenis Barang <span class="text-danger">*</span></label>
                            <select name="type" class="form-select form-select-sm" required>
                                <option value="bahan">Bahan Baku (Kain, dll)</option>
                                <option value="kemasan">Kemasan (Kardus, Plastik)</option>
                                <option value="atk">ATK / Peralatan</option>
                                <option value="inventaris">Inventaris / Aset</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-2.5">
                        <div class="col-6">
                            <label class="form-label form-label-sm fw-semibold">Satuan Unit <span class="text-danger">*</span></label>
                            <input type="text" name="unit" class="form-control form-control-sm" placeholder="meter, pcs, kg, roll, pack" value="pcs" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label form-label-sm fw-semibold">Minimal Stok Warning</label>
                            <input type="number" name="min_stock" class="form-control form-control-sm" value="5" min="0">
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label form-label-sm fw-semibold">Stok Awal</label>
                            <input type="number" step="any" name="stock" class="form-control form-control-sm" value="0" min="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label form-label-sm fw-semibold">Harga Modal (HPP)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light text-muted">Rp</span>
                                <input type="number" name="cost_price" class="form-control form-control-sm" placeholder="0" min="0">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-sm btn-v2-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-v2-primary">
                        <i class="bi bi-save me-1"></i> Simpan Barang
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Barang -->
<div class="modal fade" id="editBarangModal" tabindex="-1" aria-labelledby="editBarangModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light py-2.5 border-bottom">
                <h6 class="modal-title fw-bold d-flex align-items-center gap-2 m-0" id="editBarangModalLabel">
                    <i class="bi bi-pencil-square text-primary"></i> Edit Data Barang
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editBarangForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body p-3.5">
                    <div class="mb-2.5">
                        <label class="form-label form-label-sm fw-semibold">Nama Barang <span class="text-danger">*</span></label>
                        <input type="text" id="editBarangName" name="name" class="form-control form-control-sm" required>
                    </div>
                    <div class="row g-2 mb-2.5">
                        <div class="col-6">
                            <label class="form-label form-label-sm fw-semibold">SKU / Kode Barang <span class="text-danger">*</span></label>
                            <input type="text" id="editBarangSku" name="sku" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label form-label-sm fw-semibold">Jenis Barang <span class="text-danger">*</span></label>
                            <select id="editBarangType" name="type" class="form-select form-select-sm" required>
                                <option value="bahan">Bahan Baku (Kain, dll)</option>
                                <option value="kemasan">Kemasan (Kardus, Plastik)</option>
                                <option value="atk">ATK / Peralatan</option>
                                <option value="inventaris">Inventaris / Aset</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-4">
                            <label class="form-label form-label-sm fw-semibold">Satuan Unit <span class="text-danger">*</span></label>
                            <input type="text" id="editBarangUnit" name="unit" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label form-label-sm fw-semibold">Min. Stok Warning</label>
                            <input type="number" id="editBarangMinStock" name="min_stock" class="form-control form-control-sm" min="0">
                        </div>
                        <div class="col-4">
                            <label class="form-label form-label-sm fw-semibold">Harga Modal (HPP)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light text-muted">Rp</span>
                                <input type="number" id="editBarangCostPrice" name="cost_price" class="form-control form-control-sm" min="0">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-sm btn-v2-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-v2-primary">
                        <i class="bi bi-save me-1"></i> Update Barang
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Opname / Adjust Stock Barang -->
<div class="modal fade" id="adjustStockModal" tabindex="-1" aria-labelledby="adjustStockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light py-2.5 border-bottom">
                <h6 class="modal-title fw-bold d-flex align-items-center gap-2 m-0" id="adjustStockModalLabel">
                    <i class="bi bi-clipboard-check text-success"></i> Opname / Penyesuaian Stok
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="adjustStockForm" method="POST">
                @csrf
                <div class="modal-body p-3.5">
                    <div class="p-2.5 bg-light rounded-3 border mb-3">
                        <div class="fw-bold text-dark" style="font-size: 0.82rem;" id="adjustItemName">-</div>
                        <div class="text-muted" style="font-size: 0.7rem;">Stok Sekarang: <span class="fw-bold text-primary font-monospace" id="adjustItemStock">0</span> <span id="adjustItemUnit">pcs</span></div>
                    </div>
                    <div class="mb-2.5">
                        <label class="form-label form-label-sm fw-semibold">Jumlah Penyesuaian <span class="text-danger">*</span></label>
                        <input type="number" step="any" name="quantity" class="form-control form-control-sm" placeholder="Contoh: +10 atau -5" required>
                        <div class="form-text" style="font-size: 0.65rem;">Gunakan angka positif (+) untuk penambahan stok dan minus (-) untuk pengurangan stok.</div>
                    </div>
                    <div class="mb-2.5">
                        <label class="form-label form-label-sm fw-semibold">Keterangan / Referensi <span class="text-danger">*</span></label>
                        <input type="text" name="reference" class="form-control form-control-sm" placeholder="Contoh: Stock Opname Bulan Ini / Rusak" required>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-sm btn-v2-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-v2-success">
                        <i class="bi bi-check-lg me-1"></i> Simpan Penyesuaian
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
    // Handler Edit Barang
    $('.btn-edit-item').on('click', function() {
        const id = $(this).data('id');
        const sku = $(this).data('sku');
        const name = $(this).data('name');
        const type = $(this).data('type');
        const unit = $(this).data('unit');
        const minStock = $(this).data('min-stock');
        const costPrice = $(this).data('cost-price');

        $('#editBarangForm').attr('action', '{{ url("/v2/barang") }}/' + id);
        $('#editBarangSku').val(sku);
        $('#editBarangName').val(name);
        $('#editBarangType').val(type);
        $('#editBarangUnit').val(unit);
        $('#editBarangMinStock').val(minStock);
        $('#editBarangCostPrice').val(costPrice);

        $('#editBarangModal').modal('show');
    });

    // Handler Adjust Stock
    $('.btn-adjust-item').on('click', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const unit = $(this).data('unit');
        const stock = $(this).data('stock');

        $('#adjustStockForm').attr('action', '{{ url("/v2/barang") }}/' + id + '/adjust');
        $('#adjustItemName').text(name);
        $('#adjustItemStock').text(stock);
        $('#adjustItemUnit').text(unit);

        $('#adjustStockModal').modal('show');
    });
});
</script>
@endpush
