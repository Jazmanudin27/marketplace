@extends('layouts.app')

@section('content')
<style>
    /* Custom Portal Reference Styling matching sekolah.aspartech.com */
    .portal-stat-card {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 18px rgba(0, 0, 0, 0.03);
        padding: 1.35rem 1.5rem;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .portal-stat-label {
        font-size: 0.76rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        color: #64748b;
        text-transform: uppercase;
        margin-bottom: 0.35rem;
    }

    .portal-stat-value {
        font-size: 2.1rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.1;
        margin-bottom: 0.35rem;
    }

    .portal-stat-sub {
        font-size: 0.82rem;
        color: #64748b;
        font-weight: 500;
    }

    /* Icon Box Styles - Solid Colored Icon Containers matching Reference Image */
    .icon-box-blue {
        width: 54px;
        height: 54px;
        min-width: 54px;
        border-radius: 14px;
        background: #0284c7; /* Vibrant Blue */
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6rem;
        box-shadow: 0 6px 14px rgba(2, 132, 199, 0.28);
    }

    .icon-box-green {
        width: 54px;
        height: 54px;
        min-width: 54px;
        border-radius: 14px;
        background: #10b981; /* Vibrant Emerald Green */
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6rem;
        box-shadow: 0 6px 14px rgba(16, 185, 129, 0.28);
    }

    .icon-box-orange {
        width: 54px;
        height: 54px;
        min-width: 54px;
        border-radius: 14px;
        background: #f59e0b; /* Vibrant Orange */
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6rem;
        box-shadow: 0 6px 14px rgba(245, 158, 11, 0.28);
    }

    .icon-box-red {
        width: 54px;
        height: 54px;
        min-width: 54px;
        border-radius: 14px;
        background: #ef4444; /* Vibrant Red */
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6rem;
        box-shadow: 0 6px 14px rgba(239, 68, 68, 0.28);
    }

    /* Main Section Card */
    .portal-main-card {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        padding: 1.75rem;
    }

    .portal-section-title {
        font-size: 1.15rem;
        font-weight: 700;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }

    .portal-status-tag {
        font-size: 0.85rem;
        font-weight: 600;
        color: #d97706; /* Warning/Orange tone in screenshot */
    }

    /* Action Buttons top right */
    .btn-outline-portal-green {
        border: 1.5px solid #10b981;
        color: #10b981;
        background: #ffffff;
        font-weight: 600;
        border-radius: 10px;
        padding: 0.5rem 1.15rem;
        font-size: 0.875rem;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .btn-outline-portal-green:hover {
        background: #10b981;
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
    }

    .btn-portal-blue {
        background: #0284c7;
        border: 1.5px solid #0284c7;
        color: #ffffff;
        font-weight: 600;
        border-radius: 10px;
        padding: 0.5rem 1.25rem;
        font-size: 0.875rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        text-decoration: none;
        box-shadow: 0 4px 14px rgba(2, 132, 199, 0.25);
        transition: all 0.2s ease;
    }

    .btn-portal-blue:hover {
        background: #0369a1;
        border-color: #0369a1;
        color: #ffffff;
    }

    /* Filter Box Container */
    .portal-filter-box {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 12px;
        padding: 1.1rem 1.25rem;
        margin-top: 1.25rem;
        margin-bottom: 1.5rem;
    }

    .portal-form-label {
        font-size: 0.75rem;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.45rem;
        display: block;
    }

    .portal-input {
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 0.45rem 0.75rem;
        font-size: 0.875rem;
        color: #0f172a;
        background-color: #ffffff;
        height: 40px;
        width: 100%;
        transition: border-color 0.2s;
    }

    .portal-input:focus {
        border-color: #0284c7;
        outline: none;
        box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
    }

    .btn-portal-refresh {
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #475569;
        font-weight: 600;
        border-radius: 8px;
        height: 40px;
        padding: 0 1rem;
        font-size: 0.85rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        text-decoration: none;
        transition: all 0.2s ease;
        white-space: nowrap;
    }

    .btn-portal-refresh:hover {
        background: #f1f5f9;
        color: #0f172a;
        border-color: #94a3b8;
    }

    /* Table Design */
    .portal-table-wrapper {
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        overflow: hidden;
    }

    .portal-table {
        margin-bottom: 0;
        width: 100%;
    }

    .portal-table thead th {
        background-color: #f1f5f9;
        color: #334155;
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 0.9rem 1rem;
        border-bottom: 1px solid #cbd5e1;
        vertical-align: middle;
    }

    .portal-table tbody td {
        padding: 0.95rem 1rem;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: middle;
        font-size: 0.875rem;
        color: #1e293b;
    }

    .portal-table tbody tr:hover {
        background-color: #f8fafc;
    }

    .portal-ref-link {
        color: #0284c7;
        font-weight: 700;
        text-decoration: none;
        font-size: 0.9rem;
    }

    .portal-ref-link:hover {
        color: #0369a1;
        text-decoration: underline;
    }
</style>

<div class="container-fluid py-4">
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
                        <span class="text-success fw-bold">•</span> {{ ($totalQtyReceived ?? 0) > 0 ? number_format((($totalSisaStok ?? 0) / $totalQtyReceived) * 100, 0) : 0 }}% Stok Gudang
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
                            <input type="text" name="search" class="portal-input ps-5" placeholder="No. Referensi / Catatan..." value="{{ request('search') }}">
                            <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="portal-form-label">Supplier (Pemilik Barang)</label>
                        <select name="supplier_id" class="portal-input">
                            <option value="">-- Semua Supplier --</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
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
                        <a href="{{ route('supplier_consignments.index') }}" class="btn-portal-refresh" title="Muat Ulang / Reset Filter">
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
                            <td class="text-center text-muted fw-semibold">{{ $consignments->firstItem() + $index }}</td>
                            <td>
                                <a href="{{ route('supplier_consignments.show', $item) }}" class="portal-ref-link">
                                    {{ $item->reference_number }}
                                </a>
                            </td>
                            <td class="text-dark fw-semibold">
                                {{ $item->consignment_date->format('d/m/Y') }}
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $item->supplier ? $item->supplier->name : '-' }}</div>
                                <small class="text-muted">
                                    <i class="bi bi-telephone me-1"></i>{{ $item->supplier ? ($item->supplier->phone ?: 'No Contact') : '-' }}
                                </small>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1.5 fw-bold">
                                    {{ number_format($item->total_qty_received) }} PCS
                                </span>
                            </td>
                            <td class="text-end fw-bold text-dark font-monospace">
                                Rp {{ number_format($item->total_amount_hpp, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1.5 fw-semibold">
                                    <i class="bi bi-check-circle-fill me-1"></i>Selesai
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
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
                                <div class="py-3">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                    <span>Belum ada data penerimaan barang konsinyasi. Silakan klik tombol <b>Transaksi Baru</b>.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($consignments->hasPages())
            <div class="mt-4">
                {{ $consignments->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
