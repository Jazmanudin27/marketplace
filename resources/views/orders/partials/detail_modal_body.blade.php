<div class="modal-header bg-info bg-opacity-10 py-2 px-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
    <div class="d-flex align-items-center gap-2">
        <h6 class="modal-title fw-bold text-dark mb-0" style="font-size: 0.85rem;">
            <i class="fas fa-receipt text-info me-1"></i>
            {{ $order->invoice_number ?? $order->order_marketplace_id }}
        </h6>
        <span class="badge bg-{{ $order->status_badge ?? 'secondary' }}-subtle text-{{ $order->status_badge ?? 'secondary' }} border border-{{ $order->status_badge ?? 'secondary' }}-subtle text-uppercase px-2 py-0.5" style="font-size: 0.6rem;">
            {{ str_replace('_', ' ', $order->order_status) }}
        </span>
    </div>

    {{-- Action Buttons Toolbar --}}
    <div class="d-flex gap-1.5 align-items-center flex-wrap me-1">
        @if (!in_array($order->order_status, ['SHIPPED', 'CANCELLED', 'DELIVERED']))
            <form action="{{ route('orders.ship', $order->id) }}" method="POST" class="d-inline m-0">
                @csrf
                <button type="submit" class="btn btn-success btn-xs py-1 px-2.5 rounded-2 fw-semibold" style="font-size: 0.7rem;"
                    onclick="this.disabled=true; this.innerHTML='<i class=&quot;fas fa-spinner fa-spin&quot;></i> Memproses...'; this.form.submit();">
                    <i class="fas fa-truck-loading me-1"></i> Kirim Pesanan
                </button>
            </form>

            <button type="button" class="btn btn-danger btn-xs py-1 px-2.5 rounded-2 fw-semibold" style="font-size: 0.7rem;"
                onclick="document.getElementById('modalCancelSection-{{ $order->id }}').classList.toggle('d-none');">
                <i class="fas fa-times-circle me-1"></i> Batalkan Pesanan
            </button>
        @endif

        @if (in_array($order->order_status, ['SHIPPED', 'READY_TO_SHIP']))
            @if (empty($order->tracking_number))
                <form action="{{ route('orders.tracking', $order->id) }}" method="POST" class="d-inline m-0">
                    @csrf
                    <button type="submit" class="btn btn-warning btn-xs py-1 px-2.5 rounded-2 text-dark fw-semibold" style="font-size: 0.7rem;"
                        onclick="this.disabled=true; this.innerHTML='<i class=&quot;fas fa-spinner fa-spin&quot;></i> Menarik...'; this.form.submit();">
                        <i class="fas fa-sync me-1"></i> Tarik Resi
                    </button>
                </form>
            @endif

            <a href="{{ route('orders.print', $order->id) }}" target="_blank"
                class="btn btn-primary btn-xs py-1 px-2.5 text-white rounded-2 fw-semibold" style="font-size: 0.7rem;" data-no-modal="true">
                <i class="fas fa-print me-1"></i> Cetak Invoice
            </a>
        @endif

        <button type="button" class="btn-close ms-2 me-1" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
</div>

