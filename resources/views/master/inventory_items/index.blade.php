@extends('layouts.app')
@section('title', 'Master Barang Operasional & Bahan')
@section('page-title', 'Master Barang Operasional & Bahan')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card border shadow-sm overflow-hidden">

                {{-- Header --}}
                <div
                    class="card-header bg-info bg-opacity-10 d-flex justify-content-between align-items-center border-bottom py-2 px-3">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="fas fa-boxes me-2 text-info"></i>Master Barang (Bahan, Kemasan, ATK, Inventaris)
                        </h6>
                        <small class="text-muted d-block">Kelola kamus master data barang non-jualan (operasional, produksi, ATK)</small>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm px-3 rounded-3" data-bs-toggle="modal"
                        data-bs-target="#createItemModal">
                        <i class="fas fa-plus me-1"></i> Tambah Barang
                    </button>
                </div>

                <div class="card-body p-3">
                    {{-- Alert --}}
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    {{-- Validation/Delete Errors --}}
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                            <strong><i class="fas fa-exclamation-triangle me-2"></i>Terjadi Kesalahan:</strong>
                            <ul class="mb-0 mt-1 ps-3 small">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    {{-- Filter --}}
                    <div class="card border shadow-sm p-3 mb-3">
                        <form method="GET" action="{{ route('inventory_items.index') }}">
                            <div class="row g-2 align-items-end">
                                <div class="col-12 col-sm-6 col-md-3">
                                    <label class="form-label small">Nama Barang</label>
                                    <input type="text" name="name" class="form-control form-control-sm"
                                        placeholder="Cari nama..." value="{{ request('name') }}">
                                </div>
                                <div class="col-12 col-sm-6 col-md-3">
                                    <label class="form-label small">SKU / Kode</label>
                                    <input type="text" name="sku" class="form-control form-control-sm"
                                        placeholder="Cari SKU..." value="{{ request('sku') }}">
                                </div>
                                <div class="col-12 col-sm-6 col-md-3">
                                    <label class="form-label small">Jenis Barang</label>
                                    <select name="type" class="form-select form-select-sm">
                                        <option value="">-- Semua Jenis --</option>
                                        <option value="bahan" {{ request('type') == 'bahan' ? 'selected' : '' }}>Bahan Baku (Kain, dll)</option>
                                        <option value="kemasan" {{ request('type') == 'kemasan' ? 'selected' : '' }}>Kemasan (Kardus, dll)</option>
                                        <option value="atk" {{ request('type') == 'atk' ? 'selected' : '' }}>ATK / Peralatan</option>
                                        <option value="inventaris" {{ request('type') == 'inventaris' ? 'selected' : '' }}>Inventaris / Aset</option>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-6 col-md-auto d-flex gap-2">
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="fas fa-search me-1"></i> Cari
                                    </button>
                                    @if (request()->anyFilled(['name', 'sku', 'type']))
                                        <a href="{{ route('inventory_items.index') }}" class="btn btn-secondary btn-sm">
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
                                <tr class="small text-uppercase">
                                    <th class="text-center" style="width: 70px;">#</th>
                                    <th style="width: 150px;">SKU</th>
                                    <th>Nama Barang</th>
                                    <th class="text-center" style="width: 150px;">Jenis</th>
                                    <th class="text-center" style="width: 100px;">Satuan</th>
                                    <th class="text-end" style="width: 120px;">Stok</th>
                                    <th class="text-end" style="width: 150px;">Harga Modal</th>
                                    <th class="text-center" style="width: 150px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($items as $i => $item)
                                    <tr>
                                        <td class="text-center">
                                            <span class="badge bg-light text-secondary border small">
                                                {{ $items->firstItem() + $i }}
                                            </span>
                                        </td>
                                        <td>
                                            <code class="bg-light text-primary px-2 py-1 rounded border small fw-bold">{{ $item->sku }}</code>
                                        </td>
                                        <td>
                                            <span class="fw-semibold text-dark small">{{ $item->name }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if ($item->type === 'bahan')
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 small">
                                                    Bahan Baku
                                                </span>
                                            @elseif ($item->type === 'kemasan')
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1 small">
                                                    Kemasan
                                                </span>
                                            @elseif ($item->type === 'atk')
                                                <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1 small">
                                                    ATK / Peralatan
                                                </span>
                                            @elseif ($item->type === 'inventaris')
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 small">
                                                    Inventaris / Aset
                                                </span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1 small">
                                                    {{ ucfirst($item->type) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border small">{{ $item->unit }}</span>
                                        </td>
                                        <td class="text-end fw-bold">
                                            @if ($item->stock <= $item->min_stock)
                                                <span class="text-danger" title="Stok menipis! Minimal stok: {{ $item->min_stock }}">
                                                    {{ number_format($item->stock) }} <i class="fas fa-exclamation-circle ms-1"></i>
                                                </span>
                                            @else
                                                <span class="text-dark">
                                                    {{ number_format($item->stock) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-end font-monospace text-dark small">
                                            Rp {{ number_format($item->cost_price, 0, ',', '.') }}
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <button type="button" class="btn btn-warning btn-sm edit-item-btn"
                                                    title="Edit" data-bs-toggle="modal"
                                                    data-bs-target="#editItemModal" data-id="{{ $item->id }}"
                                                    data-sku="{{ $item->sku }}" data-name="{{ $item->name }}"
                                                    data-type="{{ $item->type }}" data-unit="{{ $item->unit }}"
                                                    data-min-stock="{{ $item->min_stock }}"
                                                    data-cost-price="{{ number_format($item->cost_price, 0, '', '') }}"
                                                    data-action="{{ route('inventory_items.update', $item) }}">
                                                    <i class="fas fa-pen"></i>
                                                </button>
                                                <form action="{{ route('inventory_items.destroy', $item) }}" method="POST"
                                                    class="confirm-delete d-inline"
                                                    data-message="Barang ini akan dihapus secara permanen!">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" title="Hapus"
                                                        data-bs-toggle="tooltip">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-5">
                                            <i class="fas fa-cubes fa-2x mb-3 d-block text-secondary opacity-25"></i>
                                            <p class="text-muted mb-0 small">Belum ada data barang.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    <div class="mt-3">
                        {{ $items->links('pagination::bootstrap-5') }}
                    </div>

                </div> {{-- End card-body --}}
            </div> {{-- End card --}}
        </div>
    </div>

    {{-- Modal Tambah --}}
    <div class="modal fade" id="createItemModal" tabindex="-1" aria-labelledby="createItemModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content overflow-hidden">
                <div class="d-flex align-items-center gap-3 p-3 border-bottom bg-primary bg-opacity-10">
                    <div class="bg-primary text-white rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 fs-5 p-2"
                        style="width: 38px; height: 38px;">
                        <i class="fas fa-plus"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="modal-title fw-bold fs-6 mb-0 text-dark" id="createItemModalLabel">Tambah Barang Baru</h5>
                        <p class="mb-0 text-muted small">Tambahkan item barang operasional, bahan baku, atau inventaris</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('inventory_items.store') }}" method="POST">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="create-sku" class="form-label fw-bold small text-dark">SKU / Kode Barang</label>
                                <input type="text" id="create-sku" name="sku" class="form-control form-control-sm"
                                    placeholder="Contoh: BRG-001 (Kosongkan untuk auto)">
                            </div>
                            <div class="col-md-6">
                                <label for="create-type" class="form-label fw-bold small text-dark">Jenis Barang <span class="text-danger">*</span></label>
                                <select id="create-type" name="type" class="form-select form-select-sm" required>
                                    <option value="bahan">Bahan Baku (Kain, benang, dll)</option>
                                    <option value="kemasan">Kemasan (Kardus, plastik, dll)</option>
                                    <option value="atk">ATK / Peralatan</option>
                                    <option value="inventaris">Inventaris / Aset Kantor</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label for="create-name" class="form-label fw-bold small text-dark">Nama Barang <span class="text-danger">*</span></label>
                                <input type="text" id="create-name" name="name" class="form-control form-control-sm"
                                    placeholder="Contoh: Kain Cotton Combed 30s / Kertas HVS" required>
                            </div>
                            <div class="col-md-6">
                                <label for="create-unit" class="form-label fw-bold small text-dark">Satuan <span class="text-danger">*</span></label>
                                <input type="text" id="create-unit" name="unit" class="form-control form-control-sm"
                                    placeholder="Contoh: PCS, METER, ROLL" required value="PCS">
                            </div>
                            <div class="col-md-6">
                                <label for="create-min-stock" class="form-label fw-bold small text-dark">Stok Minimum</label>
                                <input type="number" id="create-min-stock" name="min_stock" class="form-control form-control-sm"
                                    placeholder="0" value="5">
                            </div>
                            <div class="col-md-6">
                                <label for="create-stock" class="form-label fw-bold small text-dark">Stok Awal</label>
                                <input type="number" id="create-stock" name="stock" class="form-control form-control-sm"
                                    placeholder="0" value="0">
                            </div>
                            <div class="col-md-6">
                                <label for="create-cost-price" class="form-label fw-bold small text-dark">Harga Modal</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light">Rp</span>
                                    <input type="text" id="create-cost-price" name="cost_price" class="form-control form-control-sm rupiah-mask"
                                        placeholder="0">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-primary bg-opacity-10 px-4 py-3 border-top d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4 rounded-3">
                            <i class="fas fa-save"></i> Simpan Barang
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Edit --}}
    <div class="modal fade" id="editItemModal" tabindex="-1" aria-labelledby="editItemModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content overflow-hidden">
                <div class="d-flex align-items-center gap-3 p-3 border-bottom bg-warning bg-opacity-10">
                    <div class="bg-warning text-dark rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 fs-5 p-2"
                        style="width: 38px; height: 38px;">
                        <i class="fas fa-pen text-warning"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="modal-title fw-bold fs-6 mb-0 text-dark" id="editItemModalLabel">Edit Barang</h5>
                        <p class="mb-0 text-muted small">Perbarui data barang operasional/produksi</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="edit-form" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="edit-sku" class="form-label fw-bold small text-dark">SKU / Kode Barang</label>
                                <input type="text" id="edit-sku" name="sku" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-6">
                                <label for="edit-type" class="form-label fw-bold small text-dark">Jenis Barang <span class="text-danger">*</span></label>
                                <select id="edit-type" name="type" class="form-select form-select-sm" required>
                                    <option value="bahan">Bahan Baku</option>
                                    <option value="kemasan">Kemasan</option>
                                    <option value="atk">ATK / Peralatan</option>
                                    <option value="inventaris">Inventaris / Aset Kantor</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label for="edit-name" class="form-label fw-bold small text-dark">Nama Barang <span class="text-danger">*</span></label>
                                <input type="text" id="edit-name" name="name" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-6">
                                <label for="edit-unit" class="form-label fw-bold small text-dark">Satuan <span class="text-danger">*</span></label>
                                <input type="text" id="edit-unit" name="unit" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-6">
                                <label for="edit-min-stock" class="form-label fw-bold small text-dark">Stok Minimum</label>
                                <input type="number" id="edit-min-stock" name="min_stock" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-12">
                                <label for="edit-cost-price" class="form-label fw-bold small text-dark">Harga Modal</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light">Rp</span>
                                    <input type="text" id="edit-cost-price" name="cost_price" class="form-control form-control-sm rupiah-mask"
                                        placeholder="0">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-warning bg-opacity-10 px-4 py-3 border-top d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4 rounded-3">
                            <i class="fas fa-save"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                const formatNumber = (num) => parseFloat(num).toLocaleString('id-ID');
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

                $('.rupiah-mask').on('input', handleRupiahInput);

                $('.edit-item-btn').on('click', function() {
                    const action = $(this).data('action');
                    const sku = $(this).data('sku');
                    const name = $(this).data('name');
                    const type = $(this).data('type');
                    const unit = $(this).data('unit');
                    const minStock = $(this).data('min-stock');
                    const costPriceRaw = $(this).data('cost-price') || 0;

                    $('#edit-form').attr('action', action);
                    $('#edit-sku').val(sku);
                    $('#edit-name').val(name);
                    $('#edit-type').val(type);
                    $('#edit-unit').val(unit);
                    $('#edit-min-stock').val(minStock);
                    $('#edit-cost-price').val(costPriceRaw > 0 ? formatNumber(costPriceRaw) : '');
                });
            });
        </script>
    @endpush
@endsection
