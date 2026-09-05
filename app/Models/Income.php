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

    protected static $categoryCache = [];

    public function getCategoryLabelAttribute()
    {
        $cacheKey = ($this->tenant_id ?? 0) . '_' . ($this->category ?? '');
        if (isset(static::$categoryCache[$cacheKey])) {
            return static::$categoryCache[$cacheKey];
        }

        $cat = FinanceCategory::where('tenant_id', $this->tenant_id)
            ->where('code', $this->category)
            ->first();

        if ($cat) {
            return static::$categoryCache[$cacheKey] = $cat->name;
        }

        $defaults = [
            'investment' => 'Investasi / Modal',
            'refund'     => 'Refund / Pengembalian',
            'services'   => 'Jasa / Layanan',
            'other'      => 'Lain-lain',
        ];

        return static::$categoryCache[$cacheKey] = $defaults[$this->category] ?? ucwords(str_replace('_', ' ', $this->category));
    }

    public function getPaymentDestinationLabelAttribute()
    {
        return $this->payment_destination === 'kas_kecil' ? 'Kas Kecil (Petty Cash)' : 'Kas Besar (Main Cash)';
    }
}
