<?php

declare(strict_types=1);

use App\Lifecycle\Roster\Stables\StableFormerMemberEligibility;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

test('retired employed former members are unavailable', function () {
    $stable = Stable::factory()->retired()->create();
    $wrestler = Wrestler::factory()->employed()->create();
    $wrestler->retirements()->create(['started_at' => now()]);
    $tagTeam = TagTeam::factory()->employed()->create();
    $tagTeam->retirements()->create(['started_at' => now()]);

    $stable->wrestlers()->attach($wrestler, [
        'joined_at' => now()->subMonth(),
        'left_at' => now()->subWeek(),
    ]);
    $stable->tagTeams()->attach($tagTeam, [
        'joined_at' => now()->subMonth(),
        'left_at' => now()->subWeek(),
    ]);

    $eligibility = resolve(StableFormerMemberEligibility::class);
    $availableFormerMembers = $eligibility->availableFor($stable);
    $unavailableFormerMembers = $eligibility->unavailableKeyMembersFor($stable);

    expect($availableFormerMembers->contains(fn ($member): bool => $member->is($wrestler)))->toBeFalse()
        ->and($availableFormerMembers->contains(fn ($member): bool => $member->is($tagTeam)))->toBeFalse()
        ->and($unavailableFormerMembers->contains(fn ($member): bool => $member->is($wrestler)))->toBeTrue()
        ->and($unavailableFormerMembers->contains(fn ($member): bool => $member->is($tagTeam)))->toBeTrue();
});

test('employed healthy former members are available', function () {
    $stable = Stable::factory()->retired()->create();
    $wrestler = Wrestler::factory()->employed()->create();
    $tagTeam = TagTeam::factory()->employed()->create();

    $stable->wrestlers()->attach($wrestler, [
        'joined_at' => now()->subMonth(),
        'left_at' => now()->subWeek(),
    ]);
    $stable->tagTeams()->attach($tagTeam, [
        'joined_at' => now()->subMonth(),
        'left_at' => now()->subWeek(),
    ]);

    $eligibility = resolve(StableFormerMemberEligibility::class);

    expect($eligibility->availableFor($stable)->contains(fn ($member): bool => $member->is($wrestler)))->toBeTrue()
        ->and($eligibility->availableFor($stable)->contains(fn ($member): bool => $member->is($tagTeam)))->toBeTrue()
        ->and($eligibility->unavailableKeyMembersFor($stable))->toBeEmpty();
});

test('future-employed former members are not available yet', function () {
    $stable = Stable::factory()->retired()->create();
    $wrestler = Wrestler::factory()->create();
    $wrestler->employments()->create(['started_at' => now()->addDay()]);
    $tagTeam = TagTeam::factory()->create();
    $tagTeam->employments()->create(['started_at' => now()->addDay()]);

    $stable->wrestlers()->attach($wrestler, [
        'joined_at' => now()->subMonth(),
        'left_at' => now()->subWeek(),
    ]);
    $stable->tagTeams()->attach($tagTeam, [
        'joined_at' => now()->subMonth(),
        'left_at' => now()->subWeek(),
    ]);

    $eligibility = resolve(StableFormerMemberEligibility::class);
    $availableFormerMembers = $eligibility->availableFor($stable);

    expect($availableFormerMembers->contains(fn ($member): bool => $member->is($wrestler)))->toBeFalse()
        ->and($availableFormerMembers->contains(fn ($member): bool => $member->is($tagTeam)))->toBeFalse();
});
