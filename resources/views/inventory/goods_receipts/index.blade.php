@extends('layouts.app')
@section('title', 'Penerimaan Barang Langsung')
@section('page-title', 'Penerimaan Barang Langsung')

@section('content')
<div class="card border-0 shadow-sm rounded-3 bg-white">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle d-flex align-items-center justify-content-center"
                    style="width:42px;height:42px;background:linear-gradient(135deg,#10b981,#059669)">
                    <i class="fas fa-truck text-white"></i>
                </div>
                <div>
                    <h5 class="fw-bold text-dark mb-0">Penerimaan Barang Langsung</h5>
                    <div class="text-muted small">Barang datang tanpa PO (pembelian langsung / darurat)</div>
                </div>
            </div>
            <a href="{{ route('goods_receipts.create') }}" class="btn fw-semibold btn-sm px-3 text-white"
                style="background:linear-gradient(135deg,#10b981,#059669)">
                <i class="fas fa-plus me-1"></i> Catat Penerimaan Baru
            </a>
        </div>

        {{-- Info Banner --}}
        <div class="alert py-2 px-3 small mb-4 d-flex align-items-center gap-2"
            style="background:#ecfdf5;border:1px solid #6ee7b7;color:#065f46;border-radius:10px">
            <i class="fas fa-info-circle"></i>
            <span>Gunakan fitur ini saat barang datang <strong>tanpa melalui Purchase Order</strong> — misalnya beli cash langsung, pembelian darurat, atau barang titipan.</span>
        </div>

        {{-- Filter --}}
        <form method="GET" class="row g-2 mb-4 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label small fw-semibold text-muted">Cari No. Penerimaan</label>
                <input type="text" name="search" class="form-control form-control-sm"
                    value="{{ request('search') }}" placeholder="GR-2026...">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small fw-semibold text-muted">Jenis</label>
                <select name="source" class="form-select form-select-sm">
                    <option value="">Semua Jenis</option>
                    <option value="direct"    {{ request('source') === 'direct'    ? 'selected' : '' }}>Pembelian Langsung</option>
                    <option value="emergency" {{ request('source') === 'emergency' ? 'selected' : '' }}>Pembelian Darurat</option>
                    <option value="walk_in"   {{ request('source') === 'walk_in'   ? 'selected' : '' }}>Walk-in / Beli di Toko</option>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label small fw-semibold text-muted">Dari Tanggal</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label small fw-semibold text-muted">Sampai</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
            </div>
            <div class="col-12 col-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm px-3">
                    <i class="fas fa-search me-1"></i> Filter
                </button>
                @if(request()->anyFilled(['search','source','date_from','date_to','supplier_id']))
                    <a href="{{ route('goods_receipts.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                @endif
            </div>
        </form>

        {{-- Table --}}
        <div class="table-responsive">
            <table class="table table-hover border align-middle mb-0 rounded-2 overflow-hidden">
                <thead style="background:#ecfdf5">
                    <tr class="small text-uppercase text-muted">
                        <th class="py-2 px-3">No. Penerimaan</th>
                        <th>Supplier</th>
                        <th>Departemen</th>
                        <th>Tanggal</th>
                        <th>Jenis</th>
                        <th class="text-center">Item</th>
                        <th class="text-end">Total</th>
                        <th class="text-center" style="width:100px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($receipts as $receipt)
                        <tr>
                            <td class="font-monospace fw-bold text-dark px-3 py-3" style="font-size:13px">
                                {{ $receipt->receipt_number }}
                            </td>
                            <td class="small">
                                @if($receipt->supplier)
                                    <div class="fw-semibold text-dark">{{ $receipt->supplier->name }}</div>
                                @else
                                    <span class="text-muted">— (Tidak ada supplier)</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge rounded-pill" style="background:#f0fdf4;color:#166534;font-size:11px">
                                    {{ $receipt->department ? $receipt->department->name : 'Umum' }}
                                </span>
                            </td>
                            <td class="small text-muted">{{ $receipt->receipt_date->format('d M Y') }}</td>
                            <td>
                                <span class="badge bg-{{ $receipt->source_badge }} py-1 px-2 small">
                                    {{ $receipt->source_label }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary rounded-pill small">
                                    {{ $receipt->items->count() }} item
                                </span>
                            </td>
                            <td class="font-monospace text-end fw-bold text-dark small">
                                Rp {{ number_format($receipt->total_amount, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="{{ route('goods_receipts.show', $receipt) }}"
                                        class="btn btn-info btn-sm text-white" title="Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('goods_receipts.edit', $receipt) }}"
                                        class="btn btn-warning btn-sm text-white" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('goods_receipts.destroy', $receipt) }}" method="POST"
                                        onsubmit="return confirm('Hapus penerimaan ini? Stok akan dikurangi kembali.')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fas fa-truck fa-2x mb-3 opacity-25 d-block"></i>
                                Belum ada penerimaan barang langsung yang tercatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">{{ $receipts->links() }}</div>
    </div>
</div>
@endsection
