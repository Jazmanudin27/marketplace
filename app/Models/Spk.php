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
        'no_spk',
        'tipe_spk',
        'is_urgent',
        'tanggal',
        'deadline',
        'pemesan',
        'no_hp_pemesan',
        'instansi',
        'tambahan',
        'image_url',
        'penginput_id',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'deadline' => 'date',
        'is_urgent' => 'boolean',
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
