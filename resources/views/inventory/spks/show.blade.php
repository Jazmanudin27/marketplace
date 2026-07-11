@extends('layouts.app')
@section('title', 'Detail SPK #' . $spk->no_spk)
@section('page-title', 'Detail Surat Perintah Kerja')

@section('content')
<div class="container-fluid p-0">
    <div class="mb-3 d-flex justify-content-between align-items-center">
        <a href="{{ route('spks.index') }}" class="btn btn-secondary btn-sm rounded-3">
            <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar
        </a>
        <div class="d-flex gap-2">
            <a href="{{ route('spks.print', $spk) }}" target="_blank" class="btn btn-primary btn-sm rounded-3 fw-bold">
                <i class="fas fa-print me-1"></i> Cetak Lembar SPK
            </a>
            <form action="{{ route('spks.destroy', $spk) }}" method="POST" class="m-0"
                onsubmit="return confirm('Apakah Anda yakin ingin menghapus data SPK ini?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm rounded-3 fw-bold">
                    <i class="fas fa-trash-alt me-1"></i> Hapus
                </button>
            </form>
        </div>
    </div>

    <div class="row g-3">
        {{-- Kiri: Detail Informasi --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 bg-white mb-3">
                <div class="card-header bg-light border-0 py-3 px-4">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="fas fa-info-circle me-2 text-primary"></i>Informasi SPK
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="border rounded-2 overflow-hidden mb-3">
                        <div class="d-flex justify-content-between px-3 py-2 border-bottom" style="background:#f8fafc">
                            <span class="text-muted small">No. SPK</span>
                            <span class="font-monospace fw-bold text-dark small">{{ $spk->no_spk }}</span>
                        </div>
                        <div class="d-flex justify-content-between px-3 py-2 border-bottom">
                            <span class="text-muted small">No. Produksi</span>
                            <span class="font-monospace fw-bold text-dark small">{{ $spk->no_produksi ?: '—' }}</span>
                        </div>
                        <div class="d-flex justify-content-between px-3 py-2 border-bottom">
                            <span class="text-muted small">Tanggal Order</span>
                            <span class="small fw-semibold text-dark">{{ $spk->tanggal ? $spk->tanggal->format('d F Y') : '—' }}</span>
                        </div>
                        <div class="d-flex justify-content-between px-3 py-2 border-bottom" style="background:#fef2f2">
                            <span class="text-muted small text-danger fw-bold">Jatuh Tempo (Deadline)</span>
                            <span class="small text-danger fw-bold">{{ $spk->deadline ? $spk->deadline->format('d F Y') : '—' }}</span>
                        </div>
                        <div class="d-flex justify-content-between px-3 py-2 border-bottom">
                            <span class="text-muted small">Nama Pemesan</span>
                            <span class="small fw-bold text-dark">{{ $spk->pemesan ?: '—' }}</span>
                        </div>
                        <div class="d-flex justify-content-between px-3 py-2 border-bottom">
                            <span class="text-muted small">No. HP Pemesan</span>
                            <span class="small text-muted font-monospace">{{ $spk->no_hp_pemesan ?: '—' }}</span>
                        </div>
                        <div class="d-flex justify-content-between px-3 py-2 border-bottom">
                            <span class="text-muted small">Instansi</span>
                            <span class="small text-dark">{{ $spk->instansi ?: '—' }}</span>
                        </div>
                        <div class="d-flex justify-content-between px-3 py-2">
                            <span class="text-muted small">Penginput</span>
                            <span class="small text-muted fw-semibold">{{ $spk->penginput->name ?? 'SYSTEM' }}</span>
                        </div>
                    </div>

                    @if($spk->tambahan)
                        <div class="mb-0">
                            <label class="small text-muted fw-semibold d-block">Catatan Tambahan &amp; Aksesoris</label>
                            <div class="p-2 rounded bg-light small text-muted text-wrap font-monospace" style="white-space: pre-wrap;">{{ $spk->tambahan }}</div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Desain Visual --}}
            <div class="card border-0 shadow-sm rounded-3 bg-white">
                <div class="card-header bg-light border-0 py-3 px-4">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="fas fa-image me-2 text-primary"></i>Visual Desain
                    </h6>
                </div>
                <div class="card-body p-4 text-center">
                    @if($spk->image_url)
                        <img src="{{ $spk->image_url }}" alt="Desain Pakaian" class="img-fluid rounded border p-2 bg-light shadow-sm" style="max-height: 250px;">
                    @else
                        <div class="py-4 text-muted border border-dashed rounded bg-light small">
                            <i class="fas fa-image fa-2x opacity-25 d-block mb-1"></i>
                            Tidak ada gambar desain diupload.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Kanan: Detail Grid Item & Penjahit --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-3 bg-white mb-3">
                <div class="card-header bg-primary text-white border-0 py-3 px-4">
                    <h6 class="fw-bold mb-0">
                        <i class="fas fa-boxes me-2"></i>Rincian Produk &amp; Tugas Produksi (Tukang Jahit)
                    </h6>
                </div>
                <div class="card-body p-4">
                    {{-- Grid Breakdown --}}
                    <h6 class="fw-bold text-dark mb-2"><i class="fas fa-table me-2 text-primary"></i>Matriks Ukuran &amp; Penjahit (SPK Grid)</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle text-center mb-0">
                            <thead class="table-light small text-muted">
                                <tr>
                                    <th rowspan="2" class="align-middle text-start ps-3" style="width: 25%;">Model Varian</th>
                                    <th colspan="6">Size</th>
                                    <th rowspan="2" class="align-middle" style="width: 12%;">Total Qty</th>
                                    <th rowspan="2" class="align-middle text-start ps-3" style="width: 25%;">Tukang Jahit</th>
                                </tr>
                                <tr>
                                    <th>S</th>
                                    <th>M</th>
                                    <th>L</th>
                                    <th>XL</th>
                                    <th>XXL</th>
                                    <th>3XL</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($grouped as $model)
                                    <tr>
                                        <td class="text-start ps-3 fw-bold text-dark">{{ $model['model'] }}</td>
                                        <td>{{ $model['sizes']['S'] > 0 ? $model['sizes']['S'] : '—' }}</td>
                                        <td>{{ $model['sizes']['M'] > 0 ? $model['sizes']['M'] : '—' }}</td>
                                        <td>{{ $model['sizes']['L'] > 0 ? $model['sizes']['L'] : '—' }}</td>
                                        <td>{{ $model['sizes']['XL'] > 0 ? $model['sizes']['XL'] : '—' }}</td>
                                        <td>{{ $model['sizes']['XXL'] > 0 ? $model['sizes']['XXL'] : '—' }}</td>
                                        <td>{{ $model['sizes']['3XL'] > 0 ? $model['sizes']['3XL'] : '—' }}</td>
                                        <td class="fw-bold text-primary">{{ $model['total'] }} pcs</td>
                                        <td class="text-start ps-3 small font-monospace text-muted">{{ $model['tailors_list'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-3 small">Tidak ada data item.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Raw Items Table List --}}
                    <h6 class="fw-bold text-dark mt-4 mb-2"><i class="fas fa-list me-2 text-primary"></i>Daftar Item Flat &amp; HPP Produksi</h6>
                    <div class="table-responsive border rounded">
                        <table class="table table-sm table-striped align-middle mb-0" style="font-size: 11px;">
                            <thead class="table-light">
                                <tr class="text-muted">
                                    <th class="ps-3">Nama Produk</th>
                                    <th>SKU Induk</th>
                                    <th>SKU Varian</th>
                                    <th>Ukuran</th>
                                    <th class="text-center">Qty</th>
                                    <th>Tukang Jahit</th>
                                    <th>Alur Kerja</th>
                                    <th class="text-end">Bahan (pcs)</th>
                                    <th class="text-end">Jahit (pcs)</th>
                                    <th class="text-end">Print (pcs)</th>
                                    <th class="text-end">HPP / Pcs</th>
                                    <th class="text-end pe-3">Subtotal Biaya</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $grandTotalCost = 0; @endphp
                                @foreach($spk->items as $item)
                                    @php 
                                        $subtotal = $item->hpp * $item->quantity; 
                                        $grandTotalCost += $subtotal;
                                    @endphp
                                    <tr>
                                        <td class="ps-3 fw-semibold text-dark">{{ $item->nama_produk }}</td>
                                        <td class="font-monospace text-muted">{{ $item->sku_induk ?: '—' }}</td>
                                        <td class="font-monospace text-primary fw-bold">{{ $item->sku ?: '—' }}</td>
                                        <td><span class="badge bg-light text-dark border">{{ $item->ukuran ?: 'All Size' }}</span></td>
                                        <td class="text-center fw-bold">{{ $item->quantity }}</td>
                                        <td class="font-monospace text-muted">{{ $item->penjahit ?: '—' }}</td>
                                        <td>
                                            <span class="badge bg-info-subtle text-info border border-info border-opacity-10">{{ $item->alur_proses ?: 'Langsung Jahit' }}</span>
                                        </td>
                                        <td class="text-end font-monospace">Rp {{ number_format($item->biaya_bahan, 0, ',', '.') }}</td>
                                        <td class="text-end font-monospace">Rp {{ number_format($item->ongkos_jahit, 0, ',', '.') }}</td>
                                        <td class="text-end font-monospace">Rp {{ number_format($item->ongkos_printing, 0, ',', '.') }}</td>
                                        <td class="text-end fw-bold text-success font-monospace">Rp {{ number_format($item->hpp, 0, ',', '.') }}</td>
                                        <td class="text-end fw-bold text-primary pe-3 font-monospace">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light fw-bold text-dark" style="font-size: 12px;">
                                <tr>
                                    <td colspan="4" class="ps-3 text-start">Total Kuantitas</td>
                                    <td class="text-center bg-light text-primary">{{ $spk->items->sum('quantity') }} pcs</td>
                                    <td colspan="6" class="text-end">Total HPP Produksi SPK:</td>
                                    <td class="text-end text-primary pe-3 font-monospace fs-6">Rp {{ number_format($grandTotalCost, 0, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
