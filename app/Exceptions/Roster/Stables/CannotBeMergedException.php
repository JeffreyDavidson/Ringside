<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Stables;

use App\Exceptions\BaseBusinessException;
use App\Models\Stables\Stable;

/**
 * Exception thrown when stables cannot be merged due to business rule violations.
 *
 * This exception handles scenarios where stable merging is prevented by current state
 * or business logic constraints in wrestling promotion stable management.
 *
 * BUSINESS CONTEXT:
 * Stable merging represents the consolidation of two separate factions into a single
 * unified entity, typically due to alliance formation, leadership changes, or strategic
 * roster management. This is a significant narrative event that affects multiple
 * storylines and member relationships while creating new competitive opportunities.
 *
 * COMMON SCENARIOS:
 * - Attempting to merge inactive, retired, or disbanded stables
 * - Trying to merge incompatible stables with conflicting storylines
 * - Member compatibility issues or contractual conflicts
 * - Administrative errors involving invalid stable combinations
 * - Merge conflicts during active championship reigns or major events
 *
 * BUSINESS IMPACT:
 * - Maintains stable compatibility and storyline continuity
 * - Protects ongoing narrative investments and faction dynamics
 * - Ensures merged stable remains competitively viable
 * - Prevents disruption of active storylines and member relationships
 * - Supports proper faction management and booking flexibility
 */
final class CannotBeMergedException extends BaseBusinessException
{
    /**
     * Cannot merge a stable with itself.
     *
     * @param  Stable  $stable  The stable attempting self-merge
     */
    public static function selfMerge(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be merged with itself.");
    }

    /**
     * Primary stable is retired and cannot receive merged members.
     *
     * @param  Stable  $primaryStable  The primary stable that is retired
     */
    public static function primaryRetired(Stable $primaryStable): static
    {
        $context = self::formatModelContext($primaryStable);

        return new self("{$context} is retired and cannot receive merged members.");
    }

    /**
     * Secondary stable is retired and cannot be merged.
     *
     * @param  Stable  $secondaryStable  The secondary stable that is retired
     */
    public static function secondaryRetired(Stable $secondaryStable): static
    {
        $context = self::formatModelContext($secondaryStable);

        return new self("{$context} is retired and cannot be merged.");
    }

    /**
     * Primary stable is not active and cannot receive merged members.
     *
     * @param  Stable  $primaryStable  The primary stable that is not active
     */
    public static function primaryNotActive(Stable $primaryStable): static
    {
        $context = self::formatModelContext($primaryStable);

        return new self("{$context} is not currently active and cannot receive merged members.");
    }

    /**
     * Secondary stable is not active and cannot be merged.
     *
     * @param  Stable  $secondaryStable  The secondary stable that is not active
     */
    public static function secondaryNotActive(Stable $secondaryStable): static
    {
        $context = self::formatModelContext($secondaryStable);

        return new self("{$context} is not currently active and cannot be merged.");
    }

    /**
     * Stables cannot be merged due to conflicting storylines.
     *
     * @param  Stable  $primaryStable  The primary stable
     * @param  Stable  $secondaryStable  The secondary stable
     * @param  string  $conflictDetails  Description of the storyline conflict
     */
    public static function conflictingStorylines(Stable $primaryStable, Stable $secondaryStable, string $conflictDetails): static
    {
        $primaryContext = self::formatModelContext($primaryStable);
        $secondaryContext = self::formatModelContext($secondaryStable);

        return new self("{$primaryContext} and {$secondaryContext} cannot be merged due to conflicting storylines: {$conflictDetails}.");
    }

    /**
     * Stables cannot be merged due to incompatible member relationships.
     *
     * @param  Stable  $primaryStable  The primary stable
     * @param  Stable  $secondaryStable  The secondary stable
     * @param  string  $incompatibilityDetails  Description of member incompatibilities
     */
    public static function incompatibleMembers(Stable $primaryStable, Stable $secondaryStable, string $incompatibilityDetails): static
    {
        $primaryContext = self::formatModelContext($primaryStable);
        $secondaryContext = self::formatModelContext($secondaryStable);

        return new self("{$primaryContext} and {$secondaryContext} cannot be merged due to incompatible members: {$incompatibilityDetails}.");
    }

    /**
     * Stables cannot be merged while members hold conflicting championships.
     *
     * @param  Stable  $primaryStable  The primary stable
     * @param  Stable  $secondaryStable  The secondary stable
     * @param  array<string>  $conflictingTitles  List of conflicting championship titles
     */
    public static function conflictingChampionships(Stable $primaryStable, Stable $secondaryStable, array $conflictingTitles): static
    {
        $primaryContext = self::formatModelContext($primaryStable);
        $secondaryContext = self::formatModelContext($secondaryStable);
        $titles = implode(', ', $conflictingTitles);

        return new self("{$primaryContext} and {$secondaryContext} cannot be merged due to conflicting championships: {$titles}.");
    }

    /**
     * Stables cannot be merged during active feuds between them.
     *
     * @param  Stable  $primaryStable  The primary stable
     * @param  Stable  $secondaryStable  The secondary stable
     * @param  string  $feudDetails  Description of the active feud
     */
    public static function activeFeudBetween(Stable $primaryStable, Stable $secondaryStable, string $feudDetails): static
    {
        $primaryContext = self::formatModelContext($primaryStable);
        $secondaryContext = self::formatModelContext($secondaryStable);

        return new self("{$primaryContext} and {$secondaryContext} cannot be merged during active feud: {$feudDetails}.");
    }

    /**
     * Stables cannot be merged due to upcoming major events.
     *
     * @param  Stable  $primaryStable  The primary stable
     * @param  Stable  $secondaryStable  The secondary stable
     * @param  string  $upcomingEvent  Description of the upcoming event
     */
    public static function upcomingMajorEvent(Stable $primaryStable, Stable $secondaryStable, string $upcomingEvent): static
    {
        $primaryContext = self::formatModelContext($primaryStable);
        $secondaryContext = self::formatModelContext($secondaryStable);

        return new self("{$primaryContext} and {$secondaryContext} cannot be merged due to upcoming major event: {$upcomingEvent}.");
    }
}
