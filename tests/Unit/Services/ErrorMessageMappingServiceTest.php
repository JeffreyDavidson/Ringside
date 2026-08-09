<?php

declare(strict_types=1);

use App\Exceptions\Roster\CannotBeReinstatedException;
use App\Exceptions\Roster\TagTeams\CannotBeReinstatedException as TagTeamCannotBeReinstatedException;
use App\Services\ErrorMessageMappingService;

test('it maps injured roster reinstatement failures to healing guidance', function () {
    $exception = new CannotBeReinstatedException('The roster member is injured and requires medical clearance.');

    expect(ErrorMessageMappingService::mapWrestlerException($exception))
        ->toBe('wrestlers.errors.cannot_reinstate_injured')
        ->and(ErrorMessageMappingService::mapManagerException($exception))
        ->toBe('managers.errors.cannot_reinstate_injured')
        ->and(ErrorMessageMappingService::mapRefereeException($exception))
        ->toBe('referees.errors.cannot_reinstate_injured');
});

test('it maps available roster reinstatement failures to suspension guidance', function () {
    $exception = new CannotBeReinstatedException('The roster member is already available and does not need reinstatement.');

    expect(ErrorMessageMappingService::mapWrestlerException($exception))
        ->toBe('wrestlers.errors.not_suspended')
        ->and(ErrorMessageMappingService::mapManagerException($exception))
        ->toBe('managers.errors.not_suspended')
        ->and(ErrorMessageMappingService::mapRefereeException($exception))
        ->toBe('referees.errors.not_suspended');
});

test('it maps tag team reinstatement failures from the tag team exception', function () {
    $exception = new TagTeamCannotBeReinstatedException('The tag team is not suspended.');

    expect(ErrorMessageMappingService::mapTagTeamException($exception))
        ->toBe('tag-teams.errors.not_suspended');
});
