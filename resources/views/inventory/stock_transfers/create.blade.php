@extends('layouts.app')
@section('title', 'Buat Transfer Stok')
@section('page-title', 'Transfer Stok Antar Departemen')

@section('content')
<div class="row g-3">
    {{-- Kiri: Info & Konfig --}}
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 bg-white mb-3">
            <div class="card-body p-3">
                <div class="d-flex align-items-center gap-2 mb-4">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:38px;height:38px;background:linear-gradient(135deg,#8b5cf6,#6d28d9)">
                        <i class="fas fa-exchange-alt text-white small"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark small">Transfer Stok</div>
                        <div class="text-muted" style="font-size:11px">Distribusi ke dept. tujuan</div>
                    </div>
                </div>

                {{-- Form konfigurasi transfer --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-muted">Dari Departemen (Asal)</label>
                    <select name="from_department_id" id="fromDept" form="formTransfer" class="form-select form-select-sm">
                        <option value="">— Umum / Tidak Spesifik —</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('from_department_id') == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text" style="font-size:11px">Mis: Departemen Pembelian</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small text-muted">Ke Departemen (Tujuan) <span class="text-danger">*</span></label>
                    <select name="to_department_id" id="toDept" form="formTransfer"
                        class="form-select form-select-sm @error('to_department_id') is-invalid @enderror" required>
                        <option value="">— Pilih Tujuan —</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('to_department_id') == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text" style="font-size:11px">Mis: Gudang Bahan, Gudang Kemasan, GA</div>
                    @error('to_department_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small text-muted">Tanggal Transfer <span class="text-danger">*</span></label>
                    <input type="date" name="transfer_date" form="formTransfer"
                        class="form-control form-control-sm @error('transfer_date') is-invalid @enderror"
                        value="{{ old('transfer_date', date('Y-m-d')) }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small text-muted">Catatan</label>
                    <textarea name="notes" form="formTransfer" class="form-control form-control-sm" rows="2"
                        placeholder="Keterangan tujuan transfer...">{{ old('notes') }}</textarea>
                </div>

                <div class="alert alert-info py-2 px-3 small mb-0" style="border-radius:10px">
                    <i class="fas fa-info-circle me-1"></i>
                    Transfer langsung <strong>dikonfirmasi</strong> saat disimpan. Movement stok antar dept. akan tercatat otomatis.
                </div>
            </div>
        </div>
        <a href="{{ route('stock_transfers.index') }}" class="btn btn-outline-secondary btn-sm w-100">
            <i class="fas fa-arrow-left me-1"></i> Kembali
        </a>
    </div>

    {{-- Kanan: Pilih Barang --}}
    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-3 bg-white">
            <div class="card-header border-0 py-3 px-4" style="background:linear-gradient(135deg,#8b5cf6,#6d28d9)">
                <h6 class="fw-bold text-white mb-0">
                    <i class="fas fa-boxes me-2"></i>Pilih Barang yang Ditransfer
                </h6>
            </div>
            <div class="card-body p-4">
                @if($errors->any())
                    <div class="alert alert-danger py-2 small">
                        @foreach($errors->all() as $error)
                            <div><i class="fas fa-exclamation-circle me-1"></i>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <form action="{{ route('stock_transfers.store') }}" method="POST" id="formTransfer">
                    @csrf

                    {{-- Tampilkan item per kategori --}}
                    @php $itemIndex = 0; @endphp
                    @foreach($inventoryItems as $type => $items)
                        <div class="mb-4">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                @php
                                    $typeColor = match($type) {
                                        'bahan'     => ['bg' => '#fef3c7', 'text' => '#92400e', 'label' => 'Bahan Baku'],
                                        'kemasan'   => ['bg' => '#dbeafe', 'text' => '#1e40af', 'label' => 'Kemasan'],
                                        'atk'       => ['bg' => '#d1fae5', 'text' => '#065f46', 'label' => 'ATK'],
                                        'inventaris'=> ['bg' => '#f3e8ff', 'text' => '#6d28d9', 'label' => 'Inventaris'],
                                        default     => ['bg' => '#f1f5f9', 'text' => '#475569', 'label' => ucfirst($type)],
                                    };
                                @endphp
                                <span class="badge rounded-pill px-3 py-1" style="background:{{ $typeColor['bg'] }};color:{{ $typeColor['text'] }};font-size:12px">
                                    {{ $typeColor['label'] }}
                                </span>
                                <span class="text-muted small">{{ $items->count() }} barang tersedia</span>
                            </div>
                            <div class="border rounded-2 overflow-hidden">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead style="background:#f8fafc">
                                        <tr class="small text-muted text-uppercase">
                                            <th class="py-1 px-3" style="width:30px">
                                                <input type="checkbox" class="form-check-input check-all" data-type="{{ $type }}">
                                            </th>
                                            <th class="py-1">Nama Barang</th>
                                            <th class="text-center py-1">Stok</th>
                                            <th class="text-center py-1" style="min-width:110px">Qty Transfer</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($items as $item)
                                            <tr>
                                                <td class="px-3">
                                                    <input type="checkbox" class="form-check-input item-check"
                                                        data-type="{{ $type }}"
                                                        data-index="{{ $itemIndex }}"
                                                        data-max="{{ $item->stock }}">
                                                    <input type="hidden" name="items[{{ $itemIndex }}][item_id]"
                                                        value="{{ $item->id }}" disabled>
                                                </td>
                                                <td class="py-2">
                                                    <div class="small fw-semibold text-dark">{{ $item->name }}</div>
                                                    <div class="font-monospace text-muted" style="font-size:11px">{{ $item->sku ?: '-' }}</div>
                                                    <span class="badge" style="font-size:10px;background:{{ $typeColor['bg'] }};color:{{ $typeColor['text'] }}">{{ $item->unit }}</span>
                                                </td>
                                                <td class="text-center">
                                                    @if($item->stock <= ($item->min_stock ?? 0))
                                                        <span class="badge bg-danger">{{ $item->stock }}</span>
                                                    @else
                                                        <span class="badge bg-success">{{ $item->stock }}</span>
                                                    @endif
                                                </td>
                                                <td class="text-center py-1 px-2">
                                                    <input type="number"
                                                        name="items[{{ $itemIndex }}][quantity]"
                                                        class="form-control form-control-sm text-center item-qty"
                                                        data-index="{{ $itemIndex }}"
                                                        style="width:90px;margin:auto"
                                                        min="1" max="{{ $item->stock }}"
                                                        value="1"
                                                        disabled>
                                                </td>
                                            </tr>
                                            @php $itemIndex++; @endphp
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach

                    @if($inventoryItems->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-box-open fa-2x mb-2 opacity-25 d-block"></i>
                            <div class="small">Tidak ada barang dengan stok tersedia.</div>
                        </div>
                    @endif

                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="small text-muted" id="selectedCount">0 barang dipilih</div>
                        <button type="submit" class="btn fw-semibold px-4 text-white" id="btnSubmit" style="background:linear-gradient(135deg,#8b5cf6,#6d28d9)" disabled>
                            <i class="fas fa-paper-plane me-2"></i>Konfirmasi Transfer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Toggle item checklist
document.querySelectorAll('.item-check').forEach(function(chk) {
    chk.addEventListener('change', function() {
        const idx = this.dataset.index;
        const idInput   = document.querySelector(`input[name="items[${idx}][item_id]"]`);
        const qtyInput  = document.querySelector(`input[name="items[${idx}][quantity]"]`);
        if (this.checked) {
            idInput.disabled  = false;
            qtyInput.disabled = false;
        } else {
            idInput.disabled  = true;
            qtyInput.disabled = true;
        }
        updateCount();
    });
});

// Check-all per tipe
document.querySelectorAll('.check-all').forEach(function(chkAll) {
    chkAll.addEventListener('change', function() {
        const type = this.dataset.type;
        document.querySelectorAll(`.item-check[data-type="${type}"]`).forEach(function(chk) {
            chk.checked = chkAll.checked;
            chk.dispatchEvent(new Event('change'));
        });
    });
});

function updateCount() {
    const count = document.querySelectorAll('.item-check:checked').length;
    document.getElementById('selectedCount').textContent = count + ' barang dipilih';
    document.getElementById('btnSubmit').disabled = (count === 0);
}

document.getElementById('formTransfer')?.addEventListener('submit', function() {
    const btn = document.getElementById('btnSubmit');
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';
    btn.disabled = true;
});
</script>
@endpush
