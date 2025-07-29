<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Stables;

use App\Exceptions\BaseBusinessException;
use App\Models\Stables\Stable;

/**
 * Exception thrown when a stable cannot be soft deleted due to business rule violations.
 *
 * This exception handles scenarios where stable soft deletion is prevented by current operational
 * state or business logic constraints. Since this is soft deletion, the focus is on current
 * operational constraints rather than data preservation concerns.
 *
 * BUSINESS CONTEXT:
 * Soft deletion allows stables to be removed from active operations while preserving all
 * historical data for future reference and potential restoration. This is typically used
 * for administrative cleanup, temporary removal, or when stables need to be archived
 * without losing their complete history and relationships.
 *
 * COMMON SCENARIOS:
 * - Attempting to delete currently active stables that should be disbanded first
 * - Trying to delete stables with current members who should be removed first
 * - Administrative validation to ensure proper stable lifecycle management
 * - Operational constraints during active storylines or championship reigns
 *
 * BUSINESS IMPACT:
 * - Maintains proper stable lifecycle procedures and operational integrity
 * - Ensures current members are properly handled before stable removal
 * - Protects against accidental deletion of operationally active stables
 * - Supports proper administrative workflows for stable management
 * - All historical data is preserved since this is soft deletion (recoverable)
 */
final class CannotBeDeletedException extends BaseBusinessException
{
    /**
     * Stable is currently active and should be disbanded before deletion.
     *
     * @param  Stable  $stable  The stable that cannot be deleted
     */
    public static function currentlyActive(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} is currently active and cannot be deleted. Use disband action first.");
    }

    /**
     * Stable has current members who must be removed before deletion.
     *
     * @param  Stable  $stable  The stable that cannot be deleted
     * @param  int  $memberCount  Number of current members
     */
    public static function hasCurrentMembers(Stable $stable, int $memberCount): static
    {
        $context = self::formatModelContext($stable);
        $memberText = $memberCount === 1 ? 'member' : 'members';

        return new self("{$context} has {$memberCount} current {$memberText} and cannot be deleted. Remove members first or use disband action.");
    }

    /**
     * Stable cannot be deleted while members hold active championship titles.
     *
     * @param  Stable  $stable  The stable that cannot be deleted
     * @param  array<string>  $championshipTitles  List of championship titles held by members
     */
    public static function membersHoldingTitles(Stable $stable, array $championshipTitles): static
    {
        $context = self::formatModelContext($stable);
        $titles = implode(', ', $championshipTitles);

        return new self("{$context} cannot be deleted while members hold championships: {$titles}. Complete championship storylines first.");
    }

    /**
     * Stable cannot be deleted during active storyline participation.
     *
     * @param  Stable  $stable  The stable that cannot be deleted
     * @param  string  $storylineDetails  Description of the active storyline
     */
    public static function activeStoryline(Stable $stable, string $storylineDetails): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be deleted during active storyline: {$storylineDetails}. Complete storyline first.");
    }

    /**
     * Stable cannot be deleted due to upcoming scheduled events.
     *
     * @param  Stable  $stable  The stable that cannot be deleted
     * @param  string  $upcomingEvent  Description of the upcoming event
     */
    public static function upcomingScheduledEvent(Stable $stable, string $upcomingEvent): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be deleted due to upcoming scheduled event: {$upcomingEvent}.");
    }

    /**
     * Stable cannot be deleted while involved in active feuds.
     *
     * @param  Stable  $stable  The stable that cannot be deleted
     * @param  string  $feudDetails  Description of the active feud
     */
    public static function activeFeud(Stable $stable, string $feudDetails): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be deleted during active feud: {$feudDetails}. Resolve feud first.");
    }

    /**
     * Stable cannot be deleted without proper administrative authorization.
     *
     * @param  Stable  $stable  The stable that cannot be deleted
     * @param  string  $authorizationLevel  Required authorization level for deletion
     */
    public static function insufficientAuthorization(Stable $stable, string $authorizationLevel): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be deleted without {$authorizationLevel} authorization.");
    }
}
