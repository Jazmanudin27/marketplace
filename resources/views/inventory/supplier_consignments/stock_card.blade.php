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

    /* Icon Box Styles - Solid Colored Icon Containers */
    .icon-box-blue {
        width: 54px;
        height: 54px;
        min-width: 54px;
        border-radius: 14px;
        background: #0284c7;
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
        background: #10b981;
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
        background: #f59e0b;
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
        background: #ef4444;
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
        color: #d97706;
    }

    /* Action Buttons */
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
    }

    .portal-input:focus {
        border-color: #0284c7;
        outline: none;
        box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
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
</style>

<div class="container-fluid py-4">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm rounded-3 mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($selectedSupplierId)
        <!-- Top Row: 4 Summary Cards (Exact Replica of sekolah.aspartech.com) -->
        <div class="row g-3 mb-4">
            <!-- Card 1: Blue Theme -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="portal-stat-card">
                    <div>
                        <div class="portal-stat-label">TOTAL BARANG MASUK</div>
                        <div class="portal-stat-value">{{ number_format($totalReceivedAll) }}</div>
                        <div class="portal-stat-sub">
                            <span class="text-primary fw-bold">•</span> Pcs Total Diterima
                        </div>
                    </div>
                    <div class="icon-box-blue">
                        <i class="bi bi-box-arrow-in-down"></i>
                    </div>
                </div>
            </div>

            <!-- Card 2: Green Theme -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="portal-stat-card">
                    <div>
                        <div class="portal-stat-label">SISA STOK GUDANG</div>
                        <div class="portal-stat-value text-success">{{ number_format($totalRemainingAll) }}</div>
                        <div class="portal-stat-sub">
                            <span class="text-success fw-bold">•</span> Tersedia Siap Jual
                        </div>
                    </div>
                    <div class="icon-box-green">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                </div>
            </div>

            <!-- Card 3: Orange Theme -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="portal-stat-card">
                    <div>
                        <div class="portal-stat-label">TOTAL TERJUAL</div>
                        <div class="portal-stat-value" style="color: #d97706;">{{ number_format($totalSoldAll) }}</div>
                        <div class="portal-stat-sub">
                            <span style="color: #d97706;" class="fw-bold">•</span> Disetor: {{ number_format($totalSettledAll) }} Pcs
                        </div>
                    </div>
                    <div class="icon-box-orange">
                        <i class="bi bi-bag-check-fill"></i>
                    </div>
                </div>
            </div>

            <!-- Card 4: Red Theme -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="portal-stat-card">
                    <div>
                        <div class="portal-stat-label">PROFIT TOKO</div>
                        <div class="portal-stat-value text-danger" style="font-size: 1.6rem; font-family: monospace;">
                            Rp {{ number_format($totalProfitAll, 0, ',', '.') }}
                        </div>
                        <div class="portal-stat-sub">
                            <span class="text-danger fw-bold">•</span> Margin Keuntungan
                        </div>
                    </div>
                    <div class="icon-box-red">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Main Section Card Container -->
    <div class="portal-main-card">
        <!-- Top Section Header Row -->
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <div class="portal-section-title">
                    <i class="bi bi-card-checklist text-primary fs-4"></i>
                    <span>Kartu Stok & Mutasi Persediaan Konsinyasi</span>
                </div>
                <div class="mt-1 ms-1">
                    <span class="text-muted small">Status: </span>
                    <span class="portal-status-tag">Laporan Persediaan Gudang Master</span>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" onclick="window.print()" class="btn-outline-portal-green">
                    <i class="bi bi-printer"></i> Cetak Laporan
                </button>
                <a href="{{ route('supplier_consignments.index') }}" class="btn-portal-blue">
                    <i class="bi bi-box-seam"></i> Penerimaan Barang
                </a>
                @if($selectedSupplierId)
                    <a href="{{ route('supplier_consignments.settlement.create', ['supplier_id' => $selectedSupplierId]) }}" class="btn-outline-portal-green" style="border-color: #10b981; background: #10b981; color: white;">
                        <i class="bi bi-cash-stack"></i> Form Setoran Supplier
                    </a>
                @endif
            </div>
        </div>

        <!-- Supplier Filter Box Container -->
        <div class="portal-filter-box">
            <form method="GET" action="{{ route('supplier_consignments.stock_card') }}">
                <div class="row g-3 align-items-center">
                    <div class="col-md-6">
                        <label class="portal-form-label">Pilih Supplier Penitip Barang:</label>
                        <select name="supplier_id" class="portal-input" onchange="this.form.submit()">
                            <option value="">-- Pilih Supplier --</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ $selectedSupplierId == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }} ({{ $supplier->phone ?: 'No Contact' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 text-md-end pt-2 pt-md-0">
                        @if($selectedSupplier)
                            <div class="d-inline-flex align-items-center gap-2">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6 px-3 py-2 rounded-pill">
                                    <i class="bi bi-shop me-1"></i> {{ $selectedSupplier->name }}
                                </span>
                                @if($selectedSupplier->phone)
                                    <span class="badge bg-light text-muted border fs-6 px-3 py-2 rounded-pill">
                                        <i class="bi bi-telephone me-1"></i> {{ $selectedSupplier->phone }}
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        @if($selectedSupplierId)
            <!-- Table Mutasi & Persediaan -->
            <div class="portal-table-wrapper">
                <table class="table portal-table align-middle">
                    <thead>
                        <tr>
                            <th>SKU & NAMA PRODUK</th>
                            <th class="text-end">HARGA TITIP (HPP)</th>
                            <th class="text-end">HARGA JUAL</th>
                            <th class="text-center">TOTAL MASUK</th>
                            <th class="text-center">TERJUAL</th>
                            <th class="text-center">STOK GUDANG</th>
                            <th class="text-center">SUDAH DISETOR</th>
                            <th class="text-center">BELUM DISETOR</th>
                            <th class="text-end">HAK SUPPLIER</th>
                            <th class="text-end pe-3">PROFIT TOKO</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reportData as $row)
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark">{{ $row['name'] }}</div>
                                    <span class="badge bg-light text-dark border font-monospace mt-1">SKU: {{ $row['sku'] }}</span>
                                </td>
                                <td class="text-end font-monospace text-muted">Rp {{ number_format($row['unit_cost'], 0, ',', '.') }}</td>
                                <td class="text-end font-monospace text-muted">Rp {{ number_format($row['unit_selling'], 0, ',', '.') }}</td>
                                <td class="text-center font-monospace fw-semibold">{{ number_format($row['qty_received']) }} {{ $row['unit'] }}</td>
                                <td class="text-center font-monospace fw-bold text-info">{{ number_format($row['qty_sold']) }} {{ $row['unit'] }}</td>
                                <td class="text-center">
                                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-3 py-1.5 font-monospace fw-bold">
                                        {{ number_format($row['current_stock']) }} {{ $row['unit'] }}
                                    </span>
                                </td>
                                <td class="text-center font-monospace fw-bold text-success">{{ number_format($row['qty_settled']) }} {{ $row['unit'] }}</td>
                                <td class="text-center font-monospace fw-bold text-danger">{{ number_format($row['qty_unsettled']) }} {{ $row['unit'] }}</td>
                                <td class="text-end fw-semibold text-dark font-monospace">Rp {{ number_format($row['nominal_paid'], 0, ',', '.') }}</td>
                                <td class="text-end pe-3 fw-bold text-success font-monospace">+Rp {{ number_format($row['profit_total'], 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                    Belum ada data barang konsinyasi untuk supplier ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-5 text-muted">
                <i class="bi bi-building fs-1 d-block mb-2 text-secondary"></i>
                <h6>Silakan Pilih Supplier di atas untuk Melihat Kartu Stok & Mutasi</h6>
            </div>
        @endif
    </div>
</div>
@endsection
