@extends('layouts.app')

@section('content')
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
