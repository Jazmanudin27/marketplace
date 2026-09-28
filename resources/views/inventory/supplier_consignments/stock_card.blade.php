@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Top Header Banner (Pure Bootstrap 5 White Card) -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-3 d-flex align-items-center justify-content-center">
                        <i class="bi bi-card-checklist fs-3"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-0 text-dark">Kartu Stok & Persediaan Konsinyasi Supplier</h4>
                        <p class="text-muted small mb-0">Laporan mutasi persediaan barang titipan, penjualan, sisa stok, setoran supplier, dan profit toko</p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" onclick="window.print()" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                        <i class="bi bi-printer me-1"></i> Cetak Laporan
                    </button>
                    <a href="{{ route('supplier_consignments.index') }}" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                        <i class="bi bi-box-seam me-1"></i> Penerimaan Barang
                    </a>
                    @if($selectedSupplierId)
                        <a href="{{ route('supplier_consignments.settlement.create', ['supplier_id' => $selectedSupplierId]) }}" class="btn btn-success btn-sm rounded-pill px-4 shadow-sm fw-bold">
                            <i class="bi bi-cash-stack me-1"></i> Form Setoran Supplier
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

    <!-- Supplier Filter Card (Pure Bootstrap 5) -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('supplier_consignments.stock_card') }}">
                <div class="row g-3 align-items-center">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold small text-muted text-uppercase mb-1">Pilih Supplier Penitip Barang:</label>
                        <select name="supplier_id" class="form-select" onchange="this.form.submit()">
                            <option value="">-- Pilih Supplier --</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ $selectedSupplierId == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }} ({{ $supplier->phone ?: 'No Contact' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-7 text-md-end pt-3">
                        @if($selectedSupplier)
                            <div class="d-inline-flex align-items-center gap-2">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6 px-3 py-2">
                                    <i class="bi bi-shop me-1"></i> {{ $selectedSupplier->name }}
                                </span>
                                @if($selectedSupplier->phone)
                                    <span class="badge bg-light text-muted border fs-6 px-3 py-2">
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
        <!-- KPI Metrics Grid (Pure Bootstrap 5 Stat Cards) -->
        <div class="row g-3 mb-4">
            <!-- Total Masuk -->
            <div class="col-xl-2 col-md-4 col-6">
                <div class="card border-0 shadow-sm rounded-3 bg-white p-3 border-start border-primary border-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-muted fw-bold text-uppercase small">Total Barang Masuk</small>
                        <i class="bi bi-box-arrow-in-down text-primary fs-5"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-0 mt-1">{{ number_format($totalReceivedAll) }} <span class="fs-6 text-muted fw-normal">PCS</span></h4>
                </div>
            </div>

            <!-- Total Terjual -->
            <div class="col-xl-2 col-md-4 col-6">
                <div class="card border-0 shadow-sm rounded-3 bg-white p-3 border-start border-info border-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-muted fw-bold text-uppercase small">Total Terjual</small>
                        <i class="bi bi-bag-check text-info fs-5"></i>
                    </div>
                    <h4 class="fw-bold text-info mb-0 mt-1">{{ number_format($totalSoldAll) }} <span class="fs-6 text-muted fw-normal">PCS</span></h4>
                </div>
            </div>

            <!-- Sisa Stok Gudang -->
            <div class="col-xl-2 col-md-4 col-6">
                <div class="card border-0 shadow-sm rounded-3 bg-white p-3 border-start border-warning border-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-muted fw-bold text-uppercase small">Sisa Stok Gudang</small>
                        <i class="bi bi-boxes text-warning fs-5"></i>
                    </div>
                    <h4 class="fw-bold text-warning mb-0 mt-1">{{ number_format($totalRemainingAll) }} <span class="fs-6 text-muted fw-normal">PCS</span></h4>
                </div>
            </div>

            <!-- Sudah Disetorkan -->
            <div class="col-xl-2 col-md-4 col-6">
                <div class="card border-0 shadow-sm rounded-3 bg-white p-3 border-start border-success border-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-muted fw-bold text-uppercase small">Sudah Disetorkan</small>
                        <i class="bi bi-cash-stack text-success fs-5"></i>
                    </div>
                    <h4 class="fw-bold text-success mb-0 mt-1">{{ number_format($totalSettledAll) }} <span class="fs-6 text-muted fw-normal">PCS</span></h4>
                    <small class="text-muted font-monospace small">Rp {{ number_format($totalPaidAmountAll, 0, ',', '.') }}</small>
                </div>
            </div>

            <!-- Belum Disetorkan -->
            <div class="col-xl-2 col-md-4 col-6">
                <div class="card border-0 shadow-sm rounded-3 bg-white p-3 border-start border-danger border-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-muted fw-bold text-uppercase small">Belum Disetorkan</small>
                        <i class="bi bi-exclamation-circle text-danger fs-5"></i>
                    </div>
                    <h4 class="fw-bold text-danger mb-0 mt-1">{{ number_format($totalUnsettledAll) }} <span class="fs-6 text-muted fw-normal">PCS</span></h4>
                </div>
            </div>

            <!-- Pendapatan Profit Toko -->
            <div class="col-xl-2 col-md-4 col-6">
                <div class="card border-0 shadow-sm rounded-3 bg-success text-white p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-white text-opacity-75 fw-bold text-uppercase small">Profit Toko</small>
                        <i class="bi bi-graph-up-arrow text-white fs-5"></i>
                    </div>
                    <h4 class="fw-bold text-white mb-0 mt-1 font-monospace">Rp {{ number_format($totalProfitAll, 0, ',', '.') }}</h4>
                </div>
            </div>
        </div>

        <!-- Table Kartu Stok & Persediaan per Produk (Pure Bootstrap 5 Table) -->
        <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
            <div class="card-header bg-white py-3 border-bottom-0">
                <h6 class="fw-bold mb-0 text-dark text-uppercase small">
                    <i class="bi bi-box text-primary me-2"></i>MUTASI & STOK PERSEDIAAN PRODUK SUPPLIER {{ $selectedSupplier ? strtoupper($selectedSupplier->name) : '' }}
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-uppercase small fw-bold text-muted">
                            <tr>
                                <th class="ps-4">SKU & NAMA PRODUK</th>
                                <th class="text-end">HARGA TITIP (HPP)</th>
                                <th class="text-end">HARGA JUAL</th>
                                <th class="text-center">TOTAL MASUK</th>
                                <th class="text-center">TERJUAL</th>
                                <th class="text-center">STOK GUDANG SEKARANG</th>
                                <th class="text-center">SUDAH DISETOR</th>
                                <th class="text-center">BELUM DISETOR</th>
                                <th class="text-end">HAK SUPPLIER (DISETOR)</th>
                                <th class="text-end pe-4">PROFIT TOKO</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportData as $row)
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark">{{ $row['name'] }}</div>
                                        <span class="badge bg-light text-dark border font-monospace">SKU: {{ $row['sku'] }}</span>
                                    </td>
                                    <td class="text-end font-monospace text-muted">Rp {{ number_format($row['unit_cost'], 0, ',', '.') }}</td>
                                    <td class="text-end font-monospace text-muted">Rp {{ number_format($row['unit_selling'], 0, ',', '.') }}</td>
                                    <td class="text-center font-monospace font-semibold">{{ number_format($row['qty_received']) }} {{ $row['unit'] }}</td>
                                    <td class="text-center font-monospace fw-bold text-info">{{ number_format($row['qty_sold']) }} {{ $row['unit'] }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-3 py-1 font-monospace fw-bold fs-6">
                                            {{ number_format($row['current_stock']) }} {{ $row['unit'] }}
                                        </span>
                                    </td>
                                    <td class="text-center font-monospace fw-bold text-success">{{ number_format($row['qty_settled']) }} {{ $row['unit'] }}</td>
                                    <td class="text-center font-monospace fw-bold text-danger">{{ number_format($row['qty_unsettled']) }} {{ $row['unit'] }}</td>
                                    <td class="text-end fw-semibold text-dark font-monospace">Rp {{ number_format($row['nominal_paid'], 0, ',', '.') }}</td>
                                    <td class="text-end pe-4 fw-bold text-success font-monospace">+Rp {{ number_format($row['profit_total'], 0, ',', '.') }}</td>
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
        <div class="card border-0 shadow-sm rounded-3 p-5 text-center text-muted bg-white">
            <i class="bi bi-building fs-1 d-block mb-3 text-secondary"></i>
            <h5>Silakan Pilih Supplier di atas untuk Melihat Kartu Stok & Mutasi</h5>
        </div>
    @endif
</div>
@endsection
