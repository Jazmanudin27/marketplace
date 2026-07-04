@extends('layouts.app')
@section('title', 'Manajemen Diskon Bertingkat')
@section('page-title', 'Diskon Bertingkat (Tiered Discount)')

@section('topbar-actions')
    <a href="{{ route('marketing.tiered_discounts.create') }}" class="btn btn-sm btn-light text-primary fw-bold px-3">
        <i class="bi bi-plus-circle me-1"></i> Buat Diskon Bertingkat Baru
    </a>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
            <div>
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-layers-half text-primary me-2"></i>Daftar Aturan Diskon Berdasarkan Kuantitas</h6>
                <small class="text-muted">Potongan harga otomatis berdasarkan jumlah grosir / kuantitas pembelian</small>
            </div>
            <a href="{{ route('marketing.tiered_discounts.create') }}" class="btn btn-primary btn-sm rounded-3 fw-bold px-3">
                <i class="bi bi-plus-lg me-1"></i> Buat Baru
            </a>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="text-uppercase text-muted" style="font-size:.72rem;letter-spacing:.6px;">
                            <th class="border-0 px-3 py-3">Nama Aturan</th>
                            <th class="border-0 px-3 py-3">Produk Target</th>
                            <th class="border-0 px-3 py-3">Tier Kuantitas & Diskon</th>
                            <th class="border-0 px-3 py-3">Status</th>
                            <th class="border-0 px-3 py-3 text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($discounts as $d)
                            <tr>
                                <td class="px-3 py-3">
                                    <div class="fw-bold text-dark small">{{ $d->name }}</div>
                                    @if($d->notes)
                                        <div class="text-muted" style="font-size:.72rem;">{{ $d->notes }}</div>
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    @if($d->masterProduct)
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2 py-1 small">
                                            <i class="bi bi-box me-1"></i>{{ $d->masterProduct->name }}
                                        </span>
                                    @else
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill px-2 py-1 small">
                                            🌐 Semua Produk (Global)
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($d->tiers as $tier)
                                            @php
                                                $range = $tier->max_qty ? "{$tier->min_qty} - {$tier->max_qty} pcs" : "≥ {$tier->min_qty} pcs";
                                                $disc  = $tier->discount_type === 'percentage' ? "{$tier->discount_value}%" : "Rp " . number_format($tier->discount_value, 0, ',', '.');
                                            @endphp
                                            <span class="badge bg-light text-dark border rounded-pill px-2 py-1" style="font-size:.7rem;">
                                                <strong>{{ $range }}</strong>: <span class="text-danger fw-bold">-{{ $disc }}</span>
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-3 py-3">
                                    <form action="{{ route('marketing.tiered_discounts.toggle', $d->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm p-0 border-0">
                                            @if($d->is_active)
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2 py-1" style="font-size:.7rem;">
                                                    <i class="bi bi-circle-fill me-1" style="font-size:.45rem;"></i>Aktif
                                                </span>
                                            @else
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill px-2 py-1" style="font-size:.7rem;">
                                                    <i class="bi bi-circle me-1" style="font-size:.45rem;"></i>Nonaktif
                                                </span>
                                            @endif
                                        </button>
                                    </form>
                                </td>
                                <td class="px-3 py-3 text-end">
                                    <form action="{{ route('marketing.tiered_discounts.destroy', $d->id) }}" method="POST" onsubmit="return confirm('Hapus aturan diskon bertingkat ini?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-3 px-2" title="Hapus">
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted small">
                                    Belum ada aturan Diskon Bertingkat yang dibuat.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-3">
                {{ $discounts->links() }}
            </div>
        </div>
    </div>
@endsection
