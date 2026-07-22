@extends('layouts.app')
@section('title', 'Layar Interaktif Ambil Barang (Pick List Scanner)')
@section('page-title', 'Ambil Barang Gudang (Pick List Mode)')

@section('content')
<div class="container-fluid p-0">

    {{-- Top Action & Progress Bar Header --}}
    <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
        <div class="card-body p-4">
            <div class="row align-items-center g-3">
                <div class="col-md-6">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <a href="{{ route('fulfillment.index') }}" class="btn btn-sm btn-light border rounded-circle p-2 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" title="Kembali ke Pemenuhan Pesanan">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <h4 class="fw-bold text-dark mb-0"><i class="fas fa-barcode text-primary me-2"></i>Layar Rekap Ambil Barang</h4>
                    </div>
                    <small class="text-muted d-block ms-4">
                        Memproses <strong>{{ count($orders) }} Pesanan</strong> | Total <strong>{{ $totalPcs }} Pcs</strong> barang yang harus diambil di rak gudang.
                    </small>
                </div>
                <div class="col-md-6 text-md-end">
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
                    <span class="fw-bold small text-dark"><i class="fas fa-tasks me-1 text-primary"></i> Progress Pengambilan Barang (Overall Pick Progress):</span>
                    <span class="fw-bold text-primary fs-6"><span id="totalPickedCount">0</span> / {{ $totalPcs }} Pcs (<span id="totalProgressPercent">0</span>%)</span>
                </div>
                <div class="progress rounded-pill shadow-sm" style="height: 14px; background-color: #e2e8f0;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-success rounded-pill" id="totalProgressBar" role="progressbar" style="width: 0%;"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Barcode Scanner Input Card --}}
    <div class="card border-primary border-2 shadow-sm rounded-3 mb-4 bg-primary-subtle">
        <div class="card-body p-3">
            <div class="row align-items-center g-2">
                <div class="col-md-7 col-lg-8">
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-primary text-white border-primary"><i class="fas fa-barcode fs-4"></i></span>
                        <input type="text" id="barcodeInput" class="form-control form-control-lg border-primary fw-bold text-dark font-monospace" placeholder="Scan Barcode SKU / Ketik SKU di sini..." autocomplete="off" autofocus>
                        <button type="button" class="btn btn-primary px-4 fw-bold" id="btnSubmitScan"><i class="fas fa-arrow-right me-1"></i> Scan</button>
                    </div>
                </div>
                <div class="col-md-5 col-lg-4">
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-white border-secondary-subtle text-muted"><i class="fas fa-search"></i></span>
                        <input type="text" id="searchTableInput" class="form-control form-control-lg border-secondary-subtle small" placeholder="Filter/Cari nama barang..." autocomplete="off">
                    </div>
                </div>
            </div>
            <div class="mt-2 d-flex justify-content-between align-items-center px-1">
                <small class="text-primary fw-semibold" style="font-size: 11px;">
                    <i class="fas fa-info-circle me-1"></i> Mode Scan Aktif: Arahkan barcode scanner fisik ke SKU barang. Sistem otomatis menambah Qty +1 per scan.
                </small>
                <div id="scanFeedback" class="badge bg-success text-white px-3 py-1-5 fs-7 d-none shadow-sm">
                    <i class="fas fa-check me-1"></i> SKU Ditemukan!
                </div>
            </div>
        </div>
    </div>

    {{-- Items Picking List Grid / Table --}}
    <div class="card border-0 shadow-sm rounded-3 bg-white">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="pickingTable">
                    <thead class="table-dark">
                        <tr class="small">
                            <th class="text-center" style="width: 50px;">NO</th>
                            <th style="width: 180px;">SKU / KODE BARANG</th>
                            <th>NAMA PRODUK</th>
                            <th class="text-center" style="width: 140px;">TIPE BARANG</th>
                            <th class="text-center" style="width: 160px;">TARGET / AMBIL</th>
                            <th class="text-center" style="width: 220px;">AKSI MANUAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $no = 1; @endphp
                        @foreach($aggregated as $sku => $item)
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
                                        <span class="badge bg-purple text-white px-2 py-1-5 fw-bold" style="background-color: #8b5cf6;" title="Barang Pre-Order / Produksi SPK">
                                            <i class="fas fa-clock me-1"></i> PO / SPK
                                        </span>
                                        @if(!empty($item['spk_no']))
                                            <div class="small font-monospace text-primary fw-semibold mt-1" style="font-size: 10px;">#{{ $item['spk_no'] }}</div>
                                        @endif
                                    @else
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1-5 fw-bold" title="Barang Ready Stock">
                                            <i class="fas fa-check-circle me-1"></i> READY STOCK
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="fs-5 fw-bold text-dark">
                                        <span class="picked-qty text-danger">0</span> / <span class="target-qty">{{ $item['target'] }}</span> Pcs
                                    </div>
                                    <div class="item-status-badge mt-1">
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 small">
                                            Belum Diambil
                                        </span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm gap-1" role="group">
                                        <button type="button" class="btn btn-outline-danger btn-minus btn-sm rounded-2 px-2" title="Kurangi 1">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <button type="button" class="btn btn-primary btn-plus btn-sm rounded-2 px-3 fw-bold" title="Tambah 1">
                                            <i class="fas fa-plus me-1"></i> +1
                                        </button>
                                        <button type="button" class="btn btn-outline-success btn-complete-item btn-sm rounded-2 px-2 fw-bold" title="Set Selesai">
                                            <i class="fas fa-check me-1"></i> Full
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

