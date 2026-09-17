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
            font-size: 11px;
            color: #0f172a;
            margin: 0;
            padding: 0;
            background: #cbd5e1;
            line-height: 1.3;
        }

        .no-print-bar {
            margin: 0;
            padding: 12px 24px;
            background: #0f172a;
            color: #fff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
        }

        .btn-print {
            background-color: #2563eb;
            color: #ffffff;
            border: none;
            padding: 8px 18px;
            border-radius: 6px;
            font-weight: 700;
            cursor: pointer;
            font-size: 13px;
            transition: background 0.2s;
        }

        .btn-print:hover {
            background-color: #1d4ed8;
        }

        /* ── Real A4 Paper Sheet Container (297mm Total Height) ── */
        .a4-sheet-container {
            width: 210mm;
            height: 297mm;
            min-height: 297mm;
            max-height: 297mm;
            margin: 20px auto;
            padding: 8mm 10mm;
            background: #ffffff;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.25), 0 8px 10px -6px rgba(0, 0, 0, 0.15);
            border-radius: 4px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
            page-break-after: always;
            break-after: page;
        }

        /* Header Layout */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        .header-table td {
            vertical-align: top;
            padding: 0;
        }

        .header-left {
            width: 30%;
            font-size: 11px;
            font-weight: 700;
        }

        .header-center {
            width: 40%;
            text-align: center;
        }

        .header-right {
            width: 30%;
            text-align: right;
            font-size: 11px;
        }

        .header-qr {
            text-align: center;
            padding-left: 8px;
        }

        .qr-code-img {
            width: 80px;
            height: 80px;
            display: block;
            margin: 0 auto;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 2px;
            background: #ffffff;
            image-rendering: -webkit-optimize-contrast;
            image-rendering: crisp-edges;
        }

        .spk-title-main {
            font-size: 28px;
            font-weight: 900;
            letter-spacing: 6px;
            line-height: 1;
            margin: 0;
            color: #000;
        }

        .spk-sub-main {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #1e293b;
            margin-top: 2px;
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
            height: 3px;
            background: #000;
            margin-bottom: 8px;
        }

        /* Pemesan & Admin Bar */
        .pemesan-info-bar {
            border-top: 1.5px dashed #475569;
            border-bottom: 1.5px dashed #475569;
            padding: 6px 0;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 8px;
            color: #0f172a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Mockup / Image Frame Box (GAMBAR DESAIN) */
        .design-box-frame {
            border: 1.5px dashed #64748b;
            border-radius: 6px;
            padding: 4px;
            text-align: center;
            margin-bottom: 0;
            background: #fff;
            height: 440px;
            max-height: 480px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            width: 100%;
        }

        .design-img {
            width: 100%;
            height: 100%;
            max-height: 460px;
            object-fit: contain;
        }

        .design-placeholder-text {
            color: #94a3b8;
            font-size: 13px;
            font-weight: 700;
            border: 1px dashed #cbd5e1;
            padding: 30px;
            border-radius: 6px;
            background: #f8fafc;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Tables & Section Banners */
        .banner-blue {
            background: #2563eb;
            color: #fff;
            font-size: 10.5px;
            font-weight: 800;
            text-align: center;
            padding: 4px 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-radius: 4px 4px 0 0;
        }

        .banner-slate {
            background: #475569;
            color: #fff;
            font-size: 10.5px;
            font-weight: 800;
            text-align: center;
            padding: 4px 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-radius: 4px 4px 0 0;
        }

        .grid-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .grid-table th,
        .grid-table td {
            border: 1px solid #000;
            padding: 4px 5px;
            text-align: center;
            font-size: 10.5px;
        }

        .grid-table th {
            background: #fff;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 10px;
        }

        /* Catatan / Keterangan Box */
        .catatan-box {
            border: 1.5px solid #000;
            border-radius: 6px;
            padding: 6px 10px;
            background: #fff;
            margin-top: 4px;
            min-height: 45px;
        }

        .catatan-title {
            font-weight: 800;
            font-size: 9.5px;
            text-transform: uppercase;
            color: #0f172a;
            margin-bottom: 2px;
        }

        .catatan-text {
            font-size: 11px;
            font-weight: 700;
            color: #0f172a;
        }

        @media print {

            .no-print,
            .no-print-bar {
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
                padding: 8mm 10mm !important;
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

    <!-- Top Control Bar (Screen Only) -->
    <div class="no-print-bar no-print">
        <div>
            <strong>🖨️ Cetak SPK Perintah Kerja</strong> — 1 SPK Full Lembar A4
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <button onclick="window.print()" class="btn-print">
                🖨️ Cetak Halaman Ini
            </button>
            <button onclick="window.close()"
                style="background: #475569; color: #fff; border: none; padding: 8px 14px; border-radius: 6px; cursor: pointer; font-weight: 600;">
                Tutup
            </button>
        </div>
    </div>

    @foreach ($spkBlocks as $bIdx => $block)
        @php
            $currentSpk = $block['spk'];
            $variantRows = $block['variantRows'];
            $bazaItems = $block['bazaItems'];
            $firstVarName = !empty($variantRows) ? array_key_first($variantRows) : 'MODEL VARIAN';
        @endphp

        <!-- START REAL A4 SHEET CONTAINER (1 SPK FULL A4) -->
        <div class="a4-sheet-container">
            <div>
                <!-- Header Grid -->
                <table class="header-table">
                    <tr>
                        <td class="header-left">
                            <div><span style="color:#475569;">NO PRODUKSI :</span> <span
                                    class="val-mono">{{ $currentSpk->no_produksi ?: '—' }}</span></div>
                            <div style="margin-top: 4px;"><span style="color:#475569;">NO PESANAN :</span> <span
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
                            <div style="margin-top: 4px;"><span style="color:#475569;">DEADLINE :</span> <span
                                    class="text-danger fw-bold">{{ $currentSpk->deadline ? $currentSpk->deadline->format('Y-m-d') : '—' }}</span>
                            </div>
                        </td>
                        <td class="header-qr">
                            @php
                                $spkTrackUrl = route('spks.mobile_scan', $currentSpk->id);
                            @endphp
                            <a href="{{ $spkTrackUrl }}" target="_blank" title="Scan / Update Tracking SPK">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&margin=0&ecc=M&data={{ urlencode($spkTrackUrl) }}"
                                    alt="QR Tracking SPK" class="qr-code-img">
                            </a>
                            <div
                                style="font-size: 7.5px; text-align: center; color: #0f172a; font-weight: 800; margin-top: 2px; line-height: 1; letter-spacing: 0.3px;">
                                SCAN TRACKING
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

                <!-- GAMBAR DESAIN / MOCKUP -->
                <div style="margin-bottom: 8px;">
                    <div class="banner-slate">
                        🖼️ GAMBAR DESAIN / MOCKUP
                    </div>
                    <div class="design-box-frame">
                        @php
                            $imgSrc =
                                $currentSpk->mockup_url ?: ($currentSpk->image_url ?: $currentSpk->referensi_klien_url);
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

                <!-- RINCIAN VARIAN PRODUK & KEBUTUHAN KAIN -->
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
                <div style="margin-bottom: 8px;">
                    <div class="banner-blue">
                        RINCIAN VARIAN PRODUK &amp; KEBUTUHAN KAIN
                    </div>
                    <table class="grid-table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th rowspan="2" style="width: 25%;">Model Varian</th>
                                <th colspan="{{ count($sizesHeader) }}">Size Target / Potong</th>
                                <th rowspan="2" style="width: 10%; background: #dc2626; color: #fff;">Total QTY</th>
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
                                    <td style="text-align: left; font-weight: bold; padding-left: 6px;">
                                        {{ $varRow['sku'] ?? ($varRow['name'] ?? '—') }}
                                    </td>
                                    @foreach ($sizesHeader as $szH)
                                        <td
                                            style="{{ !empty($varRow['sizes'][$szH]) ? 'color:#dc2626; font-weight:bold;' : '' }}">
                                            {{ $varRow['sizes'][$szH] ?? '' }}
                                        </td>
                                    @endforeach
                                    <td style="background: #dc2626; color: #fff; font-weight: 900; font-size: 11px;">
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
                                        Tidak ada rincian varian produk.
                                    </td>
                                </tr>
                            @endforelse

                            @if (!empty($variantRows))
                                <tr style="background: #f1f5f9; font-weight: bold; border-top: 2px solid #000;">
                                    <td style="text-align: center; font-weight: 900; background: #e2e8f0;">TOTAL</td>
                                    @foreach ($sizesHeader as $szH)
                                        <td
                                            style="{{ $sizeTotals[$szH] > 0 ? 'color:#dc2626; font-weight:900;' : '' }}">
                                            {{ $sizeTotals[$szH] > 0 ? $sizeTotals[$szH] : '' }}
                                        </td>
                                    @endforeach
                                    <td style="background: #dc2626; color: #fff; font-weight: 900; font-size: 11px;">
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
    @endforeach

</body>

</html>
