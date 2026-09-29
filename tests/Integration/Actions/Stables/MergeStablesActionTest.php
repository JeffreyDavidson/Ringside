<?php

declare(strict_types=1);

use App\Actions\Stables\MergeStablesAction;
use App\Exceptions\Roster\Stables\CannotBeMergedException;
use App\Models\Roster\Stables\Stable;
use App\Services\Roster\Stables\StableMembershipService;

it('moves current members to the primary stable and preserves secondary history', function () {
    $primaryStable = Stable::factory()->active()->create();
    $secondaryStable = Stable::factory()->active()->create();
    $primaryWrestlerIds = $primaryStable->currentWrestlers()->pluck('wrestlers.id');
    $primaryTagTeamIds = $primaryStable->currentTagTeams()->pluck('tag_teams.id');
    $secondaryWrestlers = $secondaryStable->currentWrestlers()->get();
    $secondaryTagTeams = $secondaryStable->currentTagTeams()->get();
    $mergeDate = now();

    resolve(MergeStablesAction::class)->handle(
        $primaryStable,
        $secondaryStable,
        $mergeDate,
    );

    expect($primaryStable->currentWrestlers()->pluck('wrestlers.id')->all())
        ->toEqualCanonicalizing($primaryWrestlerIds->merge($secondaryWrestlers->modelKeys())->all())
        ->and($primaryStable->currentTagTeams()->pluck('tag_teams.id')->all())
        ->toEqualCanonicalizing($primaryTagTeamIds->merge($secondaryTagTeams->modelKeys())->all())
        ->and($secondaryStable->currentWrestlers()->exists())
        ->toBeFalse()
        ->and($secondaryStable->currentTagTeams()->exists())
        ->toBeFalse()
        ->and($secondaryStable->previousWrestlers()->pluck('wrestlers.id')->all())
        ->toEqualCanonicalizing($secondaryWrestlers->modelKeys())
        ->and($secondaryStable->previousTagTeams()->pluck('tag_teams.id')->all())
        ->toEqualCanonicalizing($secondaryTagTeams->modelKeys())
        ->and($secondaryStable->currentActivityPeriod()->exists())
        ->toBeFalse()
        ->and(requiredDate($secondaryStable->previousActivityPeriods()->firstOrFail()->ended_at)->format('Y-m-d H:i:s'))
        ->toBe($mergeDate->format('Y-m-d H:i:s'));

    $this->assertSoftDeleted($secondaryStable);
});

it('rejects unavailable secondary members without changing either stable', function () {
    $primaryStable = Stable::factory()->active()->create();
    $secondaryStable = Stable::factory()->active()->create();
    $secondaryWrestler = $secondaryStable->currentWrestlers()->firstOrFail();
    $secondaryWrestler->suspensions()->create(['started_at' => now()]);
    $primaryMemberCount = resolve(StableMembershipService::class)->currentMembers($primaryStable)->getTotalMemberCount();
    $secondaryMemberCount = resolve(StableMembershipService::class)->currentMembers($secondaryStable)->getTotalMemberCount();

    expect(fn () => resolve(MergeStablesAction::class)->handle(
        $primaryStable,
        $secondaryStable,
        now(),
    ))->toThrow(CannotBeMergedException::class)
        ->and(resolve(StableMembershipService::class)->currentMembers($primaryStable)->getTotalMemberCount())->toBe($primaryMemberCount)
        ->and(resolve(StableMembershipService::class)->currentMembers($secondaryStable)->getTotalMemberCount())->toBe($secondaryMemberCount)
        ->and($secondaryStable->currentActivityPeriod()->exists())->toBeTrue()
        ->and($secondaryStable->trashed())->toBeFalse();
});

it('rejects merging stables that are not both active and unretired', function (Closure $makeStables, string $message) {
    [$primaryStable, $secondaryStable] = $makeStables();
    $primaryMemberCount = resolve(StableMembershipService::class)->currentMembers($primaryStable)->getTotalMemberCount();
    $secondaryMemberCount = resolve(StableMembershipService::class)->currentMembers($secondaryStable)->getTotalMemberCount();

    expect(fn () => resolve(MergeStablesAction::class)->handle(
        $primaryStable,
        $secondaryStable,
        now(),
    ))->toThrow(CannotBeMergedException::class, $message)
        ->and(resolve(StableMembershipService::class)->currentMembers($primaryStable)->getTotalMemberCount())->toBe($primaryMemberCount)
        ->and(resolve(StableMembershipService::class)->currentMembers($secondaryStable)->getTotalMemberCount())->toBe($secondaryMemberCount)
        ->and($secondaryStable->fresh()?->trashed())->toBeFalse();
})->with([
    'a stable merged with itself' => [
        function (): array {
            $stable = Stable::factory()->active()->create();

            return [$stable, $stable];
        },
        'cannot be merged with itself',
    ],
    'retired primary' => [
        fn (): array => [Stable::factory()->retired()->create(), Stable::factory()->active()->create()],
        'is retired and cannot receive merged members',
    ],
    'retired secondary' => [
        fn (): array => [Stable::factory()->active()->create(), Stable::factory()->retired()->create()],
        'is retired and cannot be merged',
    ],
    'inactive primary' => [
        fn (): array => [Stable::factory()->inactive()->create(), Stable::factory()->active()->create()],
        'is not currently active and cannot receive merged members',
    ],
    'inactive secondary' => [
        fn (): array => [Stable::factory()->active()->create(), Stable::factory()->inactive()->create()],
        'is not currently active and cannot be merged',
    ],
]);
