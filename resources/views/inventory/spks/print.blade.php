<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Cetak SPK Perintah Kerja - {{ $spk->no_produksi ?: $spk->no_spk }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 9.5px;
            color: #0f172a;
            margin: 0;
            padding: 0;
            background: #cbd5e1;
            line-height: 1.2;
        }

        /* ── Real A4 Paper Sheet Container ── */
        .a4-sheet-container {
            width: 210mm;
            height: 297mm;
            min-height: 297mm;
            max-height: 297mm;
            margin: 20px auto;
            padding: 0;
            background: #ffffff;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.25), 0 8px 10px -6px rgba(0, 0, 0, 0.15);
            border-radius: 4px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        .no-print {
            margin: 0;
            padding: 10px 24px;
            background: #0f172a;
            color: #fff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
        }

        /* ── Half A4 Slip Card (Exact 147mm height per slip + 3mm separator = 297mm total) ── */
        .spk-slip-card {
            width: 100%;
            height: 147mm;
            max-height: 147mm;
            padding: 3mm 5mm;
            position: relative;
            overflow: hidden;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .slip-separator {
            border-top: 1.5px dashed #64748b;
            margin: 0;
            width: 100%;
            height: 3mm;
            flex-shrink: 0;
        }

        .page-break {
            page-break-after: always;
            break-after: page;
            height: 0;
            margin: 0;
            padding: 0;
        }

        /* Header Layout */
        .page-copy-tag {
            text-align: right;
            font-size: 7.5px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 3px;
        }

        .header-table td {
            vertical-align: top;
            padding: 0;
        }

        .header-left {
            width: 30%;
            font-size: 9px;
            font-weight: 700;
        }

        .header-center {
            width: 34%;
            text-align: center;
        }

        .header-right {
            width: 26%;
            text-align: right;
            font-size: 9px;
            padding-right: 16px;
        }

        .header-qr {
            width: 10%;
            text-align: right;
            padding-left: 4px;
        }

        .spk-title-main {
            font-size: 22px;
            font-weight: 900;
            letter-spacing: 5px;
            line-height: 1;
            margin: 0;
            color: #000;
        }

        .spk-sub-main {
            font-size: 8.5px;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: #1e293b;
            margin-top: 1px;
        }

        .val-mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-weight: 800;
        }

        .text-danger {
            color: #dc2626;
        }

        .fw-bold {
            font-weight: 700;
        }

        .header-divider-bar {
            width: 100%;
            height: 2.5px;
            background: #000;
            margin-bottom: 4px;
        }

        /* Mockup / Image Frame Box (DI ATAS) */
        .design-box-frame {
            border: 1.5px dashed #64748b;
            border-radius: 6px;
            padding: 2px;
            text-align: center;
            margin-bottom: 0;
            background: #fff;
            height: 280px;
            min-height: 280px;
            max-height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            width: 100%;
        }

        .design-img {
            width: 100%;
            height: 100%;
            max-height: 280px;
            object-fit: contain;
        }

        .design-placeholder-text {
            color: #94a3b8;
            font-size: 10px;
            font-weight: 700;
            border: 1px dashed #cbd5e1;
            padding: 15px 30px;
            border-radius: 6px;
            background: #f8fafc;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Pemesan & Admin Bar */
        .pemesan-info-bar {
            border-top: 1px dashed #475569;
            border-bottom: 1px dashed #475569;
            padding: 3px 0;
            font-size: 8.5px;
            font-weight: 700;
            margin-bottom: 4px;
            color: #0f172a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Tables & Section Banners */
        .banner-blue {
            background: #2563eb;
            color: #fff;
            font-size: 8.5px;
            font-weight: 800;
            text-align: center;
            padding: 2px 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-radius: 2px 2px 0 0;
        }

        .banner-slate {
            background: #475569;
            color: #fff;
            font-size: 8.5px;
            font-weight: 800;
            text-align: center;
            padding: 2px 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-radius: 2px 2px 0 0;
        }

        .grid-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }

        .grid-table th,
        .grid-table td {
            border: 1px solid #000;
            padding: 2px 3px;
            text-align: center;
            font-size: 8.5px;
        }

        .grid-table th {
            background: #fff;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 8px;
        }

        /* Catatan / Keterangan Box */
        .catatan-box {
            border: 1.5px solid #000;
            border-radius: 6px;
            padding: 4px 6px;
            background: #fff;
            margin-top: 2px;
            min-height: 32px;
        }

        .catatan-title {
            font-weight: 800;
            font-size: 8px;
            text-transform: uppercase;
            color: #0f172a;
            margin-bottom: 1px;
        }

        .catatan-text {
            font-size: 8.5px;
            font-weight: 700;
            color: #0f172a;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            html,
            body {
                width: 210mm !important;
                height: 297mm !important;
                background: #fff !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .a4-sheet-container {
                width: 210mm !important;
                height: 297mm !important;
                min-height: 297mm !important;
                max-height: 297mm !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                background: #fff !important;
                page-break-after: always;
                break-after: page;
                overflow: hidden !important;
            }
        }
    </style>
</head>

<body>

    {{-- Top Sticky Control Bar for Review & Actions --}}
    @php
        $globalSlipCount = 0;
        $totalBlocks = count($spkBlocks);
    @endphp

    @foreach ($spkBlocks as $bIdx => $block)
        @php
            $currentSpk = $block['spk'];
            $variantRows = $block['variantRows'];
            $bazaItems = $block['bazaItems'];
            $firstVarName = !empty($variantRows) ? array_key_first($variantRows) : 'MODEL VARIAN';
        @endphp

        @foreach ([1, 2] as $copyNum)
            @php
                $globalSlipCount++;
                $isEvenSlip = $globalSlipCount % 2 === 0;
            @endphp

            @if ($globalSlipCount % 2 === 1)
                <!-- START REAL A4 SHEET CONTAINER -->
                <div class="a4-sheet-container">
            @endif

            <div class="spk-slip-card">
                <div>
                    <!-- Copy Tag Header -->
                    <div class="page-copy-tag">
                        HALAMAN {{ $copyNum }}: {{ $copyNum === 1 ? 'TIM PRODUKSI' : 'ARSIP KANTOR / FINISHING' }}
                    </div>

                    <!-- Header Grid -->
                    <table class="header-table">
                        <tr>
                            <td class="header-left">
                                <div><span style="color:#475569;">NO PRODUKSI :</span> <span
                                        class="val-mono">{{ $currentSpk->no_produksi ?: '—' }}</span></div>
                                <div style="margin-top: 2px;"><span style="color:#475569;">NO PESANAN :</span> <span
                                        class="val-mono">{{ $currentSpk->no_spk }}</span></div>
                            </td>
                            <td class="header-center">
                                <h1 class="spk-title-main">S P K</h1>
                                <div class="spk-sub-main">SURAT PERINTAH KERJA</div>
                            </td>
                            <td class="header-right">
                                <div><span style="color:#475569;">ORDER DATE :</span>
                                    <strong>{{ $currentSpk->tanggal ? $currentSpk->tanggal->format('Y-m-d') : date('Y-m-d') }}</strong>
                                </div>
                                <div style="margin-top: 2px;"><span style="color:#475569;">DEADLINE :</span> <span
                                        class="text-danger fw-bold">{{ $currentSpk->deadline ? $currentSpk->deadline->format('Y-m-d') : '—' }}</span>
                                </div>
                            </td>
                            <td class="header-qr">
                                @php
                                    $spkTrackUrl = route('spks.mobile_scan', $currentSpk->id);
                                @endphp
                                <a href="{{ $spkTrackUrl }}" target="_blank" title="Scan / Update Tracking SPK">
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data={{ urlencode($spkTrackUrl) }}"
                                        alt="QR Tracking SPK"
                                        style="width: 44px; height: 44px; display:block; margin: 0 auto;">
                                </a>
                                <div
                                    style="font-size: 6px; text-align: center; color: #475569; font-weight: 800; margin-top: 1.5px; line-height: 1; letter-spacing: 0.2px;">
                                    SCAN TRACKING HP
                                </div>
                            </td>
                        </tr>
                    </table>

                    <div class="header-divider-bar"></div>

                    <!-- Client & Admin Bar -->
                    <div class="pemesan-info-bar">
                        PEMESAN: {{ strtoupper($currentSpk->pemesan ?: 'INTERNAL / STOK GUDANG') }}
                        @if ($currentSpk->no_hp_pemesan)
                            ({{ $currentSpk->no_hp_pemesan }})
                        @endif
                        | INSTANSI: {{ strtoupper($currentSpk->instansi ?: '—') }}
                        | ADMIN: {{ strtoupper($currentSpk->nama_pic ?: $currentSpk->penginput->name ?? 'SYSTEM') }}
                    </div>

                    <!-- 2-Column Layout: Col 5 (Gambar Desain) & Col 7 (Rincian Varian & Kain) -->
                    @php
                        $firstBazaItem = !empty($bazaItems) ? reset($bazaItems) : null;
                        $formattedQty = '—';
                        if ($firstBazaItem) {
                            $rawQty = $firstBazaItem['qty'];
                            if (is_numeric($rawQty)) {
                                $num = (float) $rawQty;
                                if ($num > 15) {
                                    $num = $num / 100;
                                }
                                $formattedQty = number_format($num, 3, ',', '.');
                                $formattedQty = rtrim(rtrim($formattedQty, '0'), ',');
                            } else {
                                $formattedQty = $rawQty;
                            }
                        }
                    @endphp
                    <!-- GAMBAR DESAIN / MOCKUP (DI ATAS) -->
                    <div style="margin-bottom: 4px;">
                        <div class="banner-slate">
                            🖼️ GAMBAR DESAIN / MOCKUP
                        </div>
                        <div class="design-box-frame">
                            @php
                                $imgSrc =
                                    $currentSpk->mockup_url ?:
                                    ($currentSpk->image_url ?:
                                    $currentSpk->referensi_klien_url);
                            @endphp
                            @if ($imgSrc)
                                <img src="{{ $imgSrc }}" class="design-img" alt="Desain SPK">
                            @else
                                <div class="design-placeholder-text">
                                    👕 TEMPEL GAMBAR DESAIN / MOCKUP DI SINI
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- RINCIAN VARIAN PRODUK & KEBUTUHAN KAIN (DI BAWAH) -->
                    <div style="margin-bottom: 4px;">
                        <div class="banner-blue">
                            RINCIAN VARIAN PRODUK &amp; KEBUTUHAN KAIN
                        </div>
                        <table class="grid-table" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th rowspan="2" style="width: 25%;">Model Varian</th>
                                    <th colspan="{{ count($sizesHeader) }}">Size Target / Potong</th>
                                    <th rowspan="2" style="width: 10%; background: #dc2626; color: #fff;">
                                        Total QTY</th>
                                    <th rowspan="2" style="width: 12.5%;">Estimasi Kain (m)</th>
                                    <th rowspan="2" style="width: 12.5%;">Sisa Kain (m)</th>
                                </tr>
                                <tr>
                                    @php $szColWidth = count($sizesHeader) > 0 ? round(40 / count($sizesHeader), 2) : 5; @endphp
                                    @foreach ($sizesHeader as $szH)
                                        <th style="width: {{ $szColWidth }}%;">{{ $szH }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $sizeTotals = [];
                                    $grandTotalQty = 0;
                                    $grandTotalFabric = 0;
                                    foreach ($sizesHeader as $szH) {
                                        $sizeTotals[$szH] = 0;
                                    }
                                @endphp

                                @forelse($variantRows as $varRowIdx => $varRow)
                                    @php
                                        $rowFabQty = (float) ($varRow['fabric_qty'] ?? 0);
                                        if (
                                            $rowFabQty <= 0 &&
                                            $loop->first &&
                                            !empty($formattedQty) &&
                                            $formattedQty !== '—'
                                        ) {
                                            $rowFabQty = (float) str_replace(',', '.', $formattedQty);
                                        }
                                        $grandTotalFabric += $rowFabQty;
                                        $grandTotalQty += (int) ($varRow['total'] ?? 0);
                                        foreach ($sizesHeader as $szH) {
                                            $sizeTotals[$szH] += (int) ($varRow['sizes'][$szH] ?? 0);
                                        }
                                    @endphp
                                    <tr>
                                        <td style="text-align: left; font-weight: bold; padding-left: 4px;">
                                            {{ $varRow['sku'] ?? ($varRow['name'] ?? '—') }}
                                        </td>
                                        @foreach ($sizesHeader as $szH)
                                            <td
                                                style="{{ !empty($varRow['sizes'][$szH]) ? 'color:#dc2626; font-weight:bold;' : '' }}">
                                                {{ $varRow['sizes'][$szH] ?? '' }}
                                            </td>
                                        @endforeach
                                        <td
                                            style="background: #dc2626; color: #fff; font-weight: 900; font-size: 11px;">
                                            {{ $varRow['total'] }}
                                        </td>
                                        <td style="font-weight: bold;">
                                            @php
                                                if ($rowFabQty > 0) {
                                                    $dispQty = number_format($rowFabQty, 2, ',', '.');
                                                    $dispQty = rtrim(rtrim($dispQty, '0'), ',');
                                                    echo $dispQty;
                                                } else {
                                                    echo '—';
                                                }
                                            @endphp
                                        </td>
                                        <td></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ count($sizesHeader) + 4 }}" class="text-center text-muted">
                                            Tidak ada rincian varian produk.</td>
                                    </tr>
                                @endforelse

                                @if (!empty($variantRows))
                                    <tr style="background: #f1f5f9; font-weight: bold; border-top: 2px solid #000;">
                                        <td style="text-align: center; font-weight: 900; background: #e2e8f0;">
                                            TOTAL</td>
                                        @foreach ($sizesHeader as $szH)
                                            <td
                                                style="{{ $sizeTotals[$szH] > 0 ? 'color:#dc2626; font-weight:900;' : '' }}">
                                                {{ $sizeTotals[$szH] > 0 ? $sizeTotals[$szH] : '' }}
                                            </td>
                                        @endforeach
                                        <td
                                            style="background: #dc2626; color: #fff; font-weight: 900; font-size: 11px;">
                                            {{ $grandTotalQty }}
                                        </td>
                                        <td style="font-weight: 900; background: #e2e8f0;">
                                            @php
                                                if ($grandTotalFabric > 0) {
                                                    $dispGQty = number_format($grandTotalFabric, 2, ',', '.');
                                                    $dispGQty = rtrim(rtrim($dispGQty, '0'), ',');
                                                    echo $dispGQty;
                                                } else {
                                                    echo '—';
                                                }
                                            @endphp
                                        </td>
                                        <td style="background: #e2e8f0;"></td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    <!-- Catatan / Keterangan Box -->
                    <div class="catatan-box">
                        <div class="catatan-title">CATATAN / KETERANGAN:</div>
                        <div class="catatan-text">
                            {{ $currentSpk->tambahan ?: '—' }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Separator or Page Break Logic --}}
            @if (!$isEvenSlip)
                <div class="slip-separator"></div>
            @endif

            @if ($isEvenSlip || $globalSlipCount === $totalBlocks * 2)
                </div>
                <!-- END REAL A4 SHEET CONTAINER -->
            @endif
        @endforeach
    @endforeach

</body>

</html>
