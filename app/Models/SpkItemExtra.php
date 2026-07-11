<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpkItemExtra extends Model
{
    protected $fillable = [
        'spk_item_id',
        'keterangan',
        'nominal',
    ];

    protected $casts = [
        'nominal' => 'decimal:2',
    ];

    public function spkItem(): BelongsTo
    {
        return $this->belongsTo(SpkItem::class);
    }
}
