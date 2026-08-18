<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BusinessRuleReason;
use App\Enums\Roster\RosterEntityType;
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

final class ErrorMessageMappingService
{
    public static function map(BaseBusinessException $exception, RosterEntityType $entityType): string
    {
        $reason = $exception->reason();

        $key = match ($exception::class) {
            CannotBeEmployedException::class,
            TagTeamCannotBeEmployedException::class => self::employmentKey($reason, $entityType),
            CannotBeReleasedException::class,
            TagTeamCannotBeReleasedException::class => self::releaseKey($reason, $entityType),
            CannotBeRetiredException::class,
            TagTeamCannotBeRetiredException::class => self::retirementKey($reason, $entityType),
            CannotBeUnretiredException::class,
            TagTeamCannotBeUnretiredException::class => self::unretirementKey($reason),
            CannotBeSuspendedException::class,
            TagTeamCannotBeSuspendedException::class => self::suspensionKey($reason, $entityType),
            CannotBeReinstatedException::class,
            TagTeamCannotBeReinstatedException::class => self::reinstatementKey($reason),
            CannotBeInjuredException::class => self::injuryKey($reason, $entityType),
            CannotBeClearedFromInjuryException::class => self::injuryClearanceKey($reason),
            CannotBeRestoredException::class,
            TagTeamCannotBeRestoredException::class => self::restorationKey($reason),
            default => 'general_error',
        };

        return "{$entityType->translationNamespace()}.errors.{$key}";
    }

    private static function employmentKey(BusinessRuleReason $reason, RosterEntityType $entityType): string
    {
        return match ($reason) {
            BusinessRuleReason::AlreadyEmployed => 'already_employed',
            BusinessRuleReason::Suspended => 'cannot_employ_suspended',
            BusinessRuleReason::Retired => 'cannot_employ_retired',
            BusinessRuleReason::Injured => $entityType === RosterEntityType::Manager ? 'cannot_employ_injured' : 'cannot_employ',
            default => 'cannot_employ',
        };
    }

    private static function releaseKey(BusinessRuleReason $reason, RosterEntityType $entityType): string
    {
        return match ($reason) {
            BusinessRuleReason::Unemployed => 'not_employed',
            BusinessRuleReason::Suspended => in_array($entityType, [RosterEntityType::Manager, RosterEntityType::TagTeam], true)
                ? 'cannot_release_suspended'
                : 'cannot_release',
            default => 'cannot_release',
        };
    }

    private static function retirementKey(BusinessRuleReason $reason, RosterEntityType $entityType): string
    {
        return match ($reason) {
            BusinessRuleReason::Unemployed => 'cannot_retire_unemployed',
            BusinessRuleReason::AlreadyRetired => 'already_retired',
            BusinessRuleReason::Suspended => in_array($entityType, [RosterEntityType::Manager, RosterEntityType::TagTeam], true)
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

    private static function suspensionKey(BusinessRuleReason $reason, RosterEntityType $entityType): string
    {
        return match ($reason) {
            BusinessRuleReason::AlreadySuspended => 'already_suspended',
            BusinessRuleReason::Unemployed => match ($entityType) {
                RosterEntityType::Manager, RosterEntityType::TagTeam => 'not_employed_suspend',
                RosterEntityType::Referee => 'cannot_suspend_unemployed',
                default => 'cannot_suspend',
            },
            BusinessRuleReason::Injured => $entityType === RosterEntityType::Manager ? 'cannot_suspend_injured' : 'cannot_suspend',
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

    private static function injuryKey(BusinessRuleReason $reason, RosterEntityType $entityType): string
    {
        return match ($reason) {
            BusinessRuleReason::AlreadyInjured => 'already_injured',
            BusinessRuleReason::Unemployed => match ($entityType) {
                RosterEntityType::Manager => 'not_employed_injure',
                RosterEntityType::Referee => 'cannot_injure_unemployed',
                default => 'cannot_injure',
            },
            BusinessRuleReason::Suspended => $entityType === RosterEntityType::Manager ? 'cannot_injure_suspended' : 'cannot_injure',
            default => 'cannot_injure',
        };
    }

    private static function injuryClearanceKey(BusinessRuleReason $reason): string
    {
        return $reason === BusinessRuleReason::NotInjured
            ? 'not_injured'
            : 'cannot_clear_from_injury';
    }

    private static function restorationKey(BusinessRuleReason $reason): string
    {
        return $reason === BusinessRuleReason::NotDeleted
            ? 'not_deleted'
            : 'cannot_restore';
    }
}
