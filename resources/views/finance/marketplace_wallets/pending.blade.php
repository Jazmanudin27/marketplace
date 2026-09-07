@extends('layouts.app')

@section('title', 'Rincian Saldo Tertahan — ' . $store->store_name)
@section('page-title', 'Rincian Saldo Tertahan — ' . $store->store_name)

@section('content')
    <div class="row">
        <div class="col-md-12">

            {{-- ── Header & Action Buttons ────────────────────────────────────────── --}}
            <div class="card border shadow-sm mb-3">
                <div class="card-header bg-light d-flex justify-content-between align-items-center py-2.5 px-3 border-bottom flex-wrap gap-2">
                    <div>
                        <h6 class="m-0 fw-bold text-warning-emphasis">
                            <i class="fas fa-hourglass-half me-2 text-warning"></i>Rincian Saldo Tertahan (Akan Dilepas) — {{ $store->store_name }}
                        </h6>
                        <p class="text-muted mb-0 small mt-1">
                            Platform: 
                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill small fw-semibold px-2 py-0.5">
                                {{ strtoupper($store->channel->code) }}
                            </span>
                            | ID Toko: <span class="font-monospace text-dark">#{{ $store->marketplace_store_id }}</span>
                            <span class="text-muted ms-2">• Daftar pesanan aktif yang dananya masih ditahan dan akan masuk ke Saldo Dompet setelah pesanan selesai.</span>
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('finance.marketplace_wallets.mutasi', $store) }}" class="btn btn-outline-primary btn-sm px-3 rounded-2 fw-semibold">
                            <i class="fas fa-history me-1"></i> Lihat Mutasi Dompet
                        </a>
                        <a href="{{ route('finance.marketplace_wallets.index') }}" class="btn btn-outline-secondary btn-sm px-3 rounded-2">
                            <i class="fas fa-arrow-left me-1"></i> Kembali ke Dompet
                        </a>
                    </div>
                </div>
            </div>

            {{-- ── Summary Cards ──────────────────────────────────────────────────── --}}
            <div class="row g-3 mb-3">
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="card border rounded-3 shadow-sm bg-white h-100 border-start border-warning border-4">
                        <div class="card-body py-2.5 px-3 d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                                <i class="fas fa-hourglass-half fs-5"></i>
                            </div>
                            <div class="min-width-0">
                                <div class="text-muted small fw-semibold">Total Saldo Tertahan</div>
                                <div class="fw-bold fs-5 text-warning-emphasis font-monospace mt-0.5">
                                    Rp {{ number_format($totalPendingAmount, 0, ',', '.') }}
                                </div>
                                <small class="text-muted" style="font-size: 0.7rem;">Estimasi bersih yang akan cair</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="card border rounded-3 shadow-sm bg-white h-100 border-start border-primary border-4">
                        <div class="card-body py-2.5 px-3 d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                                <i class="fas fa-box-open fs-5"></i>
                            </div>
                            <div class="min-width-0">
                                <div class="text-muted small fw-semibold">Pesanan Pending</div>
                                <div class="fw-bold fs-5 text-primary font-monospace mt-0.5">
                                    {{ count($pendingList) }} <span class="fs-6 fw-normal text-muted">Pesanan</span>
                                </div>
                                <small class="text-muted" style="font-size: 0.7rem;">Sedang diproses / dikirim</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="card border rounded-3 shadow-sm bg-white h-100 border-start border-dark border-4">
                        <div class="card-body py-2.5 px-3 d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-dark bg-opacity-10 text-dark d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                                <i class="fas fa-receipt fs-5"></i>
                            </div>
                            <div class="min-width-0">
                                <div class="text-muted small fw-semibold">Total Nilai Kotor (Gross)</div>
                                <div class="fw-bold fs-5 text-dark font-monospace mt-0.5">
                                    Rp {{ number_format($totalGrossAmount, 0, ',', '.') }}
                                </div>
                                <small class="text-muted" style="font-size: 0.7rem;">Nilai total belanja pembeli</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="card border rounded-3 shadow-sm bg-white h-100 border-start border-danger border-4">
                        <div class="card-body py-2.5 px-3 d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-danger bg-opacity-10 text-danger d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                                <i class="fas fa-percentage fs-5"></i>
                            </div>
                            <div class="min-width-0">
                                <div class="text-muted small fw-semibold">Est. Fee & Potongan</div>
                                <div class="fw-bold fs-5 text-danger font-monospace mt-0.5">
                                    Rp {{ number_format($totalFeeAmount, 0, ',', '.') }}
                                </div>
                                <small class="text-muted" style="font-size: 0.7rem;">Komisi, biaya layanan, dll</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Filter Bar ─────────────────────────────────────────────────────── --}}
            <div class="card border shadow-sm mb-3">
                <div class="card-body py-3 px-3">
                    <form action="{{ route('finance.marketplace_wallets.pending', $store) }}" method="GET" id="filterForm">
                        <div class="row g-2 align-items-end">
                            {{-- Mulai Tanggal --}}
                            <div class="col-6 col-md-2">
                                <label class="form-label form-label-sm fw-semibold mb-1 text-dark small">
                                    <i class="fas fa-calendar text-muted me-1"></i>Mulai Tanggal
                                </label>
                                <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control form-control-sm" required>
                            </div>

                            {{-- Sampai Tanggal --}}
                            <div class="col-6 col-md-2">
                                <label class="form-label form-label-sm fw-semibold mb-1 text-dark small">
                                    <i class="fas fa-calendar-check text-muted me-1"></i>Sampai Tanggal
                                </label>
                                <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control form-control-sm" required>
                            </div>

                            {{-- Status Pesanan --}}
                            <div class="col-6 col-md-2">
                                <label class="form-label form-label-sm fw-semibold mb-1 text-dark small">
                                    <i class="fas fa-tags text-muted me-1"></i>Status Pesanan
                                </label>
                                <select name="status" class="form-select form-select-sm">
                                    <option value="">Semua Status Aktif</option>
                                    @foreach($availableStatuses as $st)
                                        <option value="{{ $st }}" {{ ($statusFilter ?? '') === $st ? 'selected' : '' }}>
                                            {{ $st }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Cari Pesanan / Pembeli --}}
                            <div class="col-6 col-md-3">
                                <label class="form-label form-label-sm fw-semibold mb-1 text-dark small">
                                    <i class="fas fa-search text-muted me-1"></i>Cari No. Pesanan / Pembeli / Resi
                                </label>
                                <input type="text" name="search" value="{{ $search }}" class="form-control form-control-sm" placeholder="No. Pesanan / Resi / Nama...">
                            </div>

                            {{-- Buttons --}}
                            <div class="col-md-auto">
                                <button type="submit" class="btn btn-primary btn-sm px-3">
                                    <i class="fas fa-filter me-1"></i>Filter
                                </button>
                                <a href="{{ route('finance.marketplace_wallets.pending', $store) }}" class="btn btn-secondary btn-sm px-3 ms-1" title="Reset Filter">
                                    <i class="fas fa-undo me-1"></i>Reset
                                </a>
                            </div>

                            {{-- Summary Count --}}
                            <div class="col-md ms-auto text-end">
                                <span class="text-muted small">
                                    Ditemukan <strong class="text-dark font-monospace">{{ count($pendingList) }}</strong> pesanan tertahan
                                </span>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- ── Tabel Utama Rincian Pesanan Pending ────────────────────────────── --}}
            <div class="card border shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center py-2.5 px-3 border-bottom">
                    <div class="fw-bold text-dark small">
                        <i class="fas fa-list me-1.5 text-primary"></i>Daftar Pesanan yang Menyumbang Saldo Tertahan
                    </div>
                    <span class="badge bg-warning bg-opacity-25 text-dark rounded-pill px-2.5 py-1 small fw-semibold">
                        Total Estimasi: Rp {{ number_format($totalPendingAmount, 0, ',', '.') }}
                    </span>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr class="text-uppercase small font-monospace text-muted" style="font-size: 0.73rem;">
                                    <th class="text-center" style="width: 45px;">No</th>
                                    <th>Waktu Pesanan</th>
                                    <th>No. Pesanan & Resi</th>
                                    <th>Pembeli & Ekspedisi</th>
                                    <th class="text-center">Status Pesanan</th>
                                    <th class="text-end">Nilai Kotor</th>
                                    <th class="text-end">Est. Fee / Potongan</th>
                                    <th class="text-end pe-3">Est. Saldo Cair</th>
                                    <th class="text-center" style="width: 90px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pendingList as $i => $item)
                                    @php
                                        $st = strtoupper($item['order_status']);
                                        $statusClass = match(true) {
                                            in_array($st, ['SHIPPED', 'IN_TRANSIT', '121']) => 'bg-primary bg-opacity-10 text-primary border-primary border-opacity-25',
                                            in_array($st, ['DELIVERED', 'TO_CONFIRM_RECEIVE', '122']) => 'bg-info bg-opacity-10 text-info border-info border-opacity-25',
                                            in_array($st, ['READY_TO_SHIP', 'PROCESSED', 'AWAITING_SHIPMENT', 'AWAITING_COLLECTION', '111', '112']) => 'bg-warning bg-opacity-15 text-warning-emphasis border-warning border-opacity-25',
                                            default => 'bg-secondary bg-opacity-10 text-secondary border-secondary border-opacity-25'
                                        };

                                        $statusLabel = match(true) {
                                            in_array($st, ['READY_TO_SHIP', 'PROCESSED', 'AWAITING_SHIPMENT', '111']) => 'Siap Dikirim',
                                            in_array($st, ['AWAITING_COLLECTION', '112']) => 'Menunggu Pick Up',
                                            in_array($st, ['SHIPPED', 'IN_TRANSIT', '121', 'RETRY_SHIP', 'TO_RETRY_LOGISTICS']) => 'Dalam Pengiriman',
                                            in_array($st, ['DELIVERED', '122']) => 'Sampai di Tujuan',
                                            in_array($st, ['TO_CONFIRM_RECEIVE']) => 'Konfirmasi Terima',
                                            default => $item['order_status']
                                        };
                                    @endphp
                                    <tr>
                                        <td class="text-center text-muted small">{{ $i + 1 }}</td>
                                        <td class="small text-secondary font-monospace" style="white-space: nowrap;">
                                            {{ $item['order_date'] }}
                                        </td>
                                        <td>
                                            <div class="fw-bold font-monospace small text-dark">
                                                {{ $item['order_marketplace_id'] }}
                                            </div>
                                            @if($item['tracking_number'])
                                                <div class="text-muted small font-monospace mt-0.5" style="font-size: 0.72rem;">
                                                    <i class="fas fa-barcode me-1 opacity-50"></i>{{ $item['tracking_number'] }}
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark small text-truncate" style="max-width: 170px;">
                                                {{ $item['buyer_name'] }}
                                            </div>
                                            <div class="text-muted small" style="font-size: 0.72rem;">
                                                <i class="fas fa-shipping-fast me-1 opacity-50"></i>{{ $item['courier'] }}
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge border rounded-pill px-2.5 py-1 small fw-semibold {{ $statusClass }}">
                                                {{ $statusLabel }}
                                            </span>
                                            <div class="text-muted mt-0.5" style="font-size: 0.65rem;">
                                                {{ $item['order_status'] }}
                                            </div>
                                        </td>
                                        <td class="text-end font-monospace small text-secondary">
                                            Rp {{ number_format($item['gross'], 0, ',', '.') }}
                                        </td>
                                        <td class="text-end font-monospace small text-danger">
                                            - Rp {{ number_format($item['fee'], 0, ',', '.') }}
                                            <div class="text-muted" style="font-size: 0.65rem;">
                                                {{ $item['fee_type'] }}
                                            </div>
                                            @if($item['refund'] > 0)
                                                <div class="text-danger fw-semibold" style="font-size: 0.65rem;">
                                                    Retur: -Rp {{ number_format($item['refund'], 0, ',', '.') }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-end font-monospace small fw-bold text-success pe-3 fs-6">
                                            Rp {{ number_format($item['escrow'], 0, ',', '.') }}
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('orders.show', $item['order_id']) }}" target="_blank" class="btn btn-outline-primary btn-xs py-1 px-2 rounded-2" title="Buka Detail Pesanan di Tab Baru">
                                                <i class="fas fa-external-link-alt me-1"></i> Detail
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-5 small">
                                            <i class="fas fa-inbox me-2 opacity-50 fs-4 d-block mb-2"></i>
                                            Tidak ada pesanan aktif yang tertahan pada rentang tanggal dan filter ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
