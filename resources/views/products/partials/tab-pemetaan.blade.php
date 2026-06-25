<div class="card border shadow-sm">
    <div class="card-header bg-info bg-opacity-10 py-2 px-3 border-bottom">
        <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-sitemap me-2 text-info"></i>Pemetaan Kategori</h6>
        <small class="text-muted d-block">
            Pemetaan kategori lokal ke kategori marketplace. Aktifkan "Simpan Pemetaan" saat publish
            agar sistem otomatis memilih kategori yang tepat.
        </small>
    </div>

    <div class="card-body p-3">
        @if ($categoryMappings->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="fas fa-map-marked-alt d-block mb-2 opacity-25 fs-2"></i>
                Belum ada pemetaan kategori.
            </div>
        @else
            <div class="table-responsive rounded border mt-2">
                <table class="table table-sm table-striped table-bordered align-middle mb-0">
                    <thead>
                        <tr>
                            <th>KATEGORI LOKAL</th>
                            <th>TOKO</th>
                            <th>KATEGORI MARKETPLACE</th>
                            <th>ID MARKETPLACE</th>
                            <th class="text-center">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($categoryMappings as $mapping)
                            <tr>
                                <td class="fw-bold text-dark">
                                    {{ $mapping->category->name ?? '—' }}
                                </td>
                                <td>
                                    @if ($mapping->store)
                                        @php
                                            $badgeClass = 'bg-secondary';
                                            if ($mapping->store->channel->code === 'shopee') {
                                                $badgeClass = 'bg-danger text-white';
                                            } elseif ($mapping->store->channel->code === 'tiktok') {
                                                $badgeClass = 'bg-dark text-white';
                                            }
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">
                                            @if ($mapping->store->channel->code === 'shopee')
                                                <i class="fab fa-shopify me-1"></i>
                                            @elseif (($mapping->store->channel->code ?? '') === 'tiktok')
                                                <i class="fab fa-tiktok me-1"></i>
                                            @endif
                                            {{ $mapping->store->store_name }}
                                        </span>
                                    @endif
                                </td>
                                <td>{{ $mapping->marketplace_category_name ?? '—' }}</td>
                                <td>
                                    <code class="text-secondary font-monospace">{{ $mapping->marketplace_category_id }}</code>
                                </td>
                                <td class="text-center">
                                    <form action="{{ route('products.mappings.category.destroy', $mapping->id) }}"
                                        method="POST" class="confirm-delete d-inline"
                                        data-message="Hapus pemetaan kategori ini?">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" title="Hapus Pemetaan">
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
</div>
