<?php

declare(strict_types=1);

use App\Enums\BusinessRuleReason;
use App\Enums\Roster\RosterEntityType;
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
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\ErrorMessageMappingService;

test('it maps roster failures from stable reasons instead of message text', function () {
    $wrestler = new Wrestler(['name' => 'Test Wrestler']);
    $exception = CannotBeReinstatedException::injured($wrestler, 'wording may change');

    expect($exception->reason())->toBe(BusinessRuleReason::Injured)
        ->and(ErrorMessageMappingService::map($exception, RosterEntityType::Wrestler))
        ->toBe('wrestlers.errors.cannot_reinstate_injured')
        ->and(ErrorMessageMappingService::map($exception, RosterEntityType::Manager))
        ->toBe('managers.errors.cannot_reinstate_injured')
        ->and(ErrorMessageMappingService::map($exception, RosterEntityType::Referee))
        ->toBe('referees.errors.cannot_reinstate_injured');
});

test('it maps common lifecycle reasons for each roster presentation', function () {
    $wrestler = new Wrestler(['name' => 'Test Wrestler']);

    expect(ErrorMessageMappingService::map(CannotBeEmployedException::employed($wrestler), RosterEntityType::Wrestler))
        ->toBe('wrestlers.errors.already_employed')
        ->and(ErrorMessageMappingService::map(CannotBeSuspendedException::unemployed($wrestler), RosterEntityType::Manager))
        ->toBe('managers.errors.not_employed_suspend')
        ->and(ErrorMessageMappingService::map(CannotBeInjuredException::unemployed($wrestler), RosterEntityType::Referee))
        ->toBe('referees.errors.cannot_injure_unemployed')
        ->and(ErrorMessageMappingService::map(CannotBeClearedFromInjuryException::notInjured($wrestler), RosterEntityType::Wrestler))
        ->toBe('wrestlers.errors.not_injured');
});

test('it maps restoration failures from the not-deleted reason', function () {
    $wrestler = new Wrestler(['name' => 'Test Wrestler']);
    $exception = CannotBeRestoredException::notDeleted($wrestler);

    expect($exception->reason())->toBe(BusinessRuleReason::NotDeleted)
        ->and(ErrorMessageMappingService::map($exception, RosterEntityType::Wrestler))
        ->toBe('wrestlers.errors.not_deleted');
});

test('it maps available roster reinstatement failures to suspension guidance', function () {
    $wrestler = new Wrestler(['name' => 'Test Wrestler']);
    $exception = CannotBeReinstatedException::available($wrestler);

    expect($exception->reason())->toBe(BusinessRuleReason::NotSuspended)
        ->and(ErrorMessageMappingService::map($exception, RosterEntityType::Wrestler))
        ->toBe('wrestlers.errors.not_suspended')
        ->and(ErrorMessageMappingService::map($exception, RosterEntityType::Manager))
        ->toBe('managers.errors.not_suspended')
        ->and(ErrorMessageMappingService::map($exception, RosterEntityType::Referee))
        ->toBe('referees.errors.not_suspended');
});

test('it maps tag team reinstatement failures from a stable reason', function () {
    $tagTeam = new TagTeam(['name' => 'Test Team']);
    $exception = TagTeamCannotBeReinstatedException::notSuspended($tagTeam);

    expect($exception->reason())->toBe(BusinessRuleReason::NotSuspended)
        ->and(ErrorMessageMappingService::map($exception, RosterEntityType::TagTeam))
        ->toBe('tag-teams.errors.not_suspended');
});

test('it maps tag team lifecycle failures from stable reasons', function () {
    $tagTeam = new TagTeam(['name' => 'Test Team']);

    expect(ErrorMessageMappingService::map(TagTeamCannotBeEmployedException::alreadyEmployed($tagTeam), RosterEntityType::TagTeam))
        ->toBe('tag-teams.errors.already_employed')
        ->and(ErrorMessageMappingService::map(TagTeamCannotBeEmployedException::retired($tagTeam), RosterEntityType::TagTeam))
        ->toBe('tag-teams.errors.cannot_employ_retired')
        ->and(ErrorMessageMappingService::map(TagTeamCannotBeReleasedException::notEmployed($tagTeam), RosterEntityType::TagTeam))
        ->toBe('tag-teams.errors.not_employed')
        ->and(ErrorMessageMappingService::map(TagTeamCannotBeRetiredException::notEmployed($tagTeam), RosterEntityType::TagTeam))
        ->toBe('tag-teams.errors.cannot_retire_unemployed')
        ->and(ErrorMessageMappingService::map(TagTeamCannotBeRetiredException::alreadyRetired($tagTeam), RosterEntityType::TagTeam))
        ->toBe('tag-teams.errors.already_retired')
        ->and(ErrorMessageMappingService::map(TagTeamCannotBeUnretiredException::notRetired($tagTeam), RosterEntityType::TagTeam))
        ->toBe('tag-teams.errors.not_retired')
        ->and(ErrorMessageMappingService::map(TagTeamCannotBeSuspendedException::notEmployed($tagTeam), RosterEntityType::TagTeam))
        ->toBe('tag-teams.errors.not_employed_suspend')
        ->and(ErrorMessageMappingService::map(TagTeamCannotBeSuspendedException::alreadySuspended($tagTeam), RosterEntityType::TagTeam))
        ->toBe('tag-teams.errors.already_suspended')
        ->and(ErrorMessageMappingService::map(TagTeamCannotBeRestoredException::notDeleted($tagTeam), RosterEntityType::TagTeam))
        ->toBe('tag-teams.errors.not_deleted');
});

test('it uses a general message for an unmapped business exception', function () {
    $wrestler = new Wrestler(['name' => 'Test Wrestler']);
    $exception = CannotBeDeletedException::alreadyDeleted($wrestler);

    expect(ErrorMessageMappingService::map($exception, RosterEntityType::Wrestler))
        ->toBe('wrestlers.errors.general_error');
});
