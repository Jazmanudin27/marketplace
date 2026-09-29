@extends('v2.layouts.app')

@section('title', 'Detail Penerimaan Barang Masuk — ' . $goodsReceipt->receipt_number)

@section('content')

{{-- ── Page Header ── --}}
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2 mb-0">
            <i class="bi bi-box-arrow-in-down text-success fs-5"></i> Detail Penerimaan: {{ $goodsReceipt->receipt_number }}
        </h1>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('v2.barang_masuk.index') }}" class="btn btn-sm text-white py-1.5 px-3 fw-semibold" style="background:#64748b; border:none;">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
        </a>
    </div>
</div>

{{-- ── Alert Messages ── --}}
@foreach(['success','error','info'] as $type)
    @if(session($type))
        <div class="alert alert-{{ $type === 'error' ? 'danger' : ($type === 'info' ? 'info' : 'success') }} alert-dismissible fade show mb-3 border-0 shadow-sm" role="alert" style="border-radius:10px;">
            <i class="bi bi-{{ $type === 'error' ? 'exclamation-triangle' : ($type === 'info' ? 'info-circle' : 'check-circle') }} me-2"></i>
            {!! session($type) !!}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
@endforeach

<div class="row g-3">
    {{-- Kiri: Status & Information Card --}}
    <div class="col-12 col-lg-4">
        <!-- Status Card -->
        <div class="v2-card p-3 shadow-sm mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-dark mb-0">Status Penerimaan</h6>
                @if($goodsReceipt->status === 'approved')
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 fw-semibold">
                        <i class="bi bi-check-circle-fill me-1"></i> Disetujui (Approved)
                    </span>
                @else
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-3 py-1 fw-semibold">
                        <i class="bi bi-hourglass-split me-1"></i> Menunggu Approval
                    </span>
                @endif
            </div>

            @if($goodsReceipt->status === 'pending')
                <div class="alert alert-warning py-2 px-3 small mb-3 border-0 rounded-3">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Stok barang <strong>belum bertambah</strong> ke gudang. Silakan tekan tombol setujui untuk memperbarui stok.
                </div>
                <form action="{{ route('v2.barang_masuk.approve', $goodsReceipt) }}" method="POST" class="mb-2" onsubmit="return confirm('Setujui penerimaan ini dan masukkan stok ke gudang?')">
                    @csrf
                    <button type="submit" class="btn btn-sm text-white w-100 fw-bold py-2 shadow-sm" style="background:#16a34a; border:none;">
                        <i class="bi bi-check-circle me-1"></i> Setujui & Masukkan ke Stok
                    </button>
                </form>
            @else
                <div class="alert alert-success py-2 px-3 small mb-2 border-0 rounded-3">
                    <i class="bi bi-check-circle me-1"></i>
                    Stok barang telah resmi disetujui dan ditambahkan ke gudang.
                </div>
            @endif

            <form action="{{ route('v2.barang_masuk.destroy', $goodsReceipt) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan/menghapus penerimaan ini? Stok akan ditarik kembali jika sudah approved.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm text-white w-100 fw-semibold py-1.5" style="background:#dc2626; border:none;">
                    <i class="bi bi-trash me-1"></i> Batalkan & Hapus Dokumen
                </button>
            </form>
        </div>

        <!-- Detail Meta Card -->
        <div class="v2-card p-3 shadow-sm mb-3">
            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                <i class="bi bi-info-circle text-primary me-1.5"></i> Informasi Dokumen
            </h6>
            <div class="border rounded-3 overflow-hidden mb-3" style="font-size: 0.78rem;">
                <div class="d-flex justify-content-between p-2 border-bottom bg-light">
                    <span class="text-muted">No. Penerimaan</span>
                    <strong class="font-monospace text-dark">{{ $goodsReceipt->receipt_number }}</strong>
                </div>
                <div class="d-flex justify-content-between p-2 border-bottom">
                    <span class="text-muted">Tanggal</span>
                    <strong class="text-dark">{{ $goodsReceipt->receipt_date ? $goodsReceipt->receipt_date->format('d F Y') : '-' }}</strong>
                </div>
                <div class="d-flex justify-content-between p-2 border-bottom">
                    <span class="text-muted">Sumber</span>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-0.5" style="font-size:0.65rem;">
                        {{ strtoupper($goodsReceipt->source_label) }}
                    </span>
                </div>
                <div class="d-flex justify-content-between p-2 border-bottom">
                    <span class="text-muted">Supplier</span>
                    <strong class="text-dark">{{ $goodsReceipt->supplier ? $goodsReceipt->supplier->name : '— (Non-Supplier)' }}</strong>
                </div>
                <div class="d-flex justify-content-between p-2 border-bottom">
                    <span class="text-muted">Departemen Tujuan</span>
                    <strong class="text-dark">{{ $goodsReceipt->department ? $goodsReceipt->department->name : 'Gudang Utama' }}</strong>
                </div>
                <div class="d-flex justify-content-between p-2 border-bottom bg-success-subtle">
                    <span class="fw-bold text-dark">Total Nilai Penerimaan</span>
                    <strong class="font-monospace text-success fw-bold" style="font-size: 0.88rem;">
                        Rp {{ number_format($goodsReceipt->total_amount, 0, ',', '.') }}
                    </strong>
                </div>
            </div>

            @if($goodsReceipt->notes)
                <div class="p-2.5 bg-light rounded-3 border mb-3" style="font-size:0.75rem;">
                    <span class="text-muted fw-bold d-block mb-1">Catatan:</span>
                    <span class="text-dark">{{ $goodsReceipt->notes }}</span>
                </div>
            @endif

            <div class="text-muted small" style="font-size:0.72rem;">
                <div><i class="bi bi-person me-1"></i> Dicatat oleh: <strong>{{ $goodsReceipt->createdBy->name ?? 'Sistem' }}</strong></div>
                @if($goodsReceipt->approvedBy)
                    <div class="mt-1"><i class="bi bi-check2-all me-1 text-success"></i> Disetujui oleh: <strong>{{ $goodsReceipt->approvedBy->name }}</strong></div>
                    <div class="text-muted ms-3" style="font-size:0.65rem;">Pada {{ $goodsReceipt->approved_at ? $goodsReceipt->approved_at->format('d/m/Y H:i') : '-' }}</div>
                @endif
            </div>
        </div>

        <!-- Status Hutang Supplier (jika ada) -->
        @if($goodsReceipt->status === 'approved' && $goodsReceipt->supplier_id && $goodsReceipt->payable)
            @php $payable = $goodsReceipt->payable; @endphp
            <div class="v2-card p-3 shadow-sm border-start border-3 border-danger">
                <h6 class="fw-bold text-dark small mb-2 d-flex align-items-center gap-1.5">
                    <i class="bi bi-credit-card-2-back text-danger"></i> Tagihan / Hutang Supplier
                </h6>
                <div class="d-flex justify-content-between align-items-center small mb-1">
                    <span class="text-muted">Total Hutang:</span>
                    <span class="fw-bold font-monospace">Rp {{ number_format($payable->total_amount, 0, ',', '.') }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center small mb-2">
                    <span class="text-muted">Sisa Belum Dibayar:</span>
                    <span class="fw-bold font-monospace text-danger">Rp {{ number_format($payable->remaining_amount, 0, ',', '.') }}</span>
                </div>
                <a href="{{ route('supplier_payables.show', $payable) }}" class="btn btn-sm btn-outline-danger w-100 fw-semibold">
                    <i class="bi bi-eye me-1"></i> Lihat & Bayar Hutang Supplier
                </a>
            </div>
        @endif
    </div>

    {{-- Kanan: Table Items --}}
    <div class="col-12 col-lg-8">
        <div class="v2-card p-3 shadow-sm h-100 d-flex flex-column">
            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                <i class="bi bi-boxes text-success me-1.5"></i> Rincian Barang Diterima ({{ $goodsReceipt->items->count() }} Varian)
            </h6>

            <div class="table-responsive flex-grow-1">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.8rem;">
                    <thead class="bg-light text-muted">
                        <tr>
                            <th class="ps-3 py-2.5">BARANG / SKU</th>
                            <th class="text-center py-2.5">TIPE</th>
                            <th class="text-center py-2.5">QTY DITERIMA</th>
                            <th class="text-end py-2.5">HARGA SATUAN</th>
                            <th class="text-end pe-3 py-2.5">SUBTOTAL (RP)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($goodsReceipt->items as $item)
                            <tr>
                                <td class="ps-3 py-2.5">
                                    <div class="fw-bold text-dark">{{ $item->item_name }}</div>
                                    <div class="font-monospace text-muted" style="font-size:0.7rem;">SKU: {{ $item->item_sku }}</div>
                                </td>
                                <td class="text-center">
                                    @if($item->inventoryItem)
                                        <span class="badge bg-light text-dark border rounded-pill px-2 py-0.5" style="font-size:0.65rem;">
                                            {{ strtoupper($item->inventoryItem->type) }}
                                        </span>
                                    @else
                                        <span class="badge bg-secondary rounded-pill px-2 py-0.5" style="font-size:0.65rem;">PRODUK</span>
                                    @endif
                                </td>
                                <td class="text-center fw-bold text-success" style="font-size: 0.88rem;">
                                    +{{ number_format($item->quantity, 2, ',', '.') }} {{ $item->inventoryItem->unit ?? '' }}
                                </td>
                                <td class="text-end font-monospace text-muted">
                                    Rp {{ number_format($item->unit_price, 0, ',', '.') }}
                                </td>
                                <td class="text-end pe-3 font-monospace fw-bold text-dark">
                                    Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-success-subtle">
                        <tr>
                            <td colspan="4" class="ps-3 py-2.5 fw-bold text-dark text-end">TOTAL NILAI PEMASUKAN:</td>
                            <td class="pe-3 py-2.5 text-end fw-bold text-success font-monospace" style="font-size: 0.95rem;">
                                Rp {{ number_format($goodsReceipt->total_amount, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection
