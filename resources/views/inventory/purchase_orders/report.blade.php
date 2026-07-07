@extends('layouts.app')
@section('title', 'Laporan Analisis Pembelian & PO')
@section('page-title', 'Laporan Analisis Pembelian & PO')

@section('content')
    <div class="row">
        <div class="col-md-12">
            {{-- Filtering Card --}}
            <div class="card border shadow-sm mb-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center py-2 px-3 border-bottom">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="fas fa-filter text-primary me-2"></i>Filter Rentang & Kategori Laporan
                    </h6>
                    <div>
                        <a href="{{ route('purchase_orders.print_report', request()->query()) }}" target="_blank" class="btn btn-outline-danger btn-sm">
                            <i class="fas fa-print me-1"></i> Cetak Laporan
                        </a>
                    </div>
                </div>
                <div class="card-body p-3">
                    <form method="GET" action="{{ route('purchase_orders.report') }}">
                        <div class="row g-2 align-items-end">
                            <div class="col-12 col-md-3">
                                <label class="form-label small fw-semibold">Tanggal Mulai</label>
                                <input type="date" name="start_date" class="form-control form-control-sm"
                                    value="{{ $startDate }}">
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label small fw-semibold">Tanggal Selesai</label>
                                <input type="date" name="end_date" class="form-control form-control-sm"
                                    value="{{ $endDate }}">
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label small fw-semibold">Supplier</label>
                                <select name="supplier_id" class="form-select form-select-sm">
                                    <option value="">-- Semua Supplier --</option>
                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                            {{ $supplier->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label small fw-semibold">Departemen Pemohon</label>
                                <select name="department_id" class="form-select form-select-sm">
                                    <option value="">-- Semua Departemen --</option>
                                    @foreach ($departments as $dept)
                                        <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                                            {{ $dept->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 text-end mt-3">
                                <button type="submit" class="btn btn-primary btn-sm px-4">
                                    <i class="fas fa-sync-alt me-1"></i> Terapkan Filter
                                </button>
                                <a href="{{ route('purchase_orders.report') }}" class="btn btn-secondary btn-sm px-3">
                                    <i class="fas fa-times me-1"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- KPI Summary Widgets --}}
            <div class="row g-3 mb-4">
                {{-- Total Belanja --}}
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="card border shadow-sm p-3 bg-white position-relative overflow-hidden">
                        <div class="text-uppercase text-muted small fw-semibold mb-1" style="letter-spacing: .05em;">Total Belanja PO</div>
                        <div class="fs-4 fw-extrabold text-primary font-monospace">Rp {{ number_format($totalBelanja, 0, ',', '.') }}</div>
                        <i class="fas fa-wallet position-absolute opacity-10 text-primary" style="font-size: 3rem; right: 15px; bottom: 10px;"></i>
                    </div>
                </div>
                {{-- Total PO Terbuka --}}
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="card border shadow-sm p-3 bg-white position-relative overflow-hidden">
                        <div class="text-uppercase text-muted small fw-semibold mb-1" style="letter-spacing: .05em;">PO Sedang Berjalan</div>
                        <div class="fs-4 fw-extrabold text-warning font-monospace">{{ number_format($totalPoTerbuka) }} PO</div>
                        <i class="fas fa-hourglass-half position-absolute opacity-10 text-warning" style="font-size: 3rem; right: 15px; bottom: 10px;"></i>
                    </div>
                </div>
                {{-- Total PO Selesai --}}
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="card border shadow-sm p-3 bg-white position-relative overflow-hidden">
                        <div class="text-uppercase text-muted small fw-semibold mb-1" style="letter-spacing: .05em;">PO Selesai Diterima</div>
                        <div class="fs-4 fw-extrabold text-success font-monospace">{{ number_format($totalPoSelesai) }} PO</div>
                        <i class="fas fa-check-double position-absolute opacity-10 text-success" style="font-size: 3rem; right: 15px; bottom: 10px;"></i>
                    </div>
                </div>
                {{-- Rerata Transaksi PO --}}
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="card border shadow-sm p-3 bg-white position-relative overflow-hidden">
                        <div class="text-uppercase text-muted small fw-semibold mb-1" style="letter-spacing: .05em;">Rerata Nilai PO</div>
                        <div class="fs-4 fw-extrabold text-info font-monospace">Rp {{ number_format($avgPoValue, 0, ',', '.') }}</div>
                        <i class="fas fa-chart-line position-absolute opacity-10 text-info" style="font-size: 3rem; right: 15px; bottom: 10px;"></i>
                    </div>
                </div>
            </div>

            {{-- Charts Area --}}
            <div class="row g-3 mb-4">
                <div class="col-md-8">
                    <div class="card border shadow-sm p-3 bg-white h-100">
                        <h6 class="fw-bold mb-3 text-dark"><i class="fas fa-chart-area text-primary me-2"></i>Tren Pengeluaran Pembelian</h6>
                        <div style="height: 250px;">
                            <canvas id="trendChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border shadow-sm p-3 bg-white h-100">
                        <h6 class="fw-bold mb-3 text-dark"><i class="fas fa-chart-pie text-success me-2"></i>Pembelian per Supplier</h6>
                        <div style="height: 250px;">
                            <canvas id="supplierChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Breakdown Tables --}}
            <div class="row g-3 mb-4">
                {{-- Top Purchased Items --}}
                <div class="col-md-12">
                    <div class="card border shadow-sm overflow-hidden bg-white">
                        <div class="card-header bg-light py-2 px-3 fw-bold small text-dark border-bottom">
                            <i class="fas fa-cubes text-primary me-2"></i>Rincian Barang yang Dibeli (Bahan, ATK, Kemasan & Produk)
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped table-bordered align-middle mb-0">
                                <thead class="small text-uppercase">
                                    <tr>
                                        <th class="text-center" style="width: 50px;">#</th>
                                        <th style="width: 150px;">SKU</th>
                                        <th>Nama Barang</th>
                                        <th class="text-center" style="width: 150px;">Kategori</th>
                                        <th class="text-center" style="width: 90px;">Satuan</th>
                                        <th class="text-end" style="width: 120px;">Qty Dipesan</th>
                                        <th class="text-end" style="width: 120px;">Qty Diterima</th>
                                        <th class="text-end" style="width: 150px;">Rerata Harga</th>
                                        <th class="text-end" style="width: 150px;">Total Belanja</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($itemsBreakdown as $i => $item)
                                        <tr>
                                            <td class="text-center font-monospace small">{{ $loop->iteration }}</td>
                                            <td><code class="bg-light text-secondary px-2 py-0.5 rounded border small font-monospace">{{ $item['sku'] }}</code></td>
                                            <td class="fw-semibold text-dark small">{{ $item['name'] }}</td>
                                            <td class="text-center">
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary border small">{{ $item['type'] }}</span>
                                            </td>
                                            <td class="text-center small">{{ $item['unit'] }}</td>
                                            <td class="text-end fw-bold text-dark">{{ number_format($item['qty_ordered']) }}</td>
                                            <td class="text-end fw-bold text-success">{{ number_format($item['qty_received']) }}</td>
                                            <td class="text-end text-muted font-monospace small">Rp {{ number_format($item['avg_price'], 0, ',', '.') }}</td>
                                            <td class="text-end font-monospace fw-bold text-primary small">Rp {{ number_format($item['total_amount'], 0, ',', '.') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center py-4 text-muted small">Tidak ada histori pembelian barang dalam rentang tanggal ini.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Breakdown per Supplier & Department --}}
            <div class="row g-3 mb-4">
                {{-- Supplier Breakdown --}}
                <div class="col-md-6">
                    <div class="card border shadow-sm overflow-hidden bg-white">
                        <div class="card-header bg-light py-2 px-3 fw-bold small text-dark border-bottom">
                            <i class="fas fa-handshake text-success me-2"></i>Breakdown Belanja per Supplier
                        </div>
                        <div class="table-responsive" style="max-height: 250px;">
                            <table class="table table-sm table-striped table-bordered align-middle mb-0">
                                <thead class="small text-uppercase">
                                    <tr>
                                        <th>Nama Supplier</th>
                                        <th class="text-center" style="width: 100px;">Jumlah PO</th>
                                        <th class="text-end" style="width: 180px;">Total Pengeluaran</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($supplierBreakdown as $supplierId => $sup)
                                        <tr>
                                            <td class="fw-semibold text-dark small">{{ $sup['name'] }}</td>
                                            <td class="text-center small">{{ $sup['count'] }} PO</td>
                                            <td class="text-end font-monospace fw-bold text-success small">Rp {{ number_format($sup['amount'], 0, ',', '.') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center py-3 text-muted small">Belum ada data transaksi.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Department Breakdown --}}
                <div class="col-md-6">
                    <div class="card border shadow-sm overflow-hidden bg-white">
                        <div class="card-header bg-light py-2 px-3 fw-bold small text-dark border-bottom">
                            <i class="fas fa-building text-info me-2"></i>Breakdown Belanja per Departemen
                        </div>
                        <div class="table-responsive" style="max-height: 250px;">
                            <table class="table table-sm table-striped table-bordered align-middle mb-0">
                                <thead class="small text-uppercase">
                                    <tr>
                                        <th>Departemen</th>
                                        <th class="text-center" style="width: 100px;">Jumlah PO</th>
                                        <th class="text-end" style="width: 180px;">Total Pengeluaran</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($departmentBreakdown as $deptId => $dept)
                                        <tr>
                                            <td class="fw-semibold text-dark small">{{ $dept['name'] }}</td>
                                            <td class="text-center small">{{ $dept['count'] }} PO</td>
                                            <td class="text-end font-monospace fw-bold text-info small">Rp {{ number_format($dept['amount'], 0, ',', '.') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center py-3 text-muted small">Belum ada data transaksi.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- History of Purchase Orders --}}
            <div class="card border shadow-sm overflow-hidden bg-white mb-4">
                <div class="card-header bg-light py-2 px-3 fw-bold small text-dark border-bottom">
                    <i class="fas fa-history text-secondary me-2"></i>Daftar Transaksi Purchase Order
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-bordered align-middle mb-0">
                        <thead class="small text-uppercase">
                            <tr>
                                <th class="text-center" style="width: 50px;">#</th>
                                <th style="width: 150px;">No PO</th>
                                <th style="width: 150px;">Tanggal</th>
                                <th>Supplier</th>
                                <th>Departemen</th>
                                <th class="text-end" style="width: 150px;">Total Nilai</th>
                                <th class="text-center" style="width: 120px;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($orders as $i => $order)
                                <tr>
                                    <td class="text-center font-monospace small">{{ $loop->iteration }}</td>
                                    <td>
                                        <a href="{{ route('purchase_orders.show', $order) }}" class="fw-bold font-monospace text-primary small">
                                            {{ $order->po_number }}
                                        </a>
                                    </td>
                                    <td class="small">{{ \Carbon\Carbon::parse($order->po_date)->format('d-m-Y') }}</td>
                                    <td class="small">{{ $order->supplier->name ?? 'N/A' }}</td>
                                    <td class="small">
                                        <span class="badge bg-light text-dark border">{{ $order->department->name ?? 'Umum / Operasional' }}</span>
                                    </td>
                                    <td class="text-end font-monospace fw-bold text-dark small">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                                    <td class="text-center">
                                        @if ($order->status === 'draft')
                                            <span class="badge bg-secondary px-2 py-1 small">Draft</span>
                                        @elseif ($order->status === 'ordered')
                                            <span class="badge bg-primary px-2 py-1 small">Dipesan</span>
                                        @elseif ($order->status === 'partially_received')
                                            <span class="badge bg-warning text-dark px-2 py-1 small">Diterima Sebagian</span>
                                        @elseif ($order->status === 'received')
                                            <span class="badge bg-success px-2 py-1 small">Selesai</span>
                                        @elseif ($order->status === 'cancelled')
                                            <span class="badge bg-danger px-2 py-1 small">Dibatalkan</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted small">Tidak ada data Purchase Order dalam rentang tanggal ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            $(document).ready(function() {
                // 1. Trend Chart
                const trendCtx = document.getElementById('trendChart').getContext('2d');
                const trendKeys = {!! json_encode(array_keys($trendData->toArray())) !!};
                const trendValues = {!! json_encode(array_values($trendData->toArray())) !!};

                new Chart(trendCtx, {
                    type: 'line',
                    data: {
                        labels: trendKeys,
                        datasets: [{
                            label: 'Total Belanja PO',
                            data: trendValues,
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.3
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return 'Rp ' + (value / 1000).toLocaleString('id-ID') + 'k';
                                    }
                                }
                            }
                        }
                    }
                });

                // 2. Supplier Chart
                const supplierCtx = document.getElementById('supplierChart').getContext('2d');
                const supplierLabels = {!! json_encode($supplierBreakdown->pluck('name')->toArray()) !!};
                const supplierValues = {!! json_encode($supplierBreakdown->pluck('amount')->toArray()) !!};

                new Chart(supplierCtx, {
                    type: 'doughnut',
                    data: {
                        labels: supplierLabels,
                        datasets: [{
                            data: supplierValues,
                            backgroundColor: [
                                '#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { boxWidth: 12, font: { size: 10 } }
                            }
                        }
                    }
                });
            });
        </script>
    @endpush
@endsection
