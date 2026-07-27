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
        'DRAFT'                      => ['label' => 'DRAFT (Menunggu DP)',         'emoji' => '🕐', 'color' => 'secondary'],
        'Tahap Desain & Mockup'      => ['label' => 'Tahap Desain & Mockup',        'emoji' => '🎨', 'color' => 'warning'],
        'Perencanaan Produksi (SPK)' => ['label' => 'Perencanaan Produksi (SPK)',   'emoji' => '📋', 'color' => 'info'],
        'Antrian & Sampling'         => ['label' => 'Antrian & Sampling',           'emoji' => '⏳', 'color' => 'primary'],
        'Tahap Pemotongan'           => ['label' => 'Tahap Pemotongan',             'emoji' => '✂️', 'color' => 'primary'],
        'Tahap Jahit'                => ['label' => 'Tahap Jahit',                  'emoji' => '🪡', 'color' => 'purple'],
        'Tahap LKPK (Kancing)'       => ['label' => 'Tahap LKPK (Kancing)',         'emoji' => '💿', 'color' => 'dark'],
        'Quality Control'            => ['label' => 'Quality Control',              'emoji' => '🔍', 'color' => 'success'],
        'Packing / Finishing'        => ['label' => 'Packing / Finishing',          'emoji' => '📦', 'color' => 'warning'],
        'Selesai (Finished Good)'    => ['label' => 'Selesai (Finished Good)',      'emoji' => '✅', 'color' => 'success'],
        'Telah Dikirim (Shipped)'    => ['label' => 'Telah Dikirim (Shipped)',      'emoji' => '🚀', 'color' => 'dark'],
    ];

    public function getCurrentStageNameAttribute(): string
    {
        if ($this->relationLoaded('proses') && $this->proses->isNotEmpty()) {
            // Pick first uncompleted stage or last stage
            $activeStage = $this->proses->first(function ($p) {
                return isset($p->status) && $p->status !== 'Selesai';
            });
            if ($activeStage) {
                return strtoupper($activeStage->nama_proses);
            }
            return strtoupper($this->proses->last()->nama_proses);
        }
        return 'PERENCANAAN';
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
}
