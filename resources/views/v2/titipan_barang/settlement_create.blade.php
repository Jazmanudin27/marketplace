@extends('v2.layouts.app')

@section('title', 'Form Setoran Pembayaran ke Supplier V2')

@section('content')

{{-- ── Page Header ── --}}
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2 mb-0">
            <i class="bi bi-cash-stack text-success fs-5"></i> Form Setoran Pembayaran ke Supplier
        </h1>
        <p class="text-muted small mb-0" style="font-size: 0.78rem;">Catat total pembayaran setoran barang titipan/konsinyasi per supplier dengan alokasi otomatis (FIFO).</p>
    </div>
    <div>
        <a href="{{ route('supplier_consignments.stock_card', ['supplier_id' => $selectedSupplierId]) }}" class="btn btn-sm text-white py-1.5 px-3 fw-semibold" style="background:#64748b; border:none;">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Kartu Stok
        </a>
    </div>
</div>

{{-- ── Error Notification ── --}}
@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mb-3 border-0 shadow-sm" role="alert" style="border-radius:10px;">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>Terjadi Kesalahan:</strong>
        <ul class="mb-0 mt-1 ps-3 small">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<form action="{{ route('supplier_consignments.settlement.store') }}" method="POST" id="settlement-form">
    @csrf
    <div class="row g-3">
        {{-- Kiri: Form Informasi Setoran & Nominal Per Supplier --}}
        <div class="col-12 col-lg-4">
            <div class="v2-card p-3 shadow-sm h-100 d-flex flex-column">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-2.5">
                    <i class="bi bi-info-circle text-primary me-1.5"></i> Informasi Setoran Supplier
                </h6>

                <!-- No. Setoran -->
                <div class="mb-2">
                    <label class="form-label small fw-semibold text-dark mb-1">No. Setoran</label>
                    <input type="text" class="form-control form-control-sm bg-light font-monospace" value="{{ $settlementNumber }}" readonly>
                </div>

                <!-- Supplier -->
                <div class="mb-2">
                    <label class="form-label small fw-semibold text-dark mb-1">Supplier <span class="text-danger">*</span></label>
                    <select name="supplier_id"
                        class="form-select form-select-sm @error('supplier_id') is-invalid @enderror"
                        onchange="window.location.href='{{ route('supplier_consignments.settlement.create') }}?supplier_id='+this.value"
                        required>
                        <option value="">-- Pilih Supplier --</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ $selectedSupplierId == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Tanggal Setoran -->
                <div class="mb-2">
                    <label class="form-label small fw-semibold text-dark mb-1">Tanggal Setoran <span class="text-danger">*</span></label>
                    <input type="date" name="settlement_date"
                        class="form-control form-control-sm @error('settlement_date') is-invalid @enderror"
                        value="{{ old('settlement_date', date('Y-m-d')) }}" required>
                </div>

                <!-- Metode Pembayaran -->
                <div class="mb-2">
                    <label class="form-label small fw-semibold text-dark mb-1">Metode Pembayaran <span class="text-danger">*</span></label>
                    <select name="payment_method" id="payment_method" class="form-select form-select-sm" onchange="toggleBank(this.value)" required>
                        <option value="transfer" {{ old('payment_method', 'transfer') === 'transfer' ? 'selected' : '' }}>Transfer Bank</option>
                        <option value="cash" {{ old('payment_method') === 'cash' ? 'selected' : '' }}>Tunai / Kas</option>
                    </select>
                </div>

                <!-- Sumber Rekening Bank -->
                <div class="mb-2" id="bank-container">
                    <label class="form-label small fw-semibold text-dark mb-1">Sumber Rekening Bank <span class="text-danger">*</span></label>
                    <select name="bank_account_id" id="bank_account_id" class="form-select form-select-sm @error('bank_account_id') is-invalid @enderror">
                        <option value="">-- Pilih Rekening --</option>
                        @foreach ($bankAccounts as $bank)
                            <option value="{{ $bank->id }}" {{ old('bank_account_id') == $bank->id ? 'selected' : '' }}>
                                {{ $bank->bank_name }} - {{ $bank->account_number }} (a.n {{ $bank->account_name }})
                            </option>
                        @endforeach
                    </select>
                    @error('bank_account_id')
                        <div class="invalid-feedback small">{{ $message }}</div>
                    @enderror
                </div>

                <!-- No. Ref Transfer -->
                <div class="mb-2">
                    <label class="form-label small fw-semibold text-dark mb-1">No. Ref Transfer / Giro</label>
                    <input type="text" name="reference_number" class="form-control form-control-sm" placeholder="Contoh: TRF-8823901" value="{{ old('reference_number') }}">
                </div>

                <!-- Catatan -->
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark mb-1">Catatan / Keterangan</label>
                    <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Contoh: Setoran pembayaran titipan supplier...">{{ old('notes') }}</textarea>
                </div>

                <!-- ── Input Total Pembayaran Supplier ── -->
                <div class="p-3 bg-light rounded-3 border mb-3">
                    <label class="form-label small fw-bold text-success mb-1 d-flex align-items-center justify-content-between">
                        <span><i class="bi bi-cash-coin me-1"></i> Total Nominal Pembayaran (Rp) <span class="text-danger">*</span></span>
                    </label>
                    <div class="input-group mb-2">
                        <span class="input-group-text bg-white fw-bold text-success font-monospace" style="font-size:0.85rem;">Rp</span>
                        <input type="text"
                            name="total_amount"
                            id="total_amount"
                            class="form-control form-control-lg fw-bold text-success font-monospace @error('total_amount') is-invalid @enderror"
                            placeholder="0"
                            value="{{ old('total_amount') }}"
                            autocomplete="off"
                            required
                            style="font-size:1.15rem;"
                            oninput="handleNominalInput(this.value)">
                    </div>

                    {{-- Tombol Bantuan Cepat --}}
                    <div class="d-flex flex-column gap-1.5 mt-2">
                        @if(($supplierSummary['sisa_wajib_setor_laku_rp'] ?? 0) > 0)
                            <button type="button"
                                class="btn btn-sm btn-outline-warning text-dark text-start d-flex justify-content-between align-items-center py-1.5 px-2.5 rounded-2"
                                style="font-size: 0.76rem;"
                                onclick="setNominalTotal({{ (float) $supplierSummary['sisa_wajib_setor_laku_rp'] }})">
                                <span><i class="bi bi-cart-check me-1 text-warning"></i>Setor Barang Laku:</span>
                                <strong class="font-monospace">Rp {{ number_format($supplierSummary['sisa_wajib_setor_laku_rp'], 0, ',', '.') }}</strong>
                            </button>
                        @endif

                        @if(($supplierSummary['sisa_total_titipan_rp'] ?? 0) > 0)
                            <button type="button"
                                class="btn btn-sm btn-outline-success text-start d-flex justify-content-between align-items-center py-1.5 px-2.5 rounded-2"
                                style="font-size: 0.76rem;"
                                onclick="setNominalTotal({{ (float) $supplierSummary['sisa_total_titipan_rp'] }})">
                                <span><i class="bi bi-check2-all me-1"></i>Setor Semua Sisa Tagihan:</span>
                                <strong class="font-monospace">Rp {{ number_format($supplierSummary['sisa_total_titipan_rp'], 0, ',', '.') }}</strong>
                            </button>
                        @endif

                        <button type="button"
                            class="btn btn-xs btn-outline-secondary py-1 px-2 text-center rounded-2"
                            style="font-size: 0.72rem;"
                            onclick="clearNominalTotal()">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Reset / Kosongkan Nominal
                        </button>
                    </div>
                </div>

                <!-- Total Summary Box -->
                <div class="p-2.5 bg-success-subtle rounded-3 border border-success-subtle mt-auto" style="padding: 10px 12px;">
                    <div class="d-flex justify-content-between align-items-center small mb-1">
                        <span class="text-muted">Alokasi Nota Titipan:</span>
                        <strong id="summary-allocation-docs" class="text-dark font-monospace">0 Dokumen</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-success-emphasis small">Total Setoran:</span>
                        <strong id="summary-total-amount" class="text-success fs-5 fw-bold font-monospace">Rp 0</strong>
                    </div>
                </div>
            </div>
        </div>

        {{-- Kanan: Ringkasan Tagihan Supplier & Daftar Dokumen Penerimaan Titipan (FIFO) --}}
        <div class="col-12 col-lg-8">
            <div class="v2-card p-3 shadow-sm h-100 d-flex flex-column">

                {{-- ── 4 KPI Card Ringkasan Supplier ── --}}
                <div class="row g-2 mb-3">
                    <div class="col-6 col-md-3">
                        <div class="p-2.5 rounded-3 border bg-light h-100">
                            <div class="text-muted small" style="font-size:0.72rem;"><i class="bi bi-box-arrow-in-down me-1 text-primary"></i>Total Titipan Masuk</div>
                            <div class="fw-bold text-dark font-monospace fs-6 mt-1">
                                Rp {{ number_format($supplierSummary['total_titipan_rp'] ?? 0, 0, ',', '.') }}
                            </div>
                            <div class="text-muted" style="font-size:0.68rem;">{{ number_format($supplierSummary['total_qty_masuk'] ?? 0) }} PCS</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2.5 rounded-3 border bg-light h-100">
                            <div class="text-muted small" style="font-size:0.72rem;"><i class="bi bi-check2-circle me-1 text-success"></i>Sudah Disetor</div>
                            <div class="fw-bold text-success font-monospace fs-6 mt-1">
                                Rp {{ number_format($supplierSummary['setor_rp'] ?? 0, 0, ',', '.') }}
                            </div>
                            <div class="text-success" style="font-size:0.68rem;">Telah ditransfer</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2.5 rounded-3 border bg-light h-100">
                            <div class="text-muted small" style="font-size:0.72rem;"><i class="bi bi-cart-check me-1 text-warning"></i>Laku Belum Disetor</div>
                            <div class="fw-bold text-danger font-monospace fs-6 mt-1">
                                Rp {{ number_format($supplierSummary['sisa_wajib_setor_laku_rp'] ?? 0, 0, ',', '.') }}
                            </div>
                            <div class="text-muted" style="font-size:0.68rem;">{{ number_format($supplierSummary['total_qty_laku'] ?? 0) }} PCS Terjual</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2.5 rounded-3 border bg-light h-100">
                            <div class="text-muted small" style="font-size:0.72rem;"><i class="bi bi-receipt me-1 text-danger"></i>Sisa Seluruh Titipan</div>
                            <div class="fw-bold text-danger font-monospace fs-6 mt-1" id="kpi-total-remaining">
                                Rp {{ number_format($supplierSummary['sisa_total_titipan_rp'] ?? 0, 0, ',', '.') }}
                            </div>
                            <div class="text-muted" style="font-size:0.68rem;">HPP belum disetor</div>
                        </div>
                    </div>
                </div>

                {{-- ── Header Daftar Dokumen ── --}}
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom pb-2 mb-2">
                    <div>
                        <h6 class="fw-bold text-dark mb-0">
                            <i class="bi bi-receipt text-success me-1.5"></i> Dokumen Penerimaan Titipan &amp; Alokasi Otomatis (FIFO)
                        </h6>
                        <small class="text-muted" style="font-size: 0.72rem;">Nominal setoran akan dialokasikan berurutan dari dokumen tertua.</small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-light text-dark border fw-normal" style="font-size: 0.75rem;">
                            Total: {{ count($availableInvoices) }} Dokumen
                        </span>
                    </div>
                </div>

                {{-- ── Tabel Dokumen Titipan ── --}}
                <div class="table-responsive flex-grow-1 mb-2">
                    <table class="table table-bordered table-hover align-middle m-0" id="invoicesTable" style="font-size: 0.8rem; width: 100%;">
                        <thead class="bg-light text-muted">
                            <tr>
                                <th style="width: 25%;">NO. INVOICE / TITIPAN</th>
                                <th style="width: 11%;" class="text-center">QTY TOTAL</th>
                                <th style="width: 14%;" class="text-end">NILAI TITIPAN</th>
                                <th style="width: 13%;" class="text-end">SUDAH DISETOR</th>
                                <th style="width: 14%;" class="text-end">SISA TAGIHAN</th>
                                <th style="width: 15%;" class="text-end pe-2">ALOKASI SETORAN</th>
                                <th style="width: 8%;" class="text-center">STATUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($availableInvoices as $index => $inv)
                                <tr class="invoice-row {{ $inv['is_paid_off'] ? 'bg-light opacity-75' : '' }}"
                                    id="row_{{ $inv['consignment_id'] }}"
                                    data-id="{{ $inv['consignment_id'] }}"
                                    data-remaining="{{ $inv['remaining_amount'] }}"
                                    data-ref="{{ $inv['reference_number'] }}">
                                    <td class="ps-2 py-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="fw-bold text-primary font-monospace" style="font-size: 0.84rem;">
                                                {{ $inv['reference_number'] }}
                                            </span>
                                            @if($inv['is_paid_off'])
                                                <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.62rem;">Lunas</span>
                                            @endif
                                        </div>
                                        <div class="text-muted d-flex align-items-center gap-2 mt-0.5" style="font-size: 0.72rem;">
                                            <span><i class="bi bi-calendar3 me-1"></i>{{ $inv['consignment_date'] }}</span>
                                            <span>•</span>
                                            <span>{{ $inv['items_count'] }} Jenis Produk</span>
                                        </div>
                                        @if($inv['notes'] !== '-')
                                            <div class="text-muted small fst-italic text-truncate" style="max-width: 260px; font-size: 0.7rem;">
                                                "{{ $inv['notes'] }}"
                                            </div>
                                        @endif
                                        {{-- Modal Trigger Rincian Produk --}}
                                        <div class="mt-1">
                                            <button type="button"
                                                class="btn btn-xs btn-outline-primary py-0.5 px-2 rounded-pill d-inline-flex align-items-center gap-1 shadow-sm"
                                                style="font-size: 0.68rem; font-weight: 500;"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalInvoiceItems{{ $inv['consignment_id'] }}">
                                                <i class="bi bi-box-seam"></i>
                                                <span>Rincian Produk ({{ $inv['items_count'] }})</span>
                                                <i class="bi bi-box-arrow-up-right ms-0.5" style="font-size: 0.6rem;"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="text-center font-monospace py-2">
                                        {{ number_format($inv['total_qty']) }} PCS
                                    </td>
                                    <td class="text-end font-monospace py-2">
                                        Rp {{ number_format($inv['total_amount'], 0, ',', '.') }}
                                    </td>
                                    <td class="text-end font-monospace text-success py-2">
                                        Rp {{ number_format($inv['settled_amount'], 0, ',', '.') }}
                                    </td>
                                    <td class="text-end font-monospace {{ $inv['remaining_amount'] > 0 ? 'text-danger fw-bold' : 'text-muted' }} py-2">
                                        Rp {{ number_format($inv['remaining_amount'], 0, ',', '.') }}
                                    </td>
                                    <td class="text-end pe-2 py-2">
                                        <span id="alloc_text_{{ $inv['consignment_id'] }}" class="font-monospace fw-bold {{ $inv['is_paid_off'] ? 'text-muted' : 'text-dark' }}">
                                            Rp 0
                                        </span>
                                    </td>
                                    <td class="text-center py-2">
                                        <span id="status_badge_{{ $inv['consignment_id'] }}" class="badge {{ $inv['is_paid_off'] ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border' }}" style="font-size:0.65rem;">
                                            {{ $inv['is_paid_off'] ? 'Lunas' : 'Belum' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="bi bi-receipt-cutoff fs-2 d-block mb-2 text-secondary"></i>
                                        Tidak ada dokumen penerimaan titipan untuk supplier yang dipilih.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- ── Footer Card & Tombol Simpan ── --}}
                <div class="d-flex justify-content-between align-items-center pt-2 border-top mt-auto flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="small text-muted fw-semibold">Alokasi FIFO:</span>
                        <span id="footer-total-summary" class="fw-bold text-success font-monospace small">0 Dokumen | Rp 0</span>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('supplier_consignments.stock_card', ['supplier_id' => $selectedSupplierId]) }}" class="btn btn-sm text-white fw-semibold" style="background:#64748b; border:none;">
                            Batal
                        </a>
                        @if (count($availableInvoices) > 0)
                            <button type="submit" class="btn btn-sm text-white fw-semibold py-1.5 px-4 shadow-sm" style="background:#16a34a; border:none;" id="btn-submit-settle">
                                <i class="bi bi-check-circle me-1"></i> Simpan Setoran Pembayaran Supplier
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

