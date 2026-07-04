@extends('layouts.app')
@section('title', 'Buat Diskon Bertingkat Baru')
@section('page-title', 'Buat Diskon Bertingkat')

@section('topbar-actions')
    <a href="{{ route('marketing.tiered_discounts.index') }}" class="btn btn-sm btn-light text-primary fw-bold px-3">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-lg-9">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent py-3 px-4 border-bottom">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-layers-fill text-primary me-2"></i>Form Buat Diskon Bertingkat (Grosir / Kuantitas)</h5>
                </div>

                <div class="card-body p-4">
                    <form action="{{ route('marketing.tiered_discounts.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="name" class="form-label fw-bold text-secondary small text-uppercase" style="letter-spacing:.5px;font-size:.7rem;">Nama Aturan Promo <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control rounded-3" placeholder="Contoh: Diskon Grosir Kaos Polos / Promo Qty Kaos Ramadhan" required>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-12 col-md-6">
                                <label for="master_product_id" class="form-label fw-bold text-secondary small text-uppercase" style="letter-spacing:.5px;font-size:.7rem;">Produk Target</label>
                                <select name="master_product_id" id="master_product_id" class="form-select rounded-3">
                                    <option value="">🌐 Semua Produk (Global Promo)</option>
                                    @foreach($products as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }} (SKU: {{ $p->sku }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="notes" class="form-label fw-bold text-secondary small text-uppercase" style="letter-spacing:.5px;font-size:.7rem;">Catatan (Opsional)</label>
                                <input type="text" name="notes" id="notes" class="form-control rounded-3" placeholder="Syarat & ketentuan internal">
                            </div>
                        </div>

                        {{-- Tier Repeater Form --}}
                        <div class="card border mb-4">
                            <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                                <h6 class="fw-bold text-dark mb-0 small"><i class="bi bi-list-nested me-1"></i>Pengaturan Tier Diskon (Berdasarkan Jumlah Pcs)</h6>
                                <button type="button" class="btn btn-sm btn-outline-primary fw-bold" id="add-tier-btn">
                                    <i class="bi bi-plus-lg me-1"></i> Tambah Tier
                                </button>
                            </div>
                            <div class="card-body p-3">
                                <div id="tier-rows-container">
                                    {{-- Initial Tier Row 1 --}}
                                    <div class="row g-2 align-items-center mb-2 tier-row">
                                        <div class="col-3">
                                            <label class="form-label small mb-1 text-muted">Min Qty (Pcs)</label>
                                            <input type="number" name="tiers[0][min_qty]" class="form-control form-control-sm" value="3" min="1" required>
                                        </div>
                                        <div class="col-3">
                                            <label class="form-label small mb-1 text-muted">Max Qty (Kosong = ∞)</label>
                                            <input type="number" name="tiers[0][max_qty]" class="form-control form-control-sm" value="5" min="1">
                                        </div>
                                        <div class="col-3">
                                            <label class="form-label small mb-1 text-muted">Tipe Diskon</label>
                                            <select name="tiers[0][discount_type]" class="form-select form-select-sm">
                                                <option value="percentage">Persentase (%)</option>
                                                <option value="fixed_amount">Nominal Flat (Rp)</option>
                                            </select>
                                        </div>
                                        <div class="col-2">
                                            <label class="form-label small mb-1 text-muted">Nilai Diskon</label>
                                            <input type="number" name="tiers[0][discount_value]" class="form-control form-control-sm" value="5" step="0.5" min="0.1" required>
                                        </div>
                                        <div class="col-1 text-end pt-4">
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-tier-btn" disabled><i class="bi bi-x-lg"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 border-top pt-3">
                            <a href="{{ route('marketing.tiered_discounts.index') }}" class="btn btn-light rounded-3 px-4">Batal</a>
                            <button type="submit" class="btn btn-primary rounded-3 px-4 fw-bold">
                                <i class="bi bi-save2 me-1"></i> Simpan Aturan Diskon
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        let tierIndex = 1;
        const container = document.getElementById('tier-rows-container');
        const addBtn = document.getElementById('add-tier-btn');

        addBtn.addEventListener('click', function () {
            const row = document.createElement('div');
            row.className = 'row g-2 align-items-center mb-2 tier-row';
            row.innerHTML = `
                <div class="col-3">
                    <input type="number" name="tiers[${tierIndex}][min_qty]" class="form-control form-control-sm" placeholder="Min Qty" min="1" required>
                </div>
                <div class="col-3">
                    <input type="number" name="tiers[${tierIndex}][max_qty]" class="form-control form-control-sm" placeholder="Max Qty (opsional)" min="1">
                </div>
                <div class="col-3">
                    <select name="tiers[${tierIndex}][discount_type]" class="form-select form-select-sm">
                        <option value="percentage">Persentase (%)</option>
                        <option value="fixed_amount">Nominal Flat (Rp)</option>
                    </select>
                </div>
                <div class="col-2">
                    <input type="number" name="tiers[${tierIndex}][discount_value]" class="form-control form-control-sm" placeholder="Nilai" step="0.5" min="0.1" required>
                </div>
                <div class="col-1 text-end">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-tier-btn"><i class="bi bi-x-lg"></i></button>
                </div>
            `;
            container.appendChild(row);
            tierIndex++;
            updateRemoveButtons();
        });

        container.addEventListener('click', function (e) {
            if (e.target.closest('.remove-tier-btn')) {
                const row = e.target.closest('.tier-row');
                if (container.querySelectorAll('.tier-row').length > 1) {
                    row.remove();
                    updateRemoveButtons();
                }
            }
        });

        function updateRemoveButtons() {
            const rows = container.querySelectorAll('.tier-row');
            rows.forEach(r => {
                const btn = r.querySelector('.remove-tier-btn');
                btn.disabled = rows.length <= 1;
            });
        }
    });
    </script>
    @endpush
@endsection
