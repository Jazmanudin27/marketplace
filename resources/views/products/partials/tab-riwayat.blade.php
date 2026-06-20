{{-- ═══════════════════════════════════════════════════════════════
     TAB 2: RIWAYAT PUBLIKASI
     Variables: $publicationLogs
═══════════════════════════════════════════════════════════════ --}}

<div class="dashboard-card">
    <div class="card-header-line d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0"><i class="fas fa-history me-2 text-primary"></i>Riwayat Publikasi</h5>
            <p class="text-muted mb-0 mt-1" style="font-size:0.75rem;">
                Log publish produk ke marketplace — auto-refresh setiap 10 detik jika ada job berjalan
            </p>
        </div>
        <button onclick="location.reload()" class="btn btn-secondary btn-sm px-3">
            <i class="fas fa-sync-alt me-1"></i>Refresh
        </button>
    </div>

    @if($publicationLogs->isEmpty())
        <div class="text-center text-muted py-5" style="font-size:0.82rem;">
            <i class="fas fa-inbox d-block mb-2 opacity-25" style="font-size:2rem;"></i>
            Belum ada riwayat publikasi.
        </div>
    @else
        <div class="table-responsive rounded border border-secondary border-opacity-10 mt-3">
            <table class="table table-sm table-bordered table-premium-dark align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>PRODUK</th>
                        <th>TOKO</th>
                        <th>KATEGORI</th>
                        <th class="text-center">STATUS</th>
                        <th>KETERANGAN</th>
                        <th>WAKTU</th>
                        <th class="text-center" style="width:80px;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($publicationLogs as $log)
                        <tr>
                            <td>
                                <code class="text-muted font-monospace" style="font-size:0.72rem;">{{ $log->id }}</code>
                            </td>
                            <td>
                                <strong class="text-white" style="font-size:0.82rem;">{{ $log->masterProduct->name ?? '—' }}</strong>
                                <div><code class="text-muted font-monospace" style="font-size:0.7rem;">{{ $log->masterProduct->sku ?? '' }}</code></div>
                            </td>
                            <td>
                                @if($log->store)
                                    <span class="channel-badge channel-{{ $log->store->channel->code ?? '' }}"
                                        style="font-size:0.65rem;">
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
                            <td style="max-width:160px; font-size:0.78rem;">
                                <div class="text-truncate" title="{{ $log->category_name }}">{{ $log->category_name ?? '—' }}</div>
                                @if($log->category_id)
                                    <code class="text-muted" style="font-size:0.68rem;">ID: {{ $log->category_id }}</code>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($log->status === 'pending')
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size:0.68rem;">
                                        <i class="fas fa-clock me-1"></i>Menunggu
                                    </span>
                                @elseif($log->status === 'processing')
                                    <span class="badge bg-info-subtle text-info border border-info-subtle" style="font-size:0.68rem;">
                                        <i class="fas fa-spinner fa-spin me-1"></i>Diproses
                                    </span>
                                @elseif($log->status === 'success')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:0.68rem;">
                                        <i class="fas fa-check-circle me-1"></i>Berhasil
                                    </span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size:0.68rem;">
                                        <i class="fas fa-times-circle me-1"></i>Gagal
                                    </span>
                                @endif
                            </td>
                            <td style="max-width:200px; font-size:0.78rem;">
                                @if($log->status === 'success' && $log->marketplace_product_id)
                                    <span class="font-monospace text-success" style="font-size:0.72rem;">
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
                            <td class="text-nowrap" style="font-size:0.75rem;">
                                <span class="text-muted">{{ $log->created_at->diffForHumans() }}</span>
                            </td>
                            <td class="text-center">
                                @if($log->status === 'failed')
                                    <form action="{{ route('products.publish.retry', $log->id) }}"
                                        method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-primary btn-action-sm" title="Retry Publikasi">
                                            <i class="fas fa-redo"></i>
                                        </button>
                                    </form>
                                @elseif($log->status === 'success')
                                    <span class="text-success" style="font-size:0.75rem;">
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
