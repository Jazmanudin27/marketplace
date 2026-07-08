@extends('layouts.app')
@section('title', 'Buat Retur Pembelian')
@section('page-title', 'Buat Retur Pembelian')

@section('content')
<div class="row g-3">
    {{-- Kiri --}}
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 bg-white mb-3">
            <div class="card-body p-3">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:38px;height:38px;background:linear-gradient(135deg,#ef4444,#dc2626)">
                        <i class="fas fa-undo-alt text-white small"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark small">Retur Pembelian</div>
                        <div class="text-muted" style="font-size:11px">Kembalikan barang ke supplier</div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small text-muted">Pilih Purchase Order <span class="text-danger">*</span></label>
                    <select id="poSelector" class="form-select form-select-sm" onchange="loadPoItems(this.value)">
                        <option value="">— Pilih PO —</option>
                        @foreach($purchaseOrders as $po)
                            <option value="{{ $po->id }}" {{ ($selectedPo && $selectedPo->id == $po->id) ? 'selected' : '' }}>
                                {{ $po->po_number }} — {{ $po->supplier->name ?? '' }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text text-muted" style="font-size:11px">Hanya PO yang sudah dipesan/diterima</div>
                </div>

                <div class="alert alert-warning py-2 px-3 small mb-0" style="border-radius:10px">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    Setelah retur dibuat, <strong>stok akan langsung berkurang</strong>. Pastikan qty benar sebelum simpan.
                </div>
            </div>
        </div>
        <a href="{{ route('purchase_returns.index') }}" class="btn btn-outline-secondary btn-sm w-100">
            <i class="fas fa-arrow-left me-1"></i> Kembali
        </a>
    </div>

    {{-- Kanan: Form --}}
    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-3 bg-white">
            <div class="card-header border-0 py-3 px-4" style="background:linear-gradient(135deg,#ef4444,#dc2626)">
                <h6 class="fw-bold text-white mb-0">
                    <i class="fas fa-clipboard-list me-2"></i>Detail Retur
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

                <form action="{{ route('purchase_returns.store') }}" method="POST" id="formReturn">
                    @csrf
                    <input type="hidden" name="purchase_order_id" id="poIdInput" value="{{ $selectedPo ? $selectedPo->id : '' }}">

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-muted">Tanggal Retur <span class="text-danger">*</span></label>
                            <input type="date" name="return_date" class="form-control form-control-sm @error('return_date') is-invalid @enderror"
                                value="{{ old('return_date', date('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-muted">Alasan Retur <span class="text-danger">*</span></label>
                            <select name="reason" class="form-select form-select-sm @error('reason') is-invalid @enderror" required>
                                <option value="">— Pilih alasan —</option>
                                <option value="Barang cacat / rusak" {{ old('reason') === 'Barang cacat / rusak' ? 'selected' : '' }}>Barang cacat / rusak</option>
                                <option value="Salah kirim oleh supplier" {{ old('reason') === 'Salah kirim oleh supplier' ? 'selected' : '' }}>Salah kirim oleh supplier</option>
                                <option value="Kelebihan pengiriman" {{ old('reason') === 'Kelebihan pengiriman' ? 'selected' : '' }}>Kelebihan pengiriman</option>
                                <option value="Kualitas tidak sesuai spesifikasi" {{ old('reason') === 'Kualitas tidak sesuai spesifikasi' ? 'selected' : '' }}>Kualitas tidak sesuai spesifikasi</option>
                                <option value="Expired / kadaluarsa" {{ old('reason') === 'Expired / kadaluarsa' ? 'selected' : '' }}>Expired / kadaluarsa</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small text-muted">Catatan Tambahan</label>
                            <textarea name="notes" class="form-control form-control-sm" rows="2"
                                placeholder="Detail kondisi barang, nomor seri, dll...">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    {{-- Tabel Item --}}
                    <div id="itemsSection">
                        @if($selectedPo)
                            @include('inventory.purchase_returns._items_table', ['po' => $selectedPo])
                        @else
                            <div class="border rounded-2 p-5 text-center text-muted" id="emptyState">
                                <i class="fas fa-file-invoice fa-2x mb-2 opacity-25 d-block"></i>
                                <div class="small">Pilih Purchase Order terlebih dahulu</div>
                            </div>
                        @endif
                    </div>

                    <div class="d-flex justify-content-end mt-4" id="submitSection" style="{{ $selectedPo ? '' : 'display:none!important' }}">
                        <button type="submit" class="btn btn-danger fw-semibold px-4" id="btnSubmit">
                            <i class="fas fa-undo-alt me-2"></i>Buat Retur & Kurangi Stok
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
function loadPoItems(poId) {
    document.getElementById('poIdInput').value = poId;
    if (!poId) {
        document.getElementById('itemsSection').innerHTML = `
            <div class="border rounded-2 p-5 text-center text-muted">
                <i class="fas fa-file-invoice fa-2x mb-2 opacity-25 d-block"></i>
                <div class="small">Pilih Purchase Order terlebih dahulu</div>
            </div>`;
        document.getElementById('submitSection').style.display = 'none';
        return;
    }
    // Redirect to same page with po_id
    window.location.href = '{{ route("purchase_returns.create") }}?po_id=' + poId;
}

document.getElementById('formReturn')?.addEventListener('submit', function() {
    const btn = document.getElementById('btnSubmit');
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';
    btn.disabled = true;
});
</script>
@endpush
