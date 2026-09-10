<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'category',
        'marketplace_username',
        'phone',
        'address',
        'tags',
        'balance',
    ];

    public const CATEGORIES = [
        'umum'        => 'Pelanggan Umum',
        'biasa'       => 'Pelanggan Biasa',
        'dropship'    => 'Pelanggan Dropship',
        'marketplace' => 'Pelanggan Marketplace',
    ];

    public function getCategoryLabelAttribute(): string
    {
        if (!empty($this->marketplace_username) || $this->category === 'marketplace') {
            return 'Pelanggan Marketplace';
        }
        return self::CATEGORIES[$this->category] ?? 'Pelanggan Umum';
    }

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function offlineSales()
    {
        return $this->hasMany(OfflineSale::class);
    }

    /**
     * Scope query to only include offline customers.
     */
    public function scopeOffline($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('category')
              ->orWhere('category', '!=', 'marketplace');
        })
        ->where(function ($q) {
            $q->whereNull('marketplace_username')
              ->orWhere('marketplace_username', '');
        })
        ->where(function ($q) {
            $q->whereDoesntHave('orders')
              ->orWhereHas('offlineSales');
        });
    }

    /**
     * Scope query to only include marketplace customers.
     */
    public function scopeMarketplace($query)
    {
        return $query->where(function ($q) {
            $q->where('category', 'marketplace')
              ->orWhere(function ($q2) {
                  $q2->whereNotNull('marketplace_username')
                     ->where('marketplace_username', '!=', '');
              })
              ->orWhere(function ($q3) {
                  $q3->whereHas('orders')
                     ->whereDoesntHave('offlineSales');
              });
        });
    }

    public function balanceTransactions()
    {
        return $this->hasMany(ResellerBalanceTransaction::class);
    }

    /**
     * Adjust customer balance and log transaction history
     */
    public function adjustBalance(float $amount, string $type, string $description, ?int $userId = null): void
    {
        $this->increment('balance', $type === 'in' ? $amount : -$amount);
        
        $this->balanceTransactions()->create([
            'tenant_id'   => $this->tenant_id,
            'type'        => $type,
            'amount'      => $amount,
            'description' => $description,
            'user_id'     => $userId,
        ]);
    }

    // Accessors for analytics
    public function getTotalOrdersAttribute()
    {
        return $this->orders()->count();
    }

    public function getTotalSpentAttribute()
    {
        return $this->orders()->sum('net_amount');
    }
}
