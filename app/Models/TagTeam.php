<?php

namespace App\Models;

use App\Builders\TagTeamQueryBuilder;
use App\Enums\TagTeamStatus;
use App\Exceptions\CannotBeEmployedException;
use App\Exceptions\NotEnoughMembersException;
use App\Models\Contracts\Bookable;
use App\Models\Contracts\StableMember;
use App\Observers\TagTeamObserver;
use Fidum\EloquentMorphToOne\HasMorphToOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class TagTeam extends RosterMember implements Bookable, StableMember
{
    use SoftDeletes,
        HasFactory,
        HasMorphToOne,
        Concerns\OwnedByUser,
        Concerns\StableMember,
        Concerns\Unguarded,
        \Staudenmeir\EloquentHasManyDeep\HasTableAlias;

    /**
     * The "boot" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        self::observe(TagTeamObserver::class);
    }

    public function newEloquentBuilder($query)
    {
        return new TagTeamQueryBuilder($query);
    }

    /**
     * The number of the wrestlers allowed on a tag team.
     *
     * @var int
     */
    const MAX_WRESTLERS_COUNT = 2;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'status' => TagTeamStatus::class,
    ];

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
            throw new CannotBeEmployedException;
        }

        if ($this->isRetired()) {
            throw new CannotBeEmployedException;
        }

        if ($this->currentWrestlers->count() !== self::MAX_WRESTLERS_COUNT) {
            throw NotEnoughMembersException::forTagTeam();
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
     * Check to see if the tag team is bookable.
     *
     * @return bool
     */
    public function isBookable()
    {
        if ($this->isNotInEmployment()) {
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
        return ! $this->partnersAreBookable();
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
     * Determine if the model can be suspended.
     *
     * @return bool
     */
    public function canBeSuspended()
    {
        if ($this->isNotInEmployment()) {
            return false;
        }

        if ($this->isSuspended()) {
            return false;
        }

        return true;
    }

    /**
     * Determine if the model can be unretired.
     *
     * @return bool
     */
    public function canBeUnretired()
    {
        if (! $this->isRetired()) {
            return false;
        }

        return true;
    }
}
