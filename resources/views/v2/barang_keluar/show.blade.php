@extends('v2.layouts.app')

@section('title', 'Detail Pengeluaran Barang — ' . $warehouseMutation->mutation_number)

@section('content')

{{-- ── Page Header ── --}}
<div class="v2-page-header align-items-center mb-3">
    <div>
        <h1 class="v2-page-title d-flex align-items-center gap-2 mb-0">
            <i class="bi bi-box-arrow-up-right text-danger fs-5"></i> Detail Pengeluaran: {{ $warehouseMutation->mutation_number }}
        </h1>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('v2.barang_keluar.index') }}" class="btn btn-sm text-white py-1.5 px-3 fw-semibold" style="background:#64748b; border:none;">
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
    {{-- Kiri: Detail Metadata Transaksi --}}
    <div class="col-12 col-lg-3">
        <div class="v2-card p-3 shadow-sm mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-dark mb-0">Status Transaction</h6>
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 fw-semibold">
                    <i class="bi bi-check-circle-fill me-1"></i> Transaksi Berhasil (Approved)
                </span>
            </div>

            <div class="border rounded-3 overflow-hidden mb-3" style="font-size: 0.78rem;">
                <div class="d-flex justify-content-between p-2 border-bottom bg-light">
                    <span class="text-muted">No. Pengeluaran</span>
                    <strong class="font-monospace text-danger fw-bold">{{ $warehouseMutation->mutation_number }}</strong>
                </div>
                <div class="d-flex justify-content-between p-2 border-bottom">
                    <span class="text-muted">Tanggal</span>
                    <strong class="text-dark">{{ $warehouseMutation->mutation_date ? $warehouseMutation->mutation_date->format('d F Y') : '-' }}</strong>
                </div>
                <div class="d-flex justify-content-between p-2 border-bottom">
                    <span class="text-muted">Tujuan Departemen</span>
                    <strong class="text-dark">{{ $warehouseMutation->toDepartment ? $warehouseMutation->toDepartment->name : 'Lain-lain' }}</strong>
                </div>
                @if($warehouseMutation->spk)
                    <div class="d-flex justify-content-between p-2 border-bottom">
                        <span class="text-muted">Referensi SPK</span>
                        <a href="{{ Route::has('spks.show') ? route('spks.show', $warehouseMutation->spk) : '#' }}" class="fw-bold text-primary text-decoration-none">
                            SPK #{{ $warehouseMutation->spk->no_spk }}
                        </a>
                    </div>
                @endif
                <div class="d-flex justify-content-between p-2 border-bottom">
                    <span class="text-muted">Operator Pencatat</span>
                    <strong class="text-dark">{{ $warehouseMutation->createdBy->name ?? 'Sistem' }}</strong>
                </div>
            </div>

            @if($warehouseMutation->notes)
                <div class="p-2.5 bg-light rounded-3 border mb-3" style="font-size:0.75rem;">
                    <span class="text-muted fw-bold d-block mb-1">Catatan / Alasan:</span>
                    <span class="text-dark">{{ $warehouseMutation->notes }}</span>
                </div>
            @endif

            <form action="{{ route('v2.barang_keluar.destroy', $warehouseMutation) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan transaksi pengeluaran ini? Stok barang akan dikembalikan lagi ke gudang.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm text-white w-100 fw-semibold py-2" style="background:#dc2626; border:none;">
                    <i class="bi bi-trash me-1"></i> Batalkan & Kembalikan Stok Barang
                </button>
            </form>
        </div>
    </div>

    {{-- Kanan: Rincian Barang Dikeluarkan --}}
    <div class="col-12 col-lg-9">
        <div class="v2-card p-3 shadow-sm h-100 d-flex flex-column">
            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                <i class="bi bi-boxes text-danger me-1.5"></i> Rincian Barang Dikeluarkan ({{ $warehouseMutation->items->count() }} Varian)
            </h6>

            <div class="table-responsive flex-grow-1">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.8rem;">
                    <thead class="bg-light text-muted">
                        <tr>
                            <th class="ps-3 py-2.5">BARANG / SKU</th>
                            <th class="text-center py-2.5">KATEGORI TIPE</th>
                            <th class="text-center py-2.5">QTY KELUAR</th>
                            <th class="text-end py-2.5">ESTIMASI HPP</th>
                            <th class="text-end pe-3 py-2.5">TOTAL HPP (RP)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $grandTotalHpp = 0; @endphp
                        @foreach($warehouseMutation->items as $row)
                            @php
                                $itemHpp = ($row->quantity * $row->unit_price);
                                $grandTotalHpp += $itemHpp;
                            @endphp
                            <tr>
                                <td class="ps-3 py-2.5">
                                    <div class="fw-bold text-dark">{{ $row->inventoryItem->name }}</div>
                                    <div class="font-monospace text-muted" style="font-size:0.7rem;">SKU: {{ $row->inventoryItem->sku ?: '—' }}</div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border rounded-pill px-2 py-0.5" style="font-size:0.65rem;">
                                        {{ strtoupper($row->inventoryItem->type) }}
                                    </span>
                                </td>
                                <td class="text-center fw-bold text-danger" style="font-size: 0.88rem;">
                                    -{{ number_format($row->quantity, 2, ',', '.') }} {{ $row->inventoryItem->unit }}
                                </td>
                                <td class="text-end font-monospace text-muted">
                                    Rp {{ number_format($row->unit_price, 0, ',', '.') }}
                                </td>
                                <td class="text-end pe-3 font-monospace fw-bold text-dark">
                                    Rp {{ number_format($itemHpp, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-danger-subtle">
                        <tr>
                            <td colspan="4" class="ps-3 py-2.5 fw-bold text-dark text-end">TOTAL NILAI HPP PENGELUARAN:</td>
                            <td class="pe-3 py-2.5 text-end fw-bold text-danger font-monospace" style="font-size: 0.95rem;">
                                Rp {{ number_format($grandTotalHpp, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection
