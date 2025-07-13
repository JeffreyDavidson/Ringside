<?php

declare(strict_types=1);

namespace App\Builders\Titles;

use App\Builders\Concerns\HasRetirementScopes;
use App\Builders\Contracts\HasAvailability;
use App\Models\Titles\Title;
use Illuminate\Database\Eloquent\Builder;

/**
 * Custom query builder for the Title model.
 *
 * Provides specialized query methods for filtering titles by their activity periods
 * and business logic state. Uses activity period tracking rather than enum status
 * to determine title state, providing more accurate business logic representation.
 *
 * ## Business Logic States
 *
 * - **Undebuted**: Created but never debuted/activated
 * - **Active**: Currently active with an activity period
 * - **Inactive**: Previously active but no current activity period
 * - **Future Debut**: Scheduled to be activated in the future
 *
 * ## Championship Availability
 *
 * Titles have simplified status compared to roster members:
 * - Only activation/deactivation and retirement states matter
 * - No employment, injury, or suspension concepts
 * - Competable = Active (can be defended or won)
 *
 * ## Key Architecture Decisions
 *
 * This builder uses activity periods rather than the TitleStatus enum because:
 * - Activity periods provide accurate time-based tracking
 * - Business operations (debut/pull/reinstate) work with activity periods
 * - Eliminates dual status system confusion
 * - Aligns with other entity builders (Stable, etc.)
 *
 * @template TModel of Title
 *
 * @extends Builder<TModel>
 *
 * @example
 * ```php
 * // Basic activity states
 * $activeTitles = Title::query()->active()->get();
 * $undebutedTitles = Title::query()->undebuted()->get();
 * $inactiveTitles = Title::query()->inactive()->get();
 *
 * // Championship availability
 * $competableTitles = Title::query()->competable()->get();
 *
 * // Future debuts
 * $futureTitles = Title::query()->withPendingDebut()->get();
 *
 * // Complex queries
 * $vacantActiveTitles = Title::query()
 *     ->active()
 *     ->whereDoesntHave('currentChampionship')
 *     ->get();
 * ```
 *
 * @template TModel of \App\Models\Titles\Title
 *
 * @extends Builder<TModel>
 */
class TitleBuilder extends Builder implements HasAvailability
{
    use HasRetirementScopes;

    /**
     * Scope a query to include undebuted titles.
     *
     * Filters titles that have been created but never debuted.
     * These titles exist in the system but haven't been introduced yet.
     *
     * @return static The builder instance for method chaining
     */
    public function undebuted(): static
    {
        return $this->whereDoesntHave('activityPeriods');
    }

    /**
     * Scope a query to include active titles.
     *
     * Filters titles that are currently active and can be featured
     * in events and championship matches. Active titles have a current activity period.
     *
     * @return static The builder instance for method chaining
     */
    public function active(): static
    {
        return $this->whereHas('currentActivityPeriod');
    }

    /**
     * Scope a query to include inactive titles.
     *
     * Filters titles that were previously active but are currently inactive.
     * These titles can be reinstated when needed for future use.
     *
     * @return static The builder instance for method chaining
     */
    public function inactive(): static
    {
        return $this->whereHas('activityPeriods')
            ->whereDoesntHave('currentActivityPeriod');
    }

    /**
     * Scope a query to include titles with future debut.
     *
     * Filters titles that are scheduled to be activated on a future date.
     * These titles have been planned but their debut date hasn't arrived yet.
     *
     * @return static The builder instance for method chaining
     */
    public function withPendingDebut(): static
    {
        return $this->whereHas('futureActivityPeriod');
    }

    /**
     * Scope a query to include available titles.
     *
     * Filters titles that are currently available for use in championship matches.
     * Available titles are active and can be defended or awarded to new champions.
     * This provides a consistent availability interface across all entity types.
     *
     * @return static The builder instance for method chaining
     *
     * @example
     * ```php
     * // Get all available titles for match booking
     * $availableTitles = Title::query()->available()->get();
     *
     * // Find available titles without current champions
     * $vacantTitles = Title::query()
     *     ->available()
     *     ->whereDoesntHave('currentChampionship')
     *     ->get();
     * ```
     */
    public function available(): static
    {
        return $this->active();
    }

