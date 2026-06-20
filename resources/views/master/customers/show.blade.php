@extends('layouts.app')
@section('title', 'Detail Pelanggan')
@section('page-title', 'Profil Pelanggan')

@section('content')
    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item">
                <a href="{{ route('customers.index') }}" class="text-decoration-none">
                    <i class="fas fa-users me-1"></i>Pelanggan
                </a>
            </li>
            <li class="breadcrumb-item active">Profil Pelanggan</li>
        </ol>
    </nav>

    <div class="row g-3">
        {{-- Profil Kiri --}}
        <div class="col-md-5 col-lg-4">
            <div class="dashboard-card text-center mb-3">
                <div class="rounded-circle bg-primary bg-opacity-10 text-primary mx-auto mb-3 d-flex align-items-center justify-content-center fw-bold" 
                    style="width:80px; height:80px; font-size:2.5rem; border: 1px solid rgba(59, 130, 246, 0.2);">
                    {{ strtoupper(substr($customer->name, 0, 1)) }}
                </div>
                <h5 class="mb-1 fw-bold">{{ $customer->name }}</h5>
                <p class="text-muted mb-3 font-monospace small">{{ $customer->marketplace_username ?? 'No Username' }}</p>
                
                @if($customer->orders->count() >= 3)
                    <div class="badge bg-warning-subtle text-warning border border-warning-subtle mb-3 w-100 py-2" style="font-size:0.8rem;">
                        <i class="fas fa-crown me-1"></i> Loyal Customer
                    </div>
                @endif

                {{-- Alert --}}
                @if (session('success'))
                    <div class="alert alert-success py-2 px-3 mb-3 small text-start" role="alert">
                        <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
                    </div>
                @endif

                <form action="{{ route('customers.update', $customer->id) }}" method="POST" class="text-start">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-2">
                        <label class="form-label form-label-sm fw-semibold mb-1">Nama / Alias</label>
                        <input type="text" name="name" class="form-control form-control-sm" value="{{ $customer->name }}" required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label form-label-sm fw-semibold mb-1">Nomor Telepon</label>
                        <input type="text" name="phone" class="form-control form-control-sm" value="{{ $customer->phone }}">
                    </div>

                    <div class="mb-2">
                        <label class="form-label form-label-sm fw-semibold mb-1">Alamat Utama</label>
                        <textarea name="address" class="form-control form-control-sm" rows="3">{{ $customer->address }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label form-label-sm fw-semibold mb-1">Tag / Label Tambahan</label>
                        <input type="text" name="tags" class="form-control form-control-sm" value="{{ $customer->tags }}" placeholder="VIP, Reseller, Blacklist">
                        <small class="text-muted d-block mt-1" style="font-size:0.68rem;">Pisahkan dengan koma jika lebih dari satu.</small>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm w-100 mt-2">
                        <i class="fas fa-save me-1"></i> Simpan Profil
                    </button>
                </form>
            </div>

            <div class="dashboard-card">
                <h5 class="mb-3 fs-6 fw-bold"><i class="fas fa-chart-pie me-2 text-primary"></i>Ringkasan Nilai</h5>
                
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-secondary border-opacity-10">
                    <span class="text-muted small">Total Transaksi</span>
                    <span class="font-monospace fw-bold small">{{ $customer->orders->count() }}x</span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-secondary border-opacity-10">
                    <span class="text-muted small">Total Belanja (LTV)</span>
                    <span class="font-monospace text-success fw-bold small">Rp {{ number_format($totalSpent, 0, ',', '.') }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2">
                    <span class="text-muted small">Rata-rata Order</span>
                    <span class="font-monospace fw-semibold small">Rp {{ number_format($averageOrderValue, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        {{-- Riwayat Pesanan Kanan --}}
        <div class="col-md-7 col-lg-8">
            <div class="dashboard-card">
                <div class="card-header-line">
                    <h5 class="mb-0"><i class="fas fa-history me-2 text-primary"></i>Riwayat Pesanan</h5>
                </div>

                <div class="table-responsive border border-secondary border-opacity-10 rounded mt-3">
                    <table class="table table-sm table-bordered table-premium-dark align-middle mb-0">
                        <thead>
                            <tr>
                                <th>TGL PESANAN</th>
                                <th>NO INVOICE / ID</th>
                                <th>STATUS</th>
                                <th class="text-end">NILAI BERSIH (LTV)</th>
                                <th class="text-center" style="width: 100px;">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($customer->orders as $order)
                                <tr>
                                    <td style="font-size:0.78rem;">{{ $order->order_date->format('d M Y, H:i') }}</td>
                                    <td>
                                        <div class="fw-semibold" style="font-size:0.82rem;">{{ $order->invoice_number ?? $order->order_marketplace_id }}</div>
                                        <span class="text-muted small" style="font-size:0.7rem;">
                                            {{ $order->items->count() }} item produk
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $order->status_badge }}">
                                            {{ str_replace('_', ' ', $order->order_status) }}
                                        </span>
                                    </td>
                                    <td class="font-monospace fw-semibold text-success text-end" style="font-size:0.78rem;">
                                        Rp {{ number_format($order->net_amount, 0, ',', '.') }}
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('orders.show', $order->id) }}" class="btn btn-info btn-action-sm text-white" title="Detail Pesanan" data-bs-toggle="tooltip">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5">
                                        <i class="fas fa-history fa-2x mb-3 d-block text-secondary opacity-25"></i>
                                        <p class="text-muted mb-0 small">Belum ada riwayat pesanan yang valid.</p>
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
