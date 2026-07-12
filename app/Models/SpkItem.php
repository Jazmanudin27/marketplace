<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SpkItem extends Model
{
    protected $fillable = [
        'spk_id', 'master_product_id',
        'nama_produk', 'sku', 'sku_induk', 'ukuran', 'quantity', 'penjahit', 'alur_proses', 'status',
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
}
