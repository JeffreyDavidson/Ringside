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
        Concerns\Employable,
        Concerns\Retirable,
        Concerns\Injurable,
        Concerns\Suspendable,
        Concerns\Unguarded;

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::saving(function ($referee) {
            $referee->updateStatus();
        });
    }

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'status' => RefereeStatus::class,
    ];

    /**
     * Check to see if the referee is bookable.
     *
     * @return bool
     */
    public function isBookable()
    {
        if ($this->isNotInEmployment() || $this->isSuspended() || $this->isInjured()) {
            return false;
        }

        return true;
    }

    /**
     * Scope a query to only include bookable referees.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBookable($query)
    {
        return $query->whereHas('currentEmployment')
                    ->whereDoesntHave('currentSuspension')
                    ->whereDoesntHave('currentInjury');
    }

    /**
     * Update the status for the referee.
     *
     * @return void
     */
    public function updateStatus()
    {
        if ($this->isCurrentlyEmployed()) {
            if ($this->isInjured()) {
                $this->status = RefereeStatus::INJURED;
            } elseif ($this->isSuspended()) {
                $this->status = RefereeStatus::SUSPENDED;
            } elseif ($this->isBookable()) {
                $this->status = RefereeStatus::BOOKABLE;
            }
        } elseif ($this->hasFutureEmployment()) {
            $this->status = RefereeStatus::FUTURE_EMPLOYMENT;
        } elseif ($this->isReleased()) {
            $this->status = RefereeStatus::RELEASED;
        } elseif ($this->isRetired()) {
            $this->status = RefereeStatus::RETIRED;
        } else {
            $this->status = RefereeStatus::UNEMPLOYED;
        }
    }

    /**
     * Updates a referee's status and saves.
     *
     * @return void
     */
    public function updateStatusAndSave()
    {
        $this->updateStatus();
        $this->save();
    }
}
