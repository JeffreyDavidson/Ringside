<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Stables;

use App\Exceptions\BaseBusinessException;
use App\Models\Stables\Stable;

/**
 * Exception thrown when a stable cannot be disbanded due to business rule violations.
 *
 * This exception handles scenarios where stable disbandment is prevented by current state
 * or business logic constraints in wrestling promotion stable management.
 *
 * BUSINESS CONTEXT:
 * Stable disbandment represents the formal dissolution of a wrestling faction, marking
 * the end of collective storylines and member alliances. This is a significant narrative
 * event that affects multiple roster members simultaneously and often serves as a
 * climactic conclusion to long-running feuds and storylines. Proper disbandment procedures
 * ensure narrative satisfaction while maintaining continuity for individual member careers.
 *
 * COMMON SCENARIOS:
 * - Attempting to disband an inactive, unactivated, or already disbanded stable
 * - Trying to disband stables that are permanently retired from competition
 * - Disbandment conflicts with active storylines, championship reigns, or ongoing feuds
 * - Missing activation prerequisites or improper stable lifecycle management
 * - Administrative errors involving stables not eligible for standard disbandment procedures
 * - Premature disbandment attempts during critical storyline developments or major events
 *
 * BUSINESS IMPACT:
 * - Maintains stable lifecycle integrity and member relationship consistency across storylines
 * - Protects ongoing narrative investments and multi-member storyline development
 * - Ensures proper member release and reallocation procedures for future booking flexibility
 * - Prevents disruption of active feuds, championship pursuits, and established alliances
 * - Supports accurate stable management records and faction history documentation
 * - Maintains fan investment in stable dynamics and long-term storytelling payoffs
 */
final class CannotBeDisbandedException extends BaseBusinessException
{
    /**
     * Stable is not currently active and cannot be disbanded.
     *
     * @param  Stable  $stable  The stable that cannot be disbanded
     */
    public static function unactivated(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} is not active and cannot be disbanded.");
    }

    /**
     * Stable is already disbanded and cannot be disbanded again.
     *
     * @param  Stable  $stable  The stable that cannot be disbanded
     */
    public static function disbanded(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new static("{$context} is already disbanded.");
    }

    /**
     * Stable is permanently retired and cannot be disbanded.
     *
     * @param  Stable  $stable  The stable that cannot be disbanded
     */
    public static function retired(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new static("{$context} is retired and cannot be disbanded.");
    }

    /**
     * Stable has not been officially activated and cannot be disbanded.
     *
     * @param  Stable  $stable  The stable that cannot be disbanded
     */
    public static function hasFutureActivation(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new static("{$context} has not been officially activated and cannot be disbanded.");
    }

    /**
     * Stable cannot be disbanded due to active storyline commitments.
     *
     * @param  Stable  $stable  The stable that cannot be disbanded
     * @param  string  $storylineDetails  Description of the active storyline
     */
    public static function activeStoryline(Stable $stable, string $storylineDetails): static
    {
        $context = self::formatModelContext($stable);

        return new static("{$context} cannot be disbanded due to active storyline: {$storylineDetails}.");
    }

    /**
     * Stable cannot be disbanded while members have active championship reigns.
     *
     * @param  Stable  $stable  The stable that cannot be disbanded
     * @param  array<string>  $championshipTitles  List of championship titles held by members
     */
    public static function membersHoldingTitles(Stable $stable, array $championshipTitles): static
    {
        $context = self::formatModelContext($stable);
        $titles = implode(', ', $championshipTitles);

        return new static("{$context} cannot be disbanded while members hold championships: {$titles}.");
    }

    /**
     * Stable cannot be disbanded due to upcoming major storyline events.
     *
     * @param  Stable  $stable  The stable that cannot be disbanded
     * @param  string  $upcomingEvent  Description of the upcoming event
     */
    public static function upcomingMajorEvent(Stable $stable, string $upcomingEvent): static
    {
        $context = self::formatModelContext($stable);

        return new static("{$context} cannot be disbanded due to upcoming major event: {$upcomingEvent}.");
    }

    /**
     * Stable cannot be disbanded while involved in active feuds.
     *
     * @param  Stable  $stable  The stable that cannot be disbanded
     * @param  string  $feudDetails  Description of the active feud
     */
    public static function activeFeud(Stable $stable, string $feudDetails): static
    {
        $context = self::formatModelContext($stable);

        return new static("{$context} cannot be disbanded during active feud: {$feudDetails}.");
    }

    /**
     * Stable cannot be disbanded without proper authorization.
     *
     * @param  Stable  $stable  The stable that cannot be disbanded
     * @param  string  $authorizationLevel  Required authorization level for disbandment
     */
    public static function unauthorizedDisbandment(Stable $stable, string $authorizationLevel): static
    {
        $context = self::formatModelContext($stable);

        return new static("{$context} cannot be disbanded without {$authorizationLevel} authorization.");
    }

    /**
     * Stable cannot be disbanded due to contractual member obligations.
     *
     * @param  Stable  $stable  The stable that cannot be disbanded
     * @param  string  $memberObligations  Description of member contractual obligations
     */
    public static function memberContractualObligations(Stable $stable, string $memberObligations): static
    {
        $context = self::formatModelContext($stable);

        return new static("{$context} cannot be disbanded due to member contractual obligations: {$memberObligations}.");
    }

    /**
     * Stable cannot be disbanded during tournament or special event participation.
     *
     * @param  Stable  $stable  The stable that cannot be disbanded
     * @param  string  $eventDetails  Description of the tournament or special event
     */
    public static function tournamentParticipation(Stable $stable, string $eventDetails): static
    {
        $context = self::formatModelContext($stable);

        return new static("{$context} cannot be disbanded during tournament participation: {$eventDetails}.");
    }
}
