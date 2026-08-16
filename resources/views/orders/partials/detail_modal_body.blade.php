<div class="modal-header bg-info bg-opacity-10 py-3 px-4 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div class="d-flex align-items-center gap-2">
        <h5 class="modal-title fw-bold text-dark fs-6 mb-0">
            <i class="fas fa-receipt text-info me-1.5"></i>
            {{ $order->invoice_number ?? $order->order_marketplace_id }}
        </h5>
        <span class="badge bg-{{ $order->status_badge ?? 'secondary' }}-subtle text-{{ $order->status_badge ?? 'secondary' }} border border-{{ $order->status_badge ?? 'secondary' }}-subtle small text-uppercase px-2 py-1" style="font-size: 0.65rem;">
            {{ str_replace('_', ' ', $order->order_status) }}
        </span>
    </div>

    {{-- Action Buttons Toolbar --}}
    <div class="d-flex gap-2 align-items-center flex-wrap me-1">
        @if (!in_array($order->order_status, ['SHIPPED', 'CANCELLED', 'DELIVERED']))
            <form action="{{ route('orders.ship', $order->id) }}" method="POST" class="d-inline m-0">
                @csrf
                <button type="submit" class="btn btn-success btn-sm px-3 rounded-3 fw-semibold"
                    onclick="this.disabled=true; this.innerHTML='<i class=&quot;fas fa-spinner fa-spin&quot;></i> Memproses...'; this.form.submit();">
                    <i class="fas fa-truck-loading me-1"></i> Kirim Pesanan
                </button>
            </form>

            <button type="button" class="btn btn-danger btn-sm px-3 rounded-3 fw-semibold"
                onclick="document.getElementById('modalCancelSection-{{ $order->id }}').classList.toggle('d-none');">
                <i class="fas fa-times-circle me-1"></i> Batalkan Pesanan
            </button>
        @endif

        @if (in_array($order->order_status, ['SHIPPED', 'READY_TO_SHIP']))
            @if (empty($order->tracking_number))
                <form action="{{ route('orders.tracking', $order->id) }}" method="POST" class="d-inline m-0">
                    @csrf
                    <button type="submit" class="btn btn-warning btn-sm px-3 rounded-3 text-dark fw-semibold"
                        onclick="this.disabled=true; this.innerHTML='<i class=&quot;fas fa-spinner fa-spin&quot;></i> Menarik...'; this.form.submit();">
                        <i class="fas fa-sync me-1"></i> Tarik Resi
                    </button>
                </form>
            @endif

            <a href="{{ route('orders.print', $order->id) }}" target="_blank"
                class="btn btn-primary btn-sm px-3 text-white rounded-3 fw-semibold" data-no-modal="true">
                <i class="fas fa-print me-1"></i> Cetak Invoice
            </a>
        @endif

        <button type="button" class="btn-close ms-3 me-1" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
</div>

