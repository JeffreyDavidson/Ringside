<?php

declare(strict_types=1);

use App\Enums\BusinessRuleReason;
use App\Enums\Roster\RosterEntityType;
use App\Exceptions\BaseBusinessException;
use App\Exceptions\Roster\Individuals\CannotBeClearedFromInjuryException;
use App\Exceptions\Roster\Individuals\CannotBeDeletedException;
use App\Exceptions\Roster\Individuals\CannotBeEmployedException;
use App\Exceptions\Roster\Individuals\CannotBeInjuredException;
use App\Exceptions\Roster\Individuals\CannotBeReinstatedException;
use App\Exceptions\Roster\Individuals\CannotBeRestoredException;
use App\Exceptions\Roster\Individuals\CannotBeSuspendedException;
use App\Exceptions\Roster\TagTeams\CannotBeEmployedException as TagTeamCannotBeEmployedException;
use App\Exceptions\Roster\TagTeams\CannotBeReinstatedException as TagTeamCannotBeReinstatedException;
use App\Exceptions\Roster\TagTeams\CannotBeReleasedException as TagTeamCannotBeReleasedException;
use App\Exceptions\Roster\TagTeams\CannotBeRestoredException as TagTeamCannotBeRestoredException;
use App\Exceptions\Roster\TagTeams\CannotBeRetiredException as TagTeamCannotBeRetiredException;
use App\Exceptions\Roster\TagTeams\CannotBeSuspendedException as TagTeamCannotBeSuspendedException;
use App\Exceptions\Roster\TagTeams\CannotBeUnretiredException as TagTeamCannotBeUnretiredException;
use App\Livewire\Support\RosterErrorMessageResolver;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

