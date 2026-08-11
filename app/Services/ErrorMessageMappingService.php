<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;
use App\Exceptions\Roster\Individuals\CannotBeClearedFromInjuryException;
use App\Exceptions\Roster\Individuals\CannotBeEmployedException;
use App\Exceptions\Roster\Individuals\CannotBeInjuredException;
use App\Exceptions\Roster\Individuals\CannotBeReinstatedException;
use App\Exceptions\Roster\Individuals\CannotBeReleasedException;
use App\Exceptions\Roster\Individuals\CannotBeRestoredException;
use App\Exceptions\Roster\Individuals\CannotBeRetiredException;
use App\Exceptions\Roster\Individuals\CannotBeSuspendedException;
use App\Exceptions\Roster\Individuals\CannotBeUnretiredException;
use App\Exceptions\Roster\TagTeams\CannotBeEmployedException as TagTeamCannotBeEmployedException;
use App\Exceptions\Roster\TagTeams\CannotBeReinstatedException as TagTeamCannotBeReinstatedException;
use App\Exceptions\Roster\TagTeams\CannotBeReleasedException as TagTeamCannotBeReleasedException;
use App\Exceptions\Roster\TagTeams\CannotBeRestoredException as TagTeamCannotBeRestoredException;
use App\Exceptions\Roster\TagTeams\CannotBeRetiredException as TagTeamCannotBeRetiredException;
use App\Exceptions\Roster\TagTeams\CannotBeSuspendedException as TagTeamCannotBeSuspendedException;
use App\Exceptions\Roster\TagTeams\CannotBeUnretiredException as TagTeamCannotBeUnretiredException;
use Throwable;

final class ErrorMessageMappingService
{
    public static function mapWrestlerException(Throwable $exception): string
    {
        return self::map($exception, 'wrestlers');
    }

    public static function mapRefereeException(Throwable $exception): string
    {
        return self::map($exception, 'referees');
    }

    public static function mapManagerException(Throwable $exception): string
    {
        return self::map($exception, 'managers');
    }

    public static function mapTagTeamException(Throwable $exception): string
    {
        return self::map($exception, 'tag-teams');
    }

    private static function map(Throwable $exception, string $entity): string
    {
        $reason = $exception instanceof BaseBusinessException
            ? $exception->reason()
            : BusinessRuleReason::General;

        $key = match ($exception::class) {
            CannotBeEmployedException::class,
            TagTeamCannotBeEmployedException::class => self::employmentKey($reason, $entity),
            CannotBeReleasedException::class,
            TagTeamCannotBeReleasedException::class => self::releaseKey($reason, $entity),
            CannotBeRetiredException::class,
            TagTeamCannotBeRetiredException::class => self::retirementKey($reason, $entity),
            CannotBeUnretiredException::class,
            TagTeamCannotBeUnretiredException::class => self::unretirementKey($reason),
            CannotBeSuspendedException::class,
            TagTeamCannotBeSuspendedException::class => self::suspensionKey($reason, $entity),
            CannotBeReinstatedException::class,
            TagTeamCannotBeReinstatedException::class => self::reinstatementKey($reason),
            CannotBeInjuredException::class => self::injuryKey($reason, $entity),
            CannotBeClearedFromInjuryException::class => self::healingKey($reason),
            CannotBeRestoredException::class,
            TagTeamCannotBeRestoredException::class => self::restorationKey($reason),
            default => 'general_error',
        };

        return "{$entity}.errors.{$key}";
    }

    private static function employmentKey(BusinessRuleReason $reason, string $entity): string
    {
        return match ($reason) {
            BusinessRuleReason::AlreadyEmployed => 'already_employed',
            BusinessRuleReason::Suspended => 'cannot_employ_suspended',
            BusinessRuleReason::Retired => 'cannot_employ_retired',
            BusinessRuleReason::Injured => $entity === 'managers' ? 'cannot_employ_injured' : 'cannot_employ',
            default => 'cannot_employ',
        };
    }

    private static function releaseKey(BusinessRuleReason $reason, string $entity): string
    {
        return match ($reason) {
            BusinessRuleReason::Unemployed => 'not_employed',
            BusinessRuleReason::Suspended => in_array($entity, ['managers', 'tag-teams'], true)
                ? 'cannot_release_suspended'
                : 'cannot_release',
            default => 'cannot_release',
        };
    }

    private static function retirementKey(BusinessRuleReason $reason, string $entity): string
    {
        return match ($reason) {
            BusinessRuleReason::Unemployed => 'cannot_retire_unemployed',
            BusinessRuleReason::AlreadyRetired => 'already_retired',
            BusinessRuleReason::Suspended => in_array($entity, ['managers', 'tag-teams'], true)
                ? 'cannot_retire_suspended'
                : 'cannot_retire',
            default => 'cannot_retire',
        };
    }

    private static function unretirementKey(BusinessRuleReason $reason): string
    {
        return $reason === BusinessRuleReason::NotRetired
            ? 'not_retired'
            : 'cannot_unretire';
    }

    private static function suspensionKey(BusinessRuleReason $reason, string $entity): string
    {
        return match ($reason) {
            BusinessRuleReason::AlreadySuspended => 'already_suspended',
            BusinessRuleReason::Unemployed => match ($entity) {
                'managers', 'tag-teams' => 'not_employed_suspend',
                'referees' => 'cannot_suspend_unemployed',
                default => 'cannot_suspend',
            },
            BusinessRuleReason::Injured => $entity === 'managers' ? 'cannot_suspend_injured' : 'cannot_suspend',
            default => 'cannot_suspend',
        };
    }

    private static function reinstatementKey(BusinessRuleReason $reason): string
    {
        return match ($reason) {
            BusinessRuleReason::NotSuspended => 'not_suspended',
            BusinessRuleReason::Injured => 'cannot_reinstate_injured',
            default => 'cannot_reinstate',
        };
    }

    private static function injuryKey(BusinessRuleReason $reason, string $entity): string
    {
        return match ($reason) {
            BusinessRuleReason::AlreadyInjured => 'already_injured',
            BusinessRuleReason::Unemployed => match ($entity) {
                'managers' => 'not_employed_injure',
                'referees' => 'cannot_injure_unemployed',
                default => 'cannot_injure',
            },
            BusinessRuleReason::Suspended => $entity === 'managers' ? 'cannot_injure_suspended' : 'cannot_injure',
            default => 'cannot_injure',
        };
    }

    private static function healingKey(BusinessRuleReason $reason): string
    {
        return $reason === BusinessRuleReason::NotInjured
            ? 'not_injured'
            : 'cannot_heal';
    }

    private static function restorationKey(BusinessRuleReason $reason): string
    {
        return $reason === BusinessRuleReason::NotDeleted
            ? 'not_deleted'
            : 'cannot_restore';
    }
}
