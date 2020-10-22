<?php

namespace App\Models;

use App\Enums\WrestlerStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Wrestler extends Model
{
    use SoftDeletes,
        HasFactory,
        Concerns\CanBeStableMember,
        Concerns\Suspendable,
        Concerns\Retirable,
        Concerns\Employable,
        Concerns\Injurable,
        Concerns\Unguarded;

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
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'status' => WrestlerStatus::class,
    ];

    /**
     * Get the tag team history the wrestler has belonged to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tagTeams()
    {
        return $this->belongsToMany(TagTeam::class, 'tag_team_wrestler');
    }

    /**
     * Get the current tag team of the wrestler.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function currentTagTeam()
    {
        return $this->belongsTo(TagTeam::class);
    }

    /**
     * Get the previous tag teams the wrestler has belonged to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function previousTagTeams()
    {
        return $this->tagTeams()
                    ->whereNotNull('ended_at');
    }

    /**
     * Get the user assigned to the wrestler.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check to see if the wrestler is bookable.
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
     * Return the wrestler's height formatted.
     *
     * @return string
     */
    public function getFormattedHeightAttribute()
    {
        return "{$this->feet}'{$this->inches}\"";
    }

    /**
     * Return the wrestler's height in feet.
     *
     * @return string
     */
    public function getFeetAttribute()
    {
        return floor($this->height / 12);
    }

    /**
     * Return the wrestler's height in inches.
     *
     * @return string
     */
    public function getInchesAttribute()
    {
        return $this->height % 12;
    }

    /**
     * Scope a query to only include bookable wrestlers.
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
     * Update the status for the wrestler.
     *
     * @return void
     */
    public function updateStatus()
    {
        if ($this->isCurrentlyEmployed()) {
            if ($this->isInjured()) {
                $this->status = WrestlerStatus::INJURED;
            } elseif ($this->isSuspended()) {
                $this->status = WrestlerStatus::SUSPENDED;
            } elseif ($this->isBookable()) {
                $this->status = WrestlerStatus::BOOKABLE;
            }
        } elseif ($this->hasFutureEmployment()) {
            $this->status = WrestlerStatus::FUTURE_EMPLOYMENT;
        } elseif ($this->isReleased()) {
            $this->status = WrestlerStatus::RELEASED;
        } elseif ($this->isRetired()) {
            $this->status = WrestlerStatus::RETIRED;
        } else {
            $this->status = WrestlerStatus::UNEMPLOYED;
        }
    }

    /**
     * Updates a wrestler's status and saves.
     *
     * @return void
     */
    public function updateStatusAndSave()
    {
        $this->updateStatus();
        $this->save();
    }
}
