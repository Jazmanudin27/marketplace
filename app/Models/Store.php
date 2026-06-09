<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    protected $fillable = [
        'tenant_id',
        'channel_id',
        'store_name',
        'marketplace_store_id',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'status',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function marketplaceProducts(): HasMany
    {
        return $this->hasMany(MarketplaceProduct::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isTokenExpired(): bool
    {
        return $this->token_expires_at && $this->token_expires_at->isPast();
    }
}
