<?php

declare(strict_types=1);

use App\Data\Stables\StableMembershipData;
use App\Enums\Stables\StableMemberUnavailability;
use App\Exceptions\Roster\Stables\CannotBeSplitException;
use App\Lifecycle\Roster\Stables\StableFormerMemberEligibility;
use App\Lifecycle\Roster\Stables\StableRestructuringEligibility;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Collection;

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
        expect($members->wrestlers->pluck('id')->all())->toEqualCanonicalizing($stable->previousWrestlers()->pluck('wrestlers.id')->unique()->values()->all())
            ->and($members->wrestlers->where('id', $rejoiner->id))->toHaveCount(1);
    });
});

/**
 * An active stable holding bookable wrestlers and employed tag teams directly.
 *
 * @return array{stable: Stable, wrestlers: Collection<int, Wrestler>, tagTeams: Collection<int, TagTeam>}
 */
function stableReadyToSplit(int $wrestlerCount = 4, int $tagTeamCount = 2): array
{
    $stable = Stable::factory()->create();
    $stable->activityPeriods()->create(['started_at' => now()->subDays(2)]);
    $wrestlers = Wrestler::factory()->bookable()->count($wrestlerCount)->create();
    $tagTeams = TagTeam::factory()->employed()->count($tagTeamCount)->create();
    $stable->wrestlers()->attach($wrestlers->modelKeys(), ['joined_at' => now()->subDay()]);
    $stable->tagTeams()->attach($tagTeams->modelKeys(), ['joined_at' => now()->subDay()]);

    return ['stable' => $stable, 'wrestlers' => $wrestlers, 'tagTeams' => $tagTeams];
}

