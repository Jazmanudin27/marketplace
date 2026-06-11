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

<div class="stats-grid mb-4">
    <div class="stat-card stat-success">
        <div class="stat-icon"><i class="fas fa-ticket-alt"></i></div>
        <div class="stat-body">
            <div class="stat-value">{{ $activeCount }}</div>
            <div class="stat-label">Voucher Aktif</div>
        </div>
        <div class="stat-glow"></div>
    </div>
    <div class="stat-card stat-primary">
        <div class="stat-icon"><i class="fas fa-clock"></i></div>
        <div class="stat-body">
            <div class="stat-value">{{ $upcomingCount }}</div>
            <div class="stat-label">Akan Datang</div>
        </div>
        <div class="stat-glow"></div>
    </div>
    <div class="stat-card" style="opacity:.8;">
        <div class="stat-icon" style="background:rgba(107,114,128,.2); color:#6b7280;"><i class="fas fa-hourglass-end"></i></div>
        <div class="stat-body">
            <div class="stat-value" style="color:#6b7280;">{{ $expiredCount }}</div>
            <div class="stat-label">Berakhir</div>
        </div>
        <div class="stat-glow"></div>
    </div>
</div>

<div class="dashboard-card">
    <div class="card-header-line">
        <h3><i class="fas fa-tags"></i> Daftar Voucher</h3>
        <a href="{{ route('vouchers.create') }}" class="btn-primary-sm" id="btn-create-voucher">
            <i class="fas fa-plus"></i> Buat Voucher
        </a>
    </div>

    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama</th>
                    <th>Diskon</th>
                    <th>Min. Belanja</th>
                    <th>Masa Berlaku</th>
                    <th>Penggunaan</th>
                    <th>Toko</th>
                    <th>Status</th>
                    <th>Status Sync</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($vouchers as $voucher)
                <tr>
                    <td class="mono fw-bold" style="letter-spacing:.05em; font-size:0.9rem;">
                        {{ $voucher->code }}
                    </td>
                    <td>{{ $voucher->name }}</td>
                    <td class="mono fw-bold" style="color:#6c63ff;">
                        {{ $voucher->discount_display }}
                        @if($voucher->type === 'percentage' && $voucher->max_discount)
                            <br><small class="text-muted">maks Rp {{ number_format($voucher->max_discount, 0, ',', '.') }}</small>
                        @endif
                    </td>
                    <td class="mono">
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
                    <td style="text-align:center;">
                        {{ number_format($voucher->used_count) }}
                        @if($voucher->usage_limit)
                            / {{ number_format($voucher->usage_limit) }}
                        @else
                            / ∞
                        @endif
                    </td>
                    <td>
                        @if($voucher->store)
                            <span class="channel-tag channel-{{ $voucher->store->channel->code }}">
                                {{ $voucher->store->store_name }}
                            </span>
                        @else
                            <span class="text-muted" style="font-size:0.8rem;">Semua Toko</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge" style="background:{{ $voucher->status_color }}20; color:{{ $voucher->status_color }}; border:1px solid {{ $voucher->status_color }}40; font-size:0.72rem; padding:3px 10px;">
                            {{ $voucher->status_label }}
                        </span>
                    </td>
                    <td>
                        @if($voucher->store && $voucher->store->channel->code === 'tiktok')
                            <span style="font-size:0.72rem; color:#d97706;" title="TikTok API tidak mendukung pembuatan voucher otomatis"><i class="fas fa-info-circle"></i> Manual</span>
                        @elseif($voucher->marketplace_voucher_id)
                            <span style="font-size:0.72rem; color:#10b981;"><i class="fas fa-check-circle"></i> Ter-sync</span><br>
                            <span style="font-size:0.68rem; color:var(--text-secondary);">ID: {{ $voucher->marketplace_voucher_id }}</span>
                        @else
                            <span style="font-size:0.72rem; color:var(--text-secondary);">-</span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex; gap:4px; flex-wrap:wrap;">
                            <a href="{{ route('vouchers.edit', $voucher) }}" class="btn-primary-sm" style="background:#3b82f6; border-color:#3b82f6; font-size:0.72rem; padding:3px 8px;">
                                <i class="fas fa-edit"></i>
                            </a>

                             @if($voucher->store && $voucher->store->channel->code === 'shopee' && !$voucher->marketplace_voucher_id)
                            <form action="{{ route('vouchers.sync_shopee', $voucher) }}" method="POST" style="margin:0;">
                                @csrf
                                <button type="submit" class="btn-primary-sm" style="background:#ee4d2d; border-color:#ee4d2d; font-size:0.72rem; padding:3px 8px;" title="Sync ke Shopee"
                                    onclick="return confirm('Sync voucher ini ke Shopee?')">
                                    <i class="fas fa-upload"></i> Shopee
                                </button>
                            </form>
                            @endif

                            @if($voucher->store && $voucher->store->channel->code === 'tiktok')
                                <button type="button" class="btn-primary-sm" style="background:var(--border); border-color:var(--border); color:var(--text-secondary); font-size:0.72rem; padding:3px 8px; cursor:not-allowed; opacity: 0.65;" 
                                    title="Buat kupon dengan kode yang sama di TikTok Seller Center" disabled>
                                    <i class="fas fa-exclamation-triangle"></i> Manual
                                </button>
                            @endif

                            @if($voucher->marketplace_voucher_id && !$voucher->is_expired)
                            <form action="{{ route('vouchers.end_shopee', $voucher) }}" method="POST" style="margin:0;">
                                @csrf
                                <button type="submit" class="btn-primary-sm" style="background:#ef4444; border-color:#ef4444; font-size:0.72rem; padding:3px 8px;" title="Akhiri di Shopee"
                                    onclick="return confirm('Yakin ingin mengakhiri voucher di Shopee? Tindakan ini tidak dapat dibatalkan.')">
                                    <i class="fas fa-stop"></i>
                                </button>
                            </form>
                            @endif

                            <form action="{{ route('vouchers.destroy', $voucher) }}" method="POST" style="margin:0;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-primary-sm" style="background:#6b7280; border-color:#6b7280; font-size:0.72rem; padding:3px 8px;"
                                    onclick="return confirm('Hapus voucher {{ $voucher->code }}?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" style="text-align:center; padding:3rem; color:var(--text-secondary);">
                        <i class="fas fa-tags" style="font-size:2.5rem; opacity:.3; display:block; margin-bottom:.75rem;"></i>
                        Belum ada voucher. <a href="{{ route('vouchers.create') }}" style="color:var(--primary);">Buat voucher pertama Anda</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:1rem;">{{ $vouchers->links() }}</div>
</div>
@endsection
