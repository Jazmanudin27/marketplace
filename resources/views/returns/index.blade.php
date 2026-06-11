@extends('layouts.app')
@section('title', 'Manajemen Retur Otomatis')
@section('page-title', 'Pesanan Retur')

@section('content')
    <div class="card dashboard-card">
        <div class="card-header-line" style="display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h3 style="margin-bottom: 0.25rem;"><i class="fas fa-undo-alt"></i> Pusat Resolusi & Retur</h3>
                <p class="text-muted" style="font-size:0.85rem; margin-bottom:0;">Pantau pesanan yang dibatalkan atau
                    dikembalikan oleh pembeli, lalu kembalikan stok fisik ke gudang.</p>
            </div>
            <div class="d-flex" style="gap:0.5rem;">
                <form action="{{ route('returns.index') }}" method="GET" class="d-flex" style="gap:0.5rem;">
                    <input type="text" name="search" class="form-control form-control-sm"
                        placeholder="Cari Resi Retur / Invoice..." value="{{ $search }}">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
                </form>
                <form action="{{ route('returns.sync') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="fas fa-sync-alt"></i> Tarik Data Retur Shopee
                    </button>
                </form>
            </div>
        </div>

        <div class="table-responsive mt-4">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Waktu Dibuat</th>
                        <th>Detail Retur & Invoice Asli</th>
                        <th>Barang yang Diretur</th>
                        <th style="text-align:center;">Alasan / Status</th>
                        <th style="text-align:center;">Tindakan Gudang</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($returns as $ret)
                        <tr>
                            <td>{{ $ret->created_at->format('d M Y, H:i') }}</td>
                            <td>
                                <div class="fw-bold mono">{{ $ret->return_sn }}</div>
                                <div style="font-size:0.8rem; margin-top:0.3rem;">
                                    Asal: <a
                                        href="{{ route('orders.show', $ret->order->id) }}">{{ $ret->order->invoice_number ?? $ret->order->order_marketplace_id }}</a>
                                </div>
                                <div style="font-size:0.8rem; margin-top:0.2rem;">
                                    Pembeli: <span class="fw-bold">{{ $ret->order->buyer_name ?? '-' }}</span>
                                </div>
                            </td>
                            <td>
                                <ul style="padding-left: 1rem; margin-bottom:0; font-size:0.85rem;">
                                    @foreach ($ret->items as $rItem)
                                        @php
                                            $mpProduct = $rItem->orderItem->marketplaceProduct ?? null;
                                        @endphp
                                        <li>
                                            {{ $rItem->quantity }}x
                                            {{ $mpProduct ? $mpProduct->name : $rItem->orderItem->product_name ?? 'Item Tidak Ditemukan' }}
                                        </li>
                                    @endforeach
                                </ul>
                            </td>
                            <td style="text-align:center;">
                                <span class="badge bg-danger">{{ $ret->status }}</span>
                                <div style="font-size:0.8rem; margin-top:0.3rem; font-style:italic;" class="text-muted">
                                    "{{ $ret->reason ?? 'Tidak ada alasan' }}"
                                </div>
                            </td>
                            <td style="text-align:center;">
                                @if ($ret->is_restocked)
                                    @if ($ret->inspection_status === 'GOOD')
                                        <div class="d-flex flex-column align-items-center">
                                            <span class="badge bg-success mb-1" style="font-size:0.8rem; padding: 0.4rem 0.6rem; border-radius: 50px;">
                                                <i class="fas fa-check-circle"></i> Layak Jual
                                            </span>
                                            <span style="font-size:0.75rem; color:var(--text-secondary);">Stok Ditambah</span>
                                            @if($ret->inspection_notes)
                                                <div class="mt-1 text-muted" style="font-size:0.75rem; max-width: 150px; white-space: normal; line-height: 1.2;">
                                                    <i class="fas fa-comment-alt text-secondary"></i> "{{ $ret->inspection_notes }}"
                                                </div>
                                            @endif
                                        </div>
                                    @elseif ($ret->inspection_status === 'DEFECTIVE')
                                        <div class="d-flex flex-column align-items-center">
                                            <span class="badge bg-danger mb-1" style="font-size:0.8rem; padding: 0.4rem 0.6rem; border-radius: 50px;">
                                                <i class="fas fa-times-circle"></i> Rusak / Cacat
                                            </span>
                                            <span style="font-size:0.75rem; color:var(--text-secondary);">Stok Diabaikan</span>
                                            @if($ret->inspection_notes)
                                                <div class="mt-1 text-muted" style="font-size:0.75rem; max-width: 150px; white-space: normal; line-height: 1.2;">
                                                    <i class="fas fa-comment-alt text-secondary"></i> "{{ $ret->inspection_notes }}"
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <span class="badge bg-success" style="font-size:0.85rem; padding: 0.5rem 0.75rem;">
                                            <i class="fas fa-check-circle"></i> Stok Dikembalikan
                                        </span>
                                    @endif
                                @else
                                    <button type="button" class="btn-primary-sm" data-bs-toggle="modal" data-bs-target="#qcModal-{{ $ret->id }}">
                                        <i class="fas fa-clipboard-check"></i> Terima & QC
                                    </button>
                                    <div style="font-size:0.75rem; color:var(--text-secondary); margin-top:0.3rem;">
                                        Klik untuk periksa fisik<br>dan terima barang.
                                    </div>

                                    <!-- Modal QC untuk Retur ini -->
                                    <div class="modal fade text-start" id="qcModal-{{ $ret->id }}" tabindex="-1" aria-labelledby="qcModalLabel-{{ $ret->id }}" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content" style="background-color: var(--bg-card); border: 1px solid var(--border);">
                                                <div class="modal-header" style="border-bottom: 1px solid var(--border);">
                                                    <h5 class="modal-title" id="qcModalLabel-{{ $ret->id }}">
                                                        <i class="fas fa-undo-alt text-primary me-2"></i> Quality Control Retur: {{ $ret->return_sn }}
                                                    </h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form action="{{ route('returns.restock', $ret->id) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold">Barang yang Diretur:</label>
                                                            <ul class="list-group list-group-flush mb-0" style="background: transparent;">
                                                                @foreach ($ret->items as $rItem)
                                                                    @php
                                                                        $mpProduct = $rItem->orderItem->marketplaceProduct ?? null;
                                                                    @endphp
                                                                    <li class="list-group-item px-0 py-1" style="background: transparent; color: var(--text-primary); border: none;">
                                                                        <i class="fas fa-box text-secondary me-2"></i>
                                                                        <span class="badge bg-secondary me-2">{{ $rItem->quantity }} Pcs</span>
                                                                        {{ $mpProduct ? $mpProduct->name : $rItem->orderItem->product_name ?? 'Item Tidak Ditemukan' }}
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        </div>

                                                        <hr style="border-color: var(--border); margin: 1rem 0;">

                                                        <div class="mb-3">
                                                            <label for="inspection_status-{{ $ret->id }}" class="form-label fw-bold">Hasil Inspeksi / Kondisi Barang</label>
                                                            <select name="inspection_status" id="inspection_status-{{ $ret->id }}" class="form-control" style="background-color: var(--bg-body); border: 1px solid var(--border); color: var(--text-primary); cursor: pointer;" required>
                                                                <option value="GOOD">Layak Jual / Good (Kembali ke Stok Aktif)</option>
                                                                <option value="DEFECTIVE">Rusak / Defective (Tidak Mengubah Stok Gudang)</option>
                                                            </select>
                                                            <div class="form-text mt-2" style="font-size: 0.75rem; color: var(--text-secondary);">
                                                                <i class="fas fa-info-circle text-info"></i> Jika dipilih <strong>Layak Jual</strong>, stok gudang akan bertambah dan otomatis di-push ke Shopee/TikTok.
                                                            </div>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="inspection_notes-{{ $ret->id }}" class="form-label fw-bold">Catatan Inspeksi (Optional)</label>
                                                            <textarea name="inspection_notes" id="inspection_notes-{{ $ret->id }}" rows="3" class="form-control" placeholder="Tulis deskripsi kondisi fisik barang, misal: 'Kemasan sobek sedikit tapi produk aman' atau 'Kaki penyangga patah, tidak layak jual'." style="background-color: var(--bg-body); border: 1px solid var(--border); color: var(--text-primary);"></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer" style="border-top: 1px solid var(--border);">
                                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn btn-primary btn-sm">
                                                            <i class="fas fa-check"></i> Simpan Hasil QC
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align:center; padding:3rem; color:var(--text-secondary);">
                                <i class="fas fa-box-check"
                                    style="font-size:2rem; margin-bottom:1rem; opacity:0.5;"></i><br>
                                Belum ada data barang retur. Klik "Tarik Data Retur Shopee" untuk memeriksa.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $returns->withQueryString()->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection
