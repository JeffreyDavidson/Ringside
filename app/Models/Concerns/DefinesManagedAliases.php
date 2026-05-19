<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Provides concrete convenience methods for accessing managed Wrestlers and Tag Teams
 * through standardized pivot tables. Intended for use on Manager models.
 *
 * This trait assumes the existence of the following pivot tables:
 * - wrestlers_managers
 * - tag_teams_managers
 *
 * It uses shared logic from ManagesEntities to reduce duplication and provides
 * properly typed relationship methods for managers.
 *
 * @example
 * ```php
 * class Manager extends Model
 * {
 *     use DefinesManagedAliases;
 * }
 *
 * $manager = Manager::find(1);
 * $allWrestlers = $manager->wrestlers;
 * $currentWrestlers = $manager->currentWrestlers;
 * $formerWrestlers = $manager->previousWrestlers;
 * ```
 */
trait DefinesManagedAliases
{
    /** @use ManagesEntities<\Illuminate\Database\Eloquent\Model> */
    use ManagesEntities;

    // ==================== WRESTLER RELATIONSHIPS ====================

    /**
     * Get all Wrestlers currently or previously managed by the model.
     *
     * Returns all wrestler relationships regardless of their current status.
     * This includes both active and completed management relationships.
     *
     * @return BelongsToMany<Wrestler, static>
     *                                         A relationship instance for accessing all wrestlers
     *
     * @example
     * ```php
     * $manager = Manager::find(1);
     * $allWrestlers = $manager->wrestlers;
     * $wrestlerCount = $manager->wrestlers()->count();
     * ```
     */
    public function wrestlers(): BelongsToMany
    {
        /** @var BelongsToMany<Wrestler, static> $relation */
        $relation = $this->getManagedRelation(Wrestler::class, 'wrestlers_managers');

        return $relation;
    }

    /**
     * Get currently managed Wrestlers (those without a 'fired_at' timestamp).
     *
     * Returns wrestlers who are actively being managed by this manager.
     *
     * @return BelongsToMany<Wrestler, static>
     *                                         A relationship instance for accessing current wrestlers
     *
     * @example
     * ```php
     * $manager = Manager::find(1);
     * $currentWrestlers = $manager->currentWrestlers;
     *
     * if ($manager->currentWrestlers()->exists()) {
     *     echo "Manager has active wrestlers";
     * }
     * ```
     */
    public function currentWrestlers(): BelongsToMany
    {
        /** @var BelongsToMany<Wrestler, static> $relation */
        $relation = $this->currentManaged(Wrestler::class, 'wrestlers_managers');

        return $relation;
    }

    /**
     * Get previously managed Wrestlers (those with a 'fired_at' timestamp).
     *
     * Returns wrestlers who were once managed but are no longer under management.
     *
     * @return BelongsToMany<Wrestler, static>
     *                                         A relationship instance for accessing previous wrestlers
     *
     * @example
     * ```php
     * $manager = Manager::find(1);
     * $formerWrestlers = $manager->previousWrestlers;
     * $wrestlerHistory = $manager->previousWrestlers()->orderBy('pivot_fired_at', 'desc')->get();
     * ```
     */
    public function previousWrestlers(): BelongsToMany
    {
        /** @var BelongsToMany<Wrestler, static> $relation */
        $relation = $this->previousManaged(Wrestler::class, 'wrestlers_managers');

        return $relation;
    }

    // ==================== TAG TEAM RELATIONSHIPS ====================

    /**
     * Get all Tag Teams currently or previously managed by the model.
     *
     * Returns all tag team relationships regardless of their current status.
     * This includes both active and completed management relationships.
     *
     * @return BelongsToMany<TagTeam, static>
     *                                        A relationship instance for accessing all tag teams
     *
     * @example
     * ```php
     * $manager = Manager::find(1);
     * $allTagTeams = $manager->tagTeams;
     * $tagTeamCount = $manager->tagTeams()->count();
     * ```
     */
    public function tagTeams(): BelongsToMany
    {
        /** @var BelongsToMany<TagTeam, static> $relation */
        $relation = $this->getManagedRelation(TagTeam::class, 'tag_teams_managers');

        return $relation;
    }

    /**
     * Get currently managed Tag Teams (those without a 'fired_at' timestamp).
     *
     * Returns tag teams who are actively being managed by this manager.
     *
     * @return BelongsToMany<TagTeam, static>
     *                                        A relationship instance for accessing current tag teams
     *
     * @example
     * ```php
     * $manager = Manager::find(1);
     * $currentTagTeams = $manager->currentTagTeams;
     *
     * if ($manager->currentTagTeams()->exists()) {
     *     echo "Manager has active tag teams";
     * }
     * ```
     */
    public function currentTagTeams(): BelongsToMany
    {
        /** @var BelongsToMany<TagTeam, static> $relation */
        $relation = $this->currentManaged(TagTeam::class, 'tag_teams_managers');

        return $relation;
    }

    /**
     * Get previously managed Tag Teams (those with a 'fired_at' timestamp).
     *
     * Returns tag teams who were once managed but are no longer under management.
     *
     * @return BelongsToMany<TagTeam, static>
     *                                        A relationship instance for accessing previous tag teams
     *
     * @example
     * ```php
     * $manager = Manager::find(1);
     * $formerTagTeams = $manager->previousTagTeams;
     * $tagTeamHistory = $manager->previousTagTeams()->orderBy('pivot_fired_at', 'desc')->get();
     * ```
     */
    public function previousTagTeams(): BelongsToMany
    {
        /** @var BelongsToMany<TagTeam, static> $relation */
        $relation = $this->previousManaged(TagTeam::class, 'tag_teams_managers');

        return $relation;
    }
}
