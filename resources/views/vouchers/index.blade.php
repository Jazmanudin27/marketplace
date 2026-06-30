@extends('layouts.app')
@section('title', 'Voucher & Promo')
@section('page-title', 'Voucher & Promo')

@section('content')

{{-- Header stats --}}
@php
    $activeCount   = $vouchers->getCollection()->filter(fn($v) => $v->status_label === 'Aktif')->count();
    $upcomingCount = $vouchers->getCollection()->filter(fn($v) => $v->status_label === 'Akan Datang')->count();
    $expiredCount  = $vouchers->getCollection()->filter(fn($v) => $v->is_expired)->count();
@endphp

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border border-start border-4 border-success shadow-sm h-100">
            <div class="card-body py-2.5 px-3 d-flex align-items-center gap-3">
                <div class="bg-success bg-opacity-10 text-success rounded p-2 fs-4">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <div>
                    <h3 class="mb-0 fw-bold text-success fs-5">{{ $activeCount }}</h3>
                    <small class="text-muted">Voucher Aktif</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border border-start border-4 border-primary shadow-sm h-100">
            <div class="card-body py-2.5 px-3 d-flex align-items-center gap-3">
                <div class="bg-primary bg-opacity-10 text-primary rounded p-2 fs-4">
                    <i class="fas fa-clock"></i>
                </div>
                <div>
                    <h3 class="mb-0 fw-bold text-primary fs-5">{{ $upcomingCount }}</h3>
                    <small class="text-muted">Akan Datang</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border border-start border-4 border-secondary shadow-sm h-100" style="opacity: 0.85;">
            <div class="card-body py-2.5 px-3 d-flex align-items-center gap-3">
                <div class="bg-secondary bg-opacity-10 text-secondary rounded p-2 fs-4">
                    <i class="fas fa-hourglass-end"></i>
                </div>
                <div>
                    <h3 class="mb-0 fw-bold text-secondary fs-5">{{ $expiredCount }}</h3>
                    <small class="text-muted">Berakhir</small>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border shadow-sm">
    <div class="card-header bg-light d-flex justify-content-between align-items-center py-2.5 px-3 border-bottom">
        <h6 class="m-0 fw-bold text-primary"><i class="fas fa-tags me-2"></i> Daftar Voucher</h6>
        <a href="{{ route('vouchers.create') }}" class="btn btn-primary btn-sm px-3" id="btn-create-voucher">
            <i class="fas fa-plus me-1"></i> Buat Voucher
        </a>
    </div>

    <div class="card-body p-3">
        <div class="table-responsive rounded border mt-2">
            <table class="table table-sm table-bordered table-striped table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Kode</th>
                        <th>Nama</th>
                        <th>Diskon</th>
                        <th>Min. Belanja</th>
                        <th>Masa Berlaku</th>
                        <th>Penggunaan</th>
                        <th>Performa Penjualan (ROI)</th>
                        <th>Toko</th>
                        <th>Status</th>
                        <th>Status Sync</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vouchers as $voucher)
                    <tr>
                        <td class="font-monospace fw-bold text-dark small" style="letter-spacing:.05em;">
                            {{ $voucher->code }}
                        </td>
                        <td class="small">{{ $voucher->name }}</td>
                        <td class="font-monospace fw-bold small text-primary">
                            {{ $voucher->discount_display }}
                            @if($voucher->type === 'percentage' && $voucher->max_discount)
                                <br><small class="text-muted">maks Rp {{ number_format($voucher->max_discount, 0, ',', '.') }}</small>
                            @endif
                        </td>
                        <td class="font-monospace small">
                            @if($voucher->min_purchase > 0)
                                Rp {{ number_format($voucher->min_purchase, 0, ',', '.') }}
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td style="font-size:0.8rem;">
                            <div>{{ $voucher->start_date->format('d M Y') }}</div>
                            <div class="text-muted">s/d {{ $voucher->end_date->format('d M Y') }}</div>
                        </td>
                        <td class="text-center font-monospace small">
                            {{ number_format($voucher->used_count) }}
                            @if($voucher->usage_limit)
                                / {{ number_format($voucher->usage_limit) }}
                            @else
                                / ∞
                            @endif
                        </td>
                        <td>
                            @php
                                $stat = $voucherStats->get(strtoupper($voucher->code)) ?? null;
                                $uses = $stat ? $stat->total_uses : 0;
                                $revenue = $stat ? $stat->total_revenue : 0;
                                $discounts = $stat ? $stat->total_discounts : 0;
                                $roi = $discounts > 0 ? $revenue / $discounts : 0;
                            @endphp
                            @if($uses > 0)
                                <div style="font-size:0.8rem;">
                                    <span class="text-success fw-bold">Rp {{ number_format($revenue, 0, ',', '.') }}</span>
                                    <div class="text-muted" style="font-size:0.72rem;">Cost: Rp {{ number_format($discounts, 0, ',', '.') }}</div>
                                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill fw-bold" style="font-size:0.65rem; padding: 2px 6px;">
                                        {{ number_format($roi, 1) }}x ROI
                                    </span>
                                </div>
                            @else
                                <span class="text-muted small">Belum terpakai</span>
                            @endif
                        </td>
                        <td>
                            @if($voucher->store)
                                <span class="badge {{ $voucher->store->channel->code === 'shopee' ? 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25' : 'bg-dark text-white' }} small">
                                    {{ $voucher->store->store_name }}
                                </span>
                            @else
                                <span class="text-muted small">Semua Toko</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge" style="background:{{ $voucher->status_color }}20; color:{{ $voucher->status_color }}; border:1px solid {{ $voucher->status_color }}40; font-size:0.72rem; padding:3px 10px;">
                                {{ $voucher->status_label }}
                            </span>
                        </td>
                        <td>
                            @if($voucher->store && $voucher->store->channel->code === 'tiktok')
                                <span class="small text-warning" title="TikTok API tidak mendukung pembuatan voucher otomatis"><i class="fas fa-info-circle"></i> Manual</span>
                            @elseif($voucher->marketplace_voucher_id)
                                <span class="small text-success"><i class="fas fa-check-circle"></i> Ter-sync</span><br>
                                <span class="text-muted font-monospace" style="font-size:0.68rem;">ID: {{ $voucher->marketplace_voucher_id }}</span>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex gap-1 flex-wrap">
                                <a href="{{ route('vouchers.edit', $voucher) }}" class="btn btn-primary btn-sm" title="Edit Voucher">
                                    <i class="fas fa-edit"></i>
                                </a>

                                @if($voucher->store && $voucher->store->channel->code === 'shopee' && !$voucher->marketplace_voucher_id)
                                <form action="{{ route('vouchers.sync_shopee', $voucher) }}" method="POST" class="m-0">
                                    @csrf
                                    <button type="submit" class="btn btn-warning text-dark btn-sm" title="Sync ke Shopee"
                                        onclick="return confirm('Sync voucher ini ke Shopee?')">
                                        <i class="fas fa-upload"></i> Shopee
                                    </button>
                                </form>
                                @endif

                                @if($voucher->store && $voucher->store->channel->code === 'tiktok')
                                    <button type="button" class="btn btn-secondary btn-sm" style="cursor:not-allowed; opacity: 0.65;" 
                                        title="Buat kupon dengan kode yang sama di TikTok Seller Center" disabled>
                                        <i class="fas fa-exclamation-triangle"></i> Manual
                                    </button>
                                @endif

                                @if($voucher->marketplace_voucher_id && !$voucher->is_expired)
                                <form action="{{ route('vouchers.end_shopee', $voucher) }}" method="POST" class="m-0">
                                    @csrf
                                    <button type="submit" class="btn btn-danger btn-sm" title="Akhiri di Shopee"
                                        onclick="return confirm('Yakin ingin mengakhiri voucher di Shopee? Tindakan ini tidak dapat dibatalkan.')">
                                        <i class="fas fa-stop"></i>
                                    </button>
                                </form>
                                @endif

                                <form action="{{ route('vouchers.destroy', $voucher) }}" method="POST" class="m-0">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-secondary btn-sm"
                                        onclick="return confirm('Hapus voucher {{ $voucher->code }}?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-5 text-muted small">
                            <i class="fas fa-tags d-block mb-2 opacity-50" style="font-size: 2rem;"></i>
                            Belum ada voucher. <a href="{{ route('vouchers.create') }}" class="text-primary fw-bold">Buat voucher pertama Anda</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3 d-flex justify-content-center">
            {{ $vouchers->links() }}
        </div>
    </div>
</div>
@endsection
