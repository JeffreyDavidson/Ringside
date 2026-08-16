<?php

declare(strict_types=1);

use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

test('filters current and previous wrestler memberships', function () {
    $stable = Stable::factory()->create();
    $currentWrestler = Wrestler::factory()->create();
    $previousWrestler = Wrestler::factory()->create();
    $stable->wrestlers()->attach($currentWrestler, ['joined_at' => now()]);
    $stable->wrestlers()->attach($previousWrestler, [
        'joined_at' => now()->subDays(10),
        'left_at' => now()->subDay(),
    ]);

    expect($stable->wrestlers)->toHaveCount(2)
        ->and($stable->currentWrestlers->modelKeys())->toBe([$currentWrestler->getKey()])
        ->and($stable->previousWrestlers->modelKeys())->toBe([$previousWrestler->getKey()]);
});

test('filters current and previous tag team memberships', function () {
    $stable = Stable::factory()->create();
    $currentTagTeam = TagTeam::factory()->create();
    $previousTagTeam = TagTeam::factory()->create();
    $stable->tagTeams()->attach($currentTagTeam, ['joined_at' => now()]);
    $stable->tagTeams()->attach($previousTagTeam, [
        'joined_at' => now()->subDays(10),
        'left_at' => now()->subDay(),
    ]);

    expect($stable->tagTeams)->toHaveCount(2)
        ->and($stable->currentTagTeams->modelKeys())->toBe([$currentTagTeam->getKey()])
        ->and($stable->previousTagTeams->modelKeys())->toBe([$previousTagTeam->getKey()]);
});

test('allows a stable to exist without members', function () {
    $stable = Stable::factory()->create();

    expect($stable->wrestlers)->toBeEmpty()
        ->and($stable->tagTeams)->toBeEmpty();
});
