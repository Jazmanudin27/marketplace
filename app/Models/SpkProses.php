<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpkProses extends Model
{
    protected $table = 'spk_proses';

    protected $fillable = [
        'spk_id',
        'nama_proses',
        'urutan',
    ];

    public function spk()
    {
        return $this->belongsTo(Spk::class);
    }

    public function progres()
    {
        return $this->hasMany(SpkItemProgres::class);
    }
}
