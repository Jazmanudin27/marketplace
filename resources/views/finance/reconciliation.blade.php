@extends('layouts.app')
@section('title', 'Rekonsiliasi Keuangan')
@section('page-title', 'Rekonsiliasi & Margin')
@section('content')

{{-- KPI Cards --}}
<div class="row g-3 mb-4">
    <!-- Total Cair -->
    <div class="col-12 col-md-6">
        <div class="card border rounded shadow-sm bg-white">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                    <i class="fas fa-wallet fs-5"></i>
                </div>
                <div class="min-width-0">
                    <div class="fw-bold fs-4 text-dark">Rp {{ number_format($totalNetPage, 0, ',', '.') }}</div>
                    <div class="text-muted small">Total Cair (Net Amount)</div>
                </div>
            </div>
        </div>
    </div>
    <!-- Total Selisih -->
    <div class="col-12 col-md-6">
        <div class="card border rounded shadow-sm bg-white {{ $totalDiscrepancyPage > 0 ? 'border-warning' : '' }}">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 {{ $totalDiscrepancyPage > 0 ? 'bg-warning bg-opacity-10 text-warning' : 'bg-primary bg-opacity-10 text-primary' }}" style="width: 48px; height: 48px;">
                    <i class="fas fa-balance-scale fs-5"></i>
                </div>
                <div class="min-width-0">
                    <div class="fw-bold fs-4 {{ $totalDiscrepancyPage > 0 ? 'text-warning' : 'text-primary' }}">Rp {{ number_format($totalDiscrepancyPage, 0, ',', '.') }}</div>
                    <div class="text-muted small">Total Selisih (Discrepancy)</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border rounded shadow-sm bg-white mb-4">
    <div class="card-body py-2 px-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label form-label-sm fw-semibold mb-1">Status Rekonsiliasi</label>
                <select name="recon_status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <option value="pending" {{ request('recon_status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="investigating" {{ request('recon_status') === 'investigating' ? 'selected' : '' }}>Investigating</option>
                    <option value="resolved" {{ request('recon_status') === 'resolved' ? 'selected' : '' }}>Resolved</option>
                </select>
            </div>
            <div class="col-12 col-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm px-3">
                    <i class="fas fa-search me-1"></i> Filter
                </button>
                @if (request()->filled('recon_status'))
                    <a href="{{ route('finance.reconciliation') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card border rounded shadow-sm bg-white">
    <div class="card-body">
        <h5 class="fw-bold text-dark mb-3"><i class="fas fa-file-invoice-dollar text-primary me-2"></i>Detail Pesanan Selesai (Completed)</h5>
        <div class="table-responsive">
            <table class="table table-striped table-hover border align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Invoice / ID</th>
                        <th>Toko (Channel)</th>
                        <th>Penjualan (A)</th>
                        <th>Ongkir (B)</th>
                        <th>Biaya Admin (C)</th>
                        <th>Pencairan (Actual)</th>
                        <th>Selisih</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    <tr class="{{ $order->has_discrepancy ? 'table-danger' : '' }}">
                        <td class="font-monospace">
                            <a href="{{ route('orders.show', $order) }}" class="text-primary fw-bold text-decoration-none">
                                {{ $order->invoice_number ?? $order->order_marketplace_id }}
                            </a>
                            <button class="btn btn-xs btn-outline-info ms-2 py-0 px-1" type="button" data-bs-toggle="collapse" data-bs-target="#fb-detail-{{ $order->id }}" aria-expanded="false" aria-controls="fb-detail-{{ $order->id }}" style="font-size: 0.65rem;">
                                <i class="fas fa-eye me-1"></i>Rincian
                            </button><br>
                            <small class="text-muted">{{ $order->order_date->format('d/m/y H:i') }}</small>
                        </td>
                        <td>
                            <span class="text-dark fw-semibold">{{ $order->store->store_name }}</span><br>
                            <span class="badge bg-light text-dark border">{{ $order->store->channel->name }}</span>
                        </td>
                        <td class="font-monospace">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                        <td class="font-monospace text-muted">- Rp {{ number_format($order->shipping_fee, 0, ',', '.') }}</td>
                        <td class="font-monospace {{ $order->is_high_fee ? 'text-danger' : 'text-muted' }}">
                            - Rp {{ number_format($order->marketplace_fee, 0, ',', '.') }}<br>
                            <small>({{ $order->fee_percentage }}%)</small>
                        </td>
                        <td class="font-monospace fw-bold text-success">Rp {{ number_format($order->net_amount, 0, ',', '.') }}</td>
                        
                        <td class="font-monospace fw-bold {{ $order->discrepancy_amount > 0 ? 'text-danger' : 'text-dark' }}">
                            {{ $order->discrepancy_amount != 0 ? 'Rp '.number_format($order->discrepancy_amount, 0, ',', '.') : '-' }}
                        </td>
                        
                        <td>
                            <div class="d-flex flex-column gap-1">
                                @if(($order->recon_status ?? 'pending') === 'resolved')
                                    <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Resolved</span>
                                @elseif(($order->recon_status ?? 'pending') === 'investigating')
                                    <span class="badge bg-info"><i class="fas fa-search me-1"></i>Investigating</span>
                                @else
                                    @if($order->has_discrepancy)
                                        <span class="badge bg-danger"><i class="fas fa-exclamation-triangle me-1"></i>Discrepancy</span>
                                    @elseif($order->is_high_fee)
                                        <span class="badge bg-warning text-dark"><i class="fas fa-percent me-1"></i>High Fee</span>
                                    @else
                                        <span class="badge bg-light text-dark border">Matched</span>
                                    @endif
                                @endif
                                
                                <button type="button" class="btn btn-xs btn-outline-secondary mt-1 py-1 px-2 btn-edit-recon" 
                                    data-id="{{ $order->id }}"
                                    data-status="{{ $order->recon_status ?? 'pending' }}"
                                    data-notes="{{ $order->recon_notes ?? '' }}"
                                    data-invoice="{{ $order->invoice_number ?? $order->order_marketplace_id }}"
                                    data-channel="{{ $order->store->channel->name }}"
                                    data-expected="{{ $order->total_amount - $order->shipping_fee - $order->marketplace_fee }}"
                                    data-actual="{{ $order->net_amount }}"
                                    data-discrepancy="{{ $order->discrepancy_amount }}"
                                    style="font-size: 0.65rem;">
                                    <i class="fas fa-edit me-1"></i>Recon
                                </button>
                            </div>
                        </td>
                    </tr>

                    @php
                        $fb = $order->financial_breakdown ?? [];
                    @endphp
                    <tr class="collapse" id="fb-detail-{{ $order->id }}">
                        <td colspan="8" class="p-3 bg-light">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="fw-bold mb-2 text-dark small"><i class="fas fa-info-circle me-1 text-success"></i> Rincian Pendapatan</div>
                                    <table class="table table-sm table-borderless text-muted mb-0" style="font-size:0.75rem;">
                                        <tr>
                                            <td>Harga Produk (Product Amount)</td>
                                            <td class="text-end text-dark font-monospace">Rp {{ number_format($fb['original_price'] ?? $order->total_amount, 0, ',', '.') }}</td>
                                        </tr>
                                        <tr>
                                            <td>Ongkos Kirim (Shipping Fee)</td>
                                            <td class="text-end text-dark font-monospace">Rp {{ number_format($fb['actual_shipping_fee'] ?? ($fb['buyer_paid_shipping_fee'] ?? $order->shipping_fee), 0, ',', '.') }}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-4">
                                    <div class="fw-bold mb-2 text-dark small"><i class="fas fa-percentage me-1 text-danger"></i> Potongan & Komisi</div>
                                    <table class="table table-sm table-borderless text-muted mb-0" style="font-size:0.75rem;">
                                        <tr>
                                            <td>Komisi Platform (Platform Commission)</td>
                                            <td class="text-end text-danger font-monospace">- Rp {{ number_format(($fb['service_fee'] ?? 0) + ($fb['seller_transaction_fee'] ?? 0) ?: $order->marketplace_fee, 0, ',', '.') }}</td>
                                        </tr>
                                        <tr>
                                            <td>Komisi Affiliate (Affiliate Commission)</td>
                                            <td class="text-end text-danger font-monospace">- Rp {{ number_format($fb['commission_fee'] ?? 0, 0, ',', '.') }}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-4">
                                    <div class="fw-bold mb-2 text-dark small"><i class="fas fa-tags me-1 text-warning"></i> Voucher & Penyesuaian</div>
                                    <table class="table table-sm table-borderless text-muted mb-0" style="font-size:0.75rem;">
                                        <tr>
                                            <td>Subsidi Voucher (Voucher Subsidy)</td>
                                            <td class="text-end text-success font-monospace">+ Rp {{ number_format($fb['voucher_from_shopee'] ?? 0, 0, ',', '.') }}</td>
                                        </tr>
                                        <tr>
                                            <td>Penyesuaian (Adjustment)</td>
                                            <td class="text-end font-monospace {{ ($fb['adjustment_amount'] ?? 0) < 0 ? 'text-danger' : 'text-success' }}">
                                                {{ ($fb['adjustment_amount'] ?? 0) < 0 ? '-' : '+' }} Rp {{ number_format(abs($fb['adjustment_amount'] ?? 0), 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted py-5">Belum ada pesanan yang selesai</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $orders->links() }}</div>
    </div>
</div>

{{-- Reconciliation Update Modal --}}
<div class="modal fade" id="reconModal" tabindex="-1" aria-labelledby="reconModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="recon-form" method="POST" action="">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="reconModalLabel"><i class="fas fa-balance-scale text-primary me-2"></i>Rekonsiliasi Transaksi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="alert alert-light border mb-3">
                        <div class="small text-muted mb-1">Invoice / ID</div>
                        <div class="fw-bold text-dark mb-2" id="modal-invoice"></div>
                        <div class="row g-2 small">
                            <div class="col-6">
                                <span class="text-muted">Expected Net:</span><br>
                                <strong class="text-success font-monospace" id="modal-expected"></strong>
                            </div>
                            <div class="col-6">
                                <span class="text-muted">Actual Net:</span><br>
                                <strong class="text-primary font-monospace" id="modal-actual"></strong>
                            </div>
                        </div>
                        <div class="mt-2 text-danger small fw-bold" id="modal-discrepancy-wrapper">
                            Selisih: <span id="modal-discrepancy"></span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Status Penyelesaian</label>
                        <select name="recon_status" id="modal-status-select" class="form-select form-select-sm">
                            <option value="pending">Pending</option>
                            <option value="investigating">Investigating</option>
                            <option value="resolved">Resolved</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Catatan Rekonsiliasi</label>
                        <textarea name="recon_notes" id="modal-notes-input" class="form-control form-control-sm" rows="3" placeholder="Masukkan detail tindak lanjut (misal: pengajuan klaim selisih ongkir disetujui...)"></textarea>
                    </div>

                    {{-- Claim CS Copy Tool --}}
                    <div class="p-3 bg-warning bg-opacity-10 border border-warning rounded" id="claim-assistant-card">
                        <div class="small text-warning-emphasis fw-bold mb-1"><i class="fas fa-clipboard-list me-1"></i> Asisten Klaim CS</div>
                        <p class="text-muted small mb-2" style="font-size:0.75rem; line-height:1.3;">Salin draf aduan otomatis di bawah ini untuk dikirimkan ke Seller Service Center.</p>
                        <textarea id="claim-template" class="form-control form-control-sm mb-2" rows="3" style="font-size:0.72rem; background:white; color: #333;" readonly></textarea>
                        <button type="button" class="btn btn-sm btn-outline-warning w-100 py-1" id="btn-copy-template" style="font-size:0.72rem;">
                            <i class="fas fa-copy me-1"></i> Salin Template Pesan
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3">Simpan Perubahan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const reconModalElement = document.getElementById('reconModal');
    let reconModal = null;
    if (reconModalElement) {
        reconModal = new bootstrap.Modal(reconModalElement);
    }
    
    document.querySelectorAll('.btn-edit-recon').forEach(button => {
        button.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            const status = this.getAttribute('data-status');
            const notes = this.getAttribute('data-notes');
            const invoice = this.getAttribute('data-invoice');
            const channel = this.getAttribute('data-channel');
            const expected = parseFloat(this.getAttribute('data-expected') || 0);
            const actual = parseFloat(this.getAttribute('data-actual') || 0);
            const discrepancy = parseFloat(this.getAttribute('data-discrepancy') || 0);
            
            // Set Form Action
            document.getElementById('recon-form').action = `/reconciliation/${id}/update`;
            
            // Populate Modal Info
            document.getElementById('modal-invoice').innerText = invoice;
            document.getElementById('modal-expected').innerText = 'Rp ' + expected.toLocaleString('id-ID');
            document.getElementById('modal-actual').innerText = 'Rp ' + actual.toLocaleString('id-ID');
            document.getElementById('modal-status-select').value = status;
            document.getElementById('modal-notes-input').value = notes || '';
            
            if (discrepancy !== 0) {
                document.getElementById('modal-discrepancy-wrapper').style.display = 'block';
                document.getElementById('modal-discrepancy').innerText = 'Rp ' + discrepancy.toLocaleString('id-ID');
            } else {
                document.getElementById('modal-discrepancy-wrapper').style.display = 'none';
            }
            
            // Generate Claim Message Template
            const claimMsg = `Halo CS ${channel},\n` +
                `Saya ingin mengajukan komplain selisih biaya pencairan pesanan dengan No. Invoice/Pesanan #${invoice}.\n` +
                `Setelah dihitung, nominal pencairan seharusnya adalah Rp ${expected.toLocaleString('id-ID')}, namun pencairan aktual yang diterima adalah Rp ${actual.toLocaleString('id-ID')}.\n` +
                `Terdapat selisih discrepancy sebesar Rp ${discrepancy.toLocaleString('id-ID')}. Mohon bantuannya untuk meninjau kembali. Terima kasih.`;
            
            document.getElementById('claim-template').value = claimMsg;
            
            // Show Modal
            if (reconModal) {
                reconModal.show();
            }
        });
    });
    
    const btnCopy = document.getElementById('btn-copy-template');
    if (btnCopy) {
        btnCopy.addEventListener('click', function () {
            const claimText = document.getElementById('claim-template');
            claimText.select();
            document.execCommand('copy');
            
            // Visual feedback
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-check me-1"></i> Berhasil Disalin!';
            this.classList.remove('btn-outline-warning');
            this.classList.add('btn-success', 'text-white');
            
            setTimeout(() => {
                this.innerHTML = originalText;
                this.classList.remove('btn-success', 'text-white');
                this.classList.add('btn-outline-warning');
            }, 1500);
        });
    }
});
</script>
@endsection
