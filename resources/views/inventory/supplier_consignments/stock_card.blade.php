@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1"><i class="bi bi-card-checklist text-primary me-2"></i>Kartu Stok & Mutasi Konsinyasi Supplier</h3>
            <p class="text-muted small mb-0">Rekapitulasi mutasi barang masuk, barang terjual, sisa persediaan, setoran supplier, dan pendapatan bersih toko.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('supplier_consignments.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-box-seam me-1"></i> Penerimaan Barang
            </a>
            @if($selectedSupplierId)
                <a href="{{ route('supplier_consignments.settlement.create', ['supplier_id' => $selectedSupplierId]) }}" class="btn btn-success shadow-sm">
                    <i class="bi bi-cash-stack me-1"></i> Buat Setoran Supplier Baru
                </a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Supplier Filter Header -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('supplier_consignments.stock_card') }}" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <label class="form-label fw-semibold small text-uppercase mb-1">Pilih Supplier Penitip Barang:</label>
                    <select name="supplier_id" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Pilih Supplier --</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ $selectedSupplierId == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->name }} ({{ $supplier->phone ?: 'No Phone' }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-7 text-md-end pt-3">
                    @if($selectedSupplier)
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6 px-3 py-2">
                            <i class="bi bi-shop me-1"></i> {{ $selectedSupplier->name }}
                        </span>
                    @endif
                </div>
            </form>
        </div>
    </div>

    @if($selectedSupplierId)
        <!-- KPI Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-2">
                <div class="card border-0 shadow-sm rounded-3 bg-white p-3 border-start border-primary border-4">
                    <small class="text-muted fw-bold text-uppercase" style="font-size: 0.7rem;">Total Masuk (Consigned)</small>
                    <h4 class="fw-bold text-dark mb-0 mt-1">{{ number_format($totalReceivedAll) }} <span class="fs-6 text-muted fw-normal">PCS</span></h4>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card border-0 shadow-sm rounded-3 bg-white p-3 border-start border-info border-4">
                    <small class="text-muted fw-bold text-uppercase" style="font-size: 0.7rem;">Total Terjual</small>
                    <h4 class="fw-bold text-info mb-0 mt-1">{{ number_format($totalSoldAll) }} <span class="fs-6 text-muted fw-normal">PCS</span></h4>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card border-0 shadow-sm rounded-3 bg-white p-3 border-start border-warning border-4">
                    <small class="text-muted fw-bold text-uppercase" style="font-size: 0.7rem;">Sisa Persediaan Gudang</small>
                    <h4 class="fw-bold text-warning mb-0 mt-1">{{ number_format($totalRemainingAll) }} <span class="fs-6 text-muted fw-normal">PCS</span></h4>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card border-0 shadow-sm rounded-3 bg-white p-3 border-start border-success border-4">
                    <small class="text-muted fw-bold text-uppercase" style="font-size: 0.7rem;">Sudah Disetorkan</small>
                    <h4 class="fw-bold text-success mb-0 mt-1">{{ number_format($totalSettledAll) }} <span class="fs-6 text-muted fw-normal">PCS</span></h4>
                    <small class="text-muted small">Rp {{ number_format($totalPaidAmountAll, 0, ',', '.') }}</small>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card border-0 shadow-sm rounded-3 bg-white p-3 border-start border-danger border-4">
                    <small class="text-muted fw-bold text-uppercase" style="font-size: 0.7rem;">Belum Disetorkan</small>
                    <h4 class="fw-bold text-danger mb-0 mt-1">{{ number_format($totalUnsettledAll) }} <span class="fs-6 text-muted fw-normal">PCS</span></h4>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card border-0 shadow-sm rounded-3 bg-success text-white p-3">
                    <small class="text-white text-opacity-75 fw-bold text-uppercase" style="font-size: 0.7rem;">Pendapatan/Profit Toko</small>
                    <h4 class="fw-bold text-white mb-0 mt-1">Rp {{ number_format($totalProfitAll, 0, ',', '.') }}</h4>
                </div>
            </div>
        </div>

        <!-- Table Kartu Stok & Persediaan per Produk -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-box me-2 text-primary"></i>Mutasi & Stok Persediaan Produk Supplier {{ $selectedSupplier ? $selectedSupplier->name : '' }}
                </h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small text-uppercase fw-semibold">
                        <tr>
                            <th class="ps-3">SKU & Nama Produk</th>
                            <th class="text-end">Harga Titip (HPP)</th>
                            <th class="text-end">Harga Jual</th>
                            <th class="text-center">Total Masuk</th>
                            <th class="text-center">Terjual</th>
                            <th class="text-center">Sisa Stok</th>
                            <th class="text-center">Sudah Disetor</th>
                            <th class="text-center">Belum Disetor</th>
                            <th class="text-end">Hak Supplier (Disetor)</th>
                            <th class="text-end pe-3">Profit Toko</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reportData as $row)
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-bold text-dark">{{ $row['name'] }}</div>
                                    <div class="small text-muted">SKU: {{ $row['sku'] }}</div>
                                </td>
                                <td class="text-end">Rp {{ number_format($row['unit_cost'], 0, ',', '.') }}</td>
                                <td class="text-end">Rp {{ number_format($row['unit_selling'], 0, ',', '.') }}</td>
                                <td class="text-center font-monospace">{{ number_format($row['qty_received']) }} {{ $row['unit'] }}</td>
                                <td class="text-center font-monospace fw-bold text-info">{{ number_format($row['qty_sold']) }} {{ $row['unit'] }}</td>
                                <td class="text-center font-monospace fw-bold text-warning">{{ number_format($row['qty_remaining']) }} {{ $row['unit'] }}</td>
                                <td class="text-center font-monospace fw-bold text-success">{{ number_format($row['qty_settled']) }} {{ $row['unit'] }}</td>
                                <td class="text-center font-monospace fw-bold text-danger">{{ number_format($row['qty_unsettled']) }} {{ $row['unit'] }}</td>
                                <td class="text-end fw-semibold text-dark">Rp {{ number_format($row['nominal_paid'], 0, ',', '.') }}</td>
                                <td class="text-end pe-3 fw-bold text-success">+Rp {{ number_format($row['profit_total'], 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">
                                    Belum ada data barang konsinyasi approved untuk supplier ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- History Setoran Supplier -->
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-clock-history me-2 text-primary"></i>Riwayat Setoran Pembayaran ke Supplier
                </h6>
                <a href="{{ route('supplier_consignments.settlement.create', ['supplier_id' => $selectedSupplierId]) }}" class="btn btn-sm btn-success">
                    <i class="bi bi-plus-circle me-1"></i> Form Setoran Baru
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small text-uppercase fw-semibold">
                        <tr>
                            <th class="ps-3">No. Setoran</th>
                            <th>Tanggal Setoran</th>
                            <th>Metode Bayar</th>
                            <th class="text-center">Total Qty Disetor</th>
                            <th class="text-end">Total Nominal Disetorkan</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($settlements as $set)
                            <tr>
                                <td class="ps-3 fw-bold text-primary">{{ $set->settlement_number }}</td>
                                <td>{{ $set->settlement_date->format('d M Y') }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ strtoupper($set->payment_method) }}</span>
                                    @if($set->bankAccount)
                                        <small class="text-muted ms-1">({{ $set->bankAccount->bank_name }})</small>
                                    @endif
                                </td>
                                <td class="text-center fw-bold">{{ number_format($set->total_qty_settled) }} PCS</td>
                                <td class="text-end fw-bold text-success">Rp {{ number_format($set->total_amount_paid, 0, ',', '.') }}</td>
                                <td class="small text-muted">{{ $set->notes ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    Belum ada catatan setoran hasil penjualan ke supplier ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="card border-0 shadow-sm rounded-3 p-5 text-center text-muted">
            <i class="bi bi-building fs-1 d-block mb-3 text-secondary"></i>
            <h5>Silakan Pilih Supplier untuk Melihat Kartu Stok & Mutasi</h5>
        </div>
    @endif
</div>
@endsection
