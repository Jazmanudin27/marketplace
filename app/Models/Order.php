<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'tenant_id',
        'store_id',
        'order_marketplace_id',
        'invoice_number',
        'order_status',
        'buyer_name',
        'buyer_phone',
        'shipping_address',
        'total_amount',
        'shipping_fee',
        'discount_amount',
        'marketplace_fee',
        'net_amount',
        'courier',
        'tracking_number',
        'order_date',
    ];

    protected $casts = [
        'order_date'       => 'datetime',
        'total_amount'     => 'decimal:2',
        'shipping_fee'     => 'decimal:2',
        'discount_amount'  => 'decimal:2',
        'marketplace_fee'  => 'decimal:2',
        'net_amount'       => 'decimal:2',
    ];

    // Status constants
    const STATUS_UNPAID          = 'UNPAID';
    const STATUS_READY_TO_SHIP   = 'READY_TO_SHIP';
    const STATUS_SHIPPED         = 'SHIPPED';
    const STATUS_DELIVERED       = 'DELIVERED';
    const STATUS_CANCELLED       = 'CANCELLED';
    const STATUS_RETURN          = 'RETURN';

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->order_status) {
            self::STATUS_UNPAID        => 'warning',
            self::STATUS_READY_TO_SHIP => 'primary',
            self::STATUS_SHIPPED       => 'info',
            self::STATUS_DELIVERED     => 'success',
            self::STATUS_CANCELLED     => 'danger',
            self::STATUS_RETURN        => 'secondary',
            default                    => 'dark',
        };
    }
}
