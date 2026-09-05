<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = [
        'tenant_id',
        'employee_id',
        'title',
        'category',
        'payment_source',
        'amount',
        'expense_date',
        'description',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'float',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    protected static $bankAccountCache = [];

    // Accessor to display readable label for payment source
    public function getPaymentSourceLabelAttribute()
    {
        $val = $this->payment_source;
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

    protected static $categoryCache = [];

    // Accessor to display readable label for categories
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
            'salary'               => 'Gaji Karyawan',
            'rent'                 => 'Sewa Tempat',
            'utilities'            => 'Utilitas & Operasional',
            'pembelian_supplier'   => 'Bayar Hutang Supplier',
            'other'                => 'Lain-lain',
        ];

        return static::$categoryCache[$cacheKey] = $defaults[$this->category] ?? ucwords(str_replace('_', ' ', $this->category));
    }
}
