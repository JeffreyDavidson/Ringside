<?php

declare(strict_types=1);

use App\Lifecycle\Roster\Booking\IndividualRosterBookingStrategy;
use App\Lifecycle\Roster\Booking\RosterBookingStrategyResolver;
use App\Lifecycle\Roster\Booking\TagTeamRosterBookingStrategy;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

test('it resolves individual roster booking strategies', function (Referee|Wrestler $rosterMember) {
    expect(app(RosterBookingStrategyResolver::class)->resolve($rosterMember))
        ->toBeInstanceOf(IndividualRosterBookingStrategy::class);
})->with([
    'wrestler' => fn () => Wrestler::factory()->make(),
    'referee' => fn () => Referee::factory()->make(),
]);

test('it resolves tag team roster booking strategies', function () {
    expect(app(RosterBookingStrategyResolver::class)->resolve(TagTeam::factory()->make()))
        ->toBeInstanceOf(TagTeamRosterBookingStrategy::class);
});
