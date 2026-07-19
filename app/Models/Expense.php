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

    // Accessor to display readable label for payment source
    public function getPaymentSourceLabelAttribute()
    {
        return $this->payment_source === 'kas_kecil' ? 'Kas Kecil (Petty Cash)' : 'Kas Besar (Main Cash)';
    }

    // Accessor to display readable label for categories
    public function getCategoryLabelAttribute()
    {
        return [
            'salary'               => 'Gaji Karyawan',
            'rent'                 => 'Sewa Tempat',
            'utilities'            => 'Utilitas & Operasional',
            'pembelian_supplier'   => 'Bayar Hutang Supplier',
            'other'                => 'Lain-lain',
        ][$this->category] ?? ucfirst($this->category);
    }
}
