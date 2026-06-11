@extends('layouts.app')
@section('title', 'Detail Pelanggan')
@section('page-title', 'Profil Pelanggan')

@section('content')
<div class="form-page-wrapper">
    <a href="{{ route('customers.index') }}" class="btn-back">
        <i class="fas fa-arrow-left"></i> Kembali ke Data Pelanggan
    </a>

    <div style="display:grid; grid-template-columns: 350px 1fr; gap:1.5rem; margin-top:1rem;">
        
        {{-- Profil Kiri --}}
        <div>
            <div class="dashboard-card text-center">
                <div class="user-avatar mx-auto mb-3" style="width:80px; height:80px; font-size:2.5rem;">
                    {{ strtoupper(substr($customer->name, 0, 1)) }}
                </div>
                <h3 class="mb-1">{{ $customer->name }}</h3>
                <p class="text-muted mb-3 mono">{{ $customer->marketplace_username }}</p>
                
                @if($customer->orders->count() >= 3)
                    <div class="badge bg-warning text-dark mb-3 w-100 p-2" style="font-size:0.9rem;">
                        <i class="fas fa-crown"></i> Loyal Customer
                    </div>
                @endif

                <form action="{{ route('customers.update', $customer->id) }}" method="POST" style="text-align:left;">
                    @csrf
                    @method('PUT')
                    
                    <div class="form-group">
                        <label>Nama / Alias</label>
                        <input type="text" name="name" class="form-control" value="{{ $customer->name }}">
                    </div>

                    <div class="form-group">
                        <label>Nomor Telepon</label>
                        <input type="text" name="phone" class="form-control" value="{{ $customer->phone }}">
                    </div>

                    <div class="form-group">
                        <label>Alamat Utama</label>
                        <textarea name="address" class="form-control" rows="2">{{ $customer->address }}</textarea>
                    </div>

                    <div class="form-group">
                        <label>Tag / Label Tambahan</label>
                        <input type="text" name="tags" class="form-control" value="{{ $customer->tags }}" placeholder="Contoh: Reseller, Blacklist, VIP">
                        <small class="text-muted">Pisahkan dengan koma jika lebih dari satu.</small>
                    </div>

                    <button type="submit" class="btn-primary-sm w-100 mt-2">
                        <i class="fas fa-save"></i> Simpan Profil
                    </button>
                </form>
            </div>

            <div class="dashboard-card mt-3">
                <h4 style="font-size:0.9rem; font-weight:700;"><i class="fas fa-chart-pie"></i> Ringkasan Nilai Pelanggan</h4>
                <hr style="border-color:var(--border);">
                
                <div class="detail-row">
                    <span class="detail-label">Total Transaksi</span>
                    <span class="detail-value mono fw-bold">{{ $customer->orders->count() }}x</span>
                </div>
                <div class="detail-row mt-2">
                    <span class="detail-label">Total Dibelanjakan</span>
                    <span class="detail-value mono text-success fw-bold">Rp {{ number_format($totalSpent, 0, ',', '.') }}</span>
                </div>
                <div class="detail-row mt-2">
                    <span class="detail-label">Rata-rata Order</span>
                    <span class="detail-value mono">Rp {{ number_format($averageOrderValue, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        {{-- Riwayat Pesanan Kanan --}}
        <div class="dashboard-card">
            <div class="card-header-line">
                <h3><i class="fas fa-history"></i> Riwayat Pesanan</h3>
            </div>

            <div class="table-responsive mt-3">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Tgl Pesanan</th>
                            <th>No Invoice / ID</th>
                            <th>Status</th>
                            <th style="text-align:right;">Nilai Bersih (LTV)</th>
                            <th style="text-align:center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customer->orders as $order)
                        <tr>
                            <td>{{ $order->order_date->format('d M Y, H:i') }}</td>
                            <td>
                                <div class="fw-bold">{{ $order->invoice_number ?? $order->order_marketplace_id }}</div>
                                <div style="font-size:0.75rem; color:var(--text-secondary); margin-top:0.25rem;">
                                    {{ $order->items->count() }} item produk
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-{{ $order->status_badge }}">
                                    {{ str_replace('_', ' ', $order->order_status) }}
                                </span>
                            </td>
                            <td class="mono fw-bold text-success" style="text-align:right;">
                                Rp {{ number_format($order->net_amount, 0, ',', '.') }}
                            </td>
                            <td style="text-align:center;">
                                <a href="{{ route('orders.show', $order->id) }}" class="btn-primary-sm" style="text-decoration:none;">
                                    Detail <i class="fas fa-chevron-right ms-1"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" style="text-align:center; padding:2rem; color:var(--text-secondary);">
                                Belum ada riwayat pesanan yang valid.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
@endsection
