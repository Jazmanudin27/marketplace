@extends('layouts.app')
@section('title', 'Detail Perintah Kerja (SPK) — #' . $order->id)
@section('page-title', 'Detail SPK Produksi')

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-1"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row g-3">
    {{-- Kiri: Detail Informasi SPK --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-3 bg-white mb-3">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="fas fa-info-circle me-2 text-primary"></i>Informasi SPK
                    </h6>
                    <span class="badge py-2 px-3 small text-uppercase text-white"
                        style="background:{{ $order->status === 'completed' ? 'linear-gradient(135deg,#10b981,#059669)' : ($order->status === 'producing' ? 'linear-gradient(135deg,#3b82f6,#2563eb)' : 'linear-gradient(135deg,#ef4444,#dc2626)') }}">
                        @if($order->status === 'completed') Selesai
                        @elseif($order->status === 'producing') Diproses
                        @elseif($order->status === 'pending') Antrean
                        @else Dibatalkan @endif
                    </span>
                </div>

                <div class="border rounded-2 overflow-hidden mb-3">
                    <div class="d-flex justify-content-between px-3 py-2 border-bottom" style="background:#f8fafc">
                        <span class="text-muted small">ID SPK</span>
                        <span class="font-monospace fw-bold text-dark small">#{{ $order->id }}</span>
                    </div>
                    <div class="d-flex justify-content-between px-3 py-2 border-bottom">
                        <span class="text-muted small">Produk Jadi</span>
                        <span class="small fw-semibold text-dark text-end">{{ $order->masterProduct->name }}</span>
                    </div>
                    <div class="d-flex justify-content-between px-3 py-2 border-bottom">
                        <span class="text-muted small">SKU</span>
                        <span class="font-monospace small text-primary fw-bold">{{ $order->masterProduct->sku }}</span>
                    </div>
                    <div class="d-flex justify-content-between px-3 py-2 border-bottom">
                        <span class="text-muted small">Qty Hasil Produksi</span>
                        <span class="small fw-bold text-dark">{{ number_format($order->quantity) }} {{ $order->masterProduct->unit ?: 'pcs' }}</span>
                    </div>
                    <div class="d-flex justify-content-between px-3 py-2 border-bottom">
                        <span class="text-muted small">Tanggal Mulai</span>
                        <span class="small text-muted">{{ $order->created_at->format('d M Y, H:i') }}</span>
                    </div>
                    @if($order->status === 'completed' || $order->status === 'cancelled')
                        <div class="d-flex justify-content-between px-3 py-2 border-bottom">
                            <span class="text-muted small">Tanggal Selesai</span>
                            <span class="small text-muted">{{ $order->updated_at->format('d M Y, H:i') }}</span>
                        </div>
                    @endif
                </div>

                <div class="text-muted small">
                    <i class="fas fa-user me-1"></i> Diajukan oleh: <strong>{{ $order->requestedBy ? $order->requestedBy->name : 'Sistem' }}</strong>
                </div>
            </div>
        </div>

        <a href="{{ route('production_orders.index') }}" class="btn btn-outline-secondary btn-sm w-100">
            <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar SPK
        </a>
    </div>

    {{-- Kanan: Detail Hitungan HPP --}}
    <div class="col-lg-8">
        @if($order->status === 'completed')
            <div class="card border-0 shadow-sm rounded-3 bg-white mb-3">
                <div class="card-header border-0 py-3 px-4 d-flex align-items-center gap-2" style="background:#f0fdf4">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:36px;height:36px;background:#10b981">
                        <i class="fas fa-calculator text-white"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">Rincian Perhitungan HPP Batch ini</h6>
                        <small class="text-muted small">Hasil kalkulasi total biaya konsumsi bahan baku ditambah biaya jasa ahli aktual.</small>
                    </div>
                </div>
                <div class="card-body p-4">
                    <!-- 1. Konsumsi Bahan Baku & Kemasan -->
                    <div class="mb-4">
                        <h6 class="fw-bold text-dark mb-2"><i class="fas fa-boxes me-2 text-primary"></i>1. Biaya Konsumsi Bahan Baku &amp; Kemasan Aktual</h6>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle border mb-0">
                                <thead class="table-light">
                                    <tr class="small text-uppercase text-muted" style="font-size:10px">
                                        <th class="ps-3">Nama Bahan</th>
                                        <th>SKU</th>
                                        <th class="text-center">Qty Terpakai</th>
                                        <th class="text-end">Harga Pokok (Avg)</th>
                                        <th class="text-end pe-3">Subtotal Biaya</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($materialCostDetails as $mat)
                                        <tr style="font-size:12px">
                                            <td class="ps-3 fw-semibold text-dark">{{ $mat['name'] }}</td>
                                            <td class="font-monospace text-muted">{{ $mat['sku'] }}</td>
                                            <td class="text-center">{{ number_format($mat['qty'], 4) }} {{ $mat['unit'] }}</td>
                                            <td class="text-end">Rp {{ number_format($mat['price'], 0, ',', '.') }}</td>
                                            <td class="text-end fw-bold text-dark pe-3">Rp {{ number_format($mat['total_cost'], 0, ',', '.') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-3">Tidak ada data konsumsi bahan baku dari BOM.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-end mt-2">
                            <span class="small fw-semibold text-muted">Subtotal Bahan Baku: <strong class="text-dark" style="font-size:14px">Rp {{ number_format($totalMaterialCost, 0, ',', '.') }}</strong></span>
                        </div>
                    </div>

                    {{-- 2. Jasa Ahli Aktual --}}
                    <div class="mb-4">
                        <h6 class="fw-bold text-dark mb-2"><i class="fas fa-user-cog me-2 text-warning"></i>2. Biaya Jasa Ahli &amp; QC Aktual</h6>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle border mb-0">
                                <thead class="table-light">
                                    <tr class="small text-uppercase text-muted" style="font-size:10px">
                                        <th class="ps-3">Nama Jasa / QC</th>
                                        <th class="text-end pe-3">Biaya Aktual</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($order->actualLabors as $labor)
                                        <tr style="font-size:12px">
                                            <td class="ps-3 fw-semibold text-dark">{{ $labor->service_name }}</td>
                                            <td class="text-end fw-bold text-dark pe-3">Rp {{ number_format($labor->actual_cost, 0, ',', '.') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="text-center text-muted py-3">Tidak ada biaya jasa ahli / QC tambahan untuk batch ini.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-end mt-2">
                            <span class="small fw-semibold text-muted">Subtotal Jasa Ahli: <strong class="text-dark" style="font-size:14px">Rp {{ number_format($totalLaborCost, 0, ',', '.') }}</strong></span>
                        </div>
                    </div>

                    {{-- 3. Kalkulasi Akhir HPP --}}
                    <div class="p-3 rounded-3 bg-light border d-flex flex-column gap-2">
                        <h6 class="fw-bold text-dark border-bottom pb-2 mb-2"><i class="fas fa-equals me-2 text-success"></i>Kalkulasi HPP Akhir</h6>
                        <div class="d-flex justify-content-between text-muted small">
                            <span>Total Biaya Bahan Baku &amp; Kemasan</span>
                            <span class="fw-semibold text-dark">Rp {{ number_format($totalMaterialCost, 0, ',', '.') }}</span>
                        </div>
                        <div class="d-flex justify-content-between text-muted small">
                            <span>Total Biaya Jasa Ahli &amp; QC</span>
                            <span class="fw-semibold text-dark">Rp {{ number_format($totalLaborCost, 0, ',', '.') }}</span>
                        </div>
                        <div class="d-flex justify-content-between border-top pt-2">
                            <span class="fw-bold text-dark">Total Biaya Produksi Batch</span>
                            <span class="fw-bold text-dark" style="font-size:15px">Rp {{ number_format($totalProductionCost, 0, ',', '.') }}</span>
                        </div>
                        <div class="d-flex justify-content-between text-muted small">
                            <span>Kuantitas Hasil Riil</span>
                            <span class="fw-semibold text-dark">{{ number_format($order->quantity) }} {{ $order->masterProduct->unit ?: 'pcs' }}</span>
                        </div>
                        <div class="d-flex justify-content-between border-top pt-2" style="background:#e0f2fe;margin:-10px;padding:10px;border-bottom-left-radius:6px;border-bottom-right-radius:6px">
                            <span class="fw-bold text-primary" style="font-size:14px">HPP Hasil Produksi per Unit</span>
                            <span class="fw-bold text-primary" style="font-size:15px">Rp {{ number_format($calculatedHpp, 2, ',', '.') }}</span>
                        </div>
                    </div>

                    <div class="alert alert-info mt-3 small mb-0">
                        <i class="fas fa-info-circle me-1"></i>
                        HPP di atas telah digabungkan dengan stok lama di Gudang Jadi menggunakan metode **Weighted Average**. HPP produk **{{ $order->masterProduct->name }}** saat ini diperbarui menjadi **Rp {{ number_format($order->masterProduct->cost_price, 2, ',', '.') }}**.
                    </div>
                </div>
            </div>
        @else
            <div class="card border-0 shadow-sm rounded-3 bg-white text-center py-5 text-muted">
                <i class="fas fa-info-circle fa-3x mb-3 opacity-25"></i>
                <h5>Belum Ada Kalkulasi HPP</h5>
                <p class="mb-0 small">Kalkulasi HPP baru akan ditampilkan secara rinci setelah proses produksi selesai.</p>
            </div>
        @endif
    </div>
</div>
@endsection
