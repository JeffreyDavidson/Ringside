<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Stables;

use App\Exceptions\BaseBusinessException;
use App\Models\Stables\Stable;

/**
 * Exception thrown when a stable cannot be split due to business rule violations.
 *
 * This exception handles scenarios where stable splitting is prevented by current state
 * or business logic constraints in wrestling promotion stable management.
 *
 * BUSINESS CONTEXT:
 * Stable splitting represents the division of an existing faction into two separate
 * entities, typically due to internal conflicts, storyline developments, or strategic
 * roster changes. This is a significant narrative event that affects member relationships
 * and ongoing storylines while creating new faction dynamics and competitive opportunities.
 *
 * COMMON SCENARIOS:
 * - Attempting to split inactive, retired, or disbanded stables
 * - Trying to split stables with insufficient members for viable division
 * - Split conflicts with active storylines, championships, or ongoing feuds
 * - Member distribution that would leave one faction non-viable
 * - Administrative errors involving stables not eligible for splitting
 *
 * BUSINESS IMPACT:
 * - Maintains stable viability and member distribution integrity
 * - Protects ongoing narrative investments and faction dynamics
 * - Ensures both resulting stables remain competitively viable
 * - Prevents disruption of active storylines and championship pursuits
 * - Supports proper faction management and booking flexibility
 */
final class CannotBeSplitException extends BaseBusinessException
{
    /**
     * Stable is retired and cannot be split.
     *
     * @param  Stable  $stable  The stable that cannot be split
     */
    public static function retired(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} is retired and cannot be split.");
    }

    /**
     * Stable is not currently active and cannot be split.
     *
     * @param  Stable  $stable  The stable that cannot be split
     */
    public static function notActive(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} is not currently active and cannot be split.");
    }

    /**
     * Stable has insufficient members for splitting.
     *
     * @param  Stable  $stable  The stable that cannot be split
     * @param  int  $currentMembers  Number of current members
     * @param  int  $minimumRequired  Minimum members required for split
     */
    public static function insufficientMembers(Stable $stable, int $currentMembers, int $minimumRequired): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} has only {$currentMembers} members but requires at least {$minimumRequired} members to split.");
    }

    /**
     * No members specified to move to new stable.
     */
    public static function noMembersToMove(): static
    {
        return new self('Cannot split stable: at least one member must be moved to the new stable.');
    }

    /**
     * All members would be moved, leaving original stable empty.
     */
    public static function allMembersMoving(): static
    {
        return new self('Cannot split stable: at least one member must remain in the original stable.');
    }

    /**
     * Stable cannot be split during active championship reigns.
     *
     * @param  Stable  $stable  The stable that cannot be split
     * @param  array<string>  $championshipTitles  List of championship titles held by members
     */
    public static function membersHoldingTitles(Stable $stable, array $championshipTitles): static
    {
        $context = self::formatModelContext($stable);
        $titles = implode(', ', $championshipTitles);

        return new self("{$context} cannot be split while members hold championships: {$titles}.");
    }

    /**
     * Stable cannot be split during active storyline participation.
     *
     * @param  Stable  $stable  The stable that cannot be split
     * @param  string  $storylineDetails  Description of the active storyline
     */
    public static function activeStoryline(Stable $stable, string $storylineDetails): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be split during active storyline: {$storylineDetails}.");
    }

    /**
     * Stable cannot be split during active feud participation.
     *
     * @param  Stable  $stable  The stable that cannot be split
     * @param  string  $feudDetails  Description of the active feud
     */
    public static function activeFeud(Stable $stable, string $feudDetails): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be split during active feud: {$feudDetails}.");
    }

    /**
     * Stable cannot be split due to upcoming major events.
     *
     * @param  Stable  $stable  The stable that cannot be split
     * @param  string  $upcomingEvent  Description of the upcoming event
     */
    public static function upcomingMajorEvent(Stable $stable, string $upcomingEvent): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be split due to upcoming major event: {$upcomingEvent}.");
    }
}
