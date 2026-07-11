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

                    {{-- Rincian HPP Per Item --}}
                    <h6 class="fw-bold text-dark mt-4 mb-2">
                        <i class="fas fa-calculator me-2 text-primary"></i>Rincian HPP Per Item Produksi
                    </h6>
                    @php $grandTotalCost = 0; @endphp
                    @foreach($spk->items as $item)
                        @php
                            $subtotal = $item->hpp * $item->quantity;
                            $grandTotalCost += $subtotal;
                        @endphp
                        <div class="card border mb-2">
                            <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="fw-bold text-dark">{{ $item->nama_produk }}</span>
                                    <span class="badge bg-light text-dark border ms-2">{{ $item->ukuran ?: 'All Size' }}</span>
                                    @if($item->alur_proses)
                                        <span class="badge bg-info-subtle text-info border border-info border-opacity-10 ms-1">{{ $item->alur_proses }}</span>
                                    @endif
                                </div>
                                <div class="text-end">
                                    <span class="text-muted small me-3">Penjahit: <strong>{{ $item->penjahit ?: '—' }}</strong></span>
                                    <span class="text-muted small me-3">Qty: <strong>{{ $item->quantity }} pcs</strong></span>
                                    <span class="fw-bold text-success">HPP: Rp {{ number_format($item->hpp, 0, ',', '.') }}/pcs</span>
                                    <span class="fw-bold text-primary ms-3">Subtotal: Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                                </div>
                            </div>
                            <div class="card-body p-2">
                                <div class="row g-2" style="font-size:11px;">
                                    <div class="col-md-3">
                                        <p class="fw-semibold text-primary mb-1">Biaya Jasa</p>
                                        <table class="table table-sm mb-0">
                                            <tr><td class="text-muted">Jasa Konveksi</td><td class="text-end font-monospace">Rp {{ number_format($item->jasa_konveksi, 0, ',', '.') }}</td></tr>
                                            <tr><td class="text-muted">Jasa Potong</td><td class="text-end font-monospace">Rp {{ number_format($item->jasa_potong, 0, ',', '.') }}</td></tr>
                                            <tr><td class="text-muted">Jasa Printing</td><td class="text-end font-monospace">Rp {{ number_format($item->jasa_printing, 0, ',', '.') }}</td></tr>
                                            <tr><td class="text-muted">Jasa Jahit</td><td class="text-end font-monospace">Rp {{ number_format($item->jasa_jahit, 0, ',', '.') }}</td></tr>
                                            <tr><td class="text-muted">Jasa Labsas</td><td class="text-end font-monospace">Rp {{ number_format($item->jasa_labsas, 0, ',', '.') }}</td></tr>
                                        </table>
                                    </div>
                                    <div class="col-md-3">
                                        <p class="fw-semibold text-success mb-1">Biaya Bahan</p>
                                        <table class="table table-sm mb-0">
                                            <tr><td class="text-muted">Kebutuhan Kain</td><td class="text-end font-monospace">{{ $item->kebutuhan_kain }} m</td></tr>
                                            <tr><td class="text-muted">Biaya Kain</td><td class="text-end font-monospace">Rp {{ number_format($item->biaya_kain, 0, ',', '.') }}</td></tr>
                                            <tr><td class="text-muted">SBS</td><td class="text-end font-monospace">Rp {{ number_format($item->biaya_sbs, 0, ',', '.') }}</td></tr>
                                            <tr><td class="text-muted">Pitta</td><td class="text-end font-monospace">Rp {{ number_format($item->biaya_pitta, 0, ',', '.') }}</td></tr>
                                        </table>
                                    </div>
                                    <div class="col-md-3">
                                        <p class="fw-semibold text-warning mb-1">Komponen Kecil</p>
                                        <table class="table table-sm mb-0">
                                            <tr><td class="text-muted">Kancing</td><td class="text-end font-monospace">Rp {{ number_format($item->biaya_kancing, 0, ',', '.') }}</td></tr>
                                            <tr><td class="text-muted">Kancing Kait</td><td class="text-end font-monospace">Rp {{ number_format($item->biaya_kancing_kait, 0, ',', '.') }}</td></tr>
                                            <tr><td class="text-muted">Karet</td><td class="text-end font-monospace">Rp {{ number_format($item->biaya_karet, 0, ',', '.') }}</td></tr>
                                            <tr><td class="text-muted">Plastik</td><td class="text-end font-monospace">Rp {{ number_format($item->biaya_plastik, 0, ',', '.') }}</td></tr>
                                            <tr><td class="text-muted">String/Tali</td><td class="text-end font-monospace">Rp {{ number_format($item->biaya_string, 0, ',', '.') }}</td></tr>
                                        </table>
                                    </div>
                                    <div class="col-md-3">
                                        <p class="fw-semibold text-danger mb-1">Finishing & Lainnya</p>
                                        <table class="table table-sm mb-0">
                                            <tr><td class="text-muted">Bordir/Logo</td><td class="text-end font-monospace">Rp {{ number_format($item->biaya_bordir, 0, ',', '.') }}</td></tr>
                                            <tr><td class="text-muted">Servis</td><td class="text-end font-monospace">Rp {{ number_format($item->biaya_servis, 0, ',', '.') }}</td></tr>
                                            <tr><td class="text-muted">Finishing</td><td class="text-end font-monospace">Rp {{ number_format($item->biaya_finishing, 0, ',', '.') }}</td></tr>
                                            <tr><td class="text-muted">Pengiriman</td><td class="text-end font-monospace">Rp {{ number_format($item->biaya_pengiriman, 0, ',', '.') }}</td></tr>
                                            @foreach($item->extras as $extra)
                                                <tr><td class="text-muted">{{ $extra->keterangan }}</td><td class="text-end font-monospace">Rp {{ number_format($extra->nominal, 0, ',', '.') }}</td></tr>
                                            @endforeach
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <div class="d-flex justify-content-end gap-4 mt-2 p-3 bg-light rounded border">
                        <div class="text-end">
                            <div class="text-muted small">Total Quantity</div>
                            <div class="fw-bold text-dark fs-6">{{ $spk->items->sum('quantity') }} pcs</div>
                        </div>
                        <div class="text-end">
                            <div class="text-muted small">Total HPP Produksi SPK</div>
                            <div class="fw-bold text-primary fs-5">Rp {{ number_format($grandTotalCost, 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
