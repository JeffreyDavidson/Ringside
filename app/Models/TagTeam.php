<?php

namespace App\Models;

use App\Enums\TagTeamStatus;
use App\Models\Contracts\Bookable;
use App\Models\Contracts\CanJoinStable;
use App\Models\Contracts\Employable;
use App\Models\Contracts\Retirable;
use App\Models\Contracts\Suspendable;
use Fidum\EloquentMorphToOne\HasMorphToOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TagTeam extends Model implements Bookable, CanJoinStable, Employable, Retirable, Suspendable
{
    use SoftDeletes,
        HasFactory,
        HasMorphToOne,
        Concerns\CanJoinStable,
        Concerns\Employable,
        Concerns\Retirable,
        Concerns\Suspendable,
        Concerns\Unguarded;

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::saving(function ($tagTeam) {
            $tagTeam->updateStatus();
        });
    }

    /**
     * The number of the wrestlers allowed on a tag team.
     *
     * @var int
     */
    const MAX_WRESTLERS_COUNT = 2;

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['currentWrestlers'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'status' => TagTeamStatus::class,
    ];

    /**
     * Get the user belonging to the tag team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the wrestlers that have been tag team partners of the tag team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function wrestlers()
    {
        return $this->belongsToMany(Wrestler::class, 'tag_team_wrestler')
                    ->withPivot('joined_at', 'left_at');
    }

    /**
     * Get current tag team partners of the tag team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function currentWrestlers()
    {
        return $this->wrestlers()
                    ->wherePivot('joined_at', '<=', now())
                    ->wherePivot('left_at', '=', null)
                    ->limit(2);
    }

    /**
     * Get previous tag team partners of the tag team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function previousWrestlers()
    {
        return $this->wrestlers()
                    ->whereNotNull('left_at');
    }

    /**
     * Get the combined weight of both tag team partners in a tag team.
     *
     * @return int
     */
    public function getCombinedWeightAttribute()
    {
        return $this->currentWrestlers->sum('weight');
    }

    /**
     * Determine if the tag team can be employed.
     *
     * @return bool
     */
    public function canBeEmployed()
    {
        if ($this->isCurrentlyEmployed()) {
            // throw new CannotBeEmployedException('Tag Team cannot be employed. This Tag Team does not have an active employment.');
            return false;
        }

        if ($this->hasFutureEmployment() && $this->currentWrestlers->count() !== self::MAX_WRESTLERS_COUNT) {
            // throw new CannotBeEmployedException('Tag Team cannot be employed. This Tag Team does not have 2 tag team partners.');
            return false;
        }

        return true;
    }

    /**
     * Determine if the tag team can be released.
     *
     * @return bool
     */
    public function canBeReleased()
    {
        if ($this->isNotInEmployment()) {
            // throw new CannotBeEmployedException('Entity cannot be released. This entity does not have an active employment.');
            return false;
        }

        return true;
    }

    /**
     * Determine if the tag team can be reinstated.
     *
     * @return bool
     */
    public function canBeReinstated()
    {
        if (! $this->isSuspended()) {
            return false;
        }

        if ($this->currentWrestlers->count() != 2 || ! $this->currentWrestlers->each->canBeReinstated()) {
            return false;
        }

        return true;
    }

    /**
     * Scope a query to only include bookable tag teams.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBookable($query)
    {
        return $query->where('status', 'bookable');
    }

    /**
     * Check to see if the tag team is bookable.
     *
     * @return bool
     */
    public function isBookable()
    {
        if ($this->isNotInEmployment()) {
            return false;
        }

        if (! $this->partnersAreBookable()) {
            return false;
        }

        return true;
    }

    /**
     * Check to see if the tag team is unbookable.
     *
     * @return bool
     */
    public function isUnbookable()
    {
        return ! $this->isBookable();
    }

    /**
     * Find out if both tag team partners are bookable.
     *
     * @return bool
     */
    public function partnersAreBookable()
    {
        foreach ($this->currentWrestlers as $wrestler) {
            if (! $wrestler->isBookable()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Update the status for the tag team.
     *
     * @return void
     */
    public function updateStatus()
    {
        if ($this->isCurrentlyEmployed()) {
            if ($this->isSuspended()) {
                $this->status = TagTeamStatus::SUSPENDED;
            } elseif ($this->isBookable()) {
                $this->status = TagTeamStatus::BOOKABLE;
            } elseif ($this->isUnbookable()) {
                $this->status = TagTeamStatus::UNBOOKABLE;
            }
        } elseif ($this->hasFutureEmployment()) {
            $this->status = TagTeamStatus::FUTURE_EMPLOYMENT;
        } elseif ($this->isReleased()) {
            $this->status = TagTeamStatus::RELEASED;
        } elseif ($this->isRetired()) {
            $this->status = TagTeamStatus::RETIRED;
        } else {
            $this->status = TagTeamStatus::UNEMPLOYED;
        }
    }

    /**
     * Updates a tag team's status and saves.
     *
     * @return void
     */
    public function updateStatusAndSave()
    {
        $this->updateStatus();
        $this->save();
    }
}
