@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <!-- Top Header Banner (Compact Portal Style) -->
    <div class="card border-0 shadow-sm rounded-3 mb-3 bg-white">
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2.5">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; min-width: 44px;">
                        <i class="bi bi-card-checklist fs-4"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0.5 text-dark" style="letter-spacing: -0.2px;">Kartu Stok & Persediaan Konsinyasi</h5>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0.5 small fw-semibold" style="font-size: 0.7rem;">
                                <i class="bi bi-circle-fill me-1" style="font-size: 0.45rem;"></i> Laporan Persediaan & Mutasi
                            </span>
                            <span class="text-muted small" style="font-size: 0.78rem;">Laporan mutasi persediaan barang titipan, sisa stok, setoran, dan profit toko</span>
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-1.5">
                    <button type="button" onclick="window.print()" class="btn btn-outline-secondary btn-sm rounded-pill px-3 d-flex align-items-center gap-1">
                        <i class="bi bi-printer"></i> Cetak
                    </button>
                    <a href="{{ route('supplier_consignments.index') }}" class="btn btn-outline-primary btn-sm rounded-pill px-3 d-flex align-items-center gap-1">
                        <i class="bi bi-box-seam"></i> Penerimaan
                    </a>
                    @if($selectedSupplierId)
                        <a href="{{ route('supplier_consignments.settlement.create', ['supplier_id' => $selectedSupplierId]) }}" class="btn btn-success btn-sm rounded-pill px-3 fw-bold shadow-sm d-flex align-items-center gap-1">
                            <i class="bi bi-cash-stack"></i> Form Setoran
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm rounded-3 mb-3 py-2 px-3 small" role="alert">
            <i class="bi bi-check-circle-fill me-1.5"></i>{{ session('success') }}
            <button type="button" class="btn-close py-2.5" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Supplier Filter Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-3 bg-white">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('supplier_consignments.stock_card') }}">
                <div class="row g-2 align-items-center">
                    <div class="col-md-5">
                        <label class="form-label fw-bold small text-muted text-uppercase mb-1" style="font-size: 0.68rem;">Pilih Supplier Penitip Barang:</label>
                        <select name="supplier_id" class="form-select form-select-sm rounded-2" onchange="this.form.submit()">
                            <option value="">-- Pilih Supplier --</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ $selectedSupplierId == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }} ({{ $supplier->phone ?: 'No Contact' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-7 text-md-end pt-1 pt-md-0">
                        @if($selectedSupplier)
                            <div class="d-inline-flex align-items-center gap-1.5">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1 rounded-pill small" style="font-size: 0.76rem;">
                                    <i class="bi bi-shop me-1"></i> {{ $selectedSupplier->name }}
                                </span>
                                @if($selectedSupplier->phone)
                                    <span class="badge bg-light text-muted border px-2.5 py-1 rounded-pill small" style="font-size: 0.76rem;">
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
        <!-- KPI Metrics Grid (Compact 4 Card Layout) -->
        <div class="row g-2.5 mb-3">
            <!-- Card 1: Total Masuk -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-3 bg-white h-100 p-2.5" style="border: 1px solid #e2e8f0;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-uppercase fw-bold small text-muted mb-0.5" style="font-size: 0.68rem; letter-spacing: 0.4px;">
                                TOTAL BARANG MASUK
                            </div>
                            <div class="fs-4 fw-bolder text-dark line-height-1" style="font-size: 1.35rem;">
                                {{ number_format($totalReceivedAll) }}
                            </div>
                            <div class="text-muted small mt-1 d-flex align-items-center gap-1" style="font-size: 0.72rem;">
                                <span class="text-primary font-weight-bold">•</span>
                                <span>Pcs Total Diterima</span>
                            </div>
                        </div>
                        <div class="bg-primary bg-opacity-10 text-primary rounded-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; min-width: 40px;">
                            <i class="bi bi-box-arrow-in-down fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Sisa Stok Gudang -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-3 bg-white h-100 p-2.5" style="border: 1px solid #e2e8f0;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-uppercase fw-bold small text-muted mb-0.5" style="font-size: 0.68rem; letter-spacing: 0.4px;">
                                SISA STOK GUDANG
                            </div>
                            <div class="fs-4 fw-bolder text-success line-height-1" style="font-size: 1.35rem;">
                                {{ number_format($totalRemainingAll) }}
                            </div>
                            <div class="text-muted small mt-1 d-flex align-items-center gap-1" style="font-size: 0.72rem;">
                                <span class="text-success font-weight-bold">•</span>
                                <span>Tersedia Siap Jual</span>
                            </div>
                        </div>
                        <div class="bg-success bg-opacity-10 text-success rounded-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; min-width: 40px;">
                            <i class="bi bi-check-circle-fill fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 3: Total Terjual -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-3 bg-white h-100 p-2.5" style="border: 1px solid #e2e8f0;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-uppercase fw-bold small text-muted mb-0.5" style="font-size: 0.68rem; letter-spacing: 0.4px;">
                                TOTAL TERJUAL
                            </div>
                            <div class="fs-4 fw-bolder text-warning line-height-1" style="font-size: 1.35rem; color: #d97706 !important;">
                                {{ number_format($totalSoldAll) }}
                            </div>
                            <div class="text-muted small mt-1 d-flex align-items-center gap-1" style="font-size: 0.72rem;">
                                <span class="text-warning font-weight-bold" style="color: #d97706 !important;">•</span>
                                <span>Sudah Disetor: {{ number_format($totalSettledAll) }} Pcs</span>
                            </div>
                        </div>
                        <div class="bg-warning bg-opacity-15 rounded-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; min-width: 40px;">
                            <i class="bi bi-bag-check-fill fs-5" style="color: #ea580c;"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 4: Profit Toko -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-3 bg-white h-100 p-2.5" style="border: 1px solid #e2e8f0;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-uppercase fw-bold small text-muted mb-0.5" style="font-size: 0.68rem; letter-spacing: 0.4px;">
                                PROFIT TOKO
                            </div>
                            <div class="fs-5 fw-bolder text-danger font-monospace line-height-1" style="font-size: 1.15rem;">
                                Rp {{ number_format($totalProfitAll, 0, ',', '.') }}
                            </div>
                            <div class="text-muted small mt-1 d-flex align-items-center gap-1" style="font-size: 0.72rem;">
                                <span class="text-danger font-weight-bold">•</span>
                                <span>Margin Keuntungan</span>
                            </div>
                        </div>
                        <div class="bg-danger bg-opacity-10 text-danger rounded-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; min-width: 40px;">
                            <i class="bi bi-graph-up-arrow fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table Kartu Stok & Persediaan per Produk -->
        <div class="card border-0 shadow-sm rounded-3 bg-white">
            <div class="card-header bg-white p-3 border-bottom" style="border-color: #f1f5f9 !important;">
                <h6 class="fw-bold mb-0 text-dark" style="font-size: 0.9rem;">
                    <i class="bi bi-box text-primary me-1.5"></i>MUTASI & STOK PERSEDIAAN PRODUK SUPPLIER {{ $selectedSupplier ? strtoupper($selectedSupplier->name) : '' }}
                </h6>
            </div>
            <div class="card-body p-3">
                <div class="table-responsive rounded-2 border" style="border-color: #e2e8f0 !important;">
                    <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.8rem;">
                        <thead style="background-color: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                            <tr>
                                <th class="ps-2.5 text-muted fw-bold text-uppercase py-2" style="font-size: 0.68rem; letter-spacing: 0.4px;">SKU & NAMA PRODUK</th>
                                <th class="text-end text-muted fw-bold text-uppercase py-2" style="font-size: 0.68rem; letter-spacing: 0.4px;">HARGA TITIP (HPP)</th>
                                <th class="text-end text-muted fw-bold text-uppercase py-2" style="font-size: 0.68rem; letter-spacing: 0.4px;">HARGA JUAL</th>
                                <th class="text-center text-muted fw-bold text-uppercase py-2" style="font-size: 0.68rem; letter-spacing: 0.4px;">TOTAL MASUK</th>
                                <th class="text-center text-muted fw-bold text-uppercase py-2" style="font-size: 0.68rem; letter-spacing: 0.4px;">TERJUAL</th>
                                <th class="text-center text-muted fw-bold text-uppercase py-2" style="font-size: 0.68rem; letter-spacing: 0.4px;">STOK GUDANG</th>
                                <th class="text-center text-muted fw-bold text-uppercase py-2" style="font-size: 0.68rem; letter-spacing: 0.4px;">SUDAH DISETOR</th>
                                <th class="text-center text-muted fw-bold text-uppercase py-2" style="font-size: 0.68rem; letter-spacing: 0.4px;">BELUM DISETOR</th>
                                <th class="text-end text-muted fw-bold text-uppercase py-2" style="font-size: 0.68rem; letter-spacing: 0.4px;">HAK SUPPLIER</th>
                                <th class="text-end pe-2.5 text-muted fw-bold text-uppercase py-2" style="font-size: 0.68rem; letter-spacing: 0.4px;">PROFIT TOKO</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportData as $row)
                                <tr>
                                    <td class="ps-2.5 py-1.5">
                                        <div class="fw-bold text-dark" style="font-size: 0.82rem;">{{ $row['name'] }}</div>
                                        <span class="badge bg-light text-dark border font-monospace mt-0.5" style="font-size: 0.7rem;">SKU: {{ $row['sku'] }}</span>
                                    </td>
                                    <td class="text-end font-monospace text-muted py-1.5" style="font-size: 0.8rem;">Rp {{ number_format($row['unit_cost'], 0, ',', '.') }}</td>
                                    <td class="text-end font-monospace text-muted py-1.5" style="font-size: 0.8rem;">Rp {{ number_format($row['unit_selling'], 0, ',', '.') }}</td>
                                    <td class="text-center font-monospace fw-semibold py-1.5" style="font-size: 0.8rem;">{{ number_format($row['qty_received']) }} {{ $row['unit'] }}</td>
                                    <td class="text-center font-monospace fw-bold text-info py-1.5" style="font-size: 0.8rem;">{{ number_format($row['qty_sold']) }} {{ $row['unit'] }}</td>
                                    <td class="text-center py-1.5">
                                        <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2 py-0.5 font-monospace fw-bold" style="font-size: 0.76rem;">
                                            {{ number_format($row['current_stock']) }} {{ $row['unit'] }}
                                        </span>
                                    </td>
                                    <td class="text-center font-monospace fw-bold text-success py-1.5" style="font-size: 0.8rem;">{{ number_format($row['qty_settled']) }} {{ $row['unit'] }}</td>
                                    <td class="text-center font-monospace fw-bold text-danger py-1.5" style="font-size: 0.8rem;">{{ number_format($row['qty_unsettled']) }} {{ $row['unit'] }}</td>
                                    <td class="text-end fw-semibold text-dark font-monospace py-1.5" style="font-size: 0.8rem;">Rp {{ number_format($row['nominal_paid'], 0, ',', '.') }}</td>
                                    <td class="text-end pe-2.5 fw-bold text-success font-monospace py-1.5" style="font-size: 0.8rem;">+Rp {{ number_format($row['profit_total'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center py-4 text-muted small">
                                        <i class="bi bi-inbox fs-3 d-block mb-1 text-secondary"></i>
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
        <div class="card border-0 shadow-sm rounded-3 p-4 text-center text-muted bg-white">
            <i class="bi bi-building fs-2 d-block mb-2 text-secondary"></i>
            <h6 class="fw-semibold">Silakan Pilih Supplier di atas untuk Melihat Kartu Stok & Mutasi</h6>
        </div>
    @endif
</div>
@endsection
