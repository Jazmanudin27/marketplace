@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header Card (Pure Modern Portal Style) -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-4 p-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                        <i class="bi bi-box-seam-fill fs-2"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-1 text-dark" style="letter-spacing: -0.3px;">Penerimaan Barang Konsinyasi (Titipan)</h4>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 small fw-semibold">
                                <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i> Status: Transaksi Aktif / Realtime
                            </span>
                            <span class="text-muted small">Kelola penerimaan barang titipan supplier & persediaan gudang master</span>
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('supplier_consignments.stock_card') }}" class="btn btn-outline-success rounded-pill px-3.5 fw-bold shadow-sm d-flex align-items-center gap-2">
                        <i class="bi bi-card-checklist fs-6"></i> Kartu Stok Supplier
                    </a>
                    <a href="{{ route('supplier_consignments.create') }}" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm d-flex align-items-center gap-2">
                        <i class="bi bi-plus-circle-fill fs-6"></i> Transaksi Baru
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Notifications -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm rounded-3 mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm rounded-3 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- 4 Summary Metric Cards (Matching Screenshot Layout) -->
    <div class="row g-3 mb-4">
        <!-- Card 1: Barang Terdaftar / Total Qty Terima (Blue Theme) -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 bg-white h-100 p-3" style="border: 1px solid #e2e8f0;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-uppercase fw-bold small text-muted mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                            BARANG TERDAFTAR (QTY)
                        </div>
                        <div class="fs-2 fw-bolder text-dark line-height-1">
                            {{ number_format($totalQtyReceived ?? 0) }}
                        </div>
                        <div class="text-muted small mt-2 d-flex align-items-center gap-1" style="font-size: 0.78rem;">
                            <span class="text-primary font-weight-bold">•</span>
                            <span>{{ $totalConsignments ?? 0 }} Transaksi Titipan</span>
                        </div>
                    </div>
                    <div class="bg-primary bg-opacity-10 text-primary rounded-4 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px; min-width: 52px;">
                        <i class="bi bi-journal-bookmark-fill fs-3"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Stok Tersedia / Hadir (Green Theme) -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 bg-white h-100 p-3" style="border: 1px solid #e2e8f0;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-uppercase fw-bold small text-muted mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                            STOK TERSEDIA (SISA)
                        </div>
                        <div class="fs-2 fw-bolder text-success line-height-1">
                            {{ number_format($totalSisaStok ?? 0) }}
                        </div>
                        <div class="text-muted small mt-2 d-flex align-items-center gap-1" style="font-size: 0.78rem;">
                            <span class="text-success font-weight-bold">•</span>
                            <span>{{ ($totalQtyReceived ?? 0) > 0 ? number_format((($totalSisaStok ?? 0) / $totalQtyReceived) * 100, 0) : 0 }}% Stok Gudang</span>
                        </div>
                    </div>
                    <div class="bg-success bg-opacity-10 text-success rounded-4 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px; min-width: 52px;">
                        <i class="bi bi-check-circle-fill fs-3"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: Terjual / Sakit & Izin (Orange Theme) -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 bg-white h-100 p-3" style="border: 1px solid #e2e8f0;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-uppercase fw-bold small text-muted mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                            TERJUAL / DIKEMAS
                        </div>
                        <div class="fs-2 fw-bolder text-warning line-height-1" style="color: #d97706 !important;">
                            {{ number_format($totalQtySold ?? 0) }}
                        </div>
                        <div class="text-muted small mt-2 d-flex align-items-center gap-1" style="font-size: 0.78rem;">
                            <span class="text-warning font-weight-bold" style="color: #d97706 !important;">•</span>
                            <span>Menunggu Setoran</span>
                        </div>
                    </div>
                    <div class="bg-warning bg-opacity-15 rounded-4 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px; min-width: 52px;">
                        <i class="bi bi-bag-check-fill fs-3" style="color: #ea580c;"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 4: Total HPP Modal / Alpa (Red Theme) -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 bg-white h-100 p-3" style="border: 1px solid #e2e8f0;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-uppercase fw-bold small text-muted mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                            TOTAL HPP MODAL
                        </div>
                        <div class="fs-4 fw-bolder text-danger font-monospace line-height-1">
                            Rp {{ number_format($totalAmountHpp ?? 0, 0, ',', '.') }}
                        </div>
                        <div class="text-muted small mt-2 d-flex align-items-center gap-1" style="font-size: 0.78rem;">
                            <span class="text-danger font-weight-bold">•</span>
                            <span>Nilai Modal Titipan</span>
                        </div>
                    </div>
                    <div class="bg-danger bg-opacity-10 text-danger rounded-4 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px; min-width: 52px;">
                        <i class="bi bi-exclamation-octagon-fill fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Card Container (Title + Filter + Table) -->
    <div class="card border-0 shadow-sm rounded-4 bg-white">
        <!-- Section Header Bar -->
        <div class="card-header bg-white p-4 border-bottom" style="border-color: #f1f5f9 !important;">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-journal-text fs-4 text-primary"></i>
                    <h5 class="fw-bold mb-0 text-dark">Pencatatan Penerimaan Barang Titipan per Supplier</h5>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <span class="badge bg-light text-muted border px-3 py-2 rounded-pill small">
                        Status: <strong class="text-primary">Data Terverifikasi</strong>
                    </span>
                </div>
            </div>
        </div>

        <div class="card-body p-4">
            <!-- Filter Bar Form (Matching Screenshot Filter Row) -->
            <form method="GET" action="{{ route('supplier_consignments.index') }}" class="mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-muted text-uppercase mb-1" style="font-size: 0.7rem;">Cari Transaksi / Ref</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control border-start-0" placeholder="No. Referensi / Catatan..." value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-muted text-uppercase mb-1" style="font-size: 0.7rem;">Supplier (Pemilik Barang)</label>
                        <select name="supplier_id" class="form-select">
                            <option value="">-- Semua Supplier --</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold small text-muted text-uppercase mb-1" style="font-size: 0.7rem;">Tanggal Mulai</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold small text-muted text-uppercase mb-1" style="font-size: 0.7rem;">Tanggal Akhir</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100 rounded-3 fw-bold d-flex align-items-center justify-content-center gap-1">
                            <i class="bi bi-filter"></i> Filter
                        </button>
                        <a href="{{ route('supplier_consignments.index') }}" class="btn btn-outline-secondary rounded-3 px-3 d-flex align-items-center justify-content-center gap-1" title="Muat Ulang / Reset Filter">
                            <i class="bi bi-arrow-counterclockwise"></i> <span class="d-none d-lg-inline">Muat Ulang</span>
                        </a>
                    </div>
                </div>
            </form>

            <!-- Table Container -->
            <div class="table-responsive rounded-3 border" style="border-color: #e2e8f0 !important;">
                <table class="table table-hover align-middle mb-0">
                    <thead style="background-color: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                        <tr>
                            <th class="ps-3 text-center text-muted fw-bold text-uppercase" style="width: 50px; font-size: 0.75rem; letter-spacing: 0.5px;">NO</th>
                            <th class="text-muted fw-bold text-uppercase" style="width: 18%; font-size: 0.75rem; letter-spacing: 0.5px;">NO REFERENSI</th>
                            <th class="text-muted fw-bold text-uppercase" style="width: 14%; font-size: 0.75rem; letter-spacing: 0.5px;">TANGGAL</th>
                            <th class="text-muted fw-bold text-uppercase" style="width: 25%; font-size: 0.75rem; letter-spacing: 0.5px;">SUPPLIER</th>
                            <th class="text-center text-muted fw-bold text-uppercase" style="width: 12%; font-size: 0.75rem; letter-spacing: 0.5px;">TOTAL QTY</th>
                            <th class="text-end text-muted fw-bold text-uppercase" style="width: 15%; font-size: 0.75rem; letter-spacing: 0.5px;">TOTAL HPP MODAL</th>
                            <th class="text-center text-muted fw-bold text-uppercase" style="width: 12%; font-size: 0.75rem; letter-spacing: 0.5px;">STATUS</th>
                            <th class="text-center pe-3 text-muted fw-bold text-uppercase" style="width: 10%; font-size: 0.75rem; letter-spacing: 0.5px;">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($consignments as $index => $item)
                            <tr>
                                <td class="ps-3 text-center text-muted fw-bold" style="font-size: 0.88rem;">{{ $consignments->firstItem() + $index }}</td>
                                <td>
                                    <a href="{{ route('supplier_consignments.show', $item) }}" class="fw-bolder text-decoration-none text-primary" style="font-size: 0.92rem;">
                                        {{ $item->reference_number }}
                                    </a>
                                </td>
                                <td class="text-dark fw-semibold" style="font-size: 0.88rem;">
                                    {{ $item->consignment_date->format('d/m/Y') }}
                                </td>
                                <td>
                                    <div class="fw-bold text-dark" style="font-size: 0.9rem;">{{ $item->supplier ? $item->supplier->name : '-' }}</div>
                                    <small class="text-muted" style="font-size: 0.78rem;">
                                        <i class="bi bi-telephone me-1"></i>{{ $item->supplier ? ($item->supplier->phone ?: 'No Contact') : '-' }}
                                    </small>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1.5 fw-bold" style="font-size: 0.8rem;">
                                        {{ number_format($item->total_qty_received) }} PCS
                                    </span>
                                </td>
                                <td class="text-end fw-bolder text-dark font-monospace" style="font-size: 0.9rem;">
                                    Rp {{ number_format($item->total_amount_hpp, 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 fw-semibold" style="font-size: 0.78rem;">
                                        <i class="bi bi-check-circle-fill me-1"></i>Selesai
                                    </span>
                                </td>
                                <td class="text-center pe-3">
                                    <div class="btn-group btn-group-sm shadow-sm">
                                        <a href="{{ route('supplier_consignments.print_labels', $item) }}" target="_blank" class="btn btn-outline-success" title="Cetak Barcode Label">
                                            <i class="bi bi-upc-scan"></i>
                                        </a>
                                        <a href="{{ route('supplier_consignments.show', $item) }}" class="btn btn-outline-primary" title="Lihat Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('supplier_consignments.edit', $item) }}" class="btn btn-outline-warning text-dark" title="Edit Transaksi">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('supplier_consignments.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus transaksi {{ $item->reference_number }}? Stok produk master akan dikurangi kembali.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="Hapus Transaksi">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                    Belum ada data penerimaan barang konsinyasi. Silakan klik tombol <b>Transaksi Baru</b>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($consignments->hasPages())
            <div class="card-footer bg-white border-top-0 py-3 px-4">
                {{ $consignments->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
