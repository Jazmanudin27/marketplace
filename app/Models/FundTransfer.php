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

    protected static $bankAccountCache = [];

    protected function resolveBankLabel(?string $val): string
    {
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

    public function getSourceLabelAttribute()
    {
        return $this->resolveBankLabel($this->source);
    }

    public function getDestinationLabelAttribute()
    {
        return $this->resolveBankLabel($this->destination);
    }
}
