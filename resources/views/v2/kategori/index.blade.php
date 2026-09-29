@extends('v2.layouts.app')

@section('title', 'Kategori & Model Variasi V2')

@section('content')
<!-- Page Header Compact -->
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2">
            <i class="bi bi-tags-fill text-primary fs-5"></i> Manajemen Kategori & Model Variasi
        </h1>
        <p class="v2-page-subtitle mb-0">Kelola pengelompokan kategori utama dan model/variasi produk master (Lengan Panjang/Pendek, Jenjang SD/SMP/SMA, dll)</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ url('/v2/kategori-brand') }}" class="btn btn-sm btn-v2-secondary py-1.5 px-2.5" title="Refresh Page">
            <i class="bi bi-arrow-clockwise"></i>
        </a>
        <button type="button" class="btn btn-sm btn-v2-primary py-1.5 px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
            <i class="bi bi-folder-plus me-1"></i> Tambah Kategori
        </button>
        <button type="button" class="btn btn-sm py-1.5 px-3 text-white shadow-sm" style="background-color: #7c3aed !important; border-color: #7c3aed !important;" data-bs-toggle="modal" data-bs-target="#createBrandModal">
            <i class="bi bi-sliders me-1"></i> Tambah Model / Variasi
        </button>
    </div>
</div>

<!-- Success Alert -->
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show py-2 px-3 mb-3 border-0 shadow-sm" style="font-size: 0.78rem; background-color: #dcfce7; color: #15803d; border-left: 4px solid #22c55e !important;" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close py-2.5" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Summary KPI Cards (Rich Vibrant Cards) -->
<div class="row g-2 mb-3.5">
    <div class="col-12 col-sm-6 col-md-3">
        <div class="v2-stat-widget p-3 rounded-3 shadow-sm" style="background: linear-gradient(135deg, #eff6ff 0%, #ffffff 100%); border: 1px solid #bfdbfe;">
            <div class="v2-stat-icon-wrapper" style="background-color: #dbeafe; color: #1d4ed8; width: 40px; height: 40px; font-size: 1.1rem;">
                <i class="bi bi-folder2-open"></i>
            </div>
            <div class="v2-stat-info">
                <span class="v2-stat-num" style="color: #1e40af; font-size: 1.35rem; font-weight: 800;">{{ number_format($counts['total_categories']) }}</span>
                <span class="v2-stat-lbl text-primary fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.3px;">TOTAL KATEGORI</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="v2-stat-widget p-3 rounded-3 shadow-sm" style="background: linear-gradient(135deg, #faf5ff 0%, #ffffff 100%); border: 1px solid #e9d5ff;">
            <div class="v2-stat-icon-wrapper" style="background-color: #f3e8ff; color: #7e22ce; width: 40px; height: 40px; font-size: 1.1rem;">
                <i class="bi bi-sliders"></i>
            </div>
            <div class="v2-stat-info">
                <span class="v2-stat-num" style="color: #6b21a8; font-size: 1.35rem; font-weight: 800;">{{ number_format($counts['total_brands']) }}</span>
                <span class="v2-stat-lbl fw-semibold" style="color: #7e22ce; font-size: 0.72rem; letter-spacing: 0.3px;">MODEL / VARIASI</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="v2-stat-widget p-3 rounded-3 shadow-sm" style="background: linear-gradient(135deg, #ecfdf5 0%, #ffffff 100%); border: 1px solid #a7f3d0;">
            <div class="v2-stat-icon-wrapper" style="background-color: #d1fae5; color: #047857; width: 40px; height: 40px; font-size: 1.1rem;">
                <i class="bi bi-box-seam"></i>
            </div>
            <div class="v2-stat-info">
                <span class="v2-stat-num" style="color: #065f46; font-size: 1.35rem; font-weight: 800;">{{ number_format($counts['categorized_products']) }}</span>
                <span class="v2-stat-lbl text-success fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.3px;">PRODUK TERKATEGORI</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="v2-stat-widget p-3 rounded-3 shadow-sm" style="background: linear-gradient(135deg, #fffbeb 0%, #ffffff 100%); border: 1px solid #fde68a;">
            <div class="v2-stat-icon-wrapper" style="background-color: #fef3c7; color: #b45309; width: 40px; height: 40px; font-size: 1.1rem;">
                <i class="bi bi-tags"></i>
            </div>
            <div class="v2-stat-info">
                <span class="v2-stat-num" style="color: #92400e; font-size: 1.35rem; font-weight: 800;">{{ number_format($counts['branded_products']) }}</span>
                <span class="v2-stat-lbl text-warning fw-semibold" style="color: #b45309; font-size: 0.72rem; letter-spacing: 0.3px;">PRODUK TER-MODEL</span>
            </div>
        </div>
    </div>
