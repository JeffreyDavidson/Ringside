<?php

declare(strict_types=1);

namespace App\Models;

use App\Builders\WrestlerQueryBuilder;
use App\Enums\WrestlerStatus;
use App\Models\Concerns\CanJoinStables;
use App\Models\Concerns\CanJoinTagTeams;
use App\Models\Concerns\HasManagers;
use App\Models\Concerns\OwnedByUser;
use App\Models\Contracts\Bookable;
use App\Models\Contracts\CanBeAStableMember;
use App\Models\Contracts\Manageable;
use App\Models\Contracts\TagTeamMember;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Wrestler extends SingleRosterMember implements Bookable, CanBeAStableMember, Manageable, TagTeamMember
{
    use CanJoinStables,
        CanJoinTagTeams,
        HasFactory,
        HasManagers,
        OwnedByUser,
        SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['user_id', 'name', 'height', 'weight', 'hometown', 'signature_move', 'status'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => WrestlerStatus::class,
    ];

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \App\Builders\WrestlerQueryBuilder
     */
    public function newEloquentBuilder($query)
    {
        return new WrestlerQueryBuilder($query);
    }

    /**
     * Retrieve the event matches participated by the wrestler.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function eventMatches()
    {
        return $this->morphToMany(EventMatch::class, 'event_match_competitor');
    }

    /**
     * Determine if the model can be retired.
     *
     * @return bool
     */
    public function canBeRetired()
    {
        return $this->isBookable() || $this->isInjured() || $this->isSuspended();
    }

    /**
     * Determine if the model can be unretired.
     *
     * @return bool
     */
    public function canBeUnretired()
    {
        return $this->isRetired();
    }
}
