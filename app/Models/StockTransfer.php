<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransfer extends Model
{
    protected $fillable = [
        'tenant_id',
        'transfer_number',
        'from_department_id',
        'to_department_id',
        'transfer_date',
        'status',
        'notes',
        'created_by',
        'confirmed_by',
        'confirmed_at',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'confirmed_at'  => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function fromDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'from_department_id');
    }

    public function toDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'to_department_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft'     => 'Draft',
            'confirmed' => 'Dikonfirmasi',
            default     => ucfirst($this->status),
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'draft'     => 'secondary',
            'confirmed' => 'success',
            default     => 'dark',
        };
    }

    public static function generateTransferNumber(): string
    {
        $date   = now()->format('Ymd');
        $prefix = 'TRF-' . $date . '-';
        $last   = static::where('transfer_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('transfer_number');

        $seq = $last ? ((int) substr($last, -4) + 1) : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
