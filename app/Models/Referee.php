<?php

namespace App\Models;

use App\Enums\RefereeStatus;
use App\Models\Contracts\Bookable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Referee extends SingleRosterMember implements Bookable
{
    use Concerns\Bookable,
        Concerns\HasFullName,
        Concerns\Unguarded,
        HasFactory,
        SoftDeletes;

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
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'referees';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'status' => RefereeStatus::class,
    ];

    /**
     * Update the status for the referee.
     *
     * @return $this
     */
    public function updateStatus()
    {
        $this->status = match (true) {
            $this->isCurrentlyEmployed() => match (true) {
                $this->isInjured() => RefereeStatus::INJURED,
                $this->isSuspended() => RefereeStatus::SUSPENDED,
                $this->isBookable() => RefereeStatus::BOOKABLE,
            },
            $this->hasFutureEmployment() => RefereeStatus::FUTURE_EMPLOYMENT,
            $this->isReleased() => RefereeStatus::RELEASED,
            $this->isRetired() => RefereeStatus::RETIRED,
            default => RefereeStatus::UNEMPLOYED
        };

        return $this;
    }
}
