@extends('layouts.app')
@section('title', 'Laporan Rekap Penjualan')
@section('page-title', 'Laporan Rekap Penjualan')

@section('content')
<div class="row g-4">
    {{-- CARD FILTER UNTUK REKAP PENJUALAN (PERSIS DENGAN REKAP PERSEDIAAN) --}}
    <div class="col-lg-5 col-md-6">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-info bg-opacity-10 py-2 px-3 border-0">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="fas fa-th-list text-info me-2"></i>Filter Rekap Penjualan
                </h6>
            </div>
            <div class="card-body p-3">
                <form id="salesReportForm" action="{{ route('reports.sales') }}" method="GET" onsubmit="return handleFormSubmit(this)">
                    <div class="mb-2.5">
                        <label class="form-label form-label-sm fw-semibold text-muted mb-1">Kategori</label>
                        <select name="category_id" class="form-select form-select-sm">
                            <option value="">Semua Kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" {{ $categoryId == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-2.5">
                        <label class="form-label form-label-sm fw-semibold text-muted mb-1">Merk</label>
                        <select name="brand_id" class="form-select form-select-sm">
                            <option value="">Semua Merk</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->id }}" {{ $brandId == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-2.5">
                        <label class="form-label form-label-sm fw-semibold text-muted mb-1">Jenis Produk</label>
                        <select name="is_bundle" class="form-select form-select-sm">
                            <option value="">Semua Jenis (Single &amp; BUNDLE)</option>
                            <option value="0" {{ $isBundle === '0' ? 'selected' : '' }}>📦 Single (Produk Standar)</option>
                            <option value="1" {{ $isBundle === '1' ? 'selected' : '' }}>🎁 BUNDLE / Paket Set</option>
                        </select>
                    </div>

                    <div class="mb-2.5">
                        <label class="form-label form-label-sm fw-semibold text-muted mb-1">Tipe Pre-Order (PO)</label>
                        <select name="po_status" class="form-select form-select-sm">
                            <option value="">Semua Tipe (PO &amp; Reguler)</option>
                            <option value="1" {{ $isPo === '1' ? 'selected' : '' }}>⏳ Pre-Order (PO)</option>
                            <option value="0" {{ $isPo === '0' ? 'selected' : '' }}>📦 Reguler (Bukan PO)</option>
                        </select>
                    </div>

                    <div class="mb-2.5">
                        <label class="form-label form-label-sm fw-semibold text-muted mb-1">Saluran Penjualan / Channel</label>
                        <select name="channel_code" class="form-select form-select-sm">
                            <option value="all" {{ $channelCode === 'all' ? 'selected' : '' }}>Semua Saluran (Offline POS &amp; Online)</option>
                            <option value="offline" {{ $channelCode === 'offline' ? 'selected' : '' }}>POS Offline (Toko Fisik)</option>
                            <option value="shopee" {{ $channelCode === 'shopee' ? 'selected' : '' }}>Shopee</option>
                            <option value="tiktok" {{ $channelCode === 'tiktok' ? 'selected' : '' }}>TikTok Shop</option>
                            <option value="lazada" {{ $channelCode === 'lazada' ? 'selected' : '' }}>Lazada</option>
                        </select>
                    </div>

                    <div class="mb-2.5">
                        <label class="form-label form-label-sm fw-semibold text-muted mb-1">Kategori Pelanggan</label>
                        <select name="customer_category" class="form-select form-select-sm">
                            <option value="all" {{ $customerCat === 'all' ? 'selected' : '' }}>Semua Kategori Pelanggan</option>
                            <option value="dropship" {{ $customerCat === 'dropship' ? 'selected' : '' }}>Dropshipper / Reseller</option>
                            <option value="umum" {{ $customerCat === 'umum' ? 'selected' : '' }}>Pelanggan Umum / Eceran</option>
                        </select>
                    </div>

                    <div class="row g-2 mb-2.5">
                        <div class="col-6">
                            <label class="form-label form-label-sm fw-semibold text-muted mb-1">Dari Tanggal</label>
                            <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label form-label-sm fw-semibold text-muted mb-1">Sampai Tanggal</label>
                            <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo }}">
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="hide_zero_sales" value="1" id="hideZeroSales" {{ !empty($hideZeroSales) ? 'checked' : '' }}>
                            <label class="form-check-label small fw-semibold text-dark" for="hideZeroSales" style="font-size:0.8rem;">
                                Sembunyikan / Hilangkan Produk 0 Penjualan
                            </label>
                        </div>
                    </div>

                    {{-- PILIHAN FORMAT LAPORAN (BARU) --}}
                    <div class="p-2.5 bg-primary bg-opacity-10 border border-primary border-opacity-25 rounded-3 mb-3">
                        <label class="form-label form-label-sm fw-bold text-primary mb-1">
                            <i class="fas fa-file-export me-1"></i> Pilihan Format Laporan
                        </label>
                        <select id="report_format" name="format" class="form-select form-select-sm fw-semibold border-primary">
                            <option value="web">💻 Tampilan Layar Web (Interaktif)</option>
                            <option value="print">🖨️ Cetak Langsung / Print PDF</option>
                            <option value="excel">📊 Unduh File Excel / CSV</option>
                        </select>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" id="btnSubmitForm" class="btn btn-sm btn-primary px-4 fw-bold shadow-sm" style="background:#0d6efd">
                            <i class="fas fa-print me-1"></i> Cetak Rekap Penjualan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- TAPILAN HASIL TABEL & REKAP (APABILA DITAMPILKAN DI WEB) --}}
    <div class="col-lg-7 col-md-6">
        {{-- RINGKASAN CARDS --}}
        <div class="row g-2 mb-3">
            <div class="col-6">
                <div class="p-3 bg-white shadow-sm rounded-3 border-start border-primary border-4">
                    <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:0.65rem;">Total Omset Penjualan</small>
                    <strong class="text-primary font-monospace fs-6">Rp {{ number_format($grandTotalOmset, 0, ',', '.') }}</strong>
                </div>
            </div>
            <div class="col-6">
                <div class="p-3 bg-white shadow-sm rounded-3 border-start border-success border-4">
                    <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:0.65rem;">Total Barang Terjual</small>
                    <strong class="text-success font-monospace fs-6">{{ number_format($grandTotalQty) }} <span class="fw-normal text-muted fs-7">Pcs</span></strong>
                </div>
            </div>
            <div class="col-6">
                <div class="p-3 bg-white shadow-sm rounded-3 border-start border-warning border-4">
                    <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:0.65rem;">Total HPP Modal</small>
                    <strong class="text-dark font-monospace fs-6">Rp {{ number_format($grandTotalHpp, 0, ',', '.') }}</strong>
                </div>
            </div>
            <div class="col-6">
                <div class="p-3 bg-white shadow-sm rounded-3 border-start border-info border-4">
                    <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:0.65rem;">Est. Laba Kotor (Gross)</small>
                    <strong class="text-info font-monospace fs-6">Rp {{ number_format($grandTotalProfit, 0, ',', '.') }}</strong>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-3 bg-white">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2.5">
                    <h6 class="fw-bold text-dark mb-0" style="font-size:0.85rem;">
                        <i class="fas fa-list text-primary me-1"></i> Rekap Penjualan ({{ count($items) }} Produk)
                    </h6>
                    <small class="text-muted font-monospace" style="font-size:0.7rem;">
                        {{ date('d/m/Y', strtotime($dateFrom)) }} — {{ date('d/m/Y', strtotime($dateTo)) }}
                    </small>
                </div>

                <div class="table-responsive" style="max-height: 480px; overflow-y: auto;">
                    <table class="table table-sm table-hover align-middle border mb-0 rounded-2">
                        <thead class="sticky-top bg-light" style="font-size:0.7rem; text-transform:uppercase;">
                            <tr>
                                <th class="py-2 px-2">SKU</th>
                                <th>Nama Produk</th>
                                <th class="text-center">Stok</th>
                                <th class="text-center text-primary">Qty POS</th>
                                <th class="text-center text-success">Qty MP</th>
                                <th class="text-center fw-bold">Total Terjual</th>
                                <th class="text-end fw-bold text-primary">Total Omset</th>
                                <th class="text-end text-success">Laba Kotor</th>
                            </tr>
                        </thead>
                        <tbody style="font-size:0.75rem;">
                            @forelse($items as $row)
                                <tr>
                                    <td class="px-2 font-monospace fw-bold text-primary">{{ $row['sku'] ?: '—' }}</td>
                                    <td>
                                        <div class="fw-semibold text-dark text-truncate" style="max-width:180px;">{{ $row['name'] }}</div>
                                    </td>
                                    <td class="text-center font-monospace">{{ number_format($row['stock']) }}</td>
                                    <td class="text-center font-monospace text-primary fw-semibold">{{ number_format($row['qty_offline']) }}</td>
                                    <td class="text-center font-monospace text-success fw-semibold">{{ number_format($row['qty_online']) }}</td>
                                    <td class="text-center font-monospace fw-bold">{{ number_format($row['qty_total']) }}</td>
                                    <td class="text-end font-monospace fw-bold text-primary">Rp {{ number_format($row['total_omset'], 0, ',', '.') }}</td>
                                    <td class="text-end font-monospace fw-bold text-success">Rp {{ number_format($row['gross_profit'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">Tidak ada data penjualan sesuai filter.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function handleFormSubmit(form) {
    const format = document.getElementById('report_format').value;
    const printUrl = "{{ route('reports.sales.print') }}";
    const exportUrl = "{{ route('reports.sales.export') }}";
    const webUrl = "{{ route('reports.sales') }}";

    if (format === 'print') {
        form.action = printUrl;
        form.target = "_blank";
    } else if (format === 'excel') {
        form.action = exportUrl;
        form.target = "_self";
    } else {
        form.action = webUrl;
        form.target = "_self";
    }
    return true;
}
</script>
@endsection
