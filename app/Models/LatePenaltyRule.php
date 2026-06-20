<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LatePenaltyRule extends Model
{
    protected $fillable = [
        'tenant_id',
        'min_minutes',
        'penalty_amount',
    ];

    protected $casts = [
        'min_minutes' => 'integer',
        'penalty_amount' => 'decimal:2',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
