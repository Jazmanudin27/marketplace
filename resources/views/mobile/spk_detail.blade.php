@extends('layouts.mobile')

@section('title', 'Detail SPK - ' . $spk->no_spk)
@section('header-title', 'Detail SPK Produksi')

@section('styles')
<style>
    body {
        background-color: #f1f5f9 !important;
        font-family: 'Outfit', 'Inter', sans-serif;
    }

    .card-detail {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        box-shadow: 0 6px 20px rgba(15, 23, 42, 0.04);
        margin-bottom: 16px;
        overflow: hidden;
    }

    .card-header-custom {
        background-color: #f8fafc;
        border-bottom: 1px solid #f1f5f9;
        padding: 14px 16px;
    }

    .progress-bar-custom {
        height: 8px;
        border-radius: 4px;
        background-color: #e2e8f0;
        overflow: hidden;
    }

    .progress-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, #4f46e5, #0ea5e9);
        border-radius: 4px;
        transition: width 0.4s ease;
    }

    .badge-stage {
        font-size: 0.72rem;
        font-weight: 700;
        padding: 6px 12px;
        border-radius: 20px;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        user-select: none;
    }

    .badge-stage:active {
        transform: scale(0.95);
    }

    .stage-complete {
        background: #ecfdf5;
        color: #059669;
        border: 1px solid #a7f3d0;
        box-shadow: 0 2px 6px rgba(16, 185, 129, 0.15);
    }

    .stage-progress {
        background: #fffbeb;
        color: #d97706;
        border: 1px solid #fde68a;
        box-shadow: 0 2px 6px rgba(245, 158, 11, 0.15);
    }

    .stage-zero {
        background: #f8fafc;
        color: #64748b;
        border: 1px solid #cbd5e1;
    }

    .design-img-preview {
        max-height: 320px;
        object-fit: contain;
        width: 100%;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        background-color: #ffffff;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
    }

    /* Stepper Buttons for Mobile Modal */
    .btn-stepper {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        font-size: 1.2rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #cbd5e1;
        background-color: #f8fafc;
        color: #0f172a;
        transition: all 0.15s ease;
    }

    .btn-stepper:active {
        background-color: #e2e8f0;
        transform: scale(0.95);
    }
</style>
@endsection

