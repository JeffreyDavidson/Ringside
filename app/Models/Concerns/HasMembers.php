<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Manager;
use App\Models\StableManager;
use App\Models\StableTagTeam;
use App\Models\StableWrestler;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait HasMembers
{
    /**
     * Get the wrestlers belonging to the stable.
     *
     * @return BelongsToMany<Wrestler>
     */
    public function wrestlers(): BelongsToMany
    {
        return $this->belongsToMany(Wrestler::class, 'stables_wrestlers')
            ->withPivot(['joined_at', 'left_at'])
            ->using(StableWrestler::class);
    }

    /**
     * Get all current wrestlers that are members of the stable.
     *
     * @return BelongsToMany<Wrestler>
     */
    public function currentWrestlers(): BelongsToMany
    {
        return $this->wrestlers()
            ->wherePivotNull('left_at');
    }

    /**
     * Get all previous wrestlers that were members of the stable.
     *
     * @return BelongsToMany<Wrestler>
     */
    public function previousWrestlers(): BelongsToMany
    {
        return $this->wrestlers()
            ->wherePivotNotNull('left_at');
    }

    /**
     * Get the tag teams belonging to the stable.
     *
     * @return BelongsToMany<TagTeam>
     */
    public function tagTeams(): BelongsToMany
    {
        return $this->belongsToMany(TagTeam::class, 'stables_tag_teams')
            ->withPivot(['joined_at', 'left_at'])
            ->using(StableTagTeam::class);
    }

    /**
     * Get all current tag teams that are members of the stable.
     *
     * @return BelongsToMany<TagTeam>
     */
    public function currentTagTeams(): BelongsToMany
    {
        return $this->tagTeams()
            ->wherePivotNull('left_at');
    }

    /**
     * Get all previous tag teams that were members of the stable.
     *
     * @return BelongsToMany<TagTeam>
     */
    public function previousTagTeams(): BelongsToMany
    {
        return $this->tagTeams()
            ->wherePivotNotNull('left_at');
    }

    /**
     * Get the managers belonging to the stable.
     *
     * @return BelongsToMany<Manager>
     */
    public function managers(): BelongsToMany
    {
        return $this->belongsToMany(Manager::class, 'stables_managers')
            ->withPivot(['hired_at', 'left_at'])
            ->using(StableManager::class);
    }

    /**
     * Get all current managers that are members of the stable.
     *
     * @return BelongsToMany<Manager>
     */
    public function currentManagers(): BelongsToMany
    {
        return $this->managers()
            ->wherePivotNull('left_at');
    }

    /**
     * Get all previous managers that were members of the stable.
     *
     * @return BelongsToMany<Manager>
     */
    public function previousManagers(): BelongsToMany
    {
        return $this->managers()
            ->wherePivotNotNull('left_at');
    }
}
