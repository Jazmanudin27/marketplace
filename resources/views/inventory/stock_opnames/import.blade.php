@extends('layouts.app')
@section('title', 'Import Stok Opname Gudang Jadi')
@section('page-title', 'Import Stok Opname Gudang Jadi')

@section('content')

    <div class="row justify-content-center">
        <div class="col-12 col-lg-9 col-xl-8">

            {{-- Navigation / Back --}}
            <div class="d-flex justify-content-between align-items-center mb-4">
                <a href="{{ route('stock_opnames.index') }}" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                    <i class="fas fa-arrow-left"></i> Kembali ke Riwayat Opname
                </a>
                <a href="{{ route('stock_opnames.import.template') }}" class="btn btn-outline-success btn-sm d-inline-flex align-items-center gap-1">
                    <i class="fas fa-download me-1"></i> Download Template CSV
                </a>
            </div>

            {{-- Success / Error Alerts --}}
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 mb-4" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('import_errors') && count(session('import_errors')) > 0)
                <div class="alert alert-warning shadow-sm border-0 mb-4">
                    <div class="fw-bold mb-2"><i class="fas fa-exclamation-circle me-1"></i> Catatan Saat Import:</div>
                    <ul class="mb-0 small ps-3">
                        @foreach(session('import_errors') as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Main Form Card --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent py-3 border-bottom">
                    <h5 class="mb-0 fw-bold d-flex align-items-center gap-2">
                        <i class="fas fa-file-import text-primary"></i> Upload Berkas Stok Opname
                    </h5>
                </div>
                <div class="card-body p-4">

                    {{-- Format Info Box --}}
                    <div class="alert alert-primary border-0 bg-primary-subtle text-dark p-3 rounded-3 mb-4">
                        <div class="d-flex gap-3">
                            <div class="fs-3 text-primary"><i class="fas fa-info-circle"></i></div>
                            <div>
                                <h6 class="fw-bold mb-1">Ketentuan Format File Import:</h6>
                                <ul class="mb-0 small ps-3">
                                    <li>Format berkas yang didukung: <strong>CSV</strong> (`.csv`) atau <strong>Teks</strong> (`.txt`).</li>
                                    <li>Format kolom minimal: <strong>SKU</strong> dan <strong>Jumlah</strong> (stok fisik hasil opname).</li>
                                    <li>Baris pertama berisi nama kolom header (misal: <code>SKU,Jumlah</code>).</li>
                                    <li>Kolom <strong>Jumlah</strong> diisi angka fisik akhir hasil opname di gudang. Sistem akan otomatis menghitung selisih dengan stok yang ada.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('stock_opnames.import.store') }}" method="POST" enctype="multipart/form-data" id="importOpnameForm">
                        @csrf

                        <div class="row g-3 mb-4">
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold text-secondary">
                                    <i class="far fa-calendar-alt me-1"></i>Tanggal Opname <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="opname_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold text-secondary">
                                    <i class="fas fa-user me-1"></i>Petugas / PIC <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="pic" class="form-control" value="{{ Auth::user()->name }}" placeholder="Nama Petugas" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-semibold text-secondary">
                                <i class="fas fa-file-csv me-1"></i>Pilih File Berkas (.csv / .txt) <span class="text-danger">*</span>
                            </label>
                            <input type="file" name="file" class="form-control form-control-lg" accept=".csv,.txt,.xlsx,.xls" required>
                            <div class="form-text mt-2 small">
                                Belum punya format file? <a href="{{ route('stock_opnames.import.template') }}" class="fw-semibold text-decoration-none"><i class="fas fa-file-download me-1"></i>Unduh Template CSV di sini</a>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('stock_opnames.index') }}" class="btn btn-light border px-4">Batal</a>
                            <button type="submit" class="btn btn-primary fw-semibold px-4" id="btnSubmit">
                                <i class="fas fa-upload me-1"></i> Proses Import Stok Opname
                            </button>
                        </div>
                    </form>

                </div>
            </div>

            {{-- Sample Format Preview Card --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent py-3 border-bottom">
                    <h6 class="mb-0 fw-bold text-secondary">
                        <i class="fas fa-table me-1"></i> Contoh Format Berkas CSV
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0 align-middle">
                            <thead class="table-light small">
                                <tr>
                                    <th>SKU</th>
                                    <th>Jumlah</th>
                                    <th class="text-secondary fw-normal">Keterangan (Opsional)</th>
                                </tr>
                            </thead>
                            <tbody class="small font-monospace">
                                <tr>
                                    <td>PROD-001</td>
                                    <td>50</td>
                                    <td class="font-sans-serif text-muted">Stok fisik produk PROD-001 menjadi 50 pcs</td>
                                </tr>
                                <tr>
                                    <td>PROD-002</td>
                                    <td>120</td>
                                    <td class="font-sans-serif text-muted">Stok fisik produk PROD-002 menjadi 120 pcs</td>
                                </tr>
                                <tr>
                                    <td>PROD-003</td>
                                    <td>0</td>
                                    <td class="font-sans-serif text-muted">Stok fisik habis (0 pcs)</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

@endsection

@push('scripts')
<script>
    $(function() {
        $('#importOpnameForm').on('submit', function() {
            $('#btnSubmit').html('<i class="fas fa-spinner fa-spin me-1"></i> Memproses Import...').prop('disabled', true);
        });
    });
</script>
@endpush
