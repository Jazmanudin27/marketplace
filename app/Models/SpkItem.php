<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SpkItem extends Model
{
    protected $fillable = [
        'spk_id', 'master_product_id',
        // Identitas
        'nama_produk', 'sku', 'sku_induk', 'ukuran', 'quantity', 'penjahit', 'alur_proses',
        // Biaya Jasa
        'jasa_konveksi', 'jasa_potong', 'jasa_printing', 'jasa_jahit', 'jasa_labsas',
        // Biaya Bahan
        'kebutuhan_kain', 'biaya_kain', 'biaya_sbs', 'biaya_pitta',
        // Komponen Kecil
        'biaya_kancing', 'biaya_kancing_kait', 'biaya_karet', 'biaya_plastik', 'biaya_string',
        // Finishing
        'biaya_bordir', 'biaya_servis', 'biaya_finishing',
        // Lain-lain
        'biaya_pengiriman',
        // Total
        'hpp',
    ];

    protected $casts = [
        'jasa_konveksi'    => 'decimal:2',
        'jasa_potong'      => 'decimal:2',
        'jasa_printing'    => 'decimal:2',
        'jasa_jahit'       => 'decimal:2',
        'jasa_labsas'      => 'decimal:2',
        'kebutuhan_kain'   => 'decimal:2',
        'biaya_kain'       => 'decimal:2',
        'biaya_sbs'        => 'decimal:2',
        'biaya_pitta'      => 'decimal:2',
        'biaya_kancing'    => 'decimal:2',
        'biaya_kancing_kait'=> 'decimal:2',
        'biaya_karet'      => 'decimal:2',
        'biaya_plastik'    => 'decimal:2',
        'biaya_string'     => 'decimal:2',
        'biaya_bordir'     => 'decimal:2',
        'biaya_servis'     => 'decimal:2',
        'biaya_finishing'  => 'decimal:2',
        'biaya_pengiriman' => 'decimal:2',
        'hpp'              => 'decimal:2',
    ];

    /**
     * Hitung total HPP dari semua komponen biaya + extras
     */
    public function hitungHpp(): float
    {
        $standar = (float)$this->jasa_konveksi
            + (float)$this->jasa_potong
            + (float)$this->jasa_printing
            + (float)$this->jasa_jahit
            + (float)$this->jasa_labsas
            + (float)$this->biaya_kain
            + (float)$this->biaya_sbs
            + (float)$this->biaya_pitta
            + (float)$this->biaya_kancing
            + (float)$this->biaya_kancing_kait
            + (float)$this->biaya_karet
            + (float)$this->biaya_plastik
            + (float)$this->biaya_string
            + (float)$this->biaya_bordir
            + (float)$this->biaya_servis
            + (float)$this->biaya_finishing
            + (float)$this->biaya_pengiriman;

        $extras = $this->extras()->sum('nominal');

        return $standar + (float)$extras;
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
