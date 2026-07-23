@extends('layouts.app')
@section('title', 'Kartu Stok Barang - Pembelian')
@section('page-title', 'Kartu Stok')

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h5 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
            <i class="fas fa-history text-primary"></i> Kartu Stok Barang
        </h5>

        <form method="GET" action="{{ route('pembelian.stock_card') }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary mb-1">Pilih Barang</label>
                <select name="item_id" class="form-select form-select-sm select2-basic" required>
                    <option value="">-- Cari & Pilih Barang --</option>
                    @foreach($inventoryItems as $item)
                        <option value="{{ $item->id }}" {{ request('item_id') == $item->id ? 'selected' : '' }}>
                            [{{ strtoupper($item->type) }}] {{ $item->name }} ({{ $item->sku ?: 'No SKU' }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold text-secondary mb-1">Dari Tanggal</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold text-secondary mb-1">Sampai Tanggal</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo }}">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm px-4 w-100 fw-semibold">
                    <i class="fas fa-search me-1"></i> Tampilkan
                </button>
                @if($selectedItem)
                    <a href="{{ route('pembelian.print_stock_card', ['item_id' => $selectedItem->id, 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}"
                       target="_blank" class="btn btn-outline-secondary btn-sm px-4 w-100 fw-semibold">
                        <i class="fas fa-print me-1"></i> Cetak Kartu
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

@if($selectedItem)
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent py-3 border-bottom">
                    <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="fas fa-info-circle text-primary"></i> Informasi Barang
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="small text-secondary fw-semibold d-block">SKU</label>
                        <span class="badge bg-light text-dark border font-monospace">{{ $selectedItem->sku ?: '—' }}</span>
                    </div>
                    <div class="mb-3">
                        <label class="small text-secondary fw-semibold d-block">Nama Barang</label>
                        <div class="fw-bold text-dark">{{ $selectedItem->name }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="small text-secondary fw-semibold d-block">Tipe Kategori</label>
                        <span class="badge bg-info text-dark text-uppercase">{{ $selectedItem->type }}</span>
                    </div>
                    <div class="mb-3">
                        <label class="small text-secondary fw-semibold d-block">Harga Pokok (Cost Price)</label>
                        <div class="fw-bold text-dark">Rp {{ number_format($selectedItem->cost_price ?? 0, 0, ',', '.') }}</div>
                    </div>
                    <div class="p-3 rounded border bg-success bg-opacity-10 border-success border-opacity-25">
                        <label class="small text-secondary fw-semibold d-block text-uppercase mb-1 text-center" style="font-size:11px">Stok Global Saat Ini</label>
                        <div class="font-monospace fw-bold text-success text-center" style="font-size:2rem;line-height:1">
                            {{ number_format($selectedItem->stock) }}
                        </div>
                        <div class="small text-secondary text-center mt-1">{{ $selectedItem->unit }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent py-3 border-bottom">
                    <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="fas fa-history text-primary"></i> Riwayat Pergerakan Stok (Kartu Stok)
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3" style="width:20%">Tanggal / Jam</th>
                                    <th class="text-center" style="width:12%">Tipe</th>
                                    <th class="text-end" style="width:15%">Qty</th>
                                    <th class="text-end" style="width:15%">Sisa Stok</th>
                                    <th>Referensi / Alasan</th>
                                    <th class="pe-3" style="width:18%">User</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($movements as $row)
                                    <tr>
                                        <td class="ps-3 small text-muted font-monospace">{{ $row->created_at->format('d M Y H:i') }}</td>
                                        <td class="text-center">
                                            @if($row->type === 'in')
                                                <span class="badge bg-success">MASUK</span>
                                            @elseif($row->type === 'out')
                                                <span class="badge bg-danger">KELUAR</span>
                                            @else
                                                <span class="badge bg-warning text-dark">ADJUST</span>
                                            @endif
                                        </td>
                                        <td class="text-end font-monospace fw-bold {{ $row->quantity > 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $row->quantity > 0 ? '+' : '' }}{{ number_format($row->quantity) }}
                                        </td>
                                        <td class="text-end font-monospace fw-bold text-dark">{{ number_format($row->balance_after) }}</td>
                                        <td class="small text-secondary">{{ $row->reference }}</td>
                                        <td class="pe-3 small text-dark">{{ $row->user->name ?? 'System' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-secondary">
                                            <i class="fas fa-history fa-2x mb-3 opacity-25 d-block"></i>
                                            <div>Tidak ada riwayat pergerakan stok untuk barang ini pada periode terpilih.</div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@else
    <div class="card border-0 shadow-sm p-5 text-center">
        <div class="text-secondary">
            <i class="fas fa-hand-pointer fa-3x mb-3 opacity-25 text-primary"></i>
            <h5>Silakan pilih barang terlebih dahulu untuk menampilkan Kartu Stok.</h5>
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    if ($.fn.select2) {
        $('.select2-basic').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: '-- Cari & Pilih Barang --',
            allowClear: true
        });
    }
});
</script>
@endpush