<div class="modal-body p-2.5" style="max-height: 85vh; overflow-y: auto; font-size: 0.72rem;">
    {{-- Inline Cancel Order Form --}}
    <div id="modalCancelSection-{{ $order->id }}" class="d-none mb-2.5 p-2 border border-danger rounded-2 bg-danger bg-opacity-10" style="font-size: 0.7rem;">
        <h6 class="fw-bold text-danger mb-1" style="font-size: 0.75rem;"><i class="fas fa-exclamation-triangle me-1"></i> Konfirmasi Pembatalan Pesanan</h6>
        <form action="{{ route('orders.cancel', $order->id) }}" method="POST">
            @csrf
            <div class="mb-1.5">
                <label class="form-label fw-semibold mb-0.5 text-dark" style="font-size: 0.68rem;">Alasan Pembatalan <span class="text-danger">*</span></label>
                <textarea name="cancel_reason" class="form-control form-control-sm" style="font-size: 0.7rem;" rows="2" required placeholder="Contoh: Stok barang di gudang kosong / Buyer meminta cancel..."></textarea>
            </div>
            <div class="d-flex gap-1.5 justify-content-end">
                <button type="button" class="btn btn-secondary btn-xs py-0.5 px-2" style="font-size: 0.68rem;" onclick="document.getElementById('modalCancelSection-{{ $order->id }}').classList.add('d-none')">Batal</button>
                <button type="submit" class="btn btn-danger btn-xs py-0.5 px-2 fw-semibold" style="font-size: 0.68rem;">Ya, Batalkan Pesanan Ini</button>
            </div>
        </form>
    </div>

    <div class="row g-2.5">

        <!-- Left Side: Order & Item Details -->
        <div class="col-lg-7">

            <!-- Order Info Card -->
            <div class="card border shadow-sm mb-2.5 rounded-2">
                <div class="card-header bg-light py-1.5 px-2.5 fw-bold text-dark d-flex justify-content-between align-items-center" style="font-size: 0.75rem;">
                    <span><i class="fas fa-info-circle me-1 text-primary"></i> Informasi Rincian Pesanan</span>
                    <span class="text-muted font-monospace" style="font-size:0.68rem;">ID Toko: {{ $order->order_marketplace_id }}</span>
                </div>
                <div class="card-body p-2">
                    <div class="row g-1.5">
                        @if ($order->order_status === 'CANCELLED')
                            <div class="col-md-12 mb-1.5">
                                <div class="p-2 border border-danger rounded bg-danger bg-opacity-10">
                                    <small class="text-danger d-block text-uppercase fw-bold mb-1" style="font-size: 0.65rem;">
                                        <i class="fas fa-times-circle me-1"></i> Informasi Pembatalan Pesanan
                                    </small>
                                    <div class="row g-1" style="font-size: 0.7rem;">
                                        @if ($order->cancelled_by)
                                            <div class="col-md-6 text-dark">
                                                <span class="text-muted">Dibatalkan Oleh:</span> <strong>{{ $order->cancelled_by }}</strong>
                                            </div>
                                        @endif
                                        <div class="col-md-12 text-dark">
                                            <span class="text-muted">Alasan Pembatalan:</span>
                                            <strong class="text-danger-emphasis">{{ $order->cancel_reason ?? 'Tidak ada detail alasan dari marketplace' }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <style>
                            @media (min-width: 768px) {
                                .border-end-md {
                                    border-right: 1px solid rgba(0,0,0,0.08) !important;
                                }
                            }
                        </style>
                        <div class="row g-3">
                            <!-- Left Column: Informasi Pengiriman -->
                            <div class="col-md-7 pe-md-3 border-end-md">
                                <h6 class="text-uppercase fw-bold text-secondary mb-2" style="font-size: 0.65rem; letter-spacing: 0.5px;">
                                    <i class="fas fa-shipping-fast text-primary me-1.5"></i>Informasi Pengiriman
                                </h6>
                                <div class="table-responsive">
                                    <table class="table table-borderless table-sm mb-0 align-top" style="font-size: 0.72rem;">
                                        <tr>
                                            <td class="text-muted py-1 ps-0" style="width: 130px;">
                                                {{ str_starts_with($order->order_marketplace_id, 'MANUAL-') ? 'Departemen Pengaju' : 'Nama Pembeli' }}
                                            </td>
                                            <td class="py-1 text-dark fw-bold">
                                                @if ($order->customer_id)
                                                    <a href="{{ route('customers.show', $order->customer_id) }}" class="text-decoration-none text-primary fw-bold" target="_blank">
                                                        {{ $order->buyer_name ?? '-' }} <i class="fas fa-external-link-alt ms-1" style="font-size: 0.6rem;"></i>
                                                    </a>
                                                @else
                                                    {{ $order->buyer_name ?? '-' }}
                                                @endif
                                                @if ($order->buyer_email)
                                                    <div class="text-muted font-monospace fw-normal mt-0.5" style="font-size: 0.65rem;">{{ $order->buyer_email }}</div>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted py-1 ps-0">
                                                {{ str_starts_with($order->order_marketplace_id, 'MANUAL-') ? 'Tipe Permintaan' : 'No. Telepon' }}
                                            </td>
                                            <td class="py-1 text-dark font-monospace fw-semibold">
                                                {{ $order->buyer_phone ?? '-' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted py-1 ps-0">
                                                {{ str_starts_with($order->order_marketplace_id, 'MANUAL-') ? 'Tujuan / Detail' : 'Alamat Pengiriman' }}
                                            </td>
                                            <td class="py-1 text-dark-emphasis text-wrap" style="white-space: pre-line; line-height: 1.4;">
                                                {{ $order->shipping_address ?? '-' }}
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <!-- Right Column: Status & Logistik -->
                            <div class="col-md-5 ps-md-3">
                                <h6 class="text-uppercase fw-bold text-secondary mb-2" style="font-size: 0.65rem; letter-spacing: 0.5px;">
                                    <i class="fas fa-info-circle text-primary me-1.5"></i>Status & Logistik
                                </h6>
                                <div class="table-responsive">
                                    <table class="table table-borderless table-sm mb-0 align-top" style="font-size: 0.72rem;">
                                        <tr>
                                            <td class="text-muted py-1 ps-0" style="width: 110px;">Toko / Platform</td>
                                            <td class="py-1 text-dark fw-bold">
                                                {{ $order->store->store_name }}
                                                <span class="badge bg-secondary channel-{{ $order->store->channel->code }} text-uppercase ms-1" style="font-size: 0.6rem;">
                                                    {{ $order->store->channel->name }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted py-1 ps-0">Kurir</td>
                                            <td class="py-1 text-dark fw-bold">
                                                <span class="badge bg-success-subtle text-success border border-success-subtle py-0.5 px-1.5">{{ $order->courier ?? '-' }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted py-1 ps-0">No. Resi</td>
                                            <td class="py-1">
                                                @if ($order->tracking_number)
                                                    <span class="font-monospace fw-bold text-dark bg-warning-subtle py-0.5 px-1.5 rounded border border-warning-subtle">{{ $order->tracking_number }}</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                                @if ($order->package_id)
                                                    <div class="text-muted font-monospace mt-1" style="font-size: 0.6rem;">Pkg ID: {{ $order->package_id }}</div>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted py-1 ps-0">Tgl Pesanan</td>
                                            <td class="py-1 text-dark">
                                                <span class="fw-semibold">{{ $order->order_date ? $order->order_date->format('d M Y, H:i') : '-' }}</span>
                                                @if ($order->paid_at)
                                                    <div class="text-success mt-0.5" style="font-size:0.65rem;">
                                                        <i class="fas fa-check-circle me-1"></i>Dibayar: {{ \Carbon\Carbon::parse($order->paid_at)->format('d M Y, H:i') }}
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted py-1 ps-0">Tgl Cair (Net)</td>
                                            <td class="py-1">
                                                @if ($order->completed_at)
                                                    <span class="fw-bold text-primary">{{ \Carbon\Carbon::parse($order->completed_at)->format('d M Y, H:i') }}</span>
                                                @else
                                                    <span class="text-muted fw-semibold">Belum Cair / Selesai</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @if ($order->payment_method)
                                            <tr>
                                                <td class="text-muted py-1 ps-0">Metode Bayar</td>
                                                <td class="py-1 text-dark">
                                                    <span class="badge bg-info-subtle text-info border border-info-subtle py-0.5 px-1.5">{{ $order->payment_method }}</span>
                                                </td>
                                            </tr>
                                        @endif
                                    </table>
                                </div>
                            </div>

                            @if ($order->buyer_message || $order->seller_note)
                                <div class="col-12 mt-2">
                                    <div class="p-2 border border-warning-subtle rounded-3 bg-warning bg-opacity-10 text-dark" style="font-size: 0.7rem;">
                                        @if ($order->buyer_message)
                                            <div class="mb-1"><strong class="text-warning-emphasis"><i class="fas fa-comment-dots me-1"></i> Pesan Pembeli:</strong> {{ $order->buyer_message }}</div>
                                        @endif
                                        @if ($order->seller_note)
                                            <div><strong class="text-secondary"><i class="fas fa-sticky-note me-1"></i> Catatan Penjual:</strong> {{ $order->seller_note }}</div>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            @if ($order->is_dropship)
                                <div class="col-12 mt-2">
                                    <div class="p-2 border border-warning-subtle rounded-3 bg-warning bg-opacity-10 text-dark" style="font-size: 0.7rem;">
                                        <div class="fw-bold text-warning-emphasis mb-1 small">
                                            <i class="fas fa-shipping-fast me-1.5"></i>Informasi Dropshipper
                                        </div>
                                        <span class="text-muted">Pengirim:</span> <strong class="me-2 text-dark">{{ $order->dropshipper_name ?? '-' }}</strong>
                                        <span class="text-muted">No. Telp:</span> <span class="font-monospace fw-semibold text-dark">{{ $order->dropshipper_phone ?? '-' }}</span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informasi Pembayaran Card -->
            <div class="card border shadow-sm mb-2.5 rounded-2 overflow-hidden">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-1.5 px-2.5 border-bottom" style="font-size: 0.75rem;">
                    <h6 class="mb-0 fw-bold text-dark" style="font-size: 0.75rem;">
                        <i class="fas fa-file-invoice-dollar text-danger me-1.5"></i>Informasi Pembayaran
                    </h6>
                    <a href="{{ route('orders.print', $order->id) }}" target="_blank" class="text-decoration-none small text-primary fw-semibold" data-no-modal="true">
                        Lihat rincian pesanan
                    </a>
                </div>
                <div class="card-body p-2">
                    <div class="table-responsive rounded border mb-2">
                        <table class="table table-sm align-middle mb-0" style="font-size: 0.68rem;">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th class="ps-2 py-1 text-center" style="width: 40px;">No.</th>
                                    <th class="py-1">Produk</th>
                                    <th class="py-1 text-end" style="width: 100px;">Harga Satuan</th>
                                    <th class="py-1 text-end" style="width: 70px;">Jumlah</th>
                                    <th class="py-1 text-end pe-2" style="width: 100px;">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $subtotalPesanan = 0.0;
                                @endphp
                                @foreach ($order->items as $index => $item)
                                    @php
                                        $itemPrice = (float) $item->price;
                                        $itemQty = (int) $item->quantity;
                                        $itemSubtotal = $itemPrice * $itemQty;
                                        $subtotalPesanan += $itemSubtotal;

                                        // Fallback image source
                                        $imgUrl = $item->product_image 
                                            ?: ($item->masterProduct->image ?? ($item->marketplaceProduct->image_url ?? ''));
                                        $imgSrc = '';
                                        if ($imgUrl) {
                                            if (str_starts_with($imgUrl, 'http://') || str_starts_with($imgUrl, 'https://')) {
                                                $imgSrc = $imgUrl;
                                            } else {
                                                $imgSrc = asset('storage/' . $imgUrl);
                                            }
                                        } else {
                                            $imgSrc = 'https://placehold.co/60x60/f8f9fa/a3a3a3?text=Product';
                                        }

                                        // Fallback SKU
                                        $cleanSku = trim($item->sku);
                                        $cleanSellerSku = trim($item->seller_sku);
                                        $displaySku = '';
                                        if ($cleanSellerSku && $cleanSellerSku !== '-') {
                                            $displaySku = $cleanSellerSku;
                                        } elseif ($cleanSku && $cleanSku !== '-') {
                                            $displaySku = $cleanSku;
                                        } elseif ($item->masterProduct && $item->masterProduct->sku) {
                                            $displaySku = $item->masterProduct->sku;
                                        } elseif ($item->marketplaceProduct && $item->marketplaceProduct->masterProduct && $item->marketplaceProduct->masterProduct->sku) {
                                            $displaySku = $item->marketplaceProduct->masterProduct->sku;
                                        }
                                    @endphp
                                    <tr style="border-bottom: 1px solid #f0f0f0;">
                                        <td class="text-center ps-2 text-muted">{{ $index + 1 }}</td>
                                        <td>
                                            <div class="d-flex align-items-center py-1">
                                                <img src="{{ $imgSrc }}" class="img-thumbnail me-2" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;" alt="Product Image">
                                                <div>
                                                    @if ($item->masterProduct && $item->masterProduct->is_preorder)
                                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle small me-1" style="font-size:0.6rem; padding: 1px 4px;">Pre-Order</span>
                                                    @endif
                                                    <span class="text-dark fw-semibold d-block" style="font-size: 0.72rem; line-height: 1.3;">{{ $item->product_name }}</span>
                                                    @if ($item->sku_name)
                                                        <span class="text-muted d-block mt-0.5" style="font-size: 0.65rem;">Variasi: {{ $item->sku_name }}</span>
                                                    @endif
                                                    @if ($displaySku)
                                                        <span class="text-muted d-block mt-0.5" style="font-size: 0.65rem;">Kode Variasi: {{ $displaySku }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end text-dark font-monospace">{{ number_format($itemPrice, 0, ',', '.') }}</td>
                                        <td class="text-end text-dark font-monospace">{{ $itemQty }}</td>
                                        <td class="text-end text-dark font-monospace pe-2 fw-semibold">{{ number_format($itemSubtotal, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Dotted Divider Line --}}
                    <div class="w-100 mb-2" style="border-top: 1px dotted #ccc;"></div>

                    @php
                        $platformFee = (float) ($order->fee_breakdown_details['platform_fee'] ?? 0);
                        $freeShipping = (float) ($order->fee_breakdown_details['free_shipping'] ?? 0);
                        $shippingFee = (float) ($order->shipping_fee ?? 0);
                        
                        // Group remaining admin fee components (service, promo, other)
                        $totalMarketplaceFee = abs((float) $order->marketplace_fee);
                        $layananTambahan = max(0.0, $totalMarketplaceFee - $platformFee - $freeShipping);
                        $estimasiPenghasilan = (float) $order->net_amount;
                        if ($estimasiPenghasilan == 0 && empty($order->financial_breakdown)) {
                            $estimasiPenghasilan = $subtotalPesanan + $shippingFee - $totalMarketplaceFee - $order->refund_amount;
                        }
                    @endphp
 
                    {{-- Summary Grid --}}
                    <div class="row justify-content-end">
                        <div class="col-12 col-md-10 d-flex justify-content-end align-items-stretch">
                            <!-- Labels Column -->
                            <div class="pe-3 text-end d-flex flex-column justify-content-between py-0.5" style="color: #555; font-size: 0.68rem; line-height: 1.8;">
                                <div>Subtotal Pesanan</div>
                                <div>Estimasi Subtotal Ongkos Kirim</div>
                                <div>Biaya Platform <i class="far fa-question-circle text-muted" style="font-size: 0.65rem; cursor: help;" title="Biaya Komisi / Platform Marketplace"></i></div>
                                <div>Biaya Gratis Ongkir XTRA <i class="far fa-question-circle text-muted" style="font-size: 0.65rem; cursor: help;" title="Biaya Layanan Ongkir XTRA / Program Penjual"></i></div>
                                <div>Subtotal Biaya Layanan Tambahan</div>
                                <div>Retur / Refund <i class="far fa-question-circle text-muted" style="font-size: 0.65rem; cursor: help;" title="Pengembalian dana kepada pembeli akibat retur / pengembalian sebagian"></i></div>
                                <div class="text-dark fw-bold mt-0.5" style="font-size: 0.72rem;">Estimasi Total Penghasilan <i class="far fa-question-circle text-muted" style="font-size: 0.65rem; cursor: help;" title="Total pendapatan bersih yang akan dilepas ke saldo penjual"></i></div>
                            </div>
                            
                            <!-- Dotted Vertical Divider Line -->
                            <div style="border-left: 1px dotted #ccc; margin: 0 4px;"></div>
                            
                            <!-- Values Column -->
                            <div class="ps-3 text-end d-flex flex-column justify-content-between py-0.5 font-monospace" style="font-size: 0.68rem; line-height: 1.8; min-width: 130px;">
                                <div class="text-dark">Rp{{ number_format($subtotalPesanan, 0, ',', '.') }} <i class="fas fa-chevron-down text-muted" style="font-size: 0.55rem;"></i></div>
                                <div class="text-dark">Rp{{ number_format($shippingFee, 0, ',', '.') }} <i class="fas fa-chevron-down text-muted" style="font-size: 0.55rem;"></i></div>
                                <div class="text-dark">{{ $platformFee > 0 ? '-Rp' . number_format($platformFee, 0, ',', '.') : 'Rp0' }} <i class="fas fa-chevron-down text-muted" style="font-size: 0.55rem;"></i></div>
                                <div class="text-dark">{{ $freeShipping > 0 ? '-Rp' . number_format($freeShipping, 0, ',', '.') : 'Rp0' }} <i class="fas fa-chevron-down text-muted" style="font-size: 0.55rem;"></i></div>
                                <div class="text-dark">{{ $layananTambahan > 0 ? '-Rp' . number_format($layananTambahan, 0, ',', '.') : 'Rp0' }}</div>
                                <div class="text-danger">{{ $order->refund_amount > 0 ? '-Rp' . number_format($order->refund_amount, 0, ',', '.') : 'Rp0' }}</div>
                                <div class="fw-bold text-danger mt-0.5" style="color: #ee4d2d !important; font-size: 1.05rem !important;">Rp{{ number_format($estimasiPenghasilan, 0, ',', '.') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Right Side: Payment, Store, SPK, Profit -->
        <div class="col-lg-5">

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