@section('content')

    <!-- Top Navigation Actions -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="{{ url()->previous() == request()->url() ? route('mobile.produksi') : url()->previous() }}" class="btn btn-sm btn-white border shadow-sm rounded-3 px-3 fw-bold text-dark" style="background:#ffffff;">
            <i class="fas fa-arrow-left me-1 text-primary"></i> Kembali
        </a>
        <a href="{{ route('spks.print', $spk->id) }}" target="_blank" class="btn btn-sm btn-indigo text-white fw-bold rounded-3 px-3 shadow-sm" style="background:linear-gradient(135deg, #4f46e5, #3730a3);">
            <i class="fas fa-print me-1"></i> Cetak SPK
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 rounded-4 shadow-sm mb-3 p-3 text-dark d-flex align-items-center" style="background:#d1fae5; color:#065f46;">
            <i class="fas fa-check-circle fs-5 me-2 text-emerald-600"></i> {{ session('success') }}
        </div>
    @endif

    <!-- SPK Main Header Info Card -->
    <div class="card-detail">
        <div class="card-header-custom d-flex justify-content-between align-items-center">
            <div>
                <span class="text-muted d-block" style="font-size:0.68rem; font-weight:600;">NO. SPK</span>
                <h5 class="fw-bold text-dark mb-0 font-monospace" style="font-size:1.1rem;">{{ $spk->no_spk }}</h5>
            </div>
            <div class="text-end">
                <span class="text-muted d-block" style="font-size:0.68rem; font-weight:600;">NO. PRODUKSI</span>
                <span class="badge bg-white text-dark font-monospace border fw-bold px-2 py-1" style="font-size:0.8rem; border-color:#cbd5e1 !important;">
                    {{ $spk->no_produksi }}
                </span>
            </div>
        </div>

        <div class="p-3">
            <!-- Tipe SPK Badge -->
            <div class="mb-3">
                @if(($spk->tipe_spk ?? '') === 'stok_gudang')
                    <span class="badge bg-emerald-50 text-emerald border fw-bold px-3 py-1.5" style="background:#ecfdf5; color:#059669; border-color:#a7f3d0 !important; font-size:0.75rem;">
                        🏬 Produksi Stok Gudang
                    </span>
                @else
                    <span class="badge bg-indigo-50 text-indigo border fw-bold px-3 py-1.5" style="background:#e0e7ff; color:#4f46e5; border-color:#c7d2fe !important; font-size:0.75rem;">
                        🛒 Pesanan Pelanggan
                    </span>
                @endif
            </div>

            <div class="row g-2 p-2.5 rounded-3 bg-light" style="background:#f8fafc; border:1px solid #f1f5f9;">
                <div class="col-6">
                    <span class="text-muted d-block" style="font-size:0.68rem; font-weight:600;">TANGGAL SPK</span>
                    <span class="fw-bold text-dark small">{{ $spk->tanggal ? $spk->tanggal->format('d M Y') : '-' }}</span>
                </div>
                <div class="col-6">
                    <span class="text-muted d-block" style="font-size:0.68rem; font-weight:600;">TARGET DEADLINE</span>
                    <span class="fw-bold text-danger small"><i class="far fa-clock me-1"></i>{{ $spk->deadline ? $spk->deadline->format('d M Y') : '-' }}</span>
                </div>
                <div class="col-12 mt-2 pt-2 border-top border-slate-200">
                    <span class="text-muted d-block" style="font-size:0.68rem; font-weight:600;">PEMESAN / INSTANSI</span>
                    <span class="fw-bold text-dark small">
                        {{ $spk->pemesan ?: '-' }}
                        @if($spk->instansi) <span class="text-muted">({{ $spk->instansi }})</span> @endif
                    </span>
                </div>
                @if($spk->catatan)
                    <div class="col-12 mt-2 pt-2 border-top">
                        <span class="text-muted d-block" style="font-size:0.68rem; font-weight:600;">CATATAN KHUSUS</span>
                        <div class="p-2 bg-white rounded text-dark fst-italic border mt-1" style="font-size:0.78rem;">
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
                <h6 class="fw-bold text-dark mb-0" style="font-size:0.9rem;">
                    <i class="fas fa-image text-indigo me-1.5" style="color:#4f46e5;"></i> Desain Model &amp; Logo
                </h6>
            </div>
            <div class="p-3 text-center bg-light">
                <a href="{{ Storage::url($spk->desain_baju) }}" target="_blank">
                    <img src="{{ Storage::url($spk->desain_baju) }}" alt="Desain SPK" class="design-img-preview">
                </a>
                <small class="text-muted d-block mt-2" style="font-size:0.7rem;">Ketuk gambar untuk membuka ukuran penuh</small>
            </div>
        </div>
    @endif

    <!-- Items & Progres Tahapan Produksi Section -->
    <div class="card-detail">
        <div class="card-header-custom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-dark mb-0" style="font-size:0.9rem;">
                <i class="fas fa-tasks text-indigo me-1.5" style="color:#4f46e5;"></i> Item &amp; Update Tahapan
            </h6>
            <span class="badge bg-indigo-50 text-indigo border px-2 py-1" style="background:#e0e7ff; color:#4f46e5; font-size:0.65rem;">Klik Badge untuk Edit</span>
        </div>

        <div class="p-3">
            @foreach($spk->items as $item)
                @php
                    $totalProgresDone = 0;
                    $totalProgresMax = $item->quantity * max(1, $spk->proses->count());
                    foreach($spk->proses as $pr) {
                        $pgVal = $progresMap[$item->id][$pr->id] ?? null;
                        if ($pgVal) $totalProgresDone += $pgVal->qty_done;
                    }
                    $pctDone = $totalProgresMax > 0 ? min(100, round(($totalProgresDone / $totalProgresMax) * 100)) : 0;
                @endphp

                <div class="border rounded-4 p-3 mb-3 bg-white shadow-sm" style="border-color:#e2e8f0 !important;">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="fw-bold text-dark mb-0" style="font-size:0.9rem;">{{ $item->nama_produk }}</h6>
                            <div class="text-muted small mt-0.5" style="font-size:0.72rem;">
                                SKU: <code class="text-primary font-monospace">{{ $item->sku ?: '-' }}</code>
                                @if($item->ukuran) | Ukuran: <strong class="text-dark">{{ $item->ukuran }}</strong> @endif
                            </div>
                        </div>
                        <span class="badge bg-indigo-50 text-indigo border px-2.5 py-1 fw-bold" style="background:#e0e7ff; color:#4f46e5; border-color:#c7d2fe !important; font-size:0.78rem;">
                            {{ $item->quantity }} pcs
                        </span>
                    </div>

                    <div class="small text-muted mb-2" style="font-size:0.72rem;">
                        Penjahit: <strong class="text-dark">{{ $item->penjahit ?: 'Belum ditunjuk' }}</strong>
                        @if($item->barang_kantor) <span class="badge bg-info text-dark ms-1">Bahan di Kantor</span> @endif
                    </div>

                    <!-- Item Completion Progress Bar -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1" style="font-size:0.68rem;">
                            <span class="text-muted font-semibold">Progres Pengerjaan</span>
                            <span class="fw-bold text-dark">{{ $pctDone }}%</span>
                        </div>
                        <div class="progress-bar-custom">
                            <div class="progress-bar-fill" style="width: {{ $pctDone }}%;"></div>
                        </div>
                    </div>

                    <!-- Stage Progress Badges for this Item -->
                    <div class="pt-2 border-top border-slate-100">
                        <span class="text-muted d-block mb-1.5 fw-bold" style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.5px;">
                            Pilih Tahapan untuk Update Qty:
                        </span>
                        <div class="d-flex flex-wrap gap-1.5">
                            @foreach($spk->proses as $proses)
                                @php
                                    $pg = $progresMap[$item->id][$proses->id] ?? null;
                                    $qtyDone = $pg ? $pg->qty_done : 0;
                                    $totalQty = $item->quantity;
                                    
                                    $badgeClass = 'stage-zero';
                                    $iconClass = 'far fa-circle';
                                    if ($qtyDone >= $totalQty && $totalQty > 0) {
                                        $badgeClass = 'stage-complete';
                                        $iconClass = 'fas fa-check-circle';
                                    } elseif ($qtyDone > 0) {
                                        $badgeClass = 'stage-progress';
                                        $iconClass = 'fas fa-spinner fa-spin';
                                    }
                                @endphp

                                @if($pg)
                                    <span class="badge-stage {{ $badgeClass }}"
                                          onclick="openProgressModal({{ $pg->id }}, '{{ addslashes($item->nama_produk) }} ({{ $item->ukuran }})', '{{ addslashes($proses->nama_proses) }}', {{ $qtyDone }}, {{ $totalQty }})">
                                        <i class="{{ $iconClass }}" style="font-size:0.7rem;"></i>
                                        {{ $proses->nama_proses }}: <strong>{{ $qtyDone }}/{{ $totalQty }}</strong>
                                    </span>
                                @else
                                    <span class="badge-stage stage-zero">
                                        <i class="far fa-circle" style="font-size:0.7rem;"></i>
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
            <h6 class="fw-bold text-dark mb-0" style="font-size:0.9rem;">
                <i class="fas fa-hand-holding-box text-emerald me-1.5" style="color:#059669;"></i> Status Pengambilan Barang
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
                            <div class="p-2.5 bg-light rounded-3 border" style="font-size:0.72rem;">
                                <div class="d-flex justify-content-between">
                                    <strong class="text-dark">{{ $pickup->nama_pengambil }}</strong>
                                    <span class="text-muted">{{ \Carbon\Carbon::parse($pickup->tanggal_ambil)->format('d M Y') }}</span>
                                </div>
                                <div class="text-muted mt-0.5">
                                    Mengambil <strong class="text-dark">{{ $pickup->qty_diambil }} pcs</strong> {{ $pickup->item->nama_produk ?? '' }} (Size: {{ $pickup->item->ukuran ?? '' }})
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Mobile Stepper Modal Form for Quick Update -->
    <div class="modal fade" id="modalProgressMobile" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header border-bottom py-3">
                    <h6 class="modal-title fw-bold text-dark" id="modalProgressTitle"><i class="fas fa-edit text-primary me-2"></i>Update Progres Pengerjaan</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formUpdateProgressMobile">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="p-2.5 rounded-3 bg-light mb-3" style="background:#f8fafc;">
                            <div class="mb-1">
                                <span class="text-muted d-block" style="font-size:0.68rem; font-weight:600;">ITEM PRODUK</span>
                                <span class="fw-bold text-dark small" id="lblItemName">-</span>
                            </div>
                            <div>
                                <span class="text-muted d-block" style="font-size:0.68rem; font-weight:600;">TAHAPAN PRODUKSI</span>
                                <span class="badge bg-indigo-50 text-indigo border fw-bold px-2 py-1" id="lblProsesName" style="background:#e0e7ff; color:#4f46e5; border-color:#c7d2fe !important;">-</span>
                            </div>
                        </div>

                        <!-- Stepper Counter Container -->
                        <div class="text-center mb-2">
                            <label class="form-label fw-bold small text-dark mb-2">Jumlah Qty Selesai (pcs):</label>
                            
                            <div class="d-flex align-items-center justify-content-center gap-3">
                                <button type="button" class="btn btn-stepper" onclick="adjustQty(-1)">
                                    <i class="fas fa-minus"></i>
                                </button>
                                
                                <div style="min-width:100px;">
                                    <input type="number" id="inputQtyDone" name="qty_done" class="form-control form-control-lg text-center fw-extrabold text-primary border-primary" style="font-size:1.5rem;" min="0" required>
                                </div>

                                <button type="button" class="btn btn-stepper" onclick="adjustQty(1)">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            
                            <div class="text-muted small mt-2" style="font-size:0.72rem;">
                                Target Total: <strong class="text-dark" id="lblTotalQty">0</strong> pcs
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top p-3 d-flex gap-2">
                        <button type="button" class="btn btn-light w-50 fw-bold py-2.5 rounded-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-gradient-primary w-50 fw-bold py-2.5 rounded-3" id="btnSaveProgress" style="background:linear-gradient(135deg, #4f46e5, #3730a3); color:white; border:none;">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
<script>
    let activeProgresId = null;
    let maxAllowedQty = 0;

    function openProgressModal(progresId, itemName, prosesName, currentQty, totalQty) {
        activeProgresId = progresId;
        maxAllowedQty = totalQty;

        document.getElementById('lblItemName').innerText = itemName;
        document.getElementById('lblProsesName').innerText = prosesName;
        document.getElementById('lblTotalQty').innerText = totalQty;
        
        const inputQty = document.getElementById('inputQtyDone');
        inputQty.value = currentQty;
        inputQty.max = totalQty;

        const modal = new bootstrap.Modal(document.getElementById('modalProgressMobile'));
        modal.show();
    }

    function adjustQty(delta) {
        const inputQty = document.getElementById('inputQtyDone');
        let current = parseInt(inputQty.value) || 0;
        let nextVal = current + delta;

        if (nextVal < 0) nextVal = 0;
        if (maxAllowedQty > 0 && nextVal > maxAllowedQty) nextVal = maxAllowedQty;

        inputQty.value = nextVal;
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
