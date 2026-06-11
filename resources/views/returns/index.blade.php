@extends('layouts.app')
@section('title', 'Manajemen Retur Otomatis')
@section('page-title', 'Pesanan Retur')

@section('content')
    <div class="card dashboard-card">
        <div class="card-header-line" style="display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h3 style="margin-bottom: 0.25rem;"><i class="fas fa-undo-alt"></i> Pusat Resolusi & Retur</h3>
                <p class="text-muted" style="font-size:0.85rem; margin-bottom:0;">Pantau pesanan yang dibatalkan atau
                    dikembalikan oleh pembeli, lalu kembalikan stok fisik ke gudang.</p>
            </div>
            <div class="d-flex" style="gap:0.5rem;">
                <form action="{{ route('returns.index') }}" method="GET" class="d-flex" style="gap:0.5rem;">
                    <input type="text" name="search" class="form-control form-control-sm"
                        placeholder="Cari Resi Retur / Invoice..." value="{{ $search }}">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
                </form>
                <form action="{{ route('returns.sync') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="fas fa-sync-alt"></i> Tarik Data Retur Shopee
                    </button>
                </form>
            </div>
        </div>

        <div class="table-responsive mt-4">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Waktu Dibuat</th>
                        <th>Detail Retur & Invoice Asli</th>
                        <th>Barang yang Diretur</th>
                        <th style="text-align:center;">Alasan / Status</th>
                        <th style="text-align:center;">Tindakan Gudang</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($returns as $ret)
                        <tr>
                            <td>{{ $ret->created_at->format('d M Y, H:i') }}</td>
                            <td>
                                <div class="fw-bold mono">{{ $ret->return_sn }}</div>
                                <div style="font-size:0.8rem; margin-top:0.3rem;">
                                    Asal: <a
                                        href="{{ route('orders.show', $ret->order->id) }}">{{ $ret->order->invoice_number ?? $ret->order->order_marketplace_id }}</a>
                                </div>
                                <div style="font-size:0.8rem; margin-top:0.2rem;">
                                    Pembeli: <span class="fw-bold">{{ $ret->order->buyer_name ?? '-' }}</span>
                                </div>
                            </td>
                            <td>
                                <ul style="padding-left: 1rem; margin-bottom:0; font-size:0.85rem;">
                                    @foreach ($ret->items as $rItem)
                                        @php
                                            $mpProduct = $rItem->orderItem->marketplaceProduct ?? null;
                                        @endphp
                                        <li>
                                            {{ $rItem->quantity }}x
                                            {{ $mpProduct ? $mpProduct->name : $rItem->orderItem->product_name ?? 'Item Tidak Ditemukan' }}
                                        </li>
                                    @endforeach
                                </ul>
                            </td>
                            <td style="text-align:center;">
                                <span class="badge bg-danger">{{ $ret->status }}</span>
                                <div style="font-size:0.8rem; margin-top:0.3rem; font-style:italic;" class="text-muted">
                                    "{{ $ret->reason ?? 'Tidak ada alasan' }}"
                                </div>
                            </td>
                            <td style="text-align:center;">
                                @if ($ret->is_restocked)
                                    <span class="badge bg-success" style="font-size:0.85rem; padding: 0.5rem 0.75rem;">
                                        <i class="fas fa-check-circle"></i> Stok Dikembalikan
                                    </span>
                                @else
                                    <form action="{{ route('returns.restock', $ret->id) }}" method="POST"
                                        onsubmit="return confirm('Apakah Anda yakin fisik barang sudah diterima dan siap dikembalikan ke stok gudang?');">
                                        @csrf
                                        <button type="submit" class="btn-primary-sm">
                                            <i class="fas fa-box-open"></i> Terima Barang
                                        </button>
                                    </form>
                                    <div style="font-size:0.75rem; color:var(--text-secondary); margin-top:0.3rem;">
                                        Klik jika barang fisik<br>sudah Anda pegang.
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align:center; padding:3rem; color:var(--text-secondary);">
                                <i class="fas fa-box-check"
                                    style="font-size:2rem; margin-bottom:1rem; opacity:0.5;"></i><br>
                                Belum ada data barang retur. Klik "Tarik Data Retur Shopee" untuk memeriksa.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $returns->withQueryString()->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection
