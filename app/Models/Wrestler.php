<?php

namespace App\Models;

use App\Casts\HeightCast;
use App\Enums\WrestlerStatus;
use App\Models\Contracts\Bookable;
use App\Models\Contracts\Manageable;
use App\Models\Contracts\StableMember;
use App\Models\Contracts\TagTeamMember;
use Illuminate\Database\Eloquent\Concerns\HasRelationships;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Wrestler extends SingleRosterMember implements Bookable, Manageable, StableMember, TagTeamMember
{
    use Concerns\Bookable,
        Concerns\Manageable,
        Concerns\OwnedByUser,
        Concerns\StableMember,
        Concerns\TagTeamMember,
        Concerns\Unguarded,
        HasFactory,
        HasRelationships,
        SoftDeletes;

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::saving(function ($wrestler) {
            $wrestler->updateStatus();
        });
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wrestlers';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'status' => WrestlerStatus::class,
        'height' => HeightCast::class,
    ];

    /**
     * Get all of the titles held by the wrestler.
     */
    public function titles()
    {
        return $this->morphToMany(Title::class, 'championable');
    }

    /**
     * Update the status for the wrestler.
     *
     * @return $this
     */
    public function updateStatus()
    {
        $this->status = match (true) {
            $this->isCurrentlyEmployed() => match (true) {
                $this->isInjured() => WrestlerStatus::INJURED,
                $this->isSuspended() => WrestlerStatus::SUSPENDED,
                $this->isBookable() => WrestlerStatus::BOOKABLE,
            },
            $this->hasFutureEmployment() => WrestlerStatus::FUTURE_EMPLOYMENT,
            $this->isReleased() => WrestlerStatus::RELEASED,
            $this->isRetired() => WrestlerStatus::RETIRED,
            default => WrestlerStatus::UNEMPLOYED
        };

        return $this;
    }
}
