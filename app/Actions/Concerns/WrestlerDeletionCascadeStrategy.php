<?php

declare(strict_types=1);

namespace App\Actions\Concerns;

use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Wrestler deletion cascade strategies for relationship cleanup during deletion.
 *
 * This class provides strategies for automatically ending relationships when a wrestler
 * is deleted. Wrestler deletion involves ending all current professional relationships
 * while preserving historical records for administrative purposes.
 *
 * BUSINESS CONTEXT:
 * When a wrestler is deleted:
 * - Tag team partnerships are dissolved (wrestlers leave teams)
 * - Stable memberships end (wrestler leaves all stables)
 * - Manager relationships terminate (managers may manage other talent)
 * - Championships are vacated (titles become available)
 * - All professional relationships end but historical records are preserved
 *
 * DESIGN PATTERN:
 * Strategy pattern - each method returns a callable strategy that can be used
 * with StatusTransitionPipeline.withCascade() or called directly.
 *
 * @example
 * ```php
 * // Delete a wrestler and end all professional relationships
 * StatusTransitionPipeline::release($wrestler, $date)
 *     ->withCascade(WrestlerDeletionCascadeStrategy::endAllRelationships())
 *     ->execute();
 * ```
 */
class WrestlerDeletionCascadeStrategy
{
    /**
     * Strategy to end all current tag team partnerships.
     *
     * When a wrestler is deleted, they leave all tag teams and the partnerships
     * are dissolved. Tag teams may need to find new members or also be deleted.
     *
     * @return callable Strategy function for ending tag team partnerships
     */
    public static function endTagTeamPartnerships(): callable
    {
        return function (Model $entity, Carbon $date, string $transition): void {
            // Check if entity is a wrestler with tag team relationships
            if (! $entity instanceof Wrestler || ! method_exists($entity, 'tagTeams')) {
                return;
            }

            // End all current tag team partnerships
            $entity->tagTeams()->wherePivotNull('left_at')->updateExistingPivot(
                $entity->tagTeams()->wherePivotNull('left_at')->pluck('tag_team_id'),
                ['left_at' => $date]
            );
        };
    }

    /**
     * Strategy to end all current stable memberships.
     *
     * When a wrestler is deleted, they leave all stables. Stables continue
     * operating with remaining members.
     *
     * @return callable Strategy function for ending stable memberships
     */
    public static function endStableMemberships(): callable
    {
        return function (Model $entity, Carbon $date, string $transition): void {
            // Check if entity is a wrestler with stable relationships
            if (! $entity instanceof Wrestler || ! method_exists($entity, 'stables')) {
                return;
            }

            // End all current stable memberships
            $entity->stables()->wherePivotNull('left_at')->updateExistingPivot(
                $entity->stables()->wherePivotNull('left_at')->pluck('stable_id'),
                ['left_at' => $date]
            );
        };
    }

    /**
     * Strategy to end all current manager relationships.
     *
     * When a wrestler is deleted, all management contracts are terminated.
     * Managers may continue managing other talent.
     *
     * @return callable Strategy function for ending manager relationships
     */
    public static function endManagerRelationships(): callable
    {
        return function (Model $entity, Carbon $date, string $transition): void {
            // Check if entity is a wrestler with manager relationships
            if (! $entity instanceof Wrestler || ! method_exists($entity, 'managers')) {
                return;
            }

            // End all current manager relationships
            $entity->managers()->wherePivotNull('fired_at')->updateExistingPivot(
                $entity->managers()->wherePivotNull('fired_at')->pluck('manager_id'),
                ['fired_at' => $date]
            );
        };
    }

    /**
     * Strategy to vacate all current championships.
     *
     * When a wrestler is deleted, they must vacate all held championships
     * as they will no longer be able to defend them.
     *
     * @return callable Strategy function for vacating championships
     */
    public static function vacateChampionships(): callable
    {
        return function (Model $entity, Carbon $date, string $transition): void {
            // Check if entity is a wrestler with championship relationships
            if (! $entity instanceof Wrestler || ! method_exists($entity, 'currentChampionships')) {
                return;
            }

            // Vacate all current championships
            $entity->currentChampionships()->update(['lost_at' => $date]);
        };
    }

    /**
     * Combined strategy to end all professional relationships.
     *
     * This represents a complete career ending where all professional
     * relationships and commitments are terminated due to deletion.
     *
     * @return callable Strategy function for ending all relationships
     */
    public static function endAllRelationships(): callable
    {
        return function (Model $entity, Carbon $date, string $transition): void {
            // Execute all individual strategies
            static::endTagTeamPartnerships()($entity, $date, $transition);
            static::endStableMemberships()($entity, $date, $transition);
            static::endManagerRelationships()($entity, $date, $transition);
            static::vacateChampionships()($entity, $date, $transition);
        };
    }
}
