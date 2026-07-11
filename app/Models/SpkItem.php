<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpkItem extends Model
{
    protected $fillable = [
        'spk_id',
        'master_product_id',
        'nama_produk',
        'sku',
        'sku_induk',
        'ukuran',
        'quantity',
        'penjahit',
        'biaya_bahan',
        'ongkos_jahit',
        'ongkos_printing',
        'hpp',
        'alur_proses',
    ];

    public function spk(): BelongsTo
    {
        return $this->belongsTo(Spk::class);
    }

    public function masterProduct(): BelongsTo
    {
        return $this->belongsTo(MasterProduct::class);
    }
}
