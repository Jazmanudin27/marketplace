<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'status',
        'cutoff_start_day',
        'office_latitude',
        'office_longitude',
        'office_radius',
    ];

    /**
     * Get start and end date of the cutoff period based on a month period (YYYY-MM).
     *
     * @param string $period Format: YYYY-MM
     * @return array [startDate, endDate]
     */
    public function getCutoffRange(string $period): array
    {
        $currentMonth = \Carbon\Carbon::parse($period . '-01');
        $startDay = (int) ($this->cutoff_start_day ?? 21);

        if ($startDay === 1) {
            $startDate = $currentMonth->copy()->startOfMonth()->toDateString();
            $endDate = $currentMonth->copy()->endOfMonth()->toDateString();
        } else {
            $startDate = $currentMonth->copy()->subMonth()->day($startDay)->toDateString();
            // Subtract 1 day from startDay for the end date.
            // Carbon will automatically handle wrapping if startDay - 1 is 0, but since startDay is >= 2, this is safe.
            $endDate = $currentMonth->copy()->day($startDay - 1)->toDateString();
        }

        return [$startDate, $endDate];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
    }

    public function masterProducts(): HasMany
    {
        return $this->hasMany(MasterProduct::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
