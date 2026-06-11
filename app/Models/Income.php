<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Income extends Model
{
    protected $fillable = [
        'tenant_id',
        'title',
        'category',
        'payment_destination',
        'amount',
        'income_date',
        'description',
    ];

    protected $casts = [
        'income_date' => 'date',
        'amount' => 'float',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function getCategoryLabelAttribute()
    {
        return [
            'investment' => 'Investasi / Modal',
            'refund' => 'Refund / Pengembalian',
            'services' => 'Jasa / Layanan',
            'other' => 'Lain-lain',
        ][$this->category] ?? ucfirst($this->category);
    }

    public function getPaymentDestinationLabelAttribute()
    {
        return $this->payment_destination === 'kas_kecil' ? 'Kas Kecil (Petty Cash)' : 'Kas Besar (Main Cash)';
    }
}
