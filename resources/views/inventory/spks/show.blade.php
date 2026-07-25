@extends('layouts.app')
@section('title', 'Detail SPK #' . $spk->no_spk)
@section('page-title', 'Detail Surat Perintah Kerja')

@section('content')
<div class="container-fluid p-0">
    <!-- Top Action Bar -->
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <a href="{{ route('spks.index') }}" class="btn btn-secondary btn-sm rounded-3">
            <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar
        </a>
        <div class="d-flex gap-2">
            <a href="{{ route('spks.print', $spk) }}" target="_blank" class="btn btn-primary btn-sm rounded-3 fw-bold shadow-sm">
                <i class="fas fa-print me-1"></i> Cetak Lembar SPK
            </a>
            <form action="{{ route('spks.destroy', $spk) }}" method="POST" class="m-0"
                onsubmit="return confirm('Apakah Anda yakin ingin menghapus data SPK ini?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm rounded-3 fw-bold shadow-sm">
                    <i class="fas fa-trash-alt me-1"></i> Hapus SPK
                </button>
            </form>
        </div>
    </div>

    <!-- SPK Document Wrapper Full Width (col-12) -->
    <div class="card border shadow-sm rounded-3 bg-white w-100 overflow-hidden">
        <div class="card-body p-5">
            
            <!-- Document Header -->
            <div class="row align-items-center pb-4 mb-4 border-bottom">
                <div class="col-md-4">
                    <div class="mb-1 text-muted small text-uppercase fw-bold">No. Produksi</div>
                    <div class="font-monospace fw-bold fs-6 text-dark">{{ $spk->no_produksi ?: '—' }}</div>
                    
                    <div class="mt-2 mb-1 text-muted small text-uppercase fw-bold">No. Pesanan</div>
                    <div class="font-monospace fw-bold fs-6 text-primary">{{ $spk->no_spk }}</div>
                </div>
                <div class="col-md-4 text-center">
                    <h2 class="fw-bold mb-0 text-dark" style="letter-spacing: 3px; font-family: 'Outfit', sans-serif;">S P K</h2>
                    <div class="text-uppercase fw-semibold text-muted small" style="letter-spacing: 2px;">Surat Perintah Kerja</div>
                </div>
                <div class="col-md-3 text-end">
                    <div class="mb-1 text-muted small text-uppercase fw-bold">Tanggal</div>
                    <div class="fw-bold text-dark">{{ $spk->tanggal ? $spk->tanggal->format('d F Y') : '—' }}</div>
                    
                    <div class="mt-2 mb-1 text-danger small text-uppercase fw-bold">Jatuh Tempo</div>
                    <div class="fw-bold text-danger">{{ $spk->deadline ? $spk->deadline->format('d F Y') : '—' }}</div>
                </div>
                <div class="col-md-1 text-end ps-0 d-none d-md-block">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=65x65&data={{ $spk->no_spk }}" alt="QR Code" class="img-fluid rounded border p-1 bg-white">
                </div>
            </div>

            <!-- Visual Design Box -->
            <div class="border rounded-3 p-4 text-center bg-light mb-4 shadow-sm position-relative">
                <div class="position-absolute top-0 start-50 translate-middle bg-white px-3 text-uppercase fw-bold small text-muted border rounded-pill">
                    Desain Model / Bordir Logo
                </div>
                <div class="mt-2">
                    @if($spk->image_url)
                        <img src="{{ $spk->image_url }}" alt="Desain Pakaian" class="img-fluid rounded border p-2 bg-white shadow-sm" style="max-height: 220px; object-fit: contain;">
                    @else
                        <div class="py-4 text-muted small">
                            <i class="fas fa-image fa-3x opacity-25 d-block mb-2 text-secondary"></i>
                            <span class="fw-semibold">TEMPEL GAMBAR DESAIN DI SINI</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Client / Order Bar (White Background) -->
            <div class="row g-2 p-3 bg-white text-dark border shadow-sm rounded-3 mb-4 text-uppercase small fw-bold">
                <div class="col-md-3">
                    <span class="text-muted">Pemesan:</span><br>
                    <span class="text-truncate d-block text-dark fw-bold">{{ $spk->pemesan ?: 'INTERNAL STOCK' }}</span>
                </div>
                <div class="col-md-3 border-start ps-3">
                    <span class="text-muted">No HP Pemesan:</span><br>
                    <span class="text-dark fw-bold">{{ $spk->no_hp_pemesan ?: '—' }}</span>
                </div>
                <div class="col-md-3 border-start ps-3">
                    <span class="text-muted">Toko / Channel:</span><br>
                    <span class="text-truncate d-block text-dark fw-bold">{{ $spk->instansi ?: '—' }}</span>
                </div>
                <div class="col-md-3 border-start ps-3">
                    <span class="text-muted">PIC / Pembuat SPK:</span><br>
                    <span class="text-truncate d-block text-primary fw-bold">{{ $spk->penginput->name ?? 'SYSTEM' }}</span>
                </div>
            </div>

            <!-- Rincian Produk & Pembagian Kerja (Matriks Ukuran) -->
            <h5 class="fw-bold text-dark mb-3">
                <i class="fas fa-table text-primary me-2"></i>Rincian Produk &amp; Pembagian Kerja
            </h5>
            <div class="table-responsive mb-4">
                <table class="table table-bordered table-sm align-middle text-center mb-0">
                    <thead class="table-light text-muted small">
                        <tr>
                            <th rowspan="2" class="align-middle text-start ps-3" style="width: 25%;">Model Varian</th>
                            <th colspan="{{ count($sizesHeader) }}">Size</th>
                            <th rowspan="2" class="align-middle" style="width: 10%;">Total QTY</th>
                        </tr>
                        <tr>
                            @foreach($sizesHeader as $sz)
                                <th>{{ $sz }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @php $grandTotalQty = 0; @endphp
                        @forelse($grouped as $model)
                            <tr>
                                <td class="text-start ps-3 fw-bold text-dark">{{ $model['model'] }}</td>
                                @foreach($sizesHeader as $sz)
                                    @php
                                        $szKey = $sz === 'XXXL' ? '3XL' : $sz;
                                    @endphp
                                    <td>
                                        @if(isset($model['sizes'][$szKey]) && $model['sizes'][$szKey] > 0)
                                            <span class="fw-semibold text-dark">{{ $model['sizes'][$szKey] }}</span>
                                        @else
                                            <span class="text-muted opacity-50">—</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="bg-danger-subtle fw-bold text-danger">{{ $model['total'] }}</td>
                            </tr>
                            @php $grandTotalQty += $model['total']; @endphp
                        @empty
                            <tr>
                                <td colspan="{{ count($sizesHeader) + 2 }}" class="text-center py-3 text-muted">Tidak ada data produk.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Rincian HPP Produksi (Internal Office Only) -->
            @if(auth()->user()->isSuperAdmin() || auth()->user()->role === 'admin' || auth()->user()->hasRole('admin'))
            <div class="d-flex justify-content-between align-items-center mb-3 mt-4">
                <h5 class="fw-bold text-dark mb-0">
                    <i class="fas fa-calculator text-primary me-2"></i>Rincian HPP Produksi
                    <span class="badge bg-danger-subtle text-danger" style="font-size: 11px;">Internal Office Only</span>
                </h5>
            </div>
            <div class="table-responsive mb-4">
                <table class="table table-bordered table-sm align-middle text-center mb-0">
                    <thead class="table-light text-muted small">
                        <tr>
                            <th></th>
                            <th class="text-start ps-3" style="width: 22%;">SKU Produk &amp; Size</th>
                            <th style="width: 10%;">Tukang Potong</th>
                            <th style="width: 10%;">Tukang Jahit</th>
                            <th style="width: 15%;">Catatan Khusus</th>
                            <th style="width: 8%;">Bahan / pcs</th>
                            <th style="width: 8%;">Jasa / pcs</th>
                            <th style="width: 8%;">HPP / Pcs</th>
                            <th style="width: 5%;">Qty</th>
                            <th style="width: 10%;">Subtotal HPP</th>
                            <th style="width: 10%;">Status</th>
                            <th style="width: 4%;">Edit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $grandTotalHpp = 0; @endphp
                        @foreach($spk->items as $item)
                            @php
                                $subtotal = $item->hpp * $item->quantity;
                                $grandTotalHpp += $subtotal;

                                $totalBahan = 0;
                                $totalJasa = 0;
                                $materials = [];
                                $services = [];
                                foreach($item->extras as $ex) {
                                    $desc = strtolower($ex->keterangan);
                                    if (str_starts_with($desc, 'bahan:') || str_contains($desc, 'bahan')) {
                                        $totalBahan += (float)$ex->nominal;
                                        $materials[] = $ex;
                                    } else {
                                        $totalJasa += (float)$ex->nominal;
                                        $services[] = $ex;
                                    }
                                }
                            @endphp
                            
                            <!-- Main Row -->
                            <tr>
                                <td>
                                    <button class="btn btn-link btn-sm p-0 m-0 text-decoration-none" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-details-{{ $item->id }}" aria-expanded="false">
                                        <i class="fas fa-chevron-down text-secondary" style="font-size: 11px;"></i>
                                    </button>
                                </td>
                                <td class="text-start ps-3 fw-bold text-dark font-monospace">
                                    {{ $item->sku ?: ($item->sku_induk ?: $item->nama_produk) }}
                                    <span class="badge bg-light text-dark border ms-1 small font-monospace">{{ $item->ukuran ?: 'All Size' }}</span>
                                </td>
                                <td><span class="badge bg-info-subtle text-info border font-monospace">{{ $item->pemotong ?: '—' }}</span></td>
                                <td><span class="badge bg-primary-subtle text-primary border font-monospace">{{ $item->penjahit ?: '—' }}</span></td>
                                <td class="text-start small text-muted">{{ $item->catatan ?: '—' }}</td>
                                <td>Rp {{ number_format($totalBahan, 0, ',', '.') }}</td>
                                <td>Rp {{ number_format($totalJasa, 0, ',', '.') }}</td>
                                <td class="bg-danger-subtle fw-bold text-danger">Rp {{ number_format($item->hpp, 0, ',', '.') }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td class="fw-bold">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                                <td>
                                    {{-- Status Selector Form --}}
                                    <form action="{{ route('spks.items.update_status', $item->id) }}" method="POST" class="m-0">
                                        @csrf
                                        <select name="status" class="form-select form-select-sm py-0 border-primary-subtle text-primary fw-semibold" style="font-size: 10px; height: 22px; width: 110px; margin: 0 auto;" onchange="this.form.submit()">
                                            @foreach($statusOptions as $stName)
                                                <option value="{{ $stName }}" {{ ($item->status ?? 'Belum Mulai') == $stName ? 'selected' : '' }}>{{ $stName }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2" data-bs-toggle="modal" data-bs-target="#editItemModal-{{ $item->id }}" title="Edit Potong, Jahit & Catatan">
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <!-- Modal Edit Item Details -->
                                    <div class="modal fade text-start" id="editItemModal-{{ $item->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <form action="{{ route('spks.items.update_details', $item->id) }}" method="POST" class="modal-content">
                                                @csrf
                                                <div class="modal-header bg-light py-2">
                                                    <h6 class="modal-title fw-bold text-dark"><i class="fas fa-edit me-1 text-primary"></i> Edit Detail Item: {{ $item->nama_produk }}</h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body p-3">
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">Tukang Potong</label>
                                                        <input type="text" name="pemotong" class="form-control form-control-sm" value="{{ $item->pemotong }}" placeholder="Nama tukang potong...">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">Tukang Jahit</label>
                                                        <input type="text" name="penjahit" class="form-control form-control-sm" value="{{ $item->penjahit }}" placeholder="Nama tukang jahit...">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">Catatan Khusus / Variasi (Panjang/Pendek, Custom Size)</label>
                                                        <textarea name="catatan" class="form-control form-control-sm" rows="3" placeholder="Contoh: Lengan Panjang, Kancing Depan...">{{ $item->catatan }}</textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer py-2 bg-light">
                                                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-sm btn-primary fw-bold"><i class="fas fa-save me-1"></i> Simpan Perubahan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <!-- Collapsible Row for Details -->
                            <tr id="collapse-details-{{ $item->id }}" class="collapse bg-light-subtle">
                                <td colspan="12" class="p-3 border-top-0">
                                    <div class="row text-start" style="font-size: 11px;">
                                        <div class="col-md-6 border-end">
                                            <p class="fw-bold text-success border-bottom pb-1 mb-2">
                                                <i class="fas fa-boxes me-1"></i> Rincian Bahan Baku &amp; Kemasan
                                            </p>
                                            <table class="table table-sm table-borderless mb-0">
                                                @forelse($materials as $ex)
                                                    <tr>
                                                        <td class="text-muted p-0 py-1">{{ $ex->keterangan }}</td>
                                                        <td class="text-end font-monospace fw-semibold p-0 py-1">Rp {{ number_format($ex->nominal, 0, ',', '.') }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="2" class="text-muted text-center py-1">Tidak ada rincian bahan baku</td>
                                                    </tr>
                                                @endforelse
                                            </table>
                                        </div>
                                        <div class="col-md-6 ps-md-4">
                                            <p class="fw-bold text-primary border-bottom pb-1 mb-2">
                                                <i class="fas fa-cut me-1"></i> Rincian Biaya Jasa &amp; Operasional
                                            </p>
                                            <table class="table table-sm table-borderless mb-0">
                                                @forelse($services as $ex)
                                                    <tr>
                                                        <td class="text-muted p-0 py-1">{{ $ex->keterangan }}</td>
                                                        <td class="text-end font-monospace fw-semibold p-0 py-1">Rp {{ number_format($ex->nominal, 0, ',', '.') }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="2" class="text-muted text-center py-1">Tidak ada rincian biaya jasa</td>
                                                    </tr>
                                                @endforelse
                                            </table>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach

                        <!-- HPP Grand Total -->
                        <tr class="table-light fw-bold border-top border-2">
                            <td colspan="8" class="text-end pe-3 align-middle fs-6">Total Nilai HPP Produksi SPK:</td>
                            <td class="align-middle text-center fs-6 text-dark font-monospace">{{ number_format($spk->items->sum('quantity')) }} pcs</td>
                            <td class="align-middle text-end pe-3 text-primary fs-6 font-monospace">Rp {{ number_format($grandTotalHpp, 0, ',', '.') }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Tracking Progress Tahapan Produksi (Separated Table) -->
            <div class="card border shadow-sm rounded-3 mb-4 bg-white overflow-hidden">
                <div class="card-header bg-white py-3 px-3 border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="fas fa-tasks text-success me-2"></i>Tracking Progress Tahapan Produksi
                        </h6>
                        <small class="text-muted">Klik pada badge angka (misal <span class="badge bg-warning text-dark">2/5</span>) untuk mengupdate jumlah item yang sudah selesai di tahapan tersebut secara real-time.</small>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-sm align-middle text-center mb-0" style="font-size: 12px;">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th class="text-start ps-3" style="width: 30%;">SKU Produk &amp; Ukuran</th>
                                    <th style="width: 12%;">Total Qty</th>
                                    @foreach($spk->proses as $proses)
                                        <th class="text-success fw-bold">
                                            <i class="fas fa-circle-notch me-1" style="font-size: 9px;"></i>{{ $proses->nama_proses }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $prosesTotals = [];
                                    foreach($spk->proses as $pr) { $prosesTotals[$pr->id] = 0; }
                                @endphp
                                @foreach($spk->items as $item)
                                    <tr>
                                        <td class="text-start ps-3 fw-bold text-dark font-monospace">
                                            {{ $item->sku ?: ($item->sku_induk ?: $item->nama_produk) }}
                                            <span class="badge bg-light text-dark border ms-1 font-monospace">{{ $item->ukuran ?: 'All Size' }}</span>
                                        </td>
                                        <td class="fw-bold text-dark">{{ $item->quantity }} pcs</td>
                                        @foreach($spk->proses as $proses)
                                            @php
                                                $pg = $progresMap[$item->id][$proses->id] ?? null;
                                                $qtyDone = $pg ? $pg->qty_done : 0;
                                                $isFull  = $qtyDone >= $item->quantity;
                                                $pgId    = $pg ? $pg->id : null;
                                                $prosesTotals[$proses->id] += $qtyDone;
                                            @endphp
                                            <td class="p-2">
                                                @if($pgId)
                                                    <span
                                                        class="badge {{ $isFull ? 'bg-success' : ($qtyDone > 0 ? 'bg-warning text-dark' : 'bg-secondary') }} progres-badge fw-bold px-2 py-1"
                                                        style="font-size: 11px; cursor: pointer;"
                                                        data-pg-id="{{ $pgId }}"
                                                        data-qty-total="{{ $item->quantity }}"
                                                        data-update-url="{{ route('spks.progres.update', $pgId) }}"
                                                        title="Klik untuk update qty selesai"
                                                    >{{ $qtyDone }} / {{ $item->quantity }}</span>
                                                @else
                                                    <span class="badge bg-light text-muted border" style="font-size: 10px;">—</span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                                <tr class="table-light fw-bold">
                                    <td class="text-end pe-3">Total Selesai Tahapan:</td>
                                    <td>{{ number_format($spk->items->sum('quantity')) }} pcs</td>
                                    @foreach($spk->proses as $proses)
                                        <td class="text-success font-monospace fs-7">
                                            {{ number_format($prosesTotals[$proses->id] ?? 0) }} / {{ number_format($spk->items->sum('quantity')) }} pcs
                                        </td>
                                    @endforeach
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- Setting Biaya SPK di Akhir (Tambahan Jasa & Bahan / Material) -->
            @if(auth()->user()->isSuperAdmin() || auth()->user()->role === 'admin' || auth()->user()->hasRole('admin'))
            <div class="card border shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 px-3 border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-calculator text-primary me-2"></i>Setting Biaya SPK di Akhir (Tambahan Jasa &amp; Bahan / Material)</h6>
                        <small class="text-muted">Kelola total biaya Jasa &amp; Bahan untuk dokumen SPK ini. Sistem akan otomatis membagi total biaya dengan Total Qty SPK ({{ number_format($spk->items->sum('quantity')) }} pcs) untuk menetapkan HPP per unit.</small>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary fw-bold" data-bs-toggle="collapse" data-bs-target="#editGlobalCostsCollapse">
                        <i class="fas fa-edit me-1"></i> Edit / Kelola Biaya SPK
                    </button>
                </div>
                <div class="collapse card-body p-4 bg-light" id="editGlobalCostsCollapse">
                    <form action="{{ route('spks.update_global_costs', $spk->id) }}" method="POST">
                        @csrf
                        <div class="row g-3">
                            {{-- Seksi Tambahan Jasa --}}
                            <div class="col-md-6">
                                <div class="card border shadow-sm h-100 bg-white">
                                    <div class="card-header bg-primary bg-opacity-10 py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                                        <span class="fw-bold small text-primary"><i class="fas fa-cut me-1"></i>1. Tambahan Jasa (Jahit, QC, Printing, dll)</span>
                                        <button type="button" class="btn btn-outline-primary btn-xs py-0 px-2 fw-semibold" id="btnAddShowGlobalJasa">
                                            <i class="fas fa-plus me-1"></i> Tambah Jasa
                                        </button>
                                    </div>
                                    <div class="card-body p-3">
                                        <div id="showGlobalJasaContainer">
                                            @php
                                                $firstItem = $spk->items->first();
                                                $totalSpkQty = $spk->items->sum('quantity') ?: 1;
                                                $existingGlobalJasa = [];
                                                $existingGlobalBahan = [];
                                                if ($firstItem) {
                                                    foreach ($firstItem->extras as $ex) {
                                                        $desc = strtolower($ex->keterangan);
                                                        $totalNom = (float)$ex->nominal * $totalSpkQty;
                                                        if (str_starts_with($desc, 'bahan:') || str_contains($desc, 'bahan')) {
                                                            $cleanKet = str_ireplace('Bahan:', '', $ex->keterangan);
                                                            $existingGlobalBahan[] = ['keterangan' => trim($cleanKet), 'nominal' => $totalNom];
                                                        } else {
                                                            $existingGlobalJasa[] = ['keterangan' => $ex->keterangan, 'nominal' => $totalNom];
                                                        }
                                                    }
                                                }
                                            @endphp
                                            @forelse($existingGlobalJasa as $gIdx => $gj)
                                                <div class="row g-2 mb-2 align-items-center show-global-jasa-row">
                                                    <div class="col">
                                                        <input type="text" name="global_jasa[{{ $gIdx }}][keterangan]" class="form-control form-control-sm" placeholder="Misal: Jasa Jahit" value="{{ $gj['keterangan'] }}">
                                                    </div>
                                                    <div class="col-4">
                                                        <input type="text" name="global_jasa[{{ $gIdx }}][nominal]" class="form-control form-control-sm show-global-jasa-nominal rupiah-mask text-end" placeholder="0" value="{{ number_format($gj['nominal'], 0, ',', '.') }}">
                                                    </div>
                                                    <div class="col-auto">
                                                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-show-global-jasa py-0 px-2 fs-7"><i class="fas fa-times"></i></button>
                                                    </div>
                                                </div>
                                            @empty
                                                {{-- Empty placeholder --}}
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Seksi Tambahan Bahan --}}
                            <div class="col-md-6">
                                <div class="card border shadow-sm h-100 bg-white">
                                    <div class="card-header bg-info bg-opacity-10 py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                                        <span class="fw-bold small text-info"><i class="fas fa-layer-group me-1"></i>2. Tambahan Bahan / Material (Benang, Packing)</span>
                                        <button type="button" class="btn btn-outline-info btn-xs py-0 px-2 fw-semibold" id="btnAddShowGlobalBahan">
                                            <i class="fas fa-plus me-1"></i> Tambah Bahan
                                        </button>
                                    </div>
                                    <div class="card-body p-3">
                                        <div id="showGlobalBahanContainer">
                                            @forelse($existingGlobalBahan as $gIdx => $gb)
                                                <div class="row g-2 mb-2 align-items-center show-global-bahan-row">
                                                    <div class="col">
                                                        <input type="text" name="global_bahan[{{ $gIdx }}][keterangan]" class="form-control form-control-sm" placeholder="Misal: Kain / Aksesoris" value="{{ $gb['keterangan'] }}">
                                                    </div>
                                                    <div class="col-4">
                                                        <input type="text" name="global_bahan[{{ $gIdx }}][nominal]" class="form-control form-control-sm show-global-bahan-nominal rupiah-mask text-end" placeholder="0" value="{{ number_format($gb['nominal'], 0, ',', '.') }}">
                                                    </div>
                                                    <div class="col-auto">
                                                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-show-global-bahan py-0 px-2 fs-7"><i class="fas fa-times"></i></button>
                                                    </div>
                                                </div>
                                            @empty
                                                {{-- Empty placeholder --}}
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <button type="submit" class="btn btn-primary btn-sm fw-bold px-4 shadow-sm">
                                <i class="fas fa-save me-1"></i> Simpan &amp; Hitung Ulang HPP SPK
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            @endif

            <!-- Additional Accessories Block -->
            <div class="border rounded-3 p-3 bg-light mb-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-uppercase fw-bold text-danger" style="font-size: 11px; letter-spacing: 0.5px;">Atribut &amp; Aksesoris Tambahan:</span>
                    <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#editTambahanModal">
                        <i class="fas fa-edit me-1"></i> Edit Catatan Tambahan
                    </button>
                </div>
                <div class="font-monospace text-wrap" style="white-space: pre-wrap; font-size: 12px; color: #333;">
                    @if($spk->tambahan)
                        {{ $spk->tambahan }}
                    @else
                        <span class="text-muted fst-italic">Tidak ada aksesoris / catatan tambahan.</span>
                    @endif
                </div>
            </div>

            <!-- Modal Edit Catatan Tambahan -->
            <div class="modal fade text-start" id="editTambahanModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <form action="{{ route('spks.update_tambahan', $spk->id) }}" method="POST" class="modal-content">
                        @csrf
                        <div class="modal-header bg-light py-2">
                            <h6 class="modal-title fw-bold text-dark"><i class="fas fa-edit me-1 text-primary"></i> Edit Atribut &amp; Aksesoris Tambahan</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-3">
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Catatan Aksesoris / Atribut Tambahan</label>
                                <textarea name="tambahan" class="form-control form-control-sm" rows="5" placeholder="Tuliskan catatan tambahan baru di sini (misal: Bordir Logo 46 Pcs, Kancing Emas, Tambahan Masker...)">{{ old('tambahan', $spk->tambahan) }}</textarea>
                            </div>
                        </div>
                        <div class="modal-footer py-2 bg-light">
                            <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-sm btn-primary fw-bold"><i class="fas fa-save me-1"></i> Simpan Catatan</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Documentation Checklist -->
            <div class="row g-2 border rounded-3 p-3 mb-4 bg-light align-items-center">
                <div class="col-md-4 text-uppercase fw-bold text-dark small" style="letter-spacing: 0.5px;">
                    Bukti Dokumentasi Klien :
                </div>
                <div class="col-md-4 d-flex align-items-center">
                    <div class="border border-dark bg-white me-2 rounded-1" style="width: 16px; height: 16px;"></div>
                    <span class="fw-bold small text-muted">SUDAH FOTO</span>
                </div>
                <div class="col-md-4 d-flex align-items-center">
                    <div class="border border-dark bg-white me-2 rounded-1" style="width: 16px; height: 16px;"></div>
                    <span class="fw-bold small text-muted">SUDAH VIDEO</span>
                </div>
            </div>

            <!-- Signatures Grid -->
            <div class="row g-3 border rounded-3 overflow-hidden text-center mt-4">
                <div class="col-6 border-end p-3" style="min-height: 110px; display: flex; flex-direction: column; justify-content: space-between;">
                    <div class="text-uppercase fw-bold text-muted border-bottom pb-2 mb-3" style="font-size: 10px;">Paraf QC / Gudang</div>
                    <div class="text-muted small">( .................................... )</div>
                </div>
                <div class="col-6 p-3" style="min-height: 110px; display: flex; flex-direction: column; justify-content: space-between;">
                    <div class="text-uppercase fw-bold text-muted border-bottom pb-2 mb-3" style="font-size: 10px;">Project Selesai</div>
                    <div class="text-muted small fw-bold">( Paraf / Cap Tim Marketing )</div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    let showJasaIdx = 200;
    let showBahanIdx = 200;

    $(document).on('click', '#btnAddShowGlobalJasa', function() {
        const idx = showJasaIdx++;
        const html = `
            <div class="row g-2 mb-2 align-items-center show-global-jasa-row">
                <div class="col">
                    <input type="text" name="global_jasa[${idx}][keterangan]" class="form-control form-control-sm" placeholder="Misal: Jasa Jahit / QC">
                </div>
                <div class="col-4">
                    <input type="text" name="global_jasa[${idx}][nominal]" class="form-control form-control-sm show-global-jasa-nominal rupiah-mask text-end" placeholder="0">
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-show-global-jasa py-0 px-2 fs-7"><i class="fas fa-times"></i></button>
                </div>
            </div>`;
        $('#showGlobalJasaContainer').append(html);
    });

    $(document).on('click', '#btnAddShowGlobalBahan', function() {
        const idx = showBahanIdx++;
        const html = `
            <div class="row g-2 mb-2 align-items-center show-global-bahan-row">
                <div class="col">
                    <input type="text" name="global_bahan[${idx}][keterangan]" class="form-control form-control-sm" placeholder="Misal: Kain / Benang">
                </div>
                <div class="col-4">
                    <input type="text" name="global_bahan[${idx}][nominal]" class="form-control form-control-sm show-global-bahan-nominal rupiah-mask text-end" placeholder="0">
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-show-global-bahan py-0 px-2 fs-7"><i class="fas fa-times"></i></button>
                </div>
            </div>`;
        $('#showGlobalBahanContainer').append(html);
    });

    $(document).on('click', '.btn-remove-show-global-jasa, .btn-remove-show-global-bahan', function() {
        $(this).closest('.row').remove();
    });

    // ── INLINE PROGRES BADGE CLICK → show qty editor modal ──
    let activePgUrl = null;
    let activeBadge = null;
    $(document).on('click', '.progres-badge', function() {
        activeBadge = $(this);
        activePgUrl = $(this).data('update-url');
        const qtyTotal = $(this).data('qty-total');
        const currentDone = parseInt($(this).text().split('/')[0]);
        $('#progresQtyInput').val(currentDone).attr('max', qtyTotal);
        $('#progresQtyMax').text('/ ' + qtyTotal + ' pcs');
        $('#progresEditorModal').modal('show');
    });

    $('#btnSaveProgres').on('click', function() {
        const qtyDone = parseInt($('#progresQtyInput').val());
        if (!activePgUrl || isNaN(qtyDone)) return;

        $.ajax({
            url: activePgUrl,
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                qty_done: qtyDone,
            },
            success: function(res) {
                if (res.success && activeBadge) {
                    const total = activeBadge.data('qty-total');
                    activeBadge.text(res.qty_done + '/' + total);
                    const done = res.qty_done;
                    activeBadge.removeClass('bg-success bg-warning bg-secondary text-dark');
                    if (done >= total) {
                        activeBadge.addClass('bg-success');
                    } else if (done > 0) {
                        activeBadge.addClass('bg-warning text-dark');
                    } else {
                        activeBadge.addClass('bg-secondary');
                    }
                    $('#progresEditorModal').modal('hide');
                }
            },
            error: function() {
                alert('Gagal menyimpan progres. Coba lagi.');
            }
        });
    });
</script>

{{-- Modal: Inline Progres Qty Editor --}}
<div class="modal fade" id="progresEditorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-light py-2">
                <h6 class="modal-title fw-bold text-dark"><i class="fas fa-edit me-1 text-success"></i> Update Qty Selesai</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3 text-center">
                <label class="form-label small fw-semibold text-muted">Jumlah yang sudah selesai tahap ini:</label>
                <div class="d-flex align-items-center justify-content-center gap-2 mt-1">
                    <input type="number" id="progresQtyInput" class="form-control form-control-sm text-center fw-bold fs-5" min="0" style="width:80px;">
                    <span class="fw-bold text-muted" id="progresQtyMax"></span>
                </div>
            </div>
            <div class="modal-footer py-2 bg-light justify-content-center">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" id="btnSaveProgres" class="btn btn-sm btn-success fw-bold px-4">
                    <i class="fas fa-check me-1"></i> Simpan
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
