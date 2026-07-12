<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>SPK Perintah Kerja - {{ $spk->no_spk }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Arial, sans-serif; font-size: 11px; color: #000; margin: 10px 25px; line-height: 1.3; }
        .page-header { text-align: right; font-size: 8px; color: #555; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px; }
        
        /* Layout Grid Header */
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .header-table td { vertical-align: top; padding: 0; }
        .header-left { width: 35%; font-size: 11px; }
        .header-center { width: 40%; text-align: center; }
        .header-right { width: 25%; text-align: right; font-size: 11px; }
        
        .spk-title { font-size: 24px; font-weight: 900; letter-spacing: 4px; margin: 0; line-height: 1; }
        .spk-subtitle { font-size: 10px; font-weight: bold; letter-spacing: 2px; margin: 2px 0 0 0; text-transform: uppercase; }
        
        .info-label { font-weight: bold; }
        .info-val-red { color: #d11a2a; font-weight: bold; }
        
        /* Image Box Container */
        .design-box { border: 2px dashed #000; border-radius: 8px; padding: 15px; text-align: center; position: relative; margin-bottom: 15px; min-height: 180px; display: flex; flex-direction: column; justify-content: center; align-items: center; }
        .design-image-container { display: flex; justify-content: center; gap: 20px; margin-top: 10px; }
        .design-image { max-height: 140px; max-width: 140px; object-fit: contain; border: 1px solid #ddd; padding: 3px; background: #fff; }
        .design-label { font-size: 11px; font-weight: bold; margin-bottom: 5px; text-transform: uppercase; }
        
        /* Bar Pemesan */
        .pemesan-bar { border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 6px 0; font-size: 10px; font-weight: bold; margin-bottom: 15px; }
        
        /* Table Rincian Produk */
        .table-title { background: #1e293b; color: #fff; font-size: 10px; font-weight: bold; text-align: center; padding: 5px; text-transform: uppercase; letter-spacing: 1px; }
        .product-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .product-table th, .product-table td { border: 1px solid #000; padding: 6px; text-align: center; }
        .product-table th { background: #f1f5f9; font-size: 9px; font-weight: bold; text-transform: uppercase; }
        .product-table td.align-left { text-align: left; font-weight: bold; }
        .bg-red-light { background: #fee2e2; color: #991b1b; font-weight: bold; }
        
        /* Accessories & Additional Attributes */
        .attrib-box { border: 1px solid #000; padding: 10px; margin-bottom: 15px; background: #f8fafc; }
        .attrib-title { font-weight: bold; color: #b91c1c; text-transform: uppercase; font-size: 9px; margin-bottom: 5px; letter-spacing: 0.5px; }
        .attrib-content { font-size: 11px; font-weight: bold; }
        
        /* Documentation Checklist */
        .doc-checklist { display: flex; border: 1px solid #000; margin-bottom: 15px; font-weight: bold; }
        .doc-title { width: 30%; border-right: 1px solid #000; padding: 6px 10px; background: #f8fafc; }
        .doc-item { width: 35%; padding: 6px 10px; display: flex; align-items: center; justify-content: center; }
        .doc-item:not(:last-child) { border-right: 1px solid #000; }
        .checkbox-square { border: 1px solid #000; width: 12px; height: 12px; margin-right: 8px; display: inline-block; }
        
        /* Signatures Grid */
        .signature-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .signature-table td { border: 1px solid #000; width: 50%; vertical-align: top; padding: 10px 15px; height: 90px; }
        .signature-title { font-weight: bold; text-transform: uppercase; font-size: 9px; margin-bottom: 40px; border-bottom: 1px solid #000; padding-bottom: 4px; }
        
        .page-num { text-align: right; font-size: 9px; color: #555; margin-top: 15px; font-weight: bold; }
        
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
            @page { size: A4; margin: 12mm 15mm; }
        }
    </style>
</head>
<body>
    
    <div class="page-header">Halaman 3: Arsip Kantor</div>

    <!-- Header Grid -->
    <table class="header-table">
        <tr>
            <td class="header-left">
                <div><span class="info-label">NO PRODUKSI :</span> <span style="font-family:monospace; font-weight:bold;">{{ $spk->no_produksi ?: '—' }}</span></div>
                <div style="margin-top: 3px;"><span class="info-label">NO PESANAN :</span> <span style="font-family:monospace; font-weight:bold;">{{ $spk->no_spk }}</span></div>
            </td>
            <td class="header-center">
                <h1 class="spk-title">S P K</h1>
                <div class="spk-subtitle">Surat Perintah Kerja</div>
            </td>
            <td class="header-right">
                <div><span class="info-label">TANGGAL :</span> <strong>{{ $spk->tanggal ? $spk->tanggal->format('Y-m-d') : '—' }}</strong></div>
                <div style="margin-top: 3px;"><span class="info-label">JATUH TEMPO :</span> <span class="info-val-red">{{ $spk->deadline ? $spk->deadline->format('Y-m-d') : '—' }}</span></div>
            </td>
            <td style="width: 60px; padding-left: 15px;">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=55x55&data={{ $spk->no_spk }}" alt="QR Code" style="display:block;">
            </td>
        </tr>
    </table>

    <!-- Design Box -->
    <div class="design-box">
        <div class="design-label">Desain Model / Bordir Logo</div>
        <div class="design-image-container">
            @if($spk->image_url)
                <img class="design-image" src="{{ $spk->image_url }}" alt="Desain">
            @else
                <div style="color:#777; font-size:10px; border:1px dashed #ccc; padding: 25px 40px; border-radius: 4px; background:#fafafa;">
                    TEMPEL GAMBAR DESAIN DI SINI
                </div>
            @endif
        </div>
    </div>

    <!-- Client / Order Bar -->
    <div class="pemesan-bar">
        PEMESAN: {{ strtoupper($spk->pemesan ?: 'INTERNAL STOCK') }} 
        | NO HP PEMESAN: {{ $spk->no_hp_pemesan ?: '—' }}
        | INSTANSI: {{ strtoupper($spk->instansi ?: '—') }}
        | PENGINPUT: {{ strtoupper($spk->penginput->name ?? 'SYSTEM') }}
    </div>

    <!-- Rincian Produk Title -->
    <div class="table-title">Arsip Kantor: Rincian Produk &amp; Pembagian Kerja</div>

    <!-- Grid Size Table -->
    <table class="product-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 25%;">Model Varian</th>
                <th colspan="{{ count($sizesHeader) }}">Size</th>
                <th rowspan="2" style="width: 10%;">Total QTY</th>
                <th rowspan="2" style="width: 20%;">Tukang Jahit</th>
                <th rowspan="2" style="width: 20%;">Barang Sudah Ada Di Kantor</th>
            </tr>
            <tr>
                @foreach($sizesHeader as $sz)
                    <th>{{ $sz }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @php $grandTotal = 0; @endphp
            @foreach($grouped as $model)
                <tr>
                    <td class="align-left">{{ $model['model'] }}</td>
                    @foreach($sizesHeader as $sz)
                        <td>
                            {{ isset($model['sizes'][$sz]) && $model['sizes'][$sz] > 0 ? $model['sizes'][$sz] : '' }}
                        </td>
                    @endforeach
                    <td class="bg-red-light">{{ $model['total'] }}</td>
                    <td style="font-family: monospace; font-size: 10px;">{{ $model['tailors_list'] }}</td>
                    <td></td>
                </tr>
                @php $grandTotal += $model['total']; @endphp
            @endforeach
        </tbody>
    </table>

    <!-- HPP Production Details (Office Only) -->
    <div style="font-weight: bold; font-size: 10px; margin-top: 15px; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px;">
        Rincian HPP Produksi (Internal Office Only):
    </div>
    <table class="product-table">
        <thead>
            <tr>
                <th style="width: 25%; text-align: left; padding-left: 8px;">Nama Produk &amp; Size</th>
                <th style="width: 15%;">Alur Kerja</th>
                <th style="width: 15%;">Tukang Jahit</th>
                <th style="width: 10%;">Bahan / pcs</th>
                <th style="width: 10%;">Jasa / pcs</th>
                <th style="width: 10%;">HPP / Pcs</th>
                <th style="width: 5%;">Qty</th>
                <th style="width: 10%;">Subtotal HPP</th>
            </tr>
        </thead>
        <tbody>
            @php $grandTotalHpp = 0; @endphp
            @foreach($spk->items as $item)
                @php
                    $subtotal = $item->hpp * $item->quantity;
                    $grandTotalHpp += $subtotal;

                    $totalBahan = 0;
                    $totalJasa = 0;
                    foreach($item->extras as $ex) {
                        $desc = strtolower($ex->keterangan);
                        if (str_starts_with($desc, 'bahan:') || str_contains($desc, 'bahan')) {
                            $totalBahan += (float)$ex->nominal;
                        } else {
                            $totalJasa += (float)$ex->nominal;
                        }
                    }
                @endphp
                <tr>
                    <td style="text-align: left; font-weight: bold; padding-left: 8px;">{{ $item->nama_produk }} ({{ $item->ukuran ?: 'All Size' }})</td>
                    <td>{{ $item->alur_proses ?: 'Langsung Jahit' }}</td>
                    <td>{{ $item->penjahit ?: '—' }}</td>
                    <td>Rp {{ number_format($totalBahan, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($totalJasa, 0, ',', '.') }}</td>
                    <td class="bg-red-light">Rp {{ number_format($item->hpp, 0, ',', '.') }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td style="font-weight: bold;">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr style="background: #f1f5f9; font-weight: bold;">
                <td colspan="6" style="text-align: right; padding-right: 10px;">Total HPP Produksi SPK:</td>
                <td>{{ $spk->items->sum('quantity') }}</td>
                <td>Rp {{ number_format($grandTotalHpp, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Additional Attributes -->
    <div class="attrib-box">
        <div class="attrib-title">Atribut &amp; Aksesoris Tambahan:</div>
        <div class="attrib-content">
            @if($spk->tambahan)
                {!! nl2br(e($spk->tambahan)) !!}
            @else
                Tidak ada aksesoris tambahan.
            @endif
        </div>
    </div>

    <!-- Documentation Checklist -->
    <div class="doc-checklist">
        <div class="doc-title">BUKTI DOKUMENTASI KLIEN :</div>
        <div class="doc-item">
            <span class="checkbox-square"></span> SUDAH FOTO
        </div>
        <div class="doc-item">
            <span class="checkbox-square"></span> SUDAH VIDEO
        </div>
    </div>

    <!-- Signatures -->
    <table class="signature-table">
        <tr>
            <td>
                <div class="signature-title">Paraf QC / Gudang</div>
                <div style="font-size:10px; color:#777;">( .................................... )</div>
            </td>
            <td>
                <div class="signature-title">Project Selesai</div>
                <div style="font-size:10px; color:#777; font-weight:bold;">( Paraf / Cap Tim Marketing )</div>
            </td>
        </tr>
    </table>

    <div class="page-num">3/3</div>

    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>