{{-- Audio Beep Sound Effects via Web Audio API --}}
<script>
    const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    
    function playBeepSound(type = 'success') {
        try {
            if (audioCtx.state === 'suspended') {
                audioCtx.resume();
            }
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            
            if (type === 'success') {
                osc.type = 'sine';
                osc.frequency.setValueAtTime(880, audioCtx.currentTime); // A5 note
                gain.gain.setValueAtTime(0.15, audioCtx.currentTime);
                osc.start();
                osc.stop(audioCtx.currentTime + 0.12);
            } else if (type === 'complete') {
                osc.type = 'triangle';
                osc.frequency.setValueAtTime(523.25, audioCtx.currentTime);
                osc.frequency.setValueAtTime(659.25, audioCtx.currentTime + 0.08);
                osc.frequency.setValueAtTime(783.99, audioCtx.currentTime + 0.16);
                gain.gain.setValueAtTime(0.2, audioCtx.currentTime);
                osc.start();
                osc.stop(audioCtx.currentTime + 0.3);
            } else {
                osc.type = 'sawtooth';
                osc.frequency.setValueAtTime(220, audioCtx.currentTime);
                gain.gain.setValueAtTime(0.2, audioCtx.currentTime);
                osc.start();
                osc.stop(audioCtx.currentTime + 0.25);
            }
        } catch (e) {
            console.log('Audio error:', e);
        }
    }
</script>

