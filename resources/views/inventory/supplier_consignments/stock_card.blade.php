@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Top Header Banner (Pure Modern Portal Style) -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-4 p-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                        <i class="bi bi-card-checklist fs-2"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-1 text-dark" style="letter-spacing: -0.3px;">Kartu Stok & Persediaan Konsinyasi</h4>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 small fw-semibold">
                                <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i> Laporan Persediaan & Mutasi
                            </span>
                            <span class="text-muted small">Laporan mutasi persediaan barang titipan, sisa stok, setoran, dan profit toko</span>
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" onclick="window.print()" class="btn btn-outline-secondary rounded-pill px-3.5 d-flex align-items-center gap-2">
                        <i class="bi bi-printer"></i> Cetak Laporan
                    </button>
                    <a href="{{ route('supplier_consignments.index') }}" class="btn btn-outline-primary rounded-pill px-3.5 d-flex align-items-center gap-2">
                        <i class="bi bi-box-seam"></i> Penerimaan Barang
                    </a>
                    @if($selectedSupplierId)
                        <a href="{{ route('supplier_consignments.settlement.create', ['supplier_id' => $selectedSupplierId]) }}" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm d-flex align-items-center gap-2">
                            <i class="bi bi-cash-stack"></i> Form Setoran Supplier
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm rounded-3 mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Supplier Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('supplier_consignments.stock_card') }}">
                <div class="row g-3 align-items-center">
                    <div class="col-md-5">
                        <label class="form-label fw-bold small text-muted text-uppercase mb-1" style="font-size: 0.72rem;">Pilih Supplier Penitip Barang:</label>
                        <select name="supplier_id" class="form-select rounded-3" onchange="this.form.submit()">
                            <option value="">-- Pilih Supplier --</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ $selectedSupplierId == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }} ({{ $supplier->phone ?: 'No Contact' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-7 text-md-end pt-2 pt-md-0">
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
    </div>

    @if($selectedSupplierId)
        <!-- KPI Metrics Grid (Portal 4 Card Layout + Summary) -->
        <div class="row g-3 mb-4">
            <!-- Card 1: Total Masuk -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-4 bg-white h-100 p-3" style="border: 1px solid #e2e8f0;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-uppercase fw-bold small text-muted mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                                TOTAL BARANG MASUK
                            </div>
                            <div class="fs-2 fw-bolder text-dark line-height-1">
                                {{ number_format($totalReceivedAll) }}
                            </div>
                            <div class="text-muted small mt-2 d-flex align-items-center gap-1" style="font-size: 0.78rem;">
                                <span class="text-primary font-weight-bold">•</span>
                                <span>Pcs Total Diterima</span>
                            </div>
                        </div>
                        <div class="bg-primary bg-opacity-10 text-primary rounded-4 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px; min-width: 52px;">
                            <i class="bi bi-box-arrow-in-down fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Sisa Stok Gudang -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-4 bg-white h-100 p-3" style="border: 1px solid #e2e8f0;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-uppercase fw-bold small text-muted mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                                SISA STOK GUDANG
                            </div>
                            <div class="fs-2 fw-bolder text-success line-height-1">
                                {{ number_format($totalRemainingAll) }}
                            </div>
                            <div class="text-muted small mt-2 d-flex align-items-center gap-1" style="font-size: 0.78rem;">
                                <span class="text-success font-weight-bold">•</span>
                                <span>Tersedia Siap Jual</span>
                            </div>
                        </div>
                        <div class="bg-success bg-opacity-10 text-success rounded-4 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px; min-width: 52px;">
                            <i class="bi bi-check-circle-fill fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 3: Total Terjual -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-4 bg-white h-100 p-3" style="border: 1px solid #e2e8f0;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-uppercase fw-bold small text-muted mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                                TOTAL TERJUAL
                            </div>
                            <div class="fs-2 fw-bolder text-warning line-height-1" style="color: #d97706 !important;">
                                {{ number_format($totalSoldAll) }}
                            </div>
                            <div class="text-muted small mt-2 d-flex align-items-center gap-1" style="font-size: 0.78rem;">
                                <span class="text-warning font-weight-bold" style="color: #d97706 !important;">•</span>
                                <span>Sudah Disetor: {{ number_format($totalSettledAll) }} Pcs</span>
                            </div>
                        </div>
                        <div class="bg-warning bg-opacity-15 rounded-4 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px; min-width: 52px;">
                            <i class="bi bi-bag-check-fill fs-3" style="color: #ea580c;"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 4: Profit Toko -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-4 bg-white h-100 p-3" style="border: 1px solid #e2e8f0;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-uppercase fw-bold small text-muted mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                                PROFIT TOKO
                            </div>
                            <div class="fs-4 fw-bolder text-danger font-monospace line-height-1">
                                Rp {{ number_format($totalProfitAll, 0, ',', '.') }}
                            </div>
                            <div class="text-muted small mt-2 d-flex align-items-center gap-1" style="font-size: 0.78rem;">
                                <span class="text-danger font-weight-bold">•</span>
                                <span>Margin Keuntungan</span>
                            </div>
                        </div>
                        <div class="bg-danger bg-opacity-10 text-danger rounded-4 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px; min-width: 52px;">
                            <i class="bi bi-graph-up-arrow fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table Kartu Stok & Persediaan per Produk -->
        <div class="card border-0 shadow-sm rounded-4 bg-white">
            <div class="card-header bg-white p-4 border-bottom" style="border-color: #f1f5f9 !important;">
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-box text-primary me-2"></i>MUTASI & STOK PERSEDIAAN PRODUK SUPPLIER {{ $selectedSupplier ? strtoupper($selectedSupplier->name) : '' }}
                </h5>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive rounded-3 border" style="border-color: #e2e8f0 !important;">
                    <table class="table table-hover align-middle mb-0">
                        <thead style="background-color: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                            <tr>
                                <th class="ps-3 text-muted fw-bold text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">SKU & NAMA PRODUK</th>
                                <th class="text-end text-muted fw-bold text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">HARGA TITIP (HPP)</th>
                                <th class="text-end text-muted fw-bold text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">HARGA JUAL</th>
                                <th class="text-center text-muted fw-bold text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">TOTAL MASUK</th>
                                <th class="text-center text-muted fw-bold text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">TERJUAL</th>
                                <th class="text-center text-muted fw-bold text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">STOK GUDANG</th>
                                <th class="text-center text-muted fw-bold text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">SUDAH DISETOR</th>
                                <th class="text-center text-muted fw-bold text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">BELUM DISETOR</th>
                                <th class="text-end text-muted fw-bold text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">HAK SUPPLIER</th>
                                <th class="text-end pe-3 text-muted fw-bold text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">PROFIT TOKO</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportData as $row)
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-bold text-dark" style="font-size: 0.9rem;">{{ $row['name'] }}</div>
                                        <span class="badge bg-light text-dark border font-monospace mt-1" style="font-size: 0.75rem;">SKU: {{ $row['sku'] }}</span>
                                    </td>
                                    <td class="text-end font-monospace text-muted" style="font-size: 0.88rem;">Rp {{ number_format($row['unit_cost'], 0, ',', '.') }}</td>
                                    <td class="text-end font-monospace text-muted" style="font-size: 0.88rem;">Rp {{ number_format($row['unit_selling'], 0, ',', '.') }}</td>
                                    <td class="text-center font-monospace fw-semibold" style="font-size: 0.88rem;">{{ number_format($row['qty_received']) }} {{ $row['unit'] }}</td>
                                    <td class="text-center font-monospace fw-bold text-info" style="font-size: 0.88rem;">{{ number_format($row['qty_sold']) }} {{ $row['unit'] }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-3 py-1.5 font-monospace fw-bold" style="font-size: 0.82rem;">
                                            {{ number_format($row['current_stock']) }} {{ $row['unit'] }}
                                        </span>
                                    </td>
                                    <td class="text-center font-monospace fw-bold text-success" style="font-size: 0.88rem;">{{ number_format($row['qty_settled']) }} {{ $row['unit'] }}</td>
                                    <td class="text-center font-monospace fw-bold text-danger" style="font-size: 0.88rem;">{{ number_format($row['qty_unsettled']) }} {{ $row['unit'] }}</td>
                                    <td class="text-end fw-semibold text-dark font-monospace" style="font-size: 0.88rem;">Rp {{ number_format($row['nominal_paid'], 0, ',', '.') }}</td>
                                    <td class="text-end pe-3 fw-bold text-success font-monospace" style="font-size: 0.88rem;">+Rp {{ number_format($row['profit_total'], 0, ',', '.') }}</td>
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
            </div>
        </div>
    @else
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center text-muted bg-white">
            <i class="bi bi-building fs-1 d-block mb-3 text-secondary"></i>
            <h5>Silakan Pilih Supplier di atas untuk Melihat Kartu Stok & Mutasi</h5>
        </div>
    @endif
</div>
@endsection
