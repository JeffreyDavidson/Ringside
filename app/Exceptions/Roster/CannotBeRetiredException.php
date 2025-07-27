<?php

declare(strict_types=1);

namespace App\Exceptions\Roster;

use App\Exceptions\BaseBusinessException;
use App\Models\Contracts\Employable;
use App\Models\Stables\Stable;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;

/**
 * Exception thrown when a roster member cannot be retired due to business rule violations.
 *
 * This exception handles scenarios where retirement is prevented by current state
 * or business logic constraints in wrestling promotion roster lifecycle management.
 *
 * BUSINESS CONTEXT:
 * Retirement represents the permanent end of a roster member's active career,
 * marking their transition from active competition to legend status. Failed retirements
 * can disrupt career closure ceremonies and historical record keeping.
 *
 * COMMON SCENARIOS:
 * - Attempting to retire unemployed, unactivated, or already retired roster members
 * - Trying to retire members with future employment or activation commitments
 * - Tag team retirement conflicts due to individual member status issues
 * - Missing career prerequisites for proper retirement ceremony workflow
 *
 * BUSINESS IMPACT:
 * - Maintains career lifecycle integrity and historical accuracy
 * - Protects retirement ceremony planning and Hall of Fame eligibility
 * - Ensures proper pension and benefit calculations for retirees
 * - Preserves legacy storylines and character development continuity
 */
final class CannotBeRetiredException extends BaseBusinessException
{
    /**
     * Roster member is unemployed and cannot be retired.
     *
     * @param  Employable  $entity  The roster member that cannot be retired
     */
    public static function unemployed(Employable $entity): static
    {
        $context = self::formatModelContext($entity);

        return new self("{$context} is currently unemployed and cannot be retired.");
    }

    /**
     * Roster member is already retired and cannot be retired again.
     *
     * @param  Employable|Stable  $entity  The roster member that cannot be retired
     */
    public static function alreadyRetired(Employable|Stable $entity): static
    {
        $context = self::formatModelContext($entity);

        return new self("{$context} is already retired and cannot be retired again.");
    }

    /**
     * Roster member is released and cannot be retired.
     *
     * @param  Employable  $entity  The roster member that cannot be retired
     */
    public static function released(Employable $entity): static
    {
        $context = self::formatModelContext($entity);

        return new self("{$context} has been released and cannot be retired.");
    }

    /**
     * Stable is unactivated and cannot be retired.
     *
     * @param  Stable  $stable  The stable that cannot be retired
     */
    public static function unactivated(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} has never been activated and cannot be retired.");
    }

    /**
     * Roster member has future employment and cannot be retired before employment begins.
     *
     * @param  Employable  $entity  The roster member that cannot be retired
     */
    public static function hasFutureEmployment(Employable $entity): static
    {
        $context = self::formatModelContext($entity);

        return new self("{$context} has future employment scheduled and cannot be retired.");
    }

    /**
     * Stable has future establishment and cannot be retired before establishment begins.
     *
     * @param  Stable  $stable  The stable that cannot be retired
     */
    public static function hasFutureEstablishment(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} has future establishment scheduled and cannot be retired.");
    }

    /**
     * Tag team has no active wrestlers and cannot be retired.
     *
     * @param  TagTeam  $tagTeam  The tag team that cannot be retired
     */
    public static function noActiveWrestlers(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} has no active wrestlers and cannot be retired.");
    }

    /**
     * Tag team cannot be retired because a wrestler is injured.
     *
     * @param  TagTeam  $tagTeam  The tag team that cannot be retired
     * @param  Wrestler  $wrestler  The injured wrestler preventing retirement
     */
    public static function wrestlerInjured(TagTeam $tagTeam, Wrestler $wrestler): static
    {
        $tagTeamContext = self::formatModelContext($tagTeam);
        $wrestlerContext = self::formatModelContext($wrestler);

        return new self("{$tagTeamContext} cannot be retired because {$wrestlerContext} is currently injured.");
    }

    /**
     * Tag team cannot be retired because a wrestler is suspended.
     *
     * @param  TagTeam  $tagTeam  The tag team that cannot be retired
     * @param  Wrestler  $wrestler  The suspended wrestler preventing retirement
     */
    public static function wrestlerSuspended(TagTeam $tagTeam, Wrestler $wrestler): static
    {
        $tagTeamContext = self::formatModelContext($tagTeam);
        $wrestlerContext = self::formatModelContext($wrestler);

        return new self("{$tagTeamContext} cannot be retired because {$wrestlerContext} is currently suspended.");
    }

    /**
     * Tag team cannot be retired because a wrestler cannot be retired.
     *
     * @param  TagTeam  $tagTeam  The tag team that cannot be retired
     * @param  Wrestler  $wrestler  The wrestler that cannot be retired
     * @param  string|null  $reason  Optional reason why the wrestler cannot be retired
     */
    public static function wrestlerCannotBeRetired(TagTeam $tagTeam, Wrestler $wrestler, ?string $reason = null): static
    {
        $tagTeamContext = self::formatModelContext($tagTeam);
        $wrestlerContext = self::formatModelContext($wrestler);
        $reasonText = $reason ? " ({$reason})" : '';

        return new self("{$tagTeamContext} cannot be retired because {$wrestlerContext} cannot be retired{$reasonText}.");
    }

    /**
     * Roster member cannot be retired while holding active championship titles.
     *
     * @param  Wrestler|TagTeam  $entity  The roster member that cannot be retired
     * @param  array<string>  $championshipTitles  List of championship titles currently held
     */
    public static function activeChampion(Wrestler|TagTeam $entity, array $championshipTitles): static
    {
        $context = self::formatModelContext($entity);
        $titles = implode(', ', $championshipTitles);

        return new self("{$context} holds active championship(s) ({$titles}) and cannot be retired.");
    }

    /**
     * Roster member cannot be retired due to unresolved contractual obligations.
     *
     * @param  Employable|Stable  $entity  The roster member that cannot be retired
     * @param  string  $contractualObligation  Description of the unresolved contractual obligation
     */
    public static function contractualObligation(Employable|Stable $entity, string $contractualObligation): static
    {
        $context = self::formatModelContext($entity);

        return new self("{$context} has unresolved contractual obligation ({$contractualObligation}) and cannot be retired.");
    }
}
