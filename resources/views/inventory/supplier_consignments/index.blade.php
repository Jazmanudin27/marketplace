@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
        <!-- Notifications -->
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm rounded-3 mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show shadow-sm rounded-3 mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Top Row: 4 Summary Cards (Exact Replica of sekolah.aspartech.com) -->
        <div class="row g-3 mb-4">
            <!-- Card 1: Blue Theme (Barang Terdaftar) -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="portal-stat-card">
                    <div>
                        <div class="portal-stat-label">BARANG TERDAFTAR (QTY)</div>
                        <div class="portal-stat-value">{{ number_format($totalQtyReceived ?? 0) }}</div>
                        <div class="portal-stat-sub">
                            <span class="text-primary fw-bold">•</span> {{ $totalConsignments ?? 0 }} Transaksi Titipan
                        </div>
                    </div>
                    <div class="icon-box-blue">
                        <i class="bi bi-journal-bookmark-fill"></i>
                    </div>
                </div>
            </div>

            <!-- Card 2: Green Theme (Stok Tersedia) -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="portal-stat-card">
                    <div>
                        <div class="portal-stat-label">STOK TERSEDIA (SISA)</div>
                        <div class="portal-stat-value">{{ number_format($totalSisaStok ?? 0) }}</div>
                        <div class="portal-stat-sub">
                            <span class="text-success fw-bold">•</span>
                            {{ ($totalQtyReceived ?? 0) > 0 ? number_format((($totalSisaStok ?? 0) / $totalQtyReceived) * 100, 0) : 0 }}%
                            Stok Gudang
                        </div>
                    </div>
                    <div class="icon-box-green">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                </div>
            </div>

            <!-- Card 3: Orange Theme (Terjual / Dikemas) -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="portal-stat-card">
                    <div>
                        <div class="portal-stat-label">TERJUAL / DIKEMAS</div>
                        <div class="portal-stat-value" style="color: #d97706;">{{ number_format($totalQtySold ?? 0) }}</div>
                        <div class="portal-stat-sub">
                            <span style="color: #d97706;" class="fw-bold">•</span> Menunggu Setoran
                        </div>
                    </div>
                    <div class="icon-box-orange">
                        <i class="bi bi-bag-check-fill"></i>
                    </div>
                </div>
            </div>

            <!-- Card 4: Red Theme (Total HPP Modal) -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="portal-stat-card">
                    <div>
                        <div class="portal-stat-label">TOTAL HPP MODAL</div>
                        <div class="portal-stat-value text-danger" style="font-size: 1.6rem; font-family: monospace;">
                            Rp {{ number_format($totalAmountHpp ?? 0, 0, ',', '.') }}
                        </div>
                        <div class="portal-stat-sub">
                            <span class="text-danger fw-bold">•</span> Nilai Modal Titipan
                        </div>
                    </div>
                    <div class="icon-box-red">
                        <i class="bi bi-exclamation-octagon-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Section Card Container (Exact Replica of sekolah.aspartech.com) -->
        <div class="portal-main-card">
            <!-- Top Section Header Row -->
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <div class="portal-section-title">
                        <i class="bi bi-journal-bookmark-fill text-primary fs-4"></i>
                        <span>Pencatatan Penerimaan Barang Titipan per Supplier</span>
                    </div>
                    <div class="mt-1 ms-1">
                        <span class="text-muted small">Status: </span>
                        <span class="portal-status-tag">Belum Ada Catatan / Baru</span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('supplier_consignments.stock_card') }}" class="btn-outline-portal-green">
                        <i class="bi bi-check-circle-fill"></i> Kartu Stok Supplier
                    </a>
                    <a href="{{ route('supplier_consignments.create') }}" class="btn-portal-blue">
                        <i class="bi bi-plus-lg"></i> Transaksi Baru
                    </a>
                </div>
            </div>

            <!-- Filter Bar Form Box -->
            <div class="portal-filter-box">
                <form method="GET" action="{{ route('supplier_consignments.index') }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="portal-form-label">Cari Transaksi / Ref</label>
                            <div class="position-relative">
                                <input type="text" name="search" class="portal-input ps-5"
                                    placeholder="No. Referensi / Catatan..." value="{{ request('search') }}">
                                <i
                                    class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="portal-form-label">Supplier (Pemilik Barang)</label>
                            <select name="supplier_id" class="portal-input">
                                <option value="">-- Semua Supplier --</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}"
                                        {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="portal-form-label">Tanggal Mulai</label>
                            <input type="date" name="date_from" class="portal-input" value="{{ request('date_from') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="portal-form-label">Tanggal Akhir</label>
                            <input type="date" name="date_to" class="portal-input" value="{{ request('date_to') }}">
                        </div>
                        <div class="col-md-2 d-flex gap-2">
                            <button type="submit" class="btn-portal-blue w-100">
                                <i class="bi bi-filter"></i> Filter
                            </button>
                            <a href="{{ route('supplier_consignments.index') }}" class="btn-portal-refresh"
                                title="Muat Ulang / Reset Filter">
                                <i class="bi bi-arrow-counterclockwise"></i> <span>Muat Ulang</span>
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Data Table Wrapper -->
            <div class="portal-table-wrapper">
                <table class="table portal-table align-middle">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 50px;">NO</th>
                            <th style="width: 18%;">NO REFERENSI</th>
                            <th style="width: 14%;">TANGGAL</th>
                            <th style="width: 25%;">SUPPLIER</th>
                            <th class="text-center" style="width: 12%;">TOTAL QTY</th>
                            <th class="text-end" style="width: 15%;">TOTAL HPP MODAL</th>
                            <th class="text-center" style="width: 12%;">STATUS</th>
                            <th class="text-center" style="width: 10%;">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($consignments as $index => $item)
                            <tr>
                                <td class="text-center text-muted fw-semibold">{{ $consignments->firstItem() + $index }}
                                </td>
                                <td>
                                    <a href="{{ route('supplier_consignments.show', $item) }}" class="portal-ref-link">
                                        {{ $item->reference_number }}
                                    </a>
                                </td>
                                <td class="text-dark fw-semibold">
                                    {{ $item->consignment_date->format('d/m/Y') }}
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $item->supplier ? $item->supplier->name : '-' }}
                                    </div>
                                    <small class="text-muted">
                                        <i
                                            class="bi bi-telephone me-1"></i>{{ $item->supplier ? ($item->supplier->phone ?: 'No Contact') : '-' }}
                                    </small>
                                </td>
                                <td class="text-center">
                                    <span
                                        class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1.5 fw-bold">
                                        {{ number_format($item->total_qty_received) }} PCS
                                    </span>
                                </td>
                                <td class="text-end fw-bold text-dark font-monospace">
                                    Rp {{ number_format($item->total_amount_hpp, 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    <span
                                        class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1.5 fw-semibold">
                                        <i class="bi bi-check-circle-fill me-1"></i>Selesai
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('supplier_consignments.print_labels', $item) }}"
                                            target="_blank" class="btn btn-outline-success" title="Cetak Barcode Label">
                                            <i class="bi bi-upc-scan"></i>
                                        </a>
                                        <a href="{{ route('supplier_consignments.show', $item) }}"
                                            class="btn btn-outline-primary" title="Lihat Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('supplier_consignments.edit', $item) }}"
                                            class="btn btn-outline-warning text-dark" title="Edit Transaksi">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('supplier_consignments.destroy', $item) }}" method="POST"
                                            class="d-inline"
                                            onsubmit="return confirm('Apakah Anda yakin ingin menghapus transaksi {{ $item->reference_number }}? Stok produk master akan dikurangi kembali.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger"
                                                title="Hapus Transaksi">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <div class="py-3">
                                        <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                        <span>Belum ada data penerimaan barang konsinyasi. Silakan klik tombol <b>Transaksi
                                                Baru</b>.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($consignments->hasPages())
                <div class="mt-4">
                    {{ $consignments->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
