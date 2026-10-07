<?php

declare(strict_types=1);

use App\Data\TagTeams\TagTeamMembershipData;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Collection;

test('creates membership data from two wrestlers and their managers', function () {
    [$wrestlerA, $wrestlerB] = Wrestler::factory()->count(2)->make()->all();
    $manager = Manager::factory()->make();

    $members = TagTeamMembershipData::fromWrestlers(
        $wrestlerA,
        $wrestlerB,
        new Collection([$manager]),
    );

    expect($members->wrestlers?->all())->toBe([$wrestlerA, $wrestlerB])
        ->and($members->managers?->all())->toBe([$manager]);
});
