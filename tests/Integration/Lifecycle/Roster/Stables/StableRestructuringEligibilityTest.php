<?php

declare(strict_types=1);

use App\Enums\Stables\StableMemberUnavailability;
use App\Lifecycle\Roster\Stables\StableFormerMemberEligibility;
use App\Lifecycle\Roster\Stables\StableRestructuringEligibility;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

describe('member unavailability', function (): void {
    it('explains why a member cannot move between stables', function (Closure $makeMember, ?StableMemberUnavailability $expected): void {
        // Arrange
        $member = $makeMember();

        // Act
        $unavailability = resolve(StableRestructuringEligibility::class)->unavailabilityOf($member);

        // Assert
        expect($unavailability)->toBe($expected);
    })->with([
        'unemployed wrestler' => [fn (): Wrestler => Wrestler::factory()->unemployed()->create(), StableMemberUnavailability::Unemployed],
        'unemployed tag team' => [fn (): TagTeam => TagTeam::factory()->unemployed()->create(), StableMemberUnavailability::Unemployed],
        'retired wrestler' => [fn (): Wrestler => Wrestler::factory()->retired()->create(), StableMemberUnavailability::Retired],
        'suspended wrestler' => [fn (): Wrestler => Wrestler::factory()->suspended()->create(), StableMemberUnavailability::Suspended],
        'injured wrestler' => [fn (): Wrestler => Wrestler::factory()->injured()->create(), StableMemberUnavailability::Injured],
        'available wrestler' => [fn (): Wrestler => Wrestler::factory()->employed()->create(), null],
        'available tag team' => [fn (): TagTeam => TagTeam::factory()->employed()->create(), null],
    ]);

    it('labels every unavailability reason', function (StableMemberUnavailability $unavailability, string $label): void {
        expect($unavailability->label())->toBe($label);
    })->with([
        [StableMemberUnavailability::Unemployed, 'Not employed'],
        [StableMemberUnavailability::Injured, 'Injured'],
        [StableMemberUnavailability::Suspended, 'Suspended'],
        [StableMemberUnavailability::Retired, 'Retired'],
    ]);
});

describe('restructuring availability', function (): void {
    it('allows splitting only an active unretired stable with enough members', function (Closure $makeStable, bool $expected): void {
        // Arrange
        $stable = $makeStable();

        // Act
        $canSplit = resolve(StableRestructuringEligibility::class)->canSplit($stable);

        // Assert
        expect($canSplit)->toBe($expected);
    })->with([
        'active with six members' => [
            function (): Stable {
                $stable = Stable::factory()->active()->create();
                $stable->wrestlers()->attach(Wrestler::factory()->employed()->count(2)->create(), ['joined_at' => now()->subDay()]);

                return $stable;
            },
            true,
        ],
        'active with four members' => [fn (): Stable => Stable::factory()->active()->create(), false],
        'disbanded' => [fn (): Stable => Stable::factory()->inactive()->create(), false],
        'retired' => [fn (): Stable => Stable::factory()->retired()->create(), false],
    ]);

    it('allows starting a merge only for an active unretired stable', function (Closure $makeStable, bool $expected): void {
        // Arrange
        $stable = $makeStable();

        // Act
        $canStartMerge = resolve(StableRestructuringEligibility::class)->canStartMerge($stable);

        // Assert
        expect($canStartMerge)->toBe($expected);
    })->with([
        'active' => [fn (): Stable => Stable::factory()->active()->create(), true],
        'disbanded' => [fn (): Stable => Stable::factory()->inactive()->create(), false],
        'retired' => [fn (): Stable => Stable::factory()->retired()->create(), false],
        'unformed' => [fn (): Stable => Stable::factory()->withNoMembers()->create(), false],
    ]);
});

describe('former members', function (): void {
    it('lists a member who joined and left several times only once', function (): void {
        // Arrange
        $stable = Stable::factory()->disbanded()->create();
        $rejoiner = $stable->previousWrestlers()->firstOrFail();
        $stable->wrestlers()->attach($rejoiner, [
            'joined_at' => now()->subHours(20),
            'left_at' => now()->subHours(10),
        ]);

        // Act
        $members = resolve(StableFormerMemberEligibility::class)->availableMembersFor($stable);

        // Assert
        expect($members->wrestlers?->pluck('id')->all())->toEqualCanonicalizing($stable->previousWrestlers()->pluck('wrestlers.id')->unique()->all())
            ->and($members->wrestlers?->where('id', $rejoiner->id))->toHaveCount(1);
    });
});