</div>

<!-- Separate Colorful Tab Navigation Pills -->
<div class="d-flex align-items-center justify-content-between mb-3 mt-4 px-1">
    <ul class="nav nav-pills gap-2" id="masterTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link v2-tab-pill {{ $activeTab === 'category' ? 'active-cat' : '' }}" id="category-tab" data-bs-toggle="tab" data-bs-target="#category-pane" type="button" role="tab" aria-controls="category-pane" aria-selected="{{ $activeTab === 'category' ? 'true' : 'false' }}">
                <i class="bi bi-folder2 me-1.5"></i> Data Kategori
                <span class="badge rounded-pill bg-white text-primary ms-1.5 px-2 py-0.5" style="font-size: 0.68rem;">{{ $counts['total_categories'] }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link v2-tab-pill {{ $activeTab === 'brand' ? 'active-brand' : '' }}" id="brand-tab" data-bs-toggle="tab" data-bs-target="#brand-pane" type="button" role="tab" aria-controls="brand-pane" aria-selected="{{ $activeTab === 'brand' ? 'true' : 'false' }}">
                <i class="bi bi-sliders me-1.5"></i> Data Model & Variasi <small class="fw-normal opacity-75 ms-1">(Lengan Panjang/Pendek, SD/SMP/SMA, dll)</small>
                <span class="badge rounded-pill bg-white text-purple ms-1.5 px-2 py-0.5" style="font-size: 0.68rem; color: #7c3aed !important;">{{ $counts['total_brands'] }}</span>
            </button>
        </li>
    </ul>
</div>

<!-- Main Content Container with Card -->
<div class="v2-card mb-4 shadow-sm border">
    <div class="v2-card-body p-0">
        <div class="tab-content" id="masterTabsContent">
            <!-- TAB 1: KATEGORI -->
            <div class="tab-pane fade {{ $activeTab === 'category' ? 'show active' : '' }}" id="category-pane" role="tabpanel" aria-labelledby="category-tab">
                <!-- Filter Box Kategori -->
                <div class="p-3 bg-light border-bottom">
                    <form method="GET" action="{{ url('/v2/kategori-brand') }}" class="row g-2 align-items-center">
                        <input type="hidden" name="tab" value="category">
                        <div class="col-12 col-md-5 col-lg-4">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" name="cat_name" class="form-control border-start-0 ps-0" placeholder="Cari nama kategori (SD, SMP, Batik...)" value="{{ request('cat_name') }}">
                            </div>
                        </div>
                        <div class="col-auto d-flex align-items-center gap-1.5">
                            <button type="submit" class="btn btn-sm btn-v2-primary py-1 px-3">
                                <i class="bi bi-search me-1"></i> Cari Kategori
                            </button>
                            @if(request('cat_name'))
                                <a href="{{ url('/v2/kategori-brand?tab=category') }}" class="btn btn-sm btn-v2-secondary py-1" title="Reset Search">
                                    <i class="bi bi-x-lg"></i>
                                </a>
                            @endif
                        </div>
                    </form>
                </div>

                <!-- Table Kategori -->
                <div class="v2-table-responsive">
                    <table class="v2-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 45px;" class="text-center">#</th>
                                <th>NAMA KATEGORI</th>
                                <th class="text-center">JUMLAH PRODUK MASTER</th>
                                <th>TANGGAL DIBUAT</th>
                                <th class="text-center" style="width: 100px;">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categories as $index => $cat)
                                <tr>
                                    <td class="text-center text-muted" style="font-size: 0.72rem;">
                                        {{ $categories->firstItem() + $index }}
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2.5 py-0.5">
                                            <div class="v2-stat-icon-wrapper blue rounded-circle" style="width: 30px; height: 30px; font-size: 0.85rem;">
                                                <i class="bi bi-folder2"></i>
                                            </div>
                                            <div>
                                                <span class="fw-bold text-dark d-block" style="font-size: 0.82rem;">{{ $cat->name }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1 rounded-pill font-monospace" style="font-size: 0.7rem;">
                                            <i class="bi bi-box-seam me-1"></i>{{ number_format($cat->products_count) }} Produk
                                        </span>
                                    </td>
                                    <td class="text-muted" style="font-size: 0.72rem;">
                                        <i class="bi bi-calendar3 me-1 text-secondary"></i>{{ $cat->created_at ? $cat->created_at->format('d M Y, H:i') : '-' }}
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex align-items-center justify-content-center gap-1.5">
                                            <button type="button" class="btn-action-icon btn-action-edit edit-category-btn"
                                                    data-id="{{ $cat->id }}"
                                                    data-name="{{ $cat->name }}"
                                                    title="Edit Kategori">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form action="{{ route('v2.kategori.destroy', $cat->id) }}" method="POST" class="d-inline m-0 p-0">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn-action-icon btn-action-delete confirm-delete"
                                                        data-name="Kategori {{ $cat->name }}"
                                                        title="Hapus Kategori">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted" style="font-size: 0.78rem;">
                                        <i class="bi bi-folder-x fs-2 d-block mb-1 text-secondary"></i>
                                        Tidak ditemukan data kategori.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($categories->hasPages())
                    <div class="v2-card-footer bg-light p-2.5 border-top d-flex align-items-center justify-content-between">
                        <div class="text-muted" style="font-size: 0.72rem;">
                            Menampilkan {{ $categories->firstItem() ?? 0 }} - {{ $categories->lastItem() ?? 0 }} dari {{ $categories->total() }} kategori
                        </div>
                        <div>
                            {{ $categories->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                @endif
            </div>

            <!-- TAB 2: MODEL & VARIASI -->
            <div class="tab-pane fade {{ $activeTab === 'brand' ? 'show active' : '' }}" id="brand-pane" role="tabpanel" aria-labelledby="brand-tab">
                <!-- Filter Box Model & Variasi -->
                <div class="p-3 bg-light border-bottom">
                    <form method="GET" action="{{ url('/v2/kategori-brand') }}" class="row g-2 align-items-center">
                        <input type="hidden" name="tab" value="brand">
                        <div class="col-12 col-md-5 col-lg-4">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" name="brand_name" class="form-control border-start-0 ps-0" placeholder="Cari model / variasi (Lengan Panjang, SD...)" value="{{ request('brand_name') }}">
                            </div>
                        </div>
                        <div class="col-auto d-flex align-items-center gap-1.5">
                            <button type="submit" class="btn btn-sm text-white py-1 px-3" style="background-color: #7c3aed !important; border-color: #7c3aed !important;">
                                <i class="bi bi-search me-1"></i> Cari Model
                            </button>
                            @if(request('brand_name'))
                                <a href="{{ url('/v2/kategori-brand?tab=brand') }}" class="btn btn-sm btn-v2-secondary py-1" title="Reset Search">
                                    <i class="bi bi-x-lg"></i>
                                </a>
                            @endif
                        </div>
                    </form>
                </div>

                <!-- Table Model & Variasi -->
                <div class="v2-table-responsive">
                    <table class="v2-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 45px;" class="text-center">#</th>
                                <th>NAMA MODEL / VARIASI PRODUK</th>
                                <th class="text-center">JUMLAH PRODUK MASTER</th>
                                <th>TANGGAL DIBUAT</th>
                                <th class="text-center" style="width: 100px;">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($brands as $index => $b)
                                <tr>
                                    <td class="text-center text-muted" style="font-size: 0.72rem;">
                                        {{ $brands->firstItem() + $index }}
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2.5 py-0.5">
                                            <div class="v2-stat-icon-wrapper purple rounded-circle" style="width: 30px; height: 30px; font-size: 0.85rem; background-color: #f3e8ff; color: #7e22ce;">
                                                <i class="bi bi-sliders"></i>
                                            </div>
                                            <div>
                                                <span class="fw-bold text-dark d-block" style="font-size: 0.82rem;">{{ $b->name }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge px-2.5 py-1 rounded-pill font-monospace" style="font-size: 0.7rem; background-color: #f3e8ff; color: #7e22ce; border: 1px solid #e9d5ff;">
                                            <i class="bi bi-box-seam me-1"></i>{{ number_format($b->products_count) }} Produk
                                        </span>
                                    </td>
                                    <td class="text-muted" style="font-size: 0.72rem;">
                                        <i class="bi bi-calendar3 me-1 text-secondary"></i>{{ $b->created_at ? $b->created_at->format('d M Y, H:i') : '-' }}
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex align-items-center justify-content-center gap-1.5">
                                            <button type="button" class="btn-action-icon btn-action-edit edit-brand-btn"
                                                    data-id="{{ $b->id }}"
                                                    data-name="{{ $b->name }}"
                                                    title="Edit Model/Variasi">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form action="{{ route('v2.brand.destroy', $b->id) }}" method="POST" class="d-inline m-0 p-0">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn-action-icon btn-action-delete confirm-delete"
                                                        data-name="Model {{ $b->name }}"
                                                        title="Hapus Model/Variasi">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted" style="font-size: 0.78rem;">
                                        <i class="bi bi-sliders fs-2 d-block mb-1 text-secondary"></i>
                                        Tidak ditemukan data model / variasi.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($brands->hasPages())
                    <div class="v2-card-footer bg-light p-2.5 border-top d-flex align-items-center justify-content-between">
                        <div class="text-muted" style="font-size: 0.72rem;">
                            Menampilkan {{ $brands->firstItem() ?? 0 }} - {{ $brands->lastItem() ?? 0 }} dari {{ $brands->total() }} model/variasi
                        </div>
                        <div>
                            {{ $brands->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Kategori -->
<div class="modal fade" id="createCategoryModal" tabindex="-1" aria-labelledby="createCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg">
            <form action="{{ route('v2.kategori.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-light py-2.5 border-bottom">
                    <h6 class="modal-title fw-bold d-flex align-items-center gap-2 m-0" id="createCategoryModalLabel">
                        <i class="bi bi-folder-plus text-primary"></i> Tambah Kategori Baru
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="mb-2">
                        <label class="form-label fw-semibold" style="font-size: 0.75rem;">Nama Kategori <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm" placeholder="Contoh: Seragam SD, Seragam SMP, Batik..." required autofocus>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-3">
                    <button type="button" class="btn btn-sm btn-v2-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-v2-primary"><i class="bi bi-save me-1"></i> Simpan Kategori</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Kategori -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg">
            <form id="editCategoryForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header bg-light py-2.5 border-bottom">
                    <h6 class="modal-title fw-bold d-flex align-items-center gap-2 m-0" id="editCategoryModalLabel">
                        <i class="bi bi-pencil-square text-primary"></i> Edit Kategori
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="mb-2">
                        <label class="form-label fw-semibold" style="font-size: 0.75rem;">Nama Kategori <span class="text-danger">*</span></label>
                        <input type="text" id="editCatName" name="name" class="form-control form-control-sm" required autofocus>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-3">
                    <button type="button" class="btn btn-sm btn-v2-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-v2-primary"><i class="bi bi-save me-1"></i> Update Kategori</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Tambah Brand / Model -->
<div class="modal fade" id="createBrandModal" tabindex="-1" aria-labelledby="createBrandModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg">
            <form action="{{ route('v2.brand.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-light py-2.5 border-bottom">
                    <h6 class="modal-title fw-bold d-flex align-items-center gap-2 m-0" id="createBrandModalLabel" style="color: #7c3aed;">
                        <i class="bi bi-sliders"></i> Tambah Model / Variasi
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="mb-2">
                        <label class="form-label fw-semibold" style="font-size: 0.75rem;">Nama Model / Variasi <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm" placeholder="Contoh: Lengan Panjang, Lengan Pendek, SD, SMP..." required autofocus>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-3">
                    <button type="button" class="btn btn-sm btn-v2-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm text-white" style="background-color: #7c3aed !important; border-color: #7c3aed !important;"><i class="bi bi-save me-1"></i> Simpan Model</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Brand / Model -->
<div class="modal fade" id="editBrandModal" tabindex="-1" aria-labelledby="editBrandModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg">
            <form id="editBrandForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header bg-light py-2.5 border-bottom">
                    <h6 class="modal-title fw-bold d-flex align-items-center gap-2 m-0" id="editBrandModalLabel" style="color: #7c3aed;">
                        <i class="bi bi-pencil-square"></i> Edit Model / Variasi
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="mb-2">
                        <label class="form-label fw-semibold" style="font-size: 0.75rem;">Nama Model / Variasi <span class="text-danger">*</span></label>
                        <input type="text" id="editBrandName" name="name" class="form-control form-control-sm" required autofocus>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-3">
                    <button type="button" class="btn btn-sm btn-v2-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm text-white" style="background-color: #7c3aed !important; border-color: #7c3aed !important;"><i class="bi bi-save me-1"></i> Update Model</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* Tab Pill Custom Styling */
    .v2-tab-pill {
        background-color: #f1f5f9 !important;
        color: #475569 !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 20px !important;
        font-weight: 700 !important;
        font-size: 0.78rem !important;
        padding: 0.45rem 1.2rem !important;
        transition: all 0.18s ease-in-out !important;
        display: inline-flex !important;
        align-items: center !important;
    }

    .v2-tab-pill:hover {
        background-color: #e2e8f0 !important;
        color: #0f172a !important;
    }

    /* Active Category Tab */
    .v2-tab-pill.active-cat,
    .v2-tab-pill.active[data-bs-target="#category-pane"] {
        background-color: #0284c7 !important;
        color: #ffffff !important;
        border-color: #0284c7 !important;
        box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3) !important;
    }

    .v2-tab-pill.active-cat .badge,
    .v2-tab-pill.active[data-bs-target="#category-pane"] .badge {
        color: #0284c7 !important;
    }

    /* Active Brand / Model Tab */
    .v2-tab-pill.active-brand,
    .v2-tab-pill.active[data-bs-target="#brand-pane"] {
        background-color: #7c3aed !important;
        color: #ffffff !important;
        border-color: #7c3aed !important;
        box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3) !important;
    }

    .v2-tab-pill.active-brand .badge,
    .v2-tab-pill.active[data-bs-target="#brand-pane"] .badge {
        color: #7c3aed !important;
    }
</style>
@endpush

@push('scripts')
<script>
    // Tab activation styling sync
    $('#category-tab').on('click', function() {
        $('#category-tab').addClass('active-cat').removeClass('active-brand');
        $('#brand-tab').removeClass('active-brand active-cat');
    });

    $('#brand-tab').on('click', function() {
        $('#brand-tab').addClass('active-brand').removeClass('active-cat');
        $('#category-tab').removeClass('active-cat active-brand');
    });

    // Edit Category Modal Trigger
    $(document).on('click', '.edit-category-btn', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');

        $('#editCatName').val(name);
        $('#editCategoryForm').attr('action', '{{ url("/v2/kategori") }}/' + id);
        
        var editModal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
        editModal.show();
    });

    // Edit Brand Modal Trigger
    $(document).on('click', '.edit-brand-btn', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');

        $('#editBrandName').val(name);
        $('#editBrandForm').attr('action', '{{ url("/v2/brand") }}/' + id);

        var editModal = new bootstrap.Modal(document.getElementById('editBrandModal'));
        editModal.show();
    });

    // Delete Confirmation with SweetAlert
    $(document).on('click', '.confirm-delete', function(e) {
        e.preventDefault();
        var form = $(this).closest('form');
        var name = $(this).data('name') || 'Item';
        Swal.fire({
            title: 'Hapus Data?',
            text: 'Apakah Anda yakin ingin menghapus "' + name + '"?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
</script>
@endpush
@endsection
