<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>SPK Perintah Kerja - {{ $spk->no_spk }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Arial, sans-serif; font-size: 11px; color: #000; margin: 10px 25px; line-height: 1.3; }
        .page-header { text-align: right; font-size: 8px; color: #555; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px; }
        
        /* Layout Grid Header */
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .header-table td { vertical-align: top; padding: 0; }
        .header-left { width: 35%; font-size: 11px; }
        .header-center { width: 40%; text-align: center; }
        .header-right { width: 25%; text-align: right; font-size: 11px; }
        
        .spk-title { font-size: 24px; font-weight: 900; letter-spacing: 4px; margin: 0; line-height: 1; }
        .spk-subtitle { font-size: 10px; font-weight: bold; letter-spacing: 2px; margin: 2px 0 0 0; text-transform: uppercase; }
        
        .badge-tipe { font-size: 9px; font-weight: bold; padding: 2px 8px; border-radius: 4px; display: inline-block; margin-top: 4px; text-transform: uppercase; }
        .badge-stok { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
        .badge-klien { background: #dbeafe; color: #1e40af; border: 1px solid #93c5fd; }

        .info-label { font-weight: bold; }
        .info-val-red { color: #d11a2a; font-weight: bold; }
        
        /* Image Box Container */
        .design-box { border: 2px dashed #000; border-radius: 8px; padding: 18px; text-align: center; position: relative; margin-bottom: 15px; min-height: 250px; display: flex; flex-direction: column; justify-content: center; align-items: center; }
        .design-image-container { display: flex; justify-content: center; gap: 20px; margin-top: 8px; width: 100%; }
        .design-image { max-height: 280px; max-width: 90%; object-fit: contain; border: 1px solid #94a3b8; padding: 4px; background: #fff; border-radius: 4px; }
        .design-label { font-size: 12px; font-weight: bold; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 1px; }
        
        /* Bar Pemesan */
        .pemesan-bar { border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 6px 0; font-size: 10px; font-weight: bold; margin-bottom: 12px; }
        
        /* Table Rincian Produk & Common Tables */
        .table-section-title { background: #0f172a; color: #fff; font-size: 10px; font-weight: bold; text-align: left; padding: 5px 8px; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 12px; margin-bottom: 0; }
        .product-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .product-table th, .product-table td { border: 1px solid #000; padding: 5px 6px; text-align: center; font-size: 10px; }
        .product-table th { background: #f1f5f9; font-size: 9px; font-weight: bold; text-transform: uppercase; }
        .product-table td.align-left { text-align: left; font-weight: bold; }
        .bg-red-light { background: #fee2e2; color: #991b1b; font-weight: bold; }
        .bg-gray-light { background: #f8fafc; font-weight: bold; }
        
        /* Accessories & Additional Attributes */
        .attrib-box { border: 1px solid #000; padding: 8px 10px; margin-bottom: 12px; background: #f8fafc; }
        .attrib-title { font-weight: bold; color: #b91c1c; text-transform: uppercase; font-size: 9px; margin-bottom: 3px; letter-spacing: 0.5px; }
        .attrib-content { font-size: 10px; font-weight: bold; }
        
        /* Documentation Checklist */
        .doc-checklist { display: flex; border: 1px solid #000; margin-bottom: 12px; font-weight: bold; font-size: 10px; }
        .doc-title { width: 30%; border-right: 1px solid #000; padding: 5px 8px; background: #f8fafc; }
        .doc-item { width: 35%; padding: 5px 8px; display: flex; align-items: center; justify-content: center; }
        .doc-item:not(:last-child) { border-right: 1px solid #000; }
        .checkbox-square { border: 1px solid #000; width: 11px; height: 11px; margin-right: 6px; display: inline-block; }
        
        /* Signatures Grid */
        .signature-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .signature-table td { border: 1px solid #000; width: 50%; vertical-align: top; padding: 8px 12px; height: 80px; }
        .signature-title { font-weight: bold; text-transform: uppercase; font-size: 9px; margin-bottom: 35px; border-bottom: 1px solid #000; padding-bottom: 4px; }
        
        .page-num { text-align: right; font-size: 9px; color: #555; margin-top: 10px; font-weight: bold; }
        
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
            @page { size: A4; margin: 10mm 12mm; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 12px; padding: 8px 12px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; display: flex; justify-content: space-between; align-items: center;">
        <span style="font-size: 11px; font-weight: bold; color: #334155;">
            💡 Pratinjau Dokumen SPK. Tekan <strong>Ctrl + P</strong> atau klik tombol di samping untuk mencetak.
        </span>
        <button type="button" onclick="window.print()" style="padding: 5px 12px; background: #2563eb; color: #fff; border: none; border-radius: 4px; font-weight: bold; font-size: 11px; cursor: pointer;">
            🖨️ Cetak SPK (Ctrl+P)
        </button>
    </div>
    
    <div class="page-header">Arsip Lembar SPK Kantor</div>

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
                @if(($spk->tipe_spk ?? 'pesanan_pelanggan') === 'stok_gudang')
                    <div class="badge-tipe badge-stok">🏬 PRODUKSI STOK GUDANG</div>
                @else
                    <div class="badge-tipe badge-klien">🛒 PESANAN PELANGGAN</div>
                @endif
            </td>
            <td class="header-right">
                <div><span class="info-label">TANGGAL :</span> <strong>{{ $spk->tanggal ? $spk->tanggal->format('d F Y') : '—' }}</strong></div>
                <div style="margin-top: 3px;"><span class="info-label">JATUH TEMPO :</span> <span class="info-val-red">{{ $spk->deadline ? $spk->deadline->format('d F Y') : '—' }}</span></div>
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
                <div style="color:#64748b; font-size:12px; font-weight:bold; border:2px dashed #cbd5e1; padding: 45px 80px; border-radius: 6px; background:#f8fafc;">
                    📷 TEMPEL GAMBAR DESAIN DI SINI
                </div>
            @endif
        </div>
    </div>

    <!-- Client / Order Bar -->
    <div class="pemesan-bar">
        PEMESAN: {{ strtoupper($spk->pemesan ?: 'INTERNAL STOCK') }} 
        | NO HP PEMESAN: {{ $spk->no_hp_pemesan ?: '—' }}
        | INSTANSI / TOKO: {{ strtoupper($spk->instansi ?: '—') }}
        | PENGINPUT: {{ strtoupper($spk->penginput->name ?? 'SYSTEM') }}
    </div>

    <!-- Layout 2 Kolom (Side-by-Side 50%:50%) untuk Matriks Ukuran & Status Pengambilan Barang -->
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px;">
        <tr>
            <td style="width: 49%; vertical-align: top; padding: 0;">
                <!-- 1. Rincian Produk & Matriks Ukuran -->
                <div class="table-section-title">1. Rincian Produk &amp; Matriks Ukuran</div>
                <table class="product-table" style="margin-bottom: 0;">
                    <thead>
                        <tr>
                            <th rowspan="2" style="width: 40%;">Model Varian</th>
                            <th colspan="{{ count($sizesHeader) }}">Size</th>
                            <th rowspan="2" style="width: 20%;">Total</th>
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
                                    @php $szKey = $sz === 'XXXL' ? '3XL' : $sz; @endphp
                                    <td>
                                        {{ isset($model['sizes'][$szKey]) && $model['sizes'][$szKey] > 0 ? $model['sizes'][$szKey] : '' }}
                                    </td>
                                @endforeach
                                <td class="bg-red-light">{{ $model['total'] }}</td>
                            </tr>
                            @php $grandTotal += $model['total']; @endphp
                        @endforeach
                        <tr class="bg-gray-light">
                            <td colspan="{{ count($sizesHeader) + 1 }}" style="text-align: right; padding-right: 6px;">TOTAL QTY:</td>
                            <td class="bg-red-light" style="font-size: 11px;">{{ $grandTotal }} pcs</td>
                        </tr>
                    </tbody>
                </table>
            </td>
            <td style="width: 2%;"></td>
            <td style="width: 49%; vertical-align: top; padding: 0;">
                <!-- 2. Status Pengambilan Barang (Partial Handover) -->
                <div class="table-section-title">2. Status Pengambilan Barang (Partial Handover)</div>
                <table class="product-table" style="margin-bottom: 0;">
                    <thead>
                        <tr>
                            <th style="width: 40%; text-align: left; padding-left: 6px;">SKU Produk &amp; Ukuran</th>
                            <th style="width: 20%;">Total</th>
                            <th style="width: 20%;">Diambil</th>
                            <th style="width: 20%;">Sisa</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($spk->items as $item)
                            @php
                                $diambil = $item->qty_diambil;
                                $sisa = $item->sisa_qty;
                            @endphp
                            <tr>
                                <td style="text-align: left; font-weight: bold; font-family: monospace; padding-left: 6px;">
                                    {{ $item->sku ?: ($item->sku_induk ?: $item->nama_produk) }} ({{ $item->ukuran ?: 'All Size' }})
                                </td>
                                <td style="font-weight: bold;">{{ $item->quantity }} pcs</td>
                                <td style="font-weight: bold; color: #0284c7;">{{ $diambil }} pcs</td>
                                <td style="font-weight: bold; color: {{ $sisa == 0 ? '#16a34a' : '#dc2626' }};">
                                    {{ $sisa == 0 ? '0 (Selesai)' : $sisa . ' pcs' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <!-- 3. Tracking Progress Tahapan Produksi -->
    @if($spk->proses && $spk->proses->count() > 0)
    <div class="table-section-title">3. Tracking Progress Tahapan Produksi</div>
    <table class="product-table">
        <thead>
            <tr>
                <th style="width: 30%; text-align: left; padding-left: 8px;">SKU Produk &amp; Ukuran</th>
                <th style="width: 12%;">Total Qty</th>
                @foreach($spk->proses as $proses)
                    <th>{{ $proses->nama_proses }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @php
                $prosesTotals = [];
                foreach($spk->proses as $p) { $prosesTotals[$p->id] = 0; }
            @endphp
            @foreach($spk->items as $item)
                <tr>
                    <td style="text-align: left; font-weight: bold; font-family: monospace; padding-left: 8px;">
                        {{ $item->sku ?: ($item->sku_induk ?: $item->nama_produk) }} ({{ $item->ukuran ?: 'All Size' }})
                    </td>
                    <td style="font-weight: bold;">{{ $item->quantity }} pcs</td>
                    @foreach($spk->proses as $proses)
                        @php
                            $pg = $progresMap[$item->id][$proses->id] ?? null;
                            $qtyDone = $pg ? $pg->qty_selesai : 0;
                            $prosesTotals[$proses->id] += $qtyDone;
                        @endphp
                        <td style="font-weight: bold; font-family: monospace;">
                            {{ $qtyDone }} / {{ $item->quantity }}
                        </td>
                    @endforeach
                </tr>
            @endforeach
            <tr class="bg-gray-light">
                <td style="text-align: right; padding-right: 8px;">Total Selesai Tahapan:</td>
                <td style="font-size: 11px;">{{ $spk->items->sum('quantity') }} pcs</td>
                @foreach($spk->proses as $proses)
                    <td style="font-weight: bold; font-family: monospace;">
                        {{ $prosesTotals[$proses->id] ?? 0 }} / {{ $spk->items->sum('quantity') }} pcs
                    </td>
                @endforeach
            </tr>
        </tbody>
    </table>
    @endif

    {{-- Sub-table Riwayat Log Pengambilan (jika ada) --}}
    @php
        $allPickups = collect();
        foreach($spk->items as $it) {
            foreach($it->pickups as $pk) {
                $allPickups->push($pk);
            }
        }
        $allPickups = $allPickups->sortByDesc('tanggal_ambil');
    @endphp
    @if($allPickups->isNotEmpty())
        <div style="font-weight: bold; font-size: 9px; margin-top: 4px; margin-bottom: 4px; text-transform: uppercase;">
            Log Catatan Pengambilan Barang:
        </div>
        <table class="product-table" style="margin-bottom: 10px;">
            <thead>
                <tr>
                    <th style="width: 20%;">Waktu Ambil</th>
                    <th style="width: 25%; text-align: left; padding-left: 6px;">SKU Produk / Ukuran</th>
                    <th style="width: 10%;">Qty</th>
                    <th style="width: 22%;">Yang Mengambil</th>
                    <th style="width: 23%;">Yang Input Data</th>
                </tr>
            </thead>
            <tbody>
                @foreach($allPickups as $pk)
                    <tr>
                        <td style="font-family: monospace;">{{ $pk->tanggal_ambil ? $pk->tanggal_ambil->format('d/m/Y H:i') : '—' }}</td>
                        <td style="text-align: left; font-weight: bold; padding-left: 6px;">
                            {{ $pk->item->sku ?: $pk->item->nama_produk }} ({{ $pk->item->ukuran ?: 'All Size' }})
                        </td>
                        <td style="font-weight: bold; color: #2563eb;">+{{ $pk->qty_diambil }} pcs</td>
                        <td style="font-weight: bold;">{{ $pk->nama_pengambil }}</td>
                        <td>{{ $pk->pemberi->name ?? 'SYSTEM' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif



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

    <div class="page-num">Dokumen Cetak SPK</div>

</body>
</html>