<div class="modal-body p-3" style="max-height: 80vh; overflow-y: auto;">
    {{-- Inline Cancel Order Form --}}
    <div id="modalCancelSection-{{ $order->id }}" class="d-none mb-3 p-3 border border-danger rounded-3 bg-danger bg-opacity-10">
        <h6 class="fw-bold text-danger mb-2 small"><i class="fas fa-exclamation-triangle me-1"></i> Konfirmasi Pembatalan Pesanan</h6>
        <form action="{{ route('orders.cancel', $order->id) }}" method="POST">
            @csrf
            <div class="mb-2">
                <label class="form-label small fw-semibold mb-1 text-dark">Alasan Pembatalan <span class="text-danger">*</span></label>
                <textarea name="cancel_reason" class="form-control form-control-sm" rows="2" required placeholder="Contoh: Stok barang di gudang kosong / Buyer meminta cancel..."></textarea>
            </div>
            <div class="d-flex gap-2 justify-content-end">
                <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('modalCancelSection-{{ $order->id }}').classList.add('d-none')">Batal</button>
                <button type="submit" class="btn btn-danger btn-sm fw-semibold">Ya, Batalkan Pesanan Ini</button>
            </div>
        </form>
    </div>

    <div class="row g-3">

        <!-- Left Side: Order & Item Details -->
        <div class="col-lg-7">

            <!-- Order Info Card -->
            <div class="card border shadow-sm mb-3 rounded-3">
                <div class="card-header bg-light py-2 px-3 fw-bold small text-dark d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-info-circle me-1 text-primary"></i> Informasi Rincian Pesanan</span>
                    <span class="text-muted font-monospace small" style="font-size:0.75rem;">ID Toko: {{ $order->order_marketplace_id }}</span>
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

                        <div class="col-md-6">
                            <div class="p-2.5 border rounded h-100 bg-light">
                                <small class="text-muted d-block text-uppercase fw-semibold mb-1" style="font-size: 0.65rem;">
                                    {{ str_starts_with($order->order_marketplace_id, 'MANUAL-') ? 'Departemen Pengaju' : 'Pembeli' }}
                                </small>
                                <span class="fw-bold text-dark small">
                                    @if ($order->customer_id)
                                        <a href="{{ route('customers.show', $order->customer_id) }}" class="text-decoration-none text-primary" target="_blank">
                                            {{ $order->buyer_name ?? '-' }} <i class="fas fa-external-link-alt ms-1 small"></i>
                                        </a>
                                    @else
                                        {{ $order->buyer_name ?? '-' }}
                                    @endif
                                </span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="p-2.5 border rounded h-100 bg-light">
                                <small class="text-muted d-block text-uppercase fw-semibold mb-1" style="font-size: 0.65rem;">
                                    {{ str_starts_with($order->order_marketplace_id, 'MANUAL-') ? 'Tipe Permintaan' : 'No. Telp' }}
                                </small>
                                <span class="font-monospace fw-semibold text-dark small">{{ $order->buyer_phone ?? '-' }}</span>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="p-2.5 border rounded h-100 bg-light">
                                <small class="text-muted d-block text-uppercase fw-semibold mb-1" style="font-size: 0.65rem;">Alamat Pengiriman</small>
                                <span class="fw-semibold text-dark text-wrap small" style="white-space: pre-line;">{{ $order->shipping_address ?? '-' }}</span>
                            </div>
                        </div>

                        @if ($order->is_dropship)
                            <div class="col-md-12">
                                <div class="p-2.5 border border-warning rounded h-100 bg-warning bg-opacity-10">
                                    <small class="text-warning-emphasis d-block text-uppercase fw-bold mb-1" style="font-size: 0.65rem;">
                                        <i class="fas fa-shipping-fast me-1"></i> Informasi Dropshipper
                                    </small>
                                    <div class="row g-1 text-dark small">
                                        <div class="col-md-6"><span class="text-muted">Pengirim:</span> <strong>{{ $order->dropshipper_name ?? '-' }}</strong></div>
                                        <div class="col-md-6"><span class="text-muted">Telp:</span> <strong class="font-monospace">{{ $order->dropshipper_phone ?? '-' }}</strong></div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="col-md-4">
                            <div class="p-2.5 border rounded h-100 bg-light">
                                <small class="text-muted d-block text-uppercase fw-semibold mb-1" style="font-size: 0.65rem;">Kurir</small>
                                <span class="fw-bold text-success small">{{ $order->courier ?? '-' }}</span>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="p-2.5 border rounded h-100 bg-light">
                                <small class="text-muted d-block text-uppercase fw-semibold mb-1" style="font-size: 0.65rem;">No. Resi</small>
                                <span class="font-monospace fw-bold text-warning small">{{ $order->tracking_number ?? '-' }}</span>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="p-2.5 border rounded h-100 bg-light">
                                <small class="text-muted d-block text-uppercase fw-semibold mb-1" style="font-size: 0.65rem;">Tanggal Pesanan</small>
                                <span class="fw-semibold text-dark small">{{ $order->order_date ? $order->order_date->format('d M Y, H:i') : '-' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Items Card -->
            <div class="card border shadow-sm overflow-hidden mb-3 rounded-3">
                <div class="card-header bg-primary bg-opacity-10 py-2 px-3 border-bottom">
                    <h6 class="mb-0 fw-bold text-dark small"><i class="fas fa-box me-1.5 text-primary"></i>Item Pesanan</h6>
                </div>
                <div class="card-body p-2">
                    <div class="table-responsive rounded border">
                        <table class="table table-sm table-striped table-bordered align-middle mb-0" style="font-size:0.8rem;">
                            <thead class="table-light">
                                <tr>
                                    <th>PRODUK</th>
                                    <th>SKU</th>
                                    <th class="text-end">HARGA</th>
                                    <th class="text-center">QTY</th>
                                    <th class="text-end">SUBTOTAL</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($order->items as $item)
                                    <tr>
                                        <td><strong class="text-dark small">{{ $item->product_name }}</strong></td>
                                        <td><code class="text-info font-monospace small">{{ $item->sku ?? '-' }}</code></td>
                                        <td class="text-end font-monospace text-dark">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                                        <td class="text-center text-dark fw-bold">{{ $item->quantity }}</td>
                                        <td class="text-end font-monospace text-primary fw-semibold">Rp {{ number_format($item->total_price, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

        <!-- Right Side: Payment, Store, SPK, Profit -->
        <div class="col-lg-5">

            <!-- Payment Breakdown Card -->
            <div class="card border shadow-sm mb-3 rounded-3">
                <div class="card-header bg-success bg-opacity-10 py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark small"><i class="fas fa-wallet me-1.5 text-success"></i>Rincian Potongan Biaya Marketplace (Shopee / MP Format)</h6>
                    <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25">Dana Cair / Escrow</span>
                </div>
                <div class="card-body p-3" style="font-size: 0.8rem;">
                    @php 
                        $fees = $order->fee_breakdown_details;
                        $fb = $order->financial_breakdown ?? [];
                        $retOrder = $order->returnOrder;
                        $sellerReturnRefund = (float) ($fb['seller_return_refund'] ?? $fb['refund_amount'] ?? ($retOrder ? (float)$retOrder->refund_amount : (in_array(strtoupper($order->order_status), ['RETURNED', 'REFUNDED', 'RETURN']) ? (float)$order->total_amount : 0.0)));
                        $refundAmount = abs($sellerReturnRefund);
                        
                        $absFee = abs($fees['total_fee'] ?? 0);
                        if ($refundAmount >= (float)$order->total_amount && (float)$order->total_amount > 0) {
                            $finalNet = $absFee;
                        } else {
                            $finalNet = max(0.0, (float)$order->total_amount - $refundAmount - $absFee);
                        }
                    @endphp

                    @if ($refundAmount > 0)
                        <div class="alert alert-danger border border-danger d-flex align-items-center py-2 px-3 mb-2 rounded-3" style="font-size: 0.75rem;">
                            <i class="fas fa-undo-alt text-danger fs-5 me-2"></i>
                            <div>
                                <strong class="text-dark">seller_return_refund (Pengembalian Dana):</strong>
                                <span class="text-danger fw-bold ms-1">-Rp {{ number_format($refundAmount, 0, ',', '.') }}</span>
                                <div class="text-muted small">Penghasilan bersih pesanan ini dikurangi potongan pengembalian dana seller.</div>
                            </div>
                        </div>
                    @endif

                    <table class="table table-sm table-bordered align-middle mb-2 font-monospace" style="font-size: 0.75rem;">
                        <thead class="table-light text-center fw-bold">
                            <tr>
                                <th>Biaya Platform</th>
                                <th>Biaya Gratis Ongkir</th>
                                <th>Biaya Layanan</th>
                                <th>Biaya Promosi</th>
                                <th>Biaya Lainnya</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="text-center">
                                <td class="{{ $fees['platform_fee'] < 0 ? 'text-danger fw-bold' : 'text-muted' }}">{{ $fees['platform_fee'] != 0 ? number_format($fees['platform_fee'], 0, ',', '.') : '0' }}</td>
                                <td class="{{ $fees['free_shipping'] < 0 ? 'text-danger fw-bold' : 'text-muted' }}">{{ $fees['free_shipping'] != 0 ? number_format($fees['free_shipping'], 0, ',', '.') : '0' }}</td>
                                <td class="{{ $fees['service_fee'] < 0 ? 'text-danger fw-bold' : 'text-muted' }}">{{ $fees['service_fee'] != 0 ? number_format($fees['service_fee'], 0, ',', '.') : '0' }}</td>
                                <td class="{{ $fees['promo_fee'] < 0 ? 'text-danger fw-bold' : 'text-muted' }}">{{ $fees['promo_fee'] != 0 ? number_format($fees['promo_fee'], 0, ',', '.') : '0' }}</td>
                                <td class="{{ $fees['other_fee'] < 0 ? 'text-danger fw-bold' : 'text-muted' }}">{{ $fees['other_fee'] != 0 ? number_format($fees['other_fee'], 0, ',', '.') : '0' }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="d-flex justify-content-between mb-1 align-items-center">
                        <span class="text-muted">Total Omset Kotor (Nilai Transaksi)</span>
                        <span class="font-monospace text-dark">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                    </div>

                    @if ($refundAmount > 0)
                        <div class="d-flex justify-content-between mb-1 align-items-center">
                            <span class="text-danger fw-semibold">
                                <i class="fas fa-minus-circle me-1"></i>Refund
                            </span>
                            <span class="font-monospace text-danger fw-bold">
                                -Rp {{ number_format($refundAmount, 0, ',', '.') }}
                            </span>
                        </div>
                    @endif

                    <div class="d-flex justify-content-between mb-1 align-items-center">
                        <span class="text-muted">Total Potongan Marketplace</span>
                        <span class="font-monospace text-danger font-bold">
                            {{ $fees['total_fee'] != 0 ? number_format($fees['total_fee'], 0, ',', '.') : 'Rp 0' }}
                        </span>
                    </div>

                    <hr class="my-2 border-dashed opacity-50">

                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-dark">Jumlah Dana Dilepas / Cair (Net)</span>
                        <span class="font-monospace text-success fw-bold fs-6">
                            Rp {{ number_format($finalNet, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Store & Channel Card -->
            <div class="card border shadow-sm mb-3 rounded-3">
                <div class="card-header bg-light py-2 px-3 border-bottom fw-bold small text-dark">
                    <i class="fas fa-store me-1.5 text-primary"></i> Info Toko & Channel
                </div>
                <div class="card-body p-3 small">
                    <div class="d-flex justify-content-between mb-2 align-items-center">
                        <span class="text-muted">Platform</span>
                        <span class="badge bg-secondary text-uppercase small">{{ $order->store->channel->name ?? 'Marketplace' }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">Nama Toko</span>
                        <span class="fw-bold text-dark">{{ $order->store->store_name ?? '-' }}</span>
                    </div>
                </div>
            </div>

            <!-- Profit Summary Card -->
            @php
                $hppTotal = $order->hpp_total;
                $netProfit = $order->net_profit;
                $margin = $order->profit_margin;
                $profitBadge = $netProfit >= 0 ? 'success' : 'danger';
            @endphp
            <div class="card border shadow-sm mb-3 rounded-3">
                <div class="card-header bg-light py-2 px-3 border-bottom fw-bold small text-dark">
                    <i class="fas fa-chart-line me-1.5 text-primary"></i> Analisis Profit Pesanan
                </div>
                <div class="card-body p-3 small">
                    <div class="d-flex justify-content-between mb-1.5 align-items-center">
                        <span class="text-muted">Net Escrow (Penerimaan)</span>
                        <span class="font-monospace fw-semibold text-dark">Rp {{ number_format($order->net_amount, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1.5 align-items-center">
                        <span class="text-muted">HPP Total Produk</span>
                        <span class="font-monospace text-danger">
                            {{ $hppTotal > 0 ? '- Rp ' . number_format($hppTotal, 0, ',', '.') : 'HPP belum diset' }}
                        </span>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold">Estimasi Net Profit</span>
                        <span class="font-monospace fw-bold text-{{ $profitBadge }} fs-6">
                            {{ $netProfit >= 0 ? '' : '-' }}Rp {{ number_format(abs($netProfit), 0, ',', '.') }} ({{ $margin }}%)
                        </span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<div class="modal-footer bg-light py-2 px-3 border-top d-flex justify-content-between align-items-center">
    <div class="d-flex gap-2">
        <a href="{{ route('orders.show', $order->id) }}" class="btn btn-outline-secondary btn-sm rounded-3" data-no-modal="true">
            <i class="fas fa-external-link-alt me-1"></i> Buka Halaman Penuh
        </a>
        @if (in_array($order->order_status, ['SHIPPED', 'READY_TO_SHIP']))
            <a href="{{ route('orders.print', $order->id) }}" target="_blank" class="btn btn-outline-primary btn-sm rounded-3">
                <i class="fas fa-print me-1"></i> Cetak Invoice
            </a>
        @endif
    </div>
    <button type="button" class="btn btn-secondary btn-sm px-3 rounded-3" data-bs-dismiss="modal">Tutup</button>
</div>
