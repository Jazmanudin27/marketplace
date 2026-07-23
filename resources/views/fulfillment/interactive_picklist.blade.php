@extends('layouts.app')
@section('title', 'Ambil Barang Gudang (Pick List Mode)')
@section('page-title', 'Ambil Barang Gudang')

@section('content')
<div class="container-fluid p-0">

    {{-- Header Action & Progress --}}
    <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
        <div class="card-body p-4">
            <div class="row align-items-center g-3">
                <div class="col-md-6">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <a href="{{ route('fulfillment.index') }}" class="btn btn-sm btn-light border rounded-circle p-2 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" title="Kembali ke Pemenuhan Pesanan">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <h4 class="fw-bold text-dark mb-0"><i class="fas fa-boxes text-primary me-2"></i>Rekap Pengambilan Barang Gudang</h4>
                    </div>
                    <small class="text-muted d-block ms-4">
                        Memproses <strong>{{ count($orders) }} Pesanan</strong> | Total <strong>{{ $totalPcs }} Pcs</strong> barang yang harus diambil.
                    </small>
                </div>
                <div class="col-md-6 text-md-end d-flex justify-content-md-end gap-2 align-items-center">
                    <button type="button" class="btn btn-outline-success btn-lg fw-bold px-3 rounded-3 shadow-sm" id="btnPickAll">
                        <i class="fas fa-check-double me-1"></i> Ambil Semua (Full All)
                    </button>
                    <form action="{{ route('fulfillment.confirm_picking') }}" method="POST" id="confirmPickingForm" class="d-inline">
                        @csrf
                        @foreach($orderIds as $oId)
                            <input type="hidden" name="order_ids[]" value="{{ $oId }}">
                        @endforeach
                        <button type="submit" class="btn btn-success btn-lg fw-bold px-4 rounded-3 shadow-sm" id="btnFinishPicking">
                            <i class="fas fa-check-circle me-2"></i> Selesai Ambil Barang
                        </button>
                    </form>
                </div>
            </div>

            {{-- Overall Progress Bar --}}
            <div class="mt-4 pt-3 border-top">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold small text-dark"><i class="fas fa-tasks me-1 text-primary"></i> Progress Pengambilan Barang:</span>
                    <span class="fw-bold text-primary fs-6"><span id="totalPickedCount">0</span> / {{ $totalPcs }} Pcs (<span id="totalProgressPercent">0</span>%)</span>
                </div>
                <div class="progress rounded-pill shadow-sm" style="height: 14px; background-color: #e2e8f0;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-success rounded-pill" id="totalProgressBar" role="progressbar" style="width: 0%;"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Search Bar --}}
    <div class="card border rounded shadow-sm bg-white mb-3 p-3">
        <div class="row g-2 align-items-center">
            <div class="col-md-6">
                <span class="fw-bold text-dark small"><i class="fas fa-list me-1 text-secondary"></i> Input Jumlah Pengambilan Per Barang</span>
            </div>
            <div class="col-md-6 col-lg-4 ms-auto">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" id="searchTableInput" class="form-control" placeholder="Cari SKU / nama barang..." autocomplete="off">
                </div>
            </div>
        </div>
    </div>

    {{-- Items Picking List Table --}}
    <div class="card border-0 shadow-sm rounded-3 bg-white">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="pickingTable">
                    <thead class="table-dark">
                        <tr class="small">
                            <th class="text-center" style="width: 50px;">NO</th>
                            <th style="width: 170px;">SKU / KODE</th>
                            <th>NAMA PRODUK & INVOICE</th>
                            <th class="text-center" style="width: 130px;">TIPE</th>
                            <th class="text-center" style="width: 130px;">TARGET</th>
                            <th class="text-center" style="width: 250px;">JUMLAH DIAMBIL</th>
                            <th class="text-center" style="width: 150px;">STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $no = 1; @endphp
                        @forelse($aggregated as $sku => $item)
                            <tr class="picking-row" id="row-{{ Str::slug($sku) }}" data-sku="{{ strtolower($sku) }}" data-name="{{ strtolower($item['name']) }}" data-target="{{ $item['target'] }}" data-picked="0">
                                <td class="text-center fw-bold text-muted">{{ $no++ }}</td>
                                <td>
                                    <div class="font-monospace fw-bold text-dark fs-6 sku-label">{{ $item['sku'] }}</div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark fs-6 mb-1">{{ $item['name'] }}</div>
                                    <div class="text-muted small" style="font-size: 11px;">
                                        Digunakan untuk: 
                                        @foreach(array_slice(array_unique($item['orders']), 0, 5) as $inv)
                                            <span class="badge bg-light text-dark border font-monospace me-1">{{ $inv }}</span>
                                        @endforeach
                                        @if(count(array_unique($item['orders'])) > 5)
                                            <span class="text-muted small fw-semibold">+{{ count(array_unique($item['orders'])) - 5 }} order lagi</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-center">
                                    @if($item['is_po'])
                                        <span class="badge bg-purple text-white px-2 py-1 fw-bold" style="background-color: #8b5cf6;" title="Barang Pre-Order / Produksi SPK">
                                            <i class="fas fa-clock me-1"></i> PO / SPK
                                        </span>
                                        @if(!empty($item['spk_no']))
                                            <div class="small font-monospace text-primary fw-semibold mt-1" style="font-size: 10px;">#{{ $item['spk_no'] }}</div>
                                        @endif
                                    @else
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 fw-bold">
                                            <i class="fas fa-check-circle me-1"></i> READY
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="fs-5 fw-bold text-dark font-monospace">
                                        <span class="target-qty">{{ $item['target'] }}</span> Pcs
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex align-items-center justify-content-center gap-1">
                                        <button type="button" class="btn btn-outline-danger btn-sm btn-minus px-2" title="Kurangi 1">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <input type="number" class="form-control form-control-sm text-center font-monospace fw-bold input-picked-qty" style="width: 75px;" min="0" max="{{ $item['target'] }}" value="0">
                                        <button type="button" class="btn btn-outline-primary btn-sm btn-plus px-2" title="Tambah 1">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-success btn-complete-item px-2 fw-bold" title="Set Ambil Semua">
                                            <i class="fas fa-check"></i> Full
                                        </button>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="item-status-badge">
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1 small">
                                            Belum Diambil
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center p-5 text-muted">
                                    <i class="fas fa-box-open fs-1 text-muted opacity-25 mb-3 d-block"></i>
                                    <h6 class="fw-bold text-dark mb-1">Belum Ada Pesanan yang Siap Diambil</h6>
                                    <p class="small text-muted mb-0">Silakan cetak resi thermal terlebih dahulu di menu <strong>Pemenuhan Pesanan</strong>, atau centang pesanan spesifik yang ingin Anda ambil barangnya.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
    $(document).ready(function() {
        const totalPcs = {{ $totalPcs }};
        
        // Recalculate totals & progress bar
        function updateProgress() {
            let totalPicked = 0;
            $('.picking-row').each(function() {
                const picked = parseInt($(this).attr('data-picked')) || 0;
                totalPicked += picked;
            });

            $('#totalPickedCount').text(totalPicked);
            const percent = totalPcs > 0 ? Math.min(100, Math.round((totalPicked / totalPcs) * 100)) : 0;
            $('#totalProgressPercent').text(percent);
            $('#totalProgressBar').css('width', percent + '%');

            if (percent === 100) {
                $('#btnFinishPicking').removeClass('btn-success').addClass('btn-success border-3 shadow-lg btn-lg animate__animated animate__pulse animate__infinite');
            }
        }

        // Update single row UI status
        function updateRowUI($row) {
            const target = parseInt($row.attr('data-target')) || 0;
            const picked = parseInt($row.attr('data-picked')) || 0;
            const $input = $row.find('.input-picked-qty');
            const $statusContainer = $row.find('.item-status-badge');

            if ($input.val() != picked) {
                $input.val(picked);
            }

            if (picked >= target) {
                $row.removeClass('table-warning').addClass('table-success');
                $statusContainer.html('<span class="badge bg-success text-white px-2 py-1 small fw-bold"><i class="fas fa-check-circle me-1"></i> Selesai Ambil</span>');
            } else if (picked > 0) {
                $row.removeClass('table-success').addClass('table-warning');
                $statusContainer.html('<span class="badge bg-warning text-dark px-2 py-1 small fw-bold"><i class="fas fa-spinner fa-spin me-1"></i> Sedang Diambil</span>');
            } else {
                $row.removeClass('table-success table-warning');
                $statusContainer.html('<span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1 small"><i class="fas fa-clock me-1"></i> Belum Diambil</span>');
            }

            updateProgress();
        }

        // AJAX real-time stock deduction in database
        function deductStockAjax(sku, qty) {
            if (qty <= 0) return;
            $.ajax({
                url: "{{ route('fulfillment.scan_sku_deduct') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    sku: sku,
                    qty: qty
                }
            });
        }

        // Direct input change
        $(document).on('change keyup', '.input-picked-qty', function() {
            const $row = $(this).closest('.picking-row');
            const rawSku = $row.find('.sku-label').text();
            const target = parseInt($row.attr('data-target')) || 0;
            let newVal = parseInt($(this).val()) || 0;

            if (newVal < 0) newVal = 0;
            if (newVal > target) newVal = target;

            const oldVal = parseInt($row.attr('data-picked')) || 0;
            const diff = newVal - oldVal;

            $row.attr('data-picked', newVal);
            updateRowUI($row);

            if (diff > 0) {
                deductStockAjax(rawSku, diff);
            }
        });

        // Manual Button: Plus 1
        $(document).on('click', '.btn-plus', function() {
            const $row = $(this).closest('.picking-row');
            const rawSku = $row.find('.sku-label').text();
            const target = parseInt($row.attr('data-target')) || 0;
            let picked = parseInt($row.attr('data-picked')) || 0;

            if (picked < target) {
                picked += 1;
                $row.attr('data-picked', picked);
                updateRowUI($row);
                deductStockAjax(rawSku, 1);
            }
        });

        // Manual Button: Minus 1
        $(document).on('click', '.btn-minus', function() {
            const $row = $(this).closest('.picking-row');
            let picked = parseInt($row.attr('data-picked')) || 0;
            if (picked > 0) {
                picked -= 1;
                $row.attr('data-picked', picked);
                updateRowUI($row);
            }
        });

        // Manual Button: Complete Full Item
        $(document).on('click', '.btn-complete-item', function() {
            const $row = $(this).closest('.picking-row');
            const rawSku = $row.find('.sku-label').text();
            const target = parseInt($row.attr('data-target')) || 0;
            const currentPicked = parseInt($row.attr('data-picked')) || 0;
            const needed = target - currentPicked;

            if (needed > 0) {
                $row.attr('data-picked', target);
                updateRowUI($row);
                deductStockAjax(rawSku, needed);
            }
        });

        // Header Button: Pick All Items (Full All)
        $('#btnPickAll').on('click', function() {
            $('.picking-row').each(function() {
                const $row = $(this);
                const rawSku = $row.find('.sku-label').text();
                const target = parseInt($row.attr('data-target')) || 0;
                const currentPicked = parseInt($row.attr('data-picked')) || 0;
                const needed = target - currentPicked;

                if (needed > 0) {
                    $row.attr('data-picked', target);
                    updateRowUI($row);
                    deductStockAjax(rawSku, needed);
                }
            });
        });

        // Live Search Filter Table
        $('#searchTableInput').on('keyup input', function() {
            const val = $(this).val().toLowerCase().trim();
            $('.picking-row').each(function() {
                const sku = $(this).attr('data-sku');
                const name = $(this).attr('data-name');
                if (!val || sku.includes(val) || name.includes(val)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });
    });
</script>
@endpush
@endsection
