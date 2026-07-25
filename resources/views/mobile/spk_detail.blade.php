@extends('layouts.mobile')

@section('title', 'Detail SPK - ' . $spk->no_spk)
@section('header-title', 'Detail SPK Produksi')

@section('styles')
<style>
    body {
        background-color: #f8fafc !important;
    }

    .card-detail {
        background: #ffffff;
        border: 1px solid rgba(0, 0, 0, 0.06);
        border-radius: 16px;
        box-shadow: 0 4px 18px rgba(0, 0, 0, 0.03);
        margin-bottom: 16px;
        overflow: hidden;
    }

    .card-header-custom {
        background-color: #fafafa;
        border-bottom: 1px solid #f1f5f9;
        padding: 14px 16px;
    }

    .badge-stage {
        font-size: 0.7rem;
        font-weight: 700;
        padding: 5px 10px;
        border-radius: 20px;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-block;
    }

    .badge-stage:active {
        transform: scale(0.95);
    }

    .stage-complete {
        background: #ecfdf5;
        color: #059669;
        border: 1px solid #a7f3d0;
    }

    .stage-progress {
        background: #fffbeb;
        color: #d97706;
        border: 1px solid #fde68a;
    }

    .stage-zero {
        background: #f1f5f9;
        color: #64748b;
        border: 1px solid #e2e8f0;
    }

    .design-img-preview {
        max-height: 280px;
        object-fit: contain;
        width: 100%;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        background-color: #ffffff;
    }
</style>
@endsection

