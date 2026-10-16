@extends('layouts.mobile')

@section('title', 'Penjualan Offline')
@section('header-title', 'Penjualan Offline')

@section('styles')
<style>
    body {
        background-color: #f8fafc !important;
    }

    .kpi-card {
        background: #ffffff;
        border: 1px solid rgba(0, 0, 0, 0.05);
        border-radius: 14px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
        padding: 12px 14px;
    }

    .kpi-label {
        font-size: 0.68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: #64748b;
        margin-bottom: 2px;
    }

    .kpi-val {
        font-size: 1.15rem;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
    }

    .sale-card {
        background: #ffffff;
        border: 1px solid rgba(0, 0, 0, 0.06);
        border-radius: 16px;
        padding: 14px;
        margin-bottom: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
        transition: transform 0.15s ease;
    }

    .sale-card:active {
        transform: scale(0.99);
    }

    .pill-tab {
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
        text-decoration: none;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
    }

    .pill-tab.active {
        background: #0284c7;
        color: #ffffff !important;
        box-shadow: 0 2px 8px rgba(2, 132, 199, 0.25);
    }

    .pill-tab:not(.active) {
        background: #ffffff;
        color: #475569;
        border: 1px solid #e2e8f0;
    }
</style>
@endsection

@section('content')
    <!-- Top KPI Grid -->
    <div class="row g-2 mb-3">
        <div class="col-6">
            <div class="kpi-card">
                <div class="d-flex align-items-center gap-1.5 text-success mb-1">
                    <i class="fas fa-coins small"></i>
                    <span class="kpi-label mb-0">Total Omset</span>
                </div>
                <h4 class="kpi-val text-success">Rp {{ number_format($summary['total_omset'], 0, ',', '.') }}</h4>
                <div class="text-muted" style="font-size: 0.65rem;">{{ $summary['total_count'] }} transaksi</div>
            </div>
        </div>

        <div class="col-6">
            <div class="kpi-card">
                <div class="d-flex align-items-center gap-1.5 text-danger mb-1">
                    <i class="fas fa-credit-card small"></i>
                    <span class="kpi-label mb-0">Sisa Piutang</span>
                </div>
                <h4 class="kpi-val text-danger">
                    {{ $summary['total_piutang'] > 0 ? 'Rp ' . number_format($summary['total_piutang'], 0, ',', '.') : 'Lunas (Rp 0)' }}
                </h4>
                <div class="text-muted" style="font-size: 0.65rem;">Belum tertagih</div>
            </div>
        </div>

        <div class="col-6">
            <div class="kpi-card">
                <div class="d-flex align-items-center gap-1.5 text-warning mb-1">
                    <i class="fas fa-box-open small"></i>
                    <span class="kpi-label mb-0">Belum Dikemas</span>
                </div>
                <h4 class="kpi-val text-warning">{{ $summary['unpacked_count'] }} <span style="font-size: 0.75rem; font-weight: normal;">Nota</span></h4>
                <div class="text-muted" style="font-size: 0.65rem;">Perlu verifikasi</div>
            </div>
        </div>

        <div class="col-6">
            <div class="kpi-card">
                <div class="d-flex align-items-center gap-1.5 text-primary mb-1">
                    <i class="fas fa-tshirt small"></i>
                    <span class="kpi-label mb-0">Pre-Order SPK</span>
                </div>
                <h4 class="kpi-val text-primary">{{ $summary['po_count'] }} <span style="font-size: 0.75rem; font-weight: normal;">Pesanan</span></h4>
                <div class="text-muted" style="font-size: 0.65rem;">Jalur produksi</div>
            </div>
        </div>
    </div>

    <!-- Search Form -->
    <div class="mb-3">
        <form method="GET" action="{{ route('mobile.owner.penjualan_offline') }}">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="input-group bg-white border rounded-3 p-1">
                <span class="input-group-text bg-transparent border-0 text-muted"><i class="fas fa-search"></i></span>
                <input type="text" name="search" class="form-control border-0 shadow-none px-1" 
                       placeholder="Cari No. Nota, Pembeli, Instansi..." value="{{ $search }}" style="font-size: 0.85rem;">
                @if($search)
                    <a href="{{ route('mobile.owner.penjualan_offline', ['tab' => $tab]) }}" class="btn btn-link text-muted px-2 py-0 align-self-center"><i class="fas fa-times"></i></a>
                @endif
                <button type="submit" class="btn btn-primary btn-sm rounded-2 px-3 fw-bold">Cari</button>
            </div>
        </form>
    </div>

    <!-- Filter Tabs (Horizontal Scroll) -->
    <div class="d-flex gap-2 overflow-auto pb-2 mb-3" style="scrollbar-width: none;">
        <a href="{{ route('mobile.owner.penjualan_offline', ['tab' => 'all', 'search' => $search]) }}" 
           class="pill-tab {{ $tab === 'all' ? 'active' : '' }}">
            Semua
        </a>
        <a href="{{ route('mobile.owner.penjualan_offline', ['tab' => 'unpacked', 'search' => $search]) }}" 
           class="pill-tab {{ $tab === 'unpacked' ? 'active' : '' }}">
            Belum Kemas 
            @if($summary['unpacked_count'] > 0)
                <span class="badge {{ $tab === 'unpacked' ? 'bg-white text-primary' : 'bg-warning text-dark' }} rounded-pill" style="font-size: 0.65rem;">
                    {{ $summary['unpacked_count'] }}
                </span>
            @endif
        </a>
        <a href="{{ route('mobile.owner.penjualan_offline', ['tab' => 'piutang', 'search' => $search]) }}" 
           class="pill-tab {{ $tab === 'piutang' ? 'active' : '' }}">
            Ada Piutang
        </a>
        <a href="{{ route('mobile.owner.penjualan_offline', ['tab' => 'po', 'search' => $search]) }}" 
           class="pill-tab {{ $tab === 'po' ? 'active' : '' }}">
            Pre-Order SPK
        </a>
        <a href="{{ route('mobile.owner.penjualan_offline', ['tab' => 'completed', 'search' => $search]) }}" 
           class="pill-tab {{ $tab === 'completed' ? 'active' : '' }}">
            Selesai
        </a>
    </div>

    <!-- Sales List -->
    <div>
        @forelse($sales as $sale)
            @php
                $sisa = max(0, (float)$sale->grand_total - (float)$sale->paid_amount);
                $isLunas = $sisa <= 0;
                $totalQty = (int) $sale->items->sum('quantity');
                $packedQty = (int) $sale->items->sum('packed_quantity');
                $pct = $totalQty > 0 ? min(100, round(($packedQty / $totalQty) * 100)) : 0;
            @endphp
            <div class="sale-card">
                <!-- Header Card: No Nota & Status -->
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="d-flex align-items-center gap-1.5 flex-wrap">
                            <span class="font-monospace fw-bold text-dark fs-6">#{{ $sale->sale_number }}</span>
                            @if($sale->is_po)
                                <span class="badge bg-purple-subtle text-purple border border-purple-subtle" style="font-size: 0.65rem; background: #ede9fe; color: #6d28d9;">PO SPK</span>
                            @endif
                        </div>
                        <div class="text-muted" style="font-size: 0.68rem;">
                            <i class="far fa-calendar-alt me-1"></i>{{ $sale->sold_at ? \Carbon\Carbon::parse($sale->sold_at)->format('d M Y H:i') : '-' }} &bull; {{ $sale->store->store_name ?? 'Toko' }}
                        </div>
                    </div>
                    <div>
                        @if($sale->status === 'completed')
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 py-1 px-2" style="font-size: 0.68rem;">
                                <i class="fas fa-check-circle me-1"></i>Selesai
                            </span>
                        @elseif($sale->status === 'spk_processing')
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 py-1 px-2" style="font-size: 0.68rem;">
                                <i class="fas fa-clock me-1"></i>SPK Diproses
                            </span>
                        @elseif($sale->status === 'pending_spk')
                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 py-1 px-2" style="font-size: 0.68rem;">
                                <i class="fas fa-exclamation-circle me-1"></i>Belum Buat SPK
                            </span>
                        @elseif($sale->status === 'cancelled')
                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 py-1 px-2" style="font-size: 0.68rem;">
                                <i class="fas fa-ban me-1"></i>Batal
                            </span>
                        @else
                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 py-1 px-2" style="font-size: 0.68rem;">
                                {{ $sale->status_label ?? $sale->status }}
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Customer Details -->
                <div class="p-2 bg-light rounded-3 mb-2" style="font-size: 0.75rem;">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-dark"><i class="fas fa-user text-muted me-1"></i>{{ $sale->buyer_name ?: 'Pelanggan Walk-in (Umum)' }}</span>
                        @if($sale->buyer_phone)
                            <span class="text-muted"><i class="fas fa-phone me-1"></i>{{ $sale->buyer_phone }}</span>
                        @endif
                    </div>
                    @if($sale->institution_name)
                        <div class="text-primary mt-1 fw-semibold"><i class="fas fa-building me-1"></i>{{ $sale->institution_name }}</div>
                    @endif
                </div>

                <!-- Items Preview -->
                @if($sale->items->isNotEmpty())
                    <div class="text-muted mb-2" style="font-size: 0.72rem;">
                        <span class="fw-bold text-dark">Item:</span>
                        {{ $sale->items->take(2)->map(function($i) { return ($i->product_name ?? 'Produk') . ' (' . $i->quantity . ' pcs)'; })->implode(', ') }}
                        @if($sale->items->count() > 2)
                            <span class="text-primary">+{{ $sale->items->count() - 2 }} item lainnya</span>
                        @endif
                    </div>
                @endif

                <!-- Packing Progress -->
                <div class="bg-light p-2 rounded-2 mb-2">
                    <div class="d-flex justify-content-between align-items-center mb-1" style="font-size: 0.68rem;">
                        <span class="text-muted">Kemasan Fisik:</span>
                        <strong class="{{ $pct >= 100 ? 'text-success' : 'text-primary' }}">{{ $packedQty }} / {{ $totalQty }} pcs ({{ $pct }}%)</strong>
                    </div>
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar {{ $pct >= 100 ? 'bg-success' : 'bg-primary' }}" style="width: {{ $pct }}%"></div>
                    </div>
                </div>

                <!-- Bottom Financials & Link -->
                <div class="d-flex justify-content-between align-items-end pt-2 border-top">
                    <div>
                        <div class="text-muted" style="font-size: 0.65rem;">Total Tagihan:</div>
                        <div class="fw-bold font-monospace text-dark fs-6">Rp {{ number_format($sale->grand_total, 0, ',', '.') }}</div>
                    </div>
                    <div class="text-end">
                        <div class="text-muted" style="font-size: 0.65rem;">Status Bayar:</div>
                        <div class="fw-bold {{ $isLunas ? 'text-success' : 'text-danger' }}" style="font-size: 0.8rem;">
                            {{ $isLunas ? 'LUNAS' : 'Sisa: Rp ' . number_format($sisa, 0, ',', '.') }}
                        </div>
                    </div>
                    <div>
                        <a href="{{ route('v2.penjualan_offline.show', $sale->id) }}" class="btn btn-outline-primary btn-sm rounded-pill px-3 py-1" style="font-size: 0.72rem;">
                            Buka Nota <i class="fas fa-chevron-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="p-4 bg-white rounded-4 border text-center text-muted my-3">
                <i class="fas fa-receipt fa-2x mb-2 text-secondary opacity-50"></i>
                <div class="fw-bold small">Belum ada transaksi penjualan offline</div>
                <div style="font-size: 0.72rem;">{{ $search ? 'Tidak ada hasil untuk pencarian tersebut' : 'Transaksi POS toko fisik akan tampil di sini' }}</div>
            </div>
        @endforelse

        <!-- Pagination -->
        <div class="mt-3">
            {{ $sales->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection
