@extends('layouts.app')
@section('title', 'Detail Campaign: ' . $campaign->name)
@section('page-title', 'Detail Campaign Iklan')

@section('topbar-actions')
    <a href="{{ route('marketing.ads.campaigns') }}" class="btn btn-sm btn-light text-primary fw-bold px-3">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Campaigns
    </a>
@endsection

@section('content')
    {{-- Header Campaign Info --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3 px-4">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <div class="rounded-3 bg-primary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0"
                     style="width:48px;height:48px;">
                    <i class="bi bi-megaphone-fill text-primary fs-5"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0">{{ $campaign->name }}</h5>
                    <div class="d-flex align-items-center gap-2 mt-1">
                        @php
                            $pf = $campaign->adsAccount->platform;
                            $pfBadge = ['shopee'=>'bg-warning text-dark','tiktok'=>'bg-dark text-white','meta'=>'bg-primary text-white','google'=>'bg-danger text-white'][$pf] ?? 'bg-secondary text-white';
                        @endphp
                        <span class="badge {{ $pfBadge }} text-uppercase rounded-pill px-2" style="font-size:.65rem;">{{ $pf }}</span>
                        <span class="badge {{ $campaign->status === 'ACTIVE' ? 'bg-success' : 'bg-secondary' }} bg-opacity-10 {{ $campaign->status === 'ACTIVE' ? 'text-success' : 'text-secondary' }} border rounded-pill px-2" style="font-size:.65rem;">
                            <i class="bi bi-circle-fill me-1" style="font-size:.35rem;"></i>{{ $campaign->status }}
                        </span>
                    </div>
                </div>
                <div class="ms-auto d-flex gap-2">
                    <form action="{{ route('marketing.ads.toggle', $campaign->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-sm {{ $campaign->status === 'ACTIVE' ? 'btn-outline-danger' : 'btn-outline-success' }} fw-semibold">
                            <i class="bi bi-{{ $campaign->status === 'ACTIVE' ? 'pause-circle' : 'play-circle' }} me-1"></i>
                            {{ $campaign->status === 'ACTIVE' ? 'Jeda' : 'Aktifkan' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Stat Cards --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="text-muted small text-uppercase fw-semibold mb-1" style="font-size:.68rem;letter-spacing:.5px;">Total Spend</div>
                    <div class="fw-bold fs-5 text-danger">Rp {{ number_format($totalSpend, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="text-muted small text-uppercase fw-semibold mb-1" style="font-size:.68rem;letter-spacing:.5px;">Revenue Teratribusi</div>
                    <div class="fw-bold fs-5 text-success">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 bg-primary text-white shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="text-white-50 small text-uppercase fw-semibold mb-1" style="font-size:.68rem;letter-spacing:.5px;">ROAS Aktual</div>
                    <div class="fw-bold fs-5">{{ number_format($roas, 2) }}x
                        @if($campaign->target_roas)
                            <small class="fw-normal fs-6 text-white-50">/ target {{ $campaign->target_roas }}x</small>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="text-muted small text-uppercase fw-semibold mb-1" style="font-size:.68rem;letter-spacing:.5px;">CPO (Cost/Order)</div>
                    <div class="fw-bold fs-5 {{ $campaign->target_cpo && $cpo > $campaign->target_cpo ? 'text-danger' : 'text-dark' }}">
                        Rp {{ number_format($cpo, 0, ',', '.') }}
                        @if($campaign->target_cpo)
                            <small class="fw-normal fs-6 text-muted">/ target Rp {{ number_format($campaign->target_cpo, 0, ',', '.') }}</small>
                        @endif
                    </div>
                    <div class="text-muted small mt-1">{{ $totalConversions }} Order</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        {{-- Chart 30 Hari --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent py-2 px-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-bezier2 me-2 text-primary"></i>Tren Spend vs Revenue (30 Hari)</h6>
                </div>
                <div class="card-body p-3">
                    <div style="height:260px;">
                        <canvas id="campaignChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Top Products --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent py-2 px-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-star-fill me-2 text-warning"></i>Produk Terlaris</h6>
                </div>
                <div class="card-body p-3">
                    @forelse($topProducts as $p)
                        <div class="d-flex justify-content-between align-items-center py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                            <div class="me-2 flex-grow-1" style="min-width:0;">
                                <div class="fw-semibold small text-truncate">{{ $p->product_name }}</div>
                                <div class="text-muted" style="font-size:.72rem;">{{ $p->sku }}</div>
                            </div>
                            <div class="text-end flex-shrink-0">
                                <div class="fw-bold text-primary small">{{ number_format($p->total_qty) }} pcs</div>
                                <div class="text-muted" style="font-size:.72rem;">Rp {{ number_format($p->total_revenue, 0, ',', '.') }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-4 small">Belum ada data produk.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Order History --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent py-2 px-3 d-flex align-items-center">
            <h6 class="fw-bold mb-0"><i class="bi bi-receipt me-2 text-info"></i>Pesanan Teratribusi ke Campaign Ini</h6>
            <span class="badge bg-primary bg-opacity-10 text-primary border ms-2 rounded-pill small">{{ $orders->total() }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0" style="font-size:.82rem;">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Invoice</th>
                            <th>Toko</th>
                            <th>Pembeli</th>
                            <th class="text-end">Net Amount</th>
                            <th class="text-center">Status</th>
                            <th class="pe-3 text-end">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            <tr>
                                <td class="ps-3 font-monospace small text-primary fw-semibold">{{ $order->invoice_number }}</td>
                                <td class="small text-muted">{{ $order->store->name ?? '-' }}</td>
                                <td class="small">{{ $order->buyer_name }}</td>
                                <td class="text-end font-monospace text-success fw-semibold">Rp {{ number_format($order->net_amount, 0, ',', '.') }}</td>
                                <td class="text-center">
                                    @php
                                        $sc = ['COMPLETED'=>'success','DELIVERED'=>'info','SHIPPED'=>'primary','READY_TO_SHIP'=>'warning','UNPAID'=>'secondary'][$order->order_status] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $sc }} bg-opacity-10 text-{{ $sc }} border rounded-pill small">{{ $order->order_status }}</span>
                                </td>
                                <td class="text-end pe-3 text-muted small">{{ $order->order_date?->format('d/m/Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="bi bi-inbox fs-2 d-block mb-2 opacity-25"></i>
                                    Belum ada pesanan yang teratribusi ke campaign ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($orders->hasPages())
            <div class="card-footer bg-white border-0 py-3 px-3">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('campaignChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: @json($chartLabels),
            datasets: [
                {
                    label: 'Spend (Rp)',
                    data: @json($chartSpend),
                    backgroundColor: 'rgba(220,53,69,.2)',
                    borderColor: 'rgba(220,53,69,.8)',
                    borderWidth: 1.5,
                    borderRadius: 4,
                    order: 2
                },
                {
                    label: 'Revenue (Rp)',
                    data: @json($chartRevenue),
                    type: 'line',
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25,135,84,.08)',
                    borderWidth: 2,
                    pointRadius: 2,
                    fill: true,
                    tension: 0.4,
                    order: 1
                }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'top' } },
            scales: {
                y: { beginAtZero: true, ticks: { callback: v => 'Rp ' + (v/1000).toFixed(0) + 'k' } },
                x: { ticks: { font: { size: 10 } } }
            }
        }
    });
});
</script>
@endpush
