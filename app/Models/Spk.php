<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Spk extends Model
{
    protected $fillable = [
        'tenant_id',
        'order_id',
        'no_produksi',
        'no_pesanan',
        'no_spk',
        'tipe_spk',
        'kategori',
        'is_urgent',
        'tahap_saat_ini',
        'tanggal',
        'deadline',
        'pemesan',
        'no_hp_pemesan',
        'instansi',
        'nama_pic',
        'tambahan',
        'image_url',
        'referensi_klien_url',
        'mockup_url',
        'link_file_mentah',
        'sku_kain',
        'penginput_id',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'deadline' => 'date',
        'is_urgent' => 'boolean',
    ];

    /** List of available production stages */
    public const TAHAPAN = [
        'Perencanaan'             => ['label' => 'Perencanaan',                 'emoji' => '📋', 'color' => 'info'],
        'Antrian & Sampling'      => ['label' => 'Antrian & Sampling',           'emoji' => '⏳', 'color' => 'primary'],
        'Tahap Sampling'          => ['label' => 'Tahap Sampling',               'emoji' => '🧪', 'color' => 'purple'],
        'Tahap Print Kain'        => ['label' => 'Tahap Print Kain',             'emoji' => '🖨️', 'color' => 'info'],
        'Tahap Pemotongan'        => ['label' => 'Tahap Pemotongan',             'emoji' => '✂️', 'color' => 'warning'],
        'Tahap Jahit'             => ['label' => 'Tahap Jahit',                  'emoji' => '🪡', 'color' => 'purple'],
        'Tahap LKPK'              => ['label' => 'Tahap LKPK',                   'emoji' => '🧮', 'color' => 'success'],
        'Quality Control'         => ['label' => 'Quality Control',              'emoji' => '🔍', 'color' => 'success'],
        'Packing / Finishing'     => ['label' => 'Packing / Finishing',          'emoji' => '📦', 'color' => 'warning'],
        'Selesai (Finished Good)' => ['label' => 'Selesai (Finished Good)',      'emoji' => '✅', 'color' => 'success'],
    ];

    public function getCurrentStageNameAttribute(): string
    {
        if (!empty($this->tahap_saat_ini)) {
            return strtoupper($this->tahap_saat_ini);
        }

        if ($this->relationLoaded('proses') && $this->proses->isNotEmpty()) {
            if ($this->relationLoaded('items') && $this->items->isNotEmpty()) {
                $totalPcs = (int) $this->items->sum('quantity');

                if ($totalPcs > 0) {
                    // Check total qty_done across all processes for all items
                    $totalQtyDoneAcrossAllProses = 0;
                    foreach ($this->items as $item) {
                        if ($item->relationLoaded('progres') && $item->progres->isNotEmpty()) {
                            $totalQtyDoneAcrossAllProses += (int) $item->progres->sum('qty_done');
                        }
                    }

                    // If zero progress has been logged yet, return initial tahap_saat_ini
                    if ($totalQtyDoneAcrossAllProses === 0) {
                        if (!empty($this->tahap_saat_ini)) {
                            return strtoupper($this->tahap_saat_ini);
                        }
                        return 'PERANCANGAN PRODUKSI (SPK)';
                    }

                    foreach ($this->proses as $p) {
                        $qtyDoneForProses = 0;
                        foreach ($this->items as $item) {
                            if ($item->relationLoaded('progres') && $item->progres->isNotEmpty()) {
                                $pg = $item->progres->firstWhere('spk_proses_id', $p->id);
                                if ($pg) {
                                    $qtyDoneForProses += (int) $pg->qty_done;
                                }
                            }
                        }

                        // First stage that is not 100% completed across all items is the active stage
                        if ($qtyDoneForProses < $totalPcs) {
                            return strtoupper($p->nama_proses);
                        }
                    }

                    // All stages are 100% completed
                    return 'SELESAI';
                }
            } else {
                // If items/progres relations are not preloaded, perform fallback check
                $totalPcs = (int) $this->items()->sum('quantity');
                if ($totalPcs > 0) {
                    $itemIds = $this->items()->pluck('id');
                    $totalQtyDoneAcrossAllProses = (int) \App\Models\SpkItemProgres::whereIn('spk_item_id', $itemIds)->sum('qty_done');

                    if ($totalQtyDoneAcrossAllProses === 0) {
                        if (!empty($this->tahap_saat_ini)) {
                            return strtoupper($this->tahap_saat_ini);
                        }
                        return 'PERANCANGAN PRODUKSI (SPK)';
                    }

                    foreach ($this->proses as $p) {
                        $qtyDoneForProses = (int) \App\Models\SpkItemProgres::whereIn('spk_item_id', $itemIds)
                            ->where('spk_proses_id', $p->id)
                            ->sum('qty_done');

                        if ($qtyDoneForProses < $totalPcs) {
                            return strtoupper($p->nama_proses);
                        }
                    }
                    return 'SELESAI';
                }
            }

            // Fallback if totalPcs is 0
            if (!empty($this->tahap_saat_ini)) {
                return strtoupper($this->tahap_saat_ini);
            }
            return strtoupper($this->proses->first()->nama_proses);
        }

        return 'PERANCANGAN PRODUKSI (SPK)';
    }

    public function getTotalPcsAttribute(): int
    {
        if ($this->relationLoaded('items')) {
            return (int) $this->items->sum('quantity');
        }
        return (int) $this->items()->sum('quantity');
    }

    public function getVariantSummaryAttribute(): string
    {
        if (!$this->relationLoaded('items') || $this->items->isEmpty()) {
            return '—';
        }
        $sizes = $this->items->pluck('ukuran')->filter()->unique()->implode(', ');
        if (!empty($sizes)) {
            return strtoupper($sizes);
        }
        $firstSku = $this->items->first()->sku ?? $this->items->first()->sku_induk;
        return $firstSku ? strtoupper($firstSku) : 'STANDARD';
    }

    /**
     * Accessor for status (alias for tahap_saat_ini or current stage name).
     */
    public function getStatusAttribute(): ?string
    {
        return $this->attributes['tahap_saat_ini'] ?? $this->current_stage_name;
    }

    /**
     * Mutator for status (maps assignment of status to tahap_saat_ini DB column).
     */
    public function setStatusAttribute($value): void
    {
        $this->attributes['tahap_saat_ini'] = $value;
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function penginput(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penginput_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SpkItem::class);
    }

    public function proses(): HasMany
    {
        return $this->hasMany(SpkProses::class)->orderBy('urutan');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SpkPayment::class)->orderByDesc('payment_date')->orderByDesc('id');
    }

    /**
     * Total target ongkos jasa / biaya produksi SPK (tidak termasuk bahan).
     */
    public function getTotalBiayaProduksiAttribute(): float
    {
        if ($this->relationLoaded('items')) {
            $total = 0;
            foreach ($this->items as $item) {
                if ($item->relationLoaded('extras')) {
                    foreach ($item->extras as $extra) {
                        $nom = (float) $extra->nominal;
                        $ket = $extra->keterangan ?? '';
                        if ($nom > 0 && !str_contains($ket, 'Bahan:')) {
                            $total += $nom;
                        }
                    }
                } else {
                    $total += (float) $item->extras()
                        ->where('keterangan', 'not like', '%Bahan:%')
                        ->sum('nominal');
                }
            }
            return $total;
        }

        return (float) \App\Models\SpkItemExtra::whereIn(
            'spk_item_id',
            $this->items()->pluck('id')
        )->where('keterangan', 'not like', '%Bahan:%')->sum('nominal');
    }

    /**
     * Akumulasi nominal cicilan yang sudah dibayarkan untuk produksi SPK ini.
     */
    public function getTotalPaidProductionAttribute(): float
    {
        if ($this->relationLoaded('payments')) {
            return (float) $this->payments->sum('amount');
        }
        return (float) $this->payments()->sum('amount');
    }

    /**
     * Sisa tagihan biaya produksi yang belum dibayar.
     */
    public function getRemainingProductionCostAttribute(): float
    {
        return max(0, $this->total_biaya_produksi - $this->total_paid_production);
    }

    /**
     * Persentase kelunasan pembayaran produksi (0 - 100%).
     */
    public function getProductionPaymentPercentageAttribute(): float
    {
        $total = $this->total_biaya_produksi;
        if ($total <= 0) {
            return 100.0;
        }
        return round(min(100, ($this->total_paid_production / $total) * 100), 1);
    }

    /**
     * Status kode kelunasan pembayaran produksi ('none', 'unpaid', 'partial', 'paid').
     */
    public function getProductionPaymentStatusAttribute(): string
    {
        $target = $this->total_biaya_produksi;
        $paid = $this->total_paid_production;

        if ($target <= 0) {
            return 'none';
        }
        if ($paid >= $target) {
            return 'paid';
        }
        if ($paid > 0) {
            return 'partial';
        }
        return 'unpaid';
    }

    /**
     * Label status kelunasan pembayaran produksi ('Lunas', 'Dicicil', 'Belum Bayar').
     */
    public function getProductionPaymentStatusLabelAttribute(): string
    {
        return match ($this->production_payment_status) {
            'paid'    => 'Lunas',
            'partial' => 'Dicicil',
            'unpaid'  => 'Belum Bayar',
            default   => 'Biaya Belum Diset',
        };
    }

    /**
     * HTML Badge untuk status pembayaran produksi.
     */
    public function getProductionPaymentStatusBadgeAttribute(): string
    {
        return match ($this->production_payment_status) {
            'paid'    => '<span class="badge bg-success-subtle text-success border border-success-subtle fw-bold"><i class="fas fa-check-circle me-1"></i>Lunas</span>',
            'partial' => '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fw-bold"><i class="fas fa-clock me-1"></i>Dicicil</span>',
            'unpaid'  => '<span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold"><i class="fas fa-exclamation-circle me-1"></i>Belum Bayar</span>',
            default   => '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle fw-bold">-</span>',
        };
    }

    public static function generateNoSpk()
    {
        $today = date('Ymd');
        $count = self::where('no_spk', 'like', "SPK-{$today}-%")->count();
        return 'SPK-' . $today . '-' . sprintf('%04d', $count + 1);
    }

    public static function generateNoProduksi()
    {
        $prefix = 'JN' . date('ym');
        $latest = self::where('no_produksi', 'like', "{$prefix}%")
            ->orderByDesc('no_produksi')
            ->value('no_produksi');

        if ($latest) {
            $lastNum = (int) substr($latest, strlen($prefix));
            $next = $lastNum + 1;
        } else {
            $next = 1;
        }

        return $prefix . sprintf('%03d', $next);
    }

    protected static function booted(): void
    {
        static::deleted(function (Spk $spk) {
            if (!empty($spk->no_pesanan)) {
                $remainingSpks = static::where('tenant_id', $spk->tenant_id)
                    ->where('no_pesanan', $spk->no_pesanan)
                    ->count();

                if ($remainingSpks === 0) {
                    $offlineSale = OfflineSale::where('tenant_id', $spk->tenant_id)
                        ->where('sale_number', $spk->no_pesanan)
                        ->first();

                    if ($offlineSale && $offlineSale->is_po && in_array($offlineSale->status, [
                        OfflineSale::STATUS_SPK_PROCESSING,
                        OfflineSale::STATUS_PENDING_APPROVAL,
                    ])) {
                        $revertedStatus = ((float) $offlineSale->paid_amount > 0)
                            ? OfflineSale::STATUS_PENDING_SPK
                            : OfflineSale::STATUS_WAITING_DP;
                        $offlineSale->update(['status' => $revertedStatus]);
                    }
                }
            }
        });
    }
}
