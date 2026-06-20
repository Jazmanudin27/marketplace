<div class="dashboard-card">
    <div class="card-header-line">
        <h5 class="mb-0"><i class="fas fa-sitemap me-2 text-primary"></i>Pemetaan Kategori</h5>
        <p class="text-muted mb-0 mt-1" style="font-size:0.75rem;">
            Pemetaan kategori lokal ke kategori marketplace. Aktifkan "Simpan Pemetaan" saat publish
            agar sistem otomatis memilih kategori yang tepat.
        </p>
    </div>

    @if ($categoryMappings->isEmpty())
        <div class="text-center text-muted py-5" style="font-size:0.82rem;">
            <i class="fas fa-map-marked-alt d-block mb-2 opacity-25" style="font-size:2rem;"></i>
            Belum ada pemetaan kategori.
        </div>
    @else
        <div class="table-responsive rounded border border-secondary border-opacity-10 mt-3">
            <table class="table table-sm table-bordered table-premium-dark align-middle mb-0">
                <thead>
                    <tr>
                        <th>KATEGORI LOKAL</th>
                        <th>TOKO</th>
                        <th>KATEGORI MARKETPLACE</th>
                        <th>ID MARKETPLACE</th>
                        <th class="text-center" style="width:70px;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($categoryMappings as $mapping)
                        <tr>
                            <td>
                                <strong class="text-white"
                                    style="font-size:0.82rem;">{{ $mapping->category->name ?? '—' }}</strong>
                            </td>
                            <td>
                                @if ($mapping->store)
                                    <span class="channel-badge channel-{{ $mapping->store->channel->code ?? '' }}"
                                        style="font-size:0.65rem;">
                                        @if (($mapping->store->channel->code ?? '') === 'shopee')
                                            <i class="fab fa-shopify"></i>
                                        @elseif(($mapping->store->channel->code ?? '') === 'tiktok')
                                            <i class="fab fa-tiktok"></i>
                                        @endif
                                        {{ $mapping->store->store_name }}
                                    </span>
                                @endif
                            </td>
                            <td style="font-size:0.78rem;">{{ $mapping->marketplace_category_name ?? '—' }}</td>
                            <td>
                                <code class="text-muted font-monospace"
                                    style="font-size:0.72rem;">{{ $mapping->marketplace_category_id }}</code>
                            </td>
                            <td class="text-center">
                                <form action="{{ route('products.mappings.category.destroy', $mapping->id) }}"
                                    method="POST" class="confirm-delete d-inline"
                                    data-message="Hapus pemetaan kategori ini?">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-action-sm" title="Hapus Pemetaan">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
