<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak SPK Perintah Kerja - {{ $spk->no_produksi ?: $spk->no_spk }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 5mm 8mm;
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
            background: #fff;
            line-height: 1.2;
        }

        .no-print {
            margin: 10px 15px;
            padding: 10px 16px;
            background: #1e293b;
            color: #fff;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
        }

        /* ── Half A4 Slip Card ── */
        .spk-slip-card {
            width: 100%;
            height: 135mm;
            max-height: 137mm;
            padding: 3px 4px;
            position: relative;
            overflow: hidden;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .slip-separator {
            border-top: 1.5px dashed #94a3b8;
            margin: 3mm 0;
            width: 100%;
        }

        .page-break {
            page-break-after: always;
            break-after: always;
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
            width: 32%;
            font-size: 9px;
            font-weight: 700;
        }

        .header-center {
            width: 36%;
            text-align: center;
        }

        .header-right {
            width: 24%;
            text-align: right;
            font-size: 9px;
        }

        .header-qr {
            width: 8%;
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

        /* Mockup / Image Frame Box */
        .design-box-frame {
            border: 1.5px dashed #64748b;
            border-radius: 8px;
            padding: 4px;
            text-align: center;
            margin-bottom: 4px;
            background: #fff;
            height: 105px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .design-img {
            max-height: 98px;
            max-width: 95%;
            object-fit: contain;
        }

        .design-placeholder-text {
            color: #94a3b8;
            font-size: 10px;
            font-weight: 700;
            border: 1px dashed #cbd5e1;
            padding: 25px 40px;
            border-radius: 6px;
            background: #f8fafc;
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

        .grid-table th, .grid-table td {
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
            .no-print { display: none !important; }
            body { margin: 0; padding: 0; }
        }
    </style>
</head>
<body onload="window.print()">

    @php
        $globalSlipCount = 0;
        $totalBlocks = count($spkBlocks);
    @endphp

    @foreach($spkBlocks as $bIdx => $block)
        @php
            $currentSpk  = $block['spk'];
            $variantRows = $block['variantRows'];
            $bazaItems   = $block['bazaItems'];
            $firstVarName = !empty($variantRows) ? array_key_first($variantRows) : 'MODEL VARIAN';
        @endphp

        {{-- Print 2 Copies per SPK (Lembar 1: Tim Produksi, Lembar 2: Arsip Kantor/Finishing) --}}
        @foreach([1, 2] as $copyNum)
            @php
                $globalSlipCount++;
                $isEvenSlip = ($globalSlipCount % 2 === 0);
            @endphp

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
                                <div><span style="color:#475569;">NO PRODUKSI :</span> <span class="val-mono">{{ $currentSpk->no_produksi ?: '—' }}</span></div>
                                <div style="margin-top: 2px;"><span style="color:#475569;">NO PESANAN :</span> <span class="val-mono">{{ $currentSpk->no_spk }}</span></div>
                            </td>
                            <td class="header-center">
                                <h1 class="spk-title-main">S P K</h1>
                                <div class="spk-sub-main">SURAT PERINTAH KERJA</div>
                            </td>
                            <td class="header-right">
                                <div><span style="color:#475569;">ORDER DATE :</span> <strong>{{ $currentSpk->tanggal ? $currentSpk->tanggal->format('Y-m-d') : date('Y-m-d') }}</strong></div>
                                <div style="margin-top: 2px;"><span style="color:#475569;">DEADLINE :</span> <span class="text-danger fw-bold">{{ $currentSpk->deadline ? $currentSpk->deadline->format('Y-m-d') : '—' }}</span></div>
                            </td>
                            <td class="header-qr">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=48x48&data={{ $currentSpk->no_spk }}" alt="QR" style="width: 40px; height: 40px; display:block;">
                            </td>
                        </tr>
                    </table>

                    <div class="header-divider-bar"></div>

                    <!-- Design Box Frame -->
                    <div class="design-box-frame">
                        @php
                            $imgSrc = $currentSpk->mockup_url ?: ($currentSpk->image_url ?: $currentSpk->referensi_klien_url);
                        @endphp
                        @if($imgSrc)
                            <img src="{{ $imgSrc }}" class="design-img" alt="Desain SPK">
                        @else
                            <div class="design-placeholder-text">
                                👕 TEMPEL GAMBAR DESAIN / MOCKUP DI SINI
                            </div>
                        @endif
                    </div>

                    <!-- Client & Admin Bar -->
                    <div class="pemesan-info-bar">
                        PEMESAN: {{ strtoupper($currentSpk->pemesan ?: 'INTERNAL / STOK GUDANG') }}
                        @if($currentSpk->no_hp_pemesan) ({{ $currentSpk->no_hp_pemesan }}) @endif
                        | INSTANSI: {{ strtoupper($currentSpk->instansi ?: '—') }}
                        | ADMIN: {{ strtoupper($currentSpk->nama_pic ?: ($currentSpk->penginput->name ?? 'SYSTEM')) }}
                    </div>

                    <!-- 1. Table Rincian Varian Produk -->
                    <div class="banner-blue">
                        RINCIAN VARIAN PRODUK ({{ strtoupper($firstVarName) }})
                    </div>
                    <table class="grid-table">
                        <thead>
                            <tr>
                                <th rowspan="2" style="width: 26%;">Model Varian</th>
                                <th colspan="6">Size Target / Potong</th>
                                <th rowspan="2" style="width: 10%; background: #dc2626; color: #fff;">Total QTY</th>
                                <th colspan="2">Quality Control</th>
                                <th rowspan="2" style="width: 16%;">Finishing / Packing</th>
                            </tr>
                            <tr>
                                <th style="width: 6.5%;">S</th>
                                <th style="width: 6.5%;">M</th>
                                <th style="width: 6.5%;">L</th>
                                <th style="width: 6.5%;">XL</th>
                                <th style="width: 6.5%;">XXL</th>
                                <th style="width: 6.5%;">3XL</th>
                                <th style="width: 9%;">Lolos</th>
                                <th style="width: 9%;">Reject</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($variantRows as $varRow)
                                <tr>
                                    <td style="text-align: left; font-weight: bold; padding-left: 4px;">{{ $varRow['name'] }}</td>
                                    <td style="{{ !empty($varRow['sizes']['S']) ? 'color:#dc2626; font-weight:bold;' : '' }}">{{ $varRow['sizes']['S'] ?? '' }}</td>
                                    <td style="{{ !empty($varRow['sizes']['M']) ? 'color:#dc2626; font-weight:bold;' : '' }}">{{ $varRow['sizes']['M'] ?? '' }}</td>
                                    <td style="{{ !empty($varRow['sizes']['L']) ? 'color:#dc2626; font-weight:bold;' : '' }}">{{ $varRow['sizes']['L'] ?? '' }}</td>
                                    <td style="{{ !empty($varRow['sizes']['XL']) ? 'color:#dc2626; font-weight:bold;' : '' }}">{{ $varRow['sizes']['XL'] ?? '' }}</td>
                                    <td style="{{ !empty($varRow['sizes']['XXL']) ? 'color:#dc2626; font-weight:bold;' : '' }}">{{ $varRow['sizes']['XXL'] ?? '' }}</td>
                                    <td style="{{ !empty($varRow['sizes']['3XL']) ? 'color:#dc2626; font-weight:bold;' : '' }}">{{ $varRow['sizes']['3XL'] ?? '' }}</td>
                                    <td style="background: #dc2626; color: #fff; font-weight: 900; font-size: 11px;">
                                        {{ $varRow['total'] }}
                                    </td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center text-muted">Tidak ada rincian varian produk.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>



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
            @if(!$isEvenSlip)
                <div class="slip-separator"></div>
            @else
                <div class="page-break"></div>
            @endif

        @endforeach
    @endforeach

</body>
</html>
