<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Exceptions\BusinessRules\MembershipConflictException;
use App\Models\Stables\Stable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Contract for models that can belong to stables.
 *
 * This interface defines the standard contract for any model that can be
 * a member of a stable organization. This includes Wrestlers, TagTeams,
 * and Managers who can join and leave stables over time.
 *
 * Models implementing this interface should provide access to both current
 * and historical stable relationships with proper time-based filtering.
 *
 * @template TModel of Model The model that can belong to stables
 * @template TPivotModel of Pivot The pivot model for stable membership
 *
 * @example
 * ```php
 * class Wrestler extends Model implements HasStableMembership
 * {
 *     use CanJoinStables;
 *
 *     // Usage:
 *     // $wrestler->currentStable() - Get current stable membership
 *     // $wrestler->previousStables - Get former stable memberships
 *     // $wrestler->stables - Get all stable memberships (past and present)
 * }
 * ```
 */
interface HasStableMembership
{
    /**
     * Get all stables this entity has ever belonged to.
     *
     * This method should return a BelongsToMany relationship that provides access
     * to all stable records the entity has been associated with, including both
     * current and former memberships. This represents the full historical record.
     *
     * @return BelongsToMany<Stable, TModel, TPivotModel>
     *                                                    A relationship instance for accessing all stables
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $allStables = $wrestler->stables;
     * $stableCount = $wrestler->stables()->count();
     * $stableNames = $wrestler->stables->pluck('name');
     * ```
     */
    public function stables(): BelongsToMany;

    /**
     * Get the current stable this entity belongs to.
     *
     * This method should return a BelongsTo relationship or query that provides
     * access to the stable the entity is currently a member of. Returns null
     * if the entity is not currently in a stable.
     *
     * @return BelongsTo<Stable, TModel>|null
     *                                        A relationship instance for the current stable, or null
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $currentStable = $wrestler->currentStable();
     *
     * if ($currentStable) {
     *     echo "Wrestler is currently in: " . $currentStable->name;
     * }
     * ```
     */
    public function currentStable(): ?BelongsTo;

    /**
     * Get stables this entity previously belonged to but has since left.
     *
     * This method should return a BelongsToMany relationship for stables
     * the entity was once a member of but is no longer associated with
     * (i.e., have a 'left_at' date).
     *
     * @return BelongsToMany<Stable, TModel, TPivotModel>
     *                                                    A relationship instance for accessing previous stables
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $formerStables = $wrestler->previousStables;
     * $stableHistory = $wrestler->previousStables()
     *     ->orderBy('left_at', 'desc')
     *     ->get();
     *
     * // Get stables left in the last year
     * $recentlyLeft = $wrestler->previousStables()
     *     ->where('left_at', '>=', now()->subYear())
     *     ->get();
     * ```
     */
    public function previousStables(): BelongsToMany;

    /**
     * Join a stable as a member.
     *
     * This method should establish a new membership relationship with the given
     * stable, setting appropriate dates and validating business rules.
     *
     * @param  Stable  $stable  The stable to join
     * @param  Carbon|null  $startDate  The membership start date (defaults to now)
     * @return bool True if the entity successfully joined the stable
     *
     * @throws MembershipConflictException If the entity cannot join
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $stable = Stable::find(2);
     *
     * if ($wrestler->joinStable($stable)) {
     *     echo "Wrestler successfully joined the stable";
     * }
     * ```
     */
    public function joinStable(Stable $stable, ?Carbon $startDate = null): bool;

    /**
     * Leave the current stable membership.
     *
     * This method should end the current stable membership relationship
     * by setting the appropriate end date.
     *
     * @param  Carbon|null  $endDate  The membership end date (defaults to now)
     * @return bool True if the entity successfully left the stable
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     *
     * if ($wrestler->leaveStable()) {
     *     echo "Wrestler successfully left their stable";
     * }
     * ```
     */
    public function leaveStable(?Carbon $endDate = null): bool;

    /**
     * Check if the entity is currently a member of any stable.
     *
     * @return bool True if the entity belongs to a stable, false otherwise
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     *
     * if ($wrestler->isInStable()) {
     *     echo "Wrestler is currently in a stable";
     * }
     * ```
     */
    public function isInStable(): bool;

    /**
     * Check if the entity can join the specified stable.
     *
     * This method should validate business rules around stable membership,
     * such as current membership status, stable capacity, and eligibility.
     *
     * @param  Stable  $stable  The stable to check eligibility for
     * @return bool True if the entity can join the stable, false otherwise
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $stable = Stable::find(2);
     *
     * if ($wrestler->canJoinStable($stable)) {
     *     $wrestler->joinStable($stable);
     * }
     * ```
     */
    public function canJoinStable(Stable $stable): bool;
}
