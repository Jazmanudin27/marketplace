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
        'shop_cipher',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'status',
        'shipping_handover_method',
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

    public function getValidAccessToken(bool $force = false): string
    {
        if ($this->channel && $this->channel->code === 'shopee') {
            $isExpired = $force ||
                         !$this->access_token ||
                         !$this->token_expires_at ||
                         $this->token_expires_at->subMinutes(5)->isPast();

            if ($isExpired && !empty($this->refresh_token)) {
                try {
                    $shopee = app(\App\Services\ShopeeService::class);
                    $shopId = (int) $this->marketplace_store_id;
                    $tokenData = $shopee->refreshAccessToken($this->refresh_token, $shopId);

                    $this->update([
                        'access_token' => $tokenData['access_token'],
                        'refresh_token' => $tokenData['refresh_token'] ?? $this->refresh_token,
                        'token_expires_at' => now()->addSeconds($tokenData['expire_in'] ?? 14400),
                        'status' => 'connected',
                    ]);

                    $this->refresh();
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('Auto-refresh Shopee token failed inside Store model', [
                        'store_id' => $this->id,
                        'message' => $e->getMessage()
                    ]);
                    $this->update(['status' => 'expired']);
                }
            }
        }

        return $this->access_token;
    }
}
