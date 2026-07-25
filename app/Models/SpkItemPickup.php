<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpkItemPickup extends Model
{
    protected $fillable = [
        'spk_item_id',
        'qty_diambil',
        'tanggal_ambil',
        'nama_pengambil',
        'pemberi_id',
        'catatan',
    ];

    protected $casts = [
        'tanggal_ambil' => 'datetime',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(SpkItem::class, 'spk_item_id');
    }

    public function pemberi(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pemberi_id');
    }
}
