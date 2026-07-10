@extends('layouts.app')
@section('title', 'Kartu Stok Barang - Pembelian')
@section('page-title', 'Kartu Stok')

@section('content')
<div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
    <div class="card-body p-4">
        <h5 class="fw-bold text-dark mb-4">
            <i class="fas fa-history me-2" style="color:#10b981"></i>Kartu Stok Barang
        </h5>

        <form method="GET" action="{{ route('pembelian.stock_card') }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold text-muted">Pilih Barang</label>
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
                <label class="form-label small fw-semibold text-muted">Dari Tanggal</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold text-muted">Sampai Tanggal</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo }}">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-success btn-sm px-4 w-100 fw-semibold">
                    <i class="fas fa-search me-1"></i> Tampilkan
                </button>
                @if($selectedItem)
                    <a href="{{ route('pembelian.print_stock_card', ['item_id' => $selectedItem->id, 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}"
                       target="_blank" class="btn btn-sm px-4 w-100 fw-semibold text-white"
                       style="background:linear-gradient(135deg,#10b981,#059669)">
                        <i class="fas fa-print me-1"></i> Cetak Kartu
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

@if($selectedItem)
    <div class="row g-3">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 bg-white mb-3">
                <div class="card-header border-0 py-3 px-4" style="background:linear-gradient(135deg,#10b981,#059669)">
                    <h6 class="fw-bold text-white mb-0">
                        <i class="fas fa-info-circle me-2"></i>Informasi Barang
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="small text-muted fw-semibold d-block">SKU</label>
                        <code class="font-monospace text-success fw-bold" style="font-size: 13px;">{{ $selectedItem->sku ?: '—' }}</code>
                    </div>
                    <div class="mb-3">
                        <label class="small text-muted fw-semibold d-block">Nama Barang</label>
                        <div class="fw-bold text-dark">{{ $selectedItem->name }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="small text-muted fw-semibold d-block">Tipe Kategori</label>
                        <span class="badge text-uppercase" style="background:#ecfdf5;color:#047857">{{ $selectedItem->type }}</span>
                    </div>
                    <div class="mb-3">
                        <label class="small text-muted fw-semibold d-block">Harga Pokok (Cost Price)</label>
                        <div class="fw-bold text-dark">Rp {{ number_format($selectedItem->cost_price ?? 0, 0, ',', '.') }}</div>
                    </div>
                    <div class="p-3 rounded border border-success border-opacity-25" style="background:#f0fdf4">
                        <label class="small text-muted fw-semibold d-block text-uppercase mb-1 text-center" style="font-size:11px">Stok Global Saat Ini</label>
                        <div class="font-monospace fw-bold text-success text-center" style="font-size:2rem;line-height:1">
                            {{ number_format($selectedItem->stock) }}
                        </div>
                        <div class="small text-muted text-center mt-1">{{ $selectedItem->unit }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card border-0 shadow-sm rounded-3 bg-white">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-dark mb-3">
                        <i class="fas fa-history text-success me-2"></i>Riwayat Pergerakan Stok
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-hover border align-middle mb-0 rounded-2 overflow-hidden">
                            <thead style="background:#ecfdf5">
                                <tr class="small text-uppercase text-muted text-success">
                                    <th class="py-2 px-3">Tanggal / Jam</th>
                                    <th class="text-center">Tipe</th>
                                    <th class="text-end">Qty</th>
                                    <th class="text-end">Sisa Stok</th>
                                    <th>Referensi / Alasan</th>
                                    <th>User</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($movements as $row)
                                    <tr>
                                        <td class="small text-muted py-3 px-3">{{ $row->created_at->format('d M Y H:i') }}</td>
                                        <td class="text-center">
                                            @if($row->type === 'in')
                                                <span class="badge bg-success text-uppercase" style="font-size:9px">MASUK</span>
                                            @elseif($row->type === 'out')
                                                <span class="badge bg-danger text-uppercase" style="font-size:9px">KELUAR</span>
                                            @else
                                                <span class="badge bg-warning text-dark text-uppercase" style="font-size:9px">ADJUST</span>
                                            @endif
                                        </td>
                                        <td class="text-end fw-bold {{ $row->quantity > 0 ? 'text-success' : 'text-danger' }} small">
                                            {{ $row->quantity > 0 ? '+' : '' }}{{ number_format($row->quantity) }}
                                        </td>
                                        <td class="text-end fw-bold text-dark small">{{ number_format($row->balance_after) }}</td>
                                        <td class="small text-muted">{{ $row->reference }}</td>
                                        <td class="small text-dark">{{ $row->user->name ?? 'System' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="fas fa-history fa-2x mb-3 opacity-25 d-block"></i>
                                            Tidak ada riwayat pergerakan stok untuk barang ini pada periode terpilih.
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
    <div class="card border-0 shadow-sm rounded-3 bg-white p-5 text-center">
        <div class="text-muted">
            <i class="fas fa-arrow-pointer fa-3x mb-3 text-success opacity-25"></i>
            <h5>Silakan pilih barang terlebih dahulu untuk menampilkan Kartu Stok.</h5>
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('.select2-basic').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: '-- Cari & Pilih Barang --',
        allowClear: true
    });
});
</script>
@endpush
