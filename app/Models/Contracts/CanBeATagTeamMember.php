<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use Ankurk91\Eloquent\Relations\BelongsToOne;
use App\Models\TagTeams\TagTeam;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Contract for models that can be members of tag teams.
 *
 * This interface defines the basic contract for any model that can be a member
 * of tag teams. It provides a standard way to access tag team relationships
 * across different model types while maintaining type safety through generics.
 *
 * Models implementing this interface should also use the CanJoinTagTeams trait
 * to get the complete tag team membership functionality implementation.
 *
 * @template TPivotModel of Pivot The pivot model for the tag team relationship
 * @template TModel of Model The model that can be a tag team member
 *
 * @see CanJoinTagTeams For the trait implementation
 *
 * @example
 * ```php
 * class Wrestler extends Model implements CanBeATagTeamMember
 * {
 *     use CanJoinTagTeams;
 * }
 * ```
 */
interface CanBeATagTeamMember
{
    /**
     * Get all tag teams this model has been a part of.
     *
     * This method should return a BelongsToMany relationship that provides access
     * to all tag team records associated with the model, regardless of status.
     *
     * @return BelongsToMany<TagTeam, TModel, TPivotModel>
     *                                                     A relationship instance for accessing all tag teams
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $allTagTeams = $wrestler->tagTeams;
     * $teamCount = $wrestler->tagTeams()->count();
     * ```
     */
    public function tagTeams(): BelongsToMany;

    /**
     * Get the tag team the model currently belongs to.
     *
     * This method should return a BelongsToOne relationship for the currently
     * active tag team membership.
     *
     * @return BelongsToOne
     *                      A relationship instance for accessing the current tag team
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $currentTeam = $wrestler->currentTagTeam;
     *
     * if ($wrestler->currentTagTeam()->exists()) {
     *     echo "Wrestler is currently in a tag team";
     * }
     * ```
     */
    public function currentTagTeam(): BelongsToOne;

    /**
     * Get the most recent previous tag team (if any).
     *
     * This method should return a BelongsToOne relationship for the most
     * recently left tag team.
     *
     * @return BelongsToOne
     *                      A relationship instance for accessing the previous tag team
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $previousTeam = $wrestler->previousTagTeam;
     *
     * if ($wrestler->previousTagTeam()->exists()) {
     *     echo "Wrestler was previously in: " . $wrestler->previousTagTeam->name;
     * }
     * ```
     */
    public function previousTagTeam(): BelongsToOne;

    /**
     * Get all tag teams the model has previously been a part of.
     *
     * This method should return a BelongsToMany relationship that provides access
     * to all completed tag team memberships.
     *
     * @return BelongsToMany<TagTeam, TModel, TPivotModel>
     *                                                     A relationship instance for accessing previous tag teams
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $formerTeams = $wrestler->previousTagTeams;
     * $teamHistory = $wrestler->previousTagTeams()->orderBy('ended_at', 'desc')->get();
     * ```
     */
    public function previousTagTeams(): BelongsToMany;

    /**
     * Determine whether the model is currently part of an active tag team.
     *
     * This method should check if there is an active tag team membership.
     *
     * @return bool True if the model is currently in a tag team, false otherwise
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     *
     * if ($wrestler->isAMemberOfCurrentTagTeam()) {
     *     echo "Wrestler is currently in a tag team";
     * }
     * ```
     */
    public function isAMemberOfCurrentTagTeam(): bool;
}
