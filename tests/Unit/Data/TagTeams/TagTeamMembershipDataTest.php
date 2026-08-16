<?php

declare(strict_types=1);

use App\Data\TagTeams\TagTeamMembershipData;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Collection;

test('calculates the combined wrestler weight in pounds', function () {
    $members = new TagTeamMembershipData(new Collection([
        Wrestler::factory()->make(['weight' => 225]),
        Wrestler::factory()->make(['weight' => 275]),
    ]));

    expect($members->combinedWeightInPounds())->toBe(500);
});

test('returns zero without wrestlers', function () {
    $members = new TagTeamMembershipData();

    expect($members->combinedWeightInPounds())->toBe(0);
});
