<?php

declare(strict_types=1);

use App\Livewire\Support\RosterResourceRouteResolver;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

it('resolves a :dataset resource URL', function (Wrestler|TagTeam $rosterMember, string $routeName): void {
    // Arrange
    $resolver = app(RosterResourceRouteResolver::class);

    // Act
    $url = $resolver->urlFor($rosterMember);

    // Assert
    expect($url)->toBe(route($routeName, $rosterMember));
})->with([
    'wrestler' => [
        fn (): Wrestler => Wrestler::factory()->make(['id' => 1]),
        'wrestlers.show',
    ],
    'tag team' => [
        fn (): TagTeam => TagTeam::factory()->make(['id' => 1]),
        'tag-teams.show',
    ],
]);