@push('scripts')
<script>
    $(document).ready(function() {
        const barcodeInput = $('#barcodeInput');
        const totalPcs = {{ $totalPcs }};
        
        // Auto-focus barcode input
        barcodeInput.focus();
        $(document).on('click', function(e) {
            if (!$(e.target).closest('input, button, select, a, table').length) {
                barcodeInput.focus();
            }
        });

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
            const $pickedLabel = $row.find('.picked-qty');
            const $statusContainer = $row.find('.item-status-badge');

            $pickedLabel.text(picked);

            if (picked >= target) {
                $row.removeClass('table-warning').addClass('table-success');
                $pickedLabel.removeClass('text-danger text-warning').addClass('text-success fw-bold');
                $statusContainer.html('<span class="badge bg-success text-white px-2 py-1 small fw-bold"><i class="fas fa-check-circle me-1"></i> Selesai Ambil</span>');
            } else if (picked > 0) {
                $row.removeClass('table-success').addClass('table-warning');
                $pickedLabel.removeClass('text-danger text-success').addClass('text-warning-emphasis fw-bold');
                $statusContainer.html('<span class="badge bg-warning text-dark px-2 py-1 small fw-bold"><i class="fas fa-spinner fa-spin me-1"></i> Sedang Diambil</span>');
            } else {
                $row.removeClass('table-success table-warning');
                $pickedLabel.removeClass('text-success text-warning').addClass('text-danger');
                $statusContainer.html('<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 small"><i class="fas fa-clock me-1"></i> Belum Diambil</span>');
            }

            updateProgress();
        }

        // AJAX real-time stock deduction in database
        function deductStockAjax(sku, qty) {
            $.ajax({
                url: "{{ route('fulfillment.scan_sku_deduct') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    sku: sku,
                    qty: qty
                },
                success: function(res) {
                    if (res.success) {
                        $('#scanFeedback').removeClass('d-none bg-danger').addClass('bg-success')
                            .html('<i class="fas fa-check-circle me-1"></i> ' + res.message)
                            .fadeIn(100).delay(2500).fadeOut(200);
                    }
                }
            });
        }

        // Process Scan / Increment Item
        function incrementSku(querySku) {
            const cleanQuery = querySku.trim().toLowerCase();
            if (!cleanQuery) return;

            let found = false;
            $('.picking-row').each(function() {
                const rowSku = $(this).attr('data-sku');
                const rawSku = $(this).find('.sku-label').text();
                const rowName = $(this).attr('data-name');

                if (rowSku === cleanQuery || rowSku.includes(cleanQuery) || rowName.includes(cleanQuery)) {
                    let currentPicked = parseInt($(this).attr('data-picked')) || 0;
                    const target = parseInt($(this).attr('data-target')) || 0;

                    currentPicked += 1;
                    $(this).attr('data-picked', currentPicked);
                    updateRowUI($(this));

                    // Deduct stock in DB real-time
                    deductStockAjax(rawSku, 1);

                    // Highlight row effect
                    $(this).addClass('bg-success-subtle');
                    setTimeout(() => {
                        $(this).removeClass('bg-success-subtle');
                    }, 800);

                    found = true;

                    if (currentPicked >= target) {
                        playBeepSound('complete');
                    } else {
                        playBeepSound('success');
                    }
                    return false; // Break loop on first match
                }
            });

            if (!found) {
                playBeepSound('error');
                $('#scanFeedback').removeClass('d-none bg-success').addClass('bg-danger').html('<i class="fas fa-times me-1"></i> SKU "' + querySku + '" Tidak Ditemukan!').fadeIn(100).delay(1500).fadeOut(200);
            }

            barcodeInput.val('').focus();
        }

        // Handle Scan Form Submission
        $('#btnSubmitScan').on('click', function() {
            incrementSku(barcodeInput.val());
        });

        barcodeInput.on('keypress', function(e) {
            if (e.which === 13) { // Enter key from barcode scanner
                e.preventDefault();
                incrementSku($(this).val());
            }
        });

        // Manual Buttons: Plus 1
        $(document).on('click', '.btn-plus', function() {
            const $row = $(this).closest('.picking-row');
            const rawSku = $row.find('.sku-label').text();
            let picked = parseInt($row.attr('data-picked')) || 0;
            picked += 1;
            $row.attr('data-picked', picked);
            updateRowUI($row);
            deductStockAjax(rawSku, 1);
            playBeepSound('success');
        });

        // Manual Buttons: Minus 1
        $(document).on('click', '.btn-minus', function() {
            const $row = $(this).closest('.picking-row');
            let picked = parseInt($row.attr('data-picked')) || 0;
            if (picked > 0) {
                picked -= 1;
                $row.attr('data-picked', picked);
                updateRowUI($row);
            }
        });

        // Manual Buttons: Complete Full
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
                playBeepSound('complete');
            }
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
