<?php

declare(strict_types=1);

namespace App\Builders\Roster;

use App\Builders\Concerns\HasActivityPeriodScopes;
use App\Builders\Concerns\HasRetirementScopes;
use App\Data\Stables\StableMembershipData;
use App\Models\Stables\Stable;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Custom query builder for the Stable model.
 *
 * Provides specialized query methods for filtering stables by their activity periods
 * and business logic state. Uses activity period tracking rather than enum status
 * to determine stable state, providing more accurate business logic representation.
 *
 * ## Business Logic States
 *
 * - **Unestablished**: Created but never debuted/established
 * - **Established**: Currently active with an activity period
 * - **Disbanded**: Previously established but no current activity period
 * - **Future Establishment**: Scheduled to be established in the future
 *
 * ## Member Counting Logic
 *
 * Stables require a minimum of 3 members, where:
 * - Tag teams count as 2 members each (minimum wrestlers in a tag team)
 * - Individual wrestlers count as 1 member each
 * - Managers count as 1 member each
 *
 * ## Key Architecture Decisions
 *
 * This builder uses activity periods rather than the StableStatus enum because:
 * - Activity periods provide accurate time-based tracking
 * - Business operations (debut/disband) work with activity periods
 * - Eliminates dual status system confusion
 * - Aligns with other entity builders (Title, etc.)
 *
 * @template TModel of Stable
 *
 * @extends Builder<TModel>
 *
 * @example
 * ```php
 * // Basic activity states
 * $establishedStables = Stable::query()->established()->get();
 * $unestablishedStables = Stable::query()->unestablished()->get();
 * $disbandedStables = Stable::query()->disbanded()->get();
 *
 * // Member-based filtering
 * $readyStables = Stable::query()
 *     ->established()
 *     ->withMinimumMembers()
 *     ->get();
 *
 * $needingMembers = Stable::query()
 *     ->established()
 *     ->belowMinimumMembers()
 *     ->get();
 *
 * // Complex queries
 * $futureStables = Stable::query()
 *     ->withFutureEstablishment()
 *     ->with('currentWrestlers', 'currentTagTeams', 'managers')
 *     ->get();
 * ```
 *
 * @template TModel of Stable
 *
 * @extends Builder<TModel>
 */
class StableBuilder extends Builder
{
    use HasActivityPeriodScopes;
    use HasRetirementScopes;

    /**
     * Scope a query to include unestablished stables.
     *
     * Filters stables that have been created but never established.
     * These stables exist in the system but haven't debuted yet.
     *
     * @return static The builder instance for method chaining
     */
    public function unestablished(): static
    {
        return $this->whereDoesntHave('activityPeriods');
    }

    /**
     * Scope a query to include established stables.
     *
     * Filters stables that are currently established and can be featured
     * in events and storylines. Established stables have an active activity period.
     *
     * @return static The builder instance for method chaining
     */
    public function established(): static
    {
        return $this->whereHas('currentActivityPeriod');
    }

    /**
     * Scope a query to include disbanded stables.
     *
     * Filters stables that were previously established but are currently disbanded.
     * These stables can be reunited when needed for future storylines.
     *
     * @return static The builder instance for method chaining
     */
    public function disbanded(): static
    {
        return $this->whereHas('previousActivityPeriods')
            ->whereDoesntHave('currentActivityPeriod')
            ->whereDoesntHave('currentRetirement');
    }

    /**
     * Scope a query to include stables with future establishment.
     *
     * Filters stables that are scheduled to be established on a future date.
     * These stables have been planned but their establishment date hasn't
     * arrived yet.
     *
     * @return static The builder instance for method chaining
     */
    public function withFutureEstablishment(): static
    {
        return $this->whereHas('futureActivityPeriod');
    }

