@extends('v2.layouts.app')

@section('title', 'Detail Pesanan ' . $order->order_number)

@section('content')

{{-- ── Alert Notifications ── --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 mb-3" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 mb-3" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 mb-3" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- ── Page Header ── --}}
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2 mb-0">
            <i class="bi bi-receipt text-info fs-5"></i> Detail Pesanan: {{ $order->order_number }}
        </h1>
        <p class="text-muted small mb-0" style="font-size: 0.78rem;">Rincian pesanan saluran {{ ucfirst($order->channel_type) }} untuk pelanggan {{ $order->customer_name }}.</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        @if($order->remaining_amount > 0)
            <button type="button" class="btn btn-sm btn-success text-white py-1.5 px-3 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalCatatPembayaran">
                <i class="bi bi-cash-stack me-1"></i> + Catat Pembayaran
            </button>
        @else
            <button type="button" class="btn btn-sm btn-outline-success py-1.5 px-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalCatatPembayaran">
                <i class="bi bi-plus-circle me-1"></i> Tambah Pembayaran
            </button>
        @endif
        <a href="{{ route('v2.dropship.edit', $order->id) }}" class="btn btn-sm btn-outline-warning text-dark py-1.5 px-3 fw-semibold">
            <i class="bi bi-pencil-square me-1"></i> Edit Pesanan
        </a>
        <a href="{{ route('v2.dropship.index') }}" class="btn btn-sm btn-outline-secondary py-1.5 px-3 fw-semibold">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
        </a>
    </div>
</div>

<div class="row g-4 mb-4">
    {{-- Info Card --}}
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100" style="border: 1px solid #e2e8f0 !important;">
            <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-3.5">
                <h6 class="fw-bold text-dark mb-0" style="font-size:0.95rem;">
                    <i class="bi bi-info-circle text-primary me-1"></i> Informasi Pesanan
                </h6>
                @if($order->payment_status === 'paid' || ($order->paid_amount >= $order->total_amount && $order->total_amount > 0))
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 fw-bold">
                        <i class="bi bi-check-circle-fill me-1"></i>LUNAS
                    </span>
                @elseif($order->payment_status === 'partial' || ($order->paid_amount > 0 && $order->paid_amount < $order->total_amount))
                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle rounded-pill px-2.5 py-1 fw-bold">
                        <i class="bi bi-pie-chart-fill me-1 text-warning"></i>DICICIL
                    </span>
                @else
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2.5 py-1 fw-bold">
                        <i class="bi bi-clock-history me-1"></i>BELUM LUNAS
                    </span>
                @endif
            </div>

            <table class="table table-sm table-borderless mb-0" style="font-size:0.84rem;">
                <tr>
                    <td class="text-muted py-2" style="width: 40%;">No. Nota</td>
                    <td class="fw-bold font-monospace text-primary py-2">: {{ $order->order_number }}</td>
                </tr>
                <tr>
                    <td class="text-muted py-2">Saluran</td>
                    <td class="py-2">: 
                        @if($order->channel_type === 'lazada')
                            <span class="badge bg-dark-subtle text-dark border border-dark-subtle rounded-pill px-2.5 py-0.5 fw-bold">
                                <i class="bi bi-bag-fill me-1 text-danger"></i>Lazada
                            </span>
                        @else
                            <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-2.5 py-0.5 fw-bold">
                                <i class="bi bi-truck me-1"></i>Dropship
                            </span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="text-muted py-2">Status Bayar</td>
                    <td class="py-2">:
                        @if($order->payment_status === 'paid' || ($order->paid_amount >= $order->total_amount && $order->total_amount > 0))
                            <span class="text-success fw-bold">Lunas</span>
                        @elseif($order->payment_status === 'partial' || ($order->paid_amount > 0 && $order->paid_amount < $order->total_amount))
                            <span class="text-warning fw-bold">Dicicil (DP)</span>
                        @else
                            <span class="text-danger fw-bold">Belum Lunas</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="text-muted py-2">Tanggal Pesanan</td>
                    <td class="fw-semibold text-dark py-2">: {{ $order->order_date ? $order->order_date->format('d/m/Y') : '-' }}</td>
                </tr>
                <tr>
                    <td class="text-muted py-2">Nama Pelanggan</td>
                    <td class="fw-bold text-dark py-2">: {{ $order->customer_name }}</td>
                </tr>
                <tr>
                    <td class="text-muted py-2">No. Telp / HP</td>
                    <td class="text-dark py-2">: {{ $order->customer_phone ?: '-' }}</td>
                </tr>
                <tr>
                    <td class="text-muted py-2">Keterangan</td>
                    <td class="text-dark py-2">: {{ $order->notes ?: '-' }}</td>
                </tr>
                <tr>
                    <td class="text-muted py-2">Dicatat Oleh</td>
                    <td class="text-dark py-2">: {{ $order->user ? $order->user->name : 'Sistem' }}</td>
                </tr>
                <tr>
                    <td class="text-muted py-2">Dibuat Pada</td>
                    <td class="text-muted py-2">: {{ $order->created_at ? $order->created_at->format('d/m/Y H:i') : '-' }}</td>
                </tr>
            </table>

            <div class="p-3 bg-light rounded-3 border mt-4">
                <div class="d-flex justify-content-between align-items-center text-muted small mb-1">
                    <span>Total Kuantitas:</span>
                    <strong class="text-dark font-monospace">{{ number_format($order->total_qty) }} PCS</strong>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted small">Total Tagihan:</span>
                    <strong class="text-dark font-monospace">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</strong>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-1.5">
                    <span class="text-muted small">Sudah Dibayar:</span>
                    <strong class="text-success font-monospace">Rp {{ number_format($order->paid_amount, 0, ',', '.') }}</strong>
                </div>
                <hr class="my-1.5 border-secondary-subtle">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-dark small">Sisa Tagihan:</span>
                    <strong class="{{ $order->remaining_amount > 0 ? 'text-danger' : 'text-success' }} fs-5 fw-bold font-monospace">
                        Rp {{ number_format($order->remaining_amount, 0, ',', '.') }}
                    </strong>
                </div>
            </div>
        </div>
    </div>

    {{-- Detail Produk Card --}}
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100" style="border: 1px solid #e2e8f0 !important;">
            <h6 class="fw-bold text-dark border-bottom pb-3 mb-3.5" style="font-size:0.95rem;">
                <i class="bi bi-box-seam text-success me-1"></i> Rincian Produk ({{ $order->items->count() }} Jenis)
            </h6>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.83rem;">
                    <thead class="bg-light text-secondary text-uppercase fw-semibold border-bottom" style="font-size: 0.72rem;">
                        <tr>
                            <th class="ps-3 py-2" style="width: 45%;">PRODUK &amp; SKU</th>
                            <th class="text-center py-2" style="width: 15%;">KUANTITAS</th>
                            <th class="text-end py-2" style="width: 20%;">HARGA SATUAN</th>
                            <th class="text-end pe-3 py-2" style="width: 20%;">JUMLAH (RP)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $it)
                            <tr>
                                <td class="ps-3 py-2.5">
                                    <span class="fw-bold text-dark d-block">{{ $it->product_name }}</span>
                                    <small class="font-monospace text-muted">{{ $it->sku ?: '-' }}</small>
                                </td>
                                <td class="text-center font-monospace py-2.5">
                                    <span class="badge bg-secondary-subtle text-secondary px-2.5 py-1 fw-bold rounded-pill">
                                        {{ number_format($it->quantity) }} PCS
                                    </span>
                                </td>
                                <td class="text-end font-monospace py-2.5">
                                    Rp {{ number_format($it->unit_price, 0, ',', '.') }}
                                </td>
                                <td class="text-end pe-3 font-monospace fw-bold text-success py-2.5">
                                    Rp {{ number_format($it->subtotal, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-light fw-bold">
                        <tr>
                            <td class="ps-3 py-2.5 text-uppercase">TOTAL KESELURUHAN</td>
                            <td class="text-center font-monospace py-2.5">{{ number_format($order->total_qty) }} PCS</td>
                            <td></td>
                            <td class="text-end pe-3 font-monospace text-success fs-6 py-2.5">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ── Riwayat Pembayaran Card ── --}}
<div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4" style="border: 1px solid #e2e8f0 !important;">
    <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-3">
        <div>
            <h6 class="fw-bold text-dark mb-0" style="font-size:0.95rem;">
                <i class="bi bi-clock-history text-primary me-1"></i> Riwayat Pembayaran
            </h6>
            <small class="text-muted" style="font-size:0.76rem;">Catatan transaksi uang masuk / pelunasan pesanan ini.</small>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary py-1 px-3 fw-semibold rounded-2" data-bs-toggle="modal" data-bs-target="#modalCatatPembayaran">
            <i class="bi bi-plus-lg me-1"></i> Catat Pembayaran
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:0.83rem;">
            <thead class="bg-light text-secondary text-uppercase fw-semibold border-bottom" style="font-size:0.72rem;">
                <tr>
                    <th class="ps-3 py-2" style="width: 15%;">NO. PEMBAYARAN</th>
                    <th class="py-2 text-center" style="width: 12%;">TANGGAL</th>
                    <th class="py-2 text-end" style="width: 15%;">NOMINAL (RP)</th>
                    <th class="py-2 text-center" style="width: 15%;">METODE / TUJUAN</th>
                    <th class="py-2" style="width: 20%;">CATATAN / REF</th>
                    <th class="py-2" style="width: 13%;">DICATAT OLEH</th>
                    <th class="pe-3 py-2 text-center" style="width: 10%;">AKSI</th>
                </tr>
            </thead>
            <tbody>
                @forelse($order->payments as $pay)
                    <tr>
                        <td class="ps-3 py-2.5 font-monospace fw-bold text-primary">
                            {{ $pay->payment_number }}
                        </td>
                        <td class="text-center py-2.5 text-secondary">
                            <i class="bi bi-calendar-check me-1 text-muted"></i>
                            {{ $pay->payment_date ? $pay->payment_date->format('d/m/Y') : '-' }}
                        </td>
                        <td class="text-end py-2.5 font-monospace fw-bold text-success">
                            Rp {{ number_format($pay->amount, 0, ',', '.') }}
                        </td>
                        <td class="text-center py-2.5">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-0.5 fw-semibold" style="font-size:0.72rem;">
                                {{ $pay->payment_method_label }}
                            </span>
                            @if($pay->payment_destination)
                                <small class="text-muted d-block" style="font-size:0.7rem;">{{ $pay->payment_destination }}</small>
                            @endif
                        </td>
                        <td class="py-2.5">
                            @if($pay->reference_number)
                                <span class="d-block small font-monospace text-dark"><i class="bi bi-hash me-0.5"></i>{{ $pay->reference_number }}</span>
                            @endif
                            @if($pay->notes)
                                <small class="text-muted">{{ $pay->notes }}</small>
                            @elseif(!$pay->reference_number)
                                <small class="text-muted">-</small>
                            @endif
                        </td>
                        <td class="py-2.5 small text-muted">
                            <i class="bi bi-person me-0.5"></i>{{ $pay->user ? $pay->user->name : 'Sistem' }}
                        </td>
                        <td class="pe-3 py-2.5 text-center">
                            <form action="{{ route('v2.dropship.payment.destroy', ['id' => $order->id, 'paymentId' => $pay->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus pencatatan pembayaran {{ $pay->payment_number }} senilai Rp {{ number_format($pay->amount, 0, ',', '.') }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-xs btn-outline-danger py-1 px-1.5 rounded" title="Hapus Riwayat Pembayaran">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="bi bi-wallet2 fs-2 d-block mb-1 text-secondary opacity-50"></i>
                            Belum ada riwayat pembayaran yang dicatat untuk pesanan ini.
                            @if($order->remaining_amount > 0)
                                <div class="mt-2">
                                    <button type="button" class="btn btn-sm btn-primary py-1 px-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalCatatPembayaran">
                                        <i class="bi bi-cash-stack me-1"></i> Catat Pembayaran Sekarang
                                    </button>
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if($order->payments->count() > 0)
                <tfoot class="bg-light fw-bold">
                    <tr>
                        <td colspan="2" class="ps-3 py-2 text-uppercase">TOTAL PEMBAYARAN MASUK</td>
                        <td class="text-end py-2 font-monospace text-success">
                            Rp {{ number_format($order->paid_amount, 0, ',', '.') }}
                        </td>
                        <td colspan="4"></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>

{{-- ── Modal Catat Pembayaran ── --}}
<div class="modal fade" id="modalCatatPembayaran" tabindex="-1" aria-labelledby="modalCatatPembayaranLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form action="{{ route('v2.dropship.payment.store', $order->id) }}" method="POST">
                @csrf
                <div class="modal-header border-bottom px-4 py-3 bg-light rounded-top-4">
                    <h6 class="modal-title fw-bold text-dark mb-0" id="modalCatatPembayaranLabel">
                        <i class="bi bi-cash-stack text-success me-1.5"></i> Catat Pembayaran Pesanan
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-3.5">
                    <div class="alert alert-light border rounded-3 p-3 mb-3 d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted d-block">No. Nota: <strong>{{ $order->order_number }}</strong></small>
                            <small class="text-muted d-block">Total Tagihan: <strong>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</strong></small>
                        </div>
                        <div class="text-end">
                            <small class="text-muted d-block">Sisa Tagihan:</small>
                            <span class="fs-6 fw-bold text-danger font-monospace">Rp {{ number_format($order->remaining_amount, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">Tanggal Pembayaran <span class="text-danger">*</span></label>
                        <input type="date" name="payment_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">Nominal Pembayaran (Rp) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="1" max="{{ max(1, $order->remaining_amount) }}" name="amount" class="form-control form-control-sm font-monospace fw-bold" value="{{ $order->remaining_amount > 0 ? $order->remaining_amount : '' }}" placeholder="Contoh: 150000" required>
                        <div class="form-text small text-muted">Maksimal pembayaran sesuai sisa tagihan: Rp {{ number_format($order->remaining_amount, 0, ',', '.') }}</div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-dark">Metode Pembayaran <span class="text-danger">*</span></label>
                            <select name="payment_method" class="form-select form-select-sm" required>
                                <option value="transfer" selected>Transfer Bank</option>
                                <option value="tunai">Tunai / Cash</option>
                                <option value="qris">QRIS</option>
                                <option value="edc">Mesin EDC / Debit</option>
                                <option value="giro">Giro / Cek</option>
                                <option value="lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-dark">Rekening / Tujuan Kas</label>
                            <select name="payment_destination" class="form-select form-select-sm">
                                <option value="">-- Pilih Rekening / Kas --</option>
                                <option value="Kas Tunai">Kas Tunai</option>
                                @foreach($bankAccounts as $bank)
                                    <option value="{{ $bank->bank_name }} - {{ $bank->account_number }} ({{ $bank->account_holder }})">
                                        {{ $bank->bank_name }} ({{ $bank->account_number }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">No. Referensi / Bukti Transfer</label>
                        <input type="text" name="reference_number" class="form-control form-control-sm" placeholder="Contoh: TRF-20261028-001">
                    </div>

                    <div class="mb-0">
                        <label class="form-label small fw-semibold text-dark">Catatan Pembayaran</label>
                        <textarea name="notes" rows="2" class="form-control form-control-sm" placeholder="Catatan tambahan pembayaran..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-2.5 bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-success px-3 fw-semibold">
                        <i class="bi bi-check-lg me-1"></i> Simpan Pembayaran
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