    /**
     * Scope a query to include unavailable titles.
     *
     * Filters titles that cannot currently be used in championship matches.
     * This includes titles that are inactive, retired, or have never been debuted.
     * Provides a consistent unavailability interface across all entity types.
     *
     * @return static The builder instance for method chaining
     *
     * @example
     * ```php
     * // Get all unavailable titles
     * $unavailableTitles = Title::query()->unavailable()->get();
     *
     * // Find titles that need attention (inactive or retired)
     * $needsAttention = Title::query()
     *     ->unavailable()
     *     ->with(['currentRetirement'])
     *     ->get();
     * ```
     */
    public function unavailable(): static
    {
        $this->where(function (Builder $query) {
            $query->whereDoesntHave('activityPeriods') // Never debuted
                ->orWhere(function (Builder $subQuery) {
                    $subQuery->whereHas('activityPeriods')
                        ->whereDoesntHave('currentActivityPeriod'); // Inactive
                })
                ->orWhereHas('currentRetirement'); // Retired
        });

        return $this;
    }

    /**
     * Scope a query to include competable titles.
     *
     * Filters titles that are currently active and available for competition.
     * Competable titles can be defended in matches and awarded to new champions.
     * This method provides semantic clarity for match booking contexts.
     *
     * @return static The builder instance for method chaining
     *
     * @example
     * ```php
     * $competableTitles = Title::query()->competable()->get();
     * ```
     */
    public function competable(): static
    {
        return $this->active();
    }

    /**
     * Scope a query to include vacant titles.
     *
     * Filters titles that are currently active but do not have a current champion.
     * Vacant titles are available to be awarded to new champions in matches.
     *
     * @return static The builder instance for method chaining
     *
     * @example
     * ```php
     * // Get all vacant titles that need new champions
     * $vacantTitles = Title::query()->vacant()->get();
     *
     * // Find vacant titles for upcoming tournament
     * $tournamentTitles = Title::query()
     *     ->vacant()
     *     ->whereIn('id', $tournamentTitleIds)
     *     ->get();
     * ```
     */
    public function vacant(): static
    {
        return $this->active()
            ->whereDoesntHave('currentChampionship');
    }

    /**
     * Scope a query to include titles with championship history.
     *
     * Filters titles that have been defended at least once, meaning they have
     * had championship reigns in the past. This excludes newly created titles
     * that have never been awarded.
     *
     * @return static The builder instance for method chaining
     *
     * @example
     * ```php
     * // Get all titles with championship history
     * $defendedTitles = Title::query()->defended()->get();
     *
     * // Find titles with recent championship activity
     * $activeTitles = Title::query()
     *     ->defended()
     *     ->whereHas('championships', function ($query) {
     *         $query->where('started_at', '>', now()->subYear());
     *     })
     *     ->get();
     * ```
     */
    public function defended(): static
    {
        return $this->whereHas('championships');
    }

    /**
     * Scope a query to include new titles without championship history.
     *
     * Filters titles that have never been awarded to any champion.
     * These are newly created titles that are ready for their inaugural
     * championship reign.
     *
     * @return static The builder instance for method chaining
     *
     * @example
     * ```php
     * // Get all new titles ready for first champions
     * $newTitles = Title::query()->newTitles()->get();
     *
     * // Find active new titles for debut matches
     * $debutTitles = Title::query()
     *     ->newTitles()
     *     ->active()
     *     ->get();
     * ```
     */
    public function newTitles(): static
    {
        return $this->whereDoesntHave('championships');
    }

    /**
     * Scope a query to include unretired titles.
     *
     * Filters titles that are not currently retired and can potentially
     * be used in competition or reactivated.
     *
     * @return static The builder instance for method chaining
     */
    public function unretired(): static
    {
        return $this->whereDoesntHave('currentRetirement');
    }
}
