@extends('layouts.app')

@section('title', 'Atur Target Campaign Iklan')

@section('content')
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="d-flex flex-column gap-2 mb-4">
            <div>
                <a href="{{ route('marketing.ads.index') }}"
                    class="btn btn-sm btn-outline-secondary rounded-3 px-3 py-1.5 fw-semibold mb-2">
                    <i class="bi bi-arrow-left"></i> Kembali ke Dashboard Iklan
                </a>
                <h1 class="h3 mb-1 text-dark fw-bold">Pengaturan Target Campaign</h1>
                <p class="text-muted mb-0">Kelola campaign iklan Anda, hubungkan platform Meta/Google, dan definisikan target
                    KPI (ROAS & Omset).</p>
            </div>
        </div>

        <div class="row g-4">
            <!-- Form Add Campaign -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                    <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                        <h6 class="m-0 fw-bold text-dark"><i class="bi bi-plus-circle-fill text-primary me-2"></i> Tambah
                            Campaign Baru</h6>
                    </div>
                    <div class="card-body p-4">
                        <form action="{{ route('marketing.ads.campaigns.store') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="name" class="form-label fw-semibold text-secondary small">Nama
                                    Campaign</label>
                                <input type="text" name="name" id="name"
                                    class="form-control rounded-3 border-secondary border-opacity-25"
                                    placeholder="Contoh: Meta Ads Promo Hijab Syari" required
                                    style="padding: 0.6rem 0.75rem;">
                            </div>
                            <div class="mb-3">
                                <label for="platform" class="form-label fw-semibold text-secondary small">Platform
                                    Iklan</label>
                                <select name="platform" id="platform"
                                    class="form-select rounded-3 border-secondary border-opacity-25" required
                                    style="padding: 0.6rem 2.25rem 0.6rem 0.75rem;">
                                    <option value="meta">Meta Ads (Facebook & Instagram)</option>
                                    <option value="google">Google Ads</option>
                                    <option value="tiktok">TikTok Ads</option>
                                    <option value="manual">Platform Lain (Manual Log)</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="target_roas" class="form-label fw-semibold text-secondary small">Target ROAS
                                    Minimum (x)</label>
                                <input type="number" name="target_roas" id="target_roas"
                                    class="form-control rounded-3 border-secondary border-opacity-25"
                                    placeholder="Contoh: 3.50" step="0.01" min="0.1" value="2.00" required
                                    style="padding: 0.6rem 0.75rem;">
                                <div class="form-text small text-muted mt-1.5" style="font-size:0.75rem;">Sistem akan
                                    memberi peringatan jika ROAS riil berada di bawah angka target ini.</div>
                            </div>
                            <div class="mb-4">
                                <label for="target_omzet" class="form-label fw-semibold text-secondary small">Target Omzet
                                    (Rp)</label>
                                <input type="number" name="target_omzet" id="target_omzet"
                                    class="form-control rounded-3 border-secondary border-opacity-25"
                                    placeholder="Contoh: 50000000" min="0" value="0"
                                    style="padding: 0.6rem 0.75rem;">
                            </div>
                            <button type="submit" class="btn btn-primary w-100 rounded-3 py-2.5 fw-semibold shadow-sm">
                                <i class="bi bi-save me-1"></i> Simpan Campaign
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Campaign Lists Table -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                    <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                        <h6 class="m-0 fw-bold text-dark"><i class="bi bi-card-list text-primary me-2"></i> Daftar Campaign
                            & Konfigurasi KPI</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-uppercase fs-7 text-muted"
                                    style="letter-spacing: 0.5px; font-size: 0.75rem;">
                                    <tr>
                                        <th class="border-0 px-3 py-3">Nama Campaign</th>
                                        <th class="border-0 px-3 py-3">Platform</th>
                                        <th class="border-0 px-3 py-3">Target ROAS</th>
                                        <th class="border-0 px-3 py-3">Target Omzet</th>
                                        <th class="border-0 px-3 py-3">Status Iklan</th>
                                        <th class="border-0 px-3 py-3 text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($campaigns as $camp)
                                        <tr class="border-bottom border-light">
                                            <td class="px-3 py-3">
                                                <strong class="text-dark">{{ $camp->name }}</strong>
                                                <div class="text-muted small" style="font-size:0.75rem;">ID Akun:
                                                    {{ $camp->adsAccount->account_name }}</div>
                                            </td>
                                            <td class="px-3 py-3">
                                                @php
                                                    $pf = $camp->adsAccount->platform;
                                                    $pfBadge = 'bg-secondary';
                                                    if ($pf === 'meta') {
                                                        $pfBadge = 'bg-primary';
                                                    } elseif ($pf === 'google') {
                                                        $pfBadge = 'bg-danger';
                                                    } elseif ($pf === 'tiktok') {
                                                        $pfBadge = 'bg-dark';
                                                    }
                                                @endphp
                                                <span class="badge {{ $pfBadge }} text-uppercase px-2.5 py-1 rounded"
                                                    style="font-size:0.65rem;">
                                                    {{ $pf }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-3">
                                                <strong>{{ number_format($camp->target_roas, 2) }}x</strong>
                                            </td>
                                            <td class="px-3 py-3">Rp {{ number_format($camp->target_omzet, 0, ',', '.') }}
                                            </td>
                                            <td class="px-3 py-3">
                                                @if ($camp->status === 'ACTIVE')
                                                    <span
                                                        class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2.5 py-1 rounded">ACTIVE</span>
                                                @else
                                                    <span
                                                        class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2.5 py-1 rounded">PAUSED</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-3 text-end">
                                                <div class="d-flex gap-2 justify-content-end">
                                                    <!-- Button to open Update Targets modal -->
                                                    <button
                                                        class="btn btn-sm btn-outline-primary rounded-3 px-2.5 py-1.5 fw-semibold"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#editModal-{{ $camp->id }}" title="Edit Target"
                                                        style="font-size: 0.8rem;">
                                                        <i class="bi bi-pencil-square"></i> Edit
                                                    </button>
                                                    <!-- Delete button -->
                                                    <form
                                                        action="{{ route('marketing.ads.campaigns.destroy', $camp->id) }}"
                                                        method="POST"
                                                        onsubmit="return confirm('Apakah Anda yakin ingin menghapus campaign ini? Log biaya iklan akan ikut terhapus.')"
                                                        class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                            class="btn btn-sm btn-outline-danger rounded-3 px-2.5 py-1.5 fw-semibold"
                                                            title="Hapus" style="font-size: 0.8rem;">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>

                                                <!-- Edit Targets Modal -->
                                                <div class="modal fade text-start" id="editModal-{{ $camp->id }}"
                                                    tabindex="-1" aria-labelledby="editModalLabel-{{ $camp->id }}"
                                                    aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content border-0 shadow rounded-4">
                                                            <div class="modal-header border-bottom border-light px-4 py-3">
                                                                <h5 class="modal-title fw-bold text-dark"
                                                                    id="editModalLabel-{{ $camp->id }}">Update Target
                                                                    KPI: {{ $camp->name }}</h5>
                                                                <button type="button" class="btn-close"
                                                                    data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <form
                                                                action="{{ route('marketing.ads.campaigns.update', $camp->id) }}"
                                                                method="POST">
                                                                @csrf
                                                                @method('PUT')
                                                                <div class="modal-body p-4">
                                                                    <div class="mb-3">
                                                                        <label for="target_roas-{{ $camp->id }}"
                                                                            class="form-label fw-semibold text-secondary small">Target
                                                                            ROAS Minimum (x)</label>
                                                                        <input type="number" name="target_roas"
                                                                            id="target_roas-{{ $camp->id }}"
                                                                            class="form-control rounded-3 border-secondary border-opacity-25"
                                                                            value="{{ $camp->target_roas }}"
                                                                            step="0.01" min="0.1" required
                                                                            style="padding: 0.6rem 0.75rem;">
                                                                    </div>
                                                                    <div class="mb-0">
                                                                        <label for="target_omzet-{{ $camp->id }}"
                                                                            class="form-label fw-semibold text-secondary small">Target
                                                                            Omzet (Rp)</label>
                                                                        <input type="number" name="target_omzet"
                                                                            id="target_omzet-{{ $camp->id }}"
                                                                            class="form-control rounded-3 border-secondary border-opacity-25"
                                                                            value="{{ (int) $camp->target_omzet }}"
                                                                            min="0" required
                                                                            style="padding: 0.6rem 0.75rem;">
                                                                    </div>
                                                                </div>
                                                                <div
                                                                    class="modal-footer border-top border-light px-4 py-3">
                                                                    <button type="button"
                                                                        class="btn btn-light rounded-3 fw-semibold px-3 py-2"
                                                                        data-bs-dismiss="modal">Batal</button>
                                                                    <button type="submit"
                                                                        class="btn btn-primary rounded-3 fw-semibold px-4 py-2">Simpan
                                                                        Perubahan</button>
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
                                                Belum ada Campaign iklan terdaftar. Silakan buat campaign baru melalui form
                                                di samping.
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
