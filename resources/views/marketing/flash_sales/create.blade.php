@extends('layouts.app')
@section('title', 'Buat Event Flash Sale Baru')
@section('page-title', 'Buat Flash Sale Baru')

@section('topbar-actions')
    <a href="{{ route('marketing.flash_sales.index') }}" class="btn btn-sm btn-light text-primary fw-bold px-3">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
    </a>
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent py-3 px-4 border-bottom">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-lightning-charge-fill text-danger me-2"></i>Form Penjadwalan Event Flash Sale</h5>
                </div>

                <div class="card-body p-4">
                    <form action="{{ route('marketing.flash_sales.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="title" class="form-label fw-bold text-secondary small text-uppercase" style="letter-spacing:.5px;font-size:.7rem;">Judul Event Flash Sale <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="title" class="form-control rounded-3 @error('title') is-invalid @enderror" placeholder="Contoh: Flash Sale Midnight 12.12 / Promo Tanggal Kembar 7.7" value="{{ old('title') }}" required>
                            @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6">
                                <label for="store_id" class="form-label fw-bold text-secondary small text-uppercase" style="letter-spacing:.5px;font-size:.7rem;">Toko Target</label>
                                <select name="store_id" id="store_id" class="form-select rounded-3">
                                    <option value="">🌐 Semua Toko (Global Promo)</option>
                                    @foreach($stores as $s)
                                        <option value="{{ $s->id }}" {{ old('store_id') == $s->id ? 'selected' : '' }}>
                                            {{ $s->name }} ({{ strtoupper($s->channel->name ?? '') }})
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text text-muted">Biarkan kosong jika berlaku di seluruh kanal toko Anda.</div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="status" class="form-label fw-bold text-secondary small text-uppercase" style="letter-spacing:.5px;font-size:.7rem;">Status Awal <span class="text-danger">*</span></label>
                                <select name="status" id="status" class="form-select rounded-3" required>
                                    <option value="ACTIVE" selected>⚡ Aktif / Otomatis Sesuai Jam</option>
                                    <option value="DRAFT">📝 Draft (Belum Dipublikasikan)</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-12 col-md-6">
                                <label for="start_time" class="form-label fw-bold text-secondary small text-uppercase" style="letter-spacing:.5px;font-size:.7rem;">Waktu Mulai <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="start_time" id="start_time" class="form-control rounded-3 @error('start_time') is-invalid @enderror" value="{{ old('start_time', \Carbon\Carbon::now()->format('Y-m-d\TH:i')) }}" required>
                                @error('start_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="end_time" class="form-label fw-bold text-secondary small text-uppercase" style="letter-spacing:.5px;font-size:.7rem;">Waktu Selesai <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="end_time" id="end_time" class="form-control rounded-3 @error('end_time') is-invalid @enderror" value="{{ old('end_time', \Carbon\Carbon::now()->addHours(3)->format('Y-m-d\TH:i')) }}" required>
                                @error('end_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="notes" class="form-label fw-bold text-secondary small text-uppercase" style="letter-spacing:.5px;font-size:.7rem;">Catatan Internal (Opsional)</label>
                            <textarea name="notes" id="notes" rows="3" class="form-control rounded-3" placeholder="Tambahkan catatan khusus seperti syarat & ketentuan promo internal">{{ old('notes') }}</textarea>
                        </div>

                        <div class="d-flex justify-content-end gap-2 border-top pt-3">
                            <a href="{{ route('marketing.flash_sales.index') }}" class="btn btn-light rounded-3 px-4">Batal</a>
                            <button type="submit" class="btn btn-primary rounded-3 px-4 fw-bold">
                                <i class="bi bi-arrow-right-circle me-1"></i> Simpan & Lanjut Tambah Produk
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
