<?php

declare(strict_types=1);

namespace App\Models;

use App\Builders\TagTeamQueryBuilder;
use App\Enums\TagTeamStatus;
use App\Models\Contracts\Bookable;
use App\Models\Contracts\CanBeAStableMember;
use App\Models\Contracts\Competitor;
use App\Models\Contracts\Employable;
use App\Models\Contracts\Manageable;
use App\Models\Contracts\Retirable;
use App\Models\Contracts\Suspendable;
use Fidum\EloquentMorphToOne\HasMorphToOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TagTeam extends Model implements Bookable, CanBeAStableMember, Competitor, Employable, Manageable, Retirable, Suspendable
{
    use Concerns\CanJoinStables;
    use Concerns\HasEmployments;
    use Concerns\HasManagers;
    use Concerns\HasRetirements;
    use Concerns\HasSuspensions;
    use Concerns\OwnedByUser;
    use HasFactory;
    use HasMorphToOne;
    use SoftDeletes;

    /**
     * The number of the wrestlers allowed on a tag team.
     */
    public const NUMBER_OF_WRESTLERS_ON_TEAM = 2;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'signature_move',
        'status',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => TagTeamStatus::class,
    ];

    /**
     * Create a new Eloquent query builder for the model.
     */
    public function newEloquentBuilder($query): TagTeamQueryBuilder
    {
        return new TagTeamQueryBuilder($query);
    }

    /**
     * Check to see if the tag team is bookable.
     */
    public function isBookable(): bool
    {
        if ($this->isNotInEmployment() || $this->hasFutureEmployment()) {
            return false;
        }

        return true;
    }

    /**
     * Check to see if the tag team is unbookable.
     */
    public function isUnbookable(): bool
    {
        return ! $this->currentWrestlers->every->isBookable();
    }

    /**
     * Undocumented function.
     */
    public function eventMatches(): MorphToMany
    {
        return $this->morphToMany(EventMatch::class, 'event_match_competitor');
    }

    /**
     * Determine if the tag team can be employed.
     */
    public function canBeEmployed(): bool
    {
        if ($this->isCurrentlyEmployed()) {
            return false;
        }

        if ($this->isRetired()) {
            return false;
        }

        if ($this->currentWrestlers->count() !== self::NUMBER_OF_WRESTLERS_ON_TEAM) {
            return false;
        }

        return true;
    }

    /**
     * Determine if the tag team can be released.
     */
    public function canBeReleased(): bool
    {
        if ($this->isNotInEmployment() || $this->hasFutureEmployment()) {
            return false;
        }

        return true;
    }

    /**
     * Determine if the tag team can be reinstated.
     */
    public function canBeReinstated(): bool
    {
        if (! $this->isSuspended()) {
            return false;
        }

        return true;
    }

    /**
     * Determine if the tag team can be suspended.
     */
    public function canBeSuspended(): bool
    {
        if ($this->isNotInEmployment() || $this->hasFutureEmployment()) {
            return false;
        }

        if ($this->isSuspended()) {
            return false;
        }

        return true;
    }

    /**
     * Determine if the tag team can be retired.
     */
    public function canBeRetired(): bool
    {
        if ($this->isNotInEmployment()) {
            return false;
        }

        return $this->isBookable() || $this->isUnbookable();
    }

    /**
     * Determine if the tag team can be unretired.
     */
    public function canBeUnretired(): bool
    {
        if (! $this->isRetired()) {
            return false;
        }

        return ! $this->currentWrestlers->every->isBookable();
    }
}
