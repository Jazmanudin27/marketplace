@extends('layouts.app')

@section('title', 'Target & Tim Marketing')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1 text-dark">
                <i class="bi bi-people-fill text-primary me-2"></i>Target & Tim Marketing
            </h4>
            <p class="text-muted small mb-0">
                Pengaturan alokasi toko, target penjualan (Qty/Omset), dan nilai insentif rupiah per-Qty per Tim Marketing.
            </p>
        </div>
        <div>
            <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#createTeamModal">
                <i class="bi bi-plus-circle me-1"></i> Tambah Tim Marketing
            </button>
        </div>
    </div>

    <!-- Alert Flash -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm border-0 mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm border-0 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <strong>Terjadi kesalahan:</strong>
            <ul class="mb-0 mt-1 small">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Metric KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100 p-3">
                <div class="d-flex align-items-center">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-3 me-3">
                        <i class="bi bi-diagram-3 fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold">Total Tim Marketing</div>
                        <div class="fs-4 fw-bold text-dark">{{ number_format($totalTeams) }}</div>
                        <div class="text-success small fw-medium" style="font-size: 0.78rem;">
                            <i class="bi bi-check-circle me-1"></i>{{ number_format($activeTeams) }} Tim Aktif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100 p-3">
                <div class="d-flex align-items-center">
                    <div class="bg-info bg-opacity-10 text-info rounded-3 p-3 me-3">
                        <i class="bi bi-shop fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold">Toko Terhubung</div>
                        <div class="fs-4 fw-bold text-dark">{{ number_format($totalStoresLinked) }} Toko</div>
                        <div class="text-muted small" style="font-size: 0.78rem;">
                            Dari total {{ $stores->count() }} Toko ERP
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100 p-3">
                <div class="d-flex align-items-center">
                    <div class="bg-success bg-opacity-10 text-success rounded-3 p-3 me-3">
                        <i class="bi bi-bullseye fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold">Target Qty (Aktif)</div>
                        <div class="fs-4 fw-bold text-dark">{{ number_format($totalTargetQty) }} Qty</div>
                        <div class="text-success small fw-medium" style="font-size: 0.78rem;">
                            Realisasi: <strong>{{ number_format($totalActualQty) }} Qty</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100 p-3">
                <div class="d-flex align-items-center">
                    <div class="bg-warning bg-opacity-10 text-warning rounded-3 p-3 me-3">
                        <i class="bi bi-cash-coin fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold">Total Insentif Realisasi</div>
                        <div class="fs-4 fw-bold text-success">Rp {{ number_format($totalEarnedReward, 0, ',', '.') }}</div>
                        <div class="text-muted small" style="font-size: 0.78rem;">
                            Berdasarkan (Actual Qty × Rp/Qty)
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
            <h6 class="fw-bold mb-0 text-dark">
                <i class="bi bi-list-stars me-1 text-primary"></i> Daftar Tim Marketing & Target
            </h6>
            <form action="{{ route('marketing.teams.index') }}" method="GET" class="d-flex gap-2">
                <div class="input-group input-group-sm" style="max-width: 250px;">
                    <input type="text" name="search" class="form-control" placeholder="Cari nama tim..." value="{{ request('search') }}">
                    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-secondary small text-uppercase fw-bold">
                    <tr>
                        <th class="ps-3" style="width: 50px;">No</th>
                        <th>Nama Tim & Keterangan</th>
                        <th>Toko Terhubung</th>
                        <th class="text-end">Target Qty</th>
                        <th class="text-end">Rupiah per Qty</th>
                        <th class="text-end">Est. Total Insentif</th>
                        <th class="text-center" style="width: 140px;">Progres Target</th>
                        <th class="text-center" style="width: 100px;">Status</th>
                        <th class="text-end pe-3" style="width: 130px;">Aksi</th>
                    </tr>
                </thead>
                <tbody class="border-top-0">
                    @forelse($teams as $index => $team)
                        <tr>
                            <td class="ps-3 fw-semibold text-muted">{{ $index + 1 }}</td>
                            <td>
                                <div class="fw-bold text-dark fs-6">{{ $team->name }}</div>
                                @if($team->description)
                                    <div class="text-muted small text-truncate" style="max-width: 220px;" title="{{ $team->description }}">
                                        {{ $team->description }}
                                    </div>
                                @endif
                                <div class="small text-muted mt-0.5">
                                    <i class="bi bi-calendar3 me-1"></i>Periode: 
                                    <strong>{{ $team->period_month ? date('F', mktime(0, 0, 0, $team->period_month, 1)) : 'Semua' }} {{ $team->period_year ?? '' }}</strong>
                                </div>
                            </td>
                            <td>
                                @if($team->stores->isNotEmpty())
                                    <div class="d-flex flex-wrap gap-1" style="max-width: 280px;">
                                        @foreach($team->stores as $store)
                                            @php
                                                $chName = strtolower($store->channel->name ?? 'marketplace');
                                                $badgeBg = 'bg-secondary';
                                                if (str_contains($chName, 'shopee')) $badgeBg = 'bg-danger';
                                                elseif (str_contains($chName, 'tiktok')) $badgeBg = 'bg-dark';
                                                elseif (str_contains($chName, 'tokopedia')) $badgeBg = 'bg-success';
                                                elseif (str_contains($chName, 'lazada')) $badgeBg = 'bg-primary';
                                            @endphp
                                            <span class="badge {{ $badgeBg }} bg-opacity-10 text-dark border border-secondary border-opacity-25 rounded-pill px-2 py-1 small" style="font-size: 0.72rem;">
                                                <i class="bi bi-shop me-1 text-secondary"></i>{{ $store->store_name }}
                                                @if($store->channel)
                                                    <small class="fw-normal text-muted">({{ $store->channel->name }})</small>
                                                @endif
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-muted small italic"><i class="bi bi-exclamation-circle me-1"></i>Belum ada toko</span>
                                @endif
                            </td>
                            <td class="text-end fw-bold">
                                <span class="badge bg-light text-dark border px-2 py-1 fs-6">
                                    {{ number_format($team->target_qty) }} Qty
                                </span>
                            </td>
                            <td class="text-end fw-semibold text-primary">
                                Rp {{ number_format($team->reward_per_qty, 0, ',', '.') }} <span class="text-muted small fw-normal">/ Qty</span>
                            </td>
                            <td class="text-end fw-bold text-success">
                                Rp {{ number_format($team->total_reward, 0, ',', '.') }}
                                <div class="text-muted small fw-normal" style="font-size: 0.7rem;">
                                    ({{ number_format($team->actual_qty) }} Qty × Rp {{ number_format($team->reward_per_qty, 0, ',', '.') }})
                                </div>
                            </td>
                            <td>
                                @php
                                    $percent = $team->qty_progress_percent;
                                    $progressBg = 'bg-danger';
                                    if ($percent >= 100) $progressBg = 'bg-success';
                                    elseif ($percent >= 50) $progressBg = 'bg-warning';
                                @endphp
                                <div class="d-flex justify-content-between align-items-center mb-1 small">
                                    <span class="fw-semibold">{{ number_format($team->actual_qty) }} / {{ number_format($team->target_qty) }}</span>
                                    <span class="fw-bold text-dark">{{ $percent }}%</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar {{ $progressBg }}" role="progressbar" style="width: {{ $percent }}%;" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </td>
                            <td class="text-center">
                                @if($team->is_active)
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-1">
                                        <i class="bi bi-dot me-1"></i>Aktif
                                    </span>
                                @else
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill px-2.5 py-1">
                                        <i class="bi bi-dot me-1"></i>Nonaktif
                                    </span>
                                @endif
                            </td>
                            <td class="text-end pe-3">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editTeamModal{{ $team->id }}" title="Edit Tim & Target">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <form action="{{ route('marketing.teams.toggle_status', $team->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-{{ $team->is_active ? 'warning' : 'success' }}" title="{{ $team->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                            <i class="bi bi-power"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('marketing.teams.destroy', $team->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus Tim {{ $team->name }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" title="Hapus Tim">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        <!-- Modal Edit Tim -->
                        <div class="modal fade" id="editTeamModal{{ $team->id }}" tabindex="-1" aria-labelledby="editTeamModalLabel{{ $team->id }}" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                <div class="modal-content border-0 shadow">
                                    <form action="{{ route('marketing.teams.update', $team->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-header bg-primary text-white py-3">
                                            <h5 class="modal-title fw-bold" id="editTeamModalLabel{{ $team->id }}">
                                                <i class="bi bi-pencil-square me-2"></i>Edit Tim Marketing: {{ $team->name }}
                                            </h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body p-4">
                                            <div class="row g-3">
                                                <div class="col-12 col-md-6">
                                                    <label class="form-label fw-semibold small">Nama Tim Marketing <span class="text-danger">*</span></label>
                                                    <input type="text" name="name" class="form-control form-control-sm" value="{{ old('name', $team->name) }}" placeholder="Contoh: Tim Ruang, Tim Nusantara" required>
                                                </div>
                                                <div class="col-12 col-md-6">
                                                    <label class="form-label fw-semibold small">Periode Target (Bulan & Tahun)</label>
                                                    <div class="row g-2">
                                                        <div class="col-7">
                                                            <select name="period_month" class="form-select form-select-sm">
                                                                @for($m = 1; $m <= 12; $m++)
                                                                    <option value="{{ $m }}" {{ old('period_month', $team->period_month) == $m ? 'selected' : '' }}>
                                                                        {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                                                    </option>
                                                                @endfor
                                                            </select>
                                                        </div>
                                                        <div class="col-5">
                                                            <input type="number" name="period_year" class="form-control form-control-sm" value="{{ old('period_year', $team->period_year ?? date('Y')) }}" placeholder="Tahun">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-12 col-md-6">
                                                    <label class="form-label fw-semibold small">Target Qty (Jumlah Pesanan/Pcs) <span class="text-danger">*</span></label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" name="target_qty" class="form-control" value="{{ old('target_qty', $team->target_qty) }}" min="0" required placeholder="1000">
                                                        <span class="input-group-text bg-light text-muted">Qty</span>
                                                    </div>
                                                    <div class="form-text small text-muted">Contoh: 1000 Qty per periode</div>
                                                </div>

                                                <div class="col-12 col-md-6">
                                                    <label class="form-label fw-semibold small">Rupiah per Qty (Insentif/Komisi) <span class="text-danger">*</span></label>
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text bg-light text-muted">Rp</span>
                                                        <input type="number" name="reward_per_qty" class="form-control" value="{{ old('reward_per_qty', $team->reward_per_qty) }}" min="0" required placeholder="1000">
                                                        <span class="input-group-text bg-light text-muted">/ Qty</span>
                                                    </div>
                                                    <div class="form-text small text-muted">Contoh: 1000 (Artinya Rp 1.000 per Qty)</div>
                                                </div>

                                                <div class="col-12">
                                                    <label class="form-label fw-semibold small">Pilih Toko Terhubung (Channel Marketplace)</label>
                                                    <div class="card border rounded-3 p-3 bg-light" style="max-height: 200px; overflow-y: auto;">
                                                        <div class="row g-2">
                                                            @php $selectedStoreIds = $team->stores->pluck('id')->toArray(); @endphp
                                                            @forelse($stores as $st)
                                                                <div class="col-12 col-md-6">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox" name="store_ids[]" value="{{ $st->id }}" id="edit_st_{{ $team->id }}_{{ $st->id }}" {{ in_array($st->id, $selectedStoreIds) ? 'checked' : '' }}>
                                                                        <label class="form-check-label small" for="edit_st_{{ $team->id }}_{{ $st->id }}">
                                                                            <strong>{{ $st->store_name }}</strong>
                                                                            @if($st->channel)
                                                                                <span class="text-muted">({{ $st->channel->name }})</span>
                                                                            @endif
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            @empty
                                                                <div class="col-12 text-muted small italic">Tidak ada data toko marketplace.</div>
                                                            @endforelse
                                                        </div>
                                                    </div>
                                                    <div class="form-text small text-muted mt-1">Centang toko-toko yang dialokasikan ke tim ini (misal: Ruang Seragam Tiktok, Ruang Seragam Shopee).</div>
                                                </div>

                                                <div class="col-12">
                                                    <label class="form-label fw-semibold small">Catatan / Keterangan (Opsional)</label>
                                                    <textarea name="description" class="form-control form-control-sm" rows="2" placeholder="Catatan internal tim...">{{ old('description', $team->description) }}</textarea>
                                                </div>

                                                <div class="col-12">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="edit_active_{{ $team->id }}" {{ $team->is_active ? 'checked' : '' }}>
                                                        <label class="form-check-label fw-semibold small" for="edit_active_{{ $team->id }}">Status Tim Aktif</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer bg-light">
                                            <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4">
                                                <i class="bi bi-save me-1"></i> Simpan Perubahan
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-people fs-1 d-block text-secondary opacity-50 mb-2"></i>
                                Belum ada Tim Marketing yang dibuat. Klik tombol <strong>Tambah Tim Marketing</strong> di atas untuk membuat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Create Tim Marketing -->
<div class="modal fade" id="createTeamModal" tabindex="-1" aria-labelledby="createTeamModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('marketing.teams.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white py-3">
                    <h5 class="modal-title fw-bold" id="createTeamModalLabel">
                        <i class="bi bi-plus-circle me-2"></i>Tambah Tim Marketing & Target Baru
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold small">Nama Tim Marketing <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control form-control-sm" value="{{ old('name') }}" placeholder="Contoh: Tim Ruang, Tim Nusantara" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold small">Periode Target (Bulan & Tahun)</label>
                            <div class="row g-2">
                                <div class="col-7">
                                    <select name="period_month" class="form-select form-select-sm">
                                        @for($m = 1; $m <= 12; $m++)
                                            <option value="{{ $m }}" {{ old('period_month', date('n')) == $m ? 'selected' : '' }}>
                                                {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                            </option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="col-5">
                                    <input type="number" name="period_year" class="form-control form-control-sm" value="{{ old('period_year', date('Y')) }}" placeholder="Tahun">
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold small">Target Qty (Jumlah Pesanan/Pcs) <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <input type="number" name="target_qty" class="form-control" value="{{ old('target_qty', 1000) }}" min="0" required placeholder="1000">
                                <span class="input-group-text bg-light text-muted">Qty</span>
                            </div>
                            <div class="form-text small text-muted">Contoh: 1000 Qty per periode</div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold small">Rupiah per Qty (Insentif/Komisi) <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light text-muted">Rp</span>
                                <input type="number" name="reward_per_qty" class="form-control" value="{{ old('reward_per_qty', 1000) }}" min="0" required placeholder="1000">
                                <span class="input-group-text bg-light text-muted">/ Qty</span>
                            </div>
                            <div class="form-text small text-muted">Contoh: 1000 (Artinya Rp 1.000 per Qty)</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold small">Pilih Toko Terhubung (Channel Marketplace)</label>
                            <div class="card border rounded-3 p-3 bg-light" style="max-height: 200px; overflow-y: auto;">
                                <div class="row g-2">
                                    @forelse($stores as $st)
                                        <div class="col-12 col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="store_ids[]" value="{{ $st->id }}" id="create_st_{{ $st->id }}">
                                                <label class="form-check-label small" for="create_st_{{ $st->id }}">
                                                    <strong>{{ $st->store_name }}</strong>
                                                    @if($st->channel)
                                                        <span class="text-muted">({{ $st->channel->name }})</span>
                                                    @endif
                                                </label>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="col-12 text-muted small italic">Tidak ada data toko marketplace.</div>
                                    @endforelse
                                </div>
                            </div>
                            <div class="form-text small text-muted mt-1">Pilih toko yang dikelola oleh tim ini (misal: Ruang Seragam Tiktok, Ruang Seragam Shopee).</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold small">Catatan / Keterangan (Opsional)</label>
                            <textarea name="description" class="form-control form-control-sm" rows="2" placeholder="Catatan internal tim...">{{ old('description') }}</textarea>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="create_is_active" checked>
                                <label class="form-check-label fw-semibold small" for="create_is_active">Status Tim Aktif</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4">
                        <i class="bi bi-save me-1"></i> Simpan Tim Baru
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
