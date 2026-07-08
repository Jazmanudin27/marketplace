@extends('layouts.app')
@section('title', 'Kebutuhan SPK dari Pesanan (PO)')
@section('page-title', 'Kebutuhan SPK dari Pesanan (PO)')

@section('content')
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center border-bottom">
        <div>
            <h6 class="fw-bold mb-0 text-dark">
                <i class="fas fa-shopping-cart me-2 text-primary"></i>Daftar Kebutuhan Produksi Produk Dipesan
            </h6>
            <small class="text-muted d-block mt-1">Daftar item pesanan masuk dari marketplace online (Shopee/Tiktok) dan offline yang belum terkirim.</small>
        </div>
        <div>
            <input type="text" id="search-requirements" class="form-control form-control-sm" placeholder="Cari SKU / nama produk / Invoice..." style="width: 280px;">
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle border mb-0" id="table-requirements">
                <thead class="table-light text-uppercase text-muted" style="font-size: 10px;">
                    <tr>
                        <th class="ps-4 text-center" style="width: 5%">No</th>
                        <th style="width: 15%">Sumber &amp; Toko</th>
                        <th style="width: 15%">No. Pesanan / PO</th>
                        <th style="width: 25%">Produk / SKU</th>
                        <th class="text-center" style="width: 10%">Qty Dipesan</th>
                        <th class="text-center" style="width: 10%">Stok Saat Ini</th>
                        <th class="text-center" style="width: 10%">Kekurangan</th>
                        <th class="text-center" style="width: 10%">Aksi SPK</th>
                    </tr>
                </thead>
                <tbody>
                    @php $no = 1; @endphp
                    @forelse ($requirements as $req)
                        @php
                            $isOnline = $req->source === 'Online';
                            $channelUpper = strtoupper($req->channel);
                            $badgeChannel = 'bg-secondary';
                            if (str_contains($channelUpper, 'SHOPEE')) $badgeChannel = 'bg-warning text-dark';
                            elseif (str_contains($channelUpper, 'TIKTOK')) $badgeChannel = 'bg-dark text-white';
                            elseif (str_contains($channelUpper, 'LAZADA')) $badgeChannel = 'bg-primary text-white';
                            elseif ($req->source === 'Offline') $badgeChannel = 'bg-info text-white';

                            $stockClass = 'text-success fw-bold';
                            $shortageClass = 'text-muted';
                            if ($req->shortage > 0) {
                                $stockClass = 'text-danger fw-bold';
                                $shortageClass = 'text-danger fw-bold';
                            }
                        @endphp
                        <tr class="req-row" data-name="{{ strtolower($req->product_name) }}" data-sku="{{ strtolower($req->sku) }}" data-ref="{{ strtolower($req->ref_number) }}">
                            <td class="ps-4 text-center text-muted small">{{ $no++ }}</td>
                            <td>
                                <span class="badge {{ $badgeChannel }} small px-2 py-1 mb-1 d-inline-block">{{ $req->channel }}</span>
                                <small class="text-muted d-block small">{{ $req->store }}</small>
                            </td>
                            <td>
                                <span class="fw-semibold text-dark small">{{ $req->ref_number }}</span>
                                <small class="text-muted d-block" style="font-size:10px;">{{ \Carbon\Carbon::parse($req->order_date)->format('d M Y, H:i') }}</small>
                            </td>
                            <td>
                                <span class="fw-semibold text-dark small d-block">{{ $req->product_name }}</span>
                                <code class="font-monospace text-muted" style="font-size: 11px;">SKU: {{ $req->sku }}</code>
                            </td>
                            <td class="text-center fw-bold text-dark small">{{ number_format($req->qty_ordered) }} {{ $req->unit }}</td>
                            <td class="text-center small {{ $stockClass }}">{{ number_format($req->current_stock) }} {{ $req->unit }}</td>
                            <td class="text-center small {{ $shortageClass }}">
                                @if($req->shortage > 0)
                                    <span class="badge bg-danger-subtle text-danger">-{{ number_format($req->shortage) }} {{ $req->unit }}</span>
                                @else
                                    <span class="badge bg-success-subtle text-success">Cukup</span>
                                @endif
                            </td>
                            <td class="text-center pe-3">
                                <form action="{{ route('production_orders.create_from_order') }}" method="POST" class="m-0"
                                    onsubmit="return confirm('Buat perintah kerja (SPK) produksi untuk produk ini?')">
                                    @csrf
                                    <input type="hidden" name="master_product_id" value="{{ $req->product_id }}">
                                    <input type="hidden" name="quantity" value="{{ $req->shortage > 0 ? $req->shortage : $req->qty_ordered }}">
                                    
                                    @if($req->shortage > 0)
                                        <button type="submit" class="btn btn-danger btn-sm fw-bold px-3 py-1">
                                            <i class="fas fa-hammer me-1"></i> SPK Produksi ({{ number_format($req->shortage) }})
                                        </button>
                                    @else
                                        <button type="submit" class="btn btn-outline-primary btn-sm fw-bold px-3 py-1">
                                            <i class="fas fa-plus me-1"></i> SPK Cadangan
                                        </button>
                                    @endif
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fas fa-clipboard-list fa-3x mb-3 opacity-25 d-block"></i>
                                Tidak ada antrean pesanan pelanggan (PO) yang belum dipenuhi saat ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#search-requirements').on('input', function() {
        const query = $(this).val().toLowerCase();
        $('.req-row').each(function() {
            const name = $(this).data('name') || '';
            const sku = $(this).data('sku') || '';
            const ref = $(this).data('ref') || '';
            if (name.includes(query) || sku.includes(query) || ref.includes(query)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
});
</script>
@endpush
