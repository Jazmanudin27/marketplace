<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImportLog extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'filename',
        'inserted_count',
        'updated_count',
        'skipped_count',
        'created_at',
        'updated_at',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