describe('split member validation', function (): void {
    it('accepts a split that leaves both stables with enough members', function (): void {
        // Arrange
        ['stable' => $stable, 'wrestlers' => $wrestlers, 'tagTeams' => $tagTeams] = stableReadyToSplit();
        $moving = new StableMembershipData(wrestlers: $wrestlers->take(2), tagTeams: $tagTeams->take(1));

        // Act
        $act = fn () => resolve(StableRestructuringEligibility::class)->ensureSplitMembersValid($stable, $moving);

        // Assert
        expect($act)->not->toThrow(CannotBeSplitException::class);
    });

    it('refuses an invalid split with the reason, in the order the checks run', function (Closure $makeCase): void {
        // Arrange
        ['stable' => $stable, 'moving' => $moving, 'message' => $message] = $makeCase();

        // Act
        $act = fn () => resolve(StableRestructuringEligibility::class)->ensureSplitMembersValid($stable, $moving);

        // Assert
        expect($act)->toThrow(CannotBeSplitException::class, $message);
    })->with([
        'nobody is moving' => [
            function (): array {
                ['stable' => $stable] = stableReadyToSplit();

                return ['stable' => $stable, 'moving' => new StableMembershipData, 'message' => 'Cannot split stable: at least one member must be moved to the new stable.'];
            },
        ],
        'a wrestler and a tag team from outside the stable' => [
            function (): array {
                ['stable' => $stable, 'wrestlers' => $wrestlers] = stableReadyToSplit();
                $outsider = Wrestler::factory()->bookable()->create();
                $outsiderTeam = TagTeam::factory()->employed()->create();
                $moving = new StableMembershipData(wrestlers: $wrestlers->take(2)->push($outsider), tagTeams: new Collection([$outsiderTeam]));

                return ['stable' => $stable, 'moving' => $moving, 'message' => "Cannot split stable: these selected members do not belong to the original stable: {$outsider->name}, {$outsiderTeam->name}."];
            },
        ],
        'an outsider who is also unavailable' => [
            function (): array {
                ['stable' => $stable, 'wrestlers' => $wrestlers] = stableReadyToSplit();
                $outsider = Wrestler::factory()->unemployed()->create();
                $moving = new StableMembershipData(wrestlers: $wrestlers->take(2)->push($outsider));

                return ['stable' => $stable, 'moving' => $moving, 'message' => "do not belong to the original stable: {$outsider->name}."];
            },
        ],
        'an unavailable member' => [
            function (): array {
                ['stable' => $stable, 'wrestlers' => $wrestlers, 'tagTeams' => $tagTeams] = stableReadyToSplit();
                $unavailable = $wrestlers->firstOrFail();
                $unavailable->currentEmployment()->update(['ended_at' => now()]);
                $moving = new StableMembershipData(wrestlers: $wrestlers->take(2), tagTeams: $tagTeams->take(1));

                return ['stable' => $stable, 'moving' => $moving, 'message' => "Cannot split stable: these selected members are unavailable: {$unavailable->name}."];
            },
        ],
        'an unavailable member who also separates a tag team' => [
            function (): array {
                ['stable' => $stable] = stableReadyToSplit(0, 0);
                $tagTeam = TagTeam::factory()->employed()->create();
                $shared = Wrestler::query()->whereKey($tagTeam->currentWrestlers()->firstOrFail()->getKey())->sole();
                $shared->currentEmployment()->update(['ended_at' => now()]);
                $stable->tagTeams()->attach($tagTeam, ['joined_at' => now()->subDay()]);
                $stable->wrestlers()->attach($shared, ['joined_at' => now()->subDay()]);
                $moving = new StableMembershipData(wrestlers: new Collection([$shared]));

                return ['stable' => $stable, 'moving' => $moving, 'message' => "these selected members are unavailable: {$shared->name}."];
            },
        ],
        'a tag team moving without its direct wrestler' => [
            function (): array {
                ['stable' => $stable, 'wrestlers' => $wrestlers] = stableReadyToSplit(4, 0);
                $tagTeam = TagTeam::factory()->employed()->create();
                $shared = Wrestler::query()->whereKey($tagTeam->currentWrestlers()->firstOrFail()->getKey())->sole();
                $stable->tagTeams()->attach($tagTeam, ['joined_at' => now()->subDay()]);
                $stable->wrestlers()->attach($shared, ['joined_at' => now()->subDay()]);
                $moving = new StableMembershipData(wrestlers: $wrestlers->take(2), tagTeams: new Collection([$tagTeam]));

                return ['stable' => $stable, 'moving' => $moving, 'message' => "tag team '{$tagTeam->name}' and its wrestlers ({$shared->name}) must move together"];
            },
        ],
        'every member moving' => [
            function (): array {
                ['stable' => $stable, 'wrestlers' => $wrestlers, 'tagTeams' => $tagTeams] = stableReadyToSplit();

                return ['stable' => $stable, 'moving' => new StableMembershipData(wrestlers: $wrestlers, tagTeams: $tagTeams), 'message' => 'Cannot split stable: at least one member must remain in the original stable.'];
            },
        ],
        'a new stable below the minimum' => [
            function (): array {
                ['stable' => $stable, 'wrestlers' => $wrestlers] = stableReadyToSplit();

                return ['stable' => $stable, 'moving' => new StableMembershipData(wrestlers: $wrestlers->take(2)), 'message' => 'the new stable would have 2 members but requires at least 3.'];
            },
        ],
        'an original stable below the minimum' => [
            function (): array {
                ['stable' => $stable, 'wrestlers' => $wrestlers, 'tagTeams' => $tagTeams] = stableReadyToSplit();

                return ['stable' => $stable, 'moving' => new StableMembershipData(wrestlers: $wrestlers, tagTeams: $tagTeams->take(1)), 'message' => 'the original stable would have 2 members but requires at least 3.'];
            },
        ],
        'both stables below the minimum' => [
            function (): array {
                ['stable' => $stable, 'wrestlers' => $wrestlers] = stableReadyToSplit(4, 0);

                return ['stable' => $stable, 'moving' => new StableMembershipData(wrestlers: $wrestlers->take(2)), 'message' => 'the new stable would have 2 members'];
            },
        ],
    ]);
});
