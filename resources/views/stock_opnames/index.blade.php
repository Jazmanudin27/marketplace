@extends('layouts.app')
@section('title', 'Histori Stock Opname')
@section('page-title', 'Histori Stock Opname')
@section('content')
    <div class="form-page-wrapper">

        <div class="card border-0 shadow-sm" style="background-color: var(--bg-card);">
            <div class="card-header border-bottom d-flex justify-content-between align-items-center py-3"
                style="background-color: transparent;">
                <h5 class="mb-0 fw-bold text-white"><i class="fas fa-history text-primary"></i> Riwayat Stock Opname</h5>
                <div>
                    <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary btn-sm text-white me-2">
                        <i class="fas fa-arrow-left"></i> Kembali ke Inventory
                    </a>
                    <a href="{{ route('stock_opnames.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Tambah Opname
                    </a>
                </div>
            </div>

            <div class="card-body">
                <div class="mb-4 pb-3 border-bottom border-secondary">
                    <form action="{{ route('stock_opnames.index') }}" method="GET" class="row g-3 align-items-end">
                        <div class="col-md-4 col-sm-12">
                            <label class="form-label fw-semibold small mb-1 text-white">Cari Produk / PIC</label>
                            <input type="text" name="search" placeholder="Cari SKU, Nama, atau Petugas..."
                                value="{{ request('search') }}"
                                class="form-control form-control-sm bg-dark text-white border-secondary">
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <label class="form-label fw-semibold small mb-1 text-white">Dari Tanggal</label>
                            <input type="date" name="start_date" value="{{ request('start_date') }}"
                                class="form-control form-control-sm bg-dark text-white border-secondary">
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <label class="form-label fw-semibold small mb-1 text-white">Sampai Tanggal</label>
                            <input type="date" name="end_date" value="{{ request('end_date') }}"
                                class="form-control form-control-sm bg-dark text-white border-secondary">
                        </div>
                        <div class="col-md-2 col-sm-12 d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fas fa-filter"></i>
                                Filter</button>
                            @if (request()->anyFilled(['search', 'start_date', 'end_date']))
                                <a href="{{ route('stock_opnames.index') }}" class="btn btn-outline-danger btn-sm"
                                    title="Reset">
                                    <i class="fas fa-times"></i>
                                </a>
                            @endif
                        </div>
                    </form>
                </div>

                <div class="table-responsive mb-3">
                    <table class="table table-hover table-bordered table-sm table-dark align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col" class="text-white">Waktu Opname</th>
                                <th scope="col" class="text-white">Petugas (PIC)</th>
                                <th scope="col" class="text-white">SKU</th>
                                <th scope="col" class="text-white">Nama Produk</th>
                                <th scope="col" class="text-center text-white">Stok Sebelum</th>
                                <th scope="col" class="text-center text-white">Penyesuaian</th>
                                <th scope="col" class="text-center text-white table-primary"
                                    style="background-color: rgba(108,99,255,0.2);">Stok Akhir</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($histories as $history)
                                <tr>
                                    <td class="text-secondary small">{{ $history->created_at->format('d M Y H:i') }}</td>
                                    <td class="text-white">
                                        @php
                                            // Ekstrak nama PIC dari reference: "Stock Opname Massal - Budi"
                                            $pic = str_replace('Stock Opname Massal - ', '', $history->reference);
                                            if ($pic === 'Stock Opname Massal') {
                                                $pic = $history->user->name ?? 'Sistem';
                                            }
                                        @endphp
                                        <i class="fas fa-user-circle text-secondary me-1"></i> {{ $pic }}
                                    </td>
                                    <td class="text-nowrap font-monospace">{{ $history->masterProduct->sku ?? '-' }}</td>
                                    <td class="fw-bold text-white">{{ $history->masterProduct->name ?? '-' }}</td>
                                    <td class="text-center font-monospace text-secondary">
                                        {{ number_format($history->balance_after - $history->quantity) }}
                                    </td>
                                    <td class="text-center font-monospace">
                                        @if ($history->quantity > 0)
                                            <span class="text-success"><i class="fas fa-caret-up"></i>
                                                +{{ number_format($history->quantity) }}</span>
                                        @elseif($history->quantity < 0)
                                            <span class="text-danger"><i class="fas fa-caret-down"></i>
                                                {{ number_format($history->quantity) }}</span>
                                        @else
                                            <span class="text-secondary">0</span>
                                        @endif
                                    </td>
                                    <td class="text-center font-monospace fw-bold text-white"
                                        style="background-color: rgba(108,99,255,0.05);">
                                        {{ number_format($history->balance_after) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">
                                        <i class="fas fa-history fs-2 mb-3 d-block opacity-50"></i>
                                        Belum ada riwayat stock opname.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-center">
                    {{ $histories->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
