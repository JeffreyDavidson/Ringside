<?php

declare(strict_types=1);

use App\Data\Stables\StableMembershipData;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\Roster\Stables\StableMembershipService;

test('it returns only current stable members with weighted headcount', function (): void {
    $stable = Stable::factory()->create();
    $historicalWrestler = Wrestler::factory()->create();
    $currentWrestlers = Wrestler::factory()->count(2)->create();
    $currentTagTeam = TagTeam::factory()->create();

    $stable->wrestlers()->attach($historicalWrestler, [
        'joined_at' => now()->subMonths(2),
        'left_at' => now()->subMonth(),
    ]);
    $stable->wrestlers()->attach($currentWrestlers, [
        'joined_at' => now()->subWeek(),
        'left_at' => null,
    ]);
    $stable->tagTeams()->attach($currentTagTeam, [
        'joined_at' => now()->subWeek(),
        'left_at' => null,
    ]);

    $members = resolve(StableMembershipService::class)->currentMembers($stable);

    $wrestlerIds = $members->wrestlers?->pluck('id')->all() ?? [];
    $tagTeamIds = $members->tagTeams?->pluck('id')->all() ?? [];

    expect($members)->toBeInstanceOf(StableMembershipData::class)
        ->and($wrestlerIds)->toEqualCanonicalizing($currentWrestlers->modelKeys())
        ->and($tagTeamIds)->toBe([$currentTagTeam->id])
        ->and($members->getTotalMemberCount())->toBe(4)
        ->and($members->hasMinimumMembers())->toBeTrue();
});

test('it returns empty membership data for a stable without current members', function (): void {
    $stable = Stable::factory()->create();

    $members = resolve(StableMembershipService::class)->currentMembers($stable);

    expect($members->isEmpty())->toBeTrue()
        ->and($members->getTotalMemberCount())->toBe(0)
        ->and($members->hasMinimumMembers())->toBeFalse();
});
