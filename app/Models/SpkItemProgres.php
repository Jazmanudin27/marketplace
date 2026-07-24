<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpkItemProgres extends Model
{
    protected $table = 'spk_item_progres';

    protected $fillable = [
        'spk_item_id',
        'spk_proses_id',
        'qty_done',
    ];

    public function item()
    {
        return $this->belongsTo(SpkItem::class, 'spk_item_id');
    }

    public function proses()
    {
        return $this->belongsTo(SpkProses::class, 'spk_proses_id');
    }
}
