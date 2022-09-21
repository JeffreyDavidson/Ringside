<?php

declare(strict_types=1);

namespace App\Models;

use App\Builders\TagTeamQueryBuilder;
use App\Enums\TagTeamStatus;
use App\Models\Contracts\Bookable;
use App\Models\Contracts\CanBeAStableMember;
use App\Models\Contracts\Competitor;
use App\Models\Contracts\Manageable;
use Fidum\EloquentMorphToOne\HasMorphToOne;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class TagTeam extends RosterMember implements Bookable, CanBeAStableMember, Competitor, Manageable
{
    use Concerns\CanJoinStables;
    use Concerns\HasManagers;
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
    protected $fillable = ['user_id', 'name', 'signature_move', 'status'];

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
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \App\Builders\TagTeamQueryBuilder<TagTeam>
     */
    public function newEloquentBuilder($query): TagTeamQueryBuilder
    {
        return new TagTeamQueryBuilder($query);
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
     * Get current wrestlers of the tag team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function currentWrestlers()
    {
        return $this->hasMany(Wrestler::class, 'current_tag_team_id');
    }

    /**
     * Get previous tag team partners of the tag team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function previousWrestlers()
    {
        return $this->wrestlers()
            ->wherePivotNotNull('left_at');
    }

    /**
     * Get the combined weight of both tag team partners in a tag team.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function combinedWeight(): Attribute
    {
        return new Attribute(
            get: fn () => $this->currentWrestlers->sum('weight')
        );
    }

    /**
     * Check to see if the tag team is bookable.
     *
     * @return bool
     */
    public function isBookable()
    {
        if ($this->isNotInEmployment() || $this->hasFutureEmployment()) {
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
        return ! $this->currentWrestlers->every->isBookable();
    }

    /**
     * Undocumented function.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function eventMatches()
    {
        return $this->morphToMany(EventMatch::class, 'event_match_competitor');
    }
}
