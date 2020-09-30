<?php

namespace App\Models;

use App\Enums\RefereeStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Referee extends Model
{
    use SoftDeletes,
        HasFactory,
        Concerns\HasFullName,
        Concerns\Unguarded;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'status' => RefereeStatus::class,
    ];

    /**
     * Scope a query to only include bookable referees.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBookable($query)
    {
        return $query->where('status', RefereeStatus::BOOKABLE);
    }

    /**
     * Check to see if the referee is bookable.
     *
     * @return bool
     */
    public function isBookable()
    {
        if ($this->isUnemployed() || $this->isSuspended() || $this->isInjured() || $this->isRetired() || $this->hasFutureEmployment()) {
            return false;
        }

        return true;
    }
}
