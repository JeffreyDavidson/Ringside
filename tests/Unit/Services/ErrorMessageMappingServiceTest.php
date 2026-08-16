<?php

declare(strict_types=1);

use App\Enums\BusinessRuleReason;
use App\Exceptions\Roster\Individuals\CannotBeClearedFromInjuryException;
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
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\ErrorMessageMappingService;

test('it maps roster failures from stable reasons instead of message text', function () {
    $wrestler = new Wrestler(['name' => 'Test Wrestler']);
    $exception = CannotBeReinstatedException::injured($wrestler, 'wording may change');

    expect($exception->reason())->toBe(BusinessRuleReason::Injured)
        ->and(ErrorMessageMappingService::mapWrestlerException($exception))
        ->toBe('wrestlers.errors.cannot_reinstate_injured')
        ->and(ErrorMessageMappingService::mapManagerException($exception))
        ->toBe('managers.errors.cannot_reinstate_injured')
        ->and(ErrorMessageMappingService::mapRefereeException($exception))
        ->toBe('referees.errors.cannot_reinstate_injured');
});

test('it maps common lifecycle reasons for each roster presentation', function () {
    $wrestler = new Wrestler(['name' => 'Test Wrestler']);

    expect(ErrorMessageMappingService::mapWrestlerException(CannotBeEmployedException::employed($wrestler)))
        ->toBe('wrestlers.errors.already_employed')
        ->and(ErrorMessageMappingService::mapManagerException(CannotBeSuspendedException::unemployed($wrestler)))
        ->toBe('managers.errors.not_employed_suspend')
        ->and(ErrorMessageMappingService::mapRefereeException(CannotBeInjuredException::unemployed($wrestler)))
        ->toBe('referees.errors.cannot_injure_unemployed')
        ->and(ErrorMessageMappingService::mapWrestlerException(CannotBeClearedFromInjuryException::notInjured($wrestler)))
        ->toBe('wrestlers.errors.not_injured');
});

test('it maps restoration failures from the not-deleted reason', function () {
    $wrestler = new Wrestler(['name' => 'Test Wrestler']);
    $exception = CannotBeRestoredException::notDeleted($wrestler);

    expect($exception->reason())->toBe(BusinessRuleReason::NotDeleted)
        ->and(ErrorMessageMappingService::mapWrestlerException($exception))
        ->toBe('wrestlers.errors.not_deleted');
});

test('it maps available roster reinstatement failures to suspension guidance', function () {
    $wrestler = new Wrestler(['name' => 'Test Wrestler']);
    $exception = CannotBeReinstatedException::available($wrestler);

    expect($exception->reason())->toBe(BusinessRuleReason::NotSuspended)
        ->and(ErrorMessageMappingService::mapWrestlerException($exception))
        ->toBe('wrestlers.errors.not_suspended')
        ->and(ErrorMessageMappingService::mapManagerException($exception))
        ->toBe('managers.errors.not_suspended')
        ->and(ErrorMessageMappingService::mapRefereeException($exception))
        ->toBe('referees.errors.not_suspended');
});

test('it maps tag team reinstatement failures from a stable reason', function () {
    $tagTeam = new TagTeam(['name' => 'Test Team']);
    $exception = TagTeamCannotBeReinstatedException::notSuspended($tagTeam);

    expect($exception->reason())->toBe(BusinessRuleReason::NotSuspended)
        ->and(ErrorMessageMappingService::mapTagTeamException($exception))
        ->toBe('tag-teams.errors.not_suspended');
});

test('it maps tag team lifecycle failures from stable reasons', function () {
    $tagTeam = new TagTeam(['name' => 'Test Team']);

    expect(ErrorMessageMappingService::mapTagTeamException(TagTeamCannotBeEmployedException::alreadyEmployed($tagTeam)))
        ->toBe('tag-teams.errors.already_employed')
        ->and(ErrorMessageMappingService::mapTagTeamException(TagTeamCannotBeEmployedException::retired($tagTeam)))
        ->toBe('tag-teams.errors.cannot_employ_retired')
        ->and(ErrorMessageMappingService::mapTagTeamException(TagTeamCannotBeReleasedException::notEmployed($tagTeam)))
        ->toBe('tag-teams.errors.not_employed')
        ->and(ErrorMessageMappingService::mapTagTeamException(TagTeamCannotBeRetiredException::notEmployed($tagTeam)))
        ->toBe('tag-teams.errors.cannot_retire_unemployed')
        ->and(ErrorMessageMappingService::mapTagTeamException(TagTeamCannotBeRetiredException::alreadyRetired($tagTeam)))
        ->toBe('tag-teams.errors.already_retired')
        ->and(ErrorMessageMappingService::mapTagTeamException(TagTeamCannotBeUnretiredException::notRetired($tagTeam)))
        ->toBe('tag-teams.errors.not_retired')
        ->and(ErrorMessageMappingService::mapTagTeamException(TagTeamCannotBeSuspendedException::notEmployed($tagTeam)))
        ->toBe('tag-teams.errors.not_employed_suspend')
        ->and(ErrorMessageMappingService::mapTagTeamException(TagTeamCannotBeSuspendedException::alreadySuspended($tagTeam)))
        ->toBe('tag-teams.errors.already_suspended')
        ->and(ErrorMessageMappingService::mapTagTeamException(TagTeamCannotBeRestoredException::notDeleted($tagTeam)))
        ->toBe('tag-teams.errors.not_deleted');
});

test('it uses a general message for unknown exceptions', function () {
    $exception = new RuntimeException('Unknown failure.');

    expect(ErrorMessageMappingService::mapWrestlerException($exception))
        ->toBe('wrestlers.errors.general_error');
});
