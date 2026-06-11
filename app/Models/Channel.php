<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Channel extends Model
{
    protected $fillable = [
        'code',
        'name',
        'logo_url',
        'status',
    ];

    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
    }

    /**
     * Ensure channels are seeded in the database.
     * Self-healing mechanism to run ChannelSeeder if channels are missing.
     */
    public static function ensureChannelsExist(): void
    {
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('channels') && self::count() === 0) {
                if (class_exists(\Database\Seeders\ChannelSeeder::class)) {
                    $seeder = new \Database\Seeders\ChannelSeeder();
                    $seeder->run();
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to auto-seed channels: ' . $e->getMessage());
        }
    }
}