    /**
     * Scope a query to include stables with minimum required members.
     *
     * Filters stables that have at least the minimum number of members (3)
     * required for a stable to be considered properly formed.
     * Tag teams count as 2 members each and wrestlers count as 1 each.
     *
     * @return static The builder instance for method chaining
     */
    public function withMinimumMembers(): static
    {
        return $this->where(
            $this->currentMemberCountExpression(),
            '>=',
            StableMembershipData::MINIMUM_MEMBER_COUNT,
        );
    }

    /**
     * Scope a query to include stables below minimum member threshold.
     *
     * Filters stables that have fewer than the minimum number of members
     * and may need additional recruitment or should be disbanded.
     * Tag teams count as 2 members each and wrestlers count as 1 each.
     *
     * @return static The builder instance for method chaining
     */
    public function belowMinimumMembers(): static
    {
        return $this->where(
            $this->currentMemberCountExpression(),
            '<',
            StableMembershipData::MINIMUM_MEMBER_COUNT,
        );
    }

    /**
     * Scope a query to include available stables.
     *
     * Filters stables that are currently available for use in wrestling programming.
     * Available stables are established, have minimum members, and are not retired.
     * This provides a consistent availability interface across all entity types.
     *
     * @return static The builder instance for method chaining
     *
     * @example
     * ```php
     * // Get all available stables for booking
     * $availableStables = Stable::query()->available()->get();
     *
     * ```
     */
    public function available(): static
    {
        return $this->established()
            ->withMinimumMembers()
            ->whereDoesntHave('currentRetirement');
    }

    /**
     * Scope a query to include unavailable stables.
     *
     * Filters stables that cannot currently be used in wrestling programming.
     * This includes stables that are unestablished, disbanded, retired, or below
     * minimum member requirements. Provides a consistent unavailability interface.
     *
     * @return static The builder instance for method chaining
     *
     * @example
     * ```php
     * // Get all unavailable stables
     * $unavailableStables = Stable::query()->unavailable()->get();
     *
     * // Find stables that need attention
     * $needsAttention = Stable::query()
     *     ->unavailable()
     *     ->with(['currentRetirement', 'currentWrestlers'])
     *     ->get();
     * ```
     */
    public function unavailable(): static
    {
        $this->where(function (Builder $query) {
            $query->whereDoesntHave('activityPeriods')
                ->orWhere(function (Builder $subQuery) {
                    $subQuery->whereHas('activityPeriods')
                        ->whereDoesntHave('currentActivityPeriod');
                })
                ->orWhereHas('currentRetirement')
                ->orWhere(
                    $this->currentMemberCountExpression(),
                    '<',
                    StableMembershipData::MINIMUM_MEMBER_COUNT,
                );
        });

        return $this;
    }

    /**
     * Scope a query to include stables with flexible member count.
     *
     * Filters stables that have between the specified minimum and maximum
     * number of members. Provides more flexible member counting than the
     * binary withMinimumMembers() method.
     *
     * @param  int  $min  Minimum number of members required
     * @param  int|null  $max  Maximum number of members (null for no limit)
     * @return static The builder instance for method chaining
     *
     * @example
     * ```php
     * // Get small stables (3-5 members)
     * $smallStables = Stable::query()->withMemberCount(3, 5)->get();
     *
     * // Get large stables (6+ members)
     * $largeStables = Stable::query()->withMemberCount(6)->get();
     * ```
     */
    public function withMemberCount(int $min, ?int $max = null): static
    {
        $this->where($this->currentMemberCountExpression(), '>=', $min);

        if ($max !== null) {
            $this->where($this->currentMemberCountExpression(), '<=', $max);
        }

        return $this;
    }

    private function currentMemberCountExpression(): Expression
    {
        return DB::raw('(
            (SELECT COUNT(*) FROM stables_wrestlers WHERE stable_id = stables.id AND left_at IS NULL) +
            (SELECT COUNT(*) * 2 FROM stables_tag_teams WHERE stable_id = stables.id AND left_at IS NULL)
        )');
    }
}
