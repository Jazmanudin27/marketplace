<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Detail ERP vs API — {{ strtoupper($channel) }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f1f5f9; }
        .topbar { background: #0f172a; color: #fff; padding: 14px 24px; position: sticky; top: 0; z-index: 100; display: flex; align-items: center; gap: 16px; flex-wrap: wrap; box-shadow: 0 2px 12px rgba(0,0,0,.35); }
        .ch-badge { font-size: .72rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; padding: 4px 12px; border-radius: 20px; }
        .ch-tiktok { background: #333; color: #fff; }
        .ch-shopee { background: #e53e1e; color: #fff; }
        .stat-card { background: #fff; border-radius: 12px; padding: 16px 20px; box-shadow: 0 1px 6px rgba(0,0,0,.07); transition: all 0.2s ease-in-out; border-bottom: 4px solid transparent; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.12); }
        .stat-card.active-all { border-bottom: 4px solid #1e293b; background: #f8fafc; }
        .stat-card.active-mismatch { border-bottom: 4px solid #f59e0b; background: #fffbeb; }
        .stat-card.active-no_api { border-bottom: 4px solid #64748b; background: #f1f5f9; }
        .stat-card.active-match { border-bottom: 4px solid #22c55e; background: #f0fdf4; }
        .stat-card .val { font-size: 1.5rem; font-weight: 700; line-height: 1.2; }
        .stat-card .lbl { font-size: .72rem; color: #64748b; text-transform: uppercase; letter-spacing: .06em; margin-top: 4px; }
        .filter-bar { background: #fff; border-radius: 12px; padding: 12px 20px; box-shadow: 0 1px 6px rgba(0,0,0,.07); display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .tbl-wrap { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 6px rgba(0,0,0,.07); }
        table { font-size: .79rem; }
        thead th { font-size: .66rem; text-transform: uppercase; letter-spacing: .06em; font-weight: 700; white-space: nowrap; }
        .row-mm { background: #fff7ed !important; }
        .row-ok { background: #fff; }
        .row-no-fb { background: #fafafa; opacity: .75; }
        .dp { color: #b45309; font-weight: 600; }
        .dn { color: #dc2626; font-weight: 600; }
        .bm { background: #dcfce7; color: #166534; font-size: .65rem; border: 1px solid #bbf7d0; border-radius: 20px; padding: 2px 8px; }
        .bmm { background: #fef3c7; color: #92400e; font-size: .65rem; border: 1px solid #fde68a; border-radius: 20px; padding: 2px 8px; }
        .bnf { background: #f1f5f9; color: #64748b; font-size: .65rem; border: 1px solid #e2e8f0; border-radius: 20px; padding: 2px 8px; }
        .oid { color: #1d4ed8; text-decoration: none; font-weight: 600; }
        .oid:hover { color: #1e40af; text-decoration: underline; }
        .bl { border-left: 2px solid #e2e8f0 !important; }
    </style>
</head>
<body>

<div class="topbar">
    <a href="{{ route('secret_repair.index') }}" class="text-white text-decoration-none"><i class="fas fa-arrow-left me-1"></i></a>
    <span class="ch-badge {{ $channel === 'shopee' ? 'ch-shopee' : 'ch-tiktok' }}">
        @if($channel === 'shopee')
            <i class="fas fa-shopping-bag me-1"></i>Shopee
        @else
            <i class="fab fa-tiktok me-1"></i>TikTok &amp; Tokopedia
        @endif
    </span>
    <div class="ms-2">
        <div class="fw-semibold" style="font-size:.9rem">Detail Perbandingan ERP vs API per Order</div>
        <div style="font-size:.72rem;color:#94a3b8">Periode: <strong class="text-white">{{ $dateFrom ?: 'semua' }} s/d {{ $dateTo ?: 'semua' }}</strong></div>
    </div>
    <div class="ms-auto d-flex gap-2">
        @if($mismatchRowsGlobal > 0)
            <button onclick="syncAllMismatches()" id="btnTopSyncAll" class="btn btn-sm btn-warning text-dark rounded-2 fw-bold" style="font-size:.75rem">
                <i class="fas fa-bolt me-1"></i>Sinkronkan Semua ({{ $mismatchRowsGlobal }})
            </button>
        @endif
        <button onclick="window.print()" class="btn btn-sm btn-outline-light rounded-2" style="font-size:.75rem"><i class="fas fa-print me-1"></i>Print</button>
        <button onclick="exportCsv()" class="btn btn-sm btn-outline-light rounded-2" style="font-size:.75rem"><i class="fas fa-download me-1"></i>CSV</button>
    </div>
</div>

<div class="container-fluid py-4 px-4">
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <a href="{{ route('secret_repair.compare_detail', array_merge(request()->all(), ['filter' => 'all'])) }}" class="text-decoration-none text-dark">
                <div class="stat-card {{ $filter === 'all' ? 'active-all' : '' }}">
                    <div class="val text-dark">{{ number_format($totalRowsGlobal) }}</div>
                    <div class="lbl">Total Order</div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="{{ route('secret_repair.compare_detail', array_merge(request()->all(), ['filter' => 'mismatch'])) }}" class="text-decoration-none">
                <div class="stat-card {{ $filter === 'mismatch' ? 'active-mismatch' : '' }}" style="border-left:4px solid #f59e0b">
                    <div class="val" style="color:#b45309">{{ number_format($mismatchRowsGlobal) }}</div>
                    <div class="lbl" style="color:#b45309">Mismatch ERP≠API</div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="{{ route('secret_repair.compare_detail', array_merge(request()->all(), ['filter' => 'no_api'])) }}" class="text-decoration-none">
                <div class="stat-card {{ $filter === 'no_api' ? 'active-no_api' : '' }}" style="border-left:4px solid #94a3b8">
                    <div class="val text-secondary">{{ number_format($noFbRowsGlobal) }}</div>
                    <div class="lbl text-secondary">Tanpa Data API</div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="{{ route('secret_repair.compare_detail', array_merge(request()->all(), ['filter' => 'match'])) }}" class="text-decoration-none">
                <div class="stat-card {{ $filter === 'match' ? 'active-match' : '' }}" style="border-left:4px solid #22c55e">
                    <div class="val" style="color:#16a34a">{{ number_format($matchRowsGlobal) }}</div>
                    <div class="lbl" style="color:#16a34a">Match ✓</div>
                </div>
            </a>
        </div>
    </div>

    <form method="GET" action="{{ route('secret_repair.compare_detail') }}" class="filter-bar mb-4">
        <input type="hidden" name="channel" value="{{ $channel }}">
        <input type="hidden" name="date_from" value="{{ $dateFrom }}">
        <input type="hidden" name="date_to" value="{{ $dateTo }}">
        <input type="hidden" name="filter" value="{{ $filter }}">
        <div>
            <label class="text-secondary fw-semibold me-1" style="font-size:.75rem">Toko:</label>
            <select name="store_id" class="form-select form-select-sm d-inline-block rounded-2" style="width:180px;font-size:.8rem" onchange="this.form.submit()">
                <option value="">Semua Toko</option>
                @foreach($stores as $st)
                    <option value="{{ $st->id }}" {{ $storeId == $st->id ? 'selected' : '' }}>{{ $st->store_name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-secondary fw-semibold me-1" style="font-size:.75rem">Status:</label>
            <select name="status" class="form-select form-select-sm d-inline-block rounded-2" style="width:180px;font-size:.8rem" onchange="this.form.submit()">
                <option value="all" {{ ($status ?? 'all') == 'all' ? 'selected' : '' }}>Semua Status (Aktif)</option>
                <option value="READY_TO_SHIP" {{ ($status ?? '') == 'READY_TO_SHIP' ? 'selected' : '' }}>Ready to Ship</option>
                <option value="SHIPPED" {{ ($status ?? '') == 'SHIPPED' ? 'selected' : '' }}>Shipped</option>
                <option value="DELIVERED" {{ ($status ?? '') == 'DELIVERED' ? 'selected' : '' }}>Delivered</option>
                <option value="COMPLETED" {{ ($status ?? '') == 'COMPLETED' ? 'selected' : '' }}>Completed</option>
                <option value="CANCELLED" {{ ($status ?? '') == 'CANCELLED' ? 'selected' : '' }}>Cancelled</option>
                <option value="UNPAID" {{ ($status ?? '') == 'UNPAID' ? 'selected' : '' }}>Unpaid</option>
                <option value="RETURNED" {{ ($status ?? '') == 'RETURNED' ? 'selected' : '' }}>Returned</option>
            </select>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <a href="{{ route('secret_repair.compare_detail', array_merge(request()->all(), ['filter' => 'all'])) }}" class="btn btn-sm rounded-2 {{ $filter === 'all' ? 'btn-dark' : 'btn-outline-secondary' }}" style="font-size:.77rem">Semua</a>
            <a href="{{ route('secret_repair.compare_detail', array_merge(request()->all(), ['filter' => 'mismatch'])) }}" class="btn btn-sm rounded-2 {{ $filter === 'mismatch' ? 'btn-warning text-dark' : 'btn-outline-warning' }}" style="font-size:.77rem"><i class="fas fa-exclamation-triangle me-1"></i>Mismatch ({{ $mismatchRowsGlobal }})</a>
            <a href="{{ route('secret_repair.compare_detail', array_merge(request()->all(), ['filter' => 'no_api'])) }}" class="btn btn-sm rounded-2 {{ $filter === 'no_api' ? 'btn-secondary text-white' : 'btn-outline-secondary' }}" style="font-size:.77rem"><i class="fas fa-unlink me-1"></i>Tanpa API ({{ $noFbRowsGlobal }})</a>
            <a href="{{ route('secret_repair.compare_detail', array_merge(request()->all(), ['filter' => 'match'])) }}" class="btn btn-sm rounded-2 {{ $filter === 'match' ? 'btn-success text-white' : 'btn-outline-success' }}" style="font-size:.77rem"><i class="fas fa-check-circle me-1"></i>Match ({{ $matchRowsGlobal }})</a>
        </div>

        @if($mismatchRowsGlobal > 0)
            <div>
                <button type="button" onclick="syncAllMismatches()" id="btnSyncAllBar" class="btn btn-sm btn-success rounded-2 fw-semibold" style="font-size:.77rem">
                    <i class="fas fa-magic me-1"></i>Sinkronkan Semua Mismatch
                </button>
            </div>
        @endif

        <div class="ms-auto">
            <input type="text" id="searchBox" class="form-control form-control-sm rounded-2" style="width:220px;font-size:.8rem" placeholder="🔍 Cari ID / Nama Toko...">
        </div>
    </form>

    <div class="tbl-wrap">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr style="background:#0f172a">
                        <th class="ps-3 py-3 text-white" style="min-width:45px">#</th>
                        <th class="py-3 text-white" style="min-width:200px">ID ORDER MARKETPLACE</th>
                        <th class="py-3 text-white" style="min-width:95px">TANGGAL</th>
                        <th class="py-3 text-white" style="min-width:115px">STATUS</th>
                        <th class="py-3 text-white" style="min-width:120px">TOKO</th>
                        <th class="py-3 text-end text-center bl" colspan="2" style="background:#1e3a5f;color:#93c5fd;min-width:260px">OMSET</th>
                        <th class="py-3 text-end text-center bl" colspan="2" style="background:#3b1f0a;color:#fdba74;min-width:260px">BIAYA ADMIN</th>
                        <th class="py-3 text-end text-center bl" colspan="2" style="background:#052e16;color:#86efac;min-width:260px">DANA CAIR</th>
                        <th class="py-3 text-end text-center bl" colspan="2" style="background:#3b0764;color:#e9d5ff;min-width:260px">REFUND / RETUR</th>
                        <th class="py-3 text-center bl" style="background:#2e1065;color:#d8b4fe;min-width:80px">STATUS</th>
                        <th class="py-3 text-center pe-3 bl" style="background:#1e1b4b;color:#c7d2fe;min-width:110px">AKSI</th>
                    </tr>
                    <tr style="background:#1e293b;font-size:.65rem">
                        <th colspan="5" class="py-2"></th>
                        <th class="py-2 text-end bl" style="color:#93c5fd">ERP</th>
                        <th class="py-2 text-end" style="color:#93c5fd">API</th>
                        <th class="py-2 text-end bl" style="color:#fdba74">ERP</th>
                        <th class="py-2 text-end" style="color:#fdba74">API</th>
                        <th class="py-2 text-end bl" style="color:#86efac">ERP</th>
                        <th class="py-2 text-end" style="color:#86efac">API</th>
                        <th class="py-2 text-end bl" style="color:#e9d5ff">ERP</th>
                        <th class="py-2 text-end" style="color:#e9d5ff">API</th>
                        <th class="py-2 bl"></th>
                        <th class="py-2 pe-3 bl"></th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                @forelse($rows as $i => $row)
                @php
                    $rc = !$row['has_fb'] ? 'row-no-fb' : ($row['is_mismatch'] ? 'row-mm' : 'row-ok');
                @endphp
                <tr class="{{ $rc }} sr" id="order-row-{{ $row['id'] }}">
                    <td class="ps-3 text-secondary" style="font-size:.72rem">{{ $i+1 }}</td>
                    <td>
                        <a href="{{ route('orders.show', $row['id']) }}" target="_blank" class="oid st">{{ $row['marketplace_id'] ?? 'ID-'.$row['id'] }}</a>
                    </td>
                    <td class="text-secondary" style="font-size:.78rem;white-space:nowrap">{{ \Carbon\Carbon::parse($row['order_date'])->format('d M Y') }}</td>
                    <td>
                        @php
                            $sc = ['COMPLETED'=>'success','SELESAI'=>'success','FINISHED'=>'success','DELIVERED'=>'success','SHIPPED'=>'primary','IN_TRANSIT'=>'primary','READY_TO_SHIP'=>'info','PROCESSING'=>'warning','UNPAID'=>'secondary'][$row['order_status']] ?? 'secondary';
                        @endphp
                        <span class="badge bg-{{ $sc }}-subtle text-{{ $sc }}-emphasis border border-{{ $sc }}-subtle" style="font-size:.65rem">{{ $row['order_status'] }}</span>
                    </td>
                    <td class="text-secondary st" style="font-size:.78rem">{{ $row['store_name'] }}</td>

                    {{-- OMSET --}}
                    <td class="text-end font-monospace bl cell-erp-omset" style="background:#eff6ff;color:#1d4ed8">{{ 'Rp '.number_format($row['erp_omset'],0,',','.') }}</td>
                    <td class="text-end font-monospace cell-api-omset" style="background:#eff6ff;color:#1d4ed8">
                        @if($row['api_omset'] !== null)
                            {{ 'Rp '.number_format($row['api_omset'],0,',','.') }}
                            @if(abs($row['diff_omset']) > 100)
                                <br><small class="{{ $row['diff_omset'] > 0 ? 'dp' : 'dn' }}" style="font-size:.65rem">{{ ($row['diff_omset'] > 0 ? '+' : '') . number_format($row['diff_omset'],0,',','.') }}</small>
                            @endif
                        @else
                            <span class="text-secondary opacity-50">—</span>
                        @endif
                    </td>

                    {{-- BIAYA ADMIN --}}
                    <td class="text-end font-monospace bl cell-erp-fee" style="background:#fff7ed;color:#c2410c">{{ 'Rp '.number_format($row['erp_fee'],0,',','.') }}</td>
                    <td class="text-end font-monospace cell-api-fee" style="background:#fff7ed;color:#c2410c">
                        @if($row['api_fee'] !== null)
                            {{ 'Rp '.number_format($row['api_fee'],0,',','.') }}
                            @if(abs($row['diff_fee']) > 100)
                                <br><small class="{{ $row['diff_fee'] > 0 ? 'dp' : 'dn' }}" style="font-size:.65rem">{{ ($row['diff_fee'] > 0 ? '+' : '') . number_format($row['diff_fee'],0,',','.') }}</small>
                            @endif
                        @else
                            <span class="text-secondary opacity-50">—</span>
                        @endif
                    </td>

                    {{-- DANA CAIR --}}
                    <td class="text-end font-monospace bl cell-erp-net" style="background:#f0fdf4;color:#15803d">{{ 'Rp '.number_format($row['erp_net'],0,',','.') }}</td>
                    <td class="text-end font-monospace cell-api-net" style="background:#f0fdf4;color:#15803d">
                        @if($row['api_net'] !== null)
                            {{ 'Rp '.number_format($row['api_net'],0,',','.') }}
                            @if(abs($row['diff_net']) > 100)
                                <br><small class="{{ $row['diff_net'] > 0 ? 'dp' : 'dn' }}" style="font-size:.65rem">{{ ($row['diff_net'] > 0 ? '+' : '') . number_format($row['diff_net'],0,',','.') }}</small>
                            @endif
                        @else
                            <span class="text-secondary opacity-50">—</span>
                        @endif
                    </td>

                    {{-- REFUND / RETUR --}}
                    <td class="text-end font-monospace bl cell-erp-refund" style="background:#faf5ff;color:#7e22ce">
                        {{ $row['erp_refund'] > 0 ? 'Rp '.number_format($row['erp_refund'],0,',','.') : '—' }}
                    </td>
                    <td class="text-end font-monospace cell-api-refund" style="background:#faf5ff;color:#7e22ce">
                        @if($row['api_refund'] !== null)
                            {{ $row['api_refund'] > 0 ? 'Rp '.number_format($row['api_refund'],0,',','.') : '—' }}
                            @if($row['api_refund'] > 0 && abs($row['diff_refund']) > 100)
                                <br><small class="{{ $row['diff_refund'] > 0 ? 'dp' : 'dn' }}" style="font-size:.65rem">{{ ($row['diff_refund'] > 0 ? '+' : '') . number_format($row['diff_refund'],0,',','.') }}</small>
                            @elseif($row['api_refund'] > 0)
                                <br><small style="color:#16a34a;font-size:.65rem">✓ sinkron</small>
                            @endif
                        @else
                            <span class="text-secondary opacity-50">—</span>
                        @endif
                    </td>

                    <td class="text-center bl cell-status">
                        @if(!$row['has_fb'])
                            <span class="bnf">No API</span>
                        @elseif($row['is_mismatch'])
                            <span class="bmm"><i class="fas fa-exclamation-triangle me-1"></i>Beda</span>
                        @else
                            <span class="bm"><i class="fas fa-check me-1"></i>Match</span>
                        @endif
                    </td>

                    <td class="text-center pe-3 bl cell-action">
                        @if($row['is_mismatch'])
                            <button type="button" class="btn btn-sm btn-warning text-dark py-1 px-2 fw-semibold rounded-2 sync-btn" style="font-size:.68rem" onclick="syncOrder({{ $row['id'] }}, this)" title="Sinkronkan order ini ke nilai API resmi">
                                <i class="fas fa-sync-alt me-1"></i>Sinkronkan
                            </button>
                        @elseif(!$row['has_fb'])
                            <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 rounded-2 sync-btn" style="font-size:.68rem" onclick="syncOrder({{ $row['id'] }}, this)" title="Tarik data live dari API Marketplace">
                                <i class="fas fa-cloud-download-alt me-1"></i>Tarik API
                            </button>
                        @else
                            <span class="text-success fw-semibold" style="font-size:.7rem;"><i class="fas fa-check-circle me-1"></i>Sinkron</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="13" class="text-center py-5 text-secondary"><i class="fas fa-inbox fa-2x mb-3 d-block opacity-25"></i>Tidak ada order ditemukan.</td>
                </tr>
                @endforelse
                </tbody>
                @if(count($rows) > 0)
                @php
                    $sumErpOmset  = array_sum(array_column($rows, 'erp_omset'));
                    $sumErpFee    = array_sum(array_column($rows, 'erp_fee'));
                    $sumErpNet    = array_sum(array_column($rows, 'erp_net'));
                    $sumErpRefund = array_sum(array_column($rows, 'erp_refund'));
                    $sumApiOmset  = array_sum(array_filter(array_column($rows, 'api_omset'),  fn($v) => $v !== null));
                    $sumApiFee    = array_sum(array_filter(array_column($rows, 'api_fee'),    fn($v) => $v !== null));
                    $sumApiNet    = array_sum(array_filter(array_column($rows, 'api_net'),    fn($v) => $v !== null));
                    $sumApiRefund = array_sum(array_filter(array_column($rows, 'api_refund'), fn($v) => $v !== null));
                    $sumDiffOmset  = $sumErpOmset  - $sumApiOmset;
                    $sumDiffFee    = $sumErpFee    - $sumApiFee;
                    $sumDiffNet    = $sumErpNet    - $sumApiNet;
                    $sumDiffRefund = $sumErpRefund - $sumApiRefund;
                @endphp
                <tfoot class="fw-bold" style="background:#0f172a; color:#fff; font-size:0.78rem;">
                    <tr style="border-top:2px solid #334155;">
                        <td colspan="5" class="ps-3 py-3 text-uppercase text-white" style="letter-spacing:0.04em; font-size:0.74rem;">
                            TOTAL ({{ number_format(count($rows)) }} ORDER)
                        </td>

                        {{-- TOTAL OMSET --}}
                        <td class="text-end font-monospace bl" style="color:#93c5fd; background:#1e3a5f;">
                            {{ 'Rp ' . number_format($sumErpOmset, 0, ',', '.') }}
                        </td>
                        <td class="text-end font-monospace" style="color:#93c5fd; background:#1e3a5f;">
                            {{ 'Rp ' . number_format($sumApiOmset, 0, ',', '.') }}
                            @if(abs($sumDiffOmset) > 100)
                                <br><small style="color:{{ $sumDiffOmset > 0 ? '#fde047' : '#f87171' }}; font-size:0.67rem;">
                                    {{ ($sumDiffOmset > 0 ? '+' : '') . number_format($sumDiffOmset, 0, ',', '.') }}
                                </small>
                            @endif
                        </td>

                        {{-- TOTAL BIAYA ADMIN --}}
                        <td class="text-end font-monospace bl" style="color:#fdba74; background:#3b1f0a;">
                            {{ 'Rp ' . number_format($sumErpFee, 0, ',', '.') }}
                        </td>
                        <td class="text-end font-monospace" style="color:#fdba74; background:#3b1f0a;">
                            {{ 'Rp ' . number_format($sumApiFee, 0, ',', '.') }}
                            @if(abs($sumDiffFee) > 100)
                                <br><small style="color:{{ $sumDiffFee > 0 ? '#fde047' : '#f87171' }}; font-size:0.67rem;">
                                    {{ ($sumDiffFee > 0 ? '+' : '') . number_format($sumDiffFee, 0, ',', '.') }}
                                </small>
                            @endif
                        </td>

                        {{-- TOTAL DANA CAIR --}}
                        <td class="text-end font-monospace bl" style="color:#86efac; background:#052e16;">
                            {{ 'Rp ' . number_format($sumErpNet, 0, ',', '.') }}
                        </td>
                        <td class="text-end font-monospace" style="color:#86efac; background:#052e16;">
                            {{ 'Rp ' . number_format($sumApiNet, 0, ',', '.') }}
                            @if(abs($sumDiffNet) > 100)
                                <br><small style="color:{{ $sumDiffNet > 0 ? '#fde047' : '#f87171' }}; font-size:0.67rem;">
                                    {{ ($sumDiffNet > 0 ? '+' : '') . number_format($sumDiffNet, 0, ',', '.') }}
                                </small>
                            @endif
                        </td>

                        {{-- TOTAL REFUND --}}
                        <td class="text-end font-monospace bl" style="color:#e9d5ff; background:#3b0764;">
                            {{ $sumErpRefund > 0 ? 'Rp ' . number_format($sumErpRefund, 0, ',', '.') : '—' }}
                        </td>
                        <td class="text-end font-monospace" style="color:#e9d5ff; background:#3b0764;">
                            {{ $sumApiRefund > 0 ? 'Rp ' . number_format($sumApiRefund, 0, ',', '.') : '—' }}
                            @if(abs($sumDiffRefund) > 100)
                                <br><small style="color:{{ $sumDiffRefund > 0 ? '#fde047' : '#f87171' }}; font-size:0.67rem;">
                                    {{ ($sumDiffRefund > 0 ? '+' : '') . number_format($sumDiffRefund, 0, ',', '.') }}
                                </small>
                            @elseif($sumErpRefund > 0 || $sumApiRefund > 0)
                                <br><small style="color:#4ade80;font-size:.65rem">✓ sinkron</small>
                            @endif
                        </td>

                        <td class="text-center bl" style="background:#2e1065;">
                            @if(abs($sumDiffNet) < 100 && abs($sumDiffOmset) < 100 && abs($sumDiffFee) < 100 && abs($sumDiffRefund) < 100)
                                <span class="bm">✓ Match</span>
                            @else
                                <span class="bmm">Selisih</span>
                            @endif
                        </td>
                        <td class="text-center pe-3 bl" style="background:#1e1b4b;"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
        @if(count($rows) > 0)
        <div class="px-4 py-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2" style="background:#f8fafc">
            <div style="font-size:.78rem;color:#64748b">
                Menampilkan <strong>{{ count($rows) }}</strong> order
                @if($filter === 'mismatch')
                    <span class="bmm ms-1">Filter: Mismatch saja</span>
                @endif
            </div>
            <div class="d-flex gap-3" style="font-size:.73rem;color:#64748b">
                <span><span class="bm me-1">Match</span>selisih &lt; Rp 100</span>
                <span><span class="bmm me-1">Beda</span>selisih &gt; Rp 100</span>
                <span><span class="bnf me-1">No API</span>belum ada data API</span>
            </div>
        </div>
        @endif
    </div>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

document.getElementById('searchBox').addEventListener('input', function(){
    const q = this.value.toLowerCase().trim();
    document.querySelectorAll('.sr').forEach(r => {
        r.style.display = (!q || r.textContent.toLowerCase().includes(q)) ? '' : 'none';
    });
});

function syncOrder(orderId, btn) {
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>...';

    fetch(`/fix/sync-order/${orderId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            btn.className = 'btn btn-sm btn-success py-1 px-2 rounded-2 fw-semibold';
            btn.innerHTML = '<i class="fas fa-check me-1"></i>Selesai';
            setTimeout(() => location.reload(), 600);
        } else {
            alert(data.error || 'Gagal menyinkronkan pesanan.');
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    })
    .catch(err => {
        alert('Terjadi kesalahan koneksi: ' + err.message);
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}

function syncAllMismatches() {
    if (!confirm('Apakah Anda yakin ingin menyinkronkan SEMUA pesanan yang mismatch dalam filter tanggal ini ke data resmi API?')) {
        return;
    }

    const btn1 = document.getElementById('btnTopSyncAll');
    const btn2 = document.getElementById('btnSyncAllBar');
    if (btn1) { btn1.disabled = true; btn1.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyinkronkan...'; }
    if (btn2) { btn2.disabled = true; btn2.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyinkronkan...'; }

    fetch('{{ route("secret_repair.sync_mismatches") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            channel: '{{ $channel }}',
            date_from: '{{ $dateFrom }}',
            date_to: '{{ $dateTo }}',
            store_id: '{{ $storeId }}'
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.error || 'Gagal menyinkronkan data.');
            if (btn1) { btn1.disabled = false; btn1.innerHTML = '<i class="fas fa-bolt me-1"></i>Sinkronkan Semua'; }
            if (btn2) { btn2.disabled = false; btn2.innerHTML = '<i class="fas fa-magic me-1"></i>Sinkronkan Semua Mismatch'; }
        }
    })
    .catch(err => {
        alert('Terjadi kesalahan koneksi: ' + err.message);
        if (btn1) { btn1.disabled = false; }
        if (btn2) { btn2.disabled = false; }
    });
}

function exportCsv(){
    const rows = [['#','ID Order','Tanggal','Status','Toko','Omset ERP','Omset API','Selisih Omset','Admin ERP','Admin API','Selisih Admin','Dana Cair ERP','Dana Cair API','Selisih Net','Refund ERP','Refund API','Selisih Refund','Status']];
    @foreach($rows as $i => $row)
    rows.push([
        {{ $i + 1 }},
        '{{ addslashes($row["marketplace_id"] ?? "ID-".$row["id"]) }}',
        '{{ \Carbon\Carbon::parse($row["order_date"])->format("d/m/Y") }}',
        '{{ $row["order_status"] }}',
        '{{ addslashes($row["store_name"]) }}',
        {{ $row["erp_omset"] }},
        {{ $row["api_omset"] ?? "null" }},
        {{ $row["diff_omset"] ?? "null" }},
        {{ $row["erp_fee"] }},
        {{ $row["api_fee"] ?? "null" }},
        {{ $row["diff_fee"] ?? "null" }},
        {{ $row["erp_net"] }},
        {{ $row["api_net"] ?? "null" }},
        {{ $row["diff_net"] ?? "null" }},
        {{ $row["erp_refund"] }},
        {{ $row["api_refund"] ?? "null" }},
        {{ $row["diff_refund"] ?? "null" }},
        '{{ !$row["has_fb"] ? "No API" : ($row["is_mismatch"] ? "Mismatch" : "Match") }}'
    ]);
    @endforeach
    const csv = rows.map(r => r.map(v => `"${String(v ?? '').replace(/"/g,'""')}"`).join(',')).join('\n');
    const a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob(["\uFEFF" + csv], { type: 'text/csv;charset=utf-8;' }));
    a.download = `erp_api_{{ $channel }}_{{ $dateFrom }}_{{ $dateTo }}.csv`;
    a.click();
}
</script>
</body>
</html>
