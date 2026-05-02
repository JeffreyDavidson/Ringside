<?php

declare(strict_types=1);

namespace App\Actions\Concerns;

use App\Models\Managers\Manager;
use App\Models\Wrestlers\Wrestler;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Unretirement cascade strategies for automatic relationship-based unretirement.
 *
 * This class provides strategies for automatically unretiring related entities when
 * a primary entity is unretired. Common cascading scenarios include unretiring
 * wrestlers and managers when a tag team comes out of retirement.
 *
 * BUSINESS CONTEXT:
 * When a tag team is unretired (comes out of retirement):
 * - The team returns to active status and becomes available for competition
 * - Wrestlers and managers may come out of retirement with the team
 * - Not all members may be able to unretire (contracts, health, availability)
 * - Failed unretirements should not prevent the team unretirement
 * - Employment may follow unretirement as a separate business decision
 *
 * DESIGN PATTERN:
 * Strategy pattern - each method returns a callable strategy that can be used
 * for post-unretirement relationship management.
 *
 * Note: Unretirement is not a typical StatusTransitionPipeline operation as it
 * involves ending retirement rather than starting a new status.
 *
 * @example
 * ```php
 * // After unretiring a tag team, cascade to eligible members
 * UnretirementCascadeStrategy::conditionalMembers(true)($tagTeam, $date, 'unretire');
 * ```
 */
class UnretirementCascadeStrategy
{
    /**
     * Strategy to unretire all eligible wrestlers of the entity.
     *
     * Only attempts to unretire wrestlers who are currently retired.
     * Gracefully handles failures - if a wrestler cannot be unretired,
     * the operation continues for other members.
     *
     * @return callable Strategy function for wrestler unretirement cascade
     */
    public static function wrestlers(): callable
    {
        return function (Model $entity, Carbon $date, string $operation): void {
            // Only cascade on unretirement operations
            if ($operation !== 'unretire') {
                return;
            }

            // Check if entity has wrestler relationships (tag teams)
            if (! method_exists($entity, 'currentWrestlers')) {
                return;
            }

            // Get retired wrestlers who could potentially be unretired
            $retiredWrestlers = $entity->currentWrestlers
                ->filter(fn (Wrestler $wrestler) => $wrestler->isRetired());

            // Attempt to unretire each retired wrestler
            foreach ($retiredWrestlers as $wrestler) {
                try {
                    // Note: This would need to use the wrestler's UnretireAction
                    // For now, we'll end the retirement directly
                    $wrestler->retirements()->whereNull('ended_at')->update(['ended_at' => $date]);
                } catch (Exception $e) {
                    // Gracefully handle failures - continue with other members
                    // Individual wrestler unretirement failure should not stop team unretirement
                    continue;
                }
            }
        };
    }

    /**
     * Strategy to unretire all eligible managers of the entity.
     *
     * Only attempts to unretire managers who are currently retired.
     * Gracefully handles failures - if a manager cannot be unretired,
     * the operation continues for other members.
     *
     * @return callable Strategy function for manager unretirement cascade
     */
    public static function managers(): callable
    {
        return function (Model $entity, Carbon $date, string $operation): void {
            // Only cascade on unretirement operations
            if ($operation !== 'unretire') {
                return;
            }

            // Check if entity has manager relationships
            if (! method_exists($entity, 'currentManagers')) {
                return;
            }

            // Get retired managers who could potentially be unretired
            $retiredManagers = $entity->currentManagers
                ->filter(fn (Manager $manager) => $manager->isRetired());

            // Attempt to unretire each retired manager
            foreach ($retiredManagers as $manager) {
                try {
                    // Note: This would need to use the manager's UnretireAction
                    // For now, we'll end the retirement directly
                    $manager->retirements()->whereNull('ended_at')->update(['ended_at' => $date]);
                } catch (Exception $e) {
                    // Gracefully handle failures - continue with other members
                    // Individual manager unretirement failure should not stop team unretirement
                    continue;
                }
            }
        };
    }

    /**
     * Combined strategy to unretire all eligible members (wrestlers and managers).
     *
     * This represents a complete team comeback where all eligible members
     * are unretired together with graceful error handling.
     *
     * @return callable Strategy function for complete member unretirement cascade
     */
    public static function allMembers(): callable
    {
        return function (Model $entity, Carbon $date, string $operation): void {
            // Only cascade on unretirement operations
            if ($operation !== 'unretire') {
                return;
            }

            // Execute individual strategies
            static::wrestlers()($entity, $date, $operation);
            static::managers()($entity, $date, $operation);
        };
    }

    /**
     * Conditional strategy to unretire members only when explicitly requested.
     *
     * This allows for dynamic cascade behavior based on business logic
     * or user preferences (e.g., unretirePartners parameter).
     *
     * @param  bool  $shouldUnretireMembers  Whether to actually unretire the members
     * @return callable Strategy function for conditional member unretirement
     */
    public static function conditionalMembers(bool $shouldUnretireMembers): callable
    {
        return function (Model $entity, Carbon $date, string $operation) use ($shouldUnretireMembers): void {
            // Only cascade if explicitly requested and on unretirement operations
            if (! $shouldUnretireMembers || $operation !== 'unretire') {
                return;
            }

            // Execute the complete member unretirement strategy
            static::allMembers()($entity, $date, $operation);
        };
    }

    /**
     * Employment cascade strategy to employ the team immediately after unretirement.
     *
     * This handles the common scenario where a team comes out of retirement
     * and is immediately employed for active competition.
     *
     * @param  bool  $shouldEmployImmediately  Whether to employ after unretirement
     * @return callable Strategy function for post-unretirement employment
     */
    public static function employmentFollowup(bool $shouldEmployImmediately): callable
    {
        return function (Model $entity, Carbon $date, string $operation) use ($shouldEmployImmediately): void {
            // Only execute if requested and on unretirement operations
            if (! $shouldEmployImmediately || $operation !== 'unretire') {
                return;
            }

            // Check if entity can be employed
            if (! method_exists($entity, 'isEmployed') || $entity->isEmployed()) {
                return;
            }

            // For tag teams: skip auto-employment if no current wrestler partners.
            // ensureCanBeEmployed enforces partner availability and would throw,
            // breaking the unretire flow. The team can be employed later once
            // partners are attached.
            if (method_exists($entity, 'currentWrestlers') && $entity->currentWrestlers()->count() === 0) {
                return;
            }

            // Employ the entity using StatusTransitionPipeline
            StatusTransitionPipeline::employ($entity, $date)
                ->withCascade(EmploymentCascadeStrategy::wrestlers())
                ->withCascade(EmploymentCascadeStrategy::managers())
                ->execute();
        };
    }
}
