<?php

declare(strict_types=1);

use App\Actions\Stables\DisbandAction;
use App\Actions\Stables\RetireAction;
use App\Actions\Stables\ReuniteAction;
use App\Actions\Stables\UnretireAction;
use App\Data\Stables\StableMembershipData;
use App\Lifecycle\Roster\Stables\StableFormerMemberEligibility;
use App\Models\Lifecycle\Retirement;
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

test('a stable knows whether it was active when it retired', function (Closure $makeRetiredStable, bool $expected): void {
    // Arrange
    $this->travelTo(now()->startOfSecond());
    $stable = $makeRetiredStable();

    // Act
    $wasActive = resolve(StableFormerMemberEligibility::class)->wasActiveWhenRetired($stable);

    // Assert
    expect($wasActive)->toBe($expected);
})->with([
    'active, then retired' => [
        function (): Stable {
            $stable = Stable::factory()->active()->create();
            resolve(RetireAction::class)->handle($stable, now());

            return $stable;
        },
        true,
    ],
    'active, then retired in the future' => [
        function (): Stable {
            $stable = Stable::factory()->active()->create();
            resolve(RetireAction::class)->handle($stable, now()->addMonth());

            return $stable;
        },
        true,
    ],
    'disbanded, then retired later' => [
        function (): Stable {
            $stable = Stable::factory()->active()->create();
            resolve(DisbandAction::class)->handle($stable, now()->subHours(2));
            resolve(RetireAction::class)->handle($stable, now()->subHour());

            return $stable;
        },
        false,
    ],
    'disbanded, then retired at the same moment' => [
        function (): Stable {
            $stable = Stable::factory()->active()->create();
            resolve(DisbandAction::class)->handle($stable, now()->subHours(2));
            resolve(RetireAction::class)->handle($stable, now()->subHours(2));

            return $stable;
        },
        false,
    ],
    'disbanded, then retired in the future' => [
        function (): Stable {
            $stable = Stable::factory()->active()->create();
            resolve(DisbandAction::class)->handle($stable, now()->subHours(2));
            resolve(RetireAction::class)->handle($stable, now()->addMonth());

            return $stable;
        },
        false,
    ],
    'disbanded, reunited, then retired' => [
        function (): Stable {
            $stable = Stable::factory()->active()->create();
            $members = new StableMembershipData($stable->currentWrestlers()->get(), $stable->currentTagTeams()->get());
            resolve(DisbandAction::class)->handle($stable, now()->subHours(3));
            resolve(ReuniteAction::class)->handle($stable, $members, now()->subHours(2));
            resolve(RetireAction::class)->handle($stable, now()->subHour());

            return $stable;
        },
        true,
    ],
    'disbanded, retired, unretired, then retired again' => [
        function (): Stable {
            $stable = Stable::factory()->active()->create();
            resolve(DisbandAction::class)->handle($stable, now()->subHours(4));
            resolve(RetireAction::class)->handle($stable, now()->subHours(3));
            resolve(UnretireAction::class)->handle($stable, now()->subHours(2));
            resolve(RetireAction::class)->handle($stable, now()->subHour());

            return $stable;
        },
        false,
    ],
    'retired without ever being active' => [
        fn (): Stable => Stable::factory()
            ->has(Retirement::factory()->started(now()->subDay()), 'retirements')
            ->create(),
        false,
    ],
    'retired without ever being active, dated in the future' => [
        fn (): Stable => Stable::factory()
            ->has(Retirement::factory()->started(now()->addDay()), 'retirements')
            ->create(),
        false,
    ],
]);
