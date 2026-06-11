<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FundTransfer extends Model
{
    protected $fillable = [
        'tenant_id',
        'source',
        'destination',
        'amount',
        'transfer_date',
        'description',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'amount' => 'float',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function getSourceLabelAttribute()
    {
        return $this->source === 'kas_kecil' ? 'Kas Kecil (Petty Cash)' : 'Kas Besar (Main Cash)';
    }

    public function getDestinationLabelAttribute()
    {
        return $this->destination === 'kas_kecil' ? 'Kas Kecil (Petty Cash)' : 'Kas Besar (Main Cash)';
    }
}
