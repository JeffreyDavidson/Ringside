<?php

declare(strict_types=1);

use Livewire\Attributes\Locked;

test('component context identifiers are locked', function (string $component, string $property): void {
    if (! class_exists($component)) {
        throw new LogicException("Livewire component {$component} does not exist.");
    }

    $lockedAttributes = new ReflectionProperty($component, $property)->getAttributes(Locked::class);

    expect($lockedAttributes)->toHaveCount(1);
})->with([
    ['App\\Livewire\\Managers\\Tables\\PreviousStables', 'managerId'],
    ['App\\Livewire\\Managers\\Tables\\PreviousTagTeams', 'managerId'],
    ['App\\Livewire\\Managers\\Tables\\PreviousWrestlers', 'managerId'],
    ['App\\Livewire\\Matches\\Modals\\FormModal', 'eventId'],
    ['App\\Livewire\\Matches\\Modals\\ResultModal', 'matchId'],
    ['App\\Livewire\\Matches\\Tables\\MatchesTable', 'eventId'],
    ['App\\Livewire\\Referees\\Tables\\PreviousMatches', 'refereeId'],
    ['App\\Livewire\\Stables\\Tables\\PreviousManagers', 'stableId'],
    ['App\\Livewire\\Stables\\Tables\\PreviousTagTeams', 'stableId'],
    ['App\\Livewire\\Stables\\Tables\\PreviousWrestlers', 'stableId'],
    ['App\\Livewire\\TagTeams\\Tables\\PreviousManagers', 'tagTeamId'],
    ['App\\Livewire\\TagTeams\\Tables\\PreviousMatches', 'tagTeamId'],
    ['App\\Livewire\\TagTeams\\Tables\\PreviousStables', 'tagTeamId'],
    ['App\\Livewire\\TagTeams\\Tables\\PreviousTitleChampionships', 'tagTeamId'],
    ['App\\Livewire\\TagTeams\\Tables\\PreviousWrestlers', 'tagTeamId'],
    ['App\\Livewire\\Titles\\Tables\\PreviousTitleChampionships', 'titleId'],
    ['App\\Livewire\\Venues\\Tables\\PreviousEvents', 'venueId'],
    ['App\\Livewire\\Wrestlers\\Tables\\PreviousManagers', 'wrestlerId'],
    ['App\\Livewire\\Wrestlers\\Tables\\PreviousMatches', 'wrestlerId'],
    ['App\\Livewire\\Wrestlers\\Tables\\PreviousStables', 'wrestlerId'],
    ['App\\Livewire\\Wrestlers\\Tables\\PreviousTagTeams', 'wrestlerId'],
    ['App\\Livewire\\Wrestlers\\Tables\\PreviousTitleChampionships', 'wrestlerId'],
]);
