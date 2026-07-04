@extends('layouts.app')
@section('title', 'Manajemen Flash Sale')
@section('page-title', 'Manajemen Flash Sale')

@section('topbar-actions')
    <a href="{{ route('marketing.flash_sales.calculator') }}" class="btn btn-sm btn-outline-light me-2 fw-bold">
        <i class="bi bi-calculator me-1"></i> Simulator Profit Margin
    </a>
    <a href="{{ route('marketing.flash_sales.create') }}" class="btn btn-sm btn-light text-primary fw-bold px-3">
        <i class="bi bi-plus-circle me-1"></i> Buat Flash Sale Baru
    </a>
@endsection

@section('content')
    {{-- Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 bg-danger text-white">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-white-50 text-uppercase fw-bold" style="font-size:.7rem;">Event Berlangsung</small>
                            <h3 class="fw-bold mb-0 mt-1 text-white">{{ $activeSales->count() }} Event</h3>
                        </div>
                        <div class="fs-1 text-white opacity-75"><i class="bi bi-lightning-charge-fill"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 bg-white">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-secondary text-uppercase fw-bold" style="font-size:.7rem;">Total Omset Flash Sale</small>
                            <h4 class="fw-bold mb-0 mt-1 text-success">Rp {{ number_format($totalActiveOmzet, 0, ',', '.') }}</h4>
                        </div>
                        <div class="fs-2 text-success opacity-75"><i class="bi bi-cash-stack"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 bg-white">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-secondary text-uppercase fw-bold" style="font-size:.7rem;">Penyerapan Stok Kuota</small>
                            @php
                                $overallRate = $totalActiveQuota > 0 ? round(($totalActiveSoldCount / $totalActiveQuota) * 100, 1) : 0;
                            @endphp
                            <h4 class="fw-bold mb-0 mt-1 text-dark">{{ $overallRate }}%</h4>
                            <small class="text-muted" style="font-size:.7rem;">{{ $totalActiveSoldCount }} / {{ $totalActiveQuota }} Terjual</small>
                        </div>
                        <div class="fs-2 text-primary opacity-75"><i class="bi bi-pie-chart-fill"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 bg-white">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-secondary text-uppercase fw-bold" style="font-size:.7rem;">Event Akan Datang</small>
                            <h4 class="fw-bold mb-0 mt-1 text-warning">{{ $upcomingSales->count() }} Event</h4>
                        </div>
                        <div class="fs-2 text-warning opacity-75"><i class="bi bi-calendar-event"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-2 px-3">
            <form method="GET" action="{{ route('marketing.flash_sales.index') }}">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-md-4">
                        <div class="d-flex align-items-center gap-2">
                            <label class="form-label fw-semibold text-secondary mb-0 small text-uppercase" style="font-size:.7rem;min-width:60px;">Status:</label>
                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">🌐 Semua Status</option>
                                <option value="active"   {{ strtolower($status ?? '') === 'active'   ? 'selected' : '' }}>⚡ Sedang Berlangsung (ACTIVE)</option>
                                <option value="upcoming" {{ strtolower($status ?? '') === 'upcoming' ? 'selected' : '' }}>⏳ Akan Datang (UPCOMING)</option>
                                <option value="ended"    {{ strtolower($status ?? '') === 'ended'    ? 'selected' : '' }}>🏁 Berakhir (ENDED)</option>
                                <option value="draft"    {{ strtolower($status ?? '') === 'draft'    ? 'selected' : '' }}>📝 Draft</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="d-flex align-items-center gap-2">
                            <label class="form-label fw-semibold text-secondary mb-0 small text-uppercase" style="font-size:.7rem;min-width:50px;">Toko:</label>
                            <select name="store_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">Semua Toko</option>
                                @foreach($stores as $s)
                                    <option value="{{ $s->id }}" {{ ($storeId ?? '') == $s->id ? 'selected' : '' }}>
                                        {{ $s->name }} ({{ strtoupper($s->channel->name ?? '') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Event Grid / List --}}
    @if($allSales->isEmpty())
        <div class="card border-0 shadow-sm text-center py-5">
            <div class="card-body">
                <i class="bi bi-lightning-charge fs-1 text-muted opacity-50 mb-3 d-block"></i>
                <h5 class="fw-bold text-dark mb-1">Belum Ada Event Flash Sale</h5>
                <p class="text-muted small mb-3">Buat jadwal Flash Sale pertama Anda untuk mengalokasikan stok diskon berbatas waktu.</p>
                <a href="{{ route('marketing.flash_sales.create') }}" class="btn btn-primary btn-sm px-4 fw-bold">
                    <i class="bi bi-plus-circle me-1"></i> Buat Flash Sale Baru
                </a>
            </div>
        </div>
    @else
        <div class="row g-3">
            @foreach($allSales as $sale)
                @php
                    $compStatus = $sale->computed_status;
                    $badgeClass = $sale->status_badge_class;
                    $statusLabel = $sale->status_label;
                    $isLive = $compStatus === 'ACTIVE';
                    $isUpcoming = $compStatus === 'UPCOMING';
                @endphp
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100 {{ $isLive ? 'border-top border-4 border-danger' : '' }}">
                        <div class="card-header bg-transparent py-3 px-3 border-bottom-0 d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <span class="badge {{ $badgeClass }} fw-bold rounded-pill px-2 py-1 mb-2" style="font-size:.68rem;">
                                    {{ $statusLabel }}
                                </span>
                                <h6 class="fw-bold text-dark mb-1 lh-sm">{{ $sale->title }}</h6>
                                <small class="text-muted">
                                    <i class="bi bi-shop me-1"></i>{{ $sale->store ? $sale->store->name : 'Semua Toko' }}
                                </small>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light border-0 py-0 px-2" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                    <li><a class="dropdown-item small" href="{{ route('marketing.flash_sales.show', $sale->id) }}"><i class="bi bi-eye me-2 text-primary"></i> Detail & Produk</a></li>
                                    <li><a class="dropdown-item small" href="{{ route('marketing.flash_sales.edit', $sale->id) }}"><i class="bi bi-pencil me-2 text-warning"></i> Edit Jadwal</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form action="{{ route('marketing.flash_sales.destroy', $sale->id) }}" method="POST" onsubmit="return confirm('Yakin hapus event Flash Sale ini?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="dropdown-item small text-danger"><i class="bi bi-trash me-2"></i> Hapus Event</button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="card-body px-3 py-2">
                            {{-- Countdown Widget #1 --}}
                            @if($isLive)
                                <div class="p-2 bg-danger bg-opacity-10 rounded text-danger mb-3 border border-danger border-opacity-25">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="fw-bold text-uppercase" style="font-size:.65rem;"><i class="bi bi-alarm-fill me-1"></i> Berakhir Dalam:</small>
                                        <div class="fw-bold font-monospace fs-6 flash-countdown" data-target="{{ $sale->end_time->toIso8601String() }}">00:00:00</div>
                                    </div>
                                </div>
                            @elseif($isUpcoming)
                                <div class="p-2 bg-warning bg-opacity-10 rounded text-warning-emphasis mb-3 border border-warning border-opacity-25">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="fw-bold text-uppercase" style="font-size:.65rem;"><i class="bi bi-clock-history me-1"></i> Dimulai Dalam:</small>
                                        <div class="fw-bold font-monospace fs-6 flash-countdown" data-target="{{ $sale->start_time->toIso8601String() }}">00:00:00</div>
                                    </div>
                                </div>
                            @endif

                            {{-- Time Slot --}}
                            <div class="mb-3 small">
                                <div class="d-flex align-items-center text-muted mb-1">
                                    <i class="bi bi-calendar-check me-2 text-primary"></i>
                                    <span>Mulai: <strong>{{ $sale->start_time->format('d M Y H:i') }}</strong></span>
                                </div>
                                <div class="d-flex align-items-center text-muted">
                                    <i class="bi bi-calendar-x me-2 text-danger"></i>
                                    <span>Selesai: <strong>{{ $sale->end_time->format('d M Y H:i') }}</strong></span>
                                </div>
                            </div>

                            {{-- Progress Bar Kuota --}}
                            <div class="mb-2">
                                <div class="d-flex justify-content-between align-items-center mb-1" style="font-size:.72rem;">
                                    <span class="text-muted">Stok Kuota Terjual:</span>
                                    <span class="fw-bold text-dark">{{ $sale->total_sold_count }} / {{ $sale->total_quota }} unit ({{ $sale->sell_through_rate }}%)</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar {{ $isLive ? 'bg-danger' : 'bg-primary' }}" role="progressbar" style="width: {{ min(100, $sale->sell_through_rate) }}%"></div>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer bg-transparent py-2 px-3 border-top-0 d-flex justify-content-between align-items-center">
                            <small class="text-muted"><i class="bi bi-box-seam me-1"></i>{{ $sale->items->count() }} Produk SKU</small>
                            <a href="{{ route('marketing.flash_sales.show', $sale->id) }}" class="btn btn-sm btn-outline-primary rounded-3 px-3 fw-semibold">
                                Detail Event <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        function updateCountdowns() {
            document.querySelectorAll('.flash-countdown').forEach(el => {
                const targetDate = new Date(el.getAttribute('data-target')).getTime();
                const now = new Date().getTime();
                const diff = targetDate - now;

                if (diff <= 0) {
                    el.innerHTML = "00:00:00";
                    return;
                }

                const hours = Math.floor(diff / (1000 * 60 * 60));
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((diff % (1000 * 60)) / 1000);

                const h = hours.toString().padStart(2, '0');
                const m = minutes.toString().padStart(2, '0');
                const s = seconds.toString().padStart(2, '0');

                el.innerHTML = `${h}:${m}:${s}`;
            });
        }

        updateCountdowns();
        setInterval(updateCountdowns, 1000);
    });
    </script>
    @endpush
@endsection
