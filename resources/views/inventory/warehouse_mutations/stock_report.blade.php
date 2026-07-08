@extends('layouts.app')
@section('title', 'Laporan Stok Gudang')
@section('page-title', 'Laporan Stok Gudang')

@section('content')
<div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle d-flex align-items-center justify-content-center"
                    style="width:42px;height:42px;background:linear-gradient(135deg,#3b82f6,#1d4ed8)">
                    <i class="fas fa-boxes text-white"></i>
                </div>
                <div>
                    <h5 class="fw-bold text-dark mb-0">Laporan Stok Gudang (Bahan &amp; Kemasan)</h5>
                    <div class="text-muted small">Melihat kondisi ketersediaan stok fisik saat ini (Read-Only)</div>
                </div>
            </div>
            <a href="{{ route('warehouse_mutations.print_stock_report', request()->all()) }}" target="_blank"
                class="btn btn-success fw-semibold btn-sm px-3 text-white">
                <i class="fas fa-print me-1"></i> Cetak Laporan Stok
            </a>
        </div>

        {{-- Filter --}}
        <form method="GET" class="row g-2 mb-2 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label small fw-semibold text-muted">Cari Nama atau SKU</label>
                <input type="text" name="search" class="form-control form-control-sm"
                    value="{{ request('search') }}" placeholder="Ketik nama bahan/kemasan...">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small fw-semibold text-muted">Kategori</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="all" {{ request('type') === 'all' ? 'selected' : '' }}>Semua Kategori</option>
                    <option value="bahan" {{ request('type') === 'bahan' ? 'selected' : '' }}>Bahan Baku</option>
                    <option value="kemasan" {{ request('type') === 'kemasan' ? 'selected' : '' }}>Kemasan</option>
                </select>
            </div>
            <div class="col-12 col-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm px-3">
                    <i class="fas fa-search me-1"></i> Filter
                </button>
                @if(request()->anyFilled(['search','type']))
                    <a href="{{ route('warehouse_mutations.stock_report') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-3 bg-white">
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover border align-middle mb-0 rounded-2 overflow-hidden">
                <thead style="background:#f0f4f9">
                    <tr class="small text-uppercase text-muted">
                        <th class="py-2 px-3">SKU</th>
                        <th>Nama Barang</th>
                        <th>Kategori</th>
                        <th class="text-center">Stok Fisik</th>
                        <th>Satuan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        @php
                            $catColors = [
                                'bahan' => 'background:#fef3c7;color:#92400e',
                                'kemasan' => 'background:#dbeafe;color:#1e40af',
                            ];
                            $catStyle = $catColors[$item->type] ?? 'background:#f1f5f9;color:#475569';
                        @endphp
                        <tr>
                            <td class="font-monospace fw-bold text-muted py-3 px-3" style="font-size:12px">{{ $item->sku ?: '—' }}</td>
                            <td class="fw-semibold text-dark small">{{ $item->name }}</td>
                            <td>
                                <span class="badge rounded-pill" style="font-size:11px;{{ $catStyle }}">{{ ucfirst($item->type) }}</span>
                            </td>
                            <td class="text-center">
                                @if($item->stock <= ($item->min_stock ?? 0) && $item->stock > 0)
                                    <span class="badge bg-warning text-dark font-monospace fw-bold" style="font-size:13px">{{ number_format($item->stock) }}</span>
                                    <div class="text-warning" style="font-size:10px; margin-top:2px;"><i class="fas fa-exclamation-triangle"></i> Minim</div>
                                @elseif($item->stock <= 0)
                                    <span class="badge bg-danger font-monospace fw-bold" style="font-size:13px">Habis</span>
                                @else
                                    <span class="badge bg-success font-monospace fw-bold" style="font-size:13px">{{ number_format($item->stock) }}</span>
                                @endif
                            </td>
                            <td class="small text-muted">{{ $item->unit ?: 'pcs' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                Tidak ada data barang terdaftar yang sesuai.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $items->links() }}
        </div>
    </div>
</div>
@endsection
