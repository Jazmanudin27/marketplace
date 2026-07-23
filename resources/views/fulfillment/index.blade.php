@extends('layouts.app')
@section('title', 'Pemenuhan Pesanan (Pick & Pack)')
@section('page-title', 'Pemenuhan Pesanan')

@section('content')
    <div class="row">
        <div class="col-md-12">

            {{-- Stats Cards --}}
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <a href="{{ route('fulfillment.index') }}" class="text-decoration-none">
                        <div
                            class="card h-100 border rounded shadow-sm bg-white border-start border-primary border-4 {{ !request('packing_status') ? 'shadow' : '' }}">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="fs-2 text-primary opacity-75"><i class="fas fa-box"></i></div>
                                <div>
                                    <h4 class="fw-bold text-dark mb-0">{{ $stats['total'] }}</h4>
                                    <p class="text-muted mb-0 small">Total Siap Kirim</p>
                                    <div class="mt-1" style="font-size:0.68rem;">
                                        <a href="{{ route('fulfillment.index', array_merge(request()->query(), ['print_status' => 'unprinted'])) }}" class="badge bg-secondary opacity-75 text-decoration-none me-1" title="Filter Belum Print">
                                            {{ $stats['unprinted'] }} Belum Print
                                        </a>
                                        <a href="{{ route('fulfillment.index', array_merge(request()->query(), ['print_status' => 'printed'])) }}" class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 text-decoration-none" title="Filter Sudah Print">
                                            {{ $stats['printed'] }} Sudah Print
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="{{ route('fulfillment.index', array_merge(request()->query(), ['packing_status' => 'pending'])) }}"
                        class="text-decoration-none">
                        <div
                            class="card h-100 border rounded shadow-sm bg-white border-start border-warning border-4 {{ request('packing_status') === 'pending' ? 'shadow' : '' }}">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="fs-2 text-warning opacity-75"><i class="fas fa-clock"></i></div>
                                <div>
                                    <h4 class="fw-bold text-dark mb-0">{{ $stats['pending'] }}</h4>
                                    <p class="text-muted mb-0 small">Belum Diproses</p>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="{{ route('fulfillment.index', array_merge(request()->query(), ['packing_status' => 'packing'])) }}"
                        class="text-decoration-none">
                        <div
                            class="card h-100 border rounded shadow-sm bg-white border-start border-info border-4 {{ request('packing_status') === 'packing' ? 'shadow' : '' }}">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="fs-2 text-info opacity-75"><i class="fas fa-pallet"></i></div>
                                <div>
                                    <h4 class="fw-bold text-dark mb-0">{{ $stats['packing'] }}</h4>
                                    <p class="text-muted mb-0 small">Sedang Dikemas</p>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="{{ route('fulfillment.index', array_merge(request()->query(), ['packing_status' => 'verified'])) }}"
                        class="text-decoration-none">
                        <div
                            class="card h-100 border rounded shadow-sm bg-white border-start border-success border-4 {{ request('packing_status') === 'verified' ? 'shadow' : '' }}">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="fs-2 text-success opacity-75"><i class="fas fa-check-circle"></i></div>
                                <div>
                                    <h4 class="fw-bold text-dark mb-0">{{ $stats['verified'] }}</h4>
                                    <p class="text-muted mb-0 small">Selesai Scan (Verified)</p>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            {{-- Filter Panel --}}
            <div class="card border rounded shadow-sm bg-white mb-4">
                <div class="card-body p-3">
                    <form method="GET" action="{{ route('fulfillment.index') }}">
                        <div class="row g-2 align-items-end">
                            <div class="col-12 col-md-3">
                                <label class="form-label fw-bold small text-dark mb-1">
                                    <i class="fas fa-search me-1 text-secondary"></i>Pencarian
                                </label>
                                <input type="text" name="search" class="form-control form-control-sm"
                                    placeholder="Cari Invoice, ID, Pembeli, SKU..." value="{{ request('search') }}">
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label fw-bold small text-dark mb-1">
                                    <i class="fas fa-shopping-bag me-1 text-secondary"></i>Channel
                                </label>
                                <select name="channel_id" class="form-select form-select-sm">
                                    <option value="">Semua Channel</option>
                                    @foreach ($channels as $channel)
                                        <option value="{{ $channel->id }}"
                                            {{ request('channel_id') == $channel->id ? 'selected' : '' }}>
                                            {{ $channel->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label fw-bold small text-dark mb-1">
                                    <i class="fas fa-store me-1 text-secondary"></i>Toko
                                </label>
                                <select name="store_id" class="form-select form-select-sm">
                                    <option value="">Semua Toko</option>
                                    @foreach ($stores as $store)
                                        <option value="{{ $store->id }}"
                                            {{ request('store_id') == $store->id ? 'selected' : '' }}>
                                            {{ $store->store_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label fw-bold small text-dark mb-1">
                                    <i class="fas fa-truck me-1 text-secondary"></i>Kurir
                                </label>
                                <select name="courier" class="form-select form-select-sm">
                                    <option value="">Semua Kurir</option>
                                    @foreach ($couriers as $cr)
                                        <option value="{{ $cr }}"
                                            {{ request('courier') == $cr ? 'selected' : '' }}>
                                            {{ $cr }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label fw-bold small text-dark mb-1">
                                    <i class="fas fa-box-open me-1 text-secondary"></i>Status Kemas
                                </label>
                                <select name="packing_status" class="form-select form-select-sm">
                                    <option value="">Semua Status Kemas</option>
                                    <option value="pending"
                                        {{ request('packing_status') === 'pending' ? 'selected' : '' }}>Menunggu (Pending)
                                    </option>
                                    <option value="packing"
                                        {{ request('packing_status') === 'packing' ? 'selected' : '' }}>Sedang Dikemas
                                        (Packing)</option>
                                    <option value="verified"
                                        {{ request('packing_status') === 'verified' ? 'selected' : '' }}>Selesai Scan
                                        (Verified)</option>
                                </select>
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label fw-bold small text-dark mb-1">
                                    <i class="fas fa-print me-1 text-secondary"></i>Status Print
                                </label>
                                <select name="print_status" class="form-select form-select-sm">
                                    <option value="">Semua Status Print</option>
                                    <option value="unprinted" {{ request('print_status') === 'unprinted' ? 'selected' : '' }}>Belum Diprint</option>
                                    <option value="printed" {{ request('print_status') === 'printed' ? 'selected' : '' }}>Sudah Diprint</option>
                                </select>
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label fw-bold small text-dark mb-1">
                                    <i class="fas fa-box me-1 text-secondary"></i>Tipe Produk
                                </label>
                                <select name="is_po" class="form-select form-select-sm">
                                    <option value="">Semua Tipe</option>
                                    <option value="po" {{ request('is_po') === 'po' ? 'selected' : '' }}>Pre-Order (PO)
                                    </option>
                                    <option value="ready" {{ request('is_po') === 'ready' ? 'selected' : '' }}>Ready Stock
                                    </option>
                                </select>
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label fw-bold small text-dark mb-1">
                                    <i class="fas fa-calendar-alt me-1 text-secondary"></i>Rentang Tanggal
                                </label>
                                <div class="d-flex gap-1">
                                    <input type="date" name="start_date" class="form-control form-control-sm"
                                        value="{{ request('start_date') }}">
                                    <input type="date" name="end_date" class="form-control form-control-sm"
                                        value="{{ request('end_date') }}">
                                </div>
                            </div>
                            <div class="col-12 col-md-auto d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-sm px-3 rounded-3">
                                    <i class="fas fa-search me-1"></i> Cari
                                </button>
                                @if (request()->anyFilled([
                                        'search',
                                        'channel_id',
                                        'store_id',
                                        'courier',
                                        'packing_status',
                                        'print_status',
                                        'is_po',
                                        'start_date',
                                        'end_date',
                                    ]))
                                    <a href="{{ route('fulfillment.index') }}"
                                        class="btn btn-secondary btn-sm px-3 rounded-3">
                                        <i class="fas fa-times me-1"></i> Reset
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Workflow Steps Banner --}}
            <div class="card border-0 bg-primary-subtle shadow-sm rounded-3 mb-4">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary fs-6 p-2 rounded-circle"><i class="fas fa-route"></i></span>
                            <div>
                                <h6 class="fw-bold text-dark mb-0">Alur Kerja Gudang Baru (Fulfillment Workflow)</h6>
                                <small class="text-muted">Ikuti 3 langkah pemenuhan pesanan dari cetak resi hingga
                                    penyerahan kurir:</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2 small fw-bold">
                            <span class="badge bg-primary text-white py-2 px-3 shadow-sm"><i
                                    class="fas fa-print me-1"></i> 1. Cetak Resi Dulu</span>
                            <i class="fas fa-chevron-right text-muted"></i>
                            <span class="badge bg-success text-white py-2 px-3 shadow-sm"><i
                                    class="fas fa-barcode me-1"></i> 2. Ambil Barang & Scan SKU (Stok Berkurang
                                Real-time)</span>
                            <i class="fas fa-chevron-right text-muted"></i>
                            <span class="badge bg-dark text-white py-2 px-3 shadow-sm"><i class="fas fa-box me-1"></i> 3.
                                Scan Kemas & Kirim</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Table Card --}}
            <div class="card border rounded shadow-sm bg-white">
                <div class="card-body">
                    <form method="POST" id="batch-form">
                        @csrf
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                            <h5 class="fw-bold text-dark mb-0"><i class="fas fa-barcode text-primary"></i> Antrean Kemas
                                Pesanan</h5>

                            {{-- Action Buttons Bar (Top of Table) --}}
                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('fulfillment.interactive_picklist', request()->query()) }}"
                                    class="btn btn-success btn-sm d-inline-flex align-items-center gap-1 fw-bold shadow-sm"
                                    title="Buka Layar Rekap Ambil Barang Interaktif (Scan Barcode SKU)">
                                    <i class="fas fa-hand-pointer"></i> Layar Ambil Barang
                                </a>

                                <button type="button" id="btn-top-pick"
                                    class="btn btn-info text-white btn-sm d-inline-flex align-items-center gap-1 fw-semibold shadow-sm"
                                    title="Cetak Pick List (Kertas A4 / Standar)">
                                    <i class="fas fa-list-alt"></i> Cetak Pick List (A4)
                                </button>

                                <button type="button" id="btn-top-label"
                                    class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1 fw-semibold shadow-sm"
                                    title="Cetak Resi Massal (Kertas Stiker Thermal)">
                                    <i class="fas fa-print"></i> Cetak Resi Massal (Thermal)
                                </button>

                                <button type="button" id="btn-top-verify"
                                    class="btn btn-warning text-dark btn-sm d-inline-flex align-items-center gap-1 fw-bold shadow-sm"
                                    title="Verifikasi Packing Selesai">
                                    <i class="fas fa-check-double"></i> Packing Selesai
                                </button>

                                <button type="button" id="btn-top-ship"
                                    class="btn btn-outline-success btn-sm d-inline-flex align-items-center gap-1 fw-bold shadow-sm"
                                    title="Kirim Resi Massal ke Marketplace">
                                    <i class="fas fa-paper-plane"></i> Kirim Resi (Ship)
                                </button>

                                <a href="{{ route('fulfillment.scan_page') }}"
                                    class="btn btn-dark btn-sm d-inline-flex align-items-center gap-1 fw-semibold shadow-sm">
                                    <i class="fas fa-expand"></i> Scan Kemas
                                </a>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped table-hover border align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 45px;" class="text-center">
                                            <input type="checkbox" id="check-all" class="form-check-input">
                                        </th>
                                        <th>INVOICE / ID</th>
                                        <th>TOKO / CHANNEL</th>
                                        <th>PEMBELI</th>
                                        <th>DETAIL BARANG</th>
                                        <th>KURIR</th>
                                        <th>STATUS PRINT</th>
                                        <th>STATUS KEMAS</th>
                                        <th>AKSI</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($orders as $order)
                                        <tr>
                                            <td class="text-center">
                                                <input type="checkbox" name="ids[]" value="{{ $order->id }}"
                                                    class="form-check-input order-checkbox">
                                            </td>
                                            <td class="font-monospace">
                                                <span
                                                    class="fw-bold text-dark">{{ $order->invoice_number ?? $order->order_marketplace_id }}</span>
                                                <div class="small text-muted mt-1">ID: {{ $order->order_marketplace_id }}
                                                </div>
                                            </td>
                                            <td>
                                                <div class="fw-semibold text-dark">{{ $order->store->store_name }}</div>
                                                <div class="small text-muted mt-1">
                                                    <span class="badge bg-light text-dark border">
                                                        {{ $order->store->channel->name }}
                                                    </span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="fw-semibold text-dark">{{ $order->buyer_name ?? '-' }}</div>
                                                <div class="small text-muted">{{ $order->buyer_phone ?? '' }}</div>
                                            </td>
                                            <td>
                                                <ul class="ps-3 mb-0 small">
                                                    @foreach ($order->items as $item)
                                                        <li class="text-dark">
                                                            <span
                                                                class="text-primary fw-semibold">{{ $item->quantity }}x</span>
                                                            {{ $item->product_name }}
                                                            <span
                                                                class="font-monospace bg-light border px-1 rounded small">
                                                                {{ $item->sku ?? 'No SKU' }}
                                                            </span>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </td>
                                            <td>
                                                <div class="fw-semibold text-dark">{{ $order->courier ?? '-' }}</div>
                                                @if ($order->tracking_number)
                                                    <div class="small font-monospace text-muted mt-1">
                                                        <i class="fas fa-receipt text-secondary"></i>
                                                        {{ $order->tracking_number }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($order->is_printed)
                                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 py-1 px-2 fw-semibold">
                                                        <i class="fas fa-check-circle me-1"></i>Sudah Print
                                                    </span>
                                                    @if ($order->printed_at)
                                                        <div class="small font-monospace text-muted mt-1" style="font-size:0.68rem;">
                                                            {{ $order->printed_at->format('d/m H:i') }}
                                                        </div>
                                                    @endif
                                                @else
                                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 py-1 px-2">
                                                        <i class="fas fa-clock me-1"></i>Belum Print
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($order->packing_status === 'verified')
                                                    <span class="badge bg-success py-2 px-3">
                                                        <i class="fas fa-check-circle"></i> Selesai Scan
                                                    </span>
                                                    @if ($order->packed_at)
                                                        <div class="small text-muted mt-1">
                                                            {{ $order->packed_at->format('d/m H:i') }}
                                                        </div>
                                                    @endif
                                                @elseif($order->packing_status === 'packing')
                                                    <span class="badge bg-warning text-dark py-2 px-3">
                                                        <i class="fas fa-box-open"></i> Sedang Dikemas
                                                    </span>
                                                @else
                                                    <span class="badge bg-light text-muted border py-2 px-3">
                                                        <i class="fas fa-clock"></i> Menunggu
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($order->packing_status === 'verified')
                                                    <div class="d-flex flex-column gap-1">
                                                        <form action="{{ route('orders.ship', $order->id) }}"
                                                            method="POST">
                                                            @csrf
                                                            <button type="submit"
                                                                class="btn btn-success btn-sm w-100 py-1">
                                                                <i class="fas fa-paper-plane"></i> Kirim Resi
                                                            </button>
                                                        </form>
                                                        <a href="{{ route('orders.print', $order->id) }}" target="_blank"
                                                            class="btn btn-outline-secondary btn-sm w-100 py-1">
                                                            <i class="fas fa-print"></i> Cetak Label
                                                        </a>
                                                    </div>
                                                @else
                                                    <a href="{{ route('fulfillment.scan_page', ['invoice' => $order->invoice_number ?? $order->order_marketplace_id]) }}"
                                                        class="btn btn-primary btn-sm w-100 py-1 fw-semibold">
                                                        <i class="fas fa-barcode"></i> Scan & Kemas
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center text-muted p-5">
                                                <i class="fas fa-box-open fs-1 text-muted opacity-25 mb-3 d-block"></i>
                                                Tidak ada pesanan Siap Kirim saat ini.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </form>

                    <div class="mt-4">
                        {{ $orders->appends(request()->query())->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            const batchForm = $('#batch-form');
            const checkAll = $('#check-all');
            const checkboxes = $('.order-checkbox');

            checkAll.on('change', function() {
                checkboxes.prop('checked', this.checked);
            });

            checkboxes.on('change', function() {
                checkAll.prop('checked', checkboxes.length === $('.order-checkbox:checked').length);
            });

            // 1. Cetak Pick List (Daftar Pengambilan Barang) -> Kertas A4 / Standar
            $('#btn-top-pick').on('click', function() {
                batchForm.attr('action', "{{ route('fulfillment.batch_picklist') }}");
                batchForm.attr('method', "GET");
                batchForm.attr('target', "_blank");
                batchForm.submit();
            });

            // 2. Cetak Resi / Label Massal -> Kertas Stiker Thermal
            $('#btn-top-label').on('click', function() {
                const checked = $('.order-checkbox:checked');
                if (checked.length === 0) {
                    if (!confirm(
                            'Tidak ada pesanan yang diceklis. Apakah Anda ingin mencetak resi untuk SELURUH pesanan yang tampil di filter saat ini?'
                        )) {
                        return;
                    }
                }
                batchForm.attr('action', "{{ route('orders.mass_print') }}");
                batchForm.attr('method', "POST");
                batchForm.attr('target', "_blank");
                batchForm.submit();
            });

            // 3. Konfirmasi Packing Selesai
            $('#btn-top-verify').on('click', function() {
                const checked = $('.order-checkbox:checked');
                if (checked.length === 0) {
                    alert(
                        'Pilih minimal satu pesanan dengan mencentang kotak untuk memverifikasi packing.'
                    );
                    return;
                }
                if (confirm('Konfirmasi verifikasi packing massal untuk pesanan terpilih?')) {
                    batchForm.attr('action', "{{ route('fulfillment.batch_verify') }}");
                    batchForm.attr('method', "POST");
                    batchForm.removeAttr('target');
                    batchForm.submit();
                }
            });

            // 4. Kirim Resi ke API Marketplace
            $('#btn-top-ship').on('click', function() {
                const checked = $('.order-checkbox:checked');
                if (checked.length === 0) {
                    alert('Pilih minimal satu pesanan dengan mencentang kotak untuk pengiriman resi.');
                    return;
                }
                if (confirm('Kirim resi massal ke marketplace untuk pesanan terpilih?')) {
                    batchForm.attr('action', "{{ route('fulfillment.batch_ship') }}");
                    batchForm.attr('method', "POST");
                    batchForm.removeAttr('target');
                    batchForm.submit();
                }
            });
        });
    </script>
@endpush
