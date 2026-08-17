@extends('layouts.app')
@section('title', 'Detail Pesanan')
@section('page-title', 'Detail Pesanan')

@section('content')
    <div class="container-fluid p-0">
        <!-- Back Button -->
        <div class="mb-3">
            <a href="{{ route('orders.index') }}" class="btn btn-secondary btn-sm rounded-3">
                <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar Pesanan
            </a>
        </div>

        <!-- Main Grid Layout -->
        <div class="row g-3">

            <!-- Left Side: Order & Item Details -->
            <div class="col-lg-8">

                <!-- Order Detail Card -->
                <div class="card border shadow-sm mb-3">
                    <div class="card-header bg-info bg-opacity-10 d-flex justify-content-between align-items-center p-3 border-bottom">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="fas fa-receipt me-2 text-info"></i>{{ $order->invoice_number ?? $order->order_marketplace_id }}
                        </h6>
                        <div class="d-flex gap-2 align-items-center">
                            <span class="badge bg-{{ $order->status_badge ?? 'secondary' }}-subtle text-{{ $order->status_badge ?? 'secondary' }} border border-{{ $order->status_badge ?? 'secondary' }}-subtle small text-uppercase" style="font-size: 0.7rem; padding: 0.25em 0.5em;">
                                {{ str_replace('_', ' ', $order->order_status) }}
                            </span>

                            @if (!in_array($order->order_status, ['SHIPPED', 'CANCELLED', 'DELIVERED']))
                                <form action="{{ route('orders.ship', $order->id) }}" method="POST" class="d-inline m-0">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm px-3 rounded-3"
                                        onclick="this.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> Memproses...'; this.disabled=true; this.form.submit();">
                                        <i class="fas fa-truck-loading me-1"></i> Kirim Pesanan
                                    </button>
                                </form>
                                <button type="button" class="btn btn-danger btn-sm px-3 rounded-3" data-bs-toggle="modal" data-bs-target="#cancelOrderModal">
                                    <i class="fas fa-times-circle me-1"></i> Batalkan Pesanan
                                </button>
                            @endif

                            @if (in_array($order->order_status, ['SHIPPED', 'READY_TO_SHIP']))
                                @if (empty($order->tracking_number))
                                    <form action="{{ route('orders.tracking', $order->id) }}" method="POST"
                                        class="d-inline m-0">
                                        @csrf
                                        <button type="submit" class="btn btn-warning btn-sm px-3 rounded-3"
                                            onclick="this.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> Menarik...'; this.disabled=true; this.form.submit();">
                                            <i class="fas fa-sync me-1"></i> Tarik Resi
                                        </button>
                                    </form>
                                @endif

                                <a href="{{ route('orders.print', $order->id) }}" target="_blank"
                                    class="btn btn-primary btn-sm px-3 text-white rounded-3">
                                    <i class="fas fa-print me-1"></i> Cetak Invoice
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="card-body p-3">


                        <div class="row g-2">
                            @if ($order->order_status === 'CANCELLED')
                                <div class="col-md-12 mb-2">
                                    <div class="p-3 border border-danger rounded bg-danger bg-opacity-10">
                                        <small class="text-danger d-block text-uppercase fw-bold mb-2" style="font-size: 0.7rem;">
                                            <i class="fas fa-times-circle me-1"></i> Informasi Pembatalan Pesanan
                                        </small>
                                        <div class="row g-2">
                                            @if ($order->cancelled_by)
                                                <div class="col-md-6 text-dark small">
                                                    <span class="text-muted">Dibatalkan Oleh:</span> <strong>{{ $order->cancelled_by }}</strong>
                                                </div>
                                            @endif
                                            <div class="col-md-12 text-dark small">
                                                <span class="text-muted">Alasan Pembatalan:</span>
                                                <strong class="text-danger-emphasis">{{ $order->cancel_reason ?? 'Tidak ada detail alasan dari marketplace' }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @php
                                $refundValAmt = $order->refund_amount;
                                $isRefundedOrder = $refundValAmt > 0 || in_array(strtoupper($order->order_status), ['RETURNED', 'REFUNDED', 'RETURN']);
                            @endphp

                            @if ($isRefundedOrder)
                                <div class="col-md-12 mb-2">
                                    <div class="p-3 border border-danger rounded bg-danger bg-opacity-10">
                                        <small class="text-danger d-block text-uppercase fw-bold mb-1" style="font-size: 0.7rem;">
                                            <i class="fas fa-undo-alt me-1"></i> InformasI Pengembalian Dana (Refund / Retur)
                                        </small>
                                        <div class="row g-2">
                                            <div class="col-md-6 text-dark small">
                                                <span class="text-muted">Status Transaksi:</span> <strong class="text-danger text-uppercase">{{ str_replace('_', ' ', $order->order_status) }}</strong>
                                            </div>
                                            <div class="col-md-6 text-dark small">
                                                <span class="text-muted">Nominal Refund Pembeli:</span> <strong class="font-monospace text-danger">- Rp {{ number_format($refundValAmt, 0, ',', '.') }}</strong>
                                            </div>
                                            @if ($order->returnOrder && $order->returnOrder->reason)
                                                <div class="col-md-12 text-dark small mt-1">
                                                    <span class="text-muted">Alasan Pengembalian:</span> <strong>{{ $order->returnOrder->reason }}</strong>
                                                </div>
                                            @elseif ($order->cancel_reason)
                                                <div class="col-md-12 text-dark small mt-1">
                                                    <span class="text-muted">Catatan/Alasan:</span> <strong>{{ $order->cancel_reason }}</strong>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif
                            <div class="col-md-6">
                                <div class="p-3 border rounded h-100 bg-light">
                                    @if(str_starts_with($order->order_marketplace_id, 'MANUAL-'))
                                        <small class="text-muted d-block text-uppercase fw-semibold mb-1" style="font-size: 0.65rem;">Departemen Pengaju</small>
                                    @else
                                        <small class="text-muted d-block text-uppercase fw-semibold mb-1" style="font-size: 0.65rem;">Pembeli</small>
                                    @endif
                                    <span class="fw-bold text-dark small">
                                        @if ($order->customer_id)
                                            <a href="{{ route('customers.show', $order->customer_id) }}"
                                                class="text-decoration-none text-primary">
                                                {{ $order->buyer_name ?? '-' }} <i class="fas fa-external-link-alt ms-1 small"></i>
                                            </a>
                                        @else
                                            {{ $order->buyer_name ?? '-' }}
                                        @endif
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 border rounded h-100 bg-light">
                                    @if(str_starts_with($order->order_marketplace_id, 'MANUAL-'))
                                        <small class="text-muted d-block text-uppercase fw-semibold mb-1" style="font-size: 0.65rem;">Tipe Permintaan</small>
                                        <span class="fw-bold text-primary small">{{ $order->buyer_phone ?? '-' }}</span>
                                    @else
                                        <small class="text-muted d-block text-uppercase fw-semibold mb-1" style="font-size: 0.65rem;">No. Telp</small>
                                        <span class="font-monospace fw-semibold text-dark small">{{ $order->buyer_phone ?? '-' }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="p-3 border rounded h-100 bg-light">
                                    @if(str_starts_with($order->order_marketplace_id, 'MANUAL-'))
                                        <small class="text-muted d-block text-uppercase fw-semibold mb-1" style="font-size: 0.65rem;">Tujuan / Detail Pengiriman</small>
                                    @else
                                        <small class="text-muted d-block text-uppercase fw-semibold mb-1" style="font-size: 0.65rem;">Alamat Pengiriman</small>
                                    @endif
                                    <span class="fw-semibold text-dark text-wrap small" style="white-space: pre-line;">{{ $order->shipping_address ?? '-' }}</span>
                                </div>
                            </div>
                            @if ($order->is_dropship)
                                <div class="col-md-12">
                                    <div class="p-3 border border-warning rounded h-100 bg-warning bg-opacity-10">
                                        <small class="text-warning-emphasis d-block text-uppercase fw-bold mb-2" style="font-size: 0.65rem;">
                                            <i class="fas fa-shipping-fast me-1"></i> Informasi Dropshipper
                                        </small>
                                        <div class="row g-2">
                                            <div class="col-md-6 text-dark small">
                                                <span class="text-muted">Nama Pengirim:</span> <strong>{{ $order->dropshipper_name ?? '-' }}</strong>
                                            </div>
                                            <div class="col-md-6 text-dark small">
                                                <span class="text-muted">No. Telepon:</span> <strong class="font-monospace">{{ $order->dropshipper_phone ?? '-' }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            <div class="col-md-3">
                                <div class="p-3 border rounded h-100 bg-light">
                                    <small class="text-muted d-block text-uppercase fw-semibold mb-1" style="font-size: 0.65rem;">Kurir</small>
                                    <span class="fw-bold text-success small">{{ $order->courier ?? '-' }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3 border rounded h-100 bg-light">
                                    <small class="text-muted d-block text-uppercase fw-semibold mb-1" style="font-size: 0.65rem;">No. Resi</small>
                                    <span class="font-monospace fw-bold text-warning small text-truncate d-block">{{ $order->tracking_number ?? '-' }}</span>
                                    @if ($order->package_id)
                                        <div class="text-muted font-monospace small text-truncate" style="font-size: 0.65rem;">Pkg: {{ $order->package_id }}</div>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3 border rounded h-100 bg-light">
                                    <small class="text-muted d-block text-uppercase fw-semibold mb-1" style="font-size: 0.65rem;">Tanggal Pesanan</small>
                                    <span class="fw-semibold text-dark small">{{ $order->order_date ? $order->order_date->format('d M Y, H:i') : '-' }}</span>
                                    @if ($order->paid_at)
                                        <div class="text-success small" style="font-size:0.65rem;">Bayar: {{ \Carbon\Carbon::parse($order->paid_at)->format('d M Y, H:i') }}</div>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3 border rounded h-100 bg-light">
                                    <small class="text-muted d-block text-uppercase fw-semibold mb-1" style="font-size: 0.65rem;">Tanggal Cair</small>
                                    <span class="fw-bold text-primary small">{{ $order->completed_at ? \Carbon\Carbon::parse($order->completed_at)->format('d M Y, H:i') : 'Belum Cair' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Items Card -->
                <div class="card border shadow-sm overflow-hidden mb-3">
                    <div class="card-header bg-primary bg-opacity-10 d-flex align-items-center p-3 border-bottom">
                        <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-box me-2 text-primary"></i>Item Pesanan (Rincian Produk API)</h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="table-responsive rounded border">
                            <table class="table table-sm table-striped table-bordered align-middle mb-0">
                                <thead>
                                    <tr class="small text-center fw-bold">
                                        <th>PRODUK & VARIAN</th>
                                        <th>SKU / SKU ID</th>
                                        <th>HARGA ASLI</th>
                                        <th>DISKON TOKO</th>
                                        <th>HARGA JUAL</th>
                                        <th>QTY</th>
                                        <th>SUBTOTAL</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($order->items as $item)
                                        <tr>
                                            <td>
                                                <strong class="text-dark small d-block">{{ $item->product_name }}</strong>
                                                @if ($item->sku_name)
                                                    <span class="badge bg-secondary-subtle text-secondary border small">{{ $item->sku_name }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <code class="text-info font-monospace small d-block">{{ $item->seller_sku ?? $item->sku ?? '-' }}</code>
                                                @if ($item->sku_id)
                                                    <span class="text-muted font-monospace small" style="font-size:0.65rem;">ID: {{ $item->sku_id }}</span>
                                                @endif
                                            </td>
                                            <td class="text-end font-monospace text-muted small">
                                                {{ $item->original_price > 0 ? 'Rp ' . number_format($item->original_price, 0, ',', '.') : '-' }}
                                            </td>
                                            <td class="text-end font-monospace text-danger small">
                                                {{ $item->seller_discount > 0 ? '-Rp ' . number_format($item->seller_discount, 0, ',', '.') : '-' }}
                                            </td>
                                            <td class="text-end font-monospace text-dark small fw-semibold">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                                            <td class="text-center text-dark small fw-bold">{{ $item->quantity }}</td>
                                            <td class="text-end font-monospace text-primary small fw-bold">Rp {{ number_format($item->total_price, 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Side: Pay Breakdown, Store, Profit, Tracking -->
            <div class="col-lg-4">

                <!-- Payment Breakdown Card -->
                <div class="card border shadow-sm mb-3">
                    <div class="card-header bg-primary bg-opacity-10 p-3 border-bottom">
                        <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-wallet me-2 text-primary"></i>Ringkasan Pembayaran</h6>
                    </div>
                    <div class="card-body p-3">
                        @php 
                            $discountAmt = (float) $order->discount_amount;
                            $netSales = (float) $order->total_amount;
                            $grossSubtotal = $discountAmt > 0 ? ($netSales + $discountAmt) : $netSales;
                            $feeAmt = abs((float) $order->marketplace_fee);
                            $refundAmount = (float) $order->refund_amount;
                            $finalNet = (float) $order->net_amount;
                        @endphp

                        @if ($discountAmt > 0)
                            <div class="d-flex justify-content-between mb-2 align-items-center">
                                <span class="text-muted small">Subtotal Harga Produk (Sebelum Diskon)</span>
                                <span class="font-monospace text-dark fw-bold small">Rp {{ number_format($grossSubtotal, 0, ',', '.') }}</span>
                            </div>

                            <div class="d-flex justify-content-between mb-2 align-items-center">
                                <span class="text-danger small">
                                    <i class="fas fa-tag me-1"></i>Diskon Toko (Seller Voucher / Discount)
                                </span>
                                <span class="font-monospace text-danger fw-bold small">-Rp {{ number_format($discountAmt, 0, ',', '.') }}</span>
                            </div>
                        @endif

                        <div class="d-flex justify-content-between mb-2 align-items-center {{ $discountAmt > 0 ? 'pt-2 border-top' : '' }}">
                            <span class="text-dark fw-bold small">Total Nilai Transaksi Penjualan (Net Sales)</span>
                            <span class="font-monospace text-dark fw-bold small">Rp {{ number_format($netSales, 0, ',', '.') }}</span>
                        </div>

                        <div class="d-flex justify-content-between mb-2 align-items-center">
                            <span class="text-danger small">
                                <i class="fas fa-cut me-1"></i>Total Potongan Marketplace / Fee Admin
                            </span>
                            <span class="font-monospace text-danger fw-bold small">-Rp {{ number_format($feeAmt, 0, ',', '.') }}</span>
                        </div>

                        @if ($refundAmount > 0)
                            <div class="d-flex justify-content-between mb-2 align-items-center">
                                <span class="text-danger small fw-semibold">
                                    <i class="fas fa-undo-alt me-1"></i>Total Refund / Pengembalian Dana
                                </span>
                                <span class="font-monospace text-danger fw-bold small">-Rp {{ number_format($refundAmount, 0, ',', '.') }}</span>
                            </div>
                        @endif

                        <hr class="my-2 border-dashed opacity-50">

                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-dark">Jumlah Dana Dilepas / Cair (Net)</span>
                            <span class="font-monospace text-success fw-bold fs-6">
                                Rp {{ number_format($finalNet, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Store Info Card -->
                <div class="card border shadow-sm mb-3">
                    <div class="card-header bg-primary bg-opacity-10 p-3 border-bottom">
                        <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-store me-2 text-primary"></i>Info Toko</h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between mb-2.5 align-items-center">
                            <span class="text-muted small">Platform</span>
                            <span class="badge bg-secondary channel-{{ $order->store->channel->code }} text-uppercase small">
                                {{ $order->store->channel->name }}
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Nama Toko</span>
                            <span class="fw-bold text-dark small">{{ $order->store->store_name }}</span>
                        </div>
                    </div>
                </div>

                <!-- TikTok / Tokopedia Financial Statement Excel Breakdown Card -->
                @php
                    $fb = $order->financial_breakdown ?? [];
                    $stmtList = $fb['statement_transactions'] ?? $fb['statement_transaction_list'] ?? [];
                    $st0 = (is_array($stmtList) && !empty($stmtList[0]) && is_array($stmtList[0])) ? $stmtList[0] : $fb;

                    $getFee = function(array $keys, $fallback = 0) use ($st0) {
                        foreach ($keys as $k) {
                            if (isset($st0[$k]) && $st0[$k] !== '' && $st0[$k] !== null) {
                                return (float) $st0[$k];
                            }
                        }
                        return (float) $fallback;
                    };

                    $preorderFeeVal = $getFee(['preorder_service_fee_amount', 'preorder_fee_amount', 'preorder_service_fee', 'preorder_fee', 'pre_order_service_fee_amount', 'pre_order_service_fee']);
                    $platformCommVal = $getFee(['platform_commission_amount', 'platform_commission', 'commission_amount', 'commission_fee']);
                    $growthXtraVal = $getFee(['growth_xtra_fee_amount', 'growth_program_fee_amount', 'free_shipping_fee_amount', 'growth_xtra_fee', 'free_shipping_service_fee_amount']);
                    $transFeeVal = $getFee(['transaction_fee_amount', 'order_processing_fee_amount', 'transaction_fee', 'order_processing_fee']);
                    $affiliateCommVal = $getFee(['affiliate_commission_amount', 'affiliate_ads_commission_amount', 'affiliate_commission']);
                    $dynamicCommVal = $getFee(['dynamic_commission_amount', 'dynamic_commission']);
                    $actualShippingVal = $getFee(['actual_shipping_fee_amount', 'actual_shipping_fee']);
                    $returnShippingVal = $getFee(['actual_return_shipping_fee_amount', 'return_shipping_fee_amount', 'actual_return_shipping_fee', 'return_shipping_fee']);
                    $logisticsFeeVal = $getFee(['shipping_cost_amount', 'shipping_cost', 'shipping_service_fee_amount', 'logistics_service_fee_amount']);
                    $totalFeeVal = $getFee(['fee_amount', 'total_fee_amount', 'total_fee'], $order->marketplace_fee);

                    // Intelligent fee balancing: assign unassigned fee difference to pre-order service fee if total admin fee is higher than sum of known sub-fees
                    $knownSum = abs($platformCommVal) + abs($growthXtraVal) + abs($transFeeVal) + abs($affiliateCommVal) + abs($dynamicCommVal) + abs($actualShippingVal) + abs($returnShippingVal) + abs($logisticsFeeVal);
                    $unassignedDiff = abs($totalFeeVal) - $knownSum;
                    if ($preorderFeeVal == 0 && $unassignedDiff > 10) {
                        $preorderFeeVal = -$unassignedDiff;
                    }
                @endphp
                <div class="card border shadow-sm mb-3 rounded-3">
                    <div class="card-header bg-dark bg-opacity-10 py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-dark small">
                            <i class="fas fa-file-excel me-1.5 text-success"></i>Detail Statement Excel TikTok / Tokopedia
                        </h6>
                        <button class="btn btn-sm btn-outline-dark py-0 px-2 font-monospace" style="font-size:0.7rem;" type="button" data-bs-toggle="collapse" data-bs-target="#tiktokExcelCollapsePage-{{ $order->id }}">
                            <i class="fas fa-list me-1"></i>Tampilkan / Sembunyikan Detail
                        </button>
                    </div>
                    <div class="collapse {{ !empty($st0['settlement_amount']) ? 'show' : '' }}" id="tiktokExcelCollapsePage-{{ $order->id }}">
                        <div class="card-body p-2" style="font-size: 0.75rem;">
                            <div class="table-responsive rounded border" style="max-height: 350px; overflow-y: auto;">
                                <table class="table table-sm table-striped table-bordered align-middle mb-0 font-monospace" style="font-size:0.72rem;">
                                    <thead class="table-dark text-center sticky-top">
                                        <tr>
                                            <th>Field (Format Resmi Excel TikTok Seller Center)</th>
                                            <th>Nilai</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr><td>ID Pesanan/Penyesuaian</td><td>{{ $order->order_marketplace_id }}</td></tr>
                                        <tr><td>Jenis transaksi</td><td>{{ str_replace('_', ' ', $order->order_status) }}</td></tr>
                                        <tr><td>Waktu pemesanan</td><td>{{ $order->order_date ? $order->order_date->format('Y/m/d H:i') : '-' }}</td></tr>
                                        <tr><td>Waktu pembayaran pesanan</td><td>{{ $order->paid_at ? \Carbon\Carbon::parse($order->paid_at)->format('Y/m/d H:i') : '-' }}</td></tr>
                                        <tr><td>Mata uang</td><td>IDR</td></tr>
                                        <tr class="table-secondary fw-bold"><td>Subtotal sebelum diskon</td><td>Rp {{ number_format((float)($st0['gross_sales_amount'] ?? $st0['original_total_product_price'] ?? ($order->total_amount + $order->discount_amount)), 0, ',', '.') }}</td></tr>
                                        <tr class="text-danger"><td>Diskon penjual</td><td>-Rp {{ number_format(abs((float)($st0['seller_discount_amount'] ?? $st0['seller_discount'] ?? $order->discount_amount)), 0, ',', '.') }}</td></tr>
                                        <tr class="fw-bold"><td>Subtotal setelah diskon penjual</td><td>Rp {{ number_format((float)($st0['after_seller_discounts_subtotal_amount'] ?? $st0['revenue_amount'] ?? $order->total_amount), 0, ',', '.') }}</td></tr>
                                        
                                        @if ((float)($st0['customer_refund_amount'] ?? $st0['gross_sales_refund_amount'] ?? $order->refund_amount) > 0)
                                            <tr class="table-warning text-danger fw-bold"><td>Subtotal pengembalian dana sebelum diskon penjual</td><td>-Rp {{ number_format(abs((float)($st0['gross_sales_refund_amount'] ?? $st0['customer_refund_amount'] ?? $order->refund_amount)), 0, ',', '.') }}</td></tr>
                                            <tr class="text-success"><td>Pengembalian dana diskon penjual</td><td>+Rp {{ number_format(abs((float)($st0['seller_discount_refund_amount'] ?? 0)), 0, ',', '.') }}</td></tr>
                                            <tr class="table-danger text-danger fw-bold"><td>Subtotal pengembalian dana setelah diskon penjual</td><td>-Rp {{ number_format(abs((float)($st0['customer_refund_amount'] ?? $order->refund_amount)), 0, ',', '.') }}</td></tr>
                                        @endif

                                        <tr class="text-danger"><td>Biaya komisi platform</td><td>{{ $platformCommVal != 0 ? '-Rp ' . number_format(abs($platformCommVal), 0, ',', '.') : '0' }}</td></tr>
                                        <tr class="text-danger"><td>Biaya layanan pre-order</td><td>{{ $preorderFeeVal != 0 ? '-Rp ' . number_format(abs($preorderFeeVal), 0, ',', '.') : '0' }}</td></tr>
                                        <tr class="text-danger"><td>Biaya layanan Program Bebas Ongkir</td><td>{{ $growthXtraVal != 0 ? '-Rp ' . number_format(abs($growthXtraVal), 0, ',', '.') : '0' }}</td></tr>
                                        <tr class="text-danger"><td>Biaya pemrosesan pesanan</td><td>{{ $transFeeVal != 0 ? '-Rp ' . number_format(abs($transFeeVal), 0, ',', '.') : '0' }}</td></tr>
                                        <tr class="text-danger"><td>Komisi Afiliasi</td><td>{{ $affiliateCommVal != 0 ? '-Rp ' . number_format(abs($affiliateCommVal), 0, ',', '.') : '0' }}</td></tr>
                                        <tr class="text-danger"><td>Komisi dinamis</td><td>{{ $dynamicCommVal != 0 ? '-Rp ' . number_format(abs($dynamicCommVal), 0, ',', '.') : '0' }}</td></tr>
                                        <tr class="text-danger"><td>Ongkir yang ditalangi penyedia jasa logistik</td><td>{{ $actualShippingVal != 0 ? '-Rp ' . number_format(abs($actualShippingVal), 0, ',', '.') : '0' }}</td></tr>
                                        <tr class="text-danger"><td>Ongkir pengembalian barang (ditanggung pembeli)</td><td>{{ $returnShippingVal != 0 ? '-Rp ' . number_format(abs($returnShippingVal), 0, ',', '.') : '0' }}</td></tr>
                                        <tr class="text-danger"><td>Biaya layanan logistik</td><td>{{ $logisticsFeeVal != 0 ? '-Rp ' . number_format(abs($logisticsFeeVal), 0, ',', '.') : '0' }}</td></tr>
                                        <tr class="table-danger text-danger fw-bold"><td>Total Biaya / Potongan Admin</td><td>-Rp {{ number_format(abs($totalFeeVal), 0, ',', '.') }}</td></tr>
                                        
                                        <tr><td>Pembayaran oleh pembeli</td><td>Rp {{ number_format((float)($st0['customer_payment_amount'] ?? $st0['total_amount'] ?? $order->total_amount), 0, ',', '.') }}</td></tr>
                                        @if ((float)($st0['customer_order_refund_amount'] ?? 0) > 0)
                                            <tr class="text-danger"><td>Pengembalian dana pembeli</td><td>-Rp {{ number_format(abs((float)$st0['customer_order_refund_amount']), 0, ',', '.') }}</td></tr>
                                        @endif
                                        <tr><td>Diskon platform</td><td>Rp {{ number_format((float)($st0['platform_discount_amount'] ?? $st0['platform_discount'] ?? 0), 0, ',', '.') }}</td></tr>
                                        <tr><td>Ongkir yang ditanggung pembeli sebelum diskon</td><td>Rp {{ number_format((float)($st0['original_shipping_fee'] ?? 0), 0, ',', '.') }}</td></tr>
                                        <tr><td>Ongkir yang ditanggung platform</td><td>-Rp {{ number_format(abs((float)($st0['platform_shipping_fee_discount_amount'] ?? $st0['shipping_fee_platform_discount'] ?? 0)), 0, ',', '.') }}</td></tr>
                                        <tr class="table-success fw-bold text-success fs-6">
                                            <td>Jumlah penyelesaian pembayaran (Dana Cair Net)</td>
                                            <td>Rp {{ number_format((float)($st0['settlement_amount'] ?? $order->net_amount), 0, ',', '.') }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                @if (auth()->user()->isSuperAdmin() || auth()->user()->role === 'admin' || auth()->user()->hasAnyPermission(['spks.index', 'spks.show', 'spks.create']))
                <!-- SPK Produksi Card -->
                <div class="card border shadow-sm mb-3">
                    <div class="card-header bg-primary bg-opacity-10 p-3 border-bottom">
                        <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-tools me-2 text-primary"></i>SPK / Produksi</h6>
                    </div>
                    <div class="card-body p-3">
                        @if ($order->spks->isNotEmpty())
                            <div class="list-group list-group-flush small">
                                @foreach ($order->spks as $spk)
                                    <div class="list-group-item px-0 py-2 border-0 border-bottom">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            @can('spks.show')
                                            <div class="d-flex align-items-center gap-2">
                                                <a href="{{ route('spks.show', $spk->id) }}" class="fw-bold text-primary text-decoration-none">
                                                    {{ $spk->no_spk }}
                                                </a>
                                                <a href="{{ route('spks.print', $spk->id) }}" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2 rounded-2" style="font-size: 11px;" title="Cetak SPK">
                                                    <i class="fas fa-print me-1"></i> Cetak
                                                </a>
                                            </div>
                                            @else
                                            <span class="fw-bold text-dark">{{ $spk->no_spk }}</span>
                                            @endcan
                                            <span class="badge bg-secondary-subtle text-secondary small border">
                                                {{ $spk->tanggal->format('d M Y') }}
                                            </span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="text-muted">Deadline: <strong>{{ $spk->deadline->format('d M Y') }}</strong></span>
                                        </div>
                                        @if ($spk->items->isNotEmpty())
                                            <div class="mt-1 ps-2 border-start border-primary" style="font-size: 0.75rem;">
                                                @foreach ($spk->items as $spkItem)
                                                    <div class="d-flex justify-content-between text-dark">
                                                        <span>{{ $spkItem->nama_produk }} x{{ $spkItem->quantity }}</span>
                                                        <span class="badge bg-{{ $spkItem->status === 'Selesai' ? 'success' : ($spkItem->status === 'Belum Mulai' ? 'warning' : 'info') }} py-0.5" style="font-size: 0.65rem;">
                                                            {{ $spkItem->status }}
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                            @can('spks.create')
                            <div class="mt-2 text-center">
                                <a href="{{ route('spks.create', ['order_id' => $order->id]) }}" class="btn btn-outline-primary btn-sm w-100 py-1 rounded-3">
                                    <i class="fas fa-plus me-1"></i> Buat SPK Tambahan
                                </a>
                            </div>
                            @endcan
                        @else
                            <p class="text-muted small mb-3">Pesanan ini belum memiliki Surat Perintah Kerja (SPK) produksi.</p>
                            @can('spks.create')
                            @if ($order->hasPreorderItems() || str_starts_with($order->order_marketplace_id, 'MANUAL-'))
                                <a href="{{ route('spks.create', ['order_id' => $order->id]) }}" class="btn btn-sm btn-primary w-100 fw-bold py-1.5 rounded-3 text-white">
                                    <i class="fas fa-tools me-1"></i> Buat SPK Produksi
                                </a>
                            @else
                                <div class="alert alert-light py-2 px-3 m-0 small border">
                                    <i class="fas fa-info-circle me-1 text-muted"></i> Ini adalah produk ready stock. Anda tetap bisa membuat SPK jika ingin diproduksi khusus.
                                    <a href="{{ route('spks.create', ['order_id' => $order->id]) }}" class="btn btn-xs btn-outline-secondary w-100 mt-2 py-1">
                                        <i class="fas fa-tools me-1"></i> Buat SPK Kustom
                                    </a>
                                </div>
                            @endif
                            @endcan
                        @endif
                    </div>
                </div>
                @endif

                <!-- Profit Analysis Card -->
                @php
                    $order->load('items.masterProduct');
                    $hppTotal = $order->hpp_total;
                    $actualEscrow = ($isRefundedOrder && $refundValAmt >= $order->total_amount) ? 0.0 : (float)$order->net_amount;
                    $netProfit = $actualEscrow - $hppTotal;
                    $margin = $order->total_amount > 0 ? round(($netProfit / $order->total_amount) * 100, 2) : 0;
                    $profitBadge = $netProfit >= 0 ? 'success' : 'danger';
                @endphp
                <div class="card border shadow-sm mb-3">
                    <div class="card-header bg-primary bg-opacity-10 p-3 border-bottom">
                        <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-chart-line me-2 text-primary"></i>Analisis Profit</h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between mb-2 align-items-center">
                            <span class="text-muted small">Pendapatan Bersih (Escrow)</span>
                            <span class="font-monospace fw-semibold small {{ $isRefundedOrder ? 'text-danger' : 'text-dark' }}">Rp {{ number_format($actualEscrow, 0, ',', '.') }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 align-items-center">
                            <span class="text-muted small">HPP Total Item</span>
                            <span class="font-monospace text-danger small">
                                @if ($hppTotal > 0)
                                    - Rp {{ number_format($hppTotal, 0, ',', '.') }}
                                @else
                                    <span class="text-muted small">HPP belum diset</span>
                                @endif
                            </span>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold small">Net Profit</span>
                            <span class="font-monospace fw-bold text-{{ $profitBadge }} fs-5">
                                {{ $netProfit >= 0 ? '' : '-' }}Rp {{ number_format(abs($netProfit), 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 align-items-center">
                            <span class="text-muted small">Margin</span>
                            <span class="fw-bold text-{{ $profitBadge }} small">{{ $margin }}%</span>
                        </div>
                        @if ($hppTotal <= 0)
                            <div class="alert alert-warning py-2 px-3 m-0 mt-3 small shadow-sm d-flex align-items-start border-warning border-opacity-25 bg-warning-subtle text-warning-emphasis">
                                <i class="fas fa-info-circle me-2 mt-1"></i>
                                <div>
                                    Set <strong>Harga Pokok (HPP)</strong> di Master Produk agar profit dihitung akurat.
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Real-time Tracking Card (Shopee only + tracked status) -->
                @if (
                    $order->store->channel->code === 'shopee' &&
                        !in_array($order->order_status, ['UNPAID', 'READY_TO_SHIP', 'CANCELLED']))
                    <div class="card border shadow-sm mb-3">
                        <div class="card-header bg-info bg-opacity-10 d-flex justify-content-between align-items-center p-3 border-bottom cursor-pointer"
                            onclick="loadTracking()" id="tracking-toggle">
                            <h6 class="mb-0 fw-bold text-dark">
                                <i class="fas fa-shipping-fast me-2 text-info"></i>Tracking Real-time
                            </h6>
                            <button class="btn btn-outline-info btn-sm rounded-3" id="btn-load-tracking">
                                <i class="fas fa-sync" id="tracking-spin"></i> Muat Tracking
                            </button>
                        </div>
                        <div class="card-body p-3" id="tracking-panel" style="display:none;">
                            <div id="tracking-loading" class="text-center text-muted py-4">
                                <i class="fas fa-spinner fa-spin fa-2x"></i><br>
                                <small class="mt-2 d-block">Mengambil data dari Shopee...</small>
                            </div>
                            <div id="tracking-result" style="display:none;"></div>
                            <div id="tracking-error" style="display:none;"
                                class="alert alert-danger py-2 text-center small"></div>
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function loadTracking() {
                const panel = document.getElementById('tracking-panel');
                const loading = document.getElementById('tracking-loading');
                const result = document.getElementById('tracking-result');
                const errDiv = document.getElementById('tracking-error');
                const spin = document.getElementById('tracking-spin');
                const btn = document.getElementById('btn-load-tracking');

                if (panel.style.display === 'block') {
                    panel.style.display = 'none';
                    return;
                }

                panel.style.display = 'block';
                loading.style.display = 'block';
                result.style.display = 'none';
                errDiv.style.display = 'none';
                spin.className = 'fas fa-spinner fa-spin';
                btn.disabled = true;

                fetch('{{ route('orders.tracking.detail', $order->id) }}', {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(r => r.json())
                    .then(data => {
                        loading.style.display = 'none';
                        spin.className = 'fas fa-map-marker-alt';
                        btn.disabled = false;

                        if (data.error) {
                            errDiv.textContent = '⚠️ ' + data.error;
                            errDiv.style.display = 'block';
                            return;
                        }

                        const info = data.tracking_info || {};
                        const details = info.tracking_info || [];
                        const carrier = info.first_mile_tracking_number || '{{ $order->tracking_number ?? '-' }}';

                        let html = `<div class="p-1">
                            <div class="mb-3 small text-muted">
                                <i class="fas fa-barcode me-1"></i> No. Resi: <strong class="text-dark">${carrier}</strong>
                            </div>`;

                        if (details.length === 0) {
                            html += `<div class="text-center text-muted py-3">
                                <i class="fas fa-clock fa-2x mb-2 opacity-50"></i><br>
                                <small>Belum ada update tracking dari kurir.</small>
                            </div>`;
                        } else {
                            html += `<div class="tracking-timeline">`;
                            details.forEach((item, idx) => {
                                const isFirst = idx === 0;
                                const tsDate = item.seconds ? new Date(item.seconds * 1000) : null;
                                const dateStr = tsDate ? tsDate.toLocaleString('id-ID', {
                                    day: '2-digit',
                                    month: 'short',
                                    year: 'numeric',
                                    hour: '2-digit',
                                    minute: '2-digit'
                                }) : '';
                                html += `
                                <div class="tracking-step ${isFirst ? 'active' : ''}">
                                    <div class="tracking-dot ${isFirst ? 'dot-active' : ''}"></div>
                                    <div class="tracking-content">
                                        <div class="tracking-desc">${item.description || '-'}</div>
                                        ${dateStr ? `<div class="tracking-time"><i class="fas fa-clock me-1"></i>${dateStr}</div>` : ''}
                                    </div>
                                </div>`;
                            });
                            html += `</div>`;
                        }
                        html += `</div>`;
                        result.innerHTML = html;
                        result.style.display = 'block';
                    })
                    .catch(err => {
                        loading.style.display = 'none';
                        btn.disabled = false;
                        spin.className = 'fas fa-sync';
                        errDiv.textContent = '⚠️ Gagal menghubungi server: ' + err.message;
                        errDiv.style.display = 'block';
                    });
            }
        </script>
    @endpush

    @push('styles')
        <style>
            .border-dashed {
                border-style: dashed !important;
            }

            .tracking-timeline {
                padding-left: 0.5rem;
            }

            .tracking-step {
                display: flex;
                gap: 1rem;
                padding-bottom: 1.25rem;
                position: relative;
            }

            .tracking-step:not(:last-child)::after {
                content: '';
                position: absolute;
                left: 9px;
                top: 20px;
                bottom: 0;
                width: 2px;
                background: #dee2e6;
            }

            .tracking-step.active::after {
                background: #0d6efd;
            }

            .tracking-dot {
                width: 20px;
                height: 20px;
                border-radius: 50%;
                flex-shrink: 0;
                background: #dee2e6;
                margin-top: 2px;
                position: relative;
                z-index: 1;
                border: 3px solid #ffffff;
                box-shadow: 0 0 0 2px #dee2e6;
            }

            .tracking-dot.dot-active {
                background: #0d6efd;
                box-shadow: 0 0 0 3px rgba(13, 110, 253, .25);
            }

            .tracking-content {
                flex: 1;
            }

            .tracking-desc {
                font-weight: 600;
                font-size: 0.875rem;
                color: #212529;
            }

            .tracking-time {
                font-size: 0.75rem;
                color: #6c757d;
                margin-top: 2px;
            }
        </style>
    @endpush

    <!-- Cancel Order Modal -->
    <div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-labelledby="cancelOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('orders.cancel', $order->id) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold text-danger" id="cancelOrderModalLabel">
                            <i class="fas fa-exclamation-triangle me-1"></i> Batalkan Pesanan
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-muted">Apakah Anda yakin ingin membatalkan pesanan ini secara manual? Stok produk yang dialokasikan akan dikembalikan secara otomatis.</p>
                        
                        <div class="mb-3">
                            <label for="cancel_reason" class="form-label fw-bold small">Alasan Pembatalan <span class="text-danger">*</span></label>
                            <textarea name="cancel_reason" id="cancel_reason" class="form-control" rows="3" required placeholder="Contoh: Stok barang di gudang kosong / Buyer meminta cancel..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm rounded-3" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-danger btn-sm rounded-3">Ya, Batalkan Pesanan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
