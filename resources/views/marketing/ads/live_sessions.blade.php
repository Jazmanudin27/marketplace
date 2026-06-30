@extends('layouts.app')

@section('title', 'TikTok LIVE Shopping Session Tracker')
@section('page-title', 'TikTok LIVE Shopping Tracker')

@section('topbar-actions')
    <a href="{{ route('marketing.ads.index') }}" class="btn btn-sm btn-light text-primary fw-bold px-3">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard Iklan
    </a>
@endsection

@section('content')
<div class="row g-3">

    {{-- ══ LEFT COLUMN: START LIVE FORM & HOST LEADERBOARD ══ --}}
    <div class="col-lg-4">
        
        {{-- 1. Start LIVE Form --}}
        <div class="card border shadow-sm rounded-3 mb-3">
            <div class="card-header bg-danger bg-opacity-10 border-bottom py-2 px-3 d-flex align-items-center gap-2">
                <span class="bg-danger rounded-circle d-inline-flex align-items-center justify-content-center animate-pulse"
                    style="width:28px;height:28px;flex-shrink:0;">
                    <i class="bi bi-record-circle-fill text-white small"></i>
                </span>
                <div>
                    <div class="fw-bold text-dark small lh-sm">Mulai Sesi LIVE TikTok</div>
                    <div class="text-muted" style="font-size:.72rem;">Catat Host & jadwalkan siaran langsung</div>
                </div>
            </div>
            <div class="card-body p-3">
                <form action="{{ route('marketing.ads.live_sessions.start') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="title" class="form-label fw-bold text-secondary small text-uppercase"
                            style="letter-spacing:.5px;font-size:.7rem;">Judul / Tema Sesi LIVE</label>
                        <input type="text" name="title" id="title"
                            class="form-control form-control-sm rounded-3"
                            placeholder="Contoh: Flash Sale 8.8 Sore" required>
                    </div>

                    <div class="mb-3">
                        <label for="store_id" class="form-label fw-bold text-secondary small text-uppercase"
                            style="letter-spacing:.5px;font-size:.7rem;">Pilih Toko TikTok Shop</label>
                        <select name="store_id" id="store_id" class="form-select form-select-sm rounded-3" required>
                            @forelse($stores as $st)
                                <option value="{{ $st->id }}">{{ $st->store_name }}</option>
                            @empty
                                <option value="" disabled>Hubungkan toko TikTok Shop terlebih dahulu</option>
                            @endforelse
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="host_name" class="form-label fw-bold text-secondary small text-uppercase"
                            style="letter-spacing:.5px;font-size:.7rem;">Nama Host / Talent</label>
                        <input type="text" name="host_name" id="host_name"
                            class="form-control form-control-sm rounded-3"
                            placeholder="Contoh: Amelia Cantika" required>
                    </div>

                    <button type="submit" class="btn btn-danger w-100 rounded-3 fw-bold py-2" @if($stores->isEmpty()) disabled @endif>
                        <i class="bi bi-play-circle-fill me-1"></i> Mulai LIVE Sekarang
                    </button>
                </form>
            </div>
        </div>

        {{-- 2. Host Leaderboard --}}
        <div class="card border shadow-sm rounded-3">
            <div class="card-header bg-white border-bottom py-2.5 px-3">
                <div class="fw-bold text-dark small lh-sm">🏆 Peringkat Penjualan Host</div>
                <div class="text-muted" style="font-size:.7rem;">Kontribusi penjualan host saat sesi LIVE</div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size:.8rem;">
                        <thead class="table-light">
                            <tr class="text-uppercase text-muted" style="font-size:.65rem; letter-spacing:.5px; font-weight:700;">
                                <th class="px-3 py-2">Host</th>
                                <th class="px-3 py-2 text-center">Sesi</th>
                                <th class="px-3 py-2 text-end">Total Omzet</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($hosts as $host)
                                <tr>
                                    <td class="px-3 py-2.5">
                                        <strong class="text-dark">{{ $host['host_name'] }}</strong>
                                        <div class="text-muted" style="font-size:.7rem;">{{ $host['total_orders'] }} orders</div>
                                    </td>
                                    <td class="px-3 py-2.5 text-center fw-semibold">{{ $host['total_sessions'] }}x</td>
                                    <td class="px-3 py-2.5 text-end text-success fw-bold">
                                        Rp {{ number_format($host['total_revenue'], 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">Belum ada peringkat host.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>{{-- /col-lg-4 --}}

    {{-- ══ RIGHT COLUMN: LIVE SESSIONS LOG ══ --}}
    <div class="col-lg-8">
        <div class="card border shadow-sm rounded-3">
            <div class="card-header bg-white border-bottom py-2.5 px-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <span class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center"
                        style="width:28px;height:28px;">
                        <i class="bi bi-broadcast fs-6"></i>
                    </span>
                    <div>
                        <div class="fw-bold text-dark small lh-sm">Riwayat Sesi LIVE TikTok</div>
                        <div class="text-muted" style="font-size:.7rem;">
                            Jadwal siaran langsung dan rekonsiliasi pesanan masuk
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size:.82rem;">
                        <thead class="table-light">
                            <tr class="text-uppercase text-muted" style="font-size:.7rem; letter-spacing:.5px; font-weight:700;">
                                <th class="px-3 py-2.5">Detail Sesi</th>
                                <th class="px-3 py-2.5">Toko / Host</th>
                                <th class="px-3 py-2.5">Waktu</th>
                                <th class="px-3 py-2.5">Status</th>
                                <th class="px-3 py-2.5 text-center">Orders</th>
                                <th class="px-3 py-2.5 text-end">Omzet LIVE</th>
                                <th class="px-3 py-2.5 text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sessions as $session)
                                <tr>
                                    <td class="px-3 py-3">
                                        <strong class="text-dark d-block">{{ $session->title }}</strong>
                                        <span class="text-muted" style="font-size:.72rem;">ID: #{{ $session->id }}</span>
                                    </td>
                                    <td class="px-3 py-3">
                                        <span class="badge bg-light text-dark border px-2 py-0.5 rounded" style="font-size:.7rem;">
                                            {{ $session->store->store_name }}
                                        </span>
                                        <div class="mt-1" style="font-size:.78rem;">
                                            Host: <strong class="text-primary">{{ $session->host_name }}</strong>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 text-muted" style="font-size:.75rem;">
                                        <div>Mulai: {{ $session->start_time->format('d/m H:i') }}</div>
                                        @if($session->end_time)
                                            <div>Selesai: {{ $session->end_time->format('d/m H:i') }}</div>
                                        @else
                                            <div class="text-danger fw-semibold"><i class="bi bi-clock-fill me-1"></i>Sedang LIVE</div>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3">
                                        @if($session->status === 'live')
                                            <span class="badge bg-danger bg-opacity-10 text-danger fw-bold rounded-pill px-2.5 py-1 animate-pulse">
                                                🔴 LIVE
                                            </span>
                                        @else
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary fw-semibold rounded-pill px-2.5 py-1">
                                                SELESAI
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-center fw-semibold">
                                        {{ number_format($session->total_orders) }} order
                                    </td>
                                    <td class="px-3 py-3 text-end text-success fw-bold">
                                        Rp {{ number_format($session->total_revenue, 0, ',', '.') }}
                                    </td>
                                    <td class="px-3 py-3 text-end">
                                        <div class="d-flex gap-1 justify-content-end">
                                            @if($session->status === 'live')
                                                <form action="{{ route('marketing.ads.live_sessions.end', $session->id) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-danger px-2.5 rounded-pill fw-bold text-nowrap" style="font-size:.7rem;">
                                                        <i class="bi bi-stop-circle-fill"></i> Akhiri
                                                    </button>
                                                </form>
                                            @endif
                                            <form action="{{ route('marketing.ads.live_sessions.destroy', $session->id) }}" method="POST"
                                                onsubmit="return confirm('Hapus sesi live ini beserta datanya di ERP?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger border-0 p-1">
                                                    <i class="bi bi-trash-fill fs-6"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="bi bi-broadcast d-block fs-1 mb-2 opacity-25"></i>
                                        <div class="fw-bold text-dark small mb-1">Belum Ada Sesi LIVE Terdaftar</div>
                                        <div class="small">Mulai sesi LIVE perdana Anda lewat formulir di sebelah kiri.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>{{-- /col-lg-8 --}}

</div>
@endsection
