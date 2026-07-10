@extends('layouts.app')
@section('title', 'Laporan Stok Barang')
@section('page-title', 'Laporan Stok Barang')

@section('content')
<div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle d-flex align-items-center justify-content-center"
                    style="width:42px;height:42px;background:linear-gradient(135deg,#10b981,#059669)">
                    <i class="fas fa-boxes text-white"></i>
                </div>
                <div>
                    <h5 class="fw-bold text-dark mb-0">Laporan Stok Barang</h5>
                    <div class="text-muted small">Kondisi ketersediaan stok fisik barang saat ini (Bahan Baku, Kemasan, ATK &amp; Inventaris)</div>
                </div>
            </div>
            <a href="{{ route('pembelian.print_stock_report', request()->all()) }}" target="_blank"
                class="btn fw-semibold btn-sm px-3 text-white" style="background:linear-gradient(135deg,#10b981,#059669)">
                <i class="fas fa-print me-1"></i> Cetak Laporan Stok
            </a>
        </div>

        {{-- Filter --}}
        <form method="GET" class="row g-2 mb-2 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label small fw-semibold text-muted">Cari Nama atau SKU</label>
                <input type="text" name="search" class="form-control form-control-sm"
                    value="{{ request('search') }}" placeholder="Ketik nama item atau SKU...">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small fw-semibold text-muted">Kategori Tipe</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="all" {{ request('type') === 'all' ? 'selected' : '' }}>Semua Tipe</option>
                    <option value="bahan" {{ request('type') === 'bahan' ? 'selected' : '' }}>Bahan Baku</option>
                    <option value="kemasan" {{ request('type') === 'kemasan' ? 'selected' : '' }}>Kemasan</option>
                    <option value="atk" {{ request('type') === 'atk' ? 'selected' : '' }}>ATK</option>
                    <option value="inventaris" {{ request('type') === 'inventaris' ? 'selected' : '' }}>Inventaris</option>
                </select>
            </div>
            <div class="col-12 col-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-success btn-sm px-3">
                    <i class="fas fa-search me-1"></i> Filter
                </button>
                @if(request()->anyFilled(['search','type']))
                    <a href="{{ route('pembelian.stock_report') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-3 bg-white">
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover border align-middle mb-0 rounded-2 overflow-hidden">
                <thead style="background:#ecfdf5">
                    <tr class="small text-uppercase text-muted text-success">
                        <th class="py-2 px-3">SKU</th>
                        <th>Nama Barang</th>
                        <th>Kategori</th>
                        <th class="text-center">Stok Fisik</th>
                        <th>Satuan</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $row)
                        @php
                            $catColors = [
                                'bahan' => 'background:#e0f2fe;color:#0369a1',
                                'kemasan' => 'background:#fef3c7;color:#b45309',
                                'atk' => 'background:#ede9fe;color:#5b21b6',
                                'inventaris' => 'background:#dbeafe;color:#1e40af'
                            ];
                            $cs = $catColors[$row->type] ?? 'background:#f1f5f9;color:#475569';
                        @endphp
                        <tr>
                            <td class="font-monospace fw-bold text-muted py-3 px-3" style="font-size:12px">{{ $row->sku ?: '—' }}</td>
                            <td class="fw-semibold text-dark small">{{ $row->name }}</td>
                            <td>
                                <span class="badge text-uppercase" style="{{ $cs }};font-size:10px">{{ $row->type }}</span>
                            </td>
                            <td class="text-center fw-bold small">
                                @if($row->stock <= 0)
                                    <span class="text-danger">0</span>
                                @elseif($row->stock <= $row->min_stock)
                                    <span class="text-warning">{{ number_format($row->stock) }}</span>
                                @else
                                    <span class="text-success">{{ number_format($row->stock) }}</span>
                                @endif
                            </td>
                            <td class="text-muted small">{{ $row->unit }}</td>
                            <td class="text-center">
                                @if($row->stock <= 0)
                                    <span class="badge bg-danger">Habis</span>
                                @elseif($row->stock <= $row->min_stock)
                                    <span class="badge bg-warning text-dark">Stok Rendah</span>
                                @else
                                    <span class="badge bg-success">Aman</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fas fa-boxes fa-2x mb-3 opacity-25 d-block"></i>
                                Tidak ada data barang ditemukan.
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
