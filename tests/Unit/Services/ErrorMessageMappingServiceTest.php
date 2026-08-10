<?php

declare(strict_types=1);

use App\Enums\BusinessRuleReason;
use App\Exceptions\Roster\CannotBeClearedFromInjuryException;
use App\Exceptions\Roster\CannotBeEmployedException;
use App\Exceptions\Roster\CannotBeInjuredException;
use App\Exceptions\Roster\CannotBeReinstatedException;
use App\Exceptions\Roster\CannotBeSuspendedException;
use App\Exceptions\Roster\TagTeams\CannotBeReinstatedException as TagTeamCannotBeReinstatedException;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use App\Services\ErrorMessageMappingService;
use RuntimeException;

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

test('it uses a general message for unknown exceptions', function () {
    expect(ErrorMessageMappingService::mapWrestlerException(new RuntimeException('unexpected wording')))
        ->toBe('wrestlers.errors.general_error');
});
