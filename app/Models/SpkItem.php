<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SpkItem extends Model
{
    protected $fillable = [
        'spk_id', 'master_product_id',
        'nama_produk', 'sku', 'sku_kain', 'sku_induk', 'ukuran', 'catatan', 'quantity',
        'est_kain', 'kain_pakai', 'kain_sisa',
        'penjahit', 'vendor_kancing', 'pemotong', 'alur_proses', 'status',
        'hpp',
    ];

    protected $casts = [
        'hpp'              => 'decimal:2',
    ];

    /**
     * Hitung total HPP dari semua komponen biaya + extras
     */
    public function hitungHpp(): float
    {
        return (float) $this->extras()->sum('nominal');
    }

    public function spk(): BelongsTo
    {
        return $this->belongsTo(Spk::class);
    }

    public function masterProduct(): BelongsTo
    {
        return $this->belongsTo(MasterProduct::class);
    }

    public function extras(): HasMany
    {
        return $this->hasMany(SpkItemExtra::class);
    }

    public function progres(): HasMany
    {
        return $this->hasMany(SpkItemProgres::class);
    }

    public function pickups(): HasMany
    {
        return $this->hasMany(SpkItemPickup::class, 'spk_item_id');
    }

    public function getQtyDiambilAttribute(): int
    {
        return (int) $this->pickups()->sum('qty_diambil');
    }

    public function getSisaQtyAttribute(): int
    {
        return max(0, (int) $this->quantity - $this->qty_diambil);
    }
}
