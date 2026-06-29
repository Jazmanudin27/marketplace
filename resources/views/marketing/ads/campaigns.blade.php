@extends('layouts.app')

@section('title', 'Atur Target Campaign Iklan')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('marketing.ads.index') }}" class="btn btn-sm btn-outline-secondary mb-2">
                <i class="bi bi-arrow-left"></i> Kembali ke Dashboard Iklan
            </a>
            <h1 class="h3 mb-1 text-dark fw-bold">Pengaturan Target Campaign</h1>
            <p class="text-muted mb-0">Kelola campaign iklan Anda, hubungkan platform Meta/Google, dan definisikan target KPI (ROAS & Omset).</p>
        </div>
    </div>

    <div class="row">
        <!-- Form Add Campaign -->
        <div class="col-lg-4 mb-4">
            <div class="card border shadow-sm">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="m-0 fw-bold text-dark"><i class="bi bi-plus-circle-fill text-primary me-2"></i> Tambah Campaign Baru</h6>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('marketing.ads.campaigns.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="name" class="form-label fw-bold text-secondary small">Nama Campaign</label>
                            <input type="text" name="name" id="name" class="form-control" placeholder="Contoh: Meta Ads Promo Hijab Syari" required>
                        </div>
                        <div class="mb-3">
                            <label for="platform" class="form-label fw-bold text-secondary small">Platform Iklan</label>
                            <select name="platform" id="platform" class="form-select" required>
                                <option value="meta">Meta Ads (Facebook & Instagram)</option>
                                <option value="google">Google Ads</option>
                                <option value="tiktok">TikTok Ads</option>
                                <option value="manual">Platform Lain (Manual Log)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="target_roas" class="form-label fw-bold text-secondary small">Target ROAS Minimum (x)</label>
                            <input type="number" name="target_roas" id="target_roas" class="form-control" placeholder="Contoh: 3.50" step="0.01" min="0.1" value="2.00" required>
                            <div class="form-text small text-muted">Sistem akan memberi peringatan jika ROAS riil berada di bawah angka target ini.</div>
                        </div>
                        <div class="mb-3">
                            <label for="target_omzet" class="form-label fw-bold text-secondary small">Target Omzet (Rp)</label>
                            <input type="number" name="target_omzet" id="target_omzet" class="form-control" placeholder="Contoh: 50000000" min="0" value="0">
                        </div>
                        <button type="submit" class="btn btn-primary w-100 fw-semibold">
                            <i class="bi bi-save me-1"></i> Simpan Campaign
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Campaign Lists Table -->
        <div class="col-lg-8">
            <div class="card border shadow-sm">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="m-0 fw-bold text-dark"><i class="bi bi-card-list text-primary me-2"></i> Daftar Campaign & Konfigurasi KPI</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nama Campaign</th>
                                    <th>Platform</th>
                                    <th>Target ROAS</th>
                                    <th>Target Omzet</th>
                                    <th>Status Iklan</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($campaigns as $camp)
                                    <tr>
                                        <td>
                                            <strong class="text-dark">{{ $camp->name }}</strong>
                                            <div class="text-muted small">ID Akun: {{ $camp->adsAccount->account_name }}</div>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $camp->adsAccount->platform === 'meta' ? 'primary' : ($camp->adsAccount->platform === 'google' ? 'danger' : 'dark') }} text-uppercase" style="font-size:0.7rem;">
                                                {{ $camp->adsAccount->platform }}
                                            </span>
                                        </td>
                                        <td><strong>{{ number_format($camp->target_roas, 2) }}x</strong></td>
                                        <td>Rp {{ number_format($camp->target_omzet, 0, ',', '.') }}</td>
                                        <td>
                                            @if($camp->status === 'ACTIVE')
                                                <span class="badge bg-success">ACTIVE</span>
                                            @else
                                                <span class="badge bg-secondary">PAUSED</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <div class="d-flex gap-1 justify-content-end">
                                                <!-- Button to open Update Targets modal -->
                                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal-{{ $camp->id }}" title="Edit Target">
                                                    <i class="bi bi-pencil-square"></i> Edit
                                                </button>
                                                <!-- Delete button -->
                                                <form action="{{ route('marketing.ads.campaigns.destroy', $camp->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus campaign ini? Log biaya iklan akan ikut terhapus.')" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>

                                            <!-- Edit Targets Modal -->
                                            <div class="modal fade text-start" id="editModal-{{ $camp->id }}" tabindex="-1" aria-labelledby="editModalLabel-{{ $camp->id }}" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="editModalLabel-{{ $camp->id }}">Update Target KPI: {{ $camp->name }}</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form action="{{ route('marketing.ads.campaigns.update', $camp->id) }}" method="POST">
                                                            @csrf
                                                            @method('PUT')
                                                            <div class="modal-body">
                                                                <div class="mb-3">
                                                                    <label for="target_roas-{{ $camp->id }}" class="form-label fw-bold text-secondary small">Target ROAS Minimum (x)</label>
                                                                    <input type="number" name="target_roas" id="target_roas-{{ $camp->id }}" class="form-control" value="{{ $camp->target_roas }}" step="0.01" min="0.1" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="target_omzet-{{ $camp->id }}" class="form-label fw-bold text-secondary small">Target Omzet (Rp)</label>
                                                                    <input type="number" name="target_omzet" id="target_omzet-{{ $camp->id }}" class="form-control" value="{{ (int)$camp->target_omzet }}" min="0" required>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            Belum ada Campaign iklan terdaftar. Silakan buat campaign baru melalui form di samping.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
