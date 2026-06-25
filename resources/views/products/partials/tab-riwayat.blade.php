{{-- ═══════════════════════════════════════════════════════════════
     TAB 2: RIWAYAT PUBLIKASI
     Variables: $publicationLogs
 ═══════════════════════════════════════════════════════════════ --}}

<div class="card border shadow-sm">
    <div class="card-header bg-info bg-opacity-10 d-flex justify-content-between align-items-center border-bottom py-2 px-3">
        <div>
            <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-history me-2 text-info"></i>Riwayat Publikasi</h6>
            <small class="text-muted d-block">
                Log publish produk ke marketplace — auto-refresh setiap 10 detik jika ada job berjalan
            </small>
        </div>
        <button onclick="location.reload()" class="btn btn-secondary btn-sm px-3 rounded-3">
            <i class="fas fa-sync-alt me-1"></i>Refresh
        </button>
    </div>

    <div class="card-body p-3">
        @if($publicationLogs->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="fas fa-inbox d-block mb-2 opacity-25 fs-2"></i>
                Belum ada riwayat publikasi.
            </div>
        @else
            <div class="table-responsive rounded border mt-2">
                <table class="table table-sm table-striped table-bordered align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>PRODUK</th>
                            <th>TOKO</th>
                            <th>KATEGORI</th>
                            <th class="text-center">STATUS</th>
                            <th>KETERANGAN</th>
                            <th>WAKTU</th>
                            <th class="text-center">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($publicationLogs as $log)
                            <tr>
                                <td>
                                    <code class="text-secondary font-monospace">{{ $log->id }}</code>
                                </td>
                                <td>
                                    <strong class="text-dark">{{ $log->masterProduct->name ?? '—' }}</strong>
                                    <div><code class="text-secondary font-monospace">{{ $log->masterProduct->sku ?? '' }}</code></div>
                                </td>
                                <td>
                                    @if($log->store)
                                        @php
                                            $badgeClass = 'bg-secondary';
                                            if ($log->store->channel->code === 'shopee') {
                                                $badgeClass = 'bg-danger text-white';
                                            } elseif ($log->store->channel->code === 'tiktok') {
                                                $badgeClass = 'bg-dark text-white';
                                            }
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">
                                            @if(($log->store->channel->code ?? '') === 'shopee')
                                                <i class="fab fa-shopify"></i>
                                            @elseif(($log->store->channel->code ?? '') === 'tiktok')
                                                <i class="fab fa-tiktok"></i>
                                            @endif
                                            {{ $log->store->store_name }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="text-truncate" title="{{ $log->category_name }}">{{ $log->category_name ?? '—' }}</div>
                                    @if($log->category_id)
                                        <code class="text-secondary">ID: {{ $log->category_id }}</code>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($log->status === 'pending')
                                        <span class="badge bg-warning text-dark">
                                            <i class="fas fa-clock me-1"></i>Menunggu
                                        </span>
                                    @elseif($log->status === 'processing')
                                        <span class="badge bg-info text-dark">
                                            <i class="fas fa-spinner fa-spin me-1"></i>Diproses
                                        </span>
                                    @elseif($log->status === 'success')
                                        <span class="badge bg-success">
                                            <i class="fas fa-check-circle me-1"></i>Berhasil
                                        </span>
                                    @else
                                        <span class="badge bg-danger">
                                            <i class="fas fa-times-circle me-1"></i>Gagal
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($log->status === 'success' && $log->marketplace_product_id)
                                        <span class="font-monospace text-success">
                                            <i class="fas fa-link me-1"></i>ID: {{ $log->marketplace_product_id }}
                                        </span>
                                    @elseif($log->status === 'failed' && $log->error_message)
                                        <span class="text-danger small" title="{{ $log->error_message }}">
                                            <i class="fas fa-exclamation-circle me-1"></i>{{ Str::limit($log->error_message, 70) }}
                                        </span>
                                    @elseif(in_array($log->status, ['pending', 'processing']))
                                        <span class="text-muted small">Queue worker sedang memproses...</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    <span class="text-muted">{{ $log->created_at->diffForHumans() }}</span>
                                </td>
                                <td class="text-center">
                                    @if($log->status === 'failed')
                                        <form action="{{ route('products.publish.retry', $log->id) }}"
                                            method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-primary btn-sm rounded-3" title="Retry Publikasi">
                                                <i class="fas fa-redo"></i>
                                            </button>
                                        </form>
                                    @elseif($log->status === 'success')
                                        <span class="text-success">
                                            <i class="fas fa-check-circle"></i>
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