describe('roster error messages', function (): void {
    test('it maps roster failures from stable reasons instead of message text', function (): void {
        // Arrange
        $wrestler = new Wrestler(['name' => 'Test Wrestler']);
        $exception = CannotBeReinstatedException::injured($wrestler, 'wording may change');

        // Act
        $wrestlerKey = RosterErrorMessageResolver::translationKey($exception, RosterEntityType::Wrestler);
        $managerKey = RosterErrorMessageResolver::translationKey($exception, RosterEntityType::Manager);
        $refereeKey = RosterErrorMessageResolver::translationKey($exception, RosterEntityType::Referee);

        // Assert
        expect($exception->reason())->toBe(BusinessRuleReason::Injured)
            ->and($wrestlerKey)
            ->toBe('wrestlers.errors.cannot_reinstate_injured')
            ->and($managerKey)
            ->toBe('managers.errors.cannot_reinstate_injured')
            ->and($refereeKey)
            ->toBe('referees.errors.cannot_reinstate_injured');
    });

    test('it maps common lifecycle reasons for each roster presentation', function (BaseBusinessException $exception, RosterEntityType $entityType, string $expectedKey): void {
        // Arrange: each dataset supplies an independent failure and presentation.

        // Act
        $translationKey = RosterErrorMessageResolver::translationKey($exception, $entityType);

        // Assert
        expect($translationKey)->toBe($expectedKey);
    })->with([
        'wrestlers.errors.already_employed' => [
            fn (): BaseBusinessException => CannotBeEmployedException::employed(new Wrestler(['name' => 'Test Wrestler'])),
            RosterEntityType::Wrestler,
            'wrestlers.errors.already_employed',
        ],
        'managers.errors.not_employed_suspend' => [
            fn (): BaseBusinessException => CannotBeSuspendedException::unemployed(new Wrestler(['name' => 'Test Wrestler'])),
            RosterEntityType::Manager,
            'managers.errors.not_employed_suspend',
        ],
        'referees.errors.cannot_injure_unemployed' => [
            fn (): BaseBusinessException => CannotBeInjuredException::unemployed(new Wrestler(['name' => 'Test Wrestler'])),
            RosterEntityType::Referee,
            'referees.errors.cannot_injure_unemployed',
        ],
        'wrestlers.errors.not_injured' => [
            fn (): BaseBusinessException => CannotBeClearedFromInjuryException::notInjured(new Wrestler(['name' => 'Test Wrestler'])),
            RosterEntityType::Wrestler,
            'wrestlers.errors.not_injured',
        ],
    ]);

    test('it maps restoration failures from the not-deleted reason', function (): void {
        // Arrange
        $wrestler = new Wrestler(['name' => 'Test Wrestler']);
        $exception = CannotBeRestoredException::notDeleted($wrestler);

        // Act
        $restorationKey = RosterErrorMessageResolver::translationKey($exception, RosterEntityType::Wrestler);

        // Assert
        expect($exception->reason())->toBe(BusinessRuleReason::NotDeleted)
            ->and($restorationKey)
            ->toBe('wrestlers.errors.not_deleted');
    });

    test('it maps available roster reinstatement failures to suspension guidance', function (): void {
        // Arrange
        $wrestler = new Wrestler(['name' => 'Test Wrestler']);
        $exception = CannotBeReinstatedException::available($wrestler);

        // Act
        $wrestlerKey = RosterErrorMessageResolver::translationKey($exception, RosterEntityType::Wrestler);
        $managerKey = RosterErrorMessageResolver::translationKey($exception, RosterEntityType::Manager);
        $refereeKey = RosterErrorMessageResolver::translationKey($exception, RosterEntityType::Referee);

        // Assert
        expect($exception->reason())->toBe(BusinessRuleReason::NotSuspended)
            ->and($wrestlerKey)
            ->toBe('wrestlers.errors.not_suspended')
            ->and($managerKey)
            ->toBe('managers.errors.not_suspended')
            ->and($refereeKey)
            ->toBe('referees.errors.not_suspended');
    });

    test('it maps tag team reinstatement failures from a stable reason', function (): void {
        // Arrange
        $tagTeam = new TagTeam(['name' => 'Test Team']);
        $exception = TagTeamCannotBeReinstatedException::notSuspended($tagTeam);

        // Act
        $reinstatementKey = RosterErrorMessageResolver::translationKey($exception, RosterEntityType::TagTeam);

        // Assert
        expect($exception->reason())->toBe(BusinessRuleReason::NotSuspended)
            ->and($reinstatementKey)
            ->toBe('tag-teams.errors.not_suspended');
    });

    test('it maps tag team lifecycle failures from stable reasons', function (BaseBusinessException $exception, RosterEntityType $entityType, string $expectedKey): void {
        // Arrange: each dataset supplies an independent failure and presentation.

        // Act
        $translationKey = RosterErrorMessageResolver::translationKey($exception, $entityType);

        // Assert
        expect($translationKey)->toBe($expectedKey);
    })->with([
        'tag-teams.errors.already_employed' => [
            fn (): BaseBusinessException => TagTeamCannotBeEmployedException::alreadyEmployed(new TagTeam(['name' => 'Test TagTeam'])),
            RosterEntityType::TagTeam,
            'tag-teams.errors.already_employed',
        ],
        'tag-teams.errors.cannot_employ_retired' => [
            fn (): BaseBusinessException => TagTeamCannotBeEmployedException::retired(new TagTeam(['name' => 'Test TagTeam'])),
            RosterEntityType::TagTeam,
            'tag-teams.errors.cannot_employ_retired',
        ],
        'tag-teams.errors.not_employed' => [
            fn (): BaseBusinessException => TagTeamCannotBeReleasedException::notEmployed(new TagTeam(['name' => 'Test TagTeam'])),
            RosterEntityType::TagTeam,
            'tag-teams.errors.not_employed',
        ],
        'tag-teams.errors.cannot_retire_unemployed' => [
            fn (): BaseBusinessException => TagTeamCannotBeRetiredException::notEmployed(new TagTeam(['name' => 'Test TagTeam'])),
            RosterEntityType::TagTeam,
            'tag-teams.errors.cannot_retire_unemployed',
        ],
        'tag-teams.errors.already_retired' => [
            fn (): BaseBusinessException => TagTeamCannotBeRetiredException::alreadyRetired(new TagTeam(['name' => 'Test TagTeam'])),
            RosterEntityType::TagTeam,
            'tag-teams.errors.already_retired',
        ],
        'tag-teams.errors.not_retired' => [
            fn (): BaseBusinessException => TagTeamCannotBeUnretiredException::notRetired(new TagTeam(['name' => 'Test TagTeam'])),
            RosterEntityType::TagTeam,
            'tag-teams.errors.not_retired',
        ],
        'tag-teams.errors.not_employed_suspend' => [
            fn (): BaseBusinessException => TagTeamCannotBeSuspendedException::notEmployed(new TagTeam(['name' => 'Test TagTeam'])),
            RosterEntityType::TagTeam,
            'tag-teams.errors.not_employed_suspend',
        ],
        'tag-teams.errors.already_suspended' => [
            fn (): BaseBusinessException => TagTeamCannotBeSuspendedException::alreadySuspended(new TagTeam(['name' => 'Test TagTeam'])),
            RosterEntityType::TagTeam,
            'tag-teams.errors.already_suspended',
        ],
        'tag-teams.errors.not_deleted' => [
            fn (): BaseBusinessException => TagTeamCannotBeRestoredException::notDeleted(new TagTeam(['name' => 'Test TagTeam'])),
            RosterEntityType::TagTeam,
            'tag-teams.errors.not_deleted',
        ],
    ]);

    test('it uses a general message for an unmapped business exception', function (): void {
        // Arrange
        $wrestler = new Wrestler(['name' => 'Test Wrestler']);
        $exception = CannotBeDeletedException::alreadyDeleted($wrestler);

        // Act
        $fallbackKey = RosterErrorMessageResolver::translationKey($exception, RosterEntityType::Wrestler);

        // Assert
        expect($fallbackKey)
            ->toBe('wrestlers.errors.general_error');
    });
});
