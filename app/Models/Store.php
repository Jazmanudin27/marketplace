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
                         $this->token_expires_at->copy()->subMinutes(5)->isPast();

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
                    throw new \RuntimeException("Gagal memperbarui token Shopee untuk toko '{$this->store_name}': " . $e->getMessage(), 0, $e);
                }
            }
        } elseif ($this->channel && in_array($this->channel->code, ['tiktok', 'tokopedia'])) {
            $isExpired = $force ||
                         !$this->access_token ||
                         !$this->token_expires_at ||
                         $this->token_expires_at->copy()->subMinutes(5)->isPast();

            if ($isExpired && !empty($this->refresh_token)) {
                try {
                    $tiktok = app(\App\Services\TiktokService::class);
                    $tokenData = $tiktok->refreshAccessToken($this->refresh_token);

                    $this->update([
                        'access_token' => $tokenData['access_token'],
                        'refresh_token' => $tokenData['refresh_token'] ?? $this->refresh_token,
                        'token_expires_at' => date('Y-m-d H:i:s', $tokenData['access_token_expire_in'] ?? (time() + 86400)),
                        'status' => 'connected',
                    ]);

                    $this->refresh();
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('Auto-refresh TikTok/Tokopedia token failed inside Store model', [
                        'store_id' => $this->id,
                        'message' => $e->getMessage()
                    ]);
                    $this->update(['status' => 'expired']);
                    throw new \RuntimeException("Gagal memperbarui token TikTok/Tokopedia untuk toko '{$this->store_name}': " . $e->getMessage(), 0, $e);
                }
            }
        } elseif ($this->channel && $this->channel->code === 'lazada') {
            $isExpired = $force ||
                         !$this->access_token ||
                         !$this->token_expires_at ||
                         $this->token_expires_at->copy()->subMinutes(5)->isPast();

            if ($isExpired && !empty($this->refresh_token)) {
                try {
                    $lazada = app(\App\Services\LazadaService::class);
                    $tokenData = $lazada->refreshAccessToken($this->refresh_token);

                    $this->update([
                        'access_token' => $tokenData['access_token'],
                        'refresh_token' => $tokenData['refresh_token'] ?? $this->refresh_token,
                        'token_expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 604800),
                        'status' => 'connected',
                    ]);

                    $this->refresh();
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('Auto-refresh Lazada token failed inside Store model', [
                        'store_id' => $this->id,
                        'message' => $e->getMessage()
                    ]);
                    $this->update(['status' => 'expired']);
                    throw new \RuntimeException("Gagal memperbarui token Lazada untuk toko '{$this->store_name}': " . $e->getMessage(), 0, $e);
                }
            }
        }

        if (empty($this->access_token)) {
            throw new \RuntimeException("Access token untuk toko '{$this->store_name}' (ID: {$this->id}) kosong. Silakan hubungkan kembali toko Anda di menu integrasi.");
        }

        return $this->access_token;
    }
}
