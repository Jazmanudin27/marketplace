@extends('v2.layouts.app')

@section('title', 'Penerimaan Barang Konsinyasi (Titipan Supplier) V2')

@push('styles')
    <style>
        .titipan-kpi-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 14px 16px;
            position: relative;
            overflow: hidden;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .titipan-kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .titipan-kpi-title {
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #6b7280;
            margin-bottom: 4px;
        }

        .titipan-kpi-value {
            font-size: 1.15rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .titipan-kpi-sub {
            font-size: 0.7rem;
            color: #9ca3af;
            margin-top: 4px;
        }

        .titipan-kpi-icon {
            position: absolute;
            right: 12px;
            bottom: 12px;
            font-size: 2rem;
            opacity: 0.12;
            pointer-events: none;
        }
    </style>
@endpush

@section('content')

    {{-- ── Page Header ── --}}
    <div class="v2-page-header align-items-center mb-3">
        <div>
            <h1 class="v2-page-title d-flex align-items-center gap-2 mb-0">
                <i class="bi bi-box-seam text-warning fs-5"></i> Penerimaan Barang Titipan (Konsinyasi)
            </h1>
            <p class="text-muted small mb-0">Kelola daftar transaksi penerimaan barang titipan supplier dan persediaan gudang
                master</p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="{{ route('v2.titipan_barang.settlement.create') }}"
                class="btn btn-sm btn-outline-success py-1.5 px-3 rounded-2 fw-semibold">
                <i class="bi bi-cash-stack me-1"></i> Pembayaran / Setoran
            </a>
            <a href="{{ route('v2.titipan_barang.deductions.index') }}"
                class="btn btn-sm btn-outline-primary py-1.5 px-3 rounded-2 fw-semibold">
                <i class="bi bi-clipboard-check me-1"></i> Stok Opname Titipan
            </a>
            <a href="{{ route('v2.titipan_barang.stock_card') }}"
                class="btn btn-sm btn-outline-secondary py-1.5 px-3 rounded-2 fw-semibold">
                <i class="bi bi-card-checklist me-1"></i> Kartu Stok Supplier
            </a>
            <a href="{{ route('v2.titipan_barang.create') }}"
                class="btn btn-sm py-1.5 px-3 shadow-sm fw-semibold text-white"
                style="background:#f59e0b; border:none; border-radius:6px;">
                <i class="bi bi-plus-lg me-1"></i> Transaksi Baru
            </a>
        </div>
    </div>

    {{-- ── Alert Notifications ── --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 mb-3" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 mb-3" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (!empty($errorMessage))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 mb-3" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ $errorMessage }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ── KPI Summary Cards ── --}}
    <div class="row g-2.5 mb-3">
        {{-- Total Transaksi --}}
        <div class="col-6 col-md-4 col-xl-2">
            <div class="titipan-kpi-card border-start border-warning border-3 h-100">
                <div class="titipan-kpi-title text-warning">Total Transaksi</div>
                <div class="titipan-kpi-value text-dark" style="font-size: 1.05rem;">
                    {{ number_format($consignments->total(), 0, ',', '.') }}
                </div>
                <div class="titipan-kpi-sub">Nota penerimaan</div>
                <i class="bi bi-box-seam titipan-kpi-icon text-warning"></i>
            </div>
        </div>

        {{-- Total Qty Diterima --}}
        <div class="col-6 col-md-4 col-xl-2">
            <div class="titipan-kpi-card border-start border-primary border-3 h-100">
                <div class="titipan-kpi-title text-primary">Barang Masuk</div>
                <div class="titipan-kpi-value text-primary" style="font-size: 1.05rem;">
                    {{ number_format($totalQtyTitipAll ?? $consignments->sum('total_qty_received'), 0, ',', '.') }} PCS
                </div>
                <div class="titipan-kpi-sub">Modal: Rp
                    {{ number_format($totalAmountHppAll ?? $consignments->sum('total_amount_hpp'), 0, ',', '.') }}</div>
                <i class="bi bi-boxes titipan-kpi-icon text-primary"></i>
            </div>
        </div>

        {{-- 🎯 Total Terjual (Qty) --}}
        <div class="col-6 col-md-4 col-xl-2">
            <div class="titipan-kpi-card border-start border-info border-3 h-100">
                <div class="titipan-kpi-title text-info">Total Terjual</div>
                <div class="titipan-kpi-value text-info" style="font-size: 1.05rem;">
                    {{ number_format($totalQtySoldAll ?? 0, 0, ',', '.') }} PCS
                </div>
                <div class="titipan-kpi-sub">Laku terjual ke pembeli</div>
                <i class="bi bi-bag-check titipan-kpi-icon text-info"></i>
            </div>
        </div>

        {{-- 🎯 TOTAL HARUS DISETORKAN (Rp HPP) --}}
        <div class="col-6 col-md-6 col-xl-3">
            <div class="titipan-kpi-card border-start border-danger border-3 h-100"
                style="background: linear-gradient(135deg, #fff5f5 0%, #ffffff 100%);">
                <div class="titipan-kpi-title text-danger fw-bold">
                    <i class="bi bi-cash-coin me-1"></i>TOTAL HARUS DISETORKAN
                </div>
                <div class="titipan-kpi-value text-danger font-monospace" style="font-size: 1.15rem;">
                    Rp {{ number_format($totalAmountToPay ?? 0, 0, ',', '.') }}
                </div>
                <div class="titipan-kpi-sub text-danger fw-semibold">
                    Nilai HPP barang titipan yang sudah laku
                </div>
                <i class="bi bi-wallet2 titipan-kpi-icon text-danger"></i>
            </div>
        </div>

        {{-- STATUS SETORAN SUPPLIER (Sudah vs Belum) --}}
        <div class="col-12 col-md-6 col-xl-3">
            <div class="titipan-kpi-card border-start border-success border-3 h-100"
                style="background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%);">
                <div class="titipan-kpi-title text-success fw-bold">
                    <i class="bi bi-clock-history me-1"></i>SISA BELUM DISETOR
                </div>
                <div class="titipan-kpi-value text-success font-monospace" style="font-size: 1.15rem;">
                    Rp {{ number_format($totalAmountUnsettled ?? 0, 0, ',', '.') }}
                </div>
                <div class="titipan-kpi-sub text-muted">
                    Sudah disetor: <strong>Rp {{ number_format($totalAmountSettled ?? 0, 0, ',', '.') }}</strong>
                </div>
                <i class="bi bi-check2-circle titipan-kpi-icon text-success"></i>
            </div>
        </div>
    </div>

    {{-- ── 🏢 REKAPITULASI TITIPAN & SETORAN PER SUPPLIER ── --}}
    @if (!empty($supplierSummaries) && count($supplierSummaries) > 0)
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white overflow-hidden"
            style="border: 1px solid #eef2f6 !important;">
            <div
                class="px-3.5 py-3 bg-white border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 bg-primary bg-opacity-10 text-primary p-2 d-flex align-items-center justify-content-center"
                        style="width:32px; height:32px;">
                        <i class="bi bi-building fs-6"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold text-dark" style="font-size:0.88rem; letter-spacing:-0.01em;">
                            Rekapitulasi Titipan &amp; Setoran per Supplier
                        </h6>
                        <small class="text-muted" style="font-size:0.73rem;">
                            Akumulasi total barang masuk, setoran tercatat, dan sisa kewajiban dari seluruh nota konsinyasi
                        </small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-secondary border px-2.5 py-1.5 fw-semibold rounded-pill"
                        style="font-size:0.74rem;">
                        <i class="bi bi-people me-1"></i>{{ count($supplierSummaries) }} Supplier Aktif
                    </span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.84rem;">
                    <thead class="bg-light bg-opacity-75 text-uppercase fw-bold text-secondary border-bottom"
                        style="font-size: 0.72rem; letter-spacing: 0.04em;">
                        <tr>
                            <th class="ps-3.5 py-2.5" style="width: 27%;">SUPPLIER</th>
                            <th class="text-center py-2.5" style="width: 11%;">JUMLAH NOTA</th>
                            <th class="text-end py-2.5" style="width: 17%;">TOTAL TITIPAN (RP)</th>
                            <th class="text-end py-2.5" style="width: 17%;">SUDAH DISETOR (RP)</th>
                            <th class="text-end py-2.5" style="width: 18%;">SISA WAJIB SETOR (RP)</th>
                            <th class="text-center pe-3.5 py-2.5" style="width: 10%;">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($supplierSummaries as $sRow)
                            <tr style="transition: background-color 0.15s ease;">
                                <td class="ps-3.5 py-3">
                                    <div class="d-flex align-items-center gap-2.5">
                                        <div class="rounded-circle bg-light text-primary border p-2 d-flex align-items-center justify-content-center fw-bold shadow-2xs"
                                            style="width:38px; height:38px; min-width:38px; font-size:0.9rem;">
                                            {{ strtoupper(substr($sRow['supplier_name'], 0, 1)) }}
                                        </div>
                                        <div>
                                            <span class="fw-bold text-dark d-block"
                                                style="font-size:0.9rem;">{{ $sRow['supplier_name'] }}</span>
                                            <small class="text-muted d-flex align-items-center gap-1"
                                                style="font-size:0.72rem;">
                                                <i class="bi bi-telephone text-secondary" style="font-size:0.68rem;"></i>
                                                {{ $sRow['supplier_phone'] ?: 'Tanpa Kontak' }}
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center py-3">
                                    <span
                                        class="badge bg-secondary-subtle text-secondary border rounded-pill px-2.5 py-1 fw-semibold">
                                        <i class="bi bi-receipt me-1"></i>{{ $sRow['total_invoices'] }} Nota
                                    </span>
                                </td>
                                <td class="text-end py-3">
                                    <span class="fw-bold text-dark font-monospace d-block" style="font-size:0.88rem;">
                                        Rp {{ number_format($sRow['total_titipan_rp'], 0, ',', '.') }}
                                    </span>
                                    <small class="text-muted" style="font-size:0.71rem;">
                                        Total <strong>{{ number_format($sRow['total_qty_masuk']) }}</strong> PCS Masuk
                                    </small>
                                </td>
                                <td class="text-end py-3">
                                    <span class="fw-bold text-success font-monospace d-block" style="font-size:0.88rem;">
                                        Rp {{ number_format($sRow['setor_rp'], 0, ',', '.') }}
                                    </span>
                                    <small class="text-success-emphasis" style="font-size:0.71rem;">
                                        <i class="bi bi-check2 me-0.5"></i>Telah ditransfer
                                    </small>
                                </td>
                                <td class="text-end py-3">
                                    @if ($sRow['sisa_belum_setor_rp'] > 0)
                                        <span
                                            class="badge bg-danger-subtle text-danger border border-danger-subtle font-monospace px-2.5 py-1.5 fw-bold d-inline-block shadow-2xs"
                                            style="font-size:0.86rem;">
                                            Rp {{ number_format($sRow['sisa_belum_setor_rp'], 0, ',', '.') }}
                                        </span>
                                        <small class="text-danger d-block mt-0.5 fw-semibold" style="font-size:0.7rem;">
                                            Dari {{ number_format($sRow['total_qty_laku']) }} PCS Terjual
                                        </small>
                                    @else
                                        <span
                                            class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 fw-semibold rounded-pill"
                                            style="font-size:0.74rem;">
                                            <i class="bi bi-check-circle-fill me-1 text-success"></i>Lunas / Belum Laku
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center pe-3.5 py-3">
                                    <div class="d-inline-flex align-items-center justify-content-center gap-1.5">
                                        <button type="button"
                                            class="btn btn-outline-info btn-sm py-1.5 px-2.5 rounded-2 position-relative"
                                            style="font-size:0.82rem;" data-bs-toggle="collapse"
                                            data-bs-target="#historiBayarSup{{ $sRow['supplier_id'] }}"
                                            aria-expanded="false" title="Histori Pembayaran Setoran">
                                            <i class="bi bi-clock-history"></i>
                                            @if (!empty($sRow['settlement_history']) && count($sRow['settlement_history']) > 0)
                                                <span class="badge bg-info text-white rounded-pill ms-0.5"
                                                    style="font-size:0.62rem;">{{ count($sRow['settlement_history']) }}</span>
                                            @endif
                                        </button>
                                        <button type="button"
                                            class="btn btn-success btn-sm py-1.5 px-2.5 rounded-2 shadow-2xs"
                                            style="font-size:0.82rem;" data-bs-toggle="modal"
                                            data-bs-target="#modalInputSetoran{{ $sRow['supplier_id'] }}"
                                            title="Input Setoran / Pembayaran">
                                            <i class="bi bi-cash-stack"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            {{-- ── Sub-row: Histori Pembayaran Setoran (Bisa Edit & Hapus) ── --}}
                            <tr class="collapse bg-light-subtle" id="historiBayarSup{{ $sRow['supplier_id'] }}">
                                <td colspan="6" class="p-3 bg-slate-50 border-bottom">
                                    <div
                                        class="card border border-info border-opacity-25 rounded-3 shadow-none bg-white p-3">
                                        <div
                                            class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom flex-wrap gap-2">
                                            <div class="d-flex align-items-center gap-2">
                                                <span
                                                    class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1 fw-bold rounded-pill"
                                                    style="font-size:0.72rem;">
                                                    <i class="bi bi-receipt me-1"></i>Histori Setoran
                                                </span>
                                                <span class="fw-bold text-dark small" style="font-size:0.83rem;">
                                                    Riwayat Pembayaran ke {{ $sRow['supplier_name'] }}
                                                </span>
                                                <span class="text-muted small" style="font-size:0.73rem;">
                                                    (Total Terbayar: <strong class="text-success font-monospace">Rp
                                                        {{ number_format($sRow['setor_rp'], 0, ',', '.') }}</strong>)
                                                </span>
                                            </div>
                                            <a href="{{ route('v2.titipan_barang.settlement.index', ['supplier_id' => $sRow['supplier_id']]) }}"
                                                class="btn btn-xs btn-outline-secondary py-1 px-2.5 fw-semibold"
                                                style="font-size:0.72rem;">
                                                <i class="bi bi-box-arrow-up-right me-1"></i>Semua Riwayat Setoran
                                            </a>
                                        </div>

                                        @if (!empty($sRow['settlement_history']) && count($sRow['settlement_history']) > 0)
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered align-middle mb-0 bg-white"
                                                    style="font-size: 0.79rem;">
                                                    <thead class="table-light text-secondary text-uppercase fw-semibold"
                                                        style="font-size:0.71rem;">
                                                        <tr>
                                                            <th class="ps-2.5 py-1.5" style="width: 22%;">NO. SETORAN</th>
                                                            <th class="py-1.5" style="width: 14%;">TANGGAL</th>
                                                            <th class="py-1.5" style="width: 18%;">METODE</th>
                                                            <th class="py-1.5" style="width: 18%;">CATATAN / REF</th>
                                                            <th class="text-end py-1.5" style="width: 16%;">NOMINAL (RP)
                                                            </th>
                                                            <th class="text-center pe-2.5 py-1.5" style="width: 12%;">AKSI
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($sRow['settlement_history'] as $hist)
                                                            <tr>
                                                                <td
                                                                    class="ps-2.5 py-2 font-monospace fw-bold text-primary">
                                                                    <a href="{{ route('v2.titipan_barang.settlement.show', ['settlement' => $hist->id]) }}"
                                                                        class="text-decoration-none">
                                                                        {{ $hist->settlement_number }}
                                                                    </a>
                                                                </td>
                                                                <td class="py-2 text-dark">
                                                                    <i class="bi bi-calendar-event me-1 text-muted"></i>
                                                                    {{ !empty($hist->settlement_date) ? \Carbon\Carbon::parse($hist->settlement_date)->format('d/m/Y') : '-' }}
                                                                </td>
                                                                <td class="py-2">
                                                                    <span
                                                                        class="badge {{ $hist->payment_method === 'transfer' ? 'bg-info-subtle text-info border border-info-subtle' : 'bg-success-subtle text-success border border-success-subtle' }} px-2 py-0.5"
                                                                        style="font-size: 0.68rem;">
                                                                        {{ $hist->payment_method === 'transfer' ? 'Transfer Bank' : 'Tunai / Kas' }}
                                                                    </span>
                                                                    @if ($hist->bankAccount)
                                                                        <div class="text-muted small mt-0.5"
                                                                            style="font-size: 0.69rem;">
                                                                            {{ $hist->bankAccount->bank_name }}
                                                                        </div>
                                                                    @endif
                                                                </td>
                                                                <td class="py-2 text-muted" style="font-size: 0.73rem;">
                                                                    @if ($hist->reference_number)
                                                                        <span class="font-monospace text-dark d-block">Ref:
                                                                            {{ $hist->reference_number }}</span>
                                                                    @endif
                                                                    <span>{{ $hist->notes ?: '-' }}</span>
                                                                </td>
                                                                <td class="text-end py-2 font-monospace fw-bold text-success"
                                                                    style="font-size: 0.85rem;">
                                                                    Rp
                                                                    {{ number_format($hist->total_amount_paid ?? 0, 0, ',', '.') }}
                                                                </td>
                                                                <td class="text-center pe-2.5 py-2">
                                                                    <div class="d-inline-flex align-items-center gap-1">
                                                                        <a href="{{ route('v2.titipan_barang.settlement.show', ['settlement' => $hist->id]) }}"
                                                                            class="btn btn-xs btn-outline-primary py-0.5 px-1.5 rounded"
                                                                            title="Lihat Bukti Setoran">
                                                                            <i class="bi bi-eye"></i>
                                                                        </a>
                                                                        <a href="{{ route('v2.titipan_barang.settlement.edit', ['settlement' => $hist->id]) }}"
                                                                            class="btn btn-xs btn-outline-warning py-0.5 px-1.5 rounded text-dark"
                                                                            title="Edit Pembayaran Setoran">
                                                                            <i class="bi bi-pencil-square"></i>
                                                                        </a>
                                                                        <form
                                                                            action="{{ route('v2.titipan_barang.settlement.destroy', ['settlement' => $hist->id]) }}"
                                                                            method="POST" class="d-inline"
                                                                            onsubmit="return confirm('Apakah Anda yakin ingin menghapus histori setoran {{ $hist->settlement_number }} sebesar Rp {{ number_format($hist->total_amount_paid ?? 0, 0, ',', '.') }} ini? Mutasi kas pengeluaran terkait juga akan dibatalkan.')">
                                                                            @csrf
                                                                            @method('DELETE')
                                                                            <button type="submit"
                                                                                class="btn btn-xs btn-outline-danger py-0.5 px-1.5 rounded"
                                                                                title="Hapus Bukti Setoran">
                                                                                <i class="bi bi-trash"></i>
                                                                            </button>
                                                                        </form>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <div class="text-center py-3 text-muted small">
                                                <i class="bi bi-info-circle me-1"></i>Belum ada riwayat pembayaran setoran
                                                untuk supplier ini.
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ── Modals Input Pembayaran Setoran Per Supplier ── --}}
    @if (!empty($supplierSummaries))
        @foreach ($supplierSummaries as $sRow)
            <div class="modal fade" id="modalInputSetoran{{ $sRow['supplier_id'] }}" tabindex="-1"
                aria-labelledby="modalLabelSetoran{{ $sRow['supplier_id'] }}" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow" style="border-radius: 12px; overflow: hidden;">
                        <div class="modal-header py-3 px-3.5 bg-light border-bottom">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center"
                                    style="width: 36px; height: 36px;">
                                    <i class="bi bi-cash-stack fs-5"></i>
                                </div>
                                <div>
                                    <h6 class="modal-title fw-bold text-dark mb-0"
                                        id="modalLabelSetoran{{ $sRow['supplier_id'] }}">
                                        Input Pembayaran Setoran: {{ $sRow['supplier_name'] }}
                                    </h6>
                                    <small class="text-muted" style="font-size: 0.72rem;">Catat pembayaran hasil penjualan
                                        titipan ke supplier</small>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <form action="{{ route('supplier_consignments.settlement.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="supplier_id" value="{{ $sRow['supplier_id'] }}">
                            <div class="modal-body p-3.5">
                                @if ($sRow['sisa_belum_setor_rp'] > 0)
                                    <div
                                        class="alert alert-warning py-2 px-3 mb-3 d-flex align-items-center justify-content-between small rounded-3 border-0">
                                        <span><i class="bi bi-info-circle me-1"></i> Wajib Setor (Barang Laku):</span>
                                        <strong class="font-monospace text-dark">Rp
                                            {{ number_format($sRow['sisa_belum_setor_rp'], 0, ',', '.') }}</strong>
                                    </div>
                                @endif

                                <!-- Tanggal Bayar -->
                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-dark mb-1">Tanggal Bayar <span
                                            class="text-danger">*</span></label>
                                    <input type="date" name="settlement_date" class="form-control form-control-sm"
                                        value="{{ date('Y-m-d') }}" required>
                                </div>

                                <!-- Bayar Pakai Apa -->
                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-dark mb-1">Bayar Pakai Apa <span
                                            class="text-danger">*</span></label>
                                    <select name="payment_method" class="form-select form-select-sm no-select2"
                                        onchange="toggleModalBank(this, {{ $sRow['supplier_id'] }})" required>
                                        <option value="transfer">Transfer Bank</option>
                                        <option value="cash">Tunai / Kas</option>
                                    </select>
                                </div>

                                <!-- Sumber Rekening Bank -->
                                <div class="mb-3" id="modal-bank-container-{{ $sRow['supplier_id'] }}">
                                    <label class="form-label small fw-bold text-dark mb-1">Ditransfer Melalui Rekening Bank
                                        <span class="text-danger">*</span></label>
                                    <select name="bank_account_id" id="modal-bank-select-{{ $sRow['supplier_id'] }}"
                                        class="form-select form-select-sm no-select2">
                                        <option value="">-- Pilih Rekening Bank --</option>
                                        @foreach ($bankAccounts as $bank)
                                            <option value="{{ $bank->id }}">
                                                {{ $bank->bank_name }} - {{ $bank->account_number }} (a.n
                                                {{ $bank->account_name }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Jumlah Bayar -->
                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-success mb-1">Jumlah Bayar (Rp) <span
                                            class="text-danger">*</span></label>
                                    <div class="input-group input-group-sm mb-1.5">
                                        <span
                                            class="input-group-text bg-light fw-bold text-success font-monospace">Rp</span>
                                        <input type="text" name="total_amount"
                                            id="modal-amount-{{ $sRow['supplier_id'] }}"
                                            class="form-control fw-bold text-success font-monospace" placeholder="0"
                                            value="{{ $sRow['sisa_belum_setor_rp'] > 0 ? number_format($sRow['sisa_belum_setor_rp'], 0, ',', '.') : '' }}"
                                            autocomplete="off" required oninput="formatRupiahInput(this)">
                                    </div>
                                    @if ($sRow['sisa_belum_setor_rp'] > 0 || $sRow['sisa_titipan_rp'] > 0)
                                        <div class="d-flex gap-1.5 flex-wrap">
                                            @if ($sRow['sisa_belum_setor_rp'] > 0)
                                                <button type="button"
                                                    class="btn btn-xs btn-outline-warning py-0.5 px-2 text-dark"
                                                    style="font-size:0.7rem;"
                                                    onclick="setModalAmount({{ $sRow['supplier_id'] }}, {{ $sRow['sisa_belum_setor_rp'] }})">
                                                    Isi Barang Laku: Rp
                                                    {{ number_format($sRow['sisa_belum_setor_rp'], 0, ',', '.') }}
                                                </button>
                                            @endif
                                            @if ($sRow['sisa_titipan_rp'] > 0 && $sRow['sisa_titipan_rp'] != $sRow['sisa_belum_setor_rp'])
                                                <button type="button" class="btn btn-xs btn-outline-success py-0.5 px-2"
                                                    style="font-size:0.7rem;"
                                                    onclick="setModalAmount({{ $sRow['supplier_id'] }}, {{ $sRow['sisa_titipan_rp'] }})">
                                                    Isi Semua Titipan: Rp
                                                    {{ number_format($sRow['sisa_titipan_rp'], 0, ',', '.') }}
                                                </button>
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                <!-- Catatan / Keterangan -->
                                <div class="mb-1">
                                    <label class="form-label small fw-semibold text-muted mb-1">Catatan / Keterangan
                                        (Opsional)
                                    </label>
                                    <input type="text" name="notes" class="form-control form-control-sm"
                                        placeholder="Contoh: Setoran hasil penjualan...">
                                </div>
                            </div>
                            <div class="modal-footer py-2.5 px-3.5 bg-light border-top">
                                <button type="button" class="btn btn-sm btn-secondary"
                                    data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-sm text-white fw-semibold px-3"
                                    style="background:#16a34a; border:none;">
                                    <i class="bi bi-check-circle me-1"></i> Simpan Pembayaran
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    @endif

    {{-- ── Filter Card ── --}}
    <div class="card border-0 shadow-sm rounded-3 mb-3 bg-white">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('v2.titipan_barang.index') }}">
                <div class="row g-2.5 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small text-muted text-uppercase mb-1"
                            style="font-size:0.72rem;">Cari Transaksi</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-muted border-end-0"><i
                                    class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control form-control-sm border-start-0"
                                placeholder="No. Referensi / Catatan..." value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small text-muted text-uppercase mb-1"
                            style="font-size:0.72rem;">Supplier (Pemilik Barang)</label>
                        <select name="supplier_id" class="form-select form-select-sm select2">
                            <option value="">-- Semua Supplier --</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}"
                                    {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold small text-muted text-uppercase mb-1"
                            style="font-size:0.72rem;">Tanggal Mulai</label>
                        <input type="date" name="date_from" class="form-control form-control-sm"
                            value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold small text-muted text-uppercase mb-1"
                            style="font-size:0.72rem;">Tanggal Akhir</label>
                        <input type="date" name="date_to" class="form-control form-control-sm"
                            value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-1 d-flex gap-1">
                        <button type="submit" class="btn btn-primary btn-sm w-100 fw-semibold"><i
                                class="bi bi-filter"></i></button>
                        <a href="{{ route('v2.titipan_barang.index') }}" class="btn btn-outline-secondary btn-sm"><i
                                class="bi bi-arrow-counterclockwise"></i></a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Table Card ── --}}
    <div class="card border-0 shadow-sm rounded-3 bg-white overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 0.84rem;">
                <thead class="table-light text-uppercase small fw-bold text-secondary border-bottom">
                    <tr>
                        <th class="ps-3 text-center" style="width: 40px;">NO</th>
                        <th style="width: 14%;">NO REFERENSI</th>
                        <th style="width: 10%;">TANGGAL</th>
                        <th style="width: 18%;">SUPPLIER</th>
                        <th class="text-center" style="width: 10%;">QTY MASUK</th>
                        <th class="text-center" style="width: 10%;">TERJUAL</th>
                        <th class="text-end" style="width: 14%;">WAJIB DISETOR (RP)</th>
                        <th class="text-end" style="width: 12%;">TOTAL HPP</th>
                        <th class="text-center" style="width: 6%;">STATUS</th>
                        <th class="text-center pe-3" style="width: 6%;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($consignments as $index => $item)
                        @php
                            $rowQtySold = (int) $item->items->sum('qty_sold');
                            $rowAmountToPay = (float) $item->items->sum(
                                fn($it) => (int) ($it->qty_sold ?? 0) * (float) $it->unit_cost_price,
                            );
                        @endphp
                        <tr>
                            <td class="ps-3 text-center text-muted fw-semibold">{{ $consignments->firstItem() + $index }}
                            </td>
                            <td>
                                <a href="{{ route('v2.titipan_barang.show', $item) }}"
                                    class="fw-bold text-decoration-none text-primary">
                                    {{ $item->reference_number }}
                                </a>
                            </td>
                            <td class="text-secondary fw-medium">
                                {{ $item->consignment_date ? \Carbon\Carbon::parse($item->consignment_date)->format('d/m/Y') : '-' }}
                            </td>
                            <td>
                                <span
                                    class="fw-bold text-dark d-block">{{ $item->supplier ? $item->supplier->name : '-' }}</span>
                                <small
                                    class="text-muted">{{ $item->supplier ? ($item->supplier->phone ?: 'No Contact') : '-' }}</small>
                            </td>
                            <td class="text-center">
                                <span
                                    class="badge bg-secondary-subtle text-secondary border rounded-pill px-2.5 py-1 fw-semibold">
                                    {{ number_format($item->total_qty_received) }} PCS
                                </span>
                            </td>
                            <td class="text-center">
                                @if ($rowQtySold > 0)
                                    <span
                                        class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-2.5 py-1 fw-bold">
                                        {{ number_format($rowQtySold) }} PCS
                                    </span>
                                @else
                                    <span class="text-muted small">0 PCS</span>
                                @endif
                            </td>
                            <td class="text-end font-monospace">
                                @if ($rowAmountToPay > 0)
                                    <span class="fw-bold text-danger">
                                        Rp {{ number_format($rowAmountToPay, 0, ',', '.') }}
                                    </span>
                                @else
                                    <span class="text-muted small">Rp 0</span>
                                @endif
                            </td>
                            <td class="text-end fw-semibold text-dark font-monospace" style="font-size:0.8rem;">
                                Rp {{ number_format($item->total_amount_hpp, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                <span
                                    class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0.5"
                                    style="font-size:0.7rem;">
                                    <i class="bi bi-check-circle me-1"></i>Selesai
                                </span>
                            </td>
                            <td class="text-center pe-3">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('v2.titipan_barang.print_labels', $item) }}" target="_blank"
                                        class="btn btn-outline-success py-1 px-2" title="Cetak Barcode Label">
                                        <i class="bi bi-upc-scan"></i>
                                    </a>
                                    <a href="{{ route('v2.titipan_barang.show', $item) }}"
                                        class="btn btn-outline-primary py-1 px-2" title="Lihat Detail">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('v2.titipan_barang.edit', $item) }}"
                                        class="btn btn-outline-warning text-dark py-1 px-2" title="Edit Transaksi">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('v2.titipan_barang.destroy', $item) }}" method="POST"
                                        class="d-inline"
                                        onsubmit="return confirm('Apakah Anda yakin ingin menghapus transaksi {{ $item->reference_number }}? Stok produk master akan dikurangi kembali.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger py-1 px-2"
                                            title="Hapus Transaksi">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                Belum ada data penerimaan barang konsinyasi. Silakan klik tombol <b>Transaksi Baru</b>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($consignments->hasPages())
            <div class="card-footer bg-white border-top-0 py-3">
                {{ $consignments->links() }}
            </div>
        @endif
    @endsection

    @push('scripts')
        <script>
            function toggleModalBank(selectElem, supId) {
                const container = document.getElementById('modal-bank-container-' + supId);
                if (container) {
                    container.style.display = (selectElem.value === 'transfer') ? 'block' : 'none';
                }
            }

            function setModalAmount(supId, amount) {
                const input = document.getElementById('modal-amount-' + supId);
                if (input) {
                    input.value = Math.round(amount).toLocaleString('id-ID');
                }
            }

            function formatRupiahInput(input) {
                let val = input.value.replace(/[^0-9]/g, '');
                if (val) {
                    input.value = parseInt(val, 10).toLocaleString('id-ID');
                } else {
                    input.value = '';
                }
            }
        </script>
    @endpush
