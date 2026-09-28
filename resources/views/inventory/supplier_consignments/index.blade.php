@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <!-- Header Card (Compact Portal Style) -->
    <div class="card border-0 shadow-sm rounded-3 mb-3 bg-white">
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2.5">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; min-width: 44px;">
                        <i class="bi bi-box-seam-fill fs-4"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0.5 text-dark" style="letter-spacing: -0.2px;">Penerimaan Barang Konsinyasi (Titipan)</h5>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0.5 small fw-semibold" style="font-size: 0.7rem;">
                                <i class="bi bi-circle-fill me-1" style="font-size: 0.45rem;"></i> Status: Transaksi Aktif / Realtime
                            </span>
                            <span class="text-muted small" style="font-size: 0.78rem;">Kelola penerimaan barang titipan supplier & persediaan gudang master</span>
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('supplier_consignments.stock_card') }}" class="btn btn-outline-success btn-sm rounded-pill px-3 fw-bold d-flex align-items-center gap-1.5">
                        <i class="bi bi-card-checklist"></i> Kartu Stok Supplier
                    </a>
                    <a href="{{ route('supplier_consignments.create') }}" class="btn btn-primary btn-sm rounded-pill px-3 fw-bold d-flex align-items-center gap-1.5 shadow-sm">
                        <i class="bi bi-plus-circle-fill"></i> Transaksi Baru
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Notifications -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm rounded-3 mb-3 py-2 px-3 small" role="alert">
            <i class="bi bi-check-circle-fill me-1.5"></i>{{ session('success') }}
            <button type="button" class="btn-close py-2.5" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm rounded-3 mb-3 py-2 px-3 small" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-1.5"></i>{{ session('error') }}
            <button type="button" class="btn-close py-2.5" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- 4 Compact Summary Metric Cards -->
    <div class="row g-2.5 mb-3">
        <!-- Card 1: Barang Terdaftar / Total Qty Terima (Blue Theme) -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100 p-2.5" style="border: 1px solid #e2e8f0;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-uppercase fw-bold small text-muted mb-0.5" style="font-size: 0.68rem; letter-spacing: 0.4px;">
                            BARANG TERDAFTAR (QTY)
                        </div>
                        <div class="fs-4 fw-bolder text-dark line-height-1" style="font-size: 1.35rem;">
                            {{ number_format($totalQtyReceived ?? 0) }}
                        </div>
                        <div class="text-muted small mt-1 d-flex align-items-center gap-1" style="font-size: 0.72rem;">
                            <span class="text-primary font-weight-bold">•</span>
                            <span>{{ $totalConsignments ?? 0 }} Transaksi Titipan</span>
                        </div>
                    </div>
                    <div class="bg-primary bg-opacity-10 text-primary rounded-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; min-width: 40px;">
                        <i class="bi bi-journal-bookmark-fill fs-5"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Stok Tersedia / Hadir (Green Theme) -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100 p-2.5" style="border: 1px solid #e2e8f0;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-uppercase fw-bold small text-muted mb-0.5" style="font-size: 0.68rem; letter-spacing: 0.4px;">
                            STOK TERSEDIA (SISA)
                        </div>
                        <div class="fs-4 fw-bolder text-success line-height-1" style="font-size: 1.35rem;">
                            {{ number_format($totalSisaStok ?? 0) }}
                        </div>
                        <div class="text-muted small mt-1 d-flex align-items-center gap-1" style="font-size: 0.72rem;">
                            <span class="text-success font-weight-bold">•</span>
                            <span>{{ ($totalQtyReceived ?? 0) > 0 ? number_format((($totalSisaStok ?? 0) / $totalQtyReceived) * 100, 0) : 0 }}% Stok Gudang</span>
                        </div>
                    </div>
                    <div class="bg-success bg-opacity-10 text-success rounded-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; min-width: 40px;">
                        <i class="bi bi-check-circle-fill fs-5"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: Terjual / Sakit & Izin (Orange Theme) -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100 p-2.5" style="border: 1px solid #e2e8f0;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-uppercase fw-bold small text-muted mb-0.5" style="font-size: 0.68rem; letter-spacing: 0.4px;">
                            TERJUAL / DIKEMAS
                        </div>
                        <div class="fs-4 fw-bolder text-warning line-height-1" style="font-size: 1.35rem; color: #d97706 !important;">
                            {{ number_format($totalQtySold ?? 0) }}
                        </div>
                        <div class="text-muted small mt-1 d-flex align-items-center gap-1" style="font-size: 0.72rem;">
                            <span class="text-warning font-weight-bold" style="color: #d97706 !important;">•</span>
                            <span>Menunggu Setoran</span>
                        </div>
                    </div>
                    <div class="bg-warning bg-opacity-15 rounded-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; min-width: 40px;">
                        <i class="bi bi-bag-check-fill fs-5" style="color: #ea580c;"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 4: Total HPP Modal / Alpa (Red Theme) -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100 p-2.5" style="border: 1px solid #e2e8f0;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-uppercase fw-bold small text-muted mb-0.5" style="font-size: 0.68rem; letter-spacing: 0.4px;">
                            TOTAL HPP MODAL
                        </div>
                        <div class="fs-5 fw-bolder text-danger font-monospace line-height-1" style="font-size: 1.15rem;">
                            Rp {{ number_format($totalAmountHpp ?? 0, 0, ',', '.') }}
                        </div>
                        <div class="text-muted small mt-1 d-flex align-items-center gap-1" style="font-size: 0.72rem;">
                            <span class="text-danger font-weight-bold">•</span>
                            <span>Nilai Modal Titipan</span>
                        </div>
                    </div>
                    <div class="bg-danger bg-opacity-10 text-danger rounded-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; min-width: 40px;">
                        <i class="bi bi-exclamation-octagon-fill fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Card Container (Title + Filter + Table) -->
    <div class="card border-0 shadow-sm rounded-3 bg-white">
        <!-- Section Header Bar -->
        <div class="card-header bg-white p-3 border-bottom" style="border-color: #f1f5f9 !important;">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-journal-text fs-5 text-primary"></i>
                    <h6 class="fw-bold mb-0 text-dark" style="font-size: 0.95rem;">Pencatatan Penerimaan Barang Titipan per Supplier</h6>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <span class="badge bg-light text-muted border px-2.5 py-1 rounded-pill small" style="font-size: 0.72rem;">
                        Status: <strong class="text-primary">Data Terverifikasi</strong>
                    </span>
                </div>
            </div>
        </div>

        <div class="card-body p-3">
            <!-- Filter Bar Form (Compact form-control-sm & form-select-sm & btn-sm) -->
            <form method="GET" action="{{ route('supplier_consignments.index') }}" class="mb-3">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-muted text-uppercase mb-1" style="font-size: 0.68rem;">Cari Transaksi / Ref</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control form-control-sm border-start-0" placeholder="No. Referensi / Catatan..." value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-muted text-uppercase mb-1" style="font-size: 0.68rem;">Supplier (Pemilik Barang)</label>
                        <select name="supplier_id" class="form-select form-select-sm">
                            <option value="">-- Semua Supplier --</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold small text-muted text-uppercase mb-1" style="font-size: 0.68rem;">Tanggal Mulai</label>
                        <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold small text-muted text-uppercase mb-1" style="font-size: 0.68rem;">Tanggal Akhir</label>
                        <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-2 d-flex gap-1.5">
                        <button type="submit" class="btn btn-primary btn-sm w-100 rounded-2 fw-bold d-flex align-items-center justify-content-center gap-1">
                            <i class="bi bi-filter"></i> Filter
                        </button>
                        <a href="{{ route('supplier_consignments.index') }}" class="btn btn-outline-secondary btn-sm rounded-2 px-2.5 d-flex align-items-center justify-content-center gap-1" title="Muat Ulang / Reset Filter">
                            <i class="bi bi-arrow-counterclockwise"></i> <span class="d-none d-lg-inline small">Muat Ulang</span>
                        </a>
                    </div>
                </div>
            </form>

            <!-- Compact Data Table (table-sm) -->
            <div class="table-responsive rounded-2 border" style="border-color: #e2e8f0 !important;">
                <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.82rem;">
                    <thead style="background-color: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                        <tr>
                            <th class="ps-2.5 text-center text-muted fw-bold text-uppercase py-2" style="width: 45px; font-size: 0.7rem; letter-spacing: 0.4px;">NO</th>
                            <th class="text-muted fw-bold text-uppercase py-2" style="width: 18%; font-size: 0.7rem; letter-spacing: 0.4px;">NO REFERENSI</th>
                            <th class="text-muted fw-bold text-uppercase py-2" style="width: 13%; font-size: 0.7rem; letter-spacing: 0.4px;">TANGGAL</th>
                            <th class="text-muted fw-bold text-uppercase py-2" style="width: 26%; font-size: 0.7rem; letter-spacing: 0.4px;">SUPPLIER</th>
                            <th class="text-center text-muted fw-bold text-uppercase py-2" style="width: 12%; font-size: 0.7rem; letter-spacing: 0.4px;">TOTAL QTY</th>
                            <th class="text-end text-muted fw-bold text-uppercase py-2" style="width: 15%; font-size: 0.7rem; letter-spacing: 0.4px;">TOTAL HPP MODAL</th>
                            <th class="text-center text-muted fw-bold text-uppercase py-2" style="width: 11%; font-size: 0.7rem; letter-spacing: 0.4px;">STATUS</th>
                            <th class="text-center pe-2.5 text-muted fw-bold text-uppercase py-2" style="width: 10%; font-size: 0.7rem; letter-spacing: 0.4px;">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($consignments as $index => $item)
                            <tr>
                                <td class="ps-2.5 text-center text-muted fw-bold py-1.5" style="font-size: 0.8rem;">{{ $consignments->firstItem() + $index }}</td>
                                <td class="py-1.5">
                                    <a href="{{ route('supplier_consignments.show', $item) }}" class="fw-bold text-decoration-none text-primary" style="font-size: 0.83rem;">
                                        {{ $item->reference_number }}
                                    </a>
                                </td>
                                <td class="text-dark fw-semibold py-1.5" style="font-size: 0.8rem;">
                                    {{ $item->consignment_date->format('d/m/Y') }}
                                </td>
                                <td class="py-1.5">
                                    <div class="fw-bold text-dark" style="font-size: 0.82rem;">{{ $item->supplier ? $item->supplier->name : '-' }}</div>
                                    <small class="text-muted" style="font-size: 0.72rem;">
                                        <i class="bi bi-telephone me-1"></i>{{ $item->supplier ? ($item->supplier->phone ?: 'No Contact') : '-' }}
                                    </small>
                                </td>
                                <td class="text-center py-1.5">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-0.5 fw-bold" style="font-size: 0.74rem;">
                                        {{ number_format($item->total_qty_received) }} PCS
                                    </span>
                                </td>
                                <td class="text-end fw-bold text-dark font-monospace py-1.5" style="font-size: 0.82rem;">
                                    Rp {{ number_format($item->total_amount_hpp, 0, ',', '.') }}
                                </td>
                                <td class="text-center py-1.5">
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0.5 fw-semibold" style="font-size: 0.72rem;">
                                        <i class="bi bi-check-circle-fill me-1"></i>Selesai
                                    </span>
                                </td>
                                <td class="text-center pe-2.5 py-1.5">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('supplier_consignments.print_labels', $item) }}" target="_blank" class="btn btn-outline-success btn-sm py-0 px-1.5" title="Cetak Barcode Label">
                                            <i class="bi bi-upc-scan" style="font-size: 0.78rem;"></i>
                                        </a>
                                        <a href="{{ route('supplier_consignments.show', $item) }}" class="btn btn-outline-primary btn-sm py-0 px-1.5" title="Lihat Detail">
                                            <i class="bi bi-eye" style="font-size: 0.78rem;"></i>
                                        </a>
                                        <a href="{{ route('supplier_consignments.edit', $item) }}" class="btn btn-outline-warning btn-sm text-dark py-0 px-1.5" title="Edit Transaksi">
                                            <i class="bi bi-pencil" style="font-size: 0.78rem;"></i>
                                        </a>
                                        <form action="{{ route('supplier_consignments.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus transaksi {{ $item->reference_number }}? Stok produk master akan dikurangi kembali.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-1.5" title="Hapus Transaksi">
                                                <i class="bi bi-trash" style="font-size: 0.78rem;"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted small">
                                    <i class="bi bi-inbox fs-3 d-block mb-1 text-secondary"></i>
                                    Belum ada data penerimaan barang konsinyasi. Silakan klik tombol <b>Transaksi Baru</b>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($consignments->hasPages())
            <div class="card-footer bg-white border-top-0 py-2 px-3 small">
                {{ $consignments->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
