<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tailor extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'category',
        'phone',
        'address',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static function categories(): array
    {
        return [
            'Pemotong'       => 'Pemotong / Tukang Potong',
            'Penjahit'       => 'Penjahit / Tukang Jahit',
            'Vendor Kancing' => 'Vendor Kancing / LKPK',
            'Petugas QC'     => 'Petugas QC',
            'Finishing'      => 'Petugas Finishing',
            'Lainnya'        => 'Vendor Operasional Lainnya',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