{{-- Modals Rincian Produk Per Dokumen Titipan --}}
@foreach($availableInvoices as $inv)
<div class="modal fade" id="modalInvoiceItems{{ $inv['consignment_id'] }}" tabindex="-1" aria-labelledby="modalLabel{{ $inv['consignment_id'] }}" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow border-0" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header py-2.5 px-3 bg-light border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="bi bi-receipt-cutoff fs-5"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h6 class="modal-title fw-bold text-dark mb-0 font-monospace" id="modalLabel{{ $inv['consignment_id'] }}">
                                {{ $inv['reference_number'] }}
                            </h6>
                            @if($inv['is_paid_off'])
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5" style="font-size: 0.65rem;">Lunas</span>
                            @else
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-0.5" style="font-size: 0.65rem;">Sisa Tagihan: Rp {{ number_format($inv['remaining_amount'], 0, ',', '.') }}</span>
                            @endif
                        </div>
                        <div class="text-muted small mt-0.5" style="font-size: 0.73rem;">
                            <span><i class="bi bi-calendar3 me-1"></i>Tanggal: {{ $inv['consignment_date'] }}</span>
                            <span class="mx-1.5">•</span>
                            <span>{{ $inv['items_count'] }} Jenis Produk</span>
                            <span class="mx-1.5">•</span>
                            <span>Total Qty: {{ number_format($inv['total_qty']) }} PCS</span>
                            @if($inv['notes'] && $inv['notes'] !== '-')
                                <span class="mx-1.5">•</span>
                                <span class="fst-italic">"{{ $inv['notes'] }}"</span>
                            @endif
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small fw-semibold text-dark">Daftar Produk Titipan Pada Dokumen Ini:</span>
                    <input type="text"
                        class="form-control form-control-sm"
                        style="max-width: 200px; font-size: 0.75rem;"
                        placeholder="Cari SKU / nama..."
                        onkeyup="filterModalItems(this, 'tableItems{{ $inv['consignment_id'] }}')">
                </div>
                <div class="table-responsive border rounded-2" style="max-height: 380px;">
                    <table class="table table-sm table-hover align-middle mb-0" id="tableItems{{ $inv['consignment_id'] }}" style="font-size: 0.78rem;">
                        <thead class="bg-light text-muted sticky-top">
                            <tr>
                                <th style="width: 5%;" class="text-center py-2">No</th>
                                <th style="width: 45%;" class="py-2">Nama Produk / SKU</th>
                                <th style="width: 15%;" class="text-center py-2">Qty Titipan</th>
                                <th style="width: 17%;" class="text-end py-2">Harga Beli (HPP)</th>
                                <th style="width: 18%;" class="text-end pe-3 py-2">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($inv['items'] as $itIdx => $it)
                                <tr class="item-row">
                                    <td class="text-center py-1.5 text-muted">{{ $itIdx + 1 }}</td>
                                    <td class="py-1.5">
                                        <div class="fw-semibold text-dark">{{ $it['name'] }}</div>
                                        <div class="text-muted font-monospace" style="font-size: 0.68rem;">{{ $it['sku'] }}</div>
                                    </td>
                                    <td class="text-center font-monospace py-1.5">{{ number_format($it['qty']) }} PCS</td>
                                    <td class="text-end font-monospace py-1.5">Rp {{ number_format($it['unit_cost_price'], 0, ',', '.') }}</td>
                                    <td class="text-end pe-3 font-monospace fw-semibold py-1.5">Rp {{ number_format($it['subtotal'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-light fw-bold sticky-bottom border-top">
                            <tr>
                                <td colspan="2" class="py-2 ps-3">Total Nilai Tagihan Nota Ini:</td>
                                <td class="text-center font-monospace py-2">{{ number_format($inv['total_qty']) }} PCS</td>
                                <td></td>
                                <td class="text-end pe-3 font-monospace text-primary py-2">Rp {{ number_format($inv['total_amount'], 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer py-2 px-3 bg-light border-top">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endforeach

@endsection

@push('scripts')
<script>
    const maxSupplierRemaining = {{ (float) ($supplierSummary['sisa_total_titipan_rp'] ?? 0) }};

    function toggleBank(method) {
        const bankContainer = document.getElementById('bank-container');
        if (bankContainer) {
            bankContainer.style.display = (method === 'transfer') ? 'block' : 'none';
        }
    }

    function parseRupiahNumber(val) {
        if (!val) return 0;
        const clean = val.toString().replace(/[^0-9]/g, '');
        return clean ? parseFloat(clean) : 0;
    }

    function formatRupiahNumberText(num) {
        return 'Rp ' + Math.round(num).toLocaleString('id-ID');
    }

    // Dipanggil saat user mengetik nominal total
    function handleNominalInput(val) {
        const num = parseRupiahNumber(val);
        const inputElem = document.getElementById('total_amount');
        if (inputElem) {
            if (num > 0) {
                inputElem.value = Math.round(num).toLocaleString('id-ID');
            } else {
                inputElem.value = '';
            }
        }
        updateAllocationPreview(num);
    }

    // Tombol bantuan: set nominal total langsung
    function setNominalTotal(amount) {
        const inputElem = document.getElementById('total_amount');
        if (inputElem) {
            inputElem.value = Math.round(amount).toLocaleString('id-ID');
        }
        updateAllocationPreview(amount);
    }

    // Tombol bantuan: kosongkan
    function clearNominalTotal() {
        const inputElem = document.getElementById('total_amount');
        if (inputElem) {
            inputElem.value = '';
        }
        updateAllocationPreview(0);
    }

    // Menghitung alokasi FIFO ke baris-baris nota titipan secara real-time
    function updateAllocationPreview(totalPayment) {
        let remainingToAllocate = parseFloat(totalPayment) || 0;
        let totalAllocatedDocs = 0;
        let totalAllocatedAmount = 0;

        const rows = document.querySelectorAll('#invoicesTable tbody tr.invoice-row');
        rows.forEach(row => {
            const rowId = row.dataset.id;
            const rowRemaining = parseFloat(row.dataset.remaining || 0);
            const allocTextElem = document.getElementById('alloc_text_' + rowId);
            const statusBadgeElem = document.getElementById('status_badge_' + rowId);

            if (rowRemaining <= 0) {
                // Dokumen sudah lunas dari sebelumnya
                if (allocTextElem) {
                    allocTextElem.innerText = 'Rp 0';
                    allocTextElem.className = 'font-monospace fw-bold text-muted';
                }
                if (statusBadgeElem) {
                    statusBadgeElem.innerText = 'Lunas';
                    statusBadgeElem.className = 'badge bg-success-subtle text-success border border-success-subtle';
                }
                return;
            }

            if (remainingToAllocate > 0) {
                const allocAmount = Math.min(remainingToAllocate, rowRemaining);
                remainingToAllocate -= allocAmount;
                totalAllocatedAmount += allocAmount;
                totalAllocatedDocs++;

                if (allocTextElem) {
                    allocTextElem.innerText = formatRupiahNumberText(allocAmount);
                    allocTextElem.className = 'font-monospace fw-bold text-success';
                }

                if (statusBadgeElem) {
                    if (allocAmount >= rowRemaining) {
                        statusBadgeElem.innerText = 'Lunas';
                        statusBadgeElem.className = 'badge bg-success text-white shadow-2xs';
                    } else {
                        statusBadgeElem.innerText = 'Sebagian';
                        statusBadgeElem.className = 'badge bg-warning text-dark shadow-2xs';
                    }
                }
            } else {
                if (allocTextElem) {
                    allocTextElem.innerText = 'Rp 0';
                    allocTextElem.className = 'font-monospace fw-bold text-muted';
                }
                if (statusBadgeElem) {
                    statusBadgeElem.innerText = 'Belum';
                    statusBadgeElem.className = 'badge bg-secondary-subtle text-secondary border';
                }
            }
        });

        // Update Ringkasan
        const formattedAmount = formatRupiahNumberText(totalPayment);
        const formattedDocs = totalAllocatedDocs.toLocaleString('id-ID') + ' Dokumen';

        const summaryDocs = document.getElementById('summary-allocation-docs');
        if (summaryDocs) summaryDocs.innerText = formattedDocs;

        const summaryAmount = document.getElementById('summary-total-amount');
        if (summaryAmount) summaryAmount.innerText = formattedAmount;

        const footerSummary = document.getElementById('footer-total-summary');
        if (footerSummary) footerSummary.innerText = formattedDocs + ' | ' + formattedAmount;
    }

    function filterModalItems(input, tableId) {
        const val = input.value.toLowerCase().trim();
        const rows = document.querySelectorAll('#' + tableId + ' tbody tr.item-row');
        rows.forEach(row => {
            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(val) ? '' : 'none';
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        const methodSelect = document.getElementById('payment_method');
        if (methodSelect) {
            toggleBank(methodSelect.value);
        }

        const initialVal = document.getElementById('total_amount')?.value;
        if (initialVal) {
            handleNominalInput(initialVal);
        } else {
            updateAllocationPreview(0);
        }

        const form = document.getElementById('settlement-form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const method = document.getElementById('payment_method')?.value;
                const bankSelect = document.getElementById('bank_account_id');
                if (method === 'transfer' && bankSelect && !bankSelect.value) {
                    e.preventDefault();
                    alert('Silakan pilih Sumber Rekening Bank terlebih dahulu untuk metode Transfer Bank.');
                    bankSelect.focus();
                    return false;
                }

                const totalNominal = parseRupiahNumber(document.getElementById('total_amount')?.value);
                if (totalNominal <= 0) {
                    e.preventDefault();
                    alert('Silakan masukkan Nominal Total Pembayaran Setoran Supplier terlebih dahulu.');
                    document.getElementById('total_amount')?.focus();
                    return false;
                }

                if (maxSupplierRemaining > 0 && totalNominal > maxSupplierRemaining + 1) {
                    e.preventDefault();
                    alert('Nominal setoran (Rp ' + Math.round(totalNominal).toLocaleString('id-ID') + ') melebihi total seluruh sisa tagihan supplier (Rp ' + Math.round(maxSupplierRemaining).toLocaleString('id-ID') + ').');
                    document.getElementById('total_amount')?.focus();
                    return false;
                }
            });
        }
    });
</script>
@endpush
