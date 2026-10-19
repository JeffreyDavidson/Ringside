<?php

declare(strict_types=1);

use App\Actions\Stables\RetireAction;
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

    expect($availableFormerMembers->contains(fn ($member): bool => $member->is($wrestler)))->toBeFalse()
        ->and($availableFormerMembers->contains(fn ($member): bool => $member->is($tagTeam)))->toBeFalse();
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
        ->and($eligibility->availableFor($stable)->contains(fn ($member): bool => $member->is($tagTeam)))->toBeTrue();
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

test('only members who left at the retirement are available to return on unretirement', function () {
    // Arrange
    $stable = Stable::factory()
        ->retired()
        ->create();
    $earlierMember = Wrestler::factory()
        ->employed()
        ->create();
    $stable->wrestlers()->attach($earlierMember, [
        'joined_at' => now()->subYears(3),
        'left_at' => now()->subYears(2),
    ]);

    // Act
    $returning = resolve(StableFormerMemberEligibility::class)->availableMembersAtRetirementFor($stable);

    // Assert
    expect($returning->wrestlers)->toHaveCount(2)
        ->and(collect($returning->wrestlers)->contains(fn (Wrestler $wrestler): bool => $wrestler->is($earlierMember)))->toBeFalse()
        ->and($returning->tagTeams)->toHaveCount(1);
});

test('members who left when a future-dated retirement was recorded are available to return', function () {
    // Arrange
    $this->travelTo(now()->startOfSecond());
    $stable = Stable::factory()
        ->active()
        ->create();
    resolve(RetireAction::class)->handle($stable, now()->addMonth());

    // Act
    $returning = resolve(StableFormerMemberEligibility::class)->availableMembersAtRetirementFor($stable);

    // Assert
    expect($returning->wrestlers)->toHaveCount(2)
        ->and($returning->tagTeams)->toHaveCount(1);
});
