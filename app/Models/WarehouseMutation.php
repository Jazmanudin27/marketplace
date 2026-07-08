<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseMutation extends Model
{
    protected $fillable = [
        'tenant_id',
        'mutation_number',
        'type', // in, out
        'goods_receipt_id',
        'from_department_id',
        'to_department_id',
        'mutation_date',
        'status', // pending, approved
        'notes',
        'created_by',
    ];

    protected $casts = [
        'mutation_date' => 'date',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
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
        return $this->hasMany(WarehouseMutationItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending'  => 'Menunggu Approval',
            'approved' => 'Disetujui',
            default    => ucfirst($this->status),
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'pending'  => 'warning text-dark',
            'approved' => 'success',
            default    => 'secondary',
        };
    }

    public static function generateMutationNumber(string $type): string
    {
        $prefixChar = $type === 'in' ? 'WMI' : 'WMO';
        $date   = now()->format('Ymd');
        $prefix = $prefixChar . '-' . $date . '-';
        $last   = static::where('mutation_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('mutation_number');

        $seq = $last ? ((int) substr($last, -4) + 1) : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
