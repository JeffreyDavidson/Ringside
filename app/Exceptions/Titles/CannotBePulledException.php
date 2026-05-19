<?php

declare(strict_types=1);

namespace App\Exceptions\Titles;

use App\Exceptions\BaseBusinessException;
use App\Models\Titles\Title;

/**
 * Exception thrown when a title cannot be pulled from active competition due to business rule violations.
 *
 * This exception handles scenarios where title pulling is prevented by current state
 * or business logic constraints in wrestling promotion championship management.
 *
 * BUSINESS CONTEXT:
 * Title pulling represents the temporary removal of championships from active competition,
 * typically for storyline purposes, brand restructuring, or organizational changes.
 * This differs from retirement (permanent) and deactivation (general status change).
 *
 * COMMON SCENARIOS:
 * - Attempting to pull inactive or unactivated titles
 * - Trying to pull already retired titles
 * - Pulling titles with active championship reigns
 * - Missing prerequisites for proper title removal workflow
 *
 * BUSINESS IMPACT:
 * - Maintains championship status integrity and booking consistency
 * - Protects active storylines and championship lineages
 * - Ensures proper title transition and reactivation planning
 * - Prevents disruption of ongoing feuds and contender programs
 */
final class CannotBePulledException extends BaseBusinessException
{
    /**
     * Title is not currently active and cannot be pulled from competition.
     *
     * @param  Title  $title  The title that cannot be pulled
     */
    public static function notActive(Title $title): static
    {
        $context = self::formatModelContext($title);

        return new self("{$context} is not currently active and cannot be pulled from competition.");
    }

    /**
     * Title is permanently retired and cannot be pulled.
     *
     * @param  Title  $title  The title that cannot be pulled
     */
    public static function retired(Title $title): static
    {
        $context = self::formatModelContext($title);

        return new static("{$context} is retired and cannot be pulled from competition.");
    }

    /**
     * Title is already inactive and cannot be pulled again.
     *
     * @param  Title  $title  The title that cannot be pulled
     */
    public static function alreadyInactive(Title $title): static
    {
        $context = self::formatModelContext($title);

        return new static("{$context} is already inactive and cannot be pulled from competition.");
    }

    /**
     * Title has never been activated and cannot be pulled.
     *
     * @param  Title  $title  The title that cannot be pulled
     */
    public static function neverActivated(Title $title): static
    {
        $context = self::formatModelContext($title);

        return new static("{$context} has never been activated and cannot be pulled from competition.");
    }

    /**
     * Title has active championship reign and cannot be pulled while held.
     *
     * @param  Title  $title  The title that cannot be pulled
     * @param  string  $championName  Name of the current champion
     */
    public static function activeChampionshipReign(Title $title, string $championName): static
    {
        $context = self::formatModelContext($title);

        return new static("{$context} is currently held by {$championName} and cannot be pulled during an active championship reign.");
    }

    /**
     * Title has scheduled championship defense and cannot be pulled.
     *
     * @param  Title  $title  The title that cannot be pulled
     * @param  string  $upcomingDefense  Description of the upcoming title defense
     */
    public static function scheduledDefense(Title $title, string $upcomingDefense): static
    {
        $context = self::formatModelContext($title);

        return new static("{$context} has a scheduled defense ({$upcomingDefense}) and cannot be pulled until resolved.");
    }

    /**
     * Title cannot be pulled due to active storyline commitments.
     *
     * @param  Title  $title  The title that cannot be pulled
     * @param  string  $storylineDetails  Description of the active storyline
     */
    public static function activeStoryline(Title $title, string $storylineDetails): static
    {
        $context = self::formatModelContext($title);

        return new static("{$context} is involved in active storyline ({$storylineDetails}) and cannot be pulled without narrative resolution.");
    }

    /**
     * Title cannot be pulled without proper authorization.
     *
     * @param  Title  $title  The title that cannot be pulled
     * @param  string  $authorizationLevel  Required authorization level for title pulling
     */
    public static function unauthorizedPull(Title $title, string $authorizationLevel): static
    {
        $context = self::formatModelContext($title);

        return new static("{$context} cannot be pulled without {$authorizationLevel} authorization.");
    }

    /**
     * Title cannot be pulled due to tournament or special event involvement.
     *
     * @param  Title  $title  The title that cannot be pulled
     * @param  string  $eventDetails  Description of the tournament or special event
     */
    public static function tournamentInvolvement(Title $title, string $eventDetails): static
    {
        $context = self::formatModelContext($title);

        return new static("{$context} is involved in {$eventDetails} and cannot be pulled until the event concludes.");
    }

    /**
     * Title has future activation scheduled and cannot be pulled.
     *
     * @param  Title  $title  The title that cannot be pulled
     */
    public static function futureActivation(Title $title): static
    {
        $context = self::formatModelContext($title);

        return new static("{$context} has future activation scheduled and cannot be pulled.");
    }
}
