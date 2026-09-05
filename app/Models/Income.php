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

    protected static $bankAccountCache = [];

    public function getPaymentDestinationLabelAttribute()
    {
        $val = $this->payment_destination;
        if (!$val) return '-';

        $cacheKey = ($this->tenant_id ?? 0) . '_' . $val;
        if (isset(static::$bankAccountCache[$cacheKey])) {
            return static::$bankAccountCache[$cacheKey];
        }

        $bank = BankAccount::where('tenant_id', $this->tenant_id)
            ->where(function($q) use ($val) {
                $q->where('bank_name', $val)->orWhere('id', $val);
            })->first();

        if ($bank) {
            $label = $bank->bank_name;
            if ($bank->account_number) {
                $label .= ' (' . $bank->account_number . ')';
            }
            if ($bank->account_name) {
                $label .= ' - ' . $bank->account_name;
            }
            return static::$bankAccountCache[$cacheKey] = $label;
        }

        if ($val === 'kas_kecil') {
            return static::$bankAccountCache[$cacheKey] = 'Kas Kecil';
        }
        if ($val === 'kas_besar') {
            return static::$bankAccountCache[$cacheKey] = 'Kas Besar';
        }

        return static::$bankAccountCache[$cacheKey] = ucwords(str_replace('_', ' ', $val));
    }
}
