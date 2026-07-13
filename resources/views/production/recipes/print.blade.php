<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Formula BOM - {{ $product->name }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Arial, sans-serif; font-size: 11px; color: #000; margin: 20px 30px; line-height: 1.4; }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header-table td { vertical-align: top; }
        .header-left { width: 60%; }
        .header-right { width: 40%; text-align: right; }
        .company-name { font-size: 14px; font-weight: bold; text-transform: uppercase; margin: 0 0 5px 0; }
        .doc-title { font-size: 18px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; margin: 0 0 5px 0; color: #1e293b; }
        
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .info-table td { padding: 4px 0; vertical-align: top; }
        .info-label { font-weight: bold; width: 130px; }
        .info-colon { width: 15px; text-align: center; }
        
        .section-title { font-size: 11px; font-weight: bold; text-transform: uppercase; background: #f1f5f9; padding: 5px 8px; margin: 20px 0 10px 0; border-left: 3px solid #3b82f6; }
        
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .data-table th, .data-table td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
        .data-table th { background: #f8fafc; font-size: 10px; font-weight: bold; text-transform: uppercase; border-bottom: 2px solid #000; }
        .data-table td.text-center { text-align: center; }
        .data-table td.text-end { text-align: right; }
        .data-table th.text-center { text-align: center; }
        .data-table th.text-end { text-align: right; }
        .font-mono { font-family: monospace; }
        
        .summary-box { border: 2px solid #22c55e; background: #f0fdf4; padding: 12px; border-radius: 6px; text-align: center; margin-top: 25px; }
        .summary-title { font-size: 10px; font-weight: bold; color: #15803d; text-transform: uppercase; margin-bottom: 3px; }
        .summary-value { font-size: 20px; font-weight: bold; color: #166534; font-family: monospace; }
        .summary-desc { font-size: 9px; color: #166534; margin-top: 3px; }
        
        .signature-section { width: 100%; margin-top: 40px; border-collapse: collapse; }
        .signature-section td { width: 33%; text-align: center; vertical-align: top; height: 100px; }
        .signature-title { font-weight: bold; margin-bottom: 60px; }
        .signature-line { border-bottom: 1px solid #000; width: 150px; margin: 0 auto 5px auto; }
        
        .btn-print-container { text-align: right; margin-bottom: 20px; }
        .btn-print { padding: 6px 15px; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 11px; }
        
        @media print {
            .no-print { display: none; }
            body { margin: 10mm 15mm; }
        }
    </style>
</head>
<body>

    <div class="btn-print-container no-print">
        <button class="btn-print" onclick="window.print()"><i class="fas fa-print"></i> Cetak Halaman (Print / PDF)</button>
    </div>

    <table class="header-table">
        <tr>
            <td class="header-left">
                <div class="company-name">{{ Auth::user()->tenant->name ?? 'ERP Marketplace' }}</div>
                <div style="font-size: 10px; color: #555;">Sistem Informasi Produksi</div>
            </td>
            <td class="header-right">
                <h1 class="doc-title">Formula Produk (BOM)</h1>
                <div style="font-size: 9px; color: #777;">Dicetak pada: {{ date('d/m/Y H:i') }}</div>
            </td>
        </tr>
    </table>

    <table class="info-table">
        <tr>
            <td class="info-label">Nama Produk Jadi</td>
            <td class="info-colon">:</td>
            <td><strong>{{ $product->name }}</strong></td>
            
            <td class="info-label">Nama Formula</td>
            <td class="info-colon">:</td>
            <td><strong>{{ $recipe->name }}</strong></td>
        </tr>
        <tr>
            <td class="info-label">SKU Produk</td>
            <td class="info-colon">:</td>
            <td class="font-mono"><strong>{{ $product->sku ?: '—' }}</strong></td>
            
            <td class="info-label">Output Batch Standar</td>
            <td class="info-colon">:</td>
            <td><strong>{{ $recipe->batch_qty }} Pcs</strong></td>
        </tr>
    </table>

    <div class="section-title">1. Bahan Baku (Bill of Materials)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 50px;" class="text-center">#</th>
                <th>Bahan Baku</th>
                <th style="width: 120px;" class="text-center">Qty Takaran</th>
                <th style="width: 130px;" class="text-end">Harga Modal Satuan</th>
                <th style="width: 130px;" class="text-end">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @php $materialsCost = 0; @endphp
            @forelse($recipe->items as $idx => $item)
                @php
                    $costPrice = (float)($item->inventoryItem->cost_price ?? 0);
                    $subtotal = $item->quantity * $costPrice;
                    $materialsCost += $subtotal;
                @endphp
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td><strong>{{ $item->inventoryItem->name }}</strong></td>
                    <td class="text-center">{{ number_format($item->quantity, 4, ',', '.') }} {{ $item->inventoryItem->unit }}</td>
                    <td class="text-end font-mono">Rp {{ number_format($costPrice, 0, ',', '.') }}</td>
                    <td class="text-end font-mono" style="font-weight: bold;">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-muted">Tidak ada bahan baku yang terdaftar.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="font-weight: bold; background: #fafafa;">
                <td colspan="4" class="text-end">Total Biaya Bahan Baku:</td>
                <td class="text-end font-mono">Rp {{ number_format($materialsCost, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="section-title">2. Jasa &amp; QC Operasional</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 50px;" class="text-center">#</th>
                <th>Nama Jasa / QC</th>
                <th style="width: 120px;" class="text-center">Kuantitas</th>
                <th style="width: 130px;" class="text-end">Tarif / Biaya</th>
                <th style="width: 130px;" class="text-end">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @php $laborCost = 0; @endphp
            @forelse($recipe->labors as $idx => $labor)
                @php
                    $subtotal = $labor->qty * $labor->unit_cost;
                    $laborCost += $subtotal;
                @endphp
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td><strong>{{ $labor->service_name }}</strong></td>
                    <td class="text-center">{{ $labor->qty }}</td>
                    <td class="text-end font-mono">Rp {{ number_format($labor->unit_cost, 0, ',', '.') }}</td>
                    <td class="text-end font-mono" style="font-weight: bold;">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-muted">Tidak ada jasa atau QC yang terdaftar.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="font-weight: bold; background: #fafafa;">
                <td colspan="4" class="text-end">Total Biaya Jasa &amp; QC:</td>
                <td class="text-end font-mono">Rp {{ number_format($laborCost, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    @php
        $totalCost = $materialsCost + $laborCost;
        $hppPerUnit = $totalCost / ($recipe->batch_qty ?: 1);
    @endphp

    <div class="summary-box">
        <div class="summary-title">Estimasi Total HPP Produksi (Per 1 Pcs)</div>
        <div class="summary-value">Rp {{ number_format($hppPerUnit, 0, ',', '.') }}</div>
        <div class="summary-desc">Dihitung dari Total Biaya (Rp {{ number_format($totalCost, 0, ',', '.') }}) dibagi Output Batch Standar ({{ $recipe->batch_qty }} Pcs)</div>
    </div>

    <table class="signature-section">
        <tr>
            <td>
                <div class="signature-title">Dibuat Oleh,</div>
                <div class="signature-line"></div>
                <div style="font-size: 10px;">Staf Produksi / R&amp;D</div>
            </td>
            <td>
                <div class="signature-title">Diperiksa Oleh,</div>
                <div class="signature-line"></div>
                <div style="font-size: 10px;">Supervisor QC</div>
            </td>
            <td>
                <div class="signature-title">Disetujui Oleh,</div>
                <div class="signature-line"></div>
                <div style="font-size: 10px;">Manajer Produksi</div>
            </td>
        </tr>
    </table>

</body>
</html>