@section('content')

    <!-- Top Navigation Back Button -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="{{ url()->previous() == request()->url() ? route('mobile.produksi') : url()->previous() }}" class="btn btn-sm btn-outline-secondary rounded-3 px-3 fw-semibold">
            <i class="fas fa-arrow-left me-1"></i> Kembali
        </a>
        <a href="{{ route('spks.print', $spk->id) }}" target="_blank" class="btn btn-sm btn-outline-primary rounded-3 px-3 fw-semibold">
            <i class="fas fa-print me-1"></i> Cetak SPK
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- SPK Main Header Info Card -->
    <div class="card-detail">
        <div class="card-header-custom d-flex justify-content-between align-items-center">
            <div>
                <span class="text-muted d-block small" style="font-size:0.68rem;">No. SPK</span>
                <h5 class="fw-bold text-dark mb-0 font-monospace" style="font-size:1.05rem;">{{ $spk->no_spk }}</h5>
            </div>
            <div class="text-end">
                <span class="text-muted d-block small" style="font-size:0.68rem;">No. Produksi</span>
                <span class="badge bg-light text-dark font-monospace border fw-bold" style="font-size:0.8rem;">{{ $spk->no_produksi }}</span>
            </div>
        </div>

        <div class="p-3">
            <!-- Tipe SPK Badge -->
            <div class="mb-3">
                @if(($spk->tipe_spk ?? '') === 'stok_gudang')
                    <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 fw-bold px-2.5 py-1.5" style="font-size:0.75rem;">
                        🏬 Produksi Stok Gudang
                    </span>
                @else
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 fw-bold px-2.5 py-1.5" style="font-size:0.75rem;">
                        🛒 Pesanan Pelanggan
                    </span>
                @endif
            </div>

            <div class="row g-2 small">
                <div class="col-6">
                    <span class="text-muted d-block" style="font-size:0.7rem;">Tanggal SPK</span>
                    <span class="fw-semibold text-dark">{{ $spk->tanggal ? $spk->tanggal->format('d M Y') : '-' }}</span>
                </div>
                <div class="col-6">
                    <span class="text-muted d-block" style="font-size:0.7rem;">Target Deadline</span>
                    <span class="fw-bold text-danger">{{ $spk->deadline ? $spk->deadline->format('d M Y') : '-' }}</span>
                </div>
                <div class="col-12 mt-2">
                    <span class="text-muted d-block" style="font-size:0.7rem;">Pemesan / Instansi</span>
                    <span class="fw-bold text-dark">
                        {{ $spk->pemesan ?: '-' }}
                        @if($spk->instansi) <span class="text-muted">({{ $spk->instansi }})</span> @endif
                    </span>
                </div>
                @if($spk->catatan)
                    <div class="col-12 mt-2 pt-2 border-top">
                        <span class="text-muted d-block" style="font-size:0.7rem;">Catatan SPK</span>
                        <div class="p-2 bg-light rounded text-dark fst-italic" style="font-size:0.78rem;">
                            {{ $spk->catatan }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Desain Model / Bordir Logo Section -->
    @if($spk->desain_baju)
        <div class="card-detail">
            <div class="card-header-custom">
                <h6 class="fw-bold text-dark mb-0" style="font-size:0.88rem;">
                    <i class="fas fa-image text-primary me-1.5"></i> Desain Model &amp; Logo
                </h6>
            </div>
            <div class="p-3 text-center bg-light">
                <a href="{{ Storage::url($spk->desain_baju) }}" target="_blank">
                    <img src="{{ Storage::url($spk->desain_baju) }}" alt="Desain SPK" class="design-img-preview shadow-sm">
                </a>
                <small class="text-muted d-block mt-2" style="font-size:0.7rem;">Ketuk gambar untuk memperbesar</small>
            </div>
        </div>
    @endif

    <!-- Items & Progres Tahapan Produksi Section -->
    <div class="card-detail">
        <div class="card-header-custom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-dark mb-0" style="font-size:0.88rem;">
                <i class="fas fa-tasks text-primary me-1.5"></i> Items &amp; Progres Produksi
            </h6>
            <span class="badge bg-secondary text-white small" style="font-size:0.65rem;">Klik Badge untuk Edit</span>
        </div>

        <div class="p-3">
            @foreach($spk->items as $item)
                <div class="border rounded-3 p-3 mb-3 bg-white shadow-sm">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="fw-bold text-dark mb-0" style="font-size:0.88rem;">{{ $item->nama_produk }}</h6>
                            <div class="text-muted small mt-0.5" style="font-size:0.72rem;">
                                SKU: <code class="text-primary font-monospace">{{ $item->sku ?: '-' }}</code>
                                @if($item->ukuran) | Ukuran: <strong>{{ $item->ukuran }}</strong> @endif
                            </div>
                        </div>
                        <span class="badge bg-primary px-2.5 py-1 fw-bold" style="font-size:0.75rem;">
                            {{ $item->quantity }} pcs
                        </span>
                    </div>

                    <div class="small text-muted mb-2" style="font-size:0.72rem;">
                        Penjahit: <strong class="text-dark">{{ $item->penjahit ?: 'Belum ditunjuk' }}</strong>
                        @if($item->barang_kantor) <span class="badge bg-info text-dark ms-1">Bahan di Kantor</span> @endif
                    </div>

                    <!-- Stage Progress Badges for this Item -->
                    <div class="mt-3 pt-2 border-top">
                        <span class="text-muted d-block mb-1.5 fw-semibold" style="font-size:0.68rem; text-transform:uppercase; letter-spacing:0.5px;">
                            Update Tahapan Produksi:
                        </span>
                        <div class="d-flex flex-wrap gap-1.5">
                            @foreach($spk->proses as $proses)
                                @php
                                    $pg = $progresMap[$item->id][$proses->id] ?? null;
                                    $qtyDone = $pg ? $pg->qty_done : 0;
                                    $totalQty = $item->quantity;
                                    
                                    $badgeClass = 'stage-zero';
                                    if ($qtyDone >= $totalQty && $totalQty > 0) {
                                        $badgeClass = 'stage-complete';
                                    } elseif ($qtyDone > 0) {
                                        $badgeClass = 'stage-progress';
                                    }
                                @endphp

                                @if($pg)
                                    <span class="badge-stage {{ $badgeClass }}"
                                          onclick="openProgressModal({{ $pg->id }}, '{{ addslashes($item->nama_produk) }} ({{ $item->ukuran }})', '{{ addslashes($proses->nama_proses) }}', {{ $qtyDone }}, {{ $totalQty }})">
                                        <i class="fas fa-edit me-1" style="font-size:0.6rem;"></i>
                                        {{ $proses->nama_proses }}: <strong>{{ $qtyDone }}/{{ $totalQty }}</strong>
                                    </span>
                                @else
                                    <span class="badge-stage stage-zero">
                                        {{ $proses->nama_proses }}: 0/{{ $totalQty }}
                                    </span>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Status Pengambilan Barang (Partial Handover) -->
    <div class="card-detail">
        <div class="card-header-custom">
            <h6 class="fw-bold text-dark mb-0" style="font-size:0.88rem;">
                <i class="fas fa-hand-holding text-success me-1.5"></i> Status Pengambilan Barang
            </h6>
        </div>

        <div class="p-3">
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0" style="font-size:0.75rem;">
                    <thead class="table-light">
                        <tr>
                            <th>Item Produk</th>
                            <th class="text-center">Total</th>
                            <th class="text-center">Diambil</th>
                            <th class="text-center">Sisa</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($spk->items as $item)
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark">{{ $item->nama_produk }}</div>
                                    <div class="text-muted" style="font-size:0.68rem;">Size: {{ $item->ukuran }}</div>
                                </td>
                                <td class="text-center fw-bold">{{ $item->quantity }}</td>
                                <td class="text-center text-success fw-bold">{{ $item->qty_diambil }}</td>
                                <td class="text-center fw-bold {{ $item->sisa_qty > 0 ? 'text-danger' : 'text-muted' }}">
                                    {{ $item->sisa_qty }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Handover History Logs -->
            @php
                $allPickups = collect();
                foreach($spk->items as $item) {
                    foreach($item->pickups as $p) {
                        $allPickups->push($p);
                    }
                }
                $allPickups = $allPickups->sortByDesc('created_at');
            @endphp

            @if($allPickups->isNotEmpty())
                <div class="mt-3 pt-2 border-top">
                    <h6 class="fw-bold text-dark mb-2" style="font-size:0.78rem;">Riwayat Pengambilan:</h6>
                    <div class="d-flex flex-column gap-2">
                        @foreach($allPickups as $pickup)
                            <div class="p-2 bg-light rounded border" style="font-size:0.72rem;">
                                <div class="d-flex justify-content-between">
                                    <strong class="text-dark">{{ $pickup->nama_pengambil }}</strong>
                                    <span class="text-muted">{{ \Carbon\Carbon::parse($pickup->tanggal_ambil)->format('d M Y') }}</span>
                                </div>
                                <div class="text-muted mt-0.5">
                                    Mengambil <strong>{{ $pickup->qty_diambil }} pcs</strong> {{ $pickup->item->nama_produk ?? '' }} (Size: {{ $pickup->item->ukuran ?? '' }})
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Modal Form Update Progress Tahapan -->
    <div class="modal fade" id="modalProgressMobile" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0">
                <div class="modal-header border-bottom py-3">
                    <h6 class="modal-title fw-bold text-dark" id="modalProgressTitle">Update Progres Tahapan</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formUpdateProgressMobile">
                    @csrf
                    <div class="modal-body p-3">
                        <div class="mb-2">
                            <span class="text-muted d-block small" style="font-size:0.7rem;">Item Produk:</span>
                            <span class="fw-bold text-dark small" id="lblItemName">-</span>
                        </div>
                        <div class="mb-3">
                            <span class="text-muted d-block small" style="font-size:0.7rem;">Tahapan / Proses:</span>
                            <span class="badge bg-primary fw-bold" id="lblProsesName">-</span>
                        </div>

                        <div class="mb-3">
                            <label for="inputQtyDone" class="form-label fw-bold small text-dark">Jumlah Qty Selesai (pcs):</label>
                            <div class="input-group">
                                <input type="number" id="inputQtyDone" name="qty_done" class="form-control form-control-lg text-center fw-bold" min="0" required>
                                <span class="input-group-text fw-bold text-muted">/ <span id="lblTotalQty">0</span> pcs</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top p-2 d-flex gap-2">
                        <button type="button" class="btn btn-light w-50 fw-bold" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary w-50 fw-bold" id="btnSaveProgress">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
<script>
    let activeProgresId = null;

    function openProgressModal(progresId, itemName, prosesName, currentQty, totalQty) {
        activeProgresId = progresId;
        document.getElementById('lblItemName').innerText = itemName;
        document.getElementById('lblProsesName').innerText = prosesName;
        document.getElementById('lblTotalQty').innerText = totalQty;
        
        const inputQty = document.getElementById('inputQtyDone');
        inputQty.value = currentQty;
        inputQty.max = totalQty;

        const modal = new bootstrap.Modal(document.getElementById('modalProgressMobile'));
        modal.show();
    }

    document.getElementById('formUpdateProgressMobile').addEventListener('submit', function(e) {
        e.preventDefault();
        if (!activeProgresId) return;

        const btn = document.getElementById('btnSaveProgress');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...';

        const qtyDone = document.getElementById('inputQtyDone').value;

        fetch(`/spks/progres/${activeProgresId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ qty_done: qtyDone })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Gagal memperbarui progres: ' + (data.message || 'Error'));
                btn.disabled = false;
                btn.innerHTML = 'Simpan';
            }
        })
        .catch(err => {
            console.error(err);
            alert('Terjadi kesalahan jaringan.');
            btn.disabled = false;
            btn.innerHTML = 'Simpan';
        });
    });
</script>
@endsection
